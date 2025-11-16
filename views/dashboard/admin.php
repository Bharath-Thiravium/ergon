<?php
$title = 'Admin Dashboard';
$active_page = 'dashboard';

ob_start();
?>

            
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
                        <div class="chart-placeholder">
                            <div class="chart-placeholder-content">
                                <div class="chart-placeholder-icon">📊</div>
                                <div>Team Performance Chart</div>
                                <div class="chart-placeholder-text">Chart will be displayed here</div>
                            </div>
                        </div>
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
                        <div class="m-md">
                            <div class="activity-item">
                                <div class="activity-title">New leave request submitted</div>
                                <div class="activity-meta">John Doe - 1 hour ago</div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-title">Task completed</div>
                                <div class="activity-meta">Jane Smith - 2 hours ago</div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-title">Expense claim submitted</div>
                                <div class="activity-meta">Mike Johnson - 3 hours ago</div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-title">New team member added</div>
                                <div class="activity-meta">Sarah Wilson - 4 hours ago</div>
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
                                    <td><span class="alert alert--warning alert--badge">High</span></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="/ergon/leaves/view/1" class="btn btn--sm btn--primary" title="View Details">
                                                <span>👁️</span> View
                                            </a>
                                            <button onclick="deleteRecord('leaves', 1, 'Leave Request')" class="btn btn--sm btn--danger" title="Delete Request">
                                                <span>🗑️</span> Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><i class="fas fa-receipt text-info"></i> Expense</td>
                                    <td>Jane Smith</td>
                                    <td>Travel Expense - $250</td>
                                    <td>Dec 10, 2024</td>
                                    <td><span class="alert alert--success alert--badge">Medium</span></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="/ergon/expenses/view/1" class="btn btn--sm btn--primary" title="View Details">
                                                <span>👁️</span> View
                                            </a>
                                            <button onclick="deleteRecord('expenses', 1, 'Expense Claim')" class="btn btn--sm btn--danger" title="Delete Claim">
                                                <span>🗑️</span> Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>


<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
