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

// Handle booking actions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    $booking_id = $_POST['booking_id'] ?? '';
    
    if ($action === 'update_status' && $booking_id) {
        $new_status = $_POST['status'] ?? '';
        $valid_statuses = ['confirmed', 'checked_in', 'checked_out', 'completed', 'cancelled'];
        
        if (in_array($new_status, $valid_statuses)) {
            $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE booking_id = ?");
            if ($stmt->execute([$new_status, $booking_id])) {
                $success = 'Booking status updated successfully';
            } else {
                $error = 'Failed to update booking status';
            }
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
    $where_conditions[] = "b.status = ?";
    $params[] = $filter;
}

if ($search) {
    $where_conditions[] = "(b.booking_id LIKE ? OR u.full_name LIKE ? OR u.email LIKE ? OR r.name LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$stmt = $conn->prepare("
    SELECT b.*, r.name as room_name, r.image as room_image, 
           u.full_name as user_name, u.email as user_email, u.phone as user_phone
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.id 
    JOIN users u ON b.user_id = u.id 
    $where_clause
    ORDER BY b.created_at DESC
");
$stmt->execute($params);
$bookings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings - Admin Panel</title>
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
                    <h1 class="h2"><i class="fas fa-calendar-alt"></i> Bookings Management</h1>
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
                                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Bookings</option>
                                    <option value="confirmed" <?php echo $filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="checked_in" <?php echo $filter === 'checked_in' ? 'selected' : ''; ?>>Checked In</option>
                                    <option value="checked_out" <?php echo $filter === 'checked_out' ? 'selected' : ''; ?>>Checked Out</option>
                                    <option value="completed" <?php echo $filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo $filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label">Search</label>
                                <input type="text" class="form-control" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Search by booking ID, guest name, email, or room...">
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

                <!-- Bookings -->
                <div class="card">
                    <div class="card-body">
                        <?php if (!empty($bookings)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Booking ID</th>
                                            <th>Guest</th>
                                            <th>Room</th>
                                            <th>Dates</th>
                                            <th>Guests</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($bookings as $booking): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($booking['booking_id']); ?></strong>
                                                <br><small class="text-muted">
                                                    <?php echo formatDate($booking['created_at']); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($booking['user_name']); ?></strong>
                                                <br><small class="text-muted">
                                                    <?php echo htmlspecialchars($booking['user_email']); ?>
                                                </small>
                                            </td>
                                            <td><?php echo htmlspecialchars($booking['room_name']); ?></td>
                                            <td>
                                                <strong>In:</strong> <?php echo formatDate($booking['checkin_date']); ?>
                                                <br><strong>Out:</strong> <?php echo formatDate($booking['checkout_date']); ?>
                                                <br><small class="text-muted"><?php echo $booking['nights']; ?> nights</small>
                                            </td>
                                            <td><?php echo $booking['guests']; ?></td>
                                            <td>
                                                <strong><?php echo number_format($booking['total_amount'], 0); ?> Nrs.</strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $booking['status'] === 'confirmed' ? 'success' : 
                                                        ($booking['status'] === 'cancelled' ? 'danger' : 
                                                        ($booking['status'] === 'completed' ? 'primary' : 'info')); 
                                                ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $booking['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" 
                                                            onclick="viewBooking('<?php echo $booking['booking_id']; ?>')">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-outline-success" 
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
                                <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                                <h5>No Bookings Found</h5>
                                <p class="text-muted">
                                    <?php if ($filter !== 'all' || $search): ?>
                                        No bookings match your current filters.
                                        <br><a href="bookings.php" class="btn btn-outline-primary mt-2">View All Bookings</a>
                                    <?php else: ?>
                                        No bookings have been made yet.
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
                    <h5 class="modal-title">Update Booking Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="booking_id" id="modalBookingId">
                        
                        <div class="mb-3">
                            <label class="form-label">Booking ID</label>
                            <input type="text" class="form-control" id="modalBookingIdDisplay" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">New Status</label>
                            <select class="form-select" name="status" id="modalStatus" required>
                                <option value="confirmed">Confirmed</option>
                                <option value="checked_in">Checked In</option>
                                <option value="checked_out">Checked Out</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateStatus(bookingId, currentStatus) {
            document.getElementById('modalBookingId').value = bookingId;
            document.getElementById('modalBookingIdDisplay').value = bookingId;
            document.getElementById('modalStatus').value = currentStatus;
            
            new bootstrap.Modal(document.getElementById('statusModal')).show();
        }
        
        function viewBooking(bookingId) {
            // You can implement a detailed view modal here
            alert('View booking details for: ' + bookingId);
        }
    </script>
</body>
</html>