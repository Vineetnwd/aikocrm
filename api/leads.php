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
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    if ($id) {
        $lead = $leadModel->find($id);
        echo json_encode($lead);
    } else {
        $leads = $leadModel->all();
        echo json_encode($leads);
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Basic validation
    if (empty($input['name']) || empty($input['mobile'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Name and Mobile are required']);
        exit;
    }
    
    $id = $leadModel->create([
        'name' => $input['name'],
        'mobile' => $input['mobile'],
        'email' => $input['email'] ?? null,
        'requirement' => $input['requirement'] ?? null,
        'category' => $input['category'] ?? 'warm',
        'source' => $input['source'] ?? 'direct',
        'status' => 'new'
    ]);
    
    echo json_encode(['success' => true, 'id' => $id]);
} elseif ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;
    
    if ($id) {
        unset($input['id']);
        $leadModel->update($id, $input);
        echo json_encode(['success' => true]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Lead ID is required for update']);
    }
} elseif ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if ($id) {
        $leadModel->delete($id);
        echo json_encode(['success' => true]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Lead ID is required']);
    }
}
?>
