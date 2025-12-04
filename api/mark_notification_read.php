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

$data = json_decode(file_get_contents('php://input'), true);
$notification_id = $data['notification_id'] ?? 0;

if ($notification_id) {
    $result = mark_notification_as_read($conn, $notification_id, $user_id);
    echo json_encode(['success' => $result]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid notification ID']);
}
?>
