-- Create Database
CREATE DATABASE IF NOT EXISTS blood_bank;
USE blood_bank;

-- 1. Admins Table
CREATE TABLE IF NOT EXISTS admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role VARCHAR(50) DEFAULT 'super_admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Donors Table

CREATE TABLE `donors` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `age` int(11) NOT NULL,
  `weight` decimal(5,2) NOT NULL,
  `blood_group` varchar(5) DEFAULT NULL,
  `diseases` varchar(255) DEFAULT NULL,
  `last_donation_date` date DEFAULT NULL,
  `total_donations` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(100) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL
) ;


-- 3. Hospitals Table
CREATE TABLE IF NOT EXISTS hospitals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    location VARCHAR(200),
    phone VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Nurses Table
CREATE TABLE IF NOT EXISTS nurses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hospital_id INT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
);

-- 5. Appointments Table
CREATE TABLE IF NOT EXISTS appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    donor_id INT,
    hospital_id INT,
    nurse_id INT,
    appointment_date DATE,
    appointment_time TIME,
    unique_code VARCHAR(50),
    status VARCHAR(20) DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (donor_id) REFERENCES donors(id),
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id),
    FOREIGN KEY (nurse_id) REFERENCES nurses(id)
);

-- 6. System Logs Table

CREATE TABLE IF NOT EXISTS system_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    user_type VARCHAR(20),
    action VARCHAR(100),
    details TEXT,
    ip_address VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- 7. Reports Table (for nurse to hospital admin communication)
CREATE TABLE IF NOT EXISTS reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nurse_id INT NOT NULL,
    hospital_id INT NOT NULL,
    report_type VARCHAR(20) NOT NULL,
    report_data TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'sent',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    viewed_at TIMESTAMP NULL,
    FOREIGN KEY (nurse_id) REFERENCES nurses(id) ON DELETE CASCADE,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
);
-- Insert Admins
INSERT INTO admins (name, email, password, phone, role) VALUES
('System Admin', 'admin.bloodbank@gmail.com', 'admin123', '0912345678', 'super_admin'),
('Manager Tekle', 'manager.bloodbank@gmail.com', 'manager123', '0923456789', 'manager');

-- Insert Hospitals
INSERT INTO hospitals (name, email, password, location, phone) VALUES
('Wolkite University Hospital', 'wolkite.hospital@gmail.com', 'hospital123', 'Wolkite, Ethiopia', '+251-123-456789'),
('Addis Ababa General Hospital', 'addis.hospital@gmail.com', 'hospital123', 'Addis Ababa, Ethiopia', '+251-987-654321');

-- Insert Nurses
INSERT INTO nurses (hospital_id, name, email, password, phone) VALUES
(1, 'Nurse Sarah Johnson', 'sarah.nurse@gmail.com', 'nurse123', '0911111111'),
(1, 'Nurse John Michael', 'john.nurse@gmail.com', 'nurse123', '0922222222'),
(2, 'Nurse Tigist Alemu', 'tigist.nurse@gmail.com', 'nurse123', '0933333333');

-- Insert Donors
INSERT INTO donors (name, email, password, phone, age, weight, blood_group, diseases, total_donations) VALUES
('John Doe', 'johndoe@gmail.com', 'donor123', '0912345678', 25, 70, 'O+', 'None', 2),
('Abebe Kebede', 'abebekebede@gmail.com', 'donor123', '0923456789', 30, 75, 'A+', 'None', 1),
('Tigist Haile', 'tigisthaile@gmail.com', 'donor123', '0934567890', 22, 65, 'B+', 'None', 0),
('Meron Desta', 'merondesta@gmail.com', 'donor123', '0945678901', 28, 68, 'O-', 'None', 1),
('Kebede Tesfaye', 'kebedetesfaye@gmail.com', 'donor123', '0956789012', 35, 80, 'AB+', 'None', 0);

-- Update all table - convert plain text to MD5
UPDATE donors SET password = MD5(password);
UPDATE admins SET password = MD5(password);
UPDATE hospitals SET password = MD5(password);
UPDATE nurses SET password = MD5(password);