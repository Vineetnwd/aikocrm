<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Invoice.php';

use Core\Auth;
use Core\Invoice;

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$invoiceModel = new Invoice();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (isset($_GET['action']) && $_GET['action'] === 'payments') {
        $invoice_id = $_GET['id'] ?? null;
        if ($invoice_id) {
            $db = Core\Database::getInstance();
            $payments = $db->fetchAll("SELECT * FROM invoice_payments WHERE invoice_id = ? AND company_id = ? ORDER BY payment_date DESC, created_at DESC", [$invoice_id, Auth::companyId()]);
            echo json_encode($payments);
        } else {
            echo json_encode([]);
        }
        exit;
    }

    if (isset($_GET['next_num'])) {
        echo json_encode(['next_number' => $invoiceModel->getNextInvoiceNumber()]);
    } else {
        $filters = [];
        if (isset($_GET['start']) && isset($_GET['end'])) {
            $filters['start'] = $_GET['start'];
            $filters['end'] = $_GET['end'];
        }
        $invoices = $invoiceModel->all($filters);
        echo json_encode($invoices);
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['invoice_number']) || empty($input['total_amount'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invoice number and total amount are required']);
        exit;
    }

    try {
        $id = $invoiceModel->create($input);

        if (isset($input['paid_amount']) && floatval($input['paid_amount']) > 0) {
            Core\Database::getInstance()->insert('invoice_payments', [
                'company_id' => Auth::companyId(),
                'invoice_id' => $id,
                'amount' => floatval($input['paid_amount']),
                'payment_date' => $input['invoice_date'] ?? date('Y-m-d'),
                'payment_mode' => $input['payment_mode'] ?? 'cash',
                'remarks' => 'Initial payment upon invoice creation'
            ]);
        }

        // Commission calculation removed from creation step. It now happens on payment collection.

        echo json_encode(['success' => true, 'id' => $id]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} elseif ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;
    $amount = $input['payment_amount'] ?? 0;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Invoice ID is required']);
        exit;
    }

    try {
        $db = Core\Database::getInstance();
        $inv = $db->fetchOne("SELECT total_amount, paid_amount, lead_id, subtotal FROM invoices WHERE id = ?", [$id]);
        
        $new_paid = $inv['paid_amount'] + $amount;
        $new_due = $inv['total_amount'] - $new_paid;
        
        $status = 'partial';
        if ($new_due <= 0) {
            $status = 'paid';
            $new_due = 0;
        }

        $db->query("UPDATE invoices SET paid_amount = ?, due_amount = ?, payment_status = ? WHERE id = ?", [
            $new_paid, $new_due, $status, $id
        ]);

        $payment_id = $db->insert('invoice_payments', [
            'company_id' => Auth::companyId(),
            'invoice_id' => $id,
            'amount' => floatval($amount),
            'payment_date' => $input['payment_date'] ?? date('Y-m-d'),
            'payment_mode' => $input['payment_mode'] ?? 'cash',
            'reference_number' => $input['reference_number'] ?? null,
            'remarks' => $input['remarks'] ?? null
        ]);

        // ── Credit Commission to Assigned Employee on Payment ──────────────
        try {
            if ($inv['total_amount'] > 0 && $amount > 0) {
                $company_id = Auth::companyId();
                $lead = $db->fetchOne(
                    "SELECT l.assigned_employee_id, l.commission_percent 
                     FROM leads l WHERE l.id = ? AND l.company_id = ?",
                    [$inv['lead_id'], $company_id]
                );

                if ($lead && intval($lead['assigned_employee_id']) > 0) {
                    $empId = intval($lead['assigned_employee_id']);
                    $emp = $db->fetchOne(
                        "SELECT commission_type, commission_rate FROM employees WHERE id = ? AND company_id = ?",
                        [$empId, $company_id]
                    );

                    if ($emp) {
                        $invoiceBase = floatval($inv['subtotal']);
                        $rate = floatval($emp['commission_rate']);
                        $total_expected_commission = 0;

                        if ($rate > 0) {
                            $total_expected_commission = ($emp['commission_type'] === 'fixed')
                                ? $rate
                                : ($invoiceBase * $rate) / 100;
                        } elseif (floatval($lead['commission_percent']) > 0) {
                            $total_expected_commission = ($invoiceBase * floatval($lead['commission_percent'])) / 100;
                        }

                        // Prorate the commission based on the payment amount (cap at remaining due to avoid over-crediting)
                        $amount_for_comm = max(0, min(floatval($amount), floatval($inv['total_amount']) - floatval($inv['paid_amount'])));
                        $ratio = $amount_for_comm / floatval($inv['total_amount']);
                        $commissionAmt = round($total_expected_commission * $ratio, 2);

                        if ($commissionAmt > 0) {
                            $db->query(
                                "UPDATE employees SET total_commission_earned = total_commission_earned + ? WHERE id = ? AND company_id = ?",
                                [$commissionAmt, $empId, $company_id]
                            );
                            $db->query(
                                "UPDATE leads SET commission_amount = commission_amount + ? WHERE id = ? AND company_id = ?",
                                [$commissionAmt, $inv['lead_id'], $company_id]
                            );

                            // Log earning transaction
                            $db->insert('employee_commission_earnings', [
                                'company_id' => $company_id,
                                'employee_id' => $empId,
                                'lead_id' => $inv['lead_id'],
                                'invoice_id' => $id,
                                'invoice_payment_id' => $payment_id,
                                'amount' => $commissionAmt
                            ]);
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            // Do not block payment recording
        }
        // ─────────────────────────────────────────────────────────────────

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>
