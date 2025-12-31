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
        <?php 
        $userRole = $_SESSION['role'] ?? '';
        $expenseStatus = $expense['status'] ?? 'pending';
        $isOwner = $userRole === 'owner';
        $isAdmin = $userRole === 'admin';
        $isPending = $expenseStatus === 'pending';
        $isNotOwnExpense = ($expense['user_id'] ?? 0) != ($_SESSION['user_id'] ?? 0);
        
        $canApprove = $isPending && (($isOwner) || ($isAdmin && $isNotOwnExpense));
        ?>
        <?php if ($canApprove): ?>
        <form method="POST" action="/ergon/expenses/approve/<?= $expense['id'] ?>" style="display:inline-block;">
            <input type="number" step="0.01" name="approved_amount" value="<?= number_format($expense['amount'] ?? 0, 2, '.', '') ?>" class="form-control" style="display:inline-block; width:140px; margin-right:.5rem;" required />
            <button type="submit" class="btn btn--success">
                <span>✅</span> Approve
            </button>
        </form>
        <button class="btn btn--danger" onclick="showRejectModal(<?= $expense['id'] ?>)">
            <span>❌</span> Reject
        </button>
        <?php endif; ?>
        <a href="/ergon/expenses" class="btn btn--secondary">
            <span>←</span> Back to Expenses
        </a>
        <?php if (in_array($userRole, ['admin','owner']) && ($expense['status'] ?? '') === 'approved'): ?>
        <button class="btn btn--success" onclick="showMarkPaidModal(<?= $expense['id'] ?>)" style="margin-left:.5rem;">
            <span>✅</span> Mark Paid
        </button>
        <?php endif; ?>
    </div>
</div>

<div class="expense-compact">
    <div class="card">
        <div class="card__header">
            <div class="expense-title-row">
                <h2 class="expense-title">💰 <?= htmlspecialchars($expense['description'] ?? 'Expense Claim') ?></h2>
                <div class="expense-badges">
                    <?php 
                    $status = $expense['status'] ?? 'pending';
                    $statusClass = match($status) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'paid' => 'paid',
                        default => 'warning'
                    };
                    $statusIcon = match($status) {
                        'approved' => '✅',
                        'rejected' => '❌',
                        'paid' => '✓',
                        default => '⏳'
                    };
                    ?>
                    <span class="badge badge--<?= $statusClass ?>"><?= $statusIcon ?> <?= ucfirst($status) ?></span>
                    <div class="amount-display">
                        <?php 
                        $approvedAmount = null;
                        if (in_array($expense['status'] ?? '', ['approved', 'paid'])) {
                            // Check expenses table first, then approved_expenses table
                            if (!empty($expense['approved_amount'])) {
                                $approvedAmount = $expense['approved_amount'];
                            } elseif (!empty($approved['approved_amount'])) {
                                $approvedAmount = $approved['approved_amount'];
                            }
                        }
                        ?>
                        <?php if ($approvedAmount): ?>
                            <span class="amount-text" title="Approved Amount">₹<?= number_format($approvedAmount, 2) ?></span>
                        <?php else: ?>
                            <span class="amount-text" title="Claimed Amount">₹<?= number_format($expense['amount'] ?? 0, 2) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="card__body">
            <?php if (($expense['status'] ?? 'pending') === 'rejected' && !empty($expense['rejection_reason'])): ?>
            <div class="description-compact rejection-notice">
                <strong>Rejection Reason:</strong> <?= htmlspecialchars($expense['rejection_reason']) ?>
            </div>
            <?php endif; ?>
            
            <div class="details-compact">
                <div class="detail-group">
                    <h4>👤 Employee Details</h4>
                    <div class="detail-items">
                        <span><strong>Name:</strong> 👤 <?= htmlspecialchars($expense['user_name'] ?? 'Unknown') ?></span>
                        <span><strong>Category:</strong> 🏷️ <?= htmlspecialchars($expense['category'] ?? 'General') ?></span>
                        <?php if (!empty($expense['project_name'])): ?>
                        <span><strong>Project:</strong> 📁 <?= htmlspecialchars($expense['project_name']) ?></span>
                        <?php endif; ?>
                        <span>
                            <strong>Claimed:</strong> 💰 ₹<?= number_format($expense['amount'] ?? 0, 2) ?>
                        </span>
                        <?php if ($approvedAmount): ?>
                        <span>
                            <strong>Approved:</strong> 💵 ₹<?= number_format($approvedAmount, 2) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="detail-group">
                    <h4>📅 Timeline</h4>
                    <div class="detail-items">
                        <span><strong>Expense Date:</strong> 📅 <?= date('M d, Y', strtotime($expense['expense_date'] ?? 'now')) ?></span>
                        <span><strong>Submitted:</strong> 📅 <?= date('M d, Y', strtotime($expense['created_at'] ?? 'now')) ?></span>
                        <?php if (!empty($expense['approved_at'])): ?>
                        <span><strong><?= ($expense['status'] ?? 'pending') === 'approved' ? 'Approved' : 'Processed' ?>:</strong> 📅 <?= date('M d, Y', strtotime($expense['approved_at'])) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($expense['paid_at'])): ?>
                        <span><strong>Paid:</strong> 📅 <?= date('M d, Y', strtotime($expense['paid_at'])) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="detail-group">
                    <h4>📋 Details</h4>
                    <div class="detail-items">
                        <span><strong>Description:</strong> <?= nl2br(htmlspecialchars($expense['description'] ?? 'N/A')) ?></span>
                        <span><strong>Status:</strong> 
                            <span class="badge badge--<?= $statusClass ?>"><?= $statusIcon ?> <?= ucfirst($status) ?></span>
                        </span>
                        <?php if (!empty($expense['approval_remarks'])): ?>
                        <span><strong>Approval Remarks:</strong> <?= nl2br(htmlspecialchars($expense['approval_remarks'])) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($expense['rejection_reason'])): ?>
                        <span><strong>Rejection Reason:</strong> <?= nl2br(htmlspecialchars($expense['rejection_reason'])) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($expense['payment_remarks'])): ?>
                        <span><strong>Payment Details:</strong> <?= nl2br(htmlspecialchars($expense['payment_remarks'])) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php if (!empty($expense['attachment'])): ?>
            <div class="detail-item">
                <label>Receipt</label>
                <?php require_once __DIR__ . '/../../app/helpers/ProofHelper.php'; echo proof_preview_html('/ergon/storage/receipts/' . $expense['attachment'], 'Receipt'); ?>
            </div>
            <?php endif; ?>
            <?php
                // Check both approved_expenses and expenses tables for payment proof
                $proofFile = null;
                if (!empty($approved['payment_proof'])) {
                    $proofFile = $approved['payment_proof'];
                } elseif (!empty($expense['payment_proof'])) {
                    $proofFile = $expense['payment_proof'];
                }
            ?>
            <?php if (!empty($proofFile)): ?>
            <div class="detail-item" style="margin-top:1rem;">
                <label>Payment Proof</label>
                <?php require_once __DIR__ . '/../../app/helpers/ProofHelper.php'; echo proof_preview_html('/ergon/storage/proofs/' . $proofFile, 'Payment Proof'); ?>
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

<!-- Rejection Modal -->
<div id="rejectModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Reject Expense Claim</h3>
            <span class="close" onclick="closeRejectModal()">&times;</span>
        </div>
        <form id="rejectForm" method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label for="rejection_reason">Reason for Rejection:</label>
                    <textarea id="rejection_reason" name="rejection_reason" class="form-control" rows="4" placeholder="Please provide a reason for rejecting this expense claim..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--secondary" onclick="closeRejectModal()">Cancel</button>
                <button type="submit" class="btn btn--danger">Reject Expense</button>
            </div>
        </form>
    </div>
</div>

<!-- Mark as Paid Modal -->
<div id="markPaidModal" class="modal-overlay" data-visible="false">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3>💰 Mark as Paid</h3>
            <span class="close" onclick="closeMarkPaidModal()">&times;</span>
        </div>
        <form id="markPaidForm" enctype="multipart/form-data">
            <div class="modal-body">
                <div class="form-group">
                    <label for="payment_proof">Payment Proof (Image/PDF)</label>
                    <input type="file" id="payment_proof" name="proof" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                    <small class="text-muted">Optional. Max file size: 5MB. Allowed formats: JPG, PNG, PDF</small>
                </div>
                <div class="form-group">
                    <label for="payment_remarks">Payment Details/Remarks</label>
                    <textarea id="payment_remarks" name="payment_remarks" class="form-control" rows="3" placeholder="Enter payment method, transaction ID, or other payment details..."></textarea>
                </div>
                <p class="text-muted"><small>Note: Either upload payment proof or enter payment details (or both).</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--secondary" onclick="closeMarkPaidModal()">Cancel</button>
                <button type="submit" class="btn btn--success" id="markPaidBtn">✅ Mark as Paid</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../partials/proof_modal.php'; ?>

<style>
.expense-compact {
    max-width: 1000px;
    margin: 0 auto;
}

.expense-title-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    width: 100%;
    gap: 1.5rem;
    min-height: 2rem;
}

.expense-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
    flex: 1 1 auto;
    min-width: 200px;
    max-width: calc(100% - 200px);
    overflow-wrap: break-word;
    word-break: break-word;
    line-height: 1.3;
}

.expense-badges {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 0 0 auto;
    min-width: 180px;
    justify-content: flex-end;
}

.amount-display {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
}

.amount-text {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--primary);
    background: var(--bg-secondary);
    padding: 0.25rem 0.75rem;
    border-radius: 6px;
    border: 1px solid var(--border-color);
}

.description-compact {
    background: var(--bg-secondary);
    padding: 0.75rem;
    border-radius: 6px;
    border-left: 3px solid var(--primary);
    margin-bottom: 1rem;
    font-size: 0.9rem;
    line-height: 1.4;
}

.rejection-notice {
    background: #fef2f2;
    border-left-color: #dc2626;
    color: #dc2626;
}

.badge--paid {
    /*background: #166534;*/
    color: #3d8a36ff;
}

.details-compact {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.detail-group {
    background: var(--bg-secondary);
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid var(--border-color);
}

.detail-group h4 {
    margin: 0 0 0.75rem 0;
    font-size: 0.9rem;
    color: var(--primary);
    font-weight: 600;
}

.detail-items {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.detail-items span {
    font-size: 0.85rem;
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.detail-items strong {
    color: var(--text-primary);
    min-width: 80px;
    font-size: 0.8rem;
}

@media (max-width: 768px) {
    .expense-title-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
        min-height: auto;
    }
    
    .expense-title {
        max-width: 100%;
        min-width: auto;
    }
    
    .expense-badges {
        width: 100%;
        min-width: auto;
        justify-content: flex-start;
        flex-wrap: wrap;
    }
    
    .details-compact {
        grid-template-columns: 1fr;
    }
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
.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 1rem;
    border-top: 1px solid #e5e7eb;
}
.form-group {
    margin-bottom: 1rem;
}
.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #374151;
}
.form-control {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.875rem;
    resize: vertical;
}

@keyframes slideInRight {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
@keyframes slideOutRight {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(100%); opacity: 0; }
}

.text-muted {
    color: #6b7280;
    font-size: 0.875rem;
}
</style>

<script>
let currentExpenseId = null;

function showMarkPaidModal(expenseId) {
    currentExpenseId = expenseId;
    document.getElementById('payment_proof').value = '';
    document.getElementById('payment_remarks').value = '';
    showModal('markPaidModal');
}

function closeMarkPaidModal() {
    hideModal('markPaidModal');
    currentExpenseId = null;
}

function openReceiptModal(imageSrc) {
    var img = document.getElementById('receiptImage');
    if (img) img.src = imageSrc;
    if (typeof showModalById === 'function') {
        showModalById('receiptModal');
    } else {
        var m = document.getElementById('receiptModal'); if (m) m.style.display = 'block';
        document.body.classList.add('modal-open'); document.body.style.overflow = 'hidden';
    }
}

function closeReceiptModal() {
    var img = document.getElementById('receiptImage'); if (img) img.src = '';
    if (typeof hideModalById === 'function') {
        hideModalById('receiptModal');
    } else {
        var m = document.getElementById('receiptModal'); if (m) m.style.display = 'none';
        document.body.classList.remove('modal-open'); document.body.style.overflow = '';
    }
}

function showRejectModal(expenseId) {
    document.getElementById('rejectForm').action = '/ergon/expenses/reject/' + expenseId;
    if (typeof showModalById === 'function') showModalById('rejectModal'); else document.getElementById('rejectModal').style.display = 'block';
}

function closeRejectModal() {
    if (typeof hideModalById === 'function') hideModalById('rejectModal'); else document.getElementById('rejectModal').style.display = 'none';
    var rr = document.getElementById('rejection_reason'); if (rr) rr.value = '';
}



window.onclick = function(event) {
    const receiptModal = document.getElementById('receiptModal');
    const rejectModal = document.getElementById('rejectModal');
    
    if (event.target === receiptModal) {
        closeReceiptModal();
    }
    if (event.target === rejectModal) {
        closeRejectModal();
    }
}
</script>

<script>
// Handle mark as paid form submission
document.getElementById('markPaidForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!currentExpenseId) return;
    
    const proofFile = document.getElementById('payment_proof').files[0];
    const remarks = document.getElementById('payment_remarks').value.trim();
    
    // Validate that either proof or remarks is provided
    if (!proofFile && !remarks) {
        alert('Please either upload payment proof or enter payment details.');
        return;
    }
    
    const btn = document.getElementById('markPaidBtn');
    btn.disabled = true;
    btn.textContent = '⏳ Processing...';
    
    const formData = new FormData(this);
    
    fetch(`/ergon/expenses/paid/${currentExpenseId}`, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            showSuccessMessage('✅ Expense marked as paid successfully!');
            closeMarkPaidModal();
            setTimeout(() => location.reload(), 1500);
        } else {
            throw new Error('Failed to mark as paid');
        }
    })
    .catch(err => {
        showErrorMessage('❌ Error: ' + err.message);
        btn.disabled = false;
        btn.textContent = '✅ Mark as Paid';
    });
});

// Success/Error message functions
function showSuccessMessage(message) {
    const alert = document.createElement('div');
    alert.className = 'alert alert--success';
    alert.innerHTML = message;
    alert.style.position = 'fixed';
    alert.style.top = '20px';
    alert.style.right = '20px';
    alert.style.zIndex = '10000';
    alert.style.minWidth = '300px';
    alert.style.animation = 'slideInRight 0.3s ease-out';
    document.body.appendChild(alert);
    setTimeout(() => {
        alert.style.animation = 'slideOutRight 0.3s ease-in';
        setTimeout(() => alert.remove(), 300);
    }, 3000);
}

function showErrorMessage(message) {
    const alert = document.createElement('div');
    alert.className = 'alert alert--error';
    alert.innerHTML = message;
    alert.style.position = 'fixed';
    alert.style.top = '20px';
    alert.style.right = '20px';
    alert.style.zIndex = '10000';
    alert.style.minWidth = '300px';
    alert.style.animation = 'slideInRight 0.3s ease-out';
    document.body.appendChild(alert);
    setTimeout(() => {
        alert.style.animation = 'slideOutRight 0.3s ease-in';
        setTimeout(() => alert.remove(), 300);
    }, 4000);
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
