<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Get user's reviews with error handling
try {
    $user_reviews = getUserReviews($conn, $_SESSION['user_id']);
    $package_reviews = getUserPackageReviews($conn, $_SESSION['user_id']);
} catch (Exception $e) {
    $user_reviews = [];
    $package_reviews = [];
    error_log("Error fetching reviews: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reviews - Hotel Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-12 mb-4">
                <h1><i class="fas fa-star"></i> My Reviews</h1>
                <p class="text-muted">View and manage your reviews for rooms and packages</p>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs mb-4" id="reviewTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="room-reviews-tab" data-bs-toggle="tab" data-bs-target="#room-reviews" type="button" role="tab">
                    <i class="fas fa-bed me-2"></i>Room Reviews (<?php echo count($user_reviews); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="package-reviews-tab" data-bs-toggle="tab" data-bs-target="#package-reviews" type="button" role="tab">
                    <i class="fas fa-map-marked-alt me-2"></i>Package Reviews (<?php echo count($package_reviews); ?>)
                </button>
            </li>
        </ul>

        <div class="tab-content" id="reviewTabsContent">
            <!-- Room Reviews Tab -->
            <div class="tab-pane fade show active" id="room-reviews" role="tabpanel">
                <div class="row">
                    <?php if (!empty($user_reviews)): ?>
                        <?php foreach ($user_reviews as $review): ?>
                        <div class="col-12 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <h5 class="card-title"><?php echo htmlspecialchars($review['room_name']); ?></h5>
                                            
                                            <div class="mb-2">
                                                <div class="rating">
                                                    <?php echo generateStars($review['rating']); ?>
                                                    <span class="ms-2 text-muted"><?php echo $review['rating']; ?>/5</span>
                                                </div>
                                            </div>
                                            
                                            <?php if ($review['comment']): ?>
                                            <div class="mb-3">
                                                <p class="card-text"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <small class="text-muted">
                                                <i class="fas fa-calendar"></i> 
                                                Reviewed on <?php echo formatDate($review['created_at']); ?>
                                            </small>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <div class="mb-2">
                                                <span class="badge bg-primary">Room Review</span>
                                            </div>
                                            <div class="btn-group-vertical btn-group-sm">
                                                <a href="room_details.php?id=<?php echo $review['room_id']; ?>" 
                                                   class="btn btn-outline-primary">
                                                    <i class="fas fa-eye"></i> View Room
                                                </a>
                                                <?php if ($review['booking_id']): ?>
                                                <a href="booking_details.php?id=<?php echo $review['booking_id']; ?>" 
                                                   class="btn btn-outline-info">
                                                    <i class="fas fa-receipt"></i> View Booking
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12 text-center py-5">
                            <i class="fas fa-star fa-4x text-muted mb-3"></i>
                            <h4>No Room Reviews Yet</h4>
                            <p class="text-muted mb-4">You haven't reviewed any rooms yet. Complete a booking to leave a review!</p>
                            <a href="rooms.php" class="btn btn-primary">
                                <i class="fas fa-bed"></i> Browse Rooms
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Package Reviews Tab -->
            <div class="tab-pane fade" id="package-reviews" role="tabpanel">
                <div class="row">
                    <?php if (!empty($package_reviews)): ?>
                        <?php foreach ($package_reviews as $review): ?>
                        <div class="col-12 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <h5 class="card-title"><?php echo htmlspecialchars($review['package_name']); ?></h5>
                                            <p class="text-muted mb-2">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <?php echo htmlspecialchars($review['destination']); ?>
                                            </p>
                                            
                                            <div class="mb-2">
                                                <div class="rating">
                                                    <?php echo generateStars($review['rating']); ?>
                                                    <span class="ms-2 text-muted"><?php echo $review['rating']; ?>/5</span>
                                                </div>
                                            </div>
                                            
                                            <?php if ($review['comment']): ?>
                                            <div class="mb-3">
                                                <p class="card-text"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <small class="text-muted">
                                                <i class="fas fa-calendar"></i> 
                                                Reviewed on <?php echo formatDate($review['created_at']); ?>
                                            </small>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <div class="mb-2">
                                                <span class="badge bg-success">Package Review</span>
                                            </div>
                                            <div class="btn-group-vertical btn-group-sm">
                                                <a href="package_details.php?id=<?php echo $review['package_id']; ?>" 
                                                   class="btn btn-outline-primary">
                                                    <i class="fas fa-eye"></i> View Package
                                                </a>
                                                <?php if ($review['booking_id']): ?>
                                                <a href="package_booking_details.php?id=<?php echo $review['booking_id']; ?>" 
                                                   class="btn btn-outline-info">
                                                    <i class="fas fa-receipt"></i> View Booking
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12 text-center py-5">
                            <i class="fas fa-map-marked-alt fa-4x text-muted mb-3"></i>
                            <h4>No Package Reviews Yet</h4>
                            <p class="text-muted mb-4">You haven't reviewed any travel packages yet. Complete a package booking to leave a review!</p>
                            <a href="packages.php" class="btn btn-primary">
                                <i class="fas fa-map-marked-alt"></i> Browse Packages
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Summary Statistics -->
        <?php if (!empty($user_reviews) || !empty($package_reviews)): ?>
        <div class="row mt-5">
            <div class="col-12">
                <div class="card bg-light">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-chart-bar me-2"></i>Your Review Summary
                        </h5>
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="border-end">
                                    <h3 class="text-primary"><?php echo count($user_reviews) + count($package_reviews); ?></h3>
                                    <p class="text-muted mb-0">Total Reviews</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border-end">
                                    <h3 class="text-success"><?php echo count($user_reviews); ?></h3>
                                    <p class="text-muted mb-0">Room Reviews</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border-end">
                                    <h3 class="text-info"><?php echo count($package_reviews); ?></h3>
                                    <p class="text-muted mb-0">Package Reviews</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <?php 
                                $all_reviews = [...$user_reviews, ...$package_reviews];
                                $avg_rating = !empty($all_reviews) ? array_sum(array_column($all_reviews, 'rating')) / count($all_reviews) : 0;
                                ?>
                                <h3 class="text-warning"><?php echo number_format($avg_rating, 1); ?></h3>
                                <p class="text-muted mb-0">Average Rating</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>