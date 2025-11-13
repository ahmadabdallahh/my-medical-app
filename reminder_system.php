<?php
require_once 'includes/functions.php';
if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}
$user = get_logged_in_user();

// معالجة تحديث إعدادات التذكيرات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_reminders = isset($_POST['email_reminders']) ? 1 : 0;
    $sms_reminders = isset($_POST['sms_reminders']) ? 1 : 0;
    $push_notifications = isset($_POST['push_notifications']) ? 1 : 0;
    $reminder_time = sanitize_input($_POST['reminder_time']);

    update_reminder_settings($user['id'], [
        'email_reminders' => $email_reminders,
        'sms_reminders' => $sms_reminders,
        'push_notifications' => $push_notifications,
        'reminder_time' => $reminder_time
    ]);

    $success_message = "تم تحديث إعدادات التذكيرات بنجاح";
}

// جلب إعدادات التذكيرات الحالية
$reminder_settings = get_reminder_settings($user['id']);

// جلب التذكيرات القادمة
$upcoming_reminders = get_upcoming_reminders($user['id']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام التذكيرات | Health Tech</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); }
        .hero-section {
            background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
            color: #fff;
            padding: 2rem 0;
            text-align: center;
        }
        .hero-section h1 { font-size: 2.5rem; margin-bottom: 0.5rem; }
        .hero-section p { font-size: 1.1rem; opacity: 0.9; }
        .reminder-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .reminder-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        .reminder-card {
            background: #fff;
            border-radius: 1.2rem;
            box-shadow: 0 4px 20px rgba(245,158,11,0.1);
            padding: 1.5rem;
            border: 1px solid #fde68a;
        }
        .card-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #fef3c7;
        }
        .card-header i {
            font-size: 1.8rem;
            color: #f59e0b;
            background: #fef3c7;
            padding: 0.8rem;
            border-radius: 50%;
        }
        .card-header h3 {
            margin: 0;
            color: #92400e;
            font-size: 1.3rem;
        }
        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #fef3c7;
        }
        .setting-item:last-child {
            border-bottom: none;
        }
        .setting-info h4 {
            margin: 0 0 0.3rem 0;
            color: #92400e;
        }
        .setting-info p {
            margin: 0;
            color: #78716c;
            font-size: 0.9rem;
        }
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #f59e0b;
        }
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        .reminder-time-select {
            padding: 0.5rem;
            border: 2px solid #fde68a;
            border-radius: 0.5rem;
            background: #fef3c7;
            color: #92400e;
            font-weight: bold;
        }
        .reminder-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #fef3c7;
            border-radius: 0.8rem;
            margin-bottom: 1rem;
            border-right: 4px solid #f59e0b;
        }
        .reminder-icon {
            background: #f59e0b;
            color: #fff;
            padding: 0.8rem;
            border-radius: 50%;
            font-size: 1.2rem;
        }
        .reminder-info {
            flex: 1;
        }
        .reminder-info h4 {
            margin: 0 0 0.3rem 0;
            color: #92400e;
        }
        .reminder-info p {
            margin: 0;
            color: #78716c;
            font-size: 0.9rem;
        }
        .reminder-time {
            background: #f59e0b;
            color: #fff;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: bold;
            font-size: 0.9rem;
        }
        .btn-save {
            background: #f59e0b;
            color: #fff;
            border: none;
            border-radius: 0.7rem;
            padding: 0.8rem 2rem;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
            width: 100%;
            margin-top: 1rem;
        }
        .btn-save:hover {
            background: #d97706;
        }
        .success-message {
            background: #dcfce7;
            color: #166534;
            padding: 1rem;
            border-radius: 0.7rem;
            margin-bottom: 1rem;
            border: 1px solid #bbf7d0;
        }
        .notification-types {
            display: grid;
            gap: 1rem;
        }
        .notification-type {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #fef3c7;
            border-radius: 0.8rem;
            border: 2px solid transparent;
            transition: border-color 0.2s;
        }
        .notification-type.active {
            border-color: #f59e0b;
            background: #fde68a;
        }
        .notification-type i {
            font-size: 1.5rem;
            color: #f59e0b;
        }
        .notification-info h4 {
            margin: 0 0 0.3rem 0;
            color: #92400e;
        }
        .notification-info p {
            margin: 0;
            color: #78716c;
            font-size: 0.9rem;
        }
        @media (max-width: 768px) {
            .reminder-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>

<div class="hero-section">
    <h1>🔔 نظام التذكيرات</h1>
    <p>إدارة التذكيرات والإشعارات للمواعيد الطبية</p>
</div>

<div class="reminder-container">
    <?php if (isset($success_message)): ?>
        <div class="success-message">
            <i class="fas fa-check-circle"></i>
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <div class="reminder-grid">
        <!-- إعدادات التذكيرات -->
        <div class="reminder-card">
            <div class="card-header">
                <i class="fas fa-cog"></i>
                <h3>إعدادات التذكيرات</h3>
            </div>

            <form method="POST" action="">
                <div class="notification-types">
                    <div class="notification-type <?php echo $reminder_settings['email_reminders'] ? 'active' : ''; ?>">
                        <i class="fas fa-envelope"></i>
                        <div class="notification-info">
                            <h4>تذكيرات البريد الإلكتروني</h4>
                            <p>استلام تذكيرات عبر البريد الإلكتروني</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="email_reminders" <?php echo $reminder_settings['email_reminders'] ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="notification-type <?php echo $reminder_settings['sms_reminders'] ? 'active' : ''; ?>">
                        <i class="fas fa-sms"></i>
                        <div class="notification-info">
                            <h4>تذكيرات الرسائل النصية</h4>
                            <p>استلام تذكيرات عبر الرسائل النصية</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="sms_reminders" <?php echo $reminder_settings['sms_reminders'] ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="notification-type <?php echo $reminder_settings['push_notifications'] ? 'active' : ''; ?>">
                        <i class="fas fa-bell"></i>
                        <div class="notification-info">
                            <h4>الإشعارات الفورية</h4>
                            <p>استلام إشعارات فورية في الموقع</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="push_notifications" <?php echo $reminder_settings['push_notifications'] ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <div class="setting-item">
                    <div class="setting-info">
                        <h4>وقت التذكير</h4>
                        <p>متى تريد استلام التذكيرات قبل الموعد</p>
                    </div>
                    <select name="reminder_time" class="reminder-time-select">
                        <option value="15" <?php echo $reminder_settings['reminder_time'] == 15 ? 'selected' : ''; ?>>15 دقيقة</option>
                        <option value="30" <?php echo $reminder_settings['reminder_time'] == 30 ? 'selected' : ''; ?>>30 دقيقة</option>
                        <option value="60" <?php echo $reminder_settings['reminder_time'] == 60 ? 'selected' : ''; ?>>ساعة واحدة</option>
                        <option value="120" <?php echo $reminder_settings['reminder_time'] == 120 ? 'selected' : ''; ?>>ساعتين</option>
                        <option value="1440" <?php echo $reminder_settings['reminder_time'] == 1440 ? 'selected' : ''; ?>>يوم واحد</option>
                    </select>
                </div>

                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i>
                    حفظ الإعدادات
                </button>
            </form>
        </div>

        <!-- التذكيرات القادمة -->
        <div class="reminder-card">
            <div class="card-header">
                <i class="fas fa-calendar-clock"></i>
                <h3>التذكيرات القادمة</h3>
            </div>

            <?php if (empty($upcoming_reminders)): ?>
                <div style="text-align:center; color:#78716c; padding:2rem;">
                    <i class="fas fa-calendar-times" style="font-size:3rem; margin-bottom:1rem;"></i>
                    <p>لا توجد تذكيرات قادمة</p>
                </div>
            <?php else: ?>
                <?php foreach ($upcoming_reminders as $reminder): ?>
                    <div class="reminder-item">
                        <div class="reminder-icon">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <div class="reminder-info">
                            <h4><?php echo htmlspecialchars($reminder['doctor_name']); ?></h4>
                            <p><?php echo htmlspecialchars($reminder['clinic_name']); ?> - <?php echo htmlspecialchars($reminder['specialty_name'] ?? ''); ?></p>
                        </div>
                        <div class="reminder-time">
                            <?php echo date('H:i', strtotime($reminder['appointment_time'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- معلومات إضافية -->
    <div class="reminder-card" style="margin-top: 2rem;">
        <div class="card-header">
            <i class="fas fa-info-circle"></i>
            <h3>معلومات مهمة</h3>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
            <div style="padding: 1rem; background: #fef3c7; border-radius: 0.8rem;">
                <h4 style="color: #92400e; margin-bottom: 0.5rem;">
                    <i class="fas fa-envelope"></i> البريد الإلكتروني
                </h4>
                <p style="color: #78716c; font-size: 0.9rem; margin: 0;">
                    ستتمكن من استلام تذكيرات مفصلة عبر البريد الإلكتروني مع إمكانية إضافة الموعد إلى التقويم.
                </p>
            </div>

            <div style="padding: 1rem; background: #fef3c7; border-radius: 0.8rem;">
                <h4 style="color: #92400e; margin-bottom: 0.5rem;">
                    <i class="fas fa-sms"></i> الرسائل النصية
                </h4>
                <p style="color: #78716c; font-size: 0.9rem; margin: 0;">
                    تذكيرات سريعة عبر الرسائل النصية للوصول السريع حتى بدون إنترنت.
                </p>
            </div>

            <div style="padding: 1rem; background: #fef3c7; border-radius: 0.8rem;">
                <h4 style="color: #92400e; margin-bottom: 0.5rem;">
                    <i class="fas fa-bell"></i> الإشعارات الفورية
                </h4>
                <p style="color: #78716c; font-size: 0.9rem; margin: 0;">
                    إشعارات فورية تظهر في أعلى الصفحة عند تسجيل الدخول للموقع.
                </p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
<script src="assets/js/script.js"></script>
<script>
// تفعيل/إلغاء تفعيل الأنواع عند التبديل
document.querySelectorAll('.toggle-switch input').forEach(toggle => {
    toggle.addEventListener('change', function() {
        const notificationType = this.closest('.notification-type');
        if (this.checked) {
            notificationType.classList.add('active');
        } else {
            notificationType.classList.remove('active');
        }
    });
});
</script>
</body>
</html>
