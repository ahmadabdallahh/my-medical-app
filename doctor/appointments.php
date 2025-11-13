<?php
session_start();
require_once '../config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Ensure user is logged in as a doctor
if (!is_logged_in() || $_SESSION['user_type'] !== 'doctor') {
    header('Location: /App-Demo/login.php');
    exit();
}

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

$doctor_user_id = $_SESSION['user_id'];
$doctor_name = $_SESSION['user_name'] ?? 'Doctor';

// Get the real doctor_id from doctors table
try {
    $stmt = $conn->prepare("SELECT id FROM doctors WHERE user_id = ?");
    $stmt->execute([$doctor_user_id]);
    $doctor_record = $stmt->fetch();
    
    if (!$doctor_record) {
        // If no doctor record found, use user_id as fallback
        $doctor_id = $doctor_user_id;
    } else {
        $doctor_id = $doctor_record['id'];
    }
    
} catch (PDOException $e) {
    error_log("Doctor lookup error: " . $e->getMessage());
    $doctor_id = $doctor_user_id; // fallback
}

$pageTitle = "المواعيد - لوحة تحكم الدكتور";

// Get doctor's appointments
try {
    $stmt = $conn->prepare("
        SELECT a.*, u.full_name as patient_name, u.phone as patient_phone, u.email as patient_email
        FROM appointments a 
        LEFT JOIN users u ON a.user_id = u.id 
        WHERE a.doctor_id = ? 
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
    $stmt->execute([$doctor_id]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $appointments = [];
    error_log("Get appointments error: " . $e->getMessage());
}

// Handle appointment status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $appointment_id = (int)$_POST['appointment_id'];
    $new_status = sanitize_input($_POST['status']);
    
    try {
        $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ? AND doctor_id = ?");
        $stmt->execute([$new_status, $appointment_id, $doctor_id]);
        
        // Refresh appointments
        $stmt = $conn->prepare("
            SELECT a.*, u.full_name as patient_name, u.phone as patient_phone, u.email as patient_email
            FROM appointments a 
            LEFT JOIN users u ON a.user_id = u.id 
            WHERE a.doctor_id = ? 
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
        ");
        $stmt->execute([$doctor_id]);
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<script>alert('تم تحديث حالة الموعد بنجاح');</script>";
    } catch (PDOException $e) {
        echo "<script>alert('حدث خطأ أثناء تحديث الحالة');</script>";
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
                    <a href="/App-Demo/index.php" class="flex items-center">
                        <i class="fas fa-heartbeat text-blue-600 text-2xl ml-2"></i>
                        <span class="text-xl font-bold text-gray-800">Health Tech</span>
                    </a>
                </div>
                
                <div class="flex items-center space-x-4 space-x-reverse">
                    <span class="text-gray-600">
                        <i class="fas fa-user-md text-blue-600 ml-2"></i>
                        د. <?php echo htmlspecialchars($doctor_name); ?>
                    </span>
                    <a href="/App-Demo/profile.php" class="text-gray-600 hover:text-blue-600 transition-colors">
                        <i class="fas fa-user text-lg"></i>
                    </a>
                    <a href="/App-Demo/logout.php" class="text-gray-600 hover:text-red-600 transition-colors">
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
                    <a href="/App-Demo/doctor/index.php" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors">
                        <i class="fas fa-home ml-3"></i>
                        الرئيسية
                    </a>
                    
                    <a href="/App-Demo/doctor/appointments.php" class="flex items-center px-4 py-3 text-gray-700 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                        <i class="fas fa-calendar-check ml-3"></i>
                        المواعيد
                    </a>
                    
                    <a href="/App-Demo/doctor/profile.php" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors">
                        <i class="fas fa-user-circle ml-3"></i>
                        الملف الشخصي
                    </a>
                    
                    <a href="/App-Demo/doctor/availability.php" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors">
                        <i class="fas fa-clock ml-3"></i>
                        مواعيد العمل
                    </a>
                    
                    <a href="/App-Demo/search.php" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors">
                        <i class="fas fa-search ml-3"></i>
                        البحث عن أطباء
                    </a>
                    
                    <a href="/App-Demo/index.php" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors">
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
                <h1 class="text-3xl font-bold text-gray-900">المواعيد</h1>
                <p class="text-gray-600 mt-2">إدارة جميع مواعيدك مع المرضى</p>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <?php
                $today_count = 0;
                $upcoming_count = 0;
                $completed_count = 0;
                $cancelled_count = 0;
                
                foreach ($appointments as $apt) {
                    if ($apt['appointment_date'] == date('Y-m-d')) $today_count++;
                    if ($apt['appointment_date'] > date('Y-m-d') && $apt['status'] == 'confirmed') $upcoming_count++;
                    if ($apt['status'] == 'completed') $completed_count++;
                    if ($apt['status'] == 'cancelled') $cancelled_count++;
                }
                ?>
                
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">موعد اليوم</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $today_count; ?></p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i class="fas fa-calendar-day text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">قادم</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $upcoming_count; ?></p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-calendar-plus text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">مكتمل</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $completed_count; ?></p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full">
                            <i class="fas fa-check-circle text-purple-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-red-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">ملغي</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $cancelled_count; ?></p>
                        </div>
                        <div class="bg-red-100 p-3 rounded-full">
                            <i class="fas fa-times-circle text-red-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Appointments Table -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-900">جميع المواعيد</h2>
                    <span class="text-gray-500">
                        <?php echo count($appointments); ?> موعد
                    </span>
                </div>
                
                <?php if (!empty($appointments)): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b bg-gray-50">
                                    <th class="text-right py-3 px-4 font-semibold">المريض</th>
                                    <th class="text-right py-3 px-4 font-semibold">التاريخ</th>
                                    <th class="text-right py-3 px-4 font-semibold">الوقت</th>
                                    <th class="text-right py-3 px-4 font-semibold">الحالة</th>
                                    <th class="text-right py-3 px-4 font-semibold">ملاحظات</th>
                                    <th class="text-right py-3 px-4 font-semibold">إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $appointment): ?>
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="py-4 px-4">
                                            <div>
                                                <p class="font-semibold"><?php echo htmlspecialchars($appointment['patient_name'] ?? 'غير محدد'); ?></p>
                                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($appointment['patient_email'] ?? ''); ?></p>
                                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($appointment['patient_phone'] ?? ''); ?></p>
                                            </div>
                                        </td>
                                        <td class="py-4 px-4">
                                            <span class="font-medium"><?php echo $appointment['appointment_date']; ?></span>
                                        </td>
                                        <td class="py-4 px-4">
                                            <span class="font-medium"><?php echo $appointment['appointment_time']; ?></span>
                                        </td>
                                        <td class="py-4 px-4">
                                            <?php
                                            $status_class = '';
                                            $status_text = '';
                                            switch($appointment['status']) {
                                                case 'confirmed':
                                                    $status_class = 'bg-green-100 text-green-800';
                                                    $status_text = 'مؤكد';
                                                    break;
                                                case 'cancelled':
                                                    $status_class = 'bg-red-100 text-red-800';
                                                    $status_text = 'ملغي';
                                                    break;
                                                case 'completed':
                                                    $status_class = 'bg-blue-100 text-blue-800';
                                                    $status_text = 'تم';
                                                    break;
                                                default:
                                                    $status_class = 'bg-gray-100 text-gray-800';
                                                    $status_text = 'غير محدد';
                                            }
                                            ?>
                                            <span class="px-3 py-1 text-xs rounded-full <?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-4">
                                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($appointment['notes'] ?? 'لا توجد ملاحظات'); ?></p>
                                        </td>
                                        <td class="py-4 px-4">
                                            <form method="POST" action="" class="inline">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                <select name="status" class="text-sm p-1 border rounded" onchange="this.form.submit()">
                                                    <option value="confirmed" <?php echo $appointment['status'] == 'confirmed' ? 'selected' : ''; ?>>مؤكد</option>
                                                    <option value="completed" <?php echo $appointment['status'] == 'completed' ? 'selected' : ''; ?>>تم</option>
                                                    <option value="cancelled" <?php echo $appointment['status'] == 'cancelled' ? 'selected' : ''; ?>>ملغي</option>
                                                </select>
                                                <input type="hidden" name="update_status" value="1">
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12 text-gray-500">
                        <i class="fas fa-calendar-times text-5xl mb-4"></i>
                        <h3 class="text-xl font-semibold mb-2">لا توجد مواعيد حالياً</h3>
                        <p>لم يتم حجز أي مواعيد معك حتى الآن</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
                <a href="/App-Demo/doctor/availability.php" class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow text-center">
                    <i class="fas fa-clock text-blue-600 text-3xl mb-3"></i>
                    <h3 class="font-semibold text-gray-900">إدارة مواعيد العمل</h3>
                    <p class="text-gray-600 text-sm mt-2">حدد أوقات توافرك للمرضى</p>
                </a>
                
                <a href="/App-Demo/doctor/index.php" class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow text-center">
                    <i class="fas fa-arrow-left text-blue-600 text-3xl mb-3"></i>
                    <h3 class="font-semibold text-gray-900">العودة للرئيسية</h3>
                    <p class="text-gray-600 text-sm mt-2">لوحة تحكم الدكتور</p>
                </a>
            </div>
        </main>
    </div>

</body>
</html>
