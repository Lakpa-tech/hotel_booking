<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

$package_id = $_GET['id'] ?? 0;

if (!$package_id) {
    echo json_encode(['error' => 'Package ID is required']);
    exit();
}

try {
    $stmt = $conn->prepare("SELECT * FROM travel_packages WHERE id = ?");
    $stmt->execute([$package_id]);
    $package = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$package) {
        echo json_encode(['error' => 'Package not found']);
        exit();
    }
    
    echo json_encode($package);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>