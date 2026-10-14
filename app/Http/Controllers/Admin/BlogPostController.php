<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Enums\PostStatus;
use App\Http\Controllers\Controller;
use App\Http\Presenters\ImagePresenter;
use App\Http\Requests\Admin\BlogPostRequest;
use App\Models\BlogPost;
use App\Services\Audit\AuditLogger;
use App\Services\Media\ImageProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BlogPostController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly ImageProcessor $images,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize(Permission::ManageContent->value);

        $filter = in_array($request->query('status'), ['draft', 'published', 'scheduled'], true) ? $request->query('status') : null;

        $posts = BlogPost::query()
            ->when($filter === 'draft', fn ($q) => $q->where('status', PostStatus::Draft))
            ->when($filter === 'published', fn ($q) => $q->live())
            ->when($filter === 'scheduled', fn ($q) => $q->where('status', PostStatus::Published)->where('published_at', '>', now()))
            ->orderByRaw('published_at IS NULL DESC')
            ->latest('published_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/blog/index', [
            'posts' => $posts->through(fn (BlogPost $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'status' => $p->isScheduled() ? 'scheduled' : $p->status->value,
                'status_label' => $p->statusLabel(),
                'published_at' => $p->published_at?->toIso8601String(),
                'updated_at' => $p->updated_at?->toIso8601String(),
                'cover' => ImagePresenter::fromArray($p->cover, ''),
                'url' => $p->isLive() ? route('blog.show', $p) : null,
            ]),
            'filter' => $filter,
            'settings' => settings()->group('blog'),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize(Permission::ManageContent->value);

        return Inertia::render('admin/blog/form', ['post' => null]);
    }

    public function store(BlogPostRequest $request): RedirectResponse
    {
        $post = new BlogPost;
        $post->fill($request->postData());
        $post->forceFill(['user_id' => $request->user()->id]);
        $this->applyCover($request, $post);
        $post->save();

        $this->audit->log('blog.created', $post, ['title' => $post->title, 'status' => $post->status->value]);

        return redirect()->route('admin.blog.edit', $post)->with('success', $post->isLive() ? 'Post published.' : 'Post saved.');
    }

    public function edit(BlogPost $post): Response
    {
        Gate::authorize(Permission::ManageContent->value);
        $tz = (string) config('store.display_timezone');

        return Inertia::render('admin/blog/form', [
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'body' => $post->body,
                'status' => $post->status->value,
                'status_label' => $post->statusLabel(),
                'published_at' => $post->published_at?->setTimezone($tz)->format('Y-m-d\TH:i'),
                'cover' => ImagePresenter::fromArray($post->cover, (string) $post->cover_alt),
                'cover_alt' => $post->cover_alt,
                'seo_title' => $post->seo_title,
                'seo_description' => $post->seo_description,
                ...$post->only(BlogPost::translationColumns()),
                'url' => $post->isLive() ? route('blog.show', $post) : null,
            ],
        ]);
    }

    public function update(BlogPostRequest $request, BlogPost $post): RedirectResponse
    {
        $post->fill($request->postData($post));
        $changes = array_keys(AuditLogger::diff($post));
        $this->applyCover($request, $post);
        $post->save();

        $this->audit->log('blog.updated', $post, ['changed' => $changes]);

        return back()->with('success', 'Post saved.');
    }

    public function destroy(BlogPost $post): RedirectResponse
    {
        Gate::authorize(Permission::ManageContent->value);

        $cover = $post->cover;
        $this->audit->log('blog.deleted', $post, ['title' => $post->title]);
        $post->delete();
        $this->deleteImage($cover);

        return redirect()->route('admin.blog.index')->with('success', 'Post deleted.');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        Gate::authorize(Permission::ManageContent->value);

        $data = $request->validate([
            'heading' => ['required', 'string', 'max:60'],
            'intro' => ['nullable', 'string', 'max:300'],
            'heading_fr' => ['nullable', 'string', 'max:60'],
            'intro_fr' => ['nullable', 'string', 'max:300'],
        ]);
        settings()->update('blog', [
            'heading' => $data['heading'],
            'intro' => $data['intro'] ?? '',
            'heading_fr' => $data['heading_fr'] ?? '',
            'intro_fr' => $data['intro_fr'] ?? '',
        ]);
        $this->audit->log('blog.settings_updated');

        return back()->with('success', 'Blog page text saved.');
    }

    private function applyCover(BlogPostRequest $request, BlogPost $post): void
    {
        if ($request->hasFile('cover')) {
            $old = $post->cover;
            $post->cover = $this->images->store($request->file('cover'), 'blog');
            $this->deleteImage($old);
        } elseif ($request->boolean('remove_cover')) {
            $this->deleteImage($post->cover);
            $post->cover = null;
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
