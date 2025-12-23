<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    $room_id = $_POST['room_id'] ?? 0;
    $checkin = $_POST['checkin'] ?? '';
    $checkout = $_POST['checkout'] ?? '';
    
    if (!$room_id || !$checkin || !$checkout) {
        echo json_encode(['available' => false, 'message' => 'Missing required parameters']);
        exit();
    }
    
    // Validate dates
    $checkin_date = new DateTime($checkin);
    $checkout_date = new DateTime($checkout);
    $today = new DateTime();
    
    if ($checkin_date < $today) {
        echo json_encode(['available' => false, 'message' => 'Check-in date cannot be in the past']);
        exit();
    }
    
    if ($checkout_date <= $checkin_date) {
        echo json_encode(['available' => false, 'message' => 'Check-out date must be after check-in date']);
        exit();
    }
    
    // Check room availability
    $available = isRoomAvailable($conn, $room_id, $checkin, $checkout);
    
    if ($available) {
        echo json_encode(['available' => true, 'message' => 'Room is available']);
    } else {
        echo json_encode(['available' => false, 'message' => 'Room is not available for selected dates']);
    }
    
} catch (Exception $e) {
    echo json_encode(['available' => false, 'message' => 'Error checking availability: ' . $e->getMessage()]);
}
?>