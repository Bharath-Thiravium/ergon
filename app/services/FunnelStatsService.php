<?php

require_once __DIR__ . '/../config/database.php';

class FunnelStatsService {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function calculateFunnelStats($prefix) {
        try {
            // 1. Quotations
            $stmt = $this->db->prepare("
                SELECT COUNT(*) AS c, COALESCE(SUM(amount), 0) AS v 
                FROM finance_consolidated 
                WHERE company_prefix = ? AND record_type = 'quotation'
            ");
            $stmt->execute([$prefix]);
            $quotations = $stmt->fetch(PDO::FETCH_ASSOC);

            // 2. Purchase Orders
            $stmt = $this->db->prepare("
                SELECT COUNT(*) AS c, COALESCE(SUM(amount), 0) AS v 
                FROM finance_consolidated 
                WHERE company_prefix = ? AND record_type = 'purchase_order'
            ");
            $stmt->execute([$prefix]);
            $pos = $stmt->fetch(PDO::FETCH_ASSOC);

            // 3. Invoices
            $stmt = $this->db->prepare("
                SELECT COUNT(*) AS c, COALESCE(SUM(amount), 0) AS v 
                FROM finance_consolidated 
                WHERE company_prefix = ? AND record_type = 'invoice'
            ");
            $stmt->execute([$prefix]);
            $invoices = $stmt->fetch(PDO::FETCH_ASSOC);

            // 4. Payments (corrected to use payment records)
            $stmt = $this->db->prepare("
                SELECT COUNT(*) AS c, COALESCE(SUM(amount), 0) AS v 
                FROM finance_consolidated 
                WHERE company_prefix = ? AND record_type = 'payment'
            ");
            $stmt->execute([$prefix]);
            $payments = $stmt->fetch(PDO::FETCH_ASSOC);

            // Calculate conversion rates
            $poConversionRate = $quotations['c'] > 0 ? round(($pos['c'] / $quotations['c']) * 100, 2) : 0;
            $invoiceConversionRate = $pos['c'] > 0 ? round(($invoices['c'] / $pos['c']) * 100, 2) : 0;
            $paymentConversionRate = $invoices['c'] > 0 ? round(($payments['c'] / $invoices['c']) * 100, 2) : 0;

            // Store funnel stats
            $stmt = $this->db->prepare("
                INSERT INTO funnel_stats 
                (company_prefix, quotation_count, quotation_value, po_count, po_value, po_conversion_rate, 
                 invoice_count, invoice_value, invoice_conversion_rate, payment_count, payment_value, payment_conversion_rate, generated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
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
                $prefix,
                $quotations['c'], $quotations['v'],
                $pos['c'], $pos['v'], $poConversionRate,
                $invoices['c'], $invoices['v'], $invoiceConversionRate,
                $payments['c'], $payments['v'], $paymentConversionRate
            ]);

            return [
                'success' => true,
                'quotations' => $quotations['c'],
                'pos' => $pos['c'],
                'invoices' => $invoices['c'],
                'payments' => $payments['c'],
                'po_conversion_rate' => $poConversionRate,
                'invoice_conversion_rate' => $invoiceConversionRate,
                'payment_conversion_rate' => $paymentConversionRate
            ];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getFunnelStats($prefix) {
        $stmt = $this->db->prepare("SELECT * FROM funnel_stats WHERE company_prefix = ?");
        $stmt->execute([$prefix]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
