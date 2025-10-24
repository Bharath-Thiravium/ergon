<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= $title ?? 'ERGON' ?></title>
    
    <!-- Critical CSS inline -->
    <style><?= file_get_contents(__DIR__ . '/../../../public/assets/css/performance.css') ?></style>
    
    <!-- Preload key resources -->
    <link rel="preload" href="/ergon/public/assets/css/ergon.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="/ergon/public/assets/js/performance.js" as="script">
    
    <!-- DNS prefetch for external resources -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
</head>
<body>
    <div class="container-fluid">
        <nav style="padding:10px 0;border-bottom:1px solid #dee2e6;margin-bottom:20px">
            <a href="/ergon/dashboard" style="font-weight:bold;text-decoration:none;color:#007bff">ERGON</a>
            <span style="float:right">
                <a href="/ergon/settings" class="btn btn--secondary">Settings</a>
                <a href="/ergon/logout" class="btn btn--secondary">Logout</a>
            </span>
        </nav>
        
        <main>
            <?= $content ?>
        </main>
    </div>
    
    <!-- Load non-critical JS async -->
    <script src="/ergon/public/assets/js/performance.js" async></script>
    
    <!-- Load full CSS after page load -->
    <noscript><link rel="stylesheet" href="/ergon/public/assets/css/ergon.css"></noscript>
</body>
</html>