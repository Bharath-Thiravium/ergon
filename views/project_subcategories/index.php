<?php
$title = 'Project Subcategories';
$active_page = 'projects';
ob_start();
$projectId = $selectedProject['id'] ?? null;
?>

<div class="page-header">
    <div class="page-title">
        <h1>📂 Project Subcategories</h1>
        <p>Allocate budgets and track expenses/advances per work category</p>
    </div>
    <div class="page-actions">
        <?php if ($projectId): ?>
        <button onclick="showAddModal()" class="btn btn--primary">➕ Add Subcategory</button>
        <?php endif; ?>
        <a href="/ergon/projects" class="btn btn--secondary">← Back to Projects</a>
    </div>
</div>

<!-- Project Selector -->
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card__body">
        <form method="GET" style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap;">
            <div style="flex:1;min-width:200px;">
                <label style="display:block;font-weight:600;margin-bottom:.4rem;">Select Project</label>
                <select name="project_id" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Choose a project --</option>
                    <?php foreach ($projects as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= ($p['id'] == $projectId) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['name']) ?> (Budget: ₹<?= number_format($p['budget'], 2) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<?php if ($selectedProject): ?>

<!-- Project Budget Summary -->
<?php
$totalSubBudget   = array_sum(array_column($subcategories, 'budget'));
$totalOpening     = array_sum(array_column($subcategories, 'opening_utilised'));
$totalSpent       = $totalOpening + array_sum(array_column($subcategories, 'total_expenses')) + array_sum(array_column($subcategories, 'total_advances'));
$projectBudget    = floatval($selectedProject['budget']);
$unallocated      = $projectBudget - $totalSubBudget;
$remaining        = $projectBudget - $totalSpent;
?>
<div class="dashboard-grid" style="margin-bottom:1.5rem;">
    <div class="kpi-card">
        <div class="kpi-card__icon">💰</div>
        <div class="kpi-card__value">₹<?= number_format($projectBudget, 2) ?></div>
        <div class="kpi-card__label">Project Budget</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__icon">📂</div>
        <div class="kpi-card__value">₹<?= number_format($totalSubBudget, 2) ?></div>
        <div class="kpi-card__label">Allocated to Subcategories</div>
        <div class="kpi-card__status <?= $unallocated < 0 ? 'text-danger' : 'text-muted' ?>">
            <?= $unallocated >= 0 ? '₹' . number_format($unallocated, 2) . ' unallocated' : '⚠️ Over-allocated by ₹' . number_format(abs($unallocated), 2) ?>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__icon">📉</div>
        <div class="kpi-card__value" style="color:#dc2626;">₹<?= number_format($totalSpent, 2) ?></div>
        <div class="kpi-card__label">Total Spent</div>
        <div class="kpi-card__status">Opening + Expenses + Advances</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__icon">⚖️</div>
        <div class="kpi-card__value <?= $remaining >= 0 ? 'text-success' : 'text-danger' ?>">₹<?= number_format(abs($remaining), 2) ?></div>
        <div class="kpi-card__label">Budget Remaining</div>
        <div class="kpi-card__status"><?= $remaining >= 0 ? 'Available' : 'Over Budget' ?></div>
    </div>
</div>

<!-- Subcategories Table -->
<div class="card">
    <div class="card__header">
        <h2 class="card__title">📋 <?= htmlspecialchars($selectedProject['name']) ?> — Work Categories</h2>
        <div class="card__actions">
            <span class="badge badge--info"><?= count($subcategories) ?> Categories</span>
        </div>
    </div>
    <div class="card__body">
        <?php if (empty($subcategories)): ?>
        <div class="empty-state" style="text-align:center;padding:3rem;">
            <div style="font-size:3rem;opacity:.4;">📂</div>
            <h3>No subcategories yet</h3>
            <p>Add work categories to allocate budget and track spending.</p>
            <button onclick="showAddModal()" class="btn btn--primary" style="margin-top:1rem;">➕ Add First Category</button>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table--striped">
                <thead>
                    <tr>
                        <th>Category Name</th>
                        <th>Description</th>
                        <th>Allocated Budget</th>
                        <th>Opening Utilised</th>
                        <th>Expenses</th>
                        <th>Advances</th>
                        <th>Total Spent</th>
                        <th>Remaining</th>
                        <th>Usage</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($subcategories as $sub):
                    $opening   = floatval($sub['opening_utilised']);
                    $spent     = $opening + floatval($sub['total_expenses']) + floatval($sub['total_advances']);
                    $rem       = floatval($sub['budget']) - $spent;
                    $pct       = $sub['budget'] > 0 ? min(100, ($spent / $sub['budget']) * 100) : 0;
                    $barColor  = $pct >= 100 ? '#dc2626' : ($pct >= 80 ? '#f59e0b' : '#10b981');
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($sub['name']) ?></strong></td>
                    <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($sub['description'] ?? '—') ?></td>
                    <td>₹<?= number_format($sub['budget'], 2) ?></td>
                    <td>
                        <span style="color:#6b7280;">₹<?= number_format($opening, 2) ?></span>
                        <?php if ($opening > 0): ?><small class="text-muted"> (prior)</small><?php endif; ?>
                    </td>
                    <td>
                        ₹<?= number_format($sub['total_expenses'], 2) ?>
                        <small class="text-muted">(<?= $sub['expense_count'] ?>)</small>
                    </td>
                    <td>
                        ₹<?= number_format($sub['total_advances'], 2) ?>
                        <small class="text-muted">(<?= $sub['advance_count'] ?>)</small>
                    </td>
                    <td><strong>₹<?= number_format($spent, 2) ?></strong></td>
                    <td class="<?= $rem >= 0 ? 'text-success' : 'text-danger' ?>">
                        <strong>₹<?= number_format(abs($rem), 2) ?></strong>
                        <?= $rem < 0 ? ' ⚠️' : '' ?>
                    </td>
                    <td style="min-width:120px;">
                        <div style="background:#e5e7eb;border-radius:4px;height:8px;overflow:hidden;">
                            <div style="width:<?= $pct ?>%;background:<?= $barColor ?>;height:100%;transition:width .3s;"></div>
                        </div>
                        <small><?= number_format($pct, 1) ?>%</small>
                    </td>
                    <td>
                        <span class="badge badge--<?= $sub['status'] === 'active' ? 'success' : ($sub['status'] === 'completed' ? 'info' : 'warning') ?>">
                            <?= ucfirst($sub['status']) ?>
                        </span>
                    </td>
                    <td>
                        <button onclick="editSubcategory(<?= htmlspecialchars(json_encode($sub)) ?>)" class="btn btn--sm btn--outline" title="Edit">✏️</button>
                        <button onclick="deleteSubcategory(<?= $sub['id'] ?>, '<?= htmlspecialchars($sub['name']) ?>')" class="btn btn--sm btn--danger" title="Delete">🗑️</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php endif; ?>

<!-- Add Modal -->
<div id="addModal" class="modal-overlay" style="display:none;">
    <div class="modal-content" style="max-width:420px;">
        <div class="modal-header" style="padding:.75rem 1rem;">
            <h3 style="font-size:1rem;margin:0;">➕ Add Work Category</h3>
            <span class="close" onclick="closeAddModal()" style="cursor:pointer;font-size:1.2rem;">&times;</span>
        </div>
        <form id="addForm">
            <input type="hidden" name="project_id" value="<?= $projectId ?>">
            <div style="padding:.75rem 1rem;display:grid;grid-template-columns:1fr 1fr;gap:.6rem;">
                <div style="grid-column:1/-1;">
                    <label style="font-size:.8rem;font-weight:600;display:block;margin-bottom:.25rem;">Category Name *</label>
                    <input type="text" name="name" class="form-control" style="font-size:.85rem;padding:.35rem .5rem;" placeholder="e.g. Civil Work, Electrical" required>
                </div>
                <div style="grid-column:1/-1;">
                    <label style="font-size:.8rem;font-weight:600;display:block;margin-bottom:.25rem;">Description</label>
                    <input type="text" name="description" class="form-control" style="font-size:.85rem;padding:.35rem .5rem;" placeholder="Optional">
                </div>
                <div>
                    <label style="font-size:.8rem;font-weight:600;display:block;margin-bottom:.25rem;">Budget (₹)</label>
                    <input type="number" name="budget" class="form-control" style="font-size:.85rem;padding:.35rem .5rem;" step="0.01" min="0" value="0">
                </div>
                <div>
                    <label style="font-size:.8rem;font-weight:600;display:block;margin-bottom:.25rem;">Opening Utilised (₹)</label>
                    <input type="number" name="opening_utilised" class="form-control" style="font-size:.85rem;padding:.35rem .5rem;" step="0.01" min="0" value="0">
                </div>
            </div>
            <div style="padding:.6rem 1rem;border-top:1px solid #e5e7eb;display:flex;justify-content:flex-end;gap:.5rem;">
                <button type="button" onclick="closeAddModal()" class="btn btn--secondary" style="padding:.35rem .8rem;font-size:.85rem;">Cancel</button>
                <button type="submit" class="btn btn--primary" style="padding:.35rem .8rem;font-size:.85rem;">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal-overlay" style="display:none;">
    <div class="modal-content" style="max-width:480px;">
        <div class="modal-header">
            <h3>✏️ Edit Work Category</h3>
            <span class="close" onclick="closeEditModal()">&times;</span>
        </div>
        <form id="editForm">
            <input type="hidden" id="editId">
            <div class="modal-body">
                <div class="form-group">
                    <label>Category Name *</label>
                    <input type="text" id="editName" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea id="editDesc" name="description" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label>Allocated Budget (₹)</label>
                    <input type="number" id="editBudget" name="budget" class="form-control" step="0.01" min="0">
                </div>
                <div class="form-group">
                    <label>Opening Utilised (₹) <small style="font-weight:400;color:#6b7280;">— amount already spent before this system</small></label>
                    <input type="number" id="editOpening" name="opening_utilised" class="form-control" step="0.01" min="0" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select id="editStatus" name="status" class="form-control">
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                        <option value="on_hold">On Hold</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeEditModal()" class="btn btn--secondary">Cancel</button>
                <button type="submit" class="btn btn--primary">Update</button>
            </div>
        </form>
    </div>
</div>

<style>
.modal-overlay { position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.5);z-index:1000;display:flex;align-items:center;justify-content:center; }
.form-group { margin-bottom:1rem; }
.form-group label { display:block;font-weight:600;margin-bottom:.4rem;font-size:.9rem; }
.form-control { width:100%;padding:.5rem;border:1px solid #d1d5db;border-radius:6px;font-size:.9rem; }
.btn--sm { padding:.25rem .6rem;font-size:.8rem; }
.text-success { color:#10b981; }
.text-danger  { color:#dc2626; }
.text-muted   { color:#6b7280; }
</style>

<script>
function showAddModal() {
    document.getElementById('addModal').style.display = 'flex';
}
function closeAddModal() {
    document.getElementById('addModal').style.display = 'none';
    document.getElementById('addForm').reset();
}
function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

function editSubcategory(sub) {
    document.getElementById('editId').value      = sub.id;
    document.getElementById('editName').value    = sub.name;
    document.getElementById('editDesc').value    = sub.description || '';
    document.getElementById('editBudget').value  = sub.budget;
    document.getElementById('editOpening').value = sub.opening_utilised || 0;
    document.getElementById('editStatus').value  = sub.status;
    document.getElementById('editModal').style.display = 'flex';
}

function deleteSubcategory(id, name) {
    if (!confirm('Delete "' + name + '"? Expenses and advances linked to it will be unlinked.')) return;
    fetch('/ergon/project-subcategories/delete/' + id, { method: 'POST' })
        .then(r => r.json()).then(d => {
            if (d.success) location.reload();
            else alert('Error: ' + d.error);
        });
}

document.getElementById('addForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fetch('/ergon/project-subcategories/store', { method: 'POST', body: fd })
        .then(r => r.json()).then(d => {
            if (d.success) location.reload();
            else alert('Error: ' + d.error);
        });
});

document.getElementById('editForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const id = document.getElementById('editId').value;
    const fd = new FormData(this);
    fetch('/ergon/project-subcategories/update/' + id, { method: 'POST', body: fd })
        .then(r => r.json()).then(d => {
            if (d.success) location.reload();
            else alert('Error: ' + d.error);
        });
});

// Close on backdrop click
['addModal','editModal'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
