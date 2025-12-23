<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'rin-odge');

// Create connection
try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Website settings - fetch from database
$website_settings = [
    'site_name' => 'Hotel Booking System',
    'maintenance_mode' => false
];

try {
    $settings_stmt = $conn->query("SELECT setting_key, setting_value FROM website_settings");
    $db_settings = $settings_stmt->fetchAll();
    foreach ($db_settings as $setting) {
        if ($setting['setting_key'] === 'maintenance_mode') {
            $website_settings['maintenance_mode'] = $setting['setting_value'] == '1';
        } elseif ($setting['setting_key'] === 'site_name') {
            $website_settings['site_name'] = $setting['setting_value'];
        }
    }
} catch(Exception $e) {
    // If settings table doesn't exist yet, use defaults
    $website_settings['maintenance_mode'] = false;
}

// Check if website is in maintenance mode (only redirect non-admin users)
if ($website_settings['maintenance_mode'] && !isset($_SESSION['admin_id'])) {
    $current_file = basename($_SERVER['PHP_SELF']);
    if ($current_file !== 'maintenance.php' && $current_file !== 'login.php' && $current_file !== 'register.php') {
        header('Location: maintenance.php');
        exit();
    }
}
?>