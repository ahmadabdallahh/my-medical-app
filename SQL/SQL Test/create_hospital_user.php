<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

$email = 'hospital@demo.com';
$password = '123456';
$name = 'مستشفى الشفاء الدولي';
$phone = '01000000000';

// 1. Check if user exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->fetch()) {
    echo "User with email $email already exists.\n";
} else {
    // 2. Create User
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, phone, user_type, role, is_active) VALUES (?, ?, ?, ?, 'hospital', 'hospital', 1)");
    $stmt->execute([$name, $email, $hashed_password, $phone]);
    $user_id = $conn->lastInsertId();
    echo "User created with ID: $user_id\n";

    // 3. Create Hospital
    // Check if hospital exists with this email
    $stmt = $conn->prepare("SELECT id FROM hospitals WHERE email = ?");
    $stmt->execute([$email]);
    if (!$stmt->fetch()) {
        $stmt = $conn->prepare("INSERT INTO hospitals (name, email, phone, address, type, description) VALUES (?, ?, ?, 'Cairo, Egypt', 'خاص', 'مستشفى تجريبي')");
        $stmt->execute([$name, $email, $phone]);
        $hospital_id = $conn->lastInsertId();
        echo "Hospital created with ID: $hospital_id\n";
    } else {
        echo "Hospital with email $email already exists.\n";
    }
    
    echo "Done! Login with:\nEmail: $email\nPassword: $password\n";
}
?>
