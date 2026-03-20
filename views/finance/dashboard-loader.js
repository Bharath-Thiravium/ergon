function loadAllCharts(prefixOverride) {
    const prefix = prefixOverride || document.getElementById('companyPrefix')?.value;
    if (!prefix) return;
    if (window._dashboardCharts && window._dashboardCharts.renderAllCharts) {
        window._dashboardCharts.renderAllCharts(prefix);
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

// Charts are triggered by dashboard.php input/keypress handlers and loadCompanyPrefix()
