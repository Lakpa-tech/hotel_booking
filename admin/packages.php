<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isAdmin()) {
    header('Location: login.php');
    exit();
}

$success = '';
$error = '';

// Function to handle file upload
function handleImageUpload($file, $type = 'package') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowed_types)) {
        return ['error' => 'Invalid file type. Only JPG, PNG, GIF, and WebP images are allowed.'];
    }
    
    if ($file['size'] > $max_size) {
        return ['error' => 'File size too large. Maximum size is 5MB.'];
    }
    
    $upload_dir = "../uploads/{$type}s/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $file_extension;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return "uploads/{$type}s/" . $filename;
    }
    
    return ['error' => 'Failed to upload file.'];
}

// Handle package actions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    $package_id = $_POST['package_id'] ?? 0;
    
    if ($action === 'delete_package' && $package_id) {
        // Get package image to delete
        $stmt = $conn->prepare("SELECT image FROM travel_packages WHERE id = ?");
        $stmt->execute([$package_id]);
        $package = $stmt->fetch();
        
        $stmt = $conn->prepare("DELETE FROM travel_packages WHERE id = ?");
        if ($stmt->execute([$package_id])) {
            // Delete image file if it exists
            if ($package && $package['image'] && file_exists('../' . $package['image'])) {
                unlink('../' . $package['image']);
            }
            $success = 'Package deleted successfully';
        } else {
            $error = 'Failed to delete package';
        }
    } elseif ($action === 'add_package') {
        $name = trim($_POST['name'] ?? '');
        $destination = trim($_POST['destination'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $duration_days = $_POST['duration_days'] ?? 1;
        $duration_nights = $_POST['duration_nights'] ?? 0;
        $price = $_POST['price'] ?? 0;
        $original_price = $_POST['original_price'] ?? null;
        $max_people = $_POST['max_people'] ?? 10;
        $difficulty_level = $_POST['difficulty_level'] ?? 'easy';
        $best_season = trim($_POST['best_season'] ?? '');
        $featured = isset($_POST['featured']) ? 1 : 0;
        
        // Handle image upload
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_result = handleImageUpload($_FILES['image'], 'package');
            if (is_array($upload_result) && isset($upload_result['error'])) {
                $error = $upload_result['error'];
            } else {
                $image = $upload_result;
            }
        }
        
        // Process JSON fields
        $inclusions = json_encode(array_filter(explode("\n", trim($_POST['inclusions'] ?? ''))));
        $exclusions = json_encode(array_filter(explode("\n", trim($_POST['exclusions'] ?? ''))));
        $highlights = json_encode(array_filter(explode("\n", trim($_POST['highlights'] ?? ''))));
        $itinerary = json_encode(array_filter(explode("\n", trim($_POST['itinerary'] ?? ''))));
        
        if ($name && $destination && $description && $price > 0 && !$error) {
            $stmt = $conn->prepare("INSERT INTO travel_packages (name, destination, description, duration_days, duration_nights, price, original_price, max_people, image, inclusions, exclusions, itinerary, highlights, featured, difficulty_level, best_season, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())");
            if ($stmt->execute([$name, $destination, $description, $duration_days, $duration_nights, $price, $original_price, $max_people, $image, $inclusions, $exclusions, $itinerary, $highlights, $featured, $difficulty_level, $best_season])) {
                $success = 'Package added successfully';
            } else {
                $error = 'Failed to add package';
            }
        } elseif (!$error) {
            $error = 'Please fill in all required fields';
        }
    } elseif ($action === 'edit_package' && $package_id) {
        $name = trim($_POST['name'] ?? '');
        $destination = trim($_POST['destination'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $duration_days = $_POST['duration_days'] ?? 1;
        $duration_nights = $_POST['duration_nights'] ?? 0;
        $price = $_POST['price'] ?? 0;
        $original_price = $_POST['original_price'] ?? null;
        $max_people = $_POST['max_people'] ?? 10;
        $difficulty_level = $_POST['difficulty_level'] ?? 'easy';
        $best_season = trim($_POST['best_season'] ?? '');
        $featured = isset($_POST['featured']) ? 1 : 0;
        
        // Get current package data
        $stmt = $conn->prepare("SELECT image FROM travel_packages WHERE id = ?");
        $stmt->execute([$package_id]);
        $current_package = $stmt->fetch();
        $image = $current_package['image'] ?? '';
        
        // Handle image upload if new file is provided
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_result = handleImageUpload($_FILES['image'], 'package');
            if (is_array($upload_result) && isset($upload_result['error'])) {
                $error = $upload_result['error'];
            } else {
                // Delete old image if it exists
                if ($image && file_exists('../' . $image)) {
                    unlink('../' . $image);
                }
                $image = $upload_result;
            }
        }
        
        // Process JSON fields
        $inclusions = json_encode(array_filter(explode("\n", trim($_POST['inclusions'] ?? ''))));
        $exclusions = json_encode(array_filter(explode("\n", trim($_POST['exclusions'] ?? ''))));
        $highlights = json_encode(array_filter(explode("\n", trim($_POST['highlights'] ?? ''))));
        $itinerary = json_encode(array_filter(explode("\n", trim($_POST['itinerary'] ?? ''))));
        
        if ($name && $destination && $description && $price > 0 && !$error) {
            $stmt = $conn->prepare("UPDATE travel_packages SET name = ?, destination = ?, description = ?, duration_days = ?, duration_nights = ?, price = ?, original_price = ?, max_people = ?, image = ?, inclusions = ?, exclusions = ?, itinerary = ?, highlights = ?, featured = ?, difficulty_level = ?, best_season = ?, updated_at = NOW() WHERE id = ?");
            if ($stmt->execute([$name, $destination, $description, $duration_days, $duration_nights, $price, $original_price, $max_people, $image, $inclusions, $exclusions, $itinerary, $highlights, $featured, $difficulty_level, $best_season, $package_id])) {
                $success = 'Package updated successfully';
            } else {
                $error = 'Failed to update package';
            }
        } elseif (!$error) {
            $error = 'Please fill in all required fields';
        }
    }
}

// Get filters
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($filter !== 'all') {
    $where_conditions[] = "p.status = ?";
    $params[] = $filter;
}

if ($search) {
    $where_conditions[] = "(p.name LIKE ? OR p.destination LIKE ? OR p.description LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$stmt = $conn->prepare("
    SELECT p.*,
           COALESCE(AVG(pr.rating), 0) as avg_rating,
           COUNT(pr.id) as review_count,
           COUNT(DISTINCT pb.id) as booking_count
    FROM travel_packages p 
    LEFT JOIN package_reviews pr ON p.id = pr.package_id 
    LEFT JOIN package_bookings pb ON p.id = pb.package_id
    $where_clause
    GROUP BY p.id 
    ORDER BY p.featured DESC, p.name ASC
");
$stmt->execute($params);
$packages = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel Packages - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="mb-2">
                                <i class="fas fa-map-marked-alt text-primary me-3"></i>
                                Travel Packages Management
                            </h1>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                                    <li class="breadcrumb-item active">Travel Packages</li>
                                </ol>
                            </nav>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPackageModal">
                                <i class="fas fa-plus me-2"></i>Add New Package
                            </button>
                        </div>
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

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Filter by Status</label>
                                <select class="form-select" name="filter">
                                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Packages</option>
                                    <option value="active" <?php echo $filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label">Search</label>
                                <input type="text" class="form-control" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Search by package name, destination, or description...">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Packages -->
                <div class="row">
                    <?php if (!empty($packages)): ?>
                        <?php foreach ($packages as $package): ?>
                        <div class="col-lg-6 mb-4">
                            <div class="card h-100">
                                <?php if ($package['featured']): ?>
                                    <div class="badge bg-warning position-absolute" style="top: 10px; right: 10px; z-index: 1;">
                                        <i class="fas fa-star"></i> Featured
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($package['image']) && file_exists('../' . $package['image'])): ?>
                                    <img src="../<?php echo htmlspecialchars($package['image']); ?>" 
                                         class="card-img-top" alt="<?php echo htmlspecialchars($package['name']); ?>" 
                                         style="height: 200px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="card-img-top d-flex align-items-center justify-content-center bg-light" 
                                         style="height: 200px;">
                                        <i class="fas fa-image fa-3x text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title mb-0"><?php echo htmlspecialchars($package['name']); ?></h5>
                                        <span class="badge bg-<?php echo $package['status'] === 'active' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($package['status']); ?>
                                        </span>
                                    </div>
                                    
                                    <p class="text-muted mb-2">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <strong><?php echo htmlspecialchars($package['destination']); ?></strong>
                                    </p>
                                    
                                    <p class="card-text"><?php echo htmlspecialchars(substr($package['description'], 0, 100)) . '...'; ?></p>
                                    
                                    <div class="row text-center mb-3">
                                        <div class="col-4">
                                            <small class="text-muted">Duration</small>
                                            <br><strong><?php echo $package['duration_days']; ?>D/<?php echo $package['duration_nights']; ?>N</strong>
                                        </div>
                                        <div class="col-4">
                                            <small class="text-muted">Price</small>
                                            <br><strong><?php echo number_format($package['price'], 0); ?> Nrs.</strong>
                                        </div>
                                        <div class="col-4">
                                            <small class="text-muted">Max People</small>
                                            <br><strong><?php echo $package['max_people']; ?></strong>
                                        </div>
                                    </div>
                                    
                                    <div class="package-details">
                                        <small class="text-muted">
                                            <i class="fas fa-star text-warning"></i> <?php echo number_format($package['avg_rating'], 1); ?>/5
                                            <span class="mx-2">|</span>
                                            <i class="fas fa-users"></i> <?php echo $package['booking_count']; ?> bookings
                                            <span class="mx-2">|</span>
                                            <i class="fas fa-mountain"></i> <?php echo ucfirst($package['difficulty_level']); ?>
                                        </small>
                                    </div>
                                </div>
                                
                                <div class="card-footer">
                                    <div class="btn-group btn-group-sm w-100 mb-2">
                                        <button class="btn btn-outline-info" onclick="editPackage(<?php echo $package['id']; ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-outline-primary" onclick="viewPackage(<?php echo $package['id']; ?>)">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <form method="POST" class="flex-fill ms-1" 
                                              onsubmit="return confirm('Are you sure you want to delete this package?')">
                                            <input type="hidden" name="action" value="delete_package">
                                            <input type="hidden" name="package_id" value="<?php echo $package['id']; ?>">
                                            <button type="submit" class="btn btn-outline-danger w-100">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="text-center py-5">
                                <i class="fas fa-map-marked-alt fa-4x text-muted mb-3"></i>
                                <h5>No Packages Found</h5>
                                <p class="text-muted">
                                    <?php if ($filter !== 'all' || $search): ?>
                                        No packages match your current filters.
                                        <br><a href="packages.php" class="btn btn-outline-primary mt-2">View All Packages</a>
                                    <?php else: ?>
                                        No travel packages have been added yet.
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
    <!-- Add Package Modal -->
    <div class="modal fade" id="addPackageModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Add New Travel Package
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_package">
                        
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Package Name *</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Destination *</label>
                                <input type="text" class="form-control" name="destination" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Duration (Days) *</label>
                                <input type="number" class="form-control" name="duration_days" min="1" value="3" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Duration (Nights) *</label>
                                <input type="number" class="form-control" name="duration_nights" min="0" value="2" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Price (Nrs.) *</label>
                                <input type="number" class="form-control" name="price" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Original Price (Nrs.)</label>
                                <input type="number" class="form-control" name="original_price" step="0.01" min="0">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Max People</label>
                                <input type="number" class="form-control" name="max_people" min="1" value="10">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Difficulty Level</label>
                                <select class="form-select" name="difficulty_level">
                                    <option value="easy">Easy</option>
                                    <option value="moderate">Moderate</option>
                                    <option value="challenging">Challenging</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Best Season</label>
                                <input type="text" class="form-control" name="best_season" placeholder="e.g., March to November">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Package Image</label>
                            <input type="file" class="form-control" name="image" accept="image/*" required>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Upload JPG, PNG, GIF, or WebP images (max 5MB)
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description *</label>
                            <textarea class="form-control" name="description" rows="4" required 
                                      placeholder="Describe the travel package..."></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Inclusions (one per line)</label>
                                <textarea class="form-control" name="inclusions" rows="5" 
                                          placeholder="Accommodation&#10;Daily breakfast&#10;Transportation&#10;Guide services"></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Exclusions (one per line)</label>
                                <textarea class="form-control" name="exclusions" rows="5" 
                                          placeholder="Lunch and dinner&#10;Personal expenses&#10;Travel insurance"></textarea>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Highlights (one per line)</label>
                                <textarea class="form-control" name="highlights" rows="4" 
                                          placeholder="Sunrise viewpoint&#10;Cultural experiences&#10;Mountain views"></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Itinerary (one per line)</label>
                                <textarea class="form-control" name="itinerary" rows="4" 
                                          placeholder="Day 1: Arrival and sightseeing&#10;Day 2: Mountain excursion&#10;Day 3: Departure"></textarea>
                            </div>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="featured" id="addFeatured">
                            <label class="form-check-label" for="addFeatured">
                                <i class="fas fa-star text-warning me-1"></i>Featured Package
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Add Package
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Package Modal -->
    <div class="modal fade" id="editPackageModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Edit Package
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_package">
                        <input type="hidden" name="package_id" id="editPackageId">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Package Name</label>
                                <input type="text" class="form-control" name="name" id="editPackageName" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Destination</label>
                                <input type="text" class="form-control" name="destination" id="editDestination" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="editPackageDescription" rows="3" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Duration (Days)</label>
                                <input type="number" class="form-control" name="duration_days" id="editDurationDays" min="1" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Duration (Nights)</label>
                                <input type="number" class="form-control" name="duration_nights" id="editDurationNights" min="0" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Price (₹)</label>
                                <input type="number" class="form-control" name="price" id="editPrice" min="0" step="0.01" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Original Price (₹)</label>
                                <input type="number" class="form-control" name="original_price" id="editOriginalPrice" min="0" step="0.01">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Max People</label>
                                <input type="number" class="form-control" name="max_people" id="editMaxPeople" min="1" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Difficulty Level</label>
                                <select class="form-select" name="difficulty_level" id="editDifficultyLevel">
                                    <option value="easy">Easy</option>
                                    <option value="moderate">Moderate</option>
                                    <option value="challenging">Challenging</option>
                                    <option value="extreme">Extreme</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Best Season</label>
                                <input type="text" class="form-control" name="best_season" id="editBestSeason" 
                                       placeholder="e.g., March to May, October to December">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Package Image</label>
                            <input type="file" class="form-control" name="image" id="editPackageImage" accept="image/*">
                            <div id="currentPackageImageInfo" class="mt-1"></div>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Upload JPG, PNG, GIF, or WebP images (max 5MB). Leave empty to keep current image.
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Inclusions (one per line)</label>
                                <textarea class="form-control" name="inclusions" id="editInclusions" rows="4" 
                                          placeholder="Accommodation&#10;Meals&#10;Transportation&#10;Guide services"></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Exclusions (one per line)</label>
                                <textarea class="form-control" name="exclusions" id="editExclusions" rows="4" 
                                          placeholder="Personal expenses&#10;Travel insurance&#10;Tips"></textarea>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Highlights (one per line)</label>
                                <textarea class="form-control" name="highlights" id="editHighlights" rows="4" 
                                          placeholder="Sunrise viewpoint&#10;Cultural experiences&#10;Mountain views"></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Itinerary (one per line)</label>
                                <textarea class="form-control" name="itinerary" id="editItinerary" rows="4" 
                                          placeholder="Day 1: Arrival and sightseeing&#10;Day 2: Mountain excursion&#10;Day 3: Departure"></textarea>
                            </div>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="featured" id="editFeatured">
                            <label class="form-check-label" for="editFeatured">
                                <i class="fas fa-star text-warning me-1"></i>Featured Package
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Package
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editPackage(packageId) {
            // Fetch package data and populate the edit modal
            fetch(`../ajax/get_package.php?id=${packageId}`)
                .then(response => response.json())
                .then(package => {
                    if (package.error) {
                        alert('Error: ' + package.error);
                        return;
                    }
                    
                    // Populate form fields
                    document.getElementById('editPackageId').value = package.id;
                    document.getElementById('editPackageName').value = package.name;
                    document.getElementById('editDestination').value = package.destination;
                    document.getElementById('editPackageDescription').value = package.description;
                    document.getElementById('editDurationDays').value = package.duration_days;
                    document.getElementById('editDurationNights').value = package.duration_nights;
                    document.getElementById('editPrice').value = package.price;
                    document.getElementById('editOriginalPrice').value = package.original_price || '';
                    document.getElementById('editMaxPeople').value = package.max_people;
                    document.getElementById('editDifficultyLevel').value = package.difficulty_level;
                    document.getElementById('editBestSeason').value = package.best_season || '';
                    document.getElementById('editFeatured').checked = package.featured == 1;
                    
                    // Handle JSON fields
                    const inclusions = package.inclusions ? JSON.parse(package.inclusions) : [];
                    const exclusions = package.exclusions ? JSON.parse(package.exclusions) : [];
                    const highlights = package.highlights ? JSON.parse(package.highlights) : [];
                    const itinerary = package.itinerary ? JSON.parse(package.itinerary) : [];
                    
                    document.getElementById('editInclusions').value = inclusions.join('\n');
                    document.getElementById('editExclusions').value = exclusions.join('\n');
                    document.getElementById('editHighlights').value = highlights.join('\n');
                    document.getElementById('editItinerary').value = itinerary.join('\n');
                    
                    // Show current image info
                    const currentImageInfo = document.getElementById('currentPackageImageInfo');
                    if (currentImageInfo) {
                        if (package.image) {
                            const filename = package.image.split('/').pop();
                            currentImageInfo.innerHTML = `
                                <div class="d-flex align-items-center">
                                    <img src="../${package.image}" alt="Current image" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;" class="me-2">
                                    <small class="text-muted">Current: ${filename}</small>
                                </div>
                            `;
                        } else {
                            currentImageInfo.innerHTML = '<small class="text-muted">No image uploaded</small>';
                        }
                    }
                    
                    // Show the modal
                    const modal = new bootstrap.Modal(document.getElementById('editPackageModal'));
                    modal.show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load package data');
                });
        }
        
        function viewPackage(packageId) {
            window.open('../package_details.php?id=' + packageId, '_blank');
        }
        
        // Add smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>