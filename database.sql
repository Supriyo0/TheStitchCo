-- Master Database Schema for The Stitch Co.
CREATE DATABASE IF NOT EXISTS `the_stitch_co` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `the_stitch_co`;

-- 1. Users & Admins
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `fullname` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `phone` VARCHAR(50) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('customer', 'admin', 'super_admin') DEFAULT 'customer',
    `status` ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    `avatar` VARCHAR(255) DEFAULT NULL,
    `must_change_password` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Customer Addresses
CREATE TABLE IF NOT EXISTS `user_addresses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `fullname` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(50) NOT NULL,
    `address_line1` VARCHAR(255) NOT NULL,
    `address_line2` VARCHAR(255) DEFAULT NULL,
    `landmark` VARCHAR(255) DEFAULT NULL,
    `city` VARCHAR(100) NOT NULL,
    `state` VARCHAR(100) NOT NULL,
    `pincode` VARCHAR(20) NOT NULL,
    `country` VARCHAR(100) DEFAULT 'India',
    `address_type` ENUM('home', 'work', 'other') DEFAULT 'home',
    `is_default` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Categories
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `cat_key` VARCHAR(50) NOT NULL UNIQUE,
    `cat_name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `icon` VARCHAR(50) DEFAULT 'tag',
    `is_active` TINYINT(1) DEFAULT 1,
    `display_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Subcategories
CREATE TABLE IF NOT EXISTS `subcategories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NOT NULL,
    `subcat_key` VARCHAR(50) NOT NULL,
    `subcat_name` VARCHAR(100) NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Products
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `sku` VARCHAR(100) NOT NULL UNIQUE,
    `category_id` INT DEFAULT NULL,
    `category` VARCHAR(50) NOT NULL,
    `subcategory` VARCHAR(50) DEFAULT '',
    `description` TEXT DEFAULT NULL,
    `short_description` VARCHAR(500) DEFAULT NULL,
    `fabric` VARCHAR(255) DEFAULT '100% Super Combed Cotton | 240 GSM Bio Wash',
    `mrp` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `delivery_charge` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `price` DECIMAL(10,2) NOT NULL,
    `discount_percent` INT DEFAULT 0,
    `stock` INT NOT NULL DEFAULT 50,
    `rating` DECIMAL(2,1) DEFAULT 4.8,
    `review_count` INT DEFAULT 120,
    `sizes_json` TEXT DEFAULT NULL, -- JSON array of sizes: ["S", "M", "L", "XL", "XXL"]
    `colors_json` TEXT DEFAULT NULL, -- JSON array of color objects: [{"name":"Black","hex":"#111111"},{"name":"Beige","hex":"#D9C5B2"}]
    `images_json` TEXT DEFAULT NULL, -- JSON array of image URLs
    `thumbnail` VARCHAR(255) DEFAULT NULL,
    `badge` VARCHAR(100) DEFAULT 'Best Seller',
    `is_featured` TINYINT(1) DEFAULT 0,
    `is_best_seller` TINYINT(1) DEFAULT 0,
    `is_new_arrival` TINYINT(1) DEFAULT 0,
    `is_hero` TINYINT(1) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (`category`),
    INDEX (`is_active`),
    INDEX (`is_best_seller`),
    INDEX (`is_new_arrival`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Hero Banners & Promo Sections
CREATE TABLE IF NOT EXISTS `hero_banners` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `subtitle` VARCHAR(255) DEFAULT NULL,
    `tag` VARCHAR(100) DEFAULT 'NEW ARRIVALS',
    `badge_text` VARCHAR(100) DEFAULT '180-240 GSM | 100% Cotton',
    `button_text` VARCHAR(100) DEFAULT 'SHOP NOW',
    `button_url` VARCHAR(255) DEFAULT 'shop.php',
    `image` VARCHAR(255) NOT NULL,
    `mobile_image` VARCHAR(255) DEFAULT NULL,
    `display_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Coupons
CREATE TABLE IF NOT EXISTS `coupons` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `description` VARCHAR(255) DEFAULT NULL,
    `discount_type` ENUM('percentage', 'fixed') DEFAULT 'percentage',
    `discount_value` DECIMAL(10,2) NOT NULL,
    `min_cart_amount` DECIMAL(10,2) DEFAULT 0.00,
    `max_discount` DECIMAL(10,2) DEFAULT NULL,
    `usage_limit` INT DEFAULT 1000,
    `used_count` INT DEFAULT 0,
    `per_user_limit` INT DEFAULT 1,
    `start_date` DATE DEFAULT NULL,
    `expiry_date` DATE DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Coupon Usage
CREATE TABLE IF NOT EXISTS `coupon_usage` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `coupon_id` INT NOT NULL,
    `user_id` INT DEFAULT NULL,
    `order_id` INT NOT NULL,
    `discount_amount` DECIMAL(10,2) NOT NULL,
    `used_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`coupon_id`) REFERENCES `coupons`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Cart & Cart Items
CREATE TABLE IF NOT EXISTS `carts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `session_id` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (`session_id`),
    INDEX (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cart_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `cart_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `size` VARCHAR(20) DEFAULT 'M',
    `color` VARCHAR(50) DEFAULT 'Black',
    `quantity` INT NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`cart_id`) REFERENCES `carts`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Wishlists
CREATE TABLE IF NOT EXISTS `wishlists` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `user_product` (`user_id`, `product_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Orders
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_number` VARCHAR(50) NOT NULL UNIQUE,
    `customer_id` INT NOT NULL,
    `customer_name` VARCHAR(255) NOT NULL,
    `customer_email` VARCHAR(255) NOT NULL,
    `customer_phone` VARCHAR(50) NOT NULL,
    `subtotal` DECIMAL(10,2) NOT NULL,
    `discount_amount` DECIMAL(10,2) DEFAULT 0.00,
    `coupon_code` VARCHAR(50) DEFAULT NULL,
    `shipping_fee` DECIMAL(10,2) DEFAULT 0.00,
    `shipping_method` VARCHAR(50) DEFAULT 'Standard Shipping (3-5 Days)',
    `total_price` DECIMAL(10,2) NOT NULL,
    `status` ENUM('Order Placed', 'Confirmed', 'Processing', 'Packed', 'Shipped', 'Out for Delivery', 'Delivered', 'Cancelled') DEFAULT 'Order Placed',
    `payment_method` VARCHAR(50) DEFAULT 'UPI (Scan & Pay)',
    `payment_status` ENUM('Pending', 'Paid', 'Failed', 'Refunded') DEFAULT 'Pending',
    `shipping_address` TEXT NOT NULL,
    `billing_address` TEXT DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `admin_note` TEXT DEFAULT NULL,
    `cancel_reason` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (`customer_id`),
    INDEX (`status`),
    INDEX (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. Order Items
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `product_name` VARCHAR(255) NOT NULL,
    `sku` VARCHAR(100) DEFAULT NULL,
    `size` VARCHAR(20) DEFAULT 'M',
    `color` VARCHAR(50) DEFAULT 'Black',
    `image` VARCHAR(255) DEFAULT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `total` DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. Order Status History
CREATE TABLE IF NOT EXISTS `order_status_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `previous_status` VARCHAR(50) DEFAULT NULL,
    `new_status` VARCHAR(50) NOT NULL,
    `comment` TEXT DEFAULT NULL,
    `changed_by` VARCHAR(100) DEFAULT 'System',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. Payments & Payment Proofs
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `customer_id` INT NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `payment_method` VARCHAR(50) NOT NULL DEFAULT 'UPI',
    `utr_number` VARCHAR(100) DEFAULT NULL,
    `proof_screenshot` VARCHAR(255) DEFAULT NULL,
    `customer_note` TEXT DEFAULT NULL,
    `status` ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    `admin_notes` TEXT DEFAULT NULL,
    `reviewed_by` INT DEFAULT NULL,
    `reviewed_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 15. Shipping Details
CREATE TABLE IF NOT EXISTS `shipping_details` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL UNIQUE,
    `courier_name` VARCHAR(100) DEFAULT 'Delhivery',
    `tracking_number` VARCHAR(100) DEFAULT NULL,
    `tracking_url` VARCHAR(500) DEFAULT NULL,
    `shipped_date` DATETIME DEFAULT NULL,
    `estimated_delivery` DATETIME DEFAULT NULL,
    `shipping_notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 16. Store Settings
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_val` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 17. Internal Notifications
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL, -- NULL means for all Admins
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `type` ENUM('order', 'payment', 'stock', 'general') DEFAULT 'order',
    `link` VARCHAR(255) DEFAULT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 18. Admin Audit Logs
CREATE TABLE IF NOT EXISTS `admin_audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT NOT NULL,
    `admin_name` VARCHAR(100) NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `details` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default Settings Seeding
INSERT INTO `settings` (`setting_key`, `setting_val`) VALUES
('store_name', 'The Stitch Co.'),
('store_tagline', 'Wear Your Vibe'),
('store_email', 'thestitchco.official@gmail.com'),
('store_phone', '7063179581'),
('store_whatsapp', '7047051581'),
('store_address', 'Sisir Building, Jetty Ghat Bus Stopage, Fraserganj, South 24 Parganas, West Bengal, India, 743357'),
('gstin', '19GWPPD6451K1ZV'),
('upi_id', 'thestitchco@upi'),
('upi_merchant_name', 'The Stitch Co.'),
('upi_qr_image', 'assets/images/upi_qr.svg'),
('standard_shipping_fee', '0.00'),
('express_shipping_fee', '99.00'),
('free_shipping_threshold', '999.00'),
('welcome_coupon_code', 'WELCOME10'),
('welcome_coupon_discount', '10'),
('announcement_bar_enabled', '1'),
('announcement_bar_text', 'FREE SHIPPING ON PREPAID ORDERS ABOVE ₹999 🚚 &nbsp;|&nbsp; USE CODE <strong>WELCOME10</strong> FOR 10% OFF')
ON DUPLICATE KEY UPDATE `setting_val` = VALUES(`setting_val`);

-- Seed Default Admins (Master password is '123456')
INSERT INTO `users` (`fullname`, `email`, `phone`, `password_hash`, `role`, `status`) VALUES
('Super Administrator', 'sd029900@gmail.com', '7063179581', '$2y$10$wT3QyFj3qUe0a6dYJ8k5qOPm1Lg4mJ6s2B9dJ9YV4hD1N1o1G1E1q', 'super_admin', 'active')
ON DUPLICATE KEY UPDATE `password_hash` = VALUES(`password_hash`);

-- Seed Categories
INSERT INTO `categories` (`id`, `cat_key`, `cat_name`, `description`, `icon`, `display_order`) VALUES
(1, 'tshirts', 'T-Shirts', 'Classic and graphic standard fit tees', 'shirt', 1),
(2, 'oversized', 'Oversized', 'Premium 240 GSM heavy oversized streetwear tees', 'layers', 2),
(3, 'polo', 'Polo T-Shirts', 'Structured collar and knit textured polos', 'briefcase', 3),
(4, 'hoodies', 'Hoodies', '350 GSM heavyweight fleece hoodies & sweatshirts', 'feather', 4),
(5, 'new_arrivals', 'New Arrivals', 'Latest fresh drops & limited edition releases', 'zap', 5)
ON DUPLICATE KEY UPDATE `cat_name` = VALUES(`cat_name`);

-- Seed Coupons
INSERT INTO `coupons` (`id`, `code`, `description`, `discount_type`, `discount_value`, `min_cart_amount`, `max_discount`, `is_active`) VALUES
(1, 'WELCOME10', 'Get 10% OFF on your first order', 'percentage', 10.00, 499.00, 300.00, 1),
(2, 'STITCH100', 'Flat ₹100 OFF on orders above ₹999', 'fixed', 100.00, 999.00, 100.00, 1),
(3, 'VIBE20', 'Special 20% OFF for Streetwear VIPs', 'percentage', 20.00, 1499.00, 500.00, 1)
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);

-- Seed Default Customer Address
INSERT INTO `user_addresses` (`id`, `user_id`, `fullname`, `phone`, `address_line1`, `address_line2`, `landmark`, `city`, `state`, `pincode`, `is_default`) VALUES
(1, 3, 'Souvik Sayan Das', '+91 98765 43210', 'Vill - Fraserganj, PO - Fraserganj', 'P.S: Fraserganj Coastal', 'Near Sea Beach', 'South 24 Parganas', 'West Bengal', '743315', 1)
ON DUPLICATE KEY UPDATE `fullname` = VALUES(`fullname`);
