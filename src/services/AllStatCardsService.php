<?php

class AllStatCardsService {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getAllStats($prefix) {
        return [
            'stat_card_1' => $this->getStatCard1($prefix),
            'stat_card_2' => $this->getStatCard2($prefix),
            'stat_card_3' => $this->getStatCard3($prefix),
            'stat_card_4' => $this->getStatCard4($prefix),
            'stat_card_5' => $this->getStatCard5($prefix),
            'stat_card_6' => $this->getStatCard6($prefix)
        ];
    }
    
    // STAT CARD 1 — Total Invoice Amount
    private function getStatCard1($prefix) {
        $sql = "SELECT 
            COALESCE(SUM(total_amount),0) AS total_invoice_amount,
            COUNT(*) AS invoice_count,
            SUM(CASE WHEN DATE_FORMAT(invoice_date,'%Y-%m') = DATE_FORMAT(CURRENT_DATE - INTERVAL 1 MONTH,'%Y-%m') THEN 1 ELSE 0 END) AS last_month_invoice_count
        FROM finance_invoices
        WHERE invoice_number LIKE CONCAT(:prefix, '%')";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['prefix' => $prefix]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // STAT CARD 2 — Amount Received
    private function getStatCard2($prefix) {
        $sql = "SELECT 
            COALESCE(SUM(amount_paid),0) AS amount_received,
            SUM(CASE WHEN amount_paid > 0 THEN 1 ELSE 0 END) AS paid_invoices,
            COALESCE((
                SELECT SUM(amount_paid) 
                FROM finance_invoices 
                WHERE invoice_number LIKE CONCAT(:prefix2, '%')
                  AND DATE_FORMAT(invoice_date,'%Y-%m') = DATE_FORMAT(CURRENT_DATE - INTERVAL 1 MONTH,'%Y-%m')
            ),0) AS last_month_received
        FROM finance_invoices
        WHERE invoice_number LIKE CONCAT(:prefix, '%')";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['prefix' => $prefix, 'prefix2' => $prefix]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // STAT CARD 3 — Outstanding Amount
    private function getStatCard3($prefix) {
        $sql = "SELECT 
            COALESCE(SUM(total_amount - amount_paid),0) AS total_outstanding,
            SUM(CASE WHEN (total_amount - amount_paid) > 0 THEN 1 ELSE 0 END) AS pending_invoices,
            COUNT(DISTINCT CASE WHEN (total_amount - amount_paid) > 0 THEN customer_id END) AS customers_involved
        FROM finance_invoices
        WHERE invoice_number LIKE CONCAT(:prefix, '%')";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['prefix' => $prefix]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // STAT CARD 4 — GST Liability
    private function getStatCard4($prefix) {
        $sql = "SELECT
            COALESCE(SUM(igst_amount),0) AS igst,
            COALESCE(SUM(cgst_amount + sgst_amount),0) AS cgst_sgst,
            COALESCE(SUM(igst_amount + cgst_amount + sgst_amount),0) AS total_gst
        FROM finance_invoices
        WHERE (total_amount - amount_paid) > 0
          AND invoice_number LIKE CONCAT(:prefix, '%')";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['prefix' => $prefix]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // STAT CARD 5 — Purchase Order Commitments
    private function getStatCard5($prefix) {
        $sql = "SELECT
            COALESCE(SUM(po_total_value),0) AS total_po_commitments,
            SUM(CASE WHEN po_status IN ('Active','Released','Open') THEN 1 ELSE 0 END) AS open_pos,
            SUM(CASE WHEN po_status IN ('Closed','Completed','Cancelled') THEN 1 ELSE 0 END) AS closed_pos
        FROM finance_purchase_orders
        WHERE po_number LIKE CONCAT(:prefix, '%')";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['prefix' => $prefix]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // STAT CARD 6 — Claimable Amount
    private function getStatCard6($prefix) {
        $sql = "SELECT
            COALESCE(SUM(total_amount - amount_paid),0) AS claimable_amount,
            SUM(CASE WHEN (total_amount - amount_paid) > 0 THEN 1 ELSE 0 END) AS claimable_invoices,
            CASE WHEN SUM(total_amount) = 0 THEN 0
                 ELSE (SUM(total_amount - amount_paid) / SUM(total_amount)) * 100 END AS claim_rate
        FROM finance_invoices
        WHERE invoice_number LIKE CONCAT(:prefix, '%')";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['prefix' => $prefix]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}