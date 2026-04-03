<?php
/**
 * Run once: php generate_icons.php
 * Generates PWA icons at assets/icons/icon-192.png and icon-512.png
 */

$sizes = [192, 512];
$outputDir = __DIR__ . '/assets/icons/';

foreach ($sizes as $size) {
    $img = imagecreatetruecolor($size, $size);

    // Background: dark navy #0a0f2c
    $bg = imagecolorallocate($img, 10, 15, 44);
    imagefill($img, 0, 0, $bg);

    // Rounded corners via arc masking
    $corner = (int)($size * 0.18);
    imagefilledarc($img, $corner, $corner, $corner * 2, $corner * 2, 180, 270, $bg, IMG_ARC_PIE);
    imagefilledarc($img, $size - $corner, $corner, $corner * 2, $corner * 2, 270, 360, $bg, IMG_ARC_PIE);
    imagefilledarc($img, $corner, $size - $corner, $corner * 2, $corner * 2, 90, 180, $bg, IMG_ARC_PIE);
    imagefilledarc($img, $size - $corner, $size - $corner, $corner * 2, $corner * 2, 0, 90, $bg, IMG_ARC_PIE);

    // Accent circle: indigo #4f46e5
    $accent = imagecolorallocate($img, 79, 70, 229);
    $cx = (int)($size / 2);
    $cy = (int)($size / 2);
    $r  = (int)($size * 0.32);
    imagefilledellipse($img, $cx, $cy, $r * 2, $r * 2, $accent);

    // Letter "E" in white — drawn with thick lines
    $white = imagecolorallocate($img, 255, 255, 255);
    $lw = max(2, (int)($size * 0.04));
    $ex = (int)($cx - $r * 0.45);
    $ey = (int)($cy - $r * 0.55);
    $ew = (int)($r * 0.9);
    $eh = (int)($r * 1.1);

    imagesetthickness($img, $lw);
    // Vertical stroke
    imageline($img, $ex, $ey, $ex, $ey + $eh, $white);
    // Top horizontal
    imageline($img, $ex, $ey, $ex + $ew, $ey, $white);
    // Middle horizontal
    imageline($img, $ex, $ey + (int)($eh / 2), $ex + (int)($ew * 0.75), $ey + (int)($eh / 2), $white);
    // Bottom horizontal
    imageline($img, $ex, $ey + $eh, $ex + $ew, $ey + $eh, $white);

    imagepng($img, $outputDir . "icon-{$size}.png");
    imagedestroy($img);
    echo "Created icon-{$size}.png\n";
}
echo "Done.\n";
