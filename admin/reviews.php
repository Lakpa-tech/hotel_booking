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

// Handle review actions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    $review_id = $_POST['review_id'] ?? 0;
    
    if ($action === 'toggle_status' && $review_id) {
        $stmt = $conn->prepare("SELECT status FROM reviews WHERE id = ?");
        $stmt->execute([$review_id]);
        $review = $stmt->fetch();
        
        if ($review) {
            $new_status = $review['status'] === 'active' ? 'hidden' : 'active';
            $stmt = $conn->prepare("UPDATE reviews SET status = ? WHERE id = ?");
            if ($stmt->execute([$new_status, $review_id])) {
                $success = 'Review status updated successfully';
            } else {
                $error = 'Failed to update review status';
            }
        }
    } elseif ($action === 'delete' && $review_id) {
        $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
        if ($stmt->execute([$review_id])) {
            $success = 'Review deleted successfully';
        } else {
            $error = 'Failed to delete review';
        }
    }
}

// Get filters
$filter = $_GET['filter'] ?? 'all';
$rating_filter = $_GET['rating'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($filter !== 'all') {
    $where_conditions[] = "r.status = ?";
    $params[] = $filter;
}

if ($rating_filter !== 'all') {
    $where_conditions[] = "r.rating = ?";
    $params[] = $rating_filter;
}

if ($search) {
    $where_conditions[] = "(u.full_name LIKE ? OR rm.name LIKE ? OR r.comment LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$stmt = $conn->prepare("
    SELECT r.*, u.full_name as user_name, rm.name as room_name, rm.image as room_image
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    JOIN rooms rm ON r.room_id = rm.id 
    $where_clause
    ORDER BY r.created_at DESC
");
$stmt->execute($params);
$reviews = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">

</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-star"></i> Reviews Management</h1>
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
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="filter">
                                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Reviews</option>
                                    <option value="active" <?php echo $filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="hidden" <?php echo $filter === 'hidden' ? 'selected' : ''; ?>>Hidden</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Rating</label>
                                <select class="form-select" name="rating">
                                    <option value="all" <?php echo $rating_filter === 'all' ? 'selected' : ''; ?>>All Ratings</option>
                                    <option value="5" <?php echo $rating_filter === '5' ? 'selected' : ''; ?>>5 Stars</option>
                                    <option value="4" <?php echo $rating_filter === '4' ? 'selected' : ''; ?>>4 Stars</option>
                                    <option value="3" <?php echo $rating_filter === '3' ? 'selected' : ''; ?>>3 Stars</option>
                                    <option value="2" <?php echo $rating_filter === '2' ? 'selected' : ''; ?>>2 Stars</option>
                                    <option value="1" <?php echo $rating_filter === '1' ? 'selected' : ''; ?>>1 Star</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Search</label>
                                <input type="text" class="form-control" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Search by guest name, room name, or comment...">
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

                <!-- Reviews -->
                <div class="row">
                    <?php if (!empty($reviews)): ?>
                        <?php foreach ($reviews as $review): ?>
                        <div class="col-lg-6 mb-4">
                            <div class="card h-100 <?php echo $review['status'] === 'hidden' ? 'border-danger' : ''; ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle me-3">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($review['user_name']); ?></h6>
                                                <small class="text-muted"><?php echo formatDate($review['created_at']); ?></small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div class="rating mb-1">
                                                <?php echo generateStars($review['rating']); ?>
                                            </div>
                                            <span class="badge bg-<?php echo $review['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($review['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>Room:</strong> <?php echo htmlspecialchars($review['room_name']); ?>
                                        <br><strong>Booking ID:</strong> <?php echo htmlspecialchars($review['booking_id']); ?>
                                    </div>
                                    
                                    <?php if ($review['comment']): ?>
                                        <div class="mb-3">
                                            <strong>Comment:</strong>
                                            <p class="mt-2 mb-0"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <div class="btn-group btn-group-sm w-100">
                                        <form method="POST" class="flex-fill" 
                                              onsubmit="return confirm('Are you sure you want to <?php echo $review['status'] === 'active' ? 'hide' : 'show'; ?> this review?')">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                            <button type="submit" class="btn btn-outline-<?php echo $review['status'] === 'active' ? 'warning' : 'success'; ?> w-100">
                                                <i class="fas fa-<?php echo $review['status'] === 'active' ? 'eye-slash' : 'eye'; ?>"></i>
                                                <?php echo $review['status'] === 'active' ? 'Hide' : 'Show'; ?>
                                            </button>
                                        </form>
                                        <form method="POST" class="flex-fill ms-2" 
                                              onsubmit="return confirm('Are you sure you want to delete this review? This action cannot be undone.')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
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
                                <i class="fas fa-star fa-4x text-muted mb-3"></i>
                                <h5>No Reviews Found</h5>
                                <p class="text-muted">
                                    <?php if ($filter !== 'all' || $rating_filter !== 'all' || $search): ?>
                                        No reviews match your current filters.
                                        <br><a href="reviews.php" class="btn btn-outline-primary mt-2">View All Reviews</a>
                                    <?php else: ?>
                                        No reviews have been submitted yet.
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
        .avatar-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #007bff;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .rating i {
            font-size: 0.9rem;
        }
    </style>
</body>
</html>