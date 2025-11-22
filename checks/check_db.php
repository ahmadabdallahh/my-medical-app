<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "--- Users Table Schema ---\n";
$stmt = $conn->query("DESCRIBE users");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    echo $col['Field'] . " | " . $col['Type'] . "\n";
}

echo "\n--- User Data ---\n";
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute(['hospital@demo.com']);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($user);
?>
