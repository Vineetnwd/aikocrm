<?php
session_start();
require_once __DIR__ . '/../config/config.php';

// Autoloading classes
spl_autoload_register(function ($class) {
    $prefix = 'Core\\';
    $base_dir = __DIR__ . '/../core/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

use Core\Auth;
use Core\Database;

// Robust Dynamic Routing
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$script_name = $_SERVER['SCRIPT_NAME'];

$request_parts = explode('/', trim($request_uri, '/'));
$script_parts = explode('/', trim($script_name, '/'));

$path_parts = [];
$i = 0;
// Skip identical parts at the beginning (the base directory prefix)
while ($i < count($request_parts) && $i < count($script_parts) && $request_parts[$i] === $script_parts[$i]) {
    $i++;
}

// Any remaining parts in the request URI are the actual path
for (; $i < count($request_parts); $i++) {
    // Also skip 'index.php' if it appears in the request URI
    if ($request_parts[$i] !== 'index.php') {
        $path_parts[] = $request_parts[$i];
    }
}

$path = implode('/', $path_parts);
$path = trim($path, '/');

// Authentication Middleware
if ($path !== 'login' && strpos($path, 'api/') === false && !Auth::check()) {
    header('Location: ' . APP_URL . '/login');
    exit;
}

// Simple response for testing
if (strpos($path, 'api/') === 0) {
    $apiFile = __DIR__ . '/../' . $path;
    if (file_exists($apiFile)) {
        include $apiFile;
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'API endpoint not found: ' . $path]);
    }
    exit;
}

switch ($path) {
    case 'login':
        if (Auth::check()) {
            header('Location: ' . APP_URL . '/public/index.php/dashboard');
            exit;
        }
        include __DIR__ . '/../public/assets/views/login.php';
        break;
    case 'logout':
        Auth::logout();
        header('Location: ' . APP_URL . '/public/index.php/login');
        break;
    case '':
    case 'dashboard':
        include __DIR__ . '/../public/assets/views/dashboard.php';
        break;
    case 'leads':
        include __DIR__ . '/../public/assets/views/leads.php';
        break;
    case 'invoices':
        include __DIR__ . '/../public/assets/views/invoices.php';
        break;
    case 'tasks':
        include __DIR__ . '/../public/assets/views/tasks.php';
        break;
    case 'reports':
        include __DIR__ . '/../public/assets/views/reports.php';
        break;
    case 'settings':
        include __DIR__ . '/../public/assets/views/settings.php';
        break;
    default:
        http_response_code(404);
        echo "404 - Page not found: " . $path;
        break;
}
?>
