<?php
require_once 'config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// التحقق من تسجيل الدخول
if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}

// Get appointment ID from URL
$appointment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($appointment_id === 0) {
    header("Location: patient/appointments.php");
    exit();
}

// Get appointment details
$appointment = get_appointment_details($conn, $appointment_id);

if (!$appointment) {
    $_SESSION['error_message'] = 'الموعد غير موجود';
    header("Location: patient/appointments.php");
    exit();
}

// Check if the appointment belongs to the current user
$user = get_logged_in_user();
if ($appointment['user_id'] != $user['id']) {
    $_SESSION['error_message'] = 'غير مصرح لك بالوصول إلى هذا الموعد';
    header("Location: patient/appointments.php");
    exit();
}

// Handle appointment cancellation
if (isset($_POST['cancel_appointment'])) {
    if (cancel_appointment($appointment_id, $user['id'])) {
        $_SESSION['success_message'] = 'تم إلغاء الموعد بنجاح';
        header("Location: patient/appointments.php?success=cancelled");
        exit();
    } else {
        $error_message = 'حدث خطأ أثناء إلغاء الموعد';
    }
}

// Handle reschedule request
if (isset($_POST['reschedule_appointment'])) {
    header("Location: reschedule.php?id=" . $appointment_id);
    exit();
}

// Get doctor avatar URL
$doctor_avatar = 'https://ui-avatars.com/api/?name=' . urlencode($appointment['doctor_name']) . '&size=200&background=0EA5E9&color=fff&bold=true';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل الموعد - <?php echo htmlspecialchars($appointment['doctor_name']); ?></title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts (Cairo) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Cairo', sans-serif;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .status-confirmed {
            background: linear-gradient(135deg, #10B981, #34D399);
            color: white;
        }

        .status-pending {
            background: linear-gradient(135deg, #F59E0B, #FBBF24);
            color: white;
        }

        .status-cancelled {
            background: linear-gradient(135deg, #EF4444, #F87171);
            color: white;
        }

        .status-completed {
            background: linear-gradient(135deg, #0EA5E9, #60A5FA);
            color: white;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

    <?php include 'includes/header.php'; ?>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8 max-w-6xl">

        <!-- Back Button -->
        <a href="patient/appointments.php" class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-700 mb-6 transition-colors">
            <i class="fas fa-arrow-right"></i>
            <span class="font-semibold">العودة إلى مواعيدي</span>
        </a>

        <!-- Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="bg-green-100 border-r-4 border-green-500 text-green-700 px-4 py-3 rounded mb-6 flex items-center gap-2">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border-r-4 border-red-500 text-red-700 px-4 py-3 rounded mb-6 flex items-center gap-2">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <!-- Doctor Header Card -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden mb-6 border-t-4 border-blue-500">
            <div class="p-6 md:p-8">
                <div class="flex flex-col md:flex-row items-center md:items-start gap-6 mb-6">
                    <!-- Doctor Avatar -->
                    <div class="relative">
                        <img src="<?php echo htmlspecialchars($doctor_avatar); ?>"
                             alt="<?php echo htmlspecialchars($appointment['doctor_name']); ?>"
                             class="w-32 h-32 rounded-full object-cover border-4 border-blue-100 shadow-lg">
                        <div class="absolute -bottom-2 -right-2 bg-green-500 text-white rounded-full p-2 shadow-lg">
                            <i class="fas fa-check text-sm"></i>
                        </div>
                    </div>

                    <!-- Doctor Info -->
                    <div class="flex-1 text-center md:text-right">
                        <h1 class="text-3xl font-bold text-gray-800 mb-2">
                            <?php echo htmlspecialchars($appointment['doctor_name']); ?>
                        </h1>
                        <p class="text-lg text-gray-600 mb-3">
                            <i class="fas fa-stethoscope text-blue-500 ml-2"></i>
                            <?php echo htmlspecialchars($appointment['specialty_name'] ?: 'طبيب عام'); ?>
                        </p>
                        <div class="flex items-center justify-center md:justify-start gap-2 text-yellow-500">
                            <i class="fas fa-star"></i>
                            <span class="font-semibold text-gray-700">
                                <?php echo number_format($appointment['rating'] ?: 0, 1); ?>
                            </span>
                            <span class="text-gray-500 text-sm">
                                (<?php echo $appointment['total_ratings'] ?: '0'; ?> تقييم)
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Appointment Time & Status -->
                <div class="flex flex-col md:flex-row items-center justify-between gap-4 pt-6 border-t border-gray-200">
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-6 py-4 rounded-xl text-center shadow-lg">
                        <div class="text-2xl font-bold mb-1">
                            <?php echo format_date_arabic($appointment['appointment_date']); ?>
                        </div>
                        <div class="text-lg opacity-90">
                            <i class="far fa-clock ml-2"></i>
                            <?php echo format_time_arabic($appointment['appointment_time']); ?>
                        </div>
                    </div>

                    <div class="status-badge status-<?php echo $appointment['status']; ?>">
                        <i class="fas fa-circle text-xs"></i>
                        <?php echo get_status_arabic($appointment['status']); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Information Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

            <!-- Location Information -->
            <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition-shadow">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-map-marker-alt text-blue-500"></i>
                    معلومات المكان
                </h2>

                <div class="space-y-3">
                    <div class="flex items-start gap-3 p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                        <i class="fas fa-hospital text-blue-600 mt-1"></i>
                        <div>
                            <div class="text-sm text-gray-600 mb-1">المستشفى</div>
                            <div class="font-semibold text-gray-800">
                                <?php echo htmlspecialchars($appointment['hospital_name'] ?: 'غير محدد'); ?>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                        <i class="fas fa-stethoscope text-blue-600 mt-1"></i>
                        <div>
                            <div class="text-sm text-gray-600 mb-1">العيادة</div>
                            <div class="font-semibold text-gray-800">
                                <?php echo htmlspecialchars($appointment['clinic_name'] ?: 'غير محدد'); ?>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                        <i class="fas fa-phone text-blue-600 mt-1"></i>
                        <div>
                            <div class="text-sm text-gray-600 mb-1">رقم الهاتف</div>
                            <div class="font-semibold text-gray-800">
                                <?php echo htmlspecialchars($appointment['clinic_phone'] ?: 'غير متوفر'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Appointment Information -->
            <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition-shadow">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-info-circle text-green-500"></i>
                    معلومات الموعد
                </h2>

                <div class="space-y-3">
                    <div class="flex items-start gap-3 p-3 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                        <i class="fas fa-calendar-plus text-green-600 mt-1"></i>
                        <div>
                            <div class="text-sm text-gray-600 mb-1">تاريخ الحجز</div>
                            <div class="font-semibold text-gray-800">
                                <?php echo format_date_arabic($appointment['created_at']); ?>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 p-3 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                        <i class="fas fa-clock text-green-600 mt-1"></i>
                        <div>
                            <div class="text-sm text-gray-600 mb-1">نوع الموعد</div>
                            <div class="font-semibold text-gray-800">
                                <?php echo htmlspecialchars($appointment['appointment_type'] ?: 'استشارة عامة'); ?>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 p-3 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                        <i class="fas fa-money-bill-wave text-green-600 mt-1"></i>
                        <div>
                            <div class="text-sm text-gray-600 mb-1">رسوم الاستشارة</div>
                            <div class="font-semibold text-gray-800">
                                <?php echo number_format($appointment['consultation_fee'] ?: 0, 2); ?> جنيه
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes Section -->
        <?php if (!empty($appointment['notes'])): ?>
            <div class="bg-white rounded-xl shadow-md p-6 mb-6 border-r-4 border-green-500">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-notes-medical text-green-500"></i>
                    ملاحظات الموعد
                </h2>
                <div class="bg-green-50 rounded-lg p-4 text-gray-700 leading-relaxed">
                    <?php echo nl2br(htmlspecialchars($appointment['notes'])); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <?php if (can_cancel_appointment($appointment['id'], $user['id'])): ?>
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-tools text-purple-500"></i>
                    إجراءات الموعد
                </h2>
                <form method="POST" class="flex flex-col sm:flex-row gap-4">
                    <button type="submit" name="reschedule_appointment"
                            class="flex-1 bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-300 shadow-md hover:shadow-lg flex items-center justify-center gap-2">
                        <i class="fas fa-calendar-alt"></i>
                        إعادة جدولة الموعد
                    </button>
                    <button type="submit" name="cancel_appointment"
                            onclick="return confirm('هل أنت متأكد من إلغاء هذا الموعد؟')"
                            class="flex-1 bg-red-500 hover:bg-red-600 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-300 shadow-md hover:shadow-lg flex items-center justify-center gap-2">
                        <i class="fas fa-times"></i>
                        إلغاء الموعد
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Review Section -->
        <?php if ($appointment['status'] == 'completed'): ?>
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-star text-yellow-500"></i>
                    تقييم الموعد
                </h2>
                <p class="text-gray-600 mb-4">
                    شاركنا رأيك في تجربتك مع الطبيب
                </p>
                <a href="review_appointment.php?id=<?php echo $appointment['id']; ?>"
                   class="inline-flex items-center gap-2 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-300 shadow-md hover:shadow-lg">
                    <i class="fas fa-star"></i>
                    تقييم الموعد
                </a>
            </div>
        <?php endif; ?>

    </main>

    <!-- Alpine.js for mobile menu -->
    <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>
</body>
</html>
