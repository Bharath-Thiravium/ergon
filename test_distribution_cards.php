<?php
/**
 * Test page for distribution stat cards
 */
require_once __DIR__ . '/app/helpers/AdvanceDistributionHelper.php';

// Sample test data
$sampleAdvances = [
    ['id' => 1, 'status' => 'pending', 'type' => 'Salary Advance', 'amount' => 15000, 'created_at' => '2024-12-01', 'project_name' => 'Project Alpha'],
    ['id' => 2, 'status' => 'approved', 'type' => 'Travel Advance', 'amount' => 8000, 'approved_amount' => 7500, 'created_at' => '2024-12-05', 'project_name' => 'Project Beta'],
    ['id' => 3, 'status' => 'paid', 'type' => 'Emergency Advance', 'amount' => 25000, 'approved_amount' => 25000, 'created_at' => '2024-11-28', 'project_name' => 'Project Alpha'],
    ['id' => 4, 'status' => 'rejected', 'type' => 'Project Advance', 'amount' => 50000, 'created_at' => '2024-11-15', 'project_name' => 'Project Gamma'],
    ['id' => 5, 'status' => 'pending', 'type' => 'Salary Advance', 'amount' => 12000, 'created_at' => '2024-12-10', 'project_name' => 'Project Beta'],
];

$statusDistribution = AdvanceDistributionHelper::getStatusDistribution($sampleAdvances);
$typeDistribution = AdvanceDistributionHelper::getTypeDistribution($sampleAdvances);
$amountRangeDistribution = AdvanceDistributionHelper::getAmountRangeDistribution($sampleAdvances);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Distribution Cards Test</title>
    <link rel="stylesheet" href="/ergon/assets/css/ergon.css">
    <style>
        body { padding: 20px; background: #f5f5f5; }
        .test-container { max-width: 1200px; margin: 0 auto; }
        .dashboard-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
            gap: 20px; 
            margin-bottom: 30px; 
        }
        .kpi-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #ddd;
        }
        .kpi-card__header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .kpi-card__icon {
            font-size: 24px;
        }
        .kpi-card__trend {
            font-size: 12px;
            color: #666;
        }
        .kpi-card__value {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .kpi-card__label {
            font-size: 14px;
            color: #666;
            margin-bottom: 12px;
        }
        .kpi-card--primary { border-left-color: #3b82f6; }
        .kpi-card--info { border-left-color: #06b6d4; }
        .kpi-card--success { border-left-color: #10b981; }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>Distribution Stat Cards Test</h1>
        
        <div class="dashboard-grid">
            <?php
            // Status Distribution Card
            $title = 'Request Status';
            $totalValue = count($sampleAdvances);
            $distributionData = $statusDistribution;
            $icon = '📊';
            $cardClass = 'kpi-card--primary';
            include __DIR__ . '/views/shared/distribution_stat_card.php';
            ?>
            
            <?php
            // Type Distribution Card
            $title = 'Advance Types';
            $totalValue = count($typeDistribution);
            $distributionData = $typeDistribution;
            $icon = '🏷️';
            $cardClass = 'kpi-card--info';
            include __DIR__ . '/views/shared/distribution_stat_card.php';
            ?>
            
            <?php
            // Amount Distribution Card
            $title = 'Amount Ranges';
            $totalValue = array_sum(array_column($sampleAdvances, 'amount')) / count($sampleAdvances);
            $distributionData = $amountRangeDistribution;
            $icon = '💰';
            $cardClass = 'kpi-card--success';
            $valueFormat = 'currency';
            include __DIR__ . '/views/shared/distribution_stat_card.php';
            ?>
        </div>
        
        <h2>Sample Data</h2>
        <pre><?= json_encode($sampleAdvances, JSON_PRETTY_PRINT) ?></pre>
        
        <h2>Distribution Data</h2>
        <h3>Status Distribution</h3>
        <pre><?= json_encode($statusDistribution, JSON_PRETTY_PRINT) ?></pre>
        
        <h3>Type Distribution</h3>
        <pre><?= json_encode($typeDistribution, JSON_PRETTY_PRINT) ?></pre>
        
        <h3>Amount Range Distribution</h3>
        <pre><?= json_encode($amountRangeDistribution, JSON_PRETTY_PRINT) ?></pre>
    </div>
</body>
</html>
