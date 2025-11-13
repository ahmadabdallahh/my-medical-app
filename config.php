<?php
// config.php

// **Database Configuration** //
define('DB_HOST', 'localhost');
define('DB_NAME', 'medical_booking_test');
define('DB_USER', 'root');
define('DB_PASS', '');

$protocol = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$script_name = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$base_path = rtrim($script_name, '/') . '/';
define('BASE_URL', $protocol . '://' . $host . $base_path);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// **Create Database Connection** //
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log('Database Connection Error: ' . $e->getMessage());
    die('عذراً، حدث خطأ أثناء الاتصال بقاعدة البيانات. يرجى المحاولة مرة أخرى لاحقاً.');
}
