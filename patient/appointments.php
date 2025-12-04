<?php
session_start();
require_once '../config.php';
require_once '../includes/functions.php';

// Ensure user is logged in as a patient
if (!is_logged_in() || $_SESSION['user_type'] !== 'patient') {
    header('Location: ../login.php?error=access_denied');
    exit();
}

$user_id = $_SESSION['user_id'];

// Initialize database connection for get_appointments_by_user_id
require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Make $conn available globally for get_appointments_by_user_id function
global $conn;
$appointments = get_appointments_by_user_id($user_id);

// Handle flash messages
$success_message = '';
$error_message = '';

if (isset($_SESSION['flash_message'])) {
    if ($_SESSION['flash_message']['type'] === 'success') {
        $success_message = $_SESSION['flash_message']['message'];
    } elseif ($_SESSION['flash_message']['type'] === 'error') {
        $error_message = $_SESSION['flash_message']['message'];
    }
    unset($_SESSION['flash_message']);
}

// Handle GET parameters
if (isset($_GET['success'])) {
    $success_message = 'تم إلغاء الموعد بنجاح';
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'cannot_cancel':
            $error_message = 'لا يمكن إلغاء هذا الموعد';
            break;
        case 'appointment_not_found':
            $error_message = 'الموعد غير موجود';
            break;
        case 'already_cancelled':
            $error_message = 'هذا الموعد ملغي بالفعل';
            break;
        case 'cannot_cancel_completed':
            $error_message = 'لا يمكن إلغاء موعد مكتمل';
            break;
        case 'database_error':
            $error_message = 'حدث خطأ في قاعدة البيانات';
            break;
        default:
            $error_message = 'حدث خطأ أثناء إلغاء الموعد';
    }
}

$pageTitle = 'مواعيــدي';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts (Cairo) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">

    <script>
        // Custom Tailwind Config
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        cairo: ['Cairo', 'sans-serif']
                    },
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-color: #f3f4f6;
        }
        
        /* Smooth fade in animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        .appointment-card {
            transition: all 0.3s ease;
        }
        
        .appointment-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body class="font-cairo flex flex-col min-h-screen">

    <?php require_once '../includes/header.php'; ?>

    <main class="flex-1 py-10 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">

            <!-- Page Header -->
            <div class="text-center mb-10 animate-fade-in">
                <h1 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-2">
                    <span class="bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600">
                        <?php echo htmlspecialchars($pageTitle); ?>
                    </span>
                </h1>
                <p class="text-gray-500 text-lg">تتبع وإدارة مواعيدك الطبية بكل سهولة</p>
            </div>

            <!-- Messages -->
            <?php if ($success_message): ?>
                <div class="animate-fade-in bg-green-50 border-l-4 border-green-500 p-4 mb-8 rounded-r-lg shadow-sm max-w-4xl mx-auto">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-500 text-xl"></i>
                        </div>
                        <div class="mr-3">
                            <p class="text-sm font-bold text-green-800"><?php echo htmlspecialchars($success_message); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="animate-fade-in bg-red-50 border-l-4 border-red-500 p-4 mb-8 rounded-r-lg shadow-sm max-w-4xl mx-auto">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                        </div>
                        <div class="mr-3">
                            <p class="text-sm font-bold text-red-800"><?php echo htmlspecialchars($error_message); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Appointments Content -->
            <?php if (empty($appointments)): ?>
                <div class="animate-fade-in bg-white rounded-3xl shadow-xl p-12 text-center max-w-2xl mx-auto border border-gray-100">
                    <div class="w-24 h-24 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-calendar-plus text-4xl text-blue-500"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">لا توجد مواعيد حالياً</h3>
                    <p class="text-gray-500 mb-8 leading-relaxed">لم تقم بحجز أي موعد طبي بعد. ابدأ رحلتك الصحية معنا واحجز موعدك الأول الآن.</p>
                    <a href="../search.php" class="inline-flex items-center justify-center px-8 py-4 border border-transparent text-base font-bold rounded-xl text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <i class="fas fa-search ml-2"></i>
                        ابحث عن طبيب
                    </a>
                </div>
            <?php else: ?>
                
                <!-- Desktop View (Table) -->
                <div class="hidden md:block bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100 animate-fade-in">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        الطبيب
                                    </th>
                                    <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        التفاصيل
                                    </th>
                                    <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        الموعد
                                    </th>
                                    <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        الحالة
                                    </th>
                                    <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        الإجراءات
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($appointments as $index => $appt): ?>
                                    <tr class="hover:bg-blue-50/30 transition-colors duration-200" style="animation-delay: <?php echo $index * 100; ?>ms">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-12 w-12 bg-gradient-to-br from-blue-100 to-indigo-100 rounded-full flex items-center justify-center text-blue-600 font-bold text-lg shadow-sm">
                                                    <?php echo mb_substr($appt['doctor_name'], 0, 1, 'UTF-8'); ?>
                                                </div>
                                                <div class="mr-4">
                                                    <div class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($appt['doctor_name']); ?></div>
                                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($appt['specialty_name']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 font-medium"><?php echo htmlspecialchars($appt['clinic_name']); ?></div>
                                            <div class="text-xs text-gray-500 flex items-center mt-1">
                                                <i class="fas fa-map-marker-alt ml-1 text-gray-400"></i>
                                                <?php echo htmlspecialchars($appt['clinic_address']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex flex-col">
                                                <span class="text-sm font-bold text-gray-800 flex items-center">
                                                    <i class="far fa-calendar ml-2 text-blue-500"></i>
                                                    <?php echo date('Y/m/d', strtotime($appt['appointment_date'])); ?>
                                                </span>
                                                <span class="text-xs text-gray-500 mt-1 flex items-center">
                                                    <i class="far fa-clock ml-2 text-blue-400"></i>
                                                    <?php echo date('h:i A', strtotime($appt['appointment_time'])); ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $status_config = [
                                                'confirmed' => ['bg-green-100', 'text-green-700', 'border-green-200', 'مؤكد', 'fa-check'],
                                                'completed' => ['bg-blue-100', 'text-blue-700', 'border-blue-200', 'مكتمل', 'fa-check-double'],
                                                'cancelled' => ['bg-red-100', 'text-red-700', 'border-red-200', 'ملغي', 'fa-times'],
                                                'pending' => ['bg-yellow-100', 'text-yellow-700', 'border-yellow-200', 'قيد الانتظار', 'fa-hourglass-half']
                                            ];
                                            $status = $appt['status'];
                                            $config = $status_config[$status] ?? $status_config['pending'];
                                            ?>
                                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full border <?php echo $config[0] . ' ' . $config[1] . ' ' . $config[2]; ?> items-center">
                                                <i class="fas <?php echo $config[4]; ?> ml-1.5"></i>
                                                <?php echo $config[3]; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <?php if ($appt['status'] === 'confirmed'): ?>
                                                <a href="../cancel_appointment.php?id=<?php echo $appt['id']; ?>" 
                                                   onclick="return confirm('هل أنت متأكد من رغبتك في إلغاء هذا الموعد؟');"
                                                   class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg transition-colors duration-200 flex items-center w-fit">
                                                    <i class="fas fa-times-circle ml-1.5"></i>
                                                    إلغاء الموعد
                                                </a>
                                            <?php elseif ($appt['status'] === 'completed'): ?>
                                                <a href="../rate_doctor.php?appointment_id=<?php echo $appt['id']; ?>" 
                                                   class="text-yellow-600 hover:text-yellow-900 bg-yellow-50 hover:bg-yellow-100 px-3 py-1.5 rounded-lg transition-colors duration-200 flex items-center w-fit">
                                                    <i class="fas fa-star ml-1.5"></i>
                                                    تقييم الطبيب
                                                </a>
                                            <?php else: ?>
                                                <span class="text-gray-400 cursor-not-allowed text-xs">غير متاح</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Mobile View (Cards) -->
                <div class="md:hidden space-y-4 animate-fade-in">
                    <?php foreach ($appointments as $index => $appt): ?>
                        <div class="bg-white rounded-2xl shadow-md p-5 border border-gray-100 appointment-card" style="animation-delay: <?php echo $index * 100; ?>ms">
                            <!-- Header: Doctor Info & Status -->
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-12 w-12 bg-gradient-to-br from-blue-100 to-indigo-100 rounded-full flex items-center justify-center text-blue-600 font-bold text-lg shadow-sm ml-3">
                                        <?php echo mb_substr($appt['doctor_name'], 0, 1, 'UTF-8'); ?>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($appt['doctor_name']); ?></h3>
                                        <p class="text-sm text-blue-600 font-medium"><?php echo htmlspecialchars($appt['specialty_name']); ?></p>
                                    </div>
                                </div>
                                <?php
                                $status_config = [
                                    'confirmed' => ['bg-green-100', 'text-green-700', 'مؤكد'],
                                    'completed' => ['bg-blue-100', 'text-blue-700', 'مكتمل'],
                                    'cancelled' => ['bg-red-100', 'text-red-700', 'ملغي'],
                                    'pending' => ['bg-yellow-100', 'text-yellow-700', 'انتظار']
                                ];
                                $status = $appt['status'];
                                $config = $status_config[$status] ?? $status_config['pending'];
                                ?>
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold <?php echo $config[0] . ' ' . $config[1]; ?>">
                                    <?php echo $config[2]; ?>
                                </span>
                            </div>

                            <!-- Details -->
                            <div class="space-y-3 mb-5 bg-gray-50 p-4 rounded-xl">
                                <div class="flex items-center text-gray-700">
                                    <div class="w-8 flex justify-center ml-2">
                                        <i class="far fa-calendar-alt text-blue-500"></i>
                                    </div>
                                    <span class="font-medium"><?php echo date('Y/m/d', strtotime($appt['appointment_date'])); ?></span>
                                </div>
                                <div class="flex items-center text-gray-700">
                                    <div class="w-8 flex justify-center ml-2">
                                        <i class="far fa-clock text-blue-500"></i>
                                    </div>
                                    <span class="font-medium"><?php echo date('h:i A', strtotime($appt['appointment_time'])); ?></span>
                                </div>
                                <div class="flex items-center text-gray-700">
                                    <div class="w-8 flex justify-center ml-2">
                                        <i class="fas fa-map-marker-alt text-blue-500"></i>
                                    </div>
                                    <span class="text-sm"><?php echo htmlspecialchars($appt['clinic_name']); ?> - <?php echo htmlspecialchars($appt['clinic_address']); ?></span>
                                </div>
                            </div>

                            <!-- Actions -->
                            <?php if ($appt['status'] === 'confirmed'): ?>
                                <a href="../cancel_appointment.php?id=<?php echo $appt['id']; ?>" 
                                   onclick="return confirm('هل أنت متأكد من رغبتك في إلغاء هذا الموعد؟');"
                                   class="block w-full text-center bg-white border border-red-200 text-red-600 font-bold py-3 rounded-xl hover:bg-red-50 transition-colors duration-200 shadow-sm">
                                    إلغاء الموعد
                                </a>
                            <?php elseif ($appt['status'] === 'completed'): ?>
                                <a href="../rate_doctor.php?appointment_id=<?php echo $appt['id']; ?>" 
                                   class="block w-full text-center bg-white border border-yellow-200 text-yellow-600 font-bold py-3 rounded-xl hover:bg-yellow-50 transition-colors duration-200 shadow-sm">
                                    <i class="fas fa-star ml-2"></i>
                                    تقييم الطبيب
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <?php require_once '../includes/footer.php'; ?>

</body>
</html>
