-- تحديث قاعدة البيانات لإضافة ميزات جديدة
-- تاريخ التحديث: 2025-01-08

-- إضافة عمود صورة الملف الشخصي لجدول المستخدمين
ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL AFTER user_type;

-- إنشاء جدول لرموز إعادة تعيين كلمة المرور
CREATE TABLE password_reset_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
);

-- إنشاء مجلد uploads إذا لم يكن موجوداً (سيتم إنشاؤه من PHP)
-- mkdir uploads/profile_images

-- تحديث بيانات المستخدمين الحاليين لإضافة صورة افتراضية
UPDATE users SET profile_image = 'default-avatar.png' WHERE profile_image IS NULL;

-- إضافة فهرس لتحسين الأداء
ALTER TABLE users ADD INDEX idx_email (email);
ALTER TABLE users ADD INDEX idx_username (username);

-- تحديث بعض البيانات التجريبية إذا لزم الأمر
-- يمكن إضافة المزيد حسب الحاجة
