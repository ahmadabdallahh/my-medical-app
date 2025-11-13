<?php
session_start();
require_once '../config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Ensure user is logged in as a patient
if (!is_logged_in() || $_SESSION['user_type'] !== 'patient') {
    header('Location: ../login.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$user = get_logged_in_user($conn);

// Fetch patient-specific statistics
$user_id = $_SESSION['user_id'];
$upcoming_appointments = get_user_appointments($conn, $user_id);
$upcoming_count = 0;
$completed_count = 0;

foreach ($upcoming_appointments as $appointment) {
    if ($appointment['status'] == 'confirmed' && $appointment['appointment_date'] >= date('Y-m-d')) {
        $upcoming_count++;
    }
    if ($appointment['status'] == 'completed') {
        $completed_count++;
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم المريض - Health Tech</title>
    
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

    <!-- Top Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <button id="sidebarToggle" class="lg:hidden text-gray-600 hover:text-gray-900 focus:outline-none">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <a href="../index.php" class="mr-4 flex items-center">
                        <i class="fas fa-heartbeat text-blue-600 text-2xl ml-2"></i>
                        <span class="text-xl font-bold text-gray-800">Health Tech</span>
                    </a>
                </div>
                
                <div class="flex items-center space-x-4 space-x-reverse">
                    <div class="flex items-center space-x-3 space-x-reverse">
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['full_name']); ?></p>
                            <p class="text-xs text-gray-500">مريض</p>
                        </div>
                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-user text-blue-600"></i>
                        </div>
                    </div>
                    <a href="../logout.php" class="text-gray-500 hover:text-red-600 transition-colors">
                        <i class="fas fa-sign-out-alt text-xl"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex">
        <!-- Sidebar -->
        <aside id="sidebar" class="w-64 bg-white shadow-xl h-screen sticky top-16 hidden lg:block">
            <div class="p-4">
                <ul class="space-y-2">
                    <li>
                        <a href="index.php" class="flex items-center space-x-3 space-x-reverse p-3 rounded-lg bg-blue-50 text-blue-600 font-medium">
                            <i class="fas fa-home w-5"></i>
                            <span>لوحة التحكم</span>
                        </a>
                    </li>
                    <li>
                        <a href="appointments.php" class="flex items-center space-x-3 space-x-reverse p-3 rounded-lg hover:bg-gray-50 text-gray-700">
                            <i class="fas fa-calendar-alt w-5"></i>
                            <span>مواعيدي</span>
                        </a>
                    </li>
                    <li>
                        <a href="../search.php" class="flex items-center space-x-3 space-x-reverse p-3 rounded-lg hover:bg-gray-50 text-gray-700">
                            <i class="fas fa-search w-5"></i>
                            <span>البحث عن طبيب</span>
                        </a>
                    </li>
                    <li>
                        <a href="../doctors.php" class="flex items-center space-x-3 space-x-reverse p-3 rounded-lg hover:bg-gray-50 text-gray-700">
                            <i class="fas fa-user-md w-5"></i>
                            <span>الأطباء</span>
                        </a>
                    </li>
                    <li>
                        <a href="../hospitals.php" class="flex items-center space-x-3 space-x-reverse p-3 rounded-lg hover:bg-gray-50 text-gray-700">
                            <i class="fas fa-hospital w-5"></i>
                            <span>المستشفيات</span>
                        </a>
                    </li>
                    <li>
                        <a href="../profile.php" class="flex items-center space-x-3 space-x-reverse p-3 rounded-lg hover:bg-gray-50 text-gray-700">
                            <i class="fas fa-user-circle w-5"></i>
                            <span>الملف الشخصي</span>
                        </a>
                    </li>
                </ul>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-6">
            <!-- Welcome Section -->
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-2xl p-8 text-white mb-8">
                <h1 class="text-3xl font-bold mb-2">مرحباً بك، <?php echo htmlspecialchars($user['full_name']); ?>! 👋</h1>
                <p class="text-blue-100">نحن سعداء بوجودك في منصة Health Tech للرعاية الصحية</p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">المواعيد القادمة</p>
                            <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo $upcoming_count; ?></p>
                        </div>
                        <div class="bg-blue-100 rounded-full p-3">
                            <i class="fas fa-calendar-check text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">المواعيد المكتملة</p>
                            <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo $completed_count; ?></p>
                        </div>
                        <div class="bg-green-100 rounded-full p-3">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-purple-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">حالة الحساب</p>
                            <p class="text-lg font-bold text-gray-800 mt-2">نشط</p>
                        </div>
                        <div class="bg-purple-100 rounded-full p-3">
                            <i class="fas fa-shield-alt text-purple-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-orange-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">العضوية</p>
                            <p class="text-lg font-bold text-gray-800 mt-2">#<?php echo $user['id']; ?></p>
                        </div>
                        <div class="bg-orange-100 rounded-full p-3">
                            <i class="fas fa-id-card text-orange-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-8">
                <h2 class="text-xl font-bold text-gray-800 mb-6">إجراءات سريعة</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <a href="../search.php" class="group bg-blue-50 p-4 rounded-lg hover:bg-blue-100 transition-colors">
                        <div class="text-blue-600 mb-3">
                            <i class="fas fa-search text-2xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-800 group-hover:text-blue-600">البحث عن طبيب</h3>
                        <p class="text-sm text-gray-600 mt-1">ابحث عن أفضل الأطباء</p>
                    </a>

                    <a href="appointments.php" class="group bg-green-50 p-4 rounded-lg hover:bg-green-100 transition-colors">
                        <div class="text-green-600 mb-3">
                            <i class="fas fa-calendar-plus text-2xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-800 group-hover:text-green-600">حجز موعد جديد</h3>
                        <p class="text-sm text-gray-600 mt-1">احجز موعدك بسهولة</p>
                    </a>

                    <a href="../profile.php" class="group bg-purple-50 p-4 rounded-lg hover:bg-purple-100 transition-colors">
                        <div class="text-purple-600 mb-3">
                            <i class="fas fa-user-edit text-2xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-800 group-hover:text-purple-600">تعديل الملف الشخصي</h3>
                        <p class="text-sm text-gray-600 mt-1">حدث بياناتك الشخصية</p>
                    </a>

                    <a href="../appointments.php" class="group bg-orange-50 p-4 rounded-lg hover:bg-orange-100 transition-colors">
                        <div class="text-orange-600 mb-3">
                            <i class="fas fa-history text-2xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-800 group-hover:text-orange-600">السجل الطبي</h3>
                        <p class="text-sm text-gray-600 mt-1">عرض سجل المواعيد</p>
                    </a>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-6">آخر النشاطات</h2>
                <div class="space-y-4">
                    <div class="flex items-center space-x-4 space-x-reverse p-4 bg-gray-50 rounded-lg">
                        <div class="bg-blue-100 rounded-full p-2">
                            <i class="fas fa-calendar text-blue-600"></i>
                        </div>
                        <div class="flex-1">
                            <p class="font-medium text-gray-800">مرحباً بك في لوحة التحكم</p>
                            <p class="text-sm text-gray-600">آخر تسجيل دخول: اليوم</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden"></div>

    <script>
        // Mobile sidebar toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('hidden');
                sidebarOverlay.classList.toggle('hidden');
            });
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.add('hidden');
                sidebarOverlay.classList.add('hidden');
            });
        }
    </script>
</body>
</html>
