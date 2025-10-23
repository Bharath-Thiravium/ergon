<?php
$title = 'Create Daily Plan';
$active_page = 'planner';
ob_start();
?>

<div class="page-header">
    <h1>Create Daily Plan</h1>
    <div class="header-actions">
        <a href="/ergon/planner/calendar" class="btn btn--secondary">Back to Calendar</a>
    </div>
</div>

<div class="card">
    <div class="card__body">
        <form method="POST" action="/ergon/planner/create">
            <div class="form-group">
                <label>Date *</label>
                <input type="date" name="plan_date" required value="<?= date('Y-m-d') ?>">
            </div>
            
            <div class="form-group">
                <label>Department *</label>
                <select name="department_id" required>
                    <option value="">Select Department</option>
                    <?php foreach ($data['departments'] as $dept): ?>
                    <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Title *</label>
                <input type="text" name="title" required placeholder="Enter plan title">
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="4" placeholder="Detailed description of the plan"></textarea>
            </div>
            
            <div class="form-row">
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
            
            <div class="form-actions">
                <button type="button" class="btn btn--secondary" onclick="history.back()">Cancel</button>
                <button type="submit" class="btn btn--primary">Create Plan</button>
            </div>
        </form>
    </div>
</div>

<style>
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 15px;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>