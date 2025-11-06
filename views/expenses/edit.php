<?php
$title = 'Edit Expense Claim';
$active_page = 'expenses';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>✏️</span> Edit Expense Claim</h1>
        <p>Modify your expense claim details</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/expenses" class="btn btn--secondary">
            <span>←</span> Back to Expenses
        </a>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">Expense Details</h2>
    </div>
    <div class="card__body">
        <form method="POST" action="/ergon/expenses/edit/<?= $expense['id'] ?>">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-control" required>
                        <option value="travel" <?= $expense['category'] === 'travel' ? 'selected' : '' ?>>Travel</option>
                        <option value="food" <?= $expense['category'] === 'food' ? 'selected' : '' ?>>Food & Meals</option>
                        <option value="accommodation" <?= $expense['category'] === 'accommodation' ? 'selected' : '' ?>>Accommodation</option>
                        <option value="fuel" <?= $expense['category'] === 'fuel' ? 'selected' : '' ?>>Fuel</option>
                        <option value="office_supplies" <?= $expense['category'] === 'office_supplies' ? 'selected' : '' ?>>Office Supplies</option>
                        <option value="communication" <?= $expense['category'] === 'communication' ? 'selected' : '' ?>>Communication</option>
                        <option value="training" <?= $expense['category'] === 'training' ? 'selected' : '' ?>>Training</option>
                        <option value="other" <?= $expense['category'] === 'other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Amount</label>
                    <input type="number" name="amount" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($expense['amount']) ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Expense Date</label>
                <input type="date" name="expense_date" class="form-control" value="<?= htmlspecialchars($expense['expense_date']) ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4" placeholder="Provide details about the expense" required><?= htmlspecialchars($expense['description']) ?></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn--primary">
                    <span>💾</span> Update Expense Claim
                </button>
                <a href="/ergon/expenses" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>