<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    header('Location: /Ergon/login');
    exit;
}

header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0, private');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('ETag: "' . md5(time()) . '"');

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
    session_unset();
    session_destroy();
    header('Location: /Ergon/login?timeout=1');
    exit;
}

$_SESSION['last_activity'] = time();

require_once __DIR__ . '/../../app/helpers/Security.php';
$userPrefs = ['theme' => 'light', 'dashboard_layout' => 'default', 'language' => 'en'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="csrf-token" content="<?= Security::escape(Security::generateCSRFToken()) ?>">
    <title><?= $title ?? 'Dashboard' ?> - ERGON</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/Ergon/public/assets/css/ergon.css?v=<?= time() ?>" rel="stylesheet">
    <style>
    .main-content {
        margin-left: 260px !important;
        padding: 24px !important;
        background: #f8fafc !important;
        min-height: 100vh !important;
        width: calc(100vw - 260px) !important;
    }
    </style>
    <?php if (isset($userPrefs['theme']) && $userPrefs['theme'] === 'dark'): ?>
    <link id="dark-theme-css" href="/Ergon/public/assets/css/dark-theme.css" rel="stylesheet">
    <?php endif; ?>
</head>
<body data-theme="<?= isset($userPrefs['theme']) ? $userPrefs['theme'] : 'light' ?>" data-layout="<?= isset($userPrefs['dashboard_layout']) ? $userPrefs['dashboard_layout'] : 'default' ?>" data-lang="<?= isset($userPrefs['language']) ? $userPrefs['language'] : 'en' ?>">
    <button class="mobile-menu-toggle" onclick="toggleSidebar()">
        <span>☰</span>
    </button>

    <div class="layout">
        <aside class="sidebar">
            <div class="sidebar__header">
                <a href="/Ergon/dashboard" class="sidebar__brand">
                    <span>🧭</span>
                    ERGON
                </a>
                <h3><?= htmlspecialchars(ucfirst($_SESSION['role'] ?? 'User'), ENT_QUOTES, 'UTF-8') ?> Panel</h3>
            </div>
            <nav class="sidebar__menu">
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'owner'): ?>
                    <a href="/Ergon/dashboard" class="sidebar__link <?= $active_page === 'dashboard' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📊</span>
                        Executive Dashboard
                    </a>
                    <a href="/Ergon/system-admin" class="sidebar__link <?= $active_page === 'system-admin' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">🔧</span>
                        System Admins
                    </a>
                    <a href="/Ergon/admin/management" class="sidebar__link <?= $active_page === 'admin' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">👥</span>
                        User Admins
                    </a>
                    <div class="sidebar__divider">Company Overview</div>
                    <a href="/Ergon/tasks" class="sidebar__link <?= $active_page === 'tasks' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">✅</span>
                        Task Overview
                    </a>
                    <a href="/Ergon/planner/calendar" class="sidebar__link <?= $active_page === 'planner' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📅</span>
                        Daily Planner
                    </a>
                    <a href="/Ergon/daily-planner/dashboard" class="sidebar__link <?= $active_page === 'daily-planner-dashboard' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📝</span>
                        Progress Dashboard
                    </a>
                    <a href="/Ergon/leaves" class="sidebar__link <?= $active_page === 'leaves' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📅</span>
                        Leave Overview
                    </a>
                    <a href="/Ergon/expenses" class="sidebar__link <?= $active_page === 'expenses' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">💰</span>
                        Expense Overview
                    </a>
                    <a href="/Ergon/attendance" class="sidebar__link <?= $active_page === 'attendance' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📍</span>
                        Attendance Overview
                    </a>
                    <div class="sidebar__divider">System</div>
                    <a href="/Ergon/reports" class="sidebar__link <?= $active_page === 'reports' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📈</span>
                        Analytics
                    </a>
                    <a href="/Ergon/reports/activity" class="sidebar__link <?= $active_page === 'activity' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">⏱️</span>
                        Activity Reports
                    </a>
                    <a href="/Ergon/settings" class="sidebar__link <?= $active_page === 'settings' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">⚙️</span>
                        System Settings
                    </a>
                <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="/Ergon/dashboard" class="sidebar__link <?= $active_page === 'dashboard' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📊</span>
                        Admin Dashboard
                    </a>
                    <a href="/Ergon/departments" class="sidebar__link <?= $active_page === 'departments' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">🏢</span>
                        Department Management
                    </a>
                    <a href="/Ergon/users" class="sidebar__link <?= $active_page === 'users' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">👥</span>
                        User Management
                    </a>
                    <a href="/Ergon/tasks" class="sidebar__link <?= $active_page === 'tasks' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">✅</span>
                        Task Management
                    </a>
                    <a href="/Ergon/planner/calendar" class="sidebar__link <?= $active_page === 'planner' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📅</span>
                        Daily Planner
                    </a>
                    <a href="/Ergon/daily-planner/dashboard" class="sidebar__link <?= $active_page === 'daily-planner-dashboard' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📝</span>
                        Progress Dashboard
                    </a>
                    <a href="/Ergon/leaves" class="sidebar__link <?= $active_page === 'leaves' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📅</span>
                        Leave Requests
                    </a>
                    <a href="/Ergon/expenses" class="sidebar__link <?= $active_page === 'expenses' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">💰</span>
                        Expense Claims
                    </a>
                    <a href="/Ergon/reports/activity" class="sidebar__link <?= $active_page === 'activity' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">⏱️</span>
                        Activity Reports
                    </a>
                <?php else: ?>
                    <a href="/Ergon/dashboard" class="sidebar__link <?= $active_page === 'dashboard' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">🏠</span>
                        My Dashboard
                    </a>
                    <a href="/Ergon/tasks" class="sidebar__link <?= $active_page === 'tasks' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">✅</span>
                        My Tasks
                    </a>
                    <a href="/Ergon/planner/calendar" class="sidebar__link <?= $active_page === 'planner' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📅</span>
                        My Daily Planner
                    </a>
                    <a href="/Ergon/daily-planner" class="sidebar__link <?= $active_page === 'daily-planner' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📝</span>
                        Daily Progress Report
                    </a>
                    <a href="/Ergon/user/requests" class="sidebar__link <?= $active_page === 'requests' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📋</span>
                        My Requests
                    </a>
                    <a href="/Ergon/attendance" class="sidebar__link <?= $active_page === 'attendance' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📍</span>
                        My Attendance
                    </a>
                <?php endif; ?>
                
                <div class="sidebar__controls">
                    <button class="sidebar__control-btn" onclick="toggleTheme()" title="Toggle Theme">
                        <span id="themeIcon"><?= (isset($userPrefs['theme']) && $userPrefs['theme'] === 'dark') ? '☀️' : '🌙' ?></span>
                    </button>
                    <button class="sidebar__control-btn" onclick="toggleNotifications()" title="Notifications">
                        <span>🔔</span>
                        <span class="notification-badge" id="notificationBadge">0</span>
                    </button>
                    <div class="sidebar__profile-dropdown">
                        <button class="sidebar__profile-btn" onclick="toggleProfile()">
                            <span class="profile-avatar"><?= htmlspecialchars(strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)), ENT_QUOTES, 'UTF-8') ?></span>
                            <div class="profile-info">
                                <span class="profile-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="profile-role"><?= htmlspecialchars(ucfirst($_SESSION['role'] ?? 'User'), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        <div class="profile-menu" id="profileMenu">
                            <a href="/Ergon/profile/change-password" class="profile-menu-item">
                                <span class="menu-icon">🔒</span>
                                Change Password
                            </a>
                            <a href="/Ergon/profile/preferences" class="profile-menu-item">
                                <span class="menu-icon">⚙️</span>
                                Preferences
                            </a>
                            <div class="profile-menu-divider"></div>
                            <a href="/Ergon/logout" class="profile-menu-item profile-menu-item--danger">
                                <span class="menu-icon">🚪</span>
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </nav>
        </aside>
        
        <div class="notification-dropdown" id="notificationDropdown">
            <div class="notification-header">
                <h3>Notifications</h3>
                <a href="/Ergon/notifications" class="view-all-link">View All</a>
            </div>
            <div class="notification-list" id="notificationList">
                <div class="notification-loading">Loading...</div>
            </div>
        </div>

        <main class="main-content">

            
            <?= $content ?>
        </main>
    </div>


    <script src="/Ergon/public/assets/js/auth-guard.min.js?v=<?= time() ?>" defer></script>
    <script>
    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        if (sidebar) {
            sidebar.classList.toggle('sidebar--open');
        }
    }
    
    function toggleTheme() {
        var currentTheme = document.body.getAttribute('data-theme') || 'light';
        var newTheme = currentTheme === 'light' ? 'dark' : 'light';
        document.body.setAttribute('data-theme', newTheme);
        var themeIcon = document.getElementById('themeIcon');
        if (themeIcon) themeIcon.textContent = newTheme === 'dark' ? '☀️' : '🌙';
        
        fetch('/Ergon/api/update-preference', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({key: 'theme', value: newTheme})
        }).catch(function(error) { console.log('Theme save failed:', error); });
    }
    
    function toggleNotifications() {
        var dropdown = document.getElementById('notificationDropdown');
        if (dropdown) {
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }
    }
    
    function toggleProfile() {
        var menu = document.getElementById('profileMenu');
        if (menu) {
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        const activeItem = document.querySelector('.sidebar__link--active, .sidebar__link.is-active');
        if (activeItem) {
            const sidebarMenu = document.querySelector('.sidebar__menu');
            if (sidebarMenu) {
                const menuRect = sidebarMenu.getBoundingClientRect();
                const itemRect = activeItem.getBoundingClientRect();
                
                if (itemRect.top < menuRect.top || itemRect.bottom > menuRect.bottom) {
                    activeItem.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }
        }
        
        document.querySelectorAll('.sidebar__link').forEach(function(link) {
            link.addEventListener('click', function(e) {
                document.querySelectorAll('.sidebar__link').forEach(function(l) {
                    l.classList.remove('sidebar__link--active');
                });
                this.classList.add('sidebar__link--active');
            });
        });
    });
    
    document.addEventListener('click', function(e) {
        const sidebar = document.querySelector('.sidebar');
        const toggle = document.querySelector('.mobile-menu-toggle');
        const profileBtn = document.querySelector('.sidebar__profile-btn');
        const notificationBtns = document.querySelectorAll('.sidebar__control-btn');
        
        if (window.innerWidth <= 768 && sidebar && !sidebar.contains(e.target) && toggle && !toggle.contains(e.target)) {
            sidebar.classList.remove('sidebar--open');
        }
        
        if (profileBtn && !profileBtn.contains(e.target)) {
            var menu = document.getElementById('profileMenu');
            if (menu) menu.style.display = 'none';
        }
        
        var isNotificationBtn = false;
        notificationBtns.forEach(function(btn) {
            if (btn.contains(e.target)) isNotificationBtn = true;
        });
        if (!isNotificationBtn) {
            var dropdown = document.getElementById('notificationDropdown');
            if (dropdown) dropdown.style.display = 'none';
        }
    });
    
    window.addEventListener('resize', function() {
        const sidebar = document.querySelector('.sidebar');
        if (window.innerWidth > 768 && sidebar) {
            sidebar.classList.remove('sidebar--open');
        }
    });
    
    window.onpageshow = function(event) {
        if (event.persisted) {
            window.location.replace('/Ergon/login');
        }
    };
    </script>
</body>
</html>
