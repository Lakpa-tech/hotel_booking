<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isAdmin()) {
    header('Location: login.php');
    exit();
}

// Get dashboard statistics
$stats = [];

// Total bookings
$stmt = $conn->query("SELECT COUNT(*) as count FROM bookings");
$stats['total_bookings'] = $stmt->fetch()['count'];

// Total package bookings
$stmt = $conn->query("SELECT COUNT(*) as count FROM package_bookings");
$stats['total_package_bookings'] = $stmt->fetch()['count'];

// Total users
$stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
$stats['total_users'] = $stmt->fetch()['count'];

// Total rooms
$stmt = $conn->query("SELECT COUNT(*) as count FROM rooms WHERE status = 'active'");
$stats['total_rooms'] = $stmt->fetch()['count'];

// Total packages
$stmt = $conn->query("SELECT COUNT(*) as count FROM travel_packages WHERE status = 'active'");
$stats['total_packages'] = $stmt->fetch()['count'];

// Total revenue (rooms)
$stmt = $conn->query("SELECT SUM(total_amount) as revenue FROM bookings WHERE status IN ('confirmed', 'checked_in', 'checked_out', 'completed')");
$stats['total_revenue'] = $stmt->fetch()['revenue'] ?? 0;

// Total revenue (packages)
$stmt = $conn->query("SELECT SUM(total_amount) as revenue FROM package_bookings WHERE status IN ('confirmed', 'in_progress', 'completed')");
$stats['package_revenue'] = $stmt->fetch()['revenue'] ?? 0;

// Total combined revenue
$stats['combined_revenue'] = $stats['total_revenue'] + $stats['package_revenue'];

// Recent bookings
$stmt = $conn->prepare("
    SELECT b.*, r.name as room_name, u.full_name as user_name 
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.id 
    JOIN users u ON b.user_id = u.id 
    ORDER BY b.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recent_bookings = $stmt->fetchAll();

// Pending messages
$stmt = $conn->query("SELECT COUNT(*) as count FROM contact_messages WHERE status = 'new'");
$stats['pending_messages'] = $stmt->fetch()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Hotel Booking System</title>
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
                                <i class="fas fa-tachometer-alt text-primary me-3"></i>
                                Dashboard Overview
                            </h1>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item active">Dashboard</li>
                                </ol>
                            </nav>
                        </div>
                        <div class="text-end">
                            <div class="text-muted small">Last updated</div>
                            <div class="fw-bold"><?php echo date('M d, Y H:i'); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card border-left-primary">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                                    <i class="fas fa-bed"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="stat-label">Room Bookings</div>
                                    <div class="stat-value"><?php echo number_format($stats['total_bookings']); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card border-left-info">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-info bg-opacity-10 text-info">
                                    <i class="fas fa-suitcase-rolling"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="stat-label">Package Bookings</div>
                                    <div class="stat-value"><?php echo number_format($stats['total_package_bookings']); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card border-left-warning">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="stat-label">Active Users</div>
                                    <div class="stat-value"><?php echo number_format($stats['total_users']); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card border-left-secondary">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-secondary bg-opacity-10 text-secondary">
                                    <i class="fas fa-door-open"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="stat-label">Available Rooms</div>
                                    <div class="stat-value"><?php echo number_format($stats['total_rooms']); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Stats Row -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card border-left-danger">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                                    <i class="fas fa-map-marked-alt"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="stat-label">Active Packages</div>
                                    <div class="stat-value"><?php echo number_format($stats['total_packages']); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card border-left-success">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-success bg-opacity-10 text-success">
                                    <i class="fas fa-home"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="stat-label">Room Revenue</div>
                                    <div class="stat-value"><?php echo number_format($stats['total_revenue'], 0); ?> Nrs.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card border-left-info">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-info bg-opacity-10 text-info">
                                    <i class="fas fa-luggage"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="stat-label">Package Revenue</div>
                                    <div class="stat-value"><?php echo number_format($stats['package_revenue'], 0); ?> Nrs.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card border-left-primary">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="stat-label">Total Revenue</div>
                                    <div class="stat-value"><?php echo number_format($stats['combined_revenue'], 0); ?> Nrs.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                <!-- Recent Bookings -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-list-alt me-2"></i>Recent Bookings
                        </h5>
                        <a href="bookings.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-eye me-1"></i>View All
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($recent_bookings)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Booking ID</th>
                                            <th>Guest</th>
                                            <th>Room</th>
                                            <th>Check-in</th>
                                            <th>Status</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_bookings as $booking): ?>
                                        <tr>
                                            <td>
                                                <span class="fw-bold text-primary"><?php echo htmlspecialchars($booking['booking_id']); ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-2" 
                                                         style="width: 32px; height: 32px; font-size: 0.75rem;">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                    <?php echo htmlspecialchars($booking['user_name']); ?>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($booking['room_name']); ?></td>
                                            <td><?php echo formatDate($booking['checkin_date']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $booking['status'] === 'confirmed' ? 'success' : 
                                                        ($booking['status'] === 'cancelled' ? 'danger' : 'primary'); 
                                                ?>">
                                                    <?php echo ucfirst($booking['status']); ?>
                                                </span>
                                            </td>
                                            <td class="fw-bold"><?php echo number_format($booking['total_amount'], 0); ?> Nrs.</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No Recent Bookings</h5>
                                <p class="text-muted">No bookings have been made yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions & System Info -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-bolt me-2"></i>Quick Actions
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <a href="rooms.php" class="quick-action-card text-decoration-none d-block">
                                            <i class="fas fa-bed"></i>
                                            <h6 class="fw-bold mt-2 mb-1">Manage Rooms</h6>
                                            <small class="opacity-75">Add, edit, and manage hotel rooms</small>
                                        </a>
                                    </div>
                                    <div class="col-md-6">
                                        <a href="packages.php" class="quick-action-card text-decoration-none d-block" 
                                           style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                                            <i class="fas fa-map-marked-alt"></i>
                                            <h6 class="fw-bold mt-2 mb-1">Manage Packages</h6>
                                            <small class="opacity-75">Add, edit, and manage travel packages</small>
                                        </a>
                                    </div>
                                    <div class="col-md-6">
                                        <a href="bookings.php" class="quick-action-card text-decoration-none d-block" 
                                           style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                            <i class="fas fa-calendar"></i>
                                            <h6 class="fw-bold mt-2 mb-1">Room Bookings</h6>
                                            <small class="opacity-75">Monitor and manage room reservations</small>
                                        </a>
                                    </div>
                                    <div class="col-md-6">
                                        <a href="package_bookings.php" class="quick-action-card text-decoration-none d-block" 
                                           style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
                                            <i class="fas fa-suitcase-rolling"></i>
                                            <h6 class="fw-bold mt-2 mb-1">Package Bookings</h6>
                                            <small class="opacity-75">Monitor and manage package bookings</small>
                                        </a>
                                    </div>
                                    <div class="col-md-6">
                                        <a href="users.php" class="quick-action-card text-decoration-none d-block"
                                           style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                            <i class="fas fa-users"></i>
                                            <h6 class="fw-bold mt-2 mb-1">Manage Users</h6>
                                            <small class="opacity-75">View and manage user accounts</small>
                                        </a>
                                    </div>
                                    <div class="col-md-6">
                                        <a href="messages.php" class="quick-action-card text-decoration-none d-block"
                                           style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                                            <i class="fas fa-envelope"></i>
                                            <h6 class="fw-bold mt-2 mb-1">Messages</h6>
                                            <small class="opacity-75">
                                                Handle customer inquiries
                                                <?php if ($stats['pending_messages'] > 0): ?>
                                                    <span class="badge bg-danger ms-1"><?php echo $stats['pending_messages']; ?></span>
                                                <?php endif; ?>
                                            </small>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-info-circle me-2"></i>System Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-4">
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" 
                                         style="width: 60px; height: 60px;">
                                        <i class="fas fa-user-shield fa-2x"></i>
                                    </div>
                                    <h6 class="fw-bold">Welcome back!</h6>
                                    <p class="text-muted mb-0"><?php echo htmlspecialchars($_SESSION['admin_name']); ?></p>
                                    <small class="text-muted">
                                        Role: <span class="badge bg-primary"><?php echo ucfirst($_SESSION['admin_role']); ?></span>
                                    </small>
                                </div>
                                
                                <div class="border-top pt-3">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Last Login:</span>
                                        <span class="fw-medium"><?php echo date('M d, H:i'); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-3">
                                        <span class="text-muted">Server Time:</span>
                                        <span class="fw-medium" id="serverTime"><?php echo date('H:i:s'); ?></span>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <a href="settings.php" class="btn btn-outline-primary">
                                        <i class="fas fa-cog me-2"></i>Hotel Settings
                                    </a>
                                    <a href="../index.php" class="btn btn-outline-secondary" target="_blank">
                                        <i class="fas fa-external-link-alt me-2"></i>View Website
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update server time every second
        setInterval(function() {
            const now = new Date();
            document.getElementById('serverTime').textContent = now.toLocaleTimeString();
        }, 1000);
        
        // Add smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.stat-card, .card');
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