<?php

class AllStatCardsService {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    private function buildMultiFieldCondition($prefix, $fields) {
        if (empty($prefix)) {
            return ['condition' => '1=1', 'params' => []];
        }
        
        $len = strlen($prefix);
        $conditions = [];
        $params = [];
        
        foreach ($fields as $field) {
            $conditions[] = "LEFT($field, $len) = ?";
            $params[] = $prefix;
        }
        
        return [
            'condition' => '(' . implode(' OR ', $conditions) . ')',
            'params' => $params
        ];
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
        $len = strlen($prefix);
        $sql = "SELECT 
            COALESCE(SUM(total_amount),0) AS total_invoice_amount,
            COUNT(*) AS invoice_count,
            SUM(CASE WHEN DATE_FORMAT(invoice_date,'%Y-%m') = DATE_FORMAT(CURRENT_DATE - INTERVAL 1 MONTH,'%Y-%m') THEN 1 ELSE 0 END) AS last_month_invoice_count
        FROM finance_invoices
        WHERE LEFT(invoice_number, $len) = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$prefix]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // STAT CARD 2 — Amount Received
    private function getStatCard2($prefix) {
        $len = strlen($prefix);
        $sql = "SELECT 
            COALESCE(SUM(paid_amount),0) AS amount_received,
            SUM(CASE WHEN paid_amount > 0 THEN 1 ELSE 0 END) AS paid_invoices,
            0 AS last_month_received
        FROM finance_invoices
        WHERE LEFT(invoice_number, $len) = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$prefix]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // STAT CARD 3 — Outstanding Amount  
    private function getStatCard3($prefix) {
        $len = strlen($prefix);
        $sql = "SELECT 
            COALESCE(SUM(total_amount - paid_amount),0) AS total_outstanding,
            SUM(CASE WHEN (total_amount - paid_amount) > 0 THEN 1 ELSE 0 END) AS pending_invoices,
            COUNT(DISTINCT CASE WHEN (total_amount - paid_amount) > 0 THEN customer_id END) AS customers_involved
        FROM finance_invoices
        WHERE LEFT(invoice_number, $len) = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$prefix]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // STAT CARD 4 — GST Liability
    private function getStatCard4($prefix) {
        $len = strlen($prefix);
        $sql = "SELECT
            COALESCE(SUM(igst_amount),0) AS igst,
            COALESCE(SUM(cgst_amount + sgst_amount),0) AS cgst_sgst,
            COALESCE(SUM(igst_amount + cgst_amount + sgst_amount),0) AS total_gst
        FROM finance_invoices
        WHERE (total_amount - paid_amount) > 0
          AND LEFT(invoice_number, $len) = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$prefix]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // STAT CARD 5 — Purchase Order Commitments
    private function getStatCard5($prefix) {
        $len = strlen($prefix);
        $sql = "SELECT
            COALESCE(SUM(total_amount),0) AS total_po_commitments,
            SUM(CASE WHEN status IN ('active','released','open') THEN 1 ELSE 0 END) AS open_pos,
            SUM(CASE WHEN status IN ('closed','completed','cancelled') THEN 1 ELSE 0 END) AS closed_pos
        FROM finance_purchase_orders
        WHERE LEFT(po_number, $len) = ? OR LEFT(internal_po_number, $len) = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$prefix, $prefix]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // STAT CARD 6 — Claimable Amount
    private function getStatCard6($prefix) {
        $len = strlen($prefix);
        $sql = "SELECT
            COALESCE(SUM((total_amount - paid_amount) - total_tax),0) AS claimable_amount,
            SUM(CASE WHEN (total_amount - paid_amount) > 0 THEN 1 ELSE 0 END) AS claimable_invoices,
            CASE WHEN SUM(total_amount) = 0 THEN 0
                 ELSE (SUM(total_amount - paid_amount) / SUM(total_amount)) * 100 END AS claim_rate
        FROM finance_invoices
        WHERE LEFT(invoice_number, $len) = ?
          AND (total_amount - paid_amount) > 0";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$prefix]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}