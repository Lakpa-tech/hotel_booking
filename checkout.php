<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Require login
requireLogin();

$error = '';
$success = '';

// Get booking data
$room_id = $_POST['room_id'] ?? $_GET['room_id'] ?? 0;
$checkin = $_POST['checkin'] ?? $_GET['checkin'] ?? '';
$checkout = $_POST['checkout'] ?? $_GET['checkout'] ?? '';
$guests = $_POST['guests'] ?? $_GET['guests'] ?? 1;

// Validate dates
if (!$checkin || !$checkout) {
    header('Location: rooms.php');
    exit();
}

// Get room details
$room = getRoomById($conn, $room_id);
if (!$room) {
    header('Location: rooms.php');
    exit();
}

// Check availability
if (!isRoomAvailable($conn, $room_id, $checkin, $checkout)) {
    $_SESSION['error'] = 'This room is not available for the selected dates.';
    header('Location: room_details.php?id=' . $room_id);
    exit();
}

// Calculate booking details
$nights = calculateNights($checkin, $checkout);
$subtotal = $room['price'] * $nights;
$taxes = $subtotal * 0.12; // 12% tax
$total = $subtotal + $taxes;

// Process booking
if ($_POST && isset($_POST['confirm_booking'])) {
    // Set JSON header for AJAX requests
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
    }
    
    try {
        $special_requests = trim($_POST['special_requests'] ?? '');
        $arrival_time = $_POST['arrival_time'] ?? null;
        
        $booking_result = createBooking($conn, $_SESSION['user_id'], $room_id, $checkin, $checkout, $guests, $total);
        
        if (is_array($booking_result) && isset($booking_result['error'])) {
            // Handle booking error
            if (isset($_POST['ajax'])) {
                echo json_encode([
                    'success' => false,
                    'message' => $booking_result['error']
                ]);
                exit();
            }
            $_SESSION['error'] = $booking_result['error'];
        } elseif ($booking_result) {
            // Update booking with special requests and arrival time if provided
            if ($special_requests || $arrival_time) {
                $stmt = $conn->prepare("UPDATE bookings SET special_requests = ?, arrival_time = ? WHERE booking_id = ?");
                $stmt->execute([$special_requests, $arrival_time, $booking_result]);
            }
            
            // Return success response for AJAX
            if (isset($_POST['ajax'])) {
                echo json_encode([
                    'success' => true,
                    'booking_id' => $booking_result,
                    'message' => 'Booking confirmed successfully!'
                ]);
                exit();
            }
            
            $_SESSION['success'] = 'Booking confirmed successfully! Your booking ID is: ' . $booking_result;
            header('Location: bookings.php');
            exit();
        } else {
            if (isset($_POST['ajax'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to create booking. Please try again.'
                ]);
                exit();
            }
            $_SESSION['error'] = 'Failed to create booking. Please try again.';
        }
    } catch (Exception $e) {
        if (isset($_POST['ajax'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
            exit();
        }
        $error = 'An error occurred: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Hotel Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-12 mb-4">
                <h1><i class="fas fa-credit-card"></i> Checkout</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="rooms.php">Rooms</a></li>
                        <li class="breadcrumb-item"><a href="room_details.php?id=<?php echo $room['id']; ?>"><?php echo htmlspecialchars($room['name']); ?></a></li>
                        <li class="breadcrumb-item active">Checkout</li>
                    </ol>
                </nav>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <!-- Booking Details -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Booking Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <img src="<?php echo htmlspecialchars($room['image']); ?>" 
                                     class="img-fluid rounded" alt="<?php echo htmlspecialchars($room['name']); ?>">
                            </div>
                            <div class="col-md-8">
                                <h5><?php echo htmlspecialchars($room['name']); ?></h5>
                                <div class="rating mb-2">
                                    <?php echo generateStars($room['rating']); ?>
                                    <span class="ms-2 text-muted">(<?php echo $room['review_count']; ?> reviews)</span>
                                </div>
                                
                                <div class="row">
                                    <div class="col-sm-6 mb-2">
                                        <strong>Check-in:</strong> <?php echo formatDate($checkin); ?>
                                    </div>
                                    <div class="col-sm-6 mb-2">
                                        <strong>Check-out:</strong> <?php echo formatDate($checkout); ?>
                                    </div>
                                    <div class="col-sm-6 mb-2">
                                        <strong>Guests:</strong> <?php echo $guests; ?>
                                    </div>
                                    <div class="col-sm-6 mb-2">
                                        <strong>Nights:</strong> <?php echo $nights; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Guest Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user"></i> Guest Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="checkoutForm">
                            <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
                            <input type="hidden" name="checkin" value="<?php echo htmlspecialchars($checkin); ?>">
                            <input type="hidden" name="checkout" value="<?php echo htmlspecialchars($checkout); ?>">
                            <input type="hidden" name="guests" value="<?php echo $guests; ?>">
                            
                            <!-- Guest info is pre-filled from user account -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['full_name']); ?>" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <?php
                                    $user_stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
                                    $user_stmt->execute([$_SESSION['user_id']]);
                                    $user = $user_stmt->fetch();
                                    ?>
                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Special Requests (Optional)</label>
                                <textarea class="form-control" name="special_requests" rows="3" 
                                          placeholder="Any special requests or preferences..."></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Estimated Arrival Time</label>
                                <input type="time" class="form-control" name="arrival_time" 
                                       placeholder="Enter your expected arrival time">
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Please enter your expected arrival time (standard check-in is 3:00 PM)
                                </div>
                            </div>
                        </form>
                    </div>
                </div>



                <!-- Terms and Conditions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-file-contract"></i> Terms & Conditions</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="#" class="text-decoration-none">Terms of Service</a> 
                                and <a href="#" class="text-decoration-none">Cancellation Policy</a>
                            </label>
                        </div>
                        
                        <ul class="list-unstyled small text-muted">
                            <li><i class="fas fa-check text-success me-2"></i> Free cancellation up to 24 hours before check-in</li>
                            <li><i class="fas fa-check text-success me-2"></i> Check-in: 3:00 PM | Check-out: 11:00 AM</li>
                            <li><i class="fas fa-check text-success me-2"></i> Valid ID required at check-in</li>
                            <li><i class="fas fa-check text-success me-2"></i> No smoking policy in all rooms</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Booking Summary -->
            <div class="col-lg-4">
                <div class="booking-summary">
                    <h5 class="mb-4"><i class="fas fa-receipt"></i> Booking Summary</h5>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Room Rate</span>
                            <span><?php echo number_format($room['price'], 0); ?> Nrs./night</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span><?php echo $nights; ?> nights</span>
                            <span><?php echo number_format($subtotal, 0); ?> Nrs.</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Taxes & Fees</span>
                            <span><?php echo number_format($taxes, 0); ?> Nrs.</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-4">
                            <strong>Total Amount</strong>
                            <strong class="text-primary h5"><?php echo number_format($total, 0); ?> Nrs.</strong>
                        </div>
                    </div>

                    <button type="submit" form="checkoutForm" name="confirm_booking" class="btn btn-success w-100 mb-3" id="confirmBookingBtn">
                        <i class="fas fa-check"></i> Confirm Booking
                    </button>
                    
                    <div class="text-center">
                        <small class="text-muted">
                            <i class="fas fa-shield-alt"></i> 
                            Secure booking with instant confirmation
                        </small>
                    </div>
                </div>

                <!-- Contact Support -->
                <div class="card mt-4">
                    <div class="card-body text-center">
                        <h6>Need Assistance?</h6>
                        <p class="text-muted mb-3">Our support team is here to help</p>
                        <div class="d-grid gap-2">
                            <a href="tel:+1234567890" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-phone"></i> Call Support
                            </a>
                            <a href="contact.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-envelope"></i> Send Message
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="successModalLabel">
                        <i class="fas fa-check-circle"></i> Booking Confirmed!
                    </h5>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-4">
                        <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                        <h4>Congratulations!</h4>
                        <p class="text-muted">Your booking has been confirmed successfully.</p>
                    </div>
                    
                    <div class="booking-details bg-light p-3 rounded mb-4">
                        <div class="row">
                            <div class="col-6 text-start">
                                <strong>Booking ID:</strong>
                            </div>
                            <div class="col-6 text-end">
                                <span id="modalBookingId" class="text-primary fw-bold"></span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 text-start">
                                <strong>Room:</strong>
                            </div>
                            <div class="col-6 text-end">
                                <?php echo htmlspecialchars($room['name']); ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 text-start">
                                <strong>Check-in:</strong>
                            </div>
                            <div class="col-6 text-end">
                                <?php echo formatDate($checkin); ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 text-start">
                                <strong>Check-out:</strong>
                            </div>
                            <div class="col-6 text-end">
                                <?php echo formatDate($checkout); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>What's Next?</strong><br>
                        You will receive a confirmation email shortly. Please arrive at the hotel by your check-in time.
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="bookings.php" class="btn btn-primary">
                        <i class="fas fa-list"></i> View My Bookings
                    </a>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-home"></i> Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default form submission
            
            const termsCheckbox = document.getElementById('terms');
            if (!termsCheckbox.checked) {
                showAlert('Please accept the terms and conditions to proceed.', 'warning');
                return false;
            }
            
            // Show loading state
            const submitBtn = document.getElementById('confirmBookingBtn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
            submitBtn.disabled = true;
            
            // Prepare form data
            const formData = new FormData(this);
            formData.append('action', 'book_room');
            
            // Submit via AJAX
            fetch('ajax/booking.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success modal
                    document.getElementById('modalBookingId').textContent = data.booking_id;
                    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                    successModal.show();
                } else {
                    showAlert(data.message || 'Booking failed. Please try again.', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred. Please try again.', 'danger');
            })
            .finally(() => {
                // Restore button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
        
        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle'}"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const container = document.querySelector('.container');
            container.insertBefore(alertDiv, container.firstChild);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }
    </script>
</body>
</html>