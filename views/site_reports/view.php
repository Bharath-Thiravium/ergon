<?php
$mpLabels = ['engineer'=>'Engineers','supervisor'=>'Supervisors','ac_dc_team'=>'AC & DC Team',
             'mms_team'=>'MMS Team','civil_mason'=>'Civil / Mason','local_labour'=>'Local Labour',
             'driver_operator'=>'Drivers / Operators','other'=>'Other'];
$mpIcons  = ['engineer'=>'👷','supervisor'=>'🧑‍💼','ac_dc_team'=>'⚡','mms_team'=>'🔧',
             'civil_mason'=>'🧱','local_labour'=>'👥','driver_operator'=>'🧑‍🔧','other'=>'👤'];
$machLabels = ['tractor'=>'Tractor','jcb'=>'JCB','hydra'=>'Hydra','tata_ace'=>'Tata Ace',
               'dg'=>'DG','crane'=>'Crane','other'=>'Other'];
$role = $_SESSION['role'] ?? 'user';
?>
<style>
.report-card { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:1.5rem; max-width:680px; margin:0 auto 1rem; }
.report-header { text-align:center; border-bottom:2px solid #f1f5f9; padding-bottom:1rem; margin-bottom:1.25rem; }
.report-header h2 { font-size:1.1rem; margin:0 0 .25rem; }
.report-header .meta { font-size:.85rem; color:#64748b; }
.section-title { font-weight:600; font-size:.9rem; margin:1rem 0 .5rem; color:#1e293b; }
.mp-item { display:flex; justify-content:space-between; align-items:flex-start; padding:.35rem 0; border-bottom:1px solid #f8fafc; font-size:.875rem; }
.mp-item .names { color:#64748b; font-size:.8rem; margin-top:.15rem; }
.badge-count { background:#f1f5f9; border-radius:20px; padding:.1rem .6rem; font-weight:600; font-size:.8rem; }
.mach-grid { display:flex; flex-wrap:wrap; gap:.5rem; }
.mach-chip { background:#f8fafc; border:1px solid #e2e8f0; border-radius:20px; padding:.25rem .75rem; font-size:.8rem; }
.mach-chip.active { background:#dbeafe; border-color:#93c5fd; color:#1d4ed8; }
.task-list { list-style:none; padding:0; margin:0; }
.task-list li { padding:.3rem 0; font-size:.875rem; border-bottom:1px solid #f8fafc; }
.task-list li::before { content:'✅ '; }
.exp-table { width:100%; border-collapse:collapse; font-size:.85rem; }
.exp-table th { background:#f8fafc; padding:.5rem .75rem; text-align:left; font-weight:600; }
.exp-table td { padding:.5rem .75rem; border-bottom:1px solid #f1f5f9; }
.exp-total { font-weight:700; text-align:right; padding:.5rem .75rem; }
.status-badge { display:inline-block; padding:.15rem .6rem; border-radius:20px; font-size:.75rem; font-weight:600; }
.status-pending  { background:#fef3c7; color:#92400e; }
.status-approved { background:#d1fae5; color:#065f46; }
.status-rejected { background:#fee2e2; color:#991b1b; }
.status-processed{ background:#dbeafe; color:#1e40af; }
.total-manpower-box { background:#1e293b; color:#fff; border-radius:8px; padding:.75rem 1rem; text-align:center; margin-top:1rem; }
.total-manpower-box .num { font-size:2rem; font-weight:700; }
.print-btn { display:none; }
@media print { .print-btn,.page-header-modern,.btn { display:none!important; } .report-card { border:none; box-shadow:none; } }
</style>

<div class="page-header-modern">
    <div class="page-header-content">
        <h1 class="page-title">📋 Site Report</h1>
        <div style="display:flex;gap:.5rem">
            <button onclick="window.print()" class="btn btn--secondary btn--sm">🖨 Print</button>
            <a href="/ergon/site-reports" class="btn btn--secondary btn--sm">← Back</a>
        </div>
    </div>
</div>

<div class="report-card">
    <!-- Header -->
    <div class="report-header">
        <div style="font-size:1.5rem">📋</div>
        <h2>Daily Manpower & Machinery Report</h2>
        <div class="meta">
            <strong>Company:</strong> <?= htmlspecialchars($report['project_name'] ?? 'N/A') ?><br>
            <strong>Date:</strong> <?= date('d/m/Y', strtotime($report['report_date'])) ?><br>
            <strong>Site / Project:</strong> <?= htmlspecialchars($report['site_name']) ?><br>
            <strong>Submitted by:</strong> <?= htmlspecialchars($report['submitted_by_name']) ?>
            at <?= date('h:i a', strtotime($report['created_at'])) ?>
        </div>
    </div>

    <!-- Manpower -->
    <div class="section-title">👷 Manpower</div>
    <?php foreach ($manpower as $mp):
        $names = $mp['names'] ? json_decode($mp['names'], true) : [];
    ?>
    <div class="mp-item">
        <div>
            <?= $mpIcons[$mp['category']] ?? '👤' ?>
            <?= $mpLabels[$mp['category']] ?? $mp['category'] ?>
            <?php if ($names): ?>
            <div class="names"><?= implode(', ', array_map('htmlspecialchars', $names)) ?></div>
            <?php endif; ?>
        </div>
        <span class="badge-count"><?= $mp['count'] ?></span>
    </div>
    <?php endforeach; ?>

    <div class="total-manpower-box">
        <div style="font-size:.8rem;opacity:.7">TOTAL MANPOWER</div>
        <div class="num"><?= $report['total_manpower'] ?></div>
    </div>

    <!-- Machinery -->
    <?php if ($machinery): ?>
    <div class="section-title">🚜 Machinery</div>
    <div class="mach-grid">
        <?php foreach ($machinery as $m): ?>
        <div class="mach-chip <?= $m['count'] > 0 ? 'active' : '' ?>">
            <?= $machLabels[$m['machine_type']] ?? $m['machine_type'] ?>-<?= $m['count'] ?>
            <?php if ($m['hours_worked']): ?> | <?= $m['hours_worked'] ?>h<?php endif; ?>
            <?php if ($m['fuel_litres']): ?> | <?= $m['fuel_litres'] ?>L<?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Tasks -->
    <?php if ($tasks): ?>
    <div class="section-title">📌 Today's Tasks</div>
    <ul class="task-list">
        <?php foreach ($tasks as $i => $t): ?>
        <li><?= ($i+1) ?>. <?= htmlspecialchars($t['task_description']) ?></li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <!-- Expenses -->
    <?php if ($expenses): ?>
    <div class="section-title">💰 Expense Requests</div>
    <table class="exp-table">
        <thead><tr><th>Description</th><th>Amount</th><th>Type</th><th>Status</th>
        <?php if ($role === 'admin' || $role === 'company_owner'): ?><th>Action</th><?php endif; ?>
        </tr></thead>
        <tbody>
        <?php $total = 0; foreach ($expenses as $exp): $total += $exp['amount']; ?>
        <tr>
            <td><?= htmlspecialchars($exp['description']) ?></td>
            <td>₹<?= number_format($exp['amount'], 0) ?></td>
            <td><?= ucfirst(str_replace('_',' ',$exp['expense_type'])) ?></td>
            <td><span class="status-badge status-<?= $exp['status'] ?>"><?= ucfirst($exp['status']) ?></span></td>
            <?php if ($role === 'admin' || $role === 'company_owner'): ?>
            <td>
                <?php if ($exp['status'] === 'pending'): ?>
                <button class="btn btn--success btn--xs" onclick="approveExpense(<?= $exp['id'] ?>,'approved')">✓</button>
                <button class="btn btn--danger btn--xs" onclick="approveExpense(<?= $exp['id'] ?>,'rejected')">✕</button>
                <?php endif; ?>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="exp-total">Total: ₹<?= number_format($total, 0) ?></div>
    <?php endif; ?>

    <!-- Remarks -->
    <?php if ($report['remarks']): ?>
    <div class="section-title">📝 Remarks</div>
    <p style="font-size:.875rem;color:#475569"><?= nl2br(htmlspecialchars($report['remarks'])) ?></p>
    <?php endif; ?>
</div>

<script>
function approveExpense(id, action) {
    if (!confirm(action === 'approved' ? 'Approve this expense?' : 'Reject this expense?')) return;
    fetch('/ergon/site-reports/expense/approve', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
        body: `expense_id=${id}&action=${action}`
    }).then(r => r.json()).then(d => { if (d.success) location.reload(); });
}
</script>
