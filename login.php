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
    <style>
        .password-wrapper {
            position: relative;
        }

        .toggle-visibility {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #aaa;
            font-size: 1.2rem;
        }

        .toggle-visibility:hover {
            color: #6c84ee;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-box">
            <!-- Image Section (Right Side) -->
            <div class="image-section">
                <div class="image-content">
                    <img src="assets/images/doctor-illustration.png" alt="Login Illustration"
                        onerror="this.style.display='none'">
                </div>
                <div class="welcome-text">
                    <h2>أهلاً بعودتك!</h2>
                    <p>سجل دخولك للوصول إلى حسابك</p>
                </div>
            </div>

            <!-- Form Section (Left Side) -->
            <div class="form-section">
                <div class="form-content">
                    <!-- Logo -->
                    <div class="logo">
                        <i class="fas fa-heartbeat"></i>
                        <span>Health Tech</span>
                    </div>

                    <h3>تسجيل الدخول</h3>

                    <!-- Error Message -->
                    <?php if (!empty($error)): ?>
                        <div class="form-group error">
                            <span class="error-message">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <!-- Login Form -->
                    <form method="POST" action="login.php">
                        <!-- Email Input -->
                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope"></i> البريد الإلكتروني
                            </label>
                            <input type="email" id="email" name="email" value="<?php echo $email_value; ?>"
                                placeholder="example@email.com" required>
                        </div>

                        <!-- Password Input -->
                        <div class="form-group">
                            <label for="password">
                                <i class="fas fa-lock"></i> كلمة المرور
                            </label>
                            <div class="password-wrapper">
                                <input type="password" id="password" name="password" placeholder="••••••••" required>
                                <i class="fas fa-eye-slash toggle-visibility" id="togglePassword"
                                    onclick="togglePasswordVisibility()"></i>
                            </div>
                        </div>

                        <!-- Remember Me & Forgot Password -->
                        <div class="form-group" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <label style="display: flex; align-items: center; gap: 8px; margin: 0; font-weight: normal;">
                                <input type="checkbox" name="remember" style="width: auto; margin: 0;">
                                <span>تذكرني</span>
                            </label>
                            <a href="forgot-password.php" style="color: #6c84ee; text-decoration: none; font-size: 0.9rem;">
                                نسيت كلمة المرور؟
                            </a>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn-login">
                            <i class="fas fa-sign-in-alt"></i> تسجيل الدخول
                        </button>
                    </form>

                    <!-- Links -->
                    <div class="links">
                        <p>ليس لديك حساب؟ <a href="register.php">سجل الآن</a></p>
                        <p><a href="index.php"><i class="fas fa-arrow-right"></i> العودة للصفحة الرئيسية</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('togglePassword');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            }
        }
    </script>
</body>

</html>
