<?php
$title = 'Expense Claim Details';
$active_page = 'expenses';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>💰</span> Expense Claim Details</h1>
        <p>View expense claim information</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/expenses" class="btn btn--secondary">
            <span>←</span> Back to Expenses
        </a>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>💰</span> Expense Claim
        </h2>
    </div>
    <div class="card__body">
        <div class="detail-grid">
            <div class="detail-item">
                <label>Employee</label>
                <span><?= htmlspecialchars($expense['user_name'] ?? 'Unknown') ?></span>
            </div>
            <div class="detail-item">
                <label>Description</label>
                <span><?= htmlspecialchars($expense['description'] ?? 'N/A') ?></span>
            </div>
            <div class="detail-item">
                <label>Amount</label>
                <span>₹<?= number_format($expense['amount'] ?? 0, 2) ?></span>
            </div>
            <div class="detail-item">
                <label>Category</label>
                <span><?= htmlspecialchars($expense['category'] ?? 'General') ?></span>
            </div>
            <div class="detail-item">
                <label>Expense Date</label>
                <span><?= date('M d, Y', strtotime($expense['expense_date'] ?? 'now')) ?></span>
            </div>
            <div class="detail-item">
                <label>Status</label>
                <?php 
                $status = $expense['status'] ?? 'pending';
                $badgeClass = 'badge--warning';
                if ($status === 'approved') $badgeClass = 'badge--success';
                elseif ($status === 'rejected') $badgeClass = 'badge--danger';
                ?>
                <span class="badge <?= $badgeClass ?>"><?= ucfirst($status) ?></span>
            </div>
            <?php if (($expense['status'] ?? 'pending') === 'rejected' && !empty($expense['rejection_reason'])): ?>
            <div class="detail-item">
                <label>Rejection Reason</label>
                <span class="rejection-reason"><?= htmlspecialchars($expense['rejection_reason']) ?></span>
            </div>
            <?php endif; ?>
            <div class="detail-item">
                <label>Submitted</label>
                <span><?= date('M d, Y', strtotime($expense['created_at'] ?? 'now')) ?></span>
            </div>
            <?php if (!empty($expense['attachment'])): ?>
            <div class="detail-item">
                <label>Receipt</label>
                <div class="receipt-container">
                    <img src="/ergon/storage/receipts/<?= htmlspecialchars($expense['attachment']) ?>" 
                         alt="Receipt" 
                         class="receipt-image" 
                         onclick="openReceiptModal('/ergon/storage/receipts/<?= htmlspecialchars($expense['attachment']) ?>')">
                    <a href="/ergon/storage/receipts/<?= htmlspecialchars($expense['attachment']) ?>" 
                       target="_blank" 
                       class="receipt-link">View Full Size</a>
                </div>
            </div>
            <?php endif; ?>
            <?php if (!empty($expense['approved_at'])): ?>
            <div class="detail-item">
                <label><?= ($expense['status'] ?? 'pending') === 'approved' ? 'Approved' : 'Processed' ?> Date</label>
                <span><?= date('M d, Y', strtotime($expense['approved_at'])) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Receipt Modal -->
<div id="receiptModal" class="modal" style="display: none;">
    <div class="modal-content modal-content--large">
        <div class="modal-header">
            <h3>📄 Receipt Image</h3>
            <span class="close" onclick="closeReceiptModal()">&times;</span>
        </div>
        <div class="modal-body">
            <img id="receiptImage" src="" alt="Receipt" class="receipt-full">
        </div>
    </div>
</div>

<style>
.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 1rem;
}
.detail-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.detail-item label {
    font-weight: 600;
    color: #374151;
    font-size: 0.9rem;
}
.detail-item span {
    color: #6b7280;
    font-size: 0.95rem;
}
.rejection-reason {
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 6px;
    padding: 0.75rem;
    color: #dc2626 !important;
    font-style: italic;
}
.receipt-container {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.receipt-image {
    max-width: 200px;
    max-height: 150px;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
    cursor: pointer;
    transition: transform 0.2s;
}
.receipt-image:hover {
    transform: scale(1.05);
}
.receipt-link {
    color: #3b82f6;
    text-decoration: none;
    font-size: 0.875rem;
}
.receipt-link:hover {
    text-decoration: underline;
}
.modal {
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}
.modal-content {
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}
.modal-content--large {
    max-width: 90%;
    max-height: 90vh;
    margin: 2% auto;
}
.receipt-full {
    max-width: 100%;
    max-height: 80vh;
    object-fit: contain;
    border-radius: 8px;
}
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #e5e7eb;
}
.close {
    font-size: 1.5rem;
    cursor: pointer;
    color: #6b7280;
}
.close:hover {
    color: #374151;
}
.modal-body {
    padding: 1rem;
    text-align: center;
}
</style>

<script>
function openReceiptModal(imageSrc) {
    document.getElementById('receiptImage').src = imageSrc;
    document.getElementById('receiptModal').style.display = 'block';
}

function closeReceiptModal() {
    document.getElementById('receiptModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('receiptModal');
    if (event.target === modal) {
        closeReceiptModal();
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>