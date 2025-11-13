<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Create database connection
$db = new Database();
$conn = $db->getConnection();

// Admin account details
$admin_data = [
    'email' => 'admin@shifa.com',
    'password' => 'admin123', // You can change this
    'full_name' => 'مدير النظام',
    'phone' => '01234567890',
    'gender' => 'male',
    'user_type' => 'admin'
];

try {
    // 1. إضافة عمود user_type إذا لم يكن موجوداً
    $check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'user_type'");
    if ($check_column->rowCount() == 0) {
        $conn->exec("ALTER TABLE users ADD COLUMN user_type ENUM('admin', 'patient', 'doctor', 'hospital') DEFAULT 'patient' AFTER email");
        echo "<p style='color: green;'>✓ تم إضافة عمود user_type</p>";
    }

    // 2. نسخ البيانات من role إلى user_type إذا كان role موجوداً
    $check_role = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
    if ($check_role->rowCount() > 0) {
        $conn->exec("UPDATE users SET user_type = role WHERE (user_type IS NULL OR user_type = '') AND role IS NOT NULL");
        echo "<p style='color: green;'>✓ تم نسخ البيانات من role إلى user_type</p>";
    }

    // 3. Check if admin already exists
    $check_stmt = $conn->prepare("SELECT id, user_type FROM users WHERE email = ? OR user_type = 'admin' LIMIT 1");
    $check_stmt->execute([$admin_data['email']]);
    $existing_admin = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_admin) {
        // Update existing user to admin if not already
        if ($existing_admin['user_type'] != 'admin') {
            $update_stmt = $conn->prepare("UPDATE users SET user_type = 'admin' WHERE id = ?");
            $update_stmt->execute([$existing_admin['id']]);
            echo "<p style='color: green;'>✓ تم تحديث المستخدم الموجود إلى Admin</p>";
        }
        echo "<div style='background: #e8f5e9; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
        echo "<h3>حساب Admin موجود بالفعل!</h3>";
        echo "<p><strong>Email:</strong> {$admin_data['email']}</p>";
        echo "<p><strong>Password:</strong> {$admin_data['password']}</p>";
        echo "<p><strong>User Type:</strong> admin</p>";
        echo "</div>";
    } else {
        // Hash the password
        $hashed_password = hash_password($admin_data['password']);

        // Insert admin account - try with different column combinations
        try {
            // Try with username
            $insert_stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, phone, gender, user_type, created_at, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 1)");
            $result = $insert_stmt->execute([
                'admin',
                $admin_data['email'],
                $hashed_password,
                $admin_data['full_name'],
                $admin_data['phone'],
                $admin_data['gender'],
                $admin_data['user_type']
            ]);
        } catch (PDOException $e) {
            // Try without username
            $insert_stmt = $conn->prepare("INSERT INTO users (email, password, full_name, phone, gender, user_type, created_at, is_active) VALUES (?, ?, ?, ?, ?, ?, NOW(), 1)");
            $result = $insert_stmt->execute([
                $admin_data['email'],
                $hashed_password,
                $admin_data['full_name'],
                $admin_data['phone'],
                $admin_data['gender'],
                $admin_data['user_type']
            ]);
        }

        if ($result) {
            $admin_id = $conn->lastInsertId();
            echo "<div style='background: #e8f5e9; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
            echo "<h3 style='color: green;'>✓ تم إنشاء حساب Admin بنجاح!</h3>";
            echo "<p><strong>Email:</strong> {$admin_data['email']}</p>";
            echo "<p><strong>Password:</strong> {$admin_data['password']}</p>";
            echo "<p><strong>User Type:</strong> admin</p>";
            echo "<p><strong>ID:</strong> {$admin_id}</p>";
            echo "<p><strong>ملاحظة:</strong> يرجى تغيير كلمة المرور بعد تسجيل الدخول لأسباب أمنية.</p>";
            echo "</div>";
        } else {
            echo "<p style='color: red;'>✗ حدث خطأ أثناء إنشاء حساب Admin.</p>";
        }
    }

    // 4. عرض جميع المستخدمين
    echo "<hr><h3>جميع المستخدمين:</h3>";
    $users_stmt = $conn->query("SELECT id, email, full_name, COALESCE(user_type, role, 'N/A') as user_type FROM users ORDER BY id");
    $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($users) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Email</th><th>الاسم</th><th>النوع</th></tr>";
        foreach ($users as $user) {
            $type_color = $user['user_type'] == 'admin' ? 'red' : ($user['user_type'] == 'doctor' ? 'blue' : 'green');
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['full_name']}</td>";
            echo "<td style='color: {$type_color}; font-weight: bold;'>{$user['user_type']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ خطأ في قاعدة البيانات: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>

<br><br>
<a href="login.php">اذهب إلى صفحة تسجيل الدخول</a>
