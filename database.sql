-- Medical Center Management System
-- Database: mcms
-- Run this in phpMyAdmin or MySQL CLI

CREATE DATABASE IF NOT EXISTS mcms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mcms;

-- Users (all staff roles)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin','doctor','receptionist') NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Doctors (linked to users)
CREATE TABLE IF NOT EXISTS doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    specialization VARCHAR(100),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Patients
CREATE TABLE IF NOT EXISTS patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_code VARCHAR(20) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('Male','Female','Other') NOT NULL,
    address TEXT,
    blood_group VARCHAR(5),
    allergies TEXT,
    emergency_contact VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Appointments
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    visit_reason TEXT,
    status ENUM('scheduled','checked_in','completed','cancelled') DEFAULT 'scheduled',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (doctor_id) REFERENCES doctors(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    UNIQUE KEY no_double_booking (doctor_id, appointment_date, appointment_time)
);

-- Medical Records (one per consultation visit)
CREATE TABLE IF NOT EXISTS medical_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    symptoms TEXT,
    diagnosis TEXT,
    treatment_plan TEXT,
    follow_up_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id),
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (doctor_id) REFERENCES doctors(id)
);

-- Prescriptions
CREATE TABLE IF NOT EXISTS prescriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_id INT NOT NULL,
    medicine_name VARCHAR(100) NOT NULL,
    dosage VARCHAR(100),
    frequency VARCHAR(100),
    duration VARCHAR(100),
    instructions TEXT,
    FOREIGN KEY (record_id) REFERENCES medical_records(id) ON DELETE CASCADE
);

-- Lab Tests
CREATE TABLE IF NOT EXISTS lab_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_id INT,
    test_name VARCHAR(100) NOT NULL,
    test_date DATE,
    status ENUM('requested','in_progress','completed') DEFAULT 'requested',
    result TEXT,
    result_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (doctor_id) REFERENCES doctors(id)
);

-- Payments
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    appointment_id INT,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('Cash','Card','Insurance','Bank Transfer') NOT NULL,
    payment_status ENUM('paid','pending','partial') DEFAULT 'paid',
    payment_date DATE NOT NULL,
    notes TEXT,
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (appointment_id) REFERENCES appointments(id),
    FOREIGN KEY (recorded_by) REFERENCES users(id)
);

-- Audit Trail
CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- -------------------------------------------------------
-- Seed Data
-- -------------------------------------------------------

-- Default users (passwords are bcrypt of: admin123, doctor123, recept123)
INSERT INTO users (username, password, full_name, role, email, phone, is_active) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin', 'admin@mcms.local', '0599000001', 1),
('dr_ahmed', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Ahmed Al-Khalil', 'doctor', 'ahmed@mcms.local', '0599000002', 1),
('dr_sara', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Sara Mansour', 'doctor', 'sara@mcms.local', '0599000003', 1),
('recept1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lina Haddad', 'receptionist', 'lina@mcms.local', '0599000004', 1);

-- Doctors
INSERT INTO doctors (user_id, specialization) VALUES
(2, 'General Medicine'),
(3, 'Cardiology');

-- Sample patients
INSERT INTO patients (patient_code, full_name, phone, date_of_birth, gender, address, blood_group, allergies) VALUES
('PAT-0001', 'Mohammed Al-Nasser', '0591111001', '1985-03-15', 'Male', 'Nablus, Palestine', 'O+', 'Penicillin'),
('PAT-0002', 'Fatima Saleh', '0591111002', '1992-07-22', 'Female', 'Ramallah, Palestine', 'A+', NULL),
('PAT-0003', 'Khalid Ibrahim', '0591111003', '1978-11-05', 'Male', 'Jenin, Palestine', 'B-', 'Aspirin'),
('PAT-0004', 'Nour Hassan', '0591111004', '2000-01-30', 'Female', 'Tulkarm, Palestine', 'AB+', NULL);

-- Sample appointments
INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, visit_reason, status, created_by) VALUES
(1, 1, CURDATE(), '09:00:00', 'Regular checkup', 'scheduled', 4),
(2, 2, CURDATE(), '10:00:00', 'Heart palpitations', 'checked_in', 4),
(3, 1, CURDATE(), '11:00:00', 'Fever and cough', 'completed', 4),
(4, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:30:00', 'Follow-up', 'scheduled', 4);
