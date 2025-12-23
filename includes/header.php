<<?php
// Check for maintenance mode and display persistent alert
if (!empty($website_settings['maintenance_mode']) && $website_settings['maintenance_mode']) {
    echo '
    <div id="maintenanceBanner" class="maintenance-banner" style="
        position: fixed; 
        top: 0; 
        left: 0; 
        right: 0; 
        z-index: 1100; 
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 50%, #991b1b 100%); 
        color: white; 
        border: none; 
        padding: 12px 0;
        box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        animation: maintenancePulse 3s ease-in-out infinite;
    ">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle me-2 fa-lg" style="animation: shake 2s ease-in-out infinite;"></i>
                        <div>
                            <strong class="fs-6">🚧 MAINTENANCE MODE ACTIVE 🚧</strong>
                            <div class="small mt-1">
                                The hotel is currently under maintenance. Some services may be temporarily unavailable.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="d-flex align-items-center justify-content-md-end">
                        <div class="maintenance-status-indicator me-2" style="
                            width: 12px; 
                            height: 12px; 
                            background: #fbbf24; 
                            border-radius: 50%; 
                            animation: blink 1.5s ease-in-out infinite;
                        "></div>
                        <small class="text-white-50">Status: Under Maintenance</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        @keyframes maintenancePulse {
            0%, 100% { background: linear-gradient(135deg, #dc2626 0%, #b91c1c 50%, #991b1b 100%); }
            50% { background: linear-gradient(135deg, #ef4444 0%, #dc2626 50%, #b91c1c 100%); }
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-2px); }
            20%, 40%, 60%, 80% { transform: translateX(2px); }
        }
        
        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0.3; }
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .maintenance-banner {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        
        /* Ensure banner stays visible on scroll */
        body.maintenance-active {
            padding-top: 80px !important;
        }
        
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .maintenance-banner .small {
                font-size: 0.75rem;
            }
            .maintenance-banner .col-md-4 {
                margin-top: 8px;
            }
        }
    </style>
    
    <script>
        // Add maintenance class to body
        document.body.classList.add("maintenance-active");
        
        // Optional: Add periodic reminder (every 5 minutes)
        setInterval(function() {
            if (document.getElementById("maintenanceBanner")) {
                const banner = document.getElementById("maintenanceBanner");
                banner.style.animation = "none";
                setTimeout(() => {
                    banner.style.animation = "maintenancePulse 3s ease-in-out infinite";
                }, 100);
            }
        }, 300000); // 5 minutes
    </script>';
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top" style="<?php echo (!empty($website_settings['maintenance_mode']) && $website_settings['maintenance_mode']) ? 'top: 80px;' : 'top: 0;'; ?>">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-hotel"></i> Rin-Odge
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="rooms.php">Rooms</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="packages.php">Travel Packages</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="facilities.php">Facilities</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="about.php">About Us</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="contact.php">Contact</a>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <?php if (!empty($website_settings['maintenance_mode']) && $website_settings['maintenance_mode']): ?>
                    <li class="nav-item">
                        <span class="navbar-text me-3">
                            <span class="badge bg-warning text-dark px-2 py-1" style="animation: pulse 2s infinite;">
                                <i class="fas fa-tools me-1"></i>Maintenance Mode
                            </span>
                        </span>
                    </li>
                <?php endif; ?>
                
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="account.php"><i class="fas fa-user-cog"></i> My Account</a></li>
                            <li><a class="dropdown-item" href="bookings.php"><i class="fas fa-calendar-alt"></i> My Bookings</a></li>
                            <li><a class="dropdown-item" href="reviews.php"><i class="fas fa-star"></i> My Reviews</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php"><i class="fas fa-user-plus"></i> Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div style="height: <?php echo (!empty($website_settings['maintenance_mode']) && $website_settings['maintenance_mode']) ? '156px' : '76px'; ?>;"></div> <!-- Spacer for fixed navbar -->