<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Get website settings for facilities
$stmt = $conn->query("SELECT setting_key, setting_value FROM website_settings");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Default values if not set
$defaults = [
    'site_name' => 'Rin-Odge Hotel',
    'hotel_facilities' => 'Free WiFi,Restaurant,Free Parking,Room Service,24/7 Security,24/7 Reception',
    'room_amenities' => 'Air Conditioning,Comfortable Bedding,Work Desk,Seating Area,Wardrobe,Safe Deposit Box,Blackout Curtains,Daily Housekeeping,Flat Screen TV,Cable Channels,Free WiFi,Phone Service,Power Outlets,USB Charging Ports,Reading Lights,Wake-up Service',
    'bathroom_amenities' => 'Private Bathroom,Hot & Cold Water,Shower,Complimentary Toiletries,Fresh Towels,Hair Dryer,Mirror,Bathroom Slippers',
    'additional_services' => 'Laundry Service,Airport Transfer,Tour Assistance,Luggage Storage'
];

$settings = array_merge($defaults, $settings);

// Parse facilities into arrays
$main_facilities = explode(',', $settings['hotel_facilities']);
$room_amenities = explode(',', $settings['room_amenities']);
$bathroom_amenities = explode(',', $settings['bathroom_amenities']);
$additional_services = explode(',', $settings['additional_services']);

// Facility icons mapping
$facility_icons = [
    'Free WiFi' => 'fas fa-wifi',
    'Restaurant' => 'fas fa-utensils',
    'Free Parking' => 'fas fa-car',
    'Room Service' => 'fas fa-concierge-bell',
    '24/7 Security' => 'fas fa-shield-alt',
    '24/7 Reception' => 'fas fa-phone',
    'Swimming Pool' => 'fas fa-swimming-pool',
    'Gym' => 'fas fa-dumbbell',
    'Spa' => 'fas fa-spa',
    'Business Center' => 'fas fa-briefcase',
    'Laundry Service' => 'fas fa-tshirt',
    'Airport Transfer' => 'fas fa-plane',
    'Tour Assistance' => 'fas fa-map-marked-alt',
    'Luggage Storage' => 'fas fa-luggage-cart'
];

// Facility descriptions
$facility_descriptions = [
    'Free WiFi' => 'High-speed wireless internet access throughout the hotel premises, including all rooms and common areas.',
    'Restaurant' => 'Experience authentic Nepali cuisine and international dishes prepared by our skilled chefs.',
    'Free Parking' => 'Complimentary parking space for all our guests with 24/7 security monitoring.',
    'Room Service' => '24/7 room service to cater to all your needs and ensure maximum comfort during your stay.',
    '24/7 Security' => 'Round-the-clock security service with trained personnel and modern surveillance systems.',
    '24/7 Reception' => 'Our friendly reception staff is available around the clock to assist with any requests or inquiries.',
    'Swimming Pool' => 'Relax and unwind in our clean and well-maintained swimming pool facility.',
    'Gym' => 'Stay fit during your stay with our modern fitness equipment and exercise facilities.',
    'Spa' => 'Rejuvenate your body and mind with our professional spa and wellness services.',
    'Business Center' => 'Fully equipped business center with meeting rooms and office facilities.',
    'Laundry Service' => 'Professional laundry and dry cleaning services available.',
    'Airport Transfer' => 'Convenient transportation to and from the airport.',
    'Tour Assistance' => 'Local tour planning and booking assistance.',
    'Luggage Storage' => 'Secure luggage storage for early arrivals and late departures.'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facilities - Hotel Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-12 mb-4">
                <h1><i class="fas fa-building"></i> <?php echo htmlspecialchars($settings['site_name']); ?> Facilities</h1>
                <p class="text-muted">Discover our comprehensive range of amenities and services</p>
            </div>
        </div>

        <!-- Main Facilities -->
        <div class="row mb-5">
            <?php foreach ($main_facilities as $facility): ?>
                <?php 
                $facility = trim($facility);
                if (empty($facility)) continue;
                $icon = $facility_icons[$facility] ?? 'fas fa-check-circle';
                $description = $facility_descriptions[$facility] ?? 'Premium facility available for all our guests.';
                ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="<?php echo $icon; ?> fa-4x text-primary mb-3"></i>
                            <h4><?php echo htmlspecialchars($facility); ?></h4>
                            <p><?php echo htmlspecialchars($description); ?></p>
                            <div class="text-start">
                                <small class="text-success">
                                    <i class="fas fa-check me-2"></i>Available 24/7
                                </small><br>
                                <small class="text-success">
                                    <i class="fas fa-check me-2"></i>Complimentary service
                                </small><br>
                                <small class="text-success">
                                    <i class="fas fa-check me-2"></i>Professional quality
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Room Amenities -->
        <div class="row mb-5">
            <div class="col-12">
                <h2 class="text-center mb-4">Room Amenities</h2>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-bed text-primary me-2"></i> Comfort & Tech Features</h5>
                        <div class="row">
                            <?php 
                            $half = ceil(count($room_amenities) / 2);
                            $first_half = array_slice($room_amenities, 0, $half);
                            $second_half = array_slice($room_amenities, $half);
                            ?>
                            <div class="col-6">
                                <ul class="list-unstyled">
                                    <?php foreach ($first_half as $amenity): ?>
                                        <?php if (!empty(trim($amenity))): ?>
                                        <li><i class="fas fa-check text-success me-2"></i> <?php echo htmlspecialchars(trim($amenity)); ?></li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <div class="col-6">
                                <ul class="list-unstyled">
                                    <?php foreach ($second_half as $amenity): ?>
                                        <?php if (!empty(trim($amenity))): ?>
                                        <li><i class="fas fa-check text-success me-2"></i> <?php echo htmlspecialchars(trim($amenity)); ?></li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-bath text-primary me-2"></i> Bathroom Amenities</h5>
                        <div class="row">
                            <?php 
                            $bath_half = ceil(count($bathroom_amenities) / 2);
                            $bath_first_half = array_slice($bathroom_amenities, 0, $bath_half);
                            $bath_second_half = array_slice($bathroom_amenities, $bath_half);
                            ?>
                            <div class="col-6">
                                <ul class="list-unstyled">
                                    <?php foreach ($bath_first_half as $amenity): ?>
                                        <?php if (!empty(trim($amenity))): ?>
                                        <li><i class="fas fa-check text-success me-2"></i> <?php echo htmlspecialchars(trim($amenity)); ?></li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <div class="col-6">
                                <ul class="list-unstyled">
                                    <?php foreach ($bath_second_half as $amenity): ?>
                                        <?php if (!empty(trim($amenity))): ?>
                                        <li><i class="fas fa-check text-success me-2"></i> <?php echo htmlspecialchars(trim($amenity)); ?></li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Services -->
        <div class="row mb-5">
            <div class="col-12">
                <h2 class="text-center mb-4">Additional Services</h2>
            </div>
            <?php foreach ($additional_services as $service): ?>
                <?php 
                $service = trim($service);
                if (empty($service)) continue;
                $icon = $facility_icons[$service] ?? 'fas fa-concierge-bell';
                $description = $facility_descriptions[$service] ?? 'Premium service available for our guests.';
                ?>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="text-center">
                        <i class="<?php echo $icon; ?> fa-3x text-primary mb-3"></i>
                        <h5><?php echo htmlspecialchars($service); ?></h5>
                        <p><?php echo htmlspecialchars($description); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Call to Action -->
        <div class="row">
            <div class="col-12">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center py-5">
                        <h3>Experience Our Premium Facilities</h3>
                        <p class="lead mb-4">Book your stay today and enjoy all our amenities and services.</p>
                        <a href="rooms.php" class="btn btn-light btn-lg me-3">
                            <i class="fas fa-bed"></i> View Rooms
                        </a>
                        <a href="contact.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-phone"></i> Contact Us
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