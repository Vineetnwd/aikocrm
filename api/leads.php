<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Lead.php';

use Core\Auth;
use Core\Lead;

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$leadModel = new Lead();
$method    = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $id     = $_GET['id']     ?? null;
        $action = $_GET['action'] ?? null;

        if ($action === 'check_duplicate') {
            $mobile      = trim($_GET['mobile']      ?? '');
            $requirement = trim($_GET['requirement'] ?? '');
            $excludeId   = intval($_GET['exclude_id'] ?? 0) ?: null;
            if ($mobile) {
                $existing = $leadModel->findByMobile($mobile, $excludeId, $requirement);
                echo json_encode(['duplicate' => (bool)$existing, 'lead' => $existing ?: null]);
            } else {
                echo json_encode(['duplicate' => false, 'lead' => null]);
            }
            exit;
        }

        if ($id) {
            $lead = $leadModel->find(intval($id));
            echo json_encode($lead);
        } else {
            echo json_encode($leadModel->all());
        }

    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        if (empty($input['name']) || empty($input['mobile'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Name and Mobile are required']);
            exit;
        }
        if (!isset($input['status'])) $input['status'] = 'new';

        $id = $leadModel->create($input);
        echo json_encode(['success' => true, 'id' => $id]);

    } elseif ($method === 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $id    = intval($input['id'] ?? 0);

        if ($id) {
            unset($input['id']);
            $leadModel->update($id, $input);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Lead ID is required for update']);
        }

    } elseif ($method === 'DELETE') {
        $id = intval($_GET['id'] ?? 0);
        if ($id) {
            $leadModel->delete($id);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Lead ID is required']);
        }
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage(), 'file' => basename($e->getFile()), 'line' => $e->getLine()]);
}
?>
