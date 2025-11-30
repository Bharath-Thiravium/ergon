<?php 
 $title = 'Finance Dashboard';
 $active_page = 'finance';
 ob_start(); 
 // Finance-specific styles are merged into `assets/css/ergon-overrides.css`

 // Clear any error/success messages to prevent popup alerts
 if (isset($_GET['error'])) {
     unset($_GET['error']);
 }
 if (isset($_GET['success'])) {
     unset($_GET['success']);
 }
?>

<div class="container-fluid">
    <!-- Header Actions -->
    <div class="page-header-modern">
        <div class="page-header-content">
            <div class="page-title-section">
                <h1 class="page-title">Finance Dashboard</h1>
                <span class="page-subtitle">Real-time financial insights and analytics</span>
            </div>
            <div class="page-actions-section">
                <button id="syncBtn" class="btn btn--primary btn--sm">
                    <span class="btn__icon">🔄</span>
                    <span class="btn__text">Sync Data</span>
                </button>
                <div class="filter-controls">
                    <div class="form-group">
                        <input type="text" id="companyPrefix" class="form-control" placeholder="Prefix (SE, BK)" list="prefixSuggestions" maxlength="10">
                        <datalist id="prefixSuggestions"></datalist>
                        <div id="letterSelectors" class="letter-selectors"></div>
                    </div>
                    <select id="dateFilter" class="form-control">
                        <option value="all">All Time</option>
                        <option value="30">30 Days</option>
                        <option value="90">90 Days</option>
                        <option value="365">1 Year</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Top-Level KPI Cards -->
    <div class="dashboard-grid" id="kpiCardsContainer">
        <!-- KPI cards will be dynamically generated -->
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
                                <th>Shipping Address</th>
                                <th>Invoice Date</th>
                                <th>Total Amount</th>
                                <th>Outstanding Amount</th>
                                <th>Days Overdue</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="8" class="text-center">Loading outstanding invoices...</td>
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
                    <button class="filter-btn active" data-type="all" onclick="loadRecentActivities('all')" title="All Activities">All</button>
                    <button class="filter-btn" data-type="quotation" onclick="loadRecentActivities('quotation')" title="Quotations">📝</button>
                    <button class="filter-btn" data-type="purchase_order" onclick="loadRecentActivities('purchase_order')" title="Purchase Orders">🛒</button>
                    <button class="filter-btn" data-type="invoice" onclick="loadRecentActivities('invoice')" title="Invoices">💰</button>
                    <button class="filter-btn" data-type="payment" onclick="loadRecentActivities('payment')" title="Payments">💳</button>
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

    <!-- Charts Section (Moved to Bottom) -->
    <div class="dashboard-grid dashboard-grid--2-col">
        <?php include __DIR__ . '/dashboard-charts.php'; ?>
    </div>
</div>

<link rel="stylesheet" href="/ergon/views/finance/funnel-styles.css">
<script src="/ergon/views/finance/dashboard-charts.js"></script>
<script src="/ergon/views/finance/cashflow-listener.js"></script>
<script src="/ergon/views/finance/test-svg-tooltips.js"></script>
<script>

// Notification function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification--${type}`;
    // Use CSS classes instead of inline styles to avoid parsing errors
    notification.className = `notification notification--${type}`;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.padding = '12px 20px';
    notification.style.borderRadius = '6px';
    notification.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
    notification.style.zIndex = '10000';
    notification.style.maxWidth = '400px';
    notification.style.fontSize = '14px';
    
    // Set colors based on type
    if (type === 'error') {
        notification.style.background = '#f8d7da';
        notification.style.border = '1px solid #f5c6cb';
        notification.style.color = '#721c24';
    } else if (type === 'success') {
        notification.style.background = '#d4edda';
        notification.style.border = '1px solid #c3e6cb';
        notification.style.color = '#155724';
    } else if (type === 'warning') {
        notification.style.background = '#fff3cd';
        notification.style.border = '1px solid #ffeaa7';
        notification.style.color = '#856404';
    } else {
        notification.style.background = '#d1ecf1';
        notification.style.border = '1px solid #bee5eb';
        notification.style.color = '#0c5460';
    }
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 5000);
}

window.addEventListener('load', function() {
    initKPICards();
    
    const syncBtn = document.getElementById('syncBtn');
    if (syncBtn) {
        syncBtn.addEventListener('click', syncFinanceData);
    }
    

    
    const prefixInput = document.getElementById('companyPrefix');
    if (prefixInput) {
        prefixInput.addEventListener('input', function() {
            const value = this.value.trim().toUpperCase();
            localStorage.setItem('financePrefix', value);
            if (value.length >= 2) {
                updateLetterSelectors(value);
                loadAllStatCardsData();
                loadCustomersForFunnel();
                updateConversionFunnel();
            }
            debounce(updateCompanyPrefix, 500)();
        });
        prefixInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                localStorage.setItem('financePrefix', this.value.trim().toUpperCase());
                updateCompanyPrefix();
                loadAllStatCardsData();
                loadCustomersForFunnel();
                updateConversionFunnel();
            }
        });
    }

    document.getElementById('dateFilter').addEventListener('change', filterByDate);
    const customerFilter = document.getElementById('customerFilter');
    if (customerFilter) {
        customerFilter.addEventListener('change', updateConversionFunnel);
    }
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
        loadAllStatCardsData();
        loadCustomersForFunnel();
        loadCashFlow();
    });
});

// KPI Card Configuration
const KPI_CARDS_CONFIG = [
    {
        id: 'totalInvoiceAmount',
        icon: '💰',
        label: 'Total Invoice Amount',
        description: 'Total Revenue Generated',
        variant: 'success',
        trendId: 'invoiceTrend',
        details: [
            { label: 'Count', valueId: 'totalInvoiceCount' },
            { label: 'Avg', valueId: 'avgInvoiceAmount' }
        ]
    },
    {
        id: 'invoiceReceived',
        icon: '✅',
        label: 'Amount Received',
        description: 'Successfully Collected Revenue',
        variant: 'success',
        trendId: 'receivedTrend',
        details: [
            { label: 'Collection Rate', valueId: 'collectionRateKPI' },
            { label: 'Paid Invoices', valueId: 'paidInvoiceCount' }
        ]
    },
    {
        id: 'pendingInvoiceAmount',
        icon: '⏳',
        label: 'Outstanding Amount',
        description: 'Taxable Amount Pending (No GST)',
        variant: 'warning',
        trendId: 'pendingTrend',
        details: [
            { label: 'Pending Invoices', valueId: 'pendingInvoicesCount' },
            { label: 'Customers', valueId: 'customersPendingCount' },
            { label: 'Overdue Amount', valueId: 'overdueAmount' }
        ]
    },
    {
        id: 'pendingGSTAmount',
        icon: '🏛️',
        label: 'GST Liability',
        description: 'Tax Liability on Outstanding Invoices Only',
        variant: 'info',
        trendId: 'gstTrend',
        details: [
            { label: 'IGST', valueId: 'igstLiability' },
            { label: 'CGST+SGST', valueId: 'cgstSgstTotal' }
        ]
    },
    {
        id: 'pendingPOValue',
        icon: '🛒',
        label: 'PO Commitments',
        description: 'Total Value of All Purchase Orders',
        variant: 'primary',
        trendId: 'poTrend',
        details: [
            { label: 'Open POs', valueId: 'openPOCount' },
            { label: 'Closed POs', valueId: 'closedPOCount' }
        ]
    },
    {
        id: 'claimableAmount',
        icon: '💸',
        label: 'Claimable Amount',
        description: 'Total Invoice Amount - Payments Received',
        variant: 'secondary',
        trendId: 'claimableTrend',
        details: [
            { label: 'Claimable POs', valueId: 'claimablePOCount' },
            { label: 'Claim Rate', valueId: 'claimRate' }
        ]
    }
];

function initKPICards() {
    const container = document.getElementById('kpiCardsContainer');
    if (!container) return;
    
    container.innerHTML = KPI_CARDS_CONFIG.map(card => createKPICardHTML(card)).join('');
}

function createKPICardHTML(config) {
    const detailsHTML = config.details.map(detail => 
        `<div class="detail-item">${detail.label}: <span id="${detail.valueId}">${detail.label.includes('Rate') || detail.label.includes('%') ? '0%' : (detail.label.includes('Amount') ? '₹0' : '0')}</span></div>`
    ).join('');
    
    return `
        <div class="kpi-card kpi-card--${config.variant}">
            <div class="kpi-card__header">
                <div class="kpi-card__icon">${config.icon}</div>
                <div class="kpi-card__trend" id="${config.trendId}">↗ +0%</div>
            </div>
            <div class="kpi-card__value" id="${config.id}">₹0</div>
            <div class="kpi-card__label">${config.label}</div>
            <div class="kpi-card__description">${config.description}</div>
            <div class="kpi-card__details">
                ${detailsHTML}
            </div>
        </div>
    `;
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
            // Use individual style properties to avoid CSS parsing errors
        modal.style.position = 'fixed';
        modal.style.top = '50%';
        modal.style.left = '50%';
        modal.style.transform = 'translate(-50%, -50%)';
        modal.style.background = 'white';
        modal.style.padding = '20px';
        modal.style.borderRadius = '8px';
        modal.style.boxShadow = '0 4px 20px rgba(0,0,0,0.3)';
        modal.style.maxWidth = '80%';
        modal.style.maxHeight = '80%';
        modal.style.overflow = 'auto';
        modal.style.zIndex = '10000';
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
        const response = await fetch('../src/api/simple_api.php?action=dashboard&prefix=BKGE');
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
        updateKPICards(data);
        

        
        // Update conversion funnel
        if (data.conversionFunnel) {
            updateConversionFunnel(data.conversionFunnel);
        }
        
        // Update cash flow
        if (data.cashFlow) {
            updateCashFlow(data.cashFlow);
        }
        
        if (data.message) {
            showNotification(data.message, 'info');
        }
        
        // Show ETL source information
        if (data.source === 'etl_dashboard_stats') {
            console.log('✅ Using ETL-optimized analytics from consolidated SQL table');
            console.log('📊 Data source: finance_consolidated → dashboard_stats');
        } else if (data.source === 'empty') {
            // showNotification('💡 ETL Tip: Click "Sync Data" to run the ETL process and populate analytics', 'info');
        }
        
        // Load other data (placeholder functions)
        loadOutstandingInvoices();
        loadRecentActivities();
        // Note: Other chart functions disabled until APIs are implemented
        
    } catch (error) {
        console.error('Dashboard data error:', error);
        showNotification('Failed to load dashboard data', 'error');
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

function syncFinanceData() {
    const btn = document.getElementById('syncBtn');
    if (!btn) return;
    
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="btn__icon">⏳</span><span class="btn__text">Syncing...</span>';
    
    fetch('/ergon/src/api/sync.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification('✅ ' + result.message, 'success');
            setTimeout(() => loadAllStatCardsData(), 500);
        } else {
            showNotification('❌ Sync failed: ' + result.message, 'error');
        }
    })
    .catch(error => {
        showNotification('❌ Sync failed: ' + error.message, 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}

async function loadDashboardData() {
    // Placeholder function - APIs not implemented yet
    console.log('Dashboard data loading disabled - APIs not implemented');
    
    // Update with empty data to prevent UI breaking
    updateKPICards({});
    updateConversionFunnel({});
    updateCashFlow({});
    
    // Load placeholder data
    loadOutstandingInvoices();
    loadRecentActivities();
    
    showNotification('Dashboard APIs not implemented yet. Only sync functionality is available.', 'info');
}

function updateKPICards(data) {
    const funnel = data.conversionFunnel || {};
    
    // Update main KPI values using the configuration
    const kpiUpdates = {
        totalInvoiceAmount: data.totalInvoiceAmount || 0,
        invoiceReceived: data.invoiceReceived || 0,
        pendingInvoiceAmount: data.outstanding_amount || data.outstandingAmount || data.pendingInvoiceAmount || 0,
        pendingGSTAmount: data.gstLiability || data.pendingGSTAmount || 0,
        pendingPOValue: data.pendingPOValue || funnel.poValue || 0,
        claimableAmount: data.claimable_amount || data.claimableAmount || 0
    };
    
    // Update main values
    Object.entries(kpiUpdates).forEach(([id, value]) => {
        updateKPIValue(id, value);
    });
    
    // Update detail values
    updateKPIDetail('totalInvoiceCount', funnel.invoices || 0);
    updateKPIDetail('avgInvoiceAmount', funnel.invoices > 0 ? Math.round((data.totalInvoiceAmount || 0) / funnel.invoices) : 0, true);
    
    updateKPIDetail('collectionRateKPI', data.totalInvoiceAmount > 0 ? Math.round((data.invoiceReceived / data.totalInvoiceAmount) * 100) : 0, false, '%');
    updateKPIDetail('paidInvoiceCount', funnel.payments || 0);
    
    updateKPIDetail('pendingInvoicesCount', data.pending_invoices || data.pendingInvoices || 0);
    updateKPIDetail('customersPendingCount', data.customers_pending || data.customersPending || 0);
    updateKPIDetail('overdueAmount', data.overdue_amount || data.overdueAmount || 0, true);
    
    updateKPIDetail('igstLiability', data.igstLiability || 0, true);
    updateKPIDetail('cgstSgstTotal', data.cgstSgstTotal || 0, true);
    
    updateKPIDetail('openPOCount', data.openPOCount || 0);
    updateKPIDetail('closedPOCount', data.closedPOCount || 0);
    
    updateKPIDetail('claimablePOCount', data.claimable_pos || data.claimablePOCount || data.claimablePos || 0);
    updateKPIDetail('claimRate', Math.round(data.claim_rate || data.claimRate || 0), false, '%');
    
    // Update trends
    updateKPITrend('pendingTrend', data.outstanding_percentage || data.outstandingPercentage || 0, '%');
    updateKPITrend('claimableTrend', data.claim_rate || data.claimRate || 0, '%');
}

function updateKPIValue(elementId, value) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = `₹${Number(value).toLocaleString()}`;
    }
}

function updateKPIDetail(elementId, value, isCurrency = false, suffix = '') {
    const element = document.getElementById(elementId);
    if (element) {
        let displayValue = value;
        if (isCurrency) {
            displayValue = `₹${Number(value).toLocaleString()}`;
        } else if (suffix) {
            displayValue = `${value}${suffix}`;
        }
        element.textContent = displayValue;
    }
}

function updateKPITrend(elementId, value, suffix = '') {
    const element = document.getElementById(elementId);
    if (element) {
        const displayValue = `${Math.round(value)}${suffix}`;
        element.textContent = displayValue;
        
        // Update trend direction
        if (value > 0) {
            element.textContent = `↗ +${displayValue}`;
        } else if (value < 0) {
            element.textContent = `↘ ${displayValue}`;
        } else {
            element.textContent = `— ${displayValue}`;
        }
    }
}

async function updateConversionFunnel(data) {
    try {
        const response = await fetch('../src/api/simple_api.php?action=funnel-containers&prefix=BKGE');
        const funnelData = await response.json();
        
        if (funnelData.success && funnelData.containers) {
            const containers = funnelData.containers;
            
            // Container 1 - Quotations
            const container1 = containers.container1 || {};
            const quotationsEl = document.getElementById('funnelQuotations');
            const quotationValueEl = document.getElementById('funnelQuotationValue');
            if (quotationsEl) quotationsEl.textContent = container1.quotations_count || 0;
            if (quotationValueEl) quotationValueEl.textContent = `₹${(container1.quotations_total_value || 0).toLocaleString()}`;
            
            // Container 2 - Purchase Orders
            const container2 = containers.container2 || {};
            const posEl = document.getElementById('funnelPOs');
            const poValueEl = document.getElementById('funnelPOValue');
            const quotationToPOEl = document.getElementById('quotationToPO');
            if (posEl) posEl.textContent = container2.po_count || 0;
            if (poValueEl) poValueEl.textContent = `₹${(container2.po_total_value || 0).toLocaleString()}`;
            if (quotationToPOEl) quotationToPOEl.textContent = `${container2.po_conversion_rate || 0}%`;
            
            // Container 3 - Invoices
            const container3 = containers.container3 || {};
            const invoicesEl = document.getElementById('funnelInvoices');
            const invoiceValueEl = document.getElementById('funnelInvoiceValue');
            const poToInvoiceEl = document.getElementById('poToInvoice');
            if (invoicesEl) invoicesEl.textContent = container3.invoice_count || 0;
            if (invoiceValueEl) invoiceValueEl.textContent = `₹${(container3.invoice_total_value || 0).toLocaleString()}`;
            if (poToInvoiceEl) poToInvoiceEl.textContent = `${container3.invoice_conversion_rate || 0}%`;
            
            // Container 4 - Payments
            const container4 = containers.container4 || {};
            const paymentsEl = document.getElementById('funnelPayments');
            const paymentValueEl = document.getElementById('funnelPaymentValue');
            const invoiceToPaymentEl = document.getElementById('invoiceToPayment');
            if (paymentsEl) paymentsEl.textContent = container4.payment_count || 0;
            if (paymentValueEl) paymentValueEl.textContent = `₹${(container4.total_payment_received || 0).toLocaleString()}`;
            if (invoiceToPaymentEl) invoiceToPaymentEl.textContent = `${container4.payment_conversion_rate || 0}%`;
        }
    } catch (error) {
        console.warn('Funnel data not available:', error.message);
        // Use safe fallback data
        const funnel = data.conversionFunnel || {};
        const quotationsEl = document.getElementById('funnelQuotations');
        const quotationValueEl = document.getElementById('funnelQuotationValue');
        const posEl = document.getElementById('funnelPOs');
        const poValueEl = document.getElementById('funnelPOValue');
        const quotationToPOEl = document.getElementById('quotationToPO');
        const invoicesEl = document.getElementById('funnelInvoices');
        const invoiceValueEl = document.getElementById('funnelInvoiceValue');
        const poToInvoiceEl = document.getElementById('poToInvoice');
        const paymentsEl = document.getElementById('funnelPayments');
        const paymentValueEl = document.getElementById('funnelPaymentValue');
        const invoiceToPaymentEl = document.getElementById('invoiceToPayment');
        
        if (quotationsEl) quotationsEl.textContent = funnel.quotations || 0;
        if (quotationValueEl) quotationValueEl.textContent = `₹${(funnel.quotationValue || 0).toLocaleString()}`;
        if (posEl) posEl.textContent = funnel.purchaseOrders || 0;
        if (poValueEl) poValueEl.textContent = `₹${(funnel.poValue || 0).toLocaleString()}`;
        if (quotationToPOEl) quotationToPOEl.textContent = `${funnel.quotationToPO || 0}%`;
        if (invoicesEl) invoicesEl.textContent = funnel.invoices || 0;
        if (invoiceValueEl) invoiceValueEl.textContent = `₹${(funnel.invoiceValue || 0).toLocaleString()}`;
        if (poToInvoiceEl) poToInvoiceEl.textContent = `${funnel.poToInvoice || 0}%`;
        if (paymentsEl) paymentsEl.textContent = funnel.payments || 0;
        if (paymentValueEl) paymentValueEl.textContent = `₹${(funnel.paymentValue || 0).toLocaleString()}`;
        if (invoiceToPaymentEl) invoiceToPaymentEl.textContent = `${funnel.invoiceToPayment || 0}%`;
    }
}



async function loadOutstandingInvoices() {
    try {
        const prefix = document.getElementById('companyPrefix').value;
        if (!prefix) return;
        
        const response = await fetch(`/ergon/src/api/outstanding.php?prefix=${encodeURIComponent(prefix)}&limit=20`, {
            signal: AbortSignal.timeout(5000)
        }).catch(e => null);
        if (!response || !response.ok) throw new Error('Outstanding API unavailable');
        const result = await response.json();
        
        const tbody = document.querySelector('#outstandingTable tbody');
        
        if (result.success && result.data.length > 0) {
            tbody.innerHTML = result.data.map(invoice => `
                <tr class="${invoice.status === 'Overdue' ? 'table-row--danger' : ''}">
                    <td>${invoice.invoice_number}</td>
                    <td>${invoice.customer_name}</td>
                    <td><small>📍 ${invoice.shipping_address || 'N/A'}</small></td>
                    <td>${invoice.invoice_date}</td>
                    <td>₹${parseFloat(invoice.total_amount).toLocaleString()}</td>
                    <td>₹${parseFloat(invoice.outstanding_amount).toLocaleString()}</td>
                    <td>${invoice.days_overdue > 0 ? invoice.days_overdue + ' days' : '-'}</td>
                    <td><span class="list-status">${invoice.status}</span></td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">No outstanding invoices found</td></tr>';
        }
    } catch (error) {
        console.warn('Outstanding invoices load failed:', error.message);
        const tbody = document.querySelector('#outstandingTable tbody');
        if (tbody) tbody.innerHTML = '<tr><td colspan="7" class="text-center">Unable to load data</td></tr>';
    }
}

// Load outstanding-by-customer and update chart
async function loadOutstandingByCustomer(limit = 10) {
    try {
        const resp = await fetch(`../src/api/simple_api.php?action=outstanding-by-customer&limit=${limit}&prefix=BKGE`);
        const data = await resp.json();
        if (outstandingByCustomerChart && data.data && data.data.labels) {
            outstandingByCustomerChart.data.labels = data.data.labels;
            outstandingByCustomerChart.data.datasets[0].data = data.data.data;
            outstandingByCustomerChart.update();
        }
    } catch (err) {
        console.error('Failed to load outstanding by customer:', err);
    }
}

// (Server-side export used via /ergon/finance/export-outstanding)


async function loadRecentActivities(type = 'all') {
    try {
        const prefix = document.getElementById('companyPrefix').value;
        const container = document.getElementById('recentActivities');
        
        if (!prefix) {
            if (container) container.innerHTML = '<div class="activity-item"><div class="activity-loading">Select a company prefix to view activities</div></div>';
            return;
        }
        
        let url = `/ergon/src/api/activities.php?prefix=${encodeURIComponent(prefix)}&limit=20`;
        if (type !== 'all') {
            url += `&record_type=${encodeURIComponent(type)}`;
        }
        
        const response = await fetch(url, {
            signal: AbortSignal.timeout(5000)
        }).catch(e => null);
        if (!response || !response.ok) throw new Error('Activities API unavailable');
        const result = await response.json();
        
        if (!result.success) throw new Error(result.error || 'API returned error');
        
        if (result.data && result.data.length > 0) {
            container.innerHTML = result.data.map(activity => `
                <div class="activity-item">
                    <div class="activity-icon">${activity.icon}</div>
                    <div class="activity-content">
                        <div class="activity-title">${activity.document_number}</div>
                        <div class="activity-details">${activity.customer_name || 'N/A'} • ₹${activity.formatted_amount}</div>
                        ${activity.shipping_address ? `<div class="activity-address">📍 ${activity.shipping_address}</div>` : ''}
                        <div class="activity-meta">
                            <span class="activity-type">${getActivityTypeLabel(activity.record_type)}</span>
                            <span>${getTimeAgo(activity.created_at)}</span>
                        </div>
                    </div>
                    <div class="activity-status activity-status--${getActivityStatusClass(activity.status)}">
                        ${getStatusLabel(activity.status)}
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<div class="activity-item"><div class="activity-loading">No recent activities found</div></div>';
        }
        
        // Update filter button states
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.type === type);
        });
        
    } catch (error) {
        console.warn('Activities load failed:', error.message);
        const container = document.getElementById('recentActivities');
        if (container) container.innerHTML = '<div class="activity-item"><div class="activity-loading">Unable to load activities</div></div>';
    }
}

function getActivityStatusClass(status) {
    const statusMap = {
        'completed': 'activity-status--completed',
        'pending': 'activity-status--pending',
        'open': 'activity-status--pending',
        'draft': 'activity-status--draft'
    };
    return statusMap[status] || 'activity-status--pending';
}

function getStatusLabel(status) {
    const labelMap = {
        'completed': 'Completed',
        'pending': 'Pending',
        'open': 'Open',
        'draft': 'Draft'
    };
    return labelMap[status] || 'Active';
}

function getActivityTypeLabel(type) {
    const typeMap = {
        'invoice': 'Invoice',
        'quotation': 'Quotation',
        'purchase_order': 'Purchase Order',
        'payment': 'Payment'
    };
    return typeMap[type] || 'Activity';
}

function getTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = Math.abs(now - date);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays === 1) return 'Yesterday';
    if (diffDays < 7) return `${diffDays} days ago`;
    if (diffDays < 30) return `${Math.ceil(diffDays / 7)} weeks ago`;
    return date.toLocaleDateString();
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



let prefixTree = {};

async function loadCompanyPrefix() {
    try {
        const response = await fetch('/ergon/src/api/prefixes.php', {
            signal: AbortSignal.timeout(5000)
        }).catch(e => null);
        if (!response || !response.ok) throw new Error('Prefixes API unavailable');
        const data = await response.json();
        const datalist = document.getElementById('prefixSuggestions');
        const input = document.getElementById('companyPrefix');
        
        if (data.success && data.prefixes.length > 0) {
            datalist.innerHTML = '';
            data.prefixes.forEach(prefix => {
                datalist.innerHTML += `<option value="${prefix}">`;
            });
            
            // Store prefix tree
            prefixTree = data.prefix_tree || {};
            
            // Check for saved prefix in localStorage
            const savedPrefix = localStorage.getItem('financePrefix');
            const prefixToUse = savedPrefix || data.prefixes[0];
            
            // Save the prefix if it wasn't already saved
            if (!savedPrefix) {
                localStorage.setItem('financePrefix', prefixToUse);
            }
            
            input.value = prefixToUse;
            updateLetterSelectors(prefixToUse);
            setTimeout(() => {
                loadAllStatCardsData();
                loadCustomersForFunnel();
                updateConversionFunnel();
                loadRecentActivities();
            }, 100);
            return prefixToUse;
        } else {
            input.placeholder = 'No prefixes found';
            return '';
        }
    } catch (error) {
        console.warn('Prefixes load failed:', error.message);
        const input = document.getElementById('companyPrefix');
        if (input) input.placeholder = 'Enter prefix manually';
        const container = document.getElementById('recentActivities');
        if (container) container.innerHTML = '<div class="activity-item"><div class="activity-loading">Enter a company prefix to load activities</div></div>';
        return '';
    }
}

function updateLetterSelectors(currentPrefix) {
    const container = document.getElementById('letterSelectors');
    container.innerHTML = '';
    
    if (prefixTree[currentPrefix] && Array.isArray(prefixTree[currentPrefix])) {
        const select = document.createElement('select');
        select.className = 'form-control form-control--sm';
        select.style.maxWidth = '60px';
        select.innerHTML = '<option value="">+</option>';
        
        prefixTree[currentPrefix].forEach(letter => {
            select.innerHTML += `<option value="${letter}">${letter}</option>`;
        });
        
        select.addEventListener('change', function() {
            if (this.value) {
                const input = document.getElementById('companyPrefix');
                const newPrefix = currentPrefix + this.value;
                input.value = newPrefix;
                localStorage.setItem('financePrefix', newPrefix);
                updateLetterSelectors(newPrefix);
                updateCompanyPrefix();
                loadAllStatCardsData();
            }
        });
        
        container.appendChild(select);
    }
}





async function updateCompanyPrefix() {
    const input = document.getElementById('companyPrefix');
    const prefix = input.value.trim().toUpperCase();
    
    try {
        const formData = new FormData();
        formData.append('company_prefix', prefix);
        
        const response = await fetch('/ergon/finance/?action=company-prefix', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            // Save prefix to localStorage
            if (prefix) {
                localStorage.setItem('financePrefix', prefix);
                showNotification(`Filtering by: ${prefix}`, 'success');
            } else {
                localStorage.removeItem('financePrefix');
                showNotification('Showing all companies', 'success');
            }
            
            loadAllStatCardsData();
            loadCustomersForFunnel();
        } else {
            showNotification('Failed to update prefix: ' + (result.error || 'Unknown error'), 'error');
        }
    } catch (error) {
        showNotification('Failed to update prefix: ' + error.message, 'error');
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



async function refreshDashboardStats() {
    try {
        showNotification('🔄 Refreshing stat cards...', 'info');
        await loadAllStatCardsData();
        showNotification('✅ Stat cards refreshed', 'success');
    } catch (error) {
        showNotification('❌ Refresh failed: ' + error.message, 'error');
    }
}

function forceUpdateStats() {
    const prefix = document.getElementById('companyPrefix').value.trim();
    if (!prefix) {
        showNotification('Please enter a prefix first', 'warning');
        return;
    }
    console.log('Force updating with prefix:', prefix);
    loadAllStatCardsData();
    loadCustomersForFunnel();
    updateConversionFunnel();
}

async function loadCustomersForFunnel() {
    try {
        const prefix = document.getElementById('companyPrefix').value;
        if (!prefix) return;
        
        const customerSelect = document.getElementById('customerFilter');
        if (!customerSelect) return;
        
        const response = await fetch(`/ergon/src/api/customers.php?prefix=${encodeURIComponent(prefix)}`, {
            signal: AbortSignal.timeout(5000)
        }).catch(e => null);
        if (!response || !response.ok) throw new Error('Customers API unavailable');
        const result = await response.json();
        
        if (result.success) {
            customerSelect.innerHTML = '<option value="">All Customers</option>';
            result.customers.forEach(customer => {
                customerSelect.innerHTML += `<option value="${customer.id}">${customer.display_name}</option>`;
            });
        }
    } catch (error) {
        console.warn('Customers load failed:', error.message);
    }
}

async function updateConversionFunnel() {
    try {
        const prefix = document.getElementById('companyPrefix').value;
        if (!prefix) return;
        
        const customerSelect = document.getElementById('customerFilter');
        const customerId = customerSelect ? customerSelect.value : '';
        
        let url = `/ergon/src/api/funnel.php?prefix=${encodeURIComponent(prefix)}`;
        if (customerId) {
            url += `&customer_id=${encodeURIComponent(customerId)}`;
        }
        
        const response = await fetch(url, {
            signal: AbortSignal.timeout(5000)
        }).catch(e => null);
        if (!response || !response.ok) throw new Error('Funnel API unavailable');
        const result = await response.json();
        
        if (result.success && result.data) {
            const data = result.data;
            
            // Container 1 - Quotations
            const quotationsEl = document.getElementById('funnelQuotations');
            const quotationValueEl = document.getElementById('funnelQuotationValue');
            if (quotationsEl) quotationsEl.textContent = data.container1.quotation_count;
            if (quotationValueEl) quotationValueEl.textContent = `₹${data.container1.quotation_value.toLocaleString()}`;
            
            // Container 2 - Purchase Orders
            const posEl = document.getElementById('funnelPOs');
            const poValueEl = document.getElementById('funnelPOValue');
            const quotationToPOEl = document.getElementById('quotationToPO');
            if (posEl) posEl.textContent = data.container2.po_count;
            if (poValueEl) poValueEl.textContent = `₹${data.container2.po_value.toLocaleString()}`;
            if (quotationToPOEl) quotationToPOEl.textContent = `${data.container2.conversion_rate}%`;
            
            // Container 3 - Invoices
            const invoicesEl = document.getElementById('funnelInvoices');
            const invoiceValueEl = document.getElementById('funnelInvoiceValue');
            const poToInvoiceEl = document.getElementById('poToInvoice');
            if (invoicesEl) invoicesEl.textContent = data.container3.invoice_count;
            if (invoiceValueEl) invoiceValueEl.textContent = `₹${data.container3.invoice_value.toLocaleString()}`;
            if (poToInvoiceEl) poToInvoiceEl.textContent = `${data.container3.conversion_rate}%`;
            
            // Container 4 - Payments
            const paymentsEl = document.getElementById('funnelPayments');
            const paymentValueEl = document.getElementById('funnelPaymentValue');
            const invoiceToPaymentEl = document.getElementById('invoiceToPayment');
            if (paymentsEl) paymentsEl.textContent = data.container4.payment_count;
            if (paymentValueEl) paymentValueEl.textContent = `₹${data.container4.received_amount.toLocaleString()}`;
            if (invoiceToPaymentEl) invoiceToPaymentEl.textContent = `${data.container4.conversion_rate}%`;
        }
        
        // Update all analytics widgets and activities
        updateAnalyticsWidgets().catch(e => console.warn('Analytics update failed:', e));
        loadRecentActivities().catch(e => console.warn('Activities load failed:', e));
    } catch (error) {
        console.warn('Funnel update failed:', error.message);
    }
}

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
        
        const analyticsData = {
            quotations: {},
            invoices: {},
            outstanding: {},
            aging: {}
        };
        
        // Charts are auto-rendered by dashboard-charts.js
        

        
    } catch (error) {
        console.error('Analytics widgets update failed:', error);
    }
}

async function loadAllStatCardsData() {
    try {
        const prefix = document.getElementById('companyPrefix').value.trim() || '';
        if (!prefix) {
            console.log('No prefix selected, skipping stat cards update');
            return;
        }
        const response = await fetch(`/ergon/src/api/dashboard/stats.php?prefix=${encodeURIComponent(prefix)}`, {
            signal: AbortSignal.timeout(5000)
        }).catch(e => null);
        if (!response || !response.ok) throw new Error('Stats API unavailable');
        const result = await response.json();
        
        if (result.success && result.data) {
            const data = result.data;
            
            // STAT CARD 1 — Total Invoice Amount
            const card1 = data.stat_card_1 || {};
            updateKPIValue('totalInvoiceAmount', parseFloat(card1.total_invoice_amount) || 0);
            updateKPIDetail('totalInvoiceCount', parseInt(card1.invoice_count) || 0);
            updateKPIDetail('avgInvoiceAmount', card1.invoice_count > 0 ? (parseFloat(card1.total_invoice_amount) / parseInt(card1.invoice_count)) : 0, true);
            
            // STAT CARD 2 — Amount Received
            const card2 = data.stat_card_2 || {};
            updateKPIValue('invoiceReceived', parseFloat(card2.amount_received) || 0);
            updateKPIDetail('collectionRateKPI', parseFloat(card1.total_invoice_amount) > 0 ? Math.round((parseFloat(card2.amount_received) / parseFloat(card1.total_invoice_amount)) * 100) : 0, false, '%');
            updateKPIDetail('paidInvoiceCount', parseInt(card2.paid_invoices) || 0);
            
            // STAT CARD 3 — Outstanding Amount
            const card3 = data.stat_card_3 || {};
            updateKPIValue('pendingInvoiceAmount', parseFloat(card3.total_outstanding) || 0);
            updateKPIDetail('pendingInvoicesCount', parseInt(card3.pending_invoices) || 0);
            updateKPIDetail('customersPendingCount', parseInt(card3.customers_involved) || 0);
            updateKPIDetail('overdueAmount', parseFloat(card3.total_outstanding) || 0, true);
            
            // STAT CARD 4 — GST Liability
            const card4 = data.stat_card_4 || {};
            updateKPIValue('pendingGSTAmount', parseFloat(card4.total_gst) || 0);
            updateKPIDetail('igstLiability', parseFloat(card4.igst) || 0, true);
            updateKPIDetail('cgstSgstTotal', parseFloat(card4.cgst_sgst) || 0, true);
            
            // STAT CARD 5 — PO Commitments
            const card5 = data.stat_card_5 || {};
            updateKPIValue('pendingPOValue', parseFloat(card5.total_po_commitments) || 0);
            updateKPIDetail('openPOCount', parseInt(card5.open_pos) || 0);
            updateKPIDetail('closedPOCount', parseInt(card5.closed_pos) || 0);
            
            // STAT CARD 6 — Claimable Amount
            const card6 = data.stat_card_6 || {};
            updateKPIValue('claimableAmount', parseFloat(card6.claimable_amount) || 0);
            updateKPIDetail('claimablePOCount', parseInt(card6.claimable_invoices) || 0);
            updateKPIDetail('claimRate', parseFloat(card6.claim_rate) || 0, false, '%');
        }
        
        // Update analytics widgets after stat cards
        setTimeout(() => {
            updateAnalyticsWidgets().catch(e => console.warn('Analytics update failed:', e));
            loadOutstandingInvoices().catch(e => console.warn('Outstanding invoices failed:', e));
        }, 100);
    } catch (error) {
        console.error('Failed to load all stat cards data:', error);
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
    gap: 0.75rem;
    padding: 1.5rem 0;
}

.funnel-stage {
    text-align: center;
    flex: 1;
    padding: 1.25rem 0.75rem;
    background: linear-gradient(135deg, var(--bg-secondary) 0%, rgba(255,255,255,0.5) 100%);
    border: 2px solid var(--border-color);
    border-radius: 12px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.funnel-stage::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--primary), var(--success));
    opacity: 0;
    transition: opacity 0.3s ease;
}

.funnel-stage:hover {
    border-color: var(--primary);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-3px);
}

.funnel-stage:hover::before {
    /*opacity: 1;*/
}

.funnel-number {
    font-size: 2rem;
    font-weight: 800;
    background: linear-gradient(135deg, var(--primary), var(--success));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 0.5rem;
}

.funnel-label {
    font-size: 0.75rem;
    color: var(--text-secondary);
    margin-bottom: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
}

.funnel-value {
    font-size: 1rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
    font-family: 'Courier New', monospace;
}

.funnel-conversion {
    display: inline-block;
    font-size: 0.8rem;
    color: white;
    font-weight: 700;
    background: linear-gradient(135deg, var(--success), #059669);
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    margin-top: 0.25rem;
}

.funnel-arrow {
    font-size: 1.75rem;
    color: var(--primary);
    margin: 0 -0.25rem;
    font-weight: 300;
    opacity: 0.6;
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
    justify-content: center;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
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

.page-header-modern {
    width: 100%;
    margin-bottom: 1.5rem;
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 0.75rem 1.5rem;
    height: 60px;
}

.page-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    height: 100%;
    gap: 2rem;
}

.page-title-section {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.page-title-section .page-title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    white-space: nowrap;
}

.page-title-section .page-subtitle {
    font-size: 0.8rem;
    color: var(--text-secondary);
    margin: 0;
    white-space: nowrap;
}

.page-actions-section {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-shrink: 0;
}

.filter-controls {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.filter-controls .form-group {
    margin: 0;
    position: relative;
}

.filter-controls .form-control {
    width: 150px;
    font-size: 0.8rem;
    padding: 0.4rem 0.6rem;
    height: 32px;
}

.filter-controls #companyPrefix {
    width: 180px;
}

.letter-selectors {
    display: flex;
    gap: 2px;
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 10;
}

.letter-selectors select {
    width: 40px;
    font-size: 0.7rem;
    padding: 0.2rem;
    height: 24px;
}

@media (max-width: 768px) {
    .page-header-content {
        overflow-x: auto;
        flex-wrap: nowrap;
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
    position: relative;
}

.filter-btn.active {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.filter-btn::after {
    content: attr(title);
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.7rem;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.2s ease;
    z-index: 1000;
    margin-top: 0.25rem;
}

.filter-btn:hover::after {
    opacity: 1;
    visibility: visible;
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

.activity-status--draft {
    background: rgba(107, 114, 128, 0.1);
    color: var(--text-muted);
}

.activity-loading {
    text-align: center;
    color: var(--text-muted);
    font-style: italic;
}

.activity-address {
    margin-top: 0.25rem;
    color: var(--text-secondary);
    font-size: 0.75rem;
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

