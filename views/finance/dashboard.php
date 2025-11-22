<?php 
$title = 'Finance Dashboard';
$active_page = 'finance';
ob_start(); 
?>

<div class="container-fluid">
    <!-- Header Actions -->
    <div class="header-actions">
        <button id="syncBtn" class="btn btn--primary">🔄 Sync Data</button>
        <button id="exportBtn" class="btn btn--secondary">📥 Export Dashboard</button>
        <button onclick="window.open('/ergon/finance/download-database', '_blank')" class="btn btn--info">💾 Download Database</button>
        <input type="text" id="companyPrefix" class="form-control" placeholder="Company Prefix (e.g., BKC)" maxlength="10">
        <button id="updatePrefixBtn" class="btn btn--secondary">🏢 Update Prefix</button>
        <select id="dateFilter" class="form-control">
            <option value="all">All Time</option>
            <option value="30">Last 30 Days</option>
            <option value="90">Last 90 Days</option>
            <option value="365">Last Year</option>
        </select>
    </div>

    <!-- Top-Level KPI Cards -->
    <div class="dashboard-grid">
        <div class="kpi-card kpi-card--success">
            <div class="kpi-card__header">
                <div class="kpi-card__icon">💰</div>
                <div class="kpi-card__trend" id="invoiceTrend">↗ +0%</div>
            </div>
            <div class="kpi-card__value" id="totalInvoiceAmount">₹0</div>
            <div class="kpi-card__label">Total Invoice Amount</div>
            <div class="kpi-card__status">Revenue</div>
        </div>
        
        <div class="kpi-card kpi-card--success">
            <div class="kpi-card__header">
                <div class="kpi-card__icon">✅</div>
                <div class="kpi-card__trend" id="receivedTrend">↗ +0%</div>
            </div>
            <div class="kpi-card__value" id="invoiceReceived">₹0</div>
            <div class="kpi-card__label">Amount Received</div>
            <div class="kpi-card__status">Collected</div>
        </div>
        
        <div class="kpi-card kpi-card--warning">
            <div class="kpi-card__header">
                <div class="kpi-card__icon">⏳</div>
                <div class="kpi-card__trend" id="pendingTrend">— 0%</div>
            </div>
            <div class="kpi-card__value" id="pendingInvoiceAmount">₹0</div>
            <div class="kpi-card__label">Pending Invoice Amount</div>
            <div class="kpi-card__status kpi-card__status--pending">Outstanding</div>
        </div>
        
        <div class="kpi-card kpi-card--info">
            <div class="kpi-card__header">
                <div class="kpi-card__icon">🏛️</div>
                <div class="kpi-card__trend" id="gstTrend">— 0%</div>
            </div>
            <div class="kpi-card__value" id="pendingGSTAmount">₹0</div>
            <div class="kpi-card__label">Pending GST Amount</div>
            <div class="kpi-card__status">Tax Liability</div>
        </div>
        
        <div class="kpi-card kpi-card--primary">
            <div class="kpi-card__header">
                <div class="kpi-card__icon">🛒</div>
                <div class="kpi-card__trend" id="poTrend">↗ +0%</div>
            </div>
            <div class="kpi-card__value" id="pendingPOValue">₹0</div>
            <div class="kpi-card__label">Pending PO Value</div>
            <div class="kpi-card__status">Commitments</div>
        </div>
        
        <div class="kpi-card kpi-card--secondary">
            <div class="kpi-card__header">
                <div class="kpi-card__icon">💸</div>
                <div class="kpi-card__trend" id="claimableTrend">— 0%</div>
            </div>
            <div class="kpi-card__value" id="claimableAmount">₹0</div>
            <div class="kpi-card__label">Claimable Amount</div>
            <div class="kpi-card__status">Recoverable</div>
        </div>
    </div>

    <!-- Conversion Funnel -->
    <div class="dashboard-grid">
        <div class="card card--full-width">
            <div class="card__header">
                <h2 class="card__title">🔄 Revenue Conversion Funnel</h2>
                <select id="customerFilter" class="form-control" style="width: 200px;">
                    <option value="">All Customers</option>
                </select>
                <span id="customerLoader" style="margin-left:8px;display:none" aria-hidden="true"></span>
            </div>
            <div class="card__body">
                <div class="funnel-container">
                    <div class="funnel-stage">
                        <div class="funnel-number" id="funnelQuotations">0</div>
                        <div class="funnel-label">Quotations</div>
                        <div class="funnel-value" id="funnelQuotationValue">₹0</div>
                    </div>
                    <div class="funnel-arrow">→</div>
                    <div class="funnel-stage">
                        <div class="funnel-number" id="funnelPOs">0</div>
                        <div class="funnel-label">Purchase Orders</div>
                        <div class="funnel-value" id="funnelPOValue">₹0</div>
                        <div class="funnel-conversion" id="quotationToPO">0%</div>
                    </div>
                    <div class="funnel-arrow">→</div>
                    <div class="funnel-stage">
                        <div class="funnel-number" id="funnelInvoices">0</div>
                        <div class="funnel-label">Invoices</div>
                        <div class="funnel-value" id="funnelInvoiceValue">₹0</div>
                        <div class="funnel-conversion" id="poToInvoice">0%</div>
                    </div>
                    <div class="funnel-arrow">→</div>
                    <div class="funnel-stage">
                        <div class="funnel-number" id="funnelPayments">0</div>
                        <div class="funnel-label">Payments</div>
                        <div class="funnel-value" id="funnelPaymentValue">₹0</div>
                        <div class="funnel-conversion" id="invoiceToPayment">0%</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section - Row 1 (Outstanding & Aging moved higher) -->
    <div class="dashboard-grid dashboard-grid--2-col">
        <div class="stat-card">
            <div class="stat-card__header">
                <h3 class="stat-card__title">📋 Outstanding by Customer</h3>
                <div class="stat-card__controls">
                    <label for="outstandingTopN" class="sr-only">Top N</label>
                    <select id="outstandingTopN" class="form-control form-control--sm" title="Select top N customers">
                        <option value="5">Top 5</option>
                        <option value="10" selected>Top 10</option>
                        <option value="20">Top 20</option>
                        <option value="50">Top 50</option>
                    </select>
                    <button id="outstandingDownload" class="btn-icon" title="Download CSV: outstanding by customer">📥</button>
                </div>
            </div>
            <div class="stat-card__body">
                <canvas id="outstandingByCustomerChart" height="220"></canvas>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card__header">
                <h3 class="stat-card__title">⏳ Aging Buckets</h3>
                <div class="stat-card__controls">
                    <button class="btn-icon" title="Download CSV: aging buckets" onclick="exportChart('aging')">📥</button>
                </div>
            </div>
            <div class="stat-card__body">
                <canvas id="agingBucketsChart" height="220"></canvas>
            </div>
        </div>
    </div>

    <!-- Charts Section - Row 2 (Quotations & Purchase Orders) -->
    <div class="dashboard-grid dashboard-grid--2-col">
        <div class="stat-card">
            <div class="stat-card__header">
                <h3 class="stat-card__title">📝 Quotations Overview</h3>
                <div class="stat-card__controls">
                    <button class="btn-icon" title="Download CSV: quotations" onclick="exportChart('quotations')">📥</button>
                </div>
            </div>
            <div class="stat-card__body">
                <canvas id="quotationsChart" height="200"></canvas>
                <div class="chart-summary">
                    <div class="summary-item">
                        <span class="summary-label">Draft:</span>
                        <span class="summary-value" id="quotationsDraft">0</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Revised:</span>
                        <span class="summary-value" id="quotationsRevised">0</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card__header">
                <h3 class="stat-card__title">🛒 Purchase Orders</h3>
                <div class="stat-card__controls">
                    <button class="btn-icon" title="Download CSV: purchase orders" onclick="exportChart('purchase_orders')">📥</button>
                </div>
            </div>
            <div class="stat-card__body">
                <canvas id="purchaseOrdersChart" height="200"></canvas>
                <div class="highlight-card">
                    <div class="highlight-label">Largest PO:</div>
                    <div class="highlight-value" id="largestPO">₹0</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section - Row 3 (Invoices & Payments) -->
    <div class="dashboard-grid dashboard-grid--2-col">
        <div class="stat-card">
            <div class="stat-card__header">
                <h3 class="stat-card__title">💰 Invoice Status</h3>
                <div class="stat-card__controls">
                    <button class="btn-icon" title="Download CSV: invoices" onclick="exportChart('invoices')">📥</button>
                </div>
            </div>
            <div class="stat-card__body">
                <canvas id="invoicesChart" height="200"></canvas>
                <div class="chart-summary">
                    <div class="summary-item">
                        <span class="summary-label">Overdue:</span>
                        <span class="summary-value" id="overdueCount">0</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card__header">
                <h3 class="stat-card__title">💳 Payments</h3>
                <div class="stat-card__controls">
                    <button class="btn-icon" title="Download CSV: payments" onclick="exportChart('payments')">📥</button>
                </div>
            </div>
            <div class="stat-card__body">
                <div id="paymentsEmpty" class="empty-state">
                    <div class="empty-icon">💳</div>
                    <h4>No Payments Recorded</h4>
                    <p style="font-size:0.85rem">Payment data will appear here once transactions are recorded</p>
                </div>
                <canvas id="paymentsChart" height="200" style="display:none"></canvas>
            </div>
        </div>
    </div>

    <!-- Outstanding Invoices Table -->
    <div class="dashboard-grid">
        <div class="card card--full-width">
            <div class="card__header">
                <h2 class="card__title">⚠️ Outstanding Invoices</h2>
                <button class="btn btn--sm" onclick="exportTable('outstanding')">Export</button>
            </div>
            <div class="card__body">
                <div class="table-responsive">
                    <table class="table" id="outstandingTable">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Customer</th>
                                <th>Due Date</th>
                                <th>Outstanding Amount</th>
                                <th>Days Overdue</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="text-center">Loading outstanding invoices...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Quotations -->
    <div class="dashboard-grid">
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">📝 Recent Quotations</h2>
            </div>
            <div class="card__body">
                <div id="recentQuotations">
                    <div class="form-group">
                        <div class="form-label">Loading recent quotations...</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">💰 Cash Flow Projection</h2>
            </div>
            <div class="card__body">
                <div class="cash-flow-summary">
                    <div class="flow-item">
                        <div class="flow-label">Expected Inflow:</div>
                        <div class="flow-value flow-positive" id="expectedInflow">₹0</div>
                    </div>
                    <div class="flow-item">
                        <div class="flow-label">PO Commitments:</div>
                        <div class="flow-value flow-negative" id="poCommitments">₹0</div>
                    </div>
                    <div class="flow-item">
                        <div class="flow-label">Net Cash Flow:</div>
                        <div class="flow-value" id="netCashFlow">₹0</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>


let quotationsChart, purchaseOrdersChart, invoicesChart, paymentsChart;
let outstandingByCustomerChart;
let agingBucketsChart;

document.addEventListener('DOMContentLoaded', function() {
    initCharts();
    
    document.getElementById('syncBtn').addEventListener('click', syncFinanceData);
    document.getElementById('exportBtn').addEventListener('click', exportDashboard);
    document.getElementById('updatePrefixBtn').addEventListener('click', updateCompanyPrefix);
    document.getElementById('dateFilter').addEventListener('change', filterByDate);
    document.getElementById('customerFilter').addEventListener('change', filterByCustomer);
    // Outstanding top-N control
    const topN = document.getElementById('outstandingTopN');
    if (topN) topN.addEventListener('change', () => loadOutstandingByCustomer(parseInt(topN.value, 10)));
    const outDownload = document.getElementById('outstandingDownload');
    if (outDownload) outDownload.addEventListener('click', () => {
        const limit = parseInt(document.getElementById('outstandingTopN').value || '10', 10);
        // Use server-side export endpoint which supports `limit`
        window.open(`/ergon/finance/export-outstanding?limit=${limit}`, '_blank');
    });
    
    // Load prefix first, then dashboard data
    loadCompanyPrefix().then(() => {
        loadCustomers();
        loadDashboardData();
    });
});

function initCharts() {
    // Simple, consistent chart styles for dashboard
    const chartDefaults = {
        responsive: true,
        maintainAspectRatio: true,
        animation: { duration: 250 },
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const v = context.raw || 0;
                        if (typeof v === 'number') return '₹' + Number(v).toLocaleString();
                        return String(v);
                    }
                }
            }
        }
    };

    // Quotations Line Chart (simple line)
    const quotationsCtx = document.getElementById('quotationsChart');
    if (quotationsCtx) {
        quotationsChart = new Chart(quotationsCtx.getContext('2d'), {
            type: 'line',
            data: { labels: [], datasets: [{ label: 'Quotations', data: [], borderColor: '#1e40af', backgroundColor: 'rgba(30,64,175,0.08)', tension: 0.25, pointRadius: 2 }] },
            options: Object.assign({}, chartDefaults, { plugins: { legend: { display: false } } })
        });
    }

    // Purchase Orders Bar Chart (monthly simple bars)
    const poCtx = document.getElementById('purchaseOrdersChart');
    if (poCtx) {
        purchaseOrdersChart = new Chart(poCtx.getContext('2d'), {
            type: 'bar',
            data: { labels: [], datasets: [{ label: 'PO Amount', data: [], backgroundColor: '#059669' }] },
            options: Object.assign({}, chartDefaults, { scales: { y: { ticks: { callback: v => '₹' + Number(v).toLocaleString() } } } })
        });
    }

    // Invoices Donut Chart (clean palette)
    const invoicesCtx = document.getElementById('invoicesChart');
    if (invoicesCtx) {
        invoicesChart = new Chart(invoicesCtx.getContext('2d'), {
            type: 'doughnut',
            data: { labels: ['Paid','Unpaid','Overdue'], datasets: [{ data: [0,0,0], backgroundColor: ['#059669','#f59e0b','#dc2626'] }] },
            options: Object.assign({}, chartDefaults, { plugins: { legend: { position: 'bottom' } }, cutout: '60%' })
        });
    }

    // Outstanding by Customer Horizontal Bar
    const outstandingCtx = document.getElementById('outstandingByCustomerChart');
    if (outstandingCtx) {
        outstandingByCustomerChart = new Chart(outstandingCtx.getContext('2d'), {
            type: 'bar',
            data: { labels: [], datasets: [{ label: 'Outstanding', data: [], backgroundColor: '#ef4444' }] },
            options: Object.assign({}, chartDefaults, { indexAxis: 'y', scales: { x: { ticks: { callback: v => '₹' + Number(v).toLocaleString() } }, y: { ticks: { autoSkip: false } } } })
        });
    }

    // Aging Buckets Donut Chart
    const agingCtx = document.getElementById('agingBucketsChart');
    if (agingCtx) {
        agingBucketsChart = new Chart(agingCtx.getContext('2d'), {
            type: 'doughnut',
            data: { labels: [], datasets: [{ data: [], backgroundColor: ['#059669','#f59e0b','#fb923c','#dc2626'] }] },
            options: Object.assign({}, chartDefaults, { plugins: { legend: { position: 'bottom' } }, cutout: '60%' })
        });
    }
}

async function showTableStructure() {
    const btn = document.getElementById('structureBtn');
    btn.disabled = true;
    btn.textContent = 'Loading...';
    
    try {
        const response = await fetch('/ergon/finance/structure');
        const data = await response.json();
        
        if (data.error) {
            alert('Error: ' + data.error);
            return;
        }
        
        renderTableStructure(data.tables);
        
        document.getElementById('structureModal').style.display = 'block';
        
    } catch (error) {
        alert('Failed to load structure: ' + error.message);
    } finally {
        btn.disabled = false;
        btn.textContent = 'View Table Structure';
    }
}

function renderTableStructure(tables) {
    const container = document.getElementById('structureContainer');
    
    let html = '<div class="followups-modern">';
    
    tables.forEach((table, index) => {
        html += `
            <div class="followup-card">
                <div class="followup-card__header">
                    <div class="followup-icon task-linked">📋</div>
                    <div class="followup-title-section">
                        <h4 class="followup-title">${table.display_name}</h4>
                        <div class="followup-badges">
                            <span class="badge badge--info">${table.column_count} columns</span>
                            <span class="badge badge--success">${table.actual_rows} rows</span>
                        </div>
                    </div>
                    <button class="btn--modern btn--outline" onclick="toggleStructure(this)">
                        <span class="expand-icon">▼</span>
                    </button>
                </div>
                <div class="structure-details" style="display: ${index === 0 ? 'block' : 'none'}">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Column</th>
                                    <th>Type</th>
                                    <th>Nullable</th>
                                    <th>Default</th>
                                </tr>
                            </thead>
                            <tbody>`;
        
        table.columns.forEach(col => {
            html += `
                <tr>
                    <td><code>${col.name}</code></td>
                    <td><span class="badge badge--info">${col.type}</span></td>
                    <td>${col.nullable ? '✓' : '✗'}</td>
                    <td>${col.default || '-'}</td>
                </tr>`;
        });
        
        html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>`;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

function toggleStructure(button) {
    const card = button.closest('.followup-card');
    const details = card.querySelector('.structure-details');
    const icon = button.querySelector('.expand-icon');
    
    if (details.style.display === 'none') {
        details.style.display = 'block';
        icon.textContent = '▲';
    } else {
        details.style.display = 'none';
        icon.textContent = '▼';
    }
}

function closeStructureModal() {
    document.getElementById('structureModal').style.display = 'none';
}

function analyzeAllTables() {
    const btn = document.getElementById('analyzeBtn');
    btn.disabled = true;
    btn.textContent = 'Generating CSV...';
    
    // Create download link
    const link = document.createElement('a');
    link.href = '/ergon/finance/analyze';
    link.download = 'finance_analysis.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    setTimeout(() => {
        btn.disabled = false;
        btn.textContent = 'Analyze All Tables';
    }, 1000);
}

async function syncFinanceData() {
    const btn = document.getElementById('syncBtn');
    btn.disabled = true;
    btn.textContent = 'Syncing...';
    
    try {
        const response = await fetch('/ergon/finance/sync', {method: 'POST'});
        const result = await response.json();
        
        if (result.error) {
            alert('Sync failed: ' + result.error);
        } else {
            alert(`Synced ${result.tables} finance tables successfully`);
            loadDashboardData();
        }
    } catch (error) {
        alert('Sync failed: ' + error.message);
    } finally {
        btn.disabled = false;
        btn.textContent = '🔄 Sync Data';
    }
}

async function loadDashboardData() {
    try {
        const customerFilter = document.getElementById('customerFilter').value;
        const url = customerFilter ? `/ergon/finance/dashboard-stats?customer=${encodeURIComponent(customerFilter)}` : '/ergon/finance/dashboard-stats';
        const response = await fetch(url);
        const data = await response.json();
        
        updateKPICards(data);
        updateConversionFunnel(data);
        updateCharts(data);
        loadOutstandingInvoices();
        loadRecentQuotations();
        updateCashFlow(data);
        
    } catch (error) {
        console.error('Failed to load dashboard data:', error);
    }
}

function updateKPICards(data) {
    // Total Invoice Amount
    document.getElementById('totalInvoiceAmount').textContent = `₹${(data.totalInvoiceAmount || 0).toLocaleString()}`;
    
    // Invoice Amount Received
    document.getElementById('invoiceReceived').textContent = `₹${(data.invoiceReceived || 0).toLocaleString()}`;
    
    // Pending Invoice Amount
    document.getElementById('pendingInvoiceAmount').textContent = `₹${(data.pendingInvoiceAmount || 0).toLocaleString()}`;
    
    // Pending GST Amount
    document.getElementById('pendingGSTAmount').textContent = `₹${(data.pendingGSTAmount || 0).toLocaleString()}`;
    
    // Pending PO Value
    document.getElementById('pendingPOValue').textContent = `₹${(data.pendingPOValue || 0).toLocaleString()}`;
    
    // Claimable Amount
    document.getElementById('claimableAmount').textContent = `₹${(data.claimableAmount || 0).toLocaleString()}`;
}

function updateConversionFunnel(data) {
    const funnel = data.conversionFunnel || {};
    
    document.getElementById('funnelQuotations').textContent = funnel.quotations || 0;
    document.getElementById('funnelQuotationValue').textContent = `₹${(funnel.quotationValue || 0).toLocaleString()}`;
    
    document.getElementById('funnelPOs').textContent = funnel.purchaseOrders || 0;
    document.getElementById('funnelPOValue').textContent = `₹${(funnel.poValue || 0).toLocaleString()}`;
    document.getElementById('quotationToPO').textContent = `${funnel.quotationToPO || 0}%`;
    
    document.getElementById('funnelInvoices').textContent = funnel.invoices || 0;
    document.getElementById('funnelInvoiceValue').textContent = `₹${(funnel.invoiceValue || 0).toLocaleString()}`;
    document.getElementById('poToInvoice').textContent = `${funnel.poToInvoice || 0}%`;
    
    document.getElementById('funnelPayments').textContent = funnel.payments || 0;
    document.getElementById('funnelPaymentValue').textContent = `₹${(funnel.paymentValue || 0).toLocaleString()}`;
    document.getElementById('invoiceToPayment').textContent = `${funnel.invoiceToPayment || 0}%`;
}

async function updateCharts() {
    try {
        // Update Quotations Chart
        const quotationsResponse = await fetch('/ergon/finance/visualization?type=quotations');
        const quotationsData = await quotationsResponse.json();
        
        if (quotationsChart && quotationsData.data) {
            quotationsChart.data.datasets[0].data = quotationsData.data;
            quotationsChart.update();
            
            document.getElementById('quotationsDraft').textContent = quotationsData.draft || 0;
            document.getElementById('quotationsRevised').textContent = quotationsData.revised || 0;
        }
        
        // Update Purchase Orders Chart
        const poResponse = await fetch('/ergon/finance/visualization?type=purchase_orders');
        const poData = await poResponse.json();
        
        if (purchaseOrdersChart && poData.labels) {
            purchaseOrdersChart.data.labels = poData.labels;
            purchaseOrdersChart.data.datasets[0].data = poData.data;
            purchaseOrdersChart.update();
            
            document.getElementById('largestPO').textContent = `₹${(poData.largest || 0).toLocaleString()}`;
        }
        
        // Update Invoices Chart
        const invoicesResponse = await fetch('/ergon/finance/visualization?type=invoices');
        const invoicesData = await invoicesResponse.json();
        
        if (invoicesChart && invoicesData.data) {
            invoicesChart.data.datasets[0].data = invoicesData.data;
            invoicesChart.update();
            
            // Show overdue alert
            if (invoicesData.overdueCount > 0) {
                document.getElementById('overdueAlert').style.display = 'block';
                document.getElementById('overdueCount').textContent = invoicesData.overdueCount;
            }
        }
        // Update Outstanding by Customer Chart
        try {
            const topNEl = document.getElementById('outstandingTopN');
            const limit = topNEl ? parseInt(topNEl.value || '10', 10) : 10;
            await loadOutstandingByCustomer(limit);
        } catch (err) {
            console.error('Failed to load outstanding-by-customer:', err);
        }

        // Update Aging Buckets Chart
        try {
            const agingResp = await fetch('/ergon/finance/aging-buckets');
            const agingData = await agingResp.json();
            if (agingBucketsChart && agingData.labels) {
                agingBucketsChart.data.labels = agingData.labels;
                agingBucketsChart.data.datasets[0].data = agingData.data;
                agingBucketsChart.update();
            }
        } catch (err) {
            console.error('Failed to load aging-buckets:', err);
        }
        
    } catch (error) {
        console.error('Failed to update charts:', error);
    }
}

async function loadOutstandingInvoices() {
    try {
        const response = await fetch('/ergon/finance/outstanding-invoices');
        const data = await response.json();
        
        const tbody = document.querySelector('#outstandingTable tbody');
        if (data.invoices && data.invoices.length > 0) {
            tbody.innerHTML = data.invoices.map(invoice => `
                <tr class="${invoice.daysOverdue > 0 ? 'table-row--danger' : ''}">
                    <td>${invoice.invoice_number}</td>
                    <td>${invoice.customer_name}</td>
                    <td>${invoice.due_date}</td>
                    <td>₹${invoice.outstanding_amount.toLocaleString()}</td>
                    <td>${invoice.daysOverdue > 0 ? invoice.daysOverdue : '-'}</td>
                    <td><span class="badge ${invoice.daysOverdue > 0 ? 'badge--danger' : 'badge--warning'}">${invoice.status}</span></td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">No outstanding invoices</td></tr>';
        }
    } catch (error) {
        console.error('Failed to load outstanding invoices:', error);
    }
}

// Load outstanding-by-customer and update chart
async function loadOutstandingByCustomer(limit = 10) {
    try {
        const resp = await fetch(`/ergon/finance/outstanding-by-customer?limit=${limit}`);
        const data = await resp.json();
        if (outstandingByCustomerChart && data.labels) {
            outstandingByCustomerChart.data.labels = data.labels;
            outstandingByCustomerChart.data.datasets[0].data = data.data;
            outstandingByCustomerChart.update();
        }
    } catch (err) {
        console.error('Failed to load outstanding by customer:', err);
    }
}

// (Server-side export used via /ergon/finance/export-outstanding)

async function loadRecentQuotations() {
    try {
        const response = await fetch('/ergon/finance/recent-quotations');
        const data = await response.json();
        
        const container = document.getElementById('recentQuotations');
        if (data.quotations && data.quotations.length > 0) {
            container.innerHTML = data.quotations.map(quote => `
                <div class="form-group">
                    <div class="form-label">${quote.quotation_number}</div>
                    <p>₹${quote.total_amount.toLocaleString()} - ${quote.customer_name}</p>
                    <small>Expires: ${quote.valid_until}</small>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<div class="form-group"><div class="form-label">No recent quotations</div></div>';
        }
    } catch (error) {
        console.error('Failed to load recent quotations:', error);
    }
}

function updateCashFlow(data) {
    const cashFlow = data.cashFlow || {};
    
    document.getElementById('expectedInflow').textContent = `₹${(cashFlow.expectedInflow || 0).toLocaleString()}`;
    document.getElementById('poCommitments').textContent = `₹${(cashFlow.poCommitments || 0).toLocaleString()}`;
    
    const netFlow = (cashFlow.expectedInflow || 0) - (cashFlow.poCommitments || 0);
    const netElement = document.getElementById('netCashFlow');
    netElement.textContent = `₹${netFlow.toLocaleString()}`;
    netElement.className = `flow-value ${netFlow >= 0 ? 'flow-positive' : 'flow-negative'}`;
}

function toggleView(module, viewType) {
    const viewContainer = document.getElementById(`${module}View`);
    const listView = viewContainer.querySelector('.data-list');
    const gridView = viewContainer.querySelector('.data-grid');
    const buttons = viewContainer.parentElement.querySelectorAll('.view-btn');
    
    // Update button states
    buttons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // Toggle views
    if (viewType === 'list') {
        listView.classList.add('active');
        gridView.classList.remove('active');
    } else {
        listView.classList.remove('active');
        gridView.classList.add('active');
        
        // Load grid data if not already loaded
        loadGridView(module);
    }
}



function loadGridView(module) {
    const gridContainer = document.getElementById(`${module}Grid`);
    
    // Copy list data to grid format
    const listContainer = document.getElementById(`${module}List` || `${module}Container`);
    const listItems = listContainer.querySelectorAll('.list-item, .form-group');
    
    let gridHtml = '<div class="grid-container">';
    
    listItems.forEach(item => {
        if (item.classList.contains('list-item')) {
            const title = item.querySelector('.list-title')?.textContent || 'N/A';
            const amount = item.querySelector('.list-amount')?.textContent || '';
            const customer = item.querySelector('.list-customer')?.textContent || '';
            const status = item.querySelector('.list-status')?.textContent || '';
            
            gridHtml += `
                <div class="grid-item">
                    <div class="grid-header">
                        <h4>${title}</h4>
                        <span class="grid-amount">${amount}</span>
                    </div>
                    <div class="grid-body">
                        <p>${customer}</p>
                        <span class="grid-status">${status}</span>
                    </div>
                </div>`;
        } else {
            // Handle form-group items
            const label = item.querySelector('.form-label')?.textContent || '';
            const text = item.querySelector('p')?.textContent || '';
            
            gridHtml += `
                <div class="grid-item">
                    <div class="grid-header">
                        <h4>${label}</h4>
                    </div>
                    <div class="grid-body">
                        <p>${text}</p>
                    </div>
                </div>`;
        }
    });
    
    gridHtml += '</div>';
    gridContainer.innerHTML = gridHtml;
}

async function loadTables() {
    try {
        const response = await fetch('/ergon/finance/tables');
        const data = await response.json();
        
        const container = document.getElementById('tablesContainer');
        const select = document.getElementById('tableSelect');
        
        if (data.tables && data.tables.length > 0) {
            let html = '';
            select.innerHTML = '<option value="">Select Table</option>';
            
            data.tables.forEach(table => {
                const displayName = table.table_name.replace('finance_', '');
                const lastSync = table.last_sync ? new Date(table.last_sync).toLocaleDateString() : 'Never';
                
                html += `
                    <div class="form-group">
                        <div class="form-label">📊 ${displayName.charAt(0).toUpperCase() + displayName.slice(1)}</div>
                        <p>${table.record_count.toLocaleString()} records</p>
                        <small>Last sync: ${lastSync}</small>
                    </div>`;
                    
                select.innerHTML += `<option value="${table.table_name}">${displayName.charAt(0).toUpperCase() + displayName.slice(1)}</option>`;
            });
            
            container.innerHTML = html;
        } else {
            container.innerHTML = `
                <div class="form-group">
                    <div class="form-label">📋 No Data</div>
                    <p>No finance tables found. Click sync to load data.</p>
                </div>`;
        }
    } catch (error) {
        container.innerHTML = `
            <div class="form-group">
                <div class="form-label">❌ Error</div>
                <p>Failed to load table information</p>
            </div>`;
    }
}

async function loadTableData() {
    const table = document.getElementById('tableSelect').value;
    
    if (!table) {
        alert('Please select a table');
        return;
    }
    
    try {
        const response = await fetch(`/ergon/finance/data?table=${table}&limit=100`);
        const data = await response.json();
        
        if (data.error) {
            showError(data.error);
            return;
        }
        
        renderTable(data.data, data.columns);
    } catch (error) {
        showError('Failed to load data: ' + error.message);
    }
}

function renderTable(data, columns) {
    const container = document.getElementById('dataContainer');
    
    if (!data || data.length === 0) {
        container.innerHTML = `
            <div class="form-group">
                <div class="form-label">📋 No Data</div>
                <p>No records found in this table</p>
            </div>`;
        return;
    }
    
    // Show summary first
    let html = `
        <div class="overview-summary">
            <div class="summary-stat">
                <span class="summary-number">📊 ${data.length}</span>
                <span class="summary-label">Records</span>
            </div>
            <div class="summary-stat">
                <span class="summary-number">📋 ${columns.length}</span>
                <span class="summary-label">Columns</span>
            </div>
        </div>`;
    
    // Add table
    html += '<div class="table-responsive"><table class="table">';
    
    html += '<thead><tr>';
    columns.slice(0, 6).forEach(col => html += `<th>${col}</th>`);
    if (columns.length > 6) html += '<th>...</th>';
    html += '</tr></thead><tbody>';
    
    data.slice(0, 10).forEach(row => {
        html += '<tr>';
        columns.slice(0, 6).forEach(col => {
            let value = row[col];
            if (typeof value === 'number' && col.includes('amount')) {
                value = '₹' + value.toLocaleString();
            }
            html += `<td>${value !== null ? String(value).substring(0, 50) : ''}</td>`;
        });
        if (columns.length > 6) html += '<td>...</td>';
        html += '</tr>';
    });
    
    html += '</tbody></table></div>';
    
    if (data.length > 10) {
        html += `<div class="form-group"><small class="text-muted">Showing first 10 of ${data.length} records</small></div>`;
    }
    
    container.innerHTML = html;
}

function showError(message) {
    document.getElementById('dataContainer').innerHTML = `
        <div class="form-group">
            <div class="form-label">❌ Error</div>
            <p>${message}</p>
        </div>`;
}

function exportChart(type) {
    window.open(`/ergon/finance/export?type=${type}`, '_blank');
}

function exportTable(type) {
    window.open(`/ergon/finance/export-table?type=${type}`, '_blank');
}

function exportDashboard() {
    window.open('/ergon/finance/export-dashboard', '_blank');
}

async function loadCompanyPrefix() {
    try {
        const response = await fetch('/ergon/finance/company-prefix');
        const data = await response.json();
        document.getElementById('companyPrefix').value = data.prefix || 'BKC';
        return data.prefix || 'BKC';
    } catch (error) {
        console.error('Failed to load company prefix:', error);
        return 'BKC';
    }
}

async function updateCompanyPrefix() {
    const prefix = document.getElementById('companyPrefix').value.trim().toUpperCase();
    
    if (!prefix) {
        alert('Please enter a company prefix');
        return;
    }
    
    const btn = document.getElementById('updatePrefixBtn');
    btn.disabled = true;
    btn.textContent = 'Updating...';
    
    try {
        const formData = new FormData();
        formData.append('company_prefix', prefix);
        
        const response = await fetch('/ergon/finance/company-prefix', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
                alert(`Company prefix updated to: ${result.prefix}`);
                // Refresh prefix and customer dropdown so filters reflect the new prefix
                await loadCompanyPrefix();
                await loadCustomers();
                // Reset any selected customer filter when prefix changes
                const select = document.getElementById('customerFilter');
                if (select) select.value = '';
                loadDashboardData(); // Reload dashboard with new prefix
        } else {
            alert('Failed to update prefix: ' + (result.error || 'Unknown error'));
        }
    } catch (error) {
        alert('Failed to update prefix: ' + error.message);
    } finally {
        btn.disabled = false;
        btn.textContent = '🏢 Update Prefix';
    }
}

function filterByDate() {
    const days = document.getElementById('dateFilter').value;
    if (days !== 'all') {
        loadDashboardData();
    }
}

function filterByCustomer() {
    loadDashboardData();
}

async function loadCustomers() {
    const select = document.getElementById('customerFilter');
    const loader = document.getElementById('customerLoader');
    try {
        if (loader) { loader.style.display = 'inline-block'; loader.innerHTML = '<span class="mini-spinner" aria-hidden="true"></span>'; }
        if (select) { select.disabled = true; select.innerHTML = '<option value="">Loading customers...</option>'; }

        const response = await fetch('/ergon/finance/customers');
        const data = await response.json();

        if (select) select.innerHTML = '<option value="">All Customers</option>';
        if (data.customers) {
            data.customers.forEach(customer => {
                select.innerHTML += `<option value="${customer.id}">${customer.display}</option>`;
            });
        }
    } catch (error) {
        console.error('Failed to load customers:', error);
        if (select) select.innerHTML = '<option value="">Failed to load</option>';
    } finally {
        if (loader) loader.style.display = 'none';
        if (select) select.disabled = false;
    }
}
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/dashboard.php';
?>

<style>
.data-list {
    margin-top: 1rem;
    max-height: 300px;
    overflow-y: auto;
}

.list-item {
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    margin-bottom: 0.5rem;
    background: var(--bg-primary);
    transition: all 0.2s ease;
}

.list-item:hover {
    box-shadow: var(--shadow-sm);
    transform: translateY(-1px);
}

.list-item--warning {
    border-left: 4px solid var(--warning);
    background: rgba(217, 119, 6, 0.05);
}

.list-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.list-title {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 0.9rem;
}

.list-amount {
    font-weight: 700;
    color: var(--primary);
    font-size: 0.9rem;
}

.list-details {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    font-size: 0.8rem;
}

.list-customer {
    color: var(--text-secondary);
}

.list-status {
    color: var(--text-secondary);
    background: var(--bg-secondary);
    padding: 0.2rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
}

.list-meta {
    display: flex;
    justify-content: space-between;
    font-size: 0.75rem;
    color: var(--text-muted);
}

.list-outstanding {
    margin-top: 0.5rem;
    padding: 0.25rem 0.5rem;
    background: rgba(220, 38, 38, 0.1);
    color: var(--error);
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

/* View Toggle Styles */
.view-toggle {
    display: flex;
    background: var(--bg-secondary);
    border-radius: 6px;
    padding: 2px;
    margin-right: 0.5rem;
}

.view-btn {
    padding: 0.25rem 0.75rem;
    border: none;
    background: transparent;
    color: var(--text-secondary);
    border-radius: 4px;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.view-btn.active {
    background: var(--primary);
    color: white;
}

/* Conversion Funnel */
.funnel-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 0;
}

.funnel-stage {
    text-align: center;
    flex: 1;
    padding: 1rem;
    background: var(--bg-secondary);
    border-radius: 8px;
    transition: all 0.3s ease;
}

.funnel-stage:hover {
    background: var(--primary-light);
    transform: translateY(-2px);
}

.funnel-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 0.25rem;
}

.funnel-label {
    font-size: 0.8rem;
    color: var(--text-secondary);
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.funnel-value {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.funnel-conversion {
    font-size: 0.75rem;
    color: var(--success);
    font-weight: 600;
}

.funnel-arrow {
    font-size: 1.5rem;
    color: var(--text-muted);
    margin: 0 0.5rem;
}

/* Chart Summary */
.chart-summary {
    display: flex;
    justify-content: space-around;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
}

.summary-item {
    text-align: center;
}

.summary-label {
    font-size: 0.75rem;
    color: var(--text-secondary);
    margin-right: 0.5rem;
}

.summary-value {
    font-weight: 600;
    color: var(--primary);
}

/* Highlight Card */
.highlight-card {
    background: var(--bg-secondary);
    padding: 0.75rem;
    border-radius: 6px;
    margin-top: 1rem;
    text-align: center;
}

.highlight-label {
    font-size: 0.75rem;
    color: var(--text-secondary);
    margin-bottom: 0.25rem;
}

.highlight-value {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--primary);
}

/* Cash Flow */
.cash-flow-summary {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.flow-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--border-color);
}

.flow-item:last-child {
    border-bottom: none;
    font-weight: 600;
}

.flow-label {
    font-size: 0.9rem;
    color: var(--text-secondary);
}

.flow-value {
    font-weight: 600;
    font-size: 1rem;
}

.flow-positive {
    color: var(--success);
}

.flow-negative {
    color: var(--error);
}

/* Table Enhancements */
.table-row--danger {
    background: rgba(220, 38, 38, 0.05);
}

.table-row--danger:hover {
    background: rgba(220, 38, 38, 0.1);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 2rem;
    color: var(--text-muted);
}

.empty-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state h3 {
    margin-bottom: 0.5rem;
    color: var(--text-secondary);
}

/* Full Width Card */
.card--full-width {
    grid-column: 1 / -1;
}

/* 2-Column Grid Layout */
.dashboard-grid--2-col {
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

/* Stat-card styles moved to assets/css/ergon.css */

/* Data View Styles */
.data-view {
    position: relative;
}

.data-list,
.data-grid {
    display: none;
}

.data-list.active,
.data-grid.active {
    display: block;
}

/* Grid Layout */
.grid-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.grid-item {
    padding: 1rem;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: var(--bg-primary);
    transition: all 0.2s ease;
}

.grid-item:hover {
    box-shadow: var(--shadow-sm);
    transform: translateY(-2px);
}

.grid-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--border-color);
}

.grid-header h4 {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-primary);
}

.grid-amount {
    font-weight: 700;
    color: var(--primary);
    font-size: 0.9rem;
}

.grid-body p {
    margin: 0 0 0.5rem 0;
    color: var(--text-secondary);
    font-size: 0.8rem;
}

.grid-status {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    background: var(--bg-secondary);
    color: var(--text-secondary);
    border-radius: 12px;
    font-size: 0.75rem;
}

@media (max-width: 768px) {
    .funnel-container {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .funnel-arrow {
        transform: rotate(90deg);
        margin: 0.5rem 0;
    }
    
    .chart-summary {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .summary-item {
        display: flex;
        justify-content: space-between;
    }
    
    .cash-flow-summary {
        gap: 0.5rem;
    }
    
    .flow-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
}
</style>

