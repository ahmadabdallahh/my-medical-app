<?php
session_start();
require_once 'config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Ensure user is logged in
if (!is_logged_in()) {
    header('Location: login.php?error=login_required');
    exit();
}

// Get appointment ID
$appointment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

if (!$appointment_id) {
    // Determine redirect path based on user type
    $redirect_path = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'patient')
        ? 'patient/appointments.php?error=invalid_appointment'
        : 'appointments.php?error=invalid_appointment';
    header('Location: ' . $redirect_path);
    exit();
}

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Verify appointment exists and belongs to user
try {
    $stmt = $conn->prepare("SELECT id, status, appointment_date, user_id FROM appointments WHERE id = ? AND user_id = ?");
    $stmt->execute([$appointment_id, $user_id]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appointment) {
        $redirect_path = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'patient')
            ? 'patient/appointments.php?error=appointment_not_found'
            : 'appointments.php?error=appointment_not_found';
        header('Location: ' . $redirect_path);
        exit();
    }

    // Check if appointment can be cancelled
    if ($appointment['status'] === 'cancelled') {
        $redirect_path = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'patient')
            ? 'patient/appointments.php?error=already_cancelled'
            : 'appointments.php?error=already_cancelled';
        header('Location: ' . $redirect_path);
        exit();
    }

    if ($appointment['status'] === 'completed') {
        $redirect_path = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'patient')
            ? 'patient/appointments.php?error=cannot_cancel_completed'
            : 'appointments.php?error=cannot_cancel_completed';
        header('Location: ' . $redirect_path);
        exit();
    }

} catch (PDOException $e) {
    error_log('Cancel appointment error: ' . $e->getMessage());
    $redirect_path = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'patient')
        ? 'patient/appointments.php?error=database_error'
        : 'appointments.php?error=database_error';
    header('Location: ' . $redirect_path);
    exit();
}

// Cancel the appointment
if (cancel_appointment($appointment_id, $user_id)) {
    // Set success message
    $_SESSION['flash_message'] = [
        'type' => 'success',
        'message' => 'تم إلغاء الموعد بنجاح'
    ];
} else {
    // Set error message
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'حدث خطأ أثناء إلغاء الموعد. يرجى المحاولة مرة أخرى.'
    ];
}

// Redirect back to appointments page based on user type
$redirect_path = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'patient')
    ? 'patient/appointments.php'
    : 'appointments.php';
header('Location: ' . $redirect_path);
exit();
?>
