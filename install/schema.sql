-- Classifieds Database Schema v2 (April 2026)

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
    slug VARCHAR(255) UNIQUE,
    is_top TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0
);

-- Users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100),
    business_name VARCHAR(100) DEFAULT NULL,
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(20),
    password VARCHAR(255),
    is_verified TINYINT(1) DEFAULT 0,
    verification_tier ENUM('phone_verified', 'nin_verified', 'business_verified') DEFAULT 'phone_verified',
    nin_number VARCHAR(11) DEFAULT NULL,
    kyc_reference VARCHAR(100) DEFAULT NULL,
    last_verified_at DATETIME DEFAULT NULL,
    verification_fails INT DEFAULT 0,
    locked_until DATETIME DEFAULT NULL,
    is_suspended TINYINT(1) DEFAULT 0,
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
    status ENUM('pending', 'active', 'declined', 'sold', 'swapped', 'expired', 'moderation') DEFAULT 'pending',
    safety_score INT DEFAULT 50,
    is_featured TINYINT(1) DEFAULT 0,
    views INT DEFAULT 0,
    decline_reason TEXT,
    expires_at TIMESTAMP NULL DEFAULT NULL,
    bumped_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (cat_id),
    INDEX (state_id),
    INDEX (status),
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

-- Image Hashes
CREATE TABLE IF NOT EXISTS image_hashes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad_id INT,
    user_id INT,
    phash VARCHAR(64) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (phash),
    FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE
);

-- Property Declarations
CREATE TABLE IF NOT EXISTS property_declarations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad_id INT NOT NULL,
    user_id INT NOT NULL,
    declared_role ENUM('owner', 'agent') NOT NULL,
    verified TINYINT(1) DEFAULT 0,
    verification_method VARCHAR(50) DEFAULT NULL,
    verified_at DATETIME DEFAULT NULL,
    verified_by_admin_id INT DEFAULT NULL,
    FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE
);

-- Reviews
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reviewer_id INT NOT NULL,
    seller_id INT NOT NULL,
    stars TINYINT NOT NULL,
    tags JSON DEFAULT NULL,
    body TEXT,
    reply_text TEXT DEFAULT NULL,
    reply_at TIMESTAMP NULL DEFAULT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    challenged TINYINT(1) DEFAULT 0,
    challenge_resolved_at DATETIME DEFAULT NULL,
    removed TINYINT(1) DEFAULT 0,
    removed_by INT DEFAULT NULL,
    remove_reason TEXT,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Settings Table
CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT
);

-- Admin Users
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    full_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Login Logs
CREATE TABLE IF NOT EXISTS login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50),
    ip_address VARCHAR(45),
    is_success TINYINT(1),
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- IP Security
CREATE TABLE IF NOT EXISTS ip_security (
    ip_address VARCHAR(45) PRIMARY KEY,
    status ENUM('whitelisted', 'blacklisted') DEFAULT 'whitelisted',
    reason TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Payments
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad_id INT,
    user_id INT,
    amount DECIMAL(15, 2),
    status ENUM('pending', 'successful', 'failed') DEFAULT 'pending',
    payment_method VARCHAR(50),
    reference VARCHAR(100),
    proof_image VARCHAR(255) DEFAULT NULL,
    reject_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Seller Reputation
CREATE TABLE IF NOT EXISTS seller_reputation (
    user_id INT PRIMARY KEY,
    badge_tier ENUM('new', 'verified', 'active', 'trusted', 'business') DEFAULT 'new',
    transaction_count INT DEFAULT 0,
    avg_rating DECIMAL(3, 2) DEFAULT 0,
    dispute_count INT DEFAULT 0,
    last_calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255) DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Seller Analytics
CREATE TABLE IF NOT EXISTS seller_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad_id INT NOT NULL,
    viewer_id INT DEFAULT NULL,
    source VARCHAR(50),
    ip_address VARCHAR(45),
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE
);

-- Blog Posts
CREATE TABLE IF NOT EXISTS blog_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    summary TEXT,
    content LONGTEXT,
    image VARCHAR(255) DEFAULT NULL,
    meta_desc TEXT,
    meta_keywords TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- CMS Pages
CREATE TABLE IF NOT EXISTS pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content LONGTEXT,
    meta_desc TEXT,
    meta_keys TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
