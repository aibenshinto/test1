-- Create Fresh Database Script
-- This script creates a new database with the updated structure

-- Step 1: Create new database
CREATE DATABASE IF NOT EXISTS ecommerce_jun19;
USE ecommerce_jun19;

-- Step 2: Create all tables with proper structure

-- =====================================================
-- CORE USER TABLES
-- =====================================================

-- Customers table
CREATE TABLE customers (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    location VARCHAR(255) NOT NULL,
    latitude DECIMAL(10,8) NULL,
    longitude DECIMAL(11,8) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Staff table
CREATE TABLE staff (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('delivery', 'product_manager') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- PRODUCT TABLES
-- =====================================================

-- Products table
CREATE TABLE products (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    stock INT(11) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Product questions table
CREATE TABLE product_questions (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    product_id INT(11) NOT NULL,
    customer_id INT(11) NOT NULL,
    question TEXT NOT NULL,
    answer TEXT,
    staff_id INT(11),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    answered_at TIMESTAMP NULL,
    INDEX idx_product_id (product_id),
    INDEX idx_customer_id (customer_id),
    INDEX idx_staff_id (staff_id)
);

-- =====================================================
-- ORDER TABLES
-- =====================================================

-- Orders table
CREATE TABLE orders (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    customer_id INT(11) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    order_date DATETIME NOT NULL,
    status ENUM('Pending', 'Processing', 'Ready for Pickup', 'Out for Delivery', 'Delivered', 'Cancelled') NOT NULL DEFAULT 'Pending',
    delivery_type ENUM('delivery', 'pickup') NULL,
    delivery_address TEXT NULL,
    delivery_staff_id INT(11) NULL,
    delivery_distance DECIMAL(8,2) NULL,
    delivery_fee DECIMAL(10,2) DEFAULT 0.00,
    estimated_delivery_time DATETIME NULL,
    actual_delivery_time DATETIME NULL,
    INDEX idx_customer_id (customer_id),
    INDEX idx_status (status),
    INDEX idx_delivery_staff_id (delivery_staff_id)
);

-- Order items table
CREATE TABLE order_items (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    order_id INT(11) NOT NULL,
    product_id INT(11) NOT NULL,
    quantity INT(11) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    INDEX idx_product_id (product_id)
);

-- =====================================================
-- CART TABLES
-- =====================================================

-- Cart items table
CREATE TABLE cart_items (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    customer_id INT(11) NOT NULL,
    product_id INT(11) NOT NULL,
    quantity INT(11) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customer_id (customer_id),
    INDEX idx_product_id (product_id)
);

-- =====================================================
-- FOREIGN KEY CONSTRAINTS
-- =====================================================

-- Product questions foreign keys
ALTER TABLE product_questions 
ADD CONSTRAINT fk_product_questions_product 
FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE;

ALTER TABLE product_questions 
ADD CONSTRAINT fk_product_questions_customer 
FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE;

ALTER TABLE product_questions 
ADD CONSTRAINT fk_product_questions_staff 
FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE SET NULL;

-- Orders foreign keys
ALTER TABLE orders 
ADD CONSTRAINT fk_orders_customer 
FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE;

ALTER TABLE orders 
ADD CONSTRAINT fk_orders_delivery_staff 
FOREIGN KEY (delivery_staff_id) REFERENCES staff(id) ON DELETE SET NULL;

-- Order items foreign keys
ALTER TABLE order_items 
ADD CONSTRAINT fk_order_items_order 
FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE;

ALTER TABLE order_items 
ADD CONSTRAINT fk_order_items_product 
FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE;

-- Cart items foreign keys
ALTER TABLE cart_items 
ADD CONSTRAINT fk_cart_items_customer 
FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE;

ALTER TABLE cart_items 
ADD CONSTRAINT fk_cart_items_product 
FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE;

-- =====================================================
-- SAMPLE DATA
-- =====================================================

-- Insert sample products
INSERT INTO products (name, description, price, stock) VALUES
('iPhone 15 Pro', 'Latest iPhone with advanced camera system', 999.99, 50),
('Samsung Galaxy S24', 'Premium Android smartphone', 899.99, 30),
('MacBook Air M2', 'Lightweight laptop with M2 chip', 1199.99, 25),
('Sony WH-1000XM5', 'Premium noise-canceling headphones', 349.99, 40),
('iPad Air', 'Powerful tablet for work and play', 599.99, 35),
('Apple Watch Series 9', 'Advanced health and fitness tracking', 399.99, 60);

-- Insert sample staff (password is 'password123' for all)
INSERT INTO staff (name, email, password, role) VALUES
('John Delivery', 'delivery@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'delivery'),
('Jane Manager', 'manager@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'product_manager'),
('Mike Delivery', 'mike@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'delivery');

-- Insert sample customers with coordinates (Kochi warehouse coordinates: 9.9312, 76.2673)
INSERT INTO customers (name, email, password, location, latitude, longitude) VALUES
('Alice Johnson', 'alice@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Fort Kochi, Kerala', 9.9312, 76.2673),
('Bob Smith', 'bob@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mattancherry, Kochi', 9.9581, 76.2555),
('Carol Davis', 'carol@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ernakulam, Kochi', 9.9312, 76.2673),
('David Wilson', 'david@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Thrissur, Kerala', 10.5276, 76.2144);

-- Insert sample product questions
INSERT INTO product_questions (product_id, customer_id, question) VALUES
(1, 1, 'Does the iPhone 15 Pro come with a charger?'),
(1, 2, 'What is the battery life like?'),
(2, 3, 'Is the Samsung Galaxy S24 waterproof?'),
(3, 4, 'Can I run Windows on the MacBook Air?');

-- Insert sample cart items
INSERT INTO cart_items (customer_id, product_id, quantity) VALUES
(1, 1, 1),
(1, 4, 2),
(2, 2, 1),
(3, 3, 1);

-- Insert sample orders with delivery information
INSERT INTO orders (customer_id, total_amount, order_date, status, delivery_type, delivery_address, delivery_distance, delivery_fee) VALUES
(1, 1349.97, NOW() - INTERVAL 5 DAY, 'Delivered', 'delivery', 'Fort Kochi, Kerala', 0.5, 50.00),
(2, 899.99, NOW() - INTERVAL 3 DAY, 'Out for Delivery', 'delivery', 'Mattancherry, Kochi', 2.1, 50.00),
(3, 1199.99, NOW() - INTERVAL 1 DAY, 'Processing', 'delivery', 'Ernakulam, Kochi', 0.0, 0.00),
(4, 599.99, NOW() - INTERVAL 2 DAY, 'Ready for Pickup', 'pickup', 'Warehouse Pickup', 45.2, 0.00);

-- Insert sample order items
INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
(1, 1, 1, 999.99),
(1, 4, 1, 349.98),
(2, 2, 1, 899.99),
(3, 3, 1, 1199.99),
(4, 5, 1, 599.99);

-- =====================================================
-- END OF SCRIPT
-- ===================================================== 