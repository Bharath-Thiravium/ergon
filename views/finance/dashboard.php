<?php 
$title = 'Finance Dashboard';
$active_page = 'finance';
ob_start(); 
?>

<div class="container-fluid">
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 id="totalTables">0</h5>
                    <small>Finance Tables</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 id="totalRecords">0</h5>
                    <small>Total Records</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 id="invoiceCount">0</h5>
                    <small>Invoices</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <button id="syncBtn" class="btn btn-light btn-sm w-100">Sync Finance Data</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Finance Tables Overview</div>
                <div class="card-body">
                    <canvas id="tablesChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Invoice Trends</div>
                <div class="card-body">
                    <canvas id="invoicesChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Tables -->
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">Finance Tables</div>
                <div class="card-body">
                    <div id="tablesContainer">Loading...</div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <span>Table Data</span>
                    <div>
                        <select id="tableSelect" class="form-select form-select-sm d-inline-block w-auto">
                            <option value="">Select Table</option>
                        </select>
                        <button id="loadData" class="btn btn-success btn-sm ms-2">Load</button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="dataContainer">
                        <div class="text-center text-muted">
                            <i class="bi bi-bar-chart"></i>
                            <p>Select a finance table to view data</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let tablesChart, invoicesChart;

document.addEventListener('DOMContentLoaded', function() {
    initCharts();
    loadFinanceStats();
    loadTables();
    
    document.getElementById('syncBtn').addEventListener('click', syncFinanceData);
    document.getElementById('loadData').addEventListener('click', loadTableData);
});

function initCharts() {
    // Tables Chart
    const tablesCtx = document.getElementById('tablesChart').getContext('2d');
    tablesChart = new Chart(tablesCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Records',
                data: [],
                backgroundColor: 'rgba(54, 162, 235, 0.8)'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // Invoices Chart
    const invoicesCtx = document.getElementById('invoicesChart').getContext('2d');
    invoicesChart = new Chart(invoicesCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Amount',
                data: [],
                borderColor: 'rgba(75, 192, 192, 1)',
                fill: false
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
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
        
        // Count invoices
        const invoiceTable = data.tables.find(t => t.table_name === 'finance_invoices');
        document.getElementById('invoiceCount').textContent = invoiceTable ? invoiceTable.record_count : 0;
        
    } catch (error) {
        console.error('Failed to load stats:', error);
    }
}

async function updateCharts() {
    try {
        // Update tables chart
        const tablesResponse = await fetch('/ergon/finance/chart?type=tables');
        const tablesData = await tablesResponse.json();
        
        tablesChart.data.labels = tablesData.labels.map(l => l.replace('finance_', ''));
        tablesChart.data.datasets[0].data = tablesData.data;
        tablesChart.update();
        
        // Update invoices chart
        const invoicesResponse = await fetch('/ergon/finance/chart?type=invoices');
        const invoicesData = await invoicesResponse.json();
        
        invoicesChart.data.labels = invoicesData.labels;
        invoicesChart.data.datasets[0].data = invoicesData.data;
        invoicesChart.update();
        
    } catch (error) {
        console.error('Failed to update charts:', error);
    }
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