<?php
// Chart cards configuration
$CHART_CARDS = [
    [
        'id' => 'quotations',
        'icon' => '📝',
        'title' => 'Quotations Overview',
        'subtitle' => 'Quotation Status Count Distribution',
        'valueId' => 'quotationsTotal',
        'trendId' => 'quotationsTrend',
        'chartId' => 'quotationsChart',
        'chartType' => 'pie',
        'meta' => [
            ['label' => 'Placed Quotations:', 'id' => 'placedQuotations'],
            ['label' => 'Rejected Quotations:', 'id' => 'rejectedQuotations'],
            ['label' => 'Pending Quotations:', 'id' => 'pendingQuotations']
        ],
        'legend' => [
            ['color' => '#3b82f6', 'label' => 'Pending (Draft/Revised)'],
            ['color' => '#10b981', 'label' => 'Placed (Approved)'],
            ['color' => '#ef4444', 'label' => 'Rejected']
        ]
    ],
    [
        'id' => 'po',
        'icon' => '🛒',
        'title' => 'Purchase Orders',
        'subtitle' => 'Procurement Commitment Timeline',
        'valueId' => 'poTotal',
        'trendId' => 'poTrendChart',
        'chartId' => 'purchaseOrdersChart',
        'chartType' => 'line',
        'meta' => [
            ['label' => 'Fulfillment Rate:', 'id' => 'poFulfillmentRate'],
            ['label' => 'Avg Lead Time:', 'id' => 'avgLeadTime'],
            ['label' => 'Open Commitments:', 'id' => 'openCommitments']
        ]
    ],
    [
        'id' => 'invoices',
        'icon' => '💰',
        'title' => 'Invoice Status',
        'subtitle' => 'Revenue Collection Health',
        'valueId' => 'invoicesTotal',
        'trendId' => 'invoicesTrendChart',
        'chartId' => 'invoicesChart',
        'chartType' => 'doughnut',
        'meta' => [
            ['label' => 'DSO:', 'id' => 'dsoMetric'],
            ['label' => 'Bad Debt Risk:', 'id' => 'badDebtRisk'],
            ['label' => 'Collection Efficiency:', 'id' => 'collectionEfficiency']
        ],
        'legend' => [
            ['color' => '#10b981', 'label' => 'Paid (Collected)'],
            ['color' => '#f59e0b', 'label' => 'Unpaid (Due)'],
            ['color' => '#ef4444', 'label' => 'Overdue (Risk)']
        ]
    ],
    [
        'id' => 'outstanding',
        'icon' => '📊',
        'title' => 'Outstanding Distribution',
        'subtitle' => 'Top Customer Outstanding Amounts',
        'valueId' => 'outstandingTotal',
        'trendId' => 'outstandingTrend',
        'chartId' => 'outstandingByCustomerChart',
        'chartType' => 'doughnut',
        'meta' => [
            ['label' => 'Concentration Risk:', 'id' => 'concentrationRisk'],
            ['label' => 'Top 3 Exposure:', 'id' => 'top3Exposure'],
            ['label' => 'Customer Diversity:', 'id' => 'customerDiversity']
        ]
    ],
    [
        'id' => 'aging',
        'icon' => '⏳',
        'title' => 'Aging Buckets',
        'subtitle' => 'Credit Risk Assessment Matrix',
        'valueId' => 'agingTotal',
        'trendId' => 'agingTrend',
        'chartId' => 'agingBucketsChart',
        'chartType' => 'doughnut',
        'meta' => [
            ['label' => 'Provision Req:', 'id' => 'provisionRequired'],
            ['label' => 'Recovery Rate:', 'id' => 'recoveryRate'],
            ['label' => 'Credit Quality:', 'id' => 'creditQuality']
        ],
        'legend' => [
            ['color' => '#10b981', 'label' => 'Current (0-30)'],
            ['color' => '#f59e0b', 'label' => 'Watch (31-60)'],
            ['color' => '#fb923c', 'label' => 'Concern (61-90)'],
            ['color' => '#ef4444', 'label' => 'Critical (90+)']
        ]
    ],
    [
        'id' => 'payments',
        'icon' => '💳',
        'title' => 'Payments',
        'subtitle' => 'Cash Flow Realization Pattern',
        'valueId' => 'paymentsTotal',
        'trendId' => 'paymentsTrend',
        'chartId' => 'paymentsChart',
        'chartType' => 'bar',
        'meta' => [
            ['label' => 'Velocity:', 'id' => 'paymentVelocity'],
            ['label' => 'Forecast Accuracy:', 'id' => 'forecastAccuracy'],
            ['label' => 'Cash Conversion:', 'id' => 'cashConversion']
        ]
    ]
];

foreach ($CHART_CARDS as $card): ?>
<div class="chart-card">
    <div class="chart-card__header">
        <div class="chart-card__info">
            <div class="chart-card__icon"><?php echo $card['icon']; ?></div>
            <div class="chart-card__title"><?php echo $card['title']; ?></div>
            <div class="chart-card__value" id="<?php echo $card['valueId']; ?>">0</div>
            <div class="chart-card__subtitle"><?php echo $card['subtitle']; ?></div>
        </div>
        <div class="chart-card__trend" id="<?php echo $card['trendId']; ?>">+0%</div>
    </div>
    <div class="chart-card__chart">
        <canvas id="<?php echo $card['chartId']; ?>"></canvas>
        <?php if (!empty($card['legend'])): ?>
        <div class="chart-legend">
            <?php foreach ($card['legend'] as $item): ?>
            <div class="legend-item"><span class="legend-color" style="background:<?php echo $item['color']; ?>"></span><?php echo $item['label']; ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <div class="chart-card__meta">
        <?php foreach ($card['meta'] as $meta): ?>
        <div class="meta-item"><span><?php echo $meta['label']; ?></span><strong id="<?php echo $meta['id']; ?>">0</strong></div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>
