// Load charts inline after page load
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        const prefix = document.getElementById('companyPrefix')?.value || '';
        if (prefix && typeof loadAllCharts === 'function') {
            console.log('Auto-loading charts for prefix:', prefix);
            loadAllCharts();
        }
    }, 1000);
});
