<?php
require_once 'includes/functions.php';

// التحقق من تسجيل الدخول
if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}

$user = get_logged_in_user();

// الحصول على معرف الطبيب من URL
$doctor_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$doctor_id) {
    header("Location: patient_home.php");
    exit();
}

// جلب بيانات الطبيب
$doctor = get_doctor_data($doctor_id);
if (!$doctor) {
    header("Location: patient_home.php");
    exit();
}

// معالجة اختيار التاريخ
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$available_times = [];

if ($selected_date) {
    $available_times = get_available_times($doctor_id, $selected_date);
}

// معالجة الحجز
$booking_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $appointment_time = $_POST['appointment_time'];
    $notes = sanitize_input($_POST['notes']);
    
    if (book_appointment($user['id'], $doctor_id, $selected_date, $appointment_time, $notes)) {
        $booking_message = '<div class="alert alert-success">تم الحجز بنجاح! سيتم إرسال تأكيد عبر البريد الإلكتروني.</div>';
    } else {
        $booking_message = '<div class="alert alert-danger">حدث خطأ في الحجز. يرجى المحاولة مرة أخرى.</div>';
    }
}

// جلب أوقات العمل للطبيب
$working_hours = get_working_hours($doctor_id);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>أوقات الشواغر - د. <?php echo htmlspecialchars($doctor['full_name']); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .availability-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .doctor-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .doctor-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            margin-bottom: 20px;
        }
        
        .doctor-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        
        .detail-item {
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }
        
        .date-selector {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .date-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 20px;
        }
        
        .date-option {
            padding: 15px;
            text-align: center;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .date-option:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }
        
        .date-option.selected {
            border-color: #667eea;
            background: #667eea;
            color: white;
        }
        
        .time-slots {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .time-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 10px;
            margin-top: 20px;
        }
        
        .time-slot {
            padding: 12px;
            text-align: center;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        
        .time-slot:hover {
            border-color: #28a745;
            background: #f8fff9;
        }
        
        .time-slot.selected {
            border-color: #28a745;
            background: #28a745;
            color: white;
        }
        
        .time-slot.unavailable {
            background: #f8f9fa;
            color: #6c757d;
            cursor: not-allowed;
            border-color: #dee2e6;
        }
        
        .booking-form {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn-book {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-book:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(40, 167, 69, 0.3);
        }
        
        .btn-book:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }
        
        .working-hours {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .hours-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .hour-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            text-align: center;
        }
        
        .hour-item.today {
            background: #e3f2fd;
            border: 2px solid #2196f3;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .doctor-details {
                grid-template-columns: 1fr;
            }
            
            .date-grid {
                grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            }
            
            .time-grid {
                grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="hero-section">
        <h1>أوقات الشواغر</h1>
        <p>احجز موعدك مع د. <?php echo htmlspecialchars($doctor['full_name']); ?></p>
    </div>
    
    <div class="availability-container">
        <?php echo $booking_message; ?>
        
        <!-- معلومات الطبيب -->
        <div class="doctor-info">
            <div style="text-align: center; margin-bottom: 20px;">
                <img src="<?php echo $doctor['image'] ?: 'assets/images/default-doctor.jpg'; ?>" 
                     alt="صورة الطبيب" class="doctor-avatar">
                <h2><?php echo htmlspecialchars($doctor['full_name']); ?></h2>
                <p style="font-size: 18px; opacity: 0.9;">
                    <?php echo htmlspecialchars($doctor['specialty_name']); ?>
                </p>
            </div>
            
            <div class="doctor-details">
                <div class="detail-item">
                    <strong>سنوات الخبرة:</strong>
                    <p><?php echo $doctor['experience_years']; ?> سنوات</p>
                </div>
                <div class="detail-item">
                    <strong>سعر الكشف:</strong>
                    <p><?php echo number_format($doctor['consultation_fee'] ?: 200); ?> جنيه</p>
                </div>
                <div class="detail-item">
                    <strong>التقييم:</strong>
                    <p>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span style="color: <?php echo $i <= $doctor['rating'] ? '#ffd700' : '#ccc'; ?>">★</span>
                        <?php endfor; ?>
                        (<?php echo number_format($doctor['rating'], 1); ?>)
                    </p>
                </div>
                <div class="detail-item">
                    <strong>المستشفى:</strong>
                    <p><?php echo htmlspecialchars($doctor['hospital_name'] ?: 'غير محدد'); ?></p>
                </div>
            </div>
            
            <?php if ($doctor['bio']): ?>
            <div style="margin-top: 20px; padding: 20px; background: rgba(255,255,255,0.1); border-radius: 10px;">
                <h4>نبذة عن الطبيب:</h4>
                <p><?php echo htmlspecialchars($doctor['bio']); ?></p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- أوقات العمل -->
        <div class="working-hours">
            <h3>��� أوقات العمل</h3>
            <div class="hours-grid">
                <?php 
                $days = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
                $english_days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
                $today = strtolower(date('l'));
                
                foreach ($english_days as $index => $day): 
                    $day_hours = array_filter($working_hours, function($hour) use ($day) {
                        return $hour['day_of_week'] === $day;
                    });
                    $is_today = ($day === $today);
                ?>
                <div class="hour-item <?php echo $is_today ? 'today' : ''; ?>">
                    <strong><?php echo $days[$index]; ?></strong>
                    <?php if (!empty($day_hours)): ?>
                        <?php foreach ($day_hours as $hour): ?>
                            <p><?php echo date('H:i', strtotime($hour['start_time'])); ?> - 
                               <?php echo date('H:i', strtotime($hour['end_time'])); ?></p>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: #6c757d;">إجازة</p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- اختيار التاريخ -->
        <div class="date-selector">
            <h3>��� اختر التاريخ</h3>
            <div class="date-grid">
                <?php for ($i = 0; $i < 14; $i++): ?>
                    <?php 
                    $date = date('Y-m-d', strtotime("+$i days"));
                    $day_name = date('l', strtotime($date));
                    $is_selected = ($date === $selected_date);
                    $is_available = false;
                    
                    // التحقق من توفر الطبيب في هذا اليوم
                    foreach ($working_hours as $hour) {
                        if (strtolower($day_name) === $hour['day_of_week']) {
                            $is_available = true;
                            break;
                        }
                    }
                    ?>
                    <div class="date-option <?php echo $is_selected ? 'selected' : ''; ?> <?php echo !$is_available ? 'unavailable' : ''; ?>"
                         onclick="<?php echo $is_available ? "selectDate('$date')" : ''; ?>">
                        <div style="font-weight: bold;"><?php echo date('d', strtotime($date)); ?></div>
                        <div style="font-size: 12px;"><?php echo date('M', strtotime($date)); ?></div>
                        <div style="font-size: 11px; opacity: 0.8;"><?php echo $day_name; ?></div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
        
        <!-- أوقات الشواغر -->
        <div class="time-slots">
            <h3>⏰ الأوقات المتاحة - <?php echo date('d/m/Y', strtotime($selected_date)); ?></h3>
            
            <?php if (empty($available_times)): ?>
                <p style="text-align: center; color: #6c757d; padding: 20px;">
                    لا توجد أوقات شاغرة في هذا اليوم
                </p>
            <?php else: ?>
                <div class="time-grid">
                    <?php foreach ($available_times as $time): ?>
                        <div class="time-slot" onclick="selectTime('<?php echo $time; ?>')">
                            <?php echo date('H:i', strtotime($time)); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- نموذج الحجز -->
        <div class="booking-form">
            <h3>��� تأكيد الحجز</h3>
            <form method="POST" id="bookingForm">
                <div class="form-group">
                    <label>التاريخ المختار:</label>
                    <input type="text" class="form-control" id="selectedDateDisplay" 
                           value="<?php echo date('d/m/Y', strtotime($selected_date)); ?>" readonly>
                    <input type="hidden" name="selected_date" value="<?php echo $selected_date; ?>">
                </div>
                
                <div class="form-group">
                    <label>الوقت المختار:</label>
                    <input type="text" class="form-control" id="selectedTimeDisplay" 
                           placeholder="اختر وقت من الأوقات المتاحة أعلاه" readonly>
                    <input type="hidden" name="appointment_time" id="selectedTime" required>
                </div>
                
                <div class="form-group">
                    <label>ملاحظات إضافية (اختياري):</label>
                    <textarea class="form-control" name="notes" rows="3" 
                              placeholder="أي ملاحظات أو معلومات إضافية تريد إضافتها..."></textarea>
                </div>
                
                <button type="submit" name="book_appointment" class="btn-book" id="bookButton" disabled>
                    احجز الموعد الآن
                </button>
            </form>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/script.js"></script>
    <script>
        function selectDate(date) {
            window.location.href = 'doctor_availability.php?id=<?php echo $doctor_id; ?>&date=' + date;
        }
        
        function selectTime(time) {
            // إزالة التحديد من جميع الأوقات
            document.querySelectorAll('.time-slot').forEach(slot => {
                slot.classList.remove('selected');
            });
            
            // تحديد الوقت المختار
            event.target.classList.add('selected');
            
            // تحديث النموذج
            document.getElementById('selectedTime').value = time;
            document.getElementById('selectedTimeDisplay').value = time;
            
            // تفعيل زر الحجز
            document.getElementById('bookButton').disabled = false;
        }
        
        // التحقق من صحة النموذج قبل الإرسال
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            const selectedTime = document.getElementById('selectedTime').value;
            if (!selectedTime) {
                e.preventDefault();
                alert('يرجى اختيار وقت للحجز');
                return false;
            }
        });
    </script>
</body>
</html>
