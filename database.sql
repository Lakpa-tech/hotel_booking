-- Hotel Booking System Database Schema

CREATE DATABASE IF NOT EXISTS `rin-odge`;
USE `rin-odge`;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    status ENUM('active', 'banned') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Admin users table
CREATE TABLE admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'super_admin') DEFAULT 'admin',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Room categories table
CREATE TABLE room_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Rooms table
CREATE TABLE rooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    category_id INT,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    capacity INT NOT NULL DEFAULT 2,
    size VARCHAR(50),
    bed_type VARCHAR(50),
    image VARCHAR(255),
    gallery TEXT, -- JSON array of image URLs
    amenities TEXT, -- JSON array of amenities
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES room_categories(id) ON DELETE SET NULL
);

-- Room features table
CREATE TABLE room_features (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Room services table
CREATE TABLE room_services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Room feature assignments
CREATE TABLE room_feature_assignments (
    room_id INT,
    feature_id INT,
    PRIMARY KEY (room_id, feature_id),
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (feature_id) REFERENCES room_features(id) ON DELETE CASCADE
);

-- Room service assignments
CREATE TABLE room_service_assignments (
    room_id INT,
    service_id INT,
    PRIMARY KEY (room_id, service_id),
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES room_services(id) ON DELETE CASCADE
);

-- Bookings table
CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id VARCHAR(20) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    room_id INT NOT NULL,
    checkin_date DATE NOT NULL,
    checkout_date DATE NOT NULL,
    guests INT NOT NULL DEFAULT 1,
    nights INT GENERATED ALWAYS AS (DATEDIFF(checkout_date, checkin_date)) STORED,
    room_price DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('confirmed', 'checked_in', 'checked_out', 'completed', 'cancelled') DEFAULT 'confirmed',
    special_requests TEXT,
    arrival_time TIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

-- Reviews table
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    room_id INT NOT NULL,
    booking_id VARCHAR(20) NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    status ENUM('active', 'hidden') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    UNIQUE KEY unique_review (user_id, room_id, booking_id)
);

-- Website settings table
CREATE TABLE website_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Contact messages table
CREATE TABLE contact_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'replied') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Travel packages table
CREATE TABLE travel_packages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    destination VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    duration_days INT NOT NULL DEFAULT 1,
    duration_nights INT NOT NULL DEFAULT 0,
    price DECIMAL(10,2) NOT NULL,
    original_price DECIMAL(10,2) DEFAULT NULL,
    max_people INT NOT NULL DEFAULT 10,
    image VARCHAR(500) DEFAULT NULL,
    gallery TEXT, -- JSON array of image URLs
    inclusions TEXT, -- JSON array of what's included
    exclusions TEXT, -- JSON array of what's excluded
    itinerary TEXT, -- JSON array of day-wise itinerary
    highlights TEXT, -- JSON array of package highlights
    status ENUM('active', 'inactive') DEFAULT 'active',
    featured BOOLEAN DEFAULT FALSE,
    difficulty_level ENUM('easy', 'moderate', 'challenging') DEFAULT 'easy',
    best_season VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Package bookings table
CREATE TABLE package_bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id VARCHAR(20) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    package_id INT NOT NULL,
    travel_date DATE NOT NULL,
    return_date DATE NOT NULL,
    travelers INT NOT NULL DEFAULT 1,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('confirmed', 'completed', 'cancelled') DEFAULT 'confirmed',
    special_requests TEXT,
    contact_phone VARCHAR(20),
    emergency_contact VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES travel_packages(id) ON DELETE CASCADE
);

-- Package reviews table
CREATE TABLE package_reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    package_id INT NOT NULL,
    booking_id VARCHAR(20) NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    status ENUM('active', 'hidden') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES travel_packages(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES package_bookings(booking_id) ON DELETE CASCADE,
    UNIQUE KEY unique_package_review (user_id, package_id, booking_id)
);

-- Insert default admin user (password: admin123)
INSERT INTO admin_users (username, email, password, full_name, role) VALUES 
('admin', 'admin@hotel.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'super_admin');

-- Insert default room categories
INSERT INTO room_categories (name, description) VALUES 
('Standard', 'Comfortable rooms with basic amenities'),
('Deluxe', 'Spacious rooms with premium amenities'),
('Suite', 'Luxury suites with separate living area'),
('Presidential', 'Ultimate luxury with exclusive services');

-- Insert default room features
INSERT INTO room_features (name, icon, description) VALUES 
('Free WiFi', 'fas fa-wifi', 'High-speed internet access'),
('Air Conditioning', 'fas fa-snowflake', 'Climate control system'),
('Mini Bar', 'fas fa-glass-martini', 'Complimentary refreshments'),
('Room Service', 'fas fa-concierge-bell', '24/7 room service available'),
('Balcony', 'fas fa-door-open', 'Private balcony with view'),
('Jacuzzi', 'fas fa-hot-tub', 'Private jacuzzi in room'),
('Safe', 'fas fa-lock', 'In-room safety deposit box'),
('TV', 'fas fa-tv', 'Flat screen TV with cable'),
('Coffee Maker', 'fas fa-coffee', 'In-room coffee making facilities'),
('Workspace', 'fas fa-laptop', 'Dedicated work area with desk');

-- Insert default room services
INSERT INTO room_services (name, description, price) VALUES 
('Laundry Service', 'Professional laundry and dry cleaning', 15.00),
('Airport Transfer', 'Complimentary airport pickup and drop-off', 25.00),
('Spa Services', 'In-room massage and spa treatments', 80.00),
('Extra Bed', 'Additional bed setup in room', 30.00),
('Late Checkout', 'Checkout extension until 3 PM', 20.00),
('Early Checkin', 'Checkin before standard time', 15.00),
('Pet Service', 'Pet care and accommodation', 40.00),
('Babysitting', 'Professional childcare services', 25.00);

-- Insert sample rooms
INSERT INTO rooms (name, category_id, description, price, capacity, size, bed_type, image, featured) VALUES 
('Standard Queen Room', 1, 'Comfortable room with queen bed, perfect for couples. Features modern amenities and city view.', 89.99, 2, '25 sqm', 'Queen Bed', 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', TRUE),
('Deluxe King Suite', 2, 'Spacious suite with king bed and separate seating area. Includes premium amenities and harbor view.', 149.99, 2, '40 sqm', 'King Bed', 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', TRUE),
('Family Suite', 3, 'Large suite perfect for families with two bedrooms and living area. Accommodates up to 4 guests.', 199.99, 4, '60 sqm', '2 Queen Beds', 'https://images.unsplash.com/photo-1590490360182-c33d57733427?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', TRUE),
('Presidential Suite', 4, 'Ultimate luxury suite with panoramic views, private balcony, and exclusive services.', 399.99, 2, '80 sqm', 'King Bed', 'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', TRUE),
('Standard Twin Room', 1, 'Comfortable room with twin beds, ideal for friends or business travelers.', 79.99, 2, '22 sqm', '2 Twin Beds', 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', FALSE),
('Deluxe Ocean View', 2, 'Premium room with stunning ocean views and upgraded amenities.', 179.99, 2, '35 sqm', 'Queen Bed', 'https://images.unsplash.com/photo-1571896349842-33c89424de2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', TRUE),
('Executive Suite', 3, 'Business-class suite with work area and premium amenities for corporate travelers.', 249.99, 2, '45 sqm', 'King Bed', 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', TRUE),
('Honeymoon Suite', 4, 'Romantic suite with jacuzzi, champagne service, and stunning mountain views.', 349.99, 2, '55 sqm', 'King Bed', 'https://images.unsplash.com/photo-1618773928121-c32242e63f39?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', TRUE);

-- Assign features to rooms
INSERT INTO room_feature_assignments (room_id, feature_id) VALUES 
-- Standard Queen Room
(1, 1), (1, 2), (1, 7), (1, 8), (1, 9),
-- Deluxe King Suite  
(2, 1), (2, 2), (2, 3), (2, 4), (2, 5), (2, 7), (2, 8), (2, 9), (2, 10),
-- Family Suite
(3, 1), (3, 2), (3, 3), (3, 4), (3, 7), (3, 8), (3, 9), (3, 10),
-- Presidential Suite
(4, 1), (4, 2), (4, 3), (4, 4), (4, 5), (4, 6), (4, 7), (4, 8), (4, 9), (4, 10),
-- Standard Twin Room
(5, 1), (5, 2), (5, 7), (5, 8), (5, 9),
-- Deluxe Ocean View
(6, 1), (6, 2), (6, 3), (6, 4), (6, 5), (6, 7), (6, 8), (6, 9), (6, 10),
-- Executive Suite
(7, 1), (7, 2), (7, 3), (7, 4), (7, 7), (7, 8), (7, 9), (7, 10),
-- Honeymoon Suite
(8, 1), (8, 2), (8, 3), (8, 4), (8, 5), (8, 6), (8, 7), (8, 8), (8, 9), (8, 10);

-- Assign services to rooms
INSERT INTO room_service_assignments (room_id, service_id) VALUES 
-- All rooms get basic services
(1, 1), (1, 2), (1, 5), (1, 6),
(2, 1), (2, 2), (2, 4), (2, 5), (2, 6), (2, 7),
(3, 1), (3, 2), (3, 4), (3, 5), (3, 6), (3, 7), (3, 8),
(4, 1), (4, 2), (4, 3), (4, 4), (4, 5), (4, 6), (4, 7), (4, 8),
(5, 1), (5, 2), (5, 5), (5, 6),
(6, 1), (6, 2), (6, 3), (6, 4), (6, 5), (6, 6), (6, 7),
(7, 1), (7, 2), (7, 3), (7, 4), (7, 5), (7, 6), (7, 7),
(8, 1), (8, 2), (8, 3), (8, 4), (8, 5), (8, 6), (8, 7), (8, 8);

-- Insert website settings
INSERT INTO website_settings (setting_key, setting_value) VALUES 
('site_name', 'Rin-Odge Hotel'),
('maintenance_mode', '0'),
('contact_email', 'rinchhenhotel@gmail.com'),
('contact_phone', '+977 9746207003'),
('address', 'Fikkal Petrol Pump, Ilam, Nepal'),
('hotel_description', 'Experience luxury and comfort in the heart of Ilam, Nepal. Located at Fikkal Petrol Pump, Rin-Odge Hotel offers a perfect blend of modern amenities and traditional Nepali hospitality.'),
('hotel_mission', 'To provide exceptional hospitality services that exceed our guests expectations while showcasing the beauty and culture of Nepal. We strive to create memorable experiences through personalized service and attention to detail.'),
('hotel_vision', 'To be the leading hotel in the region, known for our commitment to excellence, sustainability, and community engagement. We aim to set new standards in hospitality while preserving local traditions and values.'),
('facebook_url', '#'),
('instagram_url', '#'),
('tiktok_url', '#'),
('google_maps_embed', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d14234.100901417378!2d88.06417!3d26.88682!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39e5cf621f46f601%3A0x3a9828f0eda38579!2sRinchen%20Hotel%20%26%20Lodge!5e0!3m2!1sen!2snp!4v1734789123456!5m2!1sen!2snp'),
('check_in_time', '15:00'),
('check_out_time', '11:00'),
('cancellation_policy', 'Free cancellation up to 24 hours before check-in'),
('hotel_facilities', 'Free WiFi,Restaurant,Free Parking,Room Service,24/7 Security,24/7 Reception'),
('room_amenities', 'Air Conditioning,Comfortable Bedding,Work Desk,Seating Area,Wardrobe,Safe Deposit Box,Blackout Curtains,Daily Housekeeping,Flat Screen TV,Cable Channels,Free WiFi,Phone Service,Power Outlets,USB Charging Ports,Reading Lights,Wake-up Service'),
('bathroom_amenities', 'Private Bathroom,Hot & Cold Water,Shower,Complimentary Toiletries,Fresh Towels,Hair Dryer,Mirror,Bathroom Slippers'),
('additional_services', 'Laundry Service,Airport Transfer,Tour Assistance,Luggage Storage');

-- Insert sample travel packages
INSERT INTO travel_packages (name, destination, description, duration_days, duration_nights, price, original_price, max_people, image, inclusions, exclusions, itinerary, highlights, featured, difficulty_level, best_season) VALUES 
(
    'Queen of Hills - Darjeeling Experience', 
    'Darjeeling, West Bengal', 
    'Discover the enchanting hill station of Darjeeling, known as the Queen of Hills. Experience breathtaking sunrise views from Tiger Hill, ride the famous Darjeeling Himalayan Railway, and explore tea gardens while enjoying the cool mountain air.',
    4, 3, 15999.00, 19999.00, 8,
    'https://images.unsplash.com/photo-1544735716-392fe2489ffa?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    '["Accommodation for 3 nights", "Daily breakfast", "Tiger Hill sunrise tour", "Darjeeling Himalayan Railway ride", "Tea garden visit", "Local sightseeing", "Professional guide"]',
    '["Lunch and dinner", "Personal expenses", "Travel insurance", "Tips and gratuities"]',
    '["Day 1: Arrival and local sightseeing", "Day 2: Tiger Hill sunrise and tea garden visit", "Day 3: Darjeeling Himalayan Railway and shopping", "Day 4: Departure"]',
    '["Tiger Hill sunrise view", "UNESCO World Heritage toy train", "Premium tea tasting", "Himalayan views", "Colonial architecture"]',
    TRUE, 'easy', 'October to March'
),
(
    'Gangtok Capital Adventure', 
    'Gangtok, Sikkim', 
    'Explore the vibrant capital of Sikkim with its perfect blend of tradition and modernity. Visit ancient monasteries, enjoy panoramic mountain views, experience local culture, and taste authentic Sikkimese cuisine.',
    5, 4, 22999.00, 27999.00, 6,
    'https://images.unsplash.com/photo-1626621341517-bbf3d9990a23?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    '["4 nights accommodation", "Daily breakfast", "Monastery visits", "Cable car ride", "Local sightseeing", "Cultural show", "Airport transfers"]',
    '["Lunch and dinner", "Personal shopping", "Adventure activities", "Travel insurance"]',
    '["Day 1: Arrival and MG Road exploration", "Day 2: Tsomgo Lake and Baba Mandir", "Day 3: Monastery tour and cable car", "Day 4: Local markets and cultural show", "Day 5: Departure"]',
    '["Tsomgo Lake visit", "Ancient monasteries", "Ropeway experience", "Local handicrafts", "Mountain cuisine"]',
    TRUE, 'moderate', 'March to June, September to November'
),
(
    'Shree Antu Sunrise Spectacular', 
    'Shree Antu, Ilam', 
    'Witness the most spectacular sunrise in Nepal from Shree Antu, the easternmost point of the country. Experience pristine nature, tea gardens, and panoramic views of the Himalayas including Mount Everest on clear days.',
    3, 2, 8999.00, 11999.00, 12,
    'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    '["2 nights accommodation", "Daily meals", "Sunrise viewpoint visit", "Tea garden tour", "Nature walks", "Local guide", "Transportation"]',
    '["Personal expenses", "Tips", "Travel insurance", "Extra activities"]',
    '["Day 1: Arrival and tea garden exploration", "Day 2: Early morning sunrise at Shree Antu", "Day 3: Nature walk and departure"]',
    '["First sunrise in Nepal", "Himalayan panorama", "Organic tea gardens", "Pristine nature", "Cultural immersion"]',
    TRUE, 'easy', 'October to April'
),
(
    'Eastern Himalayan Circuit', 
    'Darjeeling - Gangtok - Kalimpong', 
    'Complete circuit covering the best of Eastern Himalayas. Experience three distinct hill stations, each with unique charm, culture, and breathtaking mountain views.',
    7, 6, 35999.00, 42999.00, 10,
    'https://images.unsplash.com/photo-1605538883669-825200433431?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    '["6 nights accommodation", "Daily breakfast", "All transfers", "Sightseeing tours", "Professional guide", "Toy train ride", "Monastery visits"]',
    '["Lunch and dinner", "Personal expenses", "Adventure activities", "Shopping"]',
    '["Day 1-2: Darjeeling exploration", "Day 3-4: Gangtok sightseeing", "Day 5-6: Kalimpong visit", "Day 7: Departure"]',
    '["Three hill stations", "Cultural diversity", "Mountain railways", "Monastery visits", "Panoramic views"]',
    FALSE, 'moderate', 'March to May, October to December'
),
(
    'Tea Garden Heritage Trail', 
    'Ilam - Darjeeling', 
    'Discover the heritage of tea cultivation in the Eastern Himalayas. Visit organic tea gardens, learn about tea processing, and enjoy fresh mountain air while staying in heritage properties.',
    4, 3, 18999.00, 23999.00, 8,
    'https://images.unsplash.com/photo-1597318281675-d4b9c2c2c4f4?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    '["Heritage accommodation", "Tea garden tours", "Tea tasting sessions", "Processing unit visits", "Organic farm visits", "Cultural interactions"]',
    '["Personal shopping", "Extra meals", "Tips", "Travel insurance"]',
    '["Day 1: Ilam tea gardens", "Day 2: Processing and tasting", "Day 3: Darjeeling heritage estates", "Day 4: Departure"]',
    '["Organic tea gardens", "Heritage properties", "Tea processing", "Cultural exchange", "Scenic beauty"]',
    FALSE, 'easy', 'March to November'
);

-- Create indexes for better performance on package tables
CREATE INDEX idx_packages_destination ON travel_packages(destination);
CREATE INDEX idx_packages_status ON travel_packages(status);
CREATE INDEX idx_packages_featured ON travel_packages(featured);
CREATE INDEX idx_package_bookings_dates ON package_bookings(travel_date, return_date);
CREATE INDEX idx_package_bookings_status ON package_bookings(status);
CREATE INDEX idx_package_reviews_package ON package_reviews(package_id);
CREATE INDEX idx_package_reviews_rating ON package_reviews(rating);

-- Create indexes for better performance
CREATE INDEX idx_bookings_dates ON bookings(checkin_date, checkout_date);
CREATE INDEX idx_bookings_status ON bookings(status);
CREATE INDEX idx_bookings_user ON bookings(user_id);
CREATE INDEX idx_reviews_room ON reviews(room_id);
CREATE INDEX idx_reviews_rating ON reviews(rating);
CREATE INDEX idx_rooms_status ON rooms(status);
CREATE INDEX idx_rooms_featured ON rooms(featured);
CREATE INDEX idx_users_status ON users(status);
-- Additional indexes for double booking prevention
CREATE INDEX idx_bookings_availability ON bookings(room_id, status, checkin_date, checkout_date);
CREATE INDEX idx_bookings_room_dates ON bookings(room_id, checkin_date, checkout_date) WHERE status IN ('confirmed', 'checked_in');