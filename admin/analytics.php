<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isAdmin()) {
    header('Location: login.php');
    exit();
}

// Get date range from query parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Total Revenue (All time)
$stmt = $conn->query("SELECT SUM(total_amount) as revenue FROM bookings WHERE status IN ('confirmed', 'checked_in', 'checked_out', 'completed')");
$total_revenue = $stmt->fetch()['revenue'] ?? 0;

// Revenue in date range
$stmt = $conn->prepare("SELECT SUM(total_amount) as revenue FROM bookings WHERE status IN ('confirmed', 'checked_in', 'checked_out', 'completed') AND DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$period_revenue = $stmt->fetch()['revenue'] ?? 0;

// Room Bookings Analytics
$stmt = $conn->prepare("
    SELECT status, COUNT(*) as count, SUM(total_amount) as revenue
    FROM bookings 
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY status
");
$stmt->execute([$start_date, $end_date]);
$room_stats = [];
$room_stats['total'] = 0;
while ($row = $stmt->fetch()) {
    $room_stats[$row['status']] = $row['count'];
    $room_stats['total'] += $row['count'];
    if (!isset($room_stats['room_revenue'])) {
        $room_stats['room_revenue'] = 0;
    }
    if ($row['status'] !== 'cancelled') {
        $room_stats['room_revenue'] += $row['revenue'] ?? 0;
    }
}

// Package Bookings Analytics
$stmt = $conn->prepare("
    SELECT status, COUNT(*) as count, SUM(total_amount) as revenue
    FROM package_bookings 
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY status
");
$stmt->execute([$start_date, $end_date]);
$package_stats = [];
$package_stats['total'] = 0;
while ($row = $stmt->fetch()) {
    $package_stats[$row['status']] = $row['count'];
    $package_stats['total'] += $row['count'];
    if (!isset($package_stats['package_revenue'])) {
        $package_stats['package_revenue'] = 0;
    }
    if ($row['status'] !== 'cancelled') {
        $package_stats['package_revenue'] += $row['revenue'] ?? 0;
    }
}

// Most Popular Rooms
$stmt = $conn->prepare("
    SELECT r.id, r.name, COUNT(b.id) as bookings, AVG(b.total_amount) as avg_price
    FROM rooms r 
    LEFT JOIN bookings b ON r.id = b.room_id AND DATE(b.created_at) BETWEEN ? AND ?
    GROUP BY r.id 
    ORDER BY bookings DESC 
    LIMIT 5
");
$stmt->execute([$start_date, $end_date]);
$popular_rooms = $stmt->fetchAll();

// Most Popular Packages
$stmt = $conn->prepare("
    SELECT p.id, p.name, COUNT(pb.id) as bookings, AVG(pb.total_amount) as avg_price
    FROM travel_packages p 
    LEFT JOIN package_bookings pb ON p.id = pb.package_id AND DATE(pb.created_at) BETWEEN ? AND ?
    GROUP BY p.id 
    ORDER BY bookings DESC 
    LIMIT 5
");
$stmt->execute([$start_date, $end_date]);
$popular_packages = $stmt->fetchAll();

// Daily Revenue Chart Data
$stmt = $conn->prepare("
    SELECT DATE(created_at) as date, SUM(total_amount) as revenue
    FROM bookings 
    WHERE status IN ('confirmed', 'checked_in', 'checked_out', 'completed') 
    AND DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY DATE(created_at)
");
$stmt->execute([$start_date, $end_date]);
$daily_revenue = $stmt->fetchAll();

// User Metrics
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT id) as new_users
    FROM users 
    WHERE DATE(created_at) BETWEEN ? AND ?
");
$stmt->execute([$start_date, $end_date]);
$new_users = $stmt->fetch()['new_users'] ?? 0;

// Top Rooms by Revenue
$stmt = $conn->prepare("
    SELECT r.name, SUM(b.total_amount) as revenue, COUNT(b.id) as bookings
    FROM rooms r 
    LEFT JOIN bookings b ON r.id = b.room_id AND DATE(b.created_at) BETWEEN ? AND ?
    GROUP BY r.id 
    ORDER BY revenue DESC 
    LIMIT 5
");
$stmt->execute([$start_date, $end_date]);
$top_revenue_rooms = $stmt->fetchAll();

// Top Packages by Revenue
$stmt = $conn->prepare("
    SELECT p.name, SUM(pb.total_amount) as revenue, COUNT(pb.id) as bookings
    FROM travel_packages p 
    LEFT JOIN package_bookings pb ON p.id = pb.package_id AND DATE(pb.created_at) BETWEEN ? AND ?
    GROUP BY p.id 
    ORDER BY revenue DESC 
    LIMIT 5
");
$stmt->execute([$start_date, $end_date]);
$top_revenue_packages = $stmt->fetchAll();

// Occupancy Rate (for rooms)
$total_rooms = 0;
$stmt = $conn->query("SELECT COUNT(*) as count FROM rooms WHERE status = 'active'");
$total_rooms = $stmt->fetch()['count'];

$booked_rooms = 0;
if ($total_rooms > 0) {
    $stmt = $conn->query("SELECT COUNT(DISTINCT room_id) as count FROM bookings WHERE status IN ('confirmed', 'checked_in') AND checkin_date <= CURDATE() AND checkout_date >= CURDATE()");
    $booked_rooms = $stmt->fetch()['count'];
}
$occupancy_rate = $total_rooms > 0 ? ($booked_rooms / $total_rooms) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                                <i class="fas fa-chart-line text-primary me-3"></i>
                                Analytics Dashboard
                            </h1>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                                    <li class="breadcrumb-item active">Analytics</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>

                <!-- Date Range Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter me-2"></i>Filter
                                </button>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <a href="analytics.php" class="btn btn-secondary w-100">
                                    <i class="fas fa-redo me-2"></i>Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Key Metrics -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card border-left-primary">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="stat-label">Period Revenue</div>
                                    <div class="stat-value"><?php echo number_format($period_revenue, 0); ?> Nrs.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card border-left-success">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-success bg-opacity-10 text-success">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="stat-label">Total Bookings</div>
                                    <div class="stat-value"><?php echo number_format($room_stats['total'] + $package_stats['total']); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card border-left-info">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-info bg-opacity-10 text-info">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="stat-label">New Users</div>
                                    <div class="stat-value"><?php echo number_format($new_users); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card border-left-warning">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                                    <i class="fas fa-chart-pie"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="stat-label">Occupancy Rate</div>
                                    <div class="stat-value"><?php echo number_format($occupancy_rate, 1); ?>%</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Room vs Package Analytics -->
                <div class="row mb-4">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-bed me-2"></i>Room Bookings Analytics
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Total Bookings</span>
                                        <strong><?php echo $room_stats['total'] ?? 0; ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Revenue</span>
                                        <strong><?php echo number_format($room_stats['room_revenue'] ?? 0, 0); ?> Nrs.</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span><span class="badge bg-success">Confirmed</span></span>
                                        <strong><?php echo $room_stats['confirmed'] ?? 0; ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span><span class="badge bg-info">Checked In</span></span>
                                        <strong><?php echo $room_stats['checked_in'] ?? 0; ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span><span class="badge bg-primary">Checked Out</span></span>
                                        <strong><?php echo $room_stats['checked_out'] ?? 0; ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span><span class="badge bg-danger">Cancelled</span></span>
                                        <strong><?php echo $room_stats['cancelled'] ?? 0; ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-suitcase-rolling me-2"></i>Package Bookings Analytics
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Total Bookings</span>
                                        <strong><?php echo $package_stats['total'] ?? 0; ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Revenue</span>
                                        <strong><?php echo number_format($package_stats['package_revenue'] ?? 0, 0); ?> Nrs.</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span><span class="badge bg-success">Confirmed</span></span>
                                        <strong><?php echo $package_stats['confirmed'] ?? 0; ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span><span class="badge bg-info">In Progress</span></span>
                                        <strong><?php echo $package_stats['in_progress'] ?? 0; ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span><span class="badge bg-primary">Completed</span></span>
                                        <strong><?php echo $package_stats['completed'] ?? 0; ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span><span class="badge bg-danger">Cancelled</span></span>
                                        <strong><?php echo $package_stats['cancelled'] ?? 0; ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Revenue Chart -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-line me-2"></i>Daily Revenue Trend
                                </h5>
                            </div>
                            <div class="card-body">
                                <canvas id="revenueChart" height="60"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Popular Items -->
                <div class="row mb-4">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-star me-2"></i>Top 5 Most Booked Rooms
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Room Name</th>
                                                <th>Bookings</th>
                                                <th>Avg Price</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($popular_rooms as $room): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($room['name']); ?></td>
                                                <td><span class="badge bg-info"><?php echo $room['bookings'] ?? 0; ?></span></td>
                                                <td><?php echo number_format($room['avg_price'] ?? 0, 0); ?> Nrs.</td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-star me-2"></i>Top 5 Most Booked Packages
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Package Name</th>
                                                <th>Bookings</th>
                                                <th>Avg Price</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($popular_packages as $package): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($package['name']); ?></td>
                                                <td><span class="badge bg-info"><?php echo $package['bookings'] ?? 0; ?></span></td>
                                                <td><?php echo number_format($package['avg_price'] ?? 0, 0); ?> Nrs.</td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Revenue Items -->
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-trophy me-2"></i>Top 5 Rooms by Revenue
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Room Name</th>
                                                <th>Bookings</th>
                                                <th>Revenue</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($top_revenue_rooms as $room): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($room['name']); ?></td>
                                                <td><span class="badge bg-info"><?php echo $room['bookings'] ?? 0; ?></span></td>
                                                <td><?php echo number_format($room['revenue'] ?? 0, 0); ?> Nrs.</td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-trophy me-2"></i>Top 5 Packages by Revenue
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Package Name</th>
                                                <th>Bookings</th>
                                                <th>Revenue</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($top_revenue_packages as $package): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($package['name']); ?></td>
                                                <td><span class="badge bg-info"><?php echo $package['bookings'] ?? 0; ?></span></td>
                                                <td><?php echo number_format($package['revenue'] ?? 0, 0); ?> Nrs.</td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
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
        // Daily Revenue Chart
        const chartData = <?php echo json_encode($daily_revenue); ?>;
        
        if (chartData && chartData.length > 0) {
            const dates = chartData.map(d => d['date']);
            const revenues = chartData.map(d => parseFloat(d['revenue'] || 0));
            
            const ctx = document.getElementById('revenueChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dates,
                    datasets: [{
                        label: 'Daily Revenue (Nrs.)',
                        data: revenues,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointBackgroundColor: '#0d6efd',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString('en-IN') + ' Nrs.';

                                }
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
