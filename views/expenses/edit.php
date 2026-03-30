<?php
$title = 'Edit Expense Claim';
$active_page = 'expenses';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>💰</span> Edit Expense Claim</h1>
        <p>Update your expense claim details</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/expenses" class="btn btn--secondary">
            <span>←</span> Back to Expenses
        </a>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>🧧</span> Expense Claim Form
        </h2>
    </div>
    <div class="card__body">
        <form method="POST" action="/ergon/expenses/edit/<?= $expense['id'] ?>" class="form" enctype="multipart/form-data">
            <div class="form-group">
                <label for="project_id" class="form-label">Project</label>
                <select class="form-control" id="project_id" name="project_id" onchange="loadExpSubcats(this.value)">
                    <option value="">-- Select Project --</option>
                </select>
            </div>

            <div class="form-group" id="exp_subcat_group" style="display:none;">
                <label class="form-label">Work Category</label>
                <select class="form-control" id="subcategory_id" name="subcategory_id">
                    <option value="">-- Select work category --</option>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="category" class="form-label">Category *</label>
                    <select class="form-control" id="category" name="category" required>
                        <option value="travel" <?= $expense['category'] === 'travel' ? 'selected' : '' ?>>🚗 Travel & Transportation</option>
                        <option value="food" <?= $expense['category'] === 'food' ? 'selected' : '' ?>>🍽️ Food & Meals</option>
                        <option value="accommodation" <?= $expense['category'] === 'accommodation' ? 'selected' : '' ?>>🏨 Accommodation</option>
                        <option value="office_supplies" <?= $expense['category'] === 'office_supplies' ? 'selected' : '' ?>>📋 Office Supplies</option>
                        <option value="communication" <?= $expense['category'] === 'communication' ? 'selected' : '' ?>>📱 Communication</option>
                        <option value="training" <?= $expense['category'] === 'training' ? 'selected' : '' ?>>📚 Training & Development</option>
                        <option value="medical" <?= $expense['category'] === 'medical' ? 'selected' : '' ?>>🏥 Medical Expenses</option>
                        <option value="other" <?= $expense['category'] === 'other' ? 'selected' : '' ?>>📦 Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="amount" class="form-label">Amount (₹) *</label>
                    <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0.01" 
                           value="<?= htmlspecialchars($expense['amount']) ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="expense_date" class="form-label">Expense Date *</label>
                    <input type="date" class="form-control" id="expense_date" name="expense_date" 
                           value="<?= htmlspecialchars($expense['expense_date']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="receipt" class="form-label">Receipt (Optional)</label>
                    <input type="file" class="form-control" id="receipt" name="receipt" accept=".jpg,.jpeg,.png,.pdf">
                    <small class="form-text">Upload new receipt to replace existing (Max 5MB)</small>
                </div>
            </div>
            
            <div class="form-group">
                <label for="description" class="form-label">Description *</label>
                <textarea class="form-control" id="description" name="description" rows="4" 
                         placeholder="Provide detailed description of the expense..." required><?= htmlspecialchars($expense['description']) ?></textarea>
                <small class="form-text">Include purpose, location, and any relevant details</small>
            </div>
            
            <div class="form-actions" id="expense-form-actions">
                <button type="submit" class="btn btn--primary">
                    💸 Update Expense Claim
                </button>
                <a href="/ergon/expenses" class="btn btn--secondary">❌ Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
fetch('/ergon/api/projects.php')
    .then(r => r.json())
    .then(data => {
        const sel = document.getElementById('project_id');
        if (data.success && data.projects) {
            data.projects.forEach(p => {
                const o = document.createElement('option');
                o.value = p.id;
                o.textContent = p.name;
                if (p.id == '<?= $expense['project_id'] ?? '' ?>') o.selected = true;
                sel.appendChild(o);
            });
            if ('<?= $expense['project_id'] ?? '' ?>') {
                loadExpSubcats('<?= $expense['project_id'] ?? '' ?>', '<?= $expense['subcategory_id'] ?? '' ?>');
            }
        }
    })
    .catch(() => {});

function loadExpSubcats(projectId, selectedId) {
    const group = document.getElementById('exp_subcat_group');
    const sel   = document.getElementById('subcategory_id');
    sel.innerHTML = '<option value="">-- Select work category --</option>';
    if (!projectId) { group.style.display = 'none'; return; }
    fetch('/ergon/api/project-subcategories/' + projectId)
        .then(r => r.json())
        .then(data => {
            if (data.length) {
                data.forEach(s => {
                    const o = document.createElement('option');
                    o.value = s.id;
                    o.textContent = s.name;
                    if (selectedId && s.id == selectedId) o.selected = true;
                    sel.appendChild(o);
                });
                group.style.display = 'block';
            } else {
                group.style.display = 'none';
            }
        })
        .catch(() => { group.style.display = 'none'; });
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
