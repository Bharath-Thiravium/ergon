<?php
// Simple Finance API Test
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Finance API Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test { margin: 10px 0; padding: 10px; border: 1px solid #ddd; }
        .success { background: #d4edda; border-color: #c3e6cb; }
        .error { background: #f8d7da; border-color: #f5c6cb; }
        .loading { background: #fff3cd; border-color: #ffeaa7; }
        pre { background: #f8f9fa; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Finance API Test</h1>
    <div id="results"></div>

    <script>
    const endpoints = [
        'dashboard-stats',
        'tables',
        'customers',
        'company-prefix',
        'outstanding-invoices'
    ];

    const resultsDiv = document.getElementById('results');

    async function testEndpoint(endpoint) {
        const testDiv = document.createElement('div');
        testDiv.className = 'test loading';
        testDiv.innerHTML = `<h3>Testing: /ergon/finance/${endpoint}</h3><p>Loading...</p>`;
        resultsDiv.appendChild(testDiv);

        try {
            const response = await fetch(`/ergon/finance/${endpoint}`);
            const text = await response.text();
            
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                throw new Error('Invalid JSON response: ' + text.substring(0, 200));
            }

            testDiv.className = 'test success';
            testDiv.innerHTML = `
                <h3>✅ /ergon/finance/${endpoint}</h3>
                <p><strong>Status:</strong> ${response.status} ${response.statusText}</p>
                <p><strong>Response:</strong></p>
                <pre>${JSON.stringify(data, null, 2)}</pre>
            `;
        } catch (error) {
            testDiv.className = 'test error';
            testDiv.innerHTML = `
                <h3>❌ /ergon/finance/${endpoint}</h3>
                <p><strong>Error:</strong> ${error.message}</p>
            `;
        }
    }

    // Test all endpoints
    endpoints.forEach(endpoint => {
        setTimeout(() => testEndpoint(endpoint), 100);
    });
    </script>
</body>
</html>