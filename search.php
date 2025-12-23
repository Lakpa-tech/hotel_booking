<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$checkin = $_GET['checkin'] ?? '';
$checkout = $_GET['checkout'] ?? '';
$guests = $_GET['guests'] ?? 1;

$rooms = [];
$error = '';

if ($checkin && $checkout) {
    // Validate dates
    $checkin_date = new DateTime($checkin);
    $checkout_date = new DateTime($checkout);
    $today = new DateTime();
    
    if ($checkin_date < $today) {
        $error = 'Check-in date cannot be in the past';
    } elseif ($checkout_date <= $checkin_date) {
        $error = 'Check-out date must be after check-in date';
    } else {
        $rooms = searchRooms($conn, $checkin, $checkout, $guests);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - Hotel Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-12 mb-4">
                <h1><i class="fas fa-search"></i> Search Results</h1>
                <?php if ($checkin && $checkout): ?>
                    <p class="text-muted">
                        Available rooms from <?php echo formatDate($checkin); ?> to <?php echo formatDate($checkout); ?> 
                        for <?php echo $guests; ?> guest<?php echo $guests > 1 ? 's' : ''; ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Search Form -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Check-in</label>
                        <input type="date" class="form-control" name="checkin" 
                               value="<?php echo htmlspecialchars($checkin); ?>" 
                               min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Check-out</label>
                        <input type="date" class="form-control" name="checkout" 
                               value="<?php echo htmlspecialchars($checkout); ?>" 
                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Guests</label>
                        <input type="number" class="form-control" name="guests" 
                               min="1" max="10" value="<?php echo $guests; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Search Rooms
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Results -->
        <div class="row">
            <?php if (!empty($rooms)): ?>
                <?php foreach ($rooms as $room): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card room-card h-100">
                        <?php if ($room['featured']): ?>
                            <div class="badge bg-warning position-absolute" style="top: 10px; right: 10px; z-index: 1;">
                                <i class="fas fa-star"></i> Featured
                            </div>
                        <?php endif; ?>
                        
                        <img src="<?php echo htmlspecialchars($room['image']); ?>" 
                             class="card-img-top" alt="<?php echo htmlspecialchars($room['name']); ?>" 
                             style="height: 250px; object-fit: cover;">
                        
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($room['name']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars(substr($room['description'], 0, 100)) . '...'; ?></p>
                            
                            <div class="room-details mb-3">
                                <small class="text-muted">
                                    <i class="fas fa-users"></i> <?php echo $room['capacity']; ?> guests
                                    <span class="mx-2">|</span>
                                    <i class="fas fa-expand-arrows-alt"></i> <?php echo htmlspecialchars($room['size']); ?>
                                    <span class="mx-2">|</span>
                                    <i class="fas fa-bed"></i> <?php echo htmlspecialchars($room['bed_type']); ?>
                                </small>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h5 text-primary mb-0"><?php echo number_format($room['price'], 0); ?> Nrs./night</span>
                                <div class="rating">
                                    <?php echo generateStars($room['rating']); ?>
                                    <small class="text-muted">(<?php echo $room['review_count']; ?>)</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-footer">
                            <div class="d-grid gap-2">
                                <a href="room_details.php?id=<?php echo $room['id']; ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                                <?php if ($checkin && $checkout): ?>
                                    <form method="POST" action="checkout.php" class="d-inline">
                                        <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                        <input type="hidden" name="checkin" value="<?php echo htmlspecialchars($checkin); ?>">
                                        <input type="hidden" name="checkout" value="<?php echo htmlspecialchars($checkout); ?>">
                                        <input type="hidden" name="guests" value="<?php echo $guests; ?>">
                                        <?php if (isLoggedIn()): ?>
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="fas fa-credit-card"></i> Book Now
                                            </button>
                                        <?php else: ?>
                                            <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" 
                                               class="btn btn-primary w-100">
                                                <i class="fas fa-sign-in-alt"></i> Login to Book
                                            </a>
                                        <?php endif; ?>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php elseif ($checkin && $checkout && !$error): ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-bed fa-4x text-muted mb-3"></i>
                    <h4>No Available Rooms</h4>
                    <p class="text-muted mb-4">
                        Sorry, no rooms are available for your selected dates. 
                        Please try different dates or reduce the number of guests.
                    </p>
                    <a href="rooms.php" class="btn btn-primary">
                        <i class="fas fa-bed"></i> View All Rooms
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        // Set minimum checkout date when checkin changes
        document.querySelector('input[name="checkin"]').addEventListener('change', function() {
            const checkinDate = new Date(this.value);
            checkinDate.setDate(checkinDate.getDate() + 1);
            document.querySelector('input[name="checkout"]').min = checkinDate.toISOString().split('T')[0];
        });
    </script>
</body>
</html>