<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test New Finance Module</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 1rem;
        }
        .test-section {
            margin: 2rem 0;
            padding: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .test-section h3 {
            margin-top: 0;
            color: #666;
        }
        .btn {
            background: #007cba;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 0.5rem 0.5rem 0.5rem 0;
        }
        .btn:hover {
            background: #005a87;
        }
        .result {
            margin-top: 1rem;
            padding: 1rem;
            background: #f9f9f9;
            border-radius: 4px;
            white-space: pre-wrap;
            font-family: monospace;
            font-size: 0.9rem;
        }
        .success { border-left: 4px solid #28a745; }
        .error { border-left: 4px solid #dc3545; }
        .info { border-left: 4px solid #17a2b8; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 New Finance Module Test Suite</h1>
        <p>This page tests the new rebuilt finance module functionality.</p>
        
        <div class="test-section">
            <h3>1. Database Setup</h3>
            <button class="btn" onclick="runSetup()">Run Database Setup</button>
            <div id="setupResult" class="result" style="display: none;"></div>
        </div>
        
        <div class="test-section">
            <h3>2. API Endpoints</h3>
            <button class="btn" onclick="testAPI('stats')">Test Stats API</button>
            <button class="btn" onclick="testAPI('funnel')">Test Funnel API</button>
            <button class="btn" onclick="testAPI('charts')">Test Charts API</button>
            <button class="btn" onclick="testAPI('customers')">Test Customers API</button>
            <div id="apiResult" class="result" style="display: none;"></div>
        </div>
        
        <div class="test-section">
            <h3>3. Company Prefix</h3>
            <input type="text" id="prefixInput" placeholder="Enter company prefix (e.g., BKC)" style="padding: 0.5rem; margin-right: 0.5rem;">
            <button class="btn" onclick="updatePrefix()">Update Prefix</button>
            <button class="btn" onclick="getPrefix()">Get Current Prefix</button>
            <div id="prefixResult" class="result" style="display: none;"></div>
        </div>
        
        <div class="test-section">
            <h3>4. Data Sync</h3>
            <button class="btn" onclick="testSync()">Test Data Sync</button>
            <div id="syncResult" class="result" style="display: none;"></div>
        </div>
        
        <div class="test-section">
            <h3>5. Dashboard Access</h3>
            <a href="/ergon/finance" class="btn" target="_blank">Open New Finance Dashboard</a>
            <a href="/ergon/finance/old" class="btn" target="_blank">Open Old Finance Dashboard</a>
        </div>
    </div>

    <script>
        async function runSetup() {
            const result = document.getElementById('setupResult');
            result.style.display = 'block';
            result.textContent = 'Running database setup...';
            result.className = 'result info';
            
            try {
                const response = await fetch('/ergon/setup_new_finance.php');
                const text = await response.text();
                result.textContent = text;
                result.className = 'result success';
            } catch (error) {
                result.textContent = 'Setup failed: ' + error.message;
                result.className = 'result error';
            }
        }
        
        async function testAPI(action) {
            const result = document.getElementById('apiResult');
            result.style.display = 'block';
            result.textContent = `Testing ${action} API...`;
            result.className = 'result info';
            
            try {
                const response = await fetch(`/ergon/finance/new/api?action=${action}`);
                const data = await response.json();
                result.textContent = `${action.toUpperCase()} API Response:\n` + JSON.stringify(data, null, 2);
                result.className = 'result success';
            } catch (error) {
                result.textContent = `${action} API failed: ` + error.message;
                result.className = 'result error';
            }
        }
        
        async function updatePrefix() {
            const prefix = document.getElementById('prefixInput').value.trim();
            const result = document.getElementById('prefixResult');
            result.style.display = 'block';
            result.textContent = 'Updating prefix...';
            result.className = 'result info';
            
            try {
                const formData = new FormData();
                formData.append('prefix', prefix);
                
                const response = await fetch('/ergon/finance/new/api?action=prefix', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                result.textContent = 'Prefix Update Response:\n' + JSON.stringify(data, null, 2);
                result.className = 'result success';
            } catch (error) {
                result.textContent = 'Prefix update failed: ' + error.message;
                result.className = 'result error';
            }
        }
        
        async function getPrefix() {
            const result = document.getElementById('prefixResult');
            result.style.display = 'block';
            result.textContent = 'Getting current prefix...';
            result.className = 'result info';
            
            try {
                const response = await fetch('/ergon/finance/new/api?action=prefix');
                const data = await response.json();
                result.textContent = 'Current Prefix:\n' + JSON.stringify(data, null, 2);
                result.className = 'result success';
            } catch (error) {
                result.textContent = 'Get prefix failed: ' + error.message;
                result.className = 'result error';
            }
        }
        
        async function testSync() {
            const result = document.getElementById('syncResult');
            result.style.display = 'block';
            result.textContent = 'Testing data sync...';
            result.className = 'result info';
            
            try {
                const response = await fetch('/ergon/finance/new/api?action=sync');
                const data = await response.json();
                result.textContent = 'Sync Response:\n' + JSON.stringify(data, null, 2);
                result.className = data.success ? 'result success' : 'result error';
            } catch (error) {
                result.textContent = 'Sync test failed: ' + error.message;
                result.className = 'result error';
            }
        }
    </script>
</body>
</html>