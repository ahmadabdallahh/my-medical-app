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

$user = get_logged_in_user();
$appointments = get_user_appointments($conn, $user['id']);

// تصنيف المواعيد
$upcoming_appointments = array_filter($appointments, function($app) {
    return $app['status'] == 'confirmed' && $app['appointment_date'] >= date('Y-m-d');
});

$pending_appointments = array_filter($appointments, function($app) {
    return $app['status'] == 'pending';
});

$past_appointments = array_filter($appointments, function($app) {
    return $app['appointment_date'] < date('Y-m-d');
});

$cancelled_appointments = array_filter($appointments, function($app) {
    return $app['status'] == 'cancelled';
});

// معالجة إلغاء الموعد
if (isset($_POST['cancel_appointment'])) {
    $appointment_id = (int)$_POST['appointment_id'];
    if (cancel_appointment($appointment_id, $user['id'])) {
        header("Location: appointments.php?success=cancelled");
        exit();
    } else {
        header("Location: appointments.php?error=cancel_failed");
        exit();
    }
}

$success_message = '';
$error_message = '';

if (isset($_GET['success'])) {
    if ($_GET['success'] == 'cancelled') {
        $success_message = 'تم إلغاء الموعد بنجاح';
    }
}

if (isset($_GET['error'])) {
    if ($_GET['error'] == 'cancel_failed') {
        $error_message = 'حدث خطأ أثناء إلغاء الموعد';
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مواعيدي - موقع حجز المواعيد الطبية</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts (Cairo) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">

    <script>
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
</head>
<body class="font-cairo bg-gray-50">

<?php require_once 'includes/header.php'; ?>

<main class="bg-gray-50 py-12">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-800 text-center mb-6">مواعيدي الطبية</h1>
        <p class="text-center text-gray-600 mb-8">إدارة جميع مواعيدك الطبية في مكان واحد</p>

        <!-- Search Bar -->
        <div class="max-w-2xl mx-auto mb-8">
            <div class="relative">
                <input type="text" id="searchInput" placeholder="ابحث عن طبيب أو مستشفى أو عيادة..."
                       class="w-full bg-white border-2 border-gray-200 rounded-full py-3 px-6 pr-12 focus:outline-none focus:border-blue-500 transition">
                <i class="fas fa-search absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <button type="button" id="clearSearch" class="hidden absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($success_message): ?>
            <div class="bg-green-100 border-r-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-check-circle ml-2"></i>
                    <span><?php echo $success_message; ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="bg-red-100 border-r-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle ml-2"></i>
                    <span><?php echo $error_message; ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-lg text-center">
                <div class="text-4xl font-bold text-blue-600 mb-2"><?php echo count($upcoming_appointments); ?></div>
                <div class="text-gray-600 font-semibold">مواعيد قادمة</div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-lg text-center">
                <div class="text-4xl font-bold text-yellow-600 mb-2"><?php echo count($pending_appointments); ?></div>
                <div class="text-gray-600 font-semibold">في الانتظار</div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-lg text-center">
                <div class="text-4xl font-bold text-gray-600 mb-2"><?php echo count($past_appointments); ?></div>
                <div class="text-gray-600 font-semibold">مواعيد سابقة</div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-lg text-center">
                <div class="text-4xl font-bold text-red-600 mb-2"><?php echo count($cancelled_appointments); ?></div>
                <div class="text-gray-600 font-semibold">ملغية</div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="flex flex-wrap border-b border-gray-200">
                <button onclick="showTab('upcoming')" class="tab-btn flex-1 px-6 py-4 text-center font-semibold border-b-2 border-blue-600 text-blue-600 bg-blue-50" id="tab-btn-upcoming">
                    <i class="fas fa-calendar-check ml-2"></i>
                    قادمة (<?php echo count($upcoming_appointments); ?>)
                </button>
                <button onclick="showTab('pending')" class="tab-btn flex-1 px-6 py-4 text-center font-semibold text-gray-600 hover:bg-gray-50" id="tab-btn-pending">
                    <i class="fas fa-clock ml-2"></i>
                    في الانتظار (<?php echo count($pending_appointments); ?>)
                </button>
                <button onclick="showTab('past')" class="tab-btn flex-1 px-6 py-4 text-center font-semibold text-gray-600 hover:bg-gray-50" id="tab-btn-past">
                    <i class="fas fa-history ml-2"></i>
                    سابقة (<?php echo count($past_appointments); ?>)
                </button>
                <button onclick="showTab('cancelled')" class="tab-btn flex-1 px-6 py-4 text-center font-semibold text-gray-600 hover:bg-gray-50" id="tab-btn-cancelled">
                    <i class="fas fa-times-circle ml-2"></i>
                    ملغية (<?php echo count($cancelled_appointments); ?>)
                </button>
            </div>

            <div class="p-6">
                <!-- Upcoming Appointments -->
                <div id="tab-upcoming" class="tab-content">
                    <?php if (empty($upcoming_appointments)): ?>
                        <div class="text-center py-16">
                            <i class="fas fa-calendar-plus fa-4x text-gray-300 mb-4"></i>
                            <h3 class="text-xl font-bold text-gray-800 mb-2">لا توجد مواعيد قادمة</h3>
                            <p class="text-gray-500 mb-6">احجز موعدك الأول مع أفضل الأطباء</p>
                            <a href="search.php" class="inline-block bg-blue-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-blue-700 transition">
                                <i class="fas fa-search ml-2"></i>
                                البحث عن طبيب
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($upcoming_appointments as $appointment): ?>
                                <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 hover:shadow-lg transition">
                                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
                                        <div class="flex items-center gap-4">
                                            <div class="bg-blue-600 text-white px-4 py-3 rounded-lg text-center min-w-[120px]">
                                                <div class="font-bold text-lg"><?php echo format_date_arabic($appointment['appointment_date']); ?></div>
                                                <div class="text-sm opacity-90"><?php echo format_time_arabic($appointment['appointment_time']); ?></div>
                                            </div>
                                            <div>
                                                <h3 class="text-xl font-bold text-gray-800 mb-1"><?php echo $appointment['doctor_name']; ?></h3>
                                                <p class="text-gray-600 text-sm"><?php echo $appointment['specialty_name'] ?? 'طبيب عام'; ?></p>
                                            </div>
                                        </div>
                                        <span class="px-4 py-2 rounded-full text-sm font-semibold bg-green-100 text-green-800">
                                            <?php echo get_status_arabic($appointment['status']); ?>
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                        <div class="flex items-center gap-2 text-gray-600">
                                            <i class="fas fa-hospital text-blue-600"></i>
                                            <span class="text-sm"><?php echo $appointment['hospital_name']; ?></span>
                                        </div>
                                        <div class="flex items-center gap-2 text-gray-600">
                                            <i class="fas fa-stethoscope text-blue-600"></i>
                                            <span class="text-sm"><?php echo $appointment['clinic_name']; ?></span>
                                        </div>
                                    </div>

                                    <?php if (!empty($appointment['notes'])): ?>
                                        <div class="bg-blue-50 border-r-4 border-blue-500 p-3 rounded-lg mb-4">
                                            <p class="text-sm text-gray-700"><strong>ملاحظات:</strong> <?php echo $appointment['notes']; ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <div class="flex flex-wrap gap-3">
                                        <a href="appointment-details.php?id=<?php echo $appointment['id']; ?>" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm font-semibold">
                                            <i class="fas fa-eye ml-2"></i>
                                            عرض التفاصيل
                                        </a>
                                        <?php if (can_cancel_appointment($appointment['id'], $user['id'])): ?>
                                            <a href="reschedule.php?id=<?php echo $appointment['id']; ?>" class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition text-sm font-semibold">
                                                <i class="fas fa-calendar-alt ml-2"></i>
                                                إعادة جدولة
                                            </a>
                                            <form method="POST" class="inline" onsubmit="return confirm('هل أنت متأكد من إلغاء هذا الموعد؟')">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                <button type="submit" name="cancel_appointment" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition text-sm font-semibold">
                                                    <i class="fas fa-times ml-2"></i>
                                                    إلغاء الموعد
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Pending Appointments -->
                <div id="tab-pending" class="tab-content hidden">
                    <?php if (empty($pending_appointments)): ?>
                        <div class="text-center py-16">
                            <i class="fas fa-clock fa-4x text-gray-300 mb-4"></i>
                            <h3 class="text-xl font-bold text-gray-800 mb-2">لا توجد مواعيد في الانتظار</h3>
                            <p class="text-gray-500">جميع مواعيدك مؤكدة أو مكتملة</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($pending_appointments as $appointment): ?>
                                <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 hover:shadow-lg transition">
                                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
                                        <div class="flex items-center gap-4">
                                            <div class="bg-blue-600 text-white px-4 py-3 rounded-lg text-center min-w-[120px]">
                                                <div class="font-bold text-lg"><?php echo format_date_arabic($appointment['appointment_date']); ?></div>
                                                <div class="text-sm opacity-90"><?php echo format_time_arabic($appointment['appointment_time']); ?></div>
                                            </div>
                                            <div>
                                                <h3 class="text-xl font-bold text-gray-800 mb-1"><?php echo $appointment['doctor_name']; ?></h3>
                                                <p class="text-gray-600 text-sm"><?php echo $appointment['specialty_name'] ?? 'طبيب عام'; ?></p>
                                            </div>
                                        </div>
                                        <span class="px-4 py-2 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-800">
                                            <?php echo get_status_arabic($appointment['status']); ?>
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                        <div class="flex items-center gap-2 text-gray-600">
                                            <i class="fas fa-hospital text-blue-600"></i>
                                            <span class="text-sm"><?php echo $appointment['hospital_name']; ?></span>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap gap-3">
                                        <a href="appointment-details.php?id=<?php echo $appointment['id']; ?>" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm font-semibold">
                                            <i class="fas fa-eye ml-2"></i>
                                            عرض التفاصيل
                                        </a>
                                        <?php if (can_cancel_appointment($appointment['id'], $user['id'])): ?>
                                            <a href="reschedule.php?id=<?php echo $appointment['id']; ?>" class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition text-sm font-semibold">
                                                <i class="fas fa-calendar-alt ml-2"></i>
                                                إعادة جدولة
                                            </a>
                                            <form method="POST" class="inline" onsubmit="return confirm('هل أنت متأكد من إلغاء هذا الموعد؟')">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                <button type="submit" name="cancel_appointment" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition text-sm font-semibold">
                                                    <i class="fas fa-times ml-2"></i>
                                                    إلغاء الموعد
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Past Appointments -->
                <div id="tab-past" class="tab-content hidden">
                    <?php if (empty($past_appointments)): ?>
                        <div class="text-center py-16">
                            <i class="fas fa-history fa-4x text-gray-300 mb-4"></i>
                            <h3 class="text-xl font-bold text-gray-800 mb-2">لا توجد مواعيد سابقة</h3>
                            <p class="text-gray-500">ستظهر هنا جميع مواعيدك المكتملة</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach (array_slice($past_appointments, 0, 10) as $appointment): ?>
                                <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 hover:shadow-lg transition">
                                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
                                        <div class="flex items-center gap-4">
                                            <div class="bg-gray-600 text-white px-4 py-3 rounded-lg text-center min-w-[120px]">
                                                <div class="font-bold text-lg"><?php echo format_date_arabic($appointment['appointment_date']); ?></div>
                                                <div class="text-sm opacity-90"><?php echo format_time_arabic($appointment['appointment_time']); ?></div>
                                            </div>
                                            <div>
                                                <h3 class="text-xl font-bold text-gray-800 mb-1"><?php echo $appointment['doctor_name']; ?></h3>
                                                <p class="text-gray-600 text-sm"><?php echo $appointment['specialty_name'] ?? 'طبيب عام'; ?></p>
                                            </div>
                                        </div>
                                        <span class="px-4 py-2 rounded-full text-sm font-semibold bg-blue-100 text-blue-800">
                                            <?php echo get_status_arabic($appointment['status']); ?>
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                        <div class="flex items-center gap-2 text-gray-600">
                                            <i class="fas fa-hospital text-blue-600"></i>
                                            <span class="text-sm"><?php echo $appointment['hospital_name']; ?></span>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap gap-3">
                                        <a href="appointment-details.php?id=<?php echo $appointment['id']; ?>" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm font-semibold">
                                            <i class="fas fa-eye ml-2"></i>
                                            عرض التفاصيل
                                        </a>
                                        <?php if ($appointment['status'] == 'completed'): ?>
                                            <a href="review_appointment.php?id=<?php echo $appointment['id']; ?>" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition text-sm font-semibold">
                                                <i class="fas fa-star ml-2"></i>
                                                تقييم الموعد
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Cancelled Appointments -->
                <div id="tab-cancelled" class="tab-content hidden">
                    <?php if (empty($cancelled_appointments)): ?>
                        <div class="text-center py-16">
                            <i class="fas fa-times-circle fa-4x text-gray-300 mb-4"></i>
                            <h3 class="text-xl font-bold text-gray-800 mb-2">لا توجد مواعيد ملغية</h3>
                            <p class="text-gray-500">لم تقم بإلغاء أي مواعيد بعد</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach (array_slice($cancelled_appointments, 0, 10) as $appointment): ?>
                                <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 hover:shadow-lg transition">
                                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
                                        <div class="flex items-center gap-4">
                                            <div class="bg-red-600 text-white px-4 py-3 rounded-lg text-center min-w-[120px]">
                                                <div class="font-bold text-lg"><?php echo format_date_arabic($appointment['appointment_date']); ?></div>
                                                <div class="text-sm opacity-90"><?php echo format_time_arabic($appointment['appointment_time']); ?></div>
                                            </div>
                                            <div>
                                                <h3 class="text-xl font-bold text-gray-800 mb-1"><?php echo $appointment['doctor_name']; ?></h3>
                                                <p class="text-gray-600 text-sm"><?php echo $appointment['specialty_name'] ?? 'طبيب عام'; ?></p>
                                            </div>
                                        </div>
                                        <span class="px-4 py-2 rounded-full text-sm font-semibold bg-red-100 text-red-800">
                                            <?php echo get_status_arabic($appointment['status']); ?>
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                        <div class="flex items-center gap-2 text-gray-600">
                                            <i class="fas fa-hospital text-blue-600"></i>
                                            <span class="text-sm"><?php echo $appointment['hospital_name']; ?></span>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap gap-3">
                                        <a href="appointment-details.php?id=<?php echo $appointment['id']; ?>" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm font-semibold">
                                            <i class="fas fa-eye ml-2"></i>
                                            عرض التفاصيل
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Floating Action Button -->
<a href="search.php" class="fixed bottom-8 left-8 w-16 h-16 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 transition flex items-center justify-center text-2xl z-50" title="حجز موعد جديد">
    <i class="fas fa-plus"></i>
</a>

<?php require_once 'includes/footer.php'; ?>

<script>
    function showTab(tabName) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });

        // Remove active class from all buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('border-b-2', 'border-blue-600', 'text-blue-600', 'bg-blue-50');
            btn.classList.add('text-gray-600');
        });

        // Show selected tab content
        document.getElementById('tab-' + tabName).classList.remove('hidden');

        // Add active class to selected button
        const activeBtn = document.getElementById('tab-btn-' + tabName);
        activeBtn.classList.remove('text-gray-600');
        activeBtn.classList.add('border-b-2', 'border-blue-600', 'text-blue-600', 'bg-blue-50');
    }

    // Search functionality
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const clearButton = document.getElementById('clearSearch');

        if (searchInput && clearButton) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                const appointmentCards = document.querySelectorAll('.tab-content:not(.hidden) .bg-gray-50.border');

                if (searchTerm) {
                    clearButton.classList.remove('hidden');
                } else {
                    clearButton.classList.add('hidden');
                }

                appointmentCards.forEach(card => {
                    const text = card.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });

            clearButton.addEventListener('click', function() {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input'));
                searchInput.focus();
            });
        }
    });
</script>

</body>
</html>
