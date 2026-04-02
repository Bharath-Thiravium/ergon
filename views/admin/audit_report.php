<?php
$title = 'UI Audit Report';
$active_page = 'audit';
ob_start();

// Collect live diagnostics
$issues = [];

// ── 1. modal.css checks ──────────────────────────────────────────────────────
$modalCss = file_get_contents(__DIR__ . '/../../assets/css/modal.css');

if (strpos($modalCss, 'top: 120px') !== false || strpos($modalCss, 'top: 110px') !== false) {
    $issues[] = [
        'id'       => 'MOD-001',
        'severity' => 'critical',
        'module'   => 'modal.css',
        'title'    => 'Overlay uses fixed top offset instead of full-viewport coverage',
        'detail'   => 'top: 120px / top: 110px clips the overlay. On small screens the modal is centred inside a partial box, pushing the footer off-screen.',
        'fix'      => 'Set top:0 on .modal-overlay and use padding-top:120px to clear the header.',
        'status'   => 'open',
    ];
}

if (strpos($modalCss, 'overflow-y: auto') !== false && strpos($modalCss, 'flex-direction: column') === false) {
    $issues[] = [
        'id'       => 'MOD-002',
        'severity' => 'critical',
        'module'   => 'modal.css',
        'title'    => 'Entire .modal-content scrolls — footer scrolls off-screen',
        'detail'   => 'overflow-y:auto on .modal-content means header+body+footer all scroll together. When content is tall the footer (submit button) disappears below the fold.',
        'fix'      => 'Make .modal-content display:flex;flex-direction:column. Give .modal-body overflow-y:auto;flex:1. Give .modal-header and .modal-footer flex-shrink:0.',
        'status'   => 'open',
    ];
}

if (strpos($modalCss, 'color: var(--bg-primary') !== false) {
    $issues[] = [
        'id'       => 'MOD-003',
        'severity' => 'high',
        'module'   => 'modal.css',
        'title'    => 'Form input text is invisible — color set to background color',
        'detail'   => '.modal-body .form-control { color: var(--bg-primary, #fff) } — text colour equals background colour, making typed text invisible in light mode.',
        'fix'      => 'Change to color: var(--text-primary, #000)',
        'status'   => 'open',
    ];
}

if (strpos($modalCss, 'max-height: calc(100vh - 130px)') !== false) {
    $issues[] = [
        'id'       => 'MOD-004',
        'severity' => 'high',
        'module'   => 'modal.css (@media ≤640px)',
        'title'    => 'Mobile max-height too tight — cuts off footer on small phones',
        'detail'   => 'calc(100vh - 130px) on a 568px iPhone SE = 438px. Expense form alone needs ~530px (7 fields × 60px + header 50px + footer 60px). Footer is clipped.',
        'fix'      => 'Use calc(100svh - 90px) and let .modal-body scroll internally.',
        'status'   => 'open',
    ];
}

// ── 2. advances/index.php checks ────────────────────────────────────────────
$advHtml = file_get_contents(__DIR__ . '/../advances/index.php');

if (strpos($advHtml, "style.display = 'flex'") !== false && strpos($advHtml, "showModal(") === false) {
    $issues[] = [
        'id'       => 'MOD-005',
        'severity' => 'critical',
        'module'   => 'advances/index.php',
        'title'    => 'Advance modals bypass showModal() — use raw style.display instead',
        'detail'   => 'showApprovalModal(), showRejectModal(), showMarkPaidModal(), showAdvanceModal() all set element.style.display="flex" directly instead of calling showModal(id). This skips body.modal-open locking and the CSS [data-visible="true"] selector, so the overlay renders as a block-level element in the page flow rather than a fixed overlay — causing it to appear below the page content instead of on top.',
        'fix'      => 'Replace all element.style.display="flex" / setAttribute("data-visible","true") calls with showModal(id). Replace all style.display="none" / setAttribute("data-visible","false") with hideModal(id).',
        'status'   => 'open',
    ];
}

// ── 3. expenses/index.php checks ────────────────────────────────────────────
$expHtml = file_get_contents(__DIR__ . '/../expenses/index.php');

if (strpos($expHtml, 'card__body') !== false) {
    // Check for double card__body
    $count = substr_count($expHtml, 'card__body');
    if ($count >= 4) { // 2 opening + 2 closing = 4 occurrences minimum
        $issues[] = [
            'id'       => 'EXP-001',
            'severity' => 'medium',
            'module'   => 'expenses/index.php',
            'title'    => 'Duplicate nested <div class="card__body"> tags',
            'detail'   => 'The expense table is wrapped in two nested .card__body divs. This adds extra padding and can cause layout shifts.',
            'fix'      => 'Remove the inner duplicate <div class="card__body">.',
            'status'   => 'open',
        ];
    }
}

// ── 4. ergon.css mobile btn override check ──────────────────────────────────
$ergonCss = file_get_contents(__DIR__ . '/../../assets/css/ergon.css');

if (strpos($ergonCss, 'position: sticky') !== false && strpos($ergonCss, '.page-actions') !== false) {
    $issues[] = [
        'id'       => 'CSS-001',
        'severity' => 'high',
        'module'   => 'ergon.css (@media ≤768px)',
        'title'    => '.page-actions made sticky-bottom — breaks filter forms and inline action rows',
        'detail'   => 'The mobile rule sets .page-actions { position:sticky; bottom:0; background:white; border-top:... } which turns ALL page-actions (including filter forms, card headers) into sticky footers.',
        'fix'      => 'Override in responsive-mobile.css: .page-actions { position:static !important; background:transparent !important; border-top:none !important; }',
        'status'   => 'open',
    ];
}

if (strpos($ergonCss, 'width: 100%') !== false && strpos($ergonCss, '.btn {') !== false) {
    $issues[] = [
        'id'       => 'CSS-002',
        'severity' => 'high',
        'module'   => 'ergon.css (@media ≤768px)',
        'title'    => 'All .btn forced to width:100% on mobile — breaks table action buttons',
        'detail'   => 'The mobile rule sets .btn { width:100% } globally. This makes table action buttons, modal footer buttons, and filter buttons all stretch full-width.',
        'fix'      => 'Change to width:auto !important globally, only apply width:100% to .btn--block, form>.btn, .form-group>.btn.',
        'status'   => 'open',
    ];
}

// ── 5. responsive-mobile.css check ──────────────────────────────────────────
$respCss = @file_get_contents(__DIR__ . '/../../assets/css/responsive-mobile.css') ?: '';
$hasConsolidatedFix = strpos($respCss, 'ERGON RESPONSIVE FIXES') !== false;

if (!$hasConsolidatedFix) {
    $issues[] = [
        'id'       => 'CSS-003',
        'severity' => 'medium',
        'module'   => 'responsive-mobile.css',
        'title'    => 'Consolidated responsive override block missing',
        'detail'   => 'The responsive-mobile.css does not contain the consolidated fix block that overrides the broken ergon.css mobile rules.',
        'fix'      => 'Add the ERGON RESPONSIVE FIXES block to the top of responsive-mobile.css.',
        'status'   => 'open',
    ];
}

// ── Tally ────────────────────────────────────────────────────────────────────
$counts = ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0];
foreach ($issues as $i) $counts[$i['severity']]++;
$total = count($issues);
?>

<style>
.audit-hero{background:linear-gradient(135deg,#1e293b 0%,#0f172a 100%);color:#fff;padding:2rem;border-radius:12px;margin-bottom:1.5rem}
.audit-hero h1{margin:0 0 .5rem;font-size:1.5rem}
.audit-hero p{margin:0;color:#94a3b8;font-size:.9rem}
.audit-meta{display:flex;gap:1rem;margin-top:1rem;flex-wrap:wrap}
.audit-meta span{background:rgba(255,255,255,.1);padding:.25rem .75rem;border-radius:20px;font-size:.8rem}

.severity-bar{display:flex;gap:.75rem;margin-bottom:1.5rem;flex-wrap:wrap}
.sev-card{flex:1;min-width:120px;padding:1rem;border-radius:8px;text-align:center}
.sev-card__count{font-size:2rem;font-weight:800;line-height:1}
.sev-card__label{font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-top:.25rem}
.sev-critical{background:#fef2f2;border:2px solid #fca5a5;color:#dc2626}
.sev-high{background:#fff7ed;border:2px solid #fed7aa;color:#ea580c}
.sev-medium{background:#fefce8;border:2px solid #fde68a;color:#ca8a04}
.sev-low{background:#f0fdf4;border:2px solid #bbf7d0;color:#16a34a}
.sev-total{background:#f8fafc;border:2px solid #e2e8f0;color:#475569}

.issue-card{background:#fff;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:1rem;overflow:hidden}
.issue-card__header{display:flex;align-items:center;gap:.75rem;padding:.875rem 1rem;cursor:pointer;user-select:none}
.issue-card__header:hover{background:#f8fafc}
.issue-id{font-family:monospace;font-size:.75rem;font-weight:700;padding:.2rem .5rem;border-radius:4px;background:#f1f5f9;color:#475569;white-space:nowrap}
.issue-title{flex:1;font-weight:600;font-size:.9rem;color:#1e293b}
.issue-module{font-size:.75rem;color:#64748b;background:#f1f5f9;padding:.2rem .5rem;border-radius:4px;white-space:nowrap}
.sev-badge{font-size:.7rem;font-weight:700;text-transform:uppercase;padding:.2rem .6rem;border-radius:20px;white-space:nowrap}
.sev-badge.critical{background:#fef2f2;color:#dc2626;border:1px solid #fca5a5}
.sev-badge.high{background:#fff7ed;color:#ea580c;border:1px solid #fed7aa}
.sev-badge.medium{background:#fefce8;color:#ca8a04;border:1px solid #fde68a}
.sev-badge.low{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0}
.chevron{font-size:.75rem;color:#94a3b8;transition:transform .2s}
.issue-card__body{display:none;padding:1rem;border-top:1px solid #f1f5f9;background:#fafafa}
.issue-card__body.open{display:block}
.issue-section{margin-bottom:.875rem}
.issue-section:last-child{margin-bottom:0}
.issue-section-label{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:.35rem}
.issue-section-text{font-size:.875rem;color:#334155;line-height:1.6}
.fix-box{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;padding:.75rem;font-size:.85rem;color:#166534;line-height:1.6}
.status-open{display:inline-flex;align-items:center;gap:.35rem;font-size:.75rem;font-weight:600;color:#dc2626}
.status-open::before{content:'●';font-size:.6rem}

.filter-bar{display:flex;gap:.5rem;margin-bottom:1rem;flex-wrap:wrap}
.filter-btn{padding:.35rem .875rem;border:1px solid #e2e8f0;border-radius:20px;background:#fff;font-size:.8rem;cursor:pointer;transition:all .15s}
.filter-btn:hover,.filter-btn.active{background:#1e293b;color:#fff;border-color:#1e293b}
.filter-btn.f-critical.active{background:#dc2626;border-color:#dc2626}
.filter-btn.f-high.active{background:#ea580c;border-color:#ea580c}
.filter-btn.f-medium.active{background:#ca8a04;border-color:#ca8a04}

.no-issues{text-align:center;padding:3rem;color:#94a3b8;font-size:.9rem}

@media(max-width:640px){
    .audit-meta{gap:.5rem}
    .severity-bar{gap:.5rem}
    .sev-card{min-width:80px;padding:.75rem .5rem}
    .sev-card__count{font-size:1.5rem}
    .issue-card__header{flex-wrap:wrap;gap:.5rem}
    .issue-title{width:100%;order:-1}
}
</style>

<div class="audit-hero">
    <h1>🔍 UI Audit Report</h1>
    <p>Automated analysis of modal, CSS, and responsiveness issues across Expense &amp; Advance modules.</p>
    <div class="audit-meta">
        <span>📅 Generated: <?= date('d M Y, H:i') ?> IST</span>
        <span>🔎 <?= $total ?> issue<?= $total !== 1 ? 's' : '' ?> found</span>
        <span>📁 Files scanned: modal.css, ergon.css, responsive-mobile.css, expenses/index.php, advances/index.php</span>
    </div>
</div>

<!-- Severity Summary -->
<div class="severity-bar">
    <div class="sev-card sev-total">
        <div class="sev-card__count"><?= $total ?></div>
        <div class="sev-card__label">Total</div>
    </div>
    <div class="sev-card sev-critical">
        <div class="sev-card__count"><?= $counts['critical'] ?></div>
        <div class="sev-card__label">Critical</div>
    </div>
    <div class="sev-card sev-high">
        <div class="sev-card__count"><?= $counts['high'] ?></div>
        <div class="sev-card__label">High</div>
    </div>
    <div class="sev-card sev-medium">
        <div class="sev-card__count"><?= $counts['medium'] ?></div>
        <div class="sev-card__label">Medium</div>
    </div>
    <div class="sev-card sev-low">
        <div class="sev-card__count"><?= $counts['low'] ?></div>
        <div class="sev-card__label">Low</div>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar">
    <button class="filter-btn active" onclick="filterIssues('all', this)">All (<?= $total ?>)</button>
    <button class="filter-btn f-critical" onclick="filterIssues('critical', this)">🔴 Critical (<?= $counts['critical'] ?>)</button>
    <button class="filter-btn f-high" onclick="filterIssues('high', this)">🟠 High (<?= $counts['high'] ?>)</button>
    <button class="filter-btn f-medium" onclick="filterIssues('medium', this)">🟡 Medium (<?= $counts['medium'] ?>)</button>
    <button class="filter-btn" onclick="expandAll()">Expand All</button>
    <button class="filter-btn" onclick="collapseAll()">Collapse All</button>
</div>

<!-- Issue Cards -->
<div id="issueList">
<?php if (empty($issues)): ?>
    <div class="no-issues">✅ No issues detected. All checks passed.</div>
<?php else: ?>
    <?php foreach ($issues as $idx => $issue): ?>
    <div class="issue-card" data-severity="<?= $issue['severity'] ?>">
        <div class="issue-card__header" onclick="toggleIssue(<?= $idx ?>)">
            <span class="issue-id"><?= htmlspecialchars($issue['id']) ?></span>
            <span class="sev-badge <?= $issue['severity'] ?>"><?= ucfirst($issue['severity']) ?></span>
            <span class="issue-title"><?= htmlspecialchars($issue['title']) ?></span>
            <span class="issue-module"><?= htmlspecialchars($issue['module']) ?></span>
            <span class="chevron" id="chev-<?= $idx ?>">▼</span>
        </div>
        <div class="issue-card__body" id="body-<?= $idx ?>">
            <div class="issue-section">
                <div class="issue-section-label">Problem</div>
                <div class="issue-section-text"><?= htmlspecialchars($issue['detail']) ?></div>
            </div>
            <div class="issue-section">
                <div class="issue-section-label">Fix</div>
                <div class="fix-box">✅ <?= htmlspecialchars($issue['fix']) ?></div>
            </div>
            <div class="issue-section">
                <div class="issue-section-label">Status</div>
                <span class="status-open">Open — needs fix</span>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<!-- Known Issues Summary Table -->
<div class="card" style="margin-top:1.5rem">
    <div class="card__header">
        <h2 class="card__title">📋 Issue Summary Table</h2>
    </div>
    <div class="card__body" style="padding:0">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Severity</th>
                        <th>Module</th>
                        <th>Title</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($issues as $issue): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($issue['id']) ?></code></td>
                        <td><span class="sev-badge <?= $issue['severity'] ?>"><?= ucfirst($issue['severity']) ?></span></td>
                        <td><small><?= htmlspecialchars($issue['module']) ?></small></td>
                        <td><?= htmlspecialchars($issue['title']) ?></td>
                        <td><span class="status-open">Open</span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function toggleIssue(idx) {
    const body = document.getElementById('body-' + idx);
    const chev = document.getElementById('chev-' + idx);
    const open = body.classList.toggle('open');
    chev.style.transform = open ? 'rotate(180deg)' : '';
}

function expandAll() {
    document.querySelectorAll('.issue-card__body').forEach((b, i) => {
        b.classList.add('open');
        const chev = document.getElementById('chev-' + i);
        if (chev) chev.style.transform = 'rotate(180deg)';
    });
}

function collapseAll() {
    document.querySelectorAll('.issue-card__body').forEach((b, i) => {
        b.classList.remove('open');
        const chev = document.getElementById('chev-' + i);
        if (chev) chev.style.transform = '';
    });
}

function filterIssues(severity, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.issue-card').forEach(card => {
        card.style.display = (severity === 'all' || card.dataset.severity === severity) ? '' : 'none';
    });
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
