// Replace updateAnalyticsWidgets with this version that doesn't rely on Chart.js

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
        
        // Update quotations
        if (data.quotationDonut) {
            const placed = data.quotationDonut.placed || 0;
            const rejected = data.quotationDonut.rejected || 0;
            const pending = data.quotationDonut.pending || 0;
            const total = placed + rejected + pending;
            
            // Update DOM
            const el1 = document.getElementById('placedQuotations');
            const el2 = document.getElementById('rejectedQuotations');
            const el3 = document.getElementById('pendingQuotations');
            const el4 = document.getElementById('quotationsTotal');
            
            if (el1) el1.textContent = placed;
            if (el2) el2.textContent = rejected;
            if (el3) el3.textContent = pending;
            if (el4) el4.textContent = total;
            
            // Create simple bar visualization
            const chartCanvas = document.getElementById('quotationsChart');
            if (chartCanvas && chartCanvas.parentElement) {
                const container = chartCanvas.parentElement;
                const maxVal = Math.max(placed, rejected, pending, 1);
                
                let html = '<div style="display:flex; gap:10px; align-items:flex-end; height:80px; padding:10px;">';
                
                // Pending bar
                const pendingHeight = (pending / maxVal) * 70;
                html += `<div style="flex:1; background:#3b82f6; height:${pendingHeight}px; border-radius:4px; position:relative;">
                    <div style="position:absolute; bottom:-20px; left:0; right:0; text-align:center; font-size:12px;">${pending}</div>
                </div>`;
                
                // Placed bar
                const placedHeight = (placed / maxVal) * 70;
                html += `<div style="flex:1; background:#10b981; height:${placedHeight}px; border-radius:4px; position:relative;">
                    <div style="position:absolute; bottom:-20px; left:0; right:0; text-align:center; font-size:12px;">${placed}</div>
                </div>`;
                
                // Rejected bar
                const rejectedHeight = (rejected / maxVal) * 70;
                html += `<div style="flex:1; background:#ef4444; height:${rejectedHeight}px; border-radius:4px; position:relative;">
                    <div style="position:absolute; bottom:-20px; left:0; right:0; text-align:center; font-size:12px;">${rejected}</div>
                </div>`;
                
                html += '</div>';
                container.innerHTML = html;
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
