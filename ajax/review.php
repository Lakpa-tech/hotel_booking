<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to continue']);
    exit();
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'submit_review':
        $room_id = $_POST['room_id'] ?? 0;
        $booking_id = $_POST['booking_id'] ?? '';
        $rating = $_POST['rating'] ?? 0;
        $comment = trim($_POST['comment'] ?? '');
        
        // Validate inputs
        if (!$room_id || !$booking_id || !$rating) {
            echo json_encode(['success' => false, 'message' => 'Missing required information']);
            exit();
        }
        
        if ($rating < 1 || $rating > 5) {
            echo json_encode(['success' => false, 'message' => 'Invalid rating']);
            exit();
        }
        
        if (addReview($conn, $_SESSION['user_id'], $room_id, $booking_id, $rating, $comment)) {
            echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to submit review. You may have already reviewed this booking.']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}