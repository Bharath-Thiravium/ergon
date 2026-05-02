<?php
// Create a simple placeholder image for BKGE company
$width = 64;
$height = 64;

// Create image
$image = imagecreate($width, $height);

// Colors
$bg_color = imagecolorallocate($image, 240, 248, 255); // Light blue background
$text_color = imagecolorallocate($image, 0, 0, 128); // Navy blue text
$border_color = imagecolorallocate($image, 0, 0, 128); // Navy blue border

// Fill background
imagefill($image, 0, 0, $bg_color);

// Add border
imagerectangle($image, 0, 0, $width-1, $height-1, $border_color);

// Add text
$font_size = 3;
$text = "BKGE";
$text_width = imagefontwidth($font_size) * strlen($text);
$text_height = imagefontheight($font_size);
$x = ($width - $text_width) / 2;
$y = ($height - $text_height) / 2;

imagestring($image, $font_size, $x, $y, $text, $text_color);

// Save as PNG
$logo_dir = __DIR__ . '/storage/company/logos/';
if (!is_dir($logo_dir)) {
    mkdir($logo_dir, 0755, true);
}

imagepng($image, $logo_dir . 'BKGE.png');
imagedestroy($image);

echo "BKGE logo created successfully!\n";
?>