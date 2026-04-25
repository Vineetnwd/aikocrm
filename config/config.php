<?php
// Environment Detection
$host = explode(':', $_SERVER['HTTP_HOST'] ?? 'localhost')[0];
$is_local = in_array($host, ['localhost', '127.0.0.1']);

if ($is_local) {
    // Local Settings
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'aikaa_crm');

    // Dynamic local URL includes port if present
    define('APP_URL', 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/aikocrm');
} else {
    // Live Settings (aikocrm.com)
    define('DB_HOST', 'localhost');
    define('DB_USER', 'u138788005_aikocrm');
    define('DB_PASS', 'AikoCrm@2026');
    define('DB_NAME', 'u138788005_aikocrm');

    // Dynamic live URL detection
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    $script_dir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $base_path = preg_replace('/\/public$/', '', $script_dir);
    $base_path = rtrim($base_path, '/');
    define('APP_URL', $protocol . "://" . ($_SERVER['HTTP_HOST'] ?? 'aikocrm.com') . $base_path);
}

// App Config
define('APP_NAME', 'Aikaa CRM');

// Security
define('JWT_SECRET', 'your-very-secure-secret-key-123456');
?>