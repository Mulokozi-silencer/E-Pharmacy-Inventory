-- Medical Inventory Management System Database Schema
-- Create Database
--CREATE DATABASE IF NOT EXISTS medical_inventory;
--USE medical_inventory;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('admin', 'pharmacist', 'doctor', 'nurse') NOT NULL,
    phone VARCHAR(20),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Categories Table
CREATE TABLE IF NOT EXISTS categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category_name (category_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Suppliers Table
CREATE TABLE IF NOT EXISTS suppliers (
    supplier_id INT PRIMARY KEY AUTO_INCREMENT,
    supplier_name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_supplier_name (supplier_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Products/Inventory Table
CREATE TABLE IF NOT EXISTS products (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    product_name VARCHAR(200) NOT NULL,
    category_id INT,
    supplier_id INT,
    product_code VARCHAR(50) UNIQUE,
    description TEXT,
    unit_price DECIMAL(10, 2) NOT NULL,
    quantity_in_stock INT DEFAULT 0,
    reorder_level INT DEFAULT 10,
    unit_of_measure VARCHAR(20),
    expiry_date DATE,
    batch_number VARCHAR(50),
    storage_location VARCHAR(100),
    requires_prescription TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id),
    INDEX idx_product_name (product_name),
    INDEX idx_product_code (product_code),
    INDEX idx_expiry_date (expiry_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Stock Transactions Table
CREATE TABLE IF NOT EXISTS stock_transactions (
    transaction_id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    transaction_type ENUM('in', 'out', 'adjustment') NOT NULL,
    quantity INT NOT NULL,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_id INT NOT NULL,
    reference_number VARCHAR(50),
    notes TEXT,
    patient_name VARCHAR(100),
    doctor_name VARCHAR(100),
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    INDEX idx_transaction_date (transaction_date),
    INDEX idx_product_id (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Alerts/Notifications Table
CREATE TABLE IF NOT EXISTS alerts (
    alert_id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    alert_type ENUM('low_stock', 'expiry_soon', 'expired') NOT NULL,
    alert_message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    INDEX idx_is_read (is_read),
    INDEX idx_alert_type (alert_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Audit Log Table
CREATE TABLE IF NOT EXISTS audit_log (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_value TEXT,
    new_value TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert Default Users (password: admin123)
INSERT INTO users (username, password, full_name, email, role, phone) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@medical.com', 'admin', '+1234567890'),
('pharmacist1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Pharmacist', 'pharmacist@medical.com', 'pharmacist', '+1234567891'),
('doctor1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Sarah Doctor', 'doctor@medical.com', 'doctor', '+1234567892'),
('nurse1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mary Nurse', 'nurse@medical.com', 'nurse', '+1234567893');

-- Insert Sample Categories
INSERT INTO categories (category_name, description) VALUES
('Antibiotics', 'Antibiotic medications'),
('Analgesics', 'Pain relief medications'),
('Surgical Supplies', 'Surgical instruments and supplies'),
('Diagnostic Equipment', 'Medical diagnostic equipment'),
('First Aid', 'First aid supplies'),
('Vaccines', 'Vaccination supplies'),
('IV Fluids', 'Intravenous fluids and solutions');

-- Insert Sample Suppliers
INSERT INTO suppliers (supplier_name, contact_person, email, phone, address) VALUES
('MedSupply Inc.', 'James Wilson', 'contact@medsupply.com', '+1234567890', '123 Medical Ave, Healthcare City'),
('Pharma Distributors Ltd.', 'Emma Thompson', 'info@pharmadist.com', '+1234567891', '456 Pharmacy St, Medical Town'),
('Global Medical Equipment', 'Robert Brown', 'sales@globalmed.com', '+1234567892', '789 Equipment Blvd, Health City');

-- Insert Sample Products
INSERT INTO products (product_name, category_id, supplier_id, product_code, description, unit_price, quantity_in_stock, reorder_level, unit_of_measure, expiry_date, batch_number, storage_location, requires_prescription) VALUES
('Amoxicillin 500mg', 1, 1, 'MED001', 'Antibiotic capsules', 0.50, 500, 100, 'Capsules', '2026-12-31', 'BATCH001', 'Shelf A1', 1),
('Paracetamol 500mg', 2, 1, 'MED002', 'Pain relief tablets', 0.10, 1000, 200, 'Tablets', '2026-06-30', 'BATCH002', 'Shelf A2', 0),
('Ibuprofen 400mg', 2, 1, 'MED003', 'Anti-inflammatory tablets', 0.15, 800, 150, 'Tablets', '2026-08-15', 'BATCH003', 'Shelf A3', 0),
('Surgical Gloves', 3, 2, 'SUP001', 'Sterile latex gloves', 5.00, 200, 50, 'Pairs', '2027-01-31', 'BATCH004', 'Cabinet B1', 0),
('Blood Pressure Monitor', 4, 3, 'EQP001', 'Digital BP monitor', 50.00, 15, 5, 'Units', NULL, 'SERIAL001', 'Storage Room 1', 0),
('Sterile Gauze Pads', 5, 2, 'SUP002', 'Sterile gauze 4x4', 0.25, 500, 100, 'Pieces', '2026-09-30', 'BATCH005', 'Cabinet B2', 0),
('COVID-19 Vaccine', 6, 1, 'VAC001', 'mRNA vaccine', 25.00, 50, 20, 'Vials', '2025-12-31', 'BATCH006', 'Refrigerator A', 1),
('Normal Saline 0.9%', 7, 2, 'IV001', 'Sodium chloride solution', 2.50, 300, 100, 'Bags', '2027-03-31', 'BATCH007', 'Shelf C1', 0);

-- Create Views for Quick Access
CREATE VIEW low_stock_items AS
SELECT p.product_id, p.product_name, p.product_code, p.quantity_in_stock, 
       p.reorder_level, c.category_name, s.supplier_name
FROM products p
LEFT JOIN categories c ON p.category_id = c.category_id
LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
WHERE p.quantity_in_stock <= p.reorder_level AND p.is_active = 1;

CREATE VIEW expiring_soon_items AS
SELECT p.product_id, p.product_name, p.product_code, p.expiry_date, 
       p.quantity_in_stock, c.category_name, DATEDIFF(p.expiry_date, CURDATE()) as days_until_expiry
FROM products p
LEFT JOIN categories c ON p.category_id = c.category_id
WHERE p.expiry_date IS NOT NULL 
  AND p.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
  AND p.expiry_date > CURDATE()
  AND p.is_active = 1
ORDER BY p.expiry_date;

CREATE VIEW expired_items AS
SELECT p.product_id, p.product_name, p.product_code, p.expiry_date, 
       p.quantity_in_stock, c.category_name
FROM products p
LEFT JOIN categories c ON p.category_id = c.category_id
WHERE p.expiry_date IS NOT NULL 
  AND p.expiry_date <= CURDATE()
  AND p.is_active = 1;