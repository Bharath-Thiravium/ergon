<?php 
$title = 'Finance Dashboard';
$active_page = 'finance';
ob_start(); 
?>

<div class="finance-dashboard">
    <!-- Header -->
    <div class="dashboard-header">
        <div class="header-content">
            <h1>Finance Dashboard</h1>
            <p>Real-time financial insights and analytics</p>
        </div>
        <div class="header-actions">
            <div class="prefix-control">
                <input type="text" id="companyPrefix" placeholder="Company Prefix" maxlength="10">
                <button id="updatePrefix" class="btn btn-primary">Update</button>
            </div>
            <button id="syncData" class="btn btn-success">
                <span class="icon">🔄</span> Sync Data
            </button>
            <button id="refreshStats" class="btn btn-secondary">
                <span class="icon">📊</span> Refresh
            </button>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid">
        <div class="kpi-card revenue">
            <div class="kpi-icon">💰</div>
            <div class="kpi-content">
                <div class="kpi-value" id="totalRevenue">₹0</div>
                <div class="kpi-label">Total Revenue</div>
                <div class="kpi-change" id="revenueChange">+0%</div>
            </div>
        </div>

        <div class="kpi-card received">
            <div class="kpi-icon">✅</div>
            <div class="kpi-content">
                <div class="kpi-value" id="amountReceived">₹0</div>
                <div class="kpi-label">Amount Received</div>
                <div class="kpi-change" id="receivedChange">+0%</div>
            </div>
        </div>

        <div class="kpi-card outstanding">
            <div class="kpi-icon">⏳</div>
            <div class="kpi-content">
                <div class="kpi-value" id="outstandingAmount">₹0</div>
                <div class="kpi-label">Outstanding Amount</div>
                <div class="kpi-change" id="outstandingChange">0%</div>
            </div>
        </div>

        <div class="kpi-card gst">
            <div class="kpi-icon">🏛️</div>
            <div class="kpi-content">
                <div class="kpi-value" id="gstLiability">₹0</div>
                <div class="kpi-label">GST Liability</div>
                <div class="kpi-change" id="gstChange">0%</div>
            </div>
        </div>

        <div class="kpi-card po">
            <div class="kpi-icon">🛒</div>
            <div class="kpi-content">
                <div class="kpi-value" id="poCommitments">₹0</div>
                <div class="kpi-label">PO Commitments</div>
                <div class="kpi-change" id="poChange">+0%</div>
            </div>
        </div>

        <div class="kpi-card claimable">
            <div class="kpi-icon">💸</div>
            <div class="kpi-content">
                <div class="kpi-value" id="claimableAmount">₹0</div>
                <div class="kpi-label">Claimable Amount</div>
                <div class="kpi-change" id="claimableChange">0%</div>
            </div>
        </div>
    </div>

    <!-- Conversion Funnel -->
    <div class="funnel-section">
        <h2>Revenue Conversion Funnel</h2>
        <div class="funnel-container">
            <div class="funnel-stage">
                <div class="stage-number" id="funnelQuotations">0</div>
                <div class="stage-label">Quotations</div>
                <div class="stage-value" id="funnelQuotationValue">₹0</div>
            </div>
            <div class="funnel-arrow">→</div>
            <div class="funnel-stage">
                <div class="stage-number" id="funnelPOs">0</div>
                <div class="stage-label">Purchase Orders</div>
                <div class="stage-value" id="funnelPOValue">₹0</div>
                <div class="stage-conversion" id="quotationToPO">0%</div>
            </div>
            <div class="funnel-arrow">→</div>
            <div class="funnel-stage">
                <div class="stage-number" id="funnelInvoices">0</div>
                <div class="stage-label">Invoices</div>
                <div class="stage-value" id="funnelInvoiceValue">₹0</div>
                <div class="stage-conversion" id="poToInvoice">0%</div>
            </div>
            <div class="funnel-arrow">→</div>
            <div class="funnel-stage">
                <div class="stage-number" id="funnelPayments">0</div>
                <div class="stage-label">Payments</div>
                <div class="stage-value" id="funnelPaymentValue">₹0</div>
                <div class="stage-conversion" id="invoiceToPayment">0%</div>
            </div>
        </div>
    </div>

    <!-- Charts Grid -->
    <div class="charts-grid">
        <div class="chart-card">
            <h3>Quotations Status</h3>
            <canvas id="quotationsChart"></canvas>
        </div>
        
        <div class="chart-card">
            <h3>Invoice Status</h3>
            <canvas id="invoicesChart"></canvas>
        </div>
        
        <div class="chart-card">
            <h3>Aging Analysis</h3>
            <canvas id="agingChart"></canvas>
        </div>
        
        <div class="chart-card">
            <h3>Outstanding by Customer</h3>
            <canvas id="outstandingChart"></canvas>
        </div>
    </div>

    <!-- Data Tables -->
    <div class="tables-grid">
        <div class="table-card">
            <h3>Outstanding Invoices</h3>
            <div class="table-container">
                <table id="outstandingTable">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Customer</th>
                            <th>Due Date</th>
                            <th>Amount</th>
                            <th>Days Overdue</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="6" class="loading">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="table-card">
            <h3>Recent Activities</h3>
            <div class="activities-container" id="activitiesContainer">
                <div class="activity-item loading">Loading activities...</div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
class FinanceDashboard {
    constructor() {
        this.charts = {};
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadPrefix();
        this.loadData();
        this.initCharts();
    }

    bindEvents() {
        document.getElementById('updatePrefix').addEventListener('click', () => this.updatePrefix());
        document.getElementById('syncData').addEventListener('click', () => this.syncData());
        document.getElementById('refreshStats').addEventListener('click', () => this.loadData());
    }

    async loadPrefix() {
        try {
            const response = await fetch('/ergon/finance/new/api?action=prefix');
            const data = await response.json();
            document.getElementById('companyPrefix').value = data.prefix || '';
        } catch (error) {
            console.error('Failed to load prefix:', error);
        }
    }

    async updatePrefix() {
        const prefix = document.getElementById('companyPrefix').value.trim();
        const btn = document.getElementById('updatePrefix');
        
        btn.disabled = true;
        btn.textContent = 'Updating...';
        
        try {
            const formData = new FormData();
            formData.append('prefix', prefix);
            
            const response = await fetch('/ergon/finance/new/api?action=prefix', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                this.showNotification('Prefix updated successfully', 'success');
                this.loadData();
            } else {
                this.showNotification('Failed to update prefix', 'error');
            }
        } catch (error) {
            this.showNotification('Error updating prefix', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = 'Update';
        }
    }

    async syncData() {
        const btn = document.getElementById('syncData');
        btn.disabled = true;
        btn.innerHTML = '<span class="icon">⏳</span> Syncing...';
        
        try {
            const response = await fetch('/ergon/finance/new/api?action=sync');
            const result = await response.json();
            
            if (result.success) {
                this.showNotification(`Synced ${result.synced} tables successfully`, 'success');
                this.loadData();
            } else {
                this.showNotification('Sync failed: ' + result.error, 'error');
            }
        } catch (error) {
            this.showNotification('Sync failed: ' + error.message, 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<span class="icon">🔄</span> Sync Data';
        }
    }

    async loadData() {
        try {
            // Load all data in parallel
            const [stats, funnel, charts, outstanding, activities] = await Promise.all([
                fetch('/ergon/finance/new/api?action=stats').then(r => r.json()),
                fetch('/ergon/finance/new/api?action=funnel').then(r => r.json()),
                fetch('/ergon/finance/new/api?action=charts').then(r => r.json()),
                fetch('/ergon/finance/new/api?action=outstanding').then(r => r.json()),
                fetch('/ergon/finance/new/api?action=activities').then(r => r.json())
            ]);

            this.updateKPIs(stats);
            this.updateFunnel(funnel);
            this.updateCharts(charts);
            this.updateOutstanding(outstanding);
            this.updateActivities(activities);
            
        } catch (error) {
            console.error('Failed to load data:', error);
            this.showNotification('Failed to load dashboard data', 'error');
        }
    }

    updateKPIs(stats) {
        document.getElementById('totalRevenue').textContent = this.formatCurrency(stats.totalRevenue);
        document.getElementById('amountReceived').textContent = this.formatCurrency(stats.amountReceived);
        document.getElementById('outstandingAmount').textContent = this.formatCurrency(stats.outstandingAmount);
        document.getElementById('gstLiability').textContent = this.formatCurrency(stats.gstLiability);
        document.getElementById('poCommitments').textContent = this.formatCurrency(stats.poCommitments);
        document.getElementById('claimableAmount').textContent = this.formatCurrency(stats.claimableAmount);
        
        // Update collection rate as change indicator
        document.getElementById('receivedChange').textContent = stats.collectionRate.toFixed(1) + '%';
    }

    updateFunnel(funnel) {
        document.getElementById('funnelQuotations').textContent = funnel.quotations;
        document.getElementById('funnelQuotationValue').textContent = this.formatCurrency(funnel.quotationValue);
        
        document.getElementById('funnelPOs').textContent = funnel.purchaseOrders;
        document.getElementById('funnelPOValue').textContent = this.formatCurrency(funnel.poValue);
        document.getElementById('quotationToPO').textContent = funnel.quotationToPO.toFixed(1) + '%';
        
        document.getElementById('funnelInvoices').textContent = funnel.invoices;
        document.getElementById('funnelInvoiceValue').textContent = this.formatCurrency(funnel.invoiceValue);
        document.getElementById('poToInvoice').textContent = funnel.poToInvoice.toFixed(1) + '%';
        
        document.getElementById('funnelPayments').textContent = funnel.payments;
        document.getElementById('funnelPaymentValue').textContent = this.formatCurrency(funnel.paymentValue);
        document.getElementById('invoiceToPayment').textContent = funnel.invoiceToPayment.toFixed(1) + '%';
    }

    updateCharts(charts) {
        this.updateChart('quotationsChart', 'pie', charts.quotations);
        this.updateChart('invoicesChart', 'doughnut', charts.invoices);
        this.updateChart('agingChart', 'doughnut', charts.aging);
        this.updateChart('outstandingChart', 'bar', charts.outstanding);
    }

    updateOutstanding(data) {
        const tbody = document.querySelector('#outstandingTable tbody');
        
        if (data.invoices && data.invoices.length > 0) {
            tbody.innerHTML = data.invoices.map(invoice => `
                <tr class="${invoice.days_overdue > 0 ? 'overdue' : ''}">
                    <td>${invoice.invoice_number}</td>
                    <td>${invoice.customer_name}</td>
                    <td>${invoice.due_date}</td>
                    <td>${this.formatCurrency(invoice.outstanding_amount)}</td>
                    <td>${invoice.days_overdue > 0 ? invoice.days_overdue : '-'}</td>
                    <td><span class="status ${invoice.status.toLowerCase()}">${invoice.status}</span></td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="6" class="no-data">No outstanding invoices</td></tr>';
        }
    }

    updateActivities(data) {
        const container = document.getElementById('activitiesContainer');
        
        if (data.activities && data.activities.length > 0) {
            container.innerHTML = data.activities.map(activity => `
                <div class="activity-item">
                    <div class="activity-icon">${activity.icon}</div>
                    <div class="activity-content">
                        <div class="activity-title">${activity.title}</div>
                        <div class="activity-amount">${this.formatCurrency(activity.amount)}</div>
                        <div class="activity-date">${activity.date}</div>
                    </div>
                    <div class="activity-status ${activity.status}">${activity.status}</div>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<div class="activity-item no-data">No recent activities</div>';
        }
    }

    initCharts() {
        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        };

        // Initialize empty charts
        this.charts.quotationsChart = new Chart(document.getElementById('quotationsChart'), {
            type: 'pie',
            data: { labels: [], datasets: [{ data: [], backgroundColor: ['#3b82f6', '#10b981', '#ef4444'] }] },
            options: chartOptions
        });

        this.charts.invoicesChart = new Chart(document.getElementById('invoicesChart'), {
            type: 'doughnut',
            data: { labels: [], datasets: [{ data: [], backgroundColor: ['#10b981', '#f59e0b', '#ef4444'] }] },
            options: chartOptions
        });

        this.charts.agingChart = new Chart(document.getElementById('agingChart'), {
            type: 'doughnut',
            data: { labels: [], datasets: [{ data: [], backgroundColor: ['#10b981', '#f59e0b', '#fb923c', '#ef4444'] }] },
            options: chartOptions
        });

        this.charts.outstandingChart = new Chart(document.getElementById('outstandingChart'), {
            type: 'bar',
            data: { labels: [], datasets: [{ data: [], backgroundColor: '#3b82f6' }] },
            options: {
                ...chartOptions,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    updateChart(chartId, type, data) {
        if (this.charts[chartId] && data) {
            this.charts[chartId].data.labels = data.labels || [];
            this.charts[chartId].data.datasets[0].data = data.data || [];
            this.charts[chartId].update();
        }
    }

    formatCurrency(amount) {
        return '₹' + (amount || 0).toLocaleString('en-IN');
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 6px;
            color: white;
            font-weight: 500;
            z-index: 10000;
            animation: slideIn 0.3s ease;
        `;
        
        if (type === 'success') {
            notification.style.backgroundColor = '#10b981';
        } else if (type === 'error') {
            notification.style.backgroundColor = '#ef4444';
        } else {
            notification.style.backgroundColor = '#3b82f6';
        }
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new FinanceDashboard();
});
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/dashboard.php';
?>

<style>
@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

.finance-dashboard {
    padding: 1rem;
    max-width: 1400px;
    margin: 0 auto;
}

/* Header */
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.header-content h1 {
    margin: 0 0 0.5rem 0;
    font-size: 1.8rem;
    color: #1f2937;
}

.header-content p {
    margin: 0;
    color: #6b7280;
}

.header-actions {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.prefix-control {
    display: flex;
    gap: 0.5rem;
}

.prefix-control input {
    padding: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    width: 120px;
}

.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
}

.btn-primary { background: #3b82f6; color: white; }
.btn-success { background: #10b981; color: white; }
.btn-secondary { background: #6b7280; color: white; }

.btn:hover { transform: translateY(-1px); }
.btn:disabled { opacity: 0.6; cursor: not-allowed; }

/* KPI Cards */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.kpi-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: transform 0.2s;
}

.kpi-card:hover {
    transform: translateY(-2px);
}

.kpi-icon {
    font-size: 2rem;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #f3f4f6;
}

.kpi-content {
    flex: 1;
}

.kpi-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.25rem;
}

.kpi-label {
    font-size: 0.9rem;
    color: #6b7280;
    margin-bottom: 0.25rem;
}

.kpi-change {
    font-size: 0.8rem;
    font-weight: 600;
    color: #10b981;
}

/* Funnel */
.funnel-section {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.funnel-section h2 {
    margin: 0 0 1.5rem 0;
    color: #1f2937;
}

.funnel-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.funnel-stage {
    flex: 1;
    text-align: center;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 8px;
    transition: all 0.3s;
}

.funnel-stage:hover {
    background: #f3f4f6;
    transform: translateY(-2px);
}

.stage-number {
    font-size: 1.8rem;
    font-weight: 700;
    color: #3b82f6;
    margin-bottom: 0.5rem;
}

.stage-label {
    font-size: 0.9rem;
    color: #6b7280;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stage-value {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.25rem;
}

.stage-conversion {
    font-size: 0.8rem;
    color: #10b981;
    font-weight: 600;
}

.funnel-arrow {
    font-size: 1.5rem;
    color: #9ca3af;
}

/* Charts */
.charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.chart-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    height: 350px;
}

.chart-card h3 {
    margin: 0 0 1rem 0;
    color: #1f2937;
    font-size: 1.1rem;
}

.chart-card canvas {
    max-height: 280px;
}

/* Tables */
.tables-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1.5rem;
}

.table-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.table-card h3 {
    margin: 0 0 1rem 0;
    color: #1f2937;
}

.table-container {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}

th {
    background: #f9fafb;
    font-weight: 600;
    color: #374151;
}

tr.overdue {
    background: #fef2f2;
}

.status {
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status.pending {
    background: #fef3c7;
    color: #92400e;
}

.status.overdue {
    background: #fee2e2;
    color: #991b1b;
}

.status.paid {
    background: #d1fae5;
    color: #065f46;
}

.loading, .no-data {
    text-align: center;
    color: #6b7280;
    font-style: italic;
}

/* Activities */
.activities-container {
    max-height: 400px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border-bottom: 1px solid #e5e7eb;
    transition: background 0.2s;
}

.activity-item:hover {
    background: #f9fafb;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    font-size: 1.25rem;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f3f4f6;
    border-radius: 50%;
}

.activity-content {
    flex: 1;
}

.activity-title {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.25rem;
}

.activity-amount {
    font-weight: 500;
    color: #3b82f6;
    margin-bottom: 0.25rem;
}

.activity-date {
    font-size: 0.8rem;
    color: #6b7280;
}

.activity-status {
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

/* Responsive */
@media (max-width: 768px) {
    .dashboard-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .header-actions {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .funnel-container {
        flex-direction: column;
    }
    
    .funnel-arrow {
        transform: rotate(90deg);
    }
    
    .tables-grid {
        grid-template-columns: 1fr;
    }
    
    .charts-grid {
        grid-template-columns: 1fr;
    }
}
</style>