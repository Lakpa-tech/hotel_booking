<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Require login
requireLogin();

$error = '';
$success = '';

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Handle profile update
if ($_POST && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    if (empty($full_name) || empty($email) || empty($phone)) {
        $error = 'Please fill in all fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        // Check if email is already taken by another user
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        
        if ($stmt->fetch()) {
            $error = 'Email address is already in use';
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
            if ($stmt->execute([$full_name, $email, $phone, $_SESSION['user_id']])) {
                $_SESSION['full_name'] = $full_name;
                $success = 'Profile updated successfully';
                // Refresh user data
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
            } else {
                $error = 'Failed to update profile';
            }
        }
    }
}

// Handle password change
if ($_POST && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Please fill in all password fields';
    } elseif (!password_verify($current_password, $user['password'])) {
        $error = 'Current password is incorrect';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters long';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match';
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stmt->execute([$hashed_password, $_SESSION['user_id']])) {
            $success = 'Password changed successfully';
        } else {
            $error = 'Failed to change password';
        }
    }
}

// Get user statistics
$stats_stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_bookings,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_bookings,
        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_bookings,
        COALESCE(SUM(CASE WHEN status IN ('completed', 'checked_out') THEN total_amount END), 0) as total_spent
    FROM bookings 
    WHERE user_id = ?
");
$stats_stmt->execute([$_SESSION['user_id']]);
$stats = $stats_stmt->fetch();

// Get recent bookings
$recent_bookings_stmt = $conn->prepare("
    SELECT b.*, r.name as room_name, r.image as room_image 
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.id 
    WHERE b.user_id = ? 
    ORDER BY b.created_at DESC 
    LIMIT 3
");
$recent_bookings_stmt->execute([$_SESSION['user_id']]);
$recent_bookings = $recent_bookings_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - Hotel Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-12 mb-4">
                <h1><i class="fas fa-user-cog"></i> My Account</h1>
                <p class="text-muted">Manage your profile and view your booking history</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Account Navigation -->
            <div class="col-lg-3 mb-4">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-user-circle fa-4x text-primary"></i>
                        </div>
                        <h5><?php echo htmlspecialchars($user['full_name']); ?></h5>
                        <p class="text-muted mb-0"><?php echo htmlspecialchars($user['email']); ?></p>
                        <small class="text-muted">Member since <?php echo formatDate($user['created_at']); ?></small>
                    </div>
                </div>

                <div class="list-group mt-4">
                    <a href="#profile" class="list-group-item list-group-item-action active" data-bs-toggle="pill">
                        <i class="fas fa-user me-2"></i> Profile Information
                    </a>
                    <a href="#password" class="list-group-item list-group-item-action" data-bs-toggle="pill">
                        <i class="fas fa-lock me-2"></i> Change Password
                    </a>
                    <a href="#statistics" class="list-group-item list-group-item-action" data-bs-toggle="pill">
                        <i class="fas fa-chart-bar me-2"></i> Account Statistics
                    </a>
                    <a href="bookings.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-calendar-alt me-2"></i> My Bookings
                    </a>
                    <a href="reviews.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-star me-2"></i> My Reviews
                    </a>
                </div>
            </div>

            <!-- Account Content -->
            <div class="col-lg-9">
                <div class="tab-content">
                    <!-- Profile Information -->
                    <div class="tab-pane fade show active" id="profile">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-user"></i> Profile Information</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Full Name</label>
                                            <input type="text" class="form-control" name="full_name" 
                                                   value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Username</label>
                                            <input type="text" class="form-control" 
                                                   value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                                            <div class="form-text">Username cannot be changed</div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email Address</label>
                                            <input type="email" class="form-control" name="email" 
                                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Phone Number</label>
                                            <input type="tel" class="form-control" name="phone" 
                                                   value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Profile
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Change Password -->
                    <div class="tab-pane fade" id="password">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-lock"></i> Change Password</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" id="passwordForm">
                                    <div class="mb-3">
                                        <label class="form-label">Current Password</label>
                                        <input type="password" class="form-control" name="current_password" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">New Password</label>
                                        <input type="password" class="form-control" name="new_password" 
                                               id="new_password" required>
                                        <div class="form-text">Password must be at least 6 characters long</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" name="confirm_password" 
                                               id="confirm_password" required>
                                    </div>
                                    
                                    <button type="submit" name="change_password" class="btn btn-primary">
                                        <i class="fas fa-key"></i> Change Password
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Account Statistics -->
                    <div class="tab-pane fade" id="statistics">
                        <div class="row">
                            <div class="col-md-3 mb-4">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <i class="fas fa-calendar-check fa-2x text-primary mb-2"></i>
                                        <h4><?php echo $stats['total_bookings']; ?></h4>
                                        <p class="text-muted mb-0">Total Bookings</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 mb-4">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                        <h4><?php echo $stats['completed_bookings']; ?></h4>
                                        <p class="text-muted mb-0">Completed Stays</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 mb-4">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                                        <h4><?php echo $stats['cancelled_bookings']; ?></h4>
                                        <p class="text-muted mb-0">Cancelled</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 mb-4">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <i class="fas fa-dollar-sign fa-2x text-warning mb-2"></i>
                                        <h4><?php echo number_format($stats['total_spent'], 0); ?> Nrs.</h4>
                                        <p class="text-muted mb-0">Total Spent</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Bookings -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-history"></i> Recent Bookings</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_bookings)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                        <h5>No bookings yet</h5>
                                        <p class="text-muted">Start exploring our rooms and make your first booking!</p>
                                        <a href="rooms.php" class="btn btn-primary">Browse Rooms</a>
                                    </div>
                                <?php else: ?>
                                    <div class="row">
                                        <?php foreach ($recent_bookings as $booking): ?>
                                            <div class="col-md-4 mb-3">
                                                <div class="card">
                                                    <img src="<?php echo htmlspecialchars($booking['room_image']); ?>" 
                                                         class="card-img-top" style="height: 150px; object-fit: cover;">
                                                    <div class="card-body">
                                                        <h6 class="card-title"><?php echo htmlspecialchars($booking['room_name']); ?></h6>
                                                        <p class="card-text small">
                                                            <strong>Booking ID:</strong> <?php echo htmlspecialchars($booking['booking_id']); ?><br>
                                                            <strong>Dates:</strong> <?php echo formatDate($booking['checkin_date']); ?> - <?php echo formatDate($booking['checkout_date']); ?><br>
                                                            <strong>Status:</strong> 
                                                            <span class="badge bg-<?php echo $booking['status'] === 'completed' ? 'success' : ($booking['status'] === 'cancelled' ? 'danger' : 'primary'); ?>">
                                                                <?php echo ucfirst($booking['status']); ?>
                                                            </span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="text-center">
                                        <a href="bookings.php" class="btn btn-outline-primary">View All Bookings</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>