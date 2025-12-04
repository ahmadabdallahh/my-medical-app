<?php
session_start();
require_once '../config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/reminder_functions.php';

// Ensure user is logged in as a patient
if (!is_logged_in() || $_SESSION['user_type'] !== 'patient') {
    header('Location: ../login.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$user = get_logged_in_user();
$user_id = $_SESSION['user_id'];

// Handle Form Submissions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_reminder'])) {
        $data = [
            'medication_name' => sanitize_input($_POST['medication_name']),
            'dosage' => sanitize_input($_POST['dosage']),
            'frequency' => sanitize_input($_POST['frequency']),
            'time_of_day' => sanitize_input($_POST['time_of_day']),
            'start_date' => sanitize_input($_POST['start_date']),
            'end_date' => sanitize_input($_POST['end_date'])
        ];
        
        if (add_medication_reminder($conn, $user_id, $data)) {
            $success_message = 'تم إضافة التذكير بنجاح';
        } else {
            $error_message = 'حدث خطأ أثناء إضافة التذكير';
        }
    } elseif (isset($_POST['delete_reminder'])) {
        $reminder_id = (int)$_POST['reminder_id'];
        if (delete_reminder($conn, $reminder_id, $user_id)) {
            $success_message = 'تم حذف التذكير بنجاح';
        } else {
            $error_message = 'حدث خطأ أثناء حذف التذكير';
        }
    } elseif (isset($_POST['toggle_status'])) {
        $reminder_id = (int)$_POST['reminder_id'];
        $is_active = (int)$_POST['is_active'];
        if (update_reminder_status($conn, $reminder_id, $user_id, $is_active)) {
            $success_message = 'تم تحديث حالة التذكير';
        } else {
            $error_message = 'حدث خطأ أثناء تحديث الحالة';
        }
    } elseif (isset($_POST['simulate_notifications'])) {
        $count = process_due_reminders($conn);
        $success_message = "تمت المحاكاة: تم إرسال $count تذكير(ات)";
    }
}

$reminders = get_patient_reminders($conn, $user_id);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التذكيرات - Health Tech</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; }
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
                        <a href="index.php" class="flex items-center space-x-3 space-x-reverse p-3 rounded-lg hover:bg-gray-50 text-gray-700">
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
                        <a href="reminders.php" class="flex items-center space-x-3 space-x-reverse p-3 rounded-lg bg-blue-50 text-blue-600 font-medium">
                            <i class="fas fa-bell w-5"></i>
                            <span>التذكيرات</span>
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
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">تذكيرات الأدوية</h1>
                <div class="flex space-x-2 space-x-reverse">
                    <form method="POST" class="inline">
                        <button type="submit" name="simulate_notifications" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 transition">
                            <i class="fas fa-sync-alt ml-2"></i> محاكاة الإشعارات
                        </button>
                    </form>
                    <button onclick="document.getElementById('addReminderModal').classList.remove('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-plus ml-2"></i> إضافة تذكير
                    </button>
                </div>
            </div>

            <?php if ($success_message): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded" role="alert">
                    <p><?php echo $success_message; ?></p>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
                    <p><?php echo $error_message; ?></p>
                </div>
            <?php endif; ?>

            <!-- Reminders Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($reminders as $reminder): ?>
                    <div class="bg-white rounded-xl shadow-md p-6 border-r-4 <?php echo $reminder['is_active'] ? 'border-blue-500' : 'border-gray-300'; ?>">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($reminder['medication_name']); ?></h3>
                                <p class="text-gray-500 text-sm"><?php echo htmlspecialchars($reminder['dosage']); ?></p>
                            </div>
                            <div class="bg-blue-100 rounded-full p-2">
                                <i class="fas fa-pills text-blue-600"></i>
                            </div>
                        </div>
                        
                        <div class="space-y-2 text-gray-600 mb-4">
                            <div class="flex items-center">
                                <i class="fas fa-clock w-6 text-center text-gray-400 ml-2"></i>
                                <span><?php echo date('h:i A', strtotime($reminder['time_of_day'])); ?></span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-redo w-6 text-center text-gray-400 ml-2"></i>
                                <span>
                                    <?php 
                                    $freq_map = ['daily' => 'يومياً', 'twice_daily' => 'مرتين يومياً', 'weekly' => 'أسبوعياً', 'custom' => 'مخصص'];
                                    echo $freq_map[$reminder['frequency']] ?? $reminder['frequency']; 
                                    ?>
                                </span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-calendar-alt w-6 text-center text-gray-400 ml-2"></i>
                                <span class="text-sm">من <?php echo $reminder['start_date']; ?> إلى <?php echo $reminder['end_date']; ?></span>
                            </div>
                        </div>

                        <div class="flex justify-between items-center pt-4 border-t">
                            <form method="POST" class="inline">
                                <input type="hidden" name="reminder_id" value="<?php echo $reminder['id']; ?>">
                                <input type="hidden" name="is_active" value="<?php echo $reminder['is_active'] ? 0 : 1; ?>">
                                <button type="submit" name="toggle_status" class="text-sm font-medium <?php echo $reminder['is_active'] ? 'text-green-600 hover:text-green-800' : 'text-gray-500 hover:text-gray-700'; ?>">
                                    <i class="fas <?php echo $reminder['is_active'] ? 'fa-toggle-on' : 'fa-toggle-off'; ?> text-lg align-middle ml-1"></i>
                                    <?php echo $reminder['is_active'] ? 'نشط' : 'متوقف'; ?>
                                </button>
                            </form>
                            
                            <form method="POST" class="inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا التذكير؟');">
                                <input type="hidden" name="reminder_id" value="<?php echo $reminder['id']; ?>">
                                <button type="submit" name="delete_reminder" class="text-red-500 hover:text-red-700">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($reminders)): ?>
                    <div class="col-span-full text-center py-12 bg-white rounded-xl shadow-sm border-2 border-dashed border-gray-300">
                        <i class="fas fa-pills text-gray-300 text-5xl mb-4"></i>
                        <h3 class="text-xl font-medium text-gray-600">لا توجد تذكيرات حالياً</h3>
                        <p class="text-gray-500 mt-2">أضف تذكيراتك للأدوية لتصلك إشعارات في موعدها</p>
                        <button onclick="document.getElementById('addReminderModal').classList.remove('hidden')" class="mt-4 text-blue-600 hover:text-blue-800 font-medium">
                            + إضافة تذكير جديد
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Add Reminder Modal -->
    <div id="addReminderModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
            <div class="bg-blue-600 px-6 py-4 flex justify-between items-center">
                <h3 class="text-white font-bold text-lg">إضافة تذكير دواء</h3>
                <button onclick="document.getElementById('addReminderModal').classList.add('hidden')" class="text-white hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">اسم الدواء</label>
                    <input type="text" name="medication_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">الجرعة</label>
                        <input type="text" name="dosage" placeholder="مثال: 500mg" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">التكرار</label>
                        <select name="frequency" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                            <option value="daily">يومياً</option>
                            <option value="twice_daily">مرتين يومياً</option>
                            <option value="weekly">أسبوعياً</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">وقت التذكير</label>
                    <input type="time" name="time_of_day" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">تاريخ البدء</label>
                        <input type="date" name="start_date" value="<?php echo date('Y-m-d'); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">تاريخ الانتهاء</label>
                        <input type="date" name="end_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" name="add_reminder" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 transition">
                        حفظ التذكير
                    </button>
                </div>
            </form>
        </div>
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
