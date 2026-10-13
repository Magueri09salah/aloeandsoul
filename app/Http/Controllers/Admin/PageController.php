<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function index(): Response
    {
        Gate::authorize(Permission::ManageContent->value);

        return Inertia::render('admin/pages/index', [
            'pages' => Page::query()->orderBy('title')->get()->map(fn (Page $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'is_published' => $p->is_published,
                'needs_review' => $p->needs_review,
                'updated_at' => $p->updated_at?->toIso8601String(),
                'url' => url($p->slug),
            ]),
        ]);
    }

    public function edit(Page $page): Response
    {
        Gate::authorize(Permission::ManageContent->value);

        return Inertia::render('admin/pages/edit', [
            'page' => $page->only(['id', 'title', 'slug', 'body', 'seo_title', 'seo_description', 'is_published', 'needs_review']) + ['url' => url($page->slug)],
        ]);
    }

    public function update(Request $request, Page $page, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize(Permission::ManageContent->value);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:60000'],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:170'],
            'is_published' => ['boolean'],
            'needs_review' => ['boolean'],
        ]);

        $page->fill([
            ...$data,
            'is_published' => $request->boolean('is_published'),
            'needs_review' => $request->boolean('needs_review'),
        ]);
        $changes = array_keys(AuditLogger::diff($page));
        $page->save();
        $audit->log('page.updated', $page, ['changed' => $changes]);

        return back()->with('success', 'Page saved.');
    }
}
