/**
 * Finance Dashboard Charts - Real Data Fetching & Rendering
 */

const CURRENCY = window.dashboardCurrency || '₹';
let chartTooltip = null;

function initCharts() {
    if (!chartTooltip) {
        chartTooltip = document.createElement('div');
        chartTooltip.className = 'action-tooltip';
        document.body.appendChild(chartTooltip);
    }
    loadAllCharts();
}

function loadAllCharts() {
    Promise.all([
        fetchChartData('quotations'),
        fetchChartData('purchase_orders'),
        fetchChartData('invoices'),
        fetchChartData('outstanding'),
        fetchChartData('aging'),
        fetchChartData('payments')
    ]).then(([q, po, inv, out, age, pay]) => {
        renderQuotationsChart(q);
        renderPurchaseOrdersChart(po);
        renderInvoicesChart(inv);
        renderOutstandingChart(out);
        renderAgingChart(age);
        renderPaymentsChart(pay);
    }).catch(e => {
        console.warn('Chart load failed:', e);
        loadDemoCharts();
    });
}

async function fetchChartData(chart) {
    try {
        const response = await fetch(`/ergon/src/api/charts.php?chart=${chart}`, {
            signal: AbortSignal.timeout(5000)
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const res = await response.json();
        if (!res.success) throw new Error(res.error || 'API error');
        return res.data || null;
    } catch (e) {
        console.warn(`Failed to fetch ${chart}:`, e);
        return null;
    }
}

function createArc(start, end, r) {
    const s = (start * Math.PI) / 180, e = (end * Math.PI) / 180;
    const x1 = 100 + r * Math.cos(s), y1 = 60 + r * Math.sin(s);
    const x2 = 100 + r * Math.cos(e), y2 = 60 + r * Math.sin(e);
    const large = end - start > 180 ? 1 : 0;
    return `M ${x1} ${y1} A ${r} ${r} 0 ${large} 1 ${x2} ${y2}`;
}

function showChartTooltip(element, text) {
    if (!chartTooltip) return;
    chartTooltip.textContent = text;
    chartTooltip.style.display = 'block';
    chartTooltip.style.opacity = '0';
    
    const rect = element.getBoundingClientRect();
    const tooltipRect = chartTooltip.getBoundingClientRect();
    let left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);
    let top = rect.top - tooltipRect.height - 8;
    
    if (left < 5) left = 5;
    if (left + tooltipRect.width > window.innerWidth - 5) left = window.innerWidth - tooltipRect.width - 5;
    if (top < 5) top = rect.bottom + 8;
    
    chartTooltip.style.left = left + 'px';
    chartTooltip.style.top = top + 'px';
    chartTooltip.style.opacity = '1';
}

function hideChartTooltip() {
    if (!chartTooltip) return;
    chartTooltip.style.opacity = '0';
    setTimeout(() => { if (chartTooltip) chartTooltip.style.display = 'none'; }, 150);
}

function addInteractive(el, text) {
    el.addEventListener('mouseenter', () => { showChartTooltip(el, text); el.setAttribute('data-hover', 'true'); });
    el.addEventListener('mouseleave', () => { hideChartTooltip(); el.removeAttribute('data-hover'); });
    el.addEventListener('mousemove', (e) => {
        if (chartTooltip && chartTooltip.style.display === 'block') {
            const tooltipRect = chartTooltip.getBoundingClientRect();
            let left = e.clientX - (tooltipRect.width / 2);
            let top = e.clientY - tooltipRect.height - 8;
            if (left < 5) left = 5;
            if (left + tooltipRect.width > window.innerWidth - 5) left = window.innerWidth - tooltipRect.width - 5;
            if (top < 5) top = e.clientY + 8;
            chartTooltip.style.left = left + 'px';
            chartTooltip.style.top = top + 'px';
        }
    });
}

function renderQuotationsChart(data) {
    if (!data) data = { pending: 0, placed: 0, rejected: 0, total: 0 };
    const svg = document.getElementById('quotationsChart');
    if (!svg) return;
    
    const total = (data.pending || 0) + (data.placed || 0) + (data.rejected || 0) || 1;
    const p1 = ((data.pending || 0) / total) * 360;
    const p2 = ((data.placed || 0) / total) * 360;
    
    svg.innerHTML = `
        <defs><style>.seg { cursor: pointer; } .seg[data-hover="true"] { stroke-width: 10 !important; }</style></defs>
        <path class="seg" d="${createArc(-90, -90 + p1, 35)}" stroke="#F59E0B" stroke-width="8" fill="none" stroke-linecap="round" data-tip="Pending: ${data.pending || 0}"/>
        <path class="seg" d="${createArc(-90 + p1, -90 + p1 + p2, 35)}" stroke="#10B981" stroke-width="8" fill="none" stroke-linecap="round" data-tip="Placed: ${data.placed || 0}"/>
        <path class="seg" d="${createArc(-90 + p1 + p2, 270, 35)}" stroke="#EF4444" stroke-width="8" fill="none" stroke-linecap="round" data-tip="Rejected: ${data.rejected || 0}"/>
        <circle cx="100" cy="60" r="18" fill="var(--bg-primary)"/><text x="100" y="62" text-anchor="middle" font-size="14" font-weight="bold" fill="#10B981">${data.placed || 0}</text>
    `;
    svg.querySelectorAll('.seg').forEach(el => addInteractive(el, el.dataset.tip));
    
    const el = document.getElementById('quotationsTotal');
    if (el) el.textContent = CURRENCY + (data.total || 0).toLocaleString();
    const el2 = document.getElementById('quotationsPending');
    if (el2) el2.textContent = data.pending || 0;
    const el3 = document.getElementById('quotationsPlaced');
    if (el3) el3.textContent = data.placed || 0;
    const el4 = document.getElementById('quotationsRejected');
    if (el4) el4.textContent = data.rejected || 0;
}

function renderPurchaseOrdersChart(data) {
    if (!data || !Array.isArray(data)) data = [0];
    const svg = document.getElementById('purchaseOrdersChart');
    if (!svg) return;
    
    data = data.map(v => Number(v) || 0);
    const max = Math.max(...data, 1);
    const pts = data.map((v, i) => `${15 + i * 45},${95 - (v / max) * 60}`).join(' ');
    
    svg.innerHTML = `
        <defs><style>.dot { cursor: pointer; } .dot[data-hover="true"] { r: 5 !important; }</style></defs>
        <path d="M ${pts} L ${15 + (data.length - 1) * 45},110 L 15,110 Z" fill="#10B981" opacity="0.1"/>
        <polyline points="${pts}" fill="none" stroke="#10B981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        ${data.map((v, i) => `<circle class="dot" cx="${15 + i * 45}" cy="${95 - (v / max) * 60}" r="3" fill="#10B981" opacity="0.8" data-tip="${CURRENCY}${(v / 1000).toFixed(0)}K"/>`).join('')}
    `;
    svg.querySelectorAll('.dot').forEach(el => addInteractive(el, el.dataset.tip));
    
    const total = data.reduce((a, b) => a + b, 0);
    const el = document.getElementById('poTotal');
    if (el) el.textContent = CURRENCY + total.toLocaleString();
}

function renderInvoicesChart(data) {
    if (!data) data = { paid: 0, unpaid: 0, overdue: 0, total: 0 };
    const svg = document.getElementById('invoicesChart');
    if (!svg) return;
    
    const total = (data.paid || 0) + (data.unpaid || 0) + (data.overdue || 0) || 1;
    const p1 = ((data.paid || 0) / total) * 360;
    const p2 = ((data.unpaid || 0) / total) * 360;
    
    svg.innerHTML = `
        <defs><style>.seg { cursor: pointer; } .seg[data-hover="true"] { stroke-width: 10 !important; }</style></defs>
        <path class="seg" d="${createArc(-90, -90 + p1, 35)}" stroke="#10B981" stroke-width="8" fill="none" stroke-linecap="round" data-tip="Paid: ${data.paid || 0}"/>
        <path class="seg" d="${createArc(-90 + p1, -90 + p1 + p2, 35)}" stroke="#F59E0B" stroke-width="8" fill="none" stroke-linecap="round" data-tip="Unpaid: ${data.unpaid || 0}"/>
        <path class="seg" d="${createArc(-90 + p1 + p2, 270, 35)}" stroke="#EF4444" stroke-width="8" fill="none" stroke-linecap="round" data-tip="Overdue: ${data.overdue || 0}"/>
        <circle cx="100" cy="60" r="18" fill="var(--bg-primary)"/><text x="100" y="62" text-anchor="middle" font-size="14" font-weight="bold" fill="#10B981">${data.unpaid || 0}</text>
    `;
    svg.querySelectorAll('.seg').forEach(el => addInteractive(el, el.dataset.tip));
    
    const el = document.getElementById('invoicesTotal');
    if (el) el.textContent = CURRENCY + (data.total || 0).toLocaleString();
    const el2 = document.getElementById('invoicesPaid');
    if (el2) el2.textContent = data.paid || 0;
    const el3 = document.getElementById('invoicesUnpaid');
    if (el3) el3.textContent = data.unpaid || 0;
    const el4 = document.getElementById('invoicesOverdue');
    if (el4) el4.textContent = data.overdue || 0;
}

function renderOutstandingChart(data) {
    if (!data || !Array.isArray(data)) data = [];
    const svg = document.getElementById('outstandingByCustomerChart');
    if (!svg) return;
    
    const values = data.map(d => Number(d.amount) || 0);
    const total = values.reduce((a, b) => a + b, 1);
    const colors = ['#EF4444', '#F59E0B', '#0EA5E9', '#10B981', '#8B5CF6'];
    
    let angle = -90, paths = '';
    data.forEach((d, i) => {
        const a = ((d.amount || 0) / total) * 360;
        paths += `<path class="seg" d="${createArc(angle, angle + a, 32)}" stroke="${colors[i % colors.length]}" stroke-width="7" fill="none" stroke-linecap="round" data-tip="${d.customer}: ${CURRENCY}${(d.amount / 1000).toFixed(0)}K"/>`;
        angle += a;
    });
    
    svg.innerHTML = `<defs><style>.seg { cursor: pointer; } .seg[data-hover="true"] { stroke-width: 9 !important; }</style></defs>${paths}<circle cx="100" cy="60" r="18" fill="var(--bg-primary)"/><text x="100" y="62" text-anchor="middle" font-size="12" font-weight="bold" fill="#EF4444">${data.length}</text>`;
    svg.querySelectorAll('.seg').forEach(el => addInteractive(el, el.dataset.tip));
    
    const total_amount = values.reduce((a, b) => a + b, 0);
    const el = document.getElementById('outstandingTotal');
    if (el) el.textContent = CURRENCY + total_amount.toLocaleString();
    const el2 = document.getElementById('outstandingCustomers');
    if (el2) el2.textContent = data.length;
}

function renderAgingChart(data) {
    if (!data) data = { current: 0, watch: 0, concern: 0, critical: 0 };
    const svg = document.getElementById('agingBucketsChart');
    if (!svg) return;
    
    const total = (data.current || 0) + (data.watch || 0) + (data.concern || 0) + (data.critical || 0) || 1;
    const p1 = ((data.current || 0) / total) * 360;
    const p2 = ((data.watch || 0) / total) * 360;
    const p3 = ((data.concern || 0) / total) * 360;
    
    svg.innerHTML = `
        <defs><style>.seg { cursor: pointer; } .seg[data-hover="true"] { stroke-width: 9 !important; }</style></defs>
        <path class="seg" d="${createArc(-90, -90 + p1, 33)}" stroke="#10B981" stroke-width="7" fill="none" stroke-linecap="round" data-tip="0-30 Days: ${CURRENCY}${(data.current / 1000).toFixed(0)}K"/>
        <path class="seg" d="${createArc(-90 + p1, -90 + p1 + p2, 33)}" stroke="#F59E0B" stroke-width="7" fill="none" stroke-linecap="round" data-tip="31-60 Days: ${CURRENCY}${(data.watch / 1000).toFixed(0)}K"/>
        <path class="seg" d="${createArc(-90 + p1 + p2, -90 + p1 + p2 + p3, 33)}" stroke="#0EA5E9" stroke-width="7" fill="none" stroke-linecap="round" data-tip="61-90 Days: ${CURRENCY}${(data.concern / 1000).toFixed(0)}K"/>
        <path class="seg" d="${createArc(-90 + p1 + p2 + p3, 270, 33)}" stroke="#EF4444" stroke-width="7" fill="none" stroke-linecap="round" data-tip="90+ Days: ${CURRENCY}${(data.critical / 1000).toFixed(0)}K"/>
        <circle cx="100" cy="60" r="18" fill="var(--bg-primary)"/><text x="100" y="62" text-anchor="middle" font-size="12" font-weight="bold" fill="#10B981">${CURRENCY}${(data.current / 1000).toFixed(0)}K</text>
    `;
    svg.querySelectorAll('.seg').forEach(el => addInteractive(el, el.dataset.tip));
    
    const total_amount = (data.current || 0) + (data.watch || 0) + (data.concern || 0) + (data.critical || 0);
    const el = document.getElementById('agingTotal');
    if (el) el.textContent = CURRENCY + total_amount.toLocaleString();
    const el2 = document.getElementById('aging0to30');
    if (el2) el2.textContent = CURRENCY + (data.current || 0).toLocaleString();
    const el3 = document.getElementById('aging31to60');
    if (el3) el3.textContent = CURRENCY + (data.watch || 0).toLocaleString();
    const el4 = document.getElementById('aging61to90');
    if (el4) el4.textContent = CURRENCY + (data.concern || 0).toLocaleString();
    const el5 = document.getElementById('aging90plus');
    if (el5) el5.textContent = CURRENCY + (data.critical || 0).toLocaleString();
}

function renderPaymentsChart(data) {
    if (!data || !Array.isArray(data)) data = [0];
    const svg = document.getElementById('paymentsChart');
    if (!svg) return;
    
    data = data.map(v => Number(v) || 0);
    const max = Math.max(...data, 1);
    const days = ['M', 'T', 'W', 'T', 'F', 'S', 'S'];
    
    svg.innerHTML = `
        <defs><style>.bar { cursor: pointer; } .bar[data-hover="true"] { opacity: 1 !important; filter: brightness(1.2); }</style></defs>
        ${data.map((v, i) => { const h = (v / max) * 55; return `<rect class="bar" x="${18 + i * 25}" y="${95 - h}" width="16" height="${h}" fill="#4F46E5" opacity="0.85" rx="2" data-tip="${CURRENCY}${(v / 1000).toFixed(0)}K (${days[i]})"/>`; }).join('')}
        <line x1="10" y1="95" x2="190" y2="95" stroke="#E5E7EB" stroke-width="1"/>
    `;
    svg.querySelectorAll('.bar').forEach(el => addInteractive(el, el.dataset.tip));
    
    const total = data.reduce((a, b) => a + b, 0);
    const el = document.getElementById('paymentsTotal');
    if (el) el.textContent = CURRENCY + total.toLocaleString();
}

function loadDemoCharts() {
    renderQuotationsChart({ pending: 15, placed: 42, rejected: 8, total: 65000 });
    renderPurchaseOrdersChart([45000, 52000, 48000, 61000]);
    renderInvoicesChart({ paid: 85, unpaid: 32, overdue: 12, total: 125000 });
    renderOutstandingChart([
        { customer: 'Acme Corp', amount: 35000 },
        { customer: 'Tech Solutions', amount: 28000 },
        { customer: 'Global Industries', amount: 22000 }
    ]);
    renderAgingChart({ current: 45000, watch: 32000, concern: 22000, critical: 16000 });
    renderPaymentsChart([12000, 18000, 15000, 22000, 19000, 14000, 11000]);
}

document.addEventListener('DOMContentLoaded', initCharts);

window.refreshCharts = loadAllCharts;
