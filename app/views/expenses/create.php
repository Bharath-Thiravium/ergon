<?php
$title = 'Submit Expense';
$active_page = 'expenses';
ob_start();
?>

<div class="page-header">
    <h1>Submit Expense</h1>
    <a href="/ergon/user/requests" class="btn btn--secondary">Back to My Requests</a>
</div>

<div class="card">
    <div class="card__body">
        <form method="POST" enctype="multipart/form-data" class="form">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Security::generateCSRFToken()) ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Security::generateCSRFToken()) ?>">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-control" required>
                        <option value="">Select category</option>
                        <option value="Travel">Travel</option>
                        <option value="Meals">Meals</option>
                        <option value="Office Supplies">Office Supplies</option>
                        <option value="Equipment">Equipment</option>
                        <option value="Training">Training</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Amount</label>
                    <input type="number" name="amount" class="form-control" step="0.01" min="0" required>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Date</label>
                <input type="date" name="date" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Describe the expense..." required></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Receipt</label>
                <input type="file" name="receipt" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                <small class="form-help">Upload receipt (JPG, PNG, PDF - Max 5MB)</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn--primary">Submit Expense</button>
                <a href="/ergon/user/requests" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>