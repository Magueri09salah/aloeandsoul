<?php

use App\Enums\PostStatus;
use App\Models\BlogPost;
use App\Models\Collaborator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

describe('public blog', function () {
    it('lists only published posts that are due, newest first', function () {
        $older = BlogPost::factory()->published(now()->subDays(3))->create(['title' => 'Older post']);
        $newer = BlogPost::factory()->published(now()->subDay())->create(['title' => 'Newer post']);
        BlogPost::factory()->create(['title' => 'A draft']);
        BlogPost::factory()->scheduled()->create(['title' => 'Next week']);

        $this->get('/blog')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.url('/blog').'"', false)
            ->assertInertia(fn (Assert $page) => $page->component('blog/index')
                ->has('posts.data', 2)
                ->where('posts.data.0.title', $newer->title)
                ->where('posts.data.1.title', $older->title));
    });

    it('shows a published post with article metadata and safe HTML', function () {
        $post = BlogPost::factory()->published()->create([
            'title' => 'Morning rituals',
            'excerpt' => 'How I start the day.',
            'body' => "Hello **world**\n\n<script>alert(1)</script>\n\n[x](javascript:alert(1))",
        ]);

        $response = $this->get("/blog/{$post->slug}")
            ->assertOk()
            ->assertSee('<title data-inertia>Morning rituals — Aloe &amp; Soul</title>', false)
            ->assertSee('"@type":"BlogPosting"', false)
            ->assertSee('<meta property="og:type" content="article">', false);

        $html = inertiaProps($response)['post']['body_html'];
        expect($html)->toContain('<strong>world</strong>')->not->toContain('<script')->not->toContain('javascript:');
    });

    it('hides drafts and scheduled posts', function () {
        $draft = BlogPost::factory()->create();
        $scheduled = BlogPost::factory()->scheduled()->create();

        $this->get("/blog/{$draft->slug}")->assertNotFound();
        $this->get("/blog/{$scheduled->slug}")->assertNotFound();
    });

    it('includes live posts in the sitemap only', function () {
        $live = BlogPost::factory()->published()->create();
        $draft = BlogPost::factory()->create();

        $this->get('/sitemap.xml')->assertSee(url('/blog'))->assertSee(url("/blog/{$live->slug}"))->assertDontSee($draft->slug);
    });

    it('redirects the old blog address', function () {
        $this->get('/blog-list')->assertStatus(301)->assertRedirect('/blog');
    });
});

describe('home page sections', function () {
    it('shows visible collaborators in order and the latest posts', function () {
        Collaborator::factory()->create(['name' => 'Second', 'sort_order' => 2]);
        Collaborator::factory()->create(['name' => 'First', 'sort_order' => 1]);
        Collaborator::factory()->hidden()->create(['name' => 'Hidden one']);
        BlogPost::factory()->published()->count(4)->create();

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->has('collaborators.items', 2)
            ->where('collaborators.items.0.name', 'First')
            ->where('collaborators.items.1.name', 'Second')
            ->has('latestPosts.items', 3));
    });

    it('can switch both sections off', function () {
        Collaborator::factory()->create();
        settings()->update('home', ['collaborators_enabled' => false, 'blog_enabled' => false]);

        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('collaborators', null)->where('latestPosts', null));
    });
});

describe('admin blog', function () {
    it('creates a draft, then publishes it with a cover photo', function () {
        Storage::fake('public');
        $this->actingAs(manager());

        $this->post('/admin/blog', [
            'title' => 'My first post',
            'body' => 'Some text.',
            'status' => 'draft',
        ])->assertSessionHasNoErrors();

        $post = BlogPost::query()->sole();
        expect($post->slug)->toBe('my-first-post')
            ->and($post->status)->toBe(PostStatus::Draft)
            ->and($post->published_at)->toBeNull();
        $this->get('/blog/my-first-post')->assertNotFound();

        $this->post("/admin/blog/{$post->id}", [
            '_method' => 'put',
            'title' => 'My first post',
            'body' => 'Some text.',
            'status' => 'published',
            'cover' => UploadedFile::fake()->image('cover.jpg', 1600, 1000),
        ])->assertSessionHasNoErrors();

        $post->refresh();
        expect($post->isLive())->toBeTrue()->and($post->cover['widths'])->toBe([480, 960, 1600]);
        Storage::disk('public')->assertExists($post->cover['path'].'-960.webp');
        $this->get('/blog/my-first-post')->assertOk();
    });

    it('schedules a post with a future date in the store timezone', function () {
        $this->actingAs(manager())->post('/admin/blog', [
            'title' => 'Coming soon',
            'body' => 'Text.',
            'status' => 'published',
            'published_at' => now('Africa/Casablanca')->addDays(2)->format('Y-m-d\TH:i'),
        ])->assertSessionHasNoErrors();

        $post = BlogPost::query()->sole();
        expect($post->isScheduled())->toBeTrue();
        $this->get('/blog')->assertInertia(fn (Assert $page) => $page->has('posts.data', 0));
    });

    it('validates posts', function () {
        BlogPost::factory()->create(['slug' => 'taken']);
        $this->actingAs(manager());

        $this->post('/admin/blog', ['title' => '', 'body' => '', 'status' => 'live'])->assertSessionHasErrors(['title', 'body', 'status']);
        $this->post('/admin/blog', ['title' => 'Taken', 'slug' => 'taken', 'body' => 'x', 'status' => 'draft'])->assertSessionHasErrors('slug');
        $this->post('/admin/blog', [
            'title' => 'Bad cover', 'body' => 'x', 'status' => 'draft',
            'cover' => UploadedFile::fake()->create('cover.svg', 5, 'image/svg+xml'),
        ])->assertSessionHasErrors('cover');
    });

    it('deletes a post and its cover', function () {
        Storage::fake('public');
        $this->actingAs(manager())->post('/admin/blog', [
            'title' => 'To delete', 'body' => 'x', 'status' => 'draft',
            'cover' => UploadedFile::fake()->image('c.jpg', 1000, 800),
        ]);
        $post = BlogPost::query()->sole();
        $file = $post->cover['path'].'-480.webp';
        Storage::disk('public')->assertExists($file);

        $this->delete("/admin/blog/{$post->id}")->assertRedirect('/admin/blog');

        expect(BlogPost::query()->count())->toBe(0);
        Storage::disk('public')->assertMissing($file);
    });

    it('is not available to staff', function () {
        $this->actingAs(staff())->get('/admin/blog')->assertForbidden();
        $this->actingAs(staff())->post('/admin/blog', ['title' => 'x', 'body' => 'x', 'status' => 'draft'])->assertForbidden();
    });
});

describe('admin collaborators', function () {
    it('adds, edits and removes a collaborator with a logo', function () {
        Storage::fake('public');
        $this->actingAs(manager());

        $this->post('/admin/collaborators', [
            'name' => 'Riad Atelier',
            'description' => 'Interior design, Marrakech',
            'url' => 'https://example.com',
            'image_style' => 'logo',
            'is_visible' => true,
            'image' => UploadedFile::fake()->image('logo.png', 600, 600),
        ])->assertSessionHasNoErrors();

        $c = Collaborator::query()->sole();
        expect($c->image)->not->toBeNull()->and($c->image_style)->toBe('logo');

        $this->post("/admin/collaborators/{$c->id}", ['_method' => 'put', 'name' => 'Riad Atelier', 'image_style' => 'logo', 'is_visible' => false, 'remove_image' => true])
            ->assertSessionHasNoErrors();
        expect($c->fresh()->is_visible)->toBeFalse()->and($c->fresh()->image)->toBeNull();

        $this->delete("/admin/collaborators/{$c->id}")->assertSessionHasNoErrors();
        expect(Collaborator::query()->count())->toBe(0);
    });

    it('only accepts https links and known image styles', function () {
        $this->actingAs(manager())->post('/admin/collaborators', [
            'name' => 'X', 'url' => 'javascript:alert(1)', 'image_style' => 'banner', 'is_visible' => true,
        ])->assertSessionHasErrors(['url', 'image_style']);

        $this->post('/admin/collaborators', ['name' => 'X', 'url' => 'http://insecure.example', 'image_style' => 'photo'])->assertSessionHasErrors('url');
    });

    it('updates the home page section text', function () {
        $this->actingAs(manager())->put('/admin/collaborators/section', ['enabled' => true, 'heading' => 'Friends of Aloe & Soul', 'intro' => 'People we love working with.'])
            ->assertSessionHasNoErrors();

        expect(settings()->get('home.collaborators_heading'))->toBe('Friends of Aloe & Soul');
    });

    it('is not available to staff', function () {
        $this->actingAs(staff())->get('/admin/collaborators')->assertForbidden();
    });
});
