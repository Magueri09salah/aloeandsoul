<?php

namespace App\Services\Orders;

use App\Enums\InventoryReason;
use App\Enums\OrderEventType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\DomainException;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Moves orders through their lifecycle and applies the side effects of each
 * transition (stock release, coupon release, payment status) atomically.
 */
class OrderWorkflow
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  bool  $restock  for returns: put goods back into sellable stock
     * @param  bool  $collectPayment  for deliveries: also record cash collected
     */
    public function transition(Order $order, OrderStatus $to, User $actor, ?string $note = null, bool $restock = true, bool $collectPayment = false): Order
    {
        $updated = DB::transaction(function () use ($order, $to, $actor, $note, $restock, $collectPayment) {
            /** @var Order $locked */
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            $from = $locked->status;

            if (! $from->canTransitionTo($to)) {
                throw new DomainException("An order that is “{$from->label()}” cannot be moved to “{$to->label()}”.", 'status');
            }

            $now = now();
            $locked->status = $to;

            match ($to) {
                OrderStatus::Confirmed => $locked->confirmed_at ??= $now,
                OrderStatus::Shipped => $locked->shipped_at = $now,
                OrderStatus::Delivered => $locked->delivered_at = $now,
                OrderStatus::Cancelled => $locked->cancelled_at = $now,
                default => null,
            };

            if ($to === OrderStatus::Cancelled) {
                $this->releaseStock($locked, InventoryReason::OrderCancelled, $actor);
                $this->releaseCoupon($locked);
            }

            if ($to === OrderStatus::Returned && $restock) {
                $this->releaseStock($locked, InventoryReason::OrderReturned, $actor);
            }

            if (in_array($to, [OrderStatus::Cancelled, OrderStatus::Returned], true) && $locked->payment_status === PaymentStatus::Pending) {
                $locked->payment_status = PaymentStatus::NotCollected;
            }

            $locked->save();

            $eventNote = $note;
            if ($to === OrderStatus::Returned) {
                $eventNote = trim(($restock ? 'Items returned to stock.' : 'Items not returned to stock.').' '.($note ?? ''));
            }

            OrderEvent::query()->create([
                'order_id' => $locked->id,
                'type' => OrderEventType::Status,
                'from_status' => $from->value,
                'to_status' => $to->value,
                'note' => $eventNote ?: null,
                'user_id' => $actor->id,
            ]);

            $this->audit->log('order.status_changed', $locked, [
                'from' => $from->value,
                'to' => $to->value,
                'restock' => $to === OrderStatus::Returned ? $restock : null,
            ], $actor->id);

            if ($to === OrderStatus::Delivered && $collectPayment) {
                app(PaymentRecorder::class)->record($locked, PaymentStatus::Collected, $actor);
            }

            return $locked;
        });

        Log::info('order.status_changed', ['order_id' => $updated->id, 'to' => $to->value, 'user_id' => $actor->id]);

        return $updated->refresh();
    }

    public function addNote(Order $order, User $actor, string $note): OrderEvent
    {
        return OrderEvent::query()->create([
            'order_id' => $order->id,
            'type' => OrderEventType::Note,
            'note' => $note,
            'user_id' => $actor->id,
        ]);
    }

    public function updateDelivery(Order $order, User $actor, ?string $courier, ?string $tracking): void
    {
        DB::transaction(function () use ($order, $actor, $courier, $tracking) {
            $order->fill(['courier_name' => $courier, 'tracking_number' => $tracking]);
            if (! $order->isDirty()) {
                return;
            }
            $changes = AuditLogger::diff($order);
            $order->save();

            OrderEvent::query()->create([
                'order_id' => $order->id,
                'type' => OrderEventType::Delivery,
                'note' => trim(implode(' · ', array_filter([
                    $courier ? "Courier: {$courier}" : null,
                    $tracking ? "Tracking: {$tracking}" : null,
                ]))) ?: 'Delivery details cleared.',
                'user_id' => $actor->id,
            ]);
            $this->audit->log('order.delivery_updated', $order, $changes, $actor->id);
        });
    }

    private function releaseStock(Order $order, InventoryReason $reason, User $actor): void
    {
        $items = $order->items()->get();
        $variants = ProductVariant::query()
            ->whereIn('id', $items->pluck('product_variant_id'))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($items as $item) {
            $variant = $variants->get($item->product_variant_id);
            if ($variant !== null) {
                $this->inventory->release($variant, $item->quantity, $reason, $order, $actor);
            }
        }
    }

    private function releaseCoupon(Order $order): void
    {
        $redemption = $order->couponRedemption()->whereNull('released_at')->first();
        if ($redemption === null) {
            return;
        }

        $redemption->update(['released_at' => now()]);
        Coupon::query()->whereKey($redemption->coupon_id)->where('uses_count', '>', 0)->decrement('uses_count');
    }
}
