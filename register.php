<?php
require_once 'includes/functions.php';

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$success = '';

// Initialize form variables to pre-fill the form on error
$form_data = [
    'full_name' => '',
    'username' => '',
    'email' => '',
    'phone' => '',
    'user_type' => '',
    'gender' => ''
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. CSRF Token Validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors['csrf'] = 'محاولة غير صالحة، يرجى تحديث الصفحة والمحاولة مرة أخرى.';
    }

    // Sanitize and store all post data using sanitize_input
    foreach ($form_data as $key => $value) {
        if (isset($_POST[$key])) {
            $form_data[$key] = sanitize_input($_POST[$key]);
        }
    }

    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $profile_image_path = '';

    if (empty($errors['csrf'])) { // Proceed only if CSRF is valid
        // --- Detailed Validation Logic ---
        if (empty($form_data['full_name'])) $errors['full_name'] = 'يرجى ملء الاسم الكامل';

        if (empty($form_data['username'])) {
            $errors['username'] = 'يرجى ملء اسم المستخدم';
        } elseif (strlen($form_data['username']) < 3) {
            $errors['username'] = 'اسم المستخدم يجب أن يكون 3 أحرف على الأقل';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $form_data['username'])) {
            $errors['username'] = 'اسم المستخدم يجب أن يحتوي على أحرف وأرقام وشرطات سفلية فقط';
        }

        if (empty($form_data['email'])) {
            $errors['email'] = 'يرجى ملء البريد الإلكتروني';
        } elseif (!validate_email($form_data['email'])) { // Using the validation function
            $errors['email'] = 'البريد الإلكتروني غير صحيح';
        }

        // Profile Image Upload Handling
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $target_dir = "assets/images/profiles/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            $image_name = uniqid() . '_' . basename($_FILES["profile_image"]["name"]);
            $target_file = $target_dir . $image_name;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // Check if image file is a actual image or fake image
            $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
            if ($check === false) {
                $errors['profile_image'] = "الملف ليس صورة.";
            }
            // Check file size (e.g., 5MB limit)
            if ($_FILES["profile_image"]["size"] > 5000000) {
                $errors['profile_image'] = "عذراً، حجم الصورة كبير جداً.";
            }
            // Allow certain file formats
            if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
                $errors['profile_image'] = "عذراً، فقط JPG, JPEG, PNG & GIF مسموح بها.";
            }

            if (empty($errors['profile_image']) && move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                $profile_image_path = $target_file;
            } else {
                $errors['profile_image'] = $errors['profile_image'] ?? "عذراً، حدث خطأ أثناء رفع الصورة.";
            }
        }

        // Use enhanced password validation
        $password_validation = validate_password_enhanced($password);
        if (!$password_validation['success']) {
            $errors['password'] = $password_validation['message'];
        }

        if ($password !== $confirm_password) $errors['confirm_password'] = 'كلمة المرور غير متطابقة';

        if (empty($form_data['phone'])) {
            $errors['phone'] = 'يرجى ملء رقم الهاتف';
        } elseif (!preg_match('/^[0-9+\-\s()]{10,15}$/', $form_data['phone'])) {
            $errors['phone'] = 'رقم الهاتف غير صحيح';
        }

        if (empty($form_data['user_type'])) $errors['user_type'] = 'يرجى تحديد نوع الحساب';

        if (empty($form_data['gender']) || !in_array($form_data['gender'], ['male', 'female'])) {
            $errors['gender'] = 'يرجى تحديد الجنس';
        }

        if (!isset($_POST['terms'])) $errors['terms'] = 'يجب الموافقة على الشروط';

        if (empty($errors)) {
            $db = new Database();
            $conn = $db->getConnection();

            // Check for existing email or username
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$form_data['email'], $form_data['username']]);

            if ($stmt->fetch()) {
                // Check which one exists
                $email_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $email_check->execute([$form_data['email']]);
                if ($email_check->fetch()) {
                    $errors['email'] = 'البريد الإلكتروني موجود مسبقاً';
                }

                $username_check = $conn->prepare("SELECT id FROM users WHERE username = ?");
                $username_check->execute([$form_data['username']]);
                if ($username_check->fetch()) {
                    $errors['username'] = 'اسم المستخدم موجود مسبقاً';
                }
            } else {
                $hashed_password = hash_password($password);
                $stmt = $conn->prepare("INSERT INTO users (username, full_name, email, password, phone, gender, role) VALUES (:username, :name, :email, :password, :phone, :gender, :role)");

                $stmt->execute([
                    ':username' => $form_data['username'],
                    ':name' => $form_data['full_name'],
                    ':email' => $form_data['email'],
                    ':password' => $hashed_password,
                    ':phone' => $form_data['phone'],
                    ':gender' => $form_data['gender'],
                    ':role' => $form_data['user_type']
                ]);

                if ($stmt->rowCount()) {
                    $success = 'تم إنشاء الحساب بنجاح! يمكنك الآن تسجيل الدخول.';
                    foreach ($form_data as $key => $value) {
                        $form_data[$key] = '';
                    }
                } else {
                    $errors['general'] = 'حدث خطأ أثناء إنشاء الحساب.';
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
    <title>إنشاء حساب جديد - Health Tech</title>
    <link rel="stylesheet" href="assets/css/login.css"> <!-- Reusing login.css -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .gender-options {
            display: flex;
            gap: 20px;
            margin-top: 8px;
        }
        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-weight: normal;
        }
        .radio-option input[type="radio"] {
            margin: 0;
        }
        .password-wrapper { position: relative; }
        .toggle-visibility {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            left: 12px; /* RTL: icon on the left side */
            background: transparent;
            border: 0;
            color: #777;
            cursor: pointer;
            padding: 4px;
        }
        .toggle-visibility:focus { outline: none; }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-box">
            <div class="image-section">
                <div class="image-content">
                    <img src="assets/images/doctor-illustration.png" alt="Doctor Illustration">
                    <div class="welcome-text">
                        <h2>انضم إلينا اليوم!</h2>
                        <p>أنشئ حسابك بسهولة وابدأ رحلتك الصحية</p>
                    </div>
                </div>
            </div>
            <div class="form-section">
                <div class="form-content">
                    <div class="logo">
                        <i class="fas fa-heartbeat"></i>
                        <span>Health Tech</span>
                    </div>
                    <h3>إنشاء حساب جديد</h3>

                    <?php if ($success): ?>
                        <div class="message success"><?php echo $success; ?> <a href="login.php">سجل الدخول</a></div>
                    <?php endif; ?>

                    <?php if (!empty($errors['general'])): ?>
                        <div class="message error"><?php echo $errors['general']; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="register.php" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                        <div class="form-group <?php echo isset($errors['full_name']) ? 'error' : ''; ?>">
                            <label for="full_name">الاسم الكامل</label>
                            <input type="text" id="full_name" name="full_name" value="<?php echo $form_data['full_name']; ?>" required>
                            <?php if (isset($errors['full_name'])): ?><span class="error-message"><?php echo $errors['full_name']; ?></span><?php endif; ?>
                        </div>

                        <div class="form-group <?php echo isset($errors['username']) ? 'error' : ''; ?>">
                            <label for="username">اسم المستخدم</label>
                            <input type="text" id="username" name="username" value="<?php echo $form_data['username']; ?>" required>
                            <?php if (isset($errors['username'])): ?><span class="error-message"><?php echo $errors['username']; ?></span><?php endif; ?>
                        </div>

                        <div class="form-group <?php echo isset($errors['email']) ? 'error' : ''; ?>">
                            <label for="email">البريد الإلكتروني</label>
                            <input type="email" id="email" name="email" value="<?php echo $form_data['email']; ?>" required>
                            <?php if (isset($errors['email'])): ?><span class="error-message"><?php echo $errors['email']; ?></span><?php endif; ?>
                        </div>

                        <div class="form-row">
                            <div class="form-group <?php echo isset($errors['password']) ? 'error' : ''; ?>">
                                <label for="password">كلمة المرور</label>
                                <div class="password-wrapper">
                                    <input type="password" id="password" name="password" minlength="6" required>
                                    <button type="button" class="toggle-visibility" aria-label="إظهار/إخفاء كلمة المرور" data-target="password">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                </div>
                                <?php if (isset($errors['password'])): ?><span class="error-message"><?php echo $errors['password']; ?></span><?php endif; ?>
                            </div>

                            <div class="form-group <?php echo isset($errors['confirm_password']) ? 'error' : ''; ?>">
                                <label for="confirm_password">تأكيد كلمة المرور</label>
                                <div class="password-wrapper">
                                    <input type="password" id="confirm_password" name="confirm_password" required>
                                    <button type="button" class="toggle-visibility" aria-label="إظهار/إخفاء تأكيد كلمة المرور" data-target="confirm_password">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                </div>
                                <?php if (isset($errors['confirm_password'])): ?><span class="error-message"><?php echo $errors['confirm_password']; ?></span><?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group <?php echo isset($errors['phone']) ? 'error' : ''; ?>">
                            <label for="phone">رقم الهاتف</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo $form_data['phone']; ?>" required>
                            <?php if (isset($errors['phone'])): ?><span class="error-message"><?php echo $errors['phone']; ?></span><?php endif; ?>
                        </div>

                        <div class="form-group <?php echo isset($errors['user_type']) ? 'error' : ''; ?>">
                            <label for="user_type">نوع الحساب</label>
                            <select id="user_type" name="user_type" required>
                                <option value="" disabled <?php echo empty($form_data['user_type']) ? 'selected' : ''; ?>>-- اختر --</option>
                                <option value="patient" <?php echo ($form_data['user_type'] == 'patient') ? 'selected' : ''; ?>>مريض</option>
                                <option value="doctor" <?php echo ($form_data['user_type'] == 'doctor') ? 'selected' : ''; ?>>طبيب</option>
                            </select>
                            <?php if (isset($errors['user_type'])): ?><span class="error-message"><?php echo $errors['user_type']; ?></span><?php endif; ?>
                        </div>

                        <div class="form-group <?php echo isset($errors['gender']) ? 'error' : ''; ?>">
                            <label>الجنس</label>
                            <div class="gender-options">
                                <label class="radio-option">
                                    <input type="radio" name="gender" value="male" <?php echo ($form_data['gender'] == 'male') ? 'checked' : ''; ?> required>
                                    <span>ذكر</span>
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="gender" value="female" <?php echo ($form_data['gender'] == 'female') ? 'checked' : ''; ?> required>
                                    <span>أنثى</span>
                                </label>
                            </div>
                            <?php if (isset($errors['gender'])): ?><span class="error-message"><?php echo $errors['gender']; ?></span><?php endif; ?>
                        </div>

                        <div class="form-group <?php echo isset($errors['profile_image']) ? 'error' : ''; ?>">
                            <label for="profile_image">صورة الملف الشخصي (اختياري)</label>
                            <input type="file" id="profile_image" name="profile_image" accept="image/*">
                            <?php if (isset($errors['profile_image'])): ?><span class="error-message"><?php echo $errors['profile_image']; ?></span><?php endif; ?>
                        </div>

                        <div class="form-group terms <?php echo isset($errors['terms']) ? 'error' : ''; ?>">
                            <input type="checkbox" id="terms" name="terms" required>
                            <label for="terms">أوافق على <a href="#">شروط الخدمة</a> و <a href="#">سياسة الخصوصية</a></label>
                            <?php if (isset($errors['terms'])): ?><span class="error-message"><?php echo $errors['terms']; ?></span><?php endif; ?>
                        </div>

                        <button type="submit" class="btn-login">إنشاء الحساب</button>
                    </form>
                    <div class="links">
                        <p>لديك حساب بالفعل؟ <a href="login.php">سجل دخولك</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
<script>
    (function(){
        function toggleVisibility(btn){
            var targetId = btn.getAttribute('data-target');
            var input = document.getElementById(targetId);
            if(!input) return;
            var isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            var icon = btn.querySelector('i');
            if(icon){
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            }
        }
        document.addEventListener('click', function(e){
            var btn = e.target.closest('.toggle-visibility');
            if(btn){
                toggleVisibility(btn);
            }
        });
    })();
</script>
</body>

</html>
