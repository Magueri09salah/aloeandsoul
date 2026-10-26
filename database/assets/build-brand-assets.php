<?php

/**
 * Regenerates public/brand/* and public/favicon.ico from the source files in database/assets.
 * Run from the project root:  php database/assets/build-brand-assets.php
 *
 * Sources (supplied by the client / taken from the client's current website):
 *   logo.jpg           — Aloe & Soul logo (aloe, figure and sun inside a gold circle,
 *                        wordmark and tagline), 1254×1254, on paper-white #FFFBF8
 *   founder-dunes.jpg  — founder photographed on the dunes near Essaouira, 1200×1600
 */

require __DIR__.'/../../vendor/autoload.php';

use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

$src = __DIR__;
$out = __DIR__.'/../../public/brand';
@mkdir($out, 0755, true);

$manager = new ImageManager(new Driver);
$ivory = 'FBF9F4';
$paper = [0xFF, 0xFB, 0xF8];
$paperHex = 'FFFBF8';

/**
 * Lifts a part of the logo off its paper background. Each pixel gets the
 * transparency that, laid over the paper colour, gives back the original, so
 * the watercolour keeps its washes on the site's ivory. Pixels outside $keep
 * are dropped, which leaves out the gold circle where it runs close by.
 *
 * @param  array{int, int, int, int}  $box  [x0, y0, x1, y1] in the logo, inclusive
 * @param  list<array{int, int, int, int}>  $keep  rectangles in the logo to keep
 * @param  array{int, int, int}  $paper
 */
function cutOut(GdImage $logo, array $box, array $keep, array $paper): GdImage
{
    [$x0, $y0, $x1, $y1] = $box;
    $image = imagecreatetruecolor($x1 - $x0 + 1, $y1 - $y0 + 1);
    imagealphablending($image, false);
    imagesavealpha($image, true);
    $clear = imagecolorallocatealpha($image, $paper[0], $paper[1], $paper[2], 127);

    for ($y = $y0; $y <= $y1; $y++) {
        for ($x = $x0; $x <= $x1; $x++) {
            $inside = false;
            foreach ($keep as [$kx0, $ky0, $kx1, $ky1]) {
                $inside = $inside || ($x >= $kx0 && $x <= $kx1 && $y >= $ky0 && $y <= $ky1);
            }

            $c = imagecolorat($logo, $x, $y);
            $rgb = [($c >> 16) & 255, ($c >> 8) & 255, $c & 255];
            $alpha = 0.0;
            foreach ($rgb as $i => $v) {
                $alpha = max($alpha, max(0, $paper[$i] - $v) / $paper[$i]);
            }
            // JPEG noise on the paper would otherwise show as a faint haze.
            $alpha = $inside ? max(0.0, ($alpha - 0.03) / 0.97) : 0.0;

            if ($alpha <= 0.0) {
                imagesetpixel($image, $x - $x0, $y - $y0, $clear);

                continue;
            }

            [$r, $g, $b] = array_map(
                fn (int $i) => (int) round(min(255, max(0, ($rgb[$i] - $paper[$i] * (1 - $alpha)) / $alpha))),
                [0, 1, 2],
            );
            imagesetpixel($image, $x - $x0, $y - $y0, imagecolorallocatealpha($image, $r, $g, $b, (int) round(127 * (1 - $alpha))));
        }
    }

    return $image;
}

// Founder photograph: responsive WebP renditions.
foreach ([480, 960, 1200] as $w) {
    $manager->read("{$src}/founder-dunes.jpg")->scaleDown(width: $w)->toWebp(quality: 78)->save("{$out}/founder-dunes-{$w}.webp");
}

$logo = imagecreatefromjpeg("{$src}/logo.jpg");

// Mark: the aloe, the figure and the sun, without the circle around them.
$mark = $manager->read(cutOut($logo, [254, 167, 950, 782], [[500, 160, 770, 330], [440, 300, 820, 420], [240, 380, 970, 800]], $paper));
foreach ([96, 192] as $h) {
    (clone $mark)->scaleDown(height: $h)->toPng()->save("{$out}/logo-mark-{$h}.png");
}

// Wordmark "ALOE & SOUL" as drawn in the logo, for the header beside the mark.
$wordmark = $manager->read(cutOut($logo, [225, 810, 1047, 900], [[225, 810, 1047, 900]], $paper));
foreach ([40, 80] as $h) {
    (clone $wordmark)->scaleDown(height: $h)->toPng()->save("{$out}/logo-wordmark-{$h}.png");
}

// Full logo for structured data.
$manager->read("{$src}/logo.jpg")->scaleDown(width: 512)->toPng()->save("{$out}/logo-512.png");

// Icons: the mark on ivory (iOS fills transparency with black).
$manager->create(180, 180)->fill($ivory)->place((clone $mark)->scaleDown(width: 150), 'center')->toPng()->save("{$out}/apple-touch-icon.png");
$manager->create(192, 192)->fill($ivory)->place((clone $mark)->scaleDown(width: 168), 'center')->toPng()->save("{$out}/icon-192.png");

// Default social sharing image (1200×630): the full logo on its own paper colour.
$og = $manager->create(1200, 630)->fill($paperHex);
$og->place($manager->read("{$src}/logo.jpg")->scaleDown(height: 600), 'center');
$og->toJpeg(quality: 86)->save("{$out}/og-default.jpg");

// favicon.ico containing a 32×32 PNG (ICO files may embed PNG data): the mark
// on an ivory rounded square, drawn at 4× and scaled down for smooth corners,
// so it stays visible on dark browser tabs.
$tile = imagecreatetruecolor(128, 128);
imagealphablending($tile, false);
imagesavealpha($tile, true);
imagefill($tile, 0, 0, imagecolorallocatealpha($tile, 0xFB, 0xF9, 0xF4, 127));
$fill = imagecolorallocate($tile, 0xFB, 0xF9, 0xF4);
imagefilledrectangle($tile, 24, 0, 103, 127, $fill);
imagefilledrectangle($tile, 0, 24, 127, 103, $fill);
foreach ([[24, 24], [103, 24], [24, 103], [103, 103]] as [$cx, $cy]) {
    imagefilledellipse($tile, $cx, $cy, 48, 48, $fill);
}
$favicon = $manager->read($tile)->place((clone $mark)->scaleDown(width: 114), 'center')->scale(width: 32);
$png = (string) $favicon->toPng();
$ico = pack('vvv', 0, 1, 1).pack('CCCCvvVV', 32, 32, 0, 0, 1, 32, strlen($png), 22).$png;
file_put_contents(__DIR__.'/../../public/favicon.ico', $ico);

echo "Brand assets written to public/brand\n";
