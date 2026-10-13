<?php

namespace App\Http\Presenters;

/**
 * Editorial images stored in settings (uploaded in Admin → Content), with the
 * bundled founder photograph as the fallback.
 */
final class ContentPresenter
{
    /** Bundled renditions in public/brand (see database/assets/README.md). */
    private const FOUNDER = ['name' => 'founder-dunes', 'widths' => [480, 960, 1200], 'width' => 1200, 'height' => 1600];

    /**
     * @param  array<string, mixed>  $home
     * @return array{src: string, srcset: string, width: int, height: int, alt: string}
     */
    public static function heroImage(array $home): array
    {
        return self::image($home['hero_image'] ?? null, (string) $home['hero_image_alt']);
    }

    /**
     * @param  array<string, mixed>  $story
     * @return array{src: string, srcset: string, width: int, height: int, alt: string}
     */
    public static function storyImage(array $story): array
    {
        return self::image($story['image'] ?? null, (string) $story['image_alt']);
    }

    /**
     * @param  array{path: string, widths: list<int>, width: int, height: int}|null  $stored
     * @return array{src: string, srcset: string, width: int, height: int, alt: string}
     */
    private static function image(?array $stored, string $alt): array
    {
        if (is_array($stored) && isset($stored['path'], $stored['widths'])) {
            return ImagePresenter::fromStored($stored['path'], $stored['widths'], (int) $stored['width'], (int) $stored['height'], $alt);
        }

        return ImagePresenter::bundled(self::FOUNDER['name'], self::FOUNDER['widths'], self::FOUNDER['width'], self::FOUNDER['height'], $alt);
    }
}
