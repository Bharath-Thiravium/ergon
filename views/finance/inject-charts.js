// Inject chart cards into the dashboard if they don't exist
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        // Check if charts already exist
        if (document.getElementById('quotationsChart')) {
            console.log('Charts already in DOM');
            return;
        }
        
        // Find the dashboard grid container
        const gridContainer = document.querySelector('.dashboard-grid--2-col');
        if (!gridContainer) {
            console.log('Dashboard grid not found');
            return;
        }
        
        // Create chart cards HTML
        const chartsHTML = `
            <div class="chart-card">
                <div class="chart-card__header">
                    <div class="chart-card__info">
                        <div class="chart-card__icon">📝</div>
                        <div class="chart-card__title">Quotations Status</div>
                        <div class="chart-card__value" id="quotationsTotal">₹0</div>
                        <div class="chart-card__subtitle">Status Distribution</div>
                    </div>
                    <div class="chart-card__trend" id="quotationsTrend">+0%</div>
                </div>
                <div class="chart-container">
                    <svg class="chart-svg" viewBox="0 0 200 120" id="quotationsChart" preserveAspectRatio="xMidYMid meet"></svg>
                </div>
                <div class="chart-card__meta">
                    <div class="meta-item"><span>Pending:</span><strong id="quotationsPending">0</strong></div>
                    <div class="meta-item"><span>Placed:</span><strong id="quotationsPlaced">0</strong></div>
                    <div class="meta-item"><span>Rejected:</span><strong id="quotationsRejected">0</strong></div>
                </div>
            </div>
            <div class="chart-card">
                <div class="chart-card__header">
                    <div class="chart-card__info">
                        <div class="chart-card__icon">🛒</div>
                        <div class="chart-card__title">Purchase Orders</div>
                        <div class="chart-card__value" id="poTotal">₹0</div>
                        <div class="chart-card__subtitle">Fulfillment Rate</div>
                    </div>
                    <div class="chart-card__trend" id="poTrend">+0%</div>
                </div>
                <div class="chart-container">
                    <svg class="chart-svg" viewBox="0 0 200 120" id="purchaseOrdersChart" preserveAspectRatio="xMidYMid meet"></svg>
                </div>
                <div class="chart-card__meta">
                    <div class="meta-item"><span>Open:</span><strong id="poOpen">0</strong></div>
                    <div class="meta-item"><span>Fulfilled:</span><strong id="poFulfilled">0</strong></div>
                    <div class="meta-item"><span>Rate:</span><strong id="poFulfillmentRate">0%</strong></div>
                </div>
            </div>
            <div class="chart-card">
                <div class="chart-card__header">
                    <div class="chart-card__info">
                        <div class="chart-card__icon">💰</div>
                        <div class="chart-card__title">Invoice Status</div>
                        <div class="chart-card__value" id="invoicesTotal">₹0</div>
                        <div class="chart-card__subtitle">Revenue Collection</div>
                    </div>
                    <div class="chart-card__trend" id="invoicesTrend">+0%</div>
                </div>
                <div class="chart-container">
                    <svg class="chart-svg" viewBox="0 0 200 120" id="invoicesChart" preserveAspectRatio="xMidYMid meet"></svg>
                </div>
                <div class="chart-card__meta">
                    <div class="meta-item"><span>Paid:</span><strong id="invoicesPaid">0</strong></div>
                    <div class="meta-item"><span>Unpaid:</span><strong id="invoicesUnpaid">0</strong></div>
                    <div class="meta-item"><span>Overdue:</span><strong id="invoicesOverdue">0</strong></div>
                </div>
            </div>
            <div class="chart-card">
                <div class="chart-card__header">
                    <div class="chart-card__info">
                        <div class="chart-card__icon">📊</div>
                        <div class="chart-card__title">Outstanding by Customer</div>
                        <div class="chart-card__value" id="outstandingTotal">₹0</div>
                        <div class="chart-card__subtitle">Top Customers</div>
                    </div>
                    <div class="chart-card__trend" id="outstandingTrend">+0%</div>
                </div>
                <div class="chart-container">
                    <svg class="chart-svg" viewBox="0 0 200 120" id="outstandingByCustomerChart" preserveAspectRatio="xMidYMid meet"></svg>
                </div>
                <div class="chart-card__meta">
                    <div class="meta-item"><span>Customers:</span><strong id="outstandingCustomers">0</strong></div>
                    <div class="meta-item"><span>Concentration:</span><strong id="concentrationRisk">0%</strong></div>
                    <div class="meta-item"><span>Top 3:</span><strong id="top3Exposure">0%</strong></div>
                </div>
            </div>
            <div class="chart-card">
                <div class="chart-card__header">
                    <div class="chart-card__info">
                        <div class="chart-card__icon">⏳</div>
                        <div class="chart-card__title">Aging Buckets</div>
                        <div class="chart-card__value" id="agingTotal">₹0</div>
                        <div class="chart-card__subtitle">Credit Risk</div>
                    </div>
                    <div class="chart-card__trend" id="agingTrend">+0%</div>
                </div>
                <div class="chart-container">
                    <svg class="chart-svg" viewBox="0 0 200 120" id="agingBucketsChart" preserveAspectRatio="xMidYMid meet"></svg>
                </div>
                <div class="chart-card__meta">
                    <div class="meta-item"><span>0-30d:</span><strong id="aging0to30">0</strong></div>
                    <div class="meta-item"><span>31-60d:</span><strong id="aging31to60">0</strong></div>
                    <div class="meta-item"><span>90+d:</span><strong id="aging90plus">0</strong></div>
                </div>
            </div>
            <div class="chart-card">
                <div class="chart-card__header">
                    <div class="chart-card__info">
                        <div class="chart-card__icon">💳</div>
                        <div class="chart-card__title">Payments Trend</div>
                        <div class="chart-card__value" id="paymentsTotal">₹0</div>
                        <div class="chart-card__subtitle">Cash Flow Pattern</div>
                    </div>
                    <div class="chart-card__trend" id="paymentsTrend">+0%</div>
                </div>
                <div class="chart-container">
                    <svg class="chart-svg" viewBox="0 0 200 120" id="paymentsChart" preserveAspectRatio="xMidYMid meet"></svg>
                </div>
                <div class="chart-card__meta">
                    <div class="meta-item"><span>Velocity:</span><strong id="paymentVelocity">0</strong></div>
                    <div class="meta-item"><span>Avg:</span><strong id="paymentAvg">₹0</strong></div>
                    <div class="meta-item"><span>Count:</span><strong id="paymentCount">0</strong></div>
                </div>
            </div>
        `;
        
        // Inject charts
        gridContainer.innerHTML = chartsHTML;
        console.log('Charts injected into DOM');
        
        // Load chart data
        setTimeout(function() {
            const prefix = document.getElementById('companyPrefix')?.value || '';
            if (prefix && typeof loadAllCharts === 'function') {
                console.log('Loading charts for prefix:', prefix);
                loadAllCharts();
            }
        }, 500);
        
    }, 500);
});
