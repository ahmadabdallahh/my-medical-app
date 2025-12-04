<?php
require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();

$tables = ['reminders', 'reminder_settings', 'push_notifications'];

foreach ($tables as $table) {
    echo "Table: $table\n";
    $stmt = $conn->query("DESCRIBE $table");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo "\n-----------------------------------\n";
}
?>
