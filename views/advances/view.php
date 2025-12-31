<?php
$title = 'Advance Request Details';
$active_page = 'advances';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>💳</span> Advance Request Details</h1>
        <p>View advance request information</p>
    </div>
    <div class="page-actions">
        <?php 
        // Show approve/reject buttons for admin/owner when status is pending
        $userRole = $_SESSION['role'] ?? 'user';
        $canApprove = in_array($userRole, ['admin', 'owner']) && ($advance['status'] ?? 'pending') === 'pending';
        if ($canApprove): 
        ?>
        <form method="POST" action="/ergon/advances/approve/<?= $advance['id'] ?>" style="display: inline;">
            <input type="number" step="0.01" name="approved_amount" value="<?= number_format($advance['amount'] ?? 0, 2, '.', '') ?>" class="form-control" style="display:inline-block; width:140px; margin-right:.5rem;" required />
            <button type="submit" class="btn btn--success">
                <span>✅</span> Approve
            </button>
        </form>
        <button type="button" class="btn btn--danger" onclick="showRejectModal(<?= $advance['id'] ?>)">
            <span>❌</span> Reject
        </button>
        <?php endif; ?>
        <?php if (in_array($userRole, ['admin','owner']) && ($advance['status'] ?? '') === 'approved'): ?>
        <button class="btn btn--success" onclick="showMarkPaidModal(<?= $advance['id'] ?>)" style="margin-left:.5rem;">
            <span>✅</span> Mark Paid
        </button>
        <?php endif; ?>
        <a href="/ergon/advances" class="btn btn--secondary">
            <span>←</span> Back to Advances
        </a>
    </div>
</div>

<div class="advance-compact">
    <div class="card">
        <div class="card__header">
            <div class="advance-title-row">
                <h2 class="advance-title">💳 <?= htmlspecialchars($advance['type'] ?? 'Advance Request') ?></h2>
                <div class="advance-badges">
                    <?php 
                    $status = $advance['status'] ?? 'pending';
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
                        if (in_array($advance['status'] ?? '', ['approved', 'paid'])) {
                            if (!empty($advance['approved_amount'])) {
                                $approvedAmount = $advance['approved_amount'];
                            }
                        }
                        ?>
                        <?php if ($approvedAmount): ?>
                            <span class="amount-text" title="Approved Amount">₹<?= number_format($approvedAmount, 2) ?></span>
                        <?php else: ?>
                            <span class="amount-text" title="Requested Amount">₹<?= number_format($advance['amount'] ?? 0, 2) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="card__body">
            <?php if ($advance['reason']): ?>
            <div class="description-compact">
                <strong>Reason:</strong> <?= nl2br(htmlspecialchars($advance['reason'])) ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($advance['rejection_reason'])): ?>
            <div class="description-compact rejection-notice">
                <strong>Rejection Reason:</strong> <?= htmlspecialchars($advance['rejection_reason']) ?>
            </div>
            <?php endif; ?>
            

            
            <div class="details-compact">
                <div class="detail-group">
                    <h4>👤 Employee Details</h4>
                    <div class="detail-items">
                        <span><strong>Name:</strong> 👤 <?= htmlspecialchars($advance['user_name'] ?? 'Unknown') ?></span>
                        <span><strong>Type:</strong> 🏷️ <?= htmlspecialchars($advance['type'] ?? 'General Advance') ?></span>
                        <?php if (!empty($advance['project_name'])): ?>
                        <span><strong>Project:</strong> 📁 <?= htmlspecialchars($advance['project_name']) ?></span>
                        <?php endif; ?>
                        <span>
                            <strong>Requested:</strong> 💰 ₹<?= number_format($advance['amount'] ?? 0, 2) ?>
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
                        <span><strong>Requested:</strong> 📅 <?= date('M d, Y', strtotime($advance['created_at'] ?? 'now')) ?></span>
                        <?php if (!empty($advance['approved_at'])): ?>
                        <span><strong><?= ($advance['status'] ?? 'pending') === 'approved' ? 'Approved' : 'Processed' ?>:</strong> 📅 <?= date('M d, Y', strtotime($advance['approved_at'])) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($advance['paid_at'])): ?>
                        <span><strong>Paid:</strong> 📅 <?= date('M d, Y', strtotime($advance['paid_at'])) ?></span>
                        <?php endif; ?>
                        <?php if (isset($advance['repayment_months']) && $advance['repayment_months']): ?>
                        <span><strong>Repayment:</strong> 📅 <?= $advance['repayment_months'] ?> month<?= $advance['repayment_months'] != 1 ? 's' : '' ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="detail-group">
                    <h4>📋 Details</h4>
                    <div class="detail-items">
                        <span><strong>Reason:</strong> <?= nl2br(htmlspecialchars($advance['reason'] ?? 'N/A')) ?></span>
                        <span><strong>Status:</strong> 
                            <span class="badge badge--<?= $statusClass ?>"><?= $statusIcon ?> <?= ucfirst($status) ?></span>
                        </span>
                        <?php if (!empty($advance['approval_remarks'])): ?>
                        <span><strong>Approval Remarks:</strong> <?= nl2br(htmlspecialchars($advance['approval_remarks'])) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($advance['rejection_reason'])): ?>
                        <span><strong>Rejection Reason:</strong> <?= nl2br(htmlspecialchars($advance['rejection_reason'])) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($advance['payment_remarks'])): ?>
                        <span><strong>Payment Details:</strong> <?= nl2br(htmlspecialchars($advance['payment_remarks'])) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php if (!empty($advance['payment_proof'])): ?>
            <div class="detail-item" style="margin-top:1rem;">
                <label>Payment Proof</label>
                <?php require_once __DIR__ . '/../../app/helpers/ProofHelper.php'; echo proof_preview_html('/ergon/storage/proofs/' . $advance['payment_proof'], 'Payment Proof'); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div id="rejectModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Reject Advance Request</h3>
            <span class="close" onclick="closeRejectModal()">&times;</span>
        </div>
        <form id="rejectForm" method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label for="rejection_reason">Reason for Rejection:</label>
                    <textarea id="rejection_reason" name="rejection_reason" class="form-control" rows="4" placeholder="Please provide a reason for rejecting this advance request..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--secondary" onclick="closeRejectModal()">Cancel</button>
                <button type="submit" class="btn btn--danger">Reject Advance</button>
            </div>
        </form>
    </div>
</div>

<!-- Mark as Paid Modal -->
<div id="markPaidModal" class="modal-overlay" data-visible="false">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3>💳 Mark as Paid</h3>
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

<style>
.advance-compact {
    max-width: 1000px;
    margin: 0 auto;
}

.advance-title-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    width: 100%;
    gap: 1.5rem;
    min-height: 2rem;
}

.advance-title {
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

.advance-badges {
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



.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-overlay[data-visible="false"] {
    display: none;
}

.modal-content {
    background: white;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.modal-header h3 {
    margin: 0;
    color: var(--text-primary);
}

.modal-close {
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--text-secondary);
}

.modal-body {
    padding: 1rem;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 1rem;
    border-top: 1px solid var(--border-color);
}

@media (max-width: 768px) {
    .advance-title-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
        min-height: auto;
    }
    
    .advance-title {
        max-width: 100%;
        min-width: auto;
    }
    
    .advance-badges {
        width: 100%;
        min-width: auto;
        justify-content: flex-start;
        flex-wrap: wrap;
    }
    
    .details-compact {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .modal-content {
        width: 95%;
        margin: 1rem;
    }
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
</style>

<script>
let currentAdvanceId = null;

function showMarkPaidModal(advanceId) {
    currentAdvanceId = advanceId;
    document.getElementById('payment_proof').value = '';
    document.getElementById('payment_remarks').value = '';
    showModal('markPaidModal');
}

function closeMarkPaidModal() {
    hideModal('markPaidModal');
    currentAdvanceId = null;
}

// Modal utility functions
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
        modal.setAttribute('data-visible', 'true');
        document.body.style.overflow = 'hidden';
    }
}

function hideModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        modal.setAttribute('data-visible', 'false');
        document.body.style.overflow = '';
    }
}

function showRejectModal(advanceId) {
    document.getElementById('rejectForm').action = '/ergon/advances/reject/' + advanceId;
    const reasonField = document.getElementById('rejection_reason');
    if (reasonField) reasonField.value = '';
    document.getElementById('rejectModal').style.display = 'flex';
}

function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
    var rr = document.getElementById('rejection_reason'); if (rr) rr.value = '';
}

// Handle mark as paid form submission
document.getElementById('markPaidForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!currentAdvanceId) return;
    
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
    
    fetch(`/ergon/advances/paid/${currentAdvanceId}`, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            showSuccessMessage('✅ Advance marked as paid successfully!');
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

// Close modal when clicking outside
var rejectModalEl = document.getElementById('rejectModal');
if (rejectModalEl) {
    rejectModalEl.addEventListener('click', function(e) {
        if (e.target === this) {
            closeRejectModal();
        }
    });
}

var markPaidModalEl = document.getElementById('markPaidModal');
if (markPaidModalEl) {
    markPaidModalEl.addEventListener('click', function(e) {
        if (e.target === this) {
            closeMarkPaidModal();
        }
    });
}

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeRejectModal();
        closeMarkPaidModal();
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>

<?php // include shared proof modal so openReceiptModal works on this page too
include __DIR__ . '/../partials/proof_modal.php'; ?>
