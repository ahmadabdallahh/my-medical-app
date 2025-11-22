<?php
require_once 'config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->query("SELECT id, full_name FROM users LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($users) {
        foreach ($users as $user) {
            echo "ID: " . $user['id'] . ", Name: " . $user['full_name'] . "\n";
        }
    } else {
        echo "No users found.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
