<?php $role = $_SESSION['role'] ?? 'user'; ?>
<style>
.report-row { display:grid; grid-template-columns:90px 1fr 80px 100px 80px; gap:.75rem; align-items:center; padding:.75rem 1rem; border-bottom:1px solid #f1f5f9; font-size:.875rem; }
.report-row:hover { background:#f8fafc; }
.report-row .date { font-weight:600; color:#1e293b; }
.report-row .site { color:#475569; }
.report-row .mp-badge { background:#dbeafe; color:#1d4ed8; border-radius:20px; padding:.15rem .6rem; font-weight:600; font-size:.8rem; text-align:center; }
.report-row .exp-badge { background:#fef3c7; color:#92400e; border-radius:20px; padding:.15rem .6rem; font-size:.8rem; text-align:center; }
.report-row .pending-dot { width:8px; height:8px; background:#f59e0b; border-radius:50%; display:inline-block; margin-right:.25rem; }
@media(max-width:640px) { .report-row { grid-template-columns:80px 1fr 60px; } .report-row .exp-badge,.report-row .view-link { display:none; } }
</style>

<div class="page-header-modern">
    <div class="page-header-content">
        <h1 class="page-title">📋 Site Daily Reports</h1>
        <div style="display:flex;gap:.5rem">
            <?php if (in_array($role, ['admin', 'owner', 'company_owner'], true)): ?>
            <a href="/ergon/site-reports/summary" class="btn btn--secondary btn--sm">📊 Summary</a>
            <?php endif; ?>
            <a href="/ergon/site-reports/create" class="btn btn--primary btn--sm">+ Submit Report</a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card__body" style="padding:0">
        <?php if (empty($reports)): ?>
        <div style="padding:2rem;text-align:center;color:#94a3b8">
            No reports yet. <a href="/ergon/site-reports/create">Submit the first one →</a>
        </div>
        <?php else: ?>
        <div class="report-row" style="background:#f8fafc;font-weight:600;font-size:.75rem;text-transform:uppercase;color:#64748b">
            <div>Date</div><div>Site / Project</div><div>Manpower</div><div>Expenses</div><div></div>
        </div>
        <?php foreach ($reports as $r):
            $pendingExp = 0; // could add a subquery for this
        ?>
        <div class="report-row">
            <div class="date"><?= date('d M Y', strtotime($r['report_date'])) ?></div>
            <div>
                <div class="site"><?= htmlspecialchars($r['site_name']) ?></div>
                <?php if ($r['project_name']): ?>
                <small style="color:#94a3b8"><?= htmlspecialchars($r['project_name']) ?></small>
                <?php endif; ?>
                <small style="color:#94a3b8"> · <?= htmlspecialchars($r['submitted_by_name']) ?></small>
            </div>
            <div><span class="mp-badge">👷 <?= $r['total_manpower'] ?></span></div>
            <div>
                <?php if ($r['total_expenses_requested'] > 0): ?>
                <span class="exp-badge">₹<?= number_format($r['total_expenses_requested'], 0) ?></span>
                <?php endif; ?>
            </div>
            <div><a href="/ergon/site-reports/view/<?= $r['id'] ?>" class="btn btn--secondary btn--xs view-link">View</a></div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
