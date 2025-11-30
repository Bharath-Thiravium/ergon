function initCharts() {
    renderChart('quotationsChart', ['Pending','Placed','Rejected'], [0,0,0], ['#3b82f6','#10b981','#ef4444']);
    renderChart('purchaseOrdersChart', ['PO'], [0], ['#059669']);
    renderChart('invoicesChart', ['Paid','Unpaid','Overdue'], [0,0,0], ['#10b981','#f59e0b','#ef4444']);
    renderChart('outstandingByCustomerChart', ['Top'], [0], ['#ef4444']);
    renderChart('agingBucketsChart', ['0-30','31-60','61-90','90+'], [0,0,0,0], ['#10b981','#f59e0b','#fb923c','#ef4444']);
    renderChart('paymentsChart', ['Payments'], [0], ['#3b82f6']);
}

function renderChart(id, labels, data, colors) {
    const el = document.getElementById(id);
    if (!el) return;
    const max = Math.max(...data, 1);
    let html = '<div style="display:flex;gap:8px;align-items:flex-end;height:100px;justify-content:center;">';
    labels.forEach((l, i) => {
        const h = (data[i] / max) * 80;
        html += `<div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;"><div style="width:100%;height:${h}px;background:${colors[i]};border-radius:4px;opacity:0.8;"></div><small style="font-size:10px;color:#666;">${l}</small></div>`;
    });
    html += '</div>';
    el.innerHTML = html;
}
