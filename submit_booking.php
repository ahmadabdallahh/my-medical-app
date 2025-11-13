<?php
session_start();
require_once 'config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// 1. Ensure user is logged in as a patient
if (!is_logged_in() || $_SESSION['user_type'] !== 'patient') {
    header('Location: login.php?error=auth_required');
    exit();
}

// 2. Check if the form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

// 3. Validate incoming data
$doctor_user_id = filter_input(INPUT_POST, 'doctor_id', FILTER_VALIDATE_INT);
$appointment_date = filter_input(INPUT_POST, 'appointment_date');
$appointment_time = filter_input(INPUT_POST, 'appointment_time');
$clinic_id = filter_input(INPUT_POST, 'clinic_id', FILTER_VALIDATE_INT);

// 4. Basic validation
if (!$doctor_user_id || !$appointment_date || !$appointment_time || !$clinic_id) {
    $_SESSION['error_message'] = 'بيانات الحجز غير مكتملة.';
    header('Location: book_appointment.php?doctor_id=' . $doctor_user_id);
    exit();
}

// 5. Get the real doctor_id from doctors table (convert from users.id)
try {
    $stmt = $conn->prepare("SELECT id FROM doctors WHERE user_id = ?");
    $stmt->execute([$doctor_user_id]);
    $doctor_record = $stmt->fetch();

    if (!$doctor_record) {
        $_SESSION['error_message'] = 'الطبيب المحدد غير موجود.';
        header('Location: book_appointment.php?doctor_id=' . $doctor_user_id);
        exit();
    }

    $doctor_id = $doctor_record['id'];

} catch (PDOException $e) {
    error_log('Doctor lookup error: ' . $e->getMessage());
    $_SESSION['error_message'] = 'حدث خطأ في العثور على بيانات الطبيب.';
    header('Location: book_appointment.php?doctor_id=' . $doctor_user_id);
    exit();
}

$user_id = $_SESSION['user_id'];

// 5. Check for duplicate appointments
try {
    $stmt = $conn->prepare("SELECT id FROM appointments WHERE user_id = ? AND doctor_id = ? AND appointment_date = ? AND appointment_time = ?");
    $stmt->execute([$user_id, $doctor_id, $appointment_date, $appointment_time]);
    if ($stmt->fetch()) {
        $_SESSION['error_message'] = 'لقد قمت بحجز هذا الموعد من قبل.';
        header('Location: book_appointment.php?doctor_id=' . $doctor_user_id);
        exit();
    }
} catch (PDOException $e) {
    error_log('Duplicate check error: ' . $e->getMessage());
}

// 6. Double-check availability (race condition protection)
$is_still_available = false;
try {
    $stmt = $conn->prepare("SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ?");
    $stmt->execute([$doctor_id, $appointment_date, $appointment_time]);
    if (!$stmt->fetch()) {
        $is_still_available = true;
    }
} catch (PDOException $e) {
    error_log('Availability check error: ' . $e->getMessage());
}

if (!$is_still_available) {
    $_SESSION['error_message'] = 'عذراً، هذا الموعد تم حجزه للتو. الرجاء اختيار موعد آخر.';
    header('Location: book_appointment.php?doctor_id=' . $doctor_user_id);
    exit();
}

// 7. Insert the appointment into the database
try {
    $sql = "INSERT INTO appointments (user_id, doctor_id, clinic_id, appointment_date, appointment_time, status) VALUES (?, ?, ?, ?, ?, 'confirmed')";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id, $doctor_id, $clinic_id, $appointment_date, $appointment_time]);

    // 8. Redirect to a success page (e.g., patient dashboard)
    $_SESSION['success_message'] = 'تم تأكيد حجزك بنجاح!';
    header('Location: patient/index.php'); // Assuming a patient dashboard exists
    exit();

} catch (PDOException $e) {
    error_log('Booking submission error: ' . $e->getMessage());
    $_SESSION['error_message'] = 'حدث خطأ فني أثناء تأكيد الحجز. الرجاء المحاولة لاحقاً.';
    header('Location: book_appointment.php?doctor_id=' . $doctor_user_id);
    exit();
}
