<?php

/**
 * PWA icon generation (Phase 7): navy tile + the brand's gold star,
 * from public/images/brand/star-gold-transparent.png (800×800).
 * Regular icons get a 22% margin; maskable icons a 40% safe zone
 * (star inside the inner 60% circle per the maskable spec).
 * Run: php scripts/pwa-icons.php
 */
$star = imagecreatefrompng(__DIR__.'/../public/images/brand/star-gold-transparent.png');
imagesavealpha($star, true);

$navy = ['r' => 0x16, 'g' => 0x20, 'b' => 0x2F];

$targets = [
    ['size' => 192, 'margin' => 0.22, 'file' => 'icon-192.png'],
    ['size' => 512, 'margin' => 0.22, 'file' => 'icon-512.png'],
    ['size' => 192, 'margin' => 0.40, 'file' => 'icon-maskable-192.png'],
    ['size' => 512, 'margin' => 0.40, 'file' => 'icon-maskable-512.png'],
    ['size' => 180, 'margin' => 0.22, 'file' => 'apple-touch-icon.png'],
];

$outDir = __DIR__.'/../public/images/pwa';

if (! is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

foreach ($targets as $target) {
    $size = $target['size'];
    $canvas = imagecreatetruecolor($size, $size);
    $background = imagecolorallocate($canvas, $navy['r'], $navy['g'], $navy['b']);
    imagefill($canvas, 0, 0, $background);

    $inner = (int) round($size * (1 - 2 * $target['margin']));
    $offset = (int) round(($size - $inner) / 2);

    imagecopyresampled(
        $canvas, $star,
        $offset, $offset, 0, 0,
        $inner, $inner, imagesx($star), imagesy($star),
    );

    imagepng($canvas, "{$outDir}/{$target['file']}", 9);

    echo "{$target['file']} ({$size}px)\n";
}
