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
                    <button id="structureBtn" class="btn btn-light btn-sm w-100">View Table Structure</button>
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

    <!-- Update Progress Modal -->
    <div class="modal-overlay" id="structureModal" style="display: none;">
        <div class="modal-container">
            <div class="modal-header">
                <h3>📊 Database Structure</h3>
                <button class="modal-close" onclick="closeStructureModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="structureContainer">
                    <div class="loading-state">
                        <div class="spinner"></div>
                        <p>Loading structure...</p>
                    </div>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
let tablesChart, invoicesChart;

document.addEventListener('DOMContentLoaded', function() {
    initCharts();
    loadFinanceStats();
    loadTables();
    
    document.getElementById('syncBtn').addEventListener('click', syncFinanceData);
    document.getElementById('structureBtn').addEventListener('click', showTableStructure);
    document.getElementById('loadData').addEventListener('click', loadTableData);
});

function initCharts() {
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
        
        document.getElementById('structureModal').style.display = 'flex';
        
    } catch (error) {
        alert('Failed to load structure: ' + error.message);
    } finally {
        btn.disabled = false;
        btn.textContent = 'View Table Structure';
    }
}

function renderTableStructure(tables) {
    const container = document.getElementById('structureContainer');
    
    let html = '<div class="structure-list">';
    
    tables.forEach((table, index) => {
        html += `
            <div class="structure-item">
                <div class="structure-header" onclick="toggleStructure(this)">
                    <span class="structure-name">${table.display_name}</span>
                    <div class="structure-badges">
                        <span class="badge">${table.column_count} cols</span>
                        <span class="badge">${table.actual_rows} rows</span>
                    </div>
                    <span class="expand-icon">▼</span>
                </div>
                <div class="structure-details" style="display: ${index === 0 ? 'block' : 'none'}">
                    <div class="columns-grid">`;
        
        table.columns.forEach(col => {
            html += `
                <div class="column-item">
                    <div class="column-name">${col.name}</div>
                    <div class="column-type">${col.type}</div>
                    <div class="column-null">${col.nullable ? 'NULL' : 'NOT NULL'}</div>
                </div>`;
        });
        
        html += `
                    </div>
                </div>
            </div>`;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

function toggleStructure(header) {
    const details = header.nextElementSibling;
    const icon = header.querySelector('.expand-icon');
    
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
        const tablesResponse = await fetch('/ergon/finance/chart?type=tables');
        const tablesData = await tablesResponse.json();
        
        tablesChart.data.labels = tablesData.labels.map(l => l.replace('finance_', ''));
        tablesChart.data.datasets[0].data = tablesData.data;
        tablesChart.update();
        
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

<style>
/* Modal Styles - Similar to Task Management Update Progress */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-container {
    background: white;
    border-radius: 8px;
    width: 90%;
    max-width: 800px;
    max-height: 80vh;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e5e7eb;
    background: #f9fafb;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #374151;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #6b7280;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: all 0.2s;
}

.modal-close:hover {
    background: #e5e7eb;
    color: #374151;
}

.modal-body {
    padding: 1.5rem;
    max-height: 60vh;
    overflow-y: auto;
}

.loading-state {
    text-align: center;
    padding: 2rem;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #e5e7eb;
    border-top: 4px solid #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.structure-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.structure-item {
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    overflow: hidden;
}

.structure-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    background: #f9fafb;
    cursor: pointer;
    transition: background 0.2s;
}

.structure-header:hover {
    background: #f3f4f6;
}

.structure-name {
    font-weight: 500;
    color: #374151;
}

.structure-badges {
    display: flex;
    gap: 0.5rem;
}

.badge {
    background: #3b82f6;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.expand-icon {
    color: #6b7280;
    font-size: 0.875rem;
    transition: transform 0.2s;
}

.structure-details {
    padding: 1rem;
    background: white;
    border-top: 1px solid #e5e7eb;
}

.columns-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.5rem;
}

.column-item {
    padding: 0.75rem;
    background: #f9fafb;
    border-radius: 4px;
    border-left: 3px solid #3b82f6;
}

.column-name {
    font-weight: 500;
    color: #374151;
    font-family: monospace;
    margin-bottom: 0.25rem;
}

.column-type {
    font-size: 0.75rem;
    color: #6b7280;
    background: #e5e7eb;
    padding: 0.125rem 0.375rem;
    border-radius: 3px;
    display: inline-block;
    margin-bottom: 0.25rem;
}

.column-null {
    font-size: 0.75rem;
    color: #6b7280;
}
</style>