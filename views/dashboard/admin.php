<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ERGON</title>
    <link rel="stylesheet" href="/ergon_clean/public/assets/css/ergon.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar__header">
                <a href="#" class="sidebar__brand">
                    <i class="fas fa-user-shield"></i>
                    ERGON
                </a>
                <h3>Admin Portal</h3>
            </div>
            
            <nav class="sidebar__menu">
                <a href="/ergon_clean/public/admin/dashboard" class="sidebar__link sidebar__link--active">
                    <i class="sidebar__icon fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
                
                <div class="sidebar__divider">Management</div>
                <a href="/ergon_clean/public/tasks" class="sidebar__link">
                    <i class="sidebar__icon fas fa-tasks"></i>
                    Task Management
                </a>
                <a href="/ergon_clean/public/attendance" class="sidebar__link">
                    <i class="sidebar__icon fas fa-clock"></i>
                    Attendance
                </a>
                <a href="/ergon_clean/public/users" class="sidebar__link">
                    <i class="sidebar__icon fas fa-users"></i>
                    Team Members
                </a>
                
                <div class="sidebar__divider">Approvals</div>
                <a href="/ergon_clean/public/leaves" class="sidebar__link">
                    <i class="sidebar__icon fas fa-calendar-alt"></i>
                    Leave Requests
                </a>
                <a href="/ergon_clean/public/expenses" class="sidebar__link">
                    <i class="sidebar__icon fas fa-receipt"></i>
                    Expense Claims
                </a>
                <a href="/ergon_clean/public/advances" class="sidebar__link">
                    <i class="sidebar__icon fas fa-money-bill"></i>
                    Advance Requests
                </a>
                
                <div class="sidebar__divider">Reports</div>
                <a href="/ergon_clean/public/reports" class="sidebar__link">
                    <i class="sidebar__icon fas fa-chart-bar"></i>
                    Reports
                </a>
                <a href="/ergon_clean/public/analytics" class="sidebar__link">
                    <i class="sidebar__icon fas fa-chart-line"></i>
                    Analytics
                </a>
            </nav>
            
            <div class="sidebar__controls">
                <button class="sidebar__control-btn" title="Notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">5</span>
                </button>
                <button class="sidebar__control-btn" title="Settings">
                    <i class="fas fa-cog"></i>
                </button>
                
                <div class="sidebar__profile-dropdown">
                    <button class="sidebar__profile-btn">
                        <div class="profile-avatar"><?= strtoupper(substr($user_name, 0, 1)) ?></div>
                        <div class="profile-info">
                            <span class="profile-name"><?= htmlspecialchars($user_name) ?></span>
                            <span class="profile-role"><?= htmlspecialchars($role) ?></span>
                        </div>
                        <i class="dropdown-arrow fas fa-chevron-up"></i>
                    </button>
                    
                    <div class="profile-menu">
                        <a href="/ergon_clean/public/profile" class="profile-menu-item">
                            <i class="menu-icon fas fa-user"></i>
                            Profile
                        </a>
                        <a href="/ergon_clean/public/settings" class="profile-menu-item">
                            <i class="menu-icon fas fa-cog"></i>
                            Settings
                        </a>
                        <div class="profile-menu-divider"></div>
                        <a href="/ergon_clean/public/logout" class="profile-menu-item profile-menu-item--danger">
                            <i class="menu-icon fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">

            
            <!-- Header Actions -->
            <div class="header-actions">
                <button class="btn btn--primary">
                    <i class="fas fa-plus"></i>
                    Create Task
                </button>
                <button class="btn btn--secondary">
                    <i class="fas fa-download"></i>
                    Export Report
                </button>
            </div>
            
            <!-- KPI Dashboard Grid -->
            <div class="dashboard-grid">
                <div class="kpi-card">
                    <div class="kpi-card__header">
                        <i class="kpi-card__icon fas fa-tasks text-primary"></i>
                        <span class="kpi-card__trend kpi-card__trend--up">+3</span>
                    </div>
                    <div class="kpi-card__value"><?= $stats['my_tasks'] ?? 0 ?></div>
                    <div class="kpi-card__label">My Tasks</div>
                    <div class="kpi-card__status kpi-card__status--active">Active</div>
                </div>
                
                <div class="kpi-card kpi-card--warning">
                    <div class="kpi-card__header">
                        <i class="kpi-card__icon fas fa-clock text-warning"></i>
                        <span class="kpi-card__trend kpi-card__trend--down">-2</span>
                    </div>
                    <div class="kpi-card__value">8</div>
                    <div class="kpi-card__label">Pending Approvals</div>
                    <div class="kpi-card__status kpi-card__status--pending">Review Required</div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-card__header">
                        <i class="kpi-card__icon fas fa-users text-info"></i>
                        <span class="kpi-card__trend kpi-card__trend--up">+1</span>
                    </div>
                    <div class="kpi-card__value"><?= $stats['team_members'] ?? 0 ?></div>
                    <div class="kpi-card__label">Team Members</div>
                    <div class="kpi-card__status kpi-card__status--active">Active</div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-card__header">
                        <i class="kpi-card__icon fas fa-chart-line text-success"></i>
                        <span class="kpi-card__trend kpi-card__trend--up">+12%</span>
                    </div>
                    <div class="kpi-card__value">94%</div>
                    <div class="kpi-card__label">Team Performance</div>
                    <div class="kpi-card__status kpi-card__status--active">Excellent</div>
                </div>
            </div>
            
            <!-- Content Cards -->
            <div class="dashboard-grid">
                <!-- Team Performance Chart -->
                <div class="card">
                    <div class="card__header">
                        <h3 class="card__title">
                            <i class="fas fa-chart-bar"></i>
                            Team Performance
                        </h3>
                    </div>
                    <div class="card__body">
                        <canvas id="teamChart" style="height: 300px;"></canvas>
                    </div>
                </div>
                
                <!-- Recent Activities -->
                <div class="card">
                    <div class="card__header">
                        <h3 class="card__title">
                            <i class="fas fa-bell"></i>
                            Recent Activities
                        </h3>
                    </div>
                    <div class="card__body card__body--scrollable">
                        <div style="margin-top: 1rem;">
                            <div style="padding: 0.75rem 0; border-bottom: 1px solid var(--border-color);">
                                <div style="font-weight: 500; margin-bottom: 0.25rem;">New leave request submitted</div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary);">John Doe - 1 hour ago</div>
                            </div>
                            <div style="padding: 0.75rem 0; border-bottom: 1px solid var(--border-color);">
                                <div style="font-weight: 500; margin-bottom: 0.25rem;">Task completed</div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary);">Jane Smith - 2 hours ago</div>
                            </div>
                            <div style="padding: 0.75rem 0; border-bottom: 1px solid var(--border-color);">
                                <div style="font-weight: 500; margin-bottom: 0.25rem;">Expense claim submitted</div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary);">Mike Johnson - 3 hours ago</div>
                            </div>
                            <div style="padding: 0.75rem 0;">
                                <div style="font-weight: 500; margin-bottom: 0.25rem;">New team member added</div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary);">Sarah Wilson - 4 hours ago</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pending Approvals Table -->
            <div class="card">
                <div class="card__header">
                    <h3 class="card__title">
                        <i class="fas fa-clock"></i>
                        Pending Approvals
                    </h3>
                </div>
                <div class="card__body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Employee</th>
                                    <th>Request</th>
                                    <th>Date</th>
                                    <th>Priority</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><i class="fas fa-calendar-alt text-warning"></i> Leave</td>
                                    <td>John Doe</td>
                                    <td>Annual Leave - 3 days</td>
                                    <td>Dec 15-17, 2024</td>
                                    <td><span class="alert alert--warning" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">High</span></td>
                                    <td>
                                        <button class="btn btn--primary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">Approve</button>
                                        <button class="btn btn--secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">Reject</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><i class="fas fa-receipt text-info"></i> Expense</td>
                                    <td>Jane Smith</td>
                                    <td>Travel Expense - $250</td>
                                    <td>Dec 10, 2024</td>
                                    <td><span class="alert alert--success" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">Medium</span></td>
                                    <td>
                                        <button class="btn btn--primary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">Approve</button>
                                        <button class="btn btn--secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">Reject</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Team Performance Chart
        const ctx = document.getElementById('teamChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Tasks', 'Attendance', 'Leaves', 'Expenses'],
                datasets: [{
                    label: 'Pending Items',
                    data: [5, 2, 3, 1],
                    backgroundColor: ['#1e40af', '#059669', '#d97706', '#dc2626']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    }
                }
            }
        });
        
        // Mobile menu toggle
        document.querySelector('.mobile-menu-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('sidebar--open');
        });
        
        // Profile dropdown toggle
        document.querySelector('.sidebar__profile-btn').addEventListener('click', function() {
            const menu = document.querySelector('.profile-menu');
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        });
        
        // Close profile menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.sidebar__profile-dropdown')) {
                document.querySelector('.profile-menu').style.display = 'none';
            }
        });
    </script>
</body>
</html>