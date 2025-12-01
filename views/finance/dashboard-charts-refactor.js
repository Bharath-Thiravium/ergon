function getApiUrl(endpoint, prefix) {
    const url = new URL(endpoint, window.location.origin);
    if (prefix) url.searchParams.set('prefix', prefix);
    return url.toString();
}

const CANVAS_IDS = {
    quotations: 'quotationsChart',
    purchaseOrders: 'purchaseOrdersChart',
    invoices: 'invoicesChart',
    outstandingByCustomer: 'outstandingByCustomerChart',
    agingBuckets: 'agingBucketsChart',
    payments: 'paymentsChart'
};

const CHARTS = {
    quotations: null,
    purchaseOrders: null,
    invoices: null,
    outstandingByCustomer: null,
    agingBuckets: null,
    payments: null
};

function safeNum(v) {
    if (v === null || v === undefined || v === '') return 0;
    if (typeof v === 'number') return v;
    const n = Number(String(v).replace(/[^0-9.\-]/g, ''));
    return Number.isFinite(n) ? n : 0;
}

async function apiGet(url) {
    try {
        const res = await fetch(url, { cache: 'no-store' });
        if (!res.ok) {
            console.error(`API HTTP ${res.status}: ${url}`);
            return null;
        }
        const json = await res.json();
        if (!json || json.success === false) {
            console.warn('API returned failure:', url, json);
            return null;
        }
        return json.data ?? null;
    } catch (err) {
        console.error('API fetch error:', url, err);
        return null;
    }
}

function canvasExist(id) {
    const el = document.getElementById(id);
    return el instanceof HTMLCanvasElement ? el : null;
}

function destroyChart(key) {
    const inst = CHARTS[key];
    if (inst && typeof inst.destroy === 'function') {
        try {
            inst.destroy();
        } catch (e) {
            console.warn('Chart destroy error for', key, e);
        }
    }
    CHARTS[key] = null;
}

function ensureChartJsLoaded() {
    if (typeof Chart === 'undefined') {
        console.error('Chart.js not loaded');
        return false;
    }
    return true;
}

async function renderQuotations(prefix) {
    const canvas = canvasExist(CANVAS_IDS.quotations);
    if (!canvas) {
        console.warn('Canvas not found:', CANVAS_IDS.quotations);
        return;
    }
    const url = getApiUrl('/ergon/src/api/dashboard/quotations.php', prefix);
    const data = await apiGet(url) || {};
    const pending = safeNum(data.pending_count);
    const placed = safeNum(data.placed_count);
    const rejected = safeNum(data.rejected_count);
    const totalValue = safeNum(data.total_value);
    
    const centerEl = document.getElementById('quotationsTotal');
    if (centerEl) centerEl.textContent = '₹' + totalValue.toLocaleString();
    
    document.getElementById('quotationsPending').textContent = pending;
    document.getElementById('quotationsPlaced').textContent = placed;
    document.getElementById('quotationsRejected').textContent = rejected;
    
    if (!ensureChartJsLoaded()) return;
    destroyChart('quotations');
    CHARTS.quotations = new Chart(canvas.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Placed', 'Rejected'],
            datasets: [{
                data: [pending, placed, rejected],
                backgroundColor: ['#F59E0B', '#10B981', '#EF4444']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            plugins: { legend: { position: 'bottom' } }
        }
    });
}

async function renderPurchaseOrders(prefix) {
    const canvas = canvasExist(CANVAS_IDS.purchaseOrders);
    if (!canvas) {
        console.warn('Canvas not found:', CANVAS_IDS.purchaseOrders);
        return;
    }
    const url = getApiUrl('/ergon/src/api/dashboard/purchase-orders.php', prefix);
    const data = await apiGet(url) || {};
    const open = safeNum(data.open_count);
    const fulfilled = safeNum(data.fulfilled);
    const totalVal = safeNum(data.total_value);
    const rate = safeNum(data.rate);
    
    const elTotal = document.getElementById('poTotal');
    if (elTotal) elTotal.textContent = '₹' + totalVal.toLocaleString();
    
    document.getElementById('poOpen').textContent = open;
    document.getElementById('poFulfilled').textContent = fulfilled;
    document.getElementById('poFulfillmentRate').textContent = rate + '%';
    
    if (!ensureChartJsLoaded()) return;
    destroyChart('purchaseOrders');
    CHARTS.purchaseOrders = new Chart(canvas.getContext('2d'), {
        type: 'bar',
        data: {
            labels: ['Open', 'Fulfilled'],
            datasets: [{
                data: [open, fulfilled],
                backgroundColor: ['#0EA5E9', '#10B981']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        }
    });
}

async function renderInvoices(prefix) {
    const canvas = canvasExist(CANVAS_IDS.invoices);
    if (!canvas) {
        console.warn('Canvas not found:', CANVAS_IDS.invoices);
        return;
    }
    const url = getApiUrl('/ergon/src/api/dashboard/invoices.php', prefix);
    const data = await apiGet(url) || {};
    const paid = safeNum(data.paid);
    const unpaid = safeNum(data.unpaid);
    const overdue = safeNum(data.overdue);
    const totalVal = safeNum(data.total_value);
    
    const elTotal = document.getElementById('invoicesTotal');
    if (elTotal) elTotal.textContent = '₹' + totalVal.toLocaleString();
    
    document.getElementById('invoicesPaid').textContent = paid;
    document.getElementById('invoicesUnpaid').textContent = unpaid;
    document.getElementById('invoicesOverdue').textContent = overdue;
    
    if (!ensureChartJsLoaded()) return;
    destroyChart('invoices');
    CHARTS.invoices = new Chart(canvas.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Paid', 'Unpaid', 'Overdue'],
            datasets: [{
                data: [paid, unpaid, overdue],
                backgroundColor: ['#10B981', '#F59E0B', '#EF4444']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '58%',
            plugins: { legend: { position: 'bottom' } }
        }
    });
}

async function renderOutstandingByCustomer(prefix) {
    const canvas = canvasExist(CANVAS_IDS.outstandingByCustomer);
    if (!canvas) {
        console.warn('Canvas not found:', CANVAS_IDS.outstandingByCustomer);
        return;
    }
    const url = getApiUrl('/ergon/src/api/dashboard/outstanding-by-customer.php', prefix);
    const rows = await apiGet(url) || [];
    const labels = rows.map(r => String(r.customer_name || 'Unknown'));
    const values = rows.map(r => safeNum(r.outstanding));
    const total = values.reduce((s, v) => s + v, 0);
    
    const totalEl = document.getElementById('outstandingTotal');
    if (totalEl) totalEl.textContent = '₹' + total.toLocaleString();
    
    document.getElementById('outstandingCustomers').textContent = rows.length;
    
    if (!ensureChartJsLoaded()) return;
    destroyChart('outstandingByCustomer');
    CHARTS.outstandingByCustomer = new Chart(canvas.getContext('2d'), {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: '#EF4444'
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        }
    });
}

async function renderAgingBuckets(prefix) {
    const canvas = canvasExist(CANVAS_IDS.agingBuckets);
    if (!canvas) {
        console.warn('Canvas not found:', CANVAS_IDS.agingBuckets);
        return;
    }
    const url = getApiUrl('/ergon/src/api/dashboard/aging-buckets.php', prefix);
    const data = await apiGet(url) || {};
    const b0 = safeNum(data.bucket_0_30);
    const b31 = safeNum(data.bucket_31_60);
    const b61 = safeNum(data.bucket_61_90);
    const b90 = safeNum(data.bucket_90_plus);
    
    const elTotal = document.getElementById('agingTotal');
    if (elTotal) elTotal.textContent = '₹' + (b0 + b31 + b61 + b90).toLocaleString();
    
    document.getElementById('aging0to30').textContent = b0.toLocaleString();
    document.getElementById('aging31to60').textContent = b31.toLocaleString();
    document.getElementById('aging90plus').textContent = b90.toLocaleString();
    
    if (!ensureChartJsLoaded()) return;
    destroyChart('agingBuckets');
    CHARTS.agingBuckets = new Chart(canvas.getContext('2d'), {
        type: 'bar',
        data: {
            labels: ['0-30', '31-60', '61-90', '90+'],
            datasets: [{
                data: [b0, b31, b61, b90],
                backgroundColor: ['#10B981', '#F59E0B', '#F97316', '#EF4444']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        }
    });
}

async function renderPayments(prefix) {
    const canvas = canvasExist(CANVAS_IDS.payments);
    if (!canvas) {
        console.warn('Canvas not found:', CANVAS_IDS.payments);
        return;
    }
    const url = getApiUrl('/ergon/src/api/dashboard/payments.php', prefix);
    const data = await apiGet(url) || {};
    const total = safeNum(data.total_paid ?? data.total_amount ?? 0);
    const count = safeNum(data.payment_count);
    const avg = safeNum(data.avg_payment);
    
    const elTotal = document.getElementById('paymentsTotal');
    if (elTotal) elTotal.textContent = '₹' + total.toLocaleString();
    
    document.getElementById('paymentCount').textContent = count;
    document.getElementById('paymentAvg').textContent = '₹' + avg.toLocaleString();
    document.getElementById('paymentVelocity').textContent = count;
    
    if (!ensureChartJsLoaded()) return;
    destroyChart('payments');
    CHARTS.payments = new Chart(canvas.getContext('2d'), {
        type: 'line',
        data: {
            labels: ['Payments'],
            datasets: [{
                data: [total],
                borderColor: '#4F46E5',
                backgroundColor: 'rgba(79,70,229,0.1)',
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        }
    });
}

async function renderAllCharts(prefix) {
    if (!prefix) {
        console.warn('No prefix provided to renderAllCharts');
        return;
    }
    await Promise.all([
        renderQuotations(prefix),
        renderPurchaseOrders(prefix),
        renderInvoices(prefix),
        renderOutstandingByCustomer(prefix),
        renderAgingBuckets(prefix),
        renderPayments(prefix)
    ]);
}

window.addEventListener('load', () => {
    if (typeof Chart === 'undefined') {
        console.error('Chart.js not loaded');
        return;
    }
    const prefix = document.getElementById('companyPrefix')?.value;
    if (prefix) {
        renderAllCharts(prefix).catch(e => console.error('renderAllCharts error', e));
    }
});

window._dashboardCharts = { renderAllCharts, CHARTS };
