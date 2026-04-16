<?php
// Environment Detection
$is_local = in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1']);

if ($is_local) {
    // Local Settings
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'aikaa_crm');
    define('APP_URL', 'http://localhost/aikocrm');
} else {
    // Live Settings (aikocrm.com)
    define('DB_HOST', 'localhost');
    define('DB_USER', 'u138788005_aikocrm'); // Updated to reflect live DB name usually matches user
    define('DB_PASS', 'AikoCrm@2026');
    define('DB_NAME', 'u138788005_aikocrm');
    define('APP_URL', 'https://aikocrm.com/');
}

// App Config
define('APP_NAME', 'Aikaa CRM');

// Security
define('JWT_SECRET', 'your-very-secure-secret-key-123456');
?>