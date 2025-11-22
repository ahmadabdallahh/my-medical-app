<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

$name = "Test Hospital " . rand(1000, 9999);
$email = "test_hospital_" . rand(1000, 9999) . "@demo.com";
$phone = "0123456789";

try {
    echo "Testing insertion for: $name ($email)\n";
    $stmt_hospital = $conn->prepare("INSERT INTO hospitals (name, email, phone, type, description) VALUES (?, ?, ?, 'خاص', 'تم التسجيل عبر الموقع')");
    $stmt_hospital->execute([$name, $email, $phone]);
    echo "Success! Hospital ID: " . $conn->lastInsertId() . "\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
