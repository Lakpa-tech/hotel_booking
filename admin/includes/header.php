<nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                 style="width: 40px; height: 40px;">
                <i class="fas fa-hotel"></i>
            </div>
        </a>
        
        <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="navbar-nav ms-auto">
            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" 
                         style="width: 32px; height: 32px; font-size: 0.875rem;">
                        <i class="fas fa-user"></i>
                    </div>
                    <span class="fw-medium"><?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                    <span class="badge bg-primary ms-2"><?php echo ucfirst($_SESSION['admin_role']); ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li><h6 class="dropdown-header">
                        <i class="fas fa-user-circle me-2"></i>Account
                    </h6></li>
                    <li><a class="dropdown-item" href="settings.php">
                        <i class="fas fa-cog me-2"></i> Hotel Settings
                    </a></li>
                    <li><a class="dropdown-item" href="../index.php" target="_blank">
                        <i class="fas fa-external-link-alt me-2"></i> View Website
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>