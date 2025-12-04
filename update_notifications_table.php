<?php
require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();

try {
    // Check if columns exist and add them if needed
    $stmt = $conn->query("DESCRIBE push_notifications");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('related_id', $columns)) {
        $conn->exec("ALTER TABLE push_notifications ADD COLUMN related_id INT NULL AFTER type");
        echo "Added 'related_id' column.\n";
    }
    
    if (!in_array('action_url', $columns)) {
        $conn->exec("ALTER TABLE push_notifications ADD COLUMN action_url VARCHAR(255) NULL AFTER related_id");
        echo "Added 'action_url' column.\n";
    }
    
    echo "push_notifications table is ready.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
