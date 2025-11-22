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
                    }
                }
            }
        }
    </script>
    <style>
        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            display: flex;
            flex-direction: column;
        }

        main {
            flex: 1;
            min-height: 0;
        }

        table {
            border-collapse: separate;
            border-spacing: 0;
        }

        thead th {
            position: sticky;
            top: 0;
            z-index: 10;
        }

        tbody tr {
            transition: all 0.2s ease;
        }

        tbody tr:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            background-color: #f9fafb;
        }
    </style>
</head>

<body class="font-cairo bg-gray-50 flex flex-col min-h-screen">

    <?php require_once '../includes/header.php'; ?>

    <main class="bg-gray-50 py-8 flex-1">
        <div class="container mx-auto px-4 h-full">

            <h1 class="text-3xl md:text-4xl font-bold text-gray-800 text-center mb-8">
                <i class="fas fa-calendar-check text-blue-600 ml-2"></i>
                <?php echo htmlspecialchars($pageTitle); ?>
            </h1>

            <!-- Messages -->
            <?php if ($success_message): ?>
                <div class="bg-green-100 border-r-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg max-w-5xl mx-auto">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle ml-2"></i>
                        <span><?php echo htmlspecialchars($success_message); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="bg-red-100 border-r-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg max-w-5xl mx-auto">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle ml-2"></i>
                        <span><?php echo htmlspecialchars($error_message); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Appointments Section -->
            <div class="bg-white p-6 md:p-8 rounded-2xl shadow-xl max-w-6xl mx-auto">

                <?php if (empty($appointments)): ?>
                    <div class="text-center py-20">
                        <i class="fas fa-calendar-times fa-5x text-gray-300 mb-6"></i>
                        <p class="text-gray-600 text-xl mb-2 font-semibold">ليس لديك أي مواعيد محجوزة حتى الآن.</p>
                        <p class="text-gray-500 text-sm mb-8">ابدأ بالبحث عن طبيب واحجز موعدك الأول</p>
                        <a href="../search.php"
                            class="inline-flex items-center gap-2 bg-blue-600 text-white font-bold py-3 px-8 rounded-lg hover:bg-blue-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                            <i class="fas fa-search"></i>
                            <span>ابحث عن طبيب واحجز الآن</span>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gradient-to-r from-blue-50 to-indigo-50">
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-4 text-right text-sm font-bold text-gray-700 uppercase tracking-wider">
                                        <i class="fas fa-user-md text-blue-600 ml-2"></i>
                                        الطبيب
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-4 text-right text-sm font-bold text-gray-700 uppercase tracking-wider">
                                        <i class="fas fa-calendar-alt text-blue-600 ml-2"></i>
                                        التاريخ والوقت
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-4 text-right text-sm font-bold text-gray-700 uppercase tracking-wider">
                                        <i class="fas fa-hospital text-blue-600 ml-2"></i>
                                        العيادة
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-4 text-right text-sm font-bold text-gray-700 uppercase tracking-wider">
                                        <i class="fas fa-info-circle text-blue-600 ml-2"></i>
                                        الحالة
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-4 text-right text-sm font-bold text-gray-700 uppercase tracking-wider">
                                        <i class="fas fa-cog text-blue-600 ml-2"></i>
                                        إجراءات
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($appointments as $appt): ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="px-6 py-5 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div
                                                    class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center ml-3">
                                                    <i class="fas fa-user-md text-blue-600"></i>
                                                </div>
                                                <div>
                                                    <div class="text-sm font-bold text-gray-900">
                                                        <?php echo htmlspecialchars($appt['doctor_name']); ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500 flex items-center mt-1">
                                                        <i class="fas fa-stethoscope text-xs ml-1"></i>
                                                        <?php echo htmlspecialchars($appt['specialty_name']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 whitespace-nowrap">
                                            <div class="flex items-center text-sm">
                                                <i class="fas fa-calendar text-gray-400 ml-2"></i>
                                                <div>
                                                    <div class="font-semibold text-gray-900">
                                                        <?php echo date('d M, Y', strtotime($appt['appointment_date'])); ?>
                                                    </div>
                                                    <div class="text-gray-500 flex items-center mt-1">
                                                        <i class="fas fa-clock text-xs ml-1"></i>
                                                        <?php echo date('h:i A', strtotime($appt['appointment_time'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 whitespace-nowrap">
                                            <div class="flex items-start text-sm">
                                                <i class="fas fa-hospital text-gray-400 ml-2 mt-1"></i>
                                                <div>
                                                    <div class="font-semibold text-gray-900">
                                                        <?php echo htmlspecialchars($appt['clinic_name']); ?>
                                                    </div>
                                                    <div class="text-gray-500 text-xs mt-1 flex items-center">
                                                        <i class="fas fa-map-marker-alt text-xs ml-1"></i>
                                                        <?php echo htmlspecialchars($appt['clinic_address']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 whitespace-nowrap">
                                            <?php
                                            $status_config = [
                                                'confirmed' => ['bg-green-100', 'text-green-800', 'border-green-200', 'fa-check-circle', 'مؤكد'],
                                                'completed' => ['bg-blue-100', 'text-blue-800', 'border-blue-200', 'fa-check-double', 'مكتمل'],
                                                'cancelled' => ['bg-red-100', 'text-red-800', 'border-red-200', 'fa-times-circle', 'ملغي'],
                                                'pending' => ['bg-yellow-100', 'text-yellow-800', 'border-yellow-200', 'fa-clock', 'قيد الانتظار']
                                            ];
                                            $status = $appt['status'];
                                            $config = $status_config[$status] ?? $status_config['pending'];
                                            ?>
                                            <span
                                                class="px-3 py-1.5 inline-flex items-center text-xs font-bold leading-tight rounded-full <?php echo $config[0] . ' ' . $config[1]; ?> border <?php echo $config[2]; ?>">
                                                <i class="fas <?php echo $config[3]; ?> ml-1"></i>
                                                <?php echo $config[4]; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-5 whitespace-nowrap text-sm font-medium">
                                            <?php if ($appt['status'] === 'confirmed'): ?>
                                                <a href="../cancel_appointment.php?id=<?php echo $appt['id']; ?>"
                                                    class="inline-flex items-center gap-2 px-4 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 hover:text-red-700 transition-all duration-200 font-semibold border border-red-200"
                                                    onclick="return confirm('هل أنت متأكد من رغبتك في إلغاء هذا الموعد؟');">
                                                    <i class="fas fa-times"></i>
                                                    <span>إلغاء</span>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-gray-400 text-xs">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <div class="mt-8">
    <?php require_once '../includes/footer.php'; ?>
</div>

</body>

</html>

