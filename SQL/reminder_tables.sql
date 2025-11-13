-- جداول نظام التذكيرات والإشعارات
-- Reminder and Notification System Tables

-- جدول إعدادات التذكيرات للمستخدمين
CREATE TABLE IF NOT EXISTS reminder_settings (
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
CREATE TABLE IF NOT EXISTS reminder_logs (
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
CREATE TABLE IF NOT EXISTS push_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('appointment', 'reminder', 'system') DEFAULT 'system',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- جدول المدن (إذا لم يكن موجوداً)
CREATE TABLE IF NOT EXISTS cities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    governorate VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول الأقسام (إذا لم يكن موجوداً)
CREATE TABLE IF NOT EXISTS departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    hospital_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
);

-- جدول أوقات العمل للأطباء
CREATE TABLE IF NOT EXISTS working_hours (
    id INT PRIMARY KEY AUTO_INCREMENT,
    doctor_id INT NOT NULL,
    day_of_week ENUM('sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_available TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

-- إضافة أعمدة جديدة لجدول المستخدمين إذا لم تكن موجودة
ALTER TABLE users
ADD COLUMN IF NOT EXISTS role ENUM('patient', 'doctor', 'hospital') DEFAULT 'patient',
ADD COLUMN IF NOT EXISTS city_id INT NULL,
ADD COLUMN IF NOT EXISTS insurance_provider VARCHAR(100) NULL,
ADD COLUMN IF NOT EXISTS insurance_number VARCHAR(50) NULL,
ADD FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE SET NULL;

-- إضافة أعمدة جديدة لجدول المستشفيات إذا لم تكن موجودة
ALTER TABLE hospitals
ADD COLUMN IF NOT EXISTS has_emergency TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS has_insurance TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS city_id INT NULL,
ADD COLUMN IF NOT EXISTS rating DECIMAL(3,2) DEFAULT 0.00,
ADD FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE SET NULL;

-- إضافة أعمدة جديدة لجدول الأطباء إذا لم تكن موجودة
ALTER TABLE doctors
ADD COLUMN IF NOT EXISTS hospital_id INT NULL,
ADD COLUMN IF NOT EXISTS department_id INT NULL,
ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1,
ADD COLUMN IF NOT EXISTS consultation_fee DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS bio TEXT NULL,
ADD COLUMN IF NOT EXISTS rating DECIMAL(3,2) DEFAULT 0.00,
ADD FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE SET NULL,
ADD FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL;

-- إضافة أعمدة جديدة لجدول المواعيد إذا لم تكن موجودة
ALTER TABLE appointments
ADD COLUMN IF NOT EXISTS appointment_time DATETIME NULL,
ADD COLUMN IF NOT EXISTS status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending';

-- إضافة بيانات تجريبية للمدن
INSERT IGNORE INTO cities (name, governorate) VALUES
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

-- إنشاء فهارس لتحسين الأداء
CREATE INDEX idx_reminder_settings_user ON reminder_settings(user_id);
CREATE INDEX idx_reminder_logs_user ON reminder_logs(user_id);
CREATE INDEX idx_reminder_logs_appointment ON reminder_logs(appointment_id);
CREATE INDEX idx_push_notifications_user ON push_notifications(user_id);
CREATE INDEX idx_push_notifications_read ON push_notifications(is_read);
CREATE INDEX idx_working_hours_doctor ON working_hours(doctor_id);
CREATE INDEX idx_appointments_time ON appointments(appointment_time);
CREATE INDEX idx_appointments_status ON appointments(status);
CREATE INDEX idx_doctors_hospital ON doctors(hospital_id);
CREATE INDEX idx_doctors_active ON doctors(is_active);
CREATE INDEX idx_hospitals_city ON hospitals(city_id);
CREATE INDEX idx_users_city ON users(city_id);
CREATE INDEX idx_users_role ON users(role);
