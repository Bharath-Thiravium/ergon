<?php
/**
 * Helper class for calculating advance distribution data
 * Provides various distribution calculations for advance requests dashboard
 */
class AdvanceDistributionHelper {
    
    /**
     * Calculate status distribution by count
     */
    public static function getStatusDistribution($advances) {
        $statusCounts = [];
        
        foreach ($advances as $advance) {
            $status = ucfirst($advance['status'] ?? 'pending');
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
        }
        
        $total = array_sum($statusCounts);
        $distribution = [];
        
        foreach ($statusCounts as $status => $count) {
            $percentage = $total > 0 ? ($count / $total) * 100 : 0;
            $distribution[] = [
                'label' => $status,
                'value' => round($percentage, 1),
                'count' => $count
            ];
        }
        
        return $distribution;
    }
    
    /**
     * Get type distribution by amount for specific status
     */
    public static function getTypeDistributionByAmount($advances, $filterStatus = null) {
        $typeAmounts = [];
        
        foreach ($advances as $advance) {
            if ($filterStatus && $advance['status'] !== $filterStatus) continue;
            
            $type = $advance['type'] ?? 'General Advance';
            $amount = floatval($advance['approved_amount'] ?? $advance['amount'] ?? 0);
            
            $typeAmounts[$type] = ($typeAmounts[$type] ?? 0) + $amount;
        }
        
        $total = array_sum($typeAmounts);
        $distribution = [];
        
        foreach ($typeAmounts as $type => $amount) {
            $percentage = $total > 0 ? ($amount / $total) * 100 : 0;
            $distribution[] = [
                'label' => str_replace(' Advance', '', $type),
                'value' => round($percentage, 1),
                'amount' => $amount
            ];
        }
        
        return $distribution;
    }
    
    /**
     * Calculate monthly trend distribution
     */
    public static function getMonthlyDistribution($advances, $months = 6) {
        $monthlyData = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-{$i} months"));
            $monthlyData[$month] = ['count' => 0, 'amount' => 0];
        }
        
        foreach ($advances as $advance) {
            $createdMonth = date('Y-m', strtotime($advance['created_at'] ?? 'now'));
            if (isset($monthlyData[$createdMonth])) {
                $monthlyData[$createdMonth]['count']++;
                $monthlyData[$createdMonth]['amount'] += floatval($advance['approved_amount'] ?? $advance['amount'] ?? 0);
            }
        }
        
        $distribution = [];
        foreach ($monthlyData as $month => $data) {
            $distribution[] = [
                'label' => date('M Y', strtotime($month . '-01')),
                'value' => $data['count'],
                'amount' => $data['amount']
            ];
        }
        
        return $distribution;
    }
    
    /**
     * Calculate amount range distribution
     */
    public static function getAmountRangeDistribution($advances) {
        $ranges = [
            '0-5K' => ['min' => 0, 'max' => 5000],
            '5K-15K' => ['min' => 5000, 'max' => 15000],
            '15K-30K' => ['min' => 15000, 'max' => 30000],
            '30K+' => ['min' => 30000, 'max' => PHP_INT_MAX]
        ];
        
        $rangeCounts = array_fill_keys(array_keys($ranges), 0);
        $rangeAmounts = array_fill_keys(array_keys($ranges), 0);
        
        foreach ($advances as $advance) {
            $amount = floatval($advance['approved_amount'] ?? $advance['amount'] ?? 0);
            
            foreach ($ranges as $rangeLabel => $range) {
                if ($amount >= $range['min'] && $amount < $range['max']) {
                    $rangeCounts[$rangeLabel]++;
                    $rangeAmounts[$rangeLabel] += $amount;
                    break;
                }
            }
        }
        
        $distribution = [];
        foreach ($rangeCounts as $range => $count) {
            if ($count > 0) { // Only include ranges with data
                $distribution[] = [
                    'label' => $range,
                    'value' => $count,
                    'amount' => $rangeAmounts[$range]
                ];
            }
        }
        
        return $distribution;
    }
    
    /**
     * Get approved vs pending distribution for current month
     */
    public static function getCurrentMonthDistribution($advances) {
        $currentMonth = date('Y-m');
        $currentMonthAdvances = array_filter($advances, function($advance) use ($currentMonth) {
            return date('Y-m', strtotime($advance['created_at'] ?? 'now')) === $currentMonth;
        });
        
        return self::getStatusDistribution($currentMonthAdvances);
    }
    
    /**
     * Calculate project-based distribution
     */
    public static function getProjectDistribution($advances) {
        $projectCounts = [];
        $projectAmounts = [];
        
        foreach ($advances as $advance) {
            $projectName = !empty($advance['project_name']) ? $advance['project_name'] : 'No Project';
            $amount = floatval($advance['approved_amount'] ?? $advance['amount'] ?? 0);
            
            $projectCounts[$projectName] = ($projectCounts[$projectName] ?? 0) + 1;
            $projectAmounts[$projectName] = ($projectAmounts[$projectName] ?? 0) + $amount;
        }
        
        // Sort by count and take top 5
        arsort($projectCounts);
        $topProjects = array_slice($projectCounts, 0, 5, true);
        
        $distribution = [];
        foreach ($topProjects as $project => $count) {
            $distribution[] = [
                'label' => strlen($project) > 15 ? substr($project, 0, 15) . '...' : $project,
                'value' => $count,
                'amount' => $projectAmounts[$project] ?? 0
            ];
        }
        
        return $distribution;
    }
    
    /**
     * Calculate finance totals for dashboard
     */
    public static function getFinanceTotals($advances) {
        $totals = [
            'total_requested_amount' => 0,
            'pending_approval_amount' => 0,
            'approved_unpaid_amount' => 0,
            'total_paid_amount' => 0,
            'pending_request_count' => 0
        ];
        
        foreach ($advances as $advance) {
            $amount = floatval($advance['amount'] ?? 0);
            $approvedAmount = floatval($advance['approved_amount'] ?? $amount);
            $status = $advance['status'] ?? 'pending';
            
            $totals['total_requested_amount'] += $amount;
            
            switch ($status) {
                case 'pending':
                    $totals['pending_approval_amount'] += $amount;
                    $totals['pending_request_count']++;
                    break;
                case 'approved':
                    $totals['approved_unpaid_amount'] += $approvedAmount;
                    break;
                case 'paid':
                    $totals['total_paid_amount'] += $approvedAmount;
                    break;
            }
        }
        
        return $totals;
    }
    
    /**
     * Get status distribution for specific amount
     */
    public static function getStatusDistributionByAmount($advances, $filterStatus = null) {
        $statusAmounts = [];
        
        foreach ($advances as $advance) {
            $status = ucfirst($advance['status'] ?? 'pending');
            $amount = floatval($advance['approved_amount'] ?? $advance['amount'] ?? 0);
            
            if ($filterStatus && $advance['status'] !== $filterStatus) continue;
            
            $statusAmounts[$status] = ($statusAmounts[$status] ?? 0) + $amount;
        }
        
        $total = array_sum($statusAmounts);
        $distribution = [];
        
        foreach ($statusAmounts as $status => $amount) {
            $percentage = $total > 0 ? ($amount / $total) * 100 : 0;
            $distribution[] = [
                'label' => $status,
                'value' => round($percentage, 1),
                'amount' => $amount
            ];
        }
        
        return $distribution;
    }
}
?>
