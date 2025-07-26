-- Create Fresh Database Script
-- This script creates a new database with the updated structure

-- Step 1: Drop the database if it exists, then create a new one
DROP DATABASE IF EXISTS ecommerce_jul24;
CREATE DATABASE ecommerce_jul24;
USE ecommerce_jul24;

-- Step 2: Create all tables with proper structure

-- =====================================================
-- CORE USER TABLES
-- =====================================================

-- Staff table (renamed and restructured)
CREATE TABLE tbl_staff (
    Staff_id VARCHAR(11) PRIMARY KEY,
    Staff_fname VARCHAR(100) NOT NULL,
    Staff_lname VARCHAR(100) NOT NULL,
    Staff_street VARCHAR(200) NOT NULL,
    Staff_city VARCHAR(100) NOT NULL,
    Staff_age INT NOT NULL,
    Staff_gender VARCHAR(20) NOT NULL,
    Staff_ph VARCHAR(20) NOT NULL,
    Staff_email VARCHAR(200) NOT NULL,
    Staff_DOJ DATE NOT NULL,
    Username VARCHAR(225) NOT NULL,
    Password VARCHAR(225) NOT NULL,
    role ENUM('admin', 'delivery', 'product_manager') NOT NULL DEFAULT 'delivery'
);

-- Vendor table (renamed)
CREATE TABLE tbl_vendor (
    vendor_id VARCHAR(6) PRIMARY KEY,
    staff_id VARCHAR(11),
    vendor_name VARCHAR(200) NOT NULL,
    vendor_phone VARCHAR(20) NOT NULL,
    vendor_mail VARCHAR(150) NOT NULL,
    vendor_city VARCHAR(100) NOT NULL,
    vendor_state VARCHAR(100) NOT NULL,
    FOREIGN KEY (staff_id) REFERENCES tbl_staff(Staff_id) ON DELETE SET NULL
);

-- Customer table
CREATE TABLE tbl_customer (
    Cust_id VARCHAR(60) PRIMARY KEY,
    Cust_fname VARCHAR(100) NOT NULL,
    Cust_lname VARCHAR(100) NOT NULL,
    Cust_street VARCHAR(200) NOT NULL,
    Cust_city VARCHAR(100) NOT NULL,
    Cust_state VARCHAR(100) NOT NULL,
    Cust_gender VARCHAR(20) NOT NULL,
    Cust_ph VARCHAR(100) NOT NULL,
    Cust_email VARCHAR(225) NOT NULL,
    Username VARCHAR(225) NOT NULL,
    Password VARCHAR(225) NOT NULL,
    latitude DECIMAL(10,8) NULL,
    longitude DECIMAL(11,8) NULL
);

-- Category table
CREATE TABLE tbl_category (
    cat_id INT(11) PRIMARY KEY AUTO_INCREMENT,
    cat_name VARCHAR(100) NOT NULL UNIQUE
);

-- Item table
CREATE TABLE tbl_item (
    Item_id VARCHAR(6) PRIMARY KEY,
    Cat_id INT(11),
    Item_name VARCHAR(20) NOT NULL,
    Item_desc VARCHAR(255) NOT NULL,
    Item_brand VARCHAR(15) NOT NULL,
    Item_model VARCHAR(15) NOT NULL,
    Item_rate DECIMAL(10,2) NOT NULL,
    Item_quality VARCHAR(10) NOT NULL,
    Item_qty INT NOT NULL,
    Item_image VARCHAR(255) NULL,
    Item_rating INT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Cat_id) REFERENCES tbl_category(cat_id) ON DELETE SET NULL
);

-- Product questions table
CREATE TABLE product_questions (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    item_id VARCHAR(6) NOT NULL,
    customer_id VARCHAR(60) NOT NULL,
    question TEXT NOT NULL,
    answer TEXT,
    staff_id VARCHAR(11),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    answered_at TIMESTAMP NULL,
    INDEX idx_item_id (item_id),
    INDEX idx_customer_id (customer_id),
    INDEX idx_staff_id (staff_id),
    FOREIGN KEY (item_id) REFERENCES tbl_item(Item_id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES tbl_customer(Cust_id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES tbl_staff(Staff_id) ON DELETE SET NULL
);

-- Orders table
CREATE TABLE orders (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    customer_id VARCHAR(60) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    order_date DATETIME NOT NULL,
    status ENUM('Pending', 'Processing', 'Ready for Pickup', 'Out for Delivery', 'Delivered', 'Cancelled') NOT NULL DEFAULT 'Pending',
    delivery_type ENUM('delivery', 'pickup') NULL,
    delivery_address TEXT NULL,
    delivery_staff_id VARCHAR(11) NULL,
    delivery_distance DECIMAL(8,2) NULL,
    delivery_fee DECIMAL(10,2) DEFAULT 0.00,
    estimated_delivery_time DATETIME NULL,
    actual_delivery_time DATETIME NULL,
    INDEX idx_customer_id (customer_id),
    INDEX idx_status (status),
    INDEX idx_delivery_staff_id (delivery_staff_id),
    FOREIGN KEY (customer_id) REFERENCES tbl_customer(Cust_id) ON DELETE CASCADE,
    FOREIGN KEY (delivery_staff_id) REFERENCES tbl_staff(Staff_id) ON DELETE SET NULL
);

-- Order items table
CREATE TABLE order_items (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    order_id INT(11) NOT NULL,
    item_id VARCHAR(6) NOT NULL,
    quantity INT(11) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    INDEX idx_item_id (item_id),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES tbl_item(Item_id) ON DELETE CASCADE
);

-- Purchase Master table
CREATE TABLE tb1_purchase_master (
    P_mid VARCHAR(6) PRIMARY KEY,
    Vendor_id VARCHAR(6),
    P_date DATE NOT NULL,
    Total_amt DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (Vendor_id) REFERENCES tbl_vendor(vendor_id) ON DELETE SET NULL
);

-- Purchase child table
CREATE TABLE tbl_purchase_child (
    P_cid VARCHAR(6) PRIMARY KEY,
    P_mid VARCHAR(6),
    item_id VARCHAR(6),
    P_qty INT NOT NULL,
    P_rate DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (P_mid) REFERENCES tb1_purchase_master(P_mid) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES tbl_item(Item_id) ON DELETE SET NULL
);

-- Cart master table
CREATE TABLE tbl_cart_master (
    cart_mid VARCHAR(6) PRIMARY KEY,
    cust_id VARCHAR(60) NOT NULL,
    status ENUM('Active', 'Inactive', 'Ordered') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cust_id) REFERENCES tbl_customer(Cust_id),
    INDEX idx_cust_id (cust_id)
);

-- Cart child table
CREATE TABLE tbl_cart_child (
    cart_id VARCHAR(6) PRIMARY KEY,
    cart_mid VARCHAR(6) NOT NULL,
    item_id VARCHAR(6) NOT NULL,
    item_qty INT NOT NULL,
    item_rate DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_mid) REFERENCES tbl_cart_master(cart_mid),
    FOREIGN KEY (item_id) REFERENCES tbl_item(Item_id),
    INDEX idx_cart_mid (cart_mid),
    INDEX idx_item_id (item_id)
);

-- Payment table
CREATE TABLE tbl_payment (
    pay_id VARCHAR(20) PRIMARY KEY,
    cart_id VARCHAR(6),
    order_status ENUM('Pending', 'Paid', 'Failed', 'Refunded') NOT NULL,
    pay_amt DECIMAL(10,2) NOT NULL,
    pay_date DATE NOT NULL,
    FOREIGN KEY (cart_id) REFERENCES tbl_cart_master(cart_mid)
);

-- Card table
CREATE TABLE tbl_card (
    card_id INT PRIMARY KEY AUTO_INCREMENT,
    cust_id VARCHAR(60),
    card_name VARCHAR(20) NOT NULL,
    card_no VARCHAR(12) NOT NULL,
    card_expiry DATE NOT NULL,
    FOREIGN KEY (cust_id) REFERENCES tbl_customer(Cust_id)
);

-- Delivery table
CREATE TABLE tbl_delivery (
    Del_id VARCHAR(20) PRIMARY KEY,
    cart_id VARCHAR(6),
    cust_id VARCHAR(60),
    del_pincode VARCHAR(20) NOT NULL,
    del_date DATE NOT NULL,
    del_status VARCHAR(50) NOT NULL,
    FOREIGN KEY (cart_id) REFERENCES tbl_cart_master(cart_mid),
    FOREIGN KEY (cust_id) REFERENCES tbl_customer(Cust_id)
);

-- =====================================================
-- SAMPLE DATA
-- =====================================================

-- Insert sample categories
INSERT INTO tbl_category (cat_name) VALUES
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
INSERT INTO tbl_staff (
    Staff_id, Staff_fname, Staff_lname, Staff_street, Staff_city, Staff_age, Staff_gender, Staff_ph, Staff_email, Staff_DOJ, Username, Password, role
) VALUES
('STF001', 'Admin', 'User', 'Street 1', 'CityA', 30, 'M', '1234567890', 'admin1@gmail.com', '2024-01-01', 'adminuser', '$2y$10$.ebanUzkkcaKXDiizdyLMu5iBKBzxJ4MyYIpgDFg.flunhNhI059K', 'admin'),
('STF002', 'Product', 'Manager', 'Street 2', 'CityB', 28, 'F', '1234567891', 'manager@example.com', '2024-01-02', 'manager', '$2y$10$E.q1sVz/k.eX.YhY2U/8A.A.r2/2pjwAE1s/DBxZ4gkzrGNfGfSGu', 'product_manager'),
('STF003', 'Delivery', 'Person', 'Street 3', 'CityC', 25, 'M', '1234567892', 'delivery@example.com', '2024-01-03', 'delivery', '$2y$10$E.q1sVz/k.eX.YhY2U/8A.A.r2/2pjwAE1s/DBxZ4gkzrGNfGfSGu', 'delivery'),
('STF004', 'Mike', 'Ross', 'Street 4', 'CityD', 27, 'M', '1234567893', 'mike@example.com', '2024-01-04', 'mikeross', '$2y$10$E.q1sVz/k.eX.YhY2U/8A.A.r2/2pjwAE1s/DBxZ4gkzrGNfGfSGu', 'delivery');

-- Insert sample vendors (update for new structure)
INSERT INTO tbl_vendor (vendor_id, staff_id, vendor_name, vendor_phone, vendor_mail, vendor_city, vendor_state) VALUES
('VEND001', 'STF001', 'Vendor 1', 9876543210, 'vendor1@example.com', 'Kochi', 'Kerala'),
('VEND002', 'STF002', 'Vendor 2', 9876543211, 'vendor2@example.com', 'Thrissur', 'Kerala'),
('VEND003', 'STF003', 'Vendor 3', 9876543212, 'vendor3@example.com', 'Ernakulam', 'Kerala');

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


INSERT INTO orders (customer_id, total_amount, order_date, status, delivery_type, delivery_address, delivery_distance, delivery_fee) VALUES
('CUST01', 1349.97, NOW() - INTERVAL 5 DAY, 'Delivered', 'delivery', 'Fort Kochi, Kerala', 0.5, 50.00),
('CUST02', 899.99, NOW() - INTERVAL 3 DAY, 'Out for Delivery', 'delivery', 'Mattancherry, Kochi', 2.1, 50.00),
('CUST03', 1199.99, NOW() - INTERVAL 1 DAY, 'Processing', 'delivery', 'Ernakulam, Kochi', 0.0, 0.00),
('CUST04', 599.99, NOW() - INTERVAL 2 DAY, 'Ready for Pickup', 'pickup', 'Warehouse Pickup', 45.2, 0.00);

-- =====================================================
-- END OF SCRIPT
-- =====================================================