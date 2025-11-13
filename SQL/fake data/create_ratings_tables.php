<?php
require_once 'config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

echo "<h2>Creating Ratings and Reviews Tables</h2>";

try {
    // Create doctor_ratings table
    $sql = "
    CREATE TABLE IF NOT EXISTS doctor_ratings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        doctor_id INT NOT NULL,
        user_id INT NOT NULL,
        rating TINYINT(1) NOT NULL COMMENT '1-5 stars',
        review TEXT NULL COMMENT 'Optional review text',
        is_anonymous TINYINT(1) DEFAULT 0 COMMENT 'Whether the review is anonymous',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_doctor_user_rating (doctor_id, user_id),
        
        INDEX idx_doctor_id (doctor_id),
        INDEX idx_user_id (user_id),
        INDEX idx_rating (rating),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $conn->exec($sql);
    echo "<p style='color: green;'>✅ doctor_ratings table created successfully</p>";
    
    // Add calculated_rating and total_ratings to doctors table
    $conn->exec("ALTER TABLE doctors ADD COLUMN IF NOT EXISTS calculated_rating DECIMAL(3,2) DEFAULT 0.00 COMMENT 'Calculated average rating'");
    $conn->exec("ALTER TABLE doctors ADD COLUMN IF NOT EXISTS total_ratings INT DEFAULT 0 COMMENT 'Total number of ratings'");
    echo "<p style='color: green;'>✅ doctors table updated with rating columns</p>";
    
    // Insert sample ratings
    $sample_ratings = [
        [220, 1, 5, 'دكتور ممتاز جداً، متعاون ويفصل بوضوح'],
        [220, 2, 4, 'جيد جداً، استشارته كانت مفيدة'],
        [220, 3, 5, 'أفضل دكتور تعاملت معه'],
        [221, 1, 4, 'دكتور جيد ومتعاون'],
        [221, 4, 3, 'مقبول، لكن يمكن تحسين خدمة الانتظار'],
        [222, 2, 5, 'محترف جداً ومتخصص في مجاله'],
        [222, 5, 4, 'استشارته كانت ممتازة']
    ];
    
    foreach ($sample_ratings as $rating) {
        $stmt = $conn->prepare("
            INSERT IGNORE INTO doctor_ratings (doctor_id, user_id, rating, review) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute($rating);
    }
    echo "<p style='color: green;'>✅ Sample ratings inserted</p>";
    
    // Update calculated ratings
    $stmt = $conn->prepare("
        UPDATE doctors d SET 
            d.calculated_rating = (
                SELECT COALESCE(AVG(rating), 0) 
                FROM doctor_ratings 
                WHERE doctor_id = d.user_id
            ),
            d.total_ratings = (
                SELECT COUNT(*) 
                FROM doctor_ratings 
                WHERE doctor_id = d.user_id
            )
    ");
    $stmt->execute();
    echo "<p style='color: green;'>✅ Calculated ratings updated</p>";
    
    // Show table structure
    $stmt = $conn->prepare("DESCRIBE doctor_ratings");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Table Structure:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . $col['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test the table
    echo "<h3>Testing the Tables:</h3>";
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM doctor_ratings");
    $stmt->execute();
    $count = $stmt->fetch()['count'];
    echo "<p>Total ratings: " . $count . "</p>";
    
    // Show some sample data
    $stmt = $conn->prepare("
        SELECT dr.*, u.full_name as user_name, d.full_name as doctor_name 
        FROM doctor_ratings dr
        LEFT JOIN users u ON dr.user_id = u.id
        LEFT JOIN doctors d ON dr.doctor_id = d.user_id
        LIMIT 5
    ");
    $stmt->execute();
    $ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Sample Ratings:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Doctor</th><th>User</th><th>Rating</th><th>Review</th></tr>";
    
    foreach ($ratings as $rating) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($rating['doctor_name']) . "</td>";
        echo "<td>" . htmlspecialchars($rating['user_name']) . "</td>";
        echo "<td>" . str_repeat('⭐', $rating['rating']) . "</td>";
        echo "<td>" . htmlspecialchars($rating['review']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<br><br>";
echo "<h3>Next Steps:</h3>";
echo "<p>1. Go to doctor profile page: <a href='/App-Demo/doctor_profile.php?id=220'>Doctor Profile</a></p>";
echo "<p>2. Test the rating and review system</p>";

echo "<br><br>";
echo "<a href='/App-Demo/login.php'>Go to Login</a> | ";
echo "<a href='/App-Demo/index.php'>Go to Home</a>";
?>
