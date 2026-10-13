<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Services\Audit\AuditLogger;
use App\Services\Inventory\InventoryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize(Permission::ViewCatalog->value);

        $threshold = (int) settings()->get('store.low_stock_threshold', 5);
        $filters = [
            'q' => is_string($request->query('q')) ? mb_substr(trim($request->query('q')), 0, 100) : '',
            'level' => in_array($request->query('level'), ['low', 'out'], true) ? $request->query('level') : null,
        ];

        $variants = ProductVariant::query()
            ->with('product:id,name,status')
            ->whereHas('product', fn (Builder $q) => $q->where('status', '!=', ProductStatus::Archived))
            ->when($filters['q'] !== '', function (Builder $q) use ($filters) {
                $like = '%'.addcslashes($filters['q'], '%_\\').'%';
                $q->where(fn (Builder $w) => $w->where('sku', 'like', $like)
                    ->orWhere('name', 'like', $like)
                    ->orWhereHas('product', fn (Builder $p) => $p->where('name', 'like', $like)));
            })
            ->when($filters['level'] === 'low', fn (Builder $q) => $q->where('track_stock', true)
                ->whereRaw('stock <= COALESCE(low_stock_threshold, ?)', [$threshold]))
            ->when($filters['level'] === 'out', fn (Builder $q) => $q->where('track_stock', true)->where('stock', 0))
            ->orderBy('stock')
            ->orderBy('product_id')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('admin/inventory/index', [
            'variants' => $variants->through(fn (ProductVariant $v) => [
                'id' => $v->id,
                'product_id' => $v->product_id,
                'product_name' => $v->product->name,
                'product_status' => $v->product->status->value,
                'name' => $v->name,
                'sku' => $v->sku,
                'stock' => $v->stock,
                'track_stock' => $v->track_stock,
                'threshold' => $v->low_stock_threshold ?? $threshold,
                'is_low' => $v->isLowStock($threshold),
                'is_active' => $v->is_active,
            ]),
            'filters' => $filters,
            'canManage' => $request->user()->can(Permission::ManageCatalog->value),
        ]);
    }

    public function update(Request $request, ProductVariant $variant, InventoryService $inventory, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize(Permission::ManageCatalog->value);

        $data = $request->validate([
            'stock' => ['required', 'integer', 'between:0,1000000'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $before = $variant->stock;
        $movement = $inventory->setStock($variant, (int) $data['stock'], $request->user(), $data['note'] ?? null);

        if ($movement) {
            $audit->log('inventory.adjusted', $variant, ['from' => $before, 'to' => (int) $data['stock'], 'note' => $data['note'] ?? null]);
        }

        return back()->with('success', $movement ? 'Stock updated.' : 'Stock unchanged.');
    }

    public function history(ProductVariant $variant): Response
    {
        Gate::authorize(Permission::ViewCatalog->value);

        $variant->load('product:id,name');

        return Inertia::render('admin/inventory/history', [
            'variant' => [
                'id' => $variant->id,
                'name' => $variant->displayName(),
                'sku' => $variant->sku,
                'stock' => $variant->stock,
                'product_id' => $variant->product_id,
            ],
            'movements' => $variant->movements()->with(['user:id,name', 'order:id,reference'])->paginate(50)
                ->through(fn (InventoryMovement $m) => [
                    'id' => $m->id,
                    'change' => $m->change,
                    'stock_after' => $m->stock_after,
                    'reason' => $m->reason->label(),
                    'order_reference' => $m->order?->reference,
                    'user' => $m->user?->name,
                    'note' => $m->note,
                    'created_at' => $m->created_at?->toIso8601String(),
                ]),
        ]);
    }
}
