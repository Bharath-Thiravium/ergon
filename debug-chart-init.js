// Add this to dashboard.php after initCharts() is called to verify chart exists

function debugChartInit() {
    console.log('quotationsChart exists:', typeof quotationsChart !== 'undefined');
    console.log('quotationsChart value:', quotationsChart);
    
    if (quotationsChart) {
        console.log('Chart data:', quotationsChart.data);
        console.log('Chart datasets:', quotationsChart.data.datasets);
        
        // Force update
        quotationsChart.data.datasets[0].data = [1, 0, 0];
        quotationsChart.update();
        console.log('Chart force-updated');
    } else {
        console.error('quotationsChart is not defined!');
    }
}

// Call this in browser console to debug
// debugChartInit();
