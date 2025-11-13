-- إضافة عمود user_type إذا لم يكن موجوداً
-- Add user_type column if it doesn't exist

-- التحقق من وجود العمود وإضافته
SET @dbname = DATABASE();
SET @tablename = "users";
SET @columnname = "user_type";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 'Column already exists.'",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " ENUM('admin', 'patient', 'doctor', 'hospital') DEFAULT 'patient' AFTER email")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- نسخ البيانات من role إلى user_type إذا كان role موجوداً
UPDATE users
SET user_type = role
WHERE (user_type IS NULL OR user_type = '')
  AND role IS NOT NULL
  AND role != '';

-- إنشاء حساب Admin إذا لم يكن موجوداً
INSERT INTO users (email, password, full_name, phone, gender, user_type, created_at, is_active)
SELECT
    'admin@shifa.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: admin123
    'مدير النظام',
    '01234567890',
    'male',
    'admin',
    NOW(),
    1
WHERE NOT EXISTS (
    SELECT 1 FROM users
    WHERE (email = 'admin@shifa.com' OR email = 'admin@demo.com')
       OR (user_type = 'admin')
);

-- تحديث أي مستخدم موجود ببريد admin@shifa.com أو admin@demo.com ليكون admin
UPDATE users
SET user_type = 'admin'
WHERE (email = 'admin@shifa.com' OR email = 'admin@demo.com')
  AND (user_type IS NULL OR user_type != 'admin');

-- عرض جميع المستخدمين مع user_type
SELECT id, email, full_name, COALESCE(user_type, role, 'N/A') as user_type, created_at
FROM users
ORDER BY id;

