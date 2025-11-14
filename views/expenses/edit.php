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
        <form method="POST" action="/ergon/expenses/edit/<?= $expense['id'] ?>" class="form">
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
            
            <div class="form-actions">
                <button type="submit" class="btn btn--primary">
                    💸 Update Expense Claim
                </button>
                <a href="/ergon/expenses" class="btn btn--secondary">❌ Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>