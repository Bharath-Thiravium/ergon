<?php
$title = 'Submit Expense';
$active_page = 'expenses';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>💰</span> Submit Expense</h1>
        <p>Submit your expense claim for reimbursement</p>
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
            <span>🧾</span> Expense Claim Form
        </h2>
    </div>
    <div class="card__body">
        <form id="expenseForm" class="form">
            <div class="form-row">
                <div class="form-group">
                    <label for="category" class="form-label">Category *</label>
                    <select class="form-control" id="category" name="category" required>
                        <option value="">Select Category</option>
                        <option value="travel">🚗 Travel & Transportation</option>
                        <option value="food">🍽️ Food & Meals</option>
                        <option value="accommodation">🏨 Accommodation</option>
                        <option value="office_supplies">📋 Office Supplies</option>
                        <option value="communication">📱 Communication</option>
                        <option value="training">📚 Training & Development</option>
                        <option value="medical">🏥 Medical Expenses</option>
                        <option value="other">📦 Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="amount" class="form-label">Amount (₹) *</label>
                    <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0.01" 
                           placeholder="0.00" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="expense_date" class="form-label">Expense Date *</label>
                    <input type="date" class="form-control" id="expense_date" name="expense_date" required>
                </div>
                <div class="form-group">
                    <label for="receipt" class="form-label">Receipt (Optional)</label>
                    <input type="file" class="form-control" id="receipt" name="receipt" accept=".jpg,.jpeg,.png,.pdf">
                    <small class="form-text">Upload receipt image or PDF (Max 5MB)</small>
                </div>
            </div>
            
            <div class="form-group">
                <label for="description" class="form-label">Description *</label>
                <textarea class="form-control" id="description" name="description" rows="4" 
                         placeholder="Provide detailed description of the expense..." required></textarea>
                <small class="form-text">Include purpose, location, and any relevant details</small>
            </div>
            
            <div class="expense-guidelines">
                <h4>📋 Expense Guidelines</h4>
                <ul>
                    <li>All expenses must be business-related and pre-approved if over ₹5,000</li>
                    <li>Receipts are mandatory for expenses above ₹500</li>
                    <li>Submit claims within 30 days of the expense date</li>
                    <li>Ensure all receipt details are clearly visible</li>
                </ul>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn--primary" id="submitBtn">
                    <span>💸</span> Submit Expense Claim
                </button>
                <a href="/ergon/expenses" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<style>
.expense-guidelines {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1.5rem;
    margin: 1.5rem 0;
}

.expense-guidelines h4 {
    margin: 0 0 1rem 0;
    color: #495057;
    font-size: 1rem;
    font-weight: 600;
}

.expense-guidelines ul {
    margin: 0;
    padding-left: 1.5rem;
}

.expense-guidelines li {
    margin-bottom: 0.5rem;
    color: #6c757d;
    font-size: 0.9rem;
}

.form-text {
    font-size: 0.875rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

.form-control:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>

<script>
// Set default expense date to today
document.getElementById('expense_date').value = new Date().toISOString().split('T')[0];

// Set maximum date to today (no future expenses)
document.getElementById('expense_date').max = new Date().toISOString().split('T')[0];

document.getElementById('expenseForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.innerHTML;
    
    // Validate amount
    const amount = parseFloat(document.getElementById('amount').value);
    if (amount <= 0) {
        alert('Please enter a valid amount greater than 0');
        return;
    }
    
    // Validate description length
    const description = document.getElementById('description').value.trim();
    if (description.length < 10) {
        alert('Please provide a detailed description (minimum 10 characters)');
        return;
    }
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span>⏳</span> Submitting...';
    
    const formData = new FormData(this);
    
    fetch('/ergon/expenses/create', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP ' + response.status + ': ' + response.statusText);
        }
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON response:', text);
                throw new Error('Server returned invalid JSON: ' + text.substring(0, 100));
            }
        });
    })
    .then(data => {
        if (data.success) {
            alert('Expense claim submitted successfully!');
            window.location.href = data.redirect || '/ergon/expenses';
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Network error occurred: ' + error.message + '. Please check the console for details.');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>