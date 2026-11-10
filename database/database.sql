CREATE DATABASE IF NOT EXISTS art_ordering_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE art_ordering_system;

-- Users table (Customers and Admin)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('customer', 'admin') DEFAULT 'customer',
    profile_image VARCHAR(255) DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Artworks/Gallery table
CREATE TABLE artworks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    category ENUM('acrylic', 'pencil', 'oil', 'watercolor', 'digital') NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    artwork_type VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    requirements TEXT,
    reference_image VARCHAR(255),
    price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded', 'cod_deposit_paid') DEFAULT 'pending',
    payment_method VARCHAR(50),
    transaction_id VARCHAR(100),
    stripe_payment_intent VARCHAR(100),
    cod_deposit DECIMAL(10,2) DEFAULT NULL COMMENT '50% deposit paid upfront for COD orders',
    cod_remaining DECIMAL(10,2) DEFAULT NULL COMMENT 'Remaining 50% to be paid on delivery',
    cod_deposit_paid BOOLEAN DEFAULT FALSE,
    delivery_address TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_customer (customer_id)
);

-- Messages table
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    attachment VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_id INT,
    title VARCHAR(200),
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Insert default admin (password: admin123)
INSERT INTO users (full_name, email, password, role, phone) VALUES 
('Admin Artist', 'admin@artstudio.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '0771234567');

-- Insert sample artworks
INSERT INTO artworks (title, description, category, image_path, price) VALUES
('Sunset Portrait', 'Beautiful acrylic sunset portrait on canvas', 'acrylic', 'sample1.jpg', 150.00),
('Family Sketch', 'Detailed pencil sketch of family', 'pencil', 'sample2.jpg', 75.00),
('Abstract Oil', 'Modern abstract oil painting', 'oil', 'sample3.jpg', 200.00);