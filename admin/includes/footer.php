<?php
// Get website settings for admin footer
if (!isset($admin_footer_settings)) {
    $stmt = $conn->query("SELECT setting_key, setting_value FROM website_settings");
    $admin_footer_settings = [];
    while ($row = $stmt->fetch()) {
        $admin_footer_settings[$row['setting_key']] = $row['setting_value'];
    }
    
    // Default values if not set
    $admin_footer_defaults = [
        'site_name' => 'Rin-Odge Hotel',
        'hotel_description' => 'Experience luxury and comfort in our premium accommodations. Your perfect stay awaits.',
        'address' => 'Fikkal Petrol Pump, Ilam, Nepal',
        'contact_phone' => '+977 9746207003',
        'contact_email' => 'rinchhenhotel@gmail.com',
        'facebook_url' => '#',
        'instagram_url' => '#',
        'tiktok_url' => '#',
        'hotel_facilities' => 'Room Service,Free WiFi,Swimming Pool,Restaurant,Free Parking'
    ];
    
    $admin_footer_settings = array_merge($admin_footer_defaults, $admin_footer_settings);
}

// Get facilities for services section
$admin_facilities = explode(',', $admin_footer_settings['hotel_facilities']);
$admin_facilities = array_slice(array_map('trim', $admin_facilities), 0, 5); // Show only first 5 facilities
?>

<footer class="bg-dark text-white py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <h5><i class="fas fa-hotel"></i> <?php echo htmlspecialchars($admin_footer_settings['site_name']); ?></h5>
                <p><?php echo htmlspecialchars($admin_footer_settings['hotel_description']); ?></p>
                <div class="social-links">
                    <?php if ($admin_footer_settings['facebook_url'] && $admin_footer_settings['facebook_url'] !== '#'): ?>
                    <a href="<?php echo htmlspecialchars($admin_footer_settings['facebook_url']); ?>" target="_blank" class="text-white me-3">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($admin_footer_settings['instagram_url'] && $admin_footer_settings['instagram_url'] !== '#'): ?>
                    <a href="<?php echo htmlspecialchars($admin_footer_settings['instagram_url']); ?>" target="_blank" class="text-white me-3">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($admin_footer_settings['tiktok_url'] && $admin_footer_settings['tiktok_url'] !== '#'): ?>
                    <a href="<?php echo htmlspecialchars($admin_footer_settings['tiktok_url']); ?>" target="_blank" class="text-white">
                        <i class="fab fa-tiktok"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-6 mb-4">
                <h6>Quick Links</h6>
                <ul class="list-unstyled">
                    <li><a href="../index.php" class="text-white-50">Home</a></li>
                    <li><a href="../rooms.php" class="text-white-50">Rooms</a></li>
                    <li><a href="../packages.php" class="text-white-50">Travel Packages</a></li>
                    <li><a href="../facilities.php" class="text-white-50">Facilities</a></li>
                    <li><a href="../about.php" class="text-white-50">About Us</a></li>
                    <li><a href="../contact.php" class="text-white-50">Contact</a></li>
                </ul>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4">
                <h6>Services</h6>
                <ul class="list-unstyled">
                    <?php foreach ($admin_facilities as $facility): ?>
                        <?php if (!empty(trim($facility))): ?>
                        <li><span class="text-white-50"><?php echo htmlspecialchars(trim($facility)); ?></span></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="col-lg-3 mb-4">
                <h6>Contact Info</h6>
                <ul class="list-unstyled">
                    <li class="text-white-50">
                        <i class="fas fa-map-marker-alt me-2"></i> 
                        <?php echo htmlspecialchars($admin_footer_settings['address']); ?>
                    </li>
                    <li class="text-white-50">
                        <i class="fas fa-phone me-2"></i> 
                        <a href="tel:<?php echo htmlspecialchars($admin_footer_settings['contact_phone']); ?>" class="text-white-50 text-decoration-none">
                            <?php echo htmlspecialchars($admin_footer_settings['contact_phone']); ?>
                        </a>
                    </li>
                    <li class="text-white-50">
                        <i class="fas fa-envelope me-2"></i> 
                        <a href="mailto:<?php echo htmlspecialchars($admin_footer_settings['contact_email']); ?>" class="text-white-50 text-decoration-none">
                            <?php echo htmlspecialchars($admin_footer_settings['contact_email']); ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        
        <hr class="my-4">
        
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="mb-0 text-white-50">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($admin_footer_settings['site_name']); ?>. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="#" class="text-white-50 me-3">Privacy Policy</a>
                <a href="#" class="text-white-50">Terms of Service</a>
            </div>
        </div>
    </div>
</footer>