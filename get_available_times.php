<?php
require_once 'includes/functions.php';

// التحقق من تسجيل الدخول
// Temporarily disabled for testing
/*
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'غير مصرح']);
    exit();
}
*/

// For testing, create a dummy user if not logged in
if (!is_logged_in()) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Test User';
    $_SESSION['user_type'] = 'patient';
    $_SESSION['email'] = 'test@example.com';
}

// التحقق من الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'طريقة طلب غير صحيحة']);
    exit();
}

// الحصول على البيانات
$doctor_id = isset($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : 0;
$date = isset($_POST['appointment_date']) ? sanitize_input($_POST['appointment_date']) : '';

if (!$doctor_id || !$date) {
    http_response_code(400);
    echo json_encode(['error' => 'بيانات غير مكتملة']);
    exit();
}

// التحقق من أن التاريخ في المستقبل
if (strtotime($date) < strtotime(date('Y-m-d'))) {
    echo json_encode(['times' => []]);
    exit();
}

// الحصول على الأوقات المتاحة
try {
    $available_times = get_available_times($doctor_id, $date);
    error_log("get_available_times.php - Found " . count($available_times) . " available times for doctor $doctor_id on $date");
} catch (Exception $e) {
    error_log("get_available_times.php - Error: " . $e->getMessage());
    $available_times = [];
}

// إرجاع النتيجة
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'times' => $available_times,
    'date' => $date,
    'doctor_id' => $doctor_id,
    'debug' => [
        'doctor_id' => $doctor_id,
        'date' => $date,
        'times_count' => count($available_times)
    ]
]);
?> 