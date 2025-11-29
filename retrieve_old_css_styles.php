<?php
/**
 * Script to retrieve old CSS styles and remove existing CSS from finance dashboard
 * This script will:
 * 1. Extract existing CSS styles from finance dashboard
 * 2. Remove all CSS styling from the dashboard
 * 3. Provide only functional PHP/JavaScript code
 */

// Function to extract CSS from file content
function extractCSSFromFile($filePath) {
    if (!file_exists($filePath)) {
        return null;
    }
    
    $content = file_get_contents($filePath);
    $cssStyles = [];
    
    // Extract inline styles from <style> tags
    if (preg_match_all('/<style[^>]*>(.*?)<\/style>/s', $content, $matches)) {
        foreach ($matches[1] as $css) {
            $cssStyles[] = trim($css);
        }
    }
    
    // Extract inline style attributes
    if (preg_match_all('/style\s*=\s*["\']([^"\']*)["\']/', $content, $matches)) {
        foreach ($matches[1] as $inlineStyle) {
            $cssStyles[] = 'inline: ' . trim($inlineStyle);
        }
    }
    
    return $cssStyles;
}

// Function to remove CSS from file content
function removeCSSFromContent($content) {
    // Remove <style> tags and their content
    $content = preg_replace('/<style[^>]*>.*?<\/style>/s', '', $content);
    
    // Remove inline style attributes
    $content = preg_replace('/\s+style\s*=\s*["\'][^"\']*["\']/', '', $content);
    
    // Remove CSS class references that are styling-specific (keep functional classes)
    $stylingClasses = [
        'kpi-card--success', 'kpi-card--warning', 'kpi-card--info', 'kpi-card--primary', 'kpi-card--secondary',
        'btn--primary', 'btn--secondary', 'btn--success', 'btn--info', 'btn--warning', 'btn--sm',
        'form-control--sm', 'card--full-width', 'dashboard-grid--2-col', 'table-row--danger',
        'badge--danger', 'badge--warning', 'badge--info', 'badge--success'
    ];
    
    // Keep functional classes, remove only styling classes
    foreach ($stylingClasses as $class) {
        $content = str_replace($class, '', $content);
    }
    
    // Clean up multiple spaces and empty class attributes
    $content = preg_replace('/class\s*=\s*["\'][\s]*["\']/', '', $content);
    $content = preg_replace('/\s+/', ' ', $content);
    
    return $content;
}

// Main execution
try {
    $financeFiles = [
        'c:\laragon\www\ergon\views\finance\dashboard.php',
        'c:\laragon\www\ergon\assets\css\finance.css'
    ];
    
    $extractedCSS = [];
    $processedFiles = [];
    
    echo "<h2>🔍 Extracting Old CSS Styles</h2>\n";
    
    foreach ($financeFiles as $file) {
        if (file_exists($file)) {
            echo "<h3>📄 Processing: " . basename($file) . "</h3>\n";
            
            // Extract CSS
            $css = extractCSSFromFile($file);
            if ($css) {
                $extractedCSS[basename($file)] = $css;
                echo "<p>✅ Extracted " . count($css) . " CSS rules</p>\n";
            }
            
            // Remove CSS and create clean version
            $originalContent = file_get_contents($file);
            $cleanContent = removeCSSFromContent($originalContent);
            
            // Create backup
            $backupFile = $file . '.backup.' . date('Y-m-d_H-i-s');
            file_put_contents($backupFile, $originalContent);
            echo "<p>💾 Backup created: " . basename($backupFile) . "</p>\n";
            
            // Save clean version
            $cleanFile = str_replace('.php', '_clean.php', $file);
            $cleanFile = str_replace('.css', '_clean.css', $cleanFile);
            file_put_contents($cleanFile, $cleanContent);
            echo "<p>🧹 Clean version created: " . basename($cleanFile) . "</p>\n";
            
            $processedFiles[] = [
                'original' => $file,
                'backup' => $backupFile,
                'clean' => $cleanFile
            ];
        }
    }
    
    // Save extracted CSS to separate file
    $cssOutputFile = 'c:\laragon\www\ergon\extracted_old_css_styles.css';
    $cssOutput = "/* Extracted CSS Styles from Finance Dashboard */\n";
    $cssOutput .= "/* Generated on: " . date('Y-m-d H:i:s') . " */\n\n";
    
    foreach ($extractedCSS as $fileName => $styles) {
        $cssOutput .= "/* ===== FROM FILE: $fileName ===== */\n";
        foreach ($styles as $style) {
            if (strpos($style, 'inline:') === 0) {
                $cssOutput .= "/* Inline style: " . substr($style, 7) . " */\n";
            } else {
                $cssOutput .= $style . "\n\n";
            }
        }
        $cssOutput .= "\n";
    }
    
    file_put_contents($cssOutputFile, $cssOutput);
    echo "<h3>💾 CSS Styles Saved</h3>\n";
    echo "<p>📁 File: " . basename($cssOutputFile) . "</p>\n";
    
    // Create functional-only dashboard
    $functionalDashboard = 'c:\laragon\www\ergon\views\finance\dashboard_functional_only.php';
    
    $functionalContent = '<?php 
$title = \'Finance Dashboard\';
$active_page = \'finance\';
ob_start(); 
?>

<div>
    <!-- Header Actions -->
    <div>
        <div>
            <h1>Finance Dashboard</h1>
            <p>Real-time financial insights and analytics</p>
        </div>
        <div>
            <div>
                <button id="syncBtn">
                    <span>🔄</span>
                    <span>Sync Data</span>
                </button>
                <button id="exportBtn">
                    <span>📥</span>
                    <span>Export</span>
                </button>
                <button onclick="refreshDashboardStats()">
                    <span>🔄</span>
                    <span>Refresh Stats</span>
                </button>
            </div>
            <div>
                <div>
                    <input type="text" id="companyPrefix" placeholder="Company Prefix (e.g. BKC)" maxlength="10">
                    <button id="updatePrefixBtn">
                        <span>🏢</span>
                    </button>
                </div>
                <select id="dateFilter">
                    <option value="all">All Time</option>
                    <option value="30">Last 30 Days</option>
                    <option value="90">Last 90 Days</option>
                    <option value="365">Last Year</option>
                </select>
            </div>
        </div>
    </div>

    <!-- KPI Cards -->
    <div>
        <div>
            <div>
                <div>💰</div>
                <div id="invoiceTrend">↗ +0%</div>
            </div>
            <div id="totalInvoiceAmount">₹0</div>
            <div>Total Invoice Amount</div>
            <div>Total Revenue Generated</div>
            <div>
                <div>Count: <span id="totalInvoiceCount">0</span></div>
                <div>Avg: <span id="avgInvoiceAmount">₹0</span></div>
            </div>
        </div>
        
        <div>
            <div>
                <div>✅</div>
                <div id="receivedTrend">↗ +0%</div>
            </div>
            <div id="invoiceReceived">₹0</div>
            <div>Amount Received</div>
            <div>Successfully Collected Revenue</div>
            <div>
                <div>Collection Rate: <span id="collectionRateKPI">0%</span></div>
                <div>Paid Invoices: <span id="paidInvoiceCount">0</span></div>
            </div>
        </div>
        
        <div>
            <div>
                <div>⏳</div>
                <div id="pendingTrend">— 0%</div>
            </div>
            <div id="pendingInvoiceAmount">₹0</div>
            <div>Outstanding Amount</div>
            <div>Taxable Amount Pending (No GST)</div>
            <div>
                <div>Pending Invoices: <span id="pendingInvoicesCount">0</span></div>
                <div>Customers: <span id="customersPendingCount">0</span></div>
                <div>Overdue Amount: <span id="overdueAmount">₹0</span></div>
            </div>
        </div>
        
        <div>
            <div>
                <div>🏛️</div>
                <div id="gstTrend">— 0%</div>
            </div>
            <div id="pendingGSTAmount">₹0</div>
            <div>GST Liability</div>
            <div>Tax Liability on Outstanding Invoices Only</div>
            <div>
                <div>IGST: <span id="igstLiability">₹0</span></div>
                <div>CGST+SGST: <span id="cgstSgstTotal">₹0</span></div>
            </div>
        </div>
        
        <div>
            <div>
                <div>🛒</div>
                <div id="poTrend">↗ +0%</div>
            </div>
            <div id="pendingPOValue">₹0</div>
            <div>PO Commitments</div>
            <div>Total Value of All Purchase Orders</div>
            <div>
                <div>Open POs: <span id="openPOCount">0</span></div>
                <div>Closed POs: <span id="closedPOCount">0</span></div>
            </div>
        </div>
        
        <div>
            <div>
                <div>💸</div>
                <div id="claimableTrend">— 0%</div>
            </div>
            <div id="claimableAmount">₹0</div>
            <div>Claimable Amount</div>
            <div>Total Invoice Amount - Payments Received</div>
            <div>
                <div>Claimable POs: <span id="claimablePOCount">0</span></div>
                <div>Claim Rate: <span id="claimRate">0%</span></div>
            </div>
        </div>
    </div>

    <!-- Conversion Funnel -->
    <div>
        <div>
            <div>
                <h2>🔄 Revenue Conversion Funnel</h2>
                <select id="customerFilter">
                    <option value="">All Customers</option>
                </select>
                <span id="customerLoader"></span>
            </div>
            <div>
                <div>
                    <div>
                        <div id="funnelQuotations">0</div>
                        <div>Quotations</div>
                        <div id="funnelQuotationValue">₹0</div>
                    </div>
                    <div>→</div>
                    <div>
                        <div id="funnelPOs">0</div>
                        <div>Purchase Orders</div>
                        <div id="funnelPOValue">₹0</div>
                        <div id="quotationToPO">0%</div>
                    </div>
                    <div>→</div>
                    <div>
                        <div id="funnelInvoices">0</div>
                        <div>Invoices</div>
                        <div id="funnelInvoiceValue">₹0</div>
                        <div id="poToInvoice">0%</div>
                    </div>
                    <div>→</div>
                    <div>
                        <div id="funnelPayments">0</div>
                        <div>Payments</div>
                        <div id="funnelPaymentValue">₹0</div>
                        <div id="invoiceToPayment">0%</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div>
        <div>
            <div>
                <div>
                    <div>📝</div>
                    <div>Quotations Overview</div>
                    <div id="quotationsTotal">0</div>
                    <div>Quotation Status Count Distribution</div>
                </div>
                <div id="quotationsTrend">+0%</div>
            </div>
            <div>
                <canvas id="quotationsChart"></canvas>
            </div>
        </div>
        
        <div>
            <div>
                <div>
                    <div>💰</div>
                    <div>Invoice Status</div>
                    <div id="invoicesTotal">0</div>
                    <div>Revenue Collection Health</div>
                </div>
                <div id="invoicesTrendChart">0%</div>
            </div>
            <div>
                <canvas id="invoicesChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Outstanding Invoices Table -->
    <div>
        <div>
            <div>
                <h2>⚠️ Outstanding Invoices</h2>
                <button onclick="exportTable(\'outstanding\')">Export</button>
            </div>
            <div>
                <div>
                    <table id="outstandingTable">
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
                                <td colspan="6">Loading outstanding invoices...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activities -->
    <div>
        <div>
            <div>
                <h2>📈 Recent Activities</h2>
                <div>
                    <button data-type="all" onclick="loadRecentActivities(\'all\')">All</button>
                    <button data-type="quotation" onclick="loadRecentActivities(\'quotation\')">📝</button>
                    <button data-type="purchase_order" onclick="loadRecentActivities(\'purchase_order\')">🛒</button>
                    <button data-type="invoice" onclick="loadRecentActivities(\'invoice\')">💰</button>
                    <button data-type="payment" onclick="loadRecentActivities(\'payment\')">💳</button>
                </div>
            </div>
            <div>
                <div id="recentActivities">
                    <div>
                        <div>Loading recent activities...</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div>
            <div>
                <h2>💰 Cash Flow Projection</h2>
            </div>
            <div>
                <div>
                    <div>
                        <div>Expected Inflow:</div>
                        <div id="expectedInflow">₹0</div>
                    </div>
                    <div>
                        <div>PO Commitments:</div>
                        <div id="poCommitments">₹0</div>
                    </div>
                    <div>
                        <div>Net Cash Flow:</div>
                        <div id="netCashFlow">₹0</div>
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

function showNotification(message, type = \'info\') {
    const notification = document.createElement(\'div\');
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 5000);
}

document.addEventListener(\'DOMContentLoaded\', function() {
    initCharts();
    
    document.getElementById(\'syncBtn\').addEventListener(\'click\', syncFinanceData);
    document.getElementById(\'exportBtn\').addEventListener(\'click\', exportDashboard);
    document.getElementById(\'updatePrefixBtn\').addEventListener(\'click\', updateCompanyPrefix);

    document.getElementById(\'dateFilter\').addEventListener(\'change\', filterByDate);
    document.getElementById(\'customerFilter\').addEventListener(\'change\', filterByCustomer);
    
    loadCompanyPrefix().then(() => {
        loadCustomers();
        loadDashboardData();
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
                        if (typeof v === \'number\') return \'₹\' + Number(v).toLocaleString();
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

    const quotationsCtx = document.getElementById(\'quotationsChart\');
    if (quotationsCtx) {
        quotationsChart = new Chart(quotationsCtx.getContext(\'2d\'), {
            type: \'pie\',
            data: { labels: [\'Pending\',\'Placed\',\'Rejected\'], datasets: [{ data: [0,0,0], backgroundColor: [\'#3b82f6\',\'#10b981\',\'#ef4444\'] }] },
            options: chartDefaults
        });
    }

    const invoicesCtx = document.getElementById(\'invoicesChart\');
    if (invoicesCtx) {
        invoicesChart = new Chart(invoicesCtx.getContext(\'2d\'), {
            type: \'doughnut\',
            data: { labels: [\'Paid\',\'Unpaid\',\'Overdue\'], datasets: [{ data: [0,0,0], backgroundColor: [\'#10b981\',\'#f59e0b\',\'#ef4444\'] }] },
            options: { ...chartDefaults, cutout: \'70%\' }
        });
    }
}

async function loadDashboardData() {
    try {
        const response = await fetch(\'/ergon/finance/dashboard-stats\');
        const data = await response.json();
        
        if (data.error) {
            showNotification(data.error, \'error\');
            return;
        }
        
        updateKPICards(data);
        updateConversionFunnel(data);
        updateCharts(data);
        loadOutstandingInvoices();
        loadRecentActivities();
        updateCashFlow(data);
        
    } catch (error) {
        showNotification(\'Failed to load dashboard data: \' + error.message, \'error\');
        updateKPICards({});
        updateConversionFunnel({});
        updateCashFlow({});
    }
}

function updateKPICards(data) {
    const funnel = data.conversionFunnel || {};
    
    document.getElementById(\'totalInvoiceAmount\').textContent = `₹${(data.totalInvoiceAmount || 0).toLocaleString()}`;
    document.getElementById(\'invoiceReceived\').textContent = `₹${(data.invoiceReceived || 0).toLocaleString()}`;
    document.getElementById(\'pendingInvoiceAmount\').textContent = `₹${(data.outstandingAmount || data.pendingInvoiceAmount || 0).toLocaleString()}`;
    document.getElementById(\'pendingGSTAmount\').textContent = `₹${(data.gstLiability || data.pendingGSTAmount || 0).toLocaleString()}`;
    document.getElementById(\'pendingPOValue\').textContent = `₹${(data.pendingPOValue || funnel.poValue || 0).toLocaleString()}`;
    document.getElementById(\'claimableAmount\').textContent = `₹${(data.claimableAmount || 0).toLocaleString()}`;
    
    const totalInvoiceCount = document.getElementById(\'totalInvoiceCount\');
    const avgInvoiceAmount = document.getElementById(\'avgInvoiceAmount\');
    if (totalInvoiceCount) totalInvoiceCount.textContent = funnel.invoices || 0;
    if (avgInvoiceAmount && funnel.invoices > 0) {
        avgInvoiceAmount.textContent = `₹${Math.round((data.totalInvoiceAmount || 0) / funnel.invoices).toLocaleString()}`;
    } else if (avgInvoiceAmount) {
        avgInvoiceAmount.textContent = \'₹0\';
    }
    
    const collectionRateKPI = document.getElementById(\'collectionRateKPI\');
    const paidInvoiceCount = document.getElementById(\'paidInvoiceCount\');
    if (collectionRateKPI && data.totalInvoiceAmount > 0) {
        collectionRateKPI.textContent = `${Math.round((data.invoiceReceived / data.totalInvoiceAmount) * 100)}%`;
    } else if (collectionRateKPI) {
        collectionRateKPI.textContent = \'0%\';
    }
    if (paidInvoiceCount) paidInvoiceCount.textContent = funnel.payments || 0;
    
    const pendingInvoicesCount = document.getElementById(\'pendingInvoicesCount\');
    const customersPendingCount = document.getElementById(\'customersPendingCount\');
    const overdueAmount = document.getElementById(\'overdueAmount\');
    
    if (pendingInvoicesCount) pendingInvoicesCount.textContent = data.pendingInvoices || 0;
    if (customersPendingCount) customersPendingCount.textContent = data.customersPending || 0;
    if (overdueAmount) overdueAmount.textContent = `₹${(data.overdueAmount || 0).toLocaleString()}`;
    
    const igstLiability = document.getElementById(\'igstLiability\');
    const cgstSgstTotal = document.getElementById(\'cgstSgstTotal\');
    if (igstLiability) igstLiability.textContent = `₹${(data.igstLiability || 0).toLocaleString()}`;
    if (cgstSgstTotal) cgstSgstTotal.textContent = `₹${(data.cgstSgstTotal || 0).toLocaleString()}`;
    
    const openPOCount = document.getElementById(\'openPOCount\');
    const closedPOCount = document.getElementById(\'closedPOCount\');
    
    if (openPOCount) openPOCount.textContent = data.openPOCount || 0;
    if (closedPOCount) closedPOCount.textContent = data.closedPOCount || 0;
    
    const claimablePOCount = document.getElementById(\'claimablePOCount\');
    const claimRate = document.getElementById(\'claimRate\');
    if (claimablePOCount) claimablePOCount.textContent = data.claimablePOCount || data.claimablePos || 0;
    if (claimRate) claimRate.textContent = `${Math.round(data.claimRate || 0)}%`;
}

function updateConversionFunnel(data) {
    const funnel = data.conversionFunnel || {};
    
    document.getElementById(\'funnelQuotations\').textContent = funnel.quotations || 0;
    document.getElementById(\'funnelQuotationValue\').textContent = `₹${(funnel.quotationValue || 0).toLocaleString()}`;
    
    document.getElementById(\'funnelPOs\').textContent = funnel.purchaseOrders || 0;
    document.getElementById(\'funnelPOValue\').textContent = `₹${(funnel.poValue || 0).toLocaleString()}`;
    document.getElementById(\'quotationToPO\').textContent = `${funnel.quotationToPO || 0}%`;
    
    document.getElementById(\'funnelInvoices\').textContent = funnel.invoices || 0;
    document.getElementById(\'funnelInvoiceValue\').textContent = `₹${(funnel.invoiceValue || 0).toLocaleString()}`;
    document.getElementById(\'poToInvoice\').textContent = `${funnel.poToInvoice || 0}%`;
    
    document.getElementById(\'funnelPayments\').textContent = funnel.payments || 0;
    document.getElementById(\'funnelPaymentValue\').textContent = `₹${(funnel.paymentValue || 0).toLocaleString()}`;
    document.getElementById(\'invoiceToPayment\').textContent = `${funnel.invoiceToPayment || 0}%`;
}

async function updateCharts(data) {
    try {
        const quotationsResponse = await fetch(\'/ergon/finance/visualization?type=quotations\');
        if (quotationsResponse.ok) {
            const quotationsData = await quotationsResponse.json();
            if (quotationsChart && quotationsData.data) {
                quotationsChart.data.datasets[0].data = quotationsData.data;
                quotationsChart.update();
            }
        }
        
        const invoicesResponse = await fetch(\'/ergon/finance/visualization?type=invoices\');
        if (invoicesResponse.ok) {
            const invoicesData = await invoicesResponse.json();
            if (invoicesChart && invoicesData.data) {
                invoicesChart.data.datasets[0].data = invoicesData.data;
                invoicesChart.update();
            }
        }
        
    } catch (error) {
        console.warn(\'Charts not available:\', error.message);
    }
}

async function loadOutstandingInvoices() {
    try {
        const response = await fetch(\'/ergon/finance/outstanding-invoices\');
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: Outstanding invoices API not available`);
        }
        
        const data = await response.json();
        const tbody = document.querySelector(\'#outstandingTable tbody\');
        if (data.invoices && data.invoices.length > 0) {
            tbody.innerHTML = data.invoices.map(invoice => `
                <tr>
                    <td>${invoice.invoice_number}</td>
                    <td>${invoice.customer_name}</td>
                    <td>${invoice.due_date}</td>
                    <td>₹${invoice.outstanding_amount.toLocaleString()}</td>
                    <td>${invoice.daysOverdue > 0 ? invoice.daysOverdue : \'-\'}</td>
                    <td><span>${invoice.status}</span></td>
                </tr>
            `).join(\'\');
        } else {
            const message = data.message || \'No outstanding invoices\';
            tbody.innerHTML = `<tr><td colspan="6">${message}</td></tr>`;
        }
        
    } catch (error) {
        const tbody = document.querySelector(\'#outstandingTable tbody\');
        tbody.innerHTML = `<tr><td colspan="6">Error loading data: ${error.message}</td></tr>`;
    }
}

async function loadRecentActivities(type = \'all\') {
    try {
        const response = await fetch(\'/ergon/finance/recent-activities\');
        if (!response.ok) {
            throw new Error(\'Recent activities API not available\');
        }
        const data = await response.json();
        
        const container = document.getElementById(\'recentActivities\');
        if (data.activities && data.activities.length > 0) {
            let filteredActivities = data.activities;
            if (type !== \'all\') {
                filteredActivities = data.activities.filter(activity => activity.type === type);
            }
            
            container.innerHTML = filteredActivities.map(activity => {
                const timeAgo = getTimeAgo(activity.date);
                
                return `
                    <div>
                        <div>${activity.icon}</div>
                        <div>
                            <div>${activity.title}</div>
                            <div>${activity.description}</div>
                            <div>
                                <span>${getActivityTypeLabel(activity.type)}</span>
                                <span>${timeAgo}</span>
                            </div>
                        </div>
                        <div>${getStatusLabel(activity.status)}</div>
                    </div>
                `;
            }).join(\'\');
        } else {
            container.innerHTML = \'<div><div>No recent activities found</div></div>\';
        }
        
    } catch (error) {
        document.getElementById(\'recentActivities\').innerHTML = \'<div><div>Error loading activities</div></div>\';
    }
}

function getStatusLabel(status) {
    const labelMap = {
        \'completed\': \'Completed\',
        \'pending\': \'Pending\',
        \'open\': \'Open\',
        \'draft\': \'Draft\'
    };
    return labelMap[status] || \'Active\';
}

function getActivityTypeLabel(type) {
    const typeMap = {
        \'invoice\': \'Invoice\',
        \'quotation\': \'Quotation\',
        \'purchase_order\': \'Purchase Order\',
        \'payment\': \'Payment\'
    };
    return typeMap[type] || \'Activity\';
}

function getTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = Math.abs(now - date);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays === 1) return \'Yesterday\';
    if (diffDays < 7) return `${diffDays} days ago`;
    if (diffDays < 30) return `${Math.ceil(diffDays / 7)} weeks ago`;
    return date.toLocaleDateString();
}

function updateCashFlow(data) {
    const cashFlow = data.cashFlow || {};
    const funnel = data.conversionFunnel || {};
    
    document.getElementById(\'expectedInflow\').textContent = `₹${(cashFlow.expectedInflow || 0).toLocaleString()}`;
    document.getElementById(\'poCommitments\').textContent = `₹${(funnel.poValue || 0).toLocaleString()}`;
    
    const netFlow = (cashFlow.expectedInflow || 0) - (funnel.poValue || 0);
    const netElement = document.getElementById(\'netCashFlow\');
    netElement.textContent = `₹${netFlow.toLocaleString()}`;
}

async function syncFinanceData() {
    const btn = document.getElementById(\'syncBtn\');
    btn.disabled = true;
    btn.textContent = \'Syncing...\';
    
    try {
        const response = await fetch(\'/ergon/finance/sync\', {method: \'POST\'});
        const result = await response.json();
        
        if (result.error) {
            showNotification(\'Sync failed: \' + result.error, \'error\');
        } else {
            showNotification(`Synced ${result.tables} finance tables successfully`, \'success\');
            loadDashboardData();
        }
    } catch (error) {
        showNotification(\'Sync failed: \' + error.message, \'error\');
    } finally {
        btn.disabled = false;
        btn.textContent = \'🔄 Sync Data\';
    }
}

async function loadCompanyPrefix() {
    try {
        const response = await fetch(\'/ergon/finance/company-prefix\');
        const data = await response.json();
        const currentPrefix = data.prefix || \'\';
        
        document.getElementById(\'companyPrefix\').value = currentPrefix;
        return currentPrefix;
    } catch (error) {
        return \'\';
    }
}

async function updateCompanyPrefix() {
    const input = document.getElementById(\'companyPrefix\');
    const prefix = input.value.trim().toUpperCase();
    
    const btn = document.getElementById(\'updatePrefixBtn\');
    btn.disabled = true;
    
    try {
        const formData = new FormData();
        formData.append(\'company_prefix\', prefix);
        
        const response = await fetch(\'/ergon/finance/company-prefix\', {
            method: \'POST\',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            if (prefix) {
                showNotification(`Filtering by company: ${result.prefix}`, \'success\');
            } else {
                showNotification(\'Showing all companies\', \'success\');
            }
            
            await loadCustomers();
            
            const customerSelect = document.getElementById(\'customerFilter\');
            if (customerSelect) customerSelect.value = \'\';
            
            loadDashboardData();
        } else {
            showNotification(\'Failed to update prefix: \' + (result.error || \'Unknown error\'), \'error\');
        }
    } catch (error) {
        showNotification(\'Failed to update prefix: \' + error.message, \'error\');
    } finally {
        btn.disabled = false;
    }
}

function filterByDate() {
    const days = document.getElementById(\'dateFilter\').value;
    if (days !== \'all\') {
        loadDashboardData();
    }
}

function filterByCustomer() {
    loadDashboardData();
}

async function loadCustomers() {
    const select = document.getElementById(\'customerFilter\');
    const loader = document.getElementById(\'customerLoader\');
    try {
        if (loader) { loader.style.display = \'inline-block\'; }
        if (select) { select.disabled = true; select.innerHTML = \'<option value="">Loading customers...</option>\'; }

        const response = await fetch(\'/ergon/finance/customers\');
        const data = await response.json();

        if (select) select.innerHTML = \'<option value="">All Customers</option>\';
        if (data.customers) {
            data.customers.forEach(customer => {
                select.innerHTML += `<option value="${customer.id}">${customer.display}</option>`;
            });
        }
    } catch (error) {
        if (select) select.innerHTML = \'<option value="">Failed to load</option>\';
    } finally {
        if (loader) loader.style.display = \'none\';
        if (select) select.disabled = false;
    }
}

async function refreshDashboardStats() {
    try {
        const response = await fetch(\'/ergon/finance/refresh-stats\');
        const result = await response.json();
        
        if (result.success) {
            showNotification(\'Dashboard stats refreshed successfully!\', \'success\');
            loadDashboardData();
        } else {
            showNotification(\'Failed to refresh stats: \' + (result.error || \'Unknown error\'), \'error\');
        }
    } catch (error) {
        showNotification(\'Failed to refresh stats: \' + error.message, \'error\');
    }
}

function exportDashboard() {
    window.open(\'/ergon/finance/export-dashboard\', \'_blank\');
}

function exportTable(type) {
    window.open(`/ergon/finance/export-table?type=${type}`, \'_blank\');
}
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . \'/../layouts/dashboard.php\';
?>
';
    
    file_put_contents($functionalDashboard, $functionalContent);
    echo "<h3>🚀 Functional Dashboard Created</h3>\n";
    echo "<p>📁 File: " . basename($functionalDashboard) . "</p>\n";
    
    echo "<h2>✅ Process Complete</h2>\n";
    echo "<p><strong>Summary:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>✅ Extracted CSS styles from " . count($financeFiles) . " files</li>\n";
    echo "<li>💾 Created backups of original files</li>\n";
    echo "<li>🧹 Generated clean versions without CSS</li>\n";
    echo "<li>📄 Saved extracted CSS to: extracted_old_css_styles.css</li>\n";
    echo "<li>🚀 Created functional-only dashboard</li>\n";
    echo "</ul>\n";
    
    echo "<h3>📋 Next Steps:</h3>\n";
    echo "<ol>\n";
    echo "<li>Review extracted CSS in: extracted_old_css_styles.css</li>\n";
    echo "<li>Use functional dashboard: dashboard_functional_only.php</li>\n";
    echo "<li>Original files backed up with timestamp</li>\n";
    echo "</ol>\n";
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>\n";
    echo "<p>Error: " . $e->getMessage() . "</p>\n";
}
?>