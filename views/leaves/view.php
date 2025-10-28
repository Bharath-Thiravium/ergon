<?php
$title = 'Leave Request Details';
$active_page = 'leaves';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>📅</span> Leave Request Details</h1>
        <p>View leave request information</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/leaves" class="btn btn--secondary">
            <span>←</span> Back to Leaves
        </a>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>📅</span> Leave Request
        </h2>
    </div>
    <div class="card__body">
        <div class="detail-grid">
            <div class="detail-item">
                <label>Employee</label>
                <span><?= htmlspecialchars($leave['user_name'] ?? 'Unknown') ?></span>
            </div>
            <div class="detail-item">
                <label>Leave Type</label>
                <span><?= htmlspecialchars($leave['leave_type'] ?? 'Annual') ?></span>
            </div>
            <div class="detail-item">
                <label>Start Date</label>
                <span><?= date('M d, Y', strtotime($leave['start_date'] ?? 'now')) ?></span>
            </div>
            <div class="detail-item">
                <label>End Date</label>
                <span><?= date('M d, Y', strtotime($leave['end_date'] ?? 'now')) ?></span>
            </div>
            <div class="detail-item">
                <label>Days</label>
                <span><?= $leave['days'] ?? 1 ?></span>
            </div>
            <div class="detail-item">
                <label>Status</label>
                <span class="badge badge--warning"><?= ucfirst($leave['status'] ?? 'pending') ?></span>
            </div>
            <div class="detail-item">
                <label>Reason</label>
                <span><?= htmlspecialchars($leave['reason'] ?? 'N/A') ?></span>
            </div>
            <div class="detail-item">
                <label>Requested</label>
                <span><?= date('M d, Y', strtotime($leave['created_at'] ?? 'now')) ?></span>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>