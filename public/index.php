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

// Basic Routing (To be expanded)
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/aikocrm/public', '', $path);
$path = trim($path, '/');

// Mocking Auth check for now
Auth::check();

// Simple response for testing
if ($path === '' || $path === 'dashboard') {
    include __DIR__ . '/../public/assets/views/dashboard.php';
} else {
    echo "404 - Page not found: " . $path;
}
?>
