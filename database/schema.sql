CREATE DATABASE IF NOT EXISTS goali_tour_management
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE goali_tour_management;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'super_admin') NOT NULL DEFAULT 'admin',
    status ENUM('pending', 'active', 'inactive', 'rejected') NOT NULL DEFAULT 'pending',
    last_login DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role_status (role, status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS company_settings (
    id TINYINT UNSIGNED PRIMARY KEY,
    company_name VARCHAR(160) NOT NULL DEFAULT 'Goali Tours',
    logo_path VARCHAR(500) NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tours (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    package_id VARCHAR(40) NOT NULL UNIQUE,
    tour_name VARCHAR(200) NOT NULL,
    customer_name VARCHAR(200) NULL,
    customer_details TEXT NULL,
    duration_days TINYINT UNSIGNED NOT NULL DEFAULT 1,
    duration_nights TINYINT UNSIGNED NOT NULL DEFAULT 0,
    activity_level ENUM('Easy', 'Intermediate', 'Hard') NOT NULL DEFAULT 'Intermediate',
    locations TEXT NULL,
    day_plans LONGTEXT NULL,
    map_url TEXT NULL,
    highlights LONGTEXT NULL,
    inclusions LONGTEXT NULL,
    exclusions LONGTEXT NULL,
    price_currency CHAR(3) NOT NULL DEFAULT 'LKR',
    price_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    important_notes LONGTEXT NULL,
    images LONGTEXT NULL,
    gallery LONGTEXT NULL,
    custom_field LONGTEXT NULL,
    status ENUM('active', 'recycled') NOT NULL DEFAULT 'active',
    source_tour_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_tours_category_status (category_id, status),
    INDEX idx_tours_updated (updated_at),
    CONSTRAINT fk_tours_category FOREIGN KEY (category_id) REFERENCES categories(id),
    CONSTRAINT fk_tours_source FOREIGN KEY (source_tour_id) REFERENCES tours(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO users (name, email, password_hash, role, status)
VALUES (
    'Super Administrator',
    'admin@goalitours.com',
    '$2y$12$foXT0mX/HMYxR/6rlbjete56gtxgjsYDHT3QhpcBd7/c9BA0IvY1a',
    'super_admin',
    'active'
)
ON DUPLICATE KEY UPDATE email = VALUES(email);

INSERT INTO company_settings (id, company_name, logo_path)
VALUES (1, 'Goali Tours', NULL)
ON DUPLICATE KEY UPDATE id = VALUES(id);

INSERT INTO categories (name) VALUES
('Sri Lanka Family Tours'),
('Sri Lanka Honeymoon Tours'),
('Sri Lanka Cultural Tours'),
('Sri Lanka Adventure Tours'),
('Sri Lanka Luxury Tours'),
('Sri Lanka Beach Tours')
ON DUPLICATE KEY UPDATE name = VALUES(name);
