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

// Handle form submission
if ($_POST) {
    $settings = [
        'site_name' => trim($_POST['site_name'] ?? ''),
        'hotel_description' => trim($_POST['hotel_description'] ?? ''),
        'hotel_mission' => trim($_POST['hotel_mission'] ?? ''),
        'hotel_vision' => trim($_POST['hotel_vision'] ?? ''),
        'contact_email' => trim($_POST['contact_email'] ?? ''),
        'contact_phone' => trim($_POST['contact_phone'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'facebook_url' => trim($_POST['facebook_url'] ?? ''),
        'instagram_url' => trim($_POST['instagram_url'] ?? ''),
        'tiktok_url' => trim($_POST['tiktok_url'] ?? ''),
        'google_maps_embed' => trim($_POST['google_maps_embed'] ?? ''),
        'check_in_time' => trim($_POST['check_in_time'] ?? ''),
        'check_out_time' => trim($_POST['check_out_time'] ?? ''),
        'cancellation_policy' => trim($_POST['cancellation_policy'] ?? ''),
        'hotel_facilities' => trim($_POST['hotel_facilities'] ?? ''),
        'room_amenities' => trim($_POST['room_amenities'] ?? ''),
        'bathroom_amenities' => trim($_POST['bathroom_amenities'] ?? ''),
        'additional_services' => trim($_POST['additional_services'] ?? ''),
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0'
    ];
    
    $updated = 0;
    foreach ($settings as $key => $value) {
        $stmt = $conn->prepare("INSERT INTO website_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        if ($stmt->execute([$key, $value, $value])) {
            $updated++;
        }
    }
    
    if ($updated > 0) {
        $success = 'Hotel settings updated successfully!';
    } else {
        $error = 'Failed to update settings. Please try again.';
    }
}

// Get current settings
$stmt = $conn->query("SELECT setting_key, setting_value FROM website_settings");
$current_settings = [];
while ($row = $stmt->fetch()) {
    $current_settings[$row['setting_key']] = $row['setting_value'];
}

// Default values
$defaults = [
    'site_name' => 'Rin-Odge Hotel',
    'hotel_description' => '',
    'hotel_mission' => '',
    'hotel_vision' => '',
    'contact_email' => '',
    'contact_phone' => '',
    'address' => '',
    'facebook_url' => '#',
    'instagram_url' => '#',
    'tiktok_url' => '#',
    'google_maps_embed' => '',
    'check_in_time' => '15:00',
    'check_out_time' => '11:00',
    'cancellation_policy' => '',
    'hotel_facilities' => 'Free WiFi,Restaurant,Free Parking,Room Service,24/7 Security,24/7 Reception',
    'room_amenities' => 'Air Conditioning,Comfortable Bedding,Work Desk,Seating Area,Wardrobe,Safe Deposit Box,Blackout Curtains,Daily Housekeeping,Flat Screen TV,Cable Channels,Free WiFi,Phone Service,Power Outlets,USB Charging Ports,Reading Lights,Wake-up Service',
    'bathroom_amenities' => 'Private Bathroom,Hot & Cold Water,Shower,Complimentary Toiletries,Fresh Towels,Hair Dryer,Mirror,Bathroom Slippers',
    'additional_services' => 'Laundry Service,Airport Transfer,Tour Assistance,Luggage Storage',
    'maintenance_mode' => '0'
];

$settings = array_merge($defaults, $current_settings);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Settings - Admin Panel</title>
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
                                <i class="fas fa-cog text-primary me-3"></i>
                                Hotel Settings
                            </h1>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                                    <li class="breadcrumb-item active">Settings</li>
                                </ol>
                            </nav>
                        </div>
                        <div class="text-end">
                            <button type="submit" form="settingsForm" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
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

                <form method="POST" id="settingsForm">
                    <!-- Basic Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>Basic Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Hotel Name</label>
                                    <input type="text" class="form-control" name="site_name" 
                                           value="<?php echo htmlspecialchars($settings['site_name']); ?>" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Contact Email</label>
                                    <input type="email" class="form-control" name="contact_email" 
                                           value="<?php echo htmlspecialchars($settings['contact_email']); ?>" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Contact Phone</label>
                                    <input type="text" class="form-control" name="contact_phone" 
                                           value="<?php echo htmlspecialchars($settings['contact_phone']); ?>" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Address</label>
                                    <input type="text" class="form-control" name="address" 
                                           value="<?php echo htmlspecialchars($settings['address']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Hotel Description</label>
                                <textarea class="form-control" name="hotel_description" rows="3" 
                                          placeholder="Brief description of your hotel..."><?php echo htmlspecialchars($settings['hotel_description']); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Mission & Vision -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-bullseye me-2"></i>Mission & Vision
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Mission Statement</label>
                                    <textarea class="form-control" name="hotel_mission" rows="4" 
                                              placeholder="Your hotel's mission statement..."><?php echo htmlspecialchars($settings['hotel_mission']); ?></textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Vision Statement</label>
                                    <textarea class="form-control" name="hotel_vision" rows="4" 
                                              placeholder="Your hotel's vision statement..."><?php echo htmlspecialchars($settings['hotel_vision']); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Social Media Links -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-share-alt me-2"></i>Social Media Links
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">
                                        <i class="fab fa-facebook text-primary me-2"></i>Facebook URL
                                    </label>
                                    <input type="url" class="form-control" name="facebook_url" 
                                           value="<?php echo htmlspecialchars($settings['facebook_url']); ?>" 
                                           placeholder="https://facebook.com/yourhotel">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">
                                        <i class="fab fa-instagram text-danger me-2"></i>Instagram URL
                                    </label>
                                    <input type="url" class="form-control" name="instagram_url" 
                                           value="<?php echo htmlspecialchars($settings['instagram_url']); ?>" 
                                           placeholder="https://instagram.com/yourhotel">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">
                                        <i class="fab fa-tiktok text-dark me-2"></i>TikTok URL
                                    </label>
                                    <input type="url" class="form-control" name="tiktok_url" 
                                           value="<?php echo htmlspecialchars($settings['tiktok_url']); ?>" 
                                           placeholder="https://tiktok.com/@yourhotel">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Location & Maps -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-map-marker-alt me-2"></i>Location & Maps
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Google Maps Embed Code</label>
                                <textarea class="form-control" name="google_maps_embed" rows="3" 
                                          placeholder="Paste your Google Maps embed iframe code here..."><?php echo htmlspecialchars($settings['google_maps_embed']); ?></textarea>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Get embed code from Google Maps by clicking "Share" → "Embed a map"
                                </div>
                            </div>
                            
                            <?php if (!empty($settings['google_maps_embed'])): ?>
                            <div class="mb-3">
                                <label class="form-label">Map Preview</label>
                                <div class="mb-2">
                                    <a href="https://maps.app.goo.gl/tW9UeZjrXrzQcLhHA" target="_blank" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-external-link-alt me-2"></i>View on Google Maps
                                    </a>
                                </div>
                                <div class="border rounded">
                                    <iframe src="<?php echo htmlspecialchars($settings['google_maps_embed']); ?>" 
                                            width="100%" height="300" style="border:0;" allowfullscreen="" 
                                            loading="lazy" referrerpolicy="no-referrer-when-downgrade">
                                    </iframe>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Hotel Policies -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-clock me-2"></i>Hotel Policies
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Check-in Time</label>
                                    <input type="time" class="form-control" name="check_in_time" 
                                           value="<?php echo htmlspecialchars($settings['check_in_time']); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Check-out Time</label>
                                    <input type="time" class="form-control" name="check_out_time" 
                                           value="<?php echo htmlspecialchars($settings['check_out_time']); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-check form-switch mt-4">
                                        <input class="form-check-input" type="checkbox" name="maintenance_mode" 
                                               id="maintenanceMode" <?php echo $settings['maintenance_mode'] === '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="maintenanceMode">
                                            <strong>Maintenance Mode</strong>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Cancellation Policy</label>
                                <textarea class="form-control" name="cancellation_policy" rows="3" 
                                          placeholder="Describe your cancellation policy..."><?php echo htmlspecialchars($settings['cancellation_policy']); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Hotel Facilities & Amenities -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-concierge-bell me-2"></i>Hotel Facilities & Amenities
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Main Hotel Facilities</label>
                                    <textarea class="form-control" name="hotel_facilities" rows="4" 
                                              placeholder="Enter main facilities separated by commas (e.g., Free WiFi, Restaurant, Free Parking)"><?php echo htmlspecialchars($settings['hotel_facilities']); ?></textarea>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Main hotel facilities displayed prominently on the facilities page.
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Room Amenities</label>
                                    <textarea class="form-control" name="room_amenities" rows="4" 
                                              placeholder="Enter room amenities separated by commas (e.g., Air Conditioning, TV, WiFi)"><?php echo htmlspecialchars($settings['room_amenities']); ?></textarea>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Amenities available in guest rooms.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Bathroom Amenities</label>
                                    <textarea class="form-control" name="bathroom_amenities" rows="3" 
                                              placeholder="Enter bathroom amenities separated by commas (e.g., Hot Water, Toiletries, Hair Dryer)"><?php echo htmlspecialchars($settings['bathroom_amenities']); ?></textarea>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Amenities available in guest bathrooms.
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Additional Services</label>
                                    <textarea class="form-control" name="additional_services" rows="3" 
                                              placeholder="Enter additional services separated by commas (e.g., Laundry Service, Airport Transfer)"><?php echo htmlspecialchars($settings['additional_services']); ?></textarea>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Extra services offered to guests.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="card">
                        <div class="card-body text-center">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>Save All Settings
                            </button>
                            <div class="mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Changes will be applied immediately to your website.
                                </small>
                            </div>
                        </div>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add form validation and smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('settingsForm');
            const cards = document.querySelectorAll('.card');
            
            // Animate cards on load
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
            
            // Form submission with loading state
            form.addEventListener('submit', function() {
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="loading me-2"></span>Saving...';
                submitBtn.disabled = true;
                
                // Re-enable after 3 seconds (in case of error)
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 3000);
            });
        });
    </script>
</body>
</html>