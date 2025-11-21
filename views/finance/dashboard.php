<?php 
$title = 'Finance Dashboard';
$active_page = 'finance';
ob_start(); 
?>

<div class="container-fluid">
    <div class="header-actions">
        <button id="syncBtn" class="btn btn--primary">🔄 Sync Finance Data</button>
        <button id="structureBtn" class="btn btn--secondary">📊 View Structure</button>
        <button id="analyzeBtn" class="btn btn--secondary">📋 Analyze Tables</button>
        <a href="/ergon/finance/export?type=all" class="btn btn--secondary">📥 Export All</a>
    </div>

    <div class="dashboard-grid">
        <div class="kpi-card">
            <div class="kpi-card__header">
                <div class="kpi-card__icon">📊</div>
                <div class="kpi-card__trend">↗ +2%</div>
            </div>
            <div class="kpi-card__value" id="totalTables">0</div>
            <div class="kpi-card__label">Finance Tables</div>
            <div class="kpi-card__status">Active</div>
        </div>
        
        <div class="kpi-card">
            <div class="kpi-card__header">
                <div class="kpi-card__icon">📈</div>
                <div class="kpi-card__trend">↗ +15%</div>
            </div>
            <div class="kpi-card__value" id="totalRecords">0</div>
            <div class="kpi-card__label">Total Records</div>
            <div class="kpi-card__status">Synced</div>
        </div>
        
        <div class="kpi-card kpi-card--warning">
            <div class="kpi-card__header">
                <div class="kpi-card__icon">💰</div>
                <div class="kpi-card__trend">— 0%</div>
            </div>
            <div class="kpi-card__value" id="outstandingAmount">₹0</div>
            <div class="kpi-card__label">Outstanding Invoices</div>
            <div class="kpi-card__status kpi-card__status--pending">Needs Review</div>
        </div>
        
        <div class="kpi-card">
            <div class="kpi-card__header">
                <div class="kpi-card__icon">📋</div>
                <div class="kpi-card__trend">↗ +8%</div>
            </div>
            <div class="kpi-card__value" id="totalInvoices">0</div>
            <div class="kpi-card__label">Total Invoices</div>
            <div class="kpi-card__status">Processing</div>
        </div>
    </div>

    <!-- Database Structure Modal -->
    <div class="modal" id="structureModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>📊 Database Structure</h3>
                <button class="modal-close" onclick="closeStructureModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="structureContainer">
                    <div class="empty-state">
                        <div class="empty-icon">🔄</div>
                        <p>Loading structure...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Finance Analytics KPI Cards -->
    <div class="dashboard-grid">
        <div class="kpi-stat-card">
            <div class="kpi-stat-card__header">
                <div class="kpi-stat-card__icon">📝</div>
                <div class="kpi-stat-card__title">Quotations Analysis</div>
            </div>
            <div class="kpi-stat-card__metrics">
                <div class="metric">
                    <div class="metric__value" id="quotationTotal">0</div>
                    <div class="metric__label">Total</div>
                </div>
                <div class="metric">
                    <div class="metric__value" id="quotationValue">₹0</div>
                    <div class="metric__label">Value</div>
                </div>
                <div class="metric">
                    <div class="metric__value" id="quotationRate">0%</div>
                    <div class="metric__label">Conversion</div>
                </div>
            </div>
            <div class="kpi-stat-card__chart">
                <div class="mini-chart">
                    <div class="chart-bar" data-value="30"></div>
                    <div class="chart-bar" data-value="45"></div>
                    <div class="chart-bar" data-value="25"></div>
                    <div class="chart-bar" data-value="60"></div>
                    <div class="chart-bar" data-value="40"></div>
                </div>
            </div>
            <div class="kpi-stat-card__trend">
                <span class="trend-indicator trend-up">↗ +12%</span>
                <span class="trend-label">vs last month</span>
            </div>
        </div>
        
        <div class="kpi-stat-card">
            <div class="kpi-stat-card__header">
                <div class="kpi-stat-card__icon">🛒</div>
                <div class="kpi-stat-card__title">Purchase Orders</div>
            </div>
            <div class="kpi-stat-card__metrics">
                <div class="metric">
                    <div class="metric__value" id="poCount">0</div>
                    <div class="metric__label">Orders</div>
                </div>
                <div class="metric">
                    <div class="metric__value" id="poAmount">₹0</div>
                    <div class="metric__label">Amount</div>
                </div>
                <div class="metric">
                    <div class="metric__value" id="poAvg">₹0</div>
                    <div class="metric__label">Average</div>
                </div>
            </div>
            <div class="kpi-stat-card__chart">
                <div class="mini-chart">
                    <div class="chart-bar" data-value="50"></div>
                    <div class="chart-bar" data-value="35"></div>
                    <div class="chart-bar" data-value="70"></div>
                    <div class="chart-bar" data-value="45"></div>
                    <div class="chart-bar" data-value="55"></div>
                </div>
            </div>
            <div class="kpi-stat-card__trend">
                <span class="trend-indicator trend-up">↗ +8%</span>
                <span class="trend-label">vs last month</span>
            </div>
        </div>
        
        <div class="kpi-stat-card">
            <div class="kpi-stat-card__header">
                <div class="kpi-stat-card__icon">💰</div>
                <div class="kpi-stat-card__title">Invoice Status</div>
            </div>
            <div class="kpi-stat-card__metrics">
                <div class="metric">
                    <div class="metric__value" id="invoicePaid">₹0</div>
                    <div class="metric__label">Paid</div>
                </div>
                <div class="metric">
                    <div class="metric__value" id="invoicePending">₹0</div>
                    <div class="metric__label">Pending</div>
                </div>
                <div class="metric">
                    <div class="metric__value" id="invoiceOverdue">₹0</div>
                    <div class="metric__label">Overdue</div>
                </div>
            </div>
            <div class="kpi-stat-card__chart">
                <div class="progress-ring">
                    <div class="ring-segment paid" data-percentage="60"></div>
                    <div class="ring-segment pending" data-percentage="25"></div>
                    <div class="ring-segment overdue" data-percentage="15"></div>
                </div>
            </div>
            <div class="kpi-stat-card__trend">
                <span class="trend-indicator trend-down">↘ -5%</span>
                <span class="trend-label">overdue reduced</span>
            </div>
        </div>
        
        <div class="kpi-stat-card">
            <div class="kpi-stat-card__header">
                <div class="kpi-stat-card__icon">💳</div>
                <div class="kpi-stat-card__title">Payment Flow</div>
            </div>
            <div class="kpi-stat-card__metrics">
                <div class="metric">
                    <div class="metric__value" id="paymentReceived">₹0</div>
                    <div class="metric__label">Received</div>
                </div>
                <div class="metric">
                    <div class="metric__value" id="paymentPending">₹0</div>
                    <div class="metric__label">Pending</div>
                </div>
                <div class="metric">
                    <div class="metric__value" id="paymentRate">0%</div>
                    <div class="metric__label">Success Rate</div>
                </div>
            </div>
            <div class="kpi-stat-card__chart">
                <div class="wave-chart">
                    <div class="wave-line">
                        <span class="wave-point" data-value="40"></span>
                        <span class="wave-point" data-value="60"></span>
                        <span class="wave-point" data-value="35"></span>
                        <span class="wave-point" data-value="75"></span>
                        <span class="wave-point" data-value="50"></span>
                    </div>
                </div>
            </div>
            <div class="kpi-stat-card__trend">
                <span class="trend-indicator trend-up">↗ +15%</span>
                <span class="trend-label">collection improved</span>
            </div>
        </div>
    </div>

    <!-- Data Views Section -->
    <div class="dashboard-grid">
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">📋 Quotations Overview</h2>
                <div class="card-actions">
                    <div class="view-toggle">
                        <button class="view-btn active" onclick="toggleView('quotations', 'list')">List</button>
                        <button class="view-btn" onclick="toggleView('quotations', 'grid')">Grid</button>
                    </div>
                    <button class="btn btn--primary btn--sm" onclick="exportChart('quotations')">Export</button>
                </div>
            </div>
            <div class="card__body">
                <div class="overview-summary">
                    <div class="summary-stat">
                        <span class="summary-number" id="quotationsDraft">📝 0</span>
                        <span class="summary-label">Draft</span>
                    </div>
                    <div class="summary-stat">
                        <span class="summary-number" id="quotationsRevised">🔄 0</span>
                        <span class="summary-label">Revised</span>
                    </div>
                    <div class="summary-stat">
                        <span class="summary-number" id="quotationsConverted">✅ 0</span>
                        <span class="summary-label">Converted</span>
                    </div>
                </div>
                <div class="data-view" id="quotationsView">
                    <div class="data-list active" id="quotationsList">
                        <div class="form-group">
                            <div class="form-label">📋 Loading quotations...</div>
                        </div>
                    </div>
                    <div class="data-grid" id="quotationsGrid">
                        <div class="form-group">
                            <div class="form-label">📋 Loading quotations...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">🛒 Purchase Orders Analysis</h2>
                <div class="card-actions">
                    <div class="view-toggle">
                        <button class="view-btn active" onclick="toggleView('purchase_orders', 'list')">List</button>
                        <button class="view-btn" onclick="toggleView('purchase_orders', 'grid')">Grid</button>
                    </div>
                    <button class="btn btn--primary btn--sm" onclick="exportChart('purchase_orders')">Export</button>
                </div>
            </div>
            <div class="card__body">
                <div class="overview-summary">
                    <div class="summary-stat">
                        <span class="summary-number" id="poTotal">📦 0</span>
                        <span class="summary-label">Total Orders</span>
                    </div>
                    <div class="summary-stat">
                        <span class="summary-number" id="poValue">💰 ₹0</span>
                        <span class="summary-label">Total Value</span>
                    </div>
                    <div class="summary-stat">
                        <span class="summary-number" id="poAverage">📊 ₹0</span>
                        <span class="summary-label">Avg Order</span>
                    </div>
                </div>
                <div class="data-view" id="purchaseOrdersView">
                    <div class="data-list active" id="purchaseOrdersList">
                        <div class="form-group">
                            <div class="form-label">📦 Loading purchase orders...</div>
                        </div>
                    </div>
                    <div class="data-grid" id="purchaseOrdersGrid">
                        <div class="form-group">
                            <div class="form-label">📦 Loading purchase orders...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="dashboard-grid">
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">💰 Invoice Management</h2>
                <div class="card-actions">
                    <div class="view-toggle">
                        <button class="view-btn active" onclick="toggleView('invoices', 'list')">List</button>
                        <button class="view-btn" onclick="toggleView('invoices', 'grid')">Grid</button>
                    </div>
                    <button class="btn btn--primary btn--sm" onclick="exportChart('invoices')">Export</button>
                </div>
            </div>
            <div class="card__body">
                <div class="overview-summary">
                    <div class="summary-stat">
                        <span class="summary-number" id="invoicesPaid">✅ ₹0</span>
                        <span class="summary-label">Paid</span>
                    </div>
                    <div class="summary-stat">
                        <span class="summary-number" id="invoicesUnpaid">⏳ ₹0</span>
                        <span class="summary-label">Unpaid</span>
                    </div>
                    <div class="summary-stat">
                        <span class="summary-number" id="invoicesOverdue">🚨 ₹0</span>
                        <span class="summary-label">Overdue</span>
                    </div>
                </div>
                <div id="outstandingAlert" class="alert alert--warning" style="display: none;"></div>
                <div class="data-view" id="invoicesView">
                    <div class="data-list active" id="invoicesList">
                        <div class="form-group">
                            <div class="form-label">💰 Loading invoices...</div>
                        </div>
                    </div>
                    <div class="data-grid" id="invoicesGrid">
                        <div class="form-group">
                            <div class="form-label">💰 Loading invoices...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">💳 Payment Processing</h2>
                <div class="card-actions">
                    <div class="view-toggle">
                        <button class="view-btn active" onclick="toggleView('payments', 'list')">List</button>
                        <button class="view-btn" onclick="toggleView('payments', 'grid')">Grid</button>
                    </div>
                    <button class="btn btn--primary btn--sm" onclick="exportChart('payments')">Export</button>
                </div>
            </div>
            <div class="card__body">
                <div class="overview-summary">
                    <div class="summary-stat">
                        <span class="summary-number">💳 0</span>
                        <span class="summary-label">Processed</span>
                    </div>
                    <div class="summary-stat">
                        <span class="summary-number">⏳ 0</span>
                        <span class="summary-label">Pending</span>
                    </div>
                    <div class="summary-stat">
                        <span class="summary-number">❌ 0</span>
                        <span class="summary-label">Failed</span>
                    </div>
                </div>
                <div class="data-view" id="paymentsView">
                    <div class="data-list active" id="paymentsList">
                        <div class="form-group">
                            <div class="form-label">💳 No payments yet</div>
                            <p>Payment records will appear here</p>
                        </div>
                    </div>
                    <div class="data-grid" id="paymentsGrid">
                        <div class="form-group">
                            <div class="form-label">💳 No payments yet</div>
                            <p>Payment records will appear here</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">📊 Data Tables Summary</h2>
                <div class="card-actions">
                    <div class="view-toggle">
                        <button class="view-btn active" onclick="toggleView('tables', 'list')">List</button>
                        <button class="view-btn" onclick="toggleView('tables', 'grid')">Grid</button>
                    </div>
                </div>
            </div>
            <div class="card__body">
                <div class="data-view" id="tablesView">
                    <div class="data-list active" id="tablesContainer">
                        <div class="form-group">
                            <div class="form-label">📋 Loading Tables...</div>
                            <p>Fetching finance table information</p>
                        </div>
                    </div>
                    <div class="data-grid" id="tablesGrid">
                        <div class="form-group">
                            <div class="form-label">📋 Loading Tables...</div>
                            <p>Fetching finance table information</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">🔍 Data Explorer</h2>
                <div class="card-actions">
                    <select id="tableSelect" class="form-control">
                        <option value="">Select Table</option>
                    </select>
                    <button id="loadData" class="btn btn--primary btn--sm">Load Data</button>
                </div>
            </div>
            <div class="card__body card__body--scrollable">
                <div id="dataContainer">
                    <div class="form-group">
                        <div class="form-label">📈 Data Viewer</div>
                        <p>Select a finance table to explore detailed records and analytics</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="dashboard-grid">
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">📈 Financial Trends</h2>
            </div>
            <div class="card__body">
                <div class="overview-summary">
                    <div class="summary-stat">
                        <span class="summary-number">📊 0</span>
                        <span class="summary-label">Monthly Growth</span>
                    </div>
                    <div class="summary-stat">
                        <span class="summary-number">💹 0%</span>
                        <span class="summary-label">Revenue Change</span>
                    </div>
                    <div class="summary-stat">
                        <span class="summary-number">🎯 0%</span>
                        <span class="summary-label">Target Achievement</span>
                    </div>
                </div>
                <div class="overview-stats">
                    <div class="stat-row">
                        <div class="stat-item-inline">
                            <div class="stat-icon">📈</div>
                            <div>
                                <div class="stat-value-sm">+0%</div>
                                <div class="stat-label-sm">YoY Growth</div>
                            </div>
                        </div>
                        <div class="stat-item-inline">
                            <div class="stat-icon">💰</div>
                            <div>
                                <div class="stat-value-sm">₹0</div>
                                <div class="stat-label-sm">Revenue</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">⚡ Recent Finance Activities</h2>
            </div>
            <div class="card__body card__body--scrollable">
                <div id="recentActivities">
                    <div class="form-group">
                        <div class="form-label">🔄 System Sync</div>
                        <p>Finance data synchronized successfully</p>
                        <small>Just now</small>
                    </div>
                    <div class="form-group">
                        <div class="form-label">📊 Dashboard Loaded</div>
                        <p>Finance dashboard initialized with latest data</p>
                        <small>2 minutes ago</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>


document.addEventListener('DOMContentLoaded', function() {
    initCharts();
    loadFinanceStats();
    loadTables();
    loadDetailedLists();
    
    document.getElementById('syncBtn').addEventListener('click', syncFinanceData);
    document.getElementById('structureBtn').addEventListener('click', showTableStructure);
    document.getElementById('loadData').addEventListener('click', loadTableData);
    document.getElementById('analyzeBtn').addEventListener('click', analyzeAllTables);
});

function initCharts() {
    // Initialize mini charts
    initMiniCharts();
    initProgressRings();
    initWaveCharts();
}

function initMiniCharts() {
    document.querySelectorAll('.chart-bar').forEach(bar => {
        const value = bar.dataset.value;
        bar.style.height = value + '%';
    });
}

function initProgressRings() {
    document.querySelectorAll('.ring-segment').forEach(segment => {
        const percentage = segment.dataset.percentage;
        segment.style.setProperty('--percentage', percentage + '%');
    });
}

function initWaveCharts() {
    document.querySelectorAll('.wave-point').forEach(point => {
        const value = point.dataset.value;
        point.style.bottom = value + '%';
    });
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
            loadFinanceStats();
            loadTables();
            updateCharts();
            loadDetailedLists();
        }
    } catch (error) {
        alert('Sync failed: ' + error.message);
    } finally {
        btn.disabled = false;
        btn.textContent = 'Sync Finance Data';
    }
}

async function loadFinanceStats() {
    try {
        const response = await fetch('/ergon/finance/stats');
        const data = await response.json();
        
        // Update KPI cards
        document.getElementById('totalTables').textContent = data.totalTables || 0;
        document.getElementById('totalRecords').textContent = (data.totalRecords || 0).toLocaleString();
        
        // Update detailed stats
        updateDetailedStats(data);
        
    } catch (error) {
        console.error('Failed to load stats:', error);
    }
}

function updateDetailedStats(data) {
    // Update quotations summary
    const quotationsData = data.quotations || { draft: 0, revised: 0, converted: 0 };
    const quotationsDraft = document.getElementById('quotationsDraft');
    const quotationsRevised = document.getElementById('quotationsRevised');
    const quotationsConverted = document.getElementById('quotationsConverted');
    
    if (quotationsDraft) quotationsDraft.innerHTML = `📝 ${quotationsData.draft}`;
    if (quotationsRevised) quotationsRevised.innerHTML = `🔄 ${quotationsData.revised}`;
    if (quotationsConverted) quotationsConverted.innerHTML = `✅ ${quotationsData.converted}`;
    
    // Update purchase orders
    const poData = data.purchaseOrders || { total: 0, value: 0 };
    const poTotal = document.getElementById('poTotal');
    const poValue = document.getElementById('poValue');
    const poAverage = document.getElementById('poAverage');
    
    if (poTotal) poTotal.innerHTML = `📦 ${poData.total}`;
    if (poValue) poValue.innerHTML = `💰 ₹${(poData.value || 0).toLocaleString()}`;
    if (poAverage) poAverage.innerHTML = `📊 ₹${poData.total > 0 ? Math.round(poData.value / poData.total).toLocaleString() : 0}`;
    
    // Update invoices
    const invoiceData = data.invoices || { paid: 0, unpaid: 0, overdue: 0 };
    const invoicesPaid = document.getElementById('invoicesPaid');
    const invoicesUnpaid = document.getElementById('invoicesUnpaid');
    const invoicesOverdue = document.getElementById('invoicesOverdue');
    
    if (invoicesPaid) invoicesPaid.innerHTML = `✅ ₹${(invoiceData.paid || 0).toLocaleString()}`;
    if (invoicesUnpaid) invoicesUnpaid.innerHTML = `⏳ ₹${(invoiceData.unpaid || 0).toLocaleString()}`;
    if (invoicesOverdue) invoicesOverdue.innerHTML = `🚨 ₹${(invoiceData.overdue || 0).toLocaleString()}`;
    
    // Update outstanding amount
    const outstanding = invoiceData.unpaid + invoiceData.overdue;
    const outstandingAmount = document.getElementById('outstandingAmount');
    const totalInvoices = document.getElementById('totalInvoices');
    
    if (outstandingAmount) outstandingAmount.textContent = `₹${outstanding.toLocaleString()}`;
    if (totalInvoices) totalInvoices.textContent = invoiceData.paid + invoiceData.unpaid + invoiceData.overdue;
    
    // Show outstanding alert if needed
    if (outstanding > 0) {
        const alert = document.getElementById('outstandingAlert');
        if (alert) {
            alert.style.display = 'block';
            alert.innerHTML = `<strong>Alert:</strong> ₹${outstanding.toLocaleString()} in outstanding invoices requires attention`;
        }
    }
}

async function updateCharts() {
    try {
        const response = await fetch('/ergon/finance/stats');
        const data = await response.json();
        
        updateKPICards(data);
        
    } catch (error) {
        console.error('Failed to update KPI cards:', error);
    }
}

function updateKPICards(data) {
    // Update quotations KPI
    const quotationsData = data.quotations || {};
    const quotationTotal = quotationsData.draft + quotationsData.revised + quotationsData.converted;
    document.getElementById('quotationTotal').textContent = quotationTotal || 0;
    document.getElementById('quotationValue').textContent = '₹' + (quotationsData.value || 0).toLocaleString();
    document.getElementById('quotationRate').textContent = (quotationsData.conversionRate || 0) + '%';
    
    // Update purchase orders KPI
    const poData = data.purchaseOrders || {};
    document.getElementById('poCount').textContent = poData.total || 0;
    document.getElementById('poAmount').textContent = '₹' + (poData.value || 0).toLocaleString();
    document.getElementById('poAvg').textContent = '₹' + (poData.average || 0).toLocaleString();
    
    // Update invoices KPI
    const invoiceData = data.invoices || {};
    document.getElementById('invoicePaid').textContent = '₹' + (invoiceData.paid || 0).toLocaleString();
    document.getElementById('invoicePending').textContent = '₹' + (invoiceData.unpaid || 0).toLocaleString();
    document.getElementById('invoiceOverdue').textContent = '₹' + (invoiceData.overdue || 0).toLocaleString();
    
    // Update payments KPI
    const paymentData = data.payments || {};
    document.getElementById('paymentReceived').textContent = '₹' + (paymentData.received || 0).toLocaleString();
    document.getElementById('paymentPending').textContent = '₹' + (paymentData.pending || 0).toLocaleString();
    document.getElementById('paymentRate').textContent = (paymentData.successRate || 0) + '%';
}

function exportChart(type) {
    window.open(`/ergon/finance/export?type=${type}`, '_blank');
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

async function loadDetailedLists() {
    try {
        // Load quotations
        const quotationsResponse = await fetch('/ergon/finance/data?table=finance_quotations&limit=10');
        const quotationsData = await quotationsResponse.json();
        if (quotationsData.data) {
            renderModuleList('quotations', quotationsData.data);
        }
        
        // Load purchase orders
        const poResponse = await fetch('/ergon/finance/data?table=finance_purchase_orders&limit=10');
        const poData = await poResponse.json();
        if (poData.data) {
            renderModuleList('purchaseOrders', poData.data);
        }
        
        // Load invoices
        const invoicesResponse = await fetch('/ergon/finance/data?table=finance_invoices&limit=10');
        const invoicesData = await invoicesResponse.json();
        if (invoicesData.data) {
            renderModuleList('invoices', invoicesData.data);
        }
        
    } catch (error) {
        console.error('Failed to load detailed lists:', error);
    }
}

function renderModuleList(module, data) {
    let containerId = `${module}List`;
    if (module === 'purchaseOrders') containerId = 'purchaseOrdersList';
    
    const container = document.getElementById(containerId);
    if (!container || !data || data.length === 0) return;
    
    let html = '';
    
    data.slice(0, 5).forEach(item => {
        if (module === 'quotations') {
            html += `
                <div class="list-item">
                    <div class="list-header">
                        <div class="list-title">${item.quotation_number || 'N/A'}</div>
                        <div class="list-amount">₹${(item.total_amount || 0).toLocaleString()}</div>
                    </div>
                    <div class="list-details">
                        <div class="list-customer">${item.customer_name || 'Unknown'}</div>
                        <div class="list-status">${item.status || 'Draft'}</div>
                    </div>
                    <div class="list-meta">
                        <span>Date: ${item.quotation_date || 'N/A'}</span>
                        <span>Valid: ${item.valid_until || 'N/A'}</span>
                    </div>
                </div>`;
        } else if (module === 'purchaseOrders') {
            html += `
                <div class="list-item">
                    <div class="list-header">
                        <div class="list-title">${item.po_number || 'N/A'}</div>
                        <div class="list-amount">₹${(item.total_amount || 0).toLocaleString()}</div>
                    </div>
                    <div class="list-details">
                        <div class="list-customer">${item.vendor_name || 'Unknown'}</div>
                        <div class="list-status">${item.status || 'Pending'}</div>
                    </div>
                    <div class="list-meta">
                        <span>Date: ${item.po_date || 'N/A'}</span>
                        <span>Delivery: ${item.delivery_date || 'N/A'}</span>
                    </div>
                </div>`;
        } else if (module === 'invoices') {
            const isOverdue = item.status === 'Overdue' || item.payment_status === 'Overdue';
            html += `
                <div class="list-item ${isOverdue ? 'list-item--warning' : ''}">
                    <div class="list-header">
                        <div class="list-title">${item.invoice_number || 'N/A'}</div>
                        <div class="list-amount">₹${(item.total_amount || 0).toLocaleString()}</div>
                    </div>
                    <div class="list-details">
                        <div class="list-customer">${item.customer_name || 'Unknown'}</div>
                        <div class="list-status">${item.payment_status || 'Pending'}</div>
                    </div>
                    <div class="list-meta">
                        <span>Date: ${item.invoice_date || 'N/A'}</span>
                        <span>Due: ${item.due_date || 'N/A'}</span>
                    </div>
                    ${isOverdue ? '<div class="list-outstanding">⚠️ Payment Overdue</div>' : ''}
                </div>`;
        }
    });
    
    if (html) {
        container.innerHTML = html;
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

/* KPI Stat Cards */
.kpi-stat-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
    transition: all 0.3s ease;
}

.kpi-stat-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

.kpi-stat-card__header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.kpi-stat-card__icon {
    font-size: 1.5rem;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--bg-secondary);
    border-radius: 8px;
}

.kpi-stat-card__title {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 0.9rem;
}

.kpi-stat-card__metrics {
    display: flex;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.metric {
    text-align: center;
}

.metric__value {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 0.25rem;
}

.metric__label {
    font-size: 0.75rem;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.kpi-stat-card__chart {
    height: 60px;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Mini Bar Chart */
.mini-chart {
    display: flex;
    align-items: end;
    gap: 4px;
    height: 40px;
    width: 100%;
    max-width: 120px;
}

.chart-bar {
    flex: 1;
    background: linear-gradient(to top, var(--primary), var(--primary-light));
    border-radius: 2px;
    min-height: 8px;
    transition: all 0.3s ease;
}

.chart-bar:hover {
    background: var(--primary-dark);
}

/* Progress Ring */
.progress-ring {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: conic-gradient(
        #28a745 0% var(--paid-percentage, 60%),
        #ffc107 var(--paid-percentage, 60%) calc(var(--paid-percentage, 60%) + var(--pending-percentage, 25%)),
        #dc3545 calc(var(--paid-percentage, 60%) + var(--pending-percentage, 25%)) 100%
    );
    position: relative;
}

.progress-ring::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 30px;
    height: 30px;
    background: var(--bg-primary);
    border-radius: 50%;
}

/* Wave Chart */
.wave-chart {
    width: 100%;
    height: 40px;
    position: relative;
}

.wave-line {
    display: flex;
    align-items: end;
    justify-content: space-between;
    height: 100%;
    position: relative;
}

.wave-point {
    width: 8px;
    height: 8px;
    background: var(--primary);
    border-radius: 50%;
    position: absolute;
    transition: all 0.3s ease;
}

.wave-point:nth-child(1) { left: 0%; }
.wave-point:nth-child(2) { left: 25%; }
.wave-point:nth-child(3) { left: 50%; }
.wave-point:nth-child(4) { left: 75%; }
.wave-point:nth-child(5) { left: 100%; }

.kpi-stat-card__trend {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding-top: 0.75rem;
    border-top: 1px solid var(--border-color);
}

.trend-indicator {
    font-size: 0.8rem;
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
}

.trend-up {
    background: rgba(34, 197, 94, 0.1);
    color: #22c55e;
}

.trend-down {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
}

.trend-label {
    font-size: 0.75rem;
    color: var(--text-muted);
}

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
    .list-details {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .list-meta {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .grid-container {
        grid-template-columns: 1fr;
    }
    
    .view-toggle {
        margin-bottom: 0.5rem;
    }
    
    .kpi-stat-card__metrics {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .metric {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .kpi-stat-card__trend {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
}
</style>

