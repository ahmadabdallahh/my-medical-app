<?php
require_once 'includes/functions.php';
if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}
$user = get_logged_in_user();

// التحقق من أن المستخدم طبيب
if ($user['role'] !== 'doctor') {
    header("Location: patient_home.php");
    exit();
}

// جلب بيانات الطبيب
$doctor_id = $user['id'];
$doctor_data = get_doctor_data($doctor_id);

// جلب المواعيد القادمة
$upcoming_appointments = get_upcoming_appointments($doctor_id);

// جلب إحصائيات الطبيب
$stats = get_doctor_stats($doctor_id);

// جلب التقييمات الأخيرة
$recent_reviews = get_doctor_reviews($doctor_id, 5);

// جلب أوقات العمل
$working_hours = get_working_hours($doctor_id);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم الطبيب | صحة</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); }
        .hero-section {
            background: linear-gradient(135deg, #0ea5e9 0%, #38bdf8 100%);
            color: #fff;
            padding: 2rem 0;
            text-align: center;
        }
        .hero-section h1 { font-size: 2.5rem; margin-bottom: 0.5rem; }
        .hero-section p { font-size: 1.1rem; opacity: 0.9; }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .dashboard-card {
            background: #fff;
            border-radius: 1.2rem;
            box-shadow: 0 4px 20px rgba(14,165,233,0.1);
            padding: 1.5rem;
            border: 1px solid #e0f2fe;
        }
        .card-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f9ff;
        }
        .card-header i {
            font-size: 1.8rem;
            color: #0ea5e9;
            background: #f0f9ff;
            padding: 0.8rem;
            border-radius: 50%;
        }
        .card-header h3 {
            margin: 0;
            color: #0c4a6e;
            font-size: 1.3rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        .stat-item {
            text-align: center;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 0.8rem;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #0ea5e9;
            margin-bottom: 0.3rem;
        }
        .stat-label {
            color: #64748b;
            font-size: 0.9rem;
        }
        .appointment-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 0.8rem;
            margin-bottom: 1rem;
            border-right: 4px solid #0ea5e9;
        }
        .appointment-time {
            background: #0ea5e9;
            color: #fff;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: bold;
            min-width: 100px;
            text-align: center;
        }
        .appointment-info {
            flex: 1;
        }
        .appointment-info h4 {
            margin: 0 0 0.3rem 0;
            color: #0c4a6e;
        }
        .appointment-info p {
            margin: 0;
            color: #64748b;
            font-size: 0.9rem;
        }
        .review-item {
            padding: 1rem;
            background: #f8fafc;
            border-radius: 0.8rem;
            margin-bottom: 1rem;
        }
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        .review-stars {
            color: #fbbf24;
        }
        .review-date {
            color: #64748b;
            font-size: 0.9rem;
        }
        .review-text {
            color: #374151;
            line-height: 1.5;
        }
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        .action-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 1.2rem;
            background: #0ea5e9;
            color: #fff;
            text-decoration: none;
            border-radius: 0.7rem;
            font-weight: bold;
            transition: background 0.2s;
        }
        .action-btn:hover {
            background: #0284c7;
            color: #fff;
        }
        .action-btn.secondary {
            background: #f1f5f9;
            color: #0ea5e9;
            border: 2px solid #0ea5e9;
        }
        .action-btn.secondary:hover {
            background: #e0f2fe;
        }
        .working-hours {
            display: grid;
            gap: 0.5rem;
        }
        .hour-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem;
            background: #f8fafc;
            border-radius: 0.5rem;
        }
        .hour-day {
            font-weight: bold;
            color: #0c4a6e;
        }
        .hour-time {
            color: #64748b;
        }
        @media (max-width: 768px) {
            .dashboard-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>

<div class="hero-section">
    <h1>مرحباً د. <?php echo htmlspecialchars($user['full_name']); ?></h1>
    <p>لوحة تحكم الطبيب - إدارة المواعيد والمرضى والتقييمات</p>
</div>

<div class="dashboard-grid">
    <!-- إحصائيات سريعة -->
    <div class="dashboard-card">
        <div class="card-header">
            <i class="fas fa-chart-line"></i>
            <h3>الإحصائيات</h3>
        </div>
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-number"><?php echo $stats['total_appointments'] ?? 0; ?></div>
                <div class="stat-label">إجمالي المواعيد</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $stats['today_appointments'] ?? 0; ?></div>
                <div class="stat-label">مواعيد اليوم</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo number_format($stats['avg_rating'] ?? 0, 1); ?></div>
                <div class="stat-label">متوسط التقييم</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $stats['total_patients'] ?? 0; ?></div>
                <div class="stat-label">إجمالي المرضى</div>
            </div>
        </div>
    </div>

    <!-- المواعيد القادمة -->
    <div class="dashboard-card">
        <div class="card-header">
            <i class="fas fa-calendar-check"></i>
            <h3>المواعيد القادمة</h3>
        </div>
        <?php if (empty($upcoming_appointments)): ?>
            <p style="text-align:center; color:#64748b;">لا توجد مواعيد قادمة</p>
        <?php else: ?>
            <?php foreach (array_slice($upcoming_appointments, 0, 5) as $appointment): ?>
                <div class="appointment-item">
                    <div class="appointment-time">
                        <?php echo date('H:i', strtotime($appointment['appointment_time'])); ?>
                    </div>
                    <div class="appointment-info">
                        <h4><?php echo htmlspecialchars($appointment['patient_name']); ?></h4>
                        <p><?php echo date('Y-m-d', strtotime($appointment['appointment_time'])); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <div class="quick-actions">
            <a href="appointments.php" class="action-btn">
                <i class="fas fa-calendar-alt"></i>
                عرض جميع المواعيد
            </a>
        </div>
    </div>

    <!-- التقييمات الأخيرة -->
    <div class="dashboard-card">
        <div class="card-header">
            <i class="fas fa-star"></i>
            <h3>التقييمات الأخيرة</h3>
        </div>
        <?php if (empty($recent_reviews)): ?>
            <p style="text-align:center; color:#64748b;">لا توجد تقييمات بعد</p>
        <?php else: ?>
            <?php foreach ($recent_reviews as $review): ?>
                <div class="review-item">
                    <div class="review-header">
                        <div class="review-stars">
                            <?php
                            $rating = $review['rating'];
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $rating) {
                                    echo '<i class="fas fa-star"></i>';
                                } else {
                                    echo '<i class="far fa-star"></i>';
                                }
                            }
                            ?>
                        </div>
                        <div class="review-date">
                            <?php echo date('Y-m-d', strtotime($review['created_at'])); ?>
                        </div>
                    </div>
                    <div class="review-text">
                        <?php echo htmlspecialchars($review['comment']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- أوقات العمل -->
    <div class="dashboard-card">
        <div class="card-header">
            <i class="fas fa-clock"></i>
            <h3>أوقات العمل</h3>
        </div>
        <div class="working-hours">
            <?php if (empty($working_hours)): ?>
                <p style="text-align:center; color:#64748b;">لم يتم تحديد أوقات العمل</p>
            <?php else: ?>
                <?php foreach ($working_hours as $hour): ?>
                    <div class="hour-item">
                        <span class="hour-day"><?php echo htmlspecialchars($hour['day_name']); ?></span>
                        <span class="hour-time">
                            <?php echo $hour['start_time'] . ' - ' . $hour['end_time']; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="quick-actions">
            <a href="doctor-schedule.php" class="action-btn secondary">
                <i class="fas fa-edit"></i>
                تعديل الجدول
            </a>
        </div>
    </div>

    <!-- إجراءات سريعة -->
    <div class="dashboard-card">
        <div class="card-header">
            <i class="fas fa-tools"></i>
            <h3>إجراءات سريعة</h3>
        </div>
        <div class="quick-actions">
            <a href="doctor-profile.php" class="action-btn">
                <i class="fas fa-user-edit"></i>
                تعديل الملف الشخصي
            </a>
            <a href="clinic-settings.php" class="action-btn">
                <i class="fas fa-hospital"></i>
                إعدادات العيادة
            </a>
            <a href="patient-records.php" class="action-btn">
                <i class="fas fa-notes-medical"></i>
                سجلات المرضى
            </a>
            <a href="notifications.php" class="action-btn secondary">
                <i class="fas fa-bell"></i>
                الإشعارات
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
<script src="assets/js/script.js"></script>
</body>
</html>
