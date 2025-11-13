<?php
require_once 'includes/functions.php';

// التحقق من تسجيل الدخول
if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}

$user = get_logged_in_user();
$error = '';
$success = '';

// الحصول على معرف الموعد
$appointment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$appointment_id) {
    header("Location: appointments.php");
    exit();
}

// الحصول على معلومات الموعد
$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("
    SELECT a.*, d.full_name as doctor_name, d.id as doctor_id,
           c.name as clinic_name, h.name as hospital_name,
           s.name as specialty_name
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    JOIN clinics c ON a.clinic_id = c.id
    JOIN hospitals h ON c.hospital_id = h.id
    LEFT JOIN specialties s ON d.specialty_id = s.id
    WHERE a.id = ? AND a.user_id = ?
");
$stmt->execute([$appointment_id, $user['id']]);
$appointment = $stmt->fetch();

if (!$appointment) {
    header("Location: appointments.php");
    exit();
}

// التحقق من إمكانية إعادة الجدولة
if (!can_cancel_appointment($appointment_id, $user['id'])) {
    header("Location: appointments.php?error=cannot_reschedule");
    exit();
}

// الحصول على أوقات عمل الطبيب
$schedule = get_doctor_schedule($appointment['doctor_id']);

// معالجة إعادة الجدولة
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_date = sanitize_input($_POST['new_date']);
    $new_time = sanitize_input($_POST['new_time']);

    if (empty($new_date) || empty($new_time)) {
        $error = 'يرجى اختيار التاريخ والوقت الجديد';
    } else {
        // التحقق من أن التاريخ في المستقبل
        if (strtotime($new_date) < strtotime(date('Y-m-d'))) {
            $error = 'لا يمكن حجز موعد في تاريخ ماضي';
        } else {
            // التحقق من توفر الموعد الجديد
            if (is_appointment_available($appointment['doctor_id'], $new_date, $new_time)) {
                if (reschedule_appointment($appointment_id, $user['id'], $new_date, $new_time)) {
                    // إرسال إشعار للمستخدم
                    send_notification(
                        $user['id'], 
                        'appointment_confirmed', 
                        "تم إعادة جدولة موعدك إلى {$new_date} الساعة {$new_time} مع د. {$appointment['doctor_name']}",
                        $appointment['doctor_id']
                    );
                    
                    $success = 'تم إعادة جدولة الموعد بنجاح!';
                } else {
                    $error = 'حدث خطأ أثناء إعادة جدولة الموعد';
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
if (isset($_POST['new_date']) && !empty($_POST['new_date'])) {
    $selected_date = $_POST['new_date'];
    $available_times = get_available_times($appointment['doctor_id'], $selected_date);
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
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعادة جدولة الموعد - نظام حجز المواعيد الطبية</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .reschedule-page {
            padding-top: 80px;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);
        }
        
        .reschedule-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem 20px;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .page-header h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--primary-blue), var(--medical-green));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .current-appointment {
            background: var(--bg-primary);
            border-radius: var(--radius-xl);
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            margin-bottom: 2rem;
            border-left: 4px solid var(--warning);
        }
        
        .current-appointment h3 {
            color: var(--warning);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .appointment-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: var(--bg-secondary);
            border-radius: var(--radius);
        }
        
        .detail-item i {
            width: 20px;
            color: var(--primary-blue);
        }
        
        .reschedule-form {
            background: var(--bg-primary);
            border-radius: var(--radius-xl);
            padding: 2rem;
            box-shadow: var(--shadow-lg);
        }
        
        .form-section {
            margin-bottom: 2rem;
        }
        
        .form-section h3 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .form-input {
            width: 100%;
            padding: 1rem;
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            font-size: 1rem;
            transition: var(--transition);
            background: var(--bg-secondary);
            color: var(--text-primary);
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .time-slots {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 0.75rem;
            margin-top: 1rem;
        }
        
        .time-slot {
            padding: 0.75rem;
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            background: var(--bg-secondary);
            color: var(--text-primary);
        }
        
        .time-slot:hover {
            border-color: var(--primary-blue);
            background: var(--primary-blue);
            color: white;
        }
        
        .time-slot.selected {
            border-color: var(--medical-green);
            background: var(--medical-green);
            color: white;
        }
        
        .btn-reschedule {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--warning), #fbbf24);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-reschedule:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
        }
        
        .btn-reschedule:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .messages {
            margin-bottom: 2rem;
        }
        
        .message {
            padding: 1rem;
            border-radius: var(--radius);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .message.success {
            background: linear-gradient(135deg, var(--success), #34d399);
            color: white;
        }
        
        .message.error {
            background: linear-gradient(135deg, var(--error), #f87171);
            color: white;
        }
        
        .schedule-info {
            background: var(--bg-secondary);
            padding: 1.5rem;
            border-radius: var(--radius);
            margin-bottom: 2rem;
        }
        
        .schedule-grid {
            display: grid;
            gap: 0.75rem;
        }
        
        .schedule-day {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: var(--bg-primary);
            border-radius: var(--radius);
        }
        
        .day-name {
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .day-time {
            color: var(--text-secondary);
        }
        
        @media (max-width: 768px) {
            .appointment-details {
                grid-template-columns: 1fr;
            }
            
            .time-slots {
                grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <!-- Reschedule Page -->
    <section class="reschedule-page">
        <div class="reschedule-container">
            <!-- Page Header -->
            <div class="page-header">
                <h1>إعادة جدولة الموعد</h1>
                <p>اختر التاريخ والوقت الجديد لموعدك</p>
            </div>

            <!-- Messages -->
            <div class="messages">
                <?php if ($success): ?>
                    <div class="message success">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo $success; ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="message error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Current Appointment -->
            <div class="current-appointment">
                <h3><i class="fas fa-calendar-times"></i> الموعد الحالي</h3>
                <div class="appointment-details">
                    <div class="detail-item">
                        <i class="fas fa-user-md"></i>
                        <div>
                            <div style="font-weight: 600; color: var(--text-primary);">الطبيب</div>
                            <div><?php echo htmlspecialchars($appointment['doctor_name']); ?></div>
                        </div>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-stethoscope"></i>
                        <div>
                            <div style="font-weight: 600; color: var(--text-primary);">التخصص</div>
                            <div><?php echo htmlspecialchars($appointment['specialty_name']); ?></div>
                        </div>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-hospital"></i>
                        <div>
                            <div style="font-weight: 600; color: var(--text-primary);">المستشفى</div>
                            <div><?php echo htmlspecialchars($appointment['hospital_name']); ?></div>
                        </div>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-calendar"></i>
                        <div>
                            <div style="font-weight: 600; color: var(--text-primary);">التاريخ الحالي</div>
                            <div><?php echo format_date_arabic($appointment['appointment_date']); ?></div>
                        </div>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-clock"></i>
                        <div>
                            <div style="font-weight: 600; color: var(--text-primary);">الوقت الحالي</div>
                            <div><?php echo format_time_arabic($appointment['appointment_time']); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reschedule Form -->
            <div class="reschedule-form">
                <form method="POST" id="rescheduleForm">
                    <!-- Schedule Information -->
                    <div class="schedule-info">
                        <h4 style="margin-bottom: 1rem; color: var(--text-primary);">أوقات عمل الطبيب:</h4>
                        <div class="schedule-grid">
                            <?php foreach ($schedule as $day): ?>
                                <div class="schedule-day">
                                    <span class="day-name"><?php echo $days_arabic[$day['day_of_week']]; ?></span>
                                    <span class="day-time">
                                        <?php echo date('H:i', strtotime($day['start_time'])); ?> - 
                                        <?php echo date('H:i', strtotime($day['end_time'])); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- New Date Selection -->
                    <div class="form-section">
                        <h3><i class="fas fa-calendar"></i> التاريخ الجديد</h3>
                        <div class="form-group">
                            <label class="form-label">التاريخ المطلوب:</label>
                            <input type="date" name="new_date" class="form-input" 
                                   min="<?php echo date('Y-m-d'); ?>" 
                                   value="<?php echo $selected_date; ?>" 
                                   required>
                        </div>
                    </div>

                    <!-- New Time Selection -->
                    <div class="form-section">
                        <h3><i class="fas fa-clock"></i> الوقت الجديد</h3>
                        <div class="form-group">
                            <label class="form-label">الوقت المتاح:</label>
                            <?php if (!empty($available_times)): ?>
                                <div class="time-slots">
                                    <?php foreach ($available_times as $time): ?>
                                        <div class="time-slot" data-time="<?php echo $time; ?>">
                                            <?php echo date('H:i', strtotime($time)); ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" name="new_time" id="selected_time" required>
                            <?php else: ?>
                                <p style="color: var(--text-secondary); text-align: center; padding: 2rem;">
                                    اختر تاريخاً أولاً لعرض الأوقات المتاحة
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-reschedule" id="submitBtn" disabled>
                        <i class="fas fa-calendar-check"></i>
                        إعادة جدولة الموعد
                    </button>
                </form>
            </div>
        </div>
    </section>

    <script src="assets/js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dateInput = document.querySelector('input[name="new_date"]');
            const timeSlots = document.querySelectorAll('.time-slot');
            const selectedTimeInput = document.getElementById('selected_time');
            const submitBtn = document.getElementById('submitBtn');
            const form = document.getElementById('rescheduleForm');

            // Handle date change
            dateInput.addEventListener('change', function() {
                const selectedDate = this.value;
                if (selectedDate) {
                    // Submit form to get available times
                    const formData = new FormData();
                    formData.append('appointment_date', selectedDate);
                    formData.append('doctor_id', <?php echo $appointment['doctor_id']; ?>);
                    
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
                    });
                }
            });

            // Handle time slot selection
            timeSlots.forEach(slot => {
                slot.addEventListener('click', function() {
                    if (!this.classList.contains('unavailable')) {
                        // Remove selected class from all slots
                        timeSlots.forEach(s => s.classList.remove('selected'));
                        
                        // Add selected class to clicked slot
                        this.classList.add('selected');
                        
                        // Update hidden input
                        selectedTimeInput.value = this.dataset.time;
                        
                        // Enable submit button
                        submitBtn.disabled = false;
                    }
                });
            });

            function updateTimeSlots(times) {
                const timeSlotsContainer = document.querySelector('.time-slots');
                if (timeSlotsContainer) {
                    timeSlotsContainer.innerHTML = '';
                    
                    if (times && times.length > 0) {
                        times.forEach(time => {
                            const slot = document.createElement('div');
                            slot.className = 'time-slot';
                            slot.dataset.time = time;
                            slot.textContent = time;
                            slot.addEventListener('click', function() {
                                timeSlots.forEach(s => s.classList.remove('selected'));
                                this.classList.add('selected');
                                selectedTimeInput.value = this.dataset.time;
                                submitBtn.disabled = false;
                            });
                            timeSlotsContainer.appendChild(slot);
                        });
                    } else {
                        timeSlotsContainer.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: var(--text-secondary);">لا توجد أوقات متاحة في هذا التاريخ</p>';
                    }
                }
            }

            // Form validation
            form.addEventListener('submit', function(e) {
                if (!selectedTimeInput.value) {
                    e.preventDefault();
                    alert('يرجى اختيار وقت للموعد');
                }
            });
        });
    </script>
</body>
</html> 