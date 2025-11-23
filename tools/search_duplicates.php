<?php
// tools/search_duplicates.php
$root = realpath(__DIR__ . '/../');
$pattern = '/AttendanceController.php/i';

function find_files($dir, $pattern) {
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    $found = [];
    foreach ($rii as $file) {
        if ($file->isDir()) continue;
        if (preg_match($pattern, $file->getFilename())) {
            $found[] = realpath($file->getPathname());
        }
    }
    return $found;
}

echo "<h2>Searching for files named AttendanceController.php under {$root}</h2>";
$matches = find_files($root, $pattern);
if (empty($matches)) {
    echo "<p>No matches found.</p>";
} else {
    echo "<ul>";
    foreach ($matches as $m) {
        echo "<li>{$m} — size: " . filesize($m) . " — mtime: " . date('Y-m-d H:i:s', filemtime($m)) . "</li>";
    }
    echo "</ul>";
    echo "<p>If more than one path is listed, Hostinger could be loading a different copy.</p>";
}
?>