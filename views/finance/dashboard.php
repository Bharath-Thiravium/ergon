<?php 
$title = 'Finance Dashboard';
$active_page = 'finance';
ob_start(); 
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h4>Finance Data Sync</h4>
            <button id="syncBtn" class="btn btn-primary">Sync All PostgreSQL Data</button>
        </div>
        <div class="card-body">
            <div id="progressContainer" style="display:none;">
                <div class="progress mb-3">
                    <div id="progressBar" class="progress-bar" style="width: 0%"></div>
                </div>
                <div id="progressText">Starting sync...</div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="card border-info">
                        <div class="card-header bg-info text-white">Available Tables</div>
                        <div class="card-body">
                            <div id="tablesContainer">Loading...</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between">
                            <span>Data Viewer</span>
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
                                    <i class="bi bi-database"></i>
                                    <p>Click "Sync All PostgreSQL Data" to get started</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadTables();
    
    document.getElementById('syncBtn').addEventListener('click', startBatchSync);
    document.getElementById('loadData').addEventListener('click', loadTableData);
});

async function startBatchSync() {
    const btn = document.getElementById('syncBtn');
    const progressContainer = document.getElementById('progressContainer');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    
    btn.disabled = true;
    btn.textContent = 'Syncing...';
    progressContainer.style.display = 'block';
    
    let batch = 0;
    let totalProcessed = 0;
    let totalTables = 0;
    
    try {
        do {
            const formData = new FormData();
            formData.append('batch', batch);
            
            const response = await fetch('/ergon/finance/sync', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.error) {
                throw new Error(result.error);
            }
            
            totalProcessed = result.processed;
            totalTables = result.total;
            
            const percentage = Math.round((totalProcessed / totalTables) * 100);
            progressBar.style.width = percentage + '%';
            progressText.textContent = `Processed ${totalProcessed} of ${totalTables} tables (${percentage}%)`;
            
            if (result.hasMore) {
                batch = result.nextBatch;
                await new Promise(resolve => setTimeout(resolve, 500)); // Small delay between batches
            } else {
                break;
            }
            
        } while (true);
        
        progressText.textContent = `✅ Successfully synced all ${totalTables} tables!`;
        loadTables();
        
    } catch (error) {
        progressText.textContent = `❌ Sync failed: ${error.message}`;
    } finally {
        btn.disabled = false;
        btn.textContent = 'Sync All PostgreSQL Data';
        
        setTimeout(() => {
            progressContainer.style.display = 'none';
        }, 3000);
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
                html += `<div class="list-group-item d-flex justify-content-between">
                    <span><strong>${table.table_name}</strong></span>
                    <small>${table.record_count} records</small>
                </div>`;
                select.innerHTML += `<option value="${table.table_name}">${table.table_name}</option>`;
            });
            
            html += '</div>';
            container.innerHTML = html;
        } else {
            container.innerHTML = '<p class="text-muted">No tables found. Click sync to load data.</p>';
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
        const response = await fetch(`/ergon/finance/data?table=${table}&limit=50`);
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
        container.innerHTML = '<p class="text-muted">No data found</p>';
        return;
    }
    
    let html = '<div class="table-responsive"><table class="table table-striped table-sm">';
    
    html += '<thead class="table-dark"><tr>';
    columns.forEach(col => html += `<th>${col}</th>`);
    html += '</tr></thead><tbody>';
    
    data.forEach(row => {
        html += '<tr>';
        columns.forEach(col => {
            const value = row[col];
            html += `<td>${value !== null ? value : ''}</td>`;
        });
        html += '</tr>';
    });
    
    html += '</tbody></table></div>';
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