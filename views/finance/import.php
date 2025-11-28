<?php 
$title = 'Finance Data Import';
$active_page = 'finance';
ob_start(); 
?>

<div class="container-fluid">
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">📊 Finance Data Import</h2>
        </div>
        <div class="card__body">
            <div class="import-options">
                <div class="option-card">
                    <h3>🚀 Quick Demo Data</h3>
                    <p>Generate sample finance data for testing the dashboard</p>
                    <button id="populateDemoBtn" class="btn btn--primary">
                        <span class="btn__text">Populate Demo Data</span>
                    </button>
                </div>
                
                <div class="option-card">
                    <h3>📁 CSV Import</h3>
                    <p>Import finance data from your CSV file</p>
                    <a href="/ergon/import_finance_data.php" class="btn btn--secondary">
                        <span class="btn__text">Upload CSV File</span>
                    </a>
                </div>
            </div>
            
            <div class="import-status" id="importStatus" style="display:none;"></div>
            
            <div class="actions">
                <a href="/ergon/finance" class="btn btn--outline">
                    <span class="btn__text">← Back to Dashboard</span>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.import-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin: 2rem 0;
}

.option-card {
    padding: 2rem;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    text-align: center;
    background: var(--bg-secondary);
}

.option-card h3 {
    margin: 0 0 1rem 0;
    color: var(--text-primary);
}

.option-card p {
    margin: 0 0 1.5rem 0;
    color: var(--text-secondary);
}

.import-status {
    margin: 2rem 0;
    padding: 1rem;
    border-radius: 6px;
}

.import-status.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.import-status.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.actions {
    margin-top: 2rem;
    text-align: center;
}

@media (max-width: 768px) {
    .import-options {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
}
</style>

<script>
document.getElementById('populateDemoBtn').addEventListener('click', function() {
    const btn = this;
    const status = document.getElementById('importStatus');
    
    btn.disabled = true;
    btn.querySelector('.btn__text').textContent = 'Populating...';
    
    fetch('/ergon/finance/import', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=populate_demo'
    })
    .then(response => response.json())
    .then(data => {
        status.style.display = 'block';
        if (data.success) {
            status.className = 'import-status success';
            status.innerHTML = '✅ ' + data.message + '<br><a href="/ergon/finance">View Finance Dashboard →</a>';
        } else {
            status.className = 'import-status error';
            status.innerHTML = '❌ Error: ' + data.error;
        }
    })
    .catch(error => {
        status.style.display = 'block';
        status.className = 'import-status error';
        status.innerHTML = '❌ Network error: ' + error.message;
    })
    .finally(() => {
        btn.disabled = false;
        btn.querySelector('.btn__text').textContent = 'Populate Demo Data';
    });
});
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/dashboard.php';
?>