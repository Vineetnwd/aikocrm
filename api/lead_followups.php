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
$company_id = Auth::companyId();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $lead_id = $_GET['lead_id'] ?? null;
    if (!$lead_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Lead ID required']);
        exit;
    }

    $followups = $db->fetchAll("SELECT * FROM lead_followups WHERE lead_id = ? AND company_id = ? ORDER BY follow_up_date DESC, follow_up_time DESC", [$lead_id, $company_id]);
    echo json_encode($followups);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['lead_id']) || empty($input['follow_up_date'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Lead ID and Date are required']);
        exit;
    }

    try {
        $db->insert('lead_followups', [
            'lead_id' => $input['lead_id'],
            'company_id' => $company_id,
            'user_id' => Auth::userId(), // Automatically lock to logged-in user
            'follow_up_date' => $input['follow_up_date'],
            'follow_up_time' => $input['follow_up_time'] ?? '10:00:00',
            'remark' => $input['remark'] ?? '',
            'status' => 'pending'
        ]);

        // Also update the main leads table with the LATEST follow_up_date for quick reference
        $db->query("UPDATE leads SET follow_up_date = ? WHERE id = ?", [$input['follow_up_date'], $input['lead_id']]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>