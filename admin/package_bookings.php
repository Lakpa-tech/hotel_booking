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

// Handle status updates
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $booking_id = $_POST['booking_id'] ?? '';
    $new_status = $_POST['status'] ?? '';
    
    if ($booking_id && $new_status) {
        $stmt = $conn->prepare("UPDATE package_bookings SET status = ? WHERE booking_id = ?");
        if ($stmt->execute([$new_status, $booking_id])) {
            $success = 'Booking status updated successfully';
        } else {
            $error = 'Failed to update booking status';
        }
    }
}

// Get filters
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Update all package booking statuses based on dates
updatePackageBookingStatus($conn);

// Build query
$where_conditions = [];
$params = [];

if ($filter !== 'all') {
    $where_conditions[] = "pb.status = ?";
    $params[] = $filter;
}

if ($search) {
    $where_conditions[] = "(pb.booking_id LIKE ? OR u.full_name LIKE ? OR u.email LIKE ? OR p.name LIKE ? OR p.destination LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term, $search_term]);
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$stmt = $conn->prepare("
    SELECT pb.*, p.name as package_name, p.destination, p.image as package_image, p.duration_days, p.duration_nights,
           u.full_name as user_name, u.email as user_email, u.phone as user_phone
    FROM package_bookings pb 
    JOIN travel_packages p ON pb.package_id = p.id 
    JOIN users u ON pb.user_id = u.id
    $where_clause
    ORDER BY pb.created_at DESC
");
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Get statistics
$stats = [
    'total' => 0,
    'confirmed' => 0,
    'in_progress' => 0,
    'completed' => 0,
    'cancelled' => 0,
    'revenue' => 0
];

$stmt = $conn->query("
    SELECT status, COUNT(*) as count, SUM(total_amount) as revenue
    FROM package_bookings 
    GROUP BY status
");
while ($row = $stmt->fetch()) {
    $stats[$row['status']] = $row['count'];
    $stats['total'] += $row['count'];
    if ($row['status'] !== 'cancelled') {
        $stats['revenue'] += $row['revenue'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Package Bookings - Admin Panel</title>
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
                                <i class="fas fa-suitcase-rolling text-primary me-3"></i>
                                Package Bookings Management
                            </h1>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                                    <li class="breadcrumb-item active">Package Bookings</li>
                                </ol>
                            </nav>
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

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card stats-card">
                            <div class="card-body text-center">
                                <i class="fas fa-suitcase-rolling fa-2x text-primary mb-2"></i>
                                <h3 class="mb-1"><?php echo $stats['total']; ?></h3>
                                <p class="text-muted mb-0">Total Bookings</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card stats-card">
                            <div class="card-body text-center">
                                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                <h3 class="mb-1"><?php echo $stats['confirmed']; ?></h3>
                                <p class="text-muted mb-0">Confirmed</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card stats-card">
                            <div class="card-body text-center">
                                <i class="fas fa-plane fa-2x text-info mb-2"></i>
                                <h3 class="mb-1"><?php echo $stats['in_progress']; ?></h3>
                                <p class="text-muted mb-0">In Progress</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card stats-card">
                            <div class="card-body text-center">
                                <i class="fas fa-flag-checkered fa-2x text-primary mb-2"></i>
                                <h3 class="mb-1"><?php echo $stats['completed']; ?></h3>
                                <p class="text-muted mb-0">Completed</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card stats-card">
                            <div class="card-body text-center">
                                <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                                <h3 class="mb-1"><?php echo $stats['cancelled']; ?></h3>
                                <p class="text-muted mb-0">Cancelled</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card stats-card">
                            <div class="card-body text-center">
                                <i class="fas fa-rupee-sign fa-2x text-warning mb-2"></i>
                                <h3 class="mb-1"><?php echo number_format($stats['revenue'], 0); ?> Nrs.</h3>
                                <p class="text-muted mb-0">Revenue</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Filter by Status</label>
                                <select class="form-select" name="filter">
                                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Bookings</option>
                                    <option value="confirmed" <?php echo $filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="in_progress" <?php echo $filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="completed" <?php echo $filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo $filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label">Search</label>
                                <input type="text" class="form-control" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Search by booking ID, customer name, email, or package...">
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

                <!-- Bookings Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Package Bookings</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($bookings)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Booking Details</th>
                                            <th>Customer</th>
                                            <th>Package</th>
                                            <th>Travel Dates</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($bookings as $booking): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($booking['booking_id']); ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        <i class="fas fa-clock"></i> 
                                                        <?php echo formatDate($booking['created_at']); ?>
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($booking['user_name']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($booking['user_email']); ?></small>
                                                    <br>
                                                    <small class="text-muted">
                                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($booking['contact_phone']); ?>
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="../<?php echo htmlspecialchars($booking['package_image']); ?>" 
                                                         class="rounded me-2" alt="Package" 
                                                         style="width: 50px; height: 50px; object-fit: cover;">
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($booking['package_name']); ?></strong>
                                                        <br>
                                                        <small class="text-muted">
                                                            <i class="fas fa-map-marker-alt"></i> 
                                                            <?php echo htmlspecialchars($booking['destination']); ?>
                                                        </small>
                                                        <br>
                                                        <small class="text-muted">
                                                            <i class="fas fa-users"></i> 
                                                            <?php echo $booking['travelers']; ?> travelers
                                                        </small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong>Travel:</strong> <?php echo formatDate($booking['travel_date']); ?>
                                                    <br>
                                                    <strong>Return:</strong> <?php echo formatDate($booking['return_date']); ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php echo $booking['duration_days']; ?>D/<?php echo $booking['duration_nights']; ?>N
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                <strong class="text-primary"><?php echo number_format($booking['total_amount'], 0); ?> Nrs.</strong>
                                            </td>
                                            <td>
                                                <?php
                                                $status_class = '';
                                                $status_icon = '';
                                                $status_text = '';
                                                $today = date('Y-m-d');
                                                $travel_date = $booking['travel_date'];
                                                $return_date = $booking['return_date'];
                                                
                                                switch ($booking['status']) {
                                                    case 'confirmed':
                                                        $status_class = 'bg-success';
                                                        $status_icon = 'fas fa-check-circle';
                                                        $days_to_travel = (strtotime($travel_date) - strtotime($today)) / (60 * 60 * 24);
                                                        if ($days_to_travel > 0) {
                                                            $status_text = 'Confirmed (' . ceil($days_to_travel) . ' days to go)';
                                                        } else {
                                                            $status_text = 'Confirmed (Travel starts today!)';
                                                        }
                                                        break;
                                                    case 'in_progress':
                                                        $status_class = 'bg-info';
                                                        $status_icon = 'fas fa-plane';
                                                        $days_remaining = (strtotime($return_date) - strtotime($today)) / (60 * 60 * 24);
                                                        if ($days_remaining > 0) {
                                                            $status_text = 'In Progress (' . ceil($days_remaining) . ' days left)';
                                                        } else {
                                                            $status_text = 'In Progress (Ends today!)';
                                                        }
                                                        break;
                                                    case 'completed':
                                                        $status_class = 'bg-primary';
                                                        $status_icon = 'fas fa-flag-checkered';
                                                        $days_completed = (strtotime($today) - strtotime($return_date)) / (60 * 60 * 24);
                                                        $status_text = 'Completed (' . floor($days_completed) . ' days ago)';
                                                        break;
                                                    case 'cancelled':
                                                        $status_class = 'bg-danger';
                                                        $status_icon = 'fas fa-times-circle';
                                                        $status_text = 'Cancelled';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>" data-travel-date="<?php echo $travel_date; ?>" data-return-date="<?php echo $return_date; ?>" data-status="<?php echo $booking['status']; ?>">
                                                    <i class="<?php echo $status_icon; ?>"></i> 
                                                    <span class="status-text"><?php echo $status_text; ?></span>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-info" 
                                                            onclick="viewBooking('<?php echo $booking['booking_id']; ?>')">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-outline-primary" 
                                                            onclick="updateStatus('<?php echo $booking['booking_id']; ?>', '<?php echo $booking['status']; ?>')">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-suitcase-rolling fa-4x text-muted mb-3"></i>
                                <h5>No Package Bookings Found</h5>
                                <p class="text-muted">
                                    <?php if ($filter !== 'all' || $search): ?>
                                        No bookings match your current filters.
                                        <br><a href="package_bookings.php" class="btn btn-outline-primary mt-2">View All Bookings</a>
                                    <?php else: ?>
                                        No package bookings have been made yet.
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Update Booking Status
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="booking_id" id="statusBookingId">
                        
                        <div class="mb-3">
                            <label class="form-label">Booking ID</label>
                            <input type="text" class="form-control" id="statusBookingIdDisplay" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="statusSelect" required>
                                <option value="confirmed">Confirmed</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewBooking(bookingId) {
            window.open('../package_booking_details.php?id=' + bookingId, '_blank');
        }
        
        function updateStatus(bookingId, currentStatus) {
            document.getElementById('statusBookingId').value = bookingId;
            document.getElementById('statusBookingIdDisplay').value = bookingId;
            document.getElementById('statusSelect').value = currentStatus;
            
            new bootstrap.Modal(document.getElementById('statusModal')).show();
        }
        
        // Real-time status updates
        function updateRealTimeStatus() {
            const statusBadges = document.querySelectorAll('.badge[data-travel-date]');
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            statusBadges.forEach(badge => {
                const travelDate = new Date(badge.dataset.travelDate);
                const returnDate = new Date(badge.dataset.returnDate);
                const currentStatus = badge.dataset.status;
                const statusText = badge.querySelector('.status-text');
                
                travelDate.setHours(0, 0, 0, 0);
                returnDate.setHours(0, 0, 0, 0);
                
                if (currentStatus === 'confirmed') {
                    const daysToTravel = Math.ceil((travelDate - today) / (1000 * 60 * 60 * 24));
                    if (daysToTravel > 0) {
                        statusText.textContent = `Confirmed (${daysToTravel} days to go)`;
                    } else if (daysToTravel === 0) {
                        statusText.textContent = 'Confirmed (Travel starts today!)';
                    } else {
                        statusText.textContent = 'Should be In Progress';
                        badge.style.opacity = '0.7';
                    }
                } else if (currentStatus === 'in_progress') {
                    const daysRemaining = Math.ceil((returnDate - today) / (1000 * 60 * 60 * 24));
                    if (daysRemaining > 0) {
                        statusText.textContent = `In Progress (${daysRemaining} days left)`;
                    } else if (daysRemaining === 0) {
                        statusText.textContent = 'In Progress (Ends today!)';
                    } else {
                        statusText.textContent = 'Should be Completed';
                        badge.style.opacity = '0.7';
                    }
                } else if (currentStatus === 'completed') {
                    const daysCompleted = Math.floor((today - returnDate) / (1000 * 60 * 60 * 24));
                    statusText.textContent = `Completed (${daysCompleted} days ago)`;
                }
            });
        }
        
        // Update every minute
        setInterval(updateRealTimeStatus, 60000);
        
        // Add smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            updateRealTimeStatus();
            
            const cards = document.querySelectorAll('.stats-card');
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