<?php
$title = 'Owner - Attendance';
$active_page = 'attendance';
require_once __DIR__ . '/../../app/helpers/TimezoneHelper.php';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>👥</span> Staff Attendance</h1>
        <p>Monitor all staff attendance and working hours</p>
    </div>
    <div class="page-actions">
        <input type="date" id="attendanceDate" value="<?= $filter_date ?? date('Y-m-d') ?>" onchange="window.location.href='/ergon/attendance?date='+this.value" class="form-control" style="width: auto;">
    </div>
</div>

<?php if (empty($employees)): ?>
    <div class="empty-state">
        <div class="empty-icon">👥</div>
        <h3>No Employees Found</h3>
        <p>No employees are registered in the system.</p>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">Attendance Records</h2>
        </div>
        <div class="card__body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Hours</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employees as $emp): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div style="width: 32px; height: 32px; border-radius: 50%; background: <?= $emp['status'] === 'Present' ? '#22c55e' : '#ef4444' ?>; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.75rem; font-weight: bold;">
                                        <?= strtoupper(substr($emp['name'], 0, 2)) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 500;"><?= htmlspecialchars($emp['name']) ?></div>
                                        <div style="font-size: 0.75rem; color: #6b7280;"><?= htmlspecialchars($emp['email'] ?? '') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($emp['department'] ?? '-') ?></td>
                            <td>
                                <span class="badge badge--<?= $emp['status'] === 'Present' ? 'success' : 'danger' ?>">
                                    <?= $emp['status'] === 'Present' ? '✅ Present' : '❌ Absent' ?>
                                </span>
                            </td>
                            <td><?= $emp['check_in'] ? TimezoneHelper::displayTime($emp['check_in']) : '-' ?></td>
                            <td><?= $emp['check_out'] ? TimezoneHelper::displayTime($emp['check_out']) : '-' ?></td>
                            <td><?= number_format($emp['total_hours'], 2) ?>h</td>
                            <td>
                                <div class="ab-container">
                                    <button class="ab-btn ab-btn--view" onclick="viewEmployeeDetails(<?= $emp['id'] ?>)" title="View"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                                    <?php if (!$emp['check_in']): ?>
                                    <button class="ab-btn ab-btn--success" onclick="clockIn(<?= $emp['id'] ?>)" title="Clock In"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></button>
                                    <?php elseif (!$emp['check_out']): ?>
                                    <button class="ab-btn ab-btn--warning" onclick="clockOut(<?= $emp['id'] ?>)" title="Clock Out"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="10"/><line x1="12" y1="6" x2="12" y2="12"/><line x1="12" y1="12" x2="16" y2="14"/></svg></button>
                                    <?php endif; ?>
                                    <button class="ab-btn ab-btn--edit" onclick="manualEntry(<?= $emp['id'] ?>)" title="Manual"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="M15 5l4 4"/></svg></button>
                                    <button class="ab-btn ab-btn--info" onclick="viewHistory(<?= $emp['id'] ?>)" title="History"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></button>
                                    <button class="ab-btn ab-btn--progress" onclick="generateReport(<?= $emp['id'] ?>)" title="Report"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="13" x2="16" y2="13"/><line x1="12" y1="17" x2="16" y2="17"/></svg></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
function viewEmployeeDetails(empId) { alert('View: ' + empId); }
function clockIn(empId) { alert('Clock In: ' + empId); }
function clockOut(empId) { alert('Clock Out: ' + empId); }
function manualEntry(empId) { alert('Manual: ' + empId); }
function viewHistory(empId) { alert('History: ' + empId); }
function generateReport(empId) { alert('Report: ' + empId); }
</script>

<style>
.badge--success { background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
.badge--danger { background-color: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
.empty-state { text-align: center; padding: 3rem 1rem; color: #6b7280; }
.empty-icon { font-size: 3rem; margin-bottom: 1rem; }
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
