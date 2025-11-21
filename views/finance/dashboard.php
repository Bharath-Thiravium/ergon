<?php 
$title = 'Finance Dashboard';
$active_page = 'finance';
ob_start(); 
?>

<div class="container-fluid">
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="kpi-card">
            <div class="kpi-card__value" id="totalTables">0</div>
            <div class="kpi-card__label">Finance Tables</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-card__value" id="totalRecords">0</div>
            <div class="kpi-card__label">Total Records</div>
        </div>
        <div class="kpi-card">
            <button id="structureBtn" class="btn btn--primary btn--block">View Table Structure</button>
        </div>
        <div class="kpi-card">
            <button id="syncBtn" class="btn btn--warning btn--block">Sync Finance Data</button>
            <button id="analyzeBtn" class="btn btn--secondary btn--block">Analyze All Tables</button>
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

    <!-- Modular Finance Panels -->
    <div class="dashboard-grid">
        <!-- Quotations Panel -->
        <div class="card">
            <div class="card__header">
                <h3 class="card__title">📋 Quotations Lifecycle</h3>
                <button class="btn btn--secondary btn--sm" onclick="exportChart('quotations')">CSV</button>
            </div>
            <div class="card__body">
                <canvas id="quotationsChart" height="200"></canvas>
            </div>
        </div>
        
        <!-- Purchase Orders Panel -->
        <div class="card">
            <div class="card__header">
                <h3 class="card__title">🛒 Purchase Orders Volume</h3>
                <button class="btn btn--secondary btn--sm" onclick="exportChart('purchase_orders')">CSV</button>
            </div>
            <div class="card__body">
                <canvas id="purchaseOrdersChart" height="200"></canvas>
            </div>
        </div>
        
        <!-- Invoices Panel -->
        <div class="card">
            <div class="card__header">
                <h3 class="card__title">💰 Invoices Status</h3>
                <button class="btn btn--secondary btn--sm" onclick="exportChart('invoices')">CSV</button>
            </div>
            <div class="card__body">
                <canvas id="invoicesChart" height="200"></canvas>
                <div id="outstandingAlert" class="alert alert--warning" style="display: none;"></div>
            </div>
        </div>
        
        <!-- Payments Panel -->
        <div class="card">
            <div class="card__header">
                <h3 class="card__title">💳 Payments Status</h3>
            </div>
            <div class="card__body">
                <div class="empty-state">
                    <div class="empty-icon">⚠️</div>
                    <h5>No Payment Records</h5>
                    <p class="text-muted">Payment data will appear here once records are available</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Tables -->
    <div class="dashboard-grid">
        <div class="card">
            <div class="card__header">
                <h3 class="card__title">Finance Tables</h3>
            </div>
            <div class="card__body">
                <div id="tablesContainer">Loading...</div>
            </div>
        </div>
        <div class="card" style="grid-column: span 2;">
            <div class="card__header">
                <h3 class="card__title">Table Data</h3>
                <div class="btn-group">
                    <select id="tableSelect" class="form-control">
                        <option value="">Select Table</option>
                    </select>
                    <button id="loadData" class="btn btn--primary btn--sm">Load</button>
                </div>
            </div>
            <div class="card__body">
                <div id="dataContainer">
                    <div class="empty-state">
                        <div class="empty-icon">📈</div>
                        <p>Select a finance table to view data</p>
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
        
        document.getElementById('totalTables').textContent = data.totalTables || 0;
        document.getElementById('totalRecords').textContent = data.totalRecords || 0;
        
    } catch (error) {
        console.error('Failed to load stats:', error);
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
            let html = '<div class="list-group list-group-flush">';
            select.innerHTML = '<option value="">Select Table</option>';
            
            data.tables.forEach(table => {
                const displayName = table.table_name.replace('finance_', '');
                html += `<div class="list-group-item d-flex justify-content-between">
                    <span><strong>${displayName}</strong></span>
                    <small class="badge bg-primary">${table.record_count}</small>
                </div>`;
                select.innerHTML += `<option value="${table.table_name}">${displayName}</option>`;
            });
            
            html += '</div>';
            container.innerHTML = html;
        } else {
            container.innerHTML = '<p class="text-muted">No finance tables found. Click sync to load data.</p>';
        }
    } catch (error) {
        document.getElementById('tablesContainer').innerHTML = '<p class="text-danger">Error loading tables</p>';
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
        container.innerHTML = '<p class="text-muted">No data found in this table</p>';
        return;
    }
    
    let html = '<div class="table-responsive"><table class="table table-striped table-sm">';
    
    html += '<thead class="table-dark"><tr>';
    columns.forEach(col => html += `<th>${col}</th>`);
    html += '</tr></thead><tbody>';
    
    data.forEach(row => {
        html += '<tr>';
        columns.forEach(col => {
            let value = row[col];
            if (typeof value === 'number' && col.includes('amount')) {
                value = '₹' + value.toLocaleString();
            }
            html += `<td>${value !== null ? value : ''}</td>`;
        });
        html += '</tr>';
    });
    
    html += '</tbody></table></div>';
    html += `<small class="text-muted">Showing ${data.length} records</small>`;
    
    container.innerHTML = html;
}

function showError(message) {
    document.getElementById('dataContainer').innerHTML = 
        `<div class="alert alert-danger">${message}</div>`;
}
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/dashboard.php';
?>

