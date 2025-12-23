<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$room_id = $_GET['id'] ?? 0;
$room = getRoomById($conn, $room_id);

if (!$room) {
    header('Location: rooms.php');
    exit();
}

// Get room features
$stmt = $conn->prepare("
    SELECT rf.* FROM room_features rf
    JOIN room_feature_assignments rfa ON rf.id = rfa.feature_id
    WHERE rfa.room_id = ?
");
$stmt->execute([$room_id]);
$features = $stmt->fetchAll();

// Get room reviews
$reviews = getRoomReviews($conn, $room_id, 5);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($room['name']); ?> - Hotel Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-12 mb-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="rooms.php">Rooms</a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($room['name']); ?></li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Room Image -->
                <div class="mb-4">
                    <img src="<?php echo htmlspecialchars($room['image']); ?>" 
                         class="img-fluid rounded" alt="<?php echo htmlspecialchars($room['name']); ?>"
                         style="width: 100%; height: 400px; object-fit: cover;">
                </div>

                <!-- Room Details -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h2><?php echo htmlspecialchars($room['name']); ?></h2>
                                <div class="rating mb-2">
                                    <?php echo generateStars($room['rating']); ?>
                                    <span class="ms-2 text-muted">(<?php echo $room['review_count']; ?> reviews)</span>
                                </div>
                            </div>
                            <div class="text-end">
                                <h3 class="text-primary mb-0"><?php echo number_format($room['price'], 0); ?> Nrs.</h3>
                                <small class="text-muted">per night</small>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-3 mb-2">
                                <i class="fas fa-users text-primary me-2"></i>
                                <strong>Capacity:</strong> <?php echo $room['capacity']; ?> guests
                            </div>
                            <div class="col-md-3 mb-2">
                                <i class="fas fa-expand-arrows-alt text-primary me-2"></i>
                                <strong>Size:</strong> <?php echo htmlspecialchars($room['size']); ?>
                            </div>
                            <div class="col-md-3 mb-2">
                                <i class="fas fa-bed text-primary me-2"></i>
                                <strong>Bed:</strong> <?php echo htmlspecialchars($room['bed_type']); ?>
                            </div>
                        </div>

                        <h5>Description</h5>
                        <p><?php echo nl2br(htmlspecialchars($room['description'])); ?></p>
                    </div>
                </div>

                <!-- Room Features -->
                <?php if (!empty($features)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-star"></i> Room Features</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($features as $feature): ?>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="<?php echo htmlspecialchars($feature['icon']); ?> text-primary me-3"></i>
                                    <div>
                                        <strong><?php echo htmlspecialchars($feature['name']); ?></strong>
                                        <?php if ($feature['description']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($feature['description']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Reviews -->
                <?php if (!empty($reviews)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-comments"></i> Guest Reviews</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($reviews as $review): ?>
                        <div class="review-item mb-4">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <strong><?php echo htmlspecialchars($review['user_name']); ?></strong>
                                    <div class="rating">
                                        <?php echo generateStars($review['rating']); ?>
                                    </div>
                                </div>
                                <small class="text-muted"><?php echo formatDate($review['created_at']); ?></small>
                            </div>
                            <?php if ($review['comment']): ?>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                            <?php endif; ?>
                        </div>
                        <?php if ($review !== end($reviews)): ?>
                            <hr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Booking Form -->
            <div class="col-lg-4">
                <div class="card sticky-top" style="top: 100px;">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-calendar-check"></i> Book This Room</h5>
                    </div>
                    <div class="card-body">
                        <form action="checkout.php" method="POST" id="bookingForm">
                            <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Check-in Date</label>
                                <input type="date" class="form-control" name="checkin" required 
                                       min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Check-out Date</label>
                                <input type="date" class="form-control" name="checkout" required 
                                       min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Number of Guests</label>
                                <input type="number" class="form-control" name="guests" 
                                       min="1" max="<?php echo $room['capacity']; ?>" 
                                       value="1" required>
                                <div class="form-text">Maximum <?php echo $room['capacity']; ?> guests allowed</div>
                            </div>
                            
                            <div class="price-summary mb-3 p-3 bg-light rounded">
                                <div class="d-flex justify-content-between">
                                    <span>Price per night:</span>
                                    <span><?php echo number_format($room['price'], 0); ?> Nrs.</span>
                                </div>
                                <div class="d-flex justify-content-between" id="nightsRow" style="display: none;">
                                    <span id="nightsText">0 nights:</span>
                                    <span id="subtotalText">0 Nrs.</span>
                                </div>
                                <div class="d-flex justify-content-between" id="taxRow" style="display: none;">
                                    <span>Taxes & fees:</span>
                                    <span id="taxText">0 Nrs.</span>
                                </div>
                                <hr id="totalSeparator" style="display: none;">
                                <div class="d-flex justify-content-between fw-bold" id="totalRow" style="display: none;">
                                    <span>Total:</span>
                                    <span id="totalText" class="text-primary">0 Nrs.</span>
                                </div>
                            </div>
                            
                            <div id="availabilityMessage" class="mb-3"></div>
                            
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
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        const roomPrice = <?php echo $room['price']; ?>;
        const roomId = <?php echo $room['id']; ?>;
        
        function updatePricing() {
            const checkin = document.querySelector('input[name="checkin"]').value;
            const checkout = document.querySelector('input[name="checkout"]').value;
            
            if (checkin && checkout) {
                const checkinDate = new Date(checkin);
                const checkoutDate = new Date(checkout);
                const nights = Math.ceil((checkoutDate - checkinDate) / (1000 * 60 * 60 * 24));
                
                if (nights > 0) {
                    const subtotal = roomPrice * nights;
                    const tax = subtotal * 0.12;
                    const total = subtotal + tax;
                    
                    document.getElementById('nightsText').textContent = nights + ' night' + (nights > 1 ? 's' : '') + ':';
                    document.getElementById('subtotalText').textContent = Math.floor(subtotal).toLocaleString('en-IN') + ' Nrs.';
                    document.getElementById('taxText').textContent = Math.floor(tax).toLocaleString('en-IN') + ' Nrs.';
                    document.getElementById('totalText').textContent = Math.floor(total).toLocaleString('en-IN') + ' Nrs.';
                    
                    document.getElementById('nightsRow').style.display = 'flex';
                    document.getElementById('taxRow').style.display = 'flex';
                    document.getElementById('totalSeparator').style.display = 'block';
                    document.getElementById('totalRow').style.display = 'flex';
                    
                    // Check availability in real-time
                    checkAvailability(checkin, checkout);
                } else {
                    hideExtendedPricing();
                }
            } else {
                hideExtendedPricing();
            }
        }
        
        function checkAvailability(checkin, checkout) {
            const submitBtn = document.querySelector('button[type="submit"]');
            const availabilityMsg = document.getElementById('availabilityMessage');
            
            // Show loading state
            if (availabilityMsg) {
                availabilityMsg.innerHTML = '<small class="text-info"><i class="fas fa-spinner fa-spin me-1"></i>Checking availability...</small>';
            }
            
            fetch('ajax/check_availability.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `room_id=${roomId}&checkin=${checkin}&checkout=${checkout}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.available) {
                    if (availabilityMsg) {
                        availabilityMsg.innerHTML = '<small class="text-success"><i class="fas fa-check-circle me-1"></i>Room is available for selected dates</small>';
                    }
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fas fa-credit-card"></i> Book Now';
                    }
                } else {
                    if (availabilityMsg) {
                        availabilityMsg.innerHTML = '<small class="text-danger"><i class="fas fa-times-circle me-1"></i>Room is not available for selected dates</small>';
                    }
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-ban"></i> Not Available';
                    }
                }
            })
            .catch(error => {
                console.error('Error checking availability:', error);
                if (availabilityMsg) {
                    availabilityMsg.innerHTML = '<small class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>Unable to check availability</small>';
                }
            });
        }
        
        function hideExtendedPricing() {
            document.getElementById('nightsRow').style.display = 'none';
            document.getElementById('taxRow').style.display = 'none';
            document.getElementById('totalSeparator').style.display = 'none';
            document.getElementById('totalRow').style.display = 'none';
            
            const availabilityMsg = document.getElementById('availabilityMessage');
            if (availabilityMsg) {
                availabilityMsg.innerHTML = '';
            }
        }
        
        document.querySelector('input[name="checkin"]').addEventListener('change', updatePricing);
        document.querySelector('input[name="checkout"]').addEventListener('change', updatePricing);
        
        // Set minimum checkout date when checkin changes
        document.querySelector('input[name="checkin"]').addEventListener('change', function() {
            const checkinDate = new Date(this.value);
            checkinDate.setDate(checkinDate.getDate() + 1);
            document.querySelector('input[name="checkout"]').min = checkinDate.toISOString().split('T')[0];
        });
    </script>
</body>
</html>