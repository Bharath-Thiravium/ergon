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
    <link href="/ergon/public/assets/css/ergon.css" rel="stylesheet">
    <link href="/ergon/public/assets/css/modals.css" rel="stylesheet">
    <link href="/ergon/public/assets/css/header-components.css" rel="stylesheet">
    <?php if ($userPrefs['theme'] === 'dark'): ?>
    <link href="/ergon/public/assets/css/dark-theme.css" rel="stylesheet">
    <?php endif; ?>
</head>
<body data-theme="<?= $userPrefs['theme'] ?>" data-layout="<?= $userPrefs['dashboard_layout'] ?>" data-lang="<?= $userPrefs['language'] ?>">
    <!-- Header -->
    <header class="header">
        <nav class="header__nav">
            <div class="header__left">
                <h1 class="header__title"><?= $title ?? 'Dashboard' ?></h1>
            </div>
            <div class="header__right">
                <!-- Theme Toggle -->
                <div class="theme-toggle">
                    <button class="theme-toggle-btn" onclick="toggleTheme()" title="Toggle Theme">
                        <span class="theme-icon" id="themeIcon"><?= $userPrefs['theme'] === 'dark' ? '☀️' : '🌙' ?></span>
                    </button>
                </div>
                
                <!-- Notification Center -->
                <div class="notification-center">
                    <button class="notification-btn" onclick="toggleNotifications()">
                        <span class="notification-icon">🔔</span>
                        <span class="notification-badge" id="notificationBadge">0</span>
                    </button>
                    <div class="notification-dropdown" id="notificationDropdown">
                        <div class="notification-header">
                            <h3>Notifications</h3>
                            <a href="/ergon/notifications" class="view-all-link">View All</a>
                        </div>
                        <div class="notification-list" id="notificationList">
                            <div class="notification-loading">Loading...</div>
                        </div>
                    </div>
                </div>
                
                <!-- Profile Dropdown -->
                <div class="profile-dropdown">
                    <button class="profile-btn" onclick="toggleProfile()">
                        <span class="profile-avatar"><?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?></span>
                        <span class="profile-name"><?= $_SESSION['user_name'] ?? 'User' ?></span>
                        <span class="dropdown-arrow">▼</span>
                    </button>
                    <div class="profile-menu" id="profileMenu">
                        <div class="profile-info">
                            <div class="profile-avatar-large"><?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?></div>
                            <div class="profile-details">
                                <div class="profile-name"><?= $_SESSION['user_name'] ?? 'User' ?></div>
                                <div class="profile-role"><?= ucfirst($_SESSION['role'] ?? 'User') ?></div>
                            </div>
                        </div>
                        <div class="profile-menu-divider"></div>
                        <a href="/ergon/profile" class="profile-menu-item">
                            <span class="menu-icon">👤</span>
                            My Profile
                        </a>
                        <a href="/ergon/profile/change-password" class="profile-menu-item">
                            <span class="menu-icon">🔒</span>
                            Change Password
                        </a>
                        <a href="/ergon/profile/preferences" class="profile-menu-item">
                            <span class="menu-icon">⚙️</span>
                            Preferences
                        </a>
                        <div class="profile-menu-divider"></div>
                        <a href="/ergon/auth/logout" class="profile-menu-item profile-menu-item--danger" onclick="return confirmLogout()">
                            <span class="menu-icon">🚪</span>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

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
                    <a href="/ergon/users" class="sidebar__link <?= $active_page === 'users' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">👥</span>
                        Admin Management
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
                        Task Planner Dashboard
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
                        Task Planner Dashboard
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
                        Daily Task Planner
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
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <?= $content ?>
        </main>
    </div>

    <script src="/ergon/public/assets/js/modal-system.js"></script>
    <script src="/ergon/public/assets/js/header-components.js"></script>
    <script src="/ergon/public/assets/js/preferences-handler.js"></script>
    
    <script>
    // Prevent back button after logout
    (function() {
        if (window.history && window.history.pushState) {
            window.history.pushState('forward', null, window.location.href);
            window.addEventListener('popstate', function() {
                window.history.pushState('forward', null, window.location.href);
            });
        }
    })();
    
    // Session timeout warning (optional)
    let sessionWarningShown = false;
    setInterval(function() {
        if (!sessionWarningShown && document.visibilityState === 'visible') {
            // Only show warning after 25 minutes of inactivity
            const lastActivity = localStorage.getItem('lastActivity');
            if (lastActivity && (Date.now() - parseInt(lastActivity)) > 1500000) {
                sessionWarningShown = true;
                if (confirm('Your session will expire soon. Click OK to stay logged in.')) {
                    localStorage.setItem('lastActivity', Date.now().toString());
                    sessionWarningShown = false;
                }
            }
        }
    }, 60000); // Check every minute
    
    // Prevent access via browser navigation
    window.addEventListener('beforeunload', function() {
        // This helps prevent cached page access
    });
    
    // Clear page cache on load
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            window.location.reload();
        }
    });
    
    // Logout confirmation
    function confirmLogout() {
        return confirm('Are you sure you want to logout? You will need to enter your credentials again.');
    }
    
    // Track user activity
    document.addEventListener('DOMContentLoaded', function() {
        localStorage.setItem('lastActivity', Date.now().toString());
        
        // Update activity on user interactions
        ['click', 'keypress', 'scroll', 'mousemove'].forEach(event => {
            document.addEventListener(event, function() {
                localStorage.setItem('lastActivity', Date.now().toString());
            }, { passive: true });
        });
    });
    </script>
    
    <?php 
    // Activity tracking for IT department only (simplified)
    $userDept = $_SESSION['user_department'] ?? '';
    if (stripos($userDept, 'IT') !== false): 
    ?>
    <script src="/ergon/public/assets/js/activity-tracker.js"></script>
    <?php endif; ?>
</body>
</html>