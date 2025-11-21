<?php 
$title = 'Finance Dashboard';
$active_page = 'finance';
ob_start(); 
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h4>Finance Data Management</h4>
            <small class="text-muted">Import PostgreSQL data via CSV/JSON files</small>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">Upload Data</div>
                        <div class="card-body">
                            <form id="uploadForm" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label class="form-label">Table Name</label>
                                    <input type="text" name="tableName" class="form-control" placeholder="e.g., sales_data" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Data File (CSV/JSON)</label>
                                    <input type="file" name="dataFile" class="form-control" accept=".csv,.json" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Upload Data</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-success">
                        <div class="card-header bg-success text-white">Quick Actions</div>
                        <div class="card-body">
                            <button id="syncBtn" class="btn btn-success mb-2 w-100">Create Sample Data</button>
                            <button id="refreshBtn" class="btn btn-info w-100">Refresh Tables</button>
                            <hr>
                            <small class="text-muted">
                                <strong>Export from PostgreSQL:</strong><br>
                                1. Use pgAdmin or command line<br>
                                2. Export as CSV/JSON<br>
                                3. Upload files here
                            </small>
                        </div>
                    </div>
                </div>
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
                                    <p>Upload data or create sample data to get started</p>
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
class FinanceManager {
    constructor() {
        this.loadTables();
        this.bindEvents();
    }
    
    bindEvents() {
        document.getElementById('syncBtn').addEventListener('click', () => this.createSample());
        document.getElementById('refreshBtn').addEventListener('click', () => this.loadTables());
        document.getElementById('uploadForm').addEventListener('submit', (e) => this.uploadData(e));
        document.getElementById('loadData').addEventListener('click', () => this.loadTableData());
    }
    
    async createSample() {
        const btn = document.getElementById('syncBtn');
        btn.disabled = true;
        btn.textContent = 'Creating...';
        
        try {
            const response = await fetch('/ergon/finance/sync', {method: 'POST'});
            const result = await response.json();
            
            if (result.error) {
                alert('Error: ' + result.error);
            } else {
                alert(result.message || 'Sample data created successfully');
                this.loadTables();
            }
        } catch (error) {
            alert('Error: ' + error.message);
        } finally {
            btn.disabled = false;
            btn.textContent = 'Create Sample Data';
        }
    }
    
    async uploadData(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        
        try {
            const response = await fetch('/ergon/finance/upload', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.error) {
                alert('Upload failed: ' + result.error);
            } else {
                alert(`Successfully uploaded ${result.records} records`);
                e.target.reset();
                this.loadTables();
            }
        } catch (error) {
            alert('Upload failed: ' + error.message);
        }
    }
    
    async loadTables() {
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
                container.innerHTML = '<p class="text-muted">No tables found. Upload data to get started.</p>';
            }
        } catch (error) {
            document.getElementById('tablesContainer').innerHTML = '<p class="text-danger">Error loading tables</p>';
        }
    }
    
    async loadTableData() {
        const table = document.getElementById('tableSelect').value;
        
        if (!table) {
            alert('Please select a table');
            return;
        }
        
        try {
            const response = await fetch(`/ergon/finance/data?table=${table}&limit=50`);
            const data = await response.json();
            
            if (data.error) {
                this.showError(data.error);
                return;
            }
            
            this.renderTable(data.data, data.columns);
        } catch (error) {
            this.showError('Failed to load data: ' + error.message);
        }
    }
    
    renderTable(data, columns) {
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
    
    showError(message) {
        document.getElementById('dataContainer').innerHTML = 
            `<div class="alert alert-danger">${message}</div>`;
    }
}

new FinanceManager();
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/dashboard.php';
?>