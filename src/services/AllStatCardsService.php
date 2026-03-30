<?php

class AllStatCardsService {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    private function resolveCompanyId(string $prefix): ?int {
        $prefix = strtoupper(trim($prefix));
        if (!$prefix) return null;
        $stmt = $this->pdo->prepare('SELECT company_id FROM finance_companies WHERE UPPER(company_prefix) = ? LIMIT 1');
        $stmt->execute([$prefix]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) return (int)$row['company_id'];
        $stmt = $this->pdo->prepare('SELECT company_id FROM finance_companies WHERE UPPER(company_prefix) LIKE ? ORDER BY LENGTH(company_prefix) ASC LIMIT 1');
        $stmt->execute([$prefix . '%']);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['company_id'] : null;
    }

    public function getAllStats($prefix) {
        $companyId = $this->resolveCompanyId($prefix);
        return [
            'stat_card_1' => $this->getStatCard1($companyId),
            'stat_card_2' => $this->getStatCard2($companyId),
            'stat_card_3' => $this->getStatCard3($companyId),
            'stat_card_4' => $this->getStatCard4($companyId),
            'stat_card_5' => $this->getStatCard5($companyId),
            'stat_card_6' => $this->getStatCard6($companyId),
        ];
    }

    private function empty1() { return ['total_invoice_amount' => 0, 'invoice_count' => 0]; }
    private function empty2() { return ['amount_received' => 0, 'paid_invoices' => 0]; }
    private function empty3() { return ['total_outstanding' => 0, 'pending_invoices' => 0, 'customers_involved' => 0]; }
    private function empty4() { return ['igst' => 0, 'cgst_sgst' => 0, 'total_gst' => 0]; }
    private function empty5() { return ['total_po_commitments' => 0, 'open_pos' => 0, 'closed_pos' => 0]; }
    private function empty6() { return ['claimable_amount' => 0, 'claimable_invoices' => 0, 'claim_rate' => 0]; }

    private function getStatCard1($companyId) {
        if (!$companyId) return $this->empty1();
        $stmt = $this->pdo->prepare("SELECT
            COALESCE(SUM(total_amount),0) AS total_invoice_amount,
            COUNT(*) AS invoice_count
            FROM finance_invoices WHERE company_id = ?");
        $stmt->execute([$companyId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getStatCard2($companyId) {
        if (!$companyId) return $this->empty2();
        $stmt = $this->pdo->prepare("SELECT
            COALESCE(SUM(amount_paid),0) AS amount_received,
            SUM(CASE WHEN amount_paid > 0 THEN 1 ELSE 0 END) AS paid_invoices
            FROM finance_invoices WHERE company_id = ?");
        $stmt->execute([$companyId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getStatCard3($companyId) {
        if (!$companyId) return $this->empty3();
        $stmt = $this->pdo->prepare("SELECT
            COALESCE(SUM(outstanding_amount),0) AS total_outstanding,
            SUM(CASE WHEN outstanding_amount > 0 THEN 1 ELSE 0 END) AS pending_invoices,
            COUNT(DISTINCT CASE WHEN outstanding_amount > 0 THEN customer_id END) AS customers_involved
            FROM finance_invoices WHERE company_id = ?");
        $stmt->execute([$companyId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getStatCard4($companyId) {
        if (!$companyId) return $this->empty4();
        $stmt = $this->pdo->prepare("SELECT
            COALESCE(SUM(igst_amount),0) AS igst,
            COALESCE(SUM(cgst_amount + sgst_amount),0) AS cgst_sgst,
            COALESCE(SUM(igst_amount + cgst_amount + sgst_amount),0) AS total_gst
            FROM finance_invoices WHERE company_id = ? AND outstanding_amount > 0");
        $stmt->execute([$companyId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getStatCard5($companyId) {
        if (!$companyId) return $this->empty5();
        $stmt = $this->pdo->prepare("SELECT
            COALESCE(SUM(po_total_value),0) AS total_po_commitments,
            SUM(CASE WHEN LOWER(po_status) IN ('active','released','open') THEN 1 ELSE 0 END) AS open_pos,
            SUM(CASE WHEN LOWER(po_status) IN ('closed','completed','cancelled') THEN 1 ELSE 0 END) AS closed_pos
            FROM finance_purchase_orders WHERE company_id = ?");
        $stmt->execute([$companyId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getStatCard6($companyId) {
        if (!$companyId) return $this->empty6();
        $stmt = $this->pdo->prepare("SELECT
            COALESCE(SUM(outstanding_amount),0) AS claimable_amount,
            SUM(CASE WHEN outstanding_amount > 0 THEN 1 ELSE 0 END) AS claimable_invoices,
            CASE WHEN SUM(total_amount) = 0 THEN 0
                 ELSE ROUND((SUM(outstanding_amount) / SUM(total_amount)) * 100, 2) END AS claim_rate
            FROM finance_invoices WHERE company_id = ?");
        $stmt->execute([$companyId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
