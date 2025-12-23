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
function handleImageUpload($file, $type = 'room') {
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

// Handle room actions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    $room_id = $_POST['room_id'] ?? 0;
    
    if ($action === 'add_room') {
        $name = trim($_POST['name'] ?? '');
        $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
        $description = trim($_POST['description'] ?? '');
        $price = $_POST['price'] ?? 0;
        $capacity = $_POST['capacity'] ?? 2;
        $size = trim($_POST['size'] ?? '');
        $bed_type = trim($_POST['bed_type'] ?? '');
        $featured = isset($_POST['featured']) ? 1 : 0;
        
        // Handle image upload
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_result = handleImageUpload($_FILES['image'], 'room');
            if (is_array($upload_result) && isset($upload_result['error'])) {
                $error = $upload_result['error'];
            } else {
                $image = $upload_result;
            }
        }
        
        if ($name && $description && $price > 0 && !$error) {
            $stmt = $conn->prepare("INSERT INTO rooms (name, category_id, description, price, capacity, size, bed_type, image, featured, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())");
            if ($stmt->execute([$name, $category_id, $description, $price, $capacity, $size, $bed_type, $image, $featured])) {
                $success = 'Room added successfully';
            } else {
                $error = 'Failed to add room';
            }
        } elseif (!$error) {
            $error = 'Please fill in all required fields';
        }
    } elseif ($action === 'edit_room' && $room_id) {
        $name = trim($_POST['name'] ?? '');
        $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
        $description = trim($_POST['description'] ?? '');
        $price = $_POST['price'] ?? 0;
        $capacity = $_POST['capacity'] ?? 2;
        $size = trim($_POST['size'] ?? '');
        $bed_type = trim($_POST['bed_type'] ?? '');
        $featured = isset($_POST['featured']) ? 1 : 0;
        
        // Get current room data
        $stmt = $conn->prepare("SELECT image FROM rooms WHERE id = ?");
        $stmt->execute([$room_id]);
        $current_room = $stmt->fetch();
        $image = $current_room['image'] ?? '';
        
        // Handle image upload if new file is provided
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_result = handleImageUpload($_FILES['image'], 'room');
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
        
        if ($name && $description && $price > 0 && !$error) {
            $stmt = $conn->prepare("UPDATE rooms SET name = ?, category_id = ?, description = ?, price = ?, capacity = ?, size = ?, bed_type = ?, image = ?, featured = ?, updated_at = NOW() WHERE id = ?");
            if ($stmt->execute([$name, $category_id, $description, $price, $capacity, $size, $bed_type, $image, $featured, $room_id])) {
                $success = 'Room updated successfully';
            } else {
                $error = 'Failed to update room';
            }
        } elseif (!$error) {
            $error = 'Please fill in all required fields';
        }
    } elseif ($action === 'delete_room' && $room_id) {
        // Check if room has any bookings
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE room_id = ?");
        $stmt->execute([$room_id]);
        $booking_count = $stmt->fetch()['count'];
        
        if ($booking_count > 0) {
            $error = 'Cannot delete room with existing bookings. Please cancel all bookings first.';
        } else {
            // Delete room features and services first
            $conn->prepare("DELETE FROM room_feature_assignments WHERE room_id = ?")->execute([$room_id]);
            $conn->prepare("DELETE FROM room_service_assignments WHERE room_id = ?")->execute([$room_id]);
            
            // Delete the room
            $stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
            if ($stmt->execute([$room_id])) {
                $success = 'Room deleted successfully';
            } else {
                $error = 'Failed to delete room';
            }
        }
    } elseif ($action === 'toggle_status' && $room_id) {
        $stmt = $conn->prepare("SELECT status FROM rooms WHERE id = ?");
        $stmt->execute([$room_id]);
        $room = $stmt->fetch();
        
        if ($room) {
            $new_status = $room['status'] === 'active' ? 'inactive' : 'active';
            $stmt = $conn->prepare("UPDATE rooms SET status = ? WHERE id = ?");
            if ($stmt->execute([$new_status, $room_id])) {
                $success = 'Room status updated successfully';
            } else {
                $error = 'Failed to update room status';
            }
        }
    } elseif ($action === 'toggle_featured' && $room_id) {
        $stmt = $conn->prepare("SELECT featured FROM rooms WHERE id = ?");
        $stmt->execute([$room_id]);
        $room = $stmt->fetch();
        
        if ($room) {
            $new_featured = $room['featured'] ? 0 : 1;
            $stmt = $conn->prepare("UPDATE rooms SET featured = ? WHERE id = ?");
            if ($stmt->execute([$new_featured, $room_id])) {
                $success = 'Room featured status updated successfully';
            } else {
                $error = 'Failed to update room featured status';
            }
        }
    } elseif ($action === 'update_price' && $room_id) {
        $new_price = $_POST['price'] ?? 0;
        if ($new_price > 0) {
            $stmt = $conn->prepare("UPDATE rooms SET price = ?, updated_at = NOW() WHERE id = ?");
            if ($stmt->execute([$new_price, $room_id])) {
                $success = 'Room price updated successfully';
            } else {
                $error = 'Failed to update room price';
            }
        } else {
            $error = 'Invalid price amount';
        }
    }
}

// Fetch room categories
$stmt = $conn->query("SELECT * FROM room_categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Get filters
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($filter !== 'all') {
    $where_conditions[] = "r.status = ?";
    $params[] = $filter;
}

if ($search) {
    $where_conditions[] = "(r.name LIKE ? OR r.description LIKE ? OR rc.name LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$stmt = $conn->prepare("
    SELECT r.*, rc.name as category_name,
           COALESCE(AVG(rv.rating), 0) as avg_rating,
           COUNT(rv.id) as review_count,
           COUNT(DISTINCT b.id) as booking_count
    FROM rooms r 
    LEFT JOIN room_categories rc ON r.category_id = rc.id
    LEFT JOIN reviews rv ON r.id = rv.room_id 
    LEFT JOIN bookings b ON r.id = b.room_id
    $where_clause
    GROUP BY r.id 
    ORDER BY r.featured DESC, r.name ASC
");
$stmt->execute($params);
$rooms = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rooms - Admin Panel</title>
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
                                <i class="fas fa-bed text-primary me-3"></i>
                                Rooms Management
                            </h1>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                                    <li class="breadcrumb-item active">Rooms</li>
                                </ol>
                            </nav>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                                <i class="fas fa-plus me-2"></i>Add New Room
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
                                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Rooms</option>
                                    <option value="active" <?php echo $filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="maintenance" <?php echo $filter === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                </select>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label">Search</label>
                                <input type="text" class="form-control" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Search by room name, description, or category...">
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

                <!-- Rooms -->
                <div class="row">
                    <?php if (!empty($rooms)): ?>
                        <?php foreach ($rooms as $room): ?>
                        <div class="col-lg-6 mb-4">
                            <div class="card h-100">
                                <?php if ($room['featured']): ?>
                                    <div class="badge bg-warning position-absolute" style="top: 10px; right: 10px; z-index: 1;">
                                        <i class="fas fa-star"></i> Featured
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($room['image']) && file_exists('../' . $room['image'])): ?>
                                    <img src="../<?php echo htmlspecialchars($room['image']); ?>" 
                                         class="card-img-top" alt="<?php echo htmlspecialchars($room['name']); ?>" 
                                         style="height: 200px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="card-img-top d-flex align-items-center justify-content-center bg-light" 
                                         style="height: 200px;">
                                        <i class="fas fa-image fa-3x text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title mb-0"><?php echo htmlspecialchars($room['name']); ?></h5>
                                        <span class="badge bg-<?php 
                                            echo $room['status'] === 'active' ? 'success' : 
                                                ($room['status'] === 'inactive' ? 'danger' : 'warning'); 
                                        ?>">
                                            <?php echo ucfirst($room['status']); ?>
                                        </span>
                                    </div>
                                    
                                    <p class="text-muted mb-2">
                                        <strong>Category:</strong> <?php echo htmlspecialchars($room['category_name'] ?? 'N/A'); ?>
                                    </p>
                                    
                                    <p class="card-text"><?php echo htmlspecialchars(substr($room['description'], 0, 100)) . '...'; ?></p>
                                    
                                    <div class="row text-center mb-3">
                                        <div class="col-4">
                                            <small class="text-muted">Price</small>
                                            <br><strong><?php echo number_format($room['price'], 0); ?> Nrs.</strong>
                                        </div>
                                        <div class="col-4">
                                            <small class="text-muted">Rating</small>
                                            <br><strong><?php echo number_format($room['avg_rating'], 1); ?>/5</strong>
                                            <br><small>(<?php echo $room['review_count']; ?> reviews)</small>
                                        </div>
                                        <div class="col-4">
                                            <small class="text-muted">Bookings</small>
                                            <br><strong><?php echo $room['booking_count']; ?></strong>
                                        </div>
                                    </div>
                                    
                                    <div class="room-details">
                                        <small class="text-muted">
                                            <i class="fas fa-users"></i> <?php echo $room['capacity']; ?> guests
                                            <span class="mx-2">|</span>
                                            <i class="fas fa-expand-arrows-alt"></i> <?php echo htmlspecialchars($room['size']); ?>
                                            <span class="mx-2">|</span>
                                            <i class="fas fa-bed"></i> <?php echo htmlspecialchars($room['bed_type']); ?>
                                        </small>
                                    </div>
                                </div>
                                
                                <div class="card-footer">
                                    <div class="btn-group btn-group-sm w-100 mb-2">
                                        <button class="btn btn-outline-info" onclick="editRoom(<?php echo $room['id']; ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form method="POST" class="flex-fill ms-1">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                            <button type="submit" class="btn btn-outline-<?php echo $room['status'] === 'active' ? 'warning' : 'success'; ?> w-100">
                                                <i class="fas fa-<?php echo $room['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
                                                <?php echo $room['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                            </button>
                                        </form>
                                        <form method="POST" class="flex-fill ms-1">
                                            <input type="hidden" name="action" value="toggle_featured">
                                            <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                            <button type="submit" class="btn btn-outline-<?php echo $room['featured'] ? 'warning' : 'primary'; ?> w-100">
                                                <i class="fas fa-star"></i>
                                                <?php echo $room['featured'] ? 'Unfeature' : 'Feature'; ?>
                                            </button>
                                        </form>
                                    </div>
                                    
                                    <div class="btn-group btn-group-sm w-100">
                                        <button class="btn btn-outline-secondary" 
                                                onclick="updatePrice(<?php echo $room['id']; ?>, <?php echo $room['price']; ?>)">
                                            <i class="fas fa-dollar-sign"></i> Update Price
                                        </button>
                                        <form method="POST" class="flex-fill ms-1" 
                                              onsubmit="return confirm('Are you sure you want to delete this room? This action cannot be undone.')">
                                            <input type="hidden" name="action" value="delete_room">
                                            <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
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
                                <i class="fas fa-bed fa-4x text-muted mb-3"></i>
                                <h5>No Rooms Found</h5>
                                <p class="text-muted">
                                    <?php if ($filter !== 'all' || $search): ?>
                                        No rooms match your current filters.
                                        <br><a href="rooms.php" class="btn btn-outline-primary mt-2">View All Rooms</a>
                                    <?php else: ?>
                                        No rooms have been added yet.
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Room Modal -->
    <div class="modal fade" id="addRoomModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Add New Room
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_room">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Room Name *</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Price per Night *</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control" name="price" step="1" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Capacity</label>
                                <select class="form-select" name="capacity">
                                    <option value="1">1 Guest</option>
                                    <option value="2" selected>2 Guests</option>
                                    <option value="3">3 Guests</option>
                                    <option value="4">4 Guests</option>
                                    <option value="5">5 Guests</option>
                                    <option value="6">6 Guests</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Room Size</label>
                                <input type="text" class="form-control" name="size" placeholder="e.g., 25 sqm">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Bed Type</label>
                                <select class="form-select" name="bed_type">
                                    <option value="">Select Bed Type</option>
                                    <option value="Single Bed">Single Bed</option>
                                    <option value="Twin Beds">Twin Beds</option>
                                    <option value="Queen Bed">Queen Bed</option>
                                    <option value="King Bed">King Bed</option>
                                    <option value="2 Queen Beds">2 Queen Beds</option>
                                    <option value="2 Twin Beds">2 Twin Beds</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Room Image</label>
                                <input type="file" class="form-control" name="image" 
                                       accept="image/*" required>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Upload JPG, PNG, GIF, or WebP images (max 5MB)
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description *</label>
                            <textarea class="form-control" name="description" rows="4" required 
                                      placeholder="Describe the room features and amenities..."></textarea>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="featured" id="addFeatured">
                            <label class="form-check-label" for="addFeatured">
                                <i class="fas fa-star text-warning me-1"></i>Featured Room
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Add Room
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Room Modal -->
    <div class="modal fade" id="editRoomModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Edit Room
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editRoomForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_room">
                        <input type="hidden" name="room_id" id="editRoomId">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Room Name *</label>
                                <input type="text" class="form-control" name="name" id="editName" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category_id" id="editCategoryId">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Price per Night *</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control" name="price" id="editPrice" step="1" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Capacity</label>
                                <select class="form-select" name="capacity" id="editCapacity">
                                    <option value="1">1 Guest</option>
                                    <option value="2">2 Guests</option>
                                    <option value="3">3 Guests</option>
                                    <option value="4">4 Guests</option>
                                    <option value="5">5 Guests</option>
                                    <option value="6">6 Guests</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Room Size</label>
                                <input type="text" class="form-control" name="size" id="editSize" placeholder="e.g., 25 sqm">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Bed Type</label>
                                <select class="form-select" name="bed_type" id="editBedType">
                                    <option value="">Select Bed Type</option>
                                    <option value="Single Bed">Single Bed</option>
                                    <option value="Twin Beds">Twin Beds</option>
                                    <option value="Queen Bed">Queen Bed</option>
                                    <option value="King Bed">King Bed</option>
                                    <option value="2 Queen Beds">2 Queen Beds</option>
                                    <option value="2 Twin Beds">2 Twin Beds</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Room Image</label>
                                <input type="file" class="form-control" name="image" id="editImage" 
                                       accept="image/*">
                                <div id="currentImageInfo" class="mt-1"></div>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Upload JPG, PNG, GIF, or WebP images (max 5MB). Leave empty to keep current image.
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description *</label>
                            <textarea class="form-control" name="description" id="editDescription" rows="4" required 
                                      placeholder="Describe the room features and amenities..."></textarea>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="featured" id="editFeatured">
                            <label class="form-check-label" for="editFeatured">
                                <i class="fas fa-star text-warning me-1"></i>Featured Room
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Room
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Price Update Modal -->
    <div class="modal fade" id="priceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Room Price</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_price">
                        <input type="hidden" name="room_id" id="modalRoomId">
                        
                        <div class="mb-3">
                            <label class="form-label">New Price (per night)</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" class="form-control" name="price" id="modalPrice" 
                                       step="1" min="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Price</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updatePrice(roomId, currentPrice) {
            document.getElementById('modalRoomId').value = roomId;
            document.getElementById('modalPrice').value = currentPrice;
            
            new bootstrap.Modal(document.getElementById('priceModal')).show();
        }
        
        function editRoom(roomId) {
            // Fetch room data via AJAX
            fetch(`ajax/get_room.php?id=${roomId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const room = data.room;
                        document.getElementById('editRoomId').value = room.id;
                        document.getElementById('editName').value = room.name;
                        document.getElementById('editCategoryId').value = room.category_id || '';
                        document.getElementById('editPrice').value = room.price;
                        document.getElementById('editCapacity').value = room.capacity;
                        document.getElementById('editSize').value = room.size || '';
                        document.getElementById('editBedType').value = room.bed_type || '';
                        // Note: Cannot set file input value for security reasons
                        // Show current image info and preview instead
                        const currentImageInfo = document.getElementById('currentImageInfo');
                        if (currentImageInfo) {
                            if (room.image) {
                                const filename = room.image.split('/').pop();
                                currentImageInfo.innerHTML = `
                                    <div class="d-flex align-items-center">
                                        <img src="../${room.image}" alt="Current image" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;" class="me-2">
                                        <small class="text-muted">Current: ${filename}</small>
                                    </div>
                                `;
                            } else {
                                currentImageInfo.innerHTML = '<small class="text-muted">No image uploaded</small>';
                            }
                        }
                        document.getElementById('editDescription').value = room.description;
                        document.getElementById('editFeatured').checked = room.featured == 1;
                        
                        new bootstrap.Modal(document.getElementById('editRoomModal')).show();
                    } else {
                        alert('Failed to load room data');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while loading room data');
                });
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