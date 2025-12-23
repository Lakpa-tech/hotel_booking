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
        echo json_encode(['success' => false, 'message' => 'Please login to book a package']);
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit();
    }

    $package_id = $_POST['package_id'] ?? 0;
    $travel_date = $_POST['travel_date'] ?? '';
    $travelers = $_POST['travelers'] ?? 1;
    $total_amount = $_POST['total_amount'] ?? 0;
    $contact_phone = $_POST['contact_phone'] ?? '';
    $emergency_contact = $_POST['emergency_contact'] ?? '';

    // Validate inputs
    if (!$package_id || !$travel_date || !$travelers || !$total_amount) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
        exit();
    }

    // Validate travel date (must be in future)
    if (strtotime($travel_date) <= time()) {
        echo json_encode(['success' => false, 'message' => 'Travel date must be in the future']);
        exit();
    }

    // Get package details
    $package = getPackageById($conn, $package_id);
    if (!$package) {
        echo json_encode(['success' => false, 'message' => 'Package not found']);
        exit();
    }

    // Validate travelers count
    if ($travelers > $package['max_people']) {
        echo json_encode(['success' => false, 'message' => 'Number of travelers exceeds maximum allowed']);
        exit();
    }

    // Calculate return date
    $return_date = date('Y-m-d', strtotime($travel_date . ' + ' . ($package['duration_days'] - 1) . ' days'));

    // Create package booking
    $booking_id = createPackageBooking(
        $conn, 
        $_SESSION['user_id'], 
        $package_id, 
        $travel_date, 
        $return_date, 
        $travelers, 
        $total_amount, 
        $contact_phone, 
        $emergency_contact
    );
    
    if ($booking_id) {
        echo json_encode([
            'success' => true, 
            'message' => 'Package booked successfully!',
            'booking_id' => $booking_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create booking']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>