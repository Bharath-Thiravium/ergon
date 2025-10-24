<?php
// Prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Check session validity
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /ergon/login');
    exit;
}

// Validate session integrity
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    // Prevent role tampering by validating against database occasionally
    if (!isset($_SESSION['role_validated']) || (time() - $_SESSION['role_validated']) > 300) {
        require_once __DIR__ . '/../../../config/database.php';
        $db = new Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ? AND status = 'active'");
        $stmt->execute([$_SESSION['user_id']]);
        $dbRole = $stmt->fetchColumn();
        
        if ($dbRole && $dbRole !== $_SESSION['role']) {
            // Role changed in database, update session
            $_SESSION['role'] = $dbRole;
        } elseif (!$dbRole) {
            // User no longer exists or inactive
            session_destroy();
            header('Location: /ergon/login');
            exit;
        }
        $_SESSION['role_validated'] = time();
    }
}

// Load user preferences
require_once __DIR__ . '/../../models/UserPreference.php';
$preferenceModel = new UserPreference();
$userPrefs = $preferenceModel->getUserPreferences($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title><?= $title ?? 'Dashboard' ?> - ERGON</title>
    <link rel="icon" type="image/x-icon" href="/ergon/public/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/ergon/public/assets/css/ergon.css?v=20241220001" rel="stylesheet">
    <link href="/ergon/public/assets/css/components.css?v=20241220001" rel="stylesheet">
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
                <h3><?= ucfirst($_SESSION['role'] ?? 'User') ?> Panel</h3>
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
                            <span class="profile-avatar"><?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?></span>
                            <div class="profile-info">
                                <span class="profile-name"><?= $_SESSION['user_name'] ?? 'User' ?></span>
                                <span class="profile-role"><?= ucfirst($_SESSION['role'] ?? 'User') ?></span>
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
                            <a href="/ergon/auth/logout" class="profile-menu-item profile-menu-item--danger" onclick="return confirm('Are you sure you want to logout?')">
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
                        <span class="breadcrumb-current"><?= $title ?? ucfirst($active_page) ?></span>
                    </div>
                <?php endif; ?>
            </nav>
            
            <?= $content ?>
        </main>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
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
    
    // Maintain sidebar scroll position
    const sidebarMenu = document.querySelector('.sidebar__menu');
    if (sidebarMenu) {
        const savedScrollPos = sessionStorage.getItem('sidebarScrollPos');
        if (savedScrollPos) {
            sidebarMenu.scrollTop = parseInt(savedScrollPos);
        }
        
        sidebarMenu.addEventListener('scroll', function() {
            sessionStorage.setItem('sidebarScrollPos', this.scrollTop);
        });
    }
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        const sidebar = document.querySelector('.sidebar');
        const toggle = document.querySelector('.mobile-menu-toggle');
        const profileBtn = document.querySelector('.sidebar__profile-btn');
        const notificationBtn = document.querySelector('.sidebar__control-btn');
        
        // Close sidebar on mobile
        if (window.innerWidth <= 768 && sidebar && !sidebar.contains(e.target) && toggle && !toggle.contains(e.target)) {
            sidebar.classList.remove('sidebar--open');
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
    </script>
</body>
</html>