<?php
require_once 'config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->query("SELECT id, user_id FROM doctors LIMIT 5");
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($doctors) {
        foreach ($doctors as $doctor) {
            echo "Doctor ID: " . $doctor['id'] . ", User ID: " . $doctor['user_id'] . "\n";
        }
    } else {
        echo "No doctors found.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
