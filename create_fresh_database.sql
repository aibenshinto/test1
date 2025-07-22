-- Create Fresh Database Script
-- This script creates a new database with the updated structure

-- Step 1: Drop the database if it exists, then create a new one
DROP DATABASE IF EXISTS ecommerce_jul22;
CREATE DATABASE ecommerce_jul22;
USE ecommerce_jul22;

-- Step 2: Create all tables with proper structure

-- =====================================================
-- CORE USER TABLES
-- =====================================================

-- Customers table
CREATE TABLE tbl_customer (
    cust_id INT AUTO_INCREMENT PRIMARY KEY,
    Cust_fname VARCHAR(10) NOT NULL,
    Cust_lname VARCHAR(10) NOT NULL,
    Cust_street VARCHAR(225) NOT NULL,
    Cust_city VARCHAR(255) NOT NULL,
    Cust_state VARCHAR(225) NOT NULL,
    Cust_gender VARCHAR(10) NOT NULL,
    Cust_ph VARCHAR(10) NOT NULL,
    Cust_email VARCHAR(225) NOT NULL,
    Username VARCHAR(225) NOT NULL,
    Password VARCHAR(500) NOT NULL,
    latitude VARCHAR(500),     -- Added for storing geolocation latitude
    longitude VARCHAR(500)     -- Added for storing geolocation longitude
);

-- Staff table
CREATE TABLE staff (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('delivery', 'product_manager', 'admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Vendors table
CREATE TABLE vendors (
    vendor_id VARCHAR(6) PRIMARY KEY,
    staff_id INT(11),
    vendor_name VARCHAR(20) NOT NULL,
    vendor_phone NUMERIC(10) NOT NULL,
    vendor_mail VARCHAR(15) NOT NULL,
    vendor_city VARCHAR(10) NOT NULL,
    vendor_state VARCHAR(10) NOT NULL,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE SET NULL
);


-- =====================================================
-- PRODUCT TABLES
-- =====================================================

-- Categories table
CREATE TABLE categories (
    cat_id INT(11) PRIMARY KEY AUTO_INCREMENT,
    cat_name VARCHAR(100) NOT NULL UNIQUE
);

-- Items table (replaces products)
CREATE TABLE tbl_item (
    Item_id VARCHAR(6) PRIMARY KEY,
    Cat_id INT(11),
    Item_name VARCHAR(20) NOT NULL,
    Item_desc VARCHAR(255) NOT NULL,
    Item_brand VARCHAR(15) NOT NULL,
    Item_model VARCHAR(15) NOT NULL,
    Item_rate DECIMAL(10,2) NOT NULL,
    Item_quality VARCHAR(10) NOT NULL,
    Item_qty NUMERIC(5) NOT NULL,
    Item_image VARCHAR(255) NULL,
    Item_rating INT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Cat_id) REFERENCES categories(cat_id) ON DELETE SET NULL
);

-- Update product_questions table
CREATE TABLE product_questions (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    item_id VARCHAR(6) NOT NULL,
    customer_id INT(11) NOT NULL,
    question TEXT NOT NULL,
    answer TEXT,
    staff_id INT(11),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    answered_at TIMESTAMP NULL,
    INDEX idx_item_id (item_id),
    INDEX idx_customer_id (customer_id),
    INDEX idx_staff_id (staff_id)
);

-- =====================================================
-- ORDER AND PURCHASE TABLES
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

-- Update order_items table
CREATE TABLE order_items (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    order_id INT(11) NOT NULL,
    item_id VARCHAR(6) NOT NULL,
    quantity INT(11) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    INDEX idx_item_id (item_id)
);

-- Purchase Master table
CREATE TABLE tb1_purchase_master (
    P_mid VARCHAR(6) PRIMARY KEY,
    Vendor_id VARCHAR(6),
    P_date DATE NOT NULL,
    Total_amt DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (Vendor_id) REFERENCES vendors(vendor_id) ON DELETE SET NULL
);

-- Update purchase child table
CREATE TABLE tbl_purchase_child (
    P_cid VARCHAR(6) PRIMARY KEY,
    P_mid VARCHAR(6),
    item_id VARCHAR(6),
    P_qty NUMERIC(4) NOT NULL,
    P_rate DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (P_mid) REFERENCES tb1_purchase_master(P_mid) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES tbl_item(Item_id) ON DELETE SET NULL
);

-- =====================================================
-- CART TABLES
-- =====================================================

-- Update cart_items table
CREATE TABLE cart_items (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    customer_id INT(11) NOT NULL,
    item_id VARCHAR(6) NOT NULL,
    quantity INT(11) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customer_id (customer_id),
    INDEX idx_item_id (item_id)
);

-- =====================================================
-- FOREIGN KEY CONSTRAINTS
-- =====================================================

-- Product questions foreign keys
ALTER TABLE product_questions 
ADD CONSTRAINT fk_product_questions_item 
FOREIGN KEY (item_id) REFERENCES tbl_item(Item_id) ON DELETE CASCADE;

ALTER TABLE product_questions 
ADD CONSTRAINT fk_product_questions_customer 
FOREIGN KEY (customer_id) REFERENCES tbl_customer(Cust_id) ON DELETE CASCADE;

ALTER TABLE product_questions 
ADD CONSTRAINT fk_product_questions_staff 
FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE SET NULL;

-- Orders foreign keys
ALTER TABLE orders 
ADD CONSTRAINT fk_orders_customer 
FOREIGN KEY (customer_id) REFERENCES tbl_customer(Cust_id) ON DELETE CASCADE;

ALTER TABLE orders 
ADD CONSTRAINT fk_orders_delivery_staff 
FOREIGN KEY (delivery_staff_id) REFERENCES staff(id) ON DELETE SET NULL;

-- Order items foreign keys
ALTER TABLE order_items 
ADD CONSTRAINT fk_order_items_order 
FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE;

ALTER TABLE order_items 
ADD CONSTRAINT fk_order_items_item 
FOREIGN KEY (item_id) REFERENCES tbl_item(Item_id) ON DELETE CASCADE;

-- Cart items foreign keys
ALTER TABLE cart_items 
ADD CONSTRAINT fk_cart_items_customer 
FOREIGN KEY (customer_id) REFERENCES tbl_customer(Cust_id) ON DELETE CASCADE;

ALTER TABLE cart_items 
ADD CONSTRAINT fk_cart_items_item 
FOREIGN KEY (item_id) REFERENCES tbl_item(Item_id) ON DELETE CASCADE;

-- =====================================================
-- SAMPLE DATA
-- =====================================================

-- Insert sample categories
INSERT INTO categories (cat_name) VALUES
('Smartphones'),
('Laptops'),
('Headphones'),
('Tablets'),
('Wearables');

-- Insert sample items
INSERT INTO tbl_item (Item_id, Cat_id, Item_name, Item_desc, Item_brand, Item_model, Item_rate, Item_quality, Item_qty, Item_image, Item_rating) VALUES
('ITM001', 1, 'iPhone 15 Pro', 'Latest iPhone with advanced camera system', 'Apple', '15 Pro', 999.99, 'New', 50, 'uploads/products/6849c49d56cec.png', 5),
('ITM002', 1, 'Galaxy S24', 'Premium Android smartphone', 'Samsung', 'S24', 899.99, 'New', 30, 'uploads/products/6849cee2a52cd.png', 4),
('ITM003', 2, 'MacBook Air M2', 'Lightweight laptop with M2 chip', 'Apple', 'Air M2', 1199.99, 'New', 25, 'uploads/products/6849cf068e24e.png', 5),
('ITM004', 3, 'Sony WH-1000XM5', 'Premium noise-canceling headphones', 'Sony', 'WH-1000XM5', 349.99, 'New', 40, 'uploads/products/685fcd984dc99.png', 4),
('ITM005', 4, 'iPad Air', 'Powerful tablet for work and play', 'Apple', 'iPad Air', 599.99, 'New', 35, 'uploads/products/68611d770b13d.png', 3),
('ITM006', 5, 'Apple Watch S9', 'Advanced health and fitness tracking', 'Apple', 'Series 9', 399.99, 'New', 60, NULL, 4);

-- Insert sample staff (password is 'password123' for all, except admin)
-- Default admin: admin1@gmail.com / admin123
INSERT INTO staff (name, email, password, role) VALUES
('Admin User', 'admin1@gmail.com', '$2y$10$.ebanUzkkcaKXDiizdyLMu5iBKBzxJ4MyYIpgDFg.flunhNhI059K', 'admin'),
('Product Manager', 'manager@example.com', '$2y$10$E.q1sVz/k.eX.YhY2U/8A.A.r2/2pjwAE1s/DBxZ4gkzrGNfGfSGu', 'product_manager'),
('Delivery Person', 'delivery@example.com', '$2y$10$E.q1sVz/k.eX.YhY2U/8A.A.r2/2pjwAE1s/DBxZ4gkzrGNfGfSGu', 'delivery'),
('Mike Ross', 'mike@example.com', '$2y$10$E.q1sVz/k.eX.YhY2U/8A.A.r2/2pjwAE1s/DBxZ4gkzrGNfGfSGu', 'delivery');

-- Remove old customers table
DROP TABLE IF EXISTS customers;

-- Create new tbl_customer table
CREATE TABLE tbl_customer (
    Cust_id VARCHAR(6) PRIMARY KEY,
    Cust_fname VARCHAR(10) NOT NULL,
    Cust_lname VARCHAR(10) NOT NULL,
    Cust_street VARCHAR(20) NOT NULL,
    Cust_city VARCHAR(10) NOT NULL,
    Cust_state VARCHAR(10) NOT NULL,
    Cust_gender VARCHAR(2) NOT NULL,
    Cust_ph VARCHAR(10) NOT NULL,
    Cust_email VARCHAR(10) NOT NULL,
    Username VARCHAR(10) NOT NULL,
    Password VARCHAR(20) NOT NULL,
    latitude DECIMAL(10,8) NULL,
    longitude DECIMAL(11,8) NULL
);

-- Update all foreign key references to tbl_customer
-- Product questions foreign keys
ALTER TABLE product_questions 
DROP FOREIGN KEY fk_product_questions_customer,
ADD CONSTRAINT fk_product_questions_customer 
FOREIGN KEY (customer_id) REFERENCES tbl_customer(Cust_id) ON DELETE CASCADE;

-- Orders foreign keys
ALTER TABLE orders 
DROP FOREIGN KEY fk_orders_customer,
ADD CONSTRAINT fk_orders_customer 
FOREIGN KEY (customer_id) REFERENCES tbl_customer(Cust_id) ON DELETE CASCADE;

-- Cart items foreign keys
ALTER TABLE cart_items 
DROP FOREIGN KEY fk_cart_items_customer,
ADD CONSTRAINT fk_cart_items_customer 
FOREIGN KEY (customer_id) REFERENCES tbl_customer(Cust_id) ON DELETE CASCADE;

-- Insert sample customers (update for new structure)
INSERT INTO tbl_customer (Cust_id, Cust_fname, Cust_lname, Cust_street, Cust_city, Cust_state, Cust_gender, Cust_ph, Cust_email, Username, Password, latitude, longitude) VALUES
('CUST01', 'Alice', 'Johnson', 'Fort Kochi', 'Kochi', 'Kerala', 'F', '9876543210', 'alice@ex.com', 'alicej', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 9.9312, 76.2673),
('CUST02', 'Bob', 'Smith', 'Mattancherry', 'Kochi', 'Kerala', 'M', '9876543211', 'bob@ex.com', 'bobsmith', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 9.9581, 76.2555),
('CUST03', 'Carol', 'Davis', 'Ernakulam', 'Kochi', 'Kerala', 'F', '9876543212', 'carol@ex.com', 'carold', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 9.9312, 76.2673),
('CUST04', 'David', 'Wilson', 'Thrissur', 'Thrissur', 'Kerala', 'M', '9876543213', 'david@ex.com', 'davidw', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 10.5276, 76.2144);

-- Update sample product questions, cart items, orders, etc. to use new Cust_id values
INSERT INTO product_questions (item_id, customer_id, question) VALUES
('ITM001', 'CUST01', 'Does the iPhone 15 Pro come with a charger?'),
('ITM001', 'CUST02', 'What is the battery life like?'),
('ITM002', 'CUST03', 'Is the Samsung Galaxy S24 waterproof?'),
('ITM003', 'CUST04', 'Can I run Windows on the MacBook Air?');

INSERT INTO cart_items (customer_id, item_id, quantity) VALUES
('CUST01', 'ITM001', 1),
('CUST01', 'ITM004', 2),
('CUST02', 'ITM002', 1),
('CUST03', 'ITM003', 1);

INSERT INTO orders (customer_id, total_amount, order_date, status, delivery_type, delivery_address, delivery_distance, delivery_fee) VALUES
('CUST01', 1349.97, NOW() - INTERVAL 5 DAY, 'Delivered', 'delivery', 'Fort Kochi, Kerala', 0.5, 50.00),
('CUST02', 899.99, NOW() - INTERVAL 3 DAY, 'Out for Delivery', 'delivery', 'Mattancherry, Kochi', 2.1, 50.00),
('CUST03', 1199.99, NOW() - INTERVAL 1 DAY, 'Processing', 'delivery', 'Ernakulam, Kochi', 0.0, 0.00),
('CUST04', 599.99, NOW() - INTERVAL 2 DAY, 'Ready for Pickup', 'pickup', 'Warehouse Pickup', 45.2, 0.00);

-- =====================================================
-- END OF SCRIPT
-- =====================================================