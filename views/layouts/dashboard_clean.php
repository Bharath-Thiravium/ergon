<?php
require_once __DIR__ . '/../../app/helpers/Security.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) { header('Location: /ergon/login'); exit; }
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 28800)) { session_unset(); session_destroy(); header('Location: /ergon/login?timeout=1'); exit; }
$_SESSION['last_activity'] = time();
$content = $content ?? '';
$userPrefs = ['theme' => 'light', 'dashboard_layout' => 'default', 'language' => 'en'];
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Security::escape(Security::generateCSRFToken()) ?>">
    <title><?= $title ?? 'Dashboard' ?> - ergon</title>
    <link rel="icon" type="image/x-icon" href="data:image/x-icon;base64,">
    
    <script src="/ergon/assets/js/theme-preload.js"></script>
    
    <style>
    html{box-sizing:border-box}*,*:before,*:after{box-sizing:inherit}
    body{font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;margin:0;padding:0;background:#f8fafc;overflow-x:hidden}
    .main-header{background:#000080;position:fixed;top:0;left:0;right:0;z-index:9999;width:100%;height:110px}
    .header__top{display:flex;align-items:center;justify-content:space-between;padding:12px 24px;height:60px}
    .header__nav-container{height:50px;border-top:1px solid rgba(255,255,255,0.1)}
    .main-content{margin-top:110px;padding:24px;background:#f8fafc;min-height:calc(100vh - 110px);width:100%;max-width:100vw;overflow-x:hidden;position:relative}
    .sidebar{position:fixed;left:-280px;top:0;width:280px;height:100vh;background:#fff;z-index:9998;transition:left 0.3s ease}
    .mobile-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9997;display:none}
    </style>
    
    <link href="/ergon/assets/css/ergon.css?v=1.0" rel="stylesheet">
</head>
<body>
    <main class="main-content">
        <?= $content ?>
    </main>
</body>
</html>