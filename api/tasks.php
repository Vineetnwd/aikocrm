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
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null; // This is the lead ID
    $status = $input['status'] ?? null;

    if (!$id || !$status || !in_array($status, ['pending', 'done', 'delay'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Valid ID and Status required.']);
        exit;
    }

    try {
        $completed_at = null;
        if ($status === 'done') {
            $completed_at = date('Y-m-d H:i:s');
            $db->query("UPDATE leads SET task_status = ?, task_completed_at = ? WHERE id = ? AND company_id = ?", [$status, $completed_at, $id, $company_id]);
        } else {
            $db->query("UPDATE leads SET task_status = ?, task_completed_at = NULL WHERE id = ? AND company_id = ?", [$status, $id, $company_id]);
        }

        // Add to history (lead followups)
        $remark = $input['remark'] ?? 'Status updated to ' . strtoupper($status);
        $db->insert('lead_followups', [
            'lead_id' => $id,
            'company_id' => $company_id,
            'user_id' => Auth::user()['id'],
            'follow_up_date' => date('Y-m-d'),
            'follow_up_time' => date('H:i:s'),
            'remark' => "Task Status changed to " . strtoupper($status) . ": " . $remark,
            'status' => 'completed'
        ]);
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>
