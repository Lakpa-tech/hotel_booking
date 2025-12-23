<?php
// Authentication functions
function registerUser($conn, $username, $email, $password, $full_name, $phone) {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, phone, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    return $stmt->execute([$username, $email, $hashed_password, $full_name, $phone]);
}

function loginUser($conn, $username, $password) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'active'");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        return true;
    }
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Room functions
function getFeaturedRooms($conn, $limit = 6) {
    $limit = (int)$limit; // Ensure it's an integer
    $stmt = $conn->prepare("
        SELECT r.*, 
               COALESCE(AVG(rv.rating), 0) as rating,
               COUNT(rv.id) as review_count
        FROM rooms r 
        LEFT JOIN reviews rv ON r.id = rv.room_id 
        WHERE r.status = 'active' 
        GROUP BY r.id 
        ORDER BY r.featured DESC, rating DESC 
        LIMIT " . $limit
    );
    $stmt->execute();
    return $stmt->fetchAll();
}

function getRoomById($conn, $room_id) {
    $stmt = $conn->prepare("
        SELECT r.*, 
               COALESCE(AVG(rv.rating), 0) as rating,
               COUNT(rv.id) as review_count
        FROM rooms r 
        LEFT JOIN reviews rv ON r.id = rv.room_id 
        WHERE r.id = ? AND r.status = 'active'
        GROUP BY r.id
    ");
    $stmt->execute([$room_id]);
    return $stmt->fetch();
}

function searchRooms($conn, $checkin, $checkout, $guests = 1) {
    $stmt = $conn->prepare("
        SELECT r.*, 
               COALESCE(AVG(rv.rating), 0) as rating,
               COUNT(rv.id) as review_count
        FROM rooms r 
        LEFT JOIN reviews rv ON r.id = rv.room_id 
        WHERE r.status = 'active' 
        AND r.capacity >= ?
        AND r.id NOT IN (
            SELECT room_id FROM bookings 
            WHERE status IN ('confirmed', 'checked_in') 
            AND ((checkin_date <= ? AND checkout_date > ?) 
                 OR (checkin_date < ? AND checkout_date >= ?))
        )
        GROUP BY r.id 
        ORDER BY rating DESC
    ");
    $stmt->execute([$guests, $checkin, $checkin, $checkout, $checkout]);
    return $stmt->fetchAll();
}

function isRoomAvailable($conn, $room_id, $checkin, $checkout) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count FROM bookings 
        WHERE room_id = ? 
        AND status IN ('confirmed', 'checked_in') 
        AND ((checkin_date <= ? AND checkout_date > ?) 
             OR (checkin_date < ? AND checkout_date >= ?)
             OR (checkin_date >= ? AND checkin_date < ?))
    ");
    $stmt->execute([$room_id, $checkin, $checkin, $checkout, $checkout, $checkin, $checkout]);
    $result = $stmt->fetch();
    return $result['count'] == 0;
}

// Enhanced booking function with double booking prevention
function createBooking($conn, $user_id, $room_id, $checkin, $checkout, $guests, $total_amount) {
    try {
        // Start transaction for atomic operation
        $conn->beginTransaction();
        
        // Lock the room for reading to prevent race conditions
        $stmt = $conn->prepare("SELECT id, name, status FROM rooms WHERE id = ? FOR UPDATE");
        $stmt->execute([$room_id]);
        $room = $stmt->fetch();
        
        if (!$room) {
            $conn->rollback();
            return ['error' => 'Room not found'];
        }
        
        if ($room['status'] !== 'active') {
            $conn->rollback();
            return ['error' => 'Room is not available for booking'];
        }
        
        // Double-check availability with lock to prevent race conditions
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count FROM bookings 
            WHERE room_id = ? 
            AND status IN ('confirmed', 'checked_in') 
            AND ((checkin_date <= ? AND checkout_date > ?) 
                 OR (checkin_date < ? AND checkout_date >= ?)
                 OR (checkin_date >= ? AND checkin_date < ?))
            FOR UPDATE
        ");
        $stmt->execute([$room_id, $checkin, $checkin, $checkout, $checkout, $checkin, $checkout]);
        $availability = $stmt->fetch();
        
        if ($availability['count'] > 0) {
            $conn->rollback();
            return ['error' => 'Room is no longer available for the selected dates. Please choose different dates.'];
        }
        
        // Generate unique booking ID
        $booking_id = 'BK' . date('Ymd') . rand(1000, 9999);
        
        // Ensure booking ID is unique
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE booking_id = ?");
        $stmt->execute([$booking_id]);
        while ($stmt->fetch()['count'] > 0) {
            $booking_id = 'BK' . date('Ymd') . rand(1000, 9999);
            $stmt->execute([$booking_id]);
        }
        
        // Get room price
        $stmt = $conn->prepare("SELECT price FROM rooms WHERE id = ?");
        $stmt->execute([$room_id]);
        $room_data = $stmt->fetch();
        $room_price = $room_data ? $room_data['price'] : 0;
        
        // Create the booking
        $stmt = $conn->prepare("
            INSERT INTO bookings (booking_id, user_id, room_id, checkin_date, checkout_date, guests, room_price, total_amount, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', NOW())
        ");
        
        if ($stmt->execute([$booking_id, $user_id, $room_id, $checkin, $checkout, $guests, $room_price, $total_amount])) {
            // Commit the transaction
            $conn->commit();
            return $booking_id;
        } else {
            $conn->rollback();
            return ['error' => 'Failed to create booking'];
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        return ['error' => 'Booking failed: ' . $e->getMessage()];
    }
}

function getUserBookings($conn, $user_id) {
    $stmt = $conn->prepare("
        SELECT b.*, r.name as room_name, r.image as room_image 
        FROM bookings b 
        JOIN rooms r ON b.room_id = r.id 
        WHERE b.user_id = ? 
        ORDER BY b.created_at DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function getBookingById($conn, $booking_id, $user_id = null) {
    $sql = "
        SELECT b.*, r.name as room_name, r.image as room_image, r.price as room_price,
               u.full_name as user_name, u.email as user_email, u.phone as user_phone
        FROM bookings b 
        JOIN rooms r ON b.room_id = r.id 
        JOIN users u ON b.user_id = u.id
        WHERE b.booking_id = ?
    ";
    
    $params = [$booking_id];
    if ($user_id) {
        $sql .= " AND b.user_id = ?";
        $params[] = $user_id;
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

function cancelBooking($conn, $booking_id, $user_id) {
    $stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = ? AND user_id = ? AND status = 'confirmed'");
    return $stmt->execute([$booking_id, $user_id]);
}

// Review functions
function addReview($conn, $user_id, $room_id, $booking_id, $rating, $comment) {
    // Check if user has booked this room
    $stmt = $conn->prepare("SELECT id FROM bookings WHERE booking_id = ? AND user_id = ? AND room_id = ? AND status IN ('completed', 'checked_out')");
    $stmt->execute([$booking_id, $user_id, $room_id]);
    
    if (!$stmt->fetch()) {
        return false;
    }
    
    // Check if review already exists
    $stmt = $conn->prepare("SELECT id FROM reviews WHERE user_id = ? AND room_id = ? AND booking_id = ?");
    $stmt->execute([$user_id, $room_id, $booking_id]);
    
    if ($stmt->fetch()) {
        return false; // Review already exists
    }
    
    $stmt = $conn->prepare("INSERT INTO reviews (user_id, room_id, booking_id, rating, comment, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    return $stmt->execute([$user_id, $room_id, $booking_id, $rating, $comment]);
}

function getRoomReviews($conn, $room_id, $limit = 10) {
    $limit = (int)$limit; // Ensure it's an integer
    $stmt = $conn->prepare("
        SELECT r.*, u.full_name as user_name 
        FROM reviews r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.room_id = ? 
        ORDER BY r.created_at DESC 
        LIMIT " . $limit
    );
    $stmt->execute([$room_id]);
    return $stmt->fetchAll();
}

function getUserReviews($conn, $user_id) {
    $stmt = $conn->prepare("
        SELECT r.*, rm.name as room_name 
        FROM reviews r 
        JOIN rooms rm ON r.room_id = rm.id 
        WHERE r.user_id = ? 
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

// Utility functions
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

function calculateNights($checkin, $checkout) {
    $checkin_date = new DateTime($checkin);
    $checkout_date = new DateTime($checkout);
    return $checkout_date->diff($checkin_date)->days;
}

function generateStars($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        $class = $i <= $rating ? 'text-warning' : 'text-muted';
        $stars .= '<i class="fas fa-star ' . $class . '"></i>';
    }
    return $stars;
}

// Admin functions
function isAdmin() {
    return isset($_SESSION['admin_id']);
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: admin/login.php');
        exit();
    }
}
// Package functions
function getFeaturedPackages($conn, $limit = 6) {
    $limit = (int)$limit;
    $stmt = $conn->prepare("
        SELECT p.*, 
               COALESCE(AVG(pr.rating), 0) as rating,
               COUNT(pr.id) as review_count
        FROM travel_packages p 
        LEFT JOIN package_reviews pr ON p.id = pr.package_id 
        WHERE p.status = 'active' 
        GROUP BY p.id 
        ORDER BY p.featured DESC, rating DESC 
        LIMIT " . $limit
    );
    $stmt->execute();
    return $stmt->fetchAll();
}

function updatePackageBookingStatus($conn, $booking_id = null) {
    $today = date('Y-m-d');
    
    // Build WHERE clause
    $where = "WHERE status IN ('confirmed', 'in_progress')";
    $params = [];
    
    if ($booking_id) {
        $where .= " AND booking_id = ?";
        $params[] = $booking_id;
    }
    
    // Update to in_progress if travel date is today or past
    $stmt = $conn->prepare("
        UPDATE package_bookings 
        SET status = 'in_progress', updated_at = NOW() 
        $where AND travel_date <= ? AND status = 'confirmed'
    ");
    $stmt->execute(array_merge($params, [$today]));
    
    // Update to completed if return date is past
    $stmt = $conn->prepare("
        UPDATE package_bookings 
        SET status = 'completed', updated_at = NOW() 
        $where AND return_date < ? AND status = 'in_progress'
    ");
    $stmt->execute(array_merge($params, [$today]));
    
    return true;
}

function getPackageBookingWithRealTimeStatus($conn, $booking_id, $user_id = null) {
    // Update status first
    updatePackageBookingStatus($conn, $booking_id);
    
    // Then get the booking
    return getPackageBookingById($conn, $booking_id, $user_id);
}

function getPackageById($conn, $package_id) {
    $stmt = $conn->prepare("
        SELECT p.*, 
               COALESCE(AVG(pr.rating), 0) as rating,
               COUNT(pr.id) as review_count
        FROM travel_packages p 
        LEFT JOIN package_reviews pr ON p.id = pr.package_id 
        WHERE p.id = ? AND p.status = 'active'
        GROUP BY p.id
    ");
    $stmt->execute([$package_id]);
    return $stmt->fetch();
}

function createPackageBooking($conn, $user_id, $package_id, $travel_date, $return_date, $travelers, $total_amount, $contact_phone = '', $emergency_contact = '') {
    $booking_id = 'PK' . date('Ymd') . rand(1000, 9999);
    
    $stmt = $conn->prepare("
        INSERT INTO package_bookings (booking_id, user_id, package_id, travel_date, return_date, travelers, total_amount, contact_phone, emergency_contact, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', NOW())
    ");
    
    if ($stmt->execute([$booking_id, $user_id, $package_id, $travel_date, $return_date, $travelers, $total_amount, $contact_phone, $emergency_contact])) {
        return $booking_id;
    }
    return false;
}

function getUserPackageBookings($conn, $user_id) {
    // Update all statuses first
    updatePackageBookingStatus($conn);
    
    $stmt = $conn->prepare("
        SELECT pb.*, p.name as package_name, p.image as package_image, p.destination 
        FROM package_bookings pb 
        JOIN travel_packages p ON pb.package_id = p.id 
        WHERE pb.user_id = ? 
        ORDER BY pb.created_at DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function getPackageBookingById($conn, $booking_id, $user_id = null) {
    $sql = "
        SELECT pb.*, p.name as package_name, p.image as package_image, p.destination, p.duration_days, p.duration_nights,
               u.full_name as user_name, u.email as user_email, u.phone as user_phone
        FROM package_bookings pb 
        JOIN travel_packages p ON pb.package_id = p.id 
        JOIN users u ON pb.user_id = u.id
        WHERE pb.booking_id = ?
    ";
    
    $params = [$booking_id];
    if ($user_id) {
        $sql .= " AND pb.user_id = ?";
        $params[] = $user_id;
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

function addPackageReview($conn, $user_id, $package_id, $booking_id, $rating, $comment) {
    // Check if user has booked this package
    $stmt = $conn->prepare("SELECT id FROM package_bookings WHERE booking_id = ? AND user_id = ? AND package_id = ? AND status = 'completed'");
    $stmt->execute([$booking_id, $user_id, $package_id]);
    
    if (!$stmt->fetch()) {
        return false;
    }
    
    // Check if review already exists
    $stmt = $conn->prepare("SELECT id FROM package_reviews WHERE user_id = ? AND package_id = ? AND booking_id = ?");
    $stmt->execute([$user_id, $package_id, $booking_id]);
    
    if ($stmt->fetch()) {
        return false; // Review already exists
    }
    
    $stmt = $conn->prepare("INSERT INTO package_reviews (user_id, package_id, booking_id, rating, comment, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    return $stmt->execute([$user_id, $package_id, $booking_id, $rating, $comment]);
}

function getPackageReviews($conn, $package_id, $limit = 10) {
    $limit = (int)$limit;
    $stmt = $conn->prepare("
        SELECT pr.*, u.full_name as user_name 
        FROM package_reviews pr 
        JOIN users u ON pr.user_id = u.id 
        WHERE pr.package_id = ? AND pr.status = 'active'
        ORDER BY pr.created_at DESC 
        LIMIT " . $limit
    );
    $stmt->execute([$package_id]);
    return $stmt->fetchAll();
}

function getUserPackageReviews($conn, $user_id) {
    $stmt = $conn->prepare("
        SELECT pr.*, p.name as package_name, p.destination 
        FROM package_reviews pr 
        JOIN travel_packages p ON pr.package_id = p.id 
        WHERE pr.user_id = ? 
        ORDER BY pr.created_at DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}
// Maintenance Mode Helper Functions
function getMaintenanceAwareTitle($title, $website_settings = null) {
    global $conn;
    
    if (!$website_settings) {
        $stmt = $conn->query("SELECT setting_value FROM website_settings WHERE setting_key = 'maintenance_mode'");
        $maintenance_setting = $stmt->fetch();
        $is_maintenance = $maintenance_setting && $maintenance_setting['setting_value'] == '1';
    } else {
        $is_maintenance = !empty($website_settings['maintenance_mode']) && $website_settings['maintenance_mode'];
    }
    
    if ($is_maintenance) {
        return "🚧 " . $title . " (Maintenance Mode)";
    }
    
    return $title;
}

function addMaintenanceMetaTags($website_settings = null) {
    global $conn;
    
    if (!$website_settings) {
        $stmt = $conn->query("SELECT setting_value FROM website_settings WHERE setting_key = 'maintenance_mode'");
        $maintenance_setting = $stmt->fetch();
        $is_maintenance = $maintenance_setting && $maintenance_setting['setting_value'] == '1';
    } else {
        $is_maintenance = !empty($website_settings['maintenance_mode']) && $website_settings['maintenance_mode'];
    }
    
    if ($is_maintenance) {
        echo '<meta name="robots" content="noindex, nofollow">' . "\n";
        echo '<meta name="maintenance-mode" content="active">' . "\n";
        echo '<script>
            // Add maintenance mode indicator to browser tab
            document.addEventListener("DOMContentLoaded", function() {
                const favicon = document.querySelector("link[rel*=\'icon\']") || document.createElement("link");
                favicon.type = "image/x-icon";
                favicon.rel = "shortcut icon";
                favicon.href = "data:image/svg+xml,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 100 100\'><text y=\'.9em\' font-size=\'90\'>🚧</text></svg>";
                document.getElementsByTagName("head")[0].appendChild(favicon);
                
                // Add blinking effect to title
                let originalTitle = document.title;
                let isBlinking = false;
                setInterval(function() {
                    if (isBlinking) {
                        document.title = originalTitle;
                    } else {
                        document.title = "🚧 MAINTENANCE 🚧";
                    }
                    isBlinking = !isBlinking;
                }, 2000);
            });
        </script>' . "\n";
    }
}