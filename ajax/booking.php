<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Add error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Check maintenance mode
    if (!empty($website_settings['maintenance_mode']) && $website_settings['maintenance_mode']) {
        echo json_encode(['success' => false, 'message' => 'The hotel is currently under maintenance. Bookings are temporarily unavailable. Please try again later.']);
        exit();
    }

    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Please login to continue']);
        exit();
    }

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'book_room':
            $room_id = $_POST['room_id'] ?? 0;
            $checkin = $_POST['checkin'] ?? '';
            $checkout = $_POST['checkout'] ?? '';
            $guests = $_POST['guests'] ?? 1;
            $special_requests = trim($_POST['special_requests'] ?? '');
            $arrival_time = $_POST['arrival_time'] ?? null;
            
            // Validate inputs
            if (!$room_id || !$checkin || !$checkout) {
                echo json_encode(['success' => false, 'message' => 'Missing required information']);
                exit();
            }
            
            // Get room details
            $room = getRoomById($conn, $room_id);
            if (!$room) {
                echo json_encode(['success' => false, 'message' => 'Room not found']);
                exit();
            }
            
            // Check availability
            if (!isRoomAvailable($conn, $room_id, $checkin, $checkout)) {
                echo json_encode(['success' => false, 'message' => 'Room is not available for selected dates']);
                exit();
            }
            
            // Calculate total
            $nights = calculateNights($checkin, $checkout);
            $subtotal = $room['price'] * $nights;
            $taxes = $subtotal * 0.12;
            $total = $subtotal + $taxes;
            
            // Create booking with enhanced double booking prevention
            $booking_result = createBooking($conn, $_SESSION['user_id'], $room_id, $checkin, $checkout, $guests, $total);
            
            if (is_array($booking_result) && isset($booking_result['error'])) {
                echo json_encode(['success' => false, 'message' => $booking_result['error']]);
            } elseif ($booking_result) {
                // Update booking with special requests and arrival time if provided
                if ($special_requests || $arrival_time) {
                    $stmt = $conn->prepare("UPDATE bookings SET special_requests = ?, arrival_time = ? WHERE booking_id = ?");
                    $stmt->execute([$special_requests, $arrival_time, $booking_result]);
                }
                
                echo json_encode(['success' => true, 'booking_id' => $booking_result]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create booking']);
            }
            break;
            
        case 'cancel_booking':
            $booking_id = $_POST['booking_id'] ?? '';
            
            if (!$booking_id) {
                echo json_encode(['success' => false, 'message' => 'Booking ID is required']);
                exit();
            }
            
            if (cancelBooking($conn, $booking_id, $_SESSION['user_id'])) {
                echo json_encode(['success' => true, 'message' => 'Booking cancelled successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to cancel booking']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>