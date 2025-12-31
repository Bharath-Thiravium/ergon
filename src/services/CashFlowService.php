<?php

class CashFlowService {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function calculateExpectedInflow($prefix = null) {
        $sql = "SELECT COALESCE(SUM(total_amount - COALESCE(paid_amount, 0)), 0) as inflow 
                FROM finance_invoices 
                WHERE (total_amount - COALESCE(paid_amount, 0)) > 0";
        
        if ($prefix) {
            $sql .= " AND invoice_number LIKE ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$prefix . '%']);
        } else {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        }
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return floatval($result['inflow'] ?? 0);
    }
    
    public function calculatePOCommitments($prefix = null) {
        $sql = "SELECT COALESCE(SUM(total_amount), 0) as commitments 
                FROM finance_purchase_orders 
                WHERE status IN ('ACTIVE', 'RELEASED', 'Active', 'Released', 'draft')";
        
        if ($prefix) {
            $sql .= " AND internal_po_number LIKE ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$prefix . '%']);
        } else {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        }
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return floatval($result['commitments'] ?? 0);
    }
    
    public function calculateNetCashFlow($expectedInflow, $poCommitments) {
        return $expectedInflow - $poCommitments;
    }
    
    public function getCashFlow($prefix = null) {
        $expectedInflow = $this->calculateExpectedInflow($prefix);
        $poCommitments = $this->calculatePOCommitments($prefix);
        $netCashFlow = $this->calculateNetCashFlow($expectedInflow, $poCommitments);
        
        return [
            'expected_inflow' => $expectedInflow,
            'po_commitments' => $poCommitments,
            'net_cash_flow' => $netCashFlow
        ];
    }
}
