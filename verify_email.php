<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

session_start();

$errors = [];
$success = '';
$email = $_GET['email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $code = $_POST['verification_code'] ?? '';

    if (empty($email) || empty($code)) {
        $errors['general'] = 'يرجى إدخال البريد الإلكتروني ورمز التحقق.';
    } else {
        $db = new Database();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("SELECT id, verification_code FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if ($user['verification_code'] === $code) {
                // Verification successful
                $update = $conn->prepare("UPDATE users SET is_verified = 1, verification_code = NULL WHERE id = ?");
                $update->execute([$user['id']]);
                
                $_SESSION['success_msg'] = 'تم تفعيل حسابك بنجاح! يمكنك الآن تسجيل الدخول.';
                header("Location: login.php");
                exit;
            } else {
                $errors['code'] = 'رمز التحقق غير صحيح.';
            }
        } else {
            $errors['email'] = 'البريد الإلكتروني غير موجود.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفعيل الحساب - Health Tech</title>
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="image-section">
                <div class="image-content">
                    <img src="assets/images/doctor-illustration.png" alt="Doctor Illustration">
                    <div class="welcome-text">
                        <h2>تأكيد الحساب</h2>
                        <p>أدخل الرمز المرسل إلى بريدك الإلكتروني</p>
                    </div>
                </div>
            </div>
            <div class="form-section">
                <div class="form-content">
                    <div class="logo">
                        <i class="fas fa-heartbeat"></i>
                        <span>Health Tech</span>
                    </div>
                    <h3>تفعيل الحساب</h3>

                    <?php if (isset($_SESSION['success_msg'])): ?>
                        <div class="message success" style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px;"><?php echo $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?></div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['error_msg'])): ?>
                        <div class="message error" style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;"><?php echo $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?></div>
                    <?php endif; ?>

                    <?php if (!empty($errors['general'])): ?>
                        <div class="message error"><?php echo $errors['general']; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="verify_email.php">
                        <div class="form-group <?php echo isset($errors['email']) ? 'error' : ''; ?>">
                            <label for="email">البريد الإلكتروني</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" readonly required>
                            <?php if (isset($errors['email'])): ?><span class="error-message"><?php echo $errors['email']; ?></span><?php endif; ?>
                        </div>

                        <div class="form-group <?php echo isset($errors['code']) ? 'error' : ''; ?>">
                            <label for="verification_code">رمز التحقق (6 أرقام)</label>
                            <input type="text" id="verification_code" name="verification_code" maxlength="6" pattern="\d{6}" placeholder="XXXXXX" required>
                            <?php if (isset($errors['code'])): ?><span class="error-message"><?php echo $errors['code']; ?></span><?php endif; ?>
                        </div>

                        <button type="submit" class="btn-login">تفعيل</button>
                    </form>
                    
                    <div class="links">
                        <p>لم يصلك الرمز؟ <a href="resend_code.php?email=<?php echo htmlspecialchars($email); ?>">إعادة الإرسال</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
