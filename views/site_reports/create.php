<?php
$machines  = ['tractor'=>'Tractor','jcb'=>'JCB','hydra'=>'Hydra','tata_ace'=>'Tata Ace','dg'=>'DG (Generator)','crane'=>'Crane','other'=>'Other'];
$mpCats    = ['engineer'=>'Engineers','supervisor'=>'Supervisors','ac_dc_team'=>'AC & DC Team','mms_team'=>'MMS Team','civil_mason'=>'Civil / Mason Team','local_labour'=>'Local Labour','driver_operator'=>'Drivers / Operators','other'=>'Other'];
$expTypes  = ['labour'=>'Labour Payment','machinery'=>'Machinery','transport'=>'Transport','fuel'=>'Fuel','site_expense'=>'Site Expense','advance'=>'Advance','other'=>'Other'];
?>
<style>
.sr-tabs{display:flex;gap:0;border-bottom:2px solid #e2e8f0;margin-bottom:1.25rem}
.sr-tab{padding:.6rem 1.25rem;font-size:.875rem;font-weight:500;color:#64748b;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;background:none;border-top:none;border-left:none;border-right:none}
.sr-tab.active{color:#3b82f6;border-bottom-color:#3b82f6;font-weight:600}
.sr-pane{display:none}.sr-pane.active{display:block}
.sr-section{background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:1.25rem;margin-bottom:1rem}
.sr-section h3{font-size:.85rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#64748b;margin:0 0 1rem}
.sr-grid{display:grid;grid-template-columns:1fr 1fr;gap:.75rem}
.mp-row{display:grid;grid-template-columns:160px 80px 1fr;gap:.5rem;align-items:start;margin-bottom:.5rem}
.mp-label{font-size:.875rem;font-weight:500;padding-top:.4rem}
.exp-row{display:grid;grid-template-columns:1fr 120px 140px 36px;gap:.5rem;align-items:center;margin-bottom:.5rem}
.task-row{display:flex;gap:.5rem;align-items:center;margin-bottom:.5rem}
.task-row input{flex:1}
.btn-add{background:none;border:1px dashed #94a3b8;border-radius:6px;padding:.3rem .75rem;font-size:.8rem;color:#64748b;cursor:pointer}
.btn-add:hover{border-color:#3b82f6;color:#3b82f6}
.remove-btn{background:none;border:none;color:#ef4444;cursor:pointer;font-size:1rem;padding:0 .25rem}
.parse-box{width:100%;min-height:180px;font-family:monospace;font-size:.8rem;border:1px solid #e2e8f0;border-radius:8px;padding:.75rem;resize:vertical}
.preview-badge{display:inline-block;background:#dbeafe;color:#1d4ed8;border-radius:4px;padding:.1rem .4rem;font-size:.75rem;font-weight:600;margin-left:.4rem}
.preview-ok{color:#10b981;font-weight:600}
.preview-warn{color:#f59e0b;font-weight:600}
@media(max-width:640px){.sr-grid{grid-template-columns:1fr}.mp-row{grid-template-columns:1fr 70px}.mp-row .names-col{grid-column:1/-1}.exp-row{grid-template-columns:1fr 100px}.exp-row select,.exp-row .remove-btn{grid-column:1}}
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

<?php
$canPaste = in_array($_SESSION['role'] ?? '', ['admin','owner','company_owner']);
$defaultTab = $canPaste ? 'paste' : 'manual';
?>

<!-- Tabs -->
<div class="sr-tabs">
    <?php if ($canPaste): ?>
    <button class="sr-tab <?= $defaultTab==='paste'?'active':'' ?>" onclick="switchTab('paste')">📱 Paste WhatsApp Message</button>
    <?php endif; ?>
    <button class="sr-tab <?= $defaultTab==='manual'?'active':'' ?>" onclick="switchTab('manual')">✏️ Manual Entry</button>
</div>

<!-- PASTE TAB -->
<div id="pane-paste" class="sr-pane <?= $defaultTab==='paste'?'active':'' ?>" <?= !$canPaste?'style="display:none"':'' ?>>
    <div class="sr-section">
        <h3>Paste WhatsApp Report Message</h3>
        <textarea id="waInput" class="parse-box" placeholder="Paste the WhatsApp daily report message here..."></textarea>
        <div style="margin-top:.75rem;display:flex;gap:.5rem;align-items:center">
            <button type="button" class="btn btn--primary btn--sm" onclick="parseAndPreview()">🔍 Parse & Preview</button>
            <button type="button" class="btn btn--secondary btn--sm" onclick="clearPaste()">Clear</button>
            <span id="parseStatus" style="font-size:.85rem;color:#64748b"></span>
        </div>
    </div>

    <div id="previewSection" style="display:none">
        <div class="sr-section" style="border-color:#10b981">
            <h3>✅ Preview — Confirm before saving</h3>
            <div id="previewContent"></div>
        </div>
        <form method="POST" action="/ergon/site-reports/store" id="parsedForm">
            <input type="hidden" name="report_date"     id="f_date">
            <input type="hidden" name="site_name"       id="f_site">
            <input type="hidden" name="project_id"      id="f_project" value="">
            <input type="hidden" name="total_manpower"  id="f_total_mp">
            <input type="hidden" name="remarks"         id="f_remarks" value="">
            <div id="f_mp_fields"></div>
            <div id="f_mach_fields"></div>
            <div id="f_task_fields"></div>
            <div id="f_exp_fields"></div>
            <div style="display:flex;gap:.75rem;justify-content:flex-end;margin-bottom:2rem">
                <button type="button" class="btn btn--secondary" onclick="document.getElementById('previewSection').style.display='none'">← Re-parse</button>
                <button type="submit" class="btn btn--primary">✅ Confirm & Save</button>
            </div>
        </form>
    </div>
</div>

<!-- MANUAL TAB -->
<div id="pane-manual" class="sr-pane <?= $defaultTab==='manual'?'active':'' ?>">
<form method="POST" action="/ergon/site-reports/store" id="siteReportForm">

<div class="sr-section">
    <h3>Report Details</h3>
    <div class="sr-grid">
        <div class="form-group">
            <label class="form-label">Date *</label>
            <input type="date" name="report_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
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
            <input type="text" name="site_name" class="form-control" placeholder="e.g. 18MW PHONIX MALL Ulunthurpet site" required>
        </div>
    </div>
</div>

<div class="sr-section">
    <h3>👷 Manpower</h3>
    <?php foreach ($mpCats as $key => $label): ?>
    <div class="mp-row">
        <div class="mp-label"><?= $label ?></div>
        <div><input type="number" name="mp[<?= $key ?>][count]" class="form-control mp-count" placeholder="0" min="0" style="text-align:center"></div>
        <div class="names-col">
            <?php if (in_array($key, ['engineer','supervisor'])): ?>
            <textarea name="mp[<?= $key ?>][names]" class="form-control" rows="2" placeholder="One name per line"></textarea>
            <?php else: ?>
            <input type="text" name="mp[<?= $key ?>][names]" class="form-control" placeholder="Optional notes">
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <div style="margin-top:.75rem;padding-top:.75rem;border-top:1px solid #f1f5f9">
        <strong>Total Manpower: </strong>
        <input type="number" name="total_manpower" id="totalManpower" class="form-control"
               style="display:inline-block;width:80px;text-align:center" value="0" min="0">
    </div>
</div>

<div class="sr-section">
    <h3>🚜 Machinery</h3>
    <div class="sr-grid">
    <?php foreach ($machines as $key => $label): ?>
    <div>
        <label class="form-label"><?= $label ?></label>
        <div style="display:flex;gap:.5rem">
            <input type="number" name="mach[<?= $key ?>][count]" class="form-control" placeholder="Count" min="0" style="width:70px;text-align:center">
            <input type="number" name="mach[<?= $key ?>][hours]" class="form-control" placeholder="Hrs" min="0" step="0.5">
            <input type="number" name="mach[<?= $key ?>][fuel]"  class="form-control" placeholder="Fuel L" min="0" step="0.5">
        </div>
    </div>
    <?php endforeach; ?>
    </div>
</div>

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

<div class="sr-section">
    <h3>💰 Expense Requests</h3>
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

<div class="sr-section">
    <h3>📝 Remarks</h3>
    <textarea name="remarks" class="form-control" rows="3" placeholder="Any additional notes..."></textarea>
</div>

<div style="display:flex;gap:.75rem;justify-content:flex-end;margin-bottom:2rem">
    <a href="/ergon/site-reports" class="btn btn--secondary">Cancel</a>
    <button type="submit" class="btn btn--primary">Submit Report</button>
</div>
</form>
</div>

<script>
// ── Tab switching ──────────────────────────────────────────────
function switchTab(tab) {
    document.querySelectorAll('.sr-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.sr-pane').forEach(p => p.classList.remove('active'));
    const activeTab = document.querySelector(`.sr-tab[onclick="switchTab('${tab}')"]`);
    const activePane = document.getElementById('pane-' + tab);
    if (activeTab) activeTab.classList.add('active');
    if (activePane) activePane.classList.add('active');
}

function clearPaste() {
    document.getElementById('waInput').value = '';
    document.getElementById('previewSection').style.display = 'none';
    document.getElementById('parseStatus').textContent = '';
}

// ── WhatsApp Parser ────────────────────────────────────────────
function parseWA(text) {
    const lines = text.split('\n').map(l => l.trim()).filter(l => l);
    const result = {
        date: '', site: '', total_manpower: 0,
        manpower: { engineer:[], supervisor:[], ac_dc_team:[], mms_team:[], civil_mason:[], local_labour:[], driver_operator:[], other:[] },
        manpower_counts: {},
        machinery: {},
        tasks: [], expenses: []
    };

    // ── Date ──
    const dateMatch = text.match(/date[:\s]*(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{2,4})/i);
    if (dateMatch) {
        let [,d,m,y] = dateMatch;
        if (y.length === 2) y = '20' + y;
        result.date = `${y}-${m.padStart(2,'0')}-${d.padStart(2,'0')}`;
    }

    // ── Site ──
    const siteMatch = text.match(/site\s*[\/&]\s*project\s*[:\-]?\s*(.+)/i);
    if (siteMatch) result.site = siteMatch[1].replace(/[*_]/g,'').trim();

    // ── Total manpower ──
    const mpMatch = text.match(/total\s*manpower\s*[:\(]?\s*(\d+)/i);
    if (mpMatch) result.total_manpower = parseInt(mpMatch[1]);

    // ── Named sections parser ──
    // Collect named lists under section headings
    let currentSection = null;
    const namePattern = /^\d+[\.\)]\s*(.+)/;

    // Section keyword → category key
    const sectionMap = [
        [/engineer/i,        'engineer'],
        [/supervisor/i,      'supervisor'],
        [/ac\s*[&\+]\s*dc/i, 'ac_dc_team'],
        [/mms/i,             'mms_team'],
        [/civil|mason|weld|housekeep/i, 'civil_mason'],
        [/local\s*labour/i,  'local_labour'],
        [/driver|operator/i, 'driver_operator'],
    ];

    // Count-only patterns (e.g. "MMS team - 1", "Local Labour: 00")
    const countPatterns = [
        [/engineer[s]?\s*[\(\-:]\s*(\d+)/i,        'engineer'],
        [/supervisor[s]?\s*[\(\-:]\s*(\d+)/i,       'supervisor'],
        [/ac\s*[&\+]\s*dc\s*team\s*[\(\-:]\s*(\d+)/i,'ac_dc_team'],
        [/mms\s*team\s*[\(\-:]\s*(\d+)/i,           'mms_team'],
        [/civil\s*team\s*[\(\-:]\s*(\d+)/i,         'civil_mason'],
        [/local\s*labour\s*[\(\-:]\s*(\d+)/i,       'local_labour'],
        [/driver[s]?\s*[\/]?\s*operator[s]?\s*[:\-]?\s*(\d+)/i, 'driver_operator'],
    ];

    for (const line of lines) {
        const clean = line.replace(/[*_📋👷👥👤🚜🧑🔧]/g,'').trim();

        // Detect section heading
        let matched = false;
        for (const [rx, key] of sectionMap) {
            if (rx.test(clean) && !namePattern.test(clean)) {
                currentSection = key;
                // Also try to extract inline count e.g. "Engineers (3):"
                const inlineCount = clean.match(/[\(\-:]\s*(\d+)/);
                if (inlineCount) result.manpower_counts[key] = parseInt(inlineCount[1]);
                matched = true; break;
            }
        }
        if (matched) continue;

        // Named list item under current section
        const nm = clean.match(namePattern);
        if (nm && currentSection && ['engineer','supervisor','ac_dc_team','civil_mason'].includes(currentSection)) {
            const name = nm[1].replace(/[*_]/g,'').trim();
            if (name && !/^(today|task|machine|total|date|site|company)/i.test(name)) {
                result.manpower[currentSection].push(name);
            }
            continue;
        }

        // Tasks section
        if (/today.?s?\s*task/i.test(clean)) { currentSection = 'tasks'; continue; }
        if (currentSection === 'tasks') {
            const tm = clean.match(/^[\d\(\)]+[\.\)]\s*(.+)/);
            if (tm) result.tasks.push(tm[1].trim());
            continue;
        }

        // Machinery inline: "DG-1 | JCB-0 | Tractor-2"
        if (/machinery|machine/i.test(clean) || clean.includes('|') || /\b(dg|jcb|tractor|hydra|tata\s*ace|crane)\s*[-:]\s*\d/i.test(clean)) {
            const machRx = /(tractor|jcb|hydra|tata\s*ace|dg|crane)\s*[-:]\s*(\d+)/gi;
            let m;
            while ((m = machRx.exec(clean)) !== null) {
                const key = m[1].toLowerCase().replace(/\s+/,'_').replace('tata_ace','tata_ace');
                const count = parseInt(m[2]);
                if (count > 0) result.machinery[key] = (result.machinery[key] || 0) + count;
            }
        }
    }

    // Apply count-only patterns across full text
    for (const [rx, key] of countPatterns) {
        if (!result.manpower_counts[key]) {
            const m = text.match(rx);
            if (m) result.manpower_counts[key] = parseInt(m[1]);
        }
    }

    // Merge named list counts
    for (const key of Object.keys(result.manpower)) {
        if (result.manpower[key].length > 0 && !result.manpower_counts[key]) {
            result.manpower_counts[key] = result.manpower[key].length;
        }
    }

    return result;
}

// ── Render preview & populate hidden form ─────────────────────
function parseAndPreview() {
    const text = document.getElementById('waInput').value.trim();
    if (!text) { document.getElementById('parseStatus').textContent = '⚠️ Please paste a message first.'; return; }

    const d = parseWA(text);
    const status = document.getElementById('parseStatus');

    if (!d.date && !d.site) {
        status.textContent = '❌ Could not parse — check message format.';
        return;
    }

    status.textContent = '✅ Parsed successfully';

    // Build preview HTML
    const mpLabels = {engineer:'Engineers',supervisor:'Supervisors',ac_dc_team:'AC & DC Team',
        mms_team:'MMS Team',civil_mason:'Civil/Mason',local_labour:'Local Labour',
        driver_operator:'Drivers/Operators',other:'Other'};

    let html = `<table style="width:100%;border-collapse:collapse;font-size:.875rem">`;
    html += row('Date', d.date ? `<span class="preview-ok">${d.date}</span>` : '<span class="preview-warn">Not found</span>');
    html += row('Site', d.site ? `<span class="preview-ok">${esc(d.site)}</span>` : '<span class="preview-warn">Not found</span>');
    html += row('Total Manpower', d.total_manpower || '<span class="preview-warn">Not found</span>');

    // Manpower
    let mpRows = '';
    for (const [key, label] of Object.entries(mpLabels)) {
        const count = d.manpower_counts[key] || 0;
        const names = d.manpower[key];
        if (count > 0 || names.length > 0) {
            mpRows += `<div style="margin:.2rem 0"><strong>${label}:</strong> ${count}
                ${names.length ? '<span class="preview-badge">' + names.map(esc).join(', ') + '</span>' : ''}</div>`;
        }
    }
    if (mpRows) html += row('👷 Manpower', mpRows);

    // Machinery
    if (Object.keys(d.machinery).length) {
        const machLabels = {tractor:'Tractor',jcb:'JCB',hydra:'Hydra',tata_ace:'Tata Ace',dg:'DG',crane:'Crane'};
        const machStr = Object.entries(d.machinery).map(([k,v]) => `${machLabels[k]||k}: ${v}`).join(' | ');
        html += row('🚜 Machinery', machStr);
    }

    // Tasks
    if (d.tasks.length) {
        html += row('✅ Tasks', d.tasks.map((t,i) => `${i+1}. ${esc(t)}`).join('<br>'));
    }

    html += '</table>';

    if (!d.date || !d.site) {
        html += `<div style="margin-top:.75rem;padding:.5rem .75rem;background:#fef3c7;border-radius:6px;font-size:.8rem;color:#92400e">
            ⚠️ Some fields could not be parsed. Please switch to Manual Entry to fill them in.
        </div>`;
    }

    document.getElementById('previewContent').innerHTML = html;

    // Populate hidden form fields
    document.getElementById('f_date').value    = d.date || new Date().toISOString().slice(0,10);
    document.getElementById('f_site').value    = d.site || 'Unknown Site';
    document.getElementById('f_total_mp').value = d.total_manpower;

    // Manpower hidden fields
    const mpDiv = document.getElementById('f_mp_fields');
    mpDiv.innerHTML = '';
    for (const [key] of Object.entries(mpLabels)) {
        const count = d.manpower_counts[key] || 0;
        const names = d.manpower[key];
        mpDiv.innerHTML += `<input type="hidden" name="mp[${key}][count]" value="${count}">`;
        mpDiv.innerHTML += `<input type="hidden" name="mp[${key}][names]" value="${names.join('\n')}">`;
    }

    // Machinery hidden fields
    const machDiv = document.getElementById('f_mach_fields');
    machDiv.innerHTML = '';
    for (const [key, count] of Object.entries(d.machinery)) {
        machDiv.innerHTML += `<input type="hidden" name="mach[${key}][count]" value="${count}">`;
    }

    // Tasks hidden fields
    const taskDiv = document.getElementById('f_task_fields');
    taskDiv.innerHTML = '';
    d.tasks.forEach(t => {
        taskDiv.innerHTML += `<input type="hidden" name="tasks[]" value="${esc(t)}">`;
    });

    document.getElementById('previewSection').style.display = 'block';
    document.getElementById('previewSection').scrollIntoView({behavior:'smooth'});
}

function row(label, value) {
    return `<tr><td style="padding:.35rem .5rem;font-weight:600;color:#64748b;white-space:nowrap;vertical-align:top;width:140px">${label}</td>
                <td style="padding:.35rem .5rem">${value}</td></tr>`;
}
function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Manual form helpers ────────────────────────────────────────
document.querySelectorAll('.mp-count').forEach(inp => {
    inp.addEventListener('input', () => {
        const total = [...document.querySelectorAll('.mp-count')].reduce((s,i) => s + (parseInt(i.value)||0), 0);
        document.getElementById('totalManpower').value = total;
    });
});

let expIdx = 1;
const expTypes  = <?= json_encode(array_keys($expTypes)) ?>;
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
