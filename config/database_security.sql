-- الإصلاحات الشاملة لقاعدة البيانات
-- Comprehensive Database Security & Performance Fixes

-- 1. إصلاح سلامة البيانات ومنع المشاكل المتعلقة بالحذف
-- Fix data integrity and prevent deletion issues

-- إضافة مفاتيح خارجية مع CASCADE DELETE
ALTER TABLE appointments
ADD CONSTRAINT IF NOT EXISTS fk_appointments_user
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE appointments
ADD CONSTRAINT IF NOT EXISTS fk_appointments_doctor
FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE;

-- إصلاح جدول المراجعات
ALTER TABLE reviews
ADD CONSTRAINT IF NOT EXISTS fk_reviews_user
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE reviews
ADD CONSTRAINT IF NOT EXISTS fk_reviews_doctor
FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE;

-- 2. منع الحجز المزدوج ومعالجة Race Conditions
-- Prevent double booking and handle race conditions

-- منع الحجز المزدوج باستخدام UNIQUE INDEX
CREATE UNIQUE INDEX IF NOT EXISTS idx_unique_booking
ON appointments(doctor_id, appointment_date, appointment_time)
WHERE status IN ('confirmed', 'pending');

-- 3. تحسين الأداء بإضافة الفهارس المناسبة
-- Performance optimization with proper indexes

-- فهارس للبحث السريع
CREATE INDEX IF NOT EXISTS idx_appointments_date ON appointments(appointment_date);
CREATE INDEX IF NOT EXISTS idx_appointments_doctor ON appointments(doctor_id);
CREATE INDEX IF NOT EXISTS idx_appointments_user ON appointments(user_id);
CREATE INDEX IF NOT EXISTS idx_appointments_status ON appointments(status);

-- فهارس للمستخدمين
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_type ON users(user_type);
CREATE INDEX IF NOT EXISTS idx_users_active ON users(is_active);

-- فهارس للأطباء
CREATE INDEX IF NOT EXISTS idx_doctors_specialty ON doctors(specialty);
CREATE INDEX IF NOT EXISTS idx_doctors_rating ON doctors(rating);

-- فهارس للمراجعات
CREATE INDEX IF NOT EXISTS idx_reviews_doctor ON reviews(doctor_id);
CREATE INDEX IF NOT EXISTS idx_reviews_rating ON reviews(rating);

-- 4. تحسين البحث باستخدام FULLTEXT
-- Enhanced search with FULLTEXT indexes
CREATE FULLTEXT INDEX IF NOT EXISTS idx_search_users
ON users(full_name, email);

CREATE FULLTEXT INDEX IF NOT EXISTS idx_search_doctors
ON doctors(specialty, bio, clinic_address);

-- 5. جدول تسجيل محاولات تسجيل الدخول للأمان
-- Login attempts tracking for security
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    success TINYINT(1) DEFAULT 0,
    attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_ip_address (ip_address),
    INDEX idx_attempt_time (attempt_time)
);

-- 6. جدول للنسخ الاحتياطي والاستعادة
-- Backup and recovery tracking
CREATE TABLE IF NOT EXISTS backup_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    backup_type ENUM('daily', 'weekly', 'monthly') NOT NULL,
    backup_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    file_name VARCHAR(255) NOT NULL,
    file_size BIGINT,
    checksum VARCHAR(64),
    status ENUM('success', 'failed') DEFAULT 'success',
    error_message TEXT,
    INDEX idx_backup_date (backup_date),
    INDEX idx_backup_type (backup_type)
);

-- 7. إضافة حقول للأمان المتقدم
-- Advanced security fields
ALTER TABLE users
ADD COLUMN IF NOT EXISTS failed_login_attempts INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS last_failed_login DATETIME,
ADD COLUMN IF NOT EXISTS account_locked_until DATETIME,
ADD COLUMN IF NOT EXISTS password_changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS last_login_ip VARCHAR(45),
ADD COLUMN IF NOT EXISTS last_login_user_agent TEXT;

-- 8. جدول للتوكنات الأمنية (CSRF tokens)
-- Security tokens table
CREATE TABLE IF NOT EXISTS security_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    token VARCHAR(255) NOT NULL,
    token_type ENUM('csrf', 'password_reset', 'email_verification') NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
);

-- 9. تحسين جدول المواعيد بحقول إضافية
-- Enhance appointments table with additional fields
ALTER TABLE appointments
ADD COLUMN IF NOT EXISTS timezone VARCHAR(50) DEFAULT 'UTC',
ADD COLUMN IF NOT EXISTS reminder_sent TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS confirmation_sent TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS created_from_ip VARCHAR(45),
ADD COLUMN IF NOT EXISTS user_agent TEXT;

-- 10. جدول للإشعارات والتنبيهات
-- Notifications and alerts table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('appointment_reminder', 'booking_confirmation', 'cancellation', 'reschedule') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    related_id INT,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_type (type),
    INDEX idx_created_at (created_at),
    INDEX idx_read_status (is_read)
);

-- 11. منع القيم الفارغة والبيانات غير الصالحة
-- Prevent invalid data with constraints
ALTER TABLE appointments
ADD CONSTRAINT IF NOT EXISTS chk_appointment_date
CHECK (appointment_date >= CURDATE());

ALTER TABLE appointments
ADD CONSTRAINT IF NOT EXISTS chk_appointment_time
CHECK (appointment_time BETWEEN '08:00:00' AND '22:00:00');

-- 12. إضافة جدول للإعدادات والتفضيلات
-- Settings and preferences table
CREATE TABLE IF NOT EXISTS user_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    timezone VARCHAR(50) DEFAULT 'UTC',
    language VARCHAR(10) DEFAULT 'ar',
    email_notifications TINYINT(1) DEFAULT 1,
    sms_notifications TINYINT(1) DEFAULT 1,
    push_notifications TINYINT(1) DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_id (user_id)
);

-- 13. جدول للأداء والإحصائيات
-- Performance and analytics table
CREATE TABLE IF NOT EXISTS system_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    metric_name VARCHAR(100) NOT NULL,
    metric_value DECIMAL(10,2),
    recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_metric_name (metric_name),
    INDEX idx_recorded_at (recorded_at)
);

-- 14. إنشاء stored procedures للأمان
-- Security stored procedures
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS sp_secure_login_attempt(
    IN p_email VARCHAR(255),
    IN p_ip_address VARCHAR(45),
    IN p_user_agent TEXT,
    IN p_success TINYINT(1)
)
BEGIN
    INSERT INTO login_attempts (email, ip_address, user_agent, success, attempt_time)
    VALUES (p_email, p_ip_address, p_user_agent, p_success, NOW());

    -- تحديث عدد المحاولات الفاشلة
    IF p_success = 0 THEN
        UPDATE users
        SET failed_login_attempts = failed_login_attempts + 1,
            last_failed_login = NOW()
        WHERE email = p_email;
    ELSE
        UPDATE users
        SET failed_login_attempts = 0,
            last_login_ip = p_ip_address,
            last_login_user_agent = p_user_agent
        WHERE email = p_email;
    END IF;
END//

CREATE PROCEDURE IF NOT EXISTS sp_check_account_lock(
    IN p_email VARCHAR(255),
    OUT p_is_locked TINYINT(1)
)
BEGIN
    SELECT IF(account_locked_until > NOW(), 1, 0) INTO p_is_locked
    FROM users
    WHERE email = p_email;
END//

DELIMITER ;

-- 15. إنشاء triggers للأمان والتدقيق
-- Security and audit triggers
DELIMITER //

CREATE TRIGGER IF NOT EXISTS tr_user_update_audit
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    IF OLD.email != NEW.email THEN
        -- تسجيل تغيير البريد الإلكتروني
        INSERT INTO system_analytics (metric_name, metric_value)
        VALUES ('email_change', 1);
    END IF;
END//

CREATE TRIGGER IF NOT EXISTS tr_appointment_insert_audit
AFTER INSERT ON appointments
FOR EACH ROW
BEGIN
    INSERT INTO system_analytics (metric_name, metric_value)
    VALUES ('new_appointment', 1);
END//

DELIMITER ;

-- 16. تحسين الأداء باستخدام views
-- Performance optimization with views
CREATE OR REPLACE VIEW vw_doctor_availability AS
SELECT
    u.id as doctor_id,
    u.full_name,
    d.specialty,
    d.clinic_address,
    COUNT(a.id) as total_appointments,
    AVG(r.rating) as avg_rating
FROM users u
LEFT JOIN doctors d ON u.id = d.user_id
LEFT JOIN appointments a ON u.id = a.doctor_id AND a.status IN ('confirmed', 'pending')
LEFT JOIN reviews r ON u.id = r.doctor_id
WHERE u.user_type = 'doctor'
GROUP BY u.id;

-- 17. إنشاء جدول للأخطاء والتنبيهات
-- Error logging and alerts
CREATE TABLE IF NOT EXISTS error_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    error_type VARCHAR(50) NOT NULL,
    error_message TEXT,
    file_name VARCHAR(255),
    line_number INT,
    user_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    resolved TINYINT(1) DEFAULT 0,
    INDEX idx_error_type (error_type),
    INDEX idx_created_at (created_at),
    INDEX idx_resolved (resolved)
);

-- 18. إضافة constraints للتحقق من البيانات
-- Data validation constraints
ALTER TABLE users
ADD CONSTRAINT IF NOT EXISTS chk_email_format
CHECK (email REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$');

ALTER TABLE users
ADD CONSTRAINT IF NOT EXISTS chk_phone_format
CHECK (phone REGEXP '^[0-9+]+$');

-- 19. تحسين جدول الحجوزات بحقول إضافية للأمان
-- Enhanced appointments table with security fields
ALTER TABLE appointments
ADD COLUMN IF NOT EXISTS booking_hash VARCHAR(64) UNIQUE,
ADD COLUMN IF NOT EXISTS verification_code VARCHAR(6),
ADD COLUMN IF NOT EXISTS verification_expires DATETIME;

-- 20. إنشاء جدول للجلسات الأمنية
-- Secure sessions table
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at DATETIME NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_session_token (session_token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
);
