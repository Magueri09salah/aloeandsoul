<?php

namespace App\Services\Inventory;

use App\Enums\InventoryReason;
use App\Exceptions\DomainException;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * All stock changes go through here so every change is atomic and recorded
 * in the inventory_movements ledger.
 *
 * Methods that take a variant expect to be called inside a transaction in
 * which that variant row is already locked (SELECT … FOR UPDATE).
 */
class InventoryService
{
    /** Decrement stock for an order. Fails if not enough stock. */
    public function reserve(ProductVariant $variant, int $quantity, Order $order): void
    {
        if (! $variant->track_stock) {
            return;
        }

        // Conditional update is the final guard against overselling, even if
        // a caller forgot to lock the row.
        $affected = ProductVariant::query()
            ->whereKey($variant->id)
            ->where('stock', '>=', $quantity)
            ->decrement('stock', $quantity);

        if ($affected !== 1) {
            throw new DomainException('Not enough stock for '.$variant->displayName().'.', 'cart');
        }

        $variant->stock -= $quantity;
        $this->record($variant, -$quantity, InventoryReason::OrderPlaced, $order);
    }

    /** Put units back (cancellation or return). */
    public function release(ProductVariant $variant, int $quantity, InventoryReason $reason, ?Order $order = null, ?User $user = null): void
    {
        if (! $variant->track_stock || $quantity < 1) {
            return;
        }

        ProductVariant::query()->whereKey($variant->id)->increment('stock', $quantity);
        $variant->stock += $quantity;
        $this->record($variant, $quantity, $reason, $order, $user);
    }

    /**
     * Manual stock correction by an admin: set an absolute quantity.
     */
    public function setStock(ProductVariant $variant, int $newStock, User $user, ?string $note = null): ?InventoryMovement
    {
        if ($newStock < 0) {
            throw new DomainException('Stock cannot be negative.', 'stock');
        }

        return DB::transaction(function () use ($variant, $newStock, $user, $note) {
            /** @var ProductVariant $locked */
            $locked = ProductVariant::query()->with('product')->lockForUpdate()->findOrFail($variant->id);
            $delta = $newStock - $locked->stock;

            if ($delta === 0) {
                return null;
            }

            $locked->forceFill(['stock' => $newStock])->save();
            $reason = $delta > 0 ? InventoryReason::Restock : InventoryReason::Adjustment;
            $variant->stock = $newStock;

            return $this->record($locked, $delta, $reason, null, $user, $note);
        });
    }

    private function record(ProductVariant $variant, int $change, InventoryReason $reason, ?Order $order = null, ?User $user = null, ?string $note = null): InventoryMovement
    {
        return InventoryMovement::query()->create([
            'product_variant_id' => $variant->id,
            'change' => $change,
            'stock_after' => $variant->stock,
            'reason' => $reason,
            'order_id' => $order?->id,
            'user_id' => $user?->id,
            'note' => $note,
        ]);
    }
}
