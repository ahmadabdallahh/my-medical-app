<?php
session_start();
require_once '../config/database.php';
require_once '../includes/notification_functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

$result = mark_all_as_read($conn, $user_id);
echo json_encode(['success' => $result]);
?>
