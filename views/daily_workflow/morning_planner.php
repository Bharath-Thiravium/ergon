<?php
$title = 'Morning Planner';
$active_page = 'tasks';
ob_start();
?>

<div class="header-actions">
    <?php if ($data['canSubmit']): ?>
        <button class="btn btn--primary" onclick="addPlanRow()">
            <span>➕</span> Add Task
        </button>
    <?php else: ?>
        <span class="badge badge--success">✅ Plan Submitted</span>
    <?php endif; ?>
</div>

<?php if (isset($_GET['error']) && $_GET['error'] === 'no_morning_plan'): ?>
    <div class="alert alert--warning">
        <strong>⚠️ Morning Plan Required</strong> Please submit your morning plan before updating progress.
    </div>
<?php endif; ?>

<div class="dashboard-grid">
    <div class="kpi-card kpi-card--primary">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📅</div>
            <div class="kpi-card__trend kpi-card__trend--neutral">— Today</div>
        </div>
        <div class="kpi-card__value"><?= date('d') ?></div>
        <div class="kpi-card__label"><?= date('M Y') ?></div>
        <div class="kpi-card__status kpi-card__status--info">Current Date</div>
    </div>
    
    <div class="kpi-card kpi-card--success">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">⏰</div>
            <div class="kpi-card__trend kpi-card__trend--up">↗ Live</div>
        </div>
        <div class="kpi-card__value"><?= date('H:i') ?></div>
        <div class="kpi-card__label">Current Time</div>
        <div class="kpi-card__status kpi-card__status--active">Real-time</div>
    </div>
    
    <div class="kpi-card <?= $data['canSubmit'] ? 'kpi-card--warning' : 'kpi-card--success' ?>">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📋</div>
            <div class="kpi-card__trend <?= $data['canSubmit'] ? 'kpi-card__trend--neutral' : 'kpi-card__trend--up' ?>">
                <?= $data['canSubmit'] ? '— Draft' : '↗ Done' ?>
            </div>
        </div>
        <div class="kpi-card__value"><?= count($data['todayPlans']) ?></div>
        <div class="kpi-card__label">Planned Tasks</div>
        <div class="kpi-card__status <?= $data['canSubmit'] ? 'kpi-card__status--pending' : 'kpi-card__status--active' ?>">
            <?= $data['canSubmit'] ? 'In Progress' : 'Submitted' ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>📝</span> Daily Task Planning
        </h2>
    </div>
    <div class="card__body">
        <?php if ($data['canSubmit']): ?>
            <form method="POST" action="/ergon/daily-workflow/submit-morning-plans" id="morningPlanForm">
                <div id="planRows">
                    <?php if (empty($data['todayPlans'])): ?>
                        <div class="plan-row">
                            <div class="form-row">
                                <div class="form-group form-group--flex-2">
                                    <label class="form-label">Task Title *</label>
                                    <input type="text" name="plans[0][title]" class="form-control" required placeholder="What will you work on today?">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Priority</label>
                                    <select name="plans[0][priority]" class="form-control">
                                        <option value="low">🟢 Low</option>
                                        <option value="medium" selected>🟡 Medium</option>
                                        <option value="high">🟠 High</option>
                                        <option value="urgent">🔴 Urgent</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Est. Hours</label>
                                    <input type="number" name="plans[0][estimated_hours]" class="form-control" min="0.5" max="8" step="0.5" value="1.0">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Action</label>
                                    <button type="button" class="btn btn--danger btn--sm" onclick="removePlanRow(this)">
                                        <span>🗑️</span>
                                    </button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea name="plans[0][description]" class="form-control" rows="2" placeholder="Brief description of the task"></textarea>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($data['todayPlans'] as $index => $plan): ?>
                            <div class="plan-row">
                                <div class="form-row">
                                    <div class="form-group form-group--flex-2">
                                        <label class="form-label">Task Title *</label>
                                        <input type="text" name="plans[<?= $index ?>][title]" class="form-control" required value="<?= htmlspecialchars($plan['title']) ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Priority</label>
                                        <select name="plans[<?= $index ?>][priority]" class="form-control">
                                            <option value="low" <?= $plan['priority'] === 'low' ? 'selected' : '' ?>>🟢 Low</option>
                                            <option value="medium" <?= $plan['priority'] === 'medium' ? 'selected' : '' ?>>🟡 Medium</option>
                                            <option value="high" <?= $plan['priority'] === 'high' ? 'selected' : '' ?>>🟠 High</option>
                                            <option value="urgent" <?= $plan['priority'] === 'urgent' ? 'selected' : '' ?>>🔴 Urgent</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Est. Hours</label>
                                        <input type="number" name="plans[<?= $index ?>][estimated_hours]" class="form-control" min="0.5" max="8" step="0.5" value="<?= $plan['estimated_hours'] ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Action</label>
                                        <button type="button" class="btn btn--danger btn--sm" onclick="removePlanRow(this)">
                                            <span>🗑️</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Description</label>
                                    <textarea name="plans[<?= $index ?>][description]" class="form-control" rows="2"><?= htmlspecialchars($plan['description']) ?></textarea>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn--secondary" onclick="addPlanRow()">
                        <span>➕</span> Add Another Task
                    </button>
                    <button type="submit" class="btn btn--primary">
                        <span>📤</span> Submit Morning Plan
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert--success">
                <strong>✅ Morning Plan Submitted</strong> 
                Your daily plan was submitted at <?= date('g:i A', strtotime($data['workflowStatus']['morning_submitted_at'])) ?>.
                You can update progress in the evening.
            </div>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Task</th>
                            <th>Priority</th>
                            <th>Est. Hours</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['todayPlans'] as $plan): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($plan['title']) ?></strong>
                                    <?php if ($plan['description']): ?>
                                        <br><small style="color: var(--text-muted);"><?= htmlspecialchars($plan['description']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge--<?= $plan['priority'] === 'urgent' ? 'danger' : ($plan['priority'] === 'high' ? 'warning' : 'success') ?>">
                                        <?= ucfirst($plan['priority']) ?>
                                    </span>
                                </td>
                                <td><?= $plan['estimated_hours'] ?>h</td>
                                <td>
                                    <span class="badge badge--<?= $plan['status'] === 'completed' ? 'success' : ($plan['status'] === 'in_progress' ? 'warning' : 'info') ?>">
                                        <?= ucfirst(str_replace('_', ' ', $plan['status'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="form-actions">
                <a href="/ergon/daily-workflow/evening-update" class="btn btn--primary">
                    <span>🌅</span> Go to Evening Update
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
let planRowIndex = <?= count($data['todayPlans']) ?>;

function addPlanRow() {
    const planRows = document.getElementById('planRows');
    const newRow = document.createElement('div');
    newRow.className = 'plan-row';
    newRow.innerHTML = `
        <div class="form-row">
            <div class="form-group form-group--flex-2">
                <label class="form-label">Task Title *</label>
                <input type="text" name="plans[${planRowIndex}][title]" class="form-control" required placeholder="What will you work on today?">
            </div>
            <div class="form-group">
                <label class="form-label">Priority</label>
                <select name="plans[${planRowIndex}][priority]" class="form-control">
                    <option value="low">🟢 Low</option>
                    <option value="medium" selected>🟡 Medium</option>
                    <option value="high">🟠 High</option>
                    <option value="urgent">🔴 Urgent</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Est. Hours</label>
                <input type="number" name="plans[${planRowIndex}][estimated_hours]" class="form-control" min="0.5" max="8" step="0.5" value="1.0">
            </div>
            <div class="form-group">
                <label class="form-label">Action</label>
                <button type="button" class="btn btn--danger btn--sm" onclick="removePlanRow(this)">
                    <span>🗑️</span>
                </button>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="plans[${planRowIndex}][description]" class="form-control" rows="2" placeholder="Brief description of the task"></textarea>
        </div>
    `;
    planRows.appendChild(newRow);
    planRowIndex++;
}

function removePlanRow(button) {
    const planRow = button.closest('.plan-row');
    if (document.querySelectorAll('.plan-row').length > 1) {
        planRow.remove();
    } else {
        alert('At least one task is required for your daily plan.');
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>