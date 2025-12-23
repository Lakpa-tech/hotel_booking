<nav class="col-md-3 col-lg-2 d-md-block sidebar collapse" id="sidebarMenu">
    <div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'active' : ''; ?>" href="bookings.php">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Room Bookings</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'rooms.php' ? 'active' : ''; ?>" href="rooms.php">
                    <i class="fas fa-bed"></i>
                    <span>Rooms</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'packages.php' ? 'active' : ''; ?>" href="packages.php">
                    <i class="fas fa-map-marked-alt"></i>
                    <span>Travel Packages</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'package_bookings.php' ? 'active' : ''; ?>" href="package_bookings.php">
                    <i class="fas fa-suitcase-rolling"></i>
                    <span>Package Bookings</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" href="users.php">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'active' : ''; ?>" href="reviews.php">
                    <i class="fas fa-star"></i>
                    <span>Reviews</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : ''; ?>" href="messages.php">
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                    <?php
                    if (isset($conn)) {
                        $stmt = $conn->query("SELECT COUNT(*) as count FROM contact_messages WHERE status = 'new'");
                        $pending = $stmt->fetch()['count'];
                        if ($pending > 0) {
                            echo "<span class=\"badge bg-danger rounded-pill ms-auto\">{$pending}</span>";
                        }
                    }
                    ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                    <i class="fas fa-cog"></i>
                    <span>Hotel Settings</span>
                </a>
            </li>
        </ul>
        
        <div class="px-3 mt-2">
            <a href="analytics.php" class="btn btn-primary w-100 text-start">
                <i class="fas fa-chart-line me-2"></i>
                <div>
                    <h6 class="fw-bold mb-1">Analytics</h6>
                    <small class="text-opacity-75">Track your hotel performance</small>
                </div>
            </a>
        </div>
    </div>
</nav>