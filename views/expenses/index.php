<?php
$title = 'Expense Claims';
$active_page = 'expenses';
require_once __DIR__ . '/../../app/helpers/ExpenseDistributionHelper.php';
ob_start();
?>

<style>
.expense-info {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
}
.expense-info .row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 8px;
}
.expense-info .row:last-child {
    margin-bottom: 0;
}
.expense-info .col {
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
    border: 1px solid #e5e7eb;
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

.kpi-card--primary {
    border-left: 4px solid #3b82f6;
}

.kpi-card--success {
    border-left: 4px solid #10b981;
}

.kpi-card--info {
    border-left: 4px solid #06b6d4;
}

.kpi-card--warning {
    border-left: 4px solid #f59e0b;
}

.kpi-card--secondary {
    border-left: 4px solid #8b5cf6;
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

.kpi-card {
    transition: transform 0.2s ease;
}

.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.module-filters {
    display: flex;
    align-items: end;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 18px;
}

.module-filters__group {
    min-width: 240px;
}

.module-filters__label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 6px;
    color: #374151;
}

/* ============================================================
   EXPENSE MOBILE UX  —  max-width: 768px only
   Desktop is completely unaffected.
   ============================================================ */
@media (max-width: 768px) {

    /* ─ Show/hide toggle between views ─────────────────────────── */
    .exp-desktop-table { display: none !important; }
    .exp-mobile-list   { display: block; }

    /* ─ Sticky section header ──────────────────────────────── */
    .exp-list-header {
        position: sticky;
        top: 60px;          /* clears the fixed 60px mobile header */
        z-index: 20;
        background: var(--bg-secondary, #f8fafc);
        border-bottom: 1px solid var(--border-color, #e2e8f0);
    }

    /* ─ Accordion card list container ───────────────────────── */
    .exp-mobile-list {
        -webkit-overflow-scrolling: touch;
        padding: 4px 0 80px;  /* bottom pad clears scroll-to-top btn */
    }

    /* ─ Individual expense card ────────────────────────────── */
    .exp-card {
        background: var(--bg-primary, #fff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 10px;
        margin: 0 0 10px;
        overflow: hidden;
        box-shadow: 0 1px 4px rgba(0,0,0,.06);
        /* Smooth height transition when expanding */
        transition: box-shadow .2s ease;
    }
    .exp-card.is-open {
        box-shadow: 0 3px 10px rgba(0,0,0,.12);
    }

    /* ─ Summary row (always visible, acts as the tap target) ─── */
    .exp-card__summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        padding: 13px 14px;
        background: none;
        border: none;
        cursor: pointer;
        text-align: left;
        gap: 8px;
        min-height: 56px;   /* comfortable tap target */
        -webkit-tap-highlight-color: transparent;
    }
    .exp-card__summary:active {
        background: var(--bg-secondary, #f8fafc);
    }

    .exp-card__left {
        display: flex;
        flex-direction: column;
        gap: 2px;
        min-width: 0;
        flex: 1;
    }
    .exp-card__name {
        font-size: 13px;
        font-weight: 600;
        color: var(--text-primary, #111827);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .exp-card__cat {
        font-size: 11px;
        color: var(--text-muted, #6b7280);
    }

    .exp-card__right {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
    }
    .exp-card__amount {
        font-size: 14px;
        font-weight: 700;
        color: var(--text-primary, #111827);
    }

    /* Status badges */
    .exp-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
    }
    .exp-badge--pending  { background: #fef3c7; color: #d97706; }
    .exp-badge--approved { background: #d1fae5; color: #059669; }
    .exp-badge--rejected { background: #fee2e2; color: #dc2626; }

    /* Chevron rotates when open */
    .exp-card__chevron {
        font-size: 18px;
        color: var(--text-muted, #9ca3af);
        transition: transform .25s ease;
        line-height: 1;
    }
    .exp-card.is-open .exp-card__chevron {
        transform: rotate(90deg);
    }

    /* ─ Detail panel ───────────────────────────────────────── */
    .exp-card__detail {
        border-top: 1px solid var(--border-color, #e2e8f0);
        padding: 12px 14px 14px;
        background: var(--bg-secondary, #f8fafc);
        /* Animate open/close */
        animation: expSlideDown .2s ease;
    }
    @keyframes expSlideDown {
        from { opacity: 0; transform: translateY(-6px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .exp-card__detail-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 8px;
        padding: 5px 0;
        border-bottom: 1px solid var(--border-color, #e2e8f0);
        font-size: 13px;
    }
    .exp-card__detail-row:last-of-type { border-bottom: none; }
    .exp-card__detail-label {
        color: var(--text-muted, #6b7280);
        font-weight: 500;
        flex-shrink: 0;
        min-width: 80px;
    }
    .exp-card__detail-value {
        color: var(--text-primary, #111827);
        text-align: right;
        word-break: break-word;
    }

    /* ─ Action buttons inside detail panel ──────────────────── */
    .exp-card__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 12px;
        padding-top: 10px;
        border-top: 1px solid var(--border-color, #e2e8f0);
    }
    .exp-act-btn {
        flex: 1;
        min-width: 72px;
        padding: 9px 10px;
        border-radius: 7px;
        font-size: 12px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        text-align: center;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 40px;
        transition: opacity .15s;
    }
    .exp-act-btn:active { opacity: .75; }
    .exp-act-btn--view    { background: #dbeafe; color: #1d4ed8; }
    .exp-act-btn--edit    { background: #fef3c7; color: #d97706; }
    .exp-act-btn--approve { background: #d1fae5; color: #059669; }
    .exp-act-btn--reject  { background: #fee2e2; color: #dc2626; }
    .exp-act-btn--pay     { background: #ede9fe; color: #7c3aed; }
    .exp-act-btn--delete  { background: #fee2e2; color: #dc2626; }

    /* ─ Empty state ─────────────────────────────────────────── */
    .exp-empty {
        text-align: center;
        padding: 40px 20px;
        color: var(--text-muted, #9ca3af);
        font-size: 14px;
    }

    /* ─ Scroll-to-top FAB ─────────────────────────────────── */
    .exp-scroll-top {
        position: fixed;
        bottom: 80px;
        right: 16px;
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: #4f46e5;
        color: #fff;
        border: none;
        cursor: pointer;
        display: none;          /* shown by JS after scrolling */
        align-items: center;
        justify-content: center;
        font-size: 20px;
        box-shadow: 0 4px 12px rgba(79,70,229,.4);
        z-index: 500;
        transition: opacity .2s, transform .2s;
    }
    .exp-scroll-top.visible {
        display: flex;
    }
    .exp-scroll-top:active {
        transform: scale(.92);
    }

    /* Dark theme adjustments */
    [data-theme='dark'] .exp-card {
        background: var(--gray-800);
        border-color: var(--gray-700);
    }
    [data-theme='dark'] .exp-card__detail {
        background: var(--gray-900);
        border-color: var(--gray-700);
    }
    [data-theme='dark'] .exp-card__detail-row {
        border-color: var(--gray-700);
    }
    [data-theme='dark'] .exp-card__name,
    [data-theme='dark'] .exp-card__amount,
    [data-theme='dark'] .exp-card__detail-value { color: #f9fafb; }
    [data-theme='dark'] .exp-card__cat,
    [data-theme='dark'] .exp-card__detail-label { color: #9ca3af; }
    [data-theme='dark'] .exp-list-header {
        background: var(--gray-900);
        border-color: var(--gray-700);
    }
}

/* Desktop: hide mobile-only elements */
@media (min-width: 769px) {
    .exp-mobile-list,
    .exp-scroll-top { display: none !important; }
}
</style>

<div class="page-header">
    <div class="page-title">
        <h1><span>💰</span> Expense Management</h1>
        <p>Track and manage employee expense claims</p>
    </div>
    <div class="page-actions">
        <?php if (($user_role ?? '') !== 'user'): ?>
        <form method="GET" class="module-filters">
            <div class="module-filters__group">
                <label for="expense-project-filter" class="module-filters__label">Filter By Project</label>
                <select id="expense-project-filter" name="project_id" class="form-control" onchange="this.form.submit()">
                    <option value="">All Projects</option>
                    <?php foreach (($projects ?? []) as $project): ?>
                    <option value="<?= (int) ($project['id'] ?? 0) ?>" <?= ((int) ($filters['project_id'] ?? 0) === (int) ($project['id'] ?? 0)) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($project['name'] ?? 'Unnamed Project', ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if (!empty($filters['project_id'])): ?>
            <a href="/ergon/expenses" class="btn btn--secondary">Clear Filter</a>
            <?php endif; ?>
        </form>
        <?php endif; ?>
        <button onclick="showExpenseModal()" class="btn btn--primary">
            <span></span> Submit Expense
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
$financeTotals = ExpenseDistributionHelper::getFinanceTotals($expenses ?? []);

// Calculate distributions for each card
$totalSubmittedDistribution = ExpenseDistributionHelper::getStatusDistributionByAmount($expenses ?? []);
$pendingReviewDistribution = ExpenseDistributionHelper::getCategoryDistributionByAmount($expenses ?? [], 'pending');
$approvedUnreimbursedDistribution = ExpenseDistributionHelper::getCategoryDistributionByAmount($expenses ?? [], 'approved');
$totalReimbursedDistribution = ExpenseDistributionHelper::getCategoryDistributionByAmount($expenses ?? [], 'paid');
$expenseClaimsDistribution = ExpenseDistributionHelper::getStatusDistribution($expenses ?? []);
?>

<div class="dashboard-grid">
    <?php
    // 1. Total Expenses Submitted
    $title = 'Total Expenses Submitted';
    $totalValue = $financeTotals['total_submitted_amount'];
    $distributionData = $totalSubmittedDistribution;
    $icon = '💰';
    $cardClass = 'kpi-card--primary';
    $valueFormat = 'currency';
    $primaryLabel = 'Total expense liability created';
    include __DIR__ . '/../shared/distribution_stat_card.php';
    ?>
    
    <?php
    // 2. Pending Review Amount
    $title = 'Pending Review Amount';
    $totalValue = $financeTotals['pending_review_amount'];
    $distributionData = $pendingReviewDistribution;
    $icon = '⏳';
    $cardClass = 'kpi-card--warning';
    $valueFormat = 'currency';
    $primaryLabel = 'Expenses awaiting approval';
    include __DIR__ . '/../shared/distribution_stat_card.php';
    ?>
    
    <?php
    // 3. Approved – Yet to Reimburse
    $title = 'Approved – Yet to Reimburse';
    $totalValue = $financeTotals['approved_unreimbursed_amount'];
    $distributionData = $approvedUnreimbursedDistribution;
    $icon = '✅';
    $cardClass = 'kpi-card--info';
    $valueFormat = 'currency';
    $primaryLabel = 'Approved expenses not yet paid';
    include __DIR__ . '/../shared/distribution_stat_card.php';
    ?>
    
    <?php
    // 4. Total Reimbursed
    $title = 'Total Reimbursed';
    $totalValue = $financeTotals['total_reimbursed_amount'];
    $distributionData = $totalReimbursedDistribution;
    $icon = '💸';
    $cardClass = 'kpi-card--success';
    $valueFormat = 'currency';
    $primaryLabel = 'Actual cash outflow';
    include __DIR__ . '/../shared/distribution_stat_card.php';
    ?>
    
    <?php
    // 5. Expense Claims (Count-based) - only show if there are claims
    if ($financeTotals['total_claim_count'] > 0):
        $title = 'Expense Claims';
        $totalValue = $financeTotals['total_claim_count'];
        $distributionData = $expenseClaimsDistribution;
        $icon = '📋';
        $cardClass = 'kpi-card--secondary';
        $valueFormat = 'number';
        $primaryLabel = 'Workload & processing health';
        include __DIR__ . '/../shared/distribution_stat_card.php';
    endif;
    ?>
</div>



<div class="card">
    <div class="card__header exp-list-header">
        <h2 class="card__title">
            <span>💰</span> Expense Claims
        </h2>
    </div>
    <div class="card__body">
    <div class="card__body">

        <!-- ── Desktop table view (hidden on mobile) ── -->
        <div class="table-responsive exp-desktop-table">
            <table class="table">
                <thead>
                    <tr>
                        <th class="col-employee">Employee / Owner</th>
                        <th class="col-description">Description</th>
                        <th class="col-amount">Amount</th>
                        <th class="col-date">Date</th>
                        <th class="col-status">Status</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($expenses ?? [])): ?>
                    <tr>
                        <td colspan="6" class="text-center">
                            <div class="empty-state">
                                <div class="empty-icon">💰</div>
                                <h3>No Expense Claims</h3>
                                <p>No expense claims have been submitted yet.</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($expenses as $expense): ?>
                    <tr>
                        <td>
                            <?php 
                            $employeeRole = ucfirst($expense['user_role'] ?? 'user');
                            if ($employeeRole === 'User') $employeeRole = 'Employee';
                            
                            $employeeName = htmlspecialchars($expense['user_name'] ?? 'Unknown');
                            $isCurrentUser = ($expense['user_id'] ?? 0) == ($_SESSION['user_id'] ?? 0);
                            $displayName = $isCurrentUser ? "Myself ({$employeeName})" : $employeeName;
                            ?>
                            <strong><?= $displayName ?></strong>
                            <br><small class="text-muted"><?= $employeeRole ?></small>
                            <?php if (!empty($expense['paid_to_user_name'])): ?>
                            <br><small class="text-muted">→ Paid to: <strong><?= htmlspecialchars($expense['paid_to_user_name']) ?></strong></small>
                            <?php elseif (!empty($expense['paid_to_name'])): ?>
                            <br><small class="text-muted">→ Paid to: <strong><?= htmlspecialchars($expense['paid_to_name']) ?></strong></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($expense['description'] ?? '') ?></strong>
                            <br><small class="text-muted"><?= htmlspecialchars($expense['category'] ?? 'General') ?></small>
                        </td>
                        <td>
                            <strong>₹<?= number_format($expense['amount'] ?? 0, 2) ?></strong>
                        </td>
                        <td><?= !empty($expense['expense_date']) ? date('M d, Y', strtotime($expense['expense_date'])) : 'N/A' ?></td>
                        <td>
                            <?php 
                            $expenseStatus = $expense['status'] ?? 'pending';
                            $statusBadgeClass = match($expenseStatus) {
                                'approved' => 'badge--success',
                                'rejected' => 'badge--danger',
                                default => 'badge--warning'
                            };
                            ?>
                            <span class="badge <?= $statusBadgeClass ?>"><?= ucfirst($expenseStatus) ?></span>
                        </td>
                        <td>
                            <div class="ab-container">
                                <a class="ab-btn ab-btn--view" data-action="view" data-module="expenses" data-id="<?= $expense['id'] ?>" title="View Details">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14,2 14,8 20,8"/>
                                        <line x1="16" y1="13" x2="8" y2="13"/>
                                        <line x1="16" y1="17" x2="8" y2="17"/>
                                    </svg>
                                </a>
                                <?php if (($expense['status'] ?? 'pending') === 'pending' && ($expense['user_id'] ?? 0) == ($_SESSION['user_id'] ?? 0)): ?>
                                <button class="ab-btn ab-btn--edit" onclick="editExpense(<?= $expense['id'] ?>)" title="Edit Expense">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                        <path d="M15 5l4 4"/>
                                    </svg>
                                </button>
                                <?php endif; ?>
                                <?php 
                                $userRole = $user_role ?? '';
                                $expenseStatus = $expense['status'] ?? 'pending';
                                $isOwner = $userRole === 'owner';
                                $isAdmin = $userRole === 'admin';
                                $isPending = $expenseStatus === 'pending';
                                $isNotOwnExpense = ($expense['user_id'] ?? 0) != ($_SESSION['user_id'] ?? 0);
                                
                                $canApprove = $isPending && (($isOwner) || ($isAdmin && $isNotOwnExpense));
                                ?>
                                <?php if ($canApprove): ?>
                                <button class="ab-btn ab-btn--approve" onclick="showApprovalModal(<?= $expense['id'] ?>)" title="Approve Expense">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <polyline points="20,6 9,17 4,12"/>
                                    </svg>
                                </button>
                                <?php if (($isOwner) || ($isAdmin && $isNotOwnExpense)): ?>
                                <button class="ab-btn ab-btn--reject" onclick="showRejectModal(<?= $expense['id'] ?>)" title="Reject Expense">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <line x1="18" y1="6" x2="6" y2="18"/>
                                        <line x1="6" y1="6" x2="18" y2="18"/>
                                    </svg>
                                </button>
                                <?php endif; ?>
                                <?php endif; ?>
                                <?php if ($expenseStatus === 'approved' && ($expense['user_id'] ?? 0) != ($_SESSION['user_id'] ?? 0)): ?>
                                <button class="ab-btn ab-btn--mark-paid" onclick="showMarkPaidModal(<?= $expense['id'] ?>)" title="Mark as Paid">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M9 11l3 3l8-8"/>
                                        <path d="M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9s4.03-9 9-9c1.51 0 2.93.37 4.18 1.03"/>
                                    </svg>
                                </button>
                                <?php endif; ?>
                                <?php 
                                $canDelete = false;
                                if ($expense['user_id'] == $_SESSION['user_id'] && $expenseStatus === 'pending') {
                                    $canDelete = true; // Own pending expense
                                }
                                if ($canDelete): ?>
                                <button class="ab-btn ab-btn--delete" data-action="delete" data-module="expenses" data-id="<?= $expense['id'] ?>" data-name="Expense Claim" title="Delete Claim">
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

        <!-- ── Mobile accordion card list (hidden on desktop) ── -->
        <div class="exp-mobile-list" id="expMobileList">
            <?php if (empty($expenses ?? [])): ?>
            <div class="exp-empty">
                <div style="font-size:2.5rem;margin-bottom:8px">💰</div>
                <p>No expense claims yet.</p>
            </div>
            <?php else: ?>
                <?php foreach ($expenses as $i => $expense): ?>
                <?php
                $eStatus   = $expense['status'] ?? 'pending';
                $eBadge    = match($eStatus) { 'approved'=>'exp-badge--approved', 'rejected'=>'exp-badge--rejected', default=>'exp-badge--pending' };
                $eName     = htmlspecialchars($expense['user_name'] ?? 'Unknown');
                $eIsSelf   = ($expense['user_id'] ?? 0) == ($_SESSION['user_id'] ?? 0);
                $eDisplay  = $eIsSelf ? "Myself ({$eName})" : $eName;
                $eRole     = ucfirst($expense['user_role'] ?? 'user'); if($eRole==='User') $eRole='Employee';
                $eDesc     = htmlspecialchars($expense['description'] ?? '');
                $eCat      = htmlspecialchars($expense['category'] ?? 'General');
                $eAmt      = number_format($expense['amount'] ?? 0, 2);
                $eDate     = !empty($expense['expense_date']) ? date('d M Y', strtotime($expense['expense_date'])) : 'N/A';
                $eId       = (int)$expense['id'];

                $uRole     = $user_role ?? '';
                $isOwner   = $uRole === 'owner';
                $isAdmin   = $uRole === 'admin';
                $isPending = $eStatus === 'pending';
                $isNotOwn  = ($expense['user_id'] ?? 0) != ($_SESSION['user_id'] ?? 0);
                $canApprove = $isPending && ($isOwner || ($isAdmin && $isNotOwn));
                $canEdit   = $isPending && $eIsSelf;
                $canDelete = $eIsSelf && $isPending;
                $canPay    = $eStatus === 'approved' && $isNotOwn;
                ?>
                <div class="exp-card" id="expCard<?= $eId ?>">
                    <!-- Summary row (always visible) — tap to expand -->
                    <button class="exp-card__summary" onclick="expToggle(<?= $eId ?>)" aria-expanded="false" aria-controls="expDetail<?= $eId ?>">
                        <div class="exp-card__left">
                            <span class="exp-card__name"><?= $eDisplay ?></span>
                            <span class="exp-card__cat"><?= $eCat ?></span>
                        </div>
                        <div class="exp-card__right">
                            <span class="exp-card__amount">₹<?= $eAmt ?></span>
                            <span class="exp-badge <?= $eBadge ?>"><?= ucfirst($eStatus) ?></span>
                            <span class="exp-card__chevron" aria-hidden="true">›</span>
                        </div>
                    </button>
                    <!-- Detail panel (collapsed by default) -->
                    <div class="exp-card__detail" id="expDetail<?= $eId ?>" hidden>
                        <div class="exp-card__detail-row">
                            <span class="exp-card__detail-label">Description</span>
                            <span class="exp-card__detail-value"><?= $eDesc ?></span>
                        </div>
                        <div class="exp-card__detail-row">
                            <span class="exp-card__detail-label">Date</span>
                            <span class="exp-card__detail-value"><?= $eDate ?></span>
                        </div>
                        <div class="exp-card__detail-row">
                            <span class="exp-card__detail-label">Role</span>
                            <span class="exp-card__detail-value"><?= $eRole ?></span>
                        </div>
                        <?php if (!empty($expense['paid_to_user_name']) || !empty($expense['paid_to_name'])): ?>
                        <div class="exp-card__detail-row">
                            <span class="exp-card__detail-label">Paid To</span>
                            <span class="exp-card__detail-value"><?= htmlspecialchars($expense['paid_to_user_name'] ?? $expense['paid_to_name'] ?? '') ?></span>
                        </div>
                        <?php endif; ?>
                        <!-- Action buttons -->
                        <div class="exp-card__actions">
                            <a class="exp-act-btn exp-act-btn--view" href="/ergon/expenses/view/<?= $eId ?>">View</a>
                            <?php if ($canEdit): ?>
                            <button class="exp-act-btn exp-act-btn--edit" onclick="editExpense(<?= $eId ?>)">Edit</button>
                            <?php endif; ?>
                            <?php if ($canApprove): ?>
                            <button class="exp-act-btn exp-act-btn--approve" onclick="showApprovalModal(<?= $eId ?>)">Approve</button>
                            <button class="exp-act-btn exp-act-btn--reject" onclick="showRejectModal(<?= $eId ?>)">Reject</button>
                            <?php endif; ?>
                            <?php if ($canPay): ?>
                            <button class="exp-act-btn exp-act-btn--pay" onclick="showMarkPaidModal(<?= $eId ?>)">Mark Paid</button>
                            <?php endif; ?>
                            <?php if ($canDelete): ?>
                            <button class="exp-act-btn exp-act-btn--delete" data-action="delete" data-module="expenses" data-id="<?= $eId ?>" data-name="Expense Claim">Delete</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</div>



<!-- Approval Modal -->
<div id="approvalModal" class="modal-overlay" data-visible="false">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3>💰 Approve Expense Claim</h3>
            <span class="close" onclick="closeApprovalModal()">&times;</span>
        </div>
        <form id="approvalForm">
            <div class="modal-body">
                <div class="expense-details" id="expenseDetails">
                    <!-- Expense details will be loaded here -->
                </div>
                <div class="form-group">
                    <label for="approved_amount">Approved Amount (₹) *</label>
                    <input type="number" id="approved_amount" name="approved_amount" class="form-control" step="0.01" min="0.01" required>
                </div>
                <div class="form-group">
                    <label for="approval_remarks">Approval Remarks / Reason</label>
                    <textarea id="approval_remarks" name="approval_remarks" class="form-control" rows="3" placeholder="Enter reason for approval or any remarks..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--secondary" onclick="closeApprovalModal()">Cancel</button>
                <button type="submit" class="btn btn--success" id="approveBtn">✅ Approve Expense</button>
            </div>
        </form>
    </div>
</div>

<!-- Rejection Modal -->
<div id="rejectModal" class="modal-overlay" data-visible="false">
    <div class="modal-content" style="max-width: 500px;">
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



<script>
let currentExpenseId = null;

function showApprovalModal(expenseId) {
    currentExpenseId = expenseId;
    
    // Fetch expense details
    fetch(`/ergon/expenses/approve/${expenseId}`)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.expense) {
                const e = data.expense;
                
                // Populate expense details
                document.getElementById('expenseDetails').innerHTML = `
                    <div class="expense-info">
                        <div class="row">
                            <div class="col"><strong>Employee:</strong> ${e.user_name || 'Unknown'}</div>
                            <div class="col"><strong>Category:</strong> ${e.category || 'General'}</div>
                        </div>
                        <div class="row">
                            <div class="col"><strong>Claimed Amount:</strong> ₹${parseFloat(e.amount || 0).toFixed(2)}</div>
                            <div class="col"><strong>Expense Date:</strong> ${e.expense_date || 'N/A'}</div>
                        </div>
                        <div class="row">
                            <div class="col"><strong>Submitted Date:</strong> ${e.created_at ? new Date(e.created_at).toLocaleDateString() : 'N/A'}</div>
                            <div class="col"><strong>Status:</strong> <span class="badge badge--warning">Pending</span></div>
                        </div>
                        <div class="row">
                            <div class="col" style="grid-column: 1 / -1;"><strong>Description:</strong> ${e.description || 'No description'}</div>
                        </div>
                        ${e.attachment ? `<div class="row"><div class="col" style="grid-column: 1 / -1;"><strong>Receipt:</strong> <a href="/ergon/storage/receipts/${e.attachment}" target="_blank">View Receipt</a></div></div>` : ''}
                    </div>
                `;
                
                // Set default approved amount to claimed amount
                document.getElementById('approved_amount').value = parseFloat(e.amount || 0).toFixed(2);
                document.getElementById('approval_remarks').value = '';
                
                showModal('approvalModal');
            } else {
                alert('Error loading expense details: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(err => {
            alert('Error: ' + err.message);
        });
}

function closeApprovalModal() {
    hideModal('approvalModal');
    currentExpenseId = null;
}

function showRejectModal(expenseId) {
    document.getElementById('rejectForm').action = '/ergon/expenses/reject/' + expenseId;
    const reasonField = document.getElementById('rejection_reason');
    if (reasonField) reasonField.value = '';
    showModal('rejectModal');
}

function closeRejectModal() {
    hideModal('rejectModal');
}

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

// Handle approval form submission
document.getElementById('approvalForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!currentExpenseId) return;
    
    const btn = document.getElementById('approveBtn');
    btn.disabled = true;
    btn.textContent = '⏳ Approving...';
    
    const formData = new FormData(this);
    
    fetch(`/ergon/expenses/approve/${currentExpenseId}`, {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showSuccessMessage('✅ Expense approved successfully!');
            closeApprovalModal();
            setTimeout(() => location.reload(), 1500);
        } else {
            showErrorMessage('❌ Error: ' + (data.error || 'Approval failed'));
            btn.disabled = false;
            btn.textContent = '✅ Approve Expense';
        }
    })
    .catch(err => {
        showErrorMessage('❌ Error: ' + err.message);
        btn.disabled = false;
        btn.textContent = '✅ Approve Expense';
    });
});

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
</script>

<!-- Expense Modal -->
<div id="expenseModal" class="modal-overlay" data-visible="false">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="expenseModalTitle">💰 Submit Expense</h3>
            <button class="modal-close" onclick="closeExpenseModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="expenseForm" enctype="multipart/form-data">
                <input type="hidden" id="expense_id" name="expense_id">
                <?php if (in_array($user_role ?? '', ['owner','company_owner'])): ?>
                <div style="margin-bottom: 12px;">
                    <label>Paid To (Employee) <span style="color:#6b7280;font-weight:400;">(optional)</span></label>
                    <select id="paid_to_user_id" name="paid_to_user_id" class="form-input" onchange="togglePaidToOthersModal(this)">
                        <option value="">— Select Employee —</option>
                    </select>
                    <input type="text" id="paid_to_name_manual" name="paid_to_name_manual" class="form-input" placeholder="Enter recipient name" style="display:none; margin-top:8px;">
                </div>
                <?php endif; ?>
                <div class="form-row" style="display: flex; gap: 12px; margin-bottom: 12px;">
                    <div style="flex: 1;">
                        <label>Category *</label>
                        <select id="category" name="category" class="form-input" required>
                            <option value="">Select Category</option>
                            <option value="material_costs">🧱 Material Costs</option>
                            <option value="salary">💰 Salary</option>
                            <option value="equipment_machinery">⚙️ Equipment & Machinery Costs</option>
                            <option value="office_supplies">📋 Office Supplies</option>
                            <option value="contractor_subcontractor">👷 Contractor & Subcontractor Costs</option>
                            <option value="transportation_logistics">🚛 Transportation & Logistics</option>
                            <option value="medical_expenses">🏥 Medical Expenses</option>
                            <option value="food">🍽️ Food</option>
                            <option value="travel">✈️ Travel</option>
                            <option value="work_advance">💳 Work Advance</option>
                            <option value="utilities">⚡ Utilities</option>
                            <option value="maintenance_repairs">🔧 Maintenance & Repairs</option>
                            <option value="insurance">🛡️ Insurance</option>
                            <option value="legal_professional">⚖️ Legal & Professional Services</option>
                            <option value="marketing_advertising">📢 Marketing & Advertising</option>
                            <option value="training_development">📚 Training & Development</option>
                            <option value="others">📦 Others</option>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label>Project (Optional)</label>
                        <select id="project_id" name="project_id" class="form-input" onchange="loadExpSubcategories(this.value)">
                            <option value="">Select Project</option>
                        </select>
                    </div>
                </div>
                <div style="margin-bottom:12px;" id="exp_subcategory_group" style="display:none;">
                    <label>Work Category <span style="color:#6b7280;font-weight:400;">(optional)</span></label>
                    <select id="exp_subcategory_id" name="subcategory_id" class="form-input">
                        <option value="">-- Select work category --</option>
                    </select>
                </div>
                <div class="form-row" style="display: flex; gap: 12px; margin-bottom: 12px;">
                    <div style="flex: 1;">
                        <label>Amount (₹) *</label>
                        <input type="number" id="amount" name="amount" class="form-input" step="0.01" min="0.01" required>
                    </div>
                    <div style="flex: 1;">
                        <label>Expense Date *</label>
                        <input type="date" id="expense_date" name="expense_date" class="form-input" required>
                    </div>
                </div>
                <label>Receipt (Optional)</label>
                <input type="file" id="receipt" name="receipt" class="form-input" accept=".jpg,.jpeg,.png,.pdf" style="margin-bottom: 12px;">
                <label>Description *</label>
                <textarea id="description" name="description" class="form-input" rows="4" required></textarea>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn--secondary" onclick="closeExpenseModal()">Cancel</button>
            <button class="btn btn--primary" onclick="submitExpenseForm()" id="expenseSubmitBtn">💸 Submit Expense</button>
        </div>
    </div>
</div>

<script>
let isEditingExpense = false;

function showExpenseModal() {
    isEditingExpense = false;
    document.getElementById('expenseModalTitle').textContent = '💰 Submit Expense';
    document.getElementById('expenseSubmitBtn').textContent = '💸 Submit Expense';
    document.getElementById('expenseForm').reset();
    document.getElementById('expense_id').value = '';
    document.getElementById('expense_date').value = new Date().toISOString().split('T')[0];
    showModal('expenseModal');
    loadProjects('project_id');
    loadEmployeesForPaidTo();
}

function editExpense(id) {
    isEditingExpense = true;
    document.getElementById('expenseModalTitle').textContent = '💰 Edit Expense';
    document.getElementById('expenseSubmitBtn').textContent = '💾 Update Expense';
    showModal('expenseModal');
    
    fetch(`/ergon/api/expense.php?id=${id}`)
        .then(r => {
            if (!r.ok) throw new Error('Network response was not ok');
            return r.text();
        })
        .then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON response:', text);
                throw new Error('Invalid JSON response');
            }
        })
        .then(data => {
            if (data.success) {
                const e = data.expense;
                document.getElementById('expense_id').value = e.id;
                document.getElementById('category').value = e.category;
                document.getElementById('project_id').value = e.project_id || '';
                document.getElementById('amount').value = e.amount;
                document.getElementById('expense_date').value = e.expense_date;
                document.getElementById('description').value = e.description;
                loadProjects('project_id', e.project_id);
            }
        });
}

function closeExpenseModal() {
    hideModal('expenseModal');
}

function loadEmployeesForPaidTo() {
    const sel = document.getElementById('paid_to_user_id');
    if (!sel) return;
    fetch('/ergon/api/users')
        .then(r => r.json())
        .then(data => {
            sel.innerHTML = '<option value="">— Select Employee —</option>';
            if (data.success && data.users) {
                data.users.forEach(u => {
                    const opt = document.createElement('option');
                    opt.value = u.id;
                    opt.textContent = u.name + ' (' + u.role + ')';
                    sel.appendChild(opt);
                });
            }
            const othersOpt = document.createElement('option');
            othersOpt.value = 'others';
            othersOpt.textContent = '✏️ Others (enter name)';
            sel.appendChild(othersOpt);
        })
        .catch(() => {});
}

function togglePaidToOthersModal(sel) {
    const input = document.getElementById('paid_to_name_manual');
    if (!input) return;
    input.style.display = sel.value === 'others' ? 'block' : 'none';
    input.required = sel.value === 'others';
    if (sel.value !== 'others') input.value = '';
}

function loadExpSubcategories(projectId) {
    const group = document.getElementById('exp_subcategory_group');
    const sel   = document.getElementById('exp_subcategory_id');
    sel.innerHTML = '<option value="">-- Select work category --</option>';
    if (!projectId) { group.style.display = 'none'; return; }
    fetch('/ergon/api/project-subcategories/' + projectId)
        .then(r => r.json())
        .then(data => {
            if (data.length) {
                data.forEach(s => {
                    const o = document.createElement('option');
                    o.value = s.id;
                    o.textContent = s.name + (s.budget > 0 ? ' (₹' + parseFloat(s.budget).toLocaleString() + ')' : '');
                    sel.appendChild(o);
                });
                group.style.display = 'block';
            } else {
                group.style.display = 'none';
            }
        })
        .catch(() => { group.style.display = 'none'; });
}

function loadProjects(selectId, selectedId = null) {
    fetch('/ergon/api/projects.php')
        .then(r => {
            if (!r.ok) throw new Error('Network response was not ok');
            return r.text();
        })
        .then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON response:', text);
                throw new Error('Invalid JSON response');
            }
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
        });
}

function submitExpenseForm() {
    const form = document.getElementById('expenseForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const btn = document.getElementById('expenseSubmitBtn');
    btn.disabled = true;
    btn.textContent = '⏳ Submitting...';
    
    const formData = new FormData(form);
    const expenseId = formData.get('expense_id');
    const url = isEditingExpense && expenseId ? `/ergon/expenses/edit/${expenseId}` : '/ergon/expenses/create';
    
    fetch(url, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showSuccessMessage('✅ Expense ' + (isEditingExpense ? 'updated' : 'submitted') + ' successfully!');
                closeExpenseModal();
                setTimeout(() => location.reload(), 1500);
            } else {
                showErrorMessage('❌ Error: ' + data.error);
                btn.disabled = false;
                btn.textContent = isEditingExpense ? '💾 Update Expense' : '💸 Submit Expense';
            }
        })
        .catch(err => {
            showErrorMessage('❌ Error: ' + err.message);
            btn.disabled = false;
            btn.textContent = isEditingExpense ? '💾 Update Expense' : '💸 Submit Expense';
        });
}
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

<style>
@keyframes slideInRight {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
@keyframes slideOutRight {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(100%); opacity: 0; }
}
</style>

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

<!-- Scroll-to-top FAB (mobile only, shown by JS) -->
<button class="exp-scroll-top" id="expScrollTop" aria-label="Scroll to top" onclick="window.scrollTo({top:0,behavior:'smooth'})">
    &#8679;
</button>

<script>
// ── Accordion toggle ─────────────────────────────────────────────────
function expToggle(id) {
    const card   = document.getElementById('expCard' + id);
    const detail = document.getElementById('expDetail' + id);
    const btn    = card ? card.querySelector('.exp-card__summary') : null;
    if (!card || !detail) return;

    const isOpen = !detail.hidden;
    // Close all other open cards first
    document.querySelectorAll('.exp-card.is-open').forEach(function(c) {
        if (c !== card) {
            c.classList.remove('is-open');
            const d = c.querySelector('.exp-card__detail');
            const b = c.querySelector('.exp-card__summary');
            if (d) d.hidden = true;
            if (b) b.setAttribute('aria-expanded', 'false');
        }
    });

    // Toggle current card
    detail.hidden = isOpen;
    card.classList.toggle('is-open', !isOpen);
    if (btn) btn.setAttribute('aria-expanded', String(!isOpen));

    // Scroll card into view smoothly when opening
    if (!isOpen) {
        setTimeout(function() {
            card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 50);
    }
}

// ── Scroll-to-top FAB visibility ─────────────────────────────────
(function() {
    var fab = document.getElementById('expScrollTop');
    if (!fab) return;
    var threshold = 300;
    function onScroll() {
        if (window.scrollY > threshold) {
            fab.classList.add('visible');
        } else {
            fab.classList.remove('visible');
        }
    }
    window.addEventListener('scroll', onScroll, { passive: true });
})();

// ── Delete button handler for mobile cards ──────────────────────
document.addEventListener('click', function(e) {
    var btn = e.target.closest('.exp-act-btn--delete');
    if (!btn) return;
    var id   = btn.dataset.id;
    var name = btn.dataset.name || 'Expense Claim';
    if (id) deleteRecord('expenses', id, name);
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>

