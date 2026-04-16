<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';

use Core\Auth;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['error' => 'All fields are required']);
    exit;
}

// Detailed debug login
$db = Database::getInstance();
$user = $db->fetchOne("SELECT * FROM users WHERE email = ?", [$email]);

if (!$user) {
    echo json_encode(['error' => "Debug: Account '$email' not found in database."]);
    exit;
}

if (Auth::login($email, $password)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Debug: Password verification failed.']);
}
?>
