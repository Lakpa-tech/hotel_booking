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
$booking = getPackageBookingById($conn, $booking_id, isLoggedIn() ? $_SESSION['user_id'] : null);

if (!$booking) {
    header('Location: bookings.php');
    exit();
}

// Parse JSON fields from package
$stmt = $conn->prepare("SELECT inclusions, exclusions, highlights, itinerary FROM travel_packages WHERE id = ?");
$stmt->execute([$booking['package_id']]);
$package_details = $stmt->fetch();

$inclusions = json_decode($package_details['inclusions'], true) ?: [];
$exclusions = json_decode($package_details['exclusions'], true) ?: [];
$highlights = json_decode($package_details['highlights'], true) ?: [];
$itinerary = json_decode($package_details['itinerary'], true) ?: [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Package Booking Details - <?php echo htmlspecialchars($booking['booking_id']); ?></title>
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
                <li class="breadcrumb-item active">Package Booking Details</li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-lg-8">
                <!-- Booking Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-suitcase-rolling me-2"></i>
                            Package Booking Details
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <img src="<?php echo htmlspecialchars($booking['package_image']); ?>" 
                                     class="img-fluid rounded" alt="<?php echo htmlspecialchars($booking['package_name']); ?>">
                            </div>
                            <div class="col-md-8">
                                <h5><?php echo htmlspecialchars($booking['package_name']); ?></h5>
                                <p class="text-muted mb-2">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?php echo htmlspecialchars($booking['destination']); ?>
                                </p>
                                
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
                                            case 'in_progress': $status_class = 'bg-info'; break;
                                            case 'completed': $status_class = 'bg-primary'; break;
                                            case 'cancelled': $status_class = 'bg-danger'; break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $booking['status'])); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-sm-6">
                                        <strong>Travel Date:</strong> <?php echo formatDate($booking['travel_date']); ?>
                                    </div>
                                    <div class="col-sm-6">
                                        <strong>Return Date:</strong> <?php echo formatDate($booking['return_date']); ?>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-sm-6">
                                        <strong>Duration:</strong> <?php echo $booking['duration_days']; ?>D/<?php echo $booking['duration_nights']; ?>N
                                    </div>
                                    <div class="col-sm-6">
                                        <strong>Travelers:</strong> <?php echo $booking['travelers']; ?> people
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-sm-6">
                                        <strong>Contact Phone:</strong> <?php echo htmlspecialchars($booking['contact_phone']); ?>
                                    </div>
                                    <div class="col-sm-6">
                                        <strong>Total Amount:</strong> 
                                        <span class="h5 text-primary"><?php echo number_format($booking['total_amount'], 0); ?> Nrs.</span>
                                    </div>
                                </div>
                                
                                <?php if ($booking['emergency_contact']): ?>
                                <div class="mt-3">
                                    <strong>Emergency Contact:</strong> <?php echo htmlspecialchars($booking['emergency_contact']); ?>
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

                <!-- Package Details -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Package Information</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($highlights)): ?>
                        <div class="mb-4">
                            <h6>Package Highlights</h6>
                            <ul class="list-unstyled">
                                <?php foreach ($highlights as $highlight): ?>
                                <li class="mb-1">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <?php echo htmlspecialchars($highlight); ?>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <div class="row">
                            <?php if (!empty($inclusions)): ?>
                            <div class="col-md-6 mb-3">
                                <h6 class="text-success">
                                    <i class="fas fa-check-circle me-2"></i>Inclusions
                                </h6>
                                <ul class="list-unstyled">
                                    <?php foreach ($inclusions as $inclusion): ?>
                                    <li class="mb-1">
                                        <i class="fas fa-plus text-success me-2"></i>
                                        <?php echo htmlspecialchars($inclusion); ?>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($exclusions)): ?>
                            <div class="col-md-6 mb-3">
                                <h6 class="text-danger">
                                    <i class="fas fa-times-circle me-2"></i>Exclusions
                                </h6>
                                <ul class="list-unstyled">
                                    <?php foreach ($exclusions as $exclusion): ?>
                                    <li class="mb-1">
                                        <i class="fas fa-minus text-danger me-2"></i>
                                        <?php echo htmlspecialchars($exclusion); ?>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($itinerary)): ?>
                        <div class="mb-3">
                            <h6>Itinerary</h6>
                            <div class="timeline">
                                <?php foreach ($itinerary as $index => $day): ?>
                                <div class="d-flex mb-2">
                                    <div class="flex-shrink-0">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                             style="width: 30px; height: 30px; font-size: 0.8rem;">
                                            <?php echo $index + 1; ?>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="mb-0"><?php echo htmlspecialchars($day); ?></p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="col-lg-4">
                <div class="card">
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
                            <button class="btn btn-outline-danger" id="cancelPackageBtn"
                                    onclick="cancelBookingInstant('<?php echo $booking['booking_id']; ?>', 'package')">
                                <i class="fas fa-times"></i> Cancel Booking
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Important Information -->
                <div class="card mt-4">
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
                                <small>Valid ID required for all travelers</small>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-phone text-primary me-2"></i>
                                <small>Contact us for any changes or queries</small>
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
            if (confirm('Are you sure you want to cancel this package booking?')) {
                window.location.href = 'cancel_package_booking.php?id=' + bookingId;
            }
        }
        
        function cancelBookingInstant(bookingId, bookingType) {
            const bookingTypeName = bookingType === 'package' ? 'package booking' : 'room booking';
            
            if (confirm(`Are you sure you want to cancel this ${bookingTypeName}?\n\nThis action cannot be undone and you will be redirected to your bookings page.`)) {
                // Show loading state
                const button = document.getElementById(bookingType === 'package' ? 'cancelPackageBtn' : 'cancelBookingBtn');
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