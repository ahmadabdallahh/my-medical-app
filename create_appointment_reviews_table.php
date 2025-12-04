<?php
require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();

try {
    // Create appointment_reviews table
    $sql = "CREATE TABLE IF NOT EXISTS appointment_reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        appointment_id INT NOT NULL,
        user_id INT NOT NULL,
        rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_appointment_review (appointment_id, user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $conn->exec($sql);
    echo "Table 'appointment_reviews' created successfully.\n";

} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>
