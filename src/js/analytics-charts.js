let quotationsChart, purchaseOrdersChart, invoicesChart, paymentsChart;
let outstandingByCustomerChart, agingBucketsChart;

function initCharts() {
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js not loaded');
        return;
    }

    const chartDefaults = {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 250 },
        plugins: { legend: { display: false } }
    };

    const quotationsCtx = document.getElementById('quotationsChart');
    if (quotationsCtx) {
        quotationsChart = new Chart(quotationsCtx, {
            type: 'pie',
            data: { labels: ['Pending','Placed','Rejected'], datasets: [{ data: [0,0,0], backgroundColor: ['#3b82f6','#10b981','#ef4444'] }] },
            options: chartDefaults
        });
    }

    const invoicesCtx = document.getElementById('invoicesChart');
    if (invoicesCtx) {
        invoicesChart = new Chart(invoicesCtx, {
            type: 'doughnut',
            data: { labels: ['Paid','Unpaid','Overdue'], datasets: [{ data: [0,0,0], backgroundColor: ['#10b981','#f59e0b','#ef4444'] }] },
            options: { ...chartDefaults, cutout: '70%' }
        });
    }

    const outstandingCtx = document.getElementById('outstandingByCustomerChart');
    if (outstandingCtx) {
        outstandingByCustomerChart = new Chart(outstandingCtx, {
            type: 'doughnut',
            data: { labels: [], datasets: [{ data: [], backgroundColor: ['#ef4444', '#f97316', '#eab308', '#84cc16', '#22c55e', '#06b6d4', '#3b82f6', '#8b5cf6', '#ec4899', '#f43f5e'] }] },
            options: { ...chartDefaults, cutout: '60%' }
        });
    }

    const agingCtx = document.getElementById('agingBucketsChart');
    if (agingCtx) {
        agingBucketsChart = new Chart(agingCtx, {
            type: 'doughnut',
            data: { labels: ['0-30 Days','31-60 Days','61-90 Days','90+ Days'], datasets: [{ data: [0,0,0,0], backgroundColor: ['#10b981','#f59e0b','#fb923c','#ef4444'] }] },
            options: { ...chartDefaults, cutout: '70%' }
        });
    }
}

function updateQuotationsChart(data) {
    if (!quotationsChart || !data) return;
    quotationsChart.data.datasets[0].data = [data.pending || 0, data.placed || 0, data.rejected || 0];
    quotationsChart.update();
}

function updateInvoicesChart(data) {
    if (!invoicesChart || !data) return;
    invoicesChart.data.datasets[0].data = [data.paid_count || 0, data.unpaid_count || 0, data.overdue_count || 0];
    invoicesChart.update();
}

function updateOutstandingChart(customers) {
    if (!outstandingByCustomerChart || !customers || customers.length === 0) return;
    const labels = customers.map(c => c.customer_name || 'Unknown');
    const values = customers.map(c => parseFloat(c.outstanding_amount) || 0);
    outstandingByCustomerChart.data.labels = labels;
    outstandingByCustomerChart.data.datasets[0].data = values;
    outstandingByCustomerChart.update();
}

function updateAgingChart(data) {
    if (!agingBucketsChart || !data) return;
    agingBucketsChart.data.datasets[0].data = [data.bucket_0_30 || 0, data.bucket_31_60 || 0, data.bucket_61_90 || 0, data.bucket_90_plus || 0];
    agingBucketsChart.update();
}
