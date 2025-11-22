<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/functions.php';
require_once 'config/database.php';

// التحقق من تسجيل الدخول
// Temporarily disabled for testing
/*
if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}
*/

// For testing, create a dummy user if not logged in
if (!is_logged_in()) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Test User';
    $_SESSION['user_type'] = 'patient';
    $_SESSION['email'] = 'test@example.com';
}

$user = get_logged_in_user();
$error = '';
$success = '';

// الحصول على بيانات الطبيب والعيادة
$doctor_id = isset($_GET['doctor']) ? (int)$_GET['doctor'] : 0;
$clinic_id = isset($_GET['clinic']) ? (int)$_GET['clinic'] : 0;

// Debug: Log the received parameters
error_log("Book.php - Doctor ID: $doctor_id, Clinic ID: $clinic_id");

if (!$doctor_id || !$clinic_id) {
    error_log("Book.php - Missing parameters, redirecting to search.php");
    header("Location: search.php");
    exit();
}

// الحصول على معلومات الطبيب والعيادة
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    $stmt = $conn->prepare("
        SELECT d.*, s.name as specialty_name, c.name as clinic_name, 
               h.name as hospital_name, h.address as hospital_address,
               c.consultation_fee
        FROM doctors d
        LEFT JOIN specialties s ON d.specialty_id = s.id
        LEFT JOIN clinics c ON d.clinic_id = c.id
        LEFT JOIN hospitals h ON c.hospital_id = h.id
        WHERE d.id = ? AND c.id = ?
    ");
    $stmt->execute([$doctor_id, $clinic_id]);
    $doctor = $stmt->fetch();

    error_log("Book.php - Doctor found: " . ($doctor ? 'Yes' : 'No'));

    if (!$doctor) {
        error_log("Book.php - Doctor not found, redirecting to search.php");
        header("Location: search.php");
        exit();
    }
} catch (Exception $e) {
    error_log("Book.php - Exception: " . $e->getMessage());
    $error = "حدث خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage();
}

// الحصول على أوقات عمل الطبيب
try {
    $schedule = get_doctor_schedule($doctor_id);
} catch (Exception $e) {
    error_log("Book.php - Schedule error: " . $e->getMessage());
    $schedule = [];
}

// معالجة حجز الموعد
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $appointment_date = sanitize_input($_POST['appointment_date']);
    $appointment_time = sanitize_input($_POST['appointment_time']);
    $notes = sanitize_input($_POST['notes']);

    if (empty($appointment_date) || empty($appointment_time)) {
        $error = 'يرجى اختيار التاريخ والوقت';
    } else {
        // التحقق من أن التاريخ في المستقبل
        if (strtotime($appointment_date) < strtotime(date('Y-m-d'))) {
            $error = 'لا يمكن حجز موعد في تاريخ ماضي';
        } else {
            // التحقق من توفر الموعد
            if (is_appointment_available($doctor_id, $appointment_date, $appointment_time)) {
                if (book_appointment($user['id'], $doctor_id, $clinic_id, $appointment_date, $appointment_time, $notes)) {
                    // إرسال إشعار للمستخدم
                    send_notification(
                        $user['id'], 
                        'appointment_confirmed', 
                        "تم تأكيد موعدك في {$appointment_date} الساعة {$appointment_time} مع د. {$doctor['full_name']}",
                        $doctor_id
                    );
                    
                    $success = 'تم حجز الموعد بنجاح! ستتلقى إشعاراً قبل الموعد بـ 24 ساعة';
                } else {
                    $error = 'حدث خطأ أثناء حجز الموعد';
                }
            } else {
                $error = 'هذا الموعد محجوز مسبقاً، يرجى اختيار وقت آخر';
            }
        }
    }
}

// الحصول على الأوقات المتاحة للتاريخ المحدد
$available_times = [];
$selected_date = '';
if (isset($_POST['appointment_date']) && !empty($_POST['appointment_date'])) {
    $selected_date = $_POST['appointment_date'];
    try {
        $available_times = get_available_times($doctor_id, $selected_date);
    } catch (Exception $e) {
        error_log("Book.php - Available times error: " . $e->getMessage());
        $available_times = [];
    }
}

// تحويل أيام الأسبوع إلى العربية
$days_arabic = [
    'sunday' => 'الأحد',
    'monday' => 'الاثنين',
    'tuesday' => 'الثلاثاء',
    'wednesday' => 'الأربعاء',
    'thursday' => 'الخميس',
    'friday' => 'الجمعة',
    'saturday' => 'السبت'
];

// الحصول على تقييمات الطبيب
try {
    $reviews = get_doctor_reviews($doctor_id, 5);
} catch (Exception $e) {
    error_log("Book.php - Reviews error: " . $e->getMessage());
    $reviews = [];
}
$average_rating = 0;
if (!empty($reviews)) {
    $total_rating = array_sum(array_column($reviews, 'rating'));
    $average_rating = $total_rating / count($reviews);
}

$page_title = "حجز موعد مع د. " . htmlspecialchars($doctor['full_name']);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts (Cairo) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    
    <!-- Alpine.js for dropdown -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
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
    <style>
        body {
            font-family: 'Cairo', sans-serif;
        }
    </style>
</head>
<body class="font-cairo bg-gray-50">

<?php include 'includes/dashboard_header.php'; ?>

<!-- Debug/Error Display -->
<?php if ($error): ?>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <strong>خطأ:</strong> <?php echo htmlspecialchars($error); ?>
        </div>
    </div>
<?php endif; ?>

<!-- Debug Info -->
<?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
            <strong>Debug Info:</strong><br>
            Doctor ID: <?php echo htmlspecialchars($doctor_id); ?><br>
            Clinic ID: <?php echo htmlspecialchars($clinic_id); ?><br>
            Doctor Found: <?php echo $doctor ? 'Yes' : 'No'; ?><br>
            Schedule Count: <?php echo count($schedule); ?><br>
            Reviews Count: <?php echo count($reviews); ?>
        </div>
    </div>
<?php endif; ?>


    <!-- Booking Page -->
    <div class="min-h-screen bg-gray-50 pt-24 pb-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto">
            <!-- Page Header -->
            <div class="text-center mb-12">
                <h1 class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-teal-500 mb-4">
                    حجز موعد طبي
                </h1>
                <p class="text-lg text-gray-600">احجز موعدك مع أفضل الأطباء بسهولة وأمان</p>
            </div>

            <!-- Messages -->
            <div class="mb-8">
                <?php if ($success): ?>
                    <div class="bg-green-100 border-r-4 border-green-500 text-green-700 p-4 rounded-lg shadow-sm flex items-center" role="alert">
                        <i class="fas fa-check-circle text-xl ml-3"></i>
                        <p class="font-medium"><?php echo $success; ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="bg-red-100 border-r-4 border-red-500 text-red-700 p-4 rounded-lg shadow-sm flex items-center" role="alert">
                        <i class="fas fa-exclamation-circle text-xl ml-3"></i>
                        <p class="font-medium"><?php echo $error; ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Doctor Information -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-2xl shadow-xl overflow-hidden sticky top-24">
                        <div class="bg-gradient-to-br from-blue-600 to-teal-500 p-6 text-center">
                            <div class="w-32 h-32 mx-auto bg-white rounded-full p-1 mb-4 shadow-lg">
                                <?php if (isset($doctor['image']) && $doctor['image']): ?>
                                    <img src="<?php echo htmlspecialchars($doctor['image']); ?>" alt="<?php echo htmlspecialchars($doctor['full_name']); ?>" class="w-full h-full object-cover rounded-full">
                                <?php else: ?>
                                    <div class="w-full h-full rounded-full bg-gray-100 flex items-center justify-center text-gray-400 text-4xl">
                                        <i class="fas fa-user-md"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <h2 class="text-2xl font-bold text-white mb-1">د. <?php echo htmlspecialchars($doctor['full_name']); ?></h2>
                            <p class="text-blue-100 font-medium"><?php echo htmlspecialchars($doctor['specialty_name']); ?></p>
                        </div>

                        <div class="p-6 space-y-4">
                            <div class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-blue-50 transition-colors duration-300">
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 ml-3">
                                    <i class="fas fa-hospital"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">المستشفى</p>
                                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($doctor['hospital_name']); ?></p>
                                </div>
                            </div>

                            <div class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-blue-50 transition-colors duration-300">
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 ml-3">
                                    <i class="fas fa-stethoscope"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">العيادة</p>
                                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($doctor['clinic_name']); ?></p>
                                </div>
                            </div>

                            <div class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-blue-50 transition-colors duration-300">
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 ml-3">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">العنوان</p>
                                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($doctor['hospital_address']); ?></p>
                                </div>
                            </div>

                            <?php if (isset($doctor['consultation_fee']) && $doctor['consultation_fee'] > 0): ?>
                                <div class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-blue-50 transition-colors duration-300">
                                    <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-600 ml-3">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">رسوم الاستشارة</p>
                                        <p class="font-semibold text-gray-800"><?php echo number_format($doctor['consultation_fee'], 2); ?> جنيه</p>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="border-t border-gray-100 pt-4 mt-4">
                                <div class="text-center">
                                    <div class="flex justify-center items-center mb-2 space-x-1 space-x-reverse text-yellow-400">
                                        <?php
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $average_rating) {
                                                echo '<i class="fas fa-star"></i>';
                                            } elseif ($i - $average_rating < 1) {
                                                echo '<i class="fas fa-star-half-alt"></i>';
                                            } else {
                                                echo '<i class="far fa-star text-gray-300"></i>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    <p class="font-bold text-gray-800 text-lg"><?php echo number_format($average_rating, 1); ?> <span class="text-sm text-gray-500 font-normal">من 5</span></p>
                                    <p class="text-sm text-gray-500"><?php echo count($reviews); ?> تقييم</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Booking Form & Schedule -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Schedule Info -->
                    <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                        <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-calendar-alt text-blue-600 ml-2"></i>
                            أوقات العمل
                        </h3>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                            <?php foreach ($schedule as $day): ?>
                                <div class="bg-gray-50 rounded-xl p-3 text-center border border-gray-100 hover:border-blue-300 hover:shadow-md transition-all duration-300">
                                    <p class="font-bold text-gray-800 mb-1"><?php echo $days_arabic[$day['day_of_week']]; ?></p>
                                    <p class="text-sm text-blue-600 font-medium dir-ltr">
                                        <?php echo date('H:i', strtotime($day['start_time'])); ?> - <?php echo date('H:i', strtotime($day['end_time'])); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Booking Form -->
                    <div class="bg-white rounded-2xl shadow-xl p-8 border-t-4 border-blue-600">
                        <form method="POST" id="bookingForm" class="space-y-8">
                            <!-- Date Selection -->
                            <div>
                                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                                    <span class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center ml-2 text-sm">1</span>
                                    اختيار التاريخ
                                </h3>
                                <div class="relative">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <i class="fas fa-calendar text-gray-400"></i>
                                    </div>
                                    <input type="date" name="appointment_date" 
                                           class="w-full pr-10 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-gray-50"
                                           min="<?php echo date('Y-m-d'); ?>" 
                                           value="<?php echo $selected_date; ?>" 
                                           required>
                                </div>
                            </div>

                            <!-- Time Selection -->
                            <div>
                                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                                    <span class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center ml-2 text-sm">2</span>
                                    اختيار الوقت
                                </h3>
                                <div class="bg-gray-50 rounded-xl p-6 border-2 border-dashed border-gray-200">
                                    <?php if (!empty($available_times)): ?>
                                        <div class="time-slots grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3">
                                            <?php foreach ($available_times as $time): ?>
                                                <div class="time-slot cursor-pointer text-center py-2 px-3 rounded-lg border-2 border-gray-200 bg-white hover:border-blue-500 hover:bg-blue-50 transition-all duration-200" 
                                                     data-time="<?php echo $time; ?>">
                                                    <span class="font-medium text-gray-700 dir-ltr block"><?php echo date('H:i', strtotime($time)); ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <input type="hidden" name="appointment_time" id="selected_time" required>
                                    <?php else: ?>
                                        <div class="text-center py-8 text-gray-500">
                                            <i class="fas fa-calendar-day text-4xl mb-3 text-gray-300"></i>
                                            <p>يرجى اختيار تاريخ أعلاه لعرض الأوقات المتاحة</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div>
                                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                                    <span class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center ml-2 text-sm">3</span>
                                    ملاحظات إضافية
                                </h3>
                                <textarea name="notes" 
                                          class="w-full p-4 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-gray-50 min-h-[120px]"
                                          placeholder="هل لديك أي أعراض معينة أو ملاحظات للطبيب؟"></textarea>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-teal-500 text-white font-bold py-4 px-6 rounded-xl shadow-lg transform transition hover:-translate-y-1 hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none flex items-center justify-center gap-2" id="submitBtn" disabled>
                                <i class="fas fa-calendar-check text-xl"></i>
                                <span>تأكيد حجز الموعد</span>
                            </button>
                        </form>
                    </div>

                    <!-- Recent Reviews -->
                    <?php if (!empty($reviews)): ?>
                        <div class="bg-white rounded-2xl shadow-lg p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-6 border-b pb-4">آخر التقييمات</h3>
                            <div class="space-y-6">
                                <?php foreach (array_slice($reviews, 0, 3) as $review): ?>
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <div class="flex justify-between items-start mb-2">
                                            <div>
                                                <span class="font-bold text-gray-800 block"><?php echo htmlspecialchars($review['user_name']); ?></span>
                                                <span class="text-xs text-gray-500"><?php echo date('Y/m/d', strtotime($review['created_at'])); ?></span>
                                            </div>
                                            <div class="flex text-yellow-400 text-sm">
                                                <?php
                                                for ($i = 1; $i <= 5; $i++) {
                                                    echo $i <= $review['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star text-gray-300"></i>';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                        <?php if (!empty($review['comment'])): ?>
                                            <p class="text-gray-600 text-sm leading-relaxed"><?php echo htmlspecialchars($review['comment']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dateInput = document.querySelector('input[name="appointment_date"]');
            const timeSlotsContainer = document.querySelector('.time-slots');
            const selectedTimeInput = document.getElementById('selected_time');
            const submitBtn = document.getElementById('submitBtn');
            const form = document.getElementById('bookingForm');

            // Handle date change
            dateInput.addEventListener('change', function() {
                const selectedDate = this.value;
                if (selectedDate) {
                    // Show loading state
                    const container = document.querySelector('.bg-gray-50.rounded-xl.p-6');
                    container.innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-3xl text-blue-500"></i><p class="mt-2 text-gray-500">جاري تحميل الأوقات...</p></div>';

                    const formData = new FormData();
                    formData.append('doctor_id', <?php echo $doctor_id; ?>);
                    formData.append('appointment_date', selectedDate);
                    
                    fetch('get_available_times.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        updateTimeSlots(data.times);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        container.innerHTML = '<div class="text-center py-8 text-red-500"><i class="fas fa-exclamation-triangle text-3xl mb-2"></i><p>حدث خطأ أثناء تحميل الأوقات</p></div>';
                    });
                }
            });

            function attachTimeSlotListeners() {
                const timeSlots = document.querySelectorAll('.time-slot');
                timeSlots.forEach(slot => {
                    slot.addEventListener('click', function() {
                        if (!this.classList.contains('opacity-50')) {
                            // Remove selected class from all slots
                            timeSlots.forEach(s => {
                                s.classList.remove('border-blue-600', 'bg-blue-600', 'text-white', 'ring-2', 'ring-blue-300');
                                s.classList.add('border-gray-200', 'bg-white', 'text-gray-700');
                                // Reset hover effect
                                s.classList.add('hover:border-blue-500', 'hover:bg-blue-50');
                            });
                            
                            // Add selected class to clicked slot
                            this.classList.remove('border-gray-200', 'bg-white', 'text-gray-700', 'hover:border-blue-500', 'hover:bg-blue-50');
                            this.classList.add('border-blue-600', 'bg-blue-600', 'text-white', 'ring-2', 'ring-blue-300');
                            
                            // Update hidden input
                            selectedTimeInput.value = this.dataset.time;
                            
                            // Enable submit button
                            submitBtn.disabled = false;
                            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                        }
                    });
                });
            }

            // Initial attachment
            if (timeSlotsContainer) {
                attachTimeSlotListeners();
            }

            function updateTimeSlots(times) {
                const container = document.querySelector('.bg-gray-50.rounded-xl.p-6');
                
                if (times && times.length > 0) {
                    let html = '<div class="time-slots grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3">';
                    times.forEach(time => {
                        // Format time to HH:MM
                        const date = new Date('1970-01-01T' + time);
                        const formattedTime = date.toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit' });
                        
                        html += `
                            <div class="time-slot cursor-pointer text-center py-2 px-3 rounded-lg border-2 border-gray-200 bg-white hover:border-blue-500 hover:bg-blue-50 transition-all duration-200" 
                                 data-time="${time}">
                                <span class="font-medium text-gray-700 dir-ltr block">${formattedTime}</span>
                            </div>
                        `;
                    });
                    html += '</div>';
                    html += '<input type="hidden" name="appointment_time" id="selected_time" required>';
                    container.innerHTML = html;
                    
                    // Re-attach listeners and update references
                    selectedTimeInput = document.getElementById('selected_time');
                    attachTimeSlotListeners();
                } else {
                    container.innerHTML = `
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-calendar-times text-4xl mb-3 text-gray-300"></i>
                            <p>لا توجد أوقات متاحة في هذا التاريخ</p>
                        </div>
                    `;
                }
            }

            // Form validation
            form.addEventListener('submit', function(e) {
                const selectedTime = document.getElementById('selected_time');
                if (!selectedTime || !selectedTime.value) {
                    e.preventDefault();
                    alert('يرجى اختيار وقت للموعد');
                }
            });
        });
    </script>
    
    <?php include 'includes/dashboard_footer.php'; ?>
</body>
</html>
