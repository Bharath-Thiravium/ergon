// REPLACE updateAnalyticsWidgets with this version that properly updates the chart

async function updateAnalyticsWidgets() {
    try {
        const prefix = document.getElementById('companyPrefix').value;
        if (!prefix) return;
        
        const url = `/ergon/src/api/index.php?action=analytics&prefix=${prefix}`;
        const response = await fetch(url);
        const result = await response.json();
        
        if (!result.success || !result.data) {
            console.error('Analytics API error:', result.error);
            return;
        }
        
        const data = result.data;
        console.log('Analytics data:', data);
        
        // Update quotations - CRITICAL: Update chart FIRST before updating DOM
        if (data.quotationDonut) {
            console.log('Quotation data:', data.quotationDonut);
            
            // Update chart data
            if (quotationsChart) {
                quotationsChart.data.datasets[0].data = [
                    data.quotationDonut.pending || 0,
                    data.quotationDonut.placed || 0,
                    data.quotationDonut.rejected || 0
                ];
                quotationsChart.update();
                console.log('Chart updated with:', quotationsChart.data.datasets[0].data);
            }
            
            // Update DOM elements
            const el1 = document.getElementById('placedQuotations');
            const el2 = document.getElementById('rejectedQuotations');
            const el3 = document.getElementById('pendingQuotations');
            const el4 = document.getElementById('quotationsTotal');
            
            if (el1) el1.textContent = data.quotationDonut.placed || 0;
            if (el2) el2.textContent = data.quotationDonut.rejected || 0;
            if (el3) el3.textContent = data.quotationDonut.pending || 0;
            if (el4) el4.textContent = (data.quotationDonut.placed + data.quotationDonut.rejected + data.quotationDonut.pending) || 0;
        }
        
        // Update PO claims
        if (data.poClaimDistribution) {
            const fulfillmentEl = document.getElementById('poFulfillmentRate');
            const totalCount = Object.values(data.poClaimDistribution).reduce((a, b) => a + b, 0);
            if (fulfillmentEl && totalCount > 0) {
                const fullCount = data.poClaimDistribution['100%'] || 0;
                fulfillmentEl.textContent = `${Math.round((fullCount / totalCount) * 100)}%`;
            }
        }
        
        // Update invoices
        if (data.invoiceCollections) {
            const dsoEl = document.getElementById('dsoMetric');
            const totalEl = document.getElementById('invoicesTotal');
            
            if (dsoEl) dsoEl.textContent = `${data.invoiceCollections.dso || 0} days`;
            if (totalEl) totalEl.textContent = `₹${(data.invoiceCollections.total_invoice_value || 0).toLocaleString()}`;
        }
        
        // Update customer outstanding
        if (data.customerOutstanding && Array.isArray(data.customerOutstanding)) {
            const totalEl = document.getElementById('outstandingTotal');
            const diversityEl = document.getElementById('customerDiversity');
            
            const total = data.customerOutstanding.reduce((sum, c) => sum + (parseFloat(c.outstanding) || 0), 0);
            if (totalEl) totalEl.textContent = `₹${total.toLocaleString()}`;
            if (diversityEl) diversityEl.textContent = data.customerOutstanding.length;
        }
        
    } catch (error) {
        console.error('Analytics widgets update failed:', error);
    }
}
