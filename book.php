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

// الحصول على بيانات الطبيب والعيادة
$doctor_id = isset($_GET['doctor']) ? (int)$_GET['doctor'] : 0;
$clinic_id = isset($_GET['clinic']) ? (int)$_GET['clinic'] : 0;

if (!$doctor_id || !$clinic_id) {
    header("Location: search.php");
    exit();
}

// الحصول على معلومات الطبيب والعيادة
$db = new Database();
$conn = $db->getConnection();

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

if (!$doctor) {
    header("Location: search.php");
    exit();
}

// الحصول على أوقات عمل الطبيب
$schedule = get_doctor_schedule($doctor_id);

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
    $available_times = get_available_times($doctor_id, $selected_date);
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
$reviews = get_doctor_reviews($doctor_id, 5);
$average_rating = 0;
if (!empty($reviews)) {
    $total_rating = array_sum(array_column($reviews, 'rating'));
    $average_rating = $total_rating / count($reviews);
}

$page_title = "حجز موعد مع د. " . htmlspecialchars($doctor['full_name']);
?>

<?php include 'includes/dashboard_header.php'; ?>

<style>
        .booking-page {
            padding-top: 80px;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);
        }
        
        .booking-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 20px;
        }
        
        .booking-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .booking-header h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--primary-blue), var(--medical-green));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .booking-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }
        
        .doctor-info-card {
            background: var(--bg-primary);
            border-radius: var(--radius-xl);
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            position: sticky;
            top: 100px;
        }
        
        .doctor-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-blue), var(--medical-green));
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 3rem;
            color: white;
        }
        
        .doctor-name {
            font-size: 1.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }
        
        .doctor-specialty {
            text-align: center;
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
        }
        
        .doctor-details {
            display: grid;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: var(--bg-secondary);
            border-radius: var(--radius);
            transition: var(--transition);
        }
        
        .detail-item:hover {
            background: var(--primary-blue);
            color: white;
        }
        
        .detail-item i {
            width: 20px;
            color: var(--primary-blue);
            transition: var(--transition);
        }
        
        .detail-item:hover i {
            color: white;
        }
        
        .rating-section {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .rating-stars {
            margin-bottom: 0.5rem;
        }
        
        .rating-stars i {
            color: #fbbf24;
            font-size: 1.2rem;
        }
        
        .rating-text {
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .booking-form-card {
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
        
        .form-textarea {
            resize: vertical;
            min-height: 100px;
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
        
        .time-slot.unavailable {
            background: var(--error);
            color: white;
            cursor: not-allowed;
            opacity: 0.6;
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
        
        .btn-book {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary-blue), var(--medical-green));
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
        
        .btn-book:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
        }
        
        .btn-book:disabled {
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
        
        .reviews-section {
            margin-top: 2rem;
        }
        
        .review-item {
            background: var(--bg-secondary);
            padding: 1rem;
            border-radius: var(--radius);
            margin-bottom: 1rem;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .reviewer-name {
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .review-date {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .review-rating {
            margin-bottom: 0.5rem;
        }
        
        .review-rating i {
            color: #fbbf24;
        }
        
        .review-comment {
            color: var(--text-secondary);
            line-height: 1.6;
        }
        
        @media (max-width: 768px) {
            .booking-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .doctor-info-card {
                position: static;
            }
            
            .time-slots {
                grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            }
        }
    </style>

    <!-- Booking Page -->
    <section class="booking-page">
        <div class="booking-container">
            <!-- Page Header -->
            <div class="booking-header">
                <h1>حجز موعد طبي</h1>
                <p>احجز موعدك مع أفضل الأطباء بسهولة وأمان</p>
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

            <div class="booking-grid">
                <!-- Doctor Information -->
                <div class="doctor-info-card">
                    <div class="doctor-avatar">
                        <?php if (isset($doctor['image']) && $doctor['image']): ?>
                            <img src="<?php echo htmlspecialchars($doctor['image']); ?>" alt="<?php echo htmlspecialchars($doctor['full_name']); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                        <?php else: ?>
                            <i class="fas fa-user-md"></i>
                        <?php endif; ?>
                    </div>
                    
                    <div class="doctor-name">د. <?php echo htmlspecialchars($doctor['full_name']); ?></div>
                    <div class="doctor-specialty"><?php echo htmlspecialchars($doctor['specialty_name']); ?></div>
                    
                    <div class="doctor-details">
                        <div class="detail-item">
                            <i class="fas fa-hospital"></i>
                            <span><?php echo htmlspecialchars($doctor['hospital_name']); ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-stethoscope"></i>
                            <span><?php echo htmlspecialchars($doctor['clinic_name']); ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars($doctor['hospital_address']); ?></span>
                        </div>
                        <?php if (isset($doctor['consultation_fee']) && $doctor['consultation_fee'] > 0): ?>
                            <div class="detail-item">
                                <i class="fas fa-money-bill-wave"></i>
                                <span>رسوم الاستشارة: <?php echo number_format($doctor['consultation_fee'], 2); ?> جنيه</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="rating-section">
                        <div class="rating-stars">
                            <?php
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $average_rating) {
                                    echo '<i class="fas fa-star"></i>';
                                } elseif ($i - $average_rating < 1) {
                                    echo '<i class="fas fa-star-half-alt"></i>';
                                } else {
                                    echo '<i class="far fa-star"></i>';
                                }
                            }
                            ?>
                        </div>
                        <div class="rating-text"><?php echo number_format($average_rating, 1); ?> من 5</div>
                        <div style="color: var(--text-secondary); font-size: 0.9rem;"><?php echo count($reviews); ?> تقييم</div>
                    </div>
                    
                    <!-- Schedule Information -->
                    <div class="schedule-info">
                        <h4 style="margin-bottom: 1rem; color: var(--text-primary);">أوقات العمل:</h4>
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
                    
                    <!-- Recent Reviews -->
                    <?php if (!empty($reviews)): ?>
                        <div class="reviews-section">
                            <h4 style="margin-bottom: 1rem; color: var(--text-primary);">آخر التقييمات:</h4>
                            <?php foreach (array_slice($reviews, 0, 3) as $review): ?>
                                <div class="review-item">
                                    <div class="review-header">
                                        <span class="reviewer-name"><?php echo htmlspecialchars($review['user_name']); ?></span>
                                        <span class="review-date"><?php echo date('Y/m/d', strtotime($review['created_at'])); ?></span>
                                    </div>
                                    <div class="review-rating">
                                        <?php
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $review['rating']) {
                                                echo '<i class="fas fa-star"></i>';
                                            } else {
                                                echo '<i class="far fa-star"></i>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    <?php if (!empty($review['comment'])): ?>
                                        <div class="review-comment"><?php echo htmlspecialchars($review['comment']); ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Booking Form -->
                <div class="booking-form-card">
                    <form method="POST" id="bookingForm">
                        <!-- Date Selection -->
                        <div class="form-section">
                            <h3><i class="fas fa-calendar"></i> اختيار التاريخ</h3>
                            <div class="form-group">
                                <label class="form-label">التاريخ المطلوب:</label>
                                <input type="date" name="appointment_date" class="form-input" 
                                       min="<?php echo date('Y-m-d'); ?>" 
                                       value="<?php echo $selected_date; ?>" 
                                       required>
                            </div>
                        </div>

                        <!-- Time Selection -->
                        <div class="form-section">
                            <h3><i class="fas fa-clock"></i> اختيار الوقت</h3>
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
                                    <input type="hidden" name="appointment_time" id="selected_time" required>
                                <?php else: ?>
                                    <p style="color: var(--text-secondary); text-align: center; padding: 2rem;">
                                        اختر تاريخاً أولاً لعرض الأوقات المتاحة
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="form-section">
                            <h3><i class="fas fa-notes-medical"></i> ملاحظات إضافية</h3>
                            <div class="form-group">
                                <label class="form-label">ملاحظات (اختياري):</label>
                                <textarea name="notes" class="form-input form-textarea" 
                                          placeholder="اكتب أي ملاحظات أو تفاصيل إضافية..."></textarea>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn-book" id="submitBtn" disabled>
                            <i class="fas fa-calendar-plus"></i>
                            حجز الموعد
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <script src="assets/js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dateInput = document.querySelector('input[name="appointment_date"]');
            const timeSlots = document.querySelectorAll('.time-slot');
            const selectedTimeInput = document.getElementById('selected_time');
            const submitBtn = document.getElementById('submitBtn');
            const form = document.getElementById('bookingForm');

            // Handle date change
            dateInput.addEventListener('change', function() {
                const selectedDate = this.value;
                if (selectedDate) {
                    // Submit form to get available times
                    const formData = new FormData();
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
    
    <?php include 'includes/dashboard_footer.php'; ?>
