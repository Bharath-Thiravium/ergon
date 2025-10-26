<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - ERGON</title>
    <link rel="stylesheet" href="/ergon_clean/public/assets/css/ergon.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar__header">
                <a href="#" class="sidebar__brand">
                    <i class="fas fa-user"></i>
                    ERGON
                </a>
                <h3>User Portal</h3>
            </div>
            
            <nav class="sidebar__menu">
                <a href="/ergon_clean/public/user/dashboard" class="sidebar__link sidebar__link--active">
                    <i class="sidebar__icon fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
                
                <div class="sidebar__divider">Attendance</div>
                <a href="/ergon_clean/public/attendance/clock" class="sidebar__link">
                    <i class="sidebar__icon fas fa-clock"></i>
                    Clock In/Out
                </a>
                <a href="/ergon_clean/public/attendance" class="sidebar__link">
                    <i class="sidebar__icon fas fa-calendar-check"></i>
                    My Attendance
                </a>
                
                <div class="sidebar__divider">Tasks</div>
                <a href="/ergon_clean/public/tasks" class="sidebar__link">
                    <i class="sidebar__icon fas fa-tasks"></i>
                    My Tasks
                </a>
                <a href="/ergon_clean/public/tasks/calendar" class="sidebar__link">
                    <i class="sidebar__icon fas fa-calendar"></i>
                    Task Calendar
                </a>
                
                <div class="sidebar__divider">Requests</div>
                <a href="/ergon_clean/public/leaves/create" class="sidebar__link">
                    <i class="sidebar__icon fas fa-calendar-alt"></i>
                    Leave Request
                </a>
                <a href="/ergon_clean/public/expenses/create" class="sidebar__link">
                    <i class="sidebar__icon fas fa-receipt"></i>
                    Expense Claim
                </a>
                <a href="/ergon_clean/public/advances/create" class="sidebar__link">
                    <i class="sidebar__icon fas fa-money-bill"></i>
                    Advance Request
                </a>
            </nav>
            
            <div class="sidebar__controls">
                <button class="sidebar__control-btn" title="Notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">2</span>
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
                        <a href="/ergon_clean/public/profile/preferences" class="profile-menu-item">
                            <i class="menu-icon fas fa-cog"></i>
                            Preferences
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
                <button class="btn btn--primary" onclick="clockIn()">
                    <i class="fas fa-play"></i>
                    Clock In
                </button>
                <button class="btn btn--secondary" onclick="clockOut()">
                    <i class="fas fa-stop"></i>
                    Clock Out
                </button>
            </div>
            
            <!-- KPI Dashboard Grid -->
            <div class="dashboard-grid">
                <div class="kpi-card">
                    <div class="kpi-card__header">
                        <i class="kpi-card__icon fas fa-user-check text-success"></i>
                        <span class="kpi-card__trend kpi-card__trend--up">Active</span>
                    </div>
                    <div class="kpi-card__value"><?= $stats['today_status'] ?? 'Not Clocked In' ?></div>
                    <div class="kpi-card__label">Today's Status</div>
                    <div class="kpi-card__status kpi-card__status--active">Current</div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-card__header">
                        <i class="kpi-card__icon fas fa-tasks text-primary"></i>
                        <span class="kpi-card__trend kpi-card__trend--up">+2</span>
                    </div>
                    <div class="kpi-card__value"><?= $stats['active_tasks'] ?? 0 ?></div>
                    <div class="kpi-card__label">Active Tasks</div>
                    <div class="kpi-card__status kpi-card__status--active">In Progress</div>
                </div>
                
                <div class="kpi-card kpi-card--warning">
                    <div class="kpi-card__header">
                        <i class="kpi-card__icon fas fa-clock text-warning"></i>
                        <span class="kpi-card__trend kpi-card__trend--down">0</span>
                    </div>
                    <div class="kpi-card__value">0</div>
                    <div class="kpi-card__label">Pending Requests</div>
                    <div class="kpi-card__status kpi-card__status--pending">None</div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-card__header">
                        <i class="kpi-card__icon fas fa-calendar-check text-info"></i>
                        <span class="kpi-card__trend kpi-card__trend--up">95%</span>
                    </div>
                    <div class="kpi-card__value">22</div>
                    <div class="kpi-card__label">Days This Month</div>
                    <div class="kpi-card__status kpi-card__status--active">Present</div>
                </div>
            </div>
            
            <!-- Content Cards -->
            <div class="dashboard-grid">
                <!-- Performance Chart -->
                <div class="card">
                    <div class="card__header">
                        <h3 class="card__title">
                            <i class="fas fa-chart-line"></i>
                            My Performance
                        </h3>
                    </div>
                    <div class="card__body">
                        <canvas id="userChart" style="height: 300px;"></canvas>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card">
                    <div class="card__header">
                        <h3 class="card__title">
                            <i class="fas fa-bolt"></i>
                            Quick Actions
                        </h3>
                    </div>
                    <div class="card__body">
                        <div class="card__body">
                            <button class="btn btn--primary" onclick="clockIn()">
                                <i class="fas fa-play"></i>
                                Clock In
                            </button>
                            <button class="btn btn--secondary" onclick="clockOut()">
                                <i class="fas fa-stop"></i>
                                Clock Out
                            </button>
                            <a href="/ergon_clean/public/leaves/create" class="btn btn--secondary">
                                <i class="fas fa-calendar-alt"></i>
                                Request Leave
                            </a>
                            <a href="/ergon_clean/public/expenses/create" class="btn btn--secondary">
                                <i class="fas fa-receipt"></i>
                                Submit Expense
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Tasks -->
            <div class="card">
                <div class="card__header">
                    <h3 class="card__title">
                        <i class="fas fa-tasks"></i>
                        Recent Tasks
                    </h3>
                </div>
                <div class="card__body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Task</th>
                                    <th>Priority</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Complete project documentation</td>
                                    <td><span class="alert alert--warning" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">High</span></td>
                                    <td>Today</td>
                                    <td><span class="alert alert--info" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">In Progress</span></td>
                                    <td>75%</td>
                                </tr>
                                <tr>
                                    <td>Review client feedback</td>
                                    <td><span class="alert alert--success" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">Medium</span></td>
                                    <td>Tomorrow</td>
                                    <td><span class="alert alert--warning" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">Pending</span></td>
                                    <td>0%</td>
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
        // Performance Chart
        const ctx = document.getElementById('userChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
                datasets: [{
                    label: 'Tasks Completed',
                    data: [2, 4, 3, 5, 2],
                    borderColor: '#1e40af',
                    backgroundColor: 'rgba(30, 64, 175, 0.1)',
                    tension: 0.4,
                    fill: true
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
        
        function clockIn() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    fetch('/ergon_clean/public/attendance/clock', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `type=in&latitude=${position.coords.latitude}&longitude=${position.coords.longitude}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Clocked in successfully!');
                            location.reload();
                        } else {
                            alert(data.error);
                        }
                    });
                });
            }
        }
        
        function clockOut() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    fetch('/ergon_clean/public/attendance/clock', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `type=out&latitude=${position.coords.latitude}&longitude=${position.coords.longitude}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Clocked out successfully!');
                            location.reload();
                        } else {
                            alert(data.error);
                        }
                    });
                });
            }
        }
    </script>
</body>
</html>