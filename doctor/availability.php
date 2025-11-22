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

$pageTitle = "إدارة مواعيد العمل - لوحة تحكم الدكتور";

// Handle availability update
$success = '';
$error = '';

// Check if doctor_availability table exists
try {
    $conn->query("SELECT 1 FROM doctor_availability LIMIT 1");
    $table_exists = true;
} catch (PDOException $e) {
    $table_exists = false;
    $error = "جدول مواعيد العمل غير موجود. يرجى إنشاء الجدول أولاً.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_availability']) && $table_exists) {
    // Clear existing availability
    $conn->prepare("DELETE FROM doctor_availability WHERE doctor_id = ?")->execute([$doctor_id]);

    // Insert new availability
    $days = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

    foreach ($days as $day) {
        if (isset($_POST[$day . '_enabled']) && $_POST[$day . '_enabled'] == 'on') {
            $start_time = sanitize_input($_POST[$day . '_start']);
            $end_time = sanitize_input($_POST[$day . '_end']);
            $slot_duration = (int)$_POST['slot_duration'];

            if (!empty($start_time) && !empty($end_time)) {
                $stmt = $conn->prepare("
                    INSERT INTO doctor_availability (doctor_id, day_of_week, start_time, end_time, slot_duration)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$doctor_id, $day, $start_time, $end_time, $slot_duration]);
            }
        }
    }

    $success = 'تم تحديث مواعيد العمل بنجاح';
}

// Get current availability
$availability_map = [];
if ($table_exists) {
    try {
        $stmt = $conn->prepare("SELECT * FROM doctor_availability WHERE doctor_id = ?");
        $stmt->execute([$doctor_id]);
        $availability = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($availability as $avail) {
            $availability_map[$avail['day_of_week']] = $avail;
        }
    } catch (PDOException $e) {
        $availability_map = [];
    }
}

// Default slot duration
$default_slot_duration = 30;
if (!empty($availability)) {
    $default_slot_duration = $availability[0]['slot_duration'] ?? 30;
}

$days_arabic = [
    'saturday' => 'السبت',
    'sunday' => 'الأحد',
    'monday' => 'الإثنين',
    'tuesday' => 'الثلاثاء',
    'wednesday' => 'الأربعاء',
    'thursday' => 'الخميس',
    'friday' => 'الجمعة'
];
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

                    <a href="profile.php" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors">
                        <i class="fas fa-user-circle ml-3"></i>
                        الملف الشخصي
                    </a>

                    <a href="availability.php" class="flex items-center px-4 py-3 text-gray-700 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
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
                <h1 class="text-3xl font-bold text-gray-900">إدارة مواعيد العمل</h1>
                <p class="text-gray-600 mt-2">حدد أيام وساعات العمل المتاحة لحجز المواعيد</p>
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

            <?php if (!$table_exists): ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 mb-8">
                    <h3 class="text-lg font-semibold text-yellow-900 mb-3">
                        <i class="fas fa-database ml-2"></i>
                        إعداد جدول مواعيد العمل
                    </h3>
                    <p class="text-yellow-800 mb-4">جدول مواعيد العمل غير موجود في قاعدة البيانات. يرجى إنشاؤه أولاً.</p>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <a href="/App-Demo/create_availability_table.php" class="inline-flex items-center px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors">
                            <i class="fas fa-cog ml-2"></i>
                            إنشاء جدول مواعيد العمل
                        </a>
                        <a href="/App-Demo/SQL/doctor_availability.sql" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                            <i class="fas fa-download ml-2"></i>
                            تحميل ملف SQL
                        </a>
                    </div>
                </div>
            <?php else: ?>

                <!-- Availability Form -->
                <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">مواعيد العمل الأسبوعية</h2>

                    <form method="POST" action="" class="space-y-6">
                        <!-- Slot Duration -->
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-clock ml-2"></i>
                                مدة كل موعد (بالدقائق)
                            </label>
                            <select name="slot_duration" class="w-full md:w-48 p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="15" <?php echo $default_slot_duration == 15 ? 'selected' : ''; ?>>15 دقيقة</option>
                                <option value="30" <?php echo $default_slot_duration == 30 ? 'selected' : ''; ?>>30 دقيقة</option>
                                <option value="45" <?php echo $default_slot_duration == 45 ? 'selected' : ''; ?>>45 دقيقة</option>
                                <option value="60" <?php echo $default_slot_duration == 60 ? 'selected' : ''; ?>>60 دقيقة</option>
                            </select>
                        </div>

                        <!-- Days Schedule -->
                        <div class="space-y-4">
                            <?php foreach ($days_arabic as $day_en => $day_ar): ?>
                                <?php
                                $avail = $availability_map[$day_en] ?? null;
                                $is_enabled = $avail ? true : false;
                                $start_time = $avail['start_time'] ?? '09:00';
                                $end_time = $avail['end_time'] ?? '17:00';
                                ?>

                                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center">
                                            <input type="checkbox"
                                                name="<?php echo $day_en; ?>_enabled"
                                                id="<?php echo $day_en; ?>_enabled"
                                                <?php echo $is_enabled ? 'checked' : ''; ?>
                                                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                                onchange="toggleDay('<?php echo $day_en; ?>')">
                                            <label for="<?php echo $day_en; ?>_enabled" class="mr-3 text-lg font-semibold text-gray-900">
                                                <?php echo $day_ar; ?>
                                            </label>
                                        </div>

                                        <?php if ($is_enabled): ?>
                                            <span class="text-sm text-green-600 font-medium">
                                                <i class="fas fa-check-circle ml-1"></i>
                                                متاح
                                            </span>
                                        <?php else: ?>
                                            <span class="text-sm text-gray-500 font-medium">
                                                <i class="fas fa-times-circle ml-1"></i>
                                                مغلق
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <div id="<?php echo $day_en; ?>_times" class="grid grid-cols-1 md:grid-cols-2 gap-4 <?php echo $is_enabled ? '' : 'opacity-50 pointer-events-none'; ?>">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">وقت البدء</label>
                                            <input type="time"
                                                name="<?php echo $day_en; ?>_start"
                                                value="<?php echo $start_time; ?>"
                                                class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">وقت الانتهاء</label>
                                            <input type="time"
                                                name="<?php echo $day_en; ?>_end"
                                                value="<?php echo $end_time; ?>"
                                                class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="flex justify-end pt-4">
                            <button type="submit" name="update_availability"
                                class="px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-save ml-2"></i>
                                حفظ مواعيد العمل
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
                <a href="/App-Demo/doctor/appointments.php" class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow text-center">
                    <i class="fas fa-calendar-check text-blue-600 text-3xl mb-3"></i>
                    <h3 class="font-semibold text-gray-900">عرض المواعيد</h3>
                    <p class="text-gray-600 text-sm mt-2">شاهد جميع المواعيد المحجوزة</p>
                </a>

                <a href="/App-Demo/doctor/index.php" class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow text-center">
                    <i class="fas fa-arrow-left text-blue-600 text-3xl mb-3"></i>
                    <h3 class="font-semibold text-gray-900">العودة للرئيسية</h3>
                    <p class="text-gray-600 text-sm mt-2">لوحة تحكم الدكتور</p>
                </a>
            </div>
        </main>
    </div>

    <script>
        function toggleDay(day) {
            const checkbox = document.getElementById(day + '_enabled');
            const timesDiv = document.getElementById(day + '_times');

            if (checkbox.checked) {
                timesDiv.classList.remove('opacity-50', 'pointer-events-none');
            } else {
                timesDiv.classList.add('opacity-50', 'pointer-events-none');
            }
        }

        // Initialize all days
        document.addEventListener('DOMContentLoaded', function() {
            const days = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
            days.forEach(day => {
                toggleDay(day);
            });
        });
    </script>

</body>

</html>
