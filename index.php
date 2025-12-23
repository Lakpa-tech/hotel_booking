<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Get featured rooms
$featured_rooms = getFeaturedRooms($conn, 6);

// Get featured packages
$featured_packages = getFeaturedPackages($conn, 3);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rin-Odge</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-overlay">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8 text-center text-white">
                        <h1 class="display-4 mb-4">Welcome to Our Hotel</h1>
                        <p class="lead mb-5">Experience luxury and comfort in our premium rooms</p>
                        
                        <!-- Search Form -->
                        <div class="search-form bg-white p-4 rounded shadow">
                            <form id="searchForm" action="search.php" method="GET">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label text-dark">Check-in</label>
                                        <input type="date" class="form-control" name="checkin" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label text-dark">Check-out</label>
                                        <input type="date" class="form-control" name="checkout" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label text-dark">Guests</label>
                                        <input type="number" class="form-control" name="guests" 
                                               min="1" max="10" value="1" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-dark">&nbsp;</label>
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-search"></i> Search Rooms
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Rooms -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2>Featured Rooms</h2>
                    <p class="text-muted">Discover our most popular accommodations</p>
                </div>
            </div>
            <div class="row">
                <?php foreach ($featured_rooms as $room): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card room-card h-100">
                        <img src="<?php echo htmlspecialchars($room['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($room['name']); ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($room['name']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars(substr($room['description'], 0, 100)) . '...'; ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h5 text-primary mb-0"><?php echo number_format($room['price'], 0); ?> Nrs./night</span>
                                <div class="rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= $room['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="room_details.php?id=<?php echo $room['id']; ?>" class="btn btn-primary w-100">View Details</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Featured Packages -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2>Featured Travel Packages</h2>
                    <p class="text-muted">Explore the beautiful Eastern Himalayas</p>
                </div>
            </div>
            <div class="row">
                <?php foreach ($featured_packages as $package): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card room-card h-100">
                        <?php if (!empty($package['image']) && file_exists($package['image'])): ?>
                            <img src="<?php echo htmlspecialchars($package['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($package['name']); ?>">
                        <?php else: ?>
                            <div class="card-img-top d-flex align-items-center justify-content-center bg-light" 
                                 style="height: 200px;">
                                <i class="fas fa-image fa-3x text-muted"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($package['name']); ?></h5>
                            <p class="text-muted mb-2">
                                <i class="fas fa-map-marker-alt me-1"></i>
                                <?php echo htmlspecialchars($package['destination']); ?>
                            </p>
                            <p class="card-text"><?php echo htmlspecialchars(substr($package['description'], 0, 80)) . '...'; ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="h5 text-primary mb-0"><?php echo number_format($package['price'], 0); ?> Nrs.</span>
                                    <small class="text-muted">/person</small>
                                </div>
                                <div class="text-muted">
                                    <small><?php echo $package['duration_days']; ?>D/<?php echo $package['duration_nights']; ?>N</small>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="package_details.php?id=<?php echo $package['id']; ?>" class="btn btn-primary w-100">View Package</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center">
                <a href="packages.php" class="btn btn-outline-primary btn-lg">
                    <i class="fas fa-map-marked-alt me-2"></i>View All Packages
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2>Why Choose Us</h2>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="text-center">
                        <i class="fas fa-wifi fa-3x text-primary mb-3"></i>
                        <h5>Free WiFi</h5>
                        <p>High-speed internet in all rooms</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="text-center">
                        <i class="fas fa-swimming-pool fa-3x text-primary mb-3"></i>
                        <h5>Swimming Pool</h5>
                        <p>Relax in our outdoor pool</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="text-center">
                        <i class="fas fa-utensils fa-3x text-primary mb-3"></i>
                        <h5>Restaurant</h5>
                        <p>Fine dining experience</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="text-center">
                        <i class="fas fa-car fa-3x text-primary mb-3"></i>
                        <h5>Free Parking</h5>
                        <p>Complimentary parking for guests</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>