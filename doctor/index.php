<?php
session_start();
require_once '../config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if the user is a doctor, otherwise redirect to login
if (!is_logged_in() || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

$user_id = $_SESSION['user_id'];
$doctor_name = $_SESSION['user_name'] ?? 'Doctor';

$pageTitle = "لوحة تحكم الدكتور - Health Tech";

// Get doctor's basic info and doctor_id (doctors.id, not users.id)
$doctor_id = null;
try {
    $stmt = $conn->prepare("SELECT * FROM doctors WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $doctor_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($doctor_info) {
        $doctor_name = $doctor_info['full_name'];
        $doctor_id = $doctor_info['id']; // This is doctors.id, used in appointments table
    }
} catch (PDOException $e) {
    error_log("Doctor info error: " . $e->getMessage());
}

// If doctor_id not found, redirect
if (!$doctor_id) {
    header('Location: ../login.php?error=doctor_not_found');
    exit();
}

// Get today's appointments count
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE doctor_id = ? AND DATE(appointment_date) = CURDATE()");
    $stmt->execute([$doctor_id]);
    $today_appointments = $stmt->fetch()['count'];
} catch (PDOException $e) {
    $today_appointments = 0;
}

// Get upcoming appointments count
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE doctor_id = ? AND appointment_date > CURDATE() AND status = 'confirmed'");
    $stmt->execute([$doctor_id]);
    $upcoming_count = $stmt->fetch()['count'];
} catch (PDOException $e) {
    $upcoming_count = 0;
}

// Get total patients count
try {
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT user_id) as count FROM appointments WHERE doctor_id = ?");
    $stmt->execute([$doctor_id]);
    $total_patients = $stmt->fetch()['count'];
} catch (PDOException $e) {
    $total_patients = 0;
}

// Get recent appointments
try {
    $stmt = $conn->prepare("
        SELECT a.*, u.full_name as patient_name
        FROM appointments a
        LEFT JOIN users u ON a.user_id = u.id
        WHERE a.doctor_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
        LIMIT 5
    ");
    $stmt->execute([$doctor_id]);
    $recent_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recent_appointments = [];
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
                    <button id="openSidebar" class="md:hidden p-2 text-gray-600 hover:text-blue-600" aria-label="Toggle Menu">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
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
        <!-- Mobile Sidebar Overlay -->
        <div id="doctorOverlay" class="fixed inset-0 bg-black/40 hidden z-40 md:hidden"></div>
        <!-- Sidebar (mobile drawer + desktop static) -->
        <aside id="doctorSidebar" class="fixed inset-y-0 right-0 w-72 bg-white shadow-xl transform translate-x-full transition-transform duration-300 ease-in-out z-50 md:static md:translate-x-0 md:w-64 md:shadow-lg min-h-screen">
            <div class="p-4">
                <h3 class="text-lg font-bold text-gray-800 mb-6">لوحة التحكم</h3>

                <nav class="space-y-2">
                    <a href="index.php"
                        class="flex items-center px-4 py-3 text-gray-700 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                        <i class="fas fa-home ml-3"></i>
                        الرئيسية
                    </a>

                    <a href="appointments.php"
                        class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors">
                        <i class="fas fa-calendar-check ml-3"></i>
                        المواعيد
                    </a>

                    <a href="profile.php"
                        class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors">
                        <i class="fas fa-user-circle ml-3"></i>
                        الملف الشخصي
                    </a>

                    <a href="availability.php"
                        class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors">
                        <i class="fas fa-clock ml-3"></i>
                        مواعيد العمل
                    </a>

                    <a href="../search.php"
                        class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors">
                        <i class="fas fa-search ml-3"></i>
                        البحث عن أطباء
                    </a>

                    <a href="../hospitals.php"
                        class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors">
                        <i class="fas fa-hospital ml-3"></i>
                        المستشفيات
                    </a>

                    <a href="../index.php"
                        class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors">
                        <i class="fas fa-globe ml-3"></i>
                        الموقع الرئيسي
                    </a>
                </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-4 sm:p-6 lg:p-8">
            <!-- Welcome Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">أهلاً بك، د. <?php echo htmlspecialchars($doctor_name); ?>
                </h1>
                <p class="text-gray-600 mt-2">هذا هو ملخص نشاطك اليومي</p>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-6 mb-8">
                <!-- Today's Appointments -->
                <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6 border-l-4 border-blue-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">مواعيد اليوم</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $today_appointments; ?></p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i class="fas fa-calendar-day text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Appointments -->
                <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6 border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">المواعيد القادمة</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $upcoming_count; ?></p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-calendar-plus text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Total Patients -->
                <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6 border-l-4 border-purple-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">إجمالي المرضى</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $total_patients; ?></p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full">
                            <i class="fas fa-users text-purple-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Rating -->
                <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6 border-l-4 border-yellow-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">التقييم</p>
                            <p class="text-2xl font-bold text-gray-900">
                                <?php echo $doctor_info ? number_format($doctor_info['rating'], 1) : '0.0'; ?> ⭐
                            </p>
                        </div>
                        <div class="bg-yellow-100 p-3 rounded-full">
                            <i class="fas fa-star text-yellow-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Appointments -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">آخر المواعيد</h2>

                <?php if (!empty($recent_appointments)): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-right py-3 px-4">المريض</th>
                                    <th class="text-right py-3 px-4">التاريخ</th>
                                    <th class="text-right py-3 px-4">الوقت</th>
                                    <th class="text-right py-3 px-4">الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_appointments as $appointment): ?>
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="py-3 px-4">
                                            <?php echo htmlspecialchars($appointment['patient_name'] ?? 'غير محدد'); ?>
                                        </td>
                                        <td class="py-3 px-4"><?php echo $appointment['appointment_date']; ?></td>
                                        <td class="py-3 px-4"><?php echo $appointment['appointment_time']; ?></td>
                                        <td class="py-3 px-4">
                                            <span
                                                class="px-2 py-1 text-xs rounded-full <?php echo $appointment['status'] == 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                                <?php echo $appointment['status'] == 'confirmed' ? 'مؤكد' : 'ملغي'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-calendar-times text-4xl mb-3"></i>
                        <p>لا توجد مواعيد حالياً</p>
                    </div>
                <?php endif; ?>

                <div class="mt-6 text-center">
                    <a href="appointments.php"
                        class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-list ml-2"></i>
                        عرض جميع المواعيد
                    </a>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mt-8">
                <a href="availability.php"
                    class="bg-white rounded-xl shadow-lg p-4 sm:p-6 hover:shadow-xl transition-shadow text-center">
                    <i class="fas fa-clock text-blue-600 text-3xl mb-3"></i>
                    <h3 class="font-semibold text-gray-900">إدارة مواعيد العمل</h3>
                    <p class="text-gray-600 text-sm mt-2">حدد أوقات توافرك</p>
                </a>

                <a href="profile.php"
                    class="bg-white rounded-xl shadow-lg p-4 sm:p-6 hover:shadow-xl transition-shadow text-center">
                    <i class="fas fa-user-edit text-blue-600 text-3xl mb-3"></i>
                    <h3 class="font-semibold text-gray-900">تعديل الملف الشخصي</h3>
                    <p class="text-gray-600 text-sm mt-2">حدث بياناتك ومعلوماتك</p>
                </a>

                <a href="../search.php"
                    class="bg-white rounded-xl shadow-lg p-4 sm:p-6 hover:shadow-xl transition-shadow text-center">
                    <i class="fas fa-search text-blue-600 text-3xl mb-3"></i>
                    <h3 class="font-semibold text-gray-900">البحث عن زملاء</h3>
                    <p class="text-gray-600 text-sm mt-2">ابحث عن أطباء آخرين</p>
                </a>
            </div>
        </main>
    </div>

    <script>
      (function() {
        const openBtn = document.getElementById('openSidebar');
        const sidebar = document.getElementById('doctorSidebar');
        const overlay = document.getElementById('doctorOverlay');

        function openDrawer(){
          if(sidebar){ sidebar.classList.remove('translate-x-full'); }
          if(overlay){ overlay.classList.remove('hidden'); }
          document.body.classList.add('overflow-hidden');
        }
        function closeDrawer(){
          if(sidebar){ sidebar.classList.add('translate-x-full'); }
          if(overlay){ overlay.classList.add('hidden'); }
          document.body.classList.remove('overflow-hidden');
        }

        if(openBtn){ openBtn.addEventListener('click', openDrawer); }
        if(overlay){ overlay.addEventListener('click', closeDrawer); }
        document.addEventListener('keydown', function(e){ if(e.key==='Escape'){ closeDrawer(); }});
      })();
    </script>
</body>

</html>
