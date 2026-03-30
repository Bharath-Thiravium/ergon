<?php
/**
 * Helper class for calculating expense distribution data
 * Provides various distribution calculations for expense management dashboard
 */
class ExpenseDistributionHelper {
    
    /**
     * Calculate finance totals for expense dashboard
     */
    public static function getFinanceTotals($expenses) {
        $totals = [
            'total_submitted_amount' => 0,
            'pending_review_amount' => 0,
            'approved_unreimbursed_amount' => 0,
            'total_reimbursed_amount' => 0,
            'total_claim_count' => count($expenses)
        ];
        
        foreach ($expenses as $expense) {
            $amount = floatval($expense['amount'] ?? 0);
            $approvedAmount = floatval($expense['approved_amount'] ?? $amount);
            $status = $expense['status'] ?? 'pending';
            
            $totals['total_submitted_amount'] += $amount;
            
            switch ($status) {
                case 'pending':
                    $totals['pending_review_amount'] += $amount;
                    break;
                case 'approved':
                    $totals['approved_unreimbursed_amount'] += $approvedAmount;
                    break;
                case 'paid':
                    $totals['total_reimbursed_amount'] += $approvedAmount;
                    break;
            }
        }
        
        return $totals;
    }
    
    /**
     * Get status distribution by amount
     */
    public static function getStatusDistributionByAmount($expenses, $filterStatus = null) {
        $statusAmounts = [];
        
        foreach ($expenses as $expense) {
            $status = ucfirst($expense['status'] ?? 'pending');
            $amount = floatval($expense['approved_amount'] ?? $expense['amount'] ?? 0);
            
            if ($filterStatus && $expense['status'] !== $filterStatus) continue;
            
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
    
    /**
     * Get category distribution by amount for specific status
     */
    public static function getCategoryDistributionByAmount($expenses, $filterStatus = null) {
        $categoryAmounts = [];
        $categoryNames = [
            'travel' => 'Travel',
            'food' => 'Food & Meals',
            'accommodation' => 'Accommodation',
            'office_supplies' => 'Office Supplies',
            'communication' => 'Communication',
            'training' => 'Training',
            'medical' => 'Medical',
            'other' => 'Other'
        ];
        
        foreach ($expenses as $expense) {
            if ($filterStatus && $expense['status'] !== $filterStatus) continue;
            
            $category = $expense['category'] ?? 'other';
            $amount = floatval($expense['approved_amount'] ?? $expense['amount'] ?? 0);
            
            $categoryAmounts[$category] = ($categoryAmounts[$category] ?? 0) + $amount;
        }
        
        $total = array_sum($categoryAmounts);
        $distribution = [];
        
        foreach ($categoryAmounts as $category => $amount) {
            $percentage = $total > 0 ? ($amount / $total) * 100 : 0;
            $distribution[] = [
                'label' => $categoryNames[$category] ?? ucfirst($category),
                'value' => round($percentage, 1),
                'amount' => $amount
            ];
        }
        
        return $distribution;
    }
    
    /**
     * Calculate status distribution by count
     */
    public static function getStatusDistribution($expenses) {
        $statusCounts = [];
        
        foreach ($expenses as $expense) {
            $status = ucfirst($expense['status'] ?? 'pending');
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
     * Get employee distribution for approved expenses
     */
    public static function getEmployeeDistribution($expenses, $filterStatus = 'approved') {
        $employeeAmounts = [];
        
        foreach ($expenses as $expense) {
            if ($expense['status'] !== $filterStatus) continue;
            
            $employeeName = $expense['user_name'] ?? 'Unknown';
            $amount = floatval($expense['approved_amount'] ?? $expense['amount'] ?? 0);
            
            $employeeAmounts[$employeeName] = ($employeeAmounts[$employeeName] ?? 0) + $amount;
        }
        
        // Sort by amount and take top 5
        arsort($employeeAmounts);
        $topEmployees = array_slice($employeeAmounts, 0, 5, true);
        
        $total = array_sum($topEmployees);
        $distribution = [];
        
        foreach ($topEmployees as $employee => $amount) {
            $percentage = $total > 0 ? ($amount / $total) * 100 : 0;
            $shortName = strlen($employee) > 12 ? substr($employee, 0, 12) . '...' : $employee;
            $distribution[] = [
                'label' => $shortName,
                'value' => round($percentage, 1),
                'amount' => $amount
            ];
        }
        
        return $distribution;
    }
}
?>
