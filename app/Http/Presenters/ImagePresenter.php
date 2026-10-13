<?php

namespace App\Http\Presenters;

use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;

/**
 * Shape of a responsive image for the React <ResponsiveImage> component.
 */
final class ImagePresenter
{
    /**
     * @return array{src: string, srcset: string, width: int, height: int, alt: string}
     */
    public static function present(ProductImage $image, string $fallbackAlt = ''): array
    {
        return self::fromStored($image->path, $image->widths, $image->width, $image->height, $image->alt ?: $fallbackAlt);
    }

    /**
     * @param  list<int>  $widths
     * @return array{src: string, srcset: string, width: int, height: int, alt: string}
     */
    public static function fromStored(string $path, array $widths, int $width, int $height, string $alt): array
    {
        sort($widths);
        $disk = Storage::disk('public');
        $srcset = implode(', ', array_map(fn (int $w) => $disk->url("{$path}-{$w}.webp")." {$w}w", $widths));
        $default = $widths[min(1, count($widths) - 1)];

        return [
            'src' => $disk->url("{$path}-{$default}.webp"),
            'srcset' => $srcset,
            'width' => $width,
            'height' => $height,
            'alt' => $alt,
        ];
    }

    /**
     * Images shipped with the theme under public/brand (pre-generated renditions).
     *
     * @param  list<int>  $widths
     * @return array{src: string, srcset: string, width: int, height: int, alt: string}
     */
    public static function bundled(string $name, array $widths, int $width, int $height, string $alt): array
    {
        $srcset = implode(', ', array_map(fn (int $w) => asset("brand/{$name}-{$w}.webp")." {$w}w", $widths));

        return [
            'src' => asset("brand/{$name}-{$widths[min(1, count($widths) - 1)]}.webp"),
            'srcset' => $srcset,
            'width' => $width,
            'height' => $height,
            'alt' => $alt,
        ];
    }
}
