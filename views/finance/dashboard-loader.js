function loadAllCharts() {
    const prefix = document.getElementById('companyPrefix')?.value;
    console.log('loadAllCharts called with prefix:', prefix);
    if (!prefix) {
        console.warn('No prefix selected for charts');
        return;
    }
    if (window._dashboardCharts && window._dashboardCharts.renderAllCharts) {
        console.log('Calling renderAllCharts with prefix:', prefix);
        window._dashboardCharts.renderAllCharts(prefix);
    } else {
        console.error('_dashboardCharts not available');
    }
}

function loadCashFlow() {
    const prefix = document.getElementById('companyPrefix')?.value;
    if (!prefix) return;
    
    fetch(`/ergon/src/api/dashboard/invoices.php?prefix=${encodeURIComponent(prefix)}`)
        .then(r => r.json())
        .then(d => {
            if (d.success && d.data) {
                const outstanding = d.data.total_value - (d.data.paid || 0);
                document.getElementById('expectedInflow').textContent = '₹' + outstanding.toLocaleString();
            }
        })
        .catch(e => console.warn('Cash flow load failed:', e));
}

// Auto-trigger on prefix input change
document.addEventListener('DOMContentLoaded', () => {
    const prefixInput = document.getElementById('companyPrefix');
    if (prefixInput) {
        prefixInput.addEventListener('change', loadAllCharts);
        prefixInput.addEventListener('keyup', (e) => {
            if (e.key === 'Enter') loadAllCharts();
        });
    }
});
