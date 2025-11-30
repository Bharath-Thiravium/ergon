// Chart Registry - Store all chart instances
const chartRegistry = {};

// Safe Chart Render Function
function safeRenderChart(canvasId, chartType, labels, data, colors, options = {}) {
    if (typeof Chart === 'undefined') {
        console.error('Chart render error: Chart is not defined — ensure Chart.js is loaded before this script.');
        return null;
    }

    const canvas = document.getElementById(canvasId);
    if (!canvas) {
        console.warn(`Canvas not found: ${canvasId}`);
        return null;
    }

    // Destroy existing chart
    if (chartRegistry[canvasId]) {
        try { chartRegistry[canvasId].destroy(); } catch (e) { /* ignore */ }
    }

    try {
        const ctx = canvas.getContext('2d');
        const defaultOptions = {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 250 },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const v = context.raw || 0;
                            return typeof v === 'number' ? '₹' + Number(v).toLocaleString() : String(v);
                        }
                    }
                }
            }
        };

        const chartConfig = {
            type: chartType,
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderColor: colors.map(c => c.replace('0.1', '0.8')),
                    borderWidth: 1
                }]
            },
            options: { ...defaultOptions, ...options }
        };

        const chart = new Chart(ctx, chartConfig);
        chartRegistry[canvasId] = chart;
        return chart;
    } catch (error) {
        console.error(`Chart render error for ${canvasId}:`, error);
        return null;
    }
}

// Load Analytics Data
async function loadAnalytics() {
    try {
        const prefix = document.getElementById('companyPrefix')?.value || '';
        if (!prefix) return {};

        const endpoints = {
            quotations: `/ergon/src/api/analytics.php?type=quotations&prefix=${encodeURIComponent(prefix)}`,
            po_claims: `/ergon/src/api/analytics.php?type=po_claims&prefix=${encodeURIComponent(prefix)}`,
            invoices: `/ergon/src/api/analytics.php?type=invoices&prefix=${encodeURIComponent(prefix)}`,
            customer_outstanding: `/ergon/src/api/analytics.php?type=customer_outstanding&prefix=${encodeURIComponent(prefix)}`,
            aging_buckets: `/ergon/src/api/analytics.php?type=aging_buckets&prefix=${encodeURIComponent(prefix)}`,
            payments: `/ergon/src/api/analytics.php?type=payments&prefix=${encodeURIComponent(prefix)}`
        };

        const results = {};
        for (const [key, url] of Object.entries(endpoints)) {
            try {
                const response = await fetch(url, { signal: AbortSignal.timeout(5000) });
                if (response.ok) {
                    const data = await response.json();
                    results[key] = data.success ? data.data : {};
                }
            } catch (e) {
                console.warn(`Analytics fetch failed for ${key}:`, e.message);
                results[key] = {};
            }
        }
        return results;
    } catch (error) {
        console.error('Analytics load error:', error);
        return {};
    }
}

// Render All Charts
async function renderAllCharts() {
    const analytics = await loadAnalytics();

    // 1. Quotations Chart
    if (analytics.quotations) {
        const q = analytics.quotations;
        safeRenderChart('quotationsChart', 'doughnut',
            ['Pending', 'Placed', 'Rejected'],
            [Number(q.pending) || 0, Number(q.placed) || 0, Number(q.rejected) || 0],
            ['#3b82f6', '#10b981', '#ef4444'],
            { plugins: { legend: { display: false } }, cutout: '70%' }
        );
        updateMetrics('quotations', q);
    }

    // 2. Purchase Orders Chart
    if (analytics.po_claims) {
        const po = analytics.po_claims;
        safeRenderChart('purchaseOrdersChart', 'bar',
            ['Fulfillment'],
            [Number(po.fulfillment_rate) || 0],
            ['#059669'],
            { indexAxis: 'y', scales: { x: { max: 100 } } }
        );
        updateMetrics('po', po);
    }

    // 3. Invoices Chart
    if (analytics.invoices) {
        const inv = analytics.invoices;
        const paid = Number(inv.collected_amount) || 0;
        const unpaid = (Number(inv.pending_invoice_value) || 0) * 0.7;
        const overdue = (Number(inv.pending_invoice_value) || 0) * 0.3;
        
        safeRenderChart('invoicesChart', 'doughnut',
            ['Paid', 'Unpaid', 'Overdue'],
            [paid, unpaid, overdue],
            ['#10b981', '#f59e0b', '#ef4444'],
            { plugins: { legend: { display: false } }, cutout: '70%' }
        );
        updateMetrics('invoices', inv);
    }

    // 4. Outstanding by Customer Chart
    if (analytics.customer_outstanding && Array.isArray(analytics.customer_outstanding)) {
        const customers = analytics.customer_outstanding.slice(0, 5);
        const labels = customers.map(c => c.customer_name || 'Unknown');
        const amounts = customers.map(c => Number(c.outstanding_amount) || 0);
        
        safeRenderChart('outstandingByCustomerChart', 'doughnut',
            labels,
            amounts,
            ['#ef4444', '#f97316', '#eab308', '#84cc16', '#22c55e'],
            { plugins: { legend: { display: false } }, cutout: '60%' }
        );
        updateMetrics('outstanding', { customers: customers.length, total: amounts.reduce((a, b) => a + b, 0) });
    }

    // 5. Aging Buckets Chart
    if (analytics.aging_buckets) {
        const aging = analytics.aging_buckets;
        const b0 = Number(aging.current) || 0;
        const b1 = Number(aging.watch) || 0;
        const b2 = Number(aging.concern) || 0;
        const b3 = Number(aging.critical) || 0;
        
        safeRenderChart('agingBucketsChart', 'doughnut',
            ['0-30', '31-60', '61-90', '90+'],
            [b0, b1, b2, b3],
            ['#10b981', '#f59e0b', '#fb923c', '#ef4444'],
            { plugins: { legend: { display: false } }, cutout: '70%' }
        );
        updateMetrics('aging', aging);
    }

    // 6. Payments Chart
    if (analytics.payments) {
        const payments = analytics.payments;
        safeRenderChart('paymentsChart', 'bar',
            ['Payments'],
            [Number(payments.total) || 0],
            ['#3b82f6']
        );
        updateMetrics('payments', payments);
    }
}

// Update Chart Metrics
function updateMetrics(chartType, data) {
    const updates = {
        quotations: {
            'placedQuotations': data.placed,
            'rejectedQuotations': data.rejected,
            'pendingQuotations': data.pending,
            'quotationsTotal': (Number(data.placed) || 0) + (Number(data.rejected) || 0) + (Number(data.pending) || 0)
        },
        po: {
            'poFulfillmentRate': `${data.fulfillment_rate || 0}%`,
            'poTotal': data.po_count || 0
        },
        invoices: {
            'dsoMetric': `${data.dso || 0} days`,
            'invoicesTotal': `₹${(Number(data.total_invoice_value) || 0).toLocaleString()}`
        },
        outstanding: {
            'customerDiversity': data.customers || 0,
            'outstandingTotal': `₹${(Number(data.total) || 0).toLocaleString()}`
        },
        aging: {
            'agingTotal': `₹${((Number(data.current) || 0) + (Number(data.watch) || 0) + (Number(data.concern) || 0) + (Number(data.critical) || 0)).toLocaleString()}`
        },
        payments: {
            'paymentsTotal': `₹${(Number(data.total) || 0).toLocaleString()}`
        }
    };

    if (updates[chartType]) {
        Object.entries(updates[chartType]).forEach(([id, value]) => {
            const el = document.getElementById(id);
            if (el) el.textContent = value;
        });
    }
}

// Initialize Charts on DOM Ready
window.addEventListener('load', function() {
    if (typeof Chart === 'undefined') {
        console.error('Chart.js library not loaded. Charts will not render.');
        return;
    }

    const requiredCanvases = [
        'quotationsChart',
        'purchaseOrdersChart',
        'invoicesChart',
        'outstandingByCustomerChart',
        'agingBucketsChart',
        'paymentsChart'
    ];

    const allCanvasesExist = requiredCanvases.every(id => document.getElementById(id));
    
    if (allCanvasesExist) {
        renderAllCharts();
    } else {
        console.warn('Some chart canvases not found. Skipping chart initialization.');
    }
});

// Re-render charts when prefix changes
document.addEventListener('change', function(e) {
    if (e.target.id === 'companyPrefix') {
        setTimeout(() => renderAllCharts(), 300);
    }
});
