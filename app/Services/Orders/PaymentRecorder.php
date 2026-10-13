<?php

namespace App\Services\Orders;

use App\Enums\OrderEventType;
use App\Enums\PaymentStatus;
use App\Exceptions\DomainException;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

/**
 * Records cash collection by the courier, independently of fulfilment status.
 */
class PaymentRecorder
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function record(Order $order, PaymentStatus $to, User $actor, ?int $amountMinor = null, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $to, $actor, $amountMinor, $note) {
            /** @var Order $locked */
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            $from = $locked->payment_status;

            if (! $from->canChangeTo($to, $locked->status)) {
                throw new DomainException("Payment cannot be marked “{$to->label()}” while the order is “{$locked->status->label()}”.", 'payment_status');
            }

            $locked->payment_status = $to;

            if ($to === PaymentStatus::Collected) {
                $amount = $amountMinor ?? $locked->total_minor;
                if ($amount < 1) {
                    throw new DomainException('Enter the amount collected.', 'amount');
                }
                $locked->payment_collected_minor = $amount;
                $locked->payment_collected_at = now();
                $locked->payment_recorded_by = $actor->id;
            } elseif ($to === PaymentStatus::Pending || $to === PaymentStatus::NotCollected) {
                $locked->payment_collected_minor = null;
                $locked->payment_collected_at = null;
                $locked->payment_recorded_by = null;
            }

            $locked->save();

            $summary = $to->label();
            if ($to === PaymentStatus::Collected) {
                $summary .= ': '.Money::format((int) $locked->payment_collected_minor, $locked->currency);
                if ($locked->payment_collected_minor !== $locked->total_minor) {
                    $summary .= ' (amount due was '.Money::format($locked->total_minor, $locked->currency).')';
                }
            }

            OrderEvent::query()->create([
                'order_id' => $locked->id,
                'type' => OrderEventType::Payment,
                'from_status' => $from->value,
                'to_status' => $to->value,
                'note' => trim($summary.'. '.($note ?? '')),
                'user_id' => $actor->id,
            ]);

            $this->audit->log('order.payment_recorded', $locked, [
                'from' => $from->value,
                'to' => $to->value,
                'amount_minor' => $locked->payment_collected_minor,
            ], $actor->id);

            return $locked;
        });
    }
}
