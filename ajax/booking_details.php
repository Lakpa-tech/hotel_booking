<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    echo '<div class="alert alert-danger">Please login to view booking details</div>';
    exit();
}

$booking_id = $_GET['booking_id'] ?? '';
if (!$booking_id) {
    echo '<div class="alert alert-danger">Invalid booking ID</div>';
    exit();
}

$booking = getBookingById($conn, $booking_id, $_SESSION['user_id']);
if (!$booking) {
    echo '<div class="alert alert-danger">Booking not found</div>';
    exit();
}
?>

<div class="row">
    <div class="col-md-4 mb-3">
        <img src="<?php echo htmlspecialchars($booking['room_image']); ?>" 
             class="img-fluid rounded" alt="<?php echo htmlspecialchars($booking['room_name']); ?>">
    </div>
    <div class="col-md-8">
        <h5><?php echo htmlspecialchars($booking['room_name']); ?></h5>
        <p class="text-muted mb-3">Booking ID: <?php echo htmlspecialchars($booking['booking_id']); ?></p>
        
        <div class="row">
            <div class="col-sm-6 mb-2">
                <strong>Check-in Date:</strong><br>
                <?php echo formatDate($booking['checkin_date']); ?>
            </div>
            <div class="col-sm-6 mb-2">
                <strong>Check-out Date:</strong><br>
                <?php echo formatDate($booking['checkout_date']); ?>
            </div>
            <div class="col-sm-6 mb-2">
                <strong>Number of Guests:</strong><br>
                <?php echo $booking['guests']; ?>
            </div>
            <div class="col-sm-6 mb-2">
                <strong>Number of Nights:</strong><br>
                <?php echo $booking['nights']; ?>
            </div>
            <div class="col-sm-6 mb-2">
                <strong>Room Price:</strong><br>
                <?php echo number_format($booking['room_price'], 0); ?> Nrs. per night
            </div>
            <div class="col-sm-6 mb-2">
                <strong>Total Amount:</strong><br>
                <span class="text-primary h6"><?php echo number_format($booking['total_amount'], 0); ?> Nrs.</span>
            </div>
        </div>
        
        <div class="mt-3">
            <strong>Status:</strong>
            <span class="badge bg-<?php 
                echo $booking['status'] === 'completed' ? 'success' : 
                    ($booking['status'] === 'cancelled' ? 'danger' : 
                    ($booking['status'] === 'confirmed' ? 'primary' : 'warning')); 
            ?>">
                <?php echo ucfirst(str_replace('_', ' ', $booking['status'])); ?>
            </span>
        </div>
        
        <?php if ($booking['special_requests']): ?>
            <div class="mt-3">
                <strong>Special Requests:</strong><br>
                <p class="text-muted"><?php echo htmlspecialchars($booking['special_requests']); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="mt-3">
            <small class="text-muted">
                Booked on <?php echo formatDate($booking['created_at']); ?>
            </small>
        </div>
    </div>
</div>

<?php if ($booking['status'] === 'confirmed'): ?>
    <div class="alert alert-info mt-3">
        <h6><i class="fas fa-info-circle"></i> Important Information</h6>
        <ul class="mb-0">
            <li>Check-in time: 3:00 PM</li>
            <li>Check-out time: 11:00 AM</li>
            <li>Please bring a valid ID for check-in</li>
            <li>Free cancellation up to 24 hours before check-in</li>
        </ul>
    </div>
<?php endif; ?>