<div class="page-header">
    <div class="page-title">
        <h1><span>📞</span> <?= htmlspecialchars($data['followup']['title']) ?></h1>
        <p>Follow-up Details</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/followups" class="btn btn--secondary">← Back to Follow-ups</a>
        <?php if ($data['followup']['status'] !== 'completed'): ?>
            <button class="btn btn--warning" onclick="rescheduleFollowup(<?= $data['followup']['id'] ?>)">📅 Reschedule</button>
            <button class="btn btn--success" onclick="completeFollowup(<?= $data['followup']['id'] ?>)">✅ Complete</button>
        <?php endif; ?>
    </div>
</div>

<div class="dashboard-grid">
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">
                <span>📋</span> Follow-up Information
            </h2>
        </div>
        <div class="card__body">
            <div class="detail-grid">
                <div class="detail-item">
                    <label>Status</label>
                    <span class="badge badge--<?= $data['followup']['status'] === 'completed' ? 'success' : ($data['followup']['status'] === 'overdue' ? 'danger' : 'warning') ?>">
                        <?= ucfirst($data['followup']['status']) ?>
                    </span>
                </div>
                
                <div class="detail-item">
                    <label>Priority</label>
                    <span class="badge badge--<?= $data['followup']['priority'] === 'urgent' ? 'danger' : ($data['followup']['priority'] === 'high' ? 'warning' : 'success') ?>">
                        <?= ucfirst($data['followup']['priority']) ?>
                    </span>
                </div>
                
                <div class="detail-item">
                    <label>Follow-up Date</label>
                    <span><?= date('M j, Y', strtotime($data['followup']['follow_up_date'])) ?></span>
                </div>
                
                <div class="detail-item">
                    <label>Original Date</label>
                    <span><?= date('M j, Y', strtotime($data['followup']['original_date'])) ?></span>
                </div>
                
                <?php if ($data['followup']['reschedule_count'] > 0): ?>
                <div class="detail-item">
                    <label>Reschedule Count</label>
                    <span class="badge badge--warning"><?= $data['followup']['reschedule_count'] ?>x</span>
                </div>
                <?php endif; ?>
                
                <?php if ($data['followup']['department_name']): ?>
                <div class="detail-item">
                    <label>Department</label>
                    <span><?= htmlspecialchars($data['followup']['department_name']) ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($data['followup']['description']): ?>
            <div class="info-section">
                <label>Description</label>
                <p><?= nl2br(htmlspecialchars($data['followup']['description'])) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">
                <span>🏢</span> Contact Information
            </h2>
        </div>
        <div class="card__body">
            <div class="detail-grid">
                <?php if ($data['followup']['company_name']): ?>
                <div class="detail-item">
                    <label>Company</label>
                    <span><?= htmlspecialchars($data['followup']['company_name']) ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($data['followup']['contact_person']): ?>
                <div class="detail-item">
                    <label>Contact Person</label>
                    <span><?= htmlspecialchars($data['followup']['contact_person']) ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($data['followup']['contact_phone']): ?>
                <div class="detail-item">
                    <label>Phone</label>
                    <span><a href="tel:<?= htmlspecialchars($data['followup']['contact_phone']) ?>"><?= htmlspecialchars($data['followup']['contact_phone']) ?></a></span>
                </div>
                <?php endif; ?>
                
                <?php if ($data['followup']['contact_email']): ?>
                <div class="detail-item">
                    <label>Email</label>
                    <span><a href="mailto:<?= htmlspecialchars($data['followup']['contact_email']) ?>"><?= htmlspecialchars($data['followup']['contact_email']) ?></a></span>
                </div>
                <?php endif; ?>
                
                <?php if ($data['followup']['project_name']): ?>
                <div class="detail-item">
                    <label>Project</label>
                    <span><?= htmlspecialchars($data['followup']['project_name']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function rescheduleFollowup(id) {
    if (confirm('Reschedule this follow-up?')) {
        const newDate = prompt('Enter new date (YYYY-MM-DD):');
        if (newDate) {
            const formData = new FormData();
            formData.append('followup_id', id);
            formData.append('new_date', newDate);
            
            fetch('/ergon/followups/reschedule', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to reschedule: ' + (data.error || 'Unknown error'));
                }
            });
        }
    }
}

function completeFollowup(id) {
    if (confirm('Mark this follow-up as completed?')) {
        const formData = new FormData();
        formData.append('followup_id', id);
        
        fetch('/ergon/followups/complete', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to complete: ' + (data.error || 'Unknown error'));
            }
        });
    }
}
</script>