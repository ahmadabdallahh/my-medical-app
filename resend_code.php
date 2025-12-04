<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$email = $_GET['email'] ?? '';

if (empty($email)) {
    $_SESSION['error_msg'] = 'البريد الإلكتروني مطلوب.';
    header("Location: login.php");
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT id, is_verified FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    if ($user['is_verified']) {
        $_SESSION['success_msg'] = 'حسابك مفعل بالفعل. يمكنك تسجيل الدخول.';
        header("Location: login.php");
        exit;
    }

    // Generate new code
    $new_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

    $update = $conn->prepare("UPDATE users SET verification_code = ? WHERE id = ?");
    $update->execute([$new_code, $user['id']]);

    if (send_verification_email($email, $new_code)) {
        $_SESSION['success_msg'] = 'تم إعادة إرسال رمز التحقق بنجاح.';
    } else {
        $_SESSION['error_msg'] = 'حدث خطأ أثناء إرسال البريد الإلكتروني.';
    }
} else {
    $_SESSION['error_msg'] = 'البريد الإلكتروني غير موجود.';
}

header("Location: verify_email.php?email=" . urlencode($email));
exit;
?>
