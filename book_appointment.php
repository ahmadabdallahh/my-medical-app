<?php
session_start();
require_once 'config.php';
require_once 'includes/functions.php';

// Ensure user is logged in as a patient
if (!is_logged_in() || $_SESSION['user_type'] !== 'patient') {
    // Save the intended destination
    $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
    // Redirect to login page
    header('Location: login.php?message=login_required');
    exit();
}

// Get doctor ID from URL
$doctor_id = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;
if ($doctor_id === 0) {
    header('Location: search.php');
    exit();
}

// Fetch doctor details
$doctor = get_doctor_by_id($doctor_id);
if (!$doctor) {
    // Redirect if doctor not found
    header('Location: search.php?error=notfound');
    exit();
}

$pageTitle = 'حجز موعد مع ' . htmlspecialchars($doctor['full_name']);
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
        /* Custom styles for datepicker to match our theme */
        .datepicker-panel > ul > li.picked, .datepicker-panel > ul > li.picked:hover {
            background-color: #2563EB; /* Primary Blue */
            color: #fff;
        }
        .datepicker-panel > ul > li.highlighted {
            background-color: #DBEAFE; /* Light Blue */
            color: #1E40AF; /* Darker Blue */
        }
    </style>
</head>
<body class="font-cairo bg-gray-50">

<?php require_once 'includes/header.php'; ?>

<main class="bg-gray-50 py-12">
    <div class="container mx-auto px-4">

        <!-- Page Header -->
        <div class="text-center mb-10">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-800">حجز موعد</h1>
            <p class="text-lg text-gray-600 mt-2">أنت على وشك حجز موعد مع الدكتور: <span class="font-bold text-blue-600"><?php echo htmlspecialchars($doctor['full_name']); ?></span></p>
        </div>

        <!-- Consultation Fee Display -->
        <div class="max-w-sm mx-auto bg-blue-50 border-2 border-blue-200 p-4 rounded-xl text-center mb-8">
            <p class="text-lg font-semibold text-blue-800">سعر الكشف</p>
            <p class="text-3xl font-bold text-blue-600 mt-1">
                <?php
                if (!empty($doctor['consultation_fee']) && is_numeric($doctor['consultation_fee'])):
                    echo htmlspecialchars($doctor['consultation_fee']) . ' جنيه';
                else:
                    echo 'يُحدد لاحقًا';
                endif;
                ?>
            </p>
        </div>

        <div class="max-w-4xl mx-auto bg-white p-8 rounded-2xl shadow-xl border border-gray-100">
            <form id="booking-form" action="submit_booking.php" method="POST">
                <input type="hidden" name="doctor_id" value="<?php echo $doctor_id; ?>">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-10">

                    <!-- Step 1: Select Date -->
                    <div>
                        <label for="appointment_date" class="block text-xl font-bold text-gray-800 mb-4">١. اختر اليوم المناسب</label>
                        <div class="relative">
                            <input type="date" id="appointment_date" name="appointment_date"
                                   class="block w-full p-4 pr-12 text-lg bg-gray-100 border-2 border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 transition"
                                   required
                                   min="<?php echo date('Y-m-d'); ?>">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                <i class="fas fa-calendar-alt text-gray-400 fa-lg"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Select Time -->
                    <div>
                        <h2 class="text-xl font-bold text-gray-800 mb-4">٢. اختر الوقت المتاح</h2>
                        <div id="time-slots-container" class="relative min-h-[150px] bg-gray-100 p-4 rounded-lg">
                            <!-- Placeholder -->
                            <div id="time-slots-placeholder" class="absolute inset-0 flex items-center justify-center text-center">
                                <div>
                                    <i class="fas fa-calendar-day fa-3x text-gray-300 mb-3"></i>
                                    <p class="text-gray-500 font-semibold">اختر اليوم المناسب لعرض المواعيد المتاحة</p>
                                    <p class="text-gray-400 text-sm mt-1">ستظهر أوقات العمل المتاحة للدكتور</p>
                                </div>
                            </div>
                            <!-- Time Slots Grid -->
                            <div id="time-slots-grid" class="grid grid-cols-3 sm:grid-cols-4 gap-3"></div>
                        </div>
                        <input type="hidden" name="appointment_time" id="appointment_time_hidden" required>
                    </div>

                </div>

                <!-- Confirmation Button -->
                <div class="mt-10 pt-8 border-t border-gray-200">
                    <button type="submit" id="confirm-booking-btn" class="w-full bg-orange-500 text-white font-bold py-4 px-6 text-lg rounded-lg hover:bg-orange-600 transition transform hover:-translate-y-1 disabled:bg-gray-300 disabled:cursor-not-allowed shadow-lg disabled:shadow-none" disabled>
                        <i class="fas fa-check-circle ml-2"></i>
                        تأكيد الحجز
                    </button>
                </div>

            </form>
        </div>
    </div>
</main>

<!-- JQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

<script>
$(function () {
    const doctorId = <?php echo $doctor_id; ?>;
    const appointmentDateInput = $('#appointment_date');
    const timeSlotsContainer = $('#time-slots-container');
    const timeSlotsPlaceholder = $('#time-slots-placeholder');
    const timeSlotsGrid = $('#time-slots-grid');
    const appointmentTimeHidden = $('#appointment_time_hidden');
    const confirmBookingBtn = $('#confirm-booking-btn');

    // Function to show/hide placeholder and grid
    function toggleTimeSlotsDisplay(showGrid) {
        if (showGrid) {
            timeSlotsPlaceholder.hide();
            timeSlotsGrid.css('display', 'grid');
        } else {
            timeSlotsPlaceholder.show();
            timeSlotsGrid.hide();
        }
    }

    appointmentDateInput.on('change', function () {
        const selectedDate = $(this).val();

        // Reset state
        appointmentTimeHidden.val('');
        confirmBookingBtn.prop('disabled', true);
        timeSlotsGrid.empty();
        toggleTimeSlotsDisplay(false); // Show placeholder
        timeSlotsPlaceholder.html(`
            <div>
                <i class="fas fa-spinner fa-spin fa-2x text-blue-400"></i>
                <p class="mt-2 text-gray-500 font-semibold">جاري البحث عن المواعيد المتاحة...</p>
                <p class="text-gray-400 text-sm mt-1">يرجى الانتظار لحظة</p>
            </div>
        `);

        if (!selectedDate) {
            timeSlotsPlaceholder.html(`
                <div>
                    <i class="fas fa-calendar-day fa-3x text-gray-300 mb-3"></i>
                    <p class="text-gray-500 font-semibold">الرجاء اختيار يوم من التقويم لعرض الأوقات المتاحة.</p>
                </div>
            `);
            return;
        }

        // AJAX call to get available time slots
        $.ajax({
            url: `api/get_slots.php?doctor_id=${doctorId}&date=${selectedDate}`,
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.slots.length > 0) {
                    response.slots.forEach(slot => {
                        const time12hr = new Date(`1970-01-01T${slot}`).toLocaleTimeString('ar-EG', { hour: 'numeric', minute: '2-digit', hour12: true });
                        const button = `<button type="button" data-time="${slot}" class="time-slot-btn p-3 bg-blue-100 text-blue-800 font-semibold rounded-lg hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">${time12hr}</button>`;
                        timeSlotsGrid.append(button);
                    });
                    toggleTimeSlotsDisplay(true); // Show grid
                } else {
                    timeSlotsPlaceholder.html(`
                        <div>
                            <i class="fas fa-calendar-times fa-3x text-orange-300 mb-3"></i>
                            <p class="text-gray-600 font-semibold mb-2">الدكتور غير متاح في هذا اليوم</p>
                            <p class="text-gray-500 text-sm">جميع المواعيد محجوزة أو الدكتور لا يعمل اليوم. لا تقلق، يمكنك اختيار يوم آخر! 😊</p>
                        </div>
                    `);
                }
            },
            error: function() {
                timeSlotsPlaceholder.html(`
                    <div>
                        <i class="fas fa-exclamation-circle fa-3x text-red-400 mb-3"></i>
                        <p class="text-red-500 font-semibold">حدث خطأ أثناء تحميل المواعيد. الرجاء المحاولة مرة أخرى.</p>
                    </div>
                `);
            }
        });
    });

    // Event delegation for time slot selection
    timeSlotsGrid.on('click', '.time-slot-btn:not(:disabled)', function() {
        const selectedTime = $(this).data('time');
        appointmentTimeHidden.val(selectedTime);

        // Visual feedback for selection
        $('.time-slot-btn').removeClass('bg-blue-600 text-white').addClass('bg-blue-100 text-blue-800');
        $(this).removeClass('bg-blue-100 text-blue-800').addClass('bg-blue-600 text-white');

        confirmBookingBtn.prop('disabled', false);
    });
});
</script>

<?php
require_once 'includes/footer.php';
?>
