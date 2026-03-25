<?php
$title = 'Advance Requests';
$active_page = 'advances';
include __DIR__ . '/../shared/modal_component.php';
require_once __DIR__ . '/../../app/helpers/AdvanceDistributionHelper.php';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>💳</span> Advance Requests</h1>
        <p>Manage employee salary advance requests and approvals</p>
    </div>
    <div class="page-actions">
        <button onclick="showAdvanceModal()" class="btn btn--primary">
            <span>➕</span> Request Advance
        </button>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert--success">
    ✅ <?= htmlspecialchars($_GET['success']) ?>
</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
<div class="alert alert--error">
    ❌ <?= htmlspecialchars($_GET['error']) ?>
</div>
<?php endif; ?>



<?php
// Calculate finance totals
$financeTotals = AdvanceDistributionHelper::getFinanceTotals($advances ?? []);

// Calculate distributions for each card
$totalRequestedDistribution = AdvanceDistributionHelper::getStatusDistributionByAmount($advances ?? []);
$pendingApprovalDistribution = AdvanceDistributionHelper::getTypeDistributionByAmount($advances ?? [], 'pending');
$approvedUnpaidDistribution = AdvanceDistributionHelper::getTypeDistributionByAmount($advances ?? [], 'approved');
$totalPaidDistribution = AdvanceDistributionHelper::getTypeDistributionByAmount($advances ?? [], 'paid');
$pendingRequestsDistribution = AdvanceDistributionHelper::getStatusDistribution($advances ?? []);
?>

<div class="dashboard-grid">
    <?php
    // 1. Total Advances Requested
    $title = 'Total Advances Requested';
    $totalValue = $financeTotals['total_requested_amount'];
    $distributionData = $totalRequestedDistribution;
    $icon = '💳';
    $cardClass = 'kpi-card--primary';
    $valueFormat = 'currency';
    $primaryLabel = 'Total requested amount';
    include __DIR__ . '/../shared/distribution_stat_card.php';
    ?>
    
    <?php
    // 2. Pending Approval Amount
    $title = 'Pending Approval Amount';
    $totalValue = $financeTotals['pending_approval_amount'];
    $distributionData = $pendingApprovalDistribution;
    $icon = '⏳';
    $cardClass = 'kpi-card--warning';
    $valueFormat = 'currency';
    $primaryLabel = 'Awaiting approval';
    include __DIR__ . '/../shared/distribution_stat_card.php';
    ?>
    
    <?php
    // 3. Approved – Yet to Pay
    $title = 'Approved – Yet to Pay';
    $totalValue = $financeTotals['approved_unpaid_amount'];
    $distributionData = $approvedUnpaidDistribution;
    $icon = '✅';
    $cardClass = 'kpi-card--info';
    $valueFormat = 'currency';
    $primaryLabel = 'Approved but not disbursed';
    include __DIR__ . '/../shared/distribution_stat_card.php';
    ?>
    
    <?php
    // 4. Total Paid Advances
    $title = 'Total Paid Advances';
    $totalValue = $financeTotals['total_paid_amount'];
    $distributionData = $totalPaidDistribution;
    $icon = '💸';
    $cardClass = 'kpi-card--success';
    $valueFormat = 'currency';
    $primaryLabel = 'Successfully disbursed';
    include __DIR__ . '/../shared/distribution_stat_card.php';
    ?>
    
    <?php
    // 5. Pending Requests (Count-based) - only show if there are pending requests
    if ($financeTotals['pending_request_count'] > 0):
        $title = 'Pending Requests';
        $totalValue = $financeTotals['pending_request_count'];
        $distributionData = $pendingRequestsDistribution;
        $icon = '📋';
        $cardClass = 'kpi-card--secondary';
        $valueFormat = 'number';
        $primaryLabel = 'Requests in pipeline';
        include __DIR__ . '/../shared/distribution_stat_card.php';
    endif;
    ?>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>💳</span> Advance Requests
        </h2>
    </div>
    <div class="card__body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Reason</th>
                        <th>Approved Amount</th>
                        <th>Status</th>
                        <th>Requested</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($advances ?? [])): ?>
                    <tr>
                        <td colspan="8" class="text-center">
                            <div class="empty-state">
                                <div class="empty-icon">💳</div>
                                <h3>No Advance Requests</h3>
                                <p>No advance requests have been submitted yet.</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($advances as $advance): ?>
                        <tr>
                            <td>
                                <?php 
                                $employeeRole = ucfirst($advance['user_role'] ?? 'user');
                                if ($employeeRole === 'User') $employeeRole = 'Employee';
                                
                                $employeeName = htmlspecialchars($advance['user_name'] ?? 'Unknown');
                                $isCurrentUser = ($advance['user_id'] ?? 0) == ($_SESSION['user_id'] ?? 0);
                                $displayName = $isCurrentUser ? "Myself ({$employeeName})" : $employeeName;
                                echo $displayName . ' - ' . $employeeRole;
                                ?>
                                <?php if (!empty($advance['paid_by_name']) && ($advance['status'] ?? '') === 'paid'): ?>
                                <br><small class="text-muted">💸 Paid by: <?= htmlspecialchars($advance['paid_by_name']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars(!empty($advance['type']) ? $advance['type'] : 'General Advance') ?></td>
                            <td>₹<?= number_format($advance['amount'] ?? 0, 2) ?></td>
                            <td><?= htmlspecialchars($advance['reason'] ?? '') ?></td>
                            <td>
                                <?php if (!empty($advance['approved_amount']) && in_array($advance['status'] ?? '', ['approved', 'paid'])): ?>
                                    ₹<?= number_format($advance['approved_amount'], 2) ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $advanceStatus = $advance['status'] ?? 'pending';
                                $statusBadgeClass = match($advanceStatus) {
                                    'approved' => 'badge--success',
                                    'rejected' => 'badge--danger',
                                    default => 'badge--warning'
                                };
                                ?>
                                <span class="badge <?= $statusBadgeClass ?>"><?= ucfirst($advanceStatus) ?></span>
                            </td>
                            <td><?= date('M d, Y', strtotime($advance['created_at'] ?? 'now')) ?></td>
                            <td>
                                <div class="ab-container">
                                    <a class="ab-btn ab-btn--view" data-action="view" data-module="advances" data-id="<?= $advance['id'] ?>" title="View Details">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <polyline points="14,2 14,8 20,8"/>
                                            <line x1="16" y1="13" x2="8" y2="13"/>
                                            <line x1="16" y1="17" x2="8" y2="17"/>
                                        </svg>
                                    </a>
                                    <?php if (($advance['status'] ?? 'pending') === 'pending' && ($advance['user_id'] ?? 0) == ($_SESSION['user_id'] ?? 0)): ?>
                                    <button class="ab-btn ab-btn--edit" onclick="editAdvance(<?= $advance['id'] ?>)" title="Edit Advance">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                            <path d="M15 5l4 4"/>
                                        </svg>
                                    </button>
                                    <?php endif; ?>
                                    <?php 
                                    $currentUserRole = $user_role ?? '';
                                    $isPending = ($advance['status'] ?? 'pending') === 'pending';
                                    $isNotOwnAdvance = ($advance['user_id'] ?? 0) != ($_SESSION['user_id'] ?? 0);
                                    $canApprove = $isPending && in_array($currentUserRole, ['owner', 'admin']);
                                    ?>
                                    <?php if ($canApprove): ?>
                                    <button class="ab-btn ab-btn--approve" onclick="showApprovalModal(<?= $advance['id'] ?>)" title="Approve Advance">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <polyline points="20,6 9,17 4,12"/>
                                        </svg>
                                    </button>
                                    <button class="ab-btn ab-btn--reject" onclick="showRejectModal(<?= $advance['id'] ?>)" title="Reject Advance">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <line x1="18" y1="6" x2="6" y2="18"/>
                                            <line x1="6" y1="6" x2="18" y2="18"/>
                                        </svg>
                                    </button>
                                    <?php endif; ?>
                                    <?php if (($advance['status'] ?? 'pending') === 'approved' && ($advance['user_id'] ?? 0) != ($_SESSION['user_id'] ?? 0)): ?>
                                    <button class="ab-btn ab-btn--mark-paid" onclick="showMarkPaidModal(<?= $advance['id'] ?>)" title="Mark as Paid">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M9 11l3 3l8-8"/>
                                            <path d="M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9s4.03-9 9-9c1.51 0 2.93.37 4.18 1.03"/>
                                        </svg>
                                    </button>
                                    <?php endif; ?>
                                    <?php if (($advance['user_id'] ?? 0) == ($_SESSION['user_id'] ?? 0) && ($advance['status'] ?? 'pending') === 'pending'): ?>
                                    <button class="ab-btn ab-btn--delete" data-action="delete" data-module="advances" data-id="<?= $advance['id'] ?>" data-name="Advance Request" title="Delete Request">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M3 6h18"/>
                                            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                            <line x1="10" y1="11" x2="10" y2="17"/>
                                            <line x1="14" y1="11" x2="14" y2="17"/>
                                        </svg>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Approval Modal -->
<div id="approvalModal" class="modal-overlay" data-visible="false">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3>💳 Approve Advance Request</h3>
            <span class="close" onclick="closeApprovalModal()">&times;</span>
        </div>
        <form id="approvalForm">
            <div class="modal-body">
                <div class="advance-details" id="advanceDetails">
                    <!-- Advance details will be loaded here -->
                </div>
                <div class="form-group">
                    <label for="approved_amount">Approved Amount (₹) *</label>
                    <input type="number" id="approved_amount" name="approved_amount" class="form-control" step="0.01" min="0.01" required>
                </div>
                <div class="form-group">
                    <label for="approval_remarks">Approval Remarks / Notes</label>
                    <textarea id="approval_remarks" name="approval_remarks" class="form-control" rows="3" placeholder="Enter reason for approval or any remarks..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--secondary" onclick="closeApprovalModal()">Cancel</button>
                <button type="submit" class="btn btn--success" id="approveBtn">✅ Approve Advance</button>
            </div>
        </form>
    </div>
</div>

<!-- Rejection Modal -->
<div id="rejectModal" class="modal-overlay" data-visible="false">
    <div class="modal-content" style="max-width: 500px;">
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
                <div class="form-group" id="paidToInfo" style="background:#f0f9ff;padding:10px;border-radius:6px;border-left:3px solid #3b82f6;margin-bottom:12px;display:none;">
                    <strong>Paying to:</strong> <span id="paidToName"></span>
                </div>
                <?php
                try {
                    require_once __DIR__ . '/../../app/config/database.php';
                    $db = Database::connect();
                    $ownerRow = $db->query("SELECT id FROM users WHERE role = 'company_owner' AND status = 'active' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                    $companyOwnerId = $ownerRow['id'] ?? ($_SESSION['user_id'] ?? 0);
                } catch (Exception $e) { $companyOwnerId = $_SESSION['user_id'] ?? 0; }
                ?>
                <input type="hidden" name="paid_by_owner_id" value="<?= $companyOwnerId ?>">
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



<script>
let currentAdvanceId = null;

function showApprovalModal(advanceId) {
    currentAdvanceId = advanceId;
    
    // Fetch advance details
    fetch(`/ergon/advances/approve/${advanceId}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        credentials: 'same-origin'
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Server returned non-JSON response');
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.advance) {
                const a = data.advance;
                
                // Populate advance details
                document.getElementById('advanceDetails').innerHTML = `
                    <div class="advance-info">
                        <div class="row">
                            <div class="col"><strong>Employee:</strong> ${a.user_name || 'Unknown'}</div>
                            <div class="col"><strong>Type:</strong> ${a.type || 'General Advance'}</div>
                        </div>
                        <div class="row">
                            <div class="col"><strong>Requested Amount:</strong> ₹${parseFloat(a.amount || 0).toFixed(2)}</div>
                            <div class="col"><strong>Requested Date:</strong> ${a.requested_date || 'N/A'}</div>
                        </div>
                        <div class="row">
                            <div class="col"><strong>Submitted Date:</strong> ${a.created_at ? new Date(a.created_at).toLocaleDateString() : 'N/A'}</div>
                            <div class="col"><strong>Status:</strong> <span class="badge badge--warning">Pending</span></div>
                        </div>
                        <div class="row">
                            <div class="col" style="grid-column: 1 / -1;"><strong>Reason:</strong> ${a.reason || 'No reason provided'}</div>
                        </div>
                        ${a.repayment_date ? `<div class="row"><div class="col" style="grid-column: 1 / -1;"><strong>Expected Repayment:</strong> ${new Date(a.repayment_date).toLocaleDateString()}</div></div>` : ''}
                    </div>
                `;
                
                // Set default approved amount to requested amount
                document.getElementById('approved_amount').value = parseFloat(a.amount || 0).toFixed(2);
                document.getElementById('approval_remarks').value = '';
                
                document.getElementById('approvalModal').setAttribute('data-visible', 'true');
                document.getElementById('approvalModal').style.display = 'flex';
            } else {
                showError('Error loading advance details: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(err => {
            console.error('Approval modal error:', err);
            showError('Failed to load advance details: ' + err.message);
        });
}

function closeApprovalModal() {
    document.getElementById('approvalModal').setAttribute('data-visible', 'false');
    document.getElementById('approvalModal').style.display = 'none';
    currentAdvanceId = null;
}

function showRejectModal(advanceId) {
    document.getElementById('rejectForm').action = '/ergon/advances/reject/' + advanceId;
    const reasonField = document.getElementById('rejection_reason');
    if (reasonField) reasonField.value = '';
    document.getElementById('rejectModal').setAttribute('data-visible', 'true');
    document.getElementById('rejectModal').style.display = 'flex';
}

function closeRejectModal() {
    document.getElementById('rejectModal').setAttribute('data-visible', 'false');
    document.getElementById('rejectModal').style.display = 'none';
}

function showMarkPaidModal(advanceId) {
    currentAdvanceId = advanceId;
    document.getElementById('payment_proof').value = '';
    document.getElementById('payment_remarks').value = '';
    // Show who this advance is being paid to
    const row = document.querySelector(`button[onclick="showMarkPaidModal(${advanceId})"]`)?.closest('tr');
    const paidToInfo = document.getElementById('paidToInfo');
    const paidToName = document.getElementById('paidToName');
    if (row && paidToInfo && paidToName) {
        const employeeCell = row.querySelector('td:first-child');
        paidToName.textContent = employeeCell ? employeeCell.textContent.trim() : '';
        paidToInfo.style.display = 'block';
    }
    document.getElementById('markPaidModal').setAttribute('data-visible', 'true');
    document.getElementById('markPaidModal').style.display = 'flex';
}

function closeMarkPaidModal() {
    document.getElementById('markPaidModal').setAttribute('data-visible', 'false');
    document.getElementById('markPaidModal').style.display = 'none';
    currentAdvanceId = null;
}

// Handle approval form submission
document.getElementById('approvalForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!currentAdvanceId) return;
    
    const btn = document.getElementById('approveBtn');
    btn.disabled = true;
    btn.textContent = '⏳ Approving...';
    
    const formData = new FormData(this);
    
    fetch(`/ergon/advances/approve/${currentAdvanceId}`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Server returned non-JSON response');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showSuccess('Advance approved successfully!');
            closeApprovalModal();
            setTimeout(() => location.reload(), 2000);
        } else {
            showError(data.error || 'Approval failed');
            btn.disabled = false;
            btn.textContent = '✅ Approve Advance';
        }
    })
    .catch(err => {
        console.error('Approval submission error:', err);
        showError('Approval failed: ' + err.message);
        btn.disabled = false;
        btn.textContent = '✅ Approve Advance';
    });
});

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
            showSuccess('Advance marked as paid successfully!');
            closeMarkPaidModal();
            setTimeout(() => location.reload(), 2000);
        } else {
            throw new Error('Failed to mark as paid');
        }
    })
    .catch(err => {
        showError('Payment error: ' + err.message);
        btn.disabled = false;
        btn.textContent = '✅ Mark as Paid';
    });
});

// Using universal modal system from dashboard layout
</script>

<!-- Advance Modal -->
<div id="advanceModal" class="modal-overlay" data-visible="false">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="advanceModalTitle">💳 Request Advance</h3>
            <button class="modal-close" onclick="closeAdvanceModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="advanceForm">
                <input type="hidden" id="advance_id" name="advance_id">
                <div style="display: flex; gap: 12px; margin-bottom: 12px;">
                    <div style="flex: 1;">
                        <label>Advance Type *</label>
                        <select id="type" name="type" class="form-input" required>
                    <option value="">Select advance type</option>
                    <option value="Salary Advance">Salary Advance</option>
                    <option value="Travel Advance">Travel Advance</option>
                    <option value="Emergency Advance">Emergency Advance</option>
                    <option value="Project Advance">Project Advance</option>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label>Project *</label>
                        <select id="adv_project_id" name="project_id" class="form-input" required>
                    <option value="">Select Project</option>
                        </select>
                    </div>
                </div>
                <label>Amount (₹) *</label>
                <input type="number" id="adv_amount" name="amount" class="form-input" step="0.01" min="1" required style="margin-bottom: 12px;">
                <label>Reason *</label>
                <textarea id="reason" name="reason" class="form-input" rows="4" required style="margin-bottom: 12px;"></textarea>
                <label>Expected Repayment Date (Optional)</label>
                <input type="date" id="repayment_date" name="repayment_date" class="form-input">
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn--secondary" onclick="closeAdvanceModal()">Cancel</button>
            <button class="btn btn--primary" onclick="submitAdvanceForm()" id="advanceSubmitBtn">➕ Submit Request</button>
        </div>
    </div>
</div>

<script>
let isEditingAdvance = false;

function showAdvanceModal() {
    isEditingAdvance = false;
    document.getElementById('advanceModalTitle').textContent = '💳 Request Advance';
    document.getElementById('advanceSubmitBtn').textContent = '➕ Submit Request';
    document.getElementById('advanceForm').reset();
    document.getElementById('advance_id').value = '';
    document.getElementById('advanceModal').setAttribute('data-visible', 'true');
    document.getElementById('advanceModal').style.display = 'flex';
    loadAdvanceProjects('adv_project_id');
}

function editAdvance(id) {
    isEditingAdvance = true;
    document.getElementById('advanceModalTitle').textContent = '💳 Edit Advance';
    document.getElementById('advanceSubmitBtn').textContent = '💾 Update Request';
    document.getElementById('advanceModal').setAttribute('data-visible', 'true');
    document.getElementById('advanceModal').style.display = 'flex';
    
    fetch(`/ergon/api/advance.php?id=${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
        .then(response => {
            if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    console.error('Non-JSON response:', text);
                    throw new Error('Server returned non-JSON response');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const a = data.advance;
                document.getElementById('advance_id').value = a.id;
                document.getElementById('type').value = a.type;
                document.getElementById('adv_project_id').value = a.project_id || '';
                document.getElementById('adv_amount').value = a.amount;
                document.getElementById('reason').value = a.reason;
                document.getElementById('repayment_date').value = a.repayment_date || '';
                loadAdvanceProjects('adv_project_id', a.project_id);
            } else {
                showError('Failed to load advance details: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(err => {
            console.error('Edit advance error:', err);
            showError('Failed to load advance details: ' + err.message);
        });
}

function closeAdvanceModal() {
    document.getElementById('advanceModal').setAttribute('data-visible', 'false');
    document.getElementById('advanceModal').style.display = 'none';
}

function loadAdvanceProjects(selectId, selectedId = null) {
    fetch('/ergon/api/projects.php', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
        .then(response => {
            if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    console.error('Non-JSON response:', text);
                    throw new Error('Server returned non-JSON response');
                });
            }
            return response.json();
        })
        .then(data => {
            const select = document.getElementById(selectId);
            select.innerHTML = '<option value="">Select Project</option>';
            if (data.success && data.projects) {
                data.projects.forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.id;
                    let text = p.name;
                    if (p.department_name) text += ' - ' + p.department_name;
                    if (p.description) text += ' (' + p.description + ')';
                    opt.textContent = text;
                    if (selectedId && p.id == selectedId) opt.selected = true;
                    select.appendChild(opt);
                });
            }
        })
        .catch(err => {
            console.error('Load projects error:', err);
            showError('Failed to load projects: ' + err.message);
        });
}

function submitAdvanceForm() {
    const form = document.getElementById('advanceForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const btn = document.getElementById('advanceSubmitBtn');
    btn.disabled = true;
    btn.textContent = '⏳ Submitting...';
    
    const formData = new FormData(form);
    const advanceId = formData.get('advance_id');
    const url = isEditingAdvance && advanceId ? `/ergon/advances/edit/${advanceId}` : '/ergon/advances/create';
    
    fetch(url, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showSuccess(isEditingAdvance ? 'Advance updated successfully!' : 'Advance request submitted successfully!');
                closeAdvanceModal();
                setTimeout(() => location.reload(), 2000);
            } else {
                showError(data.error || 'Failed to process request');
                btn.disabled = false;
                btn.textContent = isEditingAdvance ? '💾 Update Request' : '➕ Submit Request';
            }
        })
        .catch(err => {
            showError('Network error: ' + err.message);
            btn.disabled = false;
            btn.textContent = isEditingAdvance ? '💾 Update Request' : '➕ Submit Request';
        });
}
</script>

<script>
// Global action button handler
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.ab-btn');
    if (!btn) return;
    
    const action = btn.dataset.action;
    const module = btn.dataset.module;
    const id = btn.dataset.id;
    const name = btn.dataset.name;
    
    if (action === 'view' && module && id) {
        window.location.href = `/ergon/${module}/view/${id}`;
    } else if (action === 'delete' && module && id && name) {
        deleteRecord(module, id, name);
    }
});
</script>



<style>
.advance-info {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
}
.advance-info .row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 8px;
}
.advance-info .row:last-child {
    margin-bottom: 0;
}
.advance-info .col {
    font-size: 14px;
}
.ab-btn--mark-paid {
    background: #10b981;
    color: white;
}

/* Distribution Card Styles */
.kpi-card {
    min-height: 200px;
    padding: 24px;
}

.kpi-card__value {
    font-size: 28px;
    font-weight: bold;
    margin-bottom: 6px;
    color: #1f2937;
}

.kpi-card__label {
    font-size: 12px;
    color: #6b7280;
    margin-bottom: 16px;
    font-weight: 500;
}

.kpi-card__chart {
    height: 90px !important;
    margin-top: 12px;
}

.kpi-card--highlight {
    background: linear-gradient(135deg, #fef2f2 0%, #ffffff 100%);
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

@media (max-width: 1024px) {
    .dashboard-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
        gap: 15px;
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
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
