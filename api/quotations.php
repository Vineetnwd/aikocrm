<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';

use Core\Auth;
use Core\Database;

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $lead_id = $_GET['lead_id'] ?? null;
    $id = $_GET['id'] ?? null;
    if ($id) {
        $quotation = $db->fetchOne("SELECT * FROM quotations WHERE id = ? AND company_id = ?", [$id, Auth::companyId()]);
        echo json_encode($quotation);
        exit;
    }
    if ($lead_id) {
        $quotations = $db->fetchAll("SELECT * FROM quotations WHERE lead_id = ? AND company_id = ? AND status != 'invoiced'", [$lead_id, Auth::companyId()]);
        echo json_encode($quotations);
        exit;
    }
}

$input = json_decode(file_get_contents('php://input'), true);

if ($method === 'POST') {
    if (isset($input['action']) && $input['action'] === 'convert') {
        // CONVERT TO INVOICE LOGIC
        $quo_id = $input['id'];
        $company_id = Auth::companyId();
        $quo = $db->fetchOne("SELECT * FROM quotations WHERE id = ? AND company_id = ?", [$quo_id, $company_id]);

        if (!$quo) {
            echo json_encode(['error' => 'Quotation not found']);
            exit;
        }

        // Generate Invoice Number
        $last = $db->fetchOne("SELECT invoice_number FROM invoices WHERE company_id = ? ORDER BY id DESC LIMIT 1", [$company_id]);
        $inv_num = $last ? "INV-" . ((int) str_replace("INV-", "", $last['invoice_number']) + 1) : "INV-1001";

        // Insert into Invoices
        $db->insert('invoices', [
            'company_id' => $company_id,
            'lead_id' => $quo['lead_id'],
            'invoice_number' => $inv_num,
            'invoice_date' => date('Y-m-d'),
            'subtotal' => $quo['subtotal'],
            'igst' => $quo['igst_amount'],
            'cgst' => $quo['cgst_amount'],
            'sgst' => $quo['sgst_amount'],
            'total_amount' => $quo['total_amount'],
            'due_amount' => $quo['total_amount'],
            'payment_status' => 'due'
        ]);

        // Update Quotation Status
        $db->query("UPDATE quotations SET status = 'invoiced' WHERE id = ?", [$quo_id]);

        // Commission calculation removed from convert step. It now happens on payment collection.

        echo json_encode(['success' => true, 'invoice_number' => $inv_num]);
        exit;
    }

    // CREATE OR UPDATE QUOTATION LOGIC
    try {
        if (!empty($input['id']) && empty($input['action'])) {
            // UPDATE
            $tax_amt = ($input['subtotal'] * ($input['tax_percent'] ?? 18)) / 100;
            $total = $input['subtotal'] + $tax_amt;

            $db->update('quotations', [
                'quotation_number' => $input['quotation_number'],
                'quotation_date' => $input['quotation_date'],
                'lead_id' => $input['lead_id'],
                'subtotal' => $input['subtotal'],
                'igst_amount' => $tax_amt,
                'total_amount' => $total
            ], "id = ? AND company_id = ?", [$input['id'], Auth::companyId()]);
            echo json_encode(['success' => true]);
            exit;
        }

        // Prevent multiple quotations for the same lead
        $existing = $db->fetchOne("SELECT id FROM quotations WHERE lead_id = ? AND company_id = ?", [$input['lead_id'], Auth::companyId()]);
        if ($existing) {
            http_response_code(400);
            echo json_encode(['error' => 'A quotation has already been issued for this lead.']);
            exit;
        }

        $tax_amt = ($input['subtotal'] * ($input['tax_percent'] ?? 18)) / 100;
        $total = $input['subtotal'] + $tax_amt;

        $db->insert('quotations', [
            'company_id' => Auth::companyId(),
            'quotation_number' => $input['quotation_number'],
            'quotation_date' => $input['quotation_date'],
            'lead_id' => $input['lead_id'],
            'subtotal' => $input['subtotal'],
            'igst_amount' => $tax_amt, // Defaulting to IGST for simplicity in this quo engine
            'total_amount' => $total,
            'status' => 'pending'
        ]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>