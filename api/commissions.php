<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';

use Core\Auth;

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = \Core\Database::getInstance();
$company_id = Auth::companyId();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? null;

    if ($action === 'payouts') {
        $payouts = $db->fetchAll("
            SELECT p.*, e.name as employee_name, e.employee_id as emp_code
            FROM employee_commission_payouts p
            JOIN employees e ON p.employee_id = e.id
            WHERE p.company_id = ?
            ORDER BY p.payout_date DESC, p.created_at DESC
        ", [$company_id]);
        echo json_encode($payouts);
        exit;
    }

    if ($action === 'balances') {
        $employees = $db->fetchAll("
            SELECT id, name, employee_id, total_commission_earned, total_commission_paid,
                   (total_commission_earned - total_commission_paid) as balance
            FROM employees
            WHERE company_id = ? AND total_commission_earned > 0
            ORDER BY balance DESC
        ", [$company_id]);
        echo json_encode($employees);
        exit;
    }

    if ($action === 'stats') {
        $stats = $db->fetchOne("
            SELECT 
                COALESCE(SUM(total_commission_earned), 0) as total_earned,
                COALESCE(SUM(total_commission_paid), 0) as total_paid,
                COALESCE(SUM(total_commission_earned - total_commission_paid), 0) as total_balance
            FROM employees
            WHERE company_id = ?
        ", [$company_id]);
        echo json_encode($stats);
        exit;
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['employee_id']) || empty($input['amount']) || empty($input['payout_date'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Employee, Amount, and Date are required']);
        exit;
    }

    try {
        $amount = floatval($input['amount']);
        $emp_id = intval($input['employee_id']);

        $db->insert('employee_commission_payouts', [
            'company_id' => $company_id,
            'employee_id' => $emp_id,
            'amount' => $amount,
            'payout_date' => $input['payout_date'],
            'reference_note' => $input['reference_note'] ?? null
        ]);

        $db->query("
            UPDATE employees 
            SET total_commission_paid = total_commission_paid + ? 
            WHERE id = ? AND company_id = ?
        ", [$amount, $emp_id, $company_id]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
