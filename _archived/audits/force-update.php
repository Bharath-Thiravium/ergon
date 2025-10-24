<?php
// Force update script
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Force Update</title>
    <meta http-equiv="refresh" content="3;url=/ergon/dashboard">
</head>
<body>
    <h2>🔄 Forcing Update...</h2>
    <p>Cache cleared at: <?= date('Y-m-d H:i:s') ?></p>
    <p>Redirecting to dashboard in 3 seconds...</p>
    <script>
        // Clear all caches
        if ('caches' in window) {
            caches.keys().then(names => {
                names.forEach(name => caches.delete(name));
            });
        }
        
        // Force reload after 2 seconds
        setTimeout(() => {
            window.location.href = '/ergon/dashboard?nocache=' + Date.now();
        }, 2000);
    </script>
</body>
</html>