<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Employee.php';

use Core\Auth;
use Core\Employee;

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$employeeModel = new Employee();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    $action = $_GET['action'] ?? null;

    if ($action === 'stats') {
        echo json_encode($employeeModel->stats());
    } elseif ($action === 'departments') {
        echo json_encode($employeeModel->departments());
    } elseif ($action === 'commissions' && $id) {
        $db = Core\Database::getInstance();
        $commissions = $db->fetchAll("
            SELECT id as lead_id, name as lead_name, requirement, commission_amount, updated_at 
            FROM leads 
            WHERE assigned_employee_id = ? AND company_id = ? AND commission_amount > 0 
            ORDER BY updated_at DESC
        ", [$id, Auth::companyId()]);
        echo json_encode($commissions);
    } elseif ($id) {
        $emp = $employeeModel->find($id);
        if ($emp) {
            echo json_encode($emp);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Employee not found']);
        }
    } else {
        $filters = [];
        if (!empty($_GET['department'])) $filters['department'] = $_GET['department'];
        if (!empty($_GET['status']))     $filters['status']     = $_GET['status'];
        if (!empty($_GET['search']))     $filters['search']     = $_GET['search'];
        echo json_encode($employeeModel->all($filters));
    }

} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Employee name is required']);
        exit;
    }

    if (!isset($input['status'])) $input['status'] = 'active';

    $id = $employeeModel->create($input);
    echo json_encode(['success' => true, 'id' => $id]);

} elseif ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;

    if ($id) {
        unset($input['id']);
        $employeeModel->update($id, $input);
        echo json_encode(['success' => true]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Employee ID is required for update']);
    }

} elseif ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if ($id) {
        $employeeModel->delete($id);
        echo json_encode(['success' => true]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Employee ID is required']);
    }
}
?>
