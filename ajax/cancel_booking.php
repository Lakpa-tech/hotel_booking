<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to continue']);
    exit();
}

$booking_id = $_POST['booking_id'] ?? '';
$booking_type = $_POST['booking_type'] ?? 'room'; // 'room' or 'package'

if (!$booking_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
    exit();
}

try {
    if ($booking_type === 'package') {
        // Handle package booking cancellation
        $booking = getPackageBookingById($conn, $booking_id, $_SESSION['user_id']);
        
        if (!$booking) {
            echo json_encode(['success' => false, 'message' => 'Package booking not found or you do not have permission to cancel it']);
            exit();
        }
        
        if ($booking['status'] !== 'confirmed') {
            echo json_encode(['success' => false, 'message' => 'Only confirmed package bookings can be cancelled']);
            exit();
        }
        
        // Cancel package booking
        $stmt = $conn->prepare("UPDATE package_bookings SET status = 'cancelled', updated_at = NOW() WHERE booking_id = ? AND user_id = ?");
        $result = $stmt->execute([$booking_id, $_SESSION['user_id']]);
        
    } else {
        // Handle room booking cancellation
        $booking = getBookingById($conn, $booking_id, $_SESSION['user_id']);
        
        if (!$booking) {
            echo json_encode(['success' => false, 'message' => 'Room booking not found or you do not have permission to cancel it']);
            exit();
        }
        
        if ($booking['status'] !== 'confirmed') {
            echo json_encode(['success' => false, 'message' => 'Only confirmed room bookings can be cancelled']);
            exit();
        }
        
        // Cancel room booking
        $result = cancelBooking($conn, $booking_id, $_SESSION['user_id']);
    }
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => ucfirst($booking_type) . ' booking cancelled successfully',
            'booking_id' => $booking_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to cancel booking. Please try again.']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>