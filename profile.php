<?php
require_once 'config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// التحقق من تسجيل الدخول
if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

$user = get_logged_in_user();
$error = '';
$success = '';

// معالجة رفع صورة الملف الشخصي
if (isset($_POST['upload_image'])) {
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $target_dir = "uploads/profile_images/";
        // ملاحظة: تأكد من أن هذا المجلد موجود وقابل للكتابة عليه
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $image_file_type = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
        $unique_name = 'user_' . $user['id'] . '_' . time() . '.' . $image_file_type;
        $target_file = $target_dir . $unique_name;

        $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
        if ($check === false) {
            $error = "الملف المرفوع ليس صورة.";
        } elseif ($_FILES["profile_image"]["size"] > 5000000) { // 5MB limit
            $error = "عذراً، حجم الصورة كبير جداً.";
        } elseif (!in_array($image_file_type, ['jpg', 'png', 'jpeg', 'gif'])) {
            $error = "عذراً، يُسمح فقط بملفات JPG, JPEG, PNG & GIF.";
        } else {
            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                $db = new Database();
                $conn = $db->getConnection();
                $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                if ($stmt->execute([$target_file, $user['id']])) {
                    if (!empty($user['profile_image']) && file_exists($user['profile_image']) && strpos($user['profile_image'], 'default-avatar.png') === false) {
                        unlink($user['profile_image']);
                    }
                    $success = "تم تحديث صورة الملف الشخصي بنجاح.";
                    $user['profile_image'] = $target_file;
                } else {
                    $error = "حدث خطأ أثناء تحديث قاعدة البيانات.";
                }
            } else {
                $error = "عذراً، حدث خطأ أثناء رفع الصورة.";
            }
        }
    } else {
        $error = "يرجى اختيار صورة لرفعها.";
    }
}

// معالجة تحديث البيانات
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    $username = sanitize_input($_POST['username']);
    $phone = sanitize_input($_POST['phone']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // التحقق من البيانات المطلوبة
    if (empty($full_name) || empty($email) || empty($username)) {
        $error = 'يرجى ملء جميع الحقول المطلوبة (الاسم، الإيميل، اسم المستخدم)';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'يرجى إدخال بريد إلكتروني صحيح';
    } elseif (strlen($username) < 3) {
        $error = 'اسم المستخدم يجب أن يكون 3 أحرف على الأقل';
    } else {
        $db = new Database();
        $conn = $db->getConnection();

        // التحقق من عدم وجود username أو email آخر بنفس الاسم
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $check_stmt->execute([$username, $email, $user['id']]);
        if ($check_stmt->rowCount() > 0) {
            $error = 'اسم المستخدم أو البريد الإلكتروني مستخدم بالفعل';
        } else {
            // تحديث البيانات الأساسية
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, username = ?, phone = ? WHERE id = ?");
            if ($stmt->execute([$full_name, $email, $username, $phone, $user['id']])) {
                $success = 'تم تحديث البيانات بنجاح';
                // تحديث بيانات الجلسة
                $_SESSION['full_name'] = $full_name;
                $_SESSION['email'] = $email;
                $_SESSION['username'] = $username;
                $user['full_name'] = $full_name;
                $user['email'] = $email;
                $user['username'] = $username;
                $user['phone'] = $phone;

                // تحديث كلمة المرور إذا تم إدخالها
                if (!empty($current_password) && !empty($new_password)) {
                    if (verify_password($current_password, $user['password'])) {
                        if ($new_password === $confirm_password) {
                            if (strlen($new_password) >= 8) {
                                $hashed_password = hash_password($new_password);
                                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                                if ($stmt->execute([$hashed_password, $user['id']])) {
                                    $success .= ' وتم تحديث كلمة المرور بنجاح';
                                } else {
                                    $error = 'حدث خطأ أثناء تحديث كلمة المرور';
                                }
                            } else {
                                $error = 'يجب أن تكون كلمة المرور الجديدة 8 أحرف على الأقل';
                            }
                        } else {
                            $error = 'كلمتا المرور الجديدتان غير متطابقتين';
                        }
                    } else {
                        $error = 'كلمة المرور الحالية غير صحيحة';
                    }
                }
            } else {
                $error = 'حدث خطأ أثناء تحديث البيانات';
            }
        }
    }
}

// الحصول على إحصائيات المستخدم
$appointments = get_user_appointments($conn, $user['id']);
$total_appointments = count($appointments);
$upcoming_appointments = count(array_filter($appointments, function($app) {
    return $app['status'] == 'confirmed' && $app['appointment_date'] >= date('Y-m-d');
}));
$completed_appointments = count(array_filter($appointments, function($app) {
    return $app['appointment_date'] < date('Y-m-d');
}));
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الملف الشخصي - موقع حجز المواعيد الطبية</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .profile-page {
            padding-top: 80px;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);
            overflow-x: hidden;
            overflow-y: visible;
        }

        .profile-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem 20px;
            overflow: visible;
            position: relative;
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .page-header h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--primary-blue), var(--medical-green));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 3rem;
            align-items: start;
            overflow: visible;
            position: relative;
        }

        .profile-sidebar {
            background: var(--bg-primary);
            border-radius: var(--radius-xl);
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            position: sticky;
            top: 100px;
            overflow: visible;
            z-index: 5;
        }

        .profile-avatar-wrapper {
            position: relative;
            width: 150px;
            margin: 0 auto 2rem;
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 0 auto 2rem;
            display: block;
            object-fit: cover;
            border: 4px solid var(--primary-blue);
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.4);
        }

        .profile-avatar-upload {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: var(--primary-blue);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid var(--bg-primary);
            z-index: 10;
        }

        .profile-avatar-upload:hover {
            transform: scale(1.1);
            background: var(--medical-green);
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.4);
            z-index: 20;
        }

        .profile-avatar-upload input[type="file"] {
            display: none;
        }

        .profile-email {
            text-align: center;
            color: var(--text-secondary);
            margin-bottom: 2rem;
            font-weight: 500;
        }

        .profile-stats {
            display: grid;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: var(--bg-secondary);
            border-radius: var(--radius);
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }

        .stat-item:hover {
            background: var(--primary-blue);
            color: white;
            transform: translateX(-5px) scale(1.02);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            z-index: 10;
        }

        .stat-label {
            font-weight: 600;
            color: var(--text-secondary);
            transition: var(--transition);
        }

        .stat-item:hover .stat-label {
            color: white;
        }

        .stat-value {
            font-weight: 700;
            color: var(--primary-blue);
            font-size: 1.2rem;
            transition: var(--transition);
        }

        .stat-item:hover .stat-value {
            color: white;
        }

        .profile-actions {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .btn-profile {
            padding: 1rem;
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .btn-edit {
            background: var(--primary-blue);
            color: white;
        }

        .btn-edit:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            z-index: 10;
        }

        .btn-danger {
            background: var(--error);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
            z-index: 10;
        }

        .profile-content {
            background: var(--bg-primary);
            border-radius: var(--radius-xl);
            padding: 2.5rem;
            box-shadow: var(--shadow-lg);
        }

        .section-title {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            color: var(--text-primary);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title i {
            color: var(--primary-blue);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            font-size: 1rem;
            transition: var(--transition);
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-group input:disabled {
            background: var(--bg-secondary);
            color: var(--text-secondary);
            cursor: not-allowed;
        }

        .password-section {
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            padding: 2rem;
            margin-top: 2rem;
            border-left: 4px solid var(--primary-blue);
        }

        .password-section h3 {
            margin-bottom: 1.5rem;
            color: var(--text-primary);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .password-section h3 i {
            color: var(--primary-blue);
        }

        .submit-section {
            margin-top: 2rem;
            text-align: center;
        }

        .btn-save {
            padding: 1rem 3rem;
            font-size: 1.1rem;
            font-weight: 700;
            /* background: linear-gradient(135deg, var(--primary-blue), var(--medical-green)); */
            background-color: #0fb3a0;
            border: none;
            color: white;
            border-radius: var(--radius-lg);
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-lg);
            position: relative;
            z-index: 1;
        }

        .btn-save:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-xl);
            z-index: 10;
        }

        .btn-save:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .info-card {
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-left: 4px solid var(--medical-green);
        }

        .info-card h4 {
            margin-bottom: 1rem;
            color: var(--text-primary);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-card h4 i {
            color: var(--medical-green);
        }

        .info-list {
            display: grid;
            gap: 0.75rem;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: var(--bg-primary);
            border-radius: var(--radius);
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }

        .info-item:hover {
            background: var(--primary-blue);
            color: white;
            transform: translateX(-5px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            z-index: 10;
        }

        .info-label {
            font-weight: 600;
            color: var(--text-secondary);
            transition: var(--transition);
        }

        .info-item:hover .info-label {
            color: white;
        }

        .info-value {
            font-weight: 700;
            color: var(--text-primary);
            transition: var(--transition);
        }

        .info-item:hover .info-value {
            color: white;
        }

        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .profile-sidebar {
                position: static;
            }

            .profile-content {
                padding: 1.5rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <i class="fas fa-heartbeat"></i>
                    <span>Health Tech</span>
                </div>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link">الرئيسية</a>
                    </li>
                    <li class="nav-item">
                        <a href="search.php" class="nav-link">البحث عن طبيب</a>
                    </li>
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link">لوحة التحكم</a>
                    </li>
                </ul>
                <div class="nav-auth">
                    <span class="user-name">مرحباً، <?php echo $user['full_name']; ?></span>
                    <a href="logout.php" class="btn btn-outline">تسجيل الخروج</a>
                </div>
            </div>
        </nav>
    </header>

    <!-- Profile Page -->
    <section class="profile-page">
        <div class="profile-container">
            <!-- Page Header -->
            <div class="page-header">
                <h1>الملف الشخصي</h1>
                <p>إدارة بياناتك الشخصية وتفضيلاتك</p>
            </div>

            <!-- Messages -->
            <?php if ($error): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $success; ?></span>
                </div>
            <?php endif; ?>

            <div class="profile-grid">
                <!-- Profile Sidebar -->
                <div class="profile-sidebar">
                    <form action="profile.php" method="post" enctype="multipart/form-data" id="avatar-form">
                        <div class="profile-avatar-wrapper">
                            <img src="<?php echo BASE_URL . htmlspecialchars($user['profile_image']); ?>" alt="الصورة الشخصية" class="profile-avatar">
                            <label for="profile_image_input" class="profile-avatar-upload">
                                <i class="fas fa-camera"></i>
                                <input type="file" name="profile_image" id="profile_image_input" accept="image/*">
                            </label>
                        </div>
                        <input type="submit" name="upload_image" id="upload_image_submit" style="display:none;">
                    </form>

                    <h2 class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                    <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>

                    <!-- Profile Stats -->
                    <div class="profile-stats">
                        <div class="stat-item">
                            <span class="stat-label">إجمالي المواعيد</span>
                            <span class="stat-value"><?php echo $total_appointments; ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">مواعيد قادمة</span>
                            <span class="stat-value"><?php echo $upcoming_appointments; ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">مواعيد مكتملة</span>
                            <span class="stat-value"><?php echo $completed_appointments; ?></span>
                        </div>
                    </div>

                    <!-- Profile Actions -->
                    <div class="profile-actions">
                        <a href="dashboard.php" class="btn-profile btn-edit">
                            <i class="fas fa-tachometer-alt"></i>
                            لوحة التحكم
                        </a>
                        <a href="appointments.php" class="btn-profile btn-edit">
                            <i class="fas fa-calendar-alt"></i>
                            مواعيدي
                        </a>
                        <button class="btn-profile btn-danger" onclick="showDeleteAccountModal()">
                            <i class="fas fa-trash"></i>
                            حذف الحساب
                        </button>
                    </div>
                </div>

                <!-- Profile Content -->
                <div class="profile-content">
                    <!-- Personal Information -->
                    <div class="section-title">
                        <i class="fas fa-user-edit"></i>
                        البيانات الشخصية
                    </div>

                    <form method="POST" id="profile-form">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="username">اسم المستخدم</label>
                                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="email">البريد الإلكتروني</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="full_name">الاسم الكامل *</label>
                                <input type="text" id="full_name" name="full_name" value="<?php echo $user['full_name']; ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="phone">رقم الهاتف *</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo $user['phone']; ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="date_of_birth">تاريخ الميلاد</label>
                                <input type="date" id="date_of_birth" value="<?php echo $user['date_of_birth']; ?>" disabled>
                            </div>

                            <div class="form-group">
                                <label for="gender">الجنس</label>
                                <input type="text" id="gender" value="<?php echo $user['gender'] == 'male' ? 'ذكر' : 'أنثى'; ?>" disabled>
                            </div>
                        </div>

                        <!-- Password Change Section -->
                        <div class="password-section">
                            <h3>
                                <i class="fas fa-lock"></i>
                                تغيير كلمة المرور
                            </h3>
                            <p style="margin-bottom: 1.5rem; color: var(--text-secondary);">
                                اترك الحقول فارغة إذا كنت لا تريد تغيير كلمة المرور
                            </p>

                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="current_password">كلمة المرور الحالية</label>
                                    <input type="password" id="current_password" name="current_password" placeholder="أدخل كلمة المرور الحالية">
                                </div>

                                <div class="form-group">
                                    <label for="new_password">كلمة المرور الجديدة</label>
                                    <input type="password" id="new_password" name="new_password" placeholder="أدخل كلمة المرور الجديدة">
                                </div>

                                <div class="form-group">
                                    <label for="confirm_password">تأكيد كلمة المرور الجديدة</label>
                                    <input type="password" id="confirm_password" name="confirm_password" placeholder="أعد إدخال كلمة المرور الجديدة">
                                </div>
                            </div>
                        </div>

                        <!-- Submit Section -->
                        <div class="submit-section">
                            <button type="submit" name="update_profile" class="btn-save" id="save-btn">
                                <i class="fas fa-save"></i>
                                حفظ التغييرات
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Account Information Card -->
            <div class="info-card" style="margin-top: 3rem;">
                <h4>
                    <i class="fas fa-info-circle"></i>
                    معلومات الحساب
                </h4>
                <div class="info-list">
                    <div class="info-item">
                        <span class="info-label">تاريخ إنشاء الحساب</span>
                        <span class="info-value"><?php echo format_date_arabic($user['created_at']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">آخر تحديث</span>
                        <span class="info-value"><?php echo format_date_arabic($user['updated_at']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">حالة الحساب</span>
                        <span class="info-value" style="color: var(--success);">نشط</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Delete Account Modal -->
    <div class="modal" id="delete-account-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>حذف الحساب</h3>
                <button class="modal-close" onclick="closeDeleteAccountModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>هل أنت متأكد من حذف حسابك؟ هذا الإجراء لا يمكن التراجع عنه.</p>
                <p>سيتم حذف جميع بياناتك ومواعيدك نهائياً.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeDeleteAccountModal()">إلغاء</button>
                <button class="btn btn-danger" onclick="deleteAccount()">حذف الحساب</button>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        // Form Validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('profile-form');
            const saveBtn = document.getElementById('save-btn');

            form.addEventListener('submit', function(e) {
                const fullName = document.getElementById('full_name').value.trim();
                const phone = document.getElementById('phone').value.trim();
                const currentPassword = document.getElementById('current_password').value;
                const newPassword = document.getElementById('new_password').value;
                const confirmPassword = document.getElementById('confirm_password').value;

                // Basic validation
                if (!fullName || !phone) {
                    e.preventDefault();
                    showMessage('يرجى ملء جميع الحقول المطلوبة', 'error');
                    return;
                }

                // Password validation
                if (currentPassword || newPassword || confirmPassword) {
                    if (!currentPassword) {
                        e.preventDefault();
                        showMessage('يرجى إدخال كلمة المرور الحالية', 'error');
                        return;
                    }

                    if (!newPassword) {
                        e.preventDefault();
                        showMessage('يرجى إدخال كلمة المرور الجديدة', 'error');
                        return;
                    }

                    if (newPassword.length < 8) {
                        e.preventDefault();
                        showMessage('كلمة المرور الجديدة يجب أن تكون 8 أحرف على الأقل', 'error');
                        return;
                    }

                    if (newPassword !== confirmPassword) {
                        e.preventDefault();
                        showMessage('كلمة المرور الجديدة غير متطابقة', 'error');
                        return;
                    }
                }

                // Show loading state
                saveBtn.innerHTML = '<span class="loading"></span> جاري الحفظ...';
                saveBtn.disabled = true;
            });
        });

        // Delete Account Modal Functions
        function showDeleteAccountModal() {
            document.getElementById('delete-account-modal').classList.add('active');
        }

        function closeDeleteAccountModal() {
            document.getElementById('delete-account-modal').classList.remove('active');
        }

        function deleteAccount() {
            if (confirm('هل أنت متأكد تماماً من حذف حسابك؟ هذا الإجراء لا يمكن التراجع عنه.')) {
                // Redirect to delete account page
                window.location.href = 'delete-account.php';
            }
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('delete-account-modal');
            if (e.target === modal) {
                closeDeleteAccountModal();
            }
        });

        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDeleteAccountModal();
            }
        });

        // Auto-submit form on file selection
        document.getElementById('profile_image_input').addEventListener('change', function() {
            document.getElementById('upload_image_submit').click();
        });
    </script>

    <style>
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: var(--bg-primary);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            max-width: 500px;
            width: 90%;
            animation: modalSlideIn 0.3s ease;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .modal-header h3 {
            margin: 0;
            color: var(--text-primary);
            font-weight: 700;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-secondary);
            transition: var(--transition);
        }

        .modal-close:hover {
            color: var(--error);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-body p {
            margin-bottom: 1rem;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        .modal-footer {
            display: flex;
            gap: 1rem;
            padding: 1.5rem;
            border-top: 1px solid var(--border-color);
            justify-content: flex-end;
        }
    </style>
</body>
</html>
