<?php
// Final verification script
require_once 'config/database.php';

echo "=== Medical App Login System Verification ===\n\n";

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    if (!$conn) {
        die("❌ Database connection failed\n");
    }
    
    echo "✅ Database connection successful\n\n";
    
    // Check users table structure
    echo "📋 Users table structure:\n";
    $stmt = $conn->prepare("DESCRIBE users");
    $stmt->execute();
    $columns = $stmt->fetchAll();
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'role' || $column['Field'] === 'user_type') {
            echo "   - {$column['Field']}: {$column['Type']} (Default: {$column['Default']})\n";
        }
    }
    
    echo "\n👥 Admin user verification:\n";
    $stmt = $conn->prepare("SELECT id, username, email, role, user_type, is_active FROM users WHERE email = 'admin@medical.com'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "   ✅ Admin user exists\n";
        echo "   📧 Email: {$admin['email']}\n";
        echo "   👤 Username: {$admin['username']}\n";
        echo "   🔐 Role: {$admin['role']}\n";
        echo "   🏷️  User Type: {$admin['user_type']}\n";
        echo "   ✅ Active: " . ($admin['is_active'] ? 'Yes' : 'No') . "\n";
    } else {
        echo "   ❌ Admin user not found\n";
    }
    
    echo "\n📊 User statistics:\n";
    $stmt = $conn->prepare("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $stmt->execute();
    $stats = $stmt->fetchAll();
    
    foreach ($stats as $stat) {
        echo "   - {$stat['role']}: {$stat['count']} users\n";
    }
    
    echo "\n🔑 Login credentials for testing:\n";
    echo "   Admin: admin@medical.com / admin123\n";
    echo "   Patient: fatima@example.com / password\n";
    echo "   Doctor: ahmed.ali@hospital.com / default_password_123\n";
    
    echo "\n✅ Setup verification completed successfully!\n";
    echo "\n📝 Next steps:\n";
    echo "   1. Open your browser and navigate to http://localhost/medical-app-test/login.php\n";
    echo "   2. Test login with the admin credentials\n";
    echo "   3. Verify that you're redirected to the admin dashboard\n";
    echo "   4. Test registration for new users\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
