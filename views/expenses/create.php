<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Submit Expense Claim</h4>
                </div>
                <div class="card-body">
                    <form id="expenseForm">
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-control" id="category" name="category" required>
                                <option value="travel">Travel</option>
                                <option value="food">Food & Meals</option>
                                <option value="accommodation">Accommodation</option>
                                <option value="office_supplies">Office Supplies</option>
                                <option value="communication">Communication</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount (₹)</label>
                            <input type="number" class="form-control" id="amount" name="amount" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="expense_date" class="form-label">Expense Date</label>
                            <input type="date" class="form-control" id="expense_date" name="expense_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit Claim</button>
                        <a href="/ergon_clean/public/expenses" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('expenseForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('/ergon_clean/public/expenses/create', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            ERGON.showAlert('Expense claim submitted successfully!', 'success');
            setTimeout(() => window.location.href = '/ergon_clean/public/expenses', 1000);
        } else {
            ERGON.showAlert(data.error, 'danger');
        }
    });
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>