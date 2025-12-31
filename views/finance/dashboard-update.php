<!-- REPLACE THE OLD updateAnalyticsWidgets FUNCTION (starting around line 1797) WITH THIS: -->

async function updateAnalyticsWidgets() {
    try {
        const prefix = document.getElementById('companyPrefix').value;
        const customerSelect = document.getElementById('customerFilter');
        const customerId = customerSelect ? customerSelect.value : '';
        
        if (!prefix) {
            console.log('No prefix for analytics widgets');
            return;
        }
        
        console.log('Updating analytics widgets for prefix:', prefix);
        
        let url = `/ergon/src/api/index.php?action=analytics&prefix=${prefix}`;
        if (customerId) {
            url += `&customer_id=${customerId}`;
        }
        
        const response = await fetch(url);
        const result = await response.json();
        
        if (!result.success) {
            console.error('Analytics API error:', result.error);
            return;
        }
        
        const data = result.data;
        
        // Update quotations
        if (data.quotationDonut) {
            const el1 = document.getElementById('placedQuotations');
            const el2 = document.getElementById('rejectedQuotations');
            const el3 = document.getElementById('pendingQuotations');
            const el4 = document.getElementById('quotationsTotal');
            
            if (el1) el1.textContent = data.quotationDonut.placed || 0;
            if (el2) el2.textContent = data.quotationDonut.rejected || 0;
            if (el3) el3.textContent = data.quotationDonut.pending || 0;
            if (el4) el4.textContent = (data.quotationDonut.placed + data.quotationDonut.rejected + data.quotationDonut.pending) || 0;
            
            if (quotationsChart) {
                quotationsChart.data.datasets[0].data = [
                    data.quotationDonut.pending || 0,
                    data.quotationDonut.placed || 0,
                    data.quotationDonut.rejected || 0
                ];
                quotationsChart.update();
            }
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
