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
    // Debug: Log POST data
    error_log('Update profile form submitted. POST data: ' . print_r($_POST, true));
    
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    $username = sanitize_input($_POST['username']);
    $phone = sanitize_input($_POST['phone']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Debug: Log sanitized data
    error_log('Sanitized data - Name: ' . $full_name . ', Email: ' . $email . ', Username: ' . $username . ', Phone: ' . $phone);

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
            try {
                $sql = "UPDATE users SET full_name = ?, email = ?, username = ?, phone = ? WHERE id = ?";
                error_log('Executing query: ' . $sql);
                error_log('With params: ' . print_r([$full_name, $email, $username, $phone, $user['id']], true));
                
                $stmt = $conn->prepare($sql);
                $result = $stmt->execute([$full_name, $email, $username, $phone, $user['id']]);
                
                if ($result) {
                    $success = 'تم تحديث البيانات بنجاح';
                    error_log('Profile update successful');
                    
                    // تحديث بيانات الجلسة
                    $_SESSION['full_name'] = $full_name;
                    $_SESSION['email'] = $email;
                    $_SESSION['username'] = $username;
                    $user['full_name'] = $full_name;
                    $user['email'] = $email;
                    $user['username'] = $username;
                    $user['phone'] = $phone;
                    
                    // Debug: Verify the update
                    $check = $conn->prepare("SELECT * FROM users WHERE id = ?");
                    $check->execute([$user['id']]);
                    $updated_user = $check->fetch(PDO::FETCH_ASSOC);
                    error_log('User data after update: ' . print_r($updated_user, true));
                } else {
                    $error = 'فشل في تحديث البيانات. ' . print_r($stmt->errorInfo(), true);
                    error_log('Update failed: ' . print_r($stmt->errorInfo(), true));
                }
            } catch (PDOException $e) {
                $error = 'حدث خطأ في قاعدة البيانات: ' . $e->getMessage();
                error_log('Database error: ' . $e->getMessage());
            }
        }
    }
}

// تحديث كلمة المرور إذا تم إدخالها
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    if (!empty($current_password) && !empty($new_password)) {
        if (verify_password($current_password, $user['password'])) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 8) {
                    $hashed_password = hash_password($new_password);
                    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    if ($stmt->execute([$hashed_password, $user['id']])) {
                        $success = (empty($success) ? '' : $success . ' و') . 'تم تحديث كلمة المرور بنجاح';
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
    <title>الملف الشخصي - Health Tech</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts (Cairo) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        cairo: ['Cairo', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 20px;
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
<body class="font-cairo bg-gray-50">

    <?php 
    // Display success/error messages
    if (!empty($success)) {
        echo '<div class="fixed top-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg shadow-lg z-50 max-w-md" id="success-message">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-600 text-xl ml-2"></i>
                    <span>'.$success.'</span>
                </div>
            </div>';
    } elseif (!empty($error)) {
        echo '<div class="fixed top-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg shadow-lg z-50 max-w-md" id="error-message">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-600 text-xl ml-2"></i>
                    <span>'.$error.'</span>
                </div>
            </div>';
    }
    ?>
    
    <?php require_once 'includes/header.php'; ?>
    
    <script>
    // Function to show message
    function showMessage(type, message) {
        // Remove any existing messages
        const oldMsg = document.getElementById('message-container');
        if (oldMsg) oldMsg.remove();
        
        // Create message element
        const messageDiv = document.createElement('div');
        messageDiv.id = 'message-container';
        messageDiv.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg ${type === 'success' ? 'bg-green-100 text-green-700 border border-green-400' : 'bg-red-100 text-red-700 border border-red-400'}`;
        messageDiv.innerHTML = `
            <div class="flex items-center">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} text-xl ml-2"></i>
                <span>${message}</span>
            </div>
        `;
        
        // Add to body
        document.body.appendChild(messageDiv);
        
        // Auto hide after 5 seconds
        setTimeout(() => {
            messageDiv.style.transition = 'opacity 0.5s';
            messageDiv.style.opacity = '0';
            setTimeout(() => messageDiv.remove(), 500);
        }, 5000);
    }
    
    // Handle form submission with AJAX
    document.addEventListener('DOMContentLoaded', function() {
        const profileForm = document.getElementById('profile-form');
        
        if (profileForm) {
            profileForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Show loading state
                const submitBtn = profileForm.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> جاري الحفظ...';
                
                // Get form data
                const formData = new FormData(profileForm);
                formData.append('update_profile', '1');
                
                // Send AJAX request
                fetch('update_profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update displayed data
                        if (data.user) {
                            document.getElementById('full_name').value = data.user.full_name || '';
                            document.getElementById('email').value = data.user.email || '';
                            document.getElementById('username').value = data.user.username || '';
                            document.getElementById('phone').value = data.user.phone || '';
                            
                            // Update profile image if changed
                            if (data.user.profile_image) {
                                const profileImg = document.querySelector('.profile-image');
                                if (profileImg) {
                                    profileImg.src = data.user.profile_image + '?t=' + new Date().getTime();
                                }
                            }
                        }
                        
                        // Show success message
                        showMessage('success', data.message || 'تم تحديث البيانات بنجاح');
                    } else {
                        // Show error message
                        showMessage('error', data.message || 'حدث خطأ أثناء تحديث البيانات');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessage('error', 'حدث خطأ في الاتصال بالخادم');
                })
                .finally(() => {
                    // Reset button state
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                });
            });
        }
    });
    </script>

    <!-- Profile Page -->
    <main class="bg-gray-50 py-12 min-h-screen">
        <div class="container mx-auto px-4">
            <!-- Page Header -->
            <div class="text-center mb-8">
                <h1 class="text-4xl md:text-5xl font-black text-gray-900 mb-4">
                    <i class="fas fa-user-circle text-blue-600 ml-2"></i>
                    الملف الشخصي
                </h1>
                <p class="text-lg text-gray-600">إدارة بياناتك الشخصية وتفضيلاتك</p>
            </div>

            <!-- Messages -->
            <?php if ($error): ?>
                <div class="bg-red-100 border-r-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg max-w-4xl mx-auto">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle ml-2"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-100 border-r-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg max-w-4xl mx-auto">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle ml-2"></i>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Profile Sidebar -->
                <div class="lg:col-span-1 bg-white rounded-2xl shadow-xl p-6 md:p-8">
                    <form action="profile.php" method="post" enctype="multipart/form-data" id="avatar-form">
                        <div class="relative w-32 h-32 mx-auto mb-6">
                            <?php
                            $profile_image = isset($user['profile_image']) && !empty($user['profile_image']) ? $user['profile_image'] : '';
                            $avatar_url = $profile_image && file_exists($profile_image)
                                ? $profile_image
                                : "https://ui-avatars.com/api/?name=" . urlencode($user['full_name']) . "&background=2563eb&color=fff&size=128&bold=true";
                            ?>
                            <img src="<?php echo htmlspecialchars($avatar_url); ?>"
                                 alt="الصورة الشخصية"
                                 class="w-full h-full rounded-full object-cover border-4 border-blue-500 shadow-lg"
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($user['full_name']); ?>&background=2563eb&color=fff&size=128&bold=true'">
                            <label for="profile_image_input" class="absolute bottom-0 right-0 bg-blue-600 text-white w-10 h-10 rounded-full flex items-center justify-center cursor-pointer hover:bg-blue-700 transition-all shadow-lg border-2 border-white">
                                <i class="fas fa-camera text-sm"></i>
                                <input type="file" name="profile_image" id="profile_image_input" accept="image/*" class="hidden">
                            </label>
                        </div>
                        <input type="submit" name="upload_image" id="upload_image_submit" style="display:none;">
                    </form>

                    <h2 class="text-2xl font-bold text-gray-900 text-center mb-2"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                    <p class="text-gray-600 text-center mb-6"><?php echo htmlspecialchars($user['email']); ?></p>

                    <!-- Profile Stats -->
                    <div class="grid grid-cols-1 gap-3 mb-6">
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-4 rounded-xl border-r-4 border-blue-500 hover:shadow-md transition-all">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-700 font-semibold flex items-center">
                                    <i class="fas fa-calendar-check text-blue-600 ml-2"></i>
                                    إجمالي المواعيد
                                </span>
                                <span class="text-2xl font-bold text-blue-600"><?php echo $total_appointments; ?></span>
                            </div>
                        </div>
                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 p-4 rounded-xl border-r-4 border-green-500 hover:shadow-md transition-all">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-700 font-semibold flex items-center">
                                    <i class="fas fa-calendar-alt text-green-600 ml-2"></i>
                                    مواعيد قادمة
                                </span>
                                <span class="text-2xl font-bold text-green-600"><?php echo $upcoming_appointments; ?></span>
                            </div>
                        </div>
                        <div class="bg-gradient-to-r from-purple-50 to-pink-50 p-4 rounded-xl border-r-4 border-purple-500 hover:shadow-md transition-all">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-700 font-semibold flex items-center">
                                    <i class="fas fa-check-circle text-purple-600 ml-2"></i>
                                    مواعيد مكتملة
                                </span>
                                <span class="text-2xl font-bold text-purple-600"><?php echo $completed_appointments; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Actions -->
                    <div class="space-y-3">
                        <a href="dashboard.php" class="w-full bg-blue-600 text-white font-semibold py-3 px-4 rounded-xl hover:bg-blue-700 transition-all duration-300 text-center block shadow-md hover:shadow-lg">
                            <i class="fas fa-tachometer-alt ml-2"></i>
                            لوحة التحكم
                        </a>
                        <a href="appointments.php" class="w-full bg-green-600 text-white font-semibold py-3 px-4 rounded-xl hover:bg-green-700 transition-all duration-300 text-center block shadow-md hover:shadow-lg mb-3">
                            <i class="fas fa-calendar-alt ml-2"></i>
                            مواعيدي
                        </a>
                        
                        <!-- Delete Account Button -->
                        <button type="button" 
                                onclick="document.getElementById('delete-account-modal').classList.remove('hidden')" 
                                class="w-full bg-red-600 text-white font-semibold py-3 px-4 rounded-xl hover:bg-red-700 transition-all duration-300 text-center block shadow-md hover:shadow-lg">
                            <i class="fas fa-trash-alt ml-2"></i>
                            حذف الحساب
                        </button>
                        <button type="submit" name="update_profile" class="w-full bg-blue-600 text-white font-semibold py-3 px-6 rounded-xl hover:bg-blue-700 transition-all duration-300 shadow-md hover:shadow-lg">
                            <i class="fas fa-save ml-2"></i>
                            حفظ التغييرات
                        </button>
                    </div>
                </div>

                <!-- Profile Content -->
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-xl p-6 md:p-8">
                    <!-- Personal Information -->
                    <div class="flex items-center gap-3 mb-6 pb-4 border-b-2 border-gray-200">
                        <i class="fas fa-user-edit text-blue-600 text-2xl"></i>
                        <h3 class="text-2xl font-bold text-gray-900">البيانات الشخصية</h3>
                    </div>

                    <form method="POST" id="profile-form" enctype="multipart/form-data">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="username" class="block text-sm font-semibold text-gray-700 mb-2">اسم المستخدم</label>
                                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>"
                                       class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:outline-none focus:border-blue-500 transition-colors" required>
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">البريد الإلكتروني</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                                       class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:outline-none focus:border-blue-500 transition-colors" required>
                            </div>

                            <div>
                                <label for="full_name" class="block text-sm font-semibold text-gray-700 mb-2">الاسم الكامل *</label>
                                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>"
                                       class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:outline-none focus:border-blue-500 transition-colors" required>
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">رقم الهاتف *</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                       class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:outline-none focus:border-blue-500 transition-colors" required>
                            </div>

                            <div>
                                <label for="date_of_birth" class="block text-sm font-semibold text-gray-700 mb-2">تاريخ الميلاد</label>
                                <input type="date" id="date_of_birth" value="<?php echo htmlspecialchars($user['date_of_birth'] ?? ''); ?>"
                                       class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl bg-gray-100 cursor-not-allowed" disabled>
                            </div>

                            <div>
                                <label for="gender" class="block text-sm font-semibold text-gray-700 mb-2">الجنس</label>
                                <input type="text" id="gender" value="<?php echo ($user['gender'] ?? '') == 'male' ? 'ذكر' : 'أنثى'; ?>"
                                       class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl bg-gray-100 cursor-not-allowed" disabled>
                            </div>
                        </div>

                        <!-- Password Change Section -->
                        <div class="mt-8 pt-8 border-t-2 border-gray-200">
                            <div class="flex items-center gap-3 mb-4">
                                <i class="fas fa-lock text-blue-600 text-xl"></i>
                                <h3 class="text-xl font-bold text-gray-900">تغيير كلمة المرور</h3>
                            </div>
                            <p class="text-gray-600 mb-6">
                                اترك الحقول فارغة إذا كنت لا تريد تغيير كلمة المرور
                            </p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="current_password" class="block text-sm font-semibold text-gray-700 mb-2">كلمة المرور الحالية</label>
                                    <input type="password" id="current_password" name="current_password" placeholder="أدخل كلمة المرور الحالية"
                                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:outline-none focus:border-blue-500 transition-colors">
                                </div>

                                <div>
                                    <label for="new_password" class="block text-sm font-semibold text-gray-700 mb-2">كلمة المرور الجديدة</label>
                                    <input type="password" id="new_password" name="new_password" placeholder="أدخل كلمة المرور الجديدة"
                                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:outline-none focus:border-blue-500 transition-colors">
                                </div>

                                <div class="md:col-span-2">
                                    <label for="confirm_password" class="block text-sm font-semibold text-gray-700 mb-2">تأكيد كلمة المرور الجديدة</label>
                                    <input type="password" id="confirm_password" name="confirm_password" placeholder="أعد إدخال كلمة المرور الجديدة"
                                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:outline-none focus:border-blue-500 transition-colors">
                                </div>
                            </div>
                        </div>

                        <!-- Submit Section -->
                        <div class="mt-8 text-center">
                            <button type="submit" name="update_profile"
                                    class="bg-gradient-to-r from-blue-600 to-blue-700 text-white font-bold py-4 px-8 rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1"
                                    id="save-btn">
                                <i class="fas fa-save ml-2"></i>
                                حفظ التغييرات
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Account Information Card -->
            <div class="bg-white rounded-2xl shadow-xl p-6 md:p-8 mt-8">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b-2 border-gray-200">
                    <i class="fas fa-info-circle text-blue-600 text-2xl"></i>
                    <h4 class="text-2xl font-bold text-gray-900">معلومات الحساب</h4>
                </div>
                <div class="space-y-4">
                    <div class="flex justify-between items-center p-4 bg-gray-50 rounded-xl hover:bg-blue-50 transition-colors">
                        <span class="text-gray-700 font-semibold flex items-center">
                            <i class="fas fa-calendar-plus text-blue-600 ml-2"></i>
                            تاريخ إنشاء الحساب
                        </span>
                        <span class="text-gray-900 font-bold"><?php echo isset($user['created_at']) ? date('d/m/Y', strtotime($user['created_at'])) : 'غير متوفر'; ?></span>
                    </div>
                    <div class="flex justify-between items-center p-4 bg-gray-50 rounded-xl hover:bg-blue-50 transition-colors">
                        <span class="text-gray-700 font-semibold flex items-center">
                            <i class="fas fa-sync-alt text-blue-600 ml-2"></i>
                            آخر تحديث
                        </span>
                        <span class="text-gray-900 font-bold"><?php echo isset($user['updated_at']) ? date('d/m/Y', strtotime($user['updated_at'])) : 'غير متوفر'; ?></span>
                    </div>
                    <div class="flex justify-between items-center p-4 bg-gray-50 rounded-xl hover:bg-green-50 transition-colors">
                        <span class="text-gray-700 font-semibold flex items-center">
                            <i class="fas fa-check-circle text-green-600 ml-2"></i>
                            حالة الحساب
                        </span>
                        <span class="text-green-600 font-bold">نشط</span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Delete Account Modal -->
    <div id="delete-account-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl p-6 w-full max-w-md">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">تأكيد حذف الحساب</h3>
                <p class="text-gray-600 mb-6">هل أنت متأكد من رغبتك في حذف حسابك؟ هذا الإجراء لا يمكن التراجع عنه وسيتم حذف جميع بياناتك نهائياً.</p>
                
                <div class="flex flex-col sm:flex-row-reverse justify-center gap-3">
                    <form id="delete-account-form" action="delete_account.php" method="POST" class="w-full">
                        <input type="hidden" name="confirm_delete" value="1">
                        <button type="submit" class="w-full bg-red-600 text-white px-6 py-3 rounded-xl hover:bg-red-700 transition-colors font-semibold">
                            نعم، احذف حسابي
                        </button>
                    </form>
                    <button type="button" 
                            onclick="document.getElementById('delete-account-modal').classList.add('hidden')" 
                            class="w-full bg-gray-200 text-gray-800 px-6 py-3 rounded-xl hover:bg-gray-300 transition-colors font-semibold">
                        إلغاء
                    </button>
                </div>
            </div>
        </div>
    </div>
    </div>

    <script>
    // Close modal when clicking outside
    document.getElementById('delete-account-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.add('hidden');
        }
    });
    
    // Close with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.getElementById('delete-account-modal').classList.add('hidden');
        }
    });
    </script>

    <?php require_once 'includes/footer.php'; ?>

    <script>
        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('delete-account-modal');
            if (e.target === modal) {
                modal.classList.add('hidden');
            }
        });
        
        // Close with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.getElementById('delete-account-modal').classList.add('hidden');
            }
        });
        
        // Handle delete account form submission
        document.getElementById('delete-account-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show confirmation dialog
            if (confirm('هل أنت متأكد أنك تريد حذف حسابك نهائياً؟ لا يمكن التراجع عن هذا الإجراء.')) {
                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> جاري الحذف...';
                
                // Submit the form
                this.submit();
            }
        });
        
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
