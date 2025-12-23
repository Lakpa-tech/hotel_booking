<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Get website settings
$stmt = $conn->query("SELECT setting_key, setting_value FROM website_settings");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Default values if not set
$defaults = [
    'site_name' => 'Rin-Odge Hotel',
    'hotel_description' => 'Experience luxury and comfort in the heart of Ilam, Nepal.',
    'hotel_mission' => 'To provide exceptional hospitality services that exceed our guests expectations while showcasing the beauty and culture of Nepal.',
    'hotel_vision' => 'To be the leading hotel in the region, known for our commitment to excellence, sustainability, and community engagement.',
    'address' => 'Fikkal Petrol Pump, Ilam, Nepal',
    'contact_phone' => '+977 9746207003',
    'contact_email' => 'rinchhenhotel@gmail.com',
    'hotel_facilities' => 'Free WiFi,Free Parking,Restaurant,Room Service,24/7 Security,24/7 Reception'
];

$settings = array_merge($defaults, $settings);
$facilities = explode(',', $settings['hotel_facilities']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Hotel Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-12 mb-4">
                <h1><i class="fas fa-info-circle"></i> About Us</h1>
                <p class="text-muted">Learn more about our hotel and commitment to excellence</p>
            </div>
        </div>

        <div class="row mb-5">
            <div class="col-lg-6">
                <h2>Welcome to <?php echo htmlspecialchars($settings['site_name']); ?></h2>
                <p class="lead"><?php echo htmlspecialchars($settings['hotel_description']); ?></p>
                <p>
                    Located at <?php echo htmlspecialchars($settings['address']); ?>, <?php echo htmlspecialchars($settings['site_name']); ?> offers a perfect blend of modern amenities 
                    and traditional Nepali hospitality. Our hotel has been serving guests since its establishment, 
                    providing comfortable accommodations and exceptional service.
                </p>
                <p>
                    Whether you're visiting for business or leisure, our dedicated staff ensures that your stay 
                    is memorable and comfortable. We take pride in offering personalized service and attention 
                    to detail that makes every guest feel special.
                </p>
            </div>
            <div class="col-lg-6">
                <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                     class="img-fluid rounded" alt="Hotel Exterior">
            </div>
        </div>

        <div class="row mb-5">
            <div class="col-12">
                <h2 class="text-center mb-4">Our Mission & Vision</h2>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-bullseye fa-3x text-primary mb-3"></i>
                        <h4>Our Mission</h4>
                        <p><?php echo nl2br(htmlspecialchars($settings['hotel_mission'])); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-eye fa-3x text-primary mb-3"></i>
                        <h4>Our Vision</h4>
                        <p><?php echo nl2br(htmlspecialchars($settings['hotel_vision'])); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-5">
            <div class="col-12">
                <h2 class="text-center mb-4">Why Choose Us</h2>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="text-center">
                    <i class="fas fa-map-marker-alt fa-3x text-primary mb-3"></i>
                    <h5>Prime Location</h5>
                    <p>Strategically located at <?php echo htmlspecialchars($settings['address']); ?>, easily accessible and close to major attractions.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="text-center">
                    <i class="fas fa-concierge-bell fa-3x text-primary mb-3"></i>
                    <h5>Exceptional Service</h5>
                    <p>24/7 dedicated staff committed to making your stay comfortable and memorable.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="text-center">
                    <i class="fas fa-bed fa-3x text-primary mb-3"></i>
                    <h5>Comfortable Rooms</h5>
                    <p>Well-appointed rooms with modern amenities and traditional Nepali touches.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="text-center">
                    <i class="fas fa-dollar-sign fa-3x text-primary mb-3"></i>
                    <h5>Great Value</h5>
                    <p>Competitive pricing without compromising on quality and service standards.</p>
                </div>
            </div>
        </div>

        <div class="row mb-5">
            <div class="col-12">
                <h2 class="text-center mb-4">Our Facilities</h2>
            </div>
            <?php 
            $facility_icons = [
                'Free WiFi' => 'fas fa-wifi',
                'Free Parking' => 'fas fa-car',
                'Restaurant' => 'fas fa-utensils',
                'Room Service' => 'fas fa-concierge-bell',
                '24/7 Security' => 'fas fa-shield-alt',
                '24/7 Reception' => 'fas fa-phone',
                'Swimming Pool' => 'fas fa-swimming-pool',
                'Gym' => 'fas fa-dumbbell',
                'Spa' => 'fas fa-spa',
                'Business Center' => 'fas fa-briefcase'
            ];
            
            foreach ($facilities as $index => $facility): 
                $facility = trim($facility);
                if (empty($facility)) continue;
                $icon = $facility_icons[$facility] ?? 'fas fa-check';
            ?>
            <div class="col-md-4 mb-3">
                <div class="d-flex align-items-center">
                    <i class="<?php echo $icon; ?> fa-2x text-primary me-3"></i>
                    <div>
                        <h6 class="mb-1"><?php echo htmlspecialchars($facility); ?></h6>
                        <small class="text-muted">Available for all guests</small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="row mb-5">
            <div class="col-12">
                <h2 class="text-center mb-4">Our Location</h2>
            </div>
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Find Us</h5>
                        <div class="mb-3">
                            <a href="https://maps.app.goo.gl/tW9UeZjrXrzQcLhHA" target="_blank" class="btn btn-primary btn-sm">
                                <i class="fas fa-map-marker-alt me-2"></i>View on Google Maps
                            </a>
                        </div>
                        <div class="mb-3">
                            <i class="fas fa-map-marker-alt text-primary me-2"></i>
                            <strong>Address:</strong> <?php echo htmlspecialchars($settings['address']); ?>
                        </div>
                        <div class="mb-3">
                            <i class="fas fa-phone text-primary me-2"></i>
                            <strong>Phone:</strong> <?php echo htmlspecialchars($settings['contact_phone']); ?>
                        </div>
                        <div class="mb-3">
                            <i class="fas fa-envelope text-primary me-2"></i>
                            <strong>Email:</strong> <?php echo htmlspecialchars($settings['contact_email']); ?>
                        </div>
                        <p class="text-muted">
                            We are conveniently located in Ilam, Nepal, making us easily accessible for both 
                            domestic and international travelers. Our prime location offers stunning views 
                            and easy access to local attractions.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-body p-0">
                        <div class="map-box">
                            <iframe src="<?php echo htmlspecialchars($settings['google_maps_embed']); ?>" 
                                    width="100%" height="350" style="border:0;" allowfullscreen="" 
                                    loading="lazy" referrerpolicy="no-referrer-when-downgrade">
                            </iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center py-5">
                        <h3>Ready to Experience Our Hospitality?</h3>
                        <p class="lead mb-4">Book your stay with us and discover the perfect blend of comfort and service.</p>
                        <a href="rooms.php" class="btn btn-light btn-lg">
                            <i class="fas fa-bed"></i> View Our Rooms
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>