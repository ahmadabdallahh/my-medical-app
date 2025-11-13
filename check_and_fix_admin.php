<?php
/**
 * Script to check and fix Admin user in database
 * This script will:
 * 1. Check if the role column includes 'admin'
 * 2. Update the role column to include 'admin' if needed
 * 3. Check and update admin user
 */

require_once 'config.php';
require_once 'config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    echo "<h2>فحص وإصلاح قاعدة البيانات - Admin User</h2>";
    echo "<pre>";

    // 1. Check role column structure
    echo "1. فحص بنية عمود role:\n";
    $stmt = $conn->query("SHOW COLUMNS FROM users WHERE Field = 'role'");
    $role_column = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($role_column) {
        echo "   نوع العمود الحالي: " . $role_column['Type'] . "\n";

        // Check if 'admin' is in the enum
        if (strpos($role_column['Type'], 'admin') === false) {
            echo "   ⚠️ 'admin' غير موجود في enum - سيتم إضافته...\n";
            $conn->exec("ALTER TABLE `users` MODIFY `role` enum('patient','doctor','hospital','admin') DEFAULT 'patient'");
            echo "   ✅ تم تحديث عمود role بنجاح\n";
        } else {
            echo "   ✅ 'admin' موجود في enum\n";
        }
    }

    // 2. Check for admin users
    echo "\n2. البحث عن مستخدمين Admin:\n";
    $stmt = $conn->query("SELECT id, email, full_name, role, user_type FROM users WHERE role = 'admin' OR user_type = 'admin' OR email LIKE '%admin%'");
    $admin_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($admin_users)) {
        echo "   ⚠️ لم يتم العثور على أي مستخدم Admin\n";
        echo "   سيتم إنشاء مستخدم Admin جديد...\n";

        // Create admin user
        $admin_email = 'admin@medical.com';
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (username, full_name, email, password, phone, gender, role, user_type, is_active)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            'admin',
            'Administrator',
            $admin_email,
            $admin_password,
            '01000000000',
            'male',
            'admin',
            'admin',
            1
        ]);

        echo "   ✅ تم إنشاء مستخدم Admin:\n";
        echo "      Email: $admin_email\n";
        echo "      Password: admin123\n";
    } else {
        echo "   تم العثور على " . count($admin_users) . " مستخدم Admin:\n";
        foreach ($admin_users as $admin) {
            echo "   - ID: {$admin['id']}, Email: {$admin['email']}, Name: {$admin['full_name']}\n";
            echo "     Role: {$admin['role']}, User Type: {$admin['user_type']}\n";

            // Update if role is not 'admin'
            if ($admin['role'] !== 'admin') {
                echo "     ⚠️ Role ليس 'admin' - سيتم التحديث...\n";
                $update_stmt = $conn->prepare("UPDATE users SET role = 'admin', user_type = 'admin' WHERE id = ?");
                $update_stmt->execute([$admin['id']]);
                echo "     ✅ تم تحديث Role إلى 'admin'\n";
            }
        }
    }

    // 3. Show all users with their roles
    echo "\n3. جميع المستخدمين مع أنواعهم:\n";
    $stmt = $conn->query("SELECT id, email, full_name, role, user_type FROM users ORDER BY id LIMIT 10");
    $all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($all_users as $user) {
        echo "   - {$user['email']}: role='{$user['role']}', user_type='{$user['user_type']}'\n";
    }

    echo "\n✅ اكتمل الفحص!\n";
    echo "</pre>";

    echo "<p><a href='login.php'>العودة إلى صفحة تسجيل الدخول</a></p>";

} catch (PDOException $e) {
    echo "<pre>";
    echo "❌ خطأ: " . $e->getMessage() . "\n";
    echo "</pre>";
}
?>

