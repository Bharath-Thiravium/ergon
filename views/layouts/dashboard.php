<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    if (!headers_sent()) {
        header('Location: /ergon/login');
    }
    exit;
}

if (!headers_sent()) {
    header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0, private');
    header('Pragma: no-cache');
    header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('ETag: "' . md5(time()) . '"');
}

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
    session_unset();
    session_destroy();
    $isProduction = strpos($_SERVER['HTTP_HOST'] ?? '', 'athenas.co.in') !== false;
    $loginUrl = $isProduction ? '/ergon/login?timeout=1' : '/ergon/login?timeout=1';
    if (!headers_sent()) {
        header('Location: ' . $loginUrl);
    }
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
    <title><?= $title ?? 'Dashboard' ?> - ergon</title>

    <link rel="preload" href="/ergon/assets/css/ergon.css?v=<?= time() ?>" as="style">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/ergon/assets/css/ergon.css?v=<?= time() ?>" rel="stylesheet">
    <style>
    .main-content {
        margin-left: 260px !important;
        padding: 24px !important;
        background: #f8fafc !important;
        min-height: 100vh !important;
        width: calc(100vw - 260px) !important;
    }
    .sidebar__profile-toggle {
        width: 100%;
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 12px;
        color: white;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }
    .sidebar__profile-toggle:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
    }
    .profile-avatar {
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, 0.2);
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 16px;
        backdrop-filter: blur(10px);
    }
    .profile-info {
        flex: 1;
        text-align: left;
    }
    .profile-name {
        display: block;
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 2px;
    }
    .profile-role {
        display: block;
        font-size: 12px;
        opacity: 0.8;
        text-transform: capitalize;
    }
    .dropdown-arrow {
        transition: transform 0.3s ease;
        font-size: 12px;
    }
    .sidebar__profile-toggle:hover .dropdown-arrow {
        transform: rotate(180deg);
    }
    .profile-menu {
        display: none;
        background: #ffffff;
        border: none;
        border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        margin-top: 12px;
        overflow: hidden;
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .profile-menu.show {
        display: block !important;
        animation: slideDown 0.3s ease;
    }
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    .profile-menu-item {
        display: flex;
        align-items: center;
        padding: 14px 18px;
        color: #ced2d9ff;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s ease;
        border-left: 3px solid transparent;
    }
    .profile-menu-item:hover {
        background: linear-gradient(90deg, #f8fafc 0%, #e2e8f0 100%);
        color: #1e293b;
        border-left-color: #667eea;
        transform: translateX(4px);
    }
    .profile-menu-item--danger {
        color: #dc2626;
    }
    .profile-menu-item--danger:hover {
        background: linear-gradient(90deg, #fef2f2 0%, #fee2e2 100%);
        color: #dc2626;
        border-left-color: #dc2626;
    }
    .profile-menu-divider {
        height: 1px;
        background: linear-gradient(90deg, transparent 0%, #e5e7eb 50%, transparent 100%);
        margin: 8px 0;
    }
    .menu-icon {
        margin-right: 12px;
        font-size: 16px;
        width: 20px;
        text-align: center;
    }
    .sidebar__profile-section {
        position: relative;
        margin-bottom: 0;
    }
    .profile-menu-item {
        display: flex;
        align-items: center;
        padding: 14px 18px;
        color: #dadde1;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s ease;
        border-left: 3px solid transparent;
    }
    </style>
    <?php if (isset($userPrefs['theme']) && $userPrefs['theme'] === 'dark'): ?>
    <link id="dark-theme-css" href="/ergon/assets/css/dark-theme.css" rel="stylesheet">
    <?php endif; ?>
</head>
<body data-theme="<?= isset($userPrefs['theme']) ? $userPrefs['theme'] : 'light' ?>" data-layout="<?= isset($userPrefs['dashboard_layout']) ? $userPrefs['dashboard_layout'] : 'default' ?>" data-lang="<?= isset($userPrefs['language']) ? $userPrefs['language'] : 'en' ?>">
    <button class="mobile-menu-toggle" onclick="toggleSidebar()">
        <span>☰</span>
    </button>

    <div class="layout">
        <aside class="sidebar">
            <div class="sidebar__header">
                <div class="sidebar__brand-section">
                    <a href="/ergon/dashboard" class="sidebar__brand">
                        <span>🧭</span>
                        Ergon
                    </a>
                    <h3><?= htmlspecialchars(ucfirst($_SESSION['role'] ?? 'User'), ENT_QUOTES, 'UTF-8') ?> Panel</h3>
                </div>
            </div>
            
            <div class="sidebar__controls-section">
                <div class="sidebar__header-controls">
                    <button class="sidebar__control-btn" onclick="toggleTheme()" title="Toggle Theme">
                        <span id="themeIcon"><?= (isset($userPrefs['theme']) && $userPrefs['theme'] === 'dark') ? '☀️' : '🌙' ?></span>
                    </button>
                    <button class="sidebar__control-btn" onclick="toggleNotifications()" title="Notifications">
                        <span>🔔</span>
                        <span class="notification-badge" id="notificationBadge">0</span>
                    </button>
                </div>
                
                <div class="sidebar__profile-section">
                    <button class="sidebar__profile-toggle" onclick="document.getElementById('profileMenu').classList.toggle('show')">
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
                        <a href="/ergon/logout" class="profile-menu-item profile-menu-item--danger">
                            <span class="menu-icon">🚪</span>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
            <nav class="sidebar__menu">
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'owner'): ?>
                    <a href="/ergon/dashboard" class="sidebar__link <?= ($active_page ?? '') === 'dashboard' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📊</span>
                        Executive Dashboard
                    </a>
                    <a href="/ergon/system-admin" class="sidebar__link <?= ($active_page ?? '') === 'system-admin' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">🔧</span>
                        System Admins
                    </a>
                    <a href="/ergon/admin/management" class="sidebar__link <?= ($active_page ?? '') === 'admin' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">👥</span>
                        User Admins
                    </a>
                    <a href="/ergon/project-management" class="sidebar__link <?= ($active_page ?? '') === 'project-management' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📁</span>
                        Project Management
                    </a>
                    <div class="sidebar__divider">Company Overview</div>
                    <a href="/ergon/tasks" class="sidebar__link <?= ($active_page ?? '') === 'tasks' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">✅</span>
                        Task Overview
                    </a>
                    <a href="/ergon/daily-workflow/morning-planner" class="sidebar__link <?= ($active_page ?? '') === 'planner' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">🌅</span>
                        Morning Planner
                    </a>
                    <a href="/ergon/followups" class="sidebar__link <?= ($active_page ?? '') === 'followups' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📞</span>
                        Follow-ups
                    </a>
                    <a href="/ergon/daily-workflow/progress-dashboard" class="sidebar__link <?= ($active_page ?? '') === 'daily-planner-dashboard' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📊</span>
                        Progress Dashboard
                    </a>
                    <a href="/ergon/leaves" class="sidebar__link <?= ($active_page ?? '') === 'leaves' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📅</span>
                        Leave Overview
                    </a>
                    <a href="/ergon/expenses" class="sidebar__link <?= ($active_page ?? '') === 'expenses' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">💰</span>
                        Expense Overview
                    </a>
                    <a href="/ergon/advances" class="sidebar__link <?= ($active_page ?? '') === 'advances' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">💳</span>
                        Advance Overview
                    </a>
                    <a href="/ergon/attendance" class="sidebar__link <?= ($active_page ?? '') === 'attendance' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📍</span>
                        Attendance Overview
                    </a>
                    <div class="sidebar__divider">System</div>
                    <a href="/ergon/reports" class="sidebar__link <?= ($active_page ?? '') === 'reports' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📈</span>
                        Analytics
                    </a>
                    <a href="/ergon/reports/activity" class="sidebar__link <?= ($active_page ?? '') === 'activity' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">⏱️</span>
                        Activity Reports
                    </a>
                    <a href="/ergon/settings" class="sidebar__link <?= ($active_page ?? '') === 'settings' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">⚙️</span>
                        System Settings
                    </a>
                <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="/ergon/dashboard" class="sidebar__link <?= ($active_page ?? '') === 'dashboard' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📊</span>
                        Admin Dashboard
                    </a>
                    <a href="/ergon/departments" class="sidebar__link <?= ($active_page ?? '') === 'departments' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">🏢</span>
                        Department Management
                    </a>
                    <a href="/ergon/users" class="sidebar__link <?= ($active_page ?? '') === 'users' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">👥</span>
                        User Management
                    </a>
                    <a href="/ergon/tasks" class="sidebar__link <?= ($active_page ?? '') === 'tasks' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">✅</span>
                        Task Management
                    </a>
                    <a href="/ergon/daily-workflow/morning-planner" class="sidebar__link <?= ($active_page ?? '') === 'planner' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">🌅</span>
                        Morning Planner
                    </a>
                    <a href="/ergon/followups" class="sidebar__link <?= ($active_page ?? '') === 'followups' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📞</span>
                        Follow-ups
                    </a>
                    <a href="/ergon/daily-workflow/progress-dashboard" class="sidebar__link <?= ($active_page ?? '') === 'daily-planner-dashboard' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📊</span>
                        Progress Dashboard
                    </a>
                    <a href="/ergon/leaves" class="sidebar__link <?= ($active_page ?? '') === 'leaves' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📅</span>
                        Leave Requests
                    </a>
                    <a href="/ergon/expenses" class="sidebar__link <?= ($active_page ?? '') === 'expenses' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">💰</span>
                        Expense Claims
                    </a>
                    <a href="/ergon/advances" class="sidebar__link <?= ($active_page ?? '') === 'advances' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">💳</span>
                        Advance Requests
                    </a>
                    <a href="/ergon/attendance" class="sidebar__link <?= ($active_page ?? '') === 'attendance' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📍</span>
                        Attendance
                    </a>
                    <a href="/ergon/reports/activity" class="sidebar__link <?= ($active_page ?? '') === 'activity' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">⏱️</span>
                        Activity Reports
                    </a>
                <?php else: ?>
                    <a href="/ergon/dashboard" class="sidebar__link <?= ($active_page ?? '') === 'dashboard' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">🏠</span>
                        My Dashboard
                    </a>
                    <a href="/ergon/tasks" class="sidebar__link <?= ($active_page ?? '') === 'tasks' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">✅</span>
                        My Tasks
                    </a>
                    <a href="/ergon/daily-workflow/morning-planner" class="sidebar__link <?= ($active_page ?? '') === 'planner' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">🌅</span>
                        Morning Planner
                    </a>
                    <a href="/ergon/followups" class="sidebar__link <?= ($active_page ?? '') === 'followups' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📞</span>
                        Follow-ups
                    </a>
                    <a href="/ergon/daily-workflow/evening-update" class="sidebar__link <?= ($active_page ?? '') === 'daily-planner' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">🌆</span>
                        Evening Update
                    </a>
                    <a href="/ergon/user/requests" class="sidebar__link <?= ($active_page ?? '') === 'requests' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📋</span>
                        My Requests
                    </a>
                    <a href="/ergon/leaves" class="sidebar__link <?= ($active_page ?? '') === 'leaves' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📅</span>
                        My Leaves
                    </a>
                    <a href="/ergon/expenses" class="sidebar__link <?= ($active_page ?? '') === 'expenses' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">💰</span>
                        My Expenses
                    </a>
                    <a href="/ergon/advances" class="sidebar__link <?= ($active_page ?? '') === 'advances' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">💳</span>
                        My Advances
                    </a>
                    <a href="/ergon/attendance" class="sidebar__link <?= ($active_page ?? '') === 'attendance' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📍</span>
                        My Attendance
                    </a>
                <?php endif; ?>
            </nav>
        </aside>
        
        <main class="main-content">
            <?= $content ?>
        </main>
    </div>

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
    }
    
    function toggleNotifications() {
        var dropdown = document.getElementById('notificationDropdown');
        if (dropdown) {
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }
    }
    </script>
</body>
</html>   Logout
                        </a>
                    </div>
                </div>
            </div>
            <nav class="sidebar__menu">
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'owner'): ?>
                    <a href="/ergon/dashboard" class="sidebar__link <?= ($active_page ?? '') === 'dashboard' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📊</span>
                        Executive Dashboard
                    </a>
                    <a href="/ergon/tasks" class="sidebar__link <?= ($active_page ?? '') === 'tasks' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">✅</span>
                        Task Overview
                    </a>
                    <a href="/ergon/users" class="sidebar__link <?= ($active_page ?? '') === 'users' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">👥</span>
                        User Management
                    </a>
                    <a href="/ergon/leaves" class="sidebar__link <?= ($active_page ?? '') === 'leaves' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📅</span>
                        Leave Overview
                    </a>
                    <a href="/ergon/expenses" class="sidebar__link <?= ($active_page ?? '') === 'expenses' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">💰</span>
                        Expense Overview
                    </a>
                    <a href="/ergon/attendance" class="sidebar__link <?= ($active_page ?? '') === 'attendance' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📍</span>
                        Attendance Overview
                    </a>
                    <a href="/ergon/reports" class="sidebar__link <?= ($active_page ?? '') === 'reports' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📈</span>
                        Analytics
                    </a>
                    <a href="/ergon/settings" class="sidebar__link <?= ($active_page ?? '') === 'settings' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">⚙️</span>
                        System Settings
                    </a>
                <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="/ergon/dashboard" class="sidebar__link <?= ($active_page ?? '') === 'dashboard' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📊</span>
                        Admin Dashboard
                    </a>
                    <a href="/ergon/users" class="sidebar__link <?= ($active_page ?? '') === 'users' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">👥</span>
                        User Management
                    </a>
                    <a href="/ergon/tasks" class="sidebar__link <?= ($active_page ?? '') === 'tasks' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">✅</span>
                        Task Management
                    </a>
                    <a href="/ergon/leaves" class="sidebar__link <?= ($active_page ?? '') === 'leaves' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📅</span>
                        Leave Requests
                    </a>
                    <a href="/ergon/expenses" class="sidebar__link <?= ($active_page ?? '') === 'expenses' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">💰</span>
                        Expense Claims
                    </a>
                    <a href="/ergon/attendance" class="sidebar__link <?= ($active_page ?? '') === 'attendance' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📍</span>
                        Attendance
                    </a>
                <?php else: ?>
                    <a href="/ergon/dashboard" class="sidebar__link <?= ($active_page ?? '') === 'dashboard' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">🏠</span>
                        My Dashboard
                    </a>
                    <a href="/ergon/tasks" class="sidebar__link <?= ($active_page ?? '') === 'tasks' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">✅</span>
                        My Tasks
                    </a>
                    <a href="/ergon/leaves" class="sidebar__link <?= ($active_page ?? '') === 'leaves' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📅</span>
                        My Leaves
                    </a>
                    <a href="/ergon/expenses" class="sidebar__link <?= ($active_page ?? '') === 'expenses' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">💰</span>
                        My Expenses
                    </a>
                    <a href="/ergon/attendance" class="sidebar__link <?= ($active_page ?? '') === 'attendance' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📍</span>
                        My Attendance
                    </a>
                <?php endif; ?>
            </nav>
        </aside>
        
        <main class="main-content">
            <?= $content ?>
        </main>
    </div>

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
    }
    
    function toggleNotifications() {
        var dropdown = document.getElementById('notificationDropdown');
        if (dropdown) {
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }
    }
    </script>
</body>
</html>   Logout
                        </a>
                    </div>
                </div>
            </div>
            <nav class="sidebar__menu">
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'owner'): ?>
                    <a href="/ergon/dashboard" class="sidebar__link <?= $active_page === 'dashboard' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📊</span>
                        Executive Dashboard
                    </a>
                    <a href="/ergon/tasks" class="sidebar__link <?= $active_page === 'tasks' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">✅</span>
                        Task Overview
                    </a>
                <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="/ergon/dashboard" class="sidebar__link <?= $active_page === 'dashboard' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📊</span>
                        Admin Dashboard
                    </a>
                    <a href="/ergon/tasks" class="sidebar__link <?= $active_page === 'tasks' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">✅</span>
                        Task Management
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
                <?php endif; ?>
            </nav>
        </aside>
        
        <main class="main-content">
            <?= $content ?>
        </main>
    </div>

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
    }
    
    function toggleNotifications() {
        var dropdown = document.getElementById('notificationDropdown');
        if (dropdown) {
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }
    }
    </script>
</body>
</html>>
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
                    <a href="/ergon/project-management" class="sidebar__link <?= $active_page === 'project-management' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📁</span>
                        Project Management
                    </a>
                    <div class="sidebar__divider">Company Overview</div>
                    <a href="/ergon/tasks" class="sidebar__link <?= $active_page === 'tasks' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">✅</span>
                        Task Overview
                    </a>
                    <a href="/ergon/daily-workflow/morning-planner" class="sidebar__link <?= $active_page === 'planner' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">🌅</span>
                        Morning Planner
                    </a>
                    <a href="/ergon/followups" class="sidebar__link <?= $active_page === 'followups' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📞</span>
                        Follow-ups
                    </a>
                    <a href="/ergon/daily-workflow/progress-dashboard" class="sidebar__link <?= $active_page === 'daily-planner-dashboard' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📊</span>
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
                    <a href="/ergon/daily-workflow/morning-planner" class="sidebar__link <?= $active_page === 'planner' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">🌅</span>
                        Morning Planner
                    </a>
                    <a href="/ergon/followups" class="sidebar__link <?= $active_page === 'followups' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📞</span>
                        Follow-ups
                    </a>
                    <a href="/ergon/daily-workflow/progress-dashboard" class="sidebar__link <?= $active_page === 'daily-planner-dashboard' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📊</span>
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
                    <a href="/ergon/tasks" class="sidebar__link <?= $active_page === 'tasks' ? 'sidebar__link--active' : '' ?>" onclick="console.log('Tasks link clicked')">
                        <span class="sidebar__icon">✅</span>
                        My Tasks
                    </a>
                    <a href="/ergon/daily-workflow/morning-planner" class="sidebar__link <?= $active_page === 'planner' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">🌅</span>
                        Morning Planner
                    </a>
                    <a href="/ergon/followups" class="sidebar__link <?= $active_page === 'followups' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">📞</span>
                        Follow-ups
                    </a>
                    <a href="/ergon/daily-workflow/evening-update" class="sidebar__link <?= $active_page === 'daily-planner' ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__icon">🌆</span>
                        Evening Update
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
        
        <div class="notification-dropdown" id="notificationDropdown">
            <div class="notification-header">
                <h3>Notifications</h3>
                <a href="/ergon/notifications" class="view-all-link">View All</a>
            </div>
            <div class="notification-list" id="notificationList">
                <div class="notification-loading">Loading...</div>
            </div>
        </div>

        <main class="main-content">
            <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'user'): ?>
            <div class="gamification-header" style="margin-bottom: 1rem;">
                <?php include __DIR__ . '/../shared/gamification_widget.php'; ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['user_id'])): ?>
            <?php include __DIR__ . '/../shared/team_competition_dashboard.php'; ?>
            
            <?php include __DIR__ . '/../shared/motivational_challenges.php'; ?>
            
            <div style="display: grid; grid-template-columns: 1fr 300px; gap: 20px; margin: 20px 0;">
                <div>
                    <?php include __DIR__ . '/../shared/task_progress_comparison.php'; ?>
                </div>
                <div>
                    <?php include __DIR__ . '/../shared/live_activity_feed.php'; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?= $content ?>
        </main>
    </div>

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
    
    window.toggleProfile = function() {
        var menu = document.getElementById('profileMenu');
        console.log('Toggle profile clicked', menu);
        menu.classList.toggle('show');
        console.log('Menu classes:', menu.className);
    }
    
    document.addEventListener('click', function(e) {
        const sidebar = document.querySelector('.sidebar');
        const toggle = document.querySelector('.mobile-menu-toggle');
        
        // Don't interfere with sidebar links
        if (e.target.closest('.sidebar__link')) {
            console.log('Sidebar link clicked, allowing navigation');
            return;
        }
        
        if (window.innerWidth <= 768 && sidebar && !sidebar.contains(e.target) && toggle && !toggle.contains(e.target)) {
            sidebar.classList.remove('sidebar--open');
        }
        
        if (!e.target.closest('.sidebar__profile-section')) {
            var menu = document.getElementById('profileMenu');
            if (menu) menu.classList.remove('show');
        }
        
        var dropdown = document.getElementById('notificationDropdown');
        if (dropdown && !e.target.closest('.sidebar__control-btn')) {
            dropdown.style.display = 'none';
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
            window.location.replace('/ergon/login');
        }
    };
    </script>
    <script src="/ergon/assets/js/auth-guard.min.js?v=<?= time() ?>" defer></script>
</body>
</html>