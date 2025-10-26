<?php
$title = 'Create Daily Plan';
$active_page = 'planner';
ob_start();
?>

<div class="page-header">
    <h1>Create Daily Plan</h1>
    <?php if (isset($_SESSION['user']['department'])): ?>
    <div class="department-badge"><?= $_SESSION['user']['department'] ?> Department</div>
    <?php endif; ?>
    <div class="header-actions">
        <a href="/ergon/public/planner/calendar" class="btn btn--secondary">Back to Calendar</a>
    </div>
</div>

<div class="create-plan-container">
    <div class="create-plan-form">
        <form method="POST" action="/ergon/public/planner/create">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Security::generateCSRFToken()) ?>">
            <div class="form-section">
                <h3>Basic Information</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Date *</label>
                        <input type="date" name="plan_date" required value="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Department *</label>
                        <?php if (!empty($data['departments'])): ?>
                        <select name="department_id" required readonly class="form-control--readonly">
                            <?php foreach ($data['departments'] as $dept): ?>
                            <option value="<?= $dept['id'] ?>" selected><?= htmlspecialchars($dept['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">You can only create plans for your department</small>
                        <?php else: ?>
                        <input type="text" value="No department assigned" readonly class="form-control--readonly">
                        <small class="text-danger">Please contact admin to assign you to a department</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h3>Plan Details</h3>
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="title" required placeholder="Enter plan title">
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="4" placeholder="Detailed description of the plan"></textarea>
                </div>
            </div>
            
            <div class="form-section">
                <h3>Settings</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Priority *</label>
                        <select name="priority" required>
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Estimated Hours</label>
                        <input type="number" name="estimated_hours" step="0.5" min="0" max="24" placeholder="0.0">
                    </div>
                    
                    <div class="form-group">
                        <label>Reminder Time</label>
                        <input type="time" name="reminder_time">
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn--secondary" onclick="history.back()">Cancel</button>
                <button type="submit" class="btn btn--primary">Create Plan</button>
            </div>
        </form>
    </div>
</div>

<style>
.department-badge {
    background: #007bff;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    margin-bottom: 10px;
    display: inline-block;
}

.form-control--readonly {
    background: #f8f9fa;
}

.create-plan-container {
    max-width: 800px;
    margin: 0 auto;
}

.create-plan-form {
    background: white;
    border-radius: 8px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.form-section {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.form-section:last-of-type {
    border-bottom: none;
}

.form-section h3 {
    margin: 0 0 20px 0;
    color: #333;
    font-size: 18px;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-grid.three-col {
    grid-template-columns: 1fr 1fr 1fr;
}

@media (max-width: 768px) {
    .form-grid,
    .form-grid.three-col {
        grid-template-columns: 1fr;
    }
    
    .create-plan-form {
        padding: 20px;
    }
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
