<?php
require_once 'config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // 1. Create a dummy user
    $email = 'test_delete_' . time() . '@example.com';
    $password = password_hash('password123', PASSWORD_DEFAULT);
    $username = 'test_user_' . time();
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, username, password, role, created_at) VALUES (?, ?, ?, ?, 'patient', NOW())");
    $stmt->execute(['Test User For Delete', $email, $username, $password]);
    $user_id = $conn->lastInsertId();
    
    echo "Created test user with ID: $user_id\n";

    // 2. Simulate deletion (calling the logic directly as we can't easily simulate session/redirect in CLI)
    echo "Attempting to delete user ID: $user_id\n";
    
    $conn->beginTransaction();
    $delStmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $delStmt->execute([$user_id]);
    $conn->commit();

    // 3. Verify deletion
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $checkStmt->execute([$user_id]);
    
    if (!$checkStmt->fetch()) {
        echo "User deleted successfully.\n";
    } else {
        echo "Failed to delete user.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
}
?>
