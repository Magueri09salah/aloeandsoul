<?php

/**
 * Regenerates public/brand/* from the source files in database/assets.
 * Run from the project root:  php database/assets/build-brand-assets.php
 *
 * Sources (supplied by the client / taken from the client's current website):
 *   logo.png           — Aloe & Soul logo (mark + wordmark), 865×1024, transparent
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

// Founder photograph: responsive WebP renditions.
foreach ([480, 960, 1200] as $w) {
    $manager->read("{$src}/founder-dunes.jpg")->scaleDown(width: $w)->toWebp(quality: 78)->save("{$out}/founder-dunes-{$w}.webp");
}

// Logo mark (the circle with the aloe leaf and figure), cropped from the full logo.
$mark = $manager->read("{$src}/logo.png")->crop(560, 560, 147, 6);
foreach ([96, 192] as $h) {
    (clone $mark)->scaleDown(height: $h)->toPng()->save("{$out}/logo-mark-{$h}.png");
}

// Full logo for structured data (square, padded, ivory background).
$full = $manager->read("{$src}/logo.png")->scaleDown(height: 440);
$manager->create(512, 512)->fill($ivory)->place($full, 'center')->toPng()->save("{$out}/logo-512.png");

// Apple touch icon: mark on ivory.
$manager->create(180, 180)->fill($ivory)->place((clone $mark)->scaleDown(height: 150), 'center')->toPng()->save("{$out}/apple-touch-icon.png");

// Default social sharing image (1200×630).
$og = $manager->create(1200, 630)->fill($ivory);
$og->place($manager->read("{$src}/logo.png")->scaleDown(height: 420), 'center');
$og->toJpeg(quality: 86)->save("{$out}/og-default.jpg");

// favicon.ico containing a 32×32 PNG (ICO files may embed PNG data).
$png = (string) (clone $mark)->scaleDown(height: 30)->resizeCanvas(32, 32, 'transparent', 'center')->toPng();
$ico = pack('vvv', 0, 1, 1).pack('CCCCvvVV', 32, 32, 0, 0, 1, 32, strlen($png), 22).$png;
file_put_contents(__DIR__.'/../../public/favicon.ico', $ico);

echo "Brand assets written to public/brand\n";
