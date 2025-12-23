<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Get filter parameters
$price_min = $_GET['price_min'] ?? '';
$price_max = $_GET['price_max'] ?? '';
$capacity = $_GET['capacity'] ?? '';
$bed_type = $_GET['bed_type'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'featured';
$search = $_GET['search'] ?? '';

// Build query conditions
$where_conditions = ["r.status = 'active'"];
$params = [];

if ($price_min !== '') {
    $where_conditions[] = "r.price >= ?";
    $params[] = $price_min;
}

if ($price_max !== '') {
    $where_conditions[] = "r.price <= ?";
    $params[] = $price_max;
}

if ($capacity !== '') {
    $where_conditions[] = "r.capacity >= ?";
    $params[] = $capacity;
}

if ($bed_type !== '') {
    $where_conditions[] = "r.bed_type = ?";
    $params[] = $bed_type;
}

if ($search !== '') {
    $where_conditions[] = "(r.name LIKE ? OR r.description LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
}

$where_clause = implode(" AND ", $where_conditions);

// Build order clause
$order_clause = "ORDER BY ";
switch ($sort_by) {
    case 'price_low':
        $order_clause .= "r.price ASC";
        break;
    case 'price_high':
        $order_clause .= "r.price DESC";
        break;
    case 'rating':
        $order_clause .= "rating DESC";
        break;
    case 'capacity':
        $order_clause .= "r.capacity DESC";
        break;
    default:
        $order_clause .= "r.featured DESC, r.name ASC";
}

// Get filtered rooms
$stmt = $conn->prepare("
    SELECT r.*, 
           COALESCE(AVG(rv.rating), 0) as rating,
           COUNT(rv.id) as review_count
    FROM rooms r 
    LEFT JOIN reviews rv ON r.id = rv.room_id 
    WHERE $where_clause
    GROUP BY r.id 
    $order_clause
");
$stmt->execute($params);
$rooms = $stmt->fetchAll();

// Get available bed types for filter
$bed_types_stmt = $conn->query("SELECT DISTINCT bed_type FROM rooms WHERE status = 'active' AND bed_type IS NOT NULL AND bed_type != '' ORDER BY bed_type");
$bed_types = $bed_types_stmt->fetchAll();

// Get price range
$price_range_stmt = $conn->query("SELECT MIN(price) as min_price, MAX(price) as max_price FROM rooms WHERE status = 'active'");
$price_range = $price_range_stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rooms - Hotel Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-12 mb-4">
                <h1><i class="fas fa-bed"></i> Our Rooms</h1>
                <p class="text-muted">Choose from our selection of comfortable and luxurious accommodations</p>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-filter me-2"></i>Filter Rooms
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
                                   placeholder="Search rooms...">
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Min Price (₹)</label>
                            <input type="number" class="form-control" name="price_min" 
                                   value="<?php echo htmlspecialchars($price_min); ?>" 
                                   min="<?php echo $price_range['min_price']; ?>" 
                                   placeholder="Min">
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Max Price (₹)</label>
                            <input type="number" class="form-control" name="price_max" 
                                   value="<?php echo htmlspecialchars($price_max); ?>" 
                                   max="<?php echo $price_range['max_price']; ?>" 
                                   placeholder="Max">
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Min Guests</label>
                            <select class="form-select" name="capacity">
                                <option value="">Any</option>
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $capacity == $i ? 'selected' : ''; ?>>
                                        <?php echo $i; ?>+ Guest<?php echo $i > 1 ? 's' : ''; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Bed Type</label>
                            <select class="form-select" name="bed_type">
                                <option value="">Any</option>
                                <?php foreach ($bed_types as $type): ?>
                                    <option value="<?php echo htmlspecialchars($type['bed_type']); ?>" 
                                            <?php echo $bed_type === $type['bed_type'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type['bed_type']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
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
                    Showing <?php echo count($rooms); ?> room<?php echo count($rooms) !== 1 ? 's' : ''; ?>
                    <?php if ($search || $price_min || $price_max || $capacity || $bed_type): ?>
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
                    <?php if ($capacity): ?><input type="hidden" name="capacity" value="<?php echo htmlspecialchars($capacity); ?>"><?php endif; ?>
                    <?php if ($bed_type): ?><input type="hidden" name="bed_type" value="<?php echo htmlspecialchars($bed_type); ?>"><?php endif; ?>
                    
                    <select class="form-select form-select-sm" name="sort_by" onchange="this.form.submit()" style="width: auto;">
                        <option value="featured" <?php echo $sort_by === 'featured' ? 'selected' : ''; ?>>Featured First</option>
                        <option value="price_low" <?php echo $sort_by === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo $sort_by === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="rating" <?php echo $sort_by === 'rating' ? 'selected' : ''; ?>>Highest Rated</option>
                        <option value="capacity" <?php echo $sort_by === 'capacity' ? 'selected' : ''; ?>>Largest Capacity</option>
                    </select>
                </form>
                
                <?php if ($search || $price_min || $price_max || $capacity || $bed_type): ?>
                    <a href="rooms.php" class="btn btn-outline-secondary btn-sm ms-2">
                        <i class="fas fa-times"></i> Clear Filters
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
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
                        <a href="room_details.php?id=<?php echo $room['id']; ?>" class="btn btn-primary w-100">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($rooms)): ?>
        <div class="row">
            <div class="col-12 text-center py-5">
                <i class="fas fa-bed fa-4x text-muted mb-3"></i>
                <h4>No Rooms Available</h4>
                <p class="text-muted">Please check back later for available rooms.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>