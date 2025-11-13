<?php
require_once 'includes/functions.php';

// التحقق من تسجيل الدخول
if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}

$user = get_logged_in_user();

// معالجة تحديث حالة الإشعار كمقروء
if (isset($_POST['mark_read'])) {
    $notification_id = (int)$_POST['notification_id'];
    if (mark_notification_read($notification_id, $user['id'])) {
        header("Location: notifications.php?success=marked_read");
        exit();
    } else {
        header("Location: notifications.php?error=mark_failed");
        exit();
    }
}

// الحصول على إشعارات المستخدم
$notifications = get_user_notifications($user['id'], 50);

// تصنيف الإشعارات
$unread_notifications = array_filter($notifications, function($notification) {
    return !$notification['is_read'];
});

$read_notifications = array_filter($notifications, function($notification) {
    return $notification['is_read'];
});

$success_message = '';
$error_message = '';

if (isset($_GET['success'])) {
    if ($_GET['success'] == 'marked_read') {
        $success_message = 'تم تحديث حالة الإشعار';
    }
}

if (isset($_GET['error'])) {
    if ($_GET['error'] == 'mark_failed') {
        $error_message = 'حدث خطأ أثناء تحديث حالة الإشعار';
    }
}

// تحويل أنواع الإشعارات إلى العربية
$notification_types = [
    'appointment_confirmed' => 'تأكيد موعد',
    'appointment_cancelled' => 'إلغاء موعد',
    'appointment_reminder' => 'تذكير موعد',
    'system_message' => 'رسالة نظام'
];

function get_notification_icon($type) {
    switch ($type) {
        case 'appointment_confirmed':
            return 'fas fa-check-circle';
        case 'appointment_cancelled':
            return 'fas fa-times-circle';
        case 'appointment_reminder':
            return 'fas fa-bell';
        case 'system_message':
            return 'fas fa-info-circle';
        default:
            return 'fas fa-bell';
    }
}

function get_notification_color($type) {
    switch ($type) {
        case 'appointment_confirmed':
            return 'success';
        case 'appointment_cancelled':
            return 'error';
        case 'appointment_reminder':
            return 'warning';
        case 'system_message':
            return 'info';
        default:
            return 'info';
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإشعارات - نظام حجز المواعيد الطبية</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .notifications-page {
            padding-top: 80px;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);
        }
        
        .notifications-container {
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
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .stat-card {
            background: var(--bg-primary);
            padding: 2rem;
            border-radius: var(--radius-xl);
            text-align: center;
            box-shadow: var(--shadow-lg);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, var(--primary-blue), var(--medical-green));
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--primary-blue), var(--medical-green));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .tabs-container {
            background: var(--bg-primary);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .tabs-header {
            display: flex;
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
        }
        
        .tab-button {
            flex: 1;
            padding: 1.5rem;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-secondary);
            transition: var(--transition);
            position: relative;
        }
        
        .tab-button:hover {
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary-blue);
        }
        
        .tab-button.active {
            background: var(--primary-blue);
            color: white;
        }
        
        .tab-button.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--medical-green);
        }
        
        .tab-content {
            display: none;
            padding: 2rem;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .notifications-list {
            display: grid;
            gap: 1rem;
        }
        
        .notification-item {
            background: var(--bg-primary);
            border: 2px solid var(--border-color);
            border-radius: var(--radius-xl);
            padding: 1.5rem;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .notification-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-blue);
            transition: var(--transition);
        }
        
        .notification-item:hover {
            border-color: var(--primary-blue);
            transform: translateY(-3px);
            box-shadow: var(--shadow-xl);
        }
        
        .notification-item.unread {
            border-color: var(--warning);
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.1), rgba(251, 191, 36, 0.05));
        }
        
        .notification-item.unread::before {
            background: var(--warning);
        }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .notification-type {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: var(--radius);
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .type-success {
            background: linear-gradient(135deg, var(--success), #34d399);
            color: white;
        }
        
        .type-error {
            background: linear-gradient(135deg, var(--error), #f87171);
            color: white;
        }
        
        .type-warning {
            background: linear-gradient(135deg, var(--warning), #fbbf24);
            color: white;
        }
        
        .type-info {
            background: linear-gradient(135deg, var(--info), var(--primary-light));
            color: white;
        }
        
        .notification-date {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .notification-message {
            color: var(--text-primary);
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        
        .notification-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }
        
        .btn-mark-read {
            padding: 0.5rem 1rem;
            background: var(--primary-blue);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-mark-read:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
        }
        
        .empty-state i {
            font-size: 4rem;
            color: var(--text-light);
            margin-bottom: 1rem;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }
        
        .empty-state p {
            margin-bottom: 2rem;
            font-size: 1.1rem;
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
        
        @media (max-width: 768px) {
            .tabs-header {
                flex-direction: column;
            }
            
            .tab-button {
                border-bottom: 1px solid var(--border-color);
            }
            
            .notification-header {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
            
            .notification-actions {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <!-- Notifications Page -->
    <section class="notifications-page">
        <div class="notifications-container">
            <!-- Page Header -->
            <div class="page-header">
                <h1>الإشعارات</h1>
                <p>تابع آخر التحديثات والرسائل المهمة</p>
            </div>

            <!-- Messages -->
            <div class="messages">
                <?php if ($success_message): ?>
                    <div class="message success">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo $success_message; ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="message error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $error_message; ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Stats Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($notifications); ?></div>
                    <div class="stat-label">إجمالي الإشعارات</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($unread_notifications); ?></div>
                    <div class="stat-label">غير مقروءة</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($read_notifications); ?></div>
                    <div class="stat-label">مقروءة</div>
                </div>
            </div>

            <!-- Tabs Container -->
            <div class="tabs-container">
                <div class="tabs-header">
                    <button class="tab-button active" data-tab="all">
                        <i class="fas fa-bell"></i>
                        جميع الإشعارات (<?php echo count($notifications); ?>)
                    </button>
                    <button class="tab-button" data-tab="unread">
                        <i class="fas fa-envelope"></i>
                        غير مقروءة (<?php echo count($unread_notifications); ?>)
                    </button>
                </div>

                <!-- All Notifications Tab -->
                <div class="tab-content active" id="all">
                    <?php if (empty($notifications)): ?>
                        <div class="empty-state">
                            <i class="fas fa-bell-slash"></i>
                            <h3>لا توجد إشعارات</h3>
                            <p>ستظهر هنا جميع إشعاراتك عند توفرها</p>
                        </div>
                    <?php else: ?>
                        <div class="notifications-list">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>">
                                    <div class="notification-header">
                                        <span class="notification-type type-<?php echo get_notification_color($notification['type']); ?>">
                                            <i class="<?php echo get_notification_icon($notification['type']); ?>"></i>
                                            <?php echo $notification_types[$notification['type']] ?? $notification['type']; ?>
                                        </span>
                                        <span class="notification-date">
                                            <?php echo date('Y/m/d H:i', strtotime($notification['created_at'])); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="notification-message">
                                        <?php echo htmlspecialchars($notification['message']); ?>
                                    </div>
                                    
                                    <?php if (!$notification['is_read']): ?>
                                        <div class="notification-actions">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                <button type="submit" name="mark_read" class="btn-mark-read">
                                                    <i class="fas fa-check"></i>
                                                    تحديد كمقروء
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Unread Notifications Tab -->
                <div class="tab-content" id="unread">
                    <?php if (empty($unread_notifications)): ?>
                        <div class="empty-state">
                            <i class="fas fa-check-circle"></i>
                            <h3>لا توجد إشعارات غير مقروءة</h3>
                            <p>جميع إشعاراتك مقروءة</p>
                        </div>
                    <?php else: ?>
                        <div class="notifications-list">
                            <?php foreach ($unread_notifications as $notification): ?>
                                <div class="notification-item unread">
                                    <div class="notification-header">
                                        <span class="notification-type type-<?php echo get_notification_color($notification['type']); ?>">
                                            <i class="<?php echo get_notification_icon($notification['type']); ?>"></i>
                                            <?php echo $notification_types[$notification['type']] ?? $notification['type']; ?>
                                        </span>
                                        <span class="notification-date">
                                            <?php echo date('Y/m/d H:i', strtotime($notification['created_at'])); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="notification-message">
                                        <?php echo htmlspecialchars($notification['message']); ?>
                                    </div>
                                    
                                    <div class="notification-actions">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                            <button type="submit" name="mark_read" class="btn-mark-read">
                                                <i class="fas fa-check"></i>
                                                تحديد كمقروء
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <script src="assets/js/script.js"></script>
    <script>
        // Tab Switching Logic
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const targetTab = this.dataset.tab;
                    
                    // Remove active class from all buttons and contents
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    // Add active class to clicked button and corresponding content
                    this.classList.add('active');
                    document.getElementById(targetTab).classList.add('active');
                });
            });
        });
    </script>
</body>
</html> 