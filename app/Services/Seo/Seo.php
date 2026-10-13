<?php

namespace App\Services\Seo;

use Illuminate\Support\Str;

/**
 * Page metadata rendered by Blade into the initial HTML (so crawlers and
 * social scrapers never depend on JavaScript) and shared with React so
 * client-side navigations update the title and description.
 */
final class Seo
{
    /**
     * @param  list<array<string, mixed>>  $jsonLd
     */
    public function __construct(
        public string $title,
        public string $description,
        public ?string $canonical = null,
        public bool $index = true,
        public ?string $image = null,
        public string $type = 'website',
        public array $jsonLd = [],
    ) {}

    public static function page(string $title, ?string $description = null, bool $index = true): self
    {
        return new self(
            title: $title,
            description: $description ?? (string) settings()->get('seo.description'),
            canonical: url()->current(),
            index: $index,
        );
    }

    public static function private(string $title): self
    {
        return new self(title: $title, description: (string) settings()->get('seo.description'), canonical: null, index: false);
    }

    public function fullTitle(): string
    {
        $store = (string) settings()->get('store.name');
        $default = (string) settings()->get('seo.title');

        if ($this->title === '' || $this->title === $default) {
            return $default;
        }

        return Str::endsWith($this->title, $store) ? $this->title : "{$this->title} — {$store}";
    }

    public function robots(): string
    {
        return $this->index ? 'index,follow' : 'noindex,follow';
    }

    public function imageUrl(): string
    {
        return $this->image ?? asset('brand/og-default.jpg');
    }

    /** @return array{title: string, description: string, canonical: string|null, robots: string} */
    public function toProps(): array
    {
        return [
            'title' => $this->fullTitle(),
            'description' => Str::limit($this->description, 160, '…'),
            'canonical' => $this->canonical,
            'robots' => $this->robots(),
        ];
    }
}
