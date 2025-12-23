<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

$booking_id = $_GET['id'] ?? '';

if (!$booking_id) {
    $_SESSION['error'] = 'Invalid booking ID';
    header('Location: bookings.php');
    exit();
}

// Get booking details to verify ownership
$booking = getPackageBookingById($conn, $booking_id, $_SESSION['user_id']);

if (!$booking) {
    $_SESSION['error'] = 'Package booking not found or you do not have permission to cancel it';
    header('Location: bookings.php');
    exit();
}

// Check if booking can be cancelled
if ($booking['status'] !== 'confirmed') {
    $_SESSION['error'] = 'Only confirmed package bookings can be cancelled';
    header('Location: bookings.php');
    exit();
}

// Process cancellation
if ($_POST && isset($_POST['confirm_cancel'])) {
    $stmt = $conn->prepare("UPDATE package_bookings SET status = 'cancelled' WHERE booking_id = ? AND user_id = ? AND status = 'confirmed'");
    if ($stmt->execute([$booking_id, $_SESSION['user_id']])) {
        $_SESSION['success'] = 'Package booking cancelled successfully';
    } else {
        $_SESSION['error'] = 'Failed to cancel package booking. Please try again.';
    }
    header('Location: bookings.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Package Booking - Hotel Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Cancel Package Booking
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Warning:</strong> This action cannot be undone. Are you sure you want to cancel this package booking?
                        </div>

                        <!-- Booking Details -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <img src="<?php echo htmlspecialchars($booking['package_image']); ?>" 
                                     class="img-fluid rounded" alt="<?php echo htmlspecialchars($booking['package_name']); ?>">
                            </div>
                            <div class="col-md-8">
                                <h5><?php echo htmlspecialchars($booking['package_name']); ?></h5>
                                <p class="text-muted">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?php echo htmlspecialchars($booking['destination']); ?>
                                </p>
                                
                                <div class="row mb-2">
                                    <div class="col-sm-6">
                                        <strong>Booking ID:</strong> <?php echo htmlspecialchars($booking['booking_id']); ?>
                                    </div>
                                    <div class="col-sm-6">
                                        <strong>Status:</strong> 
                                        <span class="badge bg-success">Confirmed</span>
                                    </div>
                                </div>
                                
                                <div class="row mb-2">
                                    <div class="col-sm-6">
                                        <strong>Travel Date:</strong> <?php echo formatDate($booking['travel_date']); ?>
                                    </div>
                                    <div class="col-sm-6">
                                        <strong>Return Date:</strong> <?php echo formatDate($booking['return_date']); ?>
                                    </div>
                                </div>
                                
                                <div class="row mb-2">
                                    <div class="col-sm-6">
                                        <strong>Travelers:</strong> <?php echo $booking['travelers']; ?>
                                    </div>
                                    <div class="col-sm-6">
                                        <strong>Total Amount:</strong> 
                                        <span class="text-primary fw-bold"><?php echo number_format($booking['total_amount'], 0); ?> Nrs.</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cancellation Policy -->
                        <div class="alert alert-info">
                            <h6><i class="fas fa-file-contract me-2"></i>Cancellation Policy</h6>
                            <ul class="mb-0">
                                <li>Free cancellation up to 24 hours before travel date</li>
                                <li>No refund for cancellations within 24 hours of travel</li>
                                <li>Refunds will be processed within 5-7 business days</li>
                            </ul>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-between">
                            <a href="bookings.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Bookings
                            </a>
                            
                            <form method="POST" class="d-inline">
                                <button type="submit" name="confirm_cancel" class="btn btn-danger" 
                                        onclick="return confirm('Are you absolutely sure you want to cancel this package booking?')">
                                    <i class="fas fa-times me-2"></i>Confirm Cancellation
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>