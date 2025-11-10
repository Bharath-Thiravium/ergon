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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" rel="stylesheet" crossorigin="anonymous">
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
    
    .nav-dropdown:hover .nav-dropdown-btn {
        background: #475569;
        border-color: #64748b;
        color: #f1f5f9;
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
        opacity: 1;
        transform: translateY(0);
        animation: dropdownFadeIn 0.2s ease-out;
    }
    
    @keyframes dropdownFadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
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
    
    /* Hide duplicate headers in notification content only */
    body[data-page="notifications"] .main-content .main-header,
    body[data-page="notifications"] .main-content .header__top {
        display: none !important;
    }
    
    /* Decrease container size for notifications page */
    body[data-page="notifications"] .main-content {
        max-width: 1500px !important;
        margin: 0 auto !important;
        padding: 5px !important;
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
        cursor: pointer !important;
    }
    
    .btn-group {
        display: flex !important;
        gap: 4px !important;
        align-items: center !important;
    }
    
    .btn--sm {
        padding: 4px 8px !important;
        font-size: 11px !important;
        gap: 3px !important;
    }
    
    /* Standardized Detail/Profile Grid Styles */
    .detail-grid, .profile-grid {
        display: grid !important;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)) !important;
        gap: 20px !important;
        margin-top: 20px !important;
    }
    
    .detail-item, .profile-item {
        display: flex !important;
        flex-direction: column !important;
        gap: 8px !important;
        padding: 16px !important;
        background: #f8fafc !important;
        border-radius: 8px !important;
        border: 1px solid #e2e8f0 !important;
        transition: all 0.2s ease !important;
    }
    
    .detail-item:hover, .profile-item:hover {
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
    }
    
    .detail-item label, .profile-item label {
        font-weight: 600 !important;
        color: #374151 !important;
        font-size: 12px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        margin: 0 !important;
    }
    
    .detail-item span, .profile-item span {
        color: #1f2937 !important;
        font-size: 14px !important;
        font-weight: 500 !important;
    }
    
    /* Dark theme support */
    [data-theme="dark"] .detail-item, [data-theme="dark"] .profile-item {
        background: #1f2937 !important;
        border-color: #374151 !important;
    }
    
    [data-theme="dark"] .detail-item label, [data-theme="dark"] .profile-item label {
        color: #d1d5db !important;
    }
    
    [data-theme="dark"] .detail-item span, [data-theme="dark"] .profile-item span {
        color: #f3f4f6 !important;
    }
    
    /* Form Styles */
    .form-row {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 1rem !important;
        margin-bottom: 1rem !important;
    }
    
    .form-group {
        display: flex !important;
        flex-direction: column !important;
        gap: 6px !important;
    }
    
    .form-label {
        font-weight: 600 !important;
        color: #374151 !important;
        font-size: 13px !important;
        margin: 0 !important;
    }
    
    .form-control {
        padding: 8px 12px !important;
        border: 1px solid #d1d5db !important;
        border-radius: 6px !important;
        font-size: 14px !important;
        transition: all 0.2s ease !important;
    }
    
    .form-control:focus {
        outline: none !important;
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
    }
    
    .form-actions {
        display: flex !important;
        gap: 12px !important;
        margin-top: 24px !important;
        padding-top: 20px !important;
        border-top: 1px solid #e5e7eb !important;
    }
    
    .form-text {
        font-size: 12px !important;
        color: #6b7280 !important;
        margin-top: 4px !important;
    }
    
    /* Page Header Styles */
    .page-header {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        margin-bottom: 24px !important;
        padding-bottom: 16px !important;
        border-bottom: 1px solid #e5e7eb !important;
    }
    
    .page-title h1 {
        margin: 0 !important;
        font-size: 24px !important;
        font-weight: 700 !important;
        color: #111827 !important;
    }
    
    .page-title p {
        margin: 4px 0 0 0 !important;
        color: #6b7280 !important;
        font-size: 14px !important;
    }
    
    .page-actions {
        display: flex !important;
        gap: 8px !important;
    }
    
    /* Dark theme form styles */
    [data-theme="dark"] .form-label {
        color: #d1d5db !important;
    }
    
    [data-theme="dark"] .form-control {
        background: #374151 !important;
        border-color: #4b5563 !important;
        color: #f3f4f6 !important;
    }
    
    [data-theme="dark"] .form-control:focus {
        border-color: #60a5fa !important;
    }
    
    [data-theme="dark"] .form-actions {
        border-top-color: #374151 !important;
    }
    
    [data-theme="dark"] .page-header {
        border-bottom-color: #374151 !important;
    }
    
    [data-theme="dark"] .page-title h1 {
        color: #f9fafb !important;
    }
    
    [data-theme="dark"] .form-text {
        color: #9ca3af !important;
    }
    
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr !important;
        }
        
        .page-header {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 12px !important;
        }
        
        .page-actions {
            width: 100% !important;
            justify-content: flex-start !important;
        }
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
    
    /* Global Icon System - Force Filled Icons Without Outlines */
    .bi, .bi::before {
        font-weight: 900 !important;
        font-variation-settings: 'FILL' 1, 'wght' 700 !important;
        -webkit-font-smoothing: antialiased !important;
        -moz-osx-font-smoothing: grayscale !important;
        text-stroke: none !important;
        -webkit-text-stroke: none !important;
        text-shadow: none !important;
        border: none !important;
        outline: none !important;
        box-shadow: none !important;
    }
    
    /* Force filled versions for all common icons */
    .bi-calendar::before { content: "\f1ec" !important; }
    .bi-calendar-check::before { content: "\f1ed" !important; }
    .bi-plus-circle::before { content: "\f62c" !important; }
    .bi-check-circle::before { content: "\f26d" !important; }
    .bi-exclamation-triangle::before { content: "\f33e" !important; }
    .bi-eye::before { content: "\f341" !important; }
    .bi-pencil::before { content: "\f4cb" !important; }
    .bi-trash::before { content: "\f5de" !important; }
    .bi-x-circle::before { content: "\f659" !important; }
    .bi-hourglass::before { content: "\f39e" !important; }
    .bi-table::before { content: "\f4fe" !important; }
    .bi-gear::before { content: "\f3e2" !important; }
    .bi-person::before { content: "\f4da" !important; }
    .bi-house::before { content: "\f3af" !important; }
    .bi-bell::before { content: "\f1f7" !important; }
    .bi-search::before { content: "\f52a" !important; }
    .bi-filter::before { content: "\f349" !important; }
    .bi-file::before { content: "\f345" !important; }
    .bi-folder::before { content: "\f364" !important; }
    .bi-star::before { content: "\f588" !important; }
    .bi-heart::before { content: "\f391" !important; }
    .bi-bookmark::before { content: "\f1f3" !important; }
    .bi-three-dots::before { content: "\f506" !important; }
    .bi-grid::before { content: "\f386" !important; }
    .bi-list-ul::before { content: "\f47c" !important; }
    
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
    
    /* Card Shadow Styles */
    .card, .card-body, .dashboard-card, .stat-card, .widget-card, .content-card,
    .form-card, .data-card, .info-card, .summary-card, .metric-card, .panel-card,
    .task-card, .user-card, .project-card, .report-card, .notification-card,
    .activity-card, .progress-card, .status-card, .detail-card, .overview-card,
    .analytics-card, .performance-card, .attendance-card, .leave-card, .expense-card,
    .advance-card, .followup-card, .department-card, .team-card, .profile-card,
    .settings-card, .config-card, .admin-card, .management-card, .system-card,
    .gamification-card, .competition-card, .leaderboard-card, .achievement-card,
    .badge-card, .reward-card, .milestone-card, .goal-card, .target-card,
    .planner-card, .workflow-card, .schedule-card, .calendar-card, .timeline-card,
    .history-card, .log-card, .audit-card, .security-card, .backup-card,
    .maintenance-card, .update-card, .version-card, .license-card, .support-card,
    .help-card, .guide-card, .tutorial-card, .documentation-card, .faq-card,
    .contact-card, .feedback-card, .review-card, .rating-card, .comment-card,
    .message-card, .chat-card, .communication-card, .collaboration-card, .sharing-card,
    .export-card, .import-card, .backup-card, .restore-card, .sync-card,
    .integration-card, .api-card, .webhook-card, .automation-card, .workflow-card,
    .process-card, .procedure-card, .policy-card, .compliance-card, .governance-card,
    .risk-card, .security-card, .privacy-card, .gdpr-card, .legal-card,
    .terms-card, .conditions-card, .agreement-card, .contract-card, .invoice-card,
    .payment-card, .billing-card, .subscription-card, .plan-card, .pricing-card,
    .quote-card, .estimate-card, .proposal-card, .order-card, .purchase-card,
    .sale-card, .transaction-card, .receipt-card, .voucher-card, .coupon-card,
    .discount-card, .offer-card, .promotion-card, .campaign-card, .marketing-card,
    .advertising-card, .branding-card, .logo-card, .design-card, .layout-card,
    .template-card, .theme-card, .style-card, .color-card, .font-card,
    .image-card, .photo-card, .gallery-card, .media-card, .video-card,
    .audio-card, .file-card, .document-card, .pdf-card, .excel-card,
    .word-card, .powerpoint-card, .presentation-card, .spreadsheet-card, .database-card,
    .table-card, .list-card, .grid-card, .chart-card, .graph-card,
    .diagram-card, .flowchart-card, .timeline-card, .gantt-card, .kanban-card,
    .scrum-card, .agile-card, .sprint-card, .backlog-card, .epic-card,
    .story-card, .feature-card, .bug-card, .issue-card, .ticket-card,
    .request-card, .inquiry-card, .complaint-card, .suggestion-card, .idea-card,
    .innovation-card, .improvement-card, .enhancement-card, .upgrade-card, .migration-card,
    .deployment-card, .release-card, .version-card, .patch-card, .hotfix-card,
    .maintenance-card, .downtime-card, .outage-card, .incident-card, .alert-card,
    .warning-card, .error-card, .exception-card, .debug-card, .trace-card,
    .log-card, .monitor-card, .health-card, .status-card, .uptime-card,
    .performance-card, .speed-card, .optimization-card, .efficiency-card, .productivity-card,
    .quality-card, .standard-card, .benchmark-card, .metric-card, .kpi-card,
    .roi-card, .profit-card, .revenue-card, .cost-card, .budget-card,
    .forecast-card, .prediction-card, .trend-card, .analysis-card, .insight-card,
    .intelligence-card, .ai-card, .ml-card, .algorithm-card, .model-card,
    .training-card, .learning-card, .education-card, .course-card, .lesson-card,
    .tutorial-card, .workshop-card, .seminar-card, .webinar-card, .conference-card,
    .event-card, .meeting-card, .appointment-card, .booking-card, .reservation-card,
    .schedule-card, .calendar-card, .reminder-card, .notification-card, .alert-card,
    .message-card, .email-card, .sms-card, .push-card, .popup-card,
    .modal-card, .dialog-card, .form-card, .input-card, .field-card,
    .button-card, .action-card, .control-card, .widget-card, .component-card,
    .element-card, .block-card, .section-card, .container-card, .wrapper-card,
    .box-card, .panel-card, .tile-card, .brick-card, .module-card,
    .plugin-card, .addon-card, .extension-card, .app-card, .application-card,
    .software-card, .program-card, .tool-card, .utility-card, .service-card,
    .platform-card, .framework-card, .library-card, .package-card, .bundle-card,
    .kit-card, .suite-card, .collection-card, .set-card, .group-card,
    .category-card, .type-card, .kind-card, .class-card, .level-card,
    .grade-card, .rank-card, .tier-card, .stage-card, .phase-card,
    .step-card, .process-card, .workflow-card, .pipeline-card, .funnel-card,
    .journey-card, .path-card, .route-card, .navigation-card, .menu-card,
    .sidebar-card, .header-card, .footer-card, .banner-card, .hero-card,
    .feature-card, .benefit-card, .advantage-card, .value-card, .proposition-card,
    .offer-card, .deal-card, .package-card, .bundle-card, .combo-card,
    .special-card, .premium-card, .pro-card, .enterprise-card, .business-card,
    .corporate-card, .organization-card, .company-card, .brand-card, .product-card,
    .service-card, .solution-card, .platform-card, .system-card, .network-card,
    .infrastructure-card, .architecture-card, .design-card, .blueprint-card, .plan-card,
    .strategy-card, .roadmap-card, .vision-card, .mission-card, .goal-card,
    .objective-card, .target-card, .milestone-card, .achievement-card, .success-card,
    .win-card, .victory-card, .triumph-card, .accomplishment-card, .completion-card,
    .finish-card, .end-card, .conclusion-card, .result-card, .outcome-card,
    .output-card, .product-card, .deliverable-card, .artifact-card, .asset-card,
    .resource-card, .material-card, .content-card, .data-card, .information-card,
    .knowledge-card, .wisdom-card, .insight-card, .understanding-card, .comprehension-card,
    .awareness-card, .consciousness-card, .mindfulness-card, .attention-card, .focus-card,
    .concentration-card, .dedication-card, .commitment-card, .loyalty-card, .trust-card,
    .confidence-card, .faith-card, .belief-card, .hope-card, .optimism-card,
    .positivity-card, .happiness-card, .joy-card, .satisfaction-card, .contentment-card,
    .fulfillment-card, .achievement-card, .success-card, .progress-card, .growth-card,
    .development-card, .improvement-card, .enhancement-card, .upgrade-card, .evolution-card,
    .transformation-card, .change-card, .innovation-card, .creativity-card, .imagination-card,
    .inspiration-card, .motivation-card, .encouragement-card, .support-card, .help-card,
    .assistance-card, .guidance-card, .direction-card, .instruction-card, .teaching-card,
    .learning-card, .education-card, .training-card, .development-card, .skill-card,
    .ability-card, .capability-card, .competency-card, .expertise-card, .mastery-card,
    .proficiency-card, .excellence-card, .quality-card, .standard-card, .benchmark-card,
    .reference-card, .example-card, .sample-card, .template-card, .model-card,
    .prototype-card, .demo-card, .preview-card, .showcase-card, .exhibition-card,
    .display-card, .presentation-card, .show-card, .performance-card, .demonstration-card,
    .illustration-card, .explanation-card, .description-card, .definition-card, .meaning-card,
    .purpose-card, .function-card, .role-card, .responsibility-card, .duty-card,
    .task-card, .job-card, .work-card, .activity-card, .action-card,
    .operation-card, .procedure-card, .process-card, .method-card, .technique-card,
    .approach-card, .strategy-card, .tactic-card, .plan-card, .scheme-card,
    .design-card, .layout-card, .structure-card, .framework-card, .architecture-card,
    .foundation-card, .base-card, .core-card, .essence-card, .heart-card,
    .center-card, .middle-card, .hub-card, .focal-card, .main-card,
    .primary-card, .principal-card, .key-card, .important-card, .critical-card,
    .essential-card, .vital-card, .crucial-card, .significant-card, .major-card,
    .minor-card, .secondary-card, .tertiary-card, .auxiliary-card, .supplementary-card,
    .additional-card, .extra-card, .bonus-card, .premium-card, .special-card,
    .unique-card, .exclusive-card, .limited-card, .rare-card, .valuable-card,
    .precious-card, .treasured-card, .cherished-card, .beloved-card, .favorite-card,
    .preferred-card, .chosen-card, .selected-card, .picked-card, .opted-card,
    .decided-card, .determined-card, .resolved-card, .settled-card, .fixed-card,
    .established-card, .confirmed-card, .verified-card, .validated-card, .approved-card,
    .accepted-card, .agreed-card, .consented-card, .permitted-card, .allowed-card,
    .authorized-card, .licensed-card, .certified-card, .qualified-card, .eligible-card,
    .suitable-card, .appropriate-card, .fitting-card, .proper-card, .correct-card,
    .right-card, .accurate-card, .precise-card, .exact-card, .perfect-card,
    .ideal-card, .optimal-card, .best-card, .top-card, .supreme-card,
    .ultimate-card, .final-card, .last-card, .end-card, .conclusion-card {
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06) !important;
        border: 1px solid rgba(0, 0, 0, 0.05) !important;
        transition: box-shadow 0.2s ease, transform 0.2s ease !important;
    }
    
    .card:hover, .card-body:hover, .dashboard-card:hover, .stat-card:hover, .widget-card:hover, .content-card:hover,
    .form-card:hover, .data-card:hover, .info-card:hover, .summary-card:hover, .metric-card:hover, .panel-card:hover,
    .task-card:hover, .user-card:hover, .project-card:hover, .report-card:hover, .notification-card:hover,
    .activity-card:hover, .progress-card:hover, .status-card:hover, .detail-card:hover, .overview-card:hover,
    .analytics-card:hover, .performance-card:hover, .attendance-card:hover, .leave-card:hover, .expense-card:hover,
    .advance-card:hover, .followup-card:hover, .department-card:hover, .team-card:hover, .profile-card:hover,
    .settings-card:hover, .config-card:hover, .admin-card:hover, .management-card:hover, .system-card:hover {
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 2px 4px rgba(0, 0, 0, 0.06) !important;
        transform: translateY(-1px) !important;
    }
    </style>
    <?php if (isset($userPrefs['theme']) && $userPrefs['theme'] === 'dark'): ?>
    <link id="dark-theme-css" href="/ergon/assets/css/dark-theme.css" rel="stylesheet">
    <style>
    /* Dark theme card shadows */
    [data-theme="dark"] .card, [data-theme="dark"] .card-body, [data-theme="dark"] .dashboard-card, 
    [data-theme="dark"] .stat-card, [data-theme="dark"] .widget-card, [data-theme="dark"] .content-card,
    [data-theme="dark"] .form-card, [data-theme="dark"] .data-card, [data-theme="dark"] .info-card, 
    [data-theme="dark"] .summary-card, [data-theme="dark"] .metric-card, [data-theme="dark"] .panel-card,
    [data-theme="dark"] .task-card, [data-theme="dark"] .user-card, [data-theme="dark"] .project-card, 
    [data-theme="dark"] .report-card, [data-theme="dark"] .notification-card, [data-theme="dark"] .activity-card,
    [data-theme="dark"] .progress-card, [data-theme="dark"] .status-card, [data-theme="dark"] .detail-card, 
    [data-theme="dark"] .overview-card, [data-theme="dark"] .analytics-card, [data-theme="dark"] .performance-card,
    [data-theme="dark"] .attendance-card, [data-theme="dark"] .leave-card, [data-theme="dark"] .expense-card,
    [data-theme="dark"] .advance-card, [data-theme="dark"] .followup-card, [data-theme="dark"] .department-card, 
    [data-theme="dark"] .team-card, [data-theme="dark"] .profile-card, [data-theme="dark"] .settings-card, 
    [data-theme="dark"] .config-card, [data-theme="dark"] .admin-card, [data-theme="dark"] .management-card, 
    [data-theme="dark"] .system-card {
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3), 0 1px 2px rgba(0, 0, 0, 0.2) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
    }
    
    [data-theme="dark"] .card:hover, [data-theme="dark"] .card-body:hover, [data-theme="dark"] .dashboard-card:hover, 
    [data-theme="dark"] .stat-card:hover, [data-theme="dark"] .widget-card:hover, [data-theme="dark"] .content-card:hover,
    [data-theme="dark"] .form-card:hover, [data-theme="dark"] .data-card:hover, [data-theme="dark"] .info-card:hover, 
    [data-theme="dark"] .summary-card:hover, [data-theme="dark"] .metric-card:hover, [data-theme="dark"] .panel-card:hover,
    [data-theme="dark"] .task-card:hover, [data-theme="dark"] .user-card:hover, [data-theme="dark"] .project-card:hover, 
    [data-theme="dark"] .report-card:hover, [data-theme="dark"] .notification-card:hover, [data-theme="dark"] .activity-card:hover,
    [data-theme="dark"] .progress-card:hover, [data-theme="dark"] .status-card:hover, [data-theme="dark"] .detail-card:hover, 
    [data-theme="dark"] .overview-card:hover, [data-theme="dark"] .analytics-card:hover, [data-theme="dark"] .performance-card:hover,
    [data-theme="dark"] .attendance-card:hover, [data-theme="dark"] .leave-card:hover, [data-theme="dark"] .expense-card:hover,
    [data-theme="dark"] .advance-card:hover, [data-theme="dark"] .followup-card:hover, [data-theme="dark"] .department-card:hover, 
    [data-theme="dark"] .team-card:hover, [data-theme="dark"] .profile-card:hover, [data-theme="dark"] .settings-card:hover, 
    [data-theme="dark"] .config-card:hover, [data-theme="dark"] .admin-card:hover, [data-theme="dark"] .management-card:hover, 
    [data-theme="dark"] .system-card:hover {
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.4), 0 2px 4px rgba(0, 0, 0, 0.3) !important;
        transform: translateY(-1px) !important;
    }
    </style>
    <?php endif; ?>
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
                    <a href="/ergon/profile/change-password" class="profile-menu-item">
                        <span class="menu-icon"><i class="bi bi-lock-fill"></i></span>
                        Change Password
                    </a>
                    <a href="/ergon/profile/preferences" class="profile-menu-item">
                        <span class="menu-icon"><i class="bi bi-gear-fill"></i></span>
                        Preferences
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
                            <a href="/ergon/daily-workflow/morning-planner" class="nav-dropdown-item <?= ($active_page ?? '') === 'planner' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">🌅</span>
                                Planner
                            </a>
                            <a href="/ergon/daily-workflow/evening-update" class="nav-dropdown-item <?= ($active_page ?? '') === 'evening-update' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">🌆</span>
                                Evening Update
                            </a>
                            <a href="/ergon/followups" class="nav-dropdown-item <?= ($active_page ?? '') === 'followups' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">📞</span>
                                Follow-ups
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
                            <a href="/ergon/planner" class="nav-dropdown-item <?= ($active_page ?? '') === 'planner' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">📅</span>
                                Daily Planner
                            </a>
                            <a href="/ergon/evening-update" class="nav-dropdown-item <?= ($active_page ?? '') === 'evening_update' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">🌅</span>
                                Evening Update
                            </a>
                            <a href="/ergon/followups" class="nav-dropdown-item <?= ($active_page ?? '') === 'followups' ? 'nav-dropdown-item--active' : '' ?>">
                                <span class="nav-icon">📞</span>
                                Follow-ups
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
    
    <div class="notification-dropdown" id="notificationDropdown" style="display: none; position: fixed; top: 60px; right: 20px; width: 350px; background: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 10000;">
        <div class="notification-header" style="padding: 16px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 16px; font-weight: 600;">Notifications</h3>
            <a href="#" class="view-all-link" style="color: #3b82f6; text-decoration: none; font-size: 14px;" onclick="navigateToNotifications(event)">View All</a>
        </div>
        <div class="notification-list" id="notificationList" style="max-height: 300px; overflow-y: auto;">
            <div class="notification-item" style="padding: 12px 16px; border-bottom: 1px solid #f3f4f6;">
                <div style="font-weight: 500; font-size: 14px; margin-bottom: 4px;">New Task Assigned</div>
                <div style="font-size: 12px; color: #6b7280;">You have been assigned a new task</div>
                <div style="font-size: 11px; color: #9ca3af; margin-top: 4px;">2 minutes ago</div>
            </div>
            <div class="notification-item" style="padding: 12px 16px; border-bottom: 1px solid #f3f4f6;">
                <div style="font-weight: 500; font-size: 14px; margin-bottom: 4px;">Leave Approved</div>
                <div style="font-size: 12px; color: #6b7280;">Your leave request has been approved</div>
                <div style="font-size: 11px; color: #9ca3af; margin-top: 4px;">1 hour ago</div>
            </div>
        </div>
    </div>

    <main class="main-content">
            <?php if (isset($title) && in_array($title, ['Executive Dashboard', 'Team Competition Dashboard', 'Follow-ups Management', 'System Settings', 'IT Activity Reports']) && ($active_page ?? '') !== 'notifications'): ?>
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
    
    function toggleNotifications(event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        var dropdown = document.getElementById('notificationDropdown');
        if (dropdown) {
            var isVisible = dropdown.style.display === 'block';
            dropdown.style.display = isVisible ? 'none' : 'block';
            
            // Close other dropdowns
            document.querySelectorAll('.nav-dropdown-menu').forEach(function(menu) {
                menu.classList.remove('show');
            });
            var profileMenu = document.getElementById('profileMenu');
            if (profileMenu) profileMenu.classList.remove('show');
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
        
        // Navigate to notifications page
        window.location.href = '/ergon/notifications';
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
    

    
    window.onpageshow = function(event) {
        if (event.persisted) {
            window.location.replace('/ergon/login');
        }
    };
    
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
    <script src="/ergon/assets/js/auth-guard.min.js?v=<?= time() ?>" defer></script>
</body>
</html>