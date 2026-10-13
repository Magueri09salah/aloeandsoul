<?php

namespace App\Services\Media;

use App\Exceptions\DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * Turns an uploaded image into WebP renditions at fixed widths.
 *
 * Re-encoding means the original bytes (and anything hidden in them, such as
 * EXIF location data or appended payloads) are never stored or served.
 */
class ImageProcessor
{
    /**
     * @return array{path: string, widths: list<int>, width: int, height: int}
     */
    public function store(UploadedFile $file, string $directory): array
    {
        $manager = new ImageManager(new Driver);

        try {
            $image = $manager->read($file->getRealPath());
        } catch (Throwable) {
            throw new DomainException('This file could not be read as an image.', 'image');
        }

        $width = $image->width();
        $height = $image->height();
        $max = (int) config('store.images.max_dimension');

        if ($width > $max || $height > $max) {
            throw new DomainException("Images can be at most {$max} pixels wide or tall.", 'image');
        }

        $disk = Storage::disk('public');
        $base = trim($directory, '/').'/'.Str::lower((string) Str::ulid());
        $quality = (int) config('store.images.quality');

        $targets = array_values(array_filter(
            config('store.images.widths'),
            fn (int $w) => $w <= $width,
        ));
        // Always keep at least one rendition, at the original width if small.
        if ($targets === []) {
            $targets = [$width];
        }

        $written = [];
        try {
            foreach ($targets as $target) {
                $rendition = clone $image;
                $rendition->scaleDown(width: $target);
                $disk->put("{$base}-{$target}.webp", (string) $rendition->toWebp(quality: $quality), 'public');
                $written[] = "{$base}-{$target}.webp";
            }
        } catch (Throwable $e) {
            $disk->delete($written);
            throw $e;
        }

        return ['path' => $base, 'widths' => $targets, 'width' => $width, 'height' => $height];
    }

    /** @param list<int> $widths */
    public function delete(string $path, array $widths): void
    {
        Storage::disk('public')->delete(array_map(fn (int $w) => "{$path}-{$w}.webp", $widths));
    }
}
