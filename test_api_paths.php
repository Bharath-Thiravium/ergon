<?php
echo "Testing API paths:\n\n";

$paths = [
    '/ergon/api/task-categories',
    '/ergon/public/api/task-categories.php',
    'api/task-categories',
    'public/api/task-categories.php'
];

foreach ($paths as $path) {
    $url = "http://localhost" . $path . "?department_id=1";
    echo "Testing: $url\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        if ($data && isset($data['categories'])) {
            echo "Categories found: " . count($data['categories']) . "\n";
            if (count($data['categories']) > 0) {
                echo "First category: " . $data['categories'][0]['category_name'] . "\n";
            }
        } else {
            echo "Response: " . substr($response, 0, 100) . "\n";
        }
    }
    echo "\n";
}
?>