<?php
session_start();
require_once '../config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Ensure user is logged in as a doctor
if (!is_logged_in() || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

$doctor_id = $_SESSION['user_id'];
$doctor_name = $_SESSION['user_name'] ?? 'Doctor';

$pageTitle = "الملف الشخصي - لوحة تحكم الدكتور";

// Get doctor's full information
try {
    $stmt = $conn->prepare("
        SELECT d.*, u.username, u.email, s.name as specialty_name, h.name as hospital_name, c.name as clinic_name
        FROM doctors d
        LEFT JOIN users u ON d.user_id = u.id
        LEFT JOIN specialties s ON d.specialty_id = s.id
        LEFT JOIN hospitals h ON d.hospital_id = h.id
        LEFT JOIN clinics c ON d.clinic_id = c.id
        WHERE d.user_id = ?
    ");
    $stmt->execute([$doctor_id]);
    $doctor_info = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Doctor info error: " . $e->getMessage());
    $doctor_info = [];
}

// Handle profile update
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = sanitize_input($_POST['full_name']);
    $phone = sanitize_input($_POST['phone']);
    $email = sanitize_input($_POST['email']);
    $education = sanitize_input($_POST['education']);
    $consultation_fee = sanitize_input($_POST['consultation_fee']);
    
    if (empty($full_name) || empty($email)) {
        $error = 'الاسم والبريد الإلكتروني مطلوبان';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'البريد الإلكتروني غير صحيح';
    } else {
        try {
            // Update users table
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $doctor_id]);
            
            // Update doctors table
            $stmt = $conn->prepare("
                UPDATE doctors SET 
                full_name = ?, phone = ?, education = ?, consultation_fee = ? 
                WHERE user_id = ?
            ");
            $stmt->execute([$full_name, $phone, $education, $consultation_fee, $doctor_id]);
            
            $success = 'تم تحديث الملف الشخصي بنجاح';
            
            // Update session
            $_SESSION['user_name'] = $full_name;
            
            // Refresh data
            $stmt = $conn->prepare("
                SELECT d.*, u.username, u.email, s.name as specialty_name, h.name as hospital_name, c.name as clinic_name
                FROM doctors d
                LEFT JOIN users u ON d.user_id = u.id
                LEFT JOIN specialties s ON d.specialty_id = s.id
                LEFT JOIN hospitals h ON d.hospital_id = h.id
                LEFT JOIN clinics c ON d.clinic_id = c.id
                WHERE d.user_id = ?
            ");
            $stmt->execute([$doctor_id]);
            $doctor_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            $error = 'حدث خطأ أثناء تحديث البيانات: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts (Cairo) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Cairo', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="../index.php" class="flex items-center">
                        <i class="fas fa-heartbeat text-blue-600 text-2xl ml-2"></i>
                        <span class="text-xl font-bold text-gray-800">Health Tech</span>
                    </a>
                </div>
                
                <div class="flex items-center space-x-4 space-x-reverse">
                    <span class="text-gray-600">
                        <i class="fas fa-user-md text-blue-600 ml-2"></i>
                        د. <?php echo htmlspecialchars($doctor_name); ?>
                    </span>
                    <a href="../profile.php" class="text-gray-600 hover:text-blue-600 transition-colors">
                        <i class="fas fa-user text-lg"></i>
                    </a>
                    <a href="../logout.php" class="text-gray-600 hover:text-red-600 transition-colors">
                        <i class="fas fa-sign-out-alt text-lg"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar and Main Content -->
    <div class="flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-lg min-h-screen">
            <div class="p-4">
                <h3 class="text-lg font-bold text-gray-800 mb-6">لوحة التحكم</h3>
                
                <nav class="space-y-2">
                    <a href="index.php" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors">
                        <i class="fas fa-home ml-3"></i>
                        الرئيسية
                    </a>
                    
                    <a href="appointments.php" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors">
                        <i class="fas fa-calendar-check ml-3"></i>
                        المواعيد
                    </a>
                    
                    <a href="profile.php" class="flex items-center px-4 py-3 text-gray-700 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                        <i class="fas fa-user-circle ml-3"></i>
                        الملف الشخصي
                    </a>
                    
                    <a href="availability.php" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors">
                        <i class="fas fa-clock ml-3"></i>
                        مواعيد العمل
                    </a>
                    
                    <a href="../search.php" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors">
                        <i class="fas fa-search ml-3"></i>
                        البحث عن أطباء
                    </a>
                    
                    <a href="../index.php" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors">
                        <i class="fas fa-globe ml-3"></i>
                        الموقع الرئيسي
                    </a>
                </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">الملف الشخصي</h1>
                <p class="text-gray-600 mt-2">إدارة معلوماتك الشخصية والمهنية</p>
            </div>

            <!-- Success/Error Messages -->
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                    <i class="fas fa-check-circle ml-2"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <i class="fas fa-exclamation-circle ml-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Profile Form -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6">المعلومات الشخصية</h2>
                
                <form method="POST" action="" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">الاسم الكامل *</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($doctor_info['full_name'] ?? ''); ?>" 
                                   class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">اسم المستخدم</label>
                            <input type="text" value="<?php echo htmlspecialchars($doctor_info['username'] ?? ''); ?>" 
                                   class="w-full p-3 border border-gray-300 rounded-lg bg-gray-50" disabled>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">البريد الإلكتروني *</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($doctor_info['email'] ?? ''); ?>" 
                                   class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">رقم الهاتف</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($doctor_info['phone'] ?? ''); ?>" 
                                   class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">التخصص</label>
                            <input type="text" value="<?php echo htmlspecialchars($doctor_info['specialty_name'] ?? ''); ?>" 
                                   class="w-full p-3 border border-gray-300 rounded-lg bg-gray-50" disabled>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">المستشفى</label>
                            <input type="text" value="<?php echo htmlspecialchars($doctor_info['hospital_name'] ?? ''); ?>" 
                                   class="w-full p-3 border border-gray-300 rounded-lg bg-gray-50" disabled>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">التعليم</label>
                        <textarea name="education" rows="3" 
                                  class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($doctor_info['education'] ?? ''); ?></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">سعر الكشف (ج.م)</label>
                        <input type="number" name="consultation_fee" value="<?php echo htmlspecialchars($doctor_info['consultation_fee'] ?? ''); ?>" 
                               step="0.01" min="0" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" name="update_profile" 
                                class="px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-save ml-2"></i>
                            حفظ التغييرات
                        </button>
                    </div>
                </form>
            </div>

            <!-- Doctor Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                    <i class="fas fa-star text-yellow-500 text-3xl mb-3"></i>
                    <h3 class="font-semibold text-gray-900">التقييم</h3>
                    <p class="text-2xl font-bold text-gray-900 mt-2"><?php echo number_format($doctor_info['rating'] ?? 0, 1); ?> ⭐</p>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                    <i class="fas fa-briefcase text-blue-600 text-3xl mb-3"></i>
                    <h3 class="font-semibold text-gray-900">سنوات الخبرة</h3>
                    <p class="text-2xl font-bold text-gray-900 mt-2"><?php echo $doctor_info['experience_years'] ?? 0; ?></p>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                    <i class="fas fa-money-bill-wave text-green-600 text-3xl mb-3"></i>
                    <h3 class="font-semibold text-gray-900">سعر الكشف</h3>
                    <p class="text-2xl font-bold text-gray-900 mt-2"><?php echo $doctor_info['consultation_fee'] ?? 0; ?> ج.م</p>
                </div>
            </div>
        </main>
    </div>

</body>
</html>
