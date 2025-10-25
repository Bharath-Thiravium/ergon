<?php
// Immediate session check before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Immediate redirect if no session - prevents flicker
if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    header('Location: /ergon/login');
    exit;
}

// Prevent caching with strongest possible headers
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0, private');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('ETag: "' . md5(time()) . '"');

// Check session timeout (1 hour)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
    session_unset();
    session_destroy();
    header('Location: /ergon/login?timeout=1');
    exit;
}

// Update last activity
$_SESSION['last_activity'] = time();

// Load Security helper
require_once __DIR__ . '/../../helpers/Security.php';
// Default preferences since UserPreference model may not exist
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

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/ergon/public/assets/css/ergon-combined.min.css?v=<?= filemtime(__DIR__ . '/../../public/assets/css/ergon-combined.min.css') ?>" rel="stylesheet">
    <?php if (isset($userPrefs['theme']) && $userPrefs['theme'] === 'dark'): ?>
    <link id="dark-theme-css" href="/ergon/public/assets/css/dark-theme.css" rel="stylesheet">
    <?php endif; ?>
</head>
<body data-theme="<?= isset($userPrefs['theme']) ? $userPrefs['theme'] : 'light' ?>" data-layout="<?= isset($userPrefs['dashboard_layout']) ? $userPrefs['dashboard_layout'] : 'default' ?>" data-lang="<?= isset($userPrefs['language']) ? $userPrefs['language'] : 'en' ?>">
    <button class="mobile-menu-toggle" onclick="toggleSidebar()">
        <span>☰</span>
    </button>


    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar__header">
                <a href="/ergon/dashboard" class="sidebar__brand">
                    <span>🧭</span>
                    ERGON
                </a>
                <h3><?= htmlspecialchars(ucfirst($_SESSION['role'] ?? 'User'), ENT_QUOTES, 'UTF-8') ?> Panel</h3>
            </div>
            <nav class="sidebar__menu">
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'owner'): ?>
                    <a href="/ergon/dashboard" class="sidebar__link <?= $active_page === 'dashboard' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📊</span>
                        Executive Dashboard
                    </a>
                    <a href="/ergon/system-admin" class="sidebar__link <?= $active_page === 'system-admin' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">🔧</span>
                        System Admins
                    </a>
                    <a href="/ergon/admin/management" class="sidebar__link <?= $active_page === 'admin' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">👥</span>
                        User Admins
                    </a>
                    <div class="sidebar__divider">Company Overview</div>
                    <a href="/ergon/tasks" class="sidebar__link <?= $active_page === 'tasks' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">✅</span>
                        Task Overview
                    </a>
                    <a href="/ergon/planner/calendar" class="sidebar__link <?= $active_page === 'planner' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📅</span>
                        Daily Planner
                    </a>
                    <a href="/ergon/daily-planner/dashboard" class="sidebar__link <?= $active_page === 'daily-planner-dashboard' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📝</span>
                        Progress Dashboard
                    </a>
                    <a href="/ergon/leaves" class="sidebar__link <?= $active_page === 'leaves' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📅</span>
                        Leave Overview
                    </a>
                    <a href="/ergon/expenses" class="sidebar__link <?= $active_page === 'expenses' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">💰</span>
                        Expense Overview
                    </a>
                    <a href="/ergon/attendance" class="sidebar__link <?= $active_page === 'attendance' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📍</span>
                        Attendance Overview
                    </a>
                    <div class="sidebar__divider">System</div>
                    <a href="/ergon/reports" class="sidebar__link <?= $active_page === 'reports' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📈</span>
                        Analytics
                    </a>
                    <a href="/ergon/reports/activity" class="sidebar__link <?= $active_page === 'activity' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">⏱️</span>
                        Activity Reports
                    </a>
                    <a href="/ergon/settings" class="sidebar__link <?= $active_page === 'settings' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">⚙️</span>
                        System Settings
                    </a>
                <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="/ergon/dashboard" class="sidebar__link <?= $active_page === 'dashboard' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📊</span>
                        Admin Dashboard
                    </a>
                    <a href="/ergon/departments" class="sidebar__link <?= $active_page === 'departments' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">🏢</span>
                        Department Management
                    </a>
                    <a href="/ergon/users" class="sidebar__link <?= $active_page === 'users' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">👥</span>
                        User Management
                    </a>
                    <a href="/ergon/tasks" class="sidebar__link <?= $active_page === 'tasks' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">✅</span>
                        Task Management
                    </a>
                    <a href="/ergon/planner/calendar" class="sidebar__link <?= $active_page === 'planner' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📅</span>
                        Daily Planner
                    </a>
                    <a href="/ergon/daily-planner/dashboard" class="sidebar__link <?= $active_page === 'daily-planner-dashboard' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📝</span>
                        Progress Dashboard
                    </a>
                    <a href="/ergon/leaves" class="sidebar__link <?= $active_page === 'leaves' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📅</span>
                        Leave Requests
                    </a>
                    <a href="/ergon/expenses" class="sidebar__link <?= $active_page === 'expenses' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">💰</span>
                        Expense Claims
                    </a>
                    <a href="/ergon/reports/activity" class="sidebar__link <?= $active_page === 'activity' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">⏱️</span>
                        Activity Reports
                    </a>

                <?php else: ?>
                    <a href="/ergon/dashboard" class="sidebar__link <?= $active_page === 'dashboard' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">🏠</span>
                        My Dashboard
                    </a>
                    <a href="/ergon/tasks" class="sidebar__link <?= $active_page === 'tasks' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">✅</span>
                        My Tasks
                    </a>
                    <a href="/ergon/planner/calendar" class="sidebar__link <?= $active_page === 'planner' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📅</span>
                        My Daily Planner
                    </a>
                    <a href="/ergon/daily-planner" class="sidebar__link <?= $active_page === 'daily-planner' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📝</span>
                        Daily Progress Report
                    </a>
                    <a href="/ergon/user/requests" class="sidebar__link <?= $active_page === 'requests' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📋</span>
                        My Requests
                    </a>
                    <a href="/ergon/attendance" class="sidebar__link <?= $active_page === 'attendance' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📍</span>
                        My Attendance
                    </a>
                <?php endif; ?>
                
                <!-- User Controls -->
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
                            <a href="/ergon/profile/change-password" class="profile-menu-item">
                                <span class="menu-icon">🔒</span>
                                Change Password
                            </a>
                            <a href="/ergon/profile/preferences" class="profile-menu-item">
                                <span class="menu-icon">⚙️</span>
                                Preferences
                            </a>
                            <div class="profile-menu-divider"></div>
                            <a href="/ergon/logout.php" class="profile-menu-item profile-menu-item--danger">
                                <span class="menu-icon">🚪</span>
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
                
            </nav>
        </aside>
        
        <!-- Notification Dropdown -->
        <div class="notification-dropdown" id="notificationDropdown">
            <div class="notification-header">
                <h3>Notifications</h3>
                <a href="/ergon/notifications" class="view-all-link">View All</a>
            </div>
            <div class="notification-list" id="notificationList">
                <div class="notification-loading">Loading...</div>
            </div>
        </div>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Breadcrumb -->
            <nav class="breadcrumb">
                <div class="breadcrumb-item">
                    <a href="/ergon/dashboard">🏠 Home</a>
                </div>
                <?php if (isset($active_page) && $active_page !== 'dashboard'): ?>
                    <span class="breadcrumb-separator">›</span>
                    <div class="breadcrumb-item">
                        <span class="breadcrumb-current"><?= htmlspecialchars($title ?? ucfirst($active_page), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endif; ?>
            </nav>
            
            <?= $content ?>
        </main>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/ergon/public/assets/js/auth-guard.min.js?v=<?= time() ?>" defer></script>
    <script src="/ergon/public/assets/js/sidebar-scroll.min.js?v=<?= filemtime(__DIR__ . '/../../public/assets/js/sidebar-scroll.min.js') ?>" defer></script>
    <script>
    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        sidebar.classList.toggle('sidebar--open');
    }
    
    function toggleTheme() {
        var currentTheme = document.body.getAttribute('data-theme') || 'light';
        var newTheme = currentTheme === 'light' ? 'dark' : 'light';
        document.body.setAttribute('data-theme', newTheme);
        var themeIcon = document.getElementById('themeIcon');
        if (themeIcon) themeIcon.textContent = newTheme === 'dark' ? '☀️' : '🌙';
        
        fetch('/ergon/api/update-preference', {
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
    
    function logout() {
        window.location.href = '/ergon/logout.php';
    }
    
    // Smooth scroll to active item only if not visible
    const activeItem = document.querySelector('.sidebar__link--active, .sidebar__link.is-active');
    if (activeItem) {
        const sidebar = document.querySelector('.sidebar');
        const sidebarRect = sidebar.getBoundingClientRect();
        const itemRect = activeItem.getBoundingClientRect();
        
        // Only scroll if item is not visible
        if (itemRect.top < sidebarRect.top || itemRect.bottom > sidebarRect.bottom) {
            activeItem.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }
    
    // Prevent forced focus jump
    document.querySelectorAll('.sidebar__link').forEach(a => {
        a.addEventListener('focus', e => {
            e.preventDefault();
            a.blur();
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        const sidebar = document.querySelector('.sidebar');
        const toggle = document.querySelector('.mobile-menu-toggle');
        const profileBtn = document.querySelector('.sidebar__profile-btn');
        const notificationBtn = document.querySelector('.sidebar__control-btn');
        
        // Close sidebar on mobile
        const sidebarEl = document.querySelector('.sidebar');
        if (window.innerWidth <= 768 && sidebarEl && !sidebarEl.contains(e.target) && toggle && !toggle.contains(e.target)) {
            sidebarEl.classList.remove('sidebar--open');
        }
        
        // Close profile menu
        if (profileBtn && !profileBtn.contains(e.target)) {
            var menu = document.getElementById('profileMenu');
            if (menu) menu.style.display = 'none';
        }
        
        // Close notification dropdown
        if (notificationBtn && !notificationBtn.contains(e.target)) {
            var dropdown = document.getElementById('notificationDropdown');
            if (dropdown) dropdown.style.display = 'none';
        }
    });
    
    // Immediate redirect for cached pages
    window.onpageshow = function(event) {
        if (event.persisted) {
            window.location.replace('/ergon/login');
        }
    };
    </script>
</body>
</html>