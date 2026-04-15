-- Jiji-Inspired-1.0 Database Schema

-- Countries for Whitelisting/Blacklisting
CREATE TABLE IF NOT EXISTS countries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    code VARCHAR(3) NOT NULL UNIQUE,
    status ENUM('whitelisted', 'not_specified', 'blacklisted') DEFAULT 'not_specified'
);

-- Nigerian States
CREATE TABLE IF NOT EXISTS states (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

-- Local Government Areas (LGAs)
CREATE TABLE IF NOT EXISTS lgas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    state_id INT,
    name VARCHAR(100) NOT NULL,
    INDEX (state_id),
    FOREIGN KEY (state_id) REFERENCES states(id) ON DELETE CASCADE
);

-- Categories
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    parent_id INT DEFAULT 0,
    icon_class VARCHAR(50),
    slug VARCHAR(50) UNIQUE,
    is_top TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0
);

-- Users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(20),
    password VARCHAR(255),
    is_verified TINYINT(1) DEFAULT 0,
    is_suspended TINYINT(1) DEFAULT 0,
    login_notif TINYINT(1) DEFAULT 1,
    social_id VARCHAR(255) DEFAULT NULL,
    social_provider VARCHAR(50) DEFAULT NULL,
    otp VARCHAR(6) DEFAULT NULL,
    otp_expires_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Admin Users
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    full_name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Ads
CREATE TABLE IF NOT EXISTS ads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    cat_id INT,
    state_id INT,
    lga_id INT,
    title VARCHAR(150),
    description TEXT,
    ad_data JSON DEFAULT NULL,
    price DECIMAL(15, 2),
    listing_type ENUM('for_sale', 'for_swap', 'for_sale_or_swap') DEFAULT 'for_sale',
    estimated_value DECIMAL(15, 2) DEFAULT NULL,
    swap_preference TEXT DEFAULT NULL,
    allow_cash_topup TINYINT(1) DEFAULT 0,
    status ENUM('pending', 'active', 'declined', 'sold', 'swapped', 'expired') DEFAULT 'pending',
    is_featured TINYINT(1) DEFAULT 0,
    views INT DEFAULT 0,
    decline_reason TEXT,
    video_url VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (cat_id),
    INDEX (state_id),
    INDEX (status),
    INDEX (is_featured),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (cat_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (state_id) REFERENCES states(id) ON DELETE SET NULL,
    FOREIGN KEY (lga_id) REFERENCES lgas(id) ON DELETE SET NULL
);

-- Ad Images
CREATE TABLE IF NOT EXISTS ad_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad_id INT,
    image_path VARCHAR(255),
    is_main TINYINT(1) DEFAULT 0,
    FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE
);

-- Internal Messages
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT, -- if 0, it is from admin
    receiver_id INT, -- if 0, it is to admin
    ad_id INT,
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Payments & Boosts
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    ad_id INT,
    amount DECIMAL(15, 2),
    method ENUM('paystack', 'flutterwave', 'bank_transfer') NOT NULL,
    reference VARCHAR(100) UNIQUE,
    status ENUM('pending', 'successful', 'failed') DEFAULT 'pending',
    proof_image VARCHAR(255) DEFAULT NULL, -- For manual bank transfer
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE SET NULL
);

-- Security Logs (Brute Force Protection)
CREATE TABLE IF NOT EXISTS login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    username VARCHAR(100), -- could be email or admin username
    is_admin TINYINT(1) DEFAULT 0,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_success TINYINT(1) DEFAULT 0
);

-- IP Blacklist / Whitelist
CREATE TABLE IF NOT EXISTS ip_security (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) UNIQUE NOT NULL,
    status ENUM('whitelisted', 'blacklisted') NOT NULL,
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- CMS Pages
CREATE TABLE IF NOT EXISTS pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    content LONGTEXT,
    meta_desc TEXT,
    meta_keys TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Blog Posts
CREATE TABLE IF NOT EXISTS blog_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(150) UNIQUE NOT NULL,
    summary TEXT,
    content LONGTEXT,
    image VARCHAR(255) DEFAULT NULL,
    meta_desc TEXT,
    meta_keys TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Swap Proposals
CREATE TABLE IF NOT EXISTS swap_proposals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad_id INT, -- The item being requested
    offered_ad_id INT, -- The item being offered
    sender_id INT,
    receiver_id INT,
    cash_topup DECIMAL(15, 2) DEFAULT 0,
    message TEXT,
    status ENUM('pending', 'accepted', 'declined', 'countered', 'expired') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE,
    FOREIGN KEY (offered_ad_id) REFERENCES ads(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Settings Table (Global configuration)
CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT
);
