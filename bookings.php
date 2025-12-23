<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Get user bookings
$bookings = getUserBookings($conn, $_SESSION['user_id']);
$package_bookings = getUserPackageBookings($conn, $_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Hotel Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-12 mb-4">
                <h1><i class="fas fa-calendar-alt"></i> My Bookings</h1>
                <p class="text-muted">Manage your hotel reservations and travel packages</p>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs mb-4" id="bookingTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="hotel-tab" data-bs-toggle="tab" data-bs-target="#hotel-bookings" type="button" role="tab">
                    <i class="fas fa-bed me-2"></i>Hotel Bookings (<?php echo count($bookings); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="package-tab" data-bs-toggle="tab" data-bs-target="#package-bookings" type="button" role="tab">
                    <i class="fas fa-map-marked-alt me-2"></i>Package Bookings (<?php echo count($package_bookings); ?>)
                </button>
            </li>
        </ul>

        <div class="tab-content" id="bookingTabsContent">
            <!-- Hotel Bookings Tab -->
            <div class="tab-pane fade show active" id="hotel-bookings" role="tabpanel">
                <div class="row">
                    <?php if (!empty($bookings)): ?>
                        <?php foreach ($bookings as $booking): ?>
                        <div class="col-12 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <img src="<?php echo htmlspecialchars($booking['room_image']); ?>" 
                                                 class="img-fluid rounded" alt="<?php echo htmlspecialchars($booking['room_name']); ?>"
                                                 style="height: 150px; width: 100%; object-fit: cover;">
                                        </div>
                                        <div class="col-md-6">
                                            <h5><?php echo htmlspecialchars($booking['room_name']); ?></h5>
                                            <p class="text-muted mb-2">
                                                <strong>Booking ID:</strong> <?php echo htmlspecialchars($booking['booking_id']); ?>
                                            </p>
                                            
                                            <div class="row">
                                                <div class="col-sm-6 mb-2">
                                                    <i class="fas fa-calendar-check text-primary me-2"></i>
                                                    <strong>Check-in:</strong> <?php echo formatDate($booking['checkin_date']); ?>
                                                </div>
                                                <div class="col-sm-6 mb-2">
                                                    <i class="fas fa-calendar-times text-primary me-2"></i>
                                                    <strong>Check-out:</strong> <?php echo formatDate($booking['checkout_date']); ?>
                                                </div>
                                                <div class="col-sm-6 mb-2">
                                                    <i class="fas fa-users text-primary me-2"></i>
                                                    <strong>Guests:</strong> <?php echo $booking['guests']; ?>
                                                </div>
                                                <div class="col-sm-6 mb-2">
                                                    <i class="fas fa-moon text-primary me-2"></i>
                                                    <strong>Nights:</strong> <?php echo calculateNights($booking['checkin_date'], $booking['checkout_date']); ?>
                                                </div>
                                            </div>
                                            
                                            <?php if ($booking['special_requests']): ?>
                                                <p class="mb-2">
                                                    <strong>Special Requests:</strong> 
                                                    <?php echo htmlspecialchars($booking['special_requests']); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-3 text-end">
                                            <div class="mb-3">
                                                <?php
                                                $status_class = '';
                                                $status_icon = '';
                                                switch ($booking['status']) {
                                                    case 'confirmed':
                                                        $status_class = 'bg-success';
                                                        $status_icon = 'fas fa-check-circle';
                                                        break;
                                                    case 'checked_in':
                                                        $status_class = 'bg-info';
                                                        $status_icon = 'fas fa-door-open';
                                                        break;
                                                    case 'checked_out':
                                                        $status_class = 'bg-warning';
                                                        $status_icon = 'fas fa-door-closed';
                                                        break;
                                                    case 'completed':
                                                        $status_class = 'bg-primary';
                                                        $status_icon = 'fas fa-flag-checkered';
                                                        break;
                                                    case 'cancelled':
                                                        $status_class = 'bg-danger';
                                                        $status_icon = 'fas fa-times-circle';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>">
                                                    <i class="<?php echo $status_icon; ?>"></i> 
                                                    <?php echo ucfirst($booking['status']); ?>
                                                </span>
                                            </div>
                                            
                                            <h4 class="text-primary mb-3"><?php echo number_format($booking['total_amount'], 0); ?> Nrs.</h4>
                                            
                                            <div class="d-grid gap-2">
                                                <a href="booking_details.php?id=<?php echo $booking['booking_id']; ?>" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-eye"></i> View Details
                                                </a>
                                                
                                                <?php if ($booking['status'] === 'confirmed'): ?>
                                                    <button class="btn btn-outline-danger btn-sm" 
                                                            onclick="cancelBookingInstant('<?php echo $booking['booking_id']; ?>', 'room')">
                                                        <i class="fas fa-times"></i> Cancel
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if (in_array($booking['status'], ['completed', 'checked_out'])): ?>
                                                    <button class="btn btn-outline-warning btn-sm" 
                                                            onclick="showReviewModal('<?php echo $booking['booking_id']; ?>', <?php echo $booking['room_id']; ?>, '<?php echo htmlspecialchars($booking['room_name']); ?>')">
                                                        <i class="fas fa-star"></i> Review
                                                    </button>
                                                <?php endif; ?>
                                            </div>
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
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12 text-center py-5">
                            <i class="fas fa-bed fa-4x text-muted mb-3"></i>
                            <h4>No Hotel Bookings Found</h4>
                            <p class="text-muted mb-4">You haven't made any hotel bookings yet.</p>
                            <a href="rooms.php" class="btn btn-primary">
                                <i class="fas fa-bed"></i> Browse Rooms
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Package Bookings Tab -->
            <div class="tab-pane fade" id="package-bookings" role="tabpanel">
                <div class="row">
                    <?php if (!empty($package_bookings)): ?>
                        <?php foreach ($package_bookings as $booking): ?>
                        <div class="col-12 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <img src="<?php echo htmlspecialchars($booking['package_image']); ?>" 
                                                 class="img-fluid rounded" alt="<?php echo htmlspecialchars($booking['package_name']); ?>"
                                                 style="height: 150px; width: 100%; object-fit: cover;">
                                        </div>
                                        <div class="col-md-6">
                                            <h5><?php echo htmlspecialchars($booking['package_name']); ?></h5>
                                            <p class="text-muted mb-2">
                                                <strong>Booking ID:</strong> <?php echo htmlspecialchars($booking['booking_id']); ?>
                                            </p>
                                            <p class="text-muted mb-2">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <strong><?php echo htmlspecialchars($booking['destination']); ?></strong>
                                            </p>
                                            
                                            <div class="row">
                                                <div class="col-sm-6 mb-2">
                                                    <i class="fas fa-calendar-check text-primary me-2"></i>
                                                    <strong>Travel Date:</strong> <?php echo formatDate($booking['travel_date']); ?>
                                                </div>
                                                <div class="col-sm-6 mb-2">
                                                    <i class="fas fa-calendar-times text-primary me-2"></i>
                                                    <strong>Return Date:</strong> <?php echo formatDate($booking['return_date']); ?>
                                                </div>
                                                <div class="col-sm-6 mb-2">
                                                    <i class="fas fa-users text-primary me-2"></i>
                                                    <strong>Travelers:</strong> <?php echo $booking['travelers']; ?>
                                                </div>
                                                <div class="col-sm-6 mb-2">
                                                    <i class="fas fa-phone text-primary me-2"></i>
                                                    <strong>Contact:</strong> <?php echo htmlspecialchars($booking['contact_phone']); ?>
                                                </div>
                                            </div>
                                            
                                            <?php if ($booking['emergency_contact']): ?>
                                                <p class="mb-2">
                                                    <strong>Emergency Contact:</strong> 
                                                    <?php echo htmlspecialchars($booking['emergency_contact']); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-3 text-end">
                                            <div class="mb-3">
                                                <?php
                                                $status_class = '';
                                                $status_icon = '';
                                                $status_text = '';
                                                $today = date('Y-m-d');
                                                $travel_date = $booking['travel_date'];
                                                $return_date = $booking['return_date'];
                                                
                                                switch ($booking['status']) {
                                                    case 'confirmed':
                                                        $status_class = 'bg-success';
                                                        $status_icon = 'fas fa-check-circle';
                                                        $days_to_travel = (strtotime($travel_date) - strtotime($today)) / (60 * 60 * 24);
                                                        if ($days_to_travel > 0) {
                                                            $status_text = 'Confirmed (' . ceil($days_to_travel) . ' days to go)';
                                                        } else {
                                                            $status_text = 'Confirmed (Travel starts today!)';
                                                        }
                                                        break;
                                                    case 'in_progress':
                                                        $status_class = 'bg-info';
                                                        $status_icon = 'fas fa-plane';
                                                        $days_remaining = (strtotime($return_date) - strtotime($today)) / (60 * 60 * 24);
                                                        if ($days_remaining > 0) {
                                                            $status_text = 'In Progress (' . ceil($days_remaining) . ' days left)';
                                                        } else {
                                                            $status_text = 'In Progress (Ends today!)';
                                                        }
                                                        break;
                                                    case 'completed':
                                                        $status_class = 'bg-primary';
                                                        $status_icon = 'fas fa-flag-checkered';
                                                        $days_completed = (strtotime($today) - strtotime($return_date)) / (60 * 60 * 24);
                                                        $status_text = 'Completed (' . floor($days_completed) . ' days ago)';
                                                        break;
                                                    case 'cancelled':
                                                        $status_class = 'bg-danger';
                                                        $status_icon = 'fas fa-times-circle';
                                                        $status_text = 'Cancelled';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>" data-travel-date="<?php echo $travel_date; ?>" data-return-date="<?php echo $return_date; ?>" data-status="<?php echo $booking['status']; ?>">
                                                    <i class="<?php echo $status_icon; ?>"></i> 
                                                    <span class="status-text"><?php echo $status_text; ?></span>
                                                </span>
                                            </div>
                                            
                                            <h4 class="text-primary mb-3"><?php echo number_format($booking['total_amount'], 0); ?> Nrs.</h4>
                                            
                                            <div class="d-grid gap-2">
                                                <a href="package_booking_details.php?id=<?php echo $booking['booking_id']; ?>" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-eye"></i> View Details
                                                </a>
                                                
                                                <?php if ($booking['status'] === 'confirmed'): ?>
                                                    <button class="btn btn-outline-danger btn-sm" 
                                                            onclick="cancelBookingInstant('<?php echo $booking['booking_id']; ?>', 'package')">
                                                        <i class="fas fa-times"></i> Cancel
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($booking['status'] === 'completed'): ?>
                                                    <button class="btn btn-outline-warning btn-sm" 
                                                            onclick="showPackageReviewModal('<?php echo $booking['booking_id']; ?>', <?php echo $booking['package_id']; ?>, '<?php echo htmlspecialchars($booking['package_name']); ?>')">
                                                        <i class="fas fa-star"></i> Review
                                                    </button>
                                                <?php endif; ?>
                                            </div>
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
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12 text-center py-5">
                            <i class="fas fa-map-marked-alt fa-4x text-muted mb-3"></i>
                            <h4>No Package Bookings Found</h4>
                            <p class="text-muted mb-4">You haven't booked any travel packages yet.</p>
                            <a href="packages.php" class="btn btn-primary">
                                <i class="fas fa-map-marked-alt"></i> Browse Packages
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Review Modal -->
    <div class="modal fade" id="reviewModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-star"></i> Write a Review
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="reviewForm">
                        <input type="hidden" id="reviewBookingId" name="booking_id">
                        <input type="hidden" id="reviewRoomId" name="room_id">
                        <input type="hidden" name="action" value="submit_review">
                        
                        <div class="mb-3">
                            <label class="form-label">Room: <span id="reviewRoomName" class="fw-bold"></span></label>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Rating</label>
                            <div class="rating-input">
                                <input type="radio" name="rating" value="5" id="star5">
                                <label for="star5"><i class="fas fa-star"></i></label>
                                <input type="radio" name="rating" value="4" id="star4">
                                <label for="star4"><i class="fas fa-star"></i></label>
                                <input type="radio" name="rating" value="3" id="star3">
                                <label for="star3"><i class="fas fa-star"></i></label>
                                <input type="radio" name="rating" value="2" id="star2">
                                <label for="star2"><i class="fas fa-star"></i></label>
                                <input type="radio" name="rating" value="1" id="star1">
                                <label for="star1"><i class="fas fa-star"></i></label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Comment (Optional)</label>
                            <textarea class="form-control" name="comment" rows="4" 
                                      placeholder="Share your experience..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitReview()">
                        <i class="fas fa-paper-plane"></i> Submit Review
                    </button>
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
                // You can implement AJAX cancellation here
                window.location.href = 'cancel_booking.php?id=' + bookingId;
            }
        }
        
        function cancelPackageBooking(bookingId) {
            if (confirm('Are you sure you want to cancel this package booking?')) {
                // You can implement AJAX cancellation here
                window.location.href = 'cancel_package_booking.php?id=' + bookingId;
            }
        }
        
        function cancelBookingInstant(bookingId, bookingType) {
            const bookingTypeName = bookingType === 'package' ? 'package booking' : 'room booking';
            
            if (confirm(`Are you sure you want to cancel this ${bookingTypeName}?\n\nThis action cannot be undone.`)) {
                // Show loading state
                const button = event.target.closest('button');
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
                        // Show success message
                        showAlert(data.message, 'success');
                        
                        // Update the booking card to show cancelled status
                        const bookingCard = button.closest('.card');
                        const statusBadge = bookingCard.querySelector('.badge');
                        statusBadge.className = 'badge bg-danger';
                        statusBadge.innerHTML = '<i class="fas fa-times-circle"></i> Cancelled';
                        
                        // Remove the cancel button
                        button.remove();
                        
                        // Optionally reload the page after a delay
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                        
                    } else {
                        showAlert(data.message || 'Failed to cancel booking', 'danger');
                        // Restore button state
                        button.innerHTML = originalText;
                        button.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('An error occurred while cancelling the booking', 'danger');
                    // Restore button state
                    button.innerHTML = originalText;
                    button.disabled = false;
                });
            }
        }
        
        function showAlert(message, type) {
            // Remove existing alerts
            const existingAlerts = document.querySelectorAll('.alert-dismissible');
            existingAlerts.forEach(alert => alert.remove());
            
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
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
        
        function showReviewModal(bookingId, roomId, roomName) {
            document.getElementById('reviewBookingId').value = bookingId;
            document.getElementById('reviewRoomId').value = roomId;
            document.getElementById('reviewRoomName').textContent = roomName;
            
            const modal = new bootstrap.Modal(document.getElementById('reviewModal'));
            modal.show();
        }
        
        function showPackageReviewModal(bookingId, packageId, packageName) {
            // Similar to room review but for packages
            alert('Package review functionality - Booking ID: ' + bookingId);
        }
        
        function submitReview() {
            const form = document.getElementById('reviewForm');
            const formData = new FormData(form);
            
            fetch('ajax/review.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Review submitted successfully!');
                    bootstrap.Modal.getInstance(document.getElementById('reviewModal')).hide();
                    location.reload();
                } else {
                    alert(data.message || 'Failed to submit review');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while submitting the review');
            });
        }
        
        // Real-time status updates for package bookings
        function updatePackageStatus() {
            const statusBadges = document.querySelectorAll('.badge[data-travel-date]');
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            statusBadges.forEach(badge => {
                const travelDate = new Date(badge.dataset.travelDate);
                const returnDate = new Date(badge.dataset.returnDate);
                const currentStatus = badge.dataset.status;
                const statusText = badge.querySelector('.status-text');
                
                travelDate.setHours(0, 0, 0, 0);
                returnDate.setHours(0, 0, 0, 0);
                
                if (currentStatus === 'confirmed') {
                    const daysToTravel = Math.ceil((travelDate - today) / (1000 * 60 * 60 * 24));
                    if (daysToTravel > 0) {
                        statusText.textContent = `Confirmed (${daysToTravel} days to go)`;
                    } else if (daysToTravel === 0) {
                        statusText.textContent = 'Confirmed (Travel starts today!)';
                    }
                } else if (currentStatus === 'in_progress') {
                    const daysRemaining = Math.ceil((returnDate - today) / (1000 * 60 * 60 * 24));
                    if (daysRemaining > 0) {
                        statusText.textContent = `In Progress (${daysRemaining} days left)`;
                    } else if (daysRemaining === 0) {
                        statusText.textContent = 'In Progress (Ends today!)';
                    }
                } else if (currentStatus === 'completed') {
                    const daysCompleted = Math.floor((today - returnDate) / (1000 * 60 * 60 * 24));
                    statusText.textContent = `Completed (${daysCompleted} days ago)`;
                }
            });
        }
        
        // Update every minute
        setInterval(updatePackageStatus, 60000);
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updatePackageStatus();
        });
    </script>
    
    <style>
        .rating-input {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
        }
        
        .rating-input input {
            display: none;
        }
        
        .rating-input label {
            cursor: pointer;
            color: #ddd;
            font-size: 1.5rem;
            margin-right: 5px;
        }
        
        .rating-input input:checked ~ label,
        .rating-input label:hover,
        .rating-input label:hover ~ label {
            color: #ffc107;
        }
    </style>
</body>
</html>