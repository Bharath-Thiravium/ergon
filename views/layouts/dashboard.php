<?php
require_once __DIR__ . '/../../app/core/Session.php';
Session::init();

if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    if (!headers_sent()) {
        header('Location: /ergon/login');
    }
    exit;
}

// Initialize content variable to prevent undefined variable warning
if (!isset($content)) {
    $content = '';
}

// Removed aggressive cache headers

// Extend session timeout to 8 hours for better user experience
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 28800)) {
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

    <meta name="csrf-token" content="<?= Security::escape(Security::generateCSRFToken()) ?>">
    <title><?= $title ?? 'Dashboard' ?> - ergon</title>

    <link rel="preload" href="/ergon/assets/css/ergon.css?v=<?= time() ?>" as="style">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="/ergon/assets/css/ergon.css?v=<?= time() ?>" rel="stylesheet">
</head>
<body data-theme="<?= isset($userPrefs['theme']) ? $userPrefs['theme'] : 'light' ?>" data-layout="<?= isset($userPrefs['dashboard_layout']) ? $userPrefs['dashboard_layout'] : 'default' ?>" data-lang="<?= isset($userPrefs['language']) ? $userPrefs['language'] : 'en' ?>" data-page="<?= isset($active_page) ? $active_page : '' ?>">
    <header class="main-header">
        <div class="header__top">
            <div class="header__brand">
                <span class="brand-icon"><i class="bi bi-compass-fill"></i></span>
                <span class="brand-text">Ergon</span>
                <span class="role-badge"><?= htmlspecialchars(ucfirst($_SESSION['role'] ?? 'User'), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            
            <div class="header__controls">
                <button class="control-btn" onclick="toggleTheme()" title="Toggle Theme">
                    <span id="themeIcon"><i class="bi bi-<?= (isset($userPrefs['theme']) && $userPrefs['theme'] === 'dark') ? 'sun-fill' : 'moon-fill' ?>"></i></span>
                </button>
                <button class="control-btn" onclick="toggleNotifications(event)" title="Notifications">
                    <i class="bi bi-bell-fill"></i>
                    <span class="notification-badge" id="notificationBadge">0</span>
                </button>
                <button class="profile-btn" onclick="document.getElementById('profileMenu').classList.toggle('show')">
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
                    <a href="/ergon/profile/preferences" class="profile-menu-item">
                        <span class="menu-icon"><i class="bi bi-palette-fill"></i></span>
                        Appearance
                    </a>
                    <a href="/ergon/settings" class="profile-menu-item">
                        <span class="menu-icon"><i class="bi bi-gear-fill"></i></span>
                        System Settings
                    </a>
                    <div class="profile-menu-divider"></div>
                    <a href="/ergon/logout" class="profile-menu-item profile-menu-item--danger">
                        <span class="menu-icon"><i class="bi bi-box-arrow-right"></i></span>
                        Logout
                    </a>
                </div>
            </div>
        </div>
        
        <div class="header__nav-container">
            <nav class="header__nav">
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'owner'): ?>
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
                    <div class="nav-dropdown" onmouseenter="showDropdown('management')" onmouseleave="hideDropdown('management')">
                        <button class="nav-dropdown-btn" onclick="toggleDropdown('management')">
                            <span class="nav-icon">🔧</span>
                            Management
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        <div class="nav-dropdown-menu" id="management">
                            <a href="/ergon/system-admin" class="nav-dropdown-item <?= ($active_page ?? '') === 'system-admin' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">🔧</span>
                                System
                            </a>
                            <a href="/ergon/admin/management" class="nav-dropdown-item <?= ($active_page ?? '') === 'admin' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">👥</span>
                                Users
                            </a>
                            <a href="/ergon/departments" class="nav-dropdown-item <?= ($active_page ?? '') === 'departments' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">🏢</span>
                                Departments
                            </a>
                            <a href="/ergon/project-management" class="nav-dropdown-item <?= ($active_page ?? '') === 'project-management' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">📁</span>
                                Projects
                            </a>
                        </div>
                    </div>
                    <div class="nav-dropdown" onmouseenter="showDropdown('operations')" onmouseleave="hideDropdown('operations')">
                        <button class="nav-dropdown-btn" onclick="toggleDropdown('operations')">
                            <span class="nav-icon">✅</span>
                            Operations
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        <div class="nav-dropdown-menu" id="operations">
                            <a href="/ergon/tasks" class="nav-dropdown-item <?= ($active_page ?? '') === 'tasks' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">✅</span>
                                Tasks
                            </a>
                            <a href="/ergon/workflow/followups" class="nav-dropdown-item <?= ($active_page ?? '') === 'followups' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">📞</span>
                                Follow-ups
                            </a>
                            <a href="/ergon/workflow/calendar" class="nav-dropdown-item <?= ($active_page ?? '') === 'calendar' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">📆</span>
                                Calendar
                            </a>
                        </div>
                    </div>
                    <div class="nav-dropdown" onmouseenter="showDropdown('hrfinance')" onmouseleave="hideDropdown('hrfinance')">
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
                    <div class="nav-dropdown" onmouseenter="showDropdown('analytics')" onmouseleave="hideDropdown('analytics')">
                        <button class="nav-dropdown-btn" onclick="toggleDropdown('analytics')">
                            <span class="nav-icon">📈</span>
                            Analytics
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        <div class="nav-dropdown-menu" id="analytics">
                            <a href="/ergon/reports" class="nav-dropdown-item <?= ($active_page ?? '') === 'reports' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">📈</span>
                                Reports
                            </a>
                            <a href="/ergon/settings" class="nav-dropdown-item <?= ($active_page ?? '') === 'settings' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">⚙️</span>
                                Settings
                            </a>
                        </div>
                    </div>
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
                    <div class="nav-dropdown" onmouseenter="showDropdown('team')" onmouseleave="hideDropdown('team')">
                        <button class="nav-dropdown-btn" onclick="toggleDropdown('team')">
                            <span class="nav-icon">👥</span>
                            Team
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        <div class="nav-dropdown-menu" id="team">
                            <a href="/ergon/users" class="nav-dropdown-item <?= ($active_page ?? '') === 'users' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">👥</span>
                                Members
                            </a>
                            <a href="/ergon/departments" class="nav-dropdown-item <?= ($active_page ?? '') === 'departments' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">🏢</span>
                                Departments
                            </a>
                        </div>
                    </div>
                    <div class="nav-dropdown" onmouseenter="showDropdown('tasks')" onmouseleave="hideDropdown('tasks')">
                        <button class="nav-dropdown-btn" onclick="toggleDropdown('tasks')">
                            <span class="nav-icon">✅</span>
                            Tasks
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        <div class="nav-dropdown-menu" id="tasks">
                            <a href="/ergon/tasks" class="nav-dropdown-item <?= ($active_page ?? '') === 'tasks' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">✅</span>
                                Allocation
                            </a>
                            <a href="/ergon/workflow/daily-planner" class="nav-dropdown-item <?= ($active_page ?? '') === 'daily-planner' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">🌅</span>
                                Planner
                            </a>
                            <a href="/ergon/workflow/followups" class="nav-dropdown-item <?= ($active_page ?? '') === 'followups' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">📞</span>
                                Follow-ups
                            </a>
                            <a href="/ergon/workflow/calendar" class="nav-dropdown-item <?= ($active_page ?? '') === 'calendar' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">📆</span>
                                Calendar
                            </a>
                        </div>
                    </div>
                    <div class="nav-dropdown" onmouseenter="showDropdown('approvals')" onmouseleave="hideDropdown('approvals')">
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
                            <a href="/ergon/reports/activity" class="nav-dropdown-item <?= ($active_page ?? '') === 'activity' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">⏱️</span>
                                Reports
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
                    <div class="nav-dropdown" onmouseenter="showDropdown('work')" onmouseleave="hideDropdown('work')">
                        <button class="nav-dropdown-btn" onclick="toggleDropdown('work')">
                            <span class="nav-icon">✅</span>
                            Work
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        <div class="nav-dropdown-menu" id="work" onmouseenter="showDropdown('work')" onmouseleave="hideDropdown('work')">
                            <a href="/ergon/tasks" class="nav-dropdown-item <?= ($active_page ?? '') === 'tasks' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">✅</span>
                                Tasks
                            </a>

                            <a href="/ergon/workflow/daily-planner" class="nav-dropdown-item <?= ($active_page ?? '') === 'daily-planner' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">📅</span>
                                Daily Planner
                            </a>
                            <a href="/ergon/workflow/evening-update" class="nav-dropdown-item <?= ($active_page ?? '') === 'evening-update' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">🌅</span>
                                Evening Update
                            </a>
                            <a href="/ergon/workflow/followups" class="nav-dropdown-item <?= ($active_page ?? '') === 'followups' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">📞</span>
                                Follow-ups
                            </a>
                            <a href="/ergon/workflow/calendar" class="nav-dropdown-item <?= ($active_page ?? '') === 'calendar' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">📆</span>
                                Calendar
                            </a>
                        </div>
                    </div>
                    <div class="nav-dropdown" onmouseenter="showDropdown('personal')" onmouseleave="hideDropdown('personal')">
                        <button class="nav-dropdown-btn" onclick="toggleDropdown('personal')">
                            <span class="nav-icon">📋</span>
                            Personal
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        <div class="nav-dropdown-menu" id="personal" onmouseenter="showDropdown('personal')" onmouseleave="hideDropdown('personal')">
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
    
    <div class="notification-dropdown" id="notificationDropdown">
        <div class="notification-header">
            <h3>Notifications</h3>
            <button type="button" class="view-all-link" onclick="navigateToNotifications(event)">View All</button>
        </div>
        <div class="notification-list" id="notificationList">
            <div class="notification-loading">Loading notifications...</div>
        </div>
    </div>

    <main class="main-content">
            <?php if (isset($title) && in_array($title, ['Executive Dashboard', 'Team Competition Dashboard', 'Follow-ups Management', 'System Settings', 'IT Activity Reports', 'Notifications'])): ?>
            <div class="page-header">
                <div class="page-title">
                    <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
                </div>
                <?php if ($title === 'Notifications'): ?>
                <div class="page-actions">
                    <button class="btn btn--primary" onclick="markAllAsRead()">
                        Mark All Read
                    </button>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?= $content ?>
    </main>

    <script>

    
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
    
    function toggleNotifications(event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        var dropdown = document.getElementById('notificationDropdown');
        var button = event.target.closest('.control-btn');
        
        if (dropdown && button) {
            var isVisible = dropdown.style.display === 'block';
            
            // Close other dropdowns first
            document.querySelectorAll('.nav-dropdown-menu').forEach(function(menu) {
                menu.classList.remove('show');
            });
            var profileMenu = document.getElementById('profileMenu');
            if (profileMenu) profileMenu.classList.remove('show');
            
            if (isVisible) {
                dropdown.style.display = 'none';
            } else {
                // Position dropdown relative to notification button
                var rect = button.getBoundingClientRect();
                dropdown.style.position = 'fixed';
                dropdown.style.top = (rect.bottom + 8) + 'px';
                dropdown.style.right = (window.innerWidth - rect.right) + 'px';
                dropdown.style.left = 'auto';
                dropdown.style.zIndex = '10000';
                dropdown.style.display = 'block';
                
                // Load notifications
                loadNotifications();
            }
        }
    }
    
    function navigateToNotifications(event) {
        event.preventDefault();
        event.stopPropagation();
        
        // Close the dropdown first
        var dropdown = document.getElementById('notificationDropdown');
        if (dropdown) {
            dropdown.style.display = 'none';
        }
        
        // Navigate to notifications page in the main window
        setTimeout(function() {
            window.location.href = '/ergon/notifications';
        }, 100);
        return false;
    }
    
    function loadNotifications() {
        var list = document.getElementById('notificationList');
        if (!list) return;
        
        fetch('/ergon/api/notifications')
        .then(response => response.json())
        .then(data => {
            if (data.notifications && data.notifications.length > 0) {
                list.innerHTML = data.notifications.map(function(notif) {
                    var link = getNotificationLink(notif.type, notif.message);
                    return '<a href="' + link + '" class="notification-item" onclick="closeNotificationDropdown()">' +
                           '<div class="notification-title">' + (notif.title || 'Notification') + '</div>' +
                           '<div class="notification-message">' + (notif.message || '') + '</div>' +
                           '<div class="notification-time">' + formatTime(notif.created_at) + '</div>' +
                           '</a>';
                }).join('');
            } else {
                list.innerHTML = '<div class="notification-loading">No notifications</div>';
            }
        })
        .catch(error => {
            list.innerHTML = '<div class="notification-loading">Failed to load notifications</div>';
        });
    }
    
    function getNotificationLink(type, message) {
        if (message.includes('task')) return '/ergon/tasks';
        if (message.includes('leave')) return '/ergon/leaves';
        if (message.includes('expense')) return '/ergon/expenses';
        if (message.includes('advance')) return '/ergon/advances';
        if (message.includes('approval')) return '/ergon/owner/approvals';
        return '/ergon/notifications';
    }
    
    function formatTime(dateStr) {
        var date = new Date(dateStr);
        var now = new Date();
        var diff = now - date;
        var minutes = Math.floor(diff / 60000);
        if (minutes < 1) return 'Just now';
        if (minutes < 60) return minutes + ' min ago';
        var hours = Math.floor(minutes / 60);
        if (hours < 24) return hours + ' hour' + (hours > 1 ? 's' : '') + ' ago';
        return date.toLocaleDateString();
    }
    
    function closeNotificationDropdown() {
        var dropdown = document.getElementById('notificationDropdown');
        if (dropdown) dropdown.style.display = 'none';
    }
    
    function markAllAsRead() {
        fetch('/ergon/api/notifications/mark-all-read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Failed to mark all as read');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error occurred');
        });
    }
    
    function showDropdown(id) {
        clearTimeout(hideTimeout);
        currentDropdown = id;
        
        var dropdown = document.getElementById(id);
        if (!dropdown) return;
        
        var btn = dropdown.previousElementSibling;
        var btnRect = btn.getBoundingClientRect();
        
        // Position dropdown
        dropdown.style.top = (btnRect.bottom + 8) + 'px';
        dropdown.style.left = btnRect.left + 'px';
        
        // Close all other dropdowns with fade out
        document.querySelectorAll('.nav-dropdown-menu').forEach(function(menu) {
            if (menu.id !== id && menu.classList.contains('show')) {
                menu.classList.remove('show');
                menu.previousElementSibling.classList.remove('active');
            }
        });
        
        // Show current dropdown with fade in
        setTimeout(function() {
            dropdown.classList.add('show');
            btn.classList.add('active');
        }, 50);
    }
    
    var hideTimeout;
    var currentDropdown = null;
    
    function hideDropdown(id) {
        hideTimeout = setTimeout(function() {
            var dropdown = document.getElementById(id);
            if (dropdown && currentDropdown === id) {
                var btn = dropdown.previousElementSibling;
                
                dropdown.classList.remove('show');
                btn.classList.remove('active');
                currentDropdown = null;
            }
        }, 150);
    }
    
    function toggleDropdown(id) {
        var dropdown = document.getElementById(id);
        var btn = dropdown.previousElementSibling;
        
        if (dropdown.classList.contains('show')) {
            hideDropdown(id);
        } else {
            showDropdown(id);
        }
    }
    
    window.toggleProfile = function() {
        var menu = document.getElementById('profileMenu');
        
        // Close all nav dropdowns
        document.querySelectorAll('.nav-dropdown-menu').forEach(function(dropdown) {
            dropdown.classList.remove('show');
        });
        document.querySelectorAll('.nav-dropdown-btn').forEach(function(btn) {
            btn.classList.remove('active');
        });
        
        menu.classList.toggle('show');
    }
    
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.header__controls')) {
            var menu = document.getElementById('profileMenu');
            if (menu) menu.classList.remove('show');
        }
        
        var dropdown = document.getElementById('notificationDropdown');
        if (dropdown && !e.target.closest('.control-btn') && !e.target.closest('#notificationDropdown')) {
            dropdown.style.display = 'none';
        }
        
        // Close nav dropdowns when clicking outside
        if (!e.target.closest('.nav-dropdown')) {
            document.querySelectorAll('.nav-dropdown-menu').forEach(function(menu) {
                menu.classList.remove('show');
            });
            document.querySelectorAll('.nav-dropdown-btn').forEach(function(btn) {
                btn.classList.remove('active');
            });
        }
    });
    

    
    // Removed aggressive page show redirect
    
    // Disable scroll restoration to prevent data duplication
    if ('scrollRestoration' in history) {
        history.scrollRestoration = 'auto';
    }
    
    // Standardized delete function
    function deleteRecord(module, id, name) {
        if (confirm('Are you sure you want to delete "' + name + '"? This action cannot be undone.')) {
            fetch('/ergon/' + module + '/delete/' + id, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to delete record'));
                }
            })
            .catch(error => {
                console.error('Delete error:', error);
                alert('An error occurred while deleting the record.');
            });
        }
    }
    

    </script>


</body>
</html>