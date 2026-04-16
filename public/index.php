<?php
session_start();

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

// Dynamic Routing
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$script_name = $_SERVER['SCRIPT_NAME'];
$base_dir = dirname($script_name);

// Remove base_dir from request_uri to get the clean path
$path = str_replace($base_dir, '', $request_uri);
$path = trim($path, '/');

// Remove 'index.php' if it's explicitly in the path
$path = str_replace('index.php', '', $path);
$path = trim($path, '/');

// Mocking Auth check for now
Auth::check();

// Simple response for testing
switch ($path) {
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
