<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$package_id = $_GET['id'] ?? 0;

if (!$package_id) {
    header('Location: packages.php');
    exit();
}

$package = getPackageById($conn, $package_id);
if (!$package) {
    header('Location: packages.php');
    exit();
}

// Get package reviews
$reviews = getPackageReviews($conn, $package_id, 10);

// Parse JSON fields
$inclusions = json_decode($package['inclusions'], true) ?: [];
$exclusions = json_decode($package['exclusions'], true) ?: [];
$highlights = json_decode($package['highlights'], true) ?: [];
$itinerary = json_decode($package['itinerary'], true) ?: [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($package['name']); ?> - Travel Package Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <!-- Package Header -->
        <div class="row mb-4">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="packages.php">Packages</a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($package['name']); ?></li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <!-- Package Image and Basic Info -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <?php if (!empty($package['image']) && file_exists($package['image'])): ?>
                        <img src="<?php echo htmlspecialchars($package['image']); ?>" 
                             class="card-img-top" alt="<?php echo htmlspecialchars($package['name']); ?>" 
                             style="height: 400px; object-fit: cover;">
                    <?php else: ?>
                        <div class="card-img-top d-flex align-items-center justify-content-center bg-light" 
                             style="height: 400px;">
                            <i class="fas fa-image fa-4x text-muted"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h1 class="card-title mb-2"><?php echo htmlspecialchars($package['name']); ?></h1>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-map-marker-alt me-2"></i>
                                    <?php echo htmlspecialchars($package['destination']); ?>
                                </p>
                            </div>
                            <?php if ($package['featured']): ?>
                                <span class="badge bg-warning">
                                    <i class="fas fa-star"></i> Featured
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-3 text-center">
                                <div class="border rounded p-3">
                                    <i class="fas fa-calendar-alt fa-2x text-primary mb-2"></i>
                                    <h6>Duration</h6>
                                    <p class="mb-0"><?php echo $package['duration_days']; ?>D/<?php echo $package['duration_nights']; ?>N</p>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="border rounded p-3">
                                    <i class="fas fa-users fa-2x text-primary mb-2"></i>
                                    <h6>Max People</h6>
                                    <p class="mb-0"><?php echo $package['max_people']; ?> people</p>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="border rounded p-3">
                                    <i class="fas fa-mountain fa-2x text-primary mb-2"></i>
                                    <h6>Difficulty</h6>
                                    <p class="mb-0"><?php echo ucfirst($package['difficulty_level']); ?></p>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="border rounded p-3">
                                    <i class="fas fa-sun fa-2x text-primary mb-2"></i>
                                    <h6>Best Season</h6>
                                    <p class="mb-0"><?php echo htmlspecialchars($package['best_season']); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h4>About This Package</h4>
                            <p><?php echo nl2br(htmlspecialchars($package['description'])); ?></p>
                        </div>

                        <?php if (!empty($highlights)): ?>
                        <div class="mb-4">
                            <h4>Package Highlights</h4>
                            <ul class="list-unstyled">
                                <?php foreach ($highlights as $highlight): ?>
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <?php echo htmlspecialchars($highlight); ?>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($itinerary)): ?>
                        <div class="mb-4">
                            <h4>Itinerary</h4>
                            <div class="timeline">
                                <?php foreach ($itinerary as $index => $day): ?>
                                <div class="d-flex mb-3">
                                    <div class="flex-shrink-0">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                             style="width: 40px; height: 40px;">
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

                        <div class="row">
                            <?php if (!empty($inclusions)): ?>
                            <div class="col-md-6 mb-4">
                                <h5 class="text-success">
                                    <i class="fas fa-check-circle me-2"></i>Inclusions
                                </h5>
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
                            <div class="col-md-6 mb-4">
                                <h5 class="text-danger">
                                    <i class="fas fa-times-circle me-2"></i>Exclusions
                                </h5>
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
                    </div>
                </div>

                <!-- Reviews Section -->
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-star text-warning me-2"></i>
                            Reviews (<?php echo count($reviews); ?>)
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($reviews)): ?>
                            <?php foreach ($reviews as $review): ?>
                            <div class="border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($review['user_name']); ?></h6>
                                        <div class="rating">
                                            <?php echo generateStars($review['rating']); ?>
                                        </div>
                                    </div>
                                    <small class="text-muted"><?php echo formatDate($review['created_at']); ?></small>
                                </div>
                                <p class="mb-0"><?php echo htmlspecialchars($review['comment']); ?></p>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted text-center py-4">No reviews yet. Be the first to review this package!</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Booking Sidebar -->
            <div class="col-lg-4">
                <div class="card sticky-top" style="top: 20px;">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <div class="d-flex justify-content-center align-items-center mb-2">
                                <?php if ($package['original_price']): ?>
                                    <span class="text-muted text-decoration-line-through me-2"><?php echo number_format($package['original_price'], 0); ?> Nrs.</span>
                                <?php endif; ?>
                                <h3 class="text-primary mb-0"><?php echo number_format($package['price'], 0); ?> Nrs.</h3>
                            </div>
                            <small class="text-muted">per person</small>
                            
                            <div class="rating mt-2">
                                <?php echo generateStars($package['rating']); ?>
                                <small class="text-muted ms-2">(<?php echo $package['review_count']; ?> reviews)</small>
                            </div>
                        </div>

                        <?php if (isLoggedIn()): ?>
                        <form id="packageBookingForm">
                            <input type="hidden" name="package_id" value="<?php echo $package['id']; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Travel Date</label>
                                <input type="date" class="form-control" name="travel_date" required 
                                       min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Number of Travelers</label>
                                <input type="number" class="form-control" name="travelers" 
                                       min="1" max="<?php echo $package['max_people']; ?>" 
                                       value="1" required id="travelersInput">
                                <div class="form-text">Maximum <?php echo $package['max_people']; ?> people allowed</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Contact Phone</label>
                                <input type="tel" class="form-control" name="contact_phone" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Emergency Contact</label>
                                <input type="text" class="form-control" name="emergency_contact" 
                                       placeholder="Name and phone number">
                            </div>
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span>Total Amount:</span>
                                    <strong id="totalAmount"><?php echo number_format($package['price'], 0); ?> Nrs.</strong>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mb-2">
                                <i class="fas fa-calendar-check me-2"></i>Book Now
                            </button>
                        </form>
                        <?php else: ?>
                        <div class="text-center">
                            <p class="text-muted mb-3">Please login to book this package</p>
                            <a href="login.php" class="btn btn-primary w-100">
                                <i class="fas fa-sign-in-alt me-2"></i>Login to Book
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Free cancellation up to 24 hours before travel date
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Success Modal -->
    <div class="modal fade" id="bookingSuccessModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-check-circle me-2"></i>Booking Confirmed!
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                    <h4>Thank you for your booking!</h4>
                    <p class="mb-3">Your package booking has been confirmed successfully.</p>
                    <p><strong>Booking ID:</strong> <span id="bookingId"></span></p>
                    <p class="text-muted">You will receive a confirmation email shortly with all the details.</p>
                </div>
                <div class="modal-footer">
                    <a href="bookings.php" class="btn btn-primary">View My Bookings</a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('packageBookingForm');
            const travelersInput = document.getElementById('travelersInput');
            const totalAmountSpan = document.getElementById('totalAmount');
            const packagePrice = <?php echo $package['price']; ?>;
            
            // Update total amount when travelers change
            if (travelersInput) {
                travelersInput.addEventListener('input', function() {
                    const travelers = parseInt(this.value) || 1;
                    const total = packagePrice * travelers;
                    const total = packagePrice * travelers;
                    totalAmountSpan.textContent = total.toLocaleString('en-IN') + ' Nrs.';
                });
            }
            
            // Handle form submission
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(form);
                    const travelers = parseInt(formData.get('travelers'));
                    const totalAmount = packagePrice * travelers;
                    formData.append('total_amount', totalAmount);
                    
                    fetch('ajax/package_booking.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('bookingId').textContent = data.booking_id;
                            new bootstrap.Modal(document.getElementById('bookingSuccessModal')).show();
                            form.reset();
                        } else {
                            alert('Booking failed: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while processing your booking.');
                    });
                });
            }
        });
    </script>
</body>
</html>