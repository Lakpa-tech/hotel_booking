<?php
session_start();
require_once 'config/database.php';

// Get hotel name from settings
$stmt = $conn->query("SELECT setting_value FROM website_settings WHERE setting_key = 'site_name'");
$hotel_name = $stmt->fetch()['setting_value'] ?? 'Rin-Odge Hotel';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Mode - <?php echo htmlspecialchars($hotel_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }

        .maintenance-container {
            max-width: 600px;
            width: 100%;
            padding: 2rem;
        }

        .maintenance-card {
            background: white;
            border-radius: 20px;
            padding: 3rem 2rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
            animation: slideUp 0.6s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .maintenance-icon {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            font-size: 3rem;
            color: white;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        h1 {
            color: #1f2937;
            font-weight: 700;
            margin-bottom: 1rem;
            font-size: 2.5rem;
        }

        .maintenance-message {
            color: #6b7280;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .maintenance-text {
            background: #f3f4f6;
            border-left: 4px solid #667eea;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: left;
        }

        .maintenance-text strong {
            color: #1f2937;
            display: block;
            margin-bottom: 0.5rem;
        }

        .maintenance-text p {
            color: #6b7280;
            margin: 0;
            font-size: 0.95rem;
        }

        .contact-info {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e5e7eb;
        }

        .contact-info p {
            color: #6b7280;
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
        }

        .contact-info strong {
            color: #1f2937;
            display: block;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
        }

        .contact-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .contact-link:hover {
            color: #764ba2;
        }

        .return-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin-top: 1.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .return-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            color: white;
        }

        .spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 1rem;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .status-badge {
            display: inline-block;
            background: #fef3c7;
            color: #92400e;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="maintenance-card">
            <div class="status-badge">
                <i class="fas fa-tools me-2"></i>Maintenance In Progress
            </div>

            <div class="maintenance-icon">
                <i class="fas fa-wrench"></i>
            </div>

            <h1>We'll Be Back Soon!</h1>

            <p class="maintenance-message">
                <?php echo htmlspecialchars($hotel_name); ?> is currently undergoing scheduled maintenance. 
                We're working hard to improve your experience and will be back online shortly.
            </p>

            <div class="maintenance-text">
                <strong><i class="fas fa-info-circle me-2"></i>Why the maintenance?</strong>
                <p>We're updating our systems to provide you with better service, improved features, and enhanced security. Thank you for your patience!</p>
            </div>

            <div class="maintenance-text">
                <strong><i class="fas fa-clock me-2"></i>What's happening?</strong>
                <p>
                    During this maintenance period, the following services are temporarily unavailable:
                    <ul style="margin-top: 0.5rem; text-align: left; padding-left: 1.5rem;">
                        <li>Hotel room bookings</li>
                        <li>Travel package reservations</li>
                        <li>Room and package browsing</li>
                    </ul>
                </p>
            </div>

            <div class="contact-info">
                <p>For urgent inquiries or assistance, please contact us:</p>
                <strong>Support Team</strong>
                <p>
                    <i class="fas fa-envelope me-2"></i>
                    <a href="mailto:support@rinodge.com" class="contact-link">support@rinodge.com</a>
                </p>
                <p>
                    <i class="fas fa-phone me-2"></i>
                    <a href="tel:+977123456789" class="contact-link">+977-1-234-5678</a>
                </p>
            </div>

            <a href="javascript:location.reload();" class="return-button">
                <i class="fas fa-sync-alt me-2"></i>Refresh Page
            </a>
        </div>
    </div>

    <script>
        // Optional: Auto-refresh the page every 60 seconds to check if maintenance is over
        // Uncomment the following line if you want auto-refresh
        // setTimeout(() => location.reload(), 60000);
    </script>
</body>
</html>
