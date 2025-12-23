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
    'contact_email' => 'rinchhenhotel@gmail.com',
    'contact_phone' => '+977 9746207003',
    'address' => 'Fikkal Petrol Pump, Ilam, Nepal',
    'google_maps_embed' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3532.123456789!2d87.9123456!3d26.9123456!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMjbCsDU0JzQ0LjQiTiA4N8KwNTQnNDQuNCJF!5e0!3m2!1sen!2snp!4v1234567890123!5m2!1sen!2snp'
];

$settings = array_merge($defaults, $settings);

$success = '';
$error = '';

if ($_POST) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Please fill in all fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())");
        if ($stmt->execute([$name, $email, $subject, $message])) {
            $success = 'Thank you for your message! We will get back to you soon.';
            $_POST = []; // Clear form
        } else {
            $error = 'Failed to send message. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Hotel Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-12 mb-4">
                <h1><i class="fas fa-envelope"></i> Contact Us</h1>
                <p class="text-muted">Get in touch with us for any questions or assistance</p>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Send us a Message</h5>
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" name="name" 
                                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" class="form-control" name="email" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Subject</label>
                                <input type="text" class="form-control" name="subject" 
                                       value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Message</label>
                                <textarea class="form-control" name="message" rows="6" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Send Message
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Contact Information</h5>
                        
                        <div class="contact-item mb-3">
                            <i class="fas fa-map-marker-alt text-primary me-3"></i>
                            <div>
                                <strong>Address</strong><br>
                                <?php echo nl2br(htmlspecialchars($settings['address'])); ?>
                            </div>
                        </div>
                        
                        <div class="contact-item mb-3">
                            <i class="fas fa-phone text-primary me-3"></i>
                            <div>
                                <strong>Phone</strong><br>
                                <?php echo htmlspecialchars($settings['contact_phone']); ?>
                            </div>
                        </div>
                        
                        <div class="contact-item mb-3">
                            <i class="fas fa-envelope text-primary me-3"></i>
                            <div>
                                <strong>Email</strong><br>
                                <?php echo htmlspecialchars($settings['contact_email']); ?>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <i class="fas fa-clock text-primary me-3"></i>
                            <div>
                                <strong>Office Hours</strong><br>
                                24/7 Available
                            </div>
                        </div>
                    </div>
                </div>

                </div>
            </div>
        </div>

        <!-- Map Section -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Find Us</h5>
                        <div class="mb-3">
                            <a href="https://maps.app.goo.gl/tW9UeZjrXrzQcLhHA" target="_blank" class="btn btn-primary">
                                <i class="fas fa-map-marker-alt me-2"></i>View on Google Maps
                            </a>
                        </div>
                        <div class="map-box">
                            <iframe src="<?php echo htmlspecialchars($settings['google_maps_embed']); ?>" 
                                    width="100%" height="450" style="border:0;" allowfullscreen="" 
                                    loading="lazy" referrerpolicy="no-referrer-when-downgrade">
                            </iframe>
                        </div>
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