<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use App\Enums\PostStatus;
use App\Models\BlogPost;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class BlogPostRequest extends AdminRequest
{
    protected function permission(): Permission
    {
        return Permission::ManageContent;
    }

    protected function prepareForValidation(): void
    {
        $this->nullIfBlank('excerpt', 'cover_alt', 'seo_title', 'seo_description', 'published_at');

        $title = is_string($this->input('title')) ? trim($this->input('title')) : $this->input('title');
        $slug = is_string($this->input('slug')) ? trim($this->input('slug')) : '';
        $this->merge([
            'title' => $title,
            'slug' => $slug !== '' ? Str::slug($slug) : (is_string($title) ? Str::slug($title) : null),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var BlogPost|null $post */
        $post = $this->route('post');

        return [
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['required', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('blog_posts', 'slug')->ignore($post?->id)],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'body' => ['required', 'string', 'max:100000'],
            'status' => ['required', Rule::enum(PostStatus::class)],
            'published_at' => ['nullable', 'date'],
            'cover' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])
                ->max((int) config('store.images.max_kilobytes'))
                ->dimensions(Rule::dimensions()->minWidth(800)->maxWidth((int) config('store.images.max_dimension'))->maxHeight((int) config('store.images.max_dimension')))],
            'remove_cover' => ['boolean'],
            'cover_alt' => ['nullable', 'string', 'max:160'],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:170'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.unique' => 'Another post already uses this web address.',
            'slug.regex' => 'Use lowercase letters, numbers and dashes only.',
            'cover.dimensions' => 'Use an image at least 800 px wide.',
            'body.required' => 'Write the post before saving.',
        ];
    }

    /**
     * Post fields to save. Publishing without a date publishes now; the date
     * is entered in the store's timezone.
     *
     * @return array<string, mixed>
     */
    public function postData(?BlogPost $existing = null): array
    {
        $status = PostStatus::from((string) $this->validated('status'));
        $tz = (string) config('store.display_timezone');
        $date = $this->validated('published_at');

        $publishedAt = $date !== null
            ? CarbonImmutable::parse((string) $date, $tz)->utc()
            : ($status === PostStatus::Published ? ($existing->published_at ?? now()) : null);

        return [
            'title' => $this->validated('title'),
            'slug' => $this->validated('slug'),
            'excerpt' => $this->validated('excerpt'),
            'body' => $this->validated('body'),
            'status' => $status,
            'published_at' => $publishedAt,
            'cover_alt' => $this->validated('cover_alt'),
            'seo_title' => $this->validated('seo_title'),
            'seo_description' => $this->validated('seo_description'),
        ];
    }
}
