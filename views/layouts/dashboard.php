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
    /* Header styles - two-row layout */
    .main-header {
        background: #1e293b;
        border-bottom: 1px solid #334155;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        position: sticky;
        top: 0;
        z-index: 9999;
        width: 100%;
    }
    
    .header__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 24px;
        border-bottom: 1px solid #334155;
    }
    
    .header__nav-container {
        padding: 8px 0;
        overflow-x: auto;
        scrollbar-width: none;
        -ms-overflow-style: none;
        width: 100%;
    }
    
    .header__nav-container::-webkit-scrollbar {
        display: none;
    }
    
    .header__brand {
        display: flex;
        align-items: center;
        gap: 12px;
        color: #f1f5f9;
        font-weight: 600;
        font-size: 18px;
    }
    
    .brand-icon {
        font-size: 24px;
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
    }
    
    .brand-text {
        font-size: 20px;
        font-weight: 700;
        color: #f8fafc;
    }
    
    .role-badge {
        background: #475569;
        color: #cbd5e1;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border: 1px solid #64748b;
    }
    
    .header__nav {
        display: flex;
        align-items: center;
        gap: 8px;
        width: 100%;
        justify-content: flex-start;
    }
    
    .nav-dropdown {
        position: relative;
        display: inline-block;
    }
    
    .nav-dropdown-btn {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 12px;
        background: #334155;
        border: 1px solid #475569;
        border-radius: 6px;
        color: #cbd5e1;
        cursor: pointer;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.2s ease;
        white-space: nowrap;
    }
    
    .nav-dropdown-btn:hover {
        background: #475569;
        border-color: #64748b;
        color: #f1f5f9;
    }
    
    .nav-dropdown:hover .nav-dropdown-menu {
        display: block !important;
        animation: slideDown 0.3s ease;
    }
    
    .nav-dropdown:hover .nav-dropdown-btn .dropdown-arrow {
        transform: rotate(180deg);
    }
    
    .nav-dropdown-btn .dropdown-arrow {
        font-size: 10px;
        transition: transform 0.3s ease;
    }
    
    .nav-dropdown-btn.active .dropdown-arrow {
        transform: rotate(180deg);
    }
    
    .nav-dropdown-menu {
        display: none;
        position: fixed;
        background: #1e293b;
        border: 1px solid #475569;
        border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
        margin-top: 8px;
        overflow: hidden;
        min-width: 200px;
        z-index: 99999;
        backdrop-filter: blur(20px);
    }
    
    .nav-dropdown-menu.show {
        display: block !important;
        animation: slideDown 0.3s ease;
    }
    
    .nav-dropdown-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        color: #cbd5e1;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s ease;
        border-left: 3px solid transparent;
    }
    
    .nav-dropdown-item:hover {
        background: #334155;
        color: #f1f5f9;
        border-left-color: #64748b;
        transform: translateX(4px);
    }
    
    .nav-dropdown-item--active {
        background: #475569;
        color: #f1f5f9;
        border-left-color: #94a3b8;
        font-weight: 600;
    }
    
    .nav-link {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 10px;
        color: #94a3b8;
        text-decoration: none;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        transition: all 0.2s ease;
        white-space: nowrap;
        min-width: fit-content;
        border: 1px solid transparent;
        flex-shrink: 0;
    }
    
    .nav-link:hover {
        background: #334155;
        color: #e2e8f0;
        border-color: #475569;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .nav-link--active {
        background: #475569;
        color: #f1f5f9;
        border-color: #64748b;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        font-weight: 600;
    }
    
    .nav-icon {
        font-size: 14px;
        filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.2));
        flex-shrink: 0;
    }
    
    .header__controls {
        display: flex;
        align-items: center;
        gap: 8px;
        position: relative;
    }
    
    .control-btn, .profile-btn {
        background: #334155;
        border: 1px solid #475569;
        border-radius: 8px;
        color: #cbd5e1;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
    }
    
    .control-btn {
        width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .profile-btn {
        padding: 8px 12px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 500;
    }
    
    .control-btn:hover, .profile-btn:hover {
        background: #475569;
        border-color: #64748b;
        color: #f1f5f9;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .profile-avatar {
        width: 26px;
        height: 26px;
        background: #64748b;
        border: 1px solid #94a3b8;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 12px;
        color: #f1f5f9;
    }
    
    .profile-name {
        font-weight: 500;
        max-width: 100px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        color: #e2e8f0;
    }
    
    .dropdown-arrow {
        font-size: 10px;
        transition: transform 0.3s ease;
        color: #94a3b8;
    }
    
    .profile-btn:hover .dropdown-arrow {
        transform: rotate(180deg);
        color: #cbd5e1;
    }
    
    .notification-badge {
        position: absolute;
        top: -4px;
        right: -4px;
        background: #dc2626;
        color: white;
        font-size: 10px;
        font-weight: bold;
        padding: 2px 6px;
        border-radius: 10px;
        min-width: 16px;
        text-align: center;
        border: 1px solid #1e293b;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
    }
    
    /* Main content - override external CSS */
    .main-content {
        margin-left: 0 !important;
        margin-right: 0 !important;
        padding: 24px !important;
        background: #f8fafc !important;
        min-height: calc(100vh - 110px) !important;
        width: 100vw !important;
        max-width: 100vw !important;
        box-sizing: border-box !important;
        position: relative;
        z-index: 1;
    }
    
    @media (max-width: 1200px) {
        .nav-group {
            padding: 0 8px;
        }
        
        .nav-group-label {
            font-size: 9px;
            padding: 4px 6px;
            margin-right: 4px;
        }
        
        .nav-link {
            padding: 6px 8px;
            font-size: 11px;
            gap: 4px;
        }
        
        .nav-icon {
            font-size: 13px;
        }
    }
    
    @media (max-width: 768px) {
        .header__top {
            padding: 10px 16px;
        }
        
        .header__nav-container {
            padding: 6px 0;
            overflow-x: auto;
        }
        
        .header__brand {
            font-size: 16px;
        }
        
        .header__nav {
            min-width: 800px;
            justify-content: flex-start;
            gap: 8px;
        }
        
        .nav-group {
            padding: 0 6px;
            flex: none;
            min-width: fit-content;
        }
        
        .nav-group-label {
            font-size: 8px;
            padding: 3px 5px;
            margin-right: 3px;
        }
        
        .nav-link {
            padding: 5px 6px;
            font-size: 10px;
            gap: 3px;
        }
        
        .nav-icon {
            font-size: 12px;
        }
        
        .profile-name {
            display: none;
        }
        
        .main-content {
            padding: 16px !important;
            min-height: calc(100vh - 90px) !important;
        }
    }
    
    @media (max-width: 480px) {
        .header__nav {
            min-width: 600px;
        }
        
        .nav-group {
            padding: 0 4px;
        }
        
        .nav-group-label {
            display: none;
        }
        
        .nav-link {
            padding: 4px 5px;
            font-size: 9px;
        }
        
        .nav-icon {
            font-size: 11px;
        }
    }

    .profile-menu {
        display: none;
        position: absolute;
        top: 100%;
        right: 0;
        background: #1e293b;
        border: 1px solid #475569;
        border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
        margin-top: 8px;
        overflow: hidden;
        min-width: 200px;
        z-index: 10001;
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
        padding: 12px 16px;
        color: #cbd5e1;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s ease;
        border-left: 3px solid transparent;
    }
    .profile-menu-item:hover {
        background: #334155;
        color: #f1f5f9;
        border-left-color: #64748b;
        transform: translateX(4px);
    }
    .profile-menu-item--danger {
        color: #f87171;
    }
    .profile-menu-item--danger:hover {
        background: #450a0a;
        color: #fca5a5;
        border-left-color: #dc2626;
    }
    .profile-menu-divider {
        height: 1px;
        background: #475569;
        margin: 8px 0;
    }
    .menu-icon {
        margin-right: 12px;
        font-size: 16px;
        width: 20px;
        text-align: center;
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
    
    /* Modern Slim Button Styles */
    .btn {
        padding: 8px 16px !important;
        font-size: 13px !important;
        font-weight: 500 !important;
        border-radius: 6px !important;
        border: 1px solid #e5e7eb !important;
        background: #ffffff !important;
        color: #374151 !important;
        text-decoration: none !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        transition: all 0.2s ease !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
    }
    
    .btn:hover {
        background: #f9fafb !important;
        border-color: #d1d5db !important;
        color: #111827 !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
    }
    
    .btn--primary {
        background: #111827 !important;
        color: #ffffff !important;
        border-color: #111827 !important;
    }
    
    .btn--primary:hover {
        background: #1f2937 !important;
        border-color: #1f2937 !important;
        color: #ffffff !important;
    }
    
    .btn--secondary {
        background: #f3f4f6 !important;
        color: #6b7280 !important;
        border-color: #e5e7eb !important;
    }
    
    .btn--secondary:hover {
        background: #e5e7eb !important;
        color: #374151 !important;
    }
    
    .btn--success {
        background: #f0fdf4 !important;
        color: #166534 !important;
        border-color: #bbf7d0 !important;
    }
    
    .btn--success:hover {
        background: #dcfce7 !important;
        color: #14532d !important;
    }
    
    /* Prevent scroll restoration */
    html {
        scroll-behavior: auto !important;
    }
    
    /* Prevent any scroll anchoring */
    * {
        scroll-behavior: auto !important;
    }
    
    body {
        scroll-behavior: auto !important;
    }
    </style>
    <?php if (isset($userPrefs['theme']) && $userPrefs['theme'] === 'dark'): ?>
    <link id="dark-theme-css" href="/ergon/assets/css/dark-theme.css" rel="stylesheet">
    <?php endif; ?>
</head>
<body data-theme="<?= isset($userPrefs['theme']) ? $userPrefs['theme'] : 'light' ?>" data-layout="<?= isset($userPrefs['dashboard_layout']) ? $userPrefs['dashboard_layout'] : 'default' ?>" data-lang="<?= isset($userPrefs['language']) ? $userPrefs['language'] : 'en' ?>">
    <header class="main-header">
        <div class="header__top">
            <div class="header__brand">
                <span class="brand-icon">🧭</span>
                <span class="brand-text">Ergon</span>
                <span class="role-badge"><?= htmlspecialchars(ucfirst($_SESSION['role'] ?? 'User'), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            
            <div class="header__controls">
                <button class="control-btn" onclick="toggleTheme()" title="Toggle Theme">
                    <span id="themeIcon"><?= (isset($userPrefs['theme']) && $userPrefs['theme'] === 'dark') ? '☀️' : '🌙' ?></span>
                </button>
                <button class="control-btn" onclick="toggleNotifications()" title="Notifications">
                    <span>🔔</span>
                    <span class="notification-badge" id="notificationBadge">0</span>
                </button>
                <button class="profile-btn" onclick="document.getElementById('profileMenu').classList.toggle('show')">
                    <span class="profile-avatar"><?= htmlspecialchars(strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)), ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="profile-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></span>
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
        
        <div class="header__nav-container">
            <nav class="header__nav">
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'owner'): ?>
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
                            <a href="/ergon/followups" class="nav-dropdown-item <?= ($active_page ?? '') === 'followups' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">📞</span>
                                Follow-ups
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
                                Allocation
                            </a>
                            <a href="/ergon/daily-workflow/morning-planner" class="nav-dropdown-item <?= ($active_page ?? '') === 'planner' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">🌅</span>
                                Planner
                            </a>
                            <a href="/ergon/followups" class="nav-dropdown-item <?= ($active_page ?? '') === 'followups' ? 'nav-dropdown-item--active' : '' ?>">
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
                            <a href="/ergon/gamification/team-competition" class="nav-dropdown-item <?= ($active_page ?? '') === 'team-competition' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">🏆</span>
                                Competition
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
                            <a href="/ergon/daily-workflow/morning-planner" class="nav-dropdown-item <?= ($active_page ?? '') === 'planner' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">🌅</span>
                                Planner
                            </a>
                            <a href="/ergon/daily-workflow/evening-update" class="nav-dropdown-item <?= ($active_page ?? '') === 'daily-planner' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">🌆</span>
                                Evening
                            </a>
                            <a href="/ergon/followups" class="nav-dropdown-item <?= ($active_page ?? '') === 'followups' ? 'nav-dropdown-item--active' : '' ?>">
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
            <?php if (isset($title) && in_array($title, ['Executive Dashboard', 'Team Competition Dashboard', 'Follow-ups Management', 'System Settings', 'IT Activity Reports'])): ?>
            <div class="page-header">
                <div class="page-title">
                    <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
                </div>
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
    
    function toggleNotifications() {
        var dropdown = document.getElementById('notificationDropdown');
        if (dropdown) {
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }
    }
    
    function showDropdown(id) {
        var dropdown = document.getElementById(id);
        var btn = dropdown.previousElementSibling;
        var btnRect = btn.getBoundingClientRect();
        
        // Position dropdown
        dropdown.style.top = (btnRect.bottom + 8) + 'px';
        dropdown.style.left = btnRect.left + 'px';
        
        // Close all other dropdowns
        document.querySelectorAll('.nav-dropdown-menu').forEach(function(menu) {
            if (menu.id !== id) {
                menu.classList.remove('show');
            }
        });
        
        dropdown.classList.add('show');
        btn.classList.add('active');
    }
    
    function hideDropdown(id) {
        var dropdown = document.getElementById(id);
        var btn = dropdown.previousElementSibling;
        
        dropdown.classList.remove('show');
        btn.classList.remove('active');
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
        if (dropdown && !e.target.closest('.control-btn')) {
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
    

    
    window.onpageshow = function(event) {
        if (event.persisted) {
            window.location.replace('/ergon/login');
        }
    };
    
    // Disable scroll restoration and preserve scroll position
    if ('scrollRestoration' in history) {
        history.scrollRestoration = 'manual';
    }
    
    // Store current scroll position before navigation
    let savedScrollPosition = 0;
    
    // Save scroll position on nav link click
    document.addEventListener('click', function(e) {
        if (e.target.closest('.nav-link')) {
            savedScrollPosition = window.pageYOffset || document.documentElement.scrollTop;
            localStorage.setItem('ergonScrollPos', savedScrollPosition);
        }
    });
    
    // Restore scroll position on page load
    document.addEventListener('DOMContentLoaded', function() {
        const savedPos = localStorage.getItem('ergonScrollPos');
        if (savedPos) {
            setTimeout(function() {
                window.scrollTo(0, parseInt(savedPos));
                localStorage.removeItem('ergonScrollPos');
            }, 50);
        }
    });
    

    </script>
    <script src="/ergon/assets/js/auth-guard.min.js?v=<?= time() ?>" defer></script>
</body>
</html>