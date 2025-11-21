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

    <div class="dashboard-grid">
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">📋 Quotations Overview</h2>
                <div class="card-actions">
                    <button class="btn btn--primary btn--sm" onclick="exportChart('quotations')">Export CSV</button>
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
                <canvas id="quotationsChart" height="150"></canvas>
                <div class="overview-progress">
                    <div class="progress-header">
                        <span class="progress-label">Conversion Rate</span>
                        <span class="progress-value" id="conversionRate">0%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" id="conversionProgress" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">🛒 Purchase Orders Analysis</h2>
                <div class="card-actions">
                    <button class="btn btn--primary btn--sm" onclick="exportChart('purchase_orders')">Export CSV</button>
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
                <canvas id="purchaseOrdersChart" height="150"></canvas>
                <div class="overview-stats">
                    <div class="stat-row">
                        <div class="stat-item-inline">
                            <div class="stat-icon">📈</div>
                            <div>
                                <div class="stat-value-sm" id="poGrowth">+0%</div>
                                <div class="stat-label-sm">Growth</div>
                            </div>
                        </div>
                        <div class="stat-item-inline">
                            <div class="stat-icon">🎯</div>
                            <div>
                                <div class="stat-value-sm" id="poTarget">85%</div>
                                <div class="stat-label-sm">Target</div>
                            </div>
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
                    <button class="btn btn--primary btn--sm" onclick="exportChart('invoices')">Export CSV</button>
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
                <canvas id="invoicesChart" height="150"></canvas>
                <div id="outstandingAlert" class="alert alert--warning" style="display: none;"></div>
                <div class="overview-progress">
                    <div class="progress-header">
                        <span class="progress-label">Collection Rate</span>
                        <span class="progress-value" id="collectionRate">0%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" id="collectionProgress" style="width: 0%"></div>
                    </div>
                    <div class="progress-footer">
                        <span class="progress-trend" id="collectionTrend">— No change</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">💳 Payment Processing</h2>
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
                <div class="empty-state">
                    <div class="empty-icon">💳</div>
                    <h5>Payment System Ready</h5>
                    <p class="text-muted">Payment records will appear here once transactions are processed</p>
                </div>
                <div class="overview-progress">
                    <div class="progress-header">
                        <span class="progress-label">Success Rate</span>
                        <span class="progress-value">100%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 100%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">📊 Data Tables Summary</h2>
            </div>
            <div class="card__body">
                <div id="tablesContainer">
                    <div class="form-group">
                        <div class="form-label">📋 Loading Tables...</div>
                        <p>Fetching finance table information</p>
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
let quotationsChart, purchaseOrdersChart, invoicesChart;

document.addEventListener('DOMContentLoaded', function() {
    initCharts();
    loadFinanceStats();
    loadTables();
    
    document.getElementById('syncBtn').addEventListener('click', syncFinanceData);
    document.getElementById('structureBtn').addEventListener('click', showTableStructure);
    document.getElementById('loadData').addEventListener('click', loadTableData);
    document.getElementById('analyzeBtn').addEventListener('click', analyzeAllTables);
});

function initCharts() {
    // Quotations Status Pie Chart
    const quotationsCtx = document.getElementById('quotationsChart').getContext('2d');
    quotationsChart = new Chart(quotationsCtx, {
        type: 'pie',
        data: {
            labels: ['Draft', 'Revised', 'Converted'],
            datasets: [{
                data: [0, 0, 0],
                backgroundColor: ['#ffc107', '#17a2b8', '#28a745']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.parsed + ' quotations';
                        }
                    }
                }
            }
        }
    });

    // Purchase Orders Monthly Bar Chart
    const purchaseOrdersCtx = document.getElementById('purchaseOrdersChart').getContext('2d');
    purchaseOrdersChart = new Chart(purchaseOrdersCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Total Amount (₹)',
                data: [],
                backgroundColor: '#007bff'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { 
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₹' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Amount: ₹' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Invoices Payment Status Donut Chart
    const invoicesCtx = document.getElementById('invoicesChart').getContext('2d');
    invoicesChart = new Chart(invoicesCtx, {
        type: 'doughnut',
        data: {
            labels: ['Paid', 'Unpaid', 'Overdue'],
            datasets: [{
                data: [0, 0, 0],
                backgroundColor: ['#28a745', '#ffc107', '#dc3545']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ₹' + context.parsed.toLocaleString();
                        }
                    }
                }
            }
        }
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
    document.getElementById('quotationsDraft').innerHTML = `📝 ${quotationsData.draft}`;
    document.getElementById('quotationsRevised').innerHTML = `🔄 ${quotationsData.revised}`;
    document.getElementById('quotationsConverted').innerHTML = `✅ ${quotationsData.converted}`;
    
    // Calculate conversion rate
    const total = quotationsData.draft + quotationsData.revised + quotationsData.converted;
    const conversionRate = total > 0 ? Math.round((quotationsData.converted / total) * 100) : 0;
    document.getElementById('conversionRate').textContent = `${conversionRate}%`;
    document.getElementById('conversionProgress').style.width = `${conversionRate}%`;
    
    // Update purchase orders
    const poData = data.purchaseOrders || { total: 0, value: 0 };
    document.getElementById('poTotal').innerHTML = `📦 ${poData.total}`;
    document.getElementById('poValue').innerHTML = `💰 ₹${(poData.value || 0).toLocaleString()}`;
    document.getElementById('poAverage').innerHTML = `📊 ₹${poData.total > 0 ? Math.round(poData.value / poData.total).toLocaleString() : 0}`;
    
    // Update invoices
    const invoiceData = data.invoices || { paid: 0, unpaid: 0, overdue: 0 };
    document.getElementById('invoicesPaid').innerHTML = `✅ ₹${(invoiceData.paid || 0).toLocaleString()}`;
    document.getElementById('invoicesUnpaid').innerHTML = `⏳ ₹${(invoiceData.unpaid || 0).toLocaleString()}`;
    document.getElementById('invoicesOverdue').innerHTML = `🚨 ₹${(invoiceData.overdue || 0).toLocaleString()}`;
    
    // Calculate collection rate
    const totalInvoices = invoiceData.paid + invoiceData.unpaid + invoiceData.overdue;
    const collectionRate = totalInvoices > 0 ? Math.round((invoiceData.paid / totalInvoices) * 100) : 100;
    document.getElementById('collectionRate').textContent = `${collectionRate}%`;
    document.getElementById('collectionProgress').style.width = `${collectionRate}%`;
    
    // Update outstanding amount
    const outstanding = invoiceData.unpaid + invoiceData.overdue;
    document.getElementById('outstandingAmount').textContent = `₹${outstanding.toLocaleString()}`;
    document.getElementById('totalInvoices').textContent = totalInvoices;
    
    // Show outstanding alert if needed
    if (outstanding > 0) {
        const alert = document.getElementById('outstandingAlert');
        alert.style.display = 'block';
        alert.innerHTML = `<strong>Alert:</strong> ₹${outstanding.toLocaleString()} in outstanding invoices requires attention`;
    }
}

async function updateCharts() {
    try {
        // Update Quotations Chart
        const quotationsResponse = await fetch('/ergon/finance/visualization?type=quotations');
        const quotationsData = await quotationsResponse.json();
        
        quotationsChart.data.datasets[0].data = quotationsData.data;
        quotationsChart.update();
        
        // Update Purchase Orders Chart
        const poResponse = await fetch('/ergon/finance/visualization?type=purchase_orders');
        const poData = await poResponse.json();
        
        purchaseOrdersChart.data.labels = poData.labels;
        purchaseOrdersChart.data.datasets[0].data = poData.data;
        purchaseOrdersChart.update();
        
        // Update Invoices Chart
        const invoicesResponse = await fetch('/ergon/finance/visualization?type=invoices');
        const invoicesData = await invoicesResponse.json();
        
        invoicesChart.data.datasets[0].data = invoicesData.data;
        invoicesChart.update();
        
        // Show outstanding alert if needed
        if (invoicesData.outstanding > 0) {
            document.getElementById('outstandingAlert').style.display = 'block';
            document.getElementById('outstandingAlert').innerHTML = 
                `<strong>Alert:</strong> ₹${invoicesData.outstanding.toLocaleString()} in outstanding invoices`;
        }
        
    } catch (error) {
        console.error('Failed to update charts:', error);
    }
}

function exportChart(type) {
    window.open(`/ergon/finance/export?type=${type}`, '_blank');
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
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/dashboard.php';
?>

