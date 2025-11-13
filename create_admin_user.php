<?php
// Create admin user script
require_once 'config/database.php';

echo "Creating admin user...\n";

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    if (!$conn) {
        die("Database connection failed\n");
    }
    
    // Check if admin user already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = 'admin@medical.com'");
    $stmt->execute();
    
    if ($stmt->fetch()) {
        echo "Admin user already exists. Updating...\n";
        $update_stmt = $conn->prepare("UPDATE users SET role = 'admin', user_type = 'admin', is_active = 1 WHERE email = 'admin@medical.com'");
        $update_stmt->execute();
    } else {
        echo "Creating new admin user...\n";
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        
        $insert_stmt = $conn->prepare("
            INSERT INTO users (
                username, full_name, email, password, phone, gender, role, user_type, is_active, created_at, updated_at
            ) VALUES (
                'admin', 'Administrator', 'admin@medical.com', ?, '01000000000', 'male', 'admin', 'admin', 1, NOW(), NOW()
            )
        ");
        
        $insert_stmt->execute([$password]);
    }
    
    echo "Admin user created/updated successfully!\n";
    echo "Login credentials:\n";
    echo "Email: admin@medical.com\n";
    echo "Password: admin123\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
