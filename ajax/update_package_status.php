<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // Update all package booking statuses
    updatePackageBookingStatus($conn);
    
    // Get updated counts for admin dashboard
    $stats = [
        'confirmed' => 0,
        'in_progress' => 0,
        'completed' => 0,
        'cancelled' => 0
    ];
    
    $stmt = $conn->query("
        SELECT status, COUNT(*) as count
        FROM package_bookings 
        GROUP BY status
    ");
    
    while ($row = $stmt->fetch()) {
        $stats[$row['status']] = $row['count'];
    }
    
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error updating status: ' . $e->getMessage()
    ]);
}
?>