-- جداول نظام التذكيرات والإشعارات
-- Reminder and Notification System Tables

-- جدول إعدادات التذكيرات للمستخدمين
CREATE TABLE IF NOT EXISTS reminder_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email_reminders TINYINT(1) DEFAULT 0,
    sms_reminders TINYINT(1) DEFAULT 0,
    push_notifications TINYINT(1) DEFAULT 1,
    reminder_time INT DEFAULT 60, -- بالدقائق
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_settings (user_id)
);

-- جدول سجل التذكيرات المرسلة
CREATE TABLE IF NOT EXISTS reminder_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
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
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('appointment', 'reminder', 'system', 'review') NOT NULL,
    related_id INT NULL, -- ID للموعد أو التقييم المرتبط
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- جدول المدن (إذا لم يكن موجوداً)
CREATE TABLE IF NOT EXISTS cities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    governorate VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول الأقسام (إذا لم يكن موجوداً)
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    hospital_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
);

-- جدول أوقات العمل للأطباء
CREATE TABLE IF NOT EXISTS working_hours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    day_of_week TINYINT NOT NULL, -- 1=الأحد, 2=الاثنين, إلخ
    day_name VARCHAR(20) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_available TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    UNIQUE KEY unique_doctor_day (doctor_id, day_of_week)
);

-- إضافة أعمدة جديدة لجدول المستشفيات إذا لم تكن موجودة
ALTER TABLE hospitals
ADD COLUMN IF NOT EXISTS has_emergency TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS has_insurance TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS city_id INT NULL,
ADD COLUMN IF NOT EXISTS rating DECIMAL(3,2) DEFAULT 0.00,
ADD FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE SET NULL;

-- إضافة أعمدة جديدة لجدول الأطباء إذا لم تكن موجودة
ALTER TABLE doctors
ADD COLUMN IF NOT EXISTS department_id INT NULL,
ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1,
ADD COLUMN IF NOT EXISTS consultation_fee DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS bio TEXT NULL,
ADD COLUMN IF NOT EXISTS rating DECIMAL(3,2) DEFAULT 0.00,
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
('كفر الشيخ', 'كفر الشيخ'),
('المنيا', 'المنيا'),
('أسيوط', 'أسيوط'),
('سوهاج', 'سوهاج');

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
