<?php
session_start();
require_once 'config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: login.php');
    exit();
}

// Redirect to appropriate dashboard based on user type
$user_type = $_SESSION['user_type'] ?? '';

switch ($user_type) {
    case 'admin':
        header('Location: admin/index.php');
        exit();
    case 'doctor':
        header('Location: doctor/index.php');
        exit();
    case 'patient':
        // Patient dashboard is this file itself, so we'll show the dashboard
        // But first check if patient/index.php exists, if so redirect there
        if (file_exists('patient/index.php')) {
            header('Location: patient/index.php');
            exit();
        }
        // Otherwise, stay on dashboard.php
        break;
    case 'hospital':
        header('Location: hospital/index.php');
        exit();
    default:
        // If no specific user type, redirect to login
        header('Location: login.php');
        exit();
}
exit();
?>
