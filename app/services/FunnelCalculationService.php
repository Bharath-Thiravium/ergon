<?php

class FunnelCalculationService {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function calculateFunnelStats($prefix) {
        // 1. Fetch raw quotations
        $quotations = $this->fetchRawQuotations($prefix);
        
        // 2. Fetch raw purchase orders
        $purchaseOrders = $this->fetchRawPurchaseOrders($prefix);
        
        // 3. Fetch raw invoices
        $invoices = $this->fetchRawInvoices($prefix);
        
        // 4. Calculate funnel metrics
        $quotation_count = count($quotations);
        $quotation_value = array_sum(array_column($quotations, 'total_amount'));
        
        $po_count = count($purchaseOrders);
        $po_value = array_sum(array_column($purchaseOrders, 'total_amount'));
        $po_conversion_rate = $quotation_count > 0 ? ($po_count / $quotation_count) * 100 : 0;
        
        $invoice_count = count($invoices);
        $invoice_value = array_sum(array_column($invoices, 'total_amount'));
        $invoice_conversion_rate = $po_count > 0 ? ($invoice_count / $po_count) * 100 : 0;
        
        $payment_count = 0;
        $payment_value = 0;
        foreach ($invoices as $invoice) {
            if ($invoice['amount_paid'] > 0) {
                $payment_count++;
                $payment_value += $invoice['amount_paid'];
            }
        }
        $payment_conversion_rate = $invoice_count > 0 ? ($payment_count / $invoice_count) * 100 : 0;
        
        // 5. Save to funnel_stats
        $this->saveFunnelStats($prefix, [
            'quotation_count' => $quotation_count,
            'quotation_value' => $quotation_value,
            'po_count' => $po_count,
            'po_value' => $po_value,
            'po_conversion_rate' => $po_conversion_rate,
            'invoice_count' => $invoice_count,
            'invoice_value' => $invoice_value,
            'invoice_conversion_rate' => $invoice_conversion_rate,
            'payment_count' => $payment_count,
            'payment_value' => $payment_value,
            'payment_conversion_rate' => $payment_conversion_rate
        ]);
    }
    
    public function calculateChartStats($prefix) {
        // Fetch raw data
        $quotations = $this->fetchRawQuotations($prefix);
        $purchaseOrders = $this->fetchRawPurchaseOrders($prefix);
        $invoices = $this->fetchRawInvoices($prefix);
        
        // Chart Card 1: Quotations
        $quotation_pipeline_draft = count(array_filter($quotations, fn($q) => $q['status'] == 'Draft'));
        $quotation_pipeline_revised = count(array_filter($quotations, fn($q) => $q['status'] == 'Revised'));
        $quotation_pipeline_converted = count(array_filter($quotations, fn($q) => $q['status'] == 'Converted'));
        $pipeline_value = array_sum(array_column($quotations, 'total_amount'));
        $avg_deal_size = count($quotations) > 0 ? $pipeline_value / count($quotations) : 0;
        $win_rate = count($quotations) > 0 ? ($quotation_pipeline_converted / count($quotations)) * 100 : 0;
        
        // Chart Card 2: Purchase Orders
        $po_count = count($purchaseOrders);
        $po_open_commitments = array_sum(array_column(array_filter($purchaseOrders, fn($po) => empty($po['received_date'])), 'total_amount'));
        $fulfilled_pos = array_filter($purchaseOrders, fn($po) => !empty($po['received_date']));
        $po_fulfillment_rate = $po_count > 0 ? (count($fulfilled_pos) / $po_count) * 100 : 0;
        $po_avg_lead_time = $this->calculateAvgLeadTime($fulfilled_pos);
        
        // Chart Card 3: Invoices
        $invoice_paid_count = count(array_filter($invoices, fn($inv) => $inv['amount_paid'] >= $inv['total_amount']));
        $invoice_unpaid_count = count(array_filter($invoices, fn($inv) => $inv['amount_paid'] == 0));
        $invoice_overdue_count = count(array_filter($invoices, fn($inv) => strtotime($inv['due_date']) < time() && $inv['amount_paid'] < $inv['total_amount']));
        $collection_efficiency = count($invoices) > 0 ? ($invoice_paid_count / count($invoices)) * 100 : 0;
        $bad_debt_risk = $this->calculateBadDebtRisk($invoices);
        
        // Chart Card 4: Outstanding Distribution
        $outstanding_data = $this->calculateOutstandingDistribution($invoices);
        
        // Chart Card 5: Aging Buckets
        $aging_data = $this->calculateAgingBuckets($invoices);
        
        // Chart Card 6: Payments
        $payment_total = array_sum(array_column($invoices, 'amount_paid'));
        $payment_velocity_daily = $payment_total / 30; // assuming 30-day period
        $cash_conversion_days = $this->calculateCashConversionDays($invoices);
        
        // Save to chart_stats
        $this->saveChartStats($prefix, array_merge([
            'quotation_pipeline_draft' => $quotation_pipeline_draft,
            'quotation_pipeline_revised' => $quotation_pipeline_revised,
            'quotation_pipeline_converted' => $quotation_pipeline_converted,
            'win_rate' => $win_rate,
            'avg_deal_size' => $avg_deal_size,
            'pipeline_value' => $pipeline_value,
            'po_count' => $po_count,
            'po_fulfillment_rate' => $po_fulfillment_rate,
            'po_avg_lead_time' => $po_avg_lead_time,
            'po_open_commitments' => $po_open_commitments,
            'invoice_paid_count' => $invoice_paid_count,
            'invoice_unpaid_count' => $invoice_unpaid_count,
            'invoice_overdue_count' => $invoice_overdue_count,
            'collection_efficiency' => $collection_efficiency,
            'bad_debt_risk' => $bad_debt_risk,
            'payment_total' => $payment_total,
            'payment_velocity_daily' => $payment_velocity_daily,
            'cash_conversion_days' => $cash_conversion_days
        ], $outstanding_data, $aging_data));
    }
    
    private function fetchRawQuotations($prefix) {
        $stmt = $this->db->prepare("SELECT id, quotation_number, total_amount, status FROM finance_quotations WHERE quotation_number LIKE ?");
        $stmt->execute([$prefix . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function fetchRawPurchaseOrders($prefix) {
        $stmt = $this->db->prepare("SELECT id, po_number, total_amount, approved_date, received_date FROM finance_purchase_orders WHERE po_number LIKE ?");
        $stmt->execute([$prefix . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function fetchRawInvoices($prefix) {
        $stmt = $this->db->prepare("SELECT id, invoice_number, total_amount, amount_paid, due_date, invoice_date FROM finance_invoices WHERE invoice_number LIKE ?");
        $stmt->execute([$prefix . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function calculateAvgLeadTime($fulfilled_pos) {
        if (empty($fulfilled_pos)) return 0;
        $total_days = 0;
        foreach ($fulfilled_pos as $po) {
            if ($po['approved_date'] && $po['received_date']) {
                $total_days += (strtotime($po['received_date']) - strtotime($po['approved_date'])) / 86400;
            }
        }
        return count($fulfilled_pos) > 0 ? $total_days / count($fulfilled_pos) : 0;
    }
    
    private function calculateBadDebtRisk($invoices) {
        $risk = 0;
        foreach ($invoices as $invoice) {
            $days_overdue = (time() - strtotime($invoice['due_date'])) / 86400;
            if ($days_overdue > 90 && $invoice['amount_paid'] < $invoice['total_amount']) {
                $risk += $invoice['total_amount'] - $invoice['amount_paid'];
            }
        }
        return $risk;
    }
    
    private function calculateOutstandingDistribution($invoices) {
        $customer_outstanding = [];
        $outstanding_total = 0;
        
        foreach ($invoices as $invoice) {
            $pending = $invoice['total_amount'] - $invoice['amount_paid'];
            if ($pending > 0) {
                $customer = $invoice['customer_name'] ?? 'Unknown';
                $customer_outstanding[$customer] = ($customer_outstanding[$customer] ?? 0) + $pending;
                $outstanding_total += $pending;
            }
        }
        
        arsort($customer_outstanding);
        $top_customer_outstanding = reset($customer_outstanding) ?: 0;
        $top3_exposure = array_sum(array_slice($customer_outstanding, 0, 3, true));
        $concentration_risk = $outstanding_total > 0 ? ($top_customer_outstanding / $outstanding_total) * 100 : 0;
        
        return [
            'outstanding_total' => $outstanding_total,
            'top_customer_outstanding' => $top_customer_outstanding,
            'concentration_risk' => $concentration_risk,
            'top3_exposure' => $top3_exposure,
            'customer_diversity' => count($customer_outstanding)
        ];
    }
    
    private function calculateAgingBuckets($invoices) {
        $aging_current = $aging_watch = $aging_concern = $aging_critical = 0;
        $total_paid = $total_amount = 0;
        
        foreach ($invoices as $invoice) {
            $pending = $invoice['total_amount'] - $invoice['amount_paid'];
            if ($pending > 0) {
                $age = (time() - strtotime($invoice['due_date'])) / 86400;
                
                if ($age <= 30) $aging_current += $pending;
                elseif ($age <= 60) $aging_watch += $pending;
                elseif ($age <= 90) $aging_concern += $pending;
                else $aging_critical += $pending;
            }
            
            $total_paid += $invoice['amount_paid'];
            $total_amount += $invoice['total_amount'];
        }
        
        $provision_required = $aging_critical * 0.50;
        $recovery_rate = $total_amount > 0 ? ($total_paid / $total_amount) * 100 : 0;
        
        $credit_quality = 'Good';
        if ($aging_critical > $aging_current) $credit_quality = 'Poor';
        elseif ($aging_concern > ($aging_current * 0.5)) $credit_quality = 'Moderate';
        
        return [
            'aging_current' => $aging_current,
            'aging_watch' => $aging_watch,
            'aging_concern' => $aging_concern,
            'aging_critical' => $aging_critical,
            'provision_required' => $provision_required,
            'recovery_rate' => $recovery_rate,
            'credit_quality' => $credit_quality
        ];
    }
    
    private function calculateCashConversionDays($invoices) {
        $total_days = 0;
        $paid_invoices = 0;
        
        foreach ($invoices as $invoice) {
            if ($invoice['amount_paid'] > 0) {
                // Assuming payment date is calculated from invoice date + some logic
                $days = 30; // placeholder - would need actual payment date
                $total_days += $days;
                $paid_invoices++;
            }
        }
        
        return $paid_invoices > 0 ? $total_days / $paid_invoices : 0;
    }
    
    private function saveFunnelStats($prefix, $data) {
        $sql = "INSERT INTO funnel_stats (company_prefix, quotation_count, quotation_value, po_count, po_value, po_conversion_rate, invoice_count, invoice_value, invoice_conversion_rate, payment_count, payment_value, payment_conversion_rate) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                quotation_count=VALUES(quotation_count), quotation_value=VALUES(quotation_value), 
                po_count=VALUES(po_count), po_value=VALUES(po_value), po_conversion_rate=VALUES(po_conversion_rate),
                invoice_count=VALUES(invoice_count), invoice_value=VALUES(invoice_value), invoice_conversion_rate=VALUES(invoice_conversion_rate),
                payment_count=VALUES(payment_count), payment_value=VALUES(payment_value), payment_conversion_rate=VALUES(payment_conversion_rate),
                generated_at=CURRENT_TIMESTAMP";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $prefix, $data['quotation_count'], $data['quotation_value'], $data['po_count'], $data['po_value'], $data['po_conversion_rate'],
            $data['invoice_count'], $data['invoice_value'], $data['invoice_conversion_rate'], $data['payment_count'], $data['payment_value'], $data['payment_conversion_rate']
        ]);
    }
    
    private function saveChartStats($prefix, $data) {
        $fields = array_keys($data);
        $placeholders = str_repeat('?,', count($fields) - 1) . '?';
        $updates = implode(',', array_map(fn($field) => "$field=VALUES($field)", $fields));
        
        $sql = "INSERT INTO chart_stats (company_prefix, " . implode(',', $fields) . ") 
                VALUES (?, $placeholders) 
                ON DUPLICATE KEY UPDATE $updates, generated_at=CURRENT_TIMESTAMP";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge([$prefix], array_values($data)));
    }
    
    public function getFunnelStats($prefix) {
        $stmt = $this->db->prepare("SELECT * FROM funnel_stats WHERE company_prefix = ? ORDER BY generated_at DESC LIMIT 1");
        $stmt->execute([$prefix]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getChartStats($prefix) {
        $stmt = $this->db->prepare("SELECT * FROM chart_stats WHERE company_prefix = ? ORDER BY generated_at DESC LIMIT 1");
        $stmt->execute([$prefix]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>