-- قاعدة بيانات موقع حجز المواعيد الطبية
-- Medical Appointment Booking System Database

CREATE DATABASE IF NOT EXISTS medical_booking;
USE medical_booking;

-- جدول المستخدمين (المرضى)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    date_of_birth DATE,
    gender ENUM('male', 'female') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- جدول التخصصات الطبية
CREATE TABLE specialties (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(100)
);

-- جدول المستشفيات
CREATE TABLE hospitals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    address TEXT NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    website VARCHAR(200),
    description TEXT,
    image VARCHAR(200),
    type ENUM('حكومي', 'خاص') DEFAULT 'حكومي',
    rating DECIMAL(3,2) DEFAULT 0.00,
    is_24h BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول العيادات
CREATE TABLE clinics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hospital_id INT,
    name VARCHAR(200) NOT NULL,
    specialty_id INT,
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(100),
    description TEXT,
    image VARCHAR(200),
    consultation_fee DECIMAL(10,2) DEFAULT 0.00,
    rating DECIMAL(3,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE,
    FOREIGN KEY (specialty_id) REFERENCES specialties(id) ON DELETE SET NULL
);

-- جدول الأطباء
CREATE TABLE doctors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    specialty_id INT,
    clinic_id INT,
    phone VARCHAR(20),
    email VARCHAR(100),
    experience_years INT,
    education TEXT,
    image VARCHAR(200),
    rating DECIMAL(3,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (specialty_id) REFERENCES specialties(id) ON DELETE SET NULL,
    FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE
);

-- جدول المواعيد
CREATE TABLE appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    doctor_id INT NOT NULL,
    clinic_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE
);

-- جدول أوقات عمل الأطباء
CREATE TABLE doctor_schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    doctor_id INT NOT NULL,
    day_of_week ENUM('sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

-- إدخال بيانات تجريبية للتخصصات
INSERT INTO specialties (name, description, icon) VALUES
('طب القلب', 'تخصص في أمراض القلب والأوعية الدموية', 'heart'),
('طب العيون', 'تخصص في أمراض العيون والرؤية', 'eye'),
('طب الأسنان', 'تخصص في صحة الفم والأسنان', 'tooth'),
('طب الأطفال', 'تخصص في رعاية الأطفال', 'baby'),
('طب النساء والولادة', 'تخصص في صحة المرأة والولادة', 'female'),
('طب الجلدية', 'تخصص في أمراض الجلد', 'skin'),
('طب العظام', 'تخصص في أمراض العظام والمفاصل', 'bone'),
('طب الأعصاب', 'تخصص في أمراض الجهاز العصبي', 'brain');

-- إدخال بيانات تجريبية للمستشفيات
INSERT INTO hospitals (name, address, phone, email, website, description, rating) VALUES
('مستشفى القاهرة العام', 'شارع القصر العيني، القاهرة', '02-23658974', 'info@cairohospital.com', 'www.cairohospital.com', 'مستشفى عام متكامل الخدمات', 4.5),
('مستشفى المعادي', 'شارع النصر، المعادي، القاهرة', '02-25258963', 'info@maadi-hospital.com', 'www.maadi-hospital.com', 'مستشفى خاص بمعايير عالمية', 4.8),
('مستشفى مصر الجديدة', 'شارع الثورة، مصر الجديدة', '02-24158974', 'info@newcairo-hospital.com', 'www.newcairo-hospital.com', 'مستشفى حديث التجهيزات', 4.2);

-- إدخال بيانات تجريبية للعيادات
INSERT INTO clinics (hospital_id, name, specialty_id, address, phone, description, rating) VALUES
(1, 'عيادة القلب', 1, 'الطابق الأول، مستشفى القاهرة العام', '02-23658975', 'عيادة متخصصة في أمراض القلب', 4.6),
(1, 'عيادة العيون', 2, 'الطابق الثاني، مستشفى القاهرة العام', '02-23658976', 'عيادة متخصصة في أمراض العيون', 4.4),
(2, 'عيادة الأسنان', 3, 'الطابق الأول، مستشفى المعادي', '02-25258964', 'عيادة متخصصة في طب الأسنان', 4.7),
(3, 'عيادة الأطفال', 4, 'الطابق الأول، مستشفى مصر الجديدة', '02-24158975', 'عيادة متخصصة في طب الأطفال', 4.3);

-- إدخال بيانات تجريبية للأطباء
INSERT INTO doctors (full_name, specialty_id, clinic_id, phone, email, experience_years, education, rating) VALUES
('د. أحمد محمد علي', 1, 1, '01012345678', 'ahmed.ali@hospital.com', 15, 'دكتوراه في أمراض القلب - جامعة القاهرة', 4.8),
('د. فاطمة أحمد حسن', 2, 2, '01012345679', 'fatima.hassan@hospital.com', 12, 'دكتوراه في طب العيون - جامعة عين شمس', 4.6),
('د. محمد سعيد أحمد', 3, 3, '01012345680', 'mohamed.saeed@hospital.com', 18, 'دكتوراه في طب الأسنان - جامعة الإسكندرية', 4.9),
('د. سارة محمود علي', 4, 4, '01012345681', 'sara.mahmoud@hospital.com', 10, 'دكتوراه في طب الأطفال - جامعة القاهرة', 4.5);

-- إدخال أوقات عمل الأطباء
INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time) VALUES
(1, 'sunday', '09:00:00', '17:00:00'),
(1, 'monday', '09:00:00', '17:00:00'),
(1, 'tuesday', '09:00:00', '17:00:00'),
(1, 'wednesday', '09:00:00', '17:00:00'),
(1, 'thursday', '09:00:00', '17:00:00'),
(2, 'sunday', '10:00:00', '18:00:00'),
(2, 'monday', '10:00:00', '18:00:00'),
(2, 'tuesday', '10:00:00', '18:00:00'),
(2, 'wednesday', '10:00:00', '18:00:00'),
(2, 'thursday', '10:00:00', '18:00:00'),
(3, 'saturday', '08:00:00', '16:00:00'),
(3, 'sunday', '08:00:00', '16:00:00'),
(3, 'monday', '08:00:00', '16:00:00'),
(3, 'tuesday', '08:00:00', '16:00:00'),
(3, 'wednesday', '08:00:00', '16:00:00'),
(4, 'sunday', '09:00:00', '15:00:00'),
(4, 'monday', '09:00:00', '15:00:00'),
(4, 'tuesday', '09:00:00', '15:00:00'),
(4, 'wednesday', '09:00:00', '15:00:00'),
(4, 'thursday', '09:00:00', '15:00:00');
