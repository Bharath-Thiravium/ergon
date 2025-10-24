<?php
// Quick CSS accessibility test after .htaccess fix
echo "🔧 CSS Fix Verification\n\n";

$cssFiles = [
    'public/assets/css/ergon.css',
    'public/assets/css/components.css'
];

foreach ($cssFiles as $file) {
    if (file_exists($file)) {
        echo "✅ {$file} - File exists (" . filesize($file) . " bytes)\n";
        
        // Test URL accessibility
        $url = "https://athenas.co.in/ergon/{$file}";
        $headers = get_headers($url, 1);
        $status = $headers[0];
        
        if (strpos($status, '200') !== false) {
            echo "✅ URL accessible: {$status}\n";
        } else {
            echo "❌ URL issue: {$status}\n";
        }
    } else {
        echo "❌ {$file} - File not found\n";
    }
    echo "\n";
}

echo "🌐 Test URLs:\n";
echo "- https://athenas.co.in/ergon/public/assets/css/ergon.css\n";
echo "- https://athenas.co.in/ergon/public/assets/css/components.css\n";
?>