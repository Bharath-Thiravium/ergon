<?php
/**
 * Reusable Distribution Stat Card Component
 * Displays a stat card with mini distribution visualization
 */

// Default values
$title = $title ?? 'Distribution';
$totalValue = $totalValue ?? 0;
$distributionData = $distributionData ?? [];
$chartType = $chartType ?? 'donut';
$icon = $icon ?? '📊';
$cardClass = $cardClass ?? '';
$valueFormat = $valueFormat ?? 'number';
$primaryLabel = $primaryLabel ?? '';
$colors = $colors ?? ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#84cc16'];

// Calculate total for percentages
$total = array_sum(array_column($distributionData, 'value'));
$hasData = $total > 0;

// Format the main value
$formattedValue = match($valueFormat) {
    'currency' => '₹' . number_format($totalValue, 2),
    'percentage' => number_format($totalValue, 1) . '%',
    default => number_format($totalValue)
};
?>

<div class="kpi-card <?= $cardClass ?>">
    <div class="kpi-card__header">
        <div class="kpi-card__icon"><?= $icon ?></div>
        <div class="kpi-card__trend"><?= htmlspecialchars($title) ?></div>
    </div>
    <div class="kpi-card__value"><?= $formattedValue ?></div>
    <div class="kpi-card__label"><?= htmlspecialchars($primaryLabel ?: $title) ?></div>
    
    <?php if ($hasData): ?>
        <div class="kpi-card__chart" id="chart_<?= md5($title . $index) ?>" style="height: 80px; margin-top: 8px;">
            <?php if ($chartType === 'donut'): ?>
                <!-- Donut Chart -->
                <div class="donut-chart">
                    <svg width="60" height="60" viewBox="0 0 42 42" style="margin: 0 auto; display: block;">
                        <?php
                        $offset = 0;
                        $radius = 15.915;
                        $circumference = 2 * pi() * $radius;
                        
                        foreach ($distributionData as $index => $item):
                            $percentage = ($item['value'] / $total) * 100;
                            $strokeDasharray = ($percentage / 100) * $circumference;
                            $strokeDashoffset = -$offset;
                            $offset += $strokeDasharray;
                            $color = $colors[$index % count($colors)];
                        ?>
                        <circle cx="21" cy="21" r="<?= $radius ?>" 
                                fill="transparent" 
                                stroke="<?= $color ?>" 
                                stroke-width="3"
                                stroke-dasharray="<?= $strokeDasharray ?> <?= $circumference ?>"
                                stroke-dashoffset="<?= $strokeDashoffset ?>"
                                transform="rotate(-90 21 21)"
                                class="chart-segment" data-index="<?= $index ?>"
                                style="cursor: pointer; transition: all 0.2s ease;"
                                title="<?= htmlspecialchars($item['label']) ?>: <?= number_format(($item['value'] / $total) * 100, 1) ?>%">
                        </circle>
                        <?php endforeach; ?>
                    </svg>
                </div>
            <?php else: ?>
                <!-- Horizontal Bar Chart -->
                <div class="bar-chart" style="display: flex; height: 20px; border-radius: 10px; overflow: hidden; margin: 10px 0;">
                    <?php foreach ($distributionData as $index => $item): 
                        $percentage = ($item['value'] / $total) * 100;
                        $color = $colors[$index % count($colors)];
                    ?>
                    <div class="chart-segment" data-index="<?= $index ?>"
                         style="background: <?= $color ?>; width: <?= $percentage ?>%; height: 100%; cursor: pointer; transition: all 0.2s ease;" 
                         title="<?= htmlspecialchars($item['label']) ?>: <?= number_format($percentage, 1) ?>%"></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Legend -->
            <div class="chart-legend" style="display: flex; flex-wrap: wrap; gap: 6px; justify-content: center; margin-top: 8px;">
                <?php foreach ($distributionData as $index => $item): 
                    $percentage = isset($item['value']) ? $item['value'] : (($item['amount'] ?? $item['count'] ?? 0) / $total) * 100;
                    $color = $colors[$index % count($colors)];
                    $shortLabel = strlen($item['label']) > 10 ? substr($item['label'], 0, 10) . '...' : $item['label'];
                ?>
                <div class="legend-item" data-index="<?= $index ?>"
                     style="display: flex; align-items: center; gap: 3px; font-size: 10px; cursor: pointer; padding: 2px 4px; border-radius: 4px; transition: all 0.2s ease;" 
                     title="<?= htmlspecialchars($item['label']) ?>: <?= number_format($percentage, 1) ?>%">
                    <div style="width: 8px; height: 8px; background: <?= $color ?>; border-radius: 50%;"></div>
                    <span style="color: #666;"><?= htmlspecialchars($shortLabel) ?>: <?= number_format($percentage, 1) ?>%</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="kpi-card__chart" style="height: 80px; margin-top: 8px; display: flex; align-items: center; justify-content: center;">
            <div style="text-align: center; color: #666; font-size: 12px;">No data available</div>
        </div>
    <?php endif; ?>
</div>

<style>
.donut-chart {
    position: relative;
}

.chart-legend {
    max-height: 50px;
    overflow: hidden;
    font-size: 11px;
}

.kpi-card {
    transition: transform 0.2s ease;
    border: 1px solid #e5e7eb;
}

.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.kpi-card:hover .kpi-card__chart {
    transform: scale(1.02);
    transition: transform 0.2s ease;
}

.chart-segment.highlighted {
    stroke-width: 5 !important;
    filter: brightness(1.2) !important;
}

.bar-chart .chart-segment.highlighted {
    opacity: 0.8 !important;
    transform: scaleY(1.1) !important;
}

.legend-item.highlighted {
    background-color: #f3f4f6 !important;
    transform: scale(1.1) !important;
    font-weight: bold !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Process each chart container separately
    document.querySelectorAll('.kpi-card__chart').forEach(chartContainer => {
        // Add hover listeners for chart segments in this container
        chartContainer.querySelectorAll('.chart-segment').forEach(segment => {
            segment.addEventListener('mouseenter', function() {
                const index = this.dataset.index;
                highlightElements(index, chartContainer);
            });
            
            segment.addEventListener('mouseleave', function() {
                clearHighlights(chartContainer);
            });
        });
        
        // Add hover listeners for legend items in this container
        chartContainer.querySelectorAll('.legend-item').forEach(item => {
            item.addEventListener('mouseenter', function() {
                const index = this.dataset.index;
                highlightElements(index, chartContainer);
            });
            
            item.addEventListener('mouseleave', function() {
                clearHighlights(chartContainer);
            });
        });
    });
    
    function highlightElements(index, chartContainer) {
        // Clear existing highlights in this chart only
        clearHighlights(chartContainer);
        // Highlight corresponding elements in this chart
        chartContainer.querySelectorAll(`[data-index="${index}"]`).forEach(el => {
            el.classList.add('highlighted');
        });
    }
    
    function clearHighlights(chartContainer) {
        chartContainer.querySelectorAll('.highlighted').forEach(el => {
            el.classList.remove('highlighted');
        });
    }
});
</script>
