<?php
$title = 'Edit Expense Claim';
$active_page = 'expenses';
ob_start();
?>

<div class="compact-header">
    <h1>✏️ Edit Expense Claim</h1>
    <div class="header-actions">
        <a href="/ergon/expenses" class="btn-back">← Back</a>
    </div>
</div>

<div class="compact-form">
    <form method="POST" action="/ergon/expenses/edit/<?= $expense['id'] ?>">
        <div class="form-section">
            <div class="form-grid">
                <div class="form-group">
                    <label for="category">💰 Category</label>
                    <select name="category" id="category" required>
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
                    <label for="amount">💵 Amount</label>
                    <input type="number" name="amount" id="amount" step="0.01" min="0" value="<?= htmlspecialchars($expense['amount']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="expense_date">📅 Expense Date</label>
                    <input type="date" name="expense_date" id="expense_date" value="<?= htmlspecialchars($expense['expense_date']) ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="description">📝 Description</label>
                <textarea name="description" id="description" rows="4" placeholder="Provide details about the expense" required><?= htmlspecialchars($expense['description']) ?></textarea>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-primary">
                ✨ Update Expense Claim
            </button>
            <a href="/ergon/expenses" class="btn-secondary">❌ Cancel</a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>