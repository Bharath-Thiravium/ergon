<?php
$title = 'Create Follow-up';
$active_page = 'followups';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>📞</span> Create Follow-up</h1>
        <p>Add a new follow-up to track client interactions</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/followups" class="btn btn--secondary">
            <span>←</span> Back to Follow-ups
        </a>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>📝</span> Follow-up Details
        </h2>
    </div>
    <div class="card__body">
        <form method="POST" action="/ergon/followups/create">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" class="form-control" required placeholder="Follow-up title">
                </div>
                <div class="form-group">
                    <label class="form-label">Company</label>
                    <input type="text" name="company_name" class="form-control" placeholder="Company name">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Contact Person</label>
                    <input type="text" name="contact_person" class="form-control" placeholder="Contact person name">
                </div>
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="tel" name="contact_phone" class="form-control" placeholder="Contact phone number">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Project</label>
                    <input type="text" name="project_name" class="form-control" placeholder="Project name">
                </div>
                <div class="form-group">
                    <label class="form-label">Follow-up Date *</label>
                    <input type="date" name="follow_up_date" class="form-control" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Follow-up Time</label>
                    <input type="time" name="reminder_time" class="form-control" value="09:00">
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="postponed">Postponed</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4" placeholder="Follow-up description and notes"></textarea>
            </div>
            
            <div class="form-actions">
                <a href="/ergon/followups" class="btn btn--secondary">Cancel</a>
                <button type="submit" class="btn btn--primary">
                    <span>💾</span> Save Follow-up
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>