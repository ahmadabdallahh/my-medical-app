-- قاعدة بيانات موقع حجز المواعيد الطبية - النسخة الكاملة
-- Medical Appointment Booking System - Complete Database

-- إنشاء قاعدة البيانات
CREATE DATABASE IF NOT EXISTS medical_booking;
USE medical_booking;

-- ========================================
-- الجداول الأساسية
-- ========================================

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
    role ENUM('patient', 'doctor', 'hospital') DEFAULT 'patient',
    city_id INT NULL,
    insurance_provider VARCHAR(100) NULL,
    insurance_number VARCHAR(50) NULL,
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

-- جدول المدن
CREATE TABLE cities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    governorate VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
    has_emergency TINYINT(1) DEFAULT 0,
    has_insurance TINYINT(1) DEFAULT 0,
    city_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE SET NULL
);

-- جدول الأقسام
CREATE TABLE departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    hospital_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
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
    hospital_id INT NULL,
    department_id INT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    experience_years INT,
    education TEXT,
    image VARCHAR(200),
    rating DECIMAL(3,2) DEFAULT 0.00,
    is_active TINYINT(1) DEFAULT 1,
    consultation_fee DECIMAL(10,2) DEFAULT 0.00,
    bio TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (specialty_id) REFERENCES specialties(id) ON DELETE SET NULL,
    FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

-- جدول المواعيد
CREATE TABLE appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    doctor_id INT NOT NULL,
    clinic_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    appointment_datetime DATETIME NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
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

-- جدول أوقات العمل للأطباء (الجديد)
CREATE TABLE working_hours (
    id INT PRIMARY KEY AUTO_INCREMENT,
    doctor_id INT NOT NULL,
    day_of_week ENUM('sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_available TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

-- ========================================
-- جداول نظام التذكيرات والإشعارات
-- ========================================

-- جدول إعدادات التذكيرات للمستخدمين
CREATE TABLE reminder_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    email_enabled TINYINT(1) DEFAULT 1,
    sms_enabled TINYINT(1) DEFAULT 1,
    push_enabled TINYINT(1) DEFAULT 1,
    reminder_hours_before INT DEFAULT 24,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- جدول سجل التذكيرات المرسلة
CREATE TABLE reminder_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    appointment_id INT NOT NULL,
    reminder_type ENUM('email', 'sms', 'push') NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('sent', 'failed') DEFAULT 'sent',
    error_message TEXT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE
);

-- جدول الإشعارات الفورية
CREATE TABLE push_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('appointment', 'reminder', 'system') DEFAULT 'system',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ========================================
-- إضافة البيانات التجريبية
-- ========================================

-- إدخال بيانات المدن
INSERT INTO cities (name, governorate) VALUES 
('القاهرة', 'القاهرة'),
('الإسكندرية', 'الإسكندرية'),
('الجيزة', 'الجيزة'),
('المنوفية', 'المنوفية'),
('الشرقية', 'الشرقية'),
('الغربية', 'الغربية'),
('أسيوط', 'أسيوط'),
('سوهاج', 'سوهاج'),
('قنا', 'قنا'),
('الأقصر', 'الأقصر'),
('أسوان', 'أسوان'),
('بني سويف', 'بني سويف'),
('الفيوم', 'الفيوم'),
('المنيا', 'المنيا'),
('دمياط', 'دمياط'),
('بورسعيد', 'بورسعيد'),
('الإسماعيلية', 'الإسماعيلية'),
('السويس', 'السويس'),
('شرم الشيخ', 'جنوب سيناء'),
('دهب', 'جنوب سيناء');

-- إدخال بيانات التخصصات
INSERT INTO specialties (name, description, icon) VALUES
('طب القلب', 'تخصص في أمراض القلب والأوعية الدموية', 'heart'),
('طب العيون', 'تخصص في أمراض العيون والرؤية', 'eye'),
('طب الأسنان', 'تخصص في صحة الفم والأسنان', 'tooth'),
('طب الأطفال', 'تخصص في رعاية الأطفال', 'baby'),
('طب النساء والولادة', 'تخصص في صحة المرأة والولادة', 'female'),
('طب الجلدية', 'تخصص في أمراض الجلد', 'skin'),
('طب العظام', 'تخصص في أمراض العظام والمفاصل', 'bone'),
('طب الأعصاب', 'تخصص في أمراض الجهاز العصبي', 'brain'),
('طب الباطنة', 'تخصص في الأمراض الباطنية', 'stomach'),
('طب الأنف والأذن والحنجرة', 'تخصص في أمراض الأنف والأذن والحنجرة', 'ear'),
('طب النفسية', 'تخصص في الأمراض النفسية والعصبية', 'brain'),
('طب التجميل', 'تخصص في جراحات التجميل', 'scissors');

-- إدخال بيانات المستشفيات
INSERT INTO hospitals (name, address, phone, email, website, description, rating, city_id, has_emergency, has_insurance) VALUES
('مستشفى القاهرة العام', 'شارع القصر العيني، القاهرة', '02-23658974', 'info@cairohospital.com', 'www.cairohospital.com', 'مستشفى عام متكامل الخدمات', 4.5, 1, 1, 1),
('مستشفى المعادي', 'شارع النصر، المعادي، القاهرة', '02-25258963', 'info@maadi-hospital.com', 'www.maadi-hospital.com', 'مستشفى خاص بمعايير عالمية', 4.8, 1, 1, 1),
('مستشفى مصر الجديدة', 'شارع الثورة، مصر الجديدة', '02-24158974', 'info@newcairo-hospital.com', 'www.newcairo-hospital.com', 'مستشفى حديث التجهيزات', 4.2, 1, 0, 1),
('مستشفى الإسكندرية العام', 'شارع الإبراهيمية، الإسكندرية', '03-45678912', 'info@alexhospital.com', 'www.alexhospital.com', 'مستشفى عام في الإسكندرية', 4.3, 2, 1, 1),
('مستشفى الجيزة التخصصي', 'شارع الهرم، الجيزة', '02-34567890', 'info@gizahospital.com', 'www.gizahospital.com', 'مستشفى تخصصي في الجيزة', 4.6, 3, 1, 0);

-- إدخال بيانات الأقسام
INSERT INTO departments (name, description, hospital_id) VALUES
('قسم القلب', 'قسم متخصص في أمراض القلب والأوعية الدموية', 1),
('قسم العيون', 'قسم متخصص في أمراض العيون والرؤية', 1),
('قسم الأسنان', 'قسم متخصص في طب الأسنان', 2),
('قسم الأطفال', 'قسم متخصص في رعاية الأطفال', 2),
('قسم النساء والولادة', 'قسم متخصص في صحة المرأة والولادة', 3),
('قسم الجلدية', 'قسم متخصص في أمراض الجلد', 3),
('قسم العظام', 'قسم متخصص في أمراض العظام والمفاصل', 4),
('قسم الأعصاب', 'قسم متخصص في أمراض الجهاز العصبي', 4),
('قسم الباطنة', 'قسم متخصص في الأمراض الباطنية', 5),
('قسم الأنف والأذن والحنجرة', 'قسم متخصص في أمراض الأنف والأذن والحنجرة', 5);

-- إدخال بيانات العيادات
INSERT INTO clinics (hospital_id, name, specialty_id, address, phone, description, rating, consultation_fee) VALUES
(1, 'عيادة القلب', 1, 'الطابق الأول، مستشفى القاهرة العام', '02-23658975', 'عيادة متخصصة في أمراض القلب', 4.6, 300),
(1, 'عيادة العيون', 2, 'الطابق الثاني، مستشفى القاهرة العام', '02-23658976', 'عيادة متخصصة في أمراض العيون', 4.4, 250),
(2, 'عيادة الأسنان', 3, 'الطابق الأول، مستشفى المعادي', '02-25258964', 'عيادة متخصصة في طب الأسنان', 4.7, 400),
(2, 'عيادة الأطفال', 4, 'الطابق الأول، مستشفى المعادي', '02-25258965', 'عيادة متخصصة في طب الأطفال', 4.3, 200),
(3, 'عيادة النساء والولادة', 5, 'الطابق الأول، مستشفى مصر الجديدة', '02-24158975', 'عيادة متخصصة في صحة المرأة', 4.5, 350),
(3, 'عيادة الجلدية', 6, 'الطابق الثاني، مستشفى مصر الجديدة', '02-24158976', 'عيادة متخصصة في أمراض الجلد', 4.2, 280),
(4, 'عيادة العظام', 7, 'الطابق الأول، مستشفى الإسكندرية العام', '03-45678913', 'عيادة متخصصة في أمراض العظام', 4.4, 320),
(4, 'عيادة الأعصاب', 8, 'الطابق الثاني، مستشفى الإسكندرية العام', '03-45678914', 'عيادة متخصصة في أمراض الأعصاب', 4.6, 380),
(5, 'عيادة الباطنة', 9, 'الطابق الأول، مستشفى الجيزة التخصصي', '02-34567891', 'عيادة متخصصة في الأمراض الباطنية', 4.3, 220),
(5, 'عيادة الأنف والأذن والحنجرة', 10, 'الطابق الثاني، مستشفى الجيزة التخصصي', '02-34567892', 'عيادة متخصصة في أمراض الأنف والأذن', 4.5, 300);

-- إدخال بيانات الأطباء
INSERT INTO doctors (full_name, specialty_id, clinic_id, hospital_id, department_id, phone, email, experience_years, education, rating, consultation_fee, bio) VALUES
('د. أحمد محمد علي', 1, 1, 1, 1, '01012345678', 'ahmed.ali@hospital.com', 15, 'دكتوراه في أمراض القلب - جامعة القاهرة', 4.8, 300, 'طبيب قلب متخصص مع خبرة 15 عام في تشخيص وعلاج أمراض القلب والأوعية الدموية'),
('د. فاطمة أحمد حسن', 2, 2, 1, 2, '01012345679', 'fatima.hassan@hospital.com', 12, 'دكتوراه في طب العيون - جامعة عين شمس', 4.6, 250, 'طبيبة عيون متخصصة في جراحات العيون المتقدمة والليزر'),
('د. محمد سعيد أحمد', 3, 3, 2, 3, '01012345680', 'mohamed.saeed@hospital.com', 18, 'دكتوراه في طب الأسنان - جامعة الإسكندرية', 4.9, 400, 'طبيب أسنان متخصص في زراعة الأسنان وتقويم الأسنان'),
('د. سارة محمود علي', 4, 4, 2, 4, '01012345681', 'sara.mahmoud@hospital.com', 10, 'دكتوراه في طب الأطفال - جامعة القاهرة', 4.5, 200, 'طبيبة أطفال متخصصة في رعاية الأطفال حديثي الولادة'),
('د. خالد عبد الرحمن', 5, 5, 3, 5, '01012345682', 'khaled.abdulrahman@hospital.com', 14, 'دكتوراه في طب النساء والولادة - جامعة الأزهر', 4.7, 350, 'طبيب نساء وولادة متخصص في الولادة الطبيعية والقيصرية'),
('د. نورا أحمد محمد', 6, 6, 3, 6, '01012345683', 'nora.ahmed@hospital.com', 8, 'دكتوراه في طب الجلدية - جامعة القاهرة', 4.3, 280, 'طبيبة جلدية متخصصة في علاج الأمراض الجلدية والليزر'),
('د. عمر محمد حسن', 7, 7, 4, 7, '01012345684', 'omar.mohamed@hospital.com', 16, 'دكتوراه في طب العظام - جامعة الإسكندرية', 4.4, 320, 'طبيب عظام متخصص في جراحات العظام والمفاصل'),
('د. ليلى أحمد سعيد', 8, 8, 4, 8, '01012345685', 'layla.ahmed@hospital.com', 13, 'دكتوراه في طب الأعصاب - جامعة عين شمس', 4.6, 380, 'طبيبة أعصاب متخصصة في تشخيص وعلاج أمراض الجهاز العصبي'),
('د. يوسف محمد علي', 9, 9, 5, 9, '01012345686', 'youssef.mohamed@hospital.com', 11, 'دكتوراه في طب الباطنة - جامعة القاهرة', 4.3, 220, 'طبيب باطنة متخصص في الأمراض الباطنية والجهاز الهضمي'),
('د. رنا أحمد حسن', 10, 10, 5, 10, '01012345687', 'rana.ahmed@hospital.com', 9, 'دكتوراه في طب الأنف والأذن والحنجرة - جامعة الأزهر', 4.5, 300, 'طبيبة أنف وأذن وحنجرة متخصصة في جراحات الأنف والأذن');

-- إدخال بيانات المستخدمين (المرضى)
INSERT INTO users (username, email, password, full_name, phone, date_of_birth, gender, role, city_id, insurance_provider, insurance_number) VALUES
('ahmed_patient', 'ahmed@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'أحمد محمد علي', '01012345688', '1990-05-15', 'male', 'patient', 1, 'شركة التأمين المصرية', 'INS001234'),
('fatima_patient', 'fatima@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'فاطمة أحمد حسن', '01012345689', '1985-08-22', 'female', 'patient', 2, 'شركة التأمين المصرية', 'INS001235'),
('mohamed_patient', 'mohamed@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'محمد سعيد أحمد', '01012345690', '1992-12-10', 'male', 'patient', 3, NULL, NULL),
('sara_patient', 'sara@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'سارة محمود علي', '01012345691', '1988-03-25', 'female', 'patient', 1, 'شركة التأمين المصرية', 'INS001236'),
('khaled_patient', 'khaled@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'خالد عبد الرحمن', '01012345692', '1995-07-18', 'male', 'patient', 2, NULL, NULL);

-- إدخال بيانات الأطباء كمسخدمين
INSERT INTO users (username, email, password, full_name, phone, date_of_birth, gender, role, city_id) VALUES
('dr_ahmed', 'dr.ahmed@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'د. أحمد محمد علي', '01012345678', '1975-05-15', 'male', 'doctor', 1),
('dr_fatima', 'dr.fatima@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'د. فاطمة أحمد حسن', '01012345679', '1980-08-22', 'female', 'doctor', 1),
('dr_mohamed', 'dr.mohamed@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'د. محمد سعيد أحمد', '01012345680', '1978-12-10', 'male', 'doctor', 1),
('dr_sara', 'dr.sara@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'د. سارة محمود علي', '01012345681', '1982-03-25', 'female', 'doctor', 1),
('dr_khaled', 'dr.khaled@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'د. خالد عبد الرحمن', '01012345682', '1976-07-18', 'male', 'doctor', 1);

-- إدخال بيانات المستشفيات كمسخدمين
INSERT INTO users (username, email, password, full_name, phone, date_of_birth, gender, role, city_id) VALUES
('cairo_hospital', 'admin@cairohospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'مستشفى القاهرة العام', '02-23658974', '1980-01-01', 'male', 'hospital', 1),
('maadi_hospital', 'admin@maadi-hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'مستشفى المعادي', '02-25258963', '1985-01-01', 'male', 'hospital', 1),
('newcairo_hospital', 'admin@newcairo-hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'مستشفى مصر الجديدة', '02-24158974', '1990-01-01', 'male', 'hospital', 1);

-- إدخال أوقات عمل الأطباء (doctor_schedules)
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
(4, 'thursday', '09:00:00', '15:00:00'),
(5, 'sunday', '08:00:00', '14:00:00'),
(5, 'monday', '08:00:00', '14:00:00'),
(5, 'tuesday', '08:00:00', '14:00:00'),
(5, 'wednesday', '08:00:00', '14:00:00'),
(5, 'thursday', '08:00:00', '14:00:00');

-- إدخال أوقات العمل الجديدة (working_hours)
INSERT INTO working_hours (doctor_id, day_of_week, start_time, end_time, is_available) VALUES
(1, 'sunday', '09:00:00', '17:00:00', 1),
(1, 'monday', '09:00:00', '17:00:00', 1),
(1, 'tuesday', '09:00:00', '17:00:00', 1),
(1, 'wednesday', '09:00:00', '17:00:00', 1),
(1, 'thursday', '09:00:00', '17:00:00', 1),
(2, 'sunday', '10:00:00', '18:00:00', 1),
(2, 'monday', '10:00:00', '18:00:00', 1),
(2, 'tuesday', '10:00:00', '18:00:00', 1),
(2, 'wednesday', '10:00:00', '18:00:00', 1),
(2, 'thursday', '10:00:00', '18:00:00', 1),
(3, 'saturday', '08:00:00', '16:00:00', 1),
(3, 'sunday', '08:00:00', '16:00:00', 1),
(3, 'monday', '08:00:00', '16:00:00', 1),
(3, 'tuesday', '08:00:00', '16:00:00', 1),
(3, 'wednesday', '08:00:00', '16:00:00', 1),
(4, 'sunday', '09:00:00', '15:00:00', 1),
(4, 'monday', '09:00:00', '15:00:00', 1),
(4, 'tuesday', '09:00:00', '15:00:00', 1),
(4, 'wednesday', '09:00:00', '15:00:00', 1),
(4, 'thursday', '09:00:00', '15:00:00', 1),
(5, 'sunday', '08:00:00', '14:00:00', 1),
(5, 'monday', '08:00:00', '14:00:00', 1),
(5, 'tuesday', '08:00:00', '14:00:00', 1),
(5, 'wednesday', '08:00:00', '14:00:00', 1),
(5, 'thursday', '08:00:00', '14:00:00', 1);

-- إدخال بيانات المواعيد
INSERT INTO appointments (user_id, doctor_id, clinic_id, appointment_date, appointment_time, appointment_datetime, status, notes) VALUES
(1, 1, 1, '2024-08-10', '10:00:00', '2024-08-10 10:00:00', 'confirmed', 'موعد دوري للكشف'),
(2, 2, 2, '2024-08-11', '14:00:00', '2024-08-11 14:00:00', 'pending', 'كشف عيون'),
(3, 3, 3, '2024-08-12', '09:00:00', '2024-08-12 09:00:00', 'confirmed', 'تنظيف أسنان'),
(4, 4, 4, '2024-08-13', '11:00:00', '2024-08-13 11:00:00', 'pending', 'كشف طفل'),
(5, 5, 5, '2024-08-14', '13:00:00', '2024-08-14 13:00:00', 'confirmed', 'متابعة حمل');

-- إدخال بيانات إعدادات التذكيرات
INSERT INTO reminder_settings (user_id, email_enabled, sms_enabled, push_enabled, reminder_hours_before) VALUES
(1, 1, 1, 1, 24),
(2, 1, 0, 1, 12),
(3, 1, 1, 0, 48),
(4, 0, 1, 1, 24),
(5, 1, 1, 1, 6);

-- إدخال بيانات التذكيرات المرسلة
INSERT INTO reminder_logs (user_id, appointment_id, reminder_type, status) VALUES
(1, 1, 'email', 'sent'),
(1, 1, 'sms', 'sent'),
(2, 2, 'email', 'sent'),
(3, 3, 'push', 'sent'),
(4, 4, 'sms', 'sent');

-- إدخال بيانات الإشعارات الفورية
INSERT INTO push_notifications (user_id, title, message, type, is_read) VALUES
(1, 'تأكيد الموعد', 'تم تأكيد موعدك مع د. أحمد محمد علي في 10 أغسطس 2024', 'appointment', 0),
(2, 'تذكير بالموعد', 'تذكير: موعدك مع د. فاطمة أحمد حسن غداً في 2:00 مساءً', 'reminder', 0),
(3, 'موعد جديد', 'تم حجز موعد جديد مع د. محمد سعيد أحمد', 'appointment', 0),
(4, 'تحديث الموعد', 'تم تحديث موعدك مع د. سارة محمود علي', 'appointment', 1),
(5, 'تذكير بالموعد', 'تذكير: موعدك مع د. خالد عبد الرحمن بعد 6 ساعات', 'reminder', 0);

-- ========================================
-- إنشاء الفهارس لتحسين الأداء
-- ========================================

-- فهارس جدول التذكيرات
CREATE INDEX idx_reminder_settings_user ON reminder_settings(user_id);
CREATE INDEX idx_reminder_logs_user ON reminder_logs(user_id);
CREATE INDEX idx_reminder_logs_appointment ON reminder_logs(appointment_id);
CREATE INDEX idx_push_notifications_user ON push_notifications(user_id);
CREATE INDEX idx_push_notifications_read ON push_notifications(is_read);

-- فهارس جدول أوقات العمل
CREATE INDEX idx_working_hours_doctor ON working_hours(doctor_id);
CREATE INDEX idx_doctor_schedules_doctor ON doctor_schedules(doctor_id);

-- فهارس جدول المواعيد
CREATE INDEX idx_appointments_time ON appointments(appointment_time);
CREATE INDEX idx_appointments_status ON appointments(status);
CREATE INDEX idx_appointments_user ON appointments(user_id);
CREATE INDEX idx_appointments_doctor ON appointments(doctor_id);
CREATE INDEX idx_appointments_date ON appointments(appointment_date);

-- فهارس جدول الأطباء
CREATE INDEX idx_doctors_hospital ON doctors(hospital_id);
CREATE INDEX idx_doctors_active ON doctors(is_active);
CREATE INDEX idx_doctors_specialty ON doctors(specialty_id);
CREATE INDEX idx_doctors_rating ON doctors(rating);

-- فهارس جدول المستشفيات
CREATE INDEX idx_hospitals_city ON hospitals(city_id);
CREATE INDEX idx_hospitals_rating ON hospitals(rating);
CREATE INDEX idx_hospitals_emergency ON hospitals(has_emergency);
CREATE INDEX idx_hospitals_insurance ON hospitals(has_insurance);

-- فهارس جدول المستخدمين
CREATE INDEX idx_users_city ON users(city_id);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_username ON users(username);

-- فهارس جدول العيادات
CREATE INDEX idx_clinics_hospital ON clinics(hospital_id);
CREATE INDEX idx_clinics_specialty ON clinics(specialty_id);
CREATE INDEX idx_clinics_rating ON clinics(rating);

-- فهارس جدول الأقسام
CREATE INDEX idx_departments_hospital ON departments(hospital_id);

-- ========================================
-- إنشاء Views مفيدة
-- ========================================

-- View لعرض معلومات الأطباء مع التخصص والمستشفى
CREATE VIEW doctor_info_view AS
SELECT 
    d.id,
    d.full_name,
    d.phone,
    d.email,
    d.experience_years,
    d.rating,
    d.consultation_fee,
    d.bio,
    d.is_active,
    s.name as specialty_name,
    h.name as hospital_name,
    c.name as clinic_name,
    dep.name as department_name
FROM doctors d
LEFT JOIN specialties s ON d.specialty_id = s.id
LEFT JOIN hospitals h ON d.hospital_id = h.id
LEFT JOIN clinics c ON d.clinic_id = c.id
LEFT JOIN departments dep ON d.department_id = dep.id;

-- View لعرض المواعيد مع معلومات المريض والطبيب
CREATE VIEW appointment_info_view AS
SELECT 
    a.id,
    a.appointment_date,
    a.appointment_time,
    a.appointment_datetime,
    a.status,
    a.notes,
    a.created_at,
    u.full_name as patient_name,
    u.phone as patient_phone,
    u.email as patient_email,
    d.full_name as doctor_name,
    d.phone as doctor_phone,
    c.name as clinic_name,
    h.name as hospital_name,
    s.name as specialty_name
FROM appointments a
JOIN users u ON a.user_id = u.id
JOIN doctors d ON a.doctor_id = d.id
JOIN clinics c ON a.clinic_id = c.id
JOIN hospitals h ON c.hospital_id = h.id
JOIN specialties s ON d.specialty_id = s.id;

-- View لعرض إحصائيات الأطباء
CREATE VIEW doctor_stats_view AS
SELECT 
    d.id,
    d.full_name,
    d.rating,
    d.consultation_fee,
    COUNT(a.id) as total_appointments,
    COUNT(CASE WHEN a.status = 'completed' THEN 1 END) as completed_appointments,
    COUNT(CASE WHEN a.status = 'pending' THEN 1 END) as pending_appointments,
    COUNT(CASE WHEN a.status = 'cancelled' THEN 1 END) as cancelled_appointments
FROM doctors d
LEFT JOIN appointments a ON d.id = a.doctor_id
GROUP BY d.id, d.full_name, d.rating, d.consultation_fee;

-- ========================================
-- إنشاء Stored Procedures مفيدة
-- ========================================

-- Procedure لحجز موعد جديد
DELIMITER //
CREATE PROCEDURE BookAppointment(
    IN p_user_id INT,
    IN p_doctor_id INT,
    IN p_clinic_id INT,
    IN p_appointment_date DATE,
    IN p_appointment_time TIME,
    IN p_notes TEXT,
    OUT p_appointment_id INT
)
BEGIN
    DECLARE appointment_datetime DATETIME;
    
    SET appointment_datetime = CONCAT(p_appointment_date, ' ', p_appointment_time);
    
    INSERT INTO appointments (user_id, doctor_id, clinic_id, appointment_date, appointment_time, appointment_datetime, notes)
    VALUES (p_user_id, p_doctor_id, p_clinic_id, p_appointment_date, p_appointment_time, appointment_datetime, p_notes);
    
    SET p_appointment_id = LAST_INSERT_ID();
    
    -- إنشاء تذكيرات تلقائية
    INSERT INTO reminder_logs (user_id, appointment_id, reminder_type, status)
    VALUES (p_user_id, p_appointment_id, 'email', 'sent');
    
    INSERT INTO push_notifications (user_id, title, message, type)
    VALUES (p_user_id, 'موعد جديد', CONCAT('تم حجز موعد جديد في ', p_appointment_date), 'appointment');
END //
DELIMITER ;

-- Procedure لجلب الأوقات المتاحة للطبيب
DELIMITER //
CREATE PROCEDURE GetAvailableTimes(
    IN p_doctor_id INT,
    IN p_date DATE
)
BEGIN
    DECLARE day_name VARCHAR(20);
    SET day_name = LOWER(DAYNAME(p_date));
    
    SELECT 
        TIME_FORMAT(wh.start_time, '%H:%i') as available_time
    FROM working_hours wh
    WHERE wh.doctor_id = p_doctor_id 
    AND wh.day_of_week = day_name
    AND wh.is_available = 1
    AND TIME_FORMAT(wh.start_time, '%H:%i') NOT IN (
        SELECT TIME_FORMAT(a.appointment_time, '%H:%i')
        FROM appointments a
        WHERE a.doctor_id = p_doctor_id 
        AND a.appointment_date = p_date
        AND a.status IN ('pending', 'confirmed')
    )
    ORDER BY wh.start_time;
END //
DELIMITER ;

-- ========================================
-- إنشاء Triggers مفيدة
-- ========================================

-- Trigger لتحديث تقييم الطبيب عند إضافة تقييم جديد
DELIMITER //
CREATE TRIGGER update_doctor_rating
AFTER INSERT ON appointments
FOR EACH ROW
BEGIN
    -- هنا يمكن إضافة منطق لتحديث تقييم الطبيب بناءً على تقييمات المرضى
    -- (يتطلب جدول منفصل لتقييمات المرضى)
    UPDATE doctors 
    SET updated_at = NOW()
    WHERE id = NEW.doctor_id;
END //
DELIMITER ;

-- Trigger لإرسال تذكيرات تلقائية
DELIMITER //
CREATE TRIGGER send_automatic_reminders
AFTER INSERT ON appointments
FOR EACH ROW
BEGIN
    -- إرسال تذكير فوري
    INSERT INTO push_notifications (user_id, title, message, type)
    VALUES (NEW.user_id, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment');
END //
DELIMITER ;

-- ========================================
-- رسالة نجاح
-- ========================================

SELECT 'تم إنشاء قاعدة البيانات بنجاح!' as message;
SELECT 'جميع الجداول والبيانات والوظائف جاهزة للاستخدام' as status; 