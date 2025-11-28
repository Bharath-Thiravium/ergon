<?php 
 $title = 'Finance Dashboard';
 $active_page = 'finance';
 ob_start(); 
 // Finance-specific styles are merged into `assets/css/ergon-overrides.css`
?>

<div class="container-fluid">
    <!-- Header Actions -->
    <div class="dashboard-header">
        <div class="dashboard-header__title">
            <h1>Finance Dashboard</h1>
            <p>Real-time financial insights and analytics</p>
        </div>
        <div class="dashboard-header__actions">
            <div class="action-group">
                <button id="syncBtn" class="btn btn--primary btn--sm">
                    <span class="btn__icon">🔄</span>
                    <span class="btn__text">Sync Data</span>
                </button>
                <button id="exportBtn" class="btn btn--secondary btn--sm">
                    <span class="btn__icon">📥</span>
                    <span class="btn__text">Export</span>
                </button>
                <button onclick="window.open('/ergon/finance/download-database', '_blank')" class="btn btn--info btn--sm">
                    <span class="btn__icon">💾</span>
                    <span class="btn__text">Download DB</span>
                </button>
                <button onclick="window.open('/ergon/finance/download-pg-tables', '_blank')" class="btn btn--warning btn--sm">
                    <span class="btn__icon">📥</span>
                    <span class="btn__text">Download PG Tables</span>
                </button>
                <button onclick="refreshDashboardStats()" class="btn btn--success btn--sm">
                    <span class="btn__icon">🔄</span>
                    <span class="btn__text">Refresh Stats</span>
                </button>
                <a href="/ergon/finance/import" class="btn btn--success btn--sm">
                    <span class="btn__icon">📥</span>
                    <span class="btn__text">Import Data</span>
                </a>
            </div>
            <div class="filter-group">
                <div class="input-group">
                    <input type="text" id="companyPrefix" class="form-control form-control--sm" placeholder="Company Prefix (e.g. BKC)" maxlength="10">
                    <button id="updatePrefixBtn" class="btn btn--secondary btn--sm">
                        <span class="btn__icon">🏢</span>
                    </button>
                </div>
                <select id="dateFilter" class="form-control form-control--sm">
                    <option value="all">All Time</option>
                    <option value="30">Last 30 Days</option>
                    <option value="90">Last 90 Days</option>
                    <option value="365">Last Year</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Top-Level KPI Cards -->
    <div class="dashboard-grid">
        <div class="kpi-card kpi-card--success">
            <div class="kpi-card__header">
                <div class="kpi-card__icon">💰</div>
                <div class="kpi-card__trend" id="invoiceTrend">↗ +0%</div>
            </div>
            <div class="kpi-card__value" id="totalInvoiceAmount">₹0</div>
            <div class="kpi-card__label">Total Invoice Amount</div>
            <div class="kpi-card__description">Total Revenue Generated</div>
            <div class="kpi-card__details">
                <div class="detail-item">Count: <span id="totalInvoiceCount">0</span></div>
                <div class="detail-item">Avg: <span id="avgInvoiceAmount">₹0</span></div>
            </div>
        </div>
        
        <div class="kpi-card kpi-card--success">
            <div class="kpi-card__header">
                <div class="kpi-card__icon">✅</div>
                <div class="kpi-card__trend" id="receivedTrend">↗ +0%</div>
            </div>
            <div class="kpi-card__value" id="invoiceReceived">₹0</div>
            <div class="kpi-card__label">Amount Received</div>
            <div class="kpi-card__description">Successfully Collected Revenue</div>
            <div class="kpi-card__details">
                <div class="detail-item">Collection Rate: <span id="collectionRateKPI">0%</span></div>
                <div class="detail-item">Paid Invoices: <span id="paidInvoiceCount">0</span></div>
            </div>
        </div>
        
        <div class="kpi-card kpi-card--warning">
            <div class="kpi-card__header">
                <div class="kpi-card__icon">⏳</div>
                <div class="kpi-card__trend" id="pendingTrend">— 0%</div>
            </div>
            <div class="kpi-card__value" id="pendingInvoiceAmount">₹0</div>
            <div class="kpi-card__label">Outstanding Amount</div>
            <div class="kpi-card__description">Taxable Amount Pending (No GST)</div>
            <div class="kpi-card__details">
                <div class="detail-item">Pending Invoices: <span id="pendingInvoicesCount">0</span></div>
                <div class="detail-item">Customers: <span id="customersPendingCount">0</span></div>
                <div class="detail-item">Overdue Amount: <span id="overdueAmount">₹0</span></div>
            </div>
            <!-- Stat Card 3 Implementation:
                 - Outstanding Amount = sum(taxable_amount - amount_paid) where pending > 0
                 - Pending Invoices = count of invoices with pending_amount > 0
                 - Customers = count of unique customer_gstin with pending_amount > 0
                 - Overdue Amount = sum(pending_amount) where due_date < today
                 - All calculations done in backend, frontend reads from dashboard_stats only
            -->
        </div>
        
        <div class="kpi-card kpi-card--info">
            <div class="kpi-card__header">
                <div class="kpi-card__icon">🏛️</div>
                <div class="kpi-card__trend" id="gstTrend">— 0%</div>
            </div>
            <div class="kpi-card__value" id="pendingGSTAmount">₹0</div>
            <div class="kpi-card__label">GST Liability</div>
            <div class="kpi-card__description">Tax Liability on Outstanding Invoices Only</div>
            <div class="kpi-card__details">
                <div class="detail-item">IGST: <span id="igstLiability">₹0</span></div>
                <div class="detail-item">CGST+SGST: <span id="cgstSgstTotal">₹0</span></div>
            </div>
            <!-- Stat Card 4 Implementation:
                 - GST Liability calculated only on outstanding invoices
                 - IGST = sum(igst) where pending_base > 0
                 - CGST+SGST = sum(cgst + sgst) where pending_base > 0
                 - Total GST Liability = IGST + CGST+SGST
                 - All calculations done in backend, frontend reads from dashboard_stats only
            -->
        </div>
        
        <div class="kpi-card kpi-card--primary">
            <div class="kpi-card__header">
                <div class="kpi-card__icon">🛒</div>
                <div class="kpi-card__trend" id="poTrend">↗ +0%</div>
            </div>
            <div class="kpi-card__value" id="pendingPOValue">₹0</div>
            <div class="kpi-card__label">PO Commitments</div>
            <div class="kpi-card__description">Total Value of All Purchase Orders</div>
            <div class="kpi-card__details">
                <div class="detail-item">Open POs: <span id="openPOCount">0</span></div>
                <div class="detail-item">Closed POs: <span id="closedPOCount">0</span></div>
            </div>
            <!-- Stat Card 5 Implementation:
                 - PO Commitments = sum(total_amount) for all POs
                 - Open POs = count where (amount_paid < total_amount) OR received_date IS NULL
                 - Closed POs = count where (amount_paid >= total_amount) AND received_date IS NOT NULL
                 - All calculations done in backend, frontend reads from dashboard_stats only
            -->
        </div>
        
        <div class="kpi-card kpi-card--secondary">
            <div class="kpi-card__header">
                <div class="kpi-card__icon">💸</div>
                <div class="kpi-card__trend" id="claimableTrend">— 0%</div>
            </div>
            <div class="kpi-card__value" id="claimableAmount">₹0</div>
            <div class="kpi-card__label">Claimable Amount</div>
            <div class="kpi-card__description">Total Invoice Amount - Payments Received</div>
            <div class="kpi-card__details">
                <div class="detail-item">Claimable POs: <span id="claimablePOCount">0</span></div>
                <div class="detail-item">Claim Rate: <span id="claimRate">0%</span></div>
            </div>
        </div>
    </div>

    <!-- Conversion Funnel -->
    <div class="dashboard-grid">
        <div class="card card--full-width">
            <div class="card__header">
                <h2 class="card__title">🔄 Revenue Conversion Funnel</h2>
                <select id="customerFilter" class="form-control customer-filter">
                    <option value="">All Customers</option>
                </select>
                <span id="customerLoader" class="customer-loader" aria-hidden="true"></span>
            </div>
            <div class="card__body">
                <div class="funnel-container">
                    <div class="funnel-stage">
                        <div class="funnel-number" id="funnelQuotations">0</div>
                        <div class="funnel-label">Quotations</div>
                        <div class="funnel-value" id="funnelQuotationValue">₹0</div>
                    </div>
                    <div class="funnel-arrow">→</div>
                    <div class="funnel-stage">
                        <div class="funnel-number" id="funnelPOs">0</div>
                        <div class="funnel-label">Purchase Orders</div>
                        <div class="funnel-value" id="funnelPOValue">₹0</div>
                        <div class="funnel-conversion" id="quotationToPO">0%</div>
                    </div>
                    <div class="funnel-arrow">→</div>
                    <div class="funnel-stage">
                        <div class="funnel-number" id="funnelInvoices">0</div>
                        <div class="funnel-label">Invoices</div>
                        <div class="funnel-value" id="funnelInvoiceValue">₹0</div>
                        <div class="funnel-conversion" id="poToInvoice">0%</div>
                    </div>
                    <div class="funnel-arrow">→</div>
                    <div class="funnel-stage">
                        <div class="funnel-number" id="funnelPayments">0</div>
                        <div class="funnel-label">Payments</div>
                        <div class="funnel-value" id="funnelPaymentValue">₹0</div>
                        <div class="funnel-conversion" id="invoiceToPayment">0%</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="dashboard-grid dashboard-grid--2-col">
        <div class="chart-card">
            <div class="chart-card__header">
                <div class="chart-card__info">
                    <div class="chart-card__icon">📝</div>
                    <div class="chart-card__title">Quotations Overview</div>
                    <div class="chart-card__value" id="quotationsTotal">0</div>
                    <div class="chart-card__subtitle">Sales Pipeline Status Distribution</div>
                </div>
                <div class="chart-card__trend" id="quotationsTrend">+0%</div>
            </div>
            <div class="chart-card__chart">
                <canvas id="quotationsChart"></canvas>
                <div class="chart-legend">
                    <div class="legend-item"><span class="legend-color" style="background:#3b82f6"></span>Draft (Initial)</div>
                    <div class="legend-item"><span class="legend-color" style="background:#f59e0b"></span>Revised (Updated)</div>
                    <div class="legend-item"><span class="legend-color" style="background:#10b981"></span>Converted (Won)</div>
                </div>
            </div>
            <div class="chart-card__meta">
                <div class="meta-item"><span>Win Rate:</span><strong id="quotationWinRate">0%</strong></div>
                <div class="meta-item"><span>Avg Deal Size:</span><strong id="quotationsAvg">₹0</strong></div>
                <div class="meta-item"><span>Pipeline Value:</span><strong id="pipelineValue">₹0</strong></div>
            </div>
        </div>
        
        <div class="chart-card">
            <div class="chart-card__header">
                <div class="chart-card__info">
                    <div class="chart-card__icon">🛒</div>
                    <div class="chart-card__title">Purchase Orders</div>
                    <div class="chart-card__value" id="poTotal">0</div>
                    <div class="chart-card__subtitle">Procurement Commitment Timeline</div>
                </div>
                <div class="chart-card__trend" id="poTrendChart">+0%</div>
            </div>
            <div class="chart-card__chart">
                <canvas id="purchaseOrdersChart"></canvas>
            </div>
            <div class="chart-card__meta">
                <div class="meta-item"><span>Fulfillment Rate:</span><strong id="poFulfillmentRate">0%</strong></div>
                <div class="meta-item"><span>Avg Lead Time:</span><strong id="avgLeadTime">0 days</strong></div>
                <div class="meta-item"><span>Open Commitments:</span><strong id="openCommitments">₹0</strong></div>
            </div>
        </div>
        
        <div class="chart-card">
            <div class="chart-card__header">
                <div class="chart-card__info">
                    <div class="chart-card__icon">💰</div>
                    <div class="chart-card__title">Invoice Status</div>
                    <div class="chart-card__value" id="invoicesTotal">0</div>
                    <div class="chart-card__subtitle">Revenue Collection Health</div>
                </div>
                <div class="chart-card__trend" id="invoicesTrendChart">0%</div>
            </div>
            <div class="chart-card__chart">
                <canvas id="invoicesChart"></canvas>
                <div class="chart-legend">
                    <div class="legend-item"><span class="legend-color" style="background:#10b981"></span>Paid (Collected)</div>
                    <div class="legend-item"><span class="legend-color" style="background:#f59e0b"></span>Unpaid (Due)</div>
                    <div class="legend-item"><span class="legend-color" style="background:#ef4444"></span>Overdue (Risk)</div>
                </div>
            </div>
            <div class="chart-card__meta">
                <div class="meta-item"><span>DSO:</span><strong id="dsoMetric">0 days</strong></div>
                <div class="meta-item"><span>Bad Debt Risk:</span><strong id="badDebtRisk">₹0</strong></div>
                <div class="meta-item"><span>Collection Efficiency:</span><strong id="collectionEfficiency">0%</strong></div>
            </div>
        </div>
        
        <div class="chart-card">
            <div class="chart-card__header">
                <div class="chart-card__info">
                    <div class="chart-card__icon">📊</div>
                    <div class="chart-card__title">Outstanding Distribution</div>
                    <div class="chart-card__value" id="outstandingTotal">₹0</div>
                    <div class="chart-card__subtitle">Top Customer Outstanding Amounts</div>
                </div>
                <div class="chart-card__trend" id="outstandingTrend">0%</div>
            </div>
            <div class="chart-card__chart">
                <canvas id="outstandingByCustomerChart"></canvas>
            </div>
            <div class="chart-card__meta">
                <div class="meta-item"><span>Concentration Risk:</span><strong id="concentrationRisk">0%</strong></div>
                <div class="meta-item"><span>Top 3 Exposure:</span><strong id="top3Exposure">₹0</strong></div>
                <div class="meta-item"><span>Customer Diversity:</span><strong id="customerDiversity">0</strong></div>
            </div>
        </div>
        
        <div class="chart-card">
            <div class="chart-card__header">
                <div class="chart-card__info">
                    <div class="chart-card__icon">⏳</div>
                    <div class="chart-card__title">Aging Buckets</div>
                    <div class="chart-card__value" id="agingTotal">₹0</div>
                    <div class="chart-card__subtitle">Credit Risk Assessment Matrix</div>
                </div>
                <div class="chart-card__trend" id="agingTrend">0%</div>
            </div>
            <div class="chart-card__chart">
                <canvas id="agingBucketsChart"></canvas>
                <div class="chart-legend">
                    <div class="legend-item"><span class="legend-color" style="background:#10b981"></span>Current (0-30)</div>
                    <div class="legend-item"><span class="legend-color" style="background:#f59e0b"></span>Watch (31-60)</div>
                    <div class="legend-item"><span class="legend-color" style="background:#fb923c"></span>Concern (61-90)</div>
                    <div class="legend-item"><span class="legend-color" style="background:#ef4444"></span>Critical (90+)</div>
                </div>
            </div>
            <div class="chart-card__meta">
                <div class="meta-item"><span>Provision Req:</span><strong id="provisionRequired">₹0</strong></div>
                <div class="meta-item"><span>Recovery Rate:</span><strong id="recoveryRate">0%</strong></div>
                <div class="meta-item"><span>Credit Quality:</span><strong id="creditQuality">Good</strong></div>
            </div>
        </div>
        
        <div class="chart-card">
            <div class="chart-card__header">
                <div class="chart-card__info">
                    <div class="chart-card__icon">💳</div>
                    <div class="chart-card__title">Payments</div>
                    <div class="chart-card__value" id="paymentsTotal">₹0</div>
                    <div class="chart-card__subtitle">Cash Flow Realization Pattern</div>
                </div>
                <div class="chart-card__trend" id="paymentsTrend">+0%</div>
            </div>
            <div class="chart-card__chart">
                <canvas id="paymentsChart"></canvas>
            </div>
            <div class="chart-card__meta">
                <div class="meta-item"><span>Velocity:</span><strong id="paymentVelocity">₹0/day</strong></div>
                <div class="meta-item"><span>Forecast Accuracy:</span><strong id="forecastAccuracy">0%</strong></div>
                <div class="meta-item"><span>Cash Conversion:</span><strong id="cashConversion">0 days</strong></div>
            </div>
        </div>
    </div>

    <!-- Outstanding Invoices Table -->
    <div class="dashboard-grid">
        <div class="card card--full-width">
            <div class="card__header">
                <h2 class="card__title">⚠️ Outstanding Invoices</h2>
                <button class="btn btn--sm" onclick="exportTable('outstanding')">Export</button>
            </div>
            <div class="card__body">
                <div class="table-responsive">
                    <table class="table" id="outstandingTable">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Customer</th>
                                <th>Due Date</th>
                                <th>Outstanding Amount</th>
                                <th>Days Overdue</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="text-center">Loading outstanding invoices...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activities -->
    <div class="dashboard-grid">
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">📈 Recent Activities</h2>
                <div class="activity-filters">
                    <button class="filter-btn active" data-type="all">All</button>
                    <button class="filter-btn" data-type="quotation">📝</button>
                    <button class="filter-btn" data-type="po">🛒</button>
                    <button class="filter-btn" data-type="invoice">💰</button>
                    <button class="filter-btn" data-type="payment">💳</button>
                </div>
            </div>
            <div class="card__body">
                <div id="recentActivities">
                    <div class="activity-item">
                        <div class="activity-loading">Loading recent activities...</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">💰 Cash Flow Projection</h2>
            </div>
            <div class="card__body">
                <div class="cash-flow-summary">
                    <div class="flow-item">
                        <div class="flow-label">Expected Inflow:</div>
                        <div class="flow-value flow-positive" id="expectedInflow">₹0</div>
                    </div>
                    <div class="flow-item">
                        <div class="flow-label">PO Commitments:</div>
                        <div class="flow-value flow-negative" id="poCommitments">₹0</div>
                    </div>
                    <div class="flow-item">
                        <div class="flow-label">Net Cash Flow:</div>
                        <div class="flow-value" id="netCashFlow">₹0</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>


let quotationsChart, purchaseOrdersChart, invoicesChart, paymentsChart;
let outstandingByCustomerChart;
let agingBucketsChart;

// Notification function
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification--${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 20px;
        background: ${type === 'error' ? '#f8d7da' : type === 'success' ? '#d4edda' : type === 'warning' ? '#fff3cd' : '#d1ecf1'};
        border: 1px solid ${type === 'error' ? '#f5c6cb' : type === 'success' ? '#c3e6cb' : type === 'warning' ? '#ffeaa7' : '#bee5eb'};
        color: ${type === 'error' ? '#721c24' : type === 'success' ? '#155724' : type === 'warning' ? '#856404' : '#0c5460'};
        border-radius: 6px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        max-width: 400px;
        font-size: 14px;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 5000);
}

document.addEventListener('DOMContentLoaded', function() {
    initCharts();
    
    document.getElementById('syncBtn').addEventListener('click', syncFinanceData);
    document.getElementById('exportBtn').addEventListener('click', exportDashboard);
    document.getElementById('updatePrefixBtn').addEventListener('click', updateCompanyPrefix);

    document.getElementById('dateFilter').addEventListener('change', filterByDate);
    document.getElementById('customerFilter').addEventListener('change', filterByCustomer);
    // Outstanding top-N control
    const topN = document.getElementById('outstandingTopN');
    if (topN) topN.addEventListener('change', () => loadOutstandingByCustomer(parseInt(topN.value, 10)));
    const outDownload = document.getElementById('outstandingDownload');
    if (outDownload) outDownload.addEventListener('click', () => {
        const limit = parseInt(document.getElementById('outstandingTopN').value || '10', 10);
        // Use server-side export endpoint which supports `limit`
        window.open(`/ergon/finance/export-outstanding?limit=${limit}`, '_blank');
    });
    
    // Load current prefix, then dashboard data
    loadCompanyPrefix().then(() => {
        loadCustomers();
        loadDashboardData();
        // Debug purchase orders
        debugPurchaseOrders();
    });
});

function initCharts() {
    const chartDefaults = {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 250 },
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const v = context.raw || 0;
                        if (typeof v === 'number') return '₹' + Number(v).toLocaleString();
                        return String(v);
                    }
                }
            }
        },
        scales: {
            x: { display: false },
            y: { display: false }
        }
    };

    // Quotations Pie Chart
    const quotationsCtx = document.getElementById('quotationsChart');
    if (quotationsCtx) {
        quotationsChart = new Chart(quotationsCtx.getContext('2d'), {
            type: 'pie',
            data: { labels: ['Draft','Revised','Converted'], datasets: [{ data: [0,0,0], backgroundColor: ['#3b82f6','#f59e0b','#10b981'] }] },
            options: chartDefaults
        });
    }

    // Purchase Orders Area Chart
    const poCtx = document.getElementById('purchaseOrdersChart');
    if (poCtx) {
        purchaseOrdersChart = new Chart(poCtx.getContext('2d'), {
            type: 'line',
            data: { labels: [], datasets: [{ label: 'PO Amount', data: [], borderColor: '#059669', backgroundColor: 'rgba(5,150,105,0.1)', fill: true, tension: 0.3 }] },
            options: chartDefaults
        });
    }

    // Invoices Donut Chart
    const invoicesCtx = document.getElementById('invoicesChart');
    if (invoicesCtx) {
        invoicesChart = new Chart(invoicesCtx.getContext('2d'), {
            type: 'doughnut',
            data: { labels: ['Paid','Unpaid','Overdue'], datasets: [{ data: [0,0,0], backgroundColor: ['#10b981','#f59e0b','#ef4444'] }] },
            options: { ...chartDefaults, cutout: '70%' }
        });
    }

    // Outstanding by Customer Donut Chart
    const outstandingCtx = document.getElementById('outstandingByCustomerChart');
    if (outstandingCtx) {
        outstandingByCustomerChart = new Chart(outstandingCtx.getContext('2d'), {
            type: 'doughnut',
            data: { 
                labels: [], 
                datasets: [{ 
                    data: [], 
                    backgroundColor: ['#ef4444', '#f97316', '#eab308', '#84cc16', '#22c55e', '#06b6d4', '#3b82f6', '#8b5cf6', '#ec4899', '#f43f5e']
                }] 
            },
            options: { ...chartDefaults, cutout: '60%' }
        });
    }

    // Aging Buckets Donut Chart
    const agingCtx = document.getElementById('agingBucketsChart');
    if (agingCtx) {
        agingBucketsChart = new Chart(agingCtx.getContext('2d'), {
            type: 'doughnut',
            data: { labels: ['0-30 Days','31-60 Days','61-90 Days','90+ Days'], datasets: [{ data: [0,0,0,0], backgroundColor: ['#10b981','#f59e0b','#fb923c','#ef4444'] }] },
            options: { ...chartDefaults, cutout: '70%' }
        });
    }

    // Payments Chart
    const paymentsCtx = document.getElementById('paymentsChart');
    if (paymentsCtx) {
        paymentsChart = new Chart(paymentsCtx.getContext('2d'), {
            type: 'bar',
            data: { labels: [], datasets: [{ label: 'Payments', data: [], backgroundColor: '#3b82f6' }] },
            options: chartDefaults
        });
    }
}

async function debugPurchaseOrders() {
    try {
        const response = await fetch('/ergon/finance/debug-po');
        const data = await response.json();
        console.log('Purchase Orders Debug:', data);
        
        if (data.total_records === 0) {
            console.warn('No purchase order records found in database');
            showNotification('No purchase order data found. Please sync data first.', 'warning');
        } else {
            console.log(`Found ${data.total_records} purchase order records`);
            if (data.sample_data && data.sample_data.length > 0) {
                console.log('Sample PO data structure:', data.sample_data[0]);
            }
        }
    } catch (error) {
        console.error('Debug PO error:', error);
    }
}

async function showTableStructure() {
    const btn = document.getElementById('structureBtn');
    btn.disabled = true;
    btn.textContent = 'Loading...';
    
    try {
        const response = await fetch('/ergon/finance/structure');
        const data = await response.json();
        
        console.log('Table Structure:', data);
        
        let structureHtml = '<h3>Database Structure</h3>';
        structureHtml += `<p><strong>Company Prefix:</strong> ${data.prefix || 'None set'}</p>`;
        
        if (data.tables && data.tables.length > 0) {
            structureHtml += '<table class="table"><thead><tr><th>Table</th><th>Records</th><th>Last Sync</th><th>Actual Count</th></tr></thead><tbody>';
            data.tables.forEach(table => {
                const actualCount = data.actual_counts[table.name] || 0;
                structureHtml += `<tr><td>${table.name}</td><td>${table.records}</td><td>${table.last_sync}</td><td>${actualCount}</td></tr>`;
            });
            structureHtml += '</tbody></table>';
        }
        
        // Show in a modal or alert
        const modal = document.createElement('div');
        modal.style.cssText = `
            position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
            background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            max-width: 80%; max-height: 80%; overflow: auto; z-index: 10000;
        `;
        modal.innerHTML = structureHtml + '<button onclick="this.parentNode.remove()" style="margin-top: 15px; padding: 8px 16px;">Close</button>';
        document.body.appendChild(modal);
        
        btn.disabled = false;
        btn.textContent = 'Show Structure';
        
    } catch (error) {
        console.error('Structure error:', error);
        showNotification('Failed to load table structure', 'error');
        btn.disabled = false;
        btn.textContent = 'Show Structure';
    }
}

async function loadDashboardData() {
    try {
        const response = await fetch('/ergon/finance/dashboard-stats');
        const data = await response.json();
        
        console.log('Dashboard Stats:', data);
        
        if (data.error) {
            showNotification(data.error, 'error');
            return;
        }
        
        if (data.message) {
            showNotification(data.message, 'info');
        }
        
        // Update KPI cards
        updateKPICard('totalInvoiceAmount', data.totalInvoiceAmount || 0);
        updateKPICard('invoiceReceived', data.invoiceReceived || 0);
        updateKPICard('pendingInvoiceAmount', data.pendingInvoiceAmount || 0);
        updateKPICard('pendingGSTAmount', data.pendingGSTAmount || 0);
        updateKPICard('pendingPOValue', data.pendingPOValue || 0);
        updateKPICard('claimableAmount', data.claimableAmount || 0);
        
        // Update PO specific metrics
        const openPOElement = document.getElementById('openPOCount');
        if (openPOElement) {
            openPOElement.textContent = data.openPOCount || 0;
        }
        
        const totalPOElement = document.getElementById('totalPOCount');
        if (totalPOElement) {
            totalPOElement.textContent = data.totalPOCount || 0;
        }
        
        // Update conversion funnel
        if (data.conversionFunnel) {
            updateConversionFunnel(data.conversionFunnel);
        }
        
        // Update cash flow
        if (data.cashFlow) {
            updateCashFlow(data.cashFlow);
        }
        
        // Load other data
        loadOutstandingInvoices();
        loadOutstandingByCustomer();
        loadAgingBuckets();
        loadRecentActivities();
        loadCharts();
        
    } catch (error) {
        console.error('Dashboard data error:', error);
        showNotification('Failed to load dashboard data', 'error');
    }
}

function updateKPICard(elementId, value) {
    const element = document.getElementById(elementId);
    if (element) {
        if (typeof value === 'number') {
            element.textContent = '₹' + value.toLocaleString();
        } else {
            element.textContent = value;
        }
    }
}

function updateConversionFunnel(funnel) {
    // Update quotations
    const quotationsElement = document.querySelector('.funnel-item:nth-child(1) .funnel-value');
    if (quotationsElement) {
        quotationsElement.textContent = '₹' + (funnel.quotationValue || 0).toLocaleString();
    }
    
    const quotationsCountElement = document.querySelector('.funnel-item:nth-child(1) .funnel-count');
    if (quotationsCountElement) {
        quotationsCountElement.textContent = (funnel.quotations || 0) + ' quotations';
    }
    
    // Update purchase orders
    const poElement = document.querySelector('.funnel-item:nth-child(2) .funnel-value');
    if (poElement) {
        poElement.textContent = '₹' + (funnel.poValue || 0).toLocaleString();
    }
    
    const poCountElement = document.querySelector('.funnel-item:nth-child(2) .funnel-count');
    if (poCountElement) {
        poCountElement.textContent = (funnel.purchaseOrders || 0) + ' POs';
    }
    
    // Update conversion percentages
    const quotationToPOElement = document.querySelector('.funnel-item:nth-child(2) .funnel-percentage');
    if (quotationToPOElement) {
        quotationToPOElement.textContent = (funnel.quotationToPO || 0) + '%';
    }
}

function updateCashFlow(cashFlow) {
    const expectedInflowElement = document.getElementById('expectedInflow');
    if (expectedInflowElement) {
        expectedInflowElement.textContent = '₹' + (cashFlow.expectedInflow || 0).toLocaleString();
    }
    
    const poCommitmentsElement = document.getElementById('poCommitments');
    if (poCommitmentsElement) {
        poCommitmentsElement.textContent = '₹' + (cashFlow.poCommitments || 0).toLocaleString();
    }
    
    const netCashFlowElement = document.getElementById('netCashFlow');
    if (netCashFlowElement) {
        const netFlow = (cashFlow.expectedInflow || 0) - (cashFlow.poCommitments || 0);
        netCashFlowElement.textContent = '₹' + netFlow.toLocaleString();
        netCashFlowElement.className = 'flow-value ' + (netFlow >= 0 ? 'flow-positive' : 'flow-negative');
    }
}

async function loadDashboardDataOld() {
    try {
        const response = await fetch('/ergon/finance/dashboard-stats');
        const data = await response.json();
        
        if (data.error) {
            alert('Error: ' + data.error);
            return;
        }
        
        renderTableStructure(data.tables);
        
        document.getElementById('structureModal').style.display = 'block';
        
    } catch (error) {
        alert('Failed to load structure: ' + error.message);
    } finally {
        btn.disabled = false;
        btn.textContent = 'View Table Structure';
    }
}

function renderTableStructure(tables) {
    const container = document.getElementById('structureContainer');
    
    let html = '<div class="followups-modern">';
    
    tables.forEach((table, index) => {
        html += `
            <div class="followup-card">
                <div class="followup-card__header">
                    <div class="followup-icon task-linked">📋</div>
                    <div class="followup-title-section">
                        <h4 class="followup-title">${table.display_name}</h4>
                        <div class="followup-badges">
                            <span class="badge badge--info">${table.column_count} columns</span>
                            <span class="badge badge--success">${table.actual_rows} rows</span>
                        </div>
                    </div>
                    <button class="btn--modern btn--outline" onclick="toggleStructure(this)">
                        <span class="expand-icon">▼</span>
                    </button>
                </div>
                <div class="structure-details ${index === 0 ? 'is-open' : ''}">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Column</th>
                                    <th>Type</th>
                                    <th>Nullable</th>
                                    <th>Default</th>
                                </tr>
                            </thead>
                            <tbody>`;
        
        table.columns.forEach(col => {
            html += `
                <tr>
                    <td><code>${col.name}</code></td>
                    <td><span class="badge badge--info">${col.type}</span></td>
                    <td>${col.nullable ? '✓' : '✗'}</td>
                    <td>${col.default || '-'}</td>
                </tr>`;
        });
        
        html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>`;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

function toggleStructure(button) {
    const card = button.closest('.followup-card');
    const details = card.querySelector('.structure-details');
    const icon = button.querySelector('.expand-icon');

    if (details.classList.contains('is-open')) {
        details.classList.remove('is-open');
        icon.textContent = '▼';
    } else {
        details.classList.add('is-open');
        icon.textContent = '▲';
    }
}

function closeStructureModal() {
    document.getElementById('structureModal').style.display = 'none';
}

function analyzeAllTables() {
    const btn = document.getElementById('analyzeBtn');
    btn.disabled = true;
    btn.textContent = 'Generating CSV...';
    
    // Create download link
    const link = document.createElement('a');
    link.href = '/ergon/finance/analyze';
    link.download = 'finance_analysis.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    setTimeout(() => {
        btn.disabled = false;
        btn.textContent = 'Analyze All Tables';
    }, 1000);
}

async function syncFinanceData() {
    const btn = document.getElementById('syncBtn');
    btn.disabled = true;
    btn.textContent = 'Syncing...';
    
    try {
        const response = await fetch('/ergon/finance/sync', {method: 'POST'});
        const result = await response.json();
        
        if (result.error) {
            alert('Sync failed: ' + result.error);
        } else {
            alert(`Synced ${result.tables} finance tables successfully`);
            loadDashboardData();
        }
    } catch (error) {
        alert('Sync failed: ' + error.message);
    } finally {
        btn.disabled = false;
        btn.textContent = '🔄 Sync Data';
    }
}

async function loadDashboardData() {
    try {
        const customerFilter = document.getElementById('customerFilter').value;
        const url = customerFilter ? `/ergon/finance/dashboard-stats?customer=${encodeURIComponent(customerFilter)}` : '/ergon/finance/dashboard-stats';
        
        console.log('Loading dashboard data from:', url);
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const text = await response.text();
        console.log('Raw response:', text.substring(0, 200));
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            throw new Error('Invalid JSON response: ' + text.substring(0, 100));
        }
        
        if (data.error) {
            console.warn('API returned error:', data.error);
            if (data.details) console.warn('Error details:', data.details);
        }
        
        updateKPICards(data);
        updateConversionFunnel(data);
        updateCharts(data);
        loadOutstandingInvoices();
        loadRecentActivities();
        updateCashFlow(data);
        
        if (data.message) {
            showNotification(data.message, 'info');
        }
        
        // Show source information for Stat Card 3
        if (data.source === 'dashboard_stats') {
            console.log('Stat Card 3: Using backend-calculated metrics from dashboard_stats table');
            console.log('Outstanding Amount calculation: taxable_amount - amount_paid (no GST)');
        } else if (data.source === 'empty') {
            showNotification('Stat Card 3 requires backend calculation. Click "Refresh Stats" to calculate metrics.', 'warning');
        }
        
    } catch (error) {
        console.error('Failed to load dashboard data:', error);
        showNotification('Failed to load dashboard data: ' + error.message, 'error');
        // Load with empty data to prevent UI breaking
        updateKPICards({});
        updateConversionFunnel({});
        updateCashFlow({});
    }
}

function updateKPICards(data) {
    const funnel = data.conversionFunnel || {};
    
    // Total Invoice Amount
    document.getElementById('totalInvoiceAmount').textContent = `₹${(data.totalInvoiceAmount || 0).toLocaleString()}`;
    
    // Update invoice details
    const totalInvoiceCount = document.getElementById('totalInvoiceCount');
    const avgInvoiceAmount = document.getElementById('avgInvoiceAmount');
    if (totalInvoiceCount) totalInvoiceCount.textContent = funnel.invoices || 0;
    if (avgInvoiceAmount && funnel.invoices > 0) {
        avgInvoiceAmount.textContent = `₹${Math.round((data.totalInvoiceAmount || 0) / funnel.invoices).toLocaleString()}`;
    } else if (avgInvoiceAmount) {
        avgInvoiceAmount.textContent = '₹0';
    }
    
    // Invoice Amount Received
    document.getElementById('invoiceReceived').textContent = `₹${(data.invoiceReceived || 0).toLocaleString()}`;
    
    // Update received details
    const collectionRateKPI = document.getElementById('collectionRateKPI');
    const paidInvoiceCount = document.getElementById('paidInvoiceCount');
    if (collectionRateKPI && data.totalInvoiceAmount > 0) {
        collectionRateKPI.textContent = `${Math.round((data.invoiceReceived / data.totalInvoiceAmount) * 100)}%`;
    } else if (collectionRateKPI) {
        collectionRateKPI.textContent = '0%';
    }
    if (paidInvoiceCount) paidInvoiceCount.textContent = funnel.payments || 0;
    
    // Stat Card 3: Outstanding Amount (Backend calculated)
    document.getElementById('pendingInvoiceAmount').textContent = `₹${(data.outstandingAmount || data.pendingInvoiceAmount || 0).toLocaleString()}`;
    
    // Update Stat Card 3 details with backend calculations
    const pendingInvoicesCount = document.getElementById('pendingInvoicesCount');
    const customersPendingCount = document.getElementById('customersPendingCount');
    const overdueAmount = document.getElementById('overdueAmount');
    
    if (pendingInvoicesCount) pendingInvoicesCount.textContent = data.pendingInvoices || 0;
    if (customersPendingCount) customersPendingCount.textContent = data.customersPending || 0;
    if (overdueAmount) overdueAmount.textContent = `₹${(data.overdueAmount || 0).toLocaleString()}`;
    
    // Update trend for outstanding percentage
    const pendingTrend = document.getElementById('pendingTrend');
    if (pendingTrend && data.outstandingPercentage !== undefined) {
        pendingTrend.textContent = `${Math.round(data.outstandingPercentage)}%`;
    }
    
    // Stat Card 4: GST Liability (Backend calculated)
    document.getElementById('pendingGSTAmount').textContent = `₹${(data.gstLiability || data.pendingGSTAmount || 0).toLocaleString()}`;
    
    // Update GST details from backend calculations
    const igstLiability = document.getElementById('igstLiability');
    const cgstSgstTotal = document.getElementById('cgstSgstTotal');
    if (igstLiability) igstLiability.textContent = `₹${(data.igstLiability || 0).toLocaleString()}`;
    if (cgstSgstTotal) cgstSgstTotal.textContent = `₹${(data.cgstSgstTotal || 0).toLocaleString()}`;
    
    // PO Commitments - Use both dashboard data and funnel data
    const poValue = data.pendingPOValue || funnel.poValue || 0;
    document.getElementById('pendingPOValue').textContent = `₹${poValue.toLocaleString()}`;
    
    // Update PO details from backend calculations
    const openPOCount = document.getElementById('openPOCount');
    const closedPOCount = document.getElementById('closedPOCount');
    
    if (openPOCount) openPOCount.textContent = data.openPOCount || 0;
    if (closedPOCount) closedPOCount.textContent = data.closedPOCount || 0;
    
    // Stat Card 6: Claimable Amount (Backend calculated from invoices)
    document.getElementById('claimableAmount').textContent = `₹${(data.claimableAmount || 0).toLocaleString()}`;
    
    // Update claimable details with backend calculations
    const claimablePOCount = document.getElementById('claimablePOCount');
    const claimRate = document.getElementById('claimRate');
    if (claimablePOCount) claimablePOCount.textContent = data.claimablePOCount || data.claimablePos || 0;
    if (claimRate) claimRate.textContent = `${Math.round(data.claimRate || 0)}%`;
    
    // Update trend for claim rate
    const claimableTrend = document.getElementById('claimableTrend');
    if (claimableTrend && data.claimRate !== undefined) {
        claimableTrend.textContent = `${Math.round(data.claimRate)}%`;
    }
}

function updateConversionFunnel(data) {
    const funnel = data.conversionFunnel || {};
    
    document.getElementById('funnelQuotations').textContent = funnel.quotations || 0;
    document.getElementById('funnelQuotationValue').textContent = `₹${(funnel.quotationValue || 0).toLocaleString()}`;
    
    document.getElementById('funnelPOs').textContent = funnel.purchaseOrders || 0;
    document.getElementById('funnelPOValue').textContent = `₹${(funnel.poValue || 0).toLocaleString()}`;
    document.getElementById('quotationToPO').textContent = `${funnel.quotationToPO || 0}%`;
    
    document.getElementById('funnelInvoices').textContent = funnel.invoices || 0;
    document.getElementById('funnelInvoiceValue').textContent = `₹${(funnel.invoiceValue || 0).toLocaleString()}`;
    document.getElementById('poToInvoice').textContent = `${funnel.poToInvoice || 0}%`;
    
    document.getElementById('funnelPayments').textContent = funnel.payments || 0;
    document.getElementById('funnelPaymentValue').textContent = `₹${(funnel.paymentValue || 0).toLocaleString()}`;
    document.getElementById('invoiceToPayment').textContent = `${funnel.invoiceToPayment || 0}%`;
}

async function updateCharts(data) {
    const funnel = data.conversionFunnel || {};
    try {
        // Update Quotations Chart
        const quotationsResponse = await fetch('/ergon/finance/visualization?type=quotations');
        if (!quotationsResponse.ok) throw new Error('Quotations API not available');
        const quotationsText = await quotationsResponse.text();
        const quotationsData = quotationsText ? JSON.parse(quotationsText) : {};
        
        if (quotationsChart && quotationsData.data) {
            quotationsChart.data.datasets[0].data = quotationsData.data;
            quotationsChart.update();
        }
        
        // Update quotation metrics from funnel data
        const funnel = data.conversionFunnel || {};
        const winRateEl = document.getElementById('quotationWinRate');
        const avgEl = document.getElementById('quotationsAvg');
        const pipelineEl = document.getElementById('pipelineValue');
        const totalEl = document.getElementById('quotationsTotal');
        
        if (winRateEl) winRateEl.textContent = `${funnel.quotationToPO || 0}%`;
        if (avgEl && funnel.quotations > 0) {
            avgEl.textContent = `₹${Math.round((funnel.quotationValue || 0) / funnel.quotations).toLocaleString()}`;
        } else if (avgEl) {
            avgEl.textContent = '₹0';
        }
        if (pipelineEl) pipelineEl.textContent = `₹${(funnel.quotationValue || 0).toLocaleString()}`;
        if (totalEl) totalEl.textContent = funnel.quotations || 0;
        
        // Update Purchase Orders Chart
        const poResponse = await fetch('/ergon/finance/visualization?type=purchase_orders');
        if (!poResponse.ok) throw new Error('PO API not available');
        const poText = await poResponse.text();
        const poData = poText ? JSON.parse(poText) : {};
        
        if (purchaseOrdersChart) {
            if (poData.labels && poData.data) {
                purchaseOrdersChart.data.labels = poData.labels;
                purchaseOrdersChart.data.datasets[0].data = poData.data;
                purchaseOrdersChart.update();
            } else {
                purchaseOrdersChart.data.labels = ['No Data'];
                purchaseOrdersChart.data.datasets[0].data = [0];
                purchaseOrdersChart.update();
            }
        }
        
        // Update PO metrics from funnel data
        const fulfillmentEl = document.getElementById('poFulfillmentRate');
        const leadTimeEl = document.getElementById('avgLeadTime');
        const commitmentsEl = document.getElementById('openCommitments');
        const poTotalEl = document.getElementById('poTotal');
        
        if (fulfillmentEl) fulfillmentEl.textContent = `${funnel.poToInvoice || 0}%`;
        if (leadTimeEl) leadTimeEl.textContent = '15 days'; // Default estimate
        if (commitmentsEl) commitmentsEl.textContent = `₹${(funnel.poValue || 0).toLocaleString()}`;
        if (poTotalEl) poTotalEl.textContent = funnel.purchaseOrders || 0;
        
        // Update Invoices Chart
        const invoicesResponse = await fetch('/ergon/finance/visualization?type=invoices');
        if (!invoicesResponse.ok) throw new Error('Invoices API not available');
        const invoicesText = await invoicesResponse.text();
        const invoicesData = invoicesText ? JSON.parse(invoicesText) : {};
        
        if (invoicesChart && invoicesData.data) {
            invoicesChart.data.datasets[0].data = invoicesData.data;
            invoicesChart.update();
        }
        
        // Update invoice metrics from dashboard data
        const dsoEl = document.getElementById('dsoMetric');
        const badDebtEl = document.getElementById('badDebtRisk');
        const efficiencyEl = document.getElementById('collectionEfficiency');
        const invoicesTotalEl = document.getElementById('invoicesTotal');
        
        if (dsoEl) {
            const dso = data.totalInvoiceAmount > 0 ? Math.round((data.pendingInvoiceAmount / data.totalInvoiceAmount) * 365) : 0;
            dsoEl.textContent = `${dso} days`;
        }
        if (badDebtEl) badDebtEl.textContent = `₹${Math.round((data.pendingInvoiceAmount || 0) * 0.05).toLocaleString()}`; // 5% estimate
        if (efficiencyEl && data.totalInvoiceAmount > 0) {
            efficiencyEl.textContent = `${Math.round((data.invoiceReceived / data.totalInvoiceAmount) * 100)}%`;
        } else if (efficiencyEl) {
            efficiencyEl.textContent = '0%';
        }
        if (invoicesTotalEl) invoicesTotalEl.textContent = funnel.invoices || 0;
        
        // Update Outstanding by Customer Chart
        const outstandingResp = await fetch('/ergon/finance/outstanding-by-customer?limit=10');
        if (!outstandingResp.ok) throw new Error('Outstanding API not available');
        const outstandingText = await outstandingResp.text();
        const outstandingData = outstandingText ? JSON.parse(outstandingText) : {};
        if (outstandingByCustomerChart && outstandingData.labels) {
            outstandingByCustomerChart.data.labels = outstandingData.labels;
            outstandingByCustomerChart.data.datasets[0].data = outstandingData.data;
            outstandingByCustomerChart.update();
        }
        
        // Update outstanding metrics
        const concentrationEl = document.getElementById('concentrationRisk');
        const exposureEl = document.getElementById('top3Exposure');
        const diversityEl = document.getElementById('customerDiversity');
        const outTotalEl = document.getElementById('outstandingTotal');
        
        if (concentrationEl && outstandingData.total > 0) {
            const topCustomer = Math.max(...(outstandingData.data || [0]));
            concentrationEl.textContent = `${Math.round((topCustomer / outstandingData.total) * 100)}%`;
        } else if (concentrationEl) {
            concentrationEl.textContent = '0%';
        }
        if (exposureEl && outstandingData.data) {
            const top3 = outstandingData.data.slice(0, 3).reduce((sum, val) => sum + val, 0);
            exposureEl.textContent = `₹${top3.toLocaleString()}`;
        } else if (exposureEl) {
            exposureEl.textContent = '₹0';
        }
        if (diversityEl) diversityEl.textContent = outstandingData.customerCount || 0;
        if (outTotalEl) outTotalEl.textContent = `₹${(outstandingData.total || 0).toLocaleString()}`;

        // Update Aging Buckets Chart
        const agingResp = await fetch('/ergon/finance/aging-buckets');
        if (!agingResp.ok) throw new Error('Aging API not available');
        const agingText = await agingResp.text();
        const agingData = agingText ? JSON.parse(agingText) : {};
        if (agingBucketsChart && agingData.labels) {
            agingBucketsChart.data.labels = agingData.labels;
            agingBucketsChart.data.datasets[0].data = agingData.data;
            agingBucketsChart.update();
        }
        
        // Update aging metrics
        const provisionEl = document.getElementById('provisionRequired');
        const recoveryEl = document.getElementById('recoveryRate');
        const qualityEl = document.getElementById('creditQuality');
        const agingTotalEl = document.getElementById('agingTotal');
        
        const agingTotal = agingData.data ? agingData.data.reduce((sum, val) => sum + val, 0) : 0;
        const criticalAmount = agingData.data ? agingData.data[3] || 0 : 0; // 90+ days
        
        if (provisionEl) provisionEl.textContent = `₹${Math.round(criticalAmount * 0.1).toLocaleString()}`; // 10% provision
        if (recoveryEl && agingTotal > 0) {
            const goodDebt = (agingData.data ? agingData.data[0] + agingData.data[1] : 0) || 0;
            recoveryEl.textContent = `${Math.round((goodDebt / agingTotal) * 100)}%`;
        } else if (recoveryEl) {
            recoveryEl.textContent = '100%';
        }
        if (qualityEl) {
            const riskRatio = agingTotal > 0 ? criticalAmount / agingTotal : 0;
            qualityEl.textContent = riskRatio > 0.2 ? 'Poor' : (riskRatio > 0.1 ? 'Fair' : 'Good');
        }
        if (agingTotalEl) agingTotalEl.textContent = `₹${agingTotal.toLocaleString()}`;
        
        // Update Payments Chart
        const paymentsResp = await fetch('/ergon/finance/visualization?type=payments');
        if (!paymentsResp.ok) throw new Error('Payments API not available');
        const paymentsText = await paymentsResp.text();
        const paymentsData = paymentsText ? JSON.parse(paymentsText) : {};
        if (paymentsChart) {
            if (paymentsData.labels && paymentsData.data) {
                paymentsChart.data.labels = paymentsData.labels;
                paymentsChart.data.datasets[0].data = paymentsData.data;
                paymentsChart.update();
            } else {
                paymentsChart.data.labels = ['No Data'];
                paymentsChart.data.datasets[0].data = [0];
                paymentsChart.update();
            }
        }
        
        // Update payment metrics from funnel data
        const velocityEl = document.getElementById('paymentVelocity');
        const accuracyEl = document.getElementById('forecastAccuracy');
        const conversionEl = document.getElementById('cashConversion');
        const paymentsTotalEl = document.getElementById('paymentsTotal');
        
        if (velocityEl) {
            const dailyVelocity = (funnel.paymentValue || 0) / 30; // Monthly average
            velocityEl.textContent = `₹${Math.round(dailyVelocity).toLocaleString()}/day`;
        }
        if (accuracyEl) accuracyEl.textContent = `${funnel.invoiceToPayment || 0}%`;
        if (conversionEl) {
            const conversionDays = data.totalInvoiceAmount > 0 ? Math.round((data.pendingInvoiceAmount / data.totalInvoiceAmount) * 30) : 0;
            conversionEl.textContent = `${conversionDays} days`;
        }
        if (paymentsTotalEl) paymentsTotalEl.textContent = `₹${(funnel.paymentValue || 0).toLocaleString()}`;
        
    } catch (error) {
        console.warn('Charts not available:', error.message);
        // Initialize charts with empty data
        if (quotationsChart) {
            quotationsChart.data.datasets[0].data = [0,0,0];
            quotationsChart.update();
        }
        if (purchaseOrdersChart) {
            purchaseOrdersChart.data.labels = ['No Data'];
            purchaseOrdersChart.data.datasets[0].data = [0];
            purchaseOrdersChart.update();
        }
    }
}

async function loadOutstandingInvoices() {
    try {
        const response = await fetch('/ergon/finance/outstanding-invoices');
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: Outstanding invoices API not available`);
        }
        
        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            throw new Error('Invalid JSON response from outstanding invoices API');
        }
        
        const tbody = document.querySelector('#outstandingTable tbody');
        if (data.invoices && data.invoices.length > 0) {
            tbody.innerHTML = data.invoices.map(invoice => `
                <tr class="${invoice.daysOverdue > 0 ? 'table-row--danger' : ''}">
                    <td>${invoice.invoice_number}</td>
                    <td>${invoice.customer_name}</td>
                    <td>${invoice.due_date}</td>
                    <td>₹${invoice.outstanding_amount.toLocaleString()}</td>
                    <td>${invoice.daysOverdue > 0 ? invoice.daysOverdue : '-'}</td>
                    <td><span class="badge ${invoice.daysOverdue > 0 ? 'badge--danger' : 'badge--warning'}">${invoice.status}</span></td>
                </tr>
            `).join('');
        } else {
            const message = data.message || 'No outstanding invoices';
            tbody.innerHTML = `<tr><td colspan="6" class="text-center">${message}</td></tr>`;
        }
        
        if (data.error) {
            console.warn('Outstanding invoices API error:', data.error);
        }
    } catch (error) {
        console.error('Failed to load outstanding invoices:', error);
        const tbody = document.querySelector('#outstandingTable tbody');
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Error loading data: ${error.message}</td></tr>`;
    }
}

// Load outstanding-by-customer and update chart
async function loadOutstandingByCustomer(limit = 10) {
    try {
        const resp = await fetch(`/ergon/finance/outstanding-by-customer?limit=${limit}`);
        const data = await resp.json();
        if (outstandingByCustomerChart && data.labels) {
            outstandingByCustomerChart.data.labels = data.labels;
            outstandingByCustomerChart.data.datasets[0].data = data.data;
            outstandingByCustomerChart.update();
        }
    } catch (err) {
        console.error('Failed to load outstanding by customer:', err);
    }
}

// (Server-side export used via /ergon/finance/export-outstanding)

async function loadRecentActivities(type = 'all') {
    try {
        // Use existing quotations endpoint as fallback until backend is implemented
        const response = await fetch('/ergon/finance/recent-quotations');
        if (!response.ok) {
            throw new Error('Recent activities API not available');
        }
        const activityText = await response.text();
        const data = activityText ? JSON.parse(activityText) : {};
        
        const container = document.getElementById('recentActivities');
        if (data.quotations && data.quotations.length > 0) {
            container.innerHTML = data.quotations.map(quote => `
                <div class="activity-item activity-item--quotation">
                    <div class="activity-icon">📝</div>
                    <div class="activity-content">
                        <div class="activity-title">${quote.quotation_number}</div>
                        <div class="activity-details">₹${quote.total_amount.toLocaleString()} - ${quote.customer_name}</div>
                        <div class="activity-meta">
                            <span class="activity-type">Quotation</span>
                            <span class="activity-date">Expires: ${quote.valid_until}</span>
                        </div>
                    </div>
                    <div class="activity-status activity-status--pending">Active</div>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<div class="activity-item"><div class="activity-loading">No recent activities</div></div>';
        }
    } catch (error) {
        console.error('Failed to load recent activities:', error);
        document.getElementById('recentActivities').innerHTML = '<div class="activity-item"><div class="activity-loading">No activities available</div></div>';
    }
}

function getActivityIcon(type) {
    const icons = {
        'quotation': '📝',
        'po': '🛒',
        'invoice': '💰',
        'payment': '💳'
    };
    return icons[type] || '📈';
}

function updateCashFlow(data) {
    const cashFlow = data.cashFlow || {};
    const funnel = data.conversionFunnel || {};
    
    document.getElementById('expectedInflow').textContent = `₹${(cashFlow.expectedInflow || 0).toLocaleString()}`;
    // Use funnel PO value for consistency
    document.getElementById('poCommitments').textContent = `₹${(funnel.poValue || 0).toLocaleString()}`;
    
    const netFlow = (cashFlow.expectedInflow || 0) - (funnel.poValue || 0);
    const netElement = document.getElementById('netCashFlow');
    netElement.textContent = `₹${netFlow.toLocaleString()}`;
    netElement.className = `flow-value ${netFlow >= 0 ? 'flow-positive' : 'flow-negative'}`;
}

function toggleView(module, viewType) {
    const viewContainer = document.getElementById(`${module}View`);
    const listView = viewContainer.querySelector('.data-list');
    const gridView = viewContainer.querySelector('.data-grid');
    const buttons = viewContainer.parentElement.querySelectorAll('.view-btn');
    
    // Update button states
    buttons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // Toggle views
    if (viewType === 'list') {
        listView.classList.add('active');
        gridView.classList.remove('active');
    } else {
        listView.classList.remove('active');
        gridView.classList.add('active');
        
        // Load grid data if not already loaded
        loadGridView(module);
    }
}



function loadGridView(module) {
    const gridContainer = document.getElementById(`${module}Grid`);
    
    // Copy list data to grid format
    const listContainer = document.getElementById(`${module}List` || `${module}Container`);
    const listItems = listContainer.querySelectorAll('.list-item, .form-group');
    
    let gridHtml = '<div class="grid-container">';
    
    listItems.forEach(item => {
        if (item.classList.contains('list-item')) {
            const title = item.querySelector('.list-title')?.textContent || 'N/A';
            const amount = item.querySelector('.list-amount')?.textContent || '';
            const customer = item.querySelector('.list-customer')?.textContent || '';
            const status = item.querySelector('.list-status')?.textContent || '';
            
            gridHtml += `
                <div class="grid-item">
                    <div class="grid-header">
                        <h4>${title}</h4>
                        <span class="grid-amount">${amount}</span>
                    </div>
                    <div class="grid-body">
                        <p>${customer}</p>
                        <span class="grid-status">${status}</span>
                    </div>
                </div>`;
        } else {
            // Handle form-group items
            const label = item.querySelector('.form-label')?.textContent || '';
            const text = item.querySelector('p')?.textContent || '';
            
            gridHtml += `
                <div class="grid-item">
                    <div class="grid-header">
                        <h4>${label}</h4>
                    </div>
                    <div class="grid-body">
                        <p>${text}</p>
                    </div>
                </div>`;
        }
    });
    
    gridHtml += '</div>';
    gridContainer.innerHTML = gridHtml;
}

async function loadTables() {
    try {
        const response = await fetch('/ergon/finance/tables');
        const data = await response.json();
        
        const container = document.getElementById('tablesContainer');
        const select = document.getElementById('tableSelect');
        
        if (data.tables && data.tables.length > 0) {
            let html = '';
            select.innerHTML = '<option value="">Select Table</option>';
            
            data.tables.forEach(table => {
                const displayName = table.table_name.replace('finance_', '');
                const lastSync = table.last_sync ? new Date(table.last_sync).toLocaleDateString() : 'Never';
                
                html += `
                    <div class="form-group">
                        <div class="form-label">📊 ${displayName.charAt(0).toUpperCase() + displayName.slice(1)}</div>
                        <p>${table.record_count.toLocaleString()} records</p>
                        <small>Last sync: ${lastSync}</small>
                    </div>`;
                    
                select.innerHTML += `<option value="${table.table_name}">${displayName.charAt(0).toUpperCase() + displayName.slice(1)}</option>`;
            });
            
            container.innerHTML = html;
        } else {
            container.innerHTML = `
                <div class="form-group">
                    <div class="form-label">📋 No Data</div>
                    <p>No finance tables found. Click sync to load data.</p>
                </div>`;
        }
    } catch (error) {
        container.innerHTML = `
            <div class="form-group">
                <div class="form-label">❌ Error</div>
                <p>Failed to load table information</p>
            </div>`;
    }
}

async function loadTableData() {
    const table = document.getElementById('tableSelect').value;
    
    if (!table) {
        alert('Please select a table');
        return;
    }
    
    try {
        const response = await fetch(`/ergon/finance/data?table=${table}&limit=100`);
        const data = await response.json();
        
        if (data.error) {
            showError(data.error);
            return;
        }
        
        renderTable(data.data, data.columns);
    } catch (error) {
        showError('Failed to load data: ' + error.message);
    }
}

function renderTable(data, columns) {
    const container = document.getElementById('dataContainer');
    
    if (!data || data.length === 0) {
        container.innerHTML = `
            <div class="form-group">
                <div class="form-label">📋 No Data</div>
                <p>No records found in this table</p>
            </div>`;
        return;
    }
    
    // Show summary first
    let html = `
        <div class="overview-summary">
            <div class="summary-stat">
                <span class="summary-number">📊 ${data.length}</span>
                <span class="summary-label">Records</span>
            </div>
            <div class="summary-stat">
                <span class="summary-number">📋 ${columns.length}</span>
                <span class="summary-label">Columns</span>
            </div>
        </div>`;
    
    // Add table
    html += '<div class="table-responsive"><table class="table">';
    
    html += '<thead><tr>';
    columns.slice(0, 6).forEach(col => html += `<th>${col}</th>`);
    if (columns.length > 6) html += '<th>...</th>';
    html += '</tr></thead><tbody>';
    
    data.slice(0, 10).forEach(row => {
        html += '<tr>';
        columns.slice(0, 6).forEach(col => {
            let value = row[col];
            if (typeof value === 'number' && col.includes('amount')) {
                value = '₹' + value.toLocaleString();
            }
            html += `<td>${value !== null ? String(value).substring(0, 50) : ''}</td>`;
        });
        if (columns.length > 6) html += '<td>...</td>';
        html += '</tr>';
    });
    
    html += '</tbody></table></div>';
    
    if (data.length > 10) {
        html += `<div class="form-group"><small class="text-muted">Showing first 10 of ${data.length} records</small></div>`;
    }
    
    container.innerHTML = html;
}

function showError(message) {
    document.getElementById('dataContainer').innerHTML = `
        <div class="form-group">
            <div class="form-label">❌ Error</div>
            <p>${message}</p>
        </div>`;
}

function exportChart(type) {
    window.open(`/ergon/finance/export?type=${type}`, '_blank');
}

function exportTable(type) {
    window.open(`/ergon/finance/export-table?type=${type}`, '_blank');
}

function exportDashboard() {
    window.open('/ergon/finance/export-dashboard', '_blank');
}



async function loadCompanyPrefix() {
    try {
        const response = await fetch('/ergon/finance/company-prefix');
        const data = await response.json();
        const currentPrefix = data.prefix || '';
        
        document.getElementById('companyPrefix').value = currentPrefix;
        return currentPrefix;
    } catch (error) {
        console.error('Failed to load company prefix:', error);
        return '';
    }
}





async function updateCompanyPrefix() {
    const input = document.getElementById('companyPrefix');
    const prefix = input.value.trim().toUpperCase();
    
    const btn = document.getElementById('updatePrefixBtn');
    btn.disabled = true;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<span class="btn__icon">⏳</span>';
    
    try {
        const formData = new FormData();
        formData.append('company_prefix', prefix);
        
        const response = await fetch('/ergon/finance/company-prefix', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            if (prefix) {
                showNotification(`Filtering by company: ${result.prefix}`, 'success');
            } else {
                showNotification('Showing all companies', 'success');
            }
            
            await loadCustomers();
            
            // Reset customer filter
            const customerSelect = document.getElementById('customerFilter');
            if (customerSelect) customerSelect.value = '';
            
            loadDashboardData();
        } else {
            showNotification('Failed to update prefix: ' + (result.error || 'Unknown error'), 'error');
        }
    } catch (error) {
        showNotification('Failed to update prefix: ' + error.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

function filterByDate() {
    const days = document.getElementById('dateFilter').value;
    if (days !== 'all') {
        loadDashboardData();
    }
}

function filterByCustomer() {
    loadDashboardData();
}

async function loadCustomers() {
    const select = document.getElementById('customerFilter');
    const loader = document.getElementById('customerLoader');
    try {
        if (loader) { loader.style.display = 'inline-block'; loader.innerHTML = '<span class="mini-spinner" aria-hidden="true"></span>'; }
        if (select) { select.disabled = true; select.innerHTML = '<option value="">Loading customers...</option>'; }

        const response = await fetch('/ergon/finance/customers');
        const data = await response.json();

        if (select) select.innerHTML = '<option value="">All Customers</option>';
        if (data.customers) {
            data.customers.forEach(customer => {
                select.innerHTML += `<option value="${customer.id}">${customer.display}</option>`;
            });
        }
    } catch (error) {
        console.error('Failed to load customers:', error);
        if (select) select.innerHTML = '<option value="">Failed to load</option>';
    } finally {
        if (loader) loader.style.display = 'none';
        if (select) select.disabled = false;
    }
}

async function refreshDashboardStats() {
    try {
        const response = await fetch('/ergon/finance/refresh-stats');
        const result = await response.json();
        
        if (result.success) {
            showNotification('Dashboard stats refreshed successfully!', 'success');
            loadDashboardData();
        } else {
            showNotification('Failed to refresh stats: ' + (result.error || 'Unknown error'), 'error');
        }
    } catch (error) {
        showNotification('Failed to refresh stats: ' + error.message, 'error');
    }
}
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/dashboard.php';
?>

<style>
.data-list {
    margin-top: 1rem;
    max-height: 300px;
    overflow-y: auto;
}

.list-item {
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    margin-bottom: 0.5rem;
    background: var(--bg-primary);
    transition: all 0.2s ease;
}

.list-item:hover {
    box-shadow: var(--shadow-sm);
    transform: translateY(-1px);
}

.list-item--warning {
    border-left: 4px solid var(--warning);
    background: rgba(217, 119, 6, 0.05);
}

.list-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.list-title {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 0.9rem;
}

.list-amount {
    font-weight: 700;
    color: var(--primary);
    font-size: 0.9rem;
}

.list-details {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    font-size: 0.8rem;
}

.list-customer {
    color: var(--text-secondary);
}

.list-status {
    color: var(--text-secondary);
    background: var(--bg-secondary);
    padding: 0.2rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
}

.list-meta {
    display: flex;
    justify-content: space-between;
    font-size: 0.75rem;
    color: var(--text-muted);
}

.list-outstanding {
    margin-top: 0.5rem;
    padding: 0.25rem 0.5rem;
    background: rgba(220, 38, 38, 0.1);
    color: var(--error);
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

/* View Toggle Styles */
.view-toggle {
    display: flex;
    background: var(--bg-secondary);
    border-radius: 6px;
    padding: 2px;
    margin-right: 0.5rem;
}

.view-btn {
    padding: 0.25rem 0.75rem;
    border: none;
    background: transparent;
    color: var(--text-secondary);
    border-radius: 4px;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.view-btn.active {
    background: var(--primary);
    color: white;
}

/* Conversion Funnel */
.funnel-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 0;
}

.funnel-stage {
    text-align: center;
    flex: 1;
    padding: 1rem;
    background: var(--bg-secondary);
    border-radius: 8px;
    transition: all 0.3s ease;
}

.funnel-stage:hover {
    background: var(--primary-light);
    transform: translateY(-2px);
}

.funnel-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 0.25rem;
}

.funnel-label {
    font-size: 0.8rem;
    color: var(--text-secondary);
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.funnel-value {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.funnel-conversion {
    font-size: 0.75rem;
    color: var(--success);
    font-weight: 600;
}

.funnel-arrow {
    font-size: 1.5rem;
    color: var(--text-muted);
    margin: 0 0.5rem;
}

/* Chart Summary */
.chart-summary {
    display: flex;
    justify-content: space-around;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
}

.summary-item {
    text-align: center;
}

.summary-label {
    font-size: 0.75rem;
    color: var(--text-secondary);
    margin-right: 0.5rem;
}

.summary-value {
    font-weight: 600;
    color: var(--primary);
}

/* Highlight Card */
.highlight-card {
    background: var(--bg-secondary);
    padding: 0.75rem;
    border-radius: 6px;
    margin-top: 1rem;
    text-align: center;
}

.highlight-label {
    font-size: 0.75rem;
    color: var(--text-secondary);
    margin-bottom: 0.25rem;
}

.highlight-value {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--primary);
}

/* Cash Flow */
.cash-flow-summary {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.flow-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--border-color);
}

.flow-item:last-child {
    border-bottom: none;
    font-weight: 600;
}

.flow-label {
    font-size: 0.9rem;
    color: var(--text-secondary);
}

.flow-value {
    font-weight: 600;
    font-size: 1rem;
}

.flow-positive {
    color: var(--success);
}

.flow-negative {
    color: var(--error);
}

/* Table Enhancements */
.table-row--danger {
    background: rgba(220, 38, 38, 0.05);
}

.table-row--danger:hover {
    background: rgba(220, 38, 38, 0.1);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 2rem;
    color: var(--text-muted);
}

.empty-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state h3 {
    margin-bottom: 0.5rem;
    color: var(--text-secondary);
}

/* Full Width Card */
.card--full-width {
    grid-column: 1 / -1;
}

/* 2-Column Grid Layout */
.dashboard-grid--2-col {
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

/* Chart Cards */
.chart-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 0.75rem;
    height: 220px;
    width: 100%;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.chart-card__header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
    height: 50px;
    flex-shrink: 0;
}

.chart-card__info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.chart-card__icon {
    font-size: 1.25rem;
}

.chart-card__title {
    font-size: 0.8rem;
    color: var(--text-secondary);
    font-weight: 500;
}

.chart-card__value {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text-primary);
}

.chart-card__trend {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--success);
    background: rgba(16, 185, 129, 0.1);
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
}

.chart-card__chart {
    height: 100px;
    width: 100%;
    position: relative;
    flex-shrink: 0;
    overflow: hidden;
}

.chart-card__chart canvas {
    max-width: 100% !important;
    max-height: 100px !important;
    width: 100% !important;
    height: 100px !important;
}

.chart-card__subtitle {
    font-size: 0.65rem;
    color: var(--text-secondary);
    font-weight: 400;
}

.chart-legend {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 0.25rem;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.2rem;
    font-size: 0.6rem;
    color: var(--text-secondary);
}

.legend-color {
    width: 8px;
    height: 8px;
    border-radius: 2px;
}

.chart-card__meta {
    display: flex;
    justify-content: space-between;
    font-size: 0.6rem;
    color: var(--text-secondary);
    margin-top: 0.5rem;
    padding-top: 0.5rem;
    border-top: 1px solid var(--border-color);
    height: 30px;
    flex-shrink: 0;
}

.meta-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.1rem;
}

.meta-item span {
    font-size: 0.55rem;
    opacity: 0.8;
}

.meta-item strong {
    font-size: 0.65rem;
    font-weight: 600;
}

.kpi-card__description {
    font-size: 0.7rem;
    color: var(--text-muted);
    font-weight: 400;
    font-style: italic;
    margin-top: 0.5rem;
    line-height: 1.2;
    opacity: 0.8;
}

.kpi-card__details {
    display: flex;
    justify-content: space-between;
    margin-top: 0.5rem;
    padding-top: 0.5rem;
    border-top: 1px solid var(--border-color);
}

.detail-item {
    font-size: 0.7rem;
    color: var(--text-secondary);
}

.detail-item span {
    font-weight: 600;
    color: var(--text-primary);
}

/* Dashboard Header */
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 8px;
}

.dashboard-header__title h1 {
    margin: 0 0 0.25rem 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary);
}

.dashboard-header__title p {
    margin: 0;
    font-size: 0.9rem;
    color: var(--text-secondary);
}

.dashboard-header__actions {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
}

.action-group {
    display: flex;
    gap: 0.5rem;
}

.filter-group {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.input-group {
    display: blockruby;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    overflow: hidden;
}

.input-group .form-control {
    border: none;
    border-radius: 0;
    margin: 0;
}

.input-group .btn {
    border-radius: 0;
    border-left: 1px solid var(--border-color);
}

.btn--sm {
    padding: 0.5rem 0.75rem;
    font-size: 0.8rem;
}

.btn__icon {
    margin-right: 0.25rem;
}

.form-control--sm {
    padding: 0.5rem;
    font-size: 0.8rem;
    min-width: 120px;
}

@media (max-width: 768px) {
    .dashboard-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .dashboard-header__actions {
        flex-direction: column;
        width: 100%;
    }
    
    .action-group,
    .filter-group {
        flex-wrap: wrap;
    }
}

/* Stat-card styles moved to assets/css/ergon.css */

/* Data View Styles */
.data-view {
    position: relative;
}

.data-list,
.data-grid {
    display: none;
}

.data-list.active,
.data-grid.active {
    display: block;
}

/* Grid Layout */
.grid-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.grid-item {
    padding: 1rem;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: var(--bg-primary);
    transition: all 0.2s ease;
}

.grid-item:hover {
    box-shadow: var(--shadow-sm);
    transform: translateY(-2px);
}

.grid-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--border-color);
}

.grid-header h4 {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-primary);
}

.grid-amount {
    font-weight: 700;
    color: var(--primary);
    font-size: 0.9rem;
}

.grid-body p {
    margin: 0 0 0.5rem 0;
    color: var(--text-secondary);
    font-size: 0.8rem;
}

.grid-status {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    background: var(--bg-secondary);
    color: var(--text-secondary);
    border-radius: 12px;
    font-size: 0.75rem;
}

/* Activity Filters */
.activity-filters {
    display: flex;
    gap: 0.25rem;
}

.filter-btn {
    padding: 0.25rem 0.5rem;
    border: 1px solid var(--border-color);
    background: var(--bg-secondary);
    color: var(--text-secondary);
    border-radius: 4px;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.filter-btn.active {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

/* Activity Items */
.activity-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    margin-bottom: 0.5rem;
    background: var(--bg-primary);
    transition: all 0.2s ease;
}

.activity-item:hover {
    box-shadow: var(--shadow-sm);
    transform: translateY(-1px);
}

.activity-icon {
    font-size: 1.25rem;
    flex-shrink: 0;
}

.activity-content {
    flex: 1;
}

.activity-title {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.activity-details {
    color: var(--text-secondary);
    font-size: 0.8rem;
    margin-bottom: 0.25rem;
}

.activity-meta {
    display: flex;
    gap: 0.5rem;
    font-size: 0.7rem;
    color: var(--text-muted);
}

.activity-type {
    background: var(--bg-secondary);
    padding: 0.1rem 0.4rem;
    border-radius: 8px;
}

.activity-status {
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
    flex-shrink: 0;
}

.activity-status--pending {
    background: rgba(217, 119, 6, 0.1);
    color: var(--warning);
}

.activity-status--completed {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
}

.activity-loading {
    text-align: center;
    color: var(--text-muted);
    font-style: italic;
}

@media (max-width: 768px) {
    .funnel-container {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .funnel-arrow {
        transform: rotate(90deg);
        margin: 0.5rem 0;
    }
    
    .chart-summary {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .summary-item {
        display: flex;
        justify-content: space-between;
    }
    
    .cash-flow-summary {
        gap: 0.5rem;
    }
    
    .flow-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
    
    .activity-filters {
        flex-wrap: wrap;
    }
}
</style>

