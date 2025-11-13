<?php
require_once 'includes/functions.php';

// التحقق من تسجيل الدخول
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'غير مصرح']);
    exit();
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
$available_times = get_available_times($doctor_id, $date);

// إرجاع النتيجة
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'times' => $available_times,
    'date' => $date,
    'doctor_id' => $doctor_id
]);
?> 