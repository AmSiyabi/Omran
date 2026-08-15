<?php

/**
 * Generates small WebP variants of the brand logos for the public site.
 * Run inside the app container: php scripts/optimize-logos.php
 */
$variants = [
    ['logo-navy-transparent.png', 'logo-navy-sm.webp', 256],
    ['logo-cream-transparent.png', 'logo-cream-sm.webp', 192],
    ['logo-gold-transparent.png', 'logo-gold-sm.webp', 288],
];

foreach ($variants as [$src, $dst, $height]) {
    $source = imagecreatefrompng(__DIR__.'/../public/images/brand/'.$src);
    $width = (int) round(imagesx($source) * $height / imagesy($source));

    $out = imagecreatetruecolor($width, $height);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127));
    imagecopyresampled($out, $source, 0, 0, 0, 0, $width, $height, imagesx($source), imagesy($source));

    $target = __DIR__.'/../public/images/brand/'.$dst;
    imagewebp($out, $target, 85);

    echo $dst.' '.filesize($target)." bytes ({$width}x{$height})".PHP_EOL;
}
