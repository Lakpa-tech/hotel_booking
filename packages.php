<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Get filter parameters
$price_min = $_GET['price_min'] ?? '';
$price_max = $_GET['price_max'] ?? '';
$duration = $_GET['duration'] ?? '';
$difficulty = $_GET['difficulty'] ?? '';
$destination = $_GET['destination'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'featured';
$search = $_GET['search'] ?? '';

// Build query conditions
$where_conditions = ["p.status = 'active'"];
$params = [];

if ($price_min !== '') {
    $where_conditions[] = "p.price >= ?";
    $params[] = $price_min;
}

if ($price_max !== '') {
    $where_conditions[] = "p.price <= ?";
    $params[] = $price_max;
}

if ($duration !== '') {
    $where_conditions[] = "p.duration_days = ?";
    $params[] = $duration;
}

if ($difficulty !== '') {
    $where_conditions[] = "p.difficulty_level = ?";
    $params[] = $difficulty;
}

if ($destination !== '') {
    $where_conditions[] = "p.destination LIKE ?";
    $params[] = "%$destination%";
}

if ($search !== '') {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ? OR p.destination LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$where_clause = implode(" AND ", $where_conditions);

// Build order clause
$order_clause = "ORDER BY ";
switch ($sort_by) {
    case 'price_low':
        $order_clause .= "p.price ASC";
        break;
    case 'price_high':
        $order_clause .= "p.price DESC";
        break;
    case 'rating':
        $order_clause .= "rating DESC";
        break;
    case 'duration':
        $order_clause .= "p.duration_days ASC";
        break;
    case 'name':
        $order_clause .= "p.name ASC";
        break;
    default:
        $order_clause .= "p.featured DESC, p.name ASC";
}

// Get filtered packages
$stmt = $conn->prepare("
    SELECT p.*, 
           COALESCE(AVG(pr.rating), 0) as rating,
           COUNT(pr.id) as review_count
    FROM travel_packages p 
    LEFT JOIN package_reviews pr ON p.id = pr.package_id 
    WHERE $where_clause
    GROUP BY p.id 
    $order_clause
");
$stmt->execute($params);
$packages = $stmt->fetchAll();

// Get available destinations for filter
$destinations_stmt = $conn->query("SELECT DISTINCT destination FROM travel_packages WHERE status = 'active' ORDER BY destination");
$destinations = $destinations_stmt->fetchAll();

// Get available difficulty levels
$difficulty_levels = ['easy', 'moderate', 'challenging', 'difficult'];

// Get price range
$price_range_stmt = $conn->query("SELECT MIN(price) as min_price, MAX(price) as max_price FROM travel_packages WHERE status = 'active'");
$price_range = $price_range_stmt->fetch();

// Get duration range
$duration_stmt = $conn->query("SELECT DISTINCT duration_days FROM travel_packages WHERE status = 'active' ORDER BY duration_days");
$durations = $duration_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel Packages - Hotel Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-12 mb-4">
                <h1><i class="fas fa-map-marked-alt"></i> Travel Packages</h1>
                <p class="text-muted">Discover amazing destinations in the Eastern Himalayas</p>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-filter me-2"></i>Filter Packages
                    <button class="btn btn-sm btn-outline-secondary float-end" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </h5>
            </div>
            <div class="collapse show" id="filterCollapse">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" class="form-control" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Search packages...">
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Destination</label>
                            <select class="form-select" name="destination">
                                <option value="">All Destinations</option>
                                <?php foreach ($destinations as $dest): ?>
                                    <option value="<?php echo htmlspecialchars($dest['destination']); ?>" 
                                            <?php echo $destination === $dest['destination'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dest['destination']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Duration</label>
                            <select class="form-select" name="duration">
                                <option value="">Any Duration</option>
                                <?php foreach ($durations as $dur): ?>
                                    <option value="<?php echo $dur['duration_days']; ?>" 
                                            <?php echo $duration == $dur['duration_days'] ? 'selected' : ''; ?>>
                                        <?php echo $dur['duration_days']; ?> Day<?php echo $dur['duration_days'] > 1 ? 's' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Difficulty</label>
                            <select class="form-select" name="difficulty">
                                <option value="">Any Level</option>
                                <?php foreach ($difficulty_levels as $level): ?>
                                    <option value="<?php echo $level; ?>" 
                                            <?php echo $difficulty === $level ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($level); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-1">
                            <label class="form-label">Min Price</label>
                            <input type="number" class="form-control" name="price_min" 
                                   value="<?php echo htmlspecialchars($price_min); ?>" 
                                   min="<?php echo $price_range['min_price']; ?>" 
                                   placeholder="Min">
                        </div>
                        
                        <div class="col-md-1">
                            <label class="form-label">Max Price</label>
                            <input type="number" class="form-control" name="price_max" 
                                   value="<?php echo htmlspecialchars($price_max); ?>" 
                                   max="<?php echo $price_range['max_price']; ?>" 
                                   placeholder="Max">
                        </div>
                        
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sort and Results Info -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <span class="text-muted">
                    Showing <?php echo count($packages); ?> package<?php echo count($packages) !== 1 ? 's' : ''; ?>
                    <?php if ($search || $price_min || $price_max || $duration || $difficulty || $destination): ?>
                        (filtered)
                    <?php endif; ?>
                </span>
            </div>
            <div class="d-flex align-items-center">
                <label class="form-label me-2 mb-0">Sort by:</label>
                <form method="GET" class="d-inline">
                    <!-- Preserve current filters -->
                    <?php if ($search): ?><input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>"><?php endif; ?>
                    <?php if ($price_min): ?><input type="hidden" name="price_min" value="<?php echo htmlspecialchars($price_min); ?>"><?php endif; ?>
                    <?php if ($price_max): ?><input type="hidden" name="price_max" value="<?php echo htmlspecialchars($price_max); ?>"><?php endif; ?>
                    <?php if ($duration): ?><input type="hidden" name="duration" value="<?php echo htmlspecialchars($duration); ?>"><?php endif; ?>
                    <?php if ($difficulty): ?><input type="hidden" name="difficulty" value="<?php echo htmlspecialchars($difficulty); ?>"><?php endif; ?>
                    <?php if ($destination): ?><input type="hidden" name="destination" value="<?php echo htmlspecialchars($destination); ?>"><?php endif; ?>
                    
                    <select class="form-select form-select-sm" name="sort_by" onchange="this.form.submit()" style="width: auto;">
                        <option value="featured" <?php echo $sort_by === 'featured' ? 'selected' : ''; ?>>Featured First</option>
                        <option value="price_low" <?php echo $sort_by === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo $sort_by === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="rating" <?php echo $sort_by === 'rating' ? 'selected' : ''; ?>>Highest Rated</option>
                        <option value="duration" <?php echo $sort_by === 'duration' ? 'selected' : ''; ?>>Shortest Duration</option>
                        <option value="name" <?php echo $sort_by === 'name' ? 'selected' : ''; ?>>Name A-Z</option>
                    </select>
                </form>
                
                <?php if ($search || $price_min || $price_max || $duration || $difficulty || $destination): ?>
                    <a href="packages.php" class="btn btn-outline-secondary btn-sm ms-2">
                        <i class="fas fa-times"></i> Clear Filters
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <?php foreach ($packages as $package): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card room-card h-100">
                    <?php if ($package['featured']): ?>
                        <div class="badge bg-warning position-absolute" style="top: 10px; right: 10px; z-index: 1;">
                            <i class="fas fa-star"></i> Featured
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($package['image']) && file_exists($package['image'])): ?>
                        <img src="<?php echo htmlspecialchars($package['image']); ?>" 
                             class="card-img-top" alt="<?php echo htmlspecialchars($package['name']); ?>" 
                             style="height: 250px; object-fit: cover;">
                    <?php else: ?>
                        <div class="card-img-top d-flex align-items-center justify-content-center bg-light" 
                             style="height: 250px;">
                            <i class="fas fa-image fa-3x text-muted"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($package['name']); ?></h5>
                        <p class="text-muted mb-2">
                            <i class="fas fa-map-marker-alt me-1"></i>
                            <?php echo htmlspecialchars($package['destination']); ?>
                        </p>
                        <p class="card-text"><?php echo htmlspecialchars(substr($package['description'], 0, 100)) . '...'; ?></p>
                        
                        <div class="package-details mb-3">
                            <small class="text-muted">
                                <i class="fas fa-calendar"></i> <?php echo $package['duration_days']; ?>D/<?php echo $package['duration_nights']; ?>N
                                <span class="mx-2">|</span>
                                <i class="fas fa-users"></i> Max <?php echo $package['max_people']; ?> people
                                <span class="mx-2">|</span>
                                <i class="fas fa-mountain"></i> <?php echo ucfirst($package['difficulty_level']); ?>
                            </small>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <?php if ($package['original_price']): ?>
                                    <span class="text-muted text-decoration-line-through"><?php echo number_format($package['original_price'], 0); ?> Nrs.</span>
                                <?php endif; ?>
                                <span class="h5 text-primary mb-0"><?php echo number_format($package['price'], 0); ?> Nrs.</span>
                                <small class="text-muted">/person</small>
                            </div>
                            <div class="rating">
                                <?php echo generateStars($package['rating']); ?>
                                <small class="text-muted">(<?php echo $package['review_count']; ?>)</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <a href="package_details.php?id=<?php echo $package['id']; ?>" class="btn btn-primary w-100">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($packages)): ?>
        <div class="row">
            <div class="col-12 text-center py-5">
                <i class="fas fa-map-marked-alt fa-4x text-muted mb-3"></i>
                <h4>No Packages Available</h4>
                <p class="text-muted">Please check back later for exciting travel packages.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>