<?php
require_once 'includes/functions.php';

$error = '';
$email_value = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $email_value = htmlspecialchars($email); // Store email for pre-filling form

    if (empty($email) || empty($password)) {
        $error = 'يرجى ملء جميع الحقول المطلوبة';
    } else {
        $db = new Database();
        $conn = $db->getConnection();

        if (!$conn) {
            $error = 'خطأ في الاتصال بقاعدة البيانات';
        } else {
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
            } else {
                // Check if user is active
                if (isset($user['is_active']) && $user['is_active'] == 0) {
                    $error = 'حسابك معطل. يرجى التواصل مع الإدارة';
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['full_name'];

                    // Use role if available, otherwise use user_type
                    $user_role = $user['role'] ?? $user['user_type'] ?? 'patient';
                    $_SESSION['user_type'] = $user_role;

                    switch ($user_role) {
                        case 'admin':
                            header("Location: admin/index.php");
                            exit();
                        case 'doctor':
                            header("Location: doctor/index.php");
                            exit();
                        case 'patient':
                            header("Location: dashboard.php");
                            exit();
                        case 'hospital':
                            header("Location: hospital/index.php");
                            exit();
                        default:
                            header("Location: index.php");
                            exit();
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - Health Tech</title>
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
                        <h2>أهلاً بعودتك!</h2>
                        <p>من فضلك أدخل بياناتك للمتابعة</p>
                    </div>
                </div>
            </div>
            <div class="form-section">
                <div class="form-content">
                    <div class="logo">
                        <i class="fas fa-heartbeat"></i>
                        <span>Health Tech</span>
                    </div>
                    <h3>تسجيل الدخول</h3>
                    <form method="POST" action="login.php">
                        <div class="form-group">
                            <label for="email">اسم المستخدم أو البريد الإلكتروني</label>
                            <input type="email" id="email" name="email" value="<?php echo $email_value; ?>" required>
                        </div>
                        <div class="form-group <?php echo !empty($error) ? 'error' : ''; ?>">
                            <label for="password">كلمة المرور</label>
                            <div class="password-wrapper">
                                <input type="password" id="password" name="password" required>
                                <i class="fas fa-eye-slash toggle-password"></i>
                            </div>
                            <?php if (!empty($error)): ?>
                                <span class="error-message"><?php echo $error; ?></span>
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="btn-login">تسجيل الدخول</button>
                    </form>
                    <div class="links">
                        <a href="forgot-password.php">نسيت كلمة المرور؟</a>
                        <p>ليس لديك حساب؟ <a href="register.php">سجل الآن</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelector('.toggle-password').addEventListener('click', function(e) {
            const passwordInput = document.getElementById('password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>

</html>
