<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Create a database connection object
$database = new Database();
$pdo = $database->getConnection();

check_user_role('admin');

// Add a critical check to ensure user is logged in properly
if (!isset($_SESSION['user_id'])) {
    // If user_id is not in session, redirect to login page
    header("Location: ../login.php?error=session_expired");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Handle Profile Information Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $profile_image_path = null;

    // Handle file upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/avatars/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_info = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($file_info, $_FILES['profile_image']['tmp_name']);
        finfo_close($file_info);

        $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($mime_type, $allowed_mime_types)) {
            $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $new_filename = 'user_' . $user_id . '_' . time() . '.' . $file_extension;
            $target_file = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                $profile_image_path = 'uploads/avatars/' . $new_filename;
            } else {
                $message = 'حدث خطأ أثناء رفع الصورة.';
                $message_type = 'error';
            }
        } else {
            $message = 'نوع الملف غير مسموح به. يرجى رفع صورة (JPG, PNG, GIF).';
            $message_type = 'error';
        }
    }

    if (empty($message)) { // Proceed only if no upload error
        $sql = "UPDATE users SET full_name = ?, username = ?, email = ?";
        $params = [$full_name, $username, $email];

        if ($profile_image_path) {
            $sql .= ", profile_image = ?";
            $params[] = $profile_image_path;
        }

        $sql .= " WHERE id = ?";
        $params[] = $user_id;

        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) {
            $message = 'تم تحديث الملف الشخصي بنجاح.';
            $message_type = 'success';
            // Update session if username changed
            $_SESSION['username'] = $username;
        } else {
            $message = 'حدث خطأ أثناء تحديث البيانات.';
            $message_type = 'error';
        }
    }
}

// Handle password update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if ($user && password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 8) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                if ($update_stmt->execute([$hashed_password, $user_id])) {
                    $message = 'تم تحديث كلمة المرور بنجاح.';
                    $message_type = 'success';
                } else {
                    $message = 'حدث خطأ أثناء تحديث كلمة المرور.';
                    $message_type = 'error';
                }
            } else {
                $message = 'يجب أن تكون كلمة المرور الجديدة 8 أحرف على الأقل.';
                $message_type = 'error';
            }
        } else {
            $message = 'كلمتا المرور الجديدتان غير متطابقتين.';
            $message_type = 'error';
        }
    } else {
        $message = 'كلمة المرور الحالية غير صحيحة.';
        $message_type = 'error';
    }
}

// Fetch user data for display
$stmt = $pdo->prepare("SELECT full_name, username, email, profile_image FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$admin_data = $stmt->fetch(PDO::FETCH_ASSOC);

$page_title = "الملف الشخصي";

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - لوحة التحكم</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Alpine.js for dropdown menu -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
        }
        .alert-success { background-color: #d4edda; border-color: #c3e6cb; color: #155724; }
        .alert-error { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; }
    </style>
</head>
<body class="bg-gray-100">

<div class="flex h-screen">
    <?php include '../includes/dashboard_sidebar.php'; ?>

    <div class="flex-1 flex flex-col overflow-hidden lg:mr-64">
        <?php include '../includes/dashboard_header.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-4 md:p-6">
            <div class="container mx-auto">

                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-bold text-gray-800"><?php echo $page_title; ?></h3>
                    <button onclick="history.back()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg inline-flex items-center">
                        <i class="fas fa-arrow-right ml-2"></i>
                        <span>العودة للخلف</span>
                    </button>
                </div>

                <?php if ($message): ?>
                    <div class="p-4 mb-4 text-sm rounded-lg <?php echo $message_type == 'success' ? 'alert-success' : 'alert-error'; ?>" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- Admin Info Card -->
                <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                    <h4 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">معلومات الحساب</h4>
                    <form action="profile.php" method="POST" enctype="multipart/form-data">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Profile Picture -->
                            <div class="md:col-span-1 flex flex-col items-center">
                                <img id="avatar-preview" src="../<?php echo !empty($admin_data['profile_image']) ? htmlspecialchars($admin_data['profile_image']) : 'assets/images/default-avatar.png'; ?>" alt="Avatar" class="w-32 h-32 rounded-full object-cover mb-4 border-2 border-gray-200">
                                <input type="file" name="profile_image" id="profile_image" class="hidden" onchange="previewAvatar(event)">
                                <label for="profile_image" class="cursor-pointer bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg">
                                    تغيير الصورة
                                </label>
                            </div>

                            <!-- User Details -->
                            <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="full_name" class="block text-sm font-medium text-gray-700">الاسم الكامل</label>
                                    <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($admin_data['full_name']); ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label for="username" class="block text-sm font-medium text-gray-700">اسم المستخدم</label>
                                    <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($admin_data['username']); ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div class="md:col-span-2">
                                    <label for="email" class="block text-sm font-medium text-gray-700">البريد الإلكتروني</label>
                                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($admin_data['email']); ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                        </div>
                        <div class="mt-6 text-left">
                            <button type="submit" name="update_profile" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg focus:outline-none focus:shadow-outline transition-colors duration-200">
                                حفظ التغييرات
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Change Password Card -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h4 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">تغيير كلمة المرور</h4>
                    <form action="profile.php" method="POST">
                        <div class="space-y-4">
                            <div>
                                <label for="current_password" class="block text-sm font-medium text-gray-700">كلمة المرور الحالية</label>
                                <input type="password" name="current_password" id="current_password" required class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label for="new_password" class="block text-sm font-medium text-gray-700">كلمة المرور الجديدة</label>
                                <input type="password" name="new_password" id="new_password" required class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700">تأكيد كلمة المرور الجديدة</label>
                                <input type="password" name="confirm_password" id="confirm_password" required class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                        <div class="mt-6">
                            <button type="submit" name="update_password" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition-colors duration-200">
                                تحديث كلمة المرور
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </main>
    </div>
</div>

<script>
    // For Sidebar Toggle
    document.addEventListener('DOMContentLoaded', function () {
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('sidebar');

        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function (e) {
                e.stopPropagation();
                sidebar.classList.toggle('hidden');
            });

            document.addEventListener('click', function (e) {
                const isClickInsideSidebar = sidebar.contains(e.target);
                const isClickOnToggle = sidebarToggle.contains(e.target);

                if (!sidebar.classList.contains('hidden') && !isClickInsideSidebar && !isClickOnToggle) {
                    sidebar.classList.add('hidden');
                }
            });
        }
    });

    // For Avatar Preview
    function previewAvatar(event) {
        const reader = new FileReader();
        reader.onload = function(){
            const output = document.getElementById('avatar-preview');
            output.src = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);
    }
</script>

</body>
</html>
