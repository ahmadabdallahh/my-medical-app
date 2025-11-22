<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a hospital
if (!is_logged_in() || $_SESSION['user_type'] !== 'hospital') {
    header('Location: ../login.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Get hospital details
$stmt = $conn->prepare("SELECT * FROM hospitals WHERE id = (SELECT id FROM hospitals WHERE email = (SELECT email FROM users WHERE id = ?))");
// Note: This query assumes a link between users and hospitals via email or some other mechanism. 
// Based on the schema, hospitals table doesn't have a user_id. 
// I need to establish how a user is linked to a hospital.
// For now, let's assume the user table has a direct link or we use the email.
// Let's check the users table schema again.
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم المستشفى - Health Tech</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen overflow-hidden">
