<?php
require_once __DIR__ . '/../config/database.php';

class FunnelStatsService {
    
    public function createFunnelStatsTable($db) {
        $sql = "CREATE TABLE IF NOT EXISTS funnel_stats (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_prefix VARCHAR(10),
            quotation_count INT DEFAULT 0,
            quotation_value DECIMAL(15,2) DEFAULT 0,
            po_count INT DEFAULT 0,
            po_value DECIMAL(15,2) DEFAULT 0,
            po_conversion_rate DECIMAL(5,2) DEFAULT 0,
            invoice_count INT DEFAULT 0,
            invoice_value DECIMAL(15,2) DEFAULT 0,
            invoice_conversion_rate DECIMAL(5,2) DEFAULT 0,
            payment_count INT DEFAULT 0,
            payment_value DECIMAL(15,2) DEFAULT 0,
            payment_conversion_rate DECIMAL(5,2) DEFAULT 0,
            generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_prefix (company_prefix)
        )";
        $db->exec($sql);
    }
    
    public function calculateFunnelStats($prefix) {
        $db = Database::connect();
        $this->createFunnelStatsTable($db);
        
        // 1. Fetch Raw Quotations (NO AGGREGATE SQL)
        $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_quotations'");
        $stmt->execute();
        $quotationRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $quotations = [];
        foreach ($quotationRows as $row) {
            $data = json_decode($row['data'], true);
            $quotationNumber = $data['quotation_number'] ?? '';
            if (!$prefix || strpos($quotationNumber, $prefix) === 0) {
                $quotations[] = [
                    'id' => $data['id'] ?? '',
                    'quotation_number' => $quotationNumber,
                    'total_amount' => floatval($data['total_amount'] ?? $data['amount'] ?? 0)
                ];
            }
        }
        
        // Backend computes quotation stats
        $quotation_count = count($quotations);
        $quotation_value = array_sum(array_column($quotations, 'total_amount'));
        
        // 2. Fetch Raw Purchase Orders (NO AGGREGATE SQL)
        $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_purchase_orders'");
        $stmt->execute();
        $poRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $pos = [];
        foreach ($poRows as $row) {
            $data = json_decode($row['data'], true);
            $poNumber = $data['po_number'] ?? $data['internal_po_number'] ?? '';
            if (!$prefix || stripos($poNumber, $prefix) !== false) {
                $pos[] = [
                    'id' => $data['id'] ?? '',
                    'po_number' => $poNumber,
                    'total_amount' => floatval($data['total_amount'] ?? $data['amount'] ?? 0),
                    'amount_paid' => floatval($data['amount_paid'] ?? 0)
                ];
            }
        }
        
        // Backend computes PO stats
        $po_count = count($pos);
        $po_value = array_sum(array_column($pos, 'total_amount'));
        $po_conversion_rate = $quotation_count > 0 ? round(($po_count / $quotation_count) * 100, 2) : 0;
        
        // 3. Fetch Raw Invoices (NO AGGREGATE SQL)
        $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices'");
        $stmt->execute();
        $invoiceRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $invoices = [];
        foreach ($invoiceRows as $row) {
            $data = json_decode($row['data'], true);
            $invoiceNumber = $data['invoice_number'] ?? '';
            if (!$prefix || strpos($invoiceNumber, $prefix) === 0) {
                $invoices[] = [
                    'id' => $data['id'] ?? '',
                    'invoice_number' => $invoiceNumber,
                    'total_amount' => floatval($data['total_amount'] ?? $data['amount'] ?? 0),
                    'amount_paid' => floatval($data['amount_paid'] ?? 0)
                ];
            }
        }
        
        // Backend computes invoice stats
        $invoice_count = count($invoices);
        $invoice_value = array_sum(array_column($invoices, 'total_amount'));
        $invoice_conversion_rate = $po_count > 0 ? round(($invoice_count / $po_count) * 100, 2) : 0;
        
        // 4. Backend computes payment stats from invoices
        $payment_value = array_sum(array_column($invoices, 'amount_paid'));
        $payment_count = count(array_filter($invoices, function($inv) { return $inv['amount_paid'] > 0; }));
        $payment_conversion_rate = $invoice_count > 0 ? round(($payment_count / $invoice_count) * 100, 2) : 0;
        
        // 5. Save calculated results into funnel_stats
        $stmt = $db->prepare("
            INSERT INTO funnel_stats (
                company_prefix, quotation_count, quotation_value,
                po_count, po_value, po_conversion_rate,
                invoice_count, invoice_value, invoice_conversion_rate,
                payment_count, payment_value, payment_conversion_rate,
                generated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                quotation_count = VALUES(quotation_count),
                quotation_value = VALUES(quotation_value),
                po_count = VALUES(po_count),
                po_value = VALUES(po_value),
                po_conversion_rate = VALUES(po_conversion_rate),
                invoice_count = VALUES(invoice_count),
                invoice_value = VALUES(invoice_value),
                invoice_conversion_rate = VALUES(invoice_conversion_rate),
                payment_count = VALUES(payment_count),
                payment_value = VALUES(payment_value),
                payment_conversion_rate = VALUES(payment_conversion_rate),
                generated_at = NOW()
        ");
        
        $stmt->execute([
            $prefix, $quotation_count, $quotation_value,
            $po_count, $po_value, $po_conversion_rate,
            $invoice_count, $invoice_value, $invoice_conversion_rate,
            $payment_count, $payment_value, $payment_conversion_rate
        ]);
        
        return [
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
        ];
    }
    
    public function getFunnelStats($prefix) {
        $db = Database::connect();
        $this->createFunnelStatsTable($db);
        
        // UI reads ONLY from funnel_stats
        $stmt = $db->prepare("
            SELECT * FROM funnel_stats 
            WHERE company_prefix = ? 
            ORDER BY generated_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$prefix]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            // Calculate if no data exists
            return $this->calculateFunnelStats($prefix);
        }
        
        return $result;
    }
    
    public function getFunnelContainers($prefix) {
        $stats = $this->getFunnelStats($prefix);
        
        return [
            'container1' => [
                'title' => 'Quotations',
                'quotations_count' => $stats['quotation_count'],
                'quotations_total_value' => $stats['quotation_value']
            ],
            'container2' => [
                'title' => 'Purchase Orders',
                'po_count' => $stats['po_count'],
                'po_total_value' => $stats['po_value'],
                'po_conversion_rate' => $stats['po_conversion_rate']
            ],
            'container3' => [
                'title' => 'Invoices',
                'invoice_count' => $stats['invoice_count'],
                'invoice_total_value' => $stats['invoice_value'],
                'invoice_conversion_rate' => $stats['invoice_conversion_rate']
            ],
            'container4' => [
                'title' => 'Payments',
                'payment_count' => $stats['payment_count'],
                'total_payment_received' => $stats['payment_value'],
                'payment_conversion_rate' => $stats['payment_conversion_rate']
            ]
        ];
    }
}
?>