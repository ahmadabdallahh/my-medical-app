<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Build absolute hospital base URL for consistent redirects
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$script_path = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$hospital_segment = '/hospital';
$hospital_pos = strpos($script_path, $hospital_segment);
if ($hospital_pos !== false) {
    $hospital_path = substr($script_path, 0, $hospital_pos + strlen($hospital_segment));
} else {
    $hospital_path = $script_path;
}
$hospital_base_url = rtrim("$protocol://$host$hospital_path", '/');

// Check if user is not logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    $_SESSION['error'] = 'يجب تسجيل الدخول أولاً';
    header('Location: ' . $hospital_base_url . '/login.php');
    exit();
}

// Check if user has hospital role
if ($_SESSION['user_type'] !== 'hospital') {
    $_SESSION['error'] = 'غير مصرح لك بالوصول إلى هذه الصفحة';
    header('Location: ' . $hospital_base_url . '/index.php');
    exit();
}

// Get hospital ID from session
if (!isset($_SESSION['hospital_id'])) {
    // If hospital_id is not set in session, try to get it from database
    $root_dir = dirname(dirname(__DIR__));
    require_once $root_dir . '/config/database.php';
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT id FROM hospitals WHERE email = (SELECT email FROM users WHERE id = ?)");
        $stmt->execute([$_SESSION['user_id']]);
        $hospital = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($hospital) {
            $_SESSION['hospital_id'] = $hospital['id'];
        } else {
            // If no hospital found for this user, redirect to login
            $_SESSION['error'] = 'لم يتم العثور على بيانات المستشفى';
            session_unset();
            session_destroy();
            header('Location: ' . $hospital_base_url . '/login.php');
            exit();
        }
    } catch (PDOException $e) {
        error_log("Database error in auth_check.php: " . $e->getMessage());
        // Don't redirect, just set a default hospital_id to prevent errors
        // The user can still use the system but won't see hospital-specific data
        if (!isset($_SESSION['hospital_id'])) {
            $_SESSION['hospital_id'] = 1; // Default fallback
        }
    }
}

// Define global hospital_id for use in other files
$hospital_id = $_SESSION['hospital_id'];

// Check if account is active
if (isset($_SESSION['account_status']) && $_SESSION['account_status'] !== 'active') {
    session_destroy();
    $_SESSION['error'] = 'حسابك غير نشط. يرجى التواصل مع الدعم الفني';
    header('Location: ../login.php');
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Session timeout after 30 minutes of inactivity
$inactive = 1800; // 30 minutes in seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactive)) {
    session_unset();
    session_destroy();
    header('Location: ' . $hospital_base_url . '/login.php?timeout=1');
    exit();
}

// Regenerate session ID periodically to prevent session fixation
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} else if (time() - $_SESSION['created'] > 1800) { // 30 minutes
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// Security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Prevent caching of sensitive pages
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
?>
