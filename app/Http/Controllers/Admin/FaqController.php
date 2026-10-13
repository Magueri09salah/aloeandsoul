<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(): Response
    {
        Gate::authorize(Permission::ManageContent->value);

        return Inertia::render('admin/faqs/index', [
            'faqs' => Faq::query()->orderBy('sort_order')->orderBy('id')->get(['id', 'question', 'answer', 'sort_order', 'is_published', 'show_on_home']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize(Permission::ManageContent->value);
        $faq = Faq::query()->create($this->validated($request));
        $this->audit->log('faq.created', $faq);

        return back()->with('success', 'Question added.');
    }

    public function update(Request $request, Faq $faq): RedirectResponse
    {
        Gate::authorize(Permission::ManageContent->value);
        $faq->update($this->validated($request));
        $this->audit->log('faq.updated', $faq);

        return back()->with('success', 'Question saved.');
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        Gate::authorize(Permission::ManageContent->value);
        $this->audit->log('faq.deleted', $faq, ['question' => $faq->question]);
        $faq->delete();

        return back()->with('success', 'Question deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:3000'],
            'sort_order' => ['nullable', 'integer', 'between:0,1000'],
            'is_published' => ['boolean'],
            'show_on_home' => ['boolean'],
        ]);

        return [
            ...$data,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_published' => $request->boolean('is_published'),
            'show_on_home' => $request->boolean('show_on_home'),
        ];
    }
}
