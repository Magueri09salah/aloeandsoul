<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Presenters\PostPresenter;
use App\Models\Collaborator;
use App\Services\Audit\AuditLogger;
use App\Services\Media\ImageProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Inertia\Inertia;
use Inertia\Response;

/**
 * People and brands Aloe & Soul works with, shown on the home page.
 */
class CollaboratorController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly ImageProcessor $images,
    ) {}

    public function index(): Response
    {
        Gate::authorize(Permission::ManageContent->value);
        $home = settings()->group('home');

        return Inertia::render('admin/collaborators/index', [
            'collaborators' => Collaborator::query()->orderBy('sort_order')->orderBy('id')->get()
                ->map(fn (Collaborator $c) => [...PostPresenter::collaborator($c), 'description_fr' => $c->description_fr, 'sort_order' => $c->sort_order, 'is_visible' => $c->is_visible]),
            'section' => [
                'enabled' => (bool) $home['collaborators_enabled'],
                'heading' => $home['collaborators_heading'],
                'intro' => $home['collaborators_intro'],
                'heading_fr' => $home['collaborators_heading_fr'],
                'intro_fr' => $home['collaborators_intro_fr'],
            ],
            'styles' => Collaborator::IMAGE_STYLES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize(Permission::ManageContent->value);

        $collaborator = new Collaborator;
        $collaborator->fill($this->validated($request));
        $this->applyImage($request, $collaborator);
        $collaborator->save();
        $this->audit->log('collaborator.created', $collaborator, ['name' => $collaborator->name]);

        return back()->with('success', "{$collaborator->name} added.");
    }

    public function update(Request $request, Collaborator $collaborator): RedirectResponse
    {
        Gate::authorize(Permission::ManageContent->value);

        $collaborator->fill($this->validated($request));
        $changes = array_keys(AuditLogger::diff($collaborator));
        $this->applyImage($request, $collaborator);
        $collaborator->save();
        $this->audit->log('collaborator.updated', $collaborator, ['changed' => $changes]);

        return back()->with('success', "{$collaborator->name} saved.");
    }

    public function destroy(Collaborator $collaborator): RedirectResponse
    {
        Gate::authorize(Permission::ManageContent->value);

        $image = $collaborator->image;
        $this->audit->log('collaborator.deleted', $collaborator, ['name' => $collaborator->name]);
        $collaborator->delete();
        $this->deleteImage($image);

        return back()->with('success', 'Collaborator removed.');
    }

    /** Home page section heading, introduction and visibility. */
    public function updateSection(Request $request): RedirectResponse
    {
        Gate::authorize(Permission::ManageContent->value);

        $data = $request->validate([
            'enabled' => ['boolean'],
            'heading' => ['required', 'string', 'max:80'],
            'intro' => ['nullable', 'string', 'max:300'],
            'heading_fr' => ['nullable', 'string', 'max:80'],
            'intro_fr' => ['nullable', 'string', 'max:300'],
        ]);
        settings()->update('home', [
            'collaborators_enabled' => $request->boolean('enabled'),
            'collaborators_heading' => $data['heading'],
            'collaborators_intro' => $data['intro'] ?? '',
            'collaborators_heading_fr' => $data['heading_fr'] ?? '',
            'collaborators_intro_fr' => $data['intro_fr'] ?? '',
        ]);
        $this->audit->log('collaborator.section_updated');

        return back()->with('success', 'Section saved.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            'description_fr' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:255', 'url:https'],
            'image_style' => ['required', Rule::in(Collaborator::IMAGE_STYLES)],
            'sort_order' => ['nullable', 'integer', 'between:0,1000'],
            'is_visible' => ['boolean'],
            'image' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])
                ->max((int) config('store.images.max_kilobytes'))
                ->dimensions(Rule::dimensions()->minWidth(200)->maxWidth((int) config('store.images.max_dimension'))->maxHeight((int) config('store.images.max_dimension')))],
            'remove_image' => ['boolean'],
        ], [
            'url.url' => 'Use a full web address starting with https://',
            'image.dimensions' => 'Use an image at least 200 px wide.',
        ]);

        return [
            'name' => trim($data['name']),
            'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
            'description_fr' => filled($data['description_fr'] ?? null) ? trim($data['description_fr']) : null,
            'url' => filled($data['url'] ?? null) ? trim($data['url']) : null,
            'image_style' => $data['image_style'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_visible' => $request->boolean('is_visible'),
        ];
    }

    private function applyImage(Request $request, Collaborator $collaborator): void
    {
        if ($request->hasFile('image')) {
            $old = $collaborator->image;
            $collaborator->image = $this->images->store($request->file('image'), 'collaborators');
            $this->deleteImage($old);
        } elseif ($request->boolean('remove_image')) {
            $this->deleteImage($collaborator->image);
            $collaborator->image = null;
        }
    }

    /** @param array<string, mixed>|null $stored */
    private function deleteImage(?array $stored): void
    {
        if (is_array($stored) && isset($stored['path'], $stored['widths']) && is_array($stored['widths'])) {
            $this->images->delete((string) $stored['path'], array_map('intval', $stored['widths']));
        }
    }
}
