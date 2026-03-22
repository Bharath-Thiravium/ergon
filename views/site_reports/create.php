<?php
$machines  = ['tractor'=>'Tractor','jcb'=>'JCB','hydra'=>'Hydra','tata_ace'=>'Tata Ace','dg'=>'DG (Generator)','crane'=>'Crane','other'=>'Other'];
$mpCats    = ['engineer'=>'Engineers','supervisor'=>'Supervisors','ac_dc_team'=>'AC & DC Team','mms_team'=>'MMS Team','civil_mason'=>'Civil / Mason Team','local_labour'=>'Local Labour','driver_operator'=>'Drivers / Operators','other'=>'Other'];
$expTypes  = ['labour'=>'Labour Payment','machinery'=>'Machinery','transport'=>'Transport','fuel'=>'Fuel','site_expense'=>'Site Expense','advance'=>'Advance','other'=>'Other'];
?>
<style>
.sr-section { background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:1.25rem; margin-bottom:1rem; }
.sr-section h3 { font-size:.85rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:#64748b; margin:0 0 1rem; }
.sr-grid { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; }
.sr-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:.75rem; }
.mp-row { display:grid; grid-template-columns:160px 80px 1fr; gap:.5rem; align-items:start; margin-bottom:.5rem; }
.mp-label { font-size:.875rem; font-weight:500; padding-top:.4rem; }
.exp-row { display:grid; grid-template-columns:1fr 120px 140px 36px; gap:.5rem; align-items:center; margin-bottom:.5rem; }
.task-row { display:flex; gap:.5rem; align-items:center; margin-bottom:.5rem; }
.task-row input { flex:1; }
.btn-add { background:none; border:1px dashed #94a3b8; border-radius:6px; padding:.3rem .75rem; font-size:.8rem; color:#64748b; cursor:pointer; }
.btn-add:hover { border-color:#3b82f6; color:#3b82f6; }
.remove-btn { background:none; border:none; color:#ef4444; cursor:pointer; font-size:1rem; padding:0 .25rem; }
@media(max-width:640px) { .sr-grid,.sr-grid-3 { grid-template-columns:1fr; } .mp-row { grid-template-columns:1fr 70px; } .mp-row .names-col { grid-column:1/-1; } .exp-row { grid-template-columns:1fr 100px; } .exp-row select,.exp-row .remove-btn { grid-column:1; } }
</style>

<div class="page-header-modern">
    <div class="page-header-content">
        <h1 class="page-title">📋 Submit Daily Site Report</h1>
        <a href="/ergon/site-reports" class="btn btn--secondary btn--sm">← Back</a>
    </div>
</div>

<?php if (isset($_GET['error'])): ?>
<div class="alert alert--error">Failed to save report. Please try again.</div>
<?php endif; ?>

<form method="POST" action="/ergon/site-reports/store" id="siteReportForm">

<!-- Header -->
<div class="sr-section">
    <h3>Report Details</h3>
    <div class="sr-grid">
        <div class="form-group">
            <label class="form-label">Date *</label>
            <input type="date" name="report_date" class="form-control" value="<?= date('Y-m-d', strtotime('-1 day')) ?>" required>
        </div>
        <div class="form-group">
            <label class="form-label">Project</label>
            <select name="project_id" class="form-control">
                <option value="">— Select Project —</option>
                <?php foreach ($projects as $proj): ?>
                <option value="<?= $proj['id'] ?>"><?= htmlspecialchars($proj['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="grid-column:1/-1">
            <label class="form-label">Site / Project Name *</label>
            <input type="text" name="site_name" class="form-control" placeholder="e.g. 18MW PHONIX MALL Ulunthurpet site (Prozeal Green Energy)" required>
        </div>
    </div>
</div>

<!-- Manpower -->
<div class="sr-section">
    <h3>👷 Manpower</h3>
    <?php foreach ($mpCats as $key => $label): ?>
    <div class="mp-row">
        <div class="mp-label"><?= $label ?></div>
        <div>
            <input type="number" name="mp[<?= $key ?>][count]" class="form-control mp-count"
                   placeholder="0" min="0" data-cat="<?= $key ?>" style="text-align:center">
        </div>
        <div class="names-col">
            <?php if (in_array($key, ['engineer','supervisor'])): ?>
            <textarea name="mp[<?= $key ?>][names]" class="form-control" rows="2"
                      placeholder="One name per line"></textarea>
            <?php else: ?>
            <input type="text" name="mp[<?= $key ?>][names]" class="form-control"
                   placeholder="Optional notes">
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <div style="margin-top:.75rem; padding-top:.75rem; border-top:1px solid #f1f5f9;">
        <strong>Total Manpower: </strong>
        <input type="number" name="total_manpower" id="totalManpower" class="form-control"
               style="display:inline-block;width:80px;text-align:center" value="0" min="0">
        <small class="text-muted">(auto-calculated, adjust if needed)</small>
    </div>
</div>

<!-- Machinery -->
<div class="sr-section">
    <h3>🚜 Machinery</h3>
    <div class="sr-grid">
    <?php foreach ($machines as $key => $label): ?>
    <div>
        <label class="form-label"><?= $label ?></label>
        <div style="display:flex;gap:.5rem">
            <input type="number" name="mach[<?= $key ?>][count]" class="form-control"
                   placeholder="Count" min="0" style="width:70px;text-align:center">
            <input type="number" name="mach[<?= $key ?>][hours]" class="form-control"
                   placeholder="Hrs" min="0" step="0.5">
            <input type="number" name="mach[<?= $key ?>][fuel]" class="form-control"
                   placeholder="Fuel L" min="0" step="0.5">
        </div>
    </div>
    <?php endforeach; ?>
    </div>
</div>

<!-- Today's Tasks -->
<div class="sr-section">
    <h3>✅ Today's Tasks</h3>
    <div id="tasksList">
        <div class="task-row">
            <input type="text" name="tasks[]" class="form-control" placeholder="Task description">
            <button type="button" class="remove-btn" onclick="removeRow(this)">✕</button>
        </div>
    </div>
    <button type="button" class="btn-add" onclick="addTask()">+ Add Task</button>
</div>

<!-- Expense Requests -->
<div class="sr-section">
    <h3>💰 Expense Requests <small class="text-muted">("Process my account")</small></h3>
    <div id="expensesList">
        <div class="exp-row">
            <input type="text" name="expenses[0][description]" class="form-control" placeholder="e.g. Tata ace advance">
            <input type="number" name="expenses[0][amount]" class="form-control" placeholder="Amount" min="0" step="0.01">
            <select name="expenses[0][type]" class="form-control">
                <?php foreach ($expTypes as $v => $l): ?><option value="<?= $v ?>"><?= $l ?></option><?php endforeach; ?>
            </select>
            <button type="button" class="remove-btn" onclick="removeRow(this)">✕</button>
        </div>
    </div>
    <button type="button" class="btn-add" onclick="addExpense()">+ Add Expense</button>
</div>

<!-- Remarks -->
<div class="sr-section">
    <h3>📝 Remarks</h3>
    <textarea name="remarks" class="form-control" rows="3" placeholder="Any additional notes..."></textarea>
</div>

<div style="display:flex;gap:.75rem;justify-content:flex-end;margin-bottom:2rem">
    <a href="/ergon/site-reports" class="btn btn--secondary">Cancel</a>
    <button type="submit" class="btn btn--primary">Submit Report</button>
</div>

</form>

<script>
// Auto-sum manpower counts
document.querySelectorAll('.mp-count').forEach(inp => {
    inp.addEventListener('input', () => {
        const total = [...document.querySelectorAll('.mp-count')]
            .reduce((s, i) => s + (parseInt(i.value) || 0), 0);
        document.getElementById('totalManpower').value = total;
    });
});

let expIdx = 1;
const expTypes = <?= json_encode(array_keys($expTypes)) ?>;
const expLabels = <?= json_encode(array_values($expTypes)) ?>;

function addTask() {
    const div = document.createElement('div');
    div.className = 'task-row';
    div.innerHTML = `<input type="text" name="tasks[]" class="form-control" placeholder="Task description">
                     <button type="button" class="remove-btn" onclick="removeRow(this)">✕</button>`;
    document.getElementById('tasksList').appendChild(div);
}

function addExpense() {
    const opts = expTypes.map((v,i) => `<option value="${v}">${expLabels[i]}</option>`).join('');
    const div = document.createElement('div');
    div.className = 'exp-row';
    div.innerHTML = `<input type="text" name="expenses[${expIdx}][description]" class="form-control" placeholder="Description">
                     <input type="number" name="expenses[${expIdx}][amount]" class="form-control" placeholder="Amount" min="0" step="0.01">
                     <select name="expenses[${expIdx}][type]" class="form-control">${opts}</select>
                     <button type="button" class="remove-btn" onclick="removeRow(this)">✕</button>`;
    document.getElementById('expensesList').appendChild(div);
    expIdx++;
}

function removeRow(btn) { btn.closest('div').remove(); }
</script>
