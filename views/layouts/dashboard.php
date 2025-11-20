<?php
ob_start();
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/../../app/helpers/Security.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) { header('Location: /ergon/login'); exit; }
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 28800)) { session_unset(); session_destroy(); header('Location: /ergon/login?timeout=1'); exit; }
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Security::escape(Security::generateCSRFToken()) ?>">
    <title><?= $title ?? 'Dashboard' ?> - ergon</title>
    <link rel="icon" type="image/x-icon" href="data:image/x-icon;base64,">
    
    <script src="/ergon/assets/js/theme-preload.js"></script>
    
    <style>
    /* Critical inline CSS to prevent FOUC and layout forcing */
    html{box-sizing:border-box}*,*:before,*:after{box-sizing:inherit}
    body{font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;margin:0;padding:0;background:#f8fafc;overflow-x:hidden}
    .main-header{background:#000080;position:fixed;top:0;left:0;right:0;z-index:9999;width:100%;height:110px}
    .header__top{display:flex;align-items:center;justify-content:space-between;padding:12px 24px;height:60px}
    .header__nav-container{height:50px;/*border-top:1px solid rgba(255,255,255,0.1)*/}
    .main-content{margin:110px 0 0 0;padding:24px 24px 24px 0;background:#f8fafc;min-height:calc(100vh - 110px);width:100%;max-width:100vw;overflow-x:hidden;position:relative}
    .sidebar{position:fixed;left:-280px;top:0;width:280px;height:100vh;background:#fff;z-index:9998;transition:left 0.3s ease}
    .mobile-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9997;display:none}
    </style>
    
    <link href="/ergon/assets/css/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/ergon/assets/css/ergon.css?v=1.0" rel="stylesheet">
    <link href="/ergon/assets/css/theme-enhanced.css?v=1.0" rel="stylesheet">
    <link href="/ergon/assets/css/utilities-new.css?v=1.0" rel="stylesheet">
    <link href="/ergon/assets/css/instant-theme.css?v=1.0" rel="stylesheet">
    <link href="/ergon/assets/css/global-tooltips.css?v=1.0" rel="stylesheet">
    <link href="/ergon/assets/css/action-button-clean.css?v=1.0" rel="stylesheet">
    <link href="/ergon/assets/css/responsive-mobile.css?v=1.0" rel="stylesheet">
    <?php if (isset($active_page) && $active_page === 'dashboard' && isset($_SESSION['role']) && $_SESSION['role'] === 'owner'): ?>
    <link href="/ergon/assets/css/dashboard-owner.css?v=1.0" rel="stylesheet">
    <?php endif; ?>

    <script src="/ergon/assets/js/theme-switcher.js?v=<?= time() ?>" defer></script>
    <script src="/ergon/assets/js/ergon-core.min.js?v=<?= time() ?>" defer></script>
    <script src="/ergon/assets/js/action-button-clean.js?v=<?= time() ?>" defer></script>
    <script src="/ergon/assets/js/mobile-enhanced.js?v=<?= time() ?>" defer></script>
    <script src="/ergon/assets/js/mobile-table-cards.js?v=<?= time() ?>" defer></script>

    <?php if (isset($_GET['validate']) && $_GET['validate'] === 'mobile'): ?>
    <script src="/ergon/assets/js/mobile-validation.js?v=<?= time() ?>" defer></script>
    <?php endif; ?>
</head>
<body data-layout="<?= isset($userPrefs['dashboard_layout']) ? $userPrefs['dashboard_layout'] : 'default' ?>" data-lang="<?= isset($userPrefs['language']) ? $userPrefs['language'] : 'en' ?>" data-page="<?= isset($active_page) ? $active_page : '' ?>">
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
                <button class="control-btn" onclick="toggleNotifications(event)" title="Notifications">
                    <i class="bi bi-bell-fill"></i>
                    <span class="notification-badge" id="notificationBadge">0</span>
                </button>
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
                    <a href="/ergon/profile/preferences" class="profile-menu-item">
                        <span class="menu-icon"><i class="bi bi-palette-fill"></i></span>
                        Appearance
                    </a>
                    <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['owner', 'admin'])): ?>
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
                    <div class="nav-dropdown">
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
                    <div class="nav-dropdown">
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
                            <a href="/ergon/contacts/followups" class="nav-dropdown-item <?= ($active_page ?? '') === 'contact_followups' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">📞</span>
                                Follow-ups
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
                    <div class="nav-dropdown">
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
                    <div class="nav-dropdown">
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
                    <div class="nav-dropdown">
                        <button class="nav-dropdown-btn" onclick="toggleDropdown('tasks')">
                            <span class="nav-icon">✅</span>
                            Tasks
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        <div class="nav-dropdown-menu" id="tasks">
                            <a href="/ergon/tasks" class="nav-dropdown-item <?= ($active_page ?? '') === 'tasks' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">✅</span>
                                Overall Tasks
                            </a>
                            <a href="/ergon/workflow/daily-planner" class="nav-dropdown-item <?= ($active_page ?? '') === 'daily-planner' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">🌅</span>
                                Daily Planner
                            </a>
                            <a href="/ergon/contacts/followups" class="nav-dropdown-item <?= ($active_page ?? '') === 'contact_followups' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">📞</span>
                                Follow-ups
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
                    <div class="nav-dropdown">
                        <button class="nav-dropdown-btn" onclick="toggleDropdown('work')">
                            <span class="nav-icon">✅</span>
                            Work
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        <div class="nav-dropdown-menu" id="work">
                            <a href="/ergon/tasks" class="nav-dropdown-item <?= ($active_page ?? '') === 'tasks' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">✅</span>
                                Tasks
                            </a>
                            <a href="/ergon/workflow/daily-planner" class="nav-dropdown-item <?= ($active_page ?? '') === 'daily-planner' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">📅</span>
                                Daily Planner
                            </a>
                            <a href="/ergon/contacts/followups" class="nav-dropdown-item <?= ($active_page ?? '') === 'contact_followups' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">📞</span>
                                Follow-ups
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
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'owner'): ?>
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
                <a href="/ergon/system-admin" class="sidebar__link <?= ($active_page ?? '') === 'system-admin' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">🔧</span>
                    System
                </a>
                <a href="/ergon/admin/management" class="sidebar__link <?= ($active_page ?? '') === 'admin' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">👥</span>
                    Users
                </a>
                <a href="/ergon/departments" class="sidebar__link <?= ($active_page ?? '') === 'departments' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">🏢</span>
                    Departments
                </a>
                <a href="/ergon/project-management" class="sidebar__link <?= ($active_page ?? '') === 'project-management' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">📁</span>
                    Projects
                </a>
                
                <div class="sidebar__divider">Operations</div>
                <a href="/ergon/tasks" class="sidebar__link <?= ($active_page ?? '') === 'tasks' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">✅</span>
                    Tasks
                </a>
                <a href="/ergon/contacts/followups" class="sidebar__link <?= ($active_page ?? '') === 'contact_followups' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">📞</span>
                    Follow-ups
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
            <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="/ergon/dashboard" class="sidebar__link <?= ($active_page ?? '') === 'dashboard' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">📊</span>
                    Dashboard
                </a>
                <a href="/ergon/tasks" class="sidebar__link <?= ($active_page ?? '') === 'tasks' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">✅</span>
                    Tasks
                </a>
                <a href="/ergon/users" class="sidebar__link <?= ($active_page ?? '') === 'users' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">👥</span>
                    Users
                </a>
                <a href="/ergon/leaves" class="sidebar__link <?= ($active_page ?? '') === 'leaves' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">📅</span>
                    Leaves
                </a>
                <a href="/ergon/expenses" class="sidebar__link <?= ($active_page ?? '') === 'expenses' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">💰</span>
                    Expenses
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
                <a href="/ergon/tasks" class="sidebar__link <?= ($active_page ?? '') === 'tasks' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">✅</span>
                    Tasks
                </a>
                <a href="/ergon/workflow/daily-planner" class="sidebar__link <?= ($active_page ?? '') === 'daily-planner' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">📅</span>
                    Daily Planner
                </a>
                <a href="/ergon/contacts/followups" class="sidebar__link <?= ($active_page ?? '') === 'contact_followups' ? 'sidebar__link--active' : '' ?>">
                    <span class="sidebar__icon">📞</span>
                    Follow-ups
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
    // Global variables - Initialize first
    let attendanceState = 'out'; // 'in' or 'out'
    
    // Simple dropdown system
    function toggleDropdown(id) {
        var dropdown = document.getElementById(id);
        if (!dropdown) return;
        
        var isOpen = dropdown.classList.contains('show');
        
        // Close all dropdowns
        document.querySelectorAll('.nav-dropdown-menu').forEach(function(menu) {
            menu.classList.remove('show');
            var btn = menu.previousElementSibling;
            if (btn) btn.classList.remove('active');
        });
        
        // Open this dropdown if it was closed
        if (!isOpen) {
            var btn = dropdown.previousElementSibling;
            var rect = btn.getBoundingClientRect();
            
            dropdown.style.position = 'fixed';
            dropdown.style.top = (rect.bottom + 8) + 'px';
            dropdown.style.left = rect.left + 'px';
            dropdown.style.zIndex = '99999';
            
            dropdown.classList.add('show');
            btn.classList.add('active');
        }
    }
    
    window.toggleDropdown = toggleDropdown;
    
    function toggleNotifications(event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        var dropdown = document.getElementById('notificationDropdown');
        var button = event.target.closest('.control-btn');
        
        if (dropdown && button) {
            var isVisible = dropdown.style.display === 'block';
            
            document.querySelectorAll('.nav-dropdown-menu').forEach(function(menu) {
                menu.classList.remove('show');
            });
            var profileMenu = document.getElementById('profileMenu');
            if (profileMenu) profileMenu.classList.remove('show');
            
            if (isVisible) {
                dropdown.style.display = 'none';
            } else {
                var rect = button.getBoundingClientRect();
                dropdown.style.position = 'fixed';
                dropdown.style.top = (rect.bottom + 8) + 'px';
                dropdown.style.right = (window.innerWidth - rect.right) + 'px';
                dropdown.style.left = 'auto';
                dropdown.style.zIndex = '10000';
                dropdown.style.display = 'block';
                
                loadNotifications();
            }
        }
    }
    
    function navigateToNotifications(event) {
        event.preventDefault();
        event.stopPropagation();
        
        var dropdown = document.getElementById('notificationDropdown');
        if (dropdown) {
            dropdown.style.display = 'none';
        }
        
        setTimeout(function() {
            window.location.href = '/ergon/notifications';
        }, 100);
        return false;
    }
    
    function loadNotifications() {
        var list = document.getElementById('notificationList');
        if (!list) return;
        
        fetch('/ergon/api/notifications.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.notifications && data.notifications.length > 0) {
                list.innerHTML = data.notifications.map(function(notif) {
                    var link = getNotificationLink(notif.module_name, notif.message);
                    return '<a href="' + link + '" class="notification-item" onclick="closeNotificationDropdown()">' +
                           '<div class="notification-title">' + (notif.action_type || 'Notification') + '</div>' +
                           '<div class="notification-message">' + (notif.message || '') + '</div>' +
                           '<div class="notification-time">' + formatTime(notif.created_at) + '</div>' +
                           '</a>';
                }).join('');
                
                updateNotificationBadge(data.unread_count || 0);
            } else {
                list.innerHTML = '<div class="notification-loading">No notifications</div>';
                updateNotificationBadge(0);
            }
        })
        .catch(error => {
            console.warn('Notification loading failed:', error.message);
            list.innerHTML = '<div class="notification-loading">Unable to load notifications</div>';
            updateNotificationBadge(0);
        });
    }
    
    function updateNotificationBadge(count) {
        var badge = document.getElementById('notificationBadge');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'block' : 'none';
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        loadNotifications();
        checkAttendanceStatus();
        
        // Ensure profile button is clickable
        var profileBtn = document.getElementById('profileButton');
        if (profileBtn) {
            profileBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleProfile();
            });
        }
    });
    
    function getNotificationLink(module, message) {
        switch(module) {
            case 'task': return '/ergon/tasks';
            case 'leave': return '/ergon/leaves';
            case 'expense': return '/ergon/expenses';
            case 'advance': return '/ergon/advances';
            default: return '/ergon/notifications';
        }
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
        fetch('/ergon/api/notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=mark-all-read'
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
    
    window.showDropdown = showDropdown;
    window.hideDropdown = hideDropdown;
    
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.header__controls')) {
            var menu = document.getElementById('profileMenu');
            if (menu) menu.classList.remove('show');
        }
        
        var dropdown = document.getElementById('notificationDropdown');
        if (dropdown && !e.target.closest('.control-btn') && !e.target.closest('#notificationDropdown')) {
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

    function goBack() {
        if (document.referrer && document.referrer.includes('/ergon/')) {
            window.history.back();
        } else {
            window.location.href = '/ergon/tasks';
        }
    }
    
    function toggleLeaveFilters() {
        const panel = document.getElementById('leaveFiltersPanel');
        if (panel) {
            panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        }
    }
    
    function initTooltips() {
        return;
    }
    
    // Attendance Toggle Function
    function toggleAttendance() {
        const button = document.getElementById('attendanceToggle');
        const icon = document.getElementById('attendanceIcon');
        const text = document.getElementById('attendanceText');
        
        // Immediate visual feedback
        button.disabled = true;
        button.classList.add('loading');
        text.textContent = 'Processing...';
        
        const action = attendanceState === 'out' ? 'in' : 'out';
        
        // Skip geolocation for faster response
        fetch('/ergon/attendance/clock', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `type=${action}&latitude=0&longitude=0`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                attendanceState = action;
                updateAttendanceButton();
                showAttendanceNotification(action === 'in' ? 'Clocked in successfully!' : 'Clocked out successfully!', 'success');
            } else {
                showAttendanceNotification(data.error || 'Failed to update attendance', 'error');
            }
        })
        .catch(error => {
            showAttendanceNotification('Network error occurred', 'error');
        })
        .finally(() => {
            button.disabled = false;
            button.classList.remove('loading');
        });
    }
    
    function updateAttendanceButton() {
        const button = document.getElementById('attendanceToggle');
        const icon = document.getElementById('attendanceIcon');
        const text = document.getElementById('attendanceText');
        
        if (attendanceState === 'in') {
            button.classList.remove('state-out');
            button.classList.add('state-in');
            icon.className = 'bi bi-stop-fill';
            text.textContent = 'Clock Out';
        } else {
            button.classList.remove('state-in');
            button.classList.add('state-out');
            icon.className = 'bi bi-play-fill';
            text.textContent = 'Clock In';
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
                <button class="dialog-close" onclick="this.parentElement.parentElement.remove()">OK</button>
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
    
    // Check attendance status on page load
    function checkAttendanceStatus() {
        // Ensure attendanceState is initialized before use
        if (typeof attendanceState === 'undefined') {
            attendanceState = 'out';
        }
        
        fetch('/ergon/attendance/status')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.attendance) {
                attendanceState = data.attendance.check_out ? 'out' : 'in';
                updateAttendanceButton();
            }
        })
        .catch(error => {
            console.warn('Attendance status check failed:', error.message);
            // Keep default state on error
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