<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(): Response
    {
        Gate::authorize(Permission::ManageCatalog->value);

        return Inertia::render('admin/categories/index', [
            'categories' => Category::query()
                ->withCount(['products', 'publishedProducts'])
                ->orderBy('sort_order')->orderBy('name')
                ->get()
                ->map(fn (Category $c) => [
                    ...$c->only(['id', 'name', 'slug', 'tagline', 'description', 'tint', 'motif', 'sort_order', 'is_active', 'seo_title', 'seo_description']),
                    'products_count' => $c->products_count,
                    'published_products_count' => $c->published_products_count,
                    'url' => route('shop.category', $c),
                ]),
            'motifs' => Category::MOTIFS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize(Permission::ManageCatalog->value);

        $category = Category::query()->create($this->validated($request));
        $this->audit->log('category.created', $category, ['name' => $category->name]);

        return back()->with('success', 'Category created.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        Gate::authorize(Permission::ManageCatalog->value);

        $category->fill($this->validated($request, $category));
        $changes = AuditLogger::diff($category);
        $category->save();
        $this->audit->log('category.updated', $category, $changes);

        return back()->with('success', 'Category saved.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        Gate::authorize(Permission::ManageCatalog->value);

        if ($category->products()->exists()) {
            return back()->with('error', 'Move or delete the products in this category first. You can also hide the category instead.');
        }

        $this->audit->log('category.deleted', $category, ['name' => $category->name]);
        $category->delete();

        return back()->with('success', 'Category deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Category $category = null): array
    {
        $request->merge([
            'slug' => Str::slug((string) ($request->input('slug') ?: $request->input('name'))),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('categories', 'slug')->ignore($category?->id)],
            'tagline' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'tint' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'motif' => ['required', Rule::in(Category::MOTIFS)],
            'sort_order' => ['nullable', 'integer', 'between:0,1000'],
            'is_active' => ['boolean'],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:170'],
        ], [
            'slug.unique' => 'Another category already uses this web address.',
            'tint.regex' => 'Use a colour like #EDE5D8.',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['tint'] = strtoupper($data['tint']);

        return $data;
    }
}
