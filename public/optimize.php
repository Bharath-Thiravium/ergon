<?php
echo "Starting optimization...<br>";

$cssDir = __DIR__ . '/assets/css/';
$jsDir = __DIR__ . '/assets/js/';

echo "CSS Dir: $cssDir<br>";
echo "JS Dir: $jsDir<br>";

if (!is_dir($cssDir)) {
    echo "CSS directory not found<br>";
} else {
    echo "CSS directory exists<br>";
}

if (!is_dir($jsDir)) {
    echo "JS directory not found<br>";
} else {
    echo "JS directory exists<br>";
}

echo "Done.";
?>