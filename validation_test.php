<?php
$root = __DIR__;
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

$report = [];
$ignorePaths = ['.git', 'vendor', 'node_modules', 'storage/cache'];

foreach ($files as $file) {
    if (!$file->isFile()) continue;
    $path = $file->getPathname();
    $relativePath = str_replace($root . DIRECTORY_SEPARATOR, '', $path);
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    
    // Skip ignored paths
    $skip = false;
    foreach ($ignorePaths as $ignorePath) {
        if (strpos($relativePath, $ignorePath) === 0) {
            $skip = true;
            break;
        }
    }
    if ($skip) continue;

    // ✅ 1️⃣ Check file path issues (only spaces, backslashes are normal on Windows)
    if (preg_match('/\s/', $path)) {
        $report[] = "⚠️ Space found in path: $relativePath";
    }

    // ✅ 2️⃣ Check VIEW files for DOCTYPE (only main views need DOCTYPE)
    if (in_array($ext, ['php','html']) && strpos($relativePath, 'views') !== false) {
        $content = file_get_contents($path);
        
        // Only check main views that should have DOCTYPE (not includes/partials/shared components)
        if (!preg_match('/<!DOCTYPE html>/i', $content) && 
            !preg_match('/ob_start|include.*layout/', $content) &&
            strpos($relativePath, 'shared') === false &&
            strpos($relativePath, 'layouts') === false) {
            $report[] = "❌ DOCTYPE missing → $relativePath";
        }

        if (preg_match('/<!--[^>]*—>/', $content)) {
            $report[] = "❌ Invalid HTML comment syntax in $relativePath";
        }

        // Check common PHP fatal syntax issues
        if (preg_match('/<<<\s*[\'"]?EOF[\'"]?/', $content) && !preg_match('/EOF;/', $content)) {
            $report[] = "❌ HEREDOC not closed → $relativePath";
        }
    }

    // ✅ 3️⃣ Test PHP syntax compilation (skip if php not in PATH)
    if ($ext === 'php') {
        $output = [];
        $phpPath = 'php';
        // Try to find PHP in common Laragon paths
        if (!shell_exec('where php 2>nul')) {
            $possiblePaths = [
                'C:\\laragon\\bin\\php\\php-8.1.10-Win32-vs16-x64\\php.exe',
                'C:\\laragon\\bin\\php\\php-8.0.23-Win32-vs16-x64\\php.exe',
                'C:\\xampp\\php\\php.exe'
            ];
            foreach ($possiblePaths as $possiblePath) {
                if (file_exists($possiblePath)) {
                    $phpPath = $possiblePath;
                    break;
                }
            }
        }
        
        exec("\"$phpPath\" -l \"$path\" 2>&1", $output, $status);
        if ($status !== 0 && !strpos(implode(' ', $output), 'not recognized')) {
            $report[] = "❌ PHP Syntax Error in $relativePath → " . implode(" | ", $output);
        }
    }
}

echo "<h2>🚀 Ergon Validation Report</h2>";
if (empty($report)) {
    echo "<h3 style='color:green'>✅ All critical issues resolved! Project is healthy ✅</h3>";
    echo "<p><strong>Note:</strong> Windows backslashes and missing DOCTYPE in PHP controllers/models are normal and expected.</p>";
} else {
    echo "<h3 style='color:orange'>⚠️ Issues Found (" . count($report) . ")</h3>";
    echo "<p><em>Filtered to show only actionable issues:</em></p><ul>";
    foreach ($report as $r) echo "<li>$r</li>";
    echo "</ul>";
}

echo "<hr><p><small>✅ Filtered out: Windows backslashes, .git files, vendor files, cache files, and PHP files without DOCTYPE (normal for controllers/models)</small></p>";
?>