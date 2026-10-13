<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Enums\ProductStatus;
use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Presenters\ImagePresenter;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\Audit\AuditLogger;
use App\Services\Inventory\InventoryService;
use App\Services\Media\ImageProcessor;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize(Permission::ViewCatalog->value);

        $filters = [
            'q' => is_string($request->query('q')) ? mb_substr(trim($request->query('q')), 0, 100) : '',
            'category' => filter_var($request->query('category'), FILTER_VALIDATE_INT) ?: null,
            'status' => ProductStatus::tryFrom((string) $request->query('status'))?->value,
            'sort' => in_array($request->query('sort'), ['name', 'updated', 'stock'], true) ? $request->query('sort') : 'updated',
        ];

        $products = Product::query()
            ->with(['category:id,name', 'primaryImage', 'variants'])
            ->withSum('variants as total_stock', 'stock')
            ->when($filters['q'] !== '', function (Builder $q) use ($filters) {
                $like = '%'.addcslashes($filters['q'], '%_\\').'%';
                $q->where(fn (Builder $w) => $w->where('name', 'like', $like)
                    ->orWhereHas('variants', fn (Builder $v) => $v->where('sku', 'like', $like)));
            })
            ->when($filters['category'], fn (Builder $q, int $c) => $q->where('category_id', $c))
            ->when($filters['status'], fn (Builder $q, string $s) => $q->where('status', $s))
            ->when($filters['sort'] === 'name', fn (Builder $q) => $q->orderBy('name'))
            ->when($filters['sort'] === 'stock', fn (Builder $q) => $q->orderBy('total_stock'))
            ->when($filters['sort'] === 'updated', fn (Builder $q) => $q->latest('updated_at'))
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('admin/products/index', [
            'products' => $products->through(function (Product $p) {
                $prices = $p->variants->where('is_active', true)->map(fn (ProductVariant $v) => $v->effectivePriceMinor())->filter();

                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'slug' => $p->slug,
                    'category' => $p->category->name,
                    'status' => $p->status->value,
                    'status_label' => $p->status->label(),
                    'image' => $p->primaryImage ? ImagePresenter::present($p->primaryImage, $p->name) : null,
                    'min_price_minor' => $prices->min(),
                    'max_price_minor' => $prices->max(),
                    'total_stock' => (int) $p->total_stock,
                    'variant_count' => $p->variants->count(),
                    'is_featured' => $p->is_featured,
                    'is_bestseller' => $p->is_bestseller,
                    'blockers' => $p->status === ProductStatus::Draft ? count($p->publishBlockers()) : 0,
                    'updated_at' => $p->updated_at?->toIso8601String(),
                ];
            }),
            'filters' => $filters,
            'categories' => Category::query()->orderBy('sort_order')->get(['id', 'name']),
            'statuses' => ProductStatus::options(),
            'currency' => settings()->currency(),
            'canManage' => $request->user()->can(Permission::ManageCatalog->value),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize(Permission::ManageCatalog->value);

        return Inertia::render('admin/products/form', [
            'product' => null,
            'categories' => Category::query()->orderBy('sort_order')->get(['id', 'name']),
            'defaultCategoryId' => filter_var($request->query('category'), FILTER_VALIDATE_INT) ?: null,
            'currency' => settings()->currency(),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = DB::transaction(function () use ($request) {
            $product = Product::query()->create([...$request->productData(), 'status' => ProductStatus::Draft]);
            $this->syncVariants($product, $request);
            $this->audit->log('product.created', $product, ['name' => $product->name]);

            return $product;
        });

        return redirect()->route('admin.products.edit', $product)
            ->with('success', 'Product created as a draft. Add images, then publish it.');
    }

    public function edit(Product $product): Response
    {
        Gate::authorize(Permission::ManageCatalog->value);

        $product->load(['variants', 'images', 'category']);
        $currency = settings()->currency();

        return Inertia::render('admin/products/form', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'category_id' => $product->category_id,
                'summary' => $product->summary,
                'description' => $product->description,
                'specifications' => $product->specifications ?? [],
                'option_label' => $product->option_label,
                'is_featured' => $product->is_featured,
                'is_bestseller' => $product->is_bestseller,
                'sort_order' => $product->sort_order,
                'seo_title' => $product->seo_title,
                'seo_description' => $product->seo_description,
                'status' => $product->status->value,
                'status_label' => $product->status->label(),
                'url' => $product->status === ProductStatus::Published ? route('products.show', $product) : null,
                'variants' => $product->variants->map(fn (ProductVariant $v) => [
                    'id' => $v->id,
                    'name' => $v->name,
                    'sku' => $v->sku,
                    'price' => Money::toDecimalString($v->price_minor, $currency),
                    'sale_price' => Money::toDecimalString($v->sale_price_minor, $currency),
                    'stock' => $v->stock,
                    'track_stock' => $v->track_stock,
                    'low_stock_threshold' => $v->low_stock_threshold,
                    'is_active' => $v->is_active,
                ])->values(),
                'images' => $product->images->map(fn (ProductImage $img) => [
                    'id' => $img->id,
                    'alt' => $img->alt,
                    ...ImagePresenter::present($img, $product->name),
                ])->values(),
                'blockers' => $product->publishBlockers(),
                'has_orders' => $product->hasOrders(),
            ],
            'categories' => Category::query()->orderBy('sort_order')->get(['id', 'name']),
            'defaultCategoryId' => null,
            'currency' => $currency,
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        DB::transaction(function () use ($request, $product) {
            $product->fill($request->productData());
            $changes = AuditLogger::diff($product);
            $product->save();
            $variantChanges = $this->syncVariants($product, $request);

            // A live product must stay sellable; roll back edits that break it.
            if ($product->status === ProductStatus::Published) {
                $product->unsetRelation('variants')->unsetRelation('images');
                if ($blockers = $product->publishBlockers()) {
                    throw new DomainException('This product is live, so it cannot be saved like this: '.implode(' ', $blockers), 'general');
                }
            }

            $this->audit->log('product.updated', $product, array_filter(['fields' => $changes, 'variants' => $variantChanges]));
        });

        return back()->with('success', 'Product saved.');
    }

    public function status(Request $request, Product $product): RedirectResponse
    {
        Gate::authorize(Permission::ManageCatalog->value);
        $action = $request->validate(['action' => ['required', Rule::in(['publish', 'unpublish', 'archive', 'restore'])]])['action'];

        $from = $product->status;
        $message = match ($action) {
            'publish' => $this->publish($product),
            'unpublish' => $this->setStatus($product, ProductStatus::Draft, 'Product moved back to drafts. It is no longer visible in the shop.'),
            'archive' => $this->setStatus($product, ProductStatus::Archived, 'Product archived. Its page now tells visitors it is no longer available.'),
            'restore' => $this->setStatus($product, ProductStatus::Draft, 'Product restored as a draft.'),
            default => abort(422),
        };

        $this->audit->log('product.status_changed', $product, ['from' => $from->value, 'to' => $product->status->value]);

        return back()->with('success', $message);
    }

    public function destroy(Product $product, ImageProcessor $images): RedirectResponse
    {
        Gate::authorize(Permission::ManageCatalog->value);

        if ($product->hasOrders()) {
            return back()->with('error', 'This product has been ordered before, so it cannot be deleted. Archive it instead.');
        }

        $product->load('images');
        DB::transaction(function () use ($product) {
            $this->audit->log('product.deleted', $product, ['name' => $product->name]);
            $product->delete();
        });
        $product->images->each(fn (ProductImage $img) => $images->delete($img->path, $img->widths));

        return redirect()->route('admin.products.index')->with('success', "“{$product->name}” deleted.");
    }

    private function publish(Product $product): string
    {
        if ($blockers = $product->publishBlockers()) {
            throw new DomainException('Not ready to publish: '.implode(' ', $blockers), 'general');
        }

        $product->forceFill([
            'status' => ProductStatus::Published,
            'published_at' => $product->published_at ?? now(),
        ])->save();

        return 'Product published. It is now visible in the shop.';
    }

    private function setStatus(Product $product, ProductStatus $status, string $message): string
    {
        $product->forceFill(['status' => $status])->save();

        return $message;
    }

    /**
     * Create/update/remove variants to match the submitted list. Stock changes
     * go through the inventory ledger. Variants that were ever ordered are
     * deactivated instead of deleted.
     *
     * @return list<string>
     */
    private function syncVariants(Product $product, ProductRequest $request): array
    {
        $user = $request->user();
        $existing = $product->variants()->get()->keyBy('id');
        $keep = [];
        $log = [];

        foreach ($request->variantsData() as $data) {
            $fields = collect($data)->except(['id', 'stock'])->all();

            if ($data['id'] !== null && $existing->has($data['id'])) {
                /** @var ProductVariant $variant */
                $variant = $existing[$data['id']];
                $variant->fill($fields);
                if ($variant->isDirty()) {
                    $log[] = 'updated #'.$variant->id.' ('.implode(', ', array_keys($variant->getDirty())).')';
                    $variant->save();
                }
            } else {
                $variant = $product->variants()->create([...$fields, 'stock' => 0]);
                $log[] = 'added #'.$variant->id;
            }

            if ($variant->stock !== $data['stock']) {
                $this->inventory->setStock($variant, $data['stock'], $user, $data['id'] === null ? 'Initial stock' : 'Edited on the product page');
                $log[] = "stock #{$variant->id} → {$data['stock']}";
            }

            $keep[] = $variant->id;
        }

        foreach ($existing->except($keep) as $old) {
            if (OrderItem::query()->where('product_variant_id', $old->id)->exists()) {
                $old->update(['is_active' => false]);
                $log[] = "deactivated #{$old->id} (has orders)";
            } else {
                $old->delete();
                $log[] = "removed #{$old->id}";
            }
        }

        return $log;
    }
}
