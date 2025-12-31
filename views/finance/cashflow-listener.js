// Load cash flow metrics
async function loadCashFlow() {
    try {
        const prefixInput = document.getElementById('companyPrefix');
        const prefix = prefixInput?.value?.trim() || '';
        
        if (!prefix) return;
        
        const url = '/ergon/src/api/cashflow.php?prefix=' + encodeURIComponent(prefix);
        const response = await fetch(url, { signal: AbortSignal.timeout(5000) }).catch(e => null);
        if (!response || !response.ok) throw new Error('Cashflow API unavailable');
        
        const result = await response.json();
        if (result.success && result.data) {
            const data = result.data;
            const expectedInflowEl = document.getElementById('expectedInflow');
            const poCommitmentsEl = document.getElementById('poCommitments');
            const netCashFlowEl = document.getElementById('netCashFlow');
            
            if (expectedInflowEl) expectedInflowEl.textContent = '₹' + parseFloat(data.expected_inflow).toLocaleString('en-IN', {maximumFractionDigits: 2});
            if (poCommitmentsEl) poCommitmentsEl.textContent = '₹' + parseFloat(data.po_commitments).toLocaleString('en-IN', {maximumFractionDigits: 2});
            if (netCashFlowEl) {
                const netFlow = parseFloat(data.net_cash_flow);
                netCashFlowEl.textContent = '₹' + netFlow.toLocaleString('en-IN', {maximumFractionDigits: 2});
                netCashFlowEl.className = 'flow-value ' + (netFlow >= 0 ? 'flow-positive' : 'flow-negative');
            }
        }
    } catch (error) {
        console.warn('Cashflow load failed:', error.message);
    }
}

// Reload cashflow when prefix changes
const prefixInput = document.getElementById('companyPrefix');
if (prefixInput) {
    prefixInput.addEventListener('change', loadCashFlow);
    prefixInput.addEventListener('input', function() {
        clearTimeout(this.cashflowTimeout);
        this.cashflowTimeout = setTimeout(loadCashFlow, 500);
    });
}
