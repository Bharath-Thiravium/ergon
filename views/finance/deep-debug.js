// Deep investigation script - runs on actual dashboard
console.log('=== DEEP DEBUG START ===');

// Test 1: Check if SVG elements exist
const svgs = ['quotationsChart', 'purchaseOrdersChart', 'invoicesChart', 'outstandingByCustomerChart', 'agingBucketsChart', 'paymentsChart'];
console.log('SVG Elements:', svgs.map(id => `${id}: ${document.getElementById(id) ? '✓' : '✗'}`).join(', '));

// Test 2: Check functions
console.log('loadAllCharts:', typeof loadAllCharts);
console.log('fetchChartData:', typeof fetchChartData);
console.log('renderQuotationsChart:', typeof renderQuotationsChart);

// Test 3: Check prefix
const prefixInput = document.getElementById('companyPrefix');
console.log('Prefix input found:', !!prefixInput);
console.log('Prefix value:', prefixInput?.value || 'EMPTY');

// Test 4: Try to manually call loadAllCharts
console.log('Attempting manual loadAllCharts call...');
if (typeof loadAllCharts === 'function') {
    try {
        loadAllCharts();
        console.log('loadAllCharts called successfully');
    } catch (e) {
        console.error('loadAllCharts error:', e.message);
    }
} else {
    console.error('loadAllCharts is not a function');
}

// Test 5: Check if scripts are loaded
console.log('Script check:');
console.log('- dashboard-charts.js loaded:', !!window.renderQuotationsChart);
console.log('- cashflow-listener.js loaded:', !!window.loadCashFlow);

// Test 6: Try API call directly
console.log('Testing API call...');
fetch('/ergon/src/api/charts.php?chart=quotations&prefix=ERGN')
    .then(r => r.json())
    .then(data => {
        console.log('API Response:', data);
        if (data.success && typeof renderQuotationsChart === 'function') {
            console.log('Attempting manual render...');
            renderQuotationsChart(data.data);
            console.log('Manual render completed');
        }
    })
    .catch(e => console.error('API Error:', e));

console.log('=== DEEP DEBUG END ===');
