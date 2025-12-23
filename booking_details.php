<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$booking_id = $_GET['id'] ?? '';

if (!$booking_id) {
    header('Location: bookings.php');
    exit();
}

// Get booking details
$booking = getBookingById($conn, $booking_id, isLoggedIn() ? $_SESSION['user_id'] : null);

if (!$booking) {
    $_SESSION['error'] = 'Booking not found or you do not have permission to view it';
    header('Location: bookings.php');
    exit();
}

// Calculate nights
$nights = calculateNights($booking['checkin_date'], $booking['checkout_date']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - <?php echo htmlspecialchars($booking['booking_id']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="bookings.php">My Bookings</a></li>
                <li class="breadcrumb-item active">Booking Details</li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-lg-8">
                <!-- Booking Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>
                            Booking Details
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <img src="<?php echo htmlspecialchars($booking['room_image']); ?>" 
                                     class="img-fluid rounded" alt="<?php echo htmlspecialchars($booking['room_name']); ?>">
                            </div>
                            <div class="col-md-8">
                                <h5><?php echo htmlspecialchars($booking['room_name']); ?></h5>
                                
                                <div class="row mb-3">
                                    <div class="col-sm-6">
                                        <strong>Booking ID:</strong> <?php echo htmlspecialchars($booking['booking_id']); ?>
                                    </div>
                                    <div class="col-sm-6">
                                        <strong>Status:</strong> 
                                        <?php
                                        $status_class = '';
                                        switch ($booking['status']) {
                                            case 'confirmed': $status_class = 'bg-success'; break;
                                            case 'checked_in': $status_class = 'bg-info'; break;
                                            case 'checked_out': $status_class = 'bg-warning'; break;
                                            case 'completed': $status_class = 'bg-primary'; break;
                                            case 'cancelled': $status_class = 'bg-danger'; break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-sm-6">
                                        <strong>Check-in:</strong> <?php echo formatDate($booking['checkin_date']); ?>
                                    </div>
                                    <div class="col-sm-6">
                                        <strong>Check-out:</strong> <?php echo formatDate($booking['checkout_date']); ?>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-sm-6">
                                        <strong>Nights:</strong> <?php echo $nights; ?>
                                    </div>
                                    <div class="col-sm-6">
                                        <strong>Guests:</strong> <?php echo $booking['guests']; ?>
                                    </div>
                                </div>
                                
                                <?php if ($booking['special_requests']): ?>
                                <div class="mb-3">
                                    <strong>Special Requests:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($booking['special_requests'])); ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($booking['arrival_time']): ?>
                                <div class="mb-3">
                                    <strong>Expected Arrival Time:</strong> <?php echo htmlspecialchars($booking['arrival_time']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-muted">
                        <small>
                            <i class="fas fa-clock"></i> 
                            Booked on <?php echo formatDate($booking['created_at']); ?>
                        </small>
                    </div>
                </div>

                <!-- Pricing Breakdown -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-receipt me-2"></i>Pricing Breakdown
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Room Rate (per night):</span>
                                    <span><?php echo number_format($booking['room_price'], 0); ?> Nrs.</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Number of Nights:</span>
                                    <span><?php echo $nights; ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal:</span>
                                    <span><?php echo number_format($booking['room_price'] * $nights, 0); ?> Nrs.</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Taxes (12%):</span>
                                    <span><?php echo number_format(($booking['room_price'] * $nights) * 0.12, 0); ?> Nrs.</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <strong>Total Amount:</strong>
                                    <strong class="text-primary"><?php echo number_format($booking['total_amount'], 0); ?> Nrs.</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-user me-2"></i>Customer Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Name:</strong><br>
                            <?php echo htmlspecialchars($booking['user_name']); ?>
                        </div>
                        <div class="mb-3">
                            <strong>Email:</strong><br>
                            <?php echo htmlspecialchars($booking['user_email']); ?>
                        </div>
                        <div class="mb-3">
                            <strong>Phone:</strong><br>
                            <?php echo htmlspecialchars($booking['user_phone']); ?>
                        </div>
                        
                        <?php if (isLoggedIn() && $booking['status'] === 'confirmed'): ?>
                        <div class="d-grid">
                            <button class="btn btn-outline-danger" id="cancelBookingBtn"
                                    onclick="cancelBookingInstant('<?php echo $booking['booking_id']; ?>', 'room')">
                                <i class="fas fa-times"></i> Cancel Booking
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Hotel Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-hotel me-2"></i>Hotel Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <strong>Check-in Time:</strong> 3:00 PM
                        </div>
                        <div class="mb-2">
                            <strong>Check-out Time:</strong> 11:00 AM
                        </div>
                        <div class="mb-2">
                            <strong>Contact:</strong> +977 9746207003
                        </div>
                        <div class="mb-2">
                            <strong>Address:</strong> Fikkal Petrol Pump, Ilam, Nepal
                        </div>
                    </div>
                </div>

                <!-- Important Information -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>Important Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                <small>Free cancellation anytime</small>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-id-card text-primary me-2"></i>
                                <small>Valid ID required at check-in</small>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-wifi text-primary me-2"></i>
                                <small>Free WiFi available</small>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-car text-primary me-2"></i>
                                <small>Free parking available</small>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        function cancelBooking(bookingId) {
            if (confirm('Are you sure you want to cancel this booking?')) {
                window.location.href = 'cancel_booking.php?id=' + bookingId;
            }
        }
        
        function cancelBookingInstant(bookingId, bookingType) {
            const bookingTypeName = bookingType === 'package' ? 'package booking' : 'room booking';
            
            if (confirm(`Are you sure you want to cancel this ${bookingTypeName}?\n\nThis action cannot be undone and you will be redirected to your bookings page.`)) {
                // Show loading state
                const button = document.getElementById('cancelBookingBtn');
                if (!button) {
                    alert('Cancel button not found. Please refresh the page and try again.');
                    return;
                }
                const originalText = button.innerHTML;
                button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Cancelling...';
                button.disabled = true;
                
                // Prepare form data
                const formData = new FormData();
                formData.append('booking_id', bookingId);
                formData.append('booking_type', bookingType);
                
                // Submit cancellation
                fetch('ajax/cancel_booking.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message and redirect
                        alert(data.message);
                        window.location.href = 'bookings.php';
                    } else {
                        alert(data.message || 'Failed to cancel booking');
                        // Restore button state
                        button.innerHTML = originalText;
                        button.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while cancelling the booking');
                    // Restore button state
                    button.innerHTML = originalText;
                    button.disabled = false;
                });
            }
        }
    </script>
</body>
</html>