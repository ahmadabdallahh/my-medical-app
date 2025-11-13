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
    header("Location: appointments.php");
    exit();
}

// Get appointment details
$appointment = get_appointment_details($conn, $appointment_id);

if (!$appointment) {
    $_SESSION['error_message'] = 'الموعد غير موجود';
    header("Location: appointments.php");
    exit();
}

// Check if the appointment belongs to the current user
$user = get_logged_in_user();
if ($appointment['user_id'] != $user['id']) {
    $_SESSION['error_message'] = 'غير مصرح لك بالوصول إلى هذا الموعد';
    header("Location: appointments.php");
    exit();
}

// Handle appointment cancellation
if (isset($_POST['cancel_appointment'])) {
    if (cancel_appointment($appointment_id, $user['id'])) {
        $_SESSION['success_message'] = 'تم إلغاء الموعد بنجاح';
        header("Location: appointments.php?success=cancelled");
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
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل الموعد - <?php echo $appointment['doctor_name']; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #0EA5E9;
            --medical-green: #10B981;
            --primary-blue-dark: #0284C7;
            --medical-green-dark: #059669;
            --accent-purple: #8B5CF6;
            --accent-pink: #EC4899;
            --gradient-1: linear-gradient(135deg, #0EA5E9 0%, #10B981 100%);
            --gradient-2: linear-gradient(135deg, #7DD3FC 0%, #34D399 100%);
            --gradient-3: linear-gradient(135deg, #38BDF8 0%, #2DD4BF 100%);
            --gradient-4: linear-gradient(135deg, #BAE6FD 0%, #A7F3D0 100%);
            --soft-blue: #E0F2FE;
            --soft-green: #F0FDF4;
            --warm-gray: #F8FAFC;
        }

        html {
            scroll-behavior: smooth;
        }

        .appointment-details-page {
            padding-top: 80px;
            min-height: 100vh;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 25%, #f0fdf4 50%, #ecfdf5 75%, #f0f9ff 100%);
            background-size: 400% 400%;
            animation: gradientShift 25s ease infinite;
            position: relative;
            overflow-x: hidden;
        }

        .appointment-details-page::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(circle at 20% 50%, rgba(14, 165, 233, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(16, 185, 129, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, rgba(14, 165, 233, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 60% 70%, rgba(16, 185, 129, 0.05) 0%, transparent 50%);
            pointer-events: none;
            z-index: 1;
            animation: gradientShift 30s ease infinite reverse;
        }

        @keyframes gradientShift {
            0%, 100% {
                background-position: 0% 50%;
                opacity: 1;
            }
            50% {
                background-position: 100% 50%;
                opacity: 0.9;
            }
        }

        .details-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem 20px;
            position: relative;
            z-index: 2;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 1.75rem;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.9));
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 14px;
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 2rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(14, 165, 233, 0.1);
        }

        .back-button:hover {
            transform: translateX(-5px);
            box-shadow: 0 8px 25px rgba(14, 165, 233, 0.2);
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.1), rgba(16, 185, 129, 0.05));
            border-color: var(--primary-blue);
        }

        .detail-header {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.9));
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow:
                0 20px 40px rgba(14, 165, 233, 0.1),
                0 10px 25px rgba(16, 185, 129, 0.08);
            margin-bottom: 2rem;
            border: 1px solid rgba(226, 232, 240, 0.8);
            position: relative;
            overflow: hidden;
        }

        .detail-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, var(--primary-blue), var(--medical-green));
        }

        .detail-header::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(14, 165, 233, 0.08) 0%, transparent 70%);
            animation: float 20s infinite linear;
        }

        @keyframes float {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        .doctor-info {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .doctor-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-blue), var(--medical-green));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            font-weight: 700;
            box-shadow:
                0 15px 35px rgba(14, 165, 233, 0.3),
                0 5px 15px rgba(16, 185, 129, 0.2);
            position: relative;
            overflow: hidden;
        }

        .doctor-avatar::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.3) 0%, transparent 70%);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        .doctor-details h1 {
            font-size: 2.25rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--primary-blue), var(--medical-green), var(--primary-blue));
            background-size: 200% 200%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradientText 3s ease infinite;
            font-weight: 800;
        }

        @keyframes gradientText {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .doctor-specialty {
            color: var(--text-secondary);
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .doctor-rating {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #fbbf24;
        }

        .appointment-time-card {
            background: linear-gradient(135deg, var(--primary-blue), var(--medical-green), var(--primary-blue));
            background-size: 200% 200%;
            color: white;
            padding: 1.75rem;
            border-radius: 18px;
            text-align: center;
            box-shadow:
                0 15px 35px rgba(14, 165, 233, 0.3),
                0 5px 15px rgba(16, 185, 129, 0.2);
            animation: gradientText 3s ease infinite;
            position: relative;
            overflow: hidden;
        }

        .appointment-time-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.2) 0%, transparent 70%);
            animation: shimmer 4s infinite;
        }

        @keyframes gradientText {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .appointment-time-card .date {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .appointment-time-card .time {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-confirmed {
            background: linear-gradient(135deg, var(--medical-green), #34d399, var(--medical-green));
            background-size: 200% 200%;
            color: white;
            animation: gradientText 3s ease infinite;
        }

        .status-pending {
            background: linear-gradient(135deg, #f59e0b, #fbbf24, #f59e0b);
            background-size: 200% 200%;
            color: white;
            animation: gradientText 3s ease infinite;
        }

        .status-cancelled {
            background: linear-gradient(135deg, #ef4444, #f87171, #ef4444);
            background-size: 200% 200%;
            color: white;
            animation: gradientText 3s ease infinite;
        }

        .status-completed {
            background: linear-gradient(135deg, var(--primary-blue), #60a5fa, var(--primary-blue));
            background-size: 200% 200%;
            color: white;
            animation: gradientText 3s ease infinite;
        }

        .detail-sections {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .detail-section {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.9));
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow:
                0 15px 35px rgba(14, 165, 233, 0.08),
                0 5px 15px rgba(16, 185, 129, 0.06);
            border: 1px solid rgba(226, 232, 240, 0.8);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .detail-section:hover {
            transform: translateY(-4px);
            box-shadow:
                0 20px 40px rgba(14, 165, 233, 0.12),
                0 8px 20px rgba(16, 185, 129, 0.08);
        }

        .detail-section::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.1), rgba(16, 185, 129, 0.1));
            border-radius: 50%;
            opacity: 0.5;
            transform: translate(30px, -30px);
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--primary-blue), var(--medical-green), var(--primary-blue));
            background-size: 200% 200%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradientText 3s ease infinite;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title i {
            font-size: 1.2rem;
            background: linear-gradient(135deg, var(--primary-blue), var(--medical-green));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.25rem;
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.05), rgba(16, 185, 129, 0.05));
            border-radius: 14px;
            margin-bottom: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(226, 232, 240, 0.5);
        }

        .info-item:hover {
            background: linear-gradient(135deg, var(--primary-blue), var(--medical-green));
            transform: translateX(-5px) scale(1.02);
            border-color: transparent;
            box-shadow: 0 8px 20px rgba(14, 165, 233, 0.25);
            color: white;
        }

        .info-item:hover .info-label,
        .info-item:hover .info-value {
            color: white;
        }

        .info-item i {
            background: linear-gradient(135deg, var(--primary-blue), var(--medical-green));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 1.3rem;
            width: 28px;
            transition: all 0.3s ease;
        }

        .info-item:hover i {
            -webkit-text-fill-color: white;
            color: white;
        }

        .info-label {
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 0.25rem;
        }

        .info-value {
            font-weight: 700;
            color: var(--text-primary);
        }

        .notes-section {
            background: linear-gradient(135deg, rgba(240, 249, 255, 0.8), rgba(240, 253, 244, 0.8));
            border-right: 4px solid var(--medical-green);
            padding: 1.75rem;
            border-radius: 14px;
            margin-top: 1rem;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.1);
            transition: all 0.3s ease;
        }

        .notes-section:hover {
            border-right-width: 6px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
        }

        .notes-content {
            color: var(--text-secondary);
            line-height: 1.6;
            font-size: 1.05rem;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 2rem;
        }

        .btn-action {
            padding: 0.875rem 1.75rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .btn-action::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .btn-action:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-blue), var(--medical-green));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 12px 25px rgba(14, 165, 233, 0.4);
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
            color: white;
        }

        .btn-warning:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 12px 25px rgba(245, 158, 11, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #f87171);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 12px 25px rgba(239, 68, 68, 0.4);
        }

        .message {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            font-weight: 500;
            animation: slideDown 0.3s ease-out;
        }

        .message.success {
            background: linear-gradient(135deg, var(--medical-green), #34d399, var(--medical-green));
            background-size: 200% 200%;
            color: white;
            animation: gradientText 3s ease infinite;
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
        }

        .message.error {
            background: linear-gradient(135deg, #ef4444, #f87171, #ef4444);
            background-size: 200% 200%;
            color: white;
            animation: gradientText 3s ease infinite;
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3);
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .doctor-info {
                flex-direction: column;
                text-align: center;
            }

            .detail-sections {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-action {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <i class="fas fa-heartbeat"></i>
                    <span>صحة</span>
                </div>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link">الرئيسية</a>
                    </li>
                    <li class="nav-item">
                        <a href="search.php" class="nav-link">البحث عن طبيب</a>
                    </li>
                    <li class="nav-item">
                        <a href="appointments.php" class="nav-link">مواعيدي</a>
                    </li>
                </ul>
                <div class="nav-auth">
                    <span class="user-name">مرحباً، <?php echo $user['full_name']; ?></span>
                    <a href="logout.php" class="btn btn-outline">تسجيل الخروج</a>
                </div>
            </div>
        </nav>
    </header>

    <!-- Appointment Details Page -->
    <section class="appointment-details-page">
        <div class="details-container">
            <!-- Back Button -->
            <a href="appointments.php" class="back-button">
                <i class="fas fa-arrow-right"></i>
                العودة إلى مواعيدي
            </a>

            <!-- Messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $error_message; ?></span>
                </div>
            <?php endif; ?>

            <!-- Detail Header -->
            <div class="detail-header">
                <div class="doctor-info">
                    <div class="doctor-avatar">
                        <?php echo mb_substr($appointment['doctor_name'], 0, 1); ?>
                    </div>
                    <div class="doctor-details">
                        <h1><?php echo $appointment['doctor_name']; ?></h1>
                        <div class="doctor-specialty">
                            <?php echo $appointment['specialty_name'] ?: 'طبيب عام'; ?>
                        </div>
                        <div class="doctor-rating">
                            <i class="fas fa-star"></i>
                            <span><?php echo $appointment['rating'] ?: '0.0'; ?></span>
                            <span>(<?php echo $appointment['total_ratings'] ?: '0'; ?> تقييم)</span>
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div class="appointment-time-card">
                        <div class="date"><?php echo format_date_arabic($appointment['appointment_date']); ?></div>
                        <div class="time"><?php echo format_time_arabic($appointment['appointment_time']); ?></div>
                    </div>
                    <div class="status-badge status-<?php echo $appointment['status']; ?>">
                        <i class="fas fa-circle"></i>
                        <?php echo get_status_arabic($appointment['status']); ?>
                    </div>
                </div>
            </div>

            <!-- Detail Sections -->
            <div class="detail-sections">
                <!-- Location Information -->
                <div class="detail-section">
                    <div class="section-title">
                        <i class="fas fa-map-marker-alt"></i>
                        معلومات المكان
                    </div>
                    <div class="info-item">
                        <i class="fas fa-hospital"></i>
                        <div>
                            <div class="info-label">المستشفى</div>
                            <div class="info-value"><?php echo $appointment['hospital_name'] ?: 'غير محدد'; ?></div>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-stethoscope"></i>
                        <div>
                            <div class="info-label">العيادة</div>
                            <div class="info-value"><?php echo $appointment['clinic_name'] ?: 'غير محدد'; ?></div>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <div class="info-label">رقم الهاتف</div>
                            <div class="info-value"><?php echo $appointment['clinic_phone'] ?: 'غير متوفر'; ?></div>
                        </div>
                    </div>
                </div>

                <!-- Appointment Information -->
                <div class="detail-section">
                    <div class="section-title">
                        <i class="fas fa-info-circle"></i>
                        معلومات الموعد
                    </div>
                    <div class="info-item">
                        <i class="fas fa-calendar-plus"></i>
                        <div>
                            <div class="info-label">تاريخ الحجز</div>
                            <div class="info-value"><?php echo format_date_arabic($appointment['created_at']); ?></div>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-clock"></i>
                        <div>
                            <div class="info-label">نوع الموعد</div>
                            <div class="info-value"><?php echo $appointment['appointment_type'] ?: 'استشارة عامة'; ?></div>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-money-bill-wave"></i>
                        <div>
                            <div class="info-label">رسول الاستشارة</div>
                            <div class="info-value"><?php echo $appointment['consultation_fee'] ?: 'غير محدد'; ?> جنيه</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes Section -->
            <?php if (!empty($appointment['notes'])): ?>
                <div class="detail-section">
                    <div class="section-title">
                        <i class="fas fa-notes-medical"></i>
                        ملاحظات الموعد
                    </div>
                    <div class="notes-section">
                        <div class="notes-content"><?php echo nl2br(htmlspecialchars($appointment['notes'])); ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <?php if (can_cancel_appointment($appointment['id'], $user['id'])): ?>
                <div class="detail-section">
                    <div class="section-title">
                        <i class="fas fa-tools"></i>
                        إجراءات الموعد
                    </div>
                    <form method="POST" style="display: inline;">
                        <div class="action-buttons">
                            <button type="submit" name="reschedule_appointment" class="btn-action btn-warning">
                                <i class="fas fa-calendar-alt"></i>
                                إعادة جدولة الموعد
                            </button>
                            <button type="submit" name="cancel_appointment" class="btn-action btn-danger"
                                    onclick="return confirm('هل أنت متأكد من إلغاء هذا الموعد؟')">
                                <i class="fas fa-times"></i>
                                إلغاء الموعد
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ($appointment['status'] == 'completed'): ?>
                <div class="detail-section">
                    <div class="section-title">
                        <i class="fas fa-star"></i>
                        تقييم الموعد
                    </div>
                    <p style="margin-bottom: 1rem; color: var(--text-secondary);">
                        شاركنا رأيك في تجربتك مع الطبيب
                    </p>
                    <a href="review_appointment.php?id=<?php echo $appointment['id']; ?>" class="btn-action btn-primary">
                        <i class="fas fa-star"></i>
                        تقييم الموعد
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <script src="assets/js/script.js"></script>
</body>
</html>
