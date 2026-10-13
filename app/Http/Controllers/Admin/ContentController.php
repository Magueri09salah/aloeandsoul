<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Presenters\ContentPresenter;
use App\Models\Product;
use App\Services\Audit\AuditLogger;
use App\Services\Catalog\ProductQuery;
use App\Services\Media\ImageProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Home page, announcement bar and founder story content (stored in settings).
 */
class ContentController extends Controller
{
    /** Links must be site-relative or https, never javascript: or similar. */
    private const SAFE_LINK = 'regex:/^(\/(?!\/)[^\s]*|https:\/\/[^\s]+)$/';

    public function __construct(private readonly AuditLogger $audit) {}

    public function editHome(ProductQuery $products): Response
    {
        Gate::authorize(Permission::ManageContent->value);
        $home = settings()->group('home');

        return Inertia::render('admin/content/home', [
            'home' => [...$home, 'hero_image' => ContentPresenter::heroImage($home), 'has_custom_image' => ! empty($home['hero_image'])],
            'announcement' => settings()->group('announcement'),
            // Only id + name are needed for the picker, so skip the card relations.
            'products' => $products->published()->without(['category', 'primaryImage', 'activeVariants'])
                ->orderBy('name')->get(['products.id', 'products.name'])
                ->map(fn (Product $p) => ['id' => $p->id, 'name' => $p->name]),
        ]);
    }

    public function updateHome(Request $request): RedirectResponse
    {
        Gate::authorize(Permission::ManageContent->value);

        $data = $request->validate([
            'announcement.enabled' => ['boolean'],
            'announcement.text' => ['nullable', 'string', 'max:140'],
            'announcement.link_label' => ['nullable', 'string', 'max:40'],
            'announcement.link_url' => ['nullable', 'string', 'max:255', self::SAFE_LINK],
            'home.hero_heading' => ['required', 'string', 'max:120'],
            'home.hero_text' => ['nullable', 'string', 'max:400'],
            'home.hero_attribution' => ['nullable', 'string', 'max:80'],
            'home.hero_image_alt' => ['required', 'string', 'max:160'],
            'home.intro_heading' => ['nullable', 'string', 'max:120'],
            'home.intro_text' => ['nullable', 'string', 'max:600'],
            'home.featured_heading' => ['required', 'string', 'max:80'],
            'home.ritual_enabled' => ['boolean'],
            'home.ritual_heading' => ['nullable', 'string', 'max:80'],
            'home.ritual_intro' => ['nullable', 'string', 'max:400'],
            'home.ritual_steps' => ['nullable', 'array', 'max:5'],
            'home.ritual_steps.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'home.ritual_steps.*.note' => ['nullable', 'string', 'max:160'],
            'home.newsletter_enabled' => ['boolean'],
        ], [
            'announcement.link_url.regex' => 'Use a link starting with / (a page on this site) or https://.',
        ]);

        $announcement = [
            'enabled' => $request->boolean('announcement.enabled'),
            'text' => (string) ($data['announcement']['text'] ?? ''),
            'link_label' => (string) ($data['announcement']['link_label'] ?? ''),
            'link_url' => (string) ($data['announcement']['link_url'] ?? ''),
        ];
        $home = [
            ...array_map(fn ($v) => $v ?? '', $data['home']),
            'ritual_enabled' => $request->boolean('home.ritual_enabled'),
            'newsletter_enabled' => $request->boolean('home.newsletter_enabled'),
            'ritual_steps' => array_values(array_map(fn ($s) => [
                'product_id' => (int) $s['product_id'],
                'note' => (string) ($s['note'] ?? ''),
            ], $data['home']['ritual_steps'] ?? [])),
        ];

        $changes = [
            'announcement' => settings()->update('announcement', $announcement),
            'home' => settings()->update('home', $home),
        ];
        $this->audit->log('content.home_updated', null, array_filter($changes));

        return back()->with('success', 'Home page saved.');
    }

    public function editStory(): Response
    {
        Gate::authorize(Permission::ManageContent->value);
        $story = settings()->group('story');

        return Inertia::render('admin/content/story', [
            'story' => [...$story, 'image' => ContentPresenter::storyImage($story), 'has_custom_image' => ! empty($story['image'])],
        ]);
    }

    public function updateStory(Request $request): RedirectResponse
    {
        Gate::authorize(Permission::ManageContent->value);

        $data = $request->validate([
            'heading' => ['required', 'string', 'max:120'],
            'intro' => ['nullable', 'string', 'max:400'],
            'image_alt' => ['required', 'string', 'max:160'],
            'milestones' => ['array', 'max:6'],
            'milestones.*.place' => ['required', 'string', 'max:40'],
            'milestones.*.text' => ['required', 'string', 'max:200'],
            'chapters' => ['required', 'array', 'min:1', 'max:6'],
            'chapters.*.key' => ['required', 'string', 'max:30', 'regex:/^[a-z0-9-]+$/', 'distinct'],
            'chapters.*.title' => ['required', 'string', 'max:80'],
            'chapters.*.body' => ['required', 'string', 'max:8000'],
            'closing' => ['nullable', 'string', 'max:600'],
            'disclaimer' => ['nullable', 'string', 'max:600'],
        ], [
            'chapters.*.key.regex' => 'Use lowercase letters, numbers and dashes (used in links such as /our-story#wellness).',
        ]);

        $changes = settings()->update('story', [
            ...$data,
            'intro' => $data['intro'] ?? '',
            'closing' => $data['closing'] ?? '',
            'disclaimer' => $data['disclaimer'] ?? '',
            'milestones' => array_values($data['milestones'] ?? []),
            'chapters' => array_values($data['chapters']),
        ]);
        $this->audit->log('content.story_updated', null, ['changed' => array_keys($changes)]);

        return back()->with('success', 'Story saved.');
    }

    public function uploadImage(Request $request, string $slot, ImageProcessor $processor): RedirectResponse
    {
        Gate::authorize(Permission::ManageContent->value);

        $request->validate([
            'image' => ['required', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])
                ->max((int) config('store.images.max_kilobytes'))
                ->dimensions(Rule::dimensions()->minWidth(800)->maxWidth((int) config('store.images.max_dimension'))->maxHeight((int) config('store.images.max_dimension')))],
        ], ['image.dimensions' => 'Use an image at least 800 px wide.']);

        [$group, $key] = $slot === 'hero' ? ['home', 'hero_image'] : ['story', 'image'];
        $old = settings()->get("{$group}.{$key}");
        $stored = $processor->store($request->file('image'), 'content');
        settings()->update($group, [$key => $stored]);

        if (is_array($old) && isset($old['path'], $old['widths'])) {
            $processor->delete($old['path'], $old['widths']);
        }
        $this->audit->log('content.image_replaced', null, ['slot' => $slot]);

        return back()->with('success', 'Image updated.');
    }

    public function removeImage(string $slot, ImageProcessor $processor): RedirectResponse
    {
        Gate::authorize(Permission::ManageContent->value);

        [$group, $key] = $slot === 'hero' ? ['home', 'hero_image'] : ['story', 'image'];
        $old = settings()->get("{$group}.{$key}");
        settings()->update($group, [$key => null]);
        if (is_array($old) && isset($old['path'], $old['widths'])) {
            $processor->delete($old['path'], $old['widths']);
        }

        return back()->with('success', 'Custom image removed. The original photograph is shown again.');
    }
}
