<?php
ob_start();
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/../../app/helpers/Security.php';
require_once __DIR__ . '/../../app/helpers/SecurityHeaders.php';
require_once __DIR__ . '/../../app/helpers/ModuleManager.php';

// Check module status once at the top
$tasksDisabled = false;
$dailyPlannerDisabled = false;
$followupsDisabled = false;
$systemAdminDisabled = false;
$usersDisabled = false;
$departmentsDisabled = false;
$projectsDisabled = false;
$financeDisabled = false;
$reportsDisabled = false;
$analyticsDisabled = false;

try {
    $tasksDisabled = ModuleManager::isModuleDisabled('tasks');
    $dailyPlannerDisabled = ModuleManager::isModuleDisabled('daily_planner');
    $followupsDisabled = ModuleManager::isModuleDisabled('followups');
    $systemAdminDisabled = ModuleManager::isModuleDisabled('system_admin');
    $usersDisabled = ModuleManager::isModuleDisabled('users');
    $departmentsDisabled = ModuleManager::isModuleDisabled('departments');
    $projectsDisabled = ModuleManager::isModuleDisabled('projects');
    $financeDisabled = ModuleManager::isModuleDisabled('finance');
    $reportsDisabled = ModuleManager::isModuleDisabled('reports');
    $analyticsDisabled = ModuleManager::isModuleDisabled('analytics');
} catch (Exception $e) {
    // Silently fail - all modules will appear enabled
}

SecurityHeaders::apply();
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) { header('Location: /ergon/login'); exit; }
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 28800)) { session_unset(); session_destroy(); header('Location: /ergon/login?timeout=1'); exit; }

// Note: Success/error messages are now handled by individual pages

// Check if user is still active and role hasn't changed
try {
    require_once __DIR__ . '/../../app/config/database.php';
    $db = Database::connect();
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    $stmt = $db->prepare("SELECT status, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || $user['status'] !== 'active') {
        session_unset();
        session_destroy();
        header('Location: /ergon/login?deactivated=1');
        exit;
    }
    if ($user['role'] !== $_SESSION['role']) {
        session_unset();
        session_destroy();
        header('Location: /ergon/login?role_changed=1');
        exit;
    }
} catch (Exception $e) {
    error_log('User status check failed: ' . $e->getMessage());
    // Redirect to login on database connection failure
    session_unset();
    session_destroy();
    header('Location: /ergon/login?error=database');
    exit;
}
$_SESSION['last_activity'] = time();
$content = $content ?? '';
$userPrefs = ['theme' => 'light', 'dashboard_layout' => 'default', 'language' => 'en'];
ob_end_clean();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="csrf-token" content="<?= Security::escape(Security::generateCSRFToken()) ?>">
    <title><?= $title ?? 'Dashboard' ?> - ergon</title>
    <link rel="icon" type="image/x-icon" href="data:image/x-icon;base64,">
    
    <script src="/ergon/assets/js/theme-preload.js?v=<?= time() ?>"></script>
    <script>const APP_BASE = (location.protocol + '//' + location.host + '/ergon');</script>
        <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[title]').forEach(function(el) {
            el.setAttribute('data-tooltip', el.getAttribute('title'));
            el.removeAttribute('title');
        });
    });
    </script>
    
    <style>
    /* Critical inline CSS to prevent FOUC and layout forcing */
    html{box-sizing:border-box}*,*:before,*:after{box-sizing:inherit}
    body{font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;margin:0;padding:0;background:#f8fafc;overflow-x:hidden}
    .main-header{background:#000080;position:fixed;top:0;left:0;right:0;z-index:1000;width:100%;height:110px}
    .header__top{display:flex;align-items:center;justify-content:space-between;padding:12px 24px;height:60px}
    .header__nav-container{height:50px;/*border-top:1px solid rgba(255,255,255,0.1)*/}
    .main-content{margin:110px 0 0 0;padding:24px 24px 24px 0;background:#f8fafc;min-height:calc(100vh - 110px);width:100%;max-width:100vw;overflow-x:hidden;position:relative}
    .sidebar{position:fixed;left:-280px;top:0;width:280px;height:100vh;background:#fff;z-index:998;transition:left 0.3s ease}
    .mobile-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:997;display:none}
    
    /* Smart Attendance Button States - Enhanced Visibility */
    .btn--attendance-toggle{background:#10b981 !important;border:3px solid #059669 !important;color:#ffffff !important;font-weight:700 !important;text-shadow:0 2px 4px rgba(0,0,0,0.4) !important;box-shadow:0 4px 12px rgba(16,185,129,0.4) !important;transition:all 0.3s ease;min-height:44px !important;padding:8px 16px !important;border-radius:8px !important}
    .btn--attendance-toggle.state-out{background:#10b981 !important;border:3px solid #059669 !important;color:#ffffff !important;font-weight:700 !important;text-shadow:0 2px 4px rgba(0,0,0,0.4) !important;box-shadow:0 4px 12px rgba(16,185,129,0.4) !important}
    .btn--attendance-toggle.state-in{background:#dc2626 !important;color:#ffffff !important;border:3px solid #991b1b !important;box-shadow:0 4px 16px rgba(220,38,38,0.6) !important;font-weight:800 !important;text-shadow:0 2px 4px rgba(0,0,0,0.5) !important;animation:pulse-red 2s infinite}
    .btn--attendance-toggle.state-completed{background:#059669 !important;border:3px solid #047857 !important;color:#ffffff !important;opacity:1 !important;box-shadow:0 4px 12px rgba(5,150,105,0.4) !important;font-weight:700 !important;text-shadow:0 2px 4px rgba(0,0,0,0.4) !important}
    .btn--attendance-toggle.state-leave{background:#f59e0b !important;border:3px solid #d97706 !important;color:#ffffff !important;opacity:1 !important;font-weight:700 !important;text-shadow:0 2px 4px rgba(0,0,0,0.4) !important}
    @keyframes pulse-red{0%,100%{box-shadow:0 4px 16px rgba(220,38,38,0.6)}50%{box-shadow:0 6px 20px rgba(220,38,38,0.8)}}
    
    /* Universal Message Modal */
    .universal-modal{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:1000000;display:none;align-items:center;justify-content:center}
    .universal-modal.show{display:flex}
    .universal-modal-content{background:#fff;border-radius:16px;padding:32px;max-width:420px;width:90%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.3);transform:scale(0.9);transition:transform 0.2s ease}
    .universal-modal.show .universal-modal-content{transform:scale(1)}
    .universal-modal-icon{font-size:64px;margin-bottom:16px;line-height:1}
    .universal-modal-title{font-size:20px;font-weight:700;margin-bottom:12px;color:#1f2937}
    .universal-modal-message{font-size:16px;margin-bottom:24px;color:#4b5563;line-height:1.5}
    .universal-modal-close{background:#3b82f6;color:#fff;border:none;padding:12px 32px;border-radius:8px;cursor:pointer;font-size:15px;font-weight:600;transition:background 0.2s ease}
    .universal-modal-close:hover{background:#2563eb}
    .universal-modal.success .universal-modal-icon{color:#10b981}
    .universal-modal.success .universal-modal-close{background:#10b981}
    .universal-modal.success .universal-modal-close:hover{background:#059669}
    .universal-modal.error .universal-modal-icon{color:#ef4444}
    .universal-modal.error .universal-modal-close{background:#ef4444}
    .universal-modal.error .universal-modal-close:hover{background:#dc2626}
    .universal-modal.warning .universal-modal-icon{color:#f59e0b}
    .universal-modal.warning .universal-modal-close{background:#f59e0b}
    .universal-modal.warning .universal-modal-close:hover{background:#d97706}
    
    /* Global Navigation Buttons - Desktop Only */
    .global-back-btn{position:fixed !important;top:400px !important;left:20px !important;right:auto !important;z-index:1000;background:rgba(255,255,255,0.95);color:#374151;border:1px solid rgba(0,0,0,0.1);border-radius:50%;width:44px;height:44px;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,0.15);backdrop-filter:blur(10px);transition:all 0.2s ease}
    .global-forward-btn{position:fixed !important;top:400px !important;right:20px !important;left:auto !important;z-index:1000;background:rgba(255,255,255,0.95);color:#374151;border:1px solid rgba(0,0,0,0.1);border-radius:50%;width:44px;height:44px;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,0.15);backdrop-filter:blur(10px);transition:all 0.2s ease}
    .global-back-btn:hover,.global-forward-btn:hover{background:rgba(255,255,255,1);transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,0,0,0.2)}
    .global-back-btn svg,.global-forward-btn svg{stroke:#374151;transition:color 0.2s ease}
    .global-back-btn:hover svg,.global-forward-btn:hover svg{stroke:#1f2937}
    [data-theme="dark"] .global-back-btn,[data-theme="dark"] .global-forward-btn{background:rgba(31,41,55,0.95);border-color:rgba(255,255,255,0.1);color:#f1f5f9}
    [data-theme="dark"] .global-back-btn:hover,[data-theme="dark"] .global-forward-btn:hover{background:rgba(31,41,55,1)}
    [data-theme="dark"] .global-back-btn svg,[data-theme="dark"] .global-forward-btn svg{stroke:#f1f5f9}
    @media (max-width:1024px){.global-back-btn,.global-forward-btn{display:none}}
    
    /* Notification Enhancements */
    .notification-item--unread{background:#f0f9ff;border-left:3px solid #0ea5e9}
    .unread-dot{color:#ef4444;font-size:12px;margin-left:4px}
    .notification-badge{background:#ef4444;color:#fff;border-radius:50%;padding:2px 6px;font-size:11px;font-weight:600;min-width:18px;text-align:center;position:absolute;top:-8px;right:-8px;z-index:10}
    .notification-badge.has-notifications{animation:pulse 2s infinite}
    .notification-dropdown{max-height:400px;overflow-y:auto;box-shadow:0 10px 25px rgba(0,0,0,0.15);background:#fff;border-radius:8px;border:1px solid #e2e8f0;min-width:320px}
    @keyframes pulse{0%{transform:scale(1)}50%{transform:scale(1.1)}100%{transform:scale(1)}}
    .control-btn{position:relative}
    
    /* Alert visibility - Allow alerts to be shown when needed */
    .alert.d-none, .alert--error.d-none, .alert--success.d-none, .alert--warning.d-none, .alert--info.d-none {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
    }
    
    .control-btn{position:relative}
    
    /* Attendance Notification Styles */
    .attendance-notification{position:fixed;top:20px;right:20px;background:#fff;border-radius:8px;padding:16px 20px;box-shadow:0 4px 20px rgba(0,0,0,0.15);z-index:50000;transform:translateX(100%);transition:transform 0.3s ease;max-width:350px;border-left:4px solid #10b981}
    .attendance-notification.show{transform:translateX(0)}
    .attendance-notification.success{border-left-color:#10b981}
    .attendance-notification.error{border-left-color:#ef4444}
    .attendance-notification.warning{border-left-color:#f59e0b}
    .notification-content{display:flex;align-items:center;gap:12px;font-size:14px;font-weight:500}
    .notification-content i{font-size:18px}
    .attendance-notification.success .notification-content i{color:#10b981}
    .attendance-notification.error .notification-content i{color:#ef4444}
    .attendance-notification.warning .notification-content i{color:#f59e0b}
    
    /* Mobile Dialog Styles */
    .attendance-dialog-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000000;display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity 0.3s ease}
    .attendance-dialog-overlay.show{opacity:1}
    .attendance-dialog{background:#fff;border-radius:12px;padding:24px;max-width:320px;width:90%;text-align:center;transform:scale(0.9);transition:transform 0.3s ease}
    .attendance-dialog-overlay.show .attendance-dialog{transform:scale(1)}
    .dialog-icon{font-size:48px;margin-bottom:16px}
    .dialog-icon i{color:#10b981}
    .attendance-dialog.error .dialog-icon i{color:#ef4444}
    .attendance-dialog.warning .dialog-icon i{color:#f59e0b}
    .dialog-message{font-size:16px;margin-bottom:20px;color:#333;line-height:1.5}
    .dialog-close{background:#007bff;color:#fff;border:none;padding:10px 24px;border-radius:6px;cursor:pointer;font-size:14px;font-weight:600}
    .dialog-close:hover{background:#0056b3}
    
    @media (max-width:768px){
        .attendance-notification{top:10px;right:10px;left:10px;max-width:none;transform:translateY(-100%)}
        .attendance-notification.show{transform:translateY(0)}
    }
    </style>
    
    <link href="/ergon/assets/css/bootstrap-icons.min.css?v=1.0" rel="stylesheet">
    <link href="/ergon/assets/css/ergon.css?v=<?= time() ?>" rel="stylesheet">
    <link href="/ergon/assets/css/theme-enhanced.css?v=1.0" rel="stylesheet">
    <link href="/ergon/assets/css/utilities-new.css?v=1.0" rel="stylesheet">
    <link href="/ergon/assets/css/instant-theme.css?v=1.0" rel="stylesheet">
    <link href="/ergon/assets/css/global-tooltips.css?v=1.0" rel="stylesheet">


    <link href="/ergon/assets/css/responsive-mobile.css?v=1.0" rel="stylesheet">
    <link href="/ergon/assets/_archive_legacy/css/user-management-mobile.css?v=1.0" rel="stylesheet">
    <link href="/ergon/assets/_archive_legacy/css/management-mobile-fix.css?v=1.0" rel="stylesheet">
    <!-- Mobile Dark Theme Fixes - Critical for visibility -->
    <link href="/ergon/assets/css/mobile-dark-theme-fixes.css?v=<?= time() ?>" rel="stylesheet">
    <!-- Dark Mode Alert & Notification Fixes - Critical for visibility -->
    <link href="/ergon/assets/css/dark-mode-alerts-fix.css?v=<?= time() ?>" rel="stylesheet">
    <!-- New Simplified Modal CSS -->
    <link href="/ergon/assets/css/modal.css?v=<?= time() ?>" rel="stylesheet">
    <!-- Dashboard overrides loaded last to ensure overrides on compiled CSS in deployments -->
    <link href="/ergon/assets/css/ergon-overrides.css?v=<?= time() ?>" rel="stylesheet">
    <link href="/ergon/assets/css/access-denied.css?v=1.0" rel="stylesheet">
    <link href="/ergon/assets/css/premium-navigation.css?v=1.0" rel="stylesheet">
    <?php if (isset($active_page) && $active_page === 'dashboard' && isset($_SESSION['role']) && $_SESSION['role'] === 'owner'): ?>
    <link href="/ergon/assets/css/dashboard-owner.css?v=1.0" rel="stylesheet">
    <?php endif; ?>
    <?php if (isset($additional_css)): ?>
    <?= $additional_css ?>
    <?php endif; ?>

    <script src="/ergon/assets/js/theme-switcher.js?v=1.0" defer></script>
    <script src="/ergon/assets/js/ergon-core.min.js?v=1.0" defer></script>
    <!-- New Simplified Modal JS -->
    <script src="/ergon/assets/js/modal.js?v=1.0" defer></script>
    <script src="/ergon/assets/_archive_legacy/js/action-button-clean.js?v=1.0" defer></script>
    <script src="/ergon/assets/_archive_legacy/js/mobile-enhanced.js?v=1.0" defer></script>
    <script src="/ergon/assets/js/mobile-table-cards.js?v=1.0" defer></script>
    <script src="/ergon/assets/js/table-utils.js?v=1.0" defer></script>
    <script src="/ergon/assets/js/user-status-check.js?v=1.0" defer></script>
    <script src="/ergon/assets/js/premium-navigation.js?v=1.0" defer></script>
    <!-- Dark Mode Alert Enhancements -->
    <script src="/ergon/assets/js/dark-mode-alerts.js?v=<?= time() ?>" defer></script>

    <?php if (isset($_GET['validate']) && $_GET['validate'] === 'mobile'): ?>
    <script src="/ergon/assets/js/mobile-validation.js?v=<?= time() ?>" defer></script>
    <?php endif; ?>
</head>
<body data-layout="<?= isset($userPrefs['dashboard_layout']) ? $userPrefs['dashboard_layout'] : 'default' ?>" data-lang="<?= isset($userPrefs['language']) ? $userPrefs['language'] : 'en' ?>" data-page="<?= isset($active_page) ? $active_page : '' ?>" data-user-role="<?= $_SESSION['role'] ?? 'user' ?>" data-theme="<?= isset($userPrefs['theme']) ? $userPrefs['theme'] : 'light' ?>">
    <header class="main-header">
        <div class="header__top">
            <div class="header__brand">
                <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                    <i class="bi bi-list"></i>
                </button>
                <span class="brand-icon"><i class="bi bi-compass-fill"></i></span>
                <span class="brand-text">Ergon</span>
                <span class="role-badge"><?= htmlspecialchars(ucfirst($_SESSION['role'] ?? 'User'), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            
            <div class="header__controls">
                <div class="attendance-controls">
                    <button class="btn btn--attendance-toggle" id="attendanceToggle" onclick="toggleAttendance()" title="Toggle Attendance">
                        <div class="attendance-icon">
                            <i class="bi bi-play-fill" id="attendanceIcon"></i>
                        </div>
                        <span class="btn-text" id="attendanceText">Clock In</span>
                        <div class="attendance-pulse"></div>
                    </button>
                </div>
                <button class="control-btn" id="theme-toggle" title="Toggle Theme">
                    <i class="bi bi-<?= (isset($userPrefs['theme']) && $userPrefs['theme'] === 'dark') ? 'sun-fill' : 'moon-fill' ?>"></i>
                </button>
                <?php 
                $notificationsDisabled = false;
                try {
                    $notificationsDisabled = ModuleManager::isModuleDisabled('notifications');
                } catch (Exception $e) {
                    // Silently fail - notifications will appear enabled
                }
                ?>
                <?php if (!$notificationsDisabled): ?>
                <button class="control-btn notification-btn" id="notificationBtn" onclick="toggleNotifications(event)" title="Notifications">
                    <i class="bi bi-bell-fill"></i>
                    <span class="notification-badge" id="notificationBadge" style="display:none;">0</span>
                </button>
                <?php else: ?>
                <button class="control-btn" style="opacity: 0.5; cursor: not-allowed;" title="Notifications (Disabled)" onclick="return false;">
                    <i class="bi bi-bell-slash"></i>
                </button>
                <?php endif; ?>
                <button class="profile-btn" id="profileButton" type="button">
                    <span class="profile-avatar"><?= htmlspecialchars(strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)), ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="profile-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="dropdown-arrow">▼</span>
                </button>
                
                <div class="profile-menu" id="profileMenu">
                    <a href="/ergon/profile" class="profile-menu-item">
                        <span class="menu-icon"><i class="bi bi-person-fill"></i></span>
                        My Profile
                    </a>
                    <a href="/ergon/profile/change-password" class="profile-menu-item">
                        <span class="menu-icon"><i class="bi bi-lock-fill"></i></span>
                        Change Password
                    </a>
                    <div class="profile-menu-divider"></div>
                    <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['owner', 'admin']) && !$systemAdminDisabled): ?>
                    <a href="/ergon/settings" class="profile-menu-item">
                        <span class="menu-icon"><i class="bi bi-gear-fill"></i></span>
                        System Settings
                    </a>
                    <?php endif; ?>
                    <div class="profile-menu-divider"></div>
                    <a href="/ergon/logout" class="profile-menu-item profile-menu-item--danger">
                        <span class="menu-icon"><i class="bi bi-box-arrow-right"></i></span>
                        Logout
                    </a>
                </div>
                
                <!-- Universal Message Modal -->
                <div id="universalModal" class="universal-modal">
                    <div class="universal-modal-content">
                        <div class="universal-modal-icon" id="universalIcon"></div>
                        <div class="universal-modal-title" id="universalTitle"></div>
                        <div class="universal-modal-message" id="universalMessage"></div>
                        <button class="universal-modal-close" onclick="closeUniversalModal()">OK</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="header__nav-container">
            <nav class="header__nav">
                <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['owner', 'company_owner'])): ?>
                    <?php if ($_SESSION['role'] === 'owner'): ?>
                    <div class="nav-dropdown">
                        <button class="nav-dropdown-btn" onclick="toggleDropdown('overview')">
                            <span class="nav-icon"><i class="bi bi-graph-up"></i></span>
                            Overview
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        <div class="nav-dropdown-menu" id="overview">
                            <a href="/ergon/dashboard" class="nav-dropdown-item <?= ($active_page ?? '') === 'dashboard' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon"><i class="bi bi-speedometer2"></i></span>
                                Dashboard
                            </a>
                            <a href="/ergon/gamification/team-competition" class="nav-dropdown-item <?= ($active_page ?? '') === 'team-competition' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon"><i class="bi bi-trophy-fill"></i></span>
                                Competition
                            </a>
                        </div>
                    </div>
                    <div class="nav-dropdown">
                        <button class="nav-dropdown-btn" onclick="toggleDropdown('management')">
                            <span class="nav-icon">🔧</span>
                            Management
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        <div class="nav-dropdown-menu" id="management">
                            <a href="/ergon/system-admin" class="nav-dropdown-item <?= ($active_page ?? '') === 'system-admin' ? 'nav-dropdown-item--active' : '' ?> <?= $systemAdminDisabled ? 'nav-dropdown-item--disabled' : '' ?>">
                                <span class="nav-icon">🔧</span>
                                System
                                <?php if ($systemAdminDisabled): ?><span class="premium-icon">🔒</span><?php endif; ?>
                            </a>
                            <a href="/ergon/admin/management" class="nav-dropdown-item <?= ($active_page ?? '') === 'admin' ? 'nav-dropdown-item--active' : '' ?> <?= $usersDisabled ? 'nav-dropdown-item--disabled' : '' ?>">
                                <span class="nav-icon">👥</span>
                                Users
                                <?php if ($usersDisabled): ?><span class="premium-icon">🔒</span><?php endif; ?>
                            </a>
                            <a href="/ergon/departments" class="nav-dropdown-item <?= ($active_page ?? '') === 'departments' ? 'nav-dropdown-item--active' : '' ?> <?= $departmentsDisabled ? 'nav-dropdown-item--disabled' : '' ?>">
                                <span class="nav-icon">🏢</span>
                                Departments
                                <?php if ($departmentsDisabled): ?><span class="premium-icon">🔒</span><?php endif; ?>
                            </a>
                            <a href="/ergon/project-management" class="nav-dropdown-item <?= ($active_page ?? '') === 'project-management' ? 'nav-dropdown-item--active' : '' ?> <?= $projectsDisabled ? 'nav-dropdown-item--disabled' : '' ?>">
                                <span class="nav-icon">📁</span>
                                Projects
                                <?php if ($projectsDisabled): ?><span class="premium-icon">🔒</span><?php endif; ?>
                            </a>
                            <a href="/ergon/modules" class="nav-dropdown-item <?= ($active_page ?? '') === 'modules' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">🔧</span>
                                Modules
                            </a>
                        </div>
                    </div>
                    <div class="nav-dropdown">
                        <button class="nav-dropdown-btn" onclick="toggleDropdown('operations')">
                            <span class="nav-icon">✅</span>
                            Operations
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        <div class="nav-dropdown-menu" id="operations">
                            <a href="/ergon/tasks" class="nav-dropdown-item <?= ($active_page ?? '') === 'tasks' ? 'nav-dropdown-item--active' : '' ?> <?= $tasksDisabled ? 'nav-dropdown-item--disabled' : '' ?>">
                                <span class="nav-icon">✅</span>
                                Tasks
                                <?php if ($tasksDisabled): ?><span class="premium-icon">🔒</span><?php endif; ?>
                            </a>
                            <a href="/ergon/contacts/followups" class="nav-dropdown-item <?= ($active_page ?? '') === 'contact_followups' ? 'nav-dropdown-item--active' : '' ?> <?= $followupsDisabled ? 'nav-dropdown-item--disabled' : '' ?>">
                                <span class="nav-icon">📞</span>
                                Follow-ups
                                <?php if ($followupsDisabled): ?><span class="premium-icon">🔒</span><?php endif; ?>
                            </a>
                            <a href="/ergon/site-reports" class="nav-dropdown-item <?= ($active_page ?? '') === 'site_reports' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">📋</span>
                                Site Reports
                            </a>
                        </div>
                    </div>
                    <div class="nav-dropdown">
                        <button class="nav-dropdown-btn" onclick="toggleDropdown('hrfinance')">
                            <span class="nav-icon">💰</span>
                            HR & Finance
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        <div class="nav-dropdown-menu" id="hrfinance">
                            <a href="/ergon/leaves" class="nav-dropdown-item <?= ($active_page ?? '') === 'leaves' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">📅</span>
                                Leaves
                            </a>
                            <a href="/ergon/expenses" class="nav-dropdown-item <?= ($active_page ?? '') === 'expenses' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">💰</span>
                                Expenses
                            </a>
                            <a href="/ergon/advances" class="nav-dropdown-item <?= ($active_page ?? '') === 'advances' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">💳</span>
                                Advances
                            </a>
                            <a href="/ergon/ledgers/project" class="nav-dropdown-item <?= ($active_page ?? '') === 'ledgers' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">📒</span>
                                Ledgers
                            </a>
                            <a href="/ergon/attendance" class="nav-dropdown-item <?= ($active_page ?? '') === 'attendance' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">📍</span>
                                Attendance
                            </a>
                        </div>
                    </div>
                    <div class="nav-dropdown">
                        <button class="nav-dropdown-btn" onclick="toggleDropdown('analytics')">
                            <span class="nav-icon">📈</span>
                            Analytics
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        <div class="nav-dropdown-menu" id="analytics">
                            <a href="/ergon/finance" class="nav-dropdown-item <?= ($active_page ?? '') === 'finance' ? 'nav-dropdown-item--active' : '' ?> <?= $financeDisabled ? 'nav-dropdown-item--disabled' : '' ?>">
                                <span class="nav-icon">💰</span>
                                Finance
                                <?php if ($financeDisabled): ?><span class="premium-icon">🔒</span><?php endif; ?>
                            </a>
                            <a href="/ergon/reports" class="nav-dropdown-item <?= ($active_page ?? '') === 'reports' ? 'nav-dropdown-item--active' : '' ?> <?= $reportsDisabled ? 'nav-dropdown-item--disabled' : '' ?>">
                                <span class="nav-icon">📈</span>
                                Reports
                                <?php if ($reportsDisabled): ?><span class="premium-icon">🔒</span><?php endif; ?>
                            </a>
                            <?php if (!$systemAdminDisabled): ?>
                            <a href="/ergon/settings" class="nav-dropdown-item <?= ($active_page ?? '') === 'settings' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">⚙️</span>
                                Settings
                            </a>
                            <?php else: ?>
                            <span class="nav-dropdown-item nav-dropdown-item--disabled" style="opacity: 0.5; cursor: not-allowed;">
                                <span class="nav-icon">⚙️</span>
                                Settings
                                <span class="premium-icon">🔒</span>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php else: // company_owner ?>
                    <div class="nav-dropdown">
                        <button class="nav-dropdown-btn" onclick="toggleDropdown('overview')">
                            <span class="nav-icon"><i class="bi bi-graph-up"></i></span>
                            Overview
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        <div class="nav-dropdown-menu" id="overview">
                            <a href="/ergon/dashboard" class="nav-dropdown-item <?= ($active_page ?? '') === 'dashboard' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon"><i class="bi bi-speedometer2"></i></span>
                                Dashboard
                            </a>
                            <a href="/ergon/finance" class="nav-dropdown-item <?= ($active_page ?? '') === 'finance' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">💰</span>
                                Finance
                            </a>
                        </div>
                    </div>
                    <div class="nav-dropdown">
                        <button class="nav-dropdown-btn" onclick="toggleDropdown('hrfinance')">
                            <span class="nav-icon">💰</span>
                            HR & Finance
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        <div class="nav-dropdown-menu" id="hrfinance">
                            <a href="/ergon/leaves" class="nav-dropdown-item <?= ($active_page ?? '') === 'leaves' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">📅</span>
                                Leaves
                            </a>
                            <a href="/ergon/expenses" class="nav-dropdown-item <?= ($active_page ?? '') === 'expenses' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">💰</span>
                                Expenses
                            </a>
                            <a href="/ergon/advances" class="nav-dropdown-item <?= ($active_page ?? '') === 'advances' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">💳</span>
                                Advances
                            </a>
                            <a href="/ergon/attendance" class="nav-dropdown-item <?= ($active_page ?? '') === 'attendance' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">📍</span>
                                Attendance
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <div class="nav-dropdown">
                        <button class="nav-dropdown-btn" onclick="toggleDropdown('overview')">
                            <span class="nav-icon">📊</span>
                            Overview
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        <div class="nav-dropdown-menu" id="overview">
                            <a href="/ergon/dashboard" class="nav-dropdown-item <?= ($active_page ?? '') === 'dashboard' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">📊</span>
                                Dashboard
                            </a>
                            <a href="/ergon/gamification/team-competition" class="nav-dropdown-item <?= ($active_page ?? '') === 'team-competition' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">🏆</span>
                                Competition
                            </a>
                        </div>
                    </div>
                    <div class="nav-dropdown">
                        <button class="nav-dropdown-btn" onclick="toggleDropdown('team')">
                            <span class="nav-icon">👥</span>
                            Team
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        <div class="nav-dropdown-menu" id="team">
                            <a href="/ergon/users" class="nav-dropdown-item <?= ($active_page ?? '') === 'users' ? 'nav-dropdown-item--active' : '' ?> <?= $usersDisabled ? 'nav-dropdown-item--disabled' : '' ?>">
                                <span class="nav-icon">👥</span>
                                Members
                                <?php if ($usersDisabled): ?><span class="premium-icon">🔒</span><?php endif; ?>
                            </a>
                            <a href="/ergon/departments" class="nav-dropdown-item <?= ($active_page ?? '') === 'departments' ? 'nav-dropdown-item--active' : '' ?> <?= $departmentsDisabled ? 'nav-dropdown-item--disabled' : '' ?>">
                                <span class="nav-icon">🏢</span>
                                Departments
                                <?php if ($departmentsDisabled): ?><span class="premium-icon">🔒</span><?php endif; ?>
                            </a>
                        </div>
                    </div>
                    <div class="nav-dropdown">
                        <button class="nav-dropdown-btn" onclick="toggleDropdown('tasks')">
                            <span class="nav-icon">✅</span>
                            Tasks
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        <div class="nav-dropdown-menu" id="tasks">
                            <a href="/ergon/tasks" class="nav-dropdown-item <?= ($active_page ?? '') === 'tasks' ? 'nav-dropdown-item--active' : '' ?> <?= $tasksDisabled ? 'nav-dropdown-item--disabled' : '' ?>">
                                <span class="nav-icon">✅</span>
                                Overall Tasks
                                <?php if ($tasksDisabled): ?><span class="premium-icon">🔒</span><?php endif; ?>
                            </a>
                            <a href="/ergon/workflow/daily-planner" class="nav-dropdown-item <?= ($active_page ?? '') === 'daily-planner' ? 'nav-dropdown-item--active' : '' ?> <?= $dailyPlannerDisabled ? 'nav-dropdown-item--disabled' : '' ?>">
                                <span class="nav-icon">🌅</span>
                                Daily Planner
                                <?php if ($dailyPlannerDisabled): ?><span class="premium-icon">🔒</span><?php endif; ?>
                            </a>
                            <a href="/ergon/contacts/followups" class="nav-dropdown-item <?= ($active_page ?? '') === 'contact_followups' ? 'nav-dropdown-item--active' : '' ?> <?= $followupsDisabled ? 'nav-dropdown-item--disabled' : '' ?>">
                                <span class="nav-icon">📞</span>
                                Follow-ups
                                <?php if ($followupsDisabled): ?><span class="premium-icon">🔒</span><?php endif; ?>
                            </a>
                            <a href="/ergon/site-reports" class="nav-dropdown-item <?= ($active_page ?? '') === 'site_reports' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">📋</span>
                                Site Reports
                            </a>
                        </div>
                    </div>
                    <div class="nav-dropdown">
                        <button class="nav-dropdown-btn" onclick="toggleDropdown('approvals')">
                            <span class="nav-icon">📅</span>
                            Approvals
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        <div class="nav-dropdown-menu" id="approvals">
                            <a href="/ergon/leaves" class="nav-dropdown-item <?= ($active_page ?? '') === 'leaves' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">📅</span>
                                Leaves
                            </a>
                            <a href="/ergon/expenses" class="nav-dropdown-item <?= ($active_page ?? '') === 'expenses' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">💰</span>
                                Expenses
                            </a>
                            <a href="/ergon/advances" class="nav-dropdown-item <?= ($active_page ?? '') === 'advances' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">💳</span>
                                Advances
                            </a>
                            <a href="/ergon/attendance" class="nav-dropdown-item <?= ($active_page ?? '') === 'attendance' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">📍</span>
                                Attendance
                            </a>
                            <a href="/ergon/admin/entry" class="nav-dropdown-item <?= ($active_page ?? '') === 'admin-entry' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">📝</span>
                                Direct Entry
                            </a>
                            <a href="/ergon/reports/activity" class="nav-dropdown-item <?= ($active_page ?? '') === 'activity' ? 'nav-dropdown-item--active' : '' ?> <?= $reportsDisabled ? 'nav-dropdown-item--disabled' : '' ?>">
                                <span class="nav-icon">⏱️</span>
                                Reports
                                <?php if ($reportsDisabled): ?><span class="premium-icon">🔒</span><?php endif; ?>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="nav-dropdown">
                        <button class="nav-dropdown-btn" onclick="toggleDropdown('overview')">
                            <span class="nav-icon">🏠</span>
                            Overview
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        <div class="nav-dropdown-menu" id="overview">
                            <a href="/ergon/dashboard" class="nav-dropdown-item <?= ($active_page ?? '') === 'dashboard' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">🏠</span>
                                Dashboard
                            </a>
                            <a href="/ergon/gamification/individual" class="nav-dropdown-item <?= ($active_page ?? '') === 'individual-gamification' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">🎖️</span>
                                My Performance
                            </a>
                            <a href="/ergon/gamification/team-competition" class="nav-dropdown-item <?= ($active_page ?? '') === 'team-competition' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">🏆</span>
                                Team Competition
                            </a>
                        </div>
                    </div>
                    <div class="nav-dropdown">
                        <button class="nav-dropdown-btn" onclick="toggleDropdown('work')">
                            <span class="nav-icon">✅</span>
                            Work
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        <div class="nav-dropdown-menu" id="work">
                            <a href="/ergon/tasks" class="nav-dropdown-item <?= ($active_page ?? '') === 'tasks' ? 'nav-dropdown-item--active' : '' ?> <?= $tasksDisabled ? 'nav-dropdown-item--disabled' : '' ?>">
                                <span class="nav-icon">✅</span>
                                Tasks
                                <?php if ($tasksDisabled): ?><span class="premium-icon">🔒</span><?php endif; ?>
                            </a>
                            <a href="/ergon/workflow/daily-planner" class="nav-dropdown-item <?= ($active_page ?? '') === 'daily-planner' ? 'nav-dropdown-item--active' : '' ?> <?= $dailyPlannerDisabled ? 'nav-dropdown-item--disabled' : '' ?>">
                                <span class="nav-icon">📅</span>
                                Daily Planner
                                <?php if ($dailyPlannerDisabled): ?><span class="premium-icon">🔒</span><?php endif; ?>
                            </a>
                            <a href="/ergon/contacts/followups" class="nav-dropdown-item <?= ($active_page ?? '') === 'contact_followups' ? 'nav-dropdown-item--active' : '' ?> <?= $followupsDisabled ? 'nav-dropdown-item--disabled' : '' ?>">
                                <span class="nav-icon">📞</span>
                                Follow-ups
                                <?php if ($followupsDisabled): ?><span class="premium-icon">🔒</span><?php endif; ?>
                            </a>
                            <a href="/ergon/site-reports" class="nav-dropdown-item <?= ($active_page ?? '') === 'site_reports' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">📋</span>
                                Site Reports
                            </a>
                        </div>
                    </div>
                    <div class="nav-dropdown">
                        <button class="nav-dropdown-btn" onclick="toggleDropdown('personal')">
                            <span class="nav-icon">📋</span>
                            Personal
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        <div class="nav-dropdown-menu" id="personal">
                            <a href="/ergon/user/requests" class="nav-dropdown-item <?= ($active_page ?? '') === 'requests' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">📋</span>
                                Requests
                            </a>
                            <a href="/ergon/leaves" class="nav-dropdown-item <?= ($active_page ?? '') === 'leaves' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">📅</span>
                                Leaves
                            </a>
                            <a href="/ergon/expenses" class="nav-dropdown-item <?= ($active_page ?? '') === 'expenses' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">💰</span>
                                Expenses
                            </a>
                            <a href="/ergon/advances" class="nav-dropdown-item <?= ($active_page ?? '') === 'advances' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">💳</span>
                                Advances
                            </a>
                            <a href="/ergon/attendance" class="nav-dropdown-item <?= ($active_page ?? '') === 'attendance' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">📍</span>
                                Attendance
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    
    <div class="mobile-overlay" id="mobileOverlay" onclick="closeMobileMenu()"></div>
    
    <aside class="sidebar" id="mobileSidebar">
        <div class="sidebar__header">
            <div class="sidebar__brand">
                <span class="brand-icon"><i class="bi bi-compass-fill"></i></span>
                <span>Ergon</span>
            </div>
        </div>
        <nav class="sidebar__menu">
            <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['owner', 'company_owner'])): ?>
                <?php if ($_SESSION['role'] === 'owner'): ?>
                <div class="sidebar__divider">Overview</div>
                <a href="/ergon/dashboard" class="sidebar__link <?= ($active_page ?? '') === 'dashboard' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon"><i class="bi bi-speedometer2"></i></span>
                    Dashboard
                </a>
                <a href="/ergon/gamification/team-competition" class="sidebar__link <?= ($active_page ?? '') === 'team-competition' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon"><i class="bi bi-trophy-fill"></i></span>
                    Competition
                </a>
                
                <div class="sidebar__divider">Management</div>
                <a href="/ergon/system-admin" class="sidebar__link <?= ($active_page ?? '') === 'system-admin' ? 'sidebar__link--active' : '' ?> <?= $systemAdminDisabled ? 'sidebar__link--disabled' : '' ?>">
                    <span class="sidebar__icon">🔧</span>
                    System
                    <?php if ($systemAdminDisabled): ?><span class="premium-icon">🔒</span><?php endif; ?>
                </a>
                <a href="/ergon/admin/management" class="sidebar__link <?= ($active_page ?? '') === 'admin' ? 'sidebar__link--active' : '' ?> <?= $usersDisabled ? 'sidebar__link--disabled' : '' ?>">
                    <span class="sidebar__icon">👥</span>
                    Users
                    <?php if ($usersDisabled): ?><span class="premium-icon">🔒</span><?php endif; ?>
                </a>
                <a href="/ergon/departments" class="sidebar__link <?= ($active_page ?? '') === 'departments' ? 'sidebar__link--active' : '' ?> <?= $departmentsDisabled ? 'sidebar__link--disabled' : '' ?>">
                    <span class="sidebar__icon">🏢</span>
                    Departments
                    <?php if ($departmentsDisabled): ?><span class="premium-icon">🔒</span><?php endif; ?>
                </a>
                <a href="/ergon/project-management" class="sidebar__link <?= ($active_page ?? '') === 'project-management' ? 'sidebar__link--active' : '' ?> <?= $projectsDisabled ? 'sidebar__link--disabled' : '' ?>">
                    <span class="sidebar__icon">📁</span>
                    Projects
                    <?php if ($projectsDisabled): ?><span class="premium-icon">🔒</span><?php endif; ?>
                </a>
                <a href="/ergon/modules" class="sidebar__link <?= ($active_page ?? '') === 'modules' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">🔧</span>
                    Modules
                </a>
                
                <div class="sidebar__divider">Operations</div>
                <a href="/ergon/tasks" class="sidebar__link <?= ($active_page ?? '') === 'tasks' ? 'sidebar__link--active' : '' ?> <?= $tasksDisabled ? 'sidebar__link--disabled' : '' ?>">
                    <span class="sidebar__icon">✅</span>
                    Tasks
                    <?php if ($tasksDisabled): ?><span class="premium-icon">🔒</span><?php endif; ?>
                </a>
                <a href="/ergon/contacts/followups" class="sidebar__link <?= ($active_page ?? '') === 'contact_followups' ? 'sidebar__link--active' : '' ?> <?= $followupsDisabled ? 'sidebar__link--disabled' : '' ?>">
                    <span class="sidebar__icon">📞</span>
                    Follow-ups
                    <?php if ($followupsDisabled): ?><span class="premium-icon">🔒</span><?php endif; ?>
                </a>
                <a href="/ergon/site-reports" class="sidebar__link <?= ($active_page ?? '') === 'site_reports' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">📋</span>
                    Site Reports
                </a>
                
                <div class="sidebar__divider">HR & Finance</div>
                <a href="/ergon/leaves" class="sidebar__link <?= ($active_page ?? '') === 'leaves' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">📅</span>
                    Leaves
                </a>
                <a href="/ergon/expenses" class="sidebar__link <?= ($active_page ?? '') === 'expenses' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">💰</span>
                    Expenses
                </a>
                <a href="/ergon/advances" class="sidebar__link <?= ($active_page ?? '') === 'advances' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">💳</span>
                    Advances
                </a>
                <a href="/ergon/ledgers/project" class="sidebar__link <?= ($active_page ?? '') === 'ledgers' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">📒</span>
                    Ledgers
                </a>
                <a href="/ergon/attendance" class="sidebar__link <?= ($active_page ?? '') === 'attendance' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">📍</span>
                    Attendance
                </a>
                
                <div class="sidebar__divider">Analytics</div>
                <a href="/ergon/finance" class="sidebar__link <?= ($active_page ?? '') === 'finance' ? 'sidebar__link--active' : '' ?> <?= $financeDisabled ? 'sidebar__link--disabled' : '' ?>">
                    <span class="sidebar__icon">💰</span>
                    Finance
                    <?php if ($financeDisabled): ?><span class="premium-icon">🔒</span><?php endif; ?>
                </a>
                <?php else: // company_owner ?>
                <div class="sidebar__divider">Overview</div>
                <a href="/ergon/dashboard" class="sidebar__link <?= ($active_page ?? '') === 'dashboard' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon"><i class="bi bi-speedometer2"></i></span>
                    Dashboard
                </a>
                <a href="/ergon/finance" class="sidebar__link <?= ($active_page ?? '') === 'finance' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">💰</span>
                    Finance
                </a>
                <a href="/ergon/site-reports" class="sidebar__link <?= ($active_page ?? '') === 'site_reports' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">&#128203;</span>
                    Site Reports
                </a>
                
                <div class="sidebar__divider">HR & Finance</div>
                <a href="/ergon/leaves" class="sidebar__link <?= ($active_page ?? '') === 'leaves' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">📅</span>
                    Leaves
                </a>
                <a href="/ergon/expenses" class="sidebar__link <?= ($active_page ?? '') === 'expenses' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">💰</span>
                    Expenses
                </a>
                <a href="/ergon/advances" class="sidebar__link <?= ($active_page ?? '') === 'advances' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">💳</span>
                    Advances
                </a>
                <a href="/ergon/attendance" class="sidebar__link <?= ($active_page ?? '') === 'attendance' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">📍</span>
                    Attendance
                </a>
                <?php endif; ?>
            <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <div class="sidebar__divider">Overview</div>
                <a href="/ergon/dashboard" class="sidebar__link <?= ($active_page ?? '') === 'dashboard' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">📊</span>
                    Dashboard
                </a>
                <a href="/ergon/gamification/team-competition" class="sidebar__link <?= ($active_page ?? '') === 'team-competition' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">🏆</span>
                    Competition
                </a>
                
                <div class="sidebar__divider">Team</div>
                <a href="/ergon/users" class="sidebar__link <?= ($active_page ?? '') === 'users' ? 'sidebar__link--active' : '' ?> <?= $usersDisabled ? 'sidebar__link--disabled' : '' ?>">
                    <span class="sidebar__icon">👥</span>
                    Members
                    <?php if ($usersDisabled): ?><span class="premium-icon">🔒</span><?php endif; ?>
                </a>
                <a href="/ergon/departments" class="sidebar__link <?= ($active_page ?? '') === 'departments' ? 'sidebar__link--active' : '' ?> <?= $departmentsDisabled ? 'sidebar__link--disabled' : '' ?>">
                    <span class="sidebar__icon">🏢</span>
                    Departments
                    <?php if ($departmentsDisabled): ?><span class="premium-icon">🔒</span><?php endif; ?>
                </a>
                
                <div class="sidebar__divider">Tasks</div>
                <a href="/ergon/tasks" class="sidebar__link <?= ($active_page ?? '') === 'tasks' ? 'sidebar__link--active' : '' ?> <?= $tasksDisabled ? 'sidebar__link--disabled' : '' ?>">
                    <span class="sidebar__icon">✅</span>
                    Overall Tasks
                    <?php if ($tasksDisabled): ?><span class="premium-icon">🔒</span><?php endif; ?>
                </a>
                <a href="/ergon/workflow/daily-planner" class="sidebar__link <?= ($active_page ?? '') === 'daily-planner' ? 'sidebar__link--active' : '' ?> <?= $dailyPlannerDisabled ? 'sidebar__link--disabled' : '' ?>">
                    <span class="sidebar__icon">🌅</span>
                    Daily Planner
                    <?php if ($dailyPlannerDisabled): ?><span class="premium-icon">🔒</span><?php endif; ?>
                </a>
                <a href="/ergon/contacts/followups" class="sidebar__link <?= ($active_page ?? '') === 'contact_followups' ? 'sidebar__link--active' : '' ?> <?= $followupsDisabled ? 'sidebar__link--disabled' : '' ?>">
                    <span class="sidebar__icon">📞</span>
                    Follow-ups
                    <?php if ($followupsDisabled): ?><span class="premium-icon">🔒</span><?php endif; ?>
                </a>
                <a href="/ergon/site-reports" class="sidebar__link <?= ($active_page ?? '') === 'site_reports' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">📋</span>
                    Site Reports
                </a>
                
                <div class="sidebar__divider">Approvals</div>
                <a href="/ergon/leaves" class="sidebar__link <?= ($active_page ?? '') === 'leaves' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">📅</span>
                    Leaves
                </a>
                <a href="/ergon/expenses" class="sidebar__link <?= ($active_page ?? '') === 'expenses' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">💰</span>
                    Expenses
                </a>
                <a href="/ergon/advances" class="sidebar__link <?= ($active_page ?? '') === 'advances' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">💳</span>
                    Advances
                </a>
                <a href="/ergon/attendance" class="sidebar__link <?= ($active_page ?? '') === 'attendance' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">📍</span>
                    Attendance
                </a>
                <a href="/ergon/admin/entry" class="sidebar__link <?= ($active_page ?? '') === 'admin-entry' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">📝</span>
                    Direct Entry
                </a>
                <a href="/ergon/reports/activity" class="sidebar__link <?= ($active_page ?? '') === 'activity' ? 'sidebar__link--active' : '' ?> <?= $reportsDisabled ? 'sidebar__link--disabled' : '' ?>">
                    <span class="sidebar__icon">⏱️</span>
                    Reports
                    <?php if ($reportsDisabled): ?><span class="premium-icon">🔒</span><?php endif; ?>
                </a>
            <?php else: ?>
                <div class="sidebar__divider">Overview</div>
                <a href="/ergon/dashboard" class="sidebar__link <?= ($active_page ?? '') === 'dashboard' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">🏠</span>
                    Dashboard
                </a>
                <a href="/ergon/gamification/individual" class="sidebar__link <?= ($active_page ?? '') === 'individual-gamification' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">🏅</span>
                    My Performance
                </a>
                <a href="/ergon/gamification/team-competition" class="sidebar__link <?= ($active_page ?? '') === 'team-competition' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">🏆</span>
                    Team Competition
                </a>
                
                <div class="sidebar__divider">Work</div>
                <a href="/ergon/tasks" class="sidebar__link <?= ($active_page ?? '') === 'tasks' ? 'sidebar__link--active' : '' ?> <?= $tasksDisabled ? 'sidebar__link--disabled' : '' ?>">
                    <span class="sidebar__icon">✅</span>
                    Tasks
                    <?php if ($tasksDisabled): ?><span class="premium-icon">🔒</span><?php endif; ?>
                </a>
                <a href="/ergon/workflow/daily-planner" class="sidebar__link <?= ($active_page ?? '') === 'daily-planner' ? 'sidebar__link--active' : '' ?> <?= $dailyPlannerDisabled ? 'sidebar__link--disabled' : '' ?>">
                    <span class="sidebar__icon">📅</span>
                    Daily Planner
                    <?php if ($dailyPlannerDisabled): ?><span class="premium-icon">🔒</span><?php endif; ?>
                </a>
                <a href="/ergon/contacts/followups" class="sidebar__link <?= ($active_page ?? '') === 'contact_followups' ? 'sidebar__link--active' : '' ?> <?= $followupsDisabled ? 'sidebar__link--disabled' : '' ?>">
                    <span class="sidebar__icon">📞</span>
                    Follow-ups
                    <?php if ($followupsDisabled): ?><span class="premium-icon">🔒</span><?php endif; ?>
                </a>
                <a href="/ergon/site-reports" class="sidebar__link <?= ($active_page ?? '') === 'site_reports' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">📋</span>
                    Site Reports
                </a>
                
                <div class="sidebar__divider">Personal</div>
                <a href="/ergon/leaves" class="sidebar__link <?= ($active_page ?? '') === 'leaves' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">📅</span>
                    Leaves
                </a>
                <a href="/ergon/expenses" class="sidebar__link <?= ($active_page ?? '') === 'expenses' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">💰</span>
                    Expenses
                </a>
                <a href="/ergon/advances" class="sidebar__link <?= ($active_page ?? '') === 'advances' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">💳</span>
                    Advances
                </a>
                <a href="/ergon/attendance" class="sidebar__link <?= ($active_page ?? '') === 'attendance' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">📍</span>
                    Attendance
                </a>
            <?php endif; ?>
        </nav>
    </aside>
    
    <div class="notification-dropdown" id="notificationDropdown">
        <div class="notification-header">
            <h3>Notifications</h3>
            <?php if (!$notificationsDisabled): ?>
            <button type="button" class="view-all-link" id="viewAllNotificationsBtn" onclick="window.location.href='/ergon/notifications'">View All</button>
            <?php endif; ?>
        </div>
        <div class="notification-list" id="notificationList">
            <div class="notification-loading">Loading notifications...</div>
        </div>
    </div>

    <main class="main-content">
        <button class="global-back-btn desktop-only" onclick="goBack()" data-tooltip="Go Back">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" width="18" height="18">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
        </button>
        <button class="global-forward-btn desktop-only" onclick="goForward()" data-tooltip="Go Forward">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" width="18" height="18">
                <path d="M5 12h14M12 5l7 7-7 7"/>
            </svg>
        </button>
        <?php if (isset($title) && in_array($title, ['Executive Dashboard', 'Team Competition Dashboard', 'Follow-ups Management', 'System Settings', 'IT Activity Reports', 'Notifications'])): ?>
        <div class="page-header">
            <div class="page-title">
                <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
            </div>
            <?php if ($title === 'Notifications'): ?>
            <div class="page-actions">
                <!-- Buttons are now handled by the view file itself -->
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?= $content ?>
    </main>

        <script>
    // Global variables - Initialize first
    let attendanceState = 'out'; // 'in' or 'out'
    
    // Notification functions - Define immediately
    function toggleNotifications(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        const dropdown = document.getElementById('notificationDropdown');
        if (!dropdown) return;
        
        const isVisible = dropdown.style.display === 'block';
        
        if (isVisible) {
            dropdown.style.display = 'none';
        } else {
            const button = document.querySelector('.notification-btn');
            if (button) {
                const rect = button.getBoundingClientRect();
                dropdown.style.position = 'fixed';
                dropdown.style.top = (rect.bottom + 8) + 'px';
                dropdown.style.right = (window.innerWidth - rect.right) + 'px';
                dropdown.style.zIndex = '10000';
            }
            dropdown.style.display = 'block';
            loadNotifications();
        }
    }
    
    function loadNotifications() {
        const list = document.getElementById('notificationList');
        if (!list) return;
        
        list.innerHTML = '<div class="notification-loading">Loading...</div>';
        
        fetch(APP_BASE + '/api/notifications/unread-count', {
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.notifications && data.notifications.length > 0) {
                    list.innerHTML = data.notifications.map(notif => {
                        const time = formatTime(notif.created_at);
                        const title = escapeHtml(notif.title || 'Notification');
                        const message = escapeHtml(notif.message || '');
                        
                        // Generate specific URL based on reference type and ID
                        let viewUrl = '/ergon/notifications'; // Default fallback
                        const referenceType = notif.module_name || notif.reference_type || '';
                        const referenceId = notif.reference_id;
                        
                        if (notif.action_url) {
                            viewUrl = notif.action_url;
                        } else if (referenceType && referenceId && referenceId > 0) {
                            switch (referenceType) {
                                case 'task':
                                case 'tasks':
                                    viewUrl = `/ergon/tasks/view/${referenceId}`;
                                    break;
                                case 'leave':
                                case 'leaves':
                                    viewUrl = `/ergon/leaves/view/${referenceId}`;
                                    break;
                                case 'expense':
                                case 'expenses':
                                    viewUrl = `/ergon/expenses/view/${referenceId}`;
                                    break;
                                case 'advance':
                                case 'advances':
                                    viewUrl = `/ergon/advances/view/${referenceId}`;
                                    break;
                                default:
                                    const pluralType = referenceType.endsWith('s') ? referenceType : referenceType + 's';
                                    viewUrl = `/ergon/${pluralType}/view/${referenceId}`;
                            }
                        }
                        
                        return `
                            <a href="${viewUrl}" class="notification-item">
                                <div class="notification-title">${title}</div>
                                <div class="notification-message">${message}</div>
                                <div class="notification-time">${time}</div>
                            </a>
                        `;
                    }).join('');
                } else {
                    list.innerHTML = '<div class="notification-loading">No notifications</div>';
                }
                
                const badge = document.getElementById('notificationBadge');
                if (badge && data.unread_count !== undefined) {
                    badge.textContent = data.unread_count;
                    badge.style.display = data.unread_count > 0 ? 'inline-block' : 'none';
                }
            })
            .catch(() => {
                list.innerHTML = '<div class="notification-loading">Failed to load</div>';
            });
    }
    
    function formatTime(dateStr) {
        try {
            const date = new Date(dateStr);
            const now = new Date();
            const diff = now - date;
            const minutes = Math.floor(diff / 60000);
            
            if (minutes < 1) return 'Just now';
            if (minutes < 60) return `${minutes}m ago`;
            
            const hours = Math.floor(minutes / 60);
            if (hours < 24) return `${hours}h ago`;
            
            return date.toLocaleDateString();
        } catch (error) {
            return 'Recently';
        }
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Load notification count only (for badge update)
    function loadNotificationCount() {
        fetch(APP_BASE + '/api/notifications/unread-count', {
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const badge = document.getElementById('notificationBadge');
                if (badge && data.unread_count !== undefined) {
                    badge.textContent = data.unread_count;
                    badge.style.display = data.unread_count > 0 ? 'inline-block' : 'none';
                    if (data.unread_count > 0) {
                        badge.classList.add('has-notifications');
                    } else {
                        badge.classList.remove('has-notifications');
                    }
                }
            }
        })
        .catch(error => {
            console.warn('Failed to load notification count:', error);
        });
    }
    
    // Make globally available
    window.toggleNotifications = toggleNotifications;
    window.loadNotificationCount = loadNotificationCount;
    
    // Global back button function
    function goBack() {
        if (window.history.length > 1) {
            window.history.back();
        } else {
            window.location.href = '/ergon/dashboard';
        }
    }
    
    // Global forward button function
    function goForward() {
        window.history.forward();
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        checkAttendanceStatus();
        loadNotificationCount();

        // Ensure profile button is clickable
        var profileBtn = document.getElementById('profileButton');
        if (profileBtn) {
            profileBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleProfile();
            });
        }
        
        // Refresh notifications every 30 seconds
        setInterval(loadNotificationCount, 30000);
    });

    function toggleProfile() {
        console.log('toggleProfile called'); // Debug log
        var menu = document.getElementById('profileMenu');
        
        if (!menu) {
            console.error('Profile menu not found');
            return;
        }
        
        // Close other dropdowns
        document.querySelectorAll('.nav-dropdown-menu').forEach(function(dropdown) {
            dropdown.classList.remove('show');
        });
        document.querySelectorAll('.nav-dropdown-btn').forEach(function(btn) {
            btn.classList.remove('active');
        });
        
        // Close notification dropdown
        var notificationDropdown = document.getElementById('notificationDropdown');
        if (notificationDropdown) {
            notificationDropdown.style.display = 'none';
        }
        
        menu.classList.toggle('show');
        console.log('Profile menu toggled, show class:', menu.classList.contains('show'));
    }
    
    // Make functions globally accessible
    window.toggleProfile = toggleProfile;
    
    // Navigation dropdown toggle function
    function toggleDropdown(dropdownId) {
        const dropdown = document.getElementById(dropdownId);
        const button = dropdown ? dropdown.previousElementSibling : null;
        
        // Close all other dropdowns
        document.querySelectorAll('.nav-dropdown-menu').forEach(function(menu) {
            if (menu.id !== dropdownId) {
                menu.classList.remove('show');
            }
        });
        document.querySelectorAll('.nav-dropdown-btn').forEach(function(btn) {
            if (btn !== button) {
                btn.classList.remove('active');
            }
        });
        
        // Toggle current dropdown
        if (dropdown) {
            dropdown.classList.toggle('show');
            if (button) {
                button.classList.toggle('active');
            }
        }
    }
    
    // Define missing dropdown functions
    function showDropdown(element) {
        if (element && element.nextElementSibling) {
            element.nextElementSibling.classList.add('show');
        }
    }
    
    function hideDropdown(element) {
        if (element && element.nextElementSibling) {
            element.nextElementSibling.classList.remove('show');
        }
    }
    
    function toggleDropdown(dropdownId) {
        const dropdown = document.getElementById(dropdownId);
        const button = dropdown ? dropdown.previousElementSibling : null;
        
        if (!dropdown) return;
        
        // Close all other dropdowns first
        document.querySelectorAll('.nav-dropdown-menu').forEach(function(menu) {
            if (menu !== dropdown) {
                menu.classList.remove('show');
            }
        });
        document.querySelectorAll('.nav-dropdown-btn').forEach(function(btn) {
            if (btn !== button) {
                btn.classList.remove('active');
            }
        });
        
        // Toggle current dropdown
        dropdown.classList.toggle('show');
        if (button) {
            button.classList.toggle('active');
        }
    }
    
    window.showDropdown = showDropdown;
    window.hideDropdown = hideDropdown;
    window.toggleDropdown = toggleDropdown;
    
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.header__controls')) {
            var menu = document.getElementById('profileMenu');
            if (menu) menu.classList.remove('show');
        }
        
        var dropdown = document.getElementById('notificationDropdown');
        if (dropdown && !e.target.closest('.notification-btn') && !e.target.closest('#notificationDropdown')) {
            dropdown.style.display = 'none';
        }
        
        if (!e.target.closest('.nav-dropdown')) {
            document.querySelectorAll('.nav-dropdown-menu').forEach(function(menu) {
                menu.classList.remove('show');
            });
            document.querySelectorAll('.nav-dropdown-btn').forEach(function(btn) {
                btn.classList.remove('active');
            });
        }
    });

    if ('scrollRestoration' in history) {
        history.scrollRestoration = 'auto';
    }
    
    function deleteRecord(module, id, name) {
        if (confirm('Are you sure you want to delete "' + name + '"? This action cannot be undone.')) {
            // Show loading state
            const deleteBtn = document.querySelector(`[data-id="${id}"][data-action="delete"]`);
            if (deleteBtn) {
                deleteBtn.disabled = true;
                deleteBtn.style.opacity = '0.5';
            }
            
            fetch(APP_BASE + '/' + module + '/delete/' + id, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Remove the row immediately
                    const row = deleteBtn ? deleteBtn.closest('tr') : null;
                    if (row) {
                        row.style.transition = 'opacity 0.3s ease';
                        row.style.opacity = '0';
                        setTimeout(() => {
                            row.remove();
                            // Show success message
                            alert('✅ ' + name + ' deleted successfully!');
                        }, 300);
                    } else {
                        location.reload();
                    }
                } else {
                    alert('❌ Error: ' + (data.message || 'Failed to delete record'));
                    if (deleteBtn) {
                        deleteBtn.disabled = false;
                        deleteBtn.style.opacity = '1';
                    }
                }
            })
            .catch(error => {
                console.error('Delete error:', error);
                alert('❌ An error occurred while deleting the record.');
                if (deleteBtn) {
                    deleteBtn.disabled = false;
                    deleteBtn.style.opacity = '1';
                }
            });
        }
    }

    function goBack() {
        window.history.back();
    }
    window.goBack = goBack;
    
    function toggleLeaveFilters() {
        const panel = document.getElementById('leaveFiltersPanel');
        if (panel) {
            panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        }
    }
    
    function initTooltips() {
        return;
    }
    
    // Smart Attendance Toggle Function - mirrors clockBtn logic
    let headerAttendanceStatus = {
        has_clocked_in: false,
        has_clocked_out: false,
        on_leave: false
    };
    
    function toggleAttendance() {
        const button = document.getElementById('attendanceToggle');
        const icon = document.getElementById('attendanceIcon');
        const text = document.getElementById('attendanceText');
        
        if (headerAttendanceStatus.on_leave) {
            showAttendanceNotification('You are on approved leave today', 'error');
            return;
        }
        
        // Determine action based on current status
        let action;
        if (!headerAttendanceStatus.has_clocked_in) {
            action = 'in';
        } else if (headerAttendanceStatus.has_clocked_in && !headerAttendanceStatus.has_clocked_out) {
            action = 'out';
        } else {
            showAttendanceNotification('Attendance completed for today', 'error');
            return;
        }
        
        // Show loading state
        button.disabled = true;
        button.classList.add('loading');
        const originalText = text.textContent;
        text.textContent = 'Getting Location...';
        
        // Get user location first
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const latitude = position.coords.latitude;
                    const longitude = position.coords.longitude;
                    
                    text.textContent = 'Validating Location...';
                    
                    // First validate location against project
                    fetch(APP_BASE + '/src/api/location-attendance.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `latitude=${latitude}&longitude=${longitude}`
                    })
                    .then(response => response.json())
                    .then(locationData => {
                        if (!locationData.success || !locationData.within_range) {
                            showAttendanceNotification(locationData.error || 'Location validation failed', 'error');
                            text.textContent = originalText;
                            button.disabled = false;
                            button.classList.remove('loading');
                            return;
                        }
                        
                        text.textContent = action === 'in' ? 'Clocking In...' : 'Clocking Out...';
                        
                        // Proceed with attendance if location is valid
                        fetch(APP_BASE + '/attendance/clock', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `type=${action}&latitude=${latitude}&longitude=${longitude}`
                        })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update status
                            if (action === 'in') {
                                headerAttendanceStatus.has_clocked_in = true;
                            } else {
                                headerAttendanceStatus.has_clocked_out = true;
                            }
                            
                            updateHeaderAttendanceButton();
                            showAttendanceNotification(`Clocked ${action} successfully!`, 'success');
                        } else {
                            // Check if it's a location restriction error
                            if (data.error && data.error.includes('Please move within the allowed area')) {
                                showAttendanceNotification('Please move within the allowed area to continue.', 'warning');
                            } else {
                                showAttendanceNotification(data.error || 'Failed to update attendance', 'error');
                            }
                            text.textContent = originalText;
                        }
                    })
                    .catch(error => {
                        showAttendanceNotification('Network error occurred', 'error');
                        text.textContent = originalText;
                    })
                        .finally(() => {
                            button.disabled = false;
                            button.classList.remove('loading');
                        });
                    })
                    .catch(error => {
                        showAttendanceNotification('Location validation failed', 'error');
                        text.textContent = originalText;
                        button.disabled = false;
                        button.classList.remove('loading');
                    });
                },
                function(error) {
                    let errorMessage = 'Location access denied';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage = 'Please enable location access to continue';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage = 'Location information unavailable';
                            break;
                        case error.TIMEOUT:
                            errorMessage = 'Location request timed out';
                            break;
                    }
                    
                    showAttendanceNotification(errorMessage, 'error');
                    text.textContent = originalText;
                    button.disabled = false;
                    button.classList.remove('loading');
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 60000
                }
            );
        } else {
            showAttendanceNotification('Geolocation is not supported by this browser', 'error');
            text.textContent = originalText;
            button.disabled = false;
            button.classList.remove('loading');
        }
    }
    
    function updateHeaderAttendanceButton() {
        const button = document.getElementById('attendanceToggle');
        const icon = document.getElementById('attendanceIcon');
        const text = document.getElementById('attendanceText');
        
        if (!button || !icon || !text) return;
        
        button.disabled = false;
        
        if (headerAttendanceStatus.on_leave) {
            // On Leave state
            text.textContent = 'On Leave';
            icon.className = 'bi bi-calendar-x';
            button.className = 'btn btn--attendance-toggle state-leave';
            button.disabled = true;
        } else if (!headerAttendanceStatus.has_clocked_in) {
            // Clock In state
            text.textContent = 'Clock In';
            icon.className = 'bi bi-play-fill';
            button.className = 'btn btn--attendance-toggle state-out';
        } else if (headerAttendanceStatus.has_clocked_in && !headerAttendanceStatus.has_clocked_out) {
            // Clock Out state
            text.textContent = 'Clock Out';
            icon.className = 'bi bi-stop-fill';
            button.className = 'btn btn--attendance-toggle state-in';
        } else {
            // Completed state
            text.textContent = 'Completed';
            icon.className = 'bi bi-check-circle-fill';
            button.className = 'btn btn--attendance-toggle state-completed';
            button.disabled = true;
        }
    }
    
    function showAttendanceNotification(message, type) {
        // Check if mobile view
        if (window.innerWidth <= 768) {
            showMobileDialog(message, type);
        } else {
            showDesktopNotification(message, type);
        }
    }
    
    function showMobileDialog(message, type) {
        const dialog = document.createElement('div');
        dialog.className = 'attendance-dialog-overlay';
        dialog.innerHTML = `
            <div class="attendance-dialog ${type}">
                <div class="dialog-icon">
                    <i class="bi bi-${type === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill'}"></i>
                </div>
                <div class="dialog-message">${message}</div>
                <button class="dialog-close" onclick="hideClosestModal(this)">OK</button>
            </div>
        `;
        
        document.body.appendChild(dialog);
        setTimeout(() => dialog.classList.add('show'), 50);
        
        // Auto close after 3 seconds
        setTimeout(() => {
            if (document.body.contains(dialog)) {
                dialog.remove();
            }
        }, 3000);
    }
    
    function showDesktopNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `attendance-notification ${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="bi bi-${type === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill'}"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => notification.classList.add('show'), 100);
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => document.body.removeChild(notification), 300);
        }, 3000);
    }
    
    // Universal Message Modal Functions
    function showUniversalModal(message, type = 'success', title = null) {
        const modal = document.getElementById('universalModal');
        const icon = document.getElementById('universalIcon');
        const titleEl = document.getElementById('universalTitle');
        const messageEl = document.getElementById('universalMessage');
        
        if (!modal || !icon || !titleEl || !messageEl) return;
        
        const config = {
            success: { icon: '✅', title: title || 'Success!' },
            error: { icon: '❌', title: title || 'Error!' },
            warning: { icon: '⚠️', title: title || 'Warning!' },
            info: { icon: 'ℹ️', title: title || 'Information' }
        };
        
        const typeConfig = config[type] || config.success;
        icon.textContent = typeConfig.icon;
        titleEl.textContent = typeConfig.title;
        messageEl.textContent = message;
        modal.className = `universal-modal ${type} show`;
        
        // Auto close after 4 seconds for success, 6 seconds for others
        const autoCloseTime = type === 'success' ? 4000 : 6000;
        setTimeout(() => {
            if (modal.classList.contains('show')) {
                closeUniversalModal();
            }
        }, autoCloseTime);
    }
    
    function closeUniversalModal() {
        const modal = document.getElementById('universalModal');
        if (modal) {
            modal.classList.remove('show');
            setTimeout(() => modal.style.display = 'none', 200);
        }
    }
    
    // Legacy function for backward compatibility
    function showMessage(message, type = 'success', title = null) {
        showUniversalModal(message, type, title);
    }
    
    function closeMessageModal() {
        closeUniversalModal();
    }
    
    // Make functions globally available
    window.showMessage = showMessage;
    window.showUniversalModal = showUniversalModal;
    window.closeMessageModal = closeMessageModal;
    window.closeUniversalModal = closeUniversalModal;
    
    // Global success/error/warning functions
    window.showSuccess = (message, title) => showUniversalModal(message, 'success', title);
    window.showError = (message, title) => showUniversalModal(message, 'error', title);
    window.showWarning = (message, title) => showUniversalModal(message, 'warning', title);
    window.showInfo = (message, title) => showUniversalModal(message, 'info', title);
    
    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeUniversalModal();
        }
    });
    
    // Close modal when clicking backdrop
    document.addEventListener('click', function(e) {
        if (e.target.id === 'universalModal') {
            closeUniversalModal();
        }
    });
    
    // Check attendance status on page load - updated for smart button
    function checkAttendanceStatus() {
        // Add timeout and retry logic for Hostinger compatibility
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout
        
        fetch(APP_BASE + '/attendance/status', {
            signal: controller.signal,
            headers: {
                'Cache-Control': 'no-cache',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            clearTimeout(timeoutId);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.text();
        })
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data && data.success) {
                    // Update header attendance status
                    headerAttendanceStatus = {
                        has_clocked_in: data.attendance && data.attendance.check_in ? true : false,
                        has_clocked_out: data.attendance && data.attendance.check_out ? true : false,
                        on_leave: data.on_leave || false
                    };
                    
                    // Legacy state for backward compatibility
                    attendanceState = (data.attendance && data.attendance.check_out) ? 'out' : 'in';
                    
                    updateHeaderAttendanceButton();
                } else {
                    // Set default state if data is invalid
                    updateHeaderAttendanceButton();
                }
            } catch (e) {
                console.warn('Attendance status response is not valid JSON:', text.substring(0, 100));
                updateHeaderAttendanceButton();
            }
        })
        .catch(error => {
            clearTimeout(timeoutId);
            if (error.name === 'AbortError') {
                console.warn('Attendance status check timed out');
            } else {
                console.warn('Attendance status check failed:', error.message);
            }
            // Set default state on error
            updateHeaderAttendanceButton();
        });
    }
    
    // Mobile Menu Functions
    function toggleMobileMenu() {
        var sidebar = document.querySelector('.sidebar');
        var overlay = document.getElementById('mobileOverlay');
        
        if (sidebar && overlay) {
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active');
            document.body.style.overflow = sidebar.classList.contains('mobile-open') ? 'hidden' : '';
        }
    }
    
    function closeMobileMenu() {
        var sidebar = document.querySelector('.sidebar');
        var overlay = document.getElementById('mobileOverlay');
        
        if (sidebar && overlay) {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
    
    // Close mobile menu when clicking on navigation links
    document.addEventListener('click', function(e) {
        if (e.target.closest('.nav-dropdown-item') || e.target.closest('.sidebar__link')) {
            closeMobileMenu();
        }
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 1024) {
            closeMobileMenu();
        }
    });
    
    // Add scroll indicator for tables on mobile
    document.addEventListener('DOMContentLoaded', function() {
        if (window.innerWidth <= 768) {
            var tables = document.querySelectorAll('.table-responsive');
            tables.forEach(function(table) {
                table.classList.add('table-mobile-scroll');
            });
        }
    });
    </script>

</body>
</html>
