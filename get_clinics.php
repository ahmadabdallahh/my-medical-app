<?php
require_once 'config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->query("SELECT id, name FROM clinics LIMIT 5");
    $clinics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($clinics) {
        foreach ($clinics as $clinic) {
            echo "Clinic ID: " . $clinic['id'] . ", Name: " . $clinic['name'] . "\n";
        }
    } else {
        echo "No clinics found.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
