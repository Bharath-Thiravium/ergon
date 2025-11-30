// Replace initCharts() with this
function initCharts() {
    // Charts disabled - using HTML visualization instead
}

// Replace updateAnalyticsWidgets() with this
async function updateAnalyticsWidgets() {
    try {
        const prefix = document.getElementById('companyPrefix').value;
        if (!prefix) return;
        
        const url = `/ergon/src/api/index.php?action=analytics&prefix=${prefix}`;
        const response = await fetch(url);
        const result = await response.json();
        
        if (!result.success || !result.data) return;
        const data = result.data;
        
        // Update quotations
        if (data.quotationDonut) {
            const p = data.quotationDonut.pending || 0;
            const pl = data.quotationDonut.placed || 0;
            const r = data.quotationDonut.rejected || 0;
            
            document.getElementById('placedQuotations').textContent = pl;
            document.getElementById('rejectedQuotations').textContent = r;
            document.getElementById('pendingQuotations').textContent = p;
            document.getElementById('quotationsTotal').textContent = p + pl + r;
            
            renderSimpleChart('quotationsChart', [p, pl, r], ['#3b82f6', '#10b981', '#ef4444']);
        }
        
        // Update invoices
        if (data.invoiceCollections) {
            document.getElementById('dsoMetric').textContent = `${data.invoiceCollections.dso || 0} days`;
            document.getElementById('invoicesTotal').textContent = `₹${(data.invoiceCollections.total_invoice_value || 0).toLocaleString()}`;
        }
        
        // Update outstanding
        if (data.customerOutstanding && Array.isArray(data.customerOutstanding)) {
            const total = data.customerOutstanding.reduce((sum, c) => sum + (parseFloat(c.outstanding) || 0), 0);
            document.getElementById('outstandingTotal').textContent = `₹${total.toLocaleString()}`;
            document.getElementById('customerDiversity').textContent = data.customerOutstanding.length;
        }
        
    } catch (error) {
        console.error('Analytics widgets update failed:', error);
    }
}

function renderSimpleChart(canvasId, data, colors) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || !canvas.parentElement) return;
    
    const maxVal = Math.max(...data, 1);
    const container = canvas.parentElement;
    let html = '<div style="display:flex; gap:10px; align-items:flex-end; height:80px; padding:10px;">';
    
    data.forEach((val, i) => {
        const height = (val / maxVal) * 70;
        html += `<div style="flex:1; background:${colors[i]}; height:${height}px; border-radius:4px; position:relative;"><div style="position:absolute; bottom:-20px; left:0; right:0; text-align:center; font-size:12px;">${val}</div></div>`;
    });
    
    html += '</div>';
    container.innerHTML = html;
}
