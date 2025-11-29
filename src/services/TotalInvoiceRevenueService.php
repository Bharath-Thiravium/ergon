<?php

class TotalInvoiceRevenueService {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function calculateTotalInvoiceRevenue($companyPrefix) {
        // Blueprint SQL Query
        $sql = "SELECT 
                    SUM(total_amount) AS total_revenue,
                    COUNT(id) AS total_invoice_count,
                    AVG(total_amount) AS average_invoice_value
                FROM finance_invoices
                WHERE invoice_number LIKE ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$companyPrefix . '%']);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'total_revenue' => (float)($result['total_revenue'] ?? 0),
            'invoice_count' => (int)($result['total_invoice_count'] ?? 0),
            'average_invoice_value' => (float)($result['average_invoice_value'] ?? 0)
        ];
    }
}