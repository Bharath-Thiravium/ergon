<?php 
$title = 'Finance Dashboard';
$active_page = 'finance';
ob_start(); 
?>

<div class="container-fluid">
    <div class="dashboard-header">
        <div class="dashboard-header__title">
            <h1>💰 Finance Dashboard</h1>
            <p>Financial insights and analytics</p>
        </div>
        <div class="dashboard-header__actions">
            <button id="populateDataBtn" class="btn btn--success btn--sm">
                <span class="btn__text">📊 Load Demo Data</span>
            </button>
            <button id="refreshBtn" class="btn btn--primary btn--sm">
                <span class="btn__text">🔄 Refresh</span>
            </button>
            <a href="/ergon/finance/import" class="btn btn--secondary btn--sm">
                <span class="btn__text">📥 Import</span>
            </a>
        </div>
    </div>

    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon">💰</div>
            <div class="kpi-value" id="totalInvoiceAmount">₹0</div>
            <div class="kpi-label">Total Invoices</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">✅</div>
            <div class="kpi-value" id="invoiceReceived">₹0</div>
            <div class="kpi-label">Amount Received</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">⏳</div>
            <div class="kpi-value" id="pendingInvoiceAmount">₹0</div>
            <div class="kpi-label">Outstanding</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">📝</div>
            <div class="kpi-value" id="quotationCount">0</div>
            <div class="kpi-label">Quotations</div>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">📊 Finance Overview</h2>
            </div>
            <div class="card__body">
                <canvas id="financeChart" width="400" height="200"></canvas>
            </div>
        </div>

        <div class="card">
            <div class="card__header">
                <h2 class="card__title">⚠️ Outstanding Invoices</h2>
            </div>
            <div class="card__body">
                <div class="table-responsive">
                    <table class="table" id="outstandingTable">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Due Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5" class="text-center">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="loadingIndicator" class="loading-indicator" style="display:none;">
        <div class="spinner"></div>
        <p>Loading finance data...</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let financeChart;

document.addEventListener('DOMContentLoaded', function() {
    initChart();
    loadDashboardData();
    
    document.getElementById('populateDataBtn').addEventListener('click', populateDemo);
    document.getElementById('refreshBtn').addEventListener('click', loadDashboardData);
});

function initChart() {
    const ctx = document.getElementById('financeChart');
    if (ctx) {
        financeChart = new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Received', 'Outstanding'],
                datasets: [{
                    data: [0, 0],
                    backgroundColor: ['#10b981', '#f59e0b']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
}

async function loadDashboardData() {
    showLoading(true);
    try {
        const response = await fetch('/ergon/finance/dashboard-stats');
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        
        const text = await response.text();
        const data = JSON.parse(text);
        
        updateKPIs(data);
        updateChart(data);
        loadOutstandingInvoices();
        
        if (data.message) {
            showNotification(data.message, 'info');
        }
    } catch (error) {
        console.error('Failed to load data:', error);
        showNotification('Failed to load finance data: ' + error.message, 'error');
        updateKPIs({});
    } finally {
        showLoading(false);
    }
}

function updateKPIs(data) {
    document.getElementById('totalInvoiceAmount').textContent = `₹${(data.totalInvoiceAmount || 0).toLocaleString()}`;
    document.getElementById('invoiceReceived').textContent = `₹${(data.invoiceReceived || 0).toLocaleString()}`;
    document.getElementById('pendingInvoiceAmount').textContent = `₹${(data.pendingInvoiceAmount || 0).toLocaleString()}`;
    
    const funnel = data.conversionFunnel || {};
    document.getElementById('quotationCount').textContent = funnel.quotations || 0;
}

function updateChart(data) {
    if (financeChart) {
        const received = data.invoiceReceived || 0;
        const pending = data.pendingInvoiceAmount || 0;
        
        financeChart.data.datasets[0].data = [received, pending];
        financeChart.update();
    }
}

async function loadOutstandingInvoices() {
    try {
        const response = await fetch('/ergon/finance/outstanding-invoices');
        if (!response.ok) throw new Error('API not available');
        
        const data = await response.json();
        const tbody = document.querySelector('#outstandingTable tbody');
        
        if (data.invoices && data.invoices.length > 0) {
            tbody.innerHTML = data.invoices.slice(0, 10).map(invoice => `
                <tr>
                    <td>${invoice.invoice_number}</td>
                    <td>${invoice.customer_name}</td>
                    <td>₹${invoice.outstanding_amount.toLocaleString()}</td>
                    <td>${invoice.due_date}</td>
                    <td><span class="badge ${invoice.daysOverdue > 0 ? 'badge--danger' : 'badge--warning'}">${invoice.status}</span></td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">No outstanding invoices</td></tr>';
        }
    } catch (error) {
        console.error('Failed to load outstanding invoices:', error);
        document.querySelector('#outstandingTable tbody').innerHTML = 
            '<tr><td colspan="5" class="text-center text-danger">Error loading data</td></tr>';
    }
}

async function populateDemo() {
    const btn = document.getElementById('populateDataBtn');
    btn.disabled = true;
    btn.querySelector('.btn__text').textContent = '⏳ Loading...';
    
    try {
        const response = await fetch('/ergon/finance/import', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=populate_demo'
        });
        
        const data = await response.json();
        if (data.success) {
            showNotification('Demo data loaded successfully!', 'success');
            setTimeout(() => loadDashboardData(), 1000);
        } else {
            showNotification('Failed to load demo data: ' + data.error, 'error');
        }
    } catch (error) {
        showNotification('Network error: ' + error.message, 'error');
    } finally {
        btn.disabled = false;
        btn.querySelector('.btn__text').textContent = '📊 Load Demo Data';
    }
}

function showLoading(show) {
    const indicator = document.getElementById('loadingIndicator');
    indicator.style.display = show ? 'flex' : 'none';
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification--${type}`;
    notification.style.cssText = `
        position: fixed; top: 20px; right: 20px; padding: 12px 20px;
        background: ${type === 'error' ? '#f8d7da' : type === 'success' ? '#d4edda' : '#fff3cd'};
        border: 1px solid ${type === 'error' ? '#f5c6cb' : type === 'success' ? '#c3e6cb' : '#ffeaa7'};
        color: ${type === 'error' ? '#721c24' : type === 'success' ? '#155724' : '#856404'};
        border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 10000;
        max-width: 400px; font-size: 14px;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 5000);
}
</script>

<style>
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 8px;
}

.dashboard-header__title h1 {
    margin: 0 0 0.25rem 0;
    font-size: 1.5rem;
    color: var(--text-primary);
}

.dashboard-header__title p {
    margin: 0;
    color: var(--text-secondary);
}

.dashboard-header__actions {
    display: flex;
    gap: 0.5rem;
}

.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.kpi-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 1.5rem;
    text-align: center;
}

.kpi-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.kpi-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 0.5rem;
}

.kpi-label {
    font-size: 0.9rem;
    color: var(--text-secondary);
}

.dashboard-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

.card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 8px;
}

.card__header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.card__title {
    margin: 0;
    font-size: 1.1rem;
    color: var(--text-primary);
}

.card__body {
    padding: 1.5rem;
}

.loading-indicator {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    color: white;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid rgba(255,255,255,0.3);
    border-top: 4px solid white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.badge {
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge--warning {
    background: rgba(217, 119, 6, 0.1);
    color: var(--warning);
}

.badge--danger {
    background: rgba(220, 38, 38, 0.1);
    color: var(--error);
}

@media (max-width: 768px) {
    .dashboard-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .kpi-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/dashboard.php';
?>