CREATE DATABASE IF NOT EXISTS archer_store;
USE archer_store;

CREATE TABLE IF NOT EXISTS store_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT
);

INSERT IGNORE INTO store_settings (setting_key, setting_value) VALUES
('store_name', 'Archer Grocery'),
('hero_title', 'Freshness You Can Trust'),
('hero_subtitle', 'Locally sourced, organic, and everyday essentials delivered to your community.'),
('about_title', 'Our Story'),
('about_text', 'Since 1998, Archer Grocery has been the heart of the community, providing fresh, locally sourced produce and everyday essentials. We believe in quality, sustainability, and supporting our local farmers.'),
('facebook_link', 'https://facebook.com/archergrocery'),
('instagram_link', 'https://instagram.com/archergrocery'),
('phone_number', '+1 (555) 123-4567'),
('email', 'contact@archergrocery.com');

CREATE TABLE IF NOT EXISTS store_schedule (
    day_of_week INT PRIMARY KEY, -- 0 = Sunday, 1 = Monday, etc.
    is_open BOOLEAN DEFAULT TRUE,
    open_time TIME,
    close_time TIME
);

INSERT IGNORE INTO store_schedule (day_of_week, is_open, open_time, close_time) VALUES
(1, 1, '08:00:00', '22:00:00'), -- Monday
(2, 1, '08:00:00', '22:00:00'), -- Tuesday
(3, 1, '08:00:00', '22:00:00'), -- Wednesday
(4, 1, '08:00:00', '22:00:00'), -- Thursday
(5, 1, '08:00:00', '23:00:00'), -- Friday
(6, 1, '09:00:00', '23:00:00'), -- Saturday
(0, 1, '09:00:00', '20:00:00'); -- Sunday

CREATE TABLE IF NOT EXISTS promos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image_url VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    valid_until DATETIME
);

INSERT IGNORE INTO promos (id, title, description, image_url, is_active, valid_until) VALUES
(1, 'Weekend Fruit Fiesta', 'Get 20% off on all organic fruits this weekend!', 'promo1.webp', 1, '2026-12-31 23:59:59'),
(2, 'Dairy Delight', 'Buy 2 Get 1 Free on selected dairy products.', 'promo2.webp', 1, '2026-12-31 23:59:59');

CREATE TABLE IF NOT EXISTS branches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    google_maps_embed TEXT,
    is_active BOOLEAN DEFAULT TRUE
);

INSERT IGNORE INTO branches (id, name, address, google_maps_embed, is_active) VALUES
(1, 'Downtown Archer', '123 Market Street, City Center', '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3151.83543450937!2d144.95373531531615!3d-37.816279742021665!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x6ad65d4c2b349649%3A0xb6899234e561db11!2sEnvato!5e0!3m2!1sen!2sus!4v1620131464303!5m2!1sen!2sus" width="100%" height="300" style="border:0;" allowfullscreen="" loading="lazy"></iframe>', 1),
(2, 'Westside Plaza', '456 West Avenue, Shopping District', '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3151.83543450937!2d144.95373531531615!3d-37.816279742021665!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x6ad65d4c2b349649%3A0xb6899234e561db11!2sEnvato!5e0!3m2!1sen!2sus!4v1620131464303!5m2!1sen!2sus" width="100%" height="300" style="border:0;" allowfullscreen="" loading="lazy"></iframe>', 1);
