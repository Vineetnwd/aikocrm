<?php
namespace Core;

class Invoice {
    private $db;
    private $company_id;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->company_id = Auth::companyId();
    }

    public function all($filters = []) {
        $sql = "SELECT i.*, l.name as client_name 
                FROM invoices i 
                LEFT JOIN leads l ON i.lead_id = l.id 
                WHERE i.company_id = ?";
        $params = [$this->company_id];

        if (isset($filters['start']) && isset($filters['end'])) {
            $sql .= " AND i.invoice_date BETWEEN ? AND ?";
            $params[] = $filters['start'];
            $params[] = $filters['end'];
        }

        $sql .= " ORDER BY i.created_at DESC";
        return $this->db->fetchAll($sql, $params);
    }

    public function create($data) {
        $data['company_id'] = $this->company_id;
        
        // Auto-calculate due amount if it equals total
        if (!isset($data['due_amount'])) {
            $data['due_amount'] = $data['total_amount'] - ($data['paid_amount'] ?? 0);
        }
        
        // Set payment status based on paid amount
        if ($data['paid_amount'] >= $data['total_amount']) {
            $data['payment_status'] = 'paid';
        } elseif ($data['paid_amount'] > 0) {
            $data['payment_status'] = 'partial';
        } else {
            $data['payment_status'] = 'due';
        }

        return $this->db->insert('invoices', $data);
    }

    public function getNextInvoiceNumber() {
        $last = $this->db->fetchOne("SELECT invoice_number FROM invoices WHERE company_id = ? ORDER BY id DESC LIMIT 1", [$this->company_id]);
        if (!$last) return "INV-1001";
        
        $num = (int)str_replace("INV-", "", $last['invoice_number']);
        return "INV-" . ($num + 1);
    }
}
