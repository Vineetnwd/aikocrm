<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';

use Core\Database;
use Core\Auth;

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance();
$company_id = Auth::companyId();
$employee_id = $_GET['employee_id'] ?? null;
$type = $_GET['type'] ?? '';

if (!$employee_id || !$type) {
    http_response_code(400);
    echo json_encode(['error' => 'Employee ID and Type required']);
    exit;
}

try {
    $data = [];
    if ($type === 'leads') {
        $data = $db->fetchAll("
            SELECT name, mobile, status, category, follow_up_date
            FROM leads
            WHERE assigned_employee_id = ? AND company_id = ?
            ORDER BY created_at DESC
        ", [$employee_id, $company_id]);
    } elseif ($type === 'tasks') {
        $data = $db->fetchAll("
            SELECT name, requirement, task_status, follow_up_date as due_date
            FROM leads
            WHERE assigned_employee_id = ? AND company_id = ?
            ORDER BY follow_up_date ASC
        ", [$employee_id, $company_id]);
    } elseif ($type === 'revenue') {
        $data = $db->fetchAll("
            SELECT p.amount, p.payment_date, i.invoice_number, l.name as client_name
            FROM invoice_payments p
            JOIN invoices i ON p.invoice_id = i.id
            JOIN leads l ON i.lead_id = l.id
            WHERE l.assigned_employee_id = ? AND p.company_id = ?
            ORDER BY p.payment_date DESC
        ", [$employee_id, $company_id]);
    }

    echo json_encode($data);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
