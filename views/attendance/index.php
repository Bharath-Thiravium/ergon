<?php
$title = 'Attendance';
$active_page = 'attendance';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>📍</span> Attendance Management</h1>
        <p>Track employee attendance and working hours</p>
    </div>
    <div class="page-actions">
        <select id="filterSelect" onchange="filterAttendance(this.value)" class="form-input">
            <option value="today" <?= ($current_filter ?? 'today') === 'today' ? 'selected' : '' ?>>Today</option>
            <option value="week" <?= ($current_filter ?? '') === 'week' ? 'selected' : '' ?>>One Week</option>
            <option value="two_weeks" <?= ($current_filter ?? '') === 'two_weeks' ? 'selected' : '' ?>>Two Weeks</option>
            <option value="month" <?= ($current_filter ?? '') === 'month' ? 'selected' : '' ?>>One Month</option>
        </select>
        <a href="/ergon/attendance/clock" class="btn btn--primary">
            <span>🕰️</span> Clock In/Out
        </a>
    </div>
</div>

<div class="dashboard-grid">
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📍</div>
            <div class="kpi-card__trend">↗ +5%</div>
        </div>
        <div class="kpi-card__value"><?= count($attendance ?? []) ?></div>
        <div class="kpi-card__label">Total Records</div>
        <div class="kpi-card__status">Tracked</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✅</div>
            <div class="kpi-card__trend">Present</div>
        </div>
        <div class="kpi-card__value"><?= $stats['present_days'] ?? 0 ?></div>
        <div class="kpi-card__label">Days Present</div>
        <div class="kpi-card__status">Active</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🕰️</div>
            <div class="kpi-card__trend">Total</div>
        </div>
        <div class="kpi-card__value"><?= ($stats['total_hours'] ?? 0) ?>h <?= (int)round($stats['total_minutes'] ?? 0) ?>m</div>
        <div class="kpi-card__label">Working Hours</div>
        <div class="kpi-card__status">Logged</div>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">Attendance Records</h2>
    </div>
    <div class="card__body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 35%;">Employee</th>
                        <th>Date & Status</th>
                        <th>Working Hours</th>
                        <th>Check Times</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($attendance ?? [])): ?>
                    <tr>
                        <td colspan="5" class="text-center">
                            <div class="empty-state">
                                <div class="empty-icon">📍</div>
                                <h3>No Attendance Records</h3>
                                <p>No attendance records found.</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($attendance as $record): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($record['user_name'] ?? 'Unknown') ?></strong>
                                <?php if (isset($record['employee_id'])): ?>
                                    <br><small class="text-muted">ID: <?= htmlspecialchars($record['employee_id']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="assignment-info">
                                    <div class="assigned-user"><?= $record['check_in'] ? date('M d, Y', strtotime($record['check_in'])) : '-' ?></div>
                                    <div class="priority-badge">
                                        <?php 
                                        $statusClass = match($record['status'] ?? 'present') {
                                            'present' => 'success',
                                            'late' => 'warning',
                                            'absent' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge badge--<?= $statusClass ?>"><?= ucfirst($record['status'] ?? 'present') ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php 
                                $totalMins = 0;
                                $hrs = 0;
                                $mins = 0;
                                if ($record['check_out']) {
                                    $totalMins = (strtotime($record['check_out']) - strtotime($record['check_in'])) / 60;
                                    $hrs = (int)floor($totalMins / 60);
                                    $mins = (int)round((int)$totalMins % 60);
                                }
                                ?>
                                <div class="progress-container">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?= min(100, ($totalMins / 480) * 100) ?>%; background: <?= $totalMins >= 480 ? '#10b981' : ($totalMins >= 360 ? '#3b82f6' : ($totalMins >= 240 ? '#f59e0b' : '#e2e8f0')) ?>"></div>
                                    </div>
                                    <div class="progress-info">
                                        <span class="progress-percentage"><?= $record['check_out'] ? $hrs . 'h ' . $mins . 'm' : '-' ?></span>
                                        <span class="progress-status"><?= $record['check_out'] ? sprintf('%.1f', $totalMins/60) . ' hours' : 'Active' ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="cell-meta">
                                    <div class="cell-primary">In: <?= $record['check_in'] ? date('H:i', strtotime($record['check_in'])) : '-' ?></div>
                                    <div class="cell-secondary">Out: <?= $record['check_out'] ? date('H:i', strtotime($record['check_out'])) : 'Active' ?></div>
                                </div>
                            </td>
                            <td>
                                <div class="ab-container">
                                    <?php if (!$record['check_out'] && ($record['user_id'] ?? 0) == ($_SESSION['user_id'] ?? 0)): ?>
                                        <button class="ab-btn ab-btn--warning" onclick="checkOut(<?= $record['id'] ?? 0 ?>)" title="Check Out">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <circle cx="12" cy="12" r="10"/>
                                                <polyline points="12,6 12,12 16,14"/>
                                            </svg>
                                        </button>
                                    <?php endif; ?>
                                    <?php if (in_array($_SESSION['role'] ?? '', ['admin', 'owner'])): ?>
                                        <a class="ab-btn ab-btn--edit" data-action="edit" data-module="attendance" data-id="<?= $record['id'] ?? 0 ?>" title="Edit Record">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                                <path d="M15 5l4 4"/>
                                            </svg>
                                        </a>
                                        <button class="ab-btn ab-btn--delete" data-action="delete" data-module="attendance" data-id="<?= $record['id'] ?? 0 ?>" data-name="<?= htmlspecialchars($record['user_name'] ?? 'Record', ENT_QUOTES) ?>" title="Delete Record">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path d="M3 6h18"/>
                                                <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                                <line x1="10" y1="11" x2="10" y2="17"/>
                                                <line x1="14" y1="11" x2="14" y2="17"/>
                                            </svg>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="/ergon/assets/js/table-utils.js"></script>

<script>
function filterAttendance(filter) {
    window.location.href = '/ergon/attendance?filter=' + filter;
}

function checkOut(recordId) {
    if (confirm('Are you sure you want to check out?')) {
        fetch('/ergon/attendance/checkout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ record_id: recordId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed to check out'));
            }
        })
        .catch(error => {
            console.error('Check out error:', error);
            alert('An error occurred while checking out.');
        });
    }
}

// Standard action button handlers
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.ab-btn');
    if (!btn) return;
    
    const action = btn.dataset.action;
    const module = btn.dataset.module;
    const id = btn.dataset.id;
    const name = btn.dataset.name;
    
    if (action === 'view' && module && id) {
        window.location.href = `/ergon/${module}/view/${id}`;
    } else if (action === 'edit' && module && id) {
        window.location.href = `/ergon/${module}/edit/${id}`;
    } else if (action === 'delete' && module && id && name) {
        deleteRecord(module, id, name);
    }
});
</script>

<style>
.page-actions .form-input {
    width: auto;
    margin-right: var(--space-3);
}

.assignment-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.assigned-user {
    font-weight: 500;
    color: var(--text-primary);
}

.priority-badge {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.cell-meta {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.cell-primary {
    font-weight: 500;
    color: var(--text-primary);
}

.cell-secondary {
    font-size: var(--font-size-xs);
    color: var(--text-muted);
}

.progress-container {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 120px;
}

.progress-bar {
    width: 100%;
    height: 6px;
    background: #e2e8f0;
    border-radius: 3px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.3s ease;
}

.progress-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: var(--font-size-xs);
}

.progress-percentage {
    font-weight: 600;
    color: var(--text-primary);
}

.progress-status {
    color: var(--text-muted);
    font-size: 11px;
}

@media (max-width: 768px) {
    .page-actions {
        flex-direction: column;
        gap: var(--space-2);
    }
    
    .page-actions .form-input,
    .page-actions select {
        display: none !important;
    }
    
    .priority-badge {
        flex-direction: column;
        gap: 4px;
    }
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
