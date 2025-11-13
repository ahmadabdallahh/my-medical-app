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
    WHERE a.id = ? AND a.user_id = ? AND a.status = 'completed'
");
$stmt->execute([$appointment_id, $user['id']]);
$appointment = $stmt->fetch();

if (!$appointment) {
    header("Location: appointments.php?error=invalid_appointment");
    exit();
}

// التحقق من وجود تقييم سابق
$stmt = $conn->prepare("SELECT * FROM appointment_reviews WHERE appointment_id = ? AND user_id = ?");
$stmt->execute([$appointment_id, $user['id']]);
$existing_review = $stmt->fetch();

// معالجة إرسال التقييم
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rating = (int)$_POST['rating'];
    $comment = sanitize_input($_POST['comment']);

    if ($rating < 1 || $rating > 5) {
        $error = 'يرجى اختيار تقييم صحيح';
    } else {
        if ($existing_review) {
            // تحديث التقييم الموجود
            if (add_appointment_review($appointment_id, $user['id'], $rating, $comment)) {
                $success = 'تم تحديث تقييمك بنجاح!';
                $existing_review['rating'] = $rating;
                $existing_review['comment'] = $comment;
            } else {
                $error = 'حدث خطأ أثناء تحديث التقييم';
            }
        } else {
            // إضافة تقييم جديد
            if (add_appointment_review($appointment_id, $user['id'], $rating, $comment)) {
                $success = 'تم إرسال تقييمك بنجاح!';
                $existing_review = [
                    'rating' => $rating,
                    'comment' => $comment,
                    'created_at' => date('Y-m-d H:i:s')
                ];
            } else {
                $error = 'حدث خطأ أثناء إرسال التقييم';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقييم الموعد - نظام حجز المواعيد الطبية</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .review-page {
            padding-top: 80px;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);
        }
        
        .review-container {
            max-width: 800px;
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
        
        .appointment-info {
            background: var(--bg-primary);
            border-radius: var(--radius-xl);
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            margin-bottom: 2rem;
            border-left: 4px solid var(--success);
        }
        
        .appointment-info h3 {
            color: var(--success);
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
        
        .review-form {
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
        
        .rating-section {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .rating-stars {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .star {
            font-size: 2rem;
            color: #e5e7eb;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .star:hover,
        .star.active {
            color: #fbbf24;
        }
        
        .star.filled {
            color: #fbbf24;
        }
        
        .rating-text {
            font-size: 1.1rem;
            color: var(--text-secondary);
            margin-bottom: 1rem;
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
        
        .form-textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            font-size: 1rem;
            transition: var(--transition);
            background: var(--bg-secondary);
            color: var(--text-primary);
            resize: vertical;
            min-height: 120px;
        }
        
        .form-textarea:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .btn-submit {
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
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
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
        
        .existing-review {
            background: var(--bg-secondary);
            padding: 1.5rem;
            border-radius: var(--radius);
            margin-bottom: 2rem;
            border-left: 4px solid var(--info);
        }
        
        .existing-review h4 {
            color: var(--info);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .review-rating {
            margin-bottom: 1rem;
        }
        
        .review-rating i {
            color: #fbbf24;
            font-size: 1.2rem;
        }
        
        .review-comment {
            color: var(--text-secondary);
            line-height: 1.6;
        }
        
        @media (max-width: 768px) {
            .appointment-details {
                grid-template-columns: 1fr;
            }
            
            .rating-stars {
                gap: 0.25rem;
            }
            
            .star {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <!-- Review Page -->
    <section class="review-page">
        <div class="review-container">
            <!-- Page Header -->
            <div class="page-header">
                <h1>تقييم الموعد</h1>
                <p>شاركنا تجربتك مع الطبيب</p>
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

            <!-- Appointment Information -->
            <div class="appointment-info">
                <h3><i class="fas fa-calendar-check"></i> تفاصيل الموعد المكتمل</h3>
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
                            <div style="font-weight: 600; color: var(--text-primary);">تاريخ الموعد</div>
                            <div><?php echo format_date_arabic($appointment['appointment_date']); ?></div>
                        </div>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-clock"></i>
                        <div>
                            <div style="font-weight: 600; color: var(--text-primary);">وقت الموعد</div>
                            <div><?php echo format_time_arabic($appointment['appointment_time']); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Existing Review -->
            <?php if ($existing_review): ?>
                <div class="existing-review">
                    <h4><i class="fas fa-star"></i> تقييمك الحالي</h4>
                    <div class="review-rating">
                        <?php
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $existing_review['rating']) {
                                echo '<i class="fas fa-star"></i>';
                            } else {
                                echo '<i class="far fa-star"></i>';
                            }
                        }
                        ?>
                        <span style="margin-right: 0.5rem; color: var(--text-primary);"><?php echo $existing_review['rating']; ?>/5</span>
                    </div>
                    <?php if (!empty($existing_review['comment'])): ?>
                        <div class="review-comment"><?php echo htmlspecialchars($existing_review['comment']); ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Review Form -->
            <div class="review-form">
                <form method="POST" id="reviewForm">
                    <!-- Rating Section -->
                    <div class="form-section">
                        <h3><i class="fas fa-star"></i> التقييم</h3>
                        <div class="rating-section">
                            <div class="rating-stars" id="ratingStars">
                                <i class="star" data-rating="1"></i>
                                <i class="star" data-rating="2"></i>
                                <i class="star" data-rating="3"></i>
                                <i class="star" data-rating="4"></i>
                                <i class="star" data-rating="5"></i>
                            </div>
                            <div class="rating-text" id="ratingText">اختر تقييمك</div>
                            <input type="hidden" name="rating" id="selectedRating" value="<?php echo $existing_review['rating'] ?? ''; ?>" required>
                        </div>
                    </div>

                    <!-- Comment Section -->
                    <div class="form-section">
                        <h3><i class="fas fa-comment"></i> التعليق (اختياري)</h3>
                        <div class="form-group">
                            <label class="form-label">شاركنا تجربتك مع الطبيب:</label>
                            <textarea name="comment" class="form-textarea" 
                                      placeholder="اكتب تعليقك هنا..."><?php echo htmlspecialchars($existing_review['comment'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i>
                        <?php echo $existing_review ? 'تحديث التقييم' : 'إرسال التقييم'; ?>
                    </button>
                </form>
            </div>
        </div>
    </section>

    <script src="assets/js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const stars = document.querySelectorAll('.star');
            const ratingText = document.getElementById('ratingText');
            const selectedRating = document.getElementById('selectedRating');
            const form = document.getElementById('reviewForm');
            
            // Set initial rating if exists
            const initialRating = <?php echo $existing_review['rating'] ?? 0; ?>;
            if (initialRating > 0) {
                updateStars(initialRating);
                updateRatingText(initialRating);
            }
            
            // Star click events
            stars.forEach(star => {
                star.addEventListener('click', function() {
                    const rating = this.dataset.rating;
                    updateStars(rating);
                    updateRatingText(rating);
                    selectedRating.value = rating;
                });
                
                star.addEventListener('mouseenter', function() {
                    const rating = this.dataset.rating;
                    updateStars(rating);
                    updateRatingText(rating);
                });
            });
            
            // Mouse leave event for stars container
            document.getElementById('ratingStars').addEventListener('mouseleave', function() {
                const currentRating = selectedRating.value || initialRating;
                updateStars(currentRating);
                updateRatingText(currentRating);
            });
            
            function updateStars(rating) {
                stars.forEach((star, index) => {
                    if (index < rating) {
                        star.classList.add('filled');
                        star.classList.remove('far');
                        star.classList.add('fas');
                    } else {
                        star.classList.remove('filled');
                        star.classList.remove('fas');
                        star.classList.add('far');
                    }
                });
            }
            
            function updateRatingText(rating) {
                const texts = {
                    1: 'سيء جداً',
                    2: 'سيء',
                    3: 'جيد',
                    4: 'جيد جداً',
                    5: 'ممتاز'
                };
                ratingText.textContent = texts[rating] || 'اختر تقييمك';
            }
            
            // Form validation
            form.addEventListener('submit', function(e) {
                if (!selectedRating.value) {
                    e.preventDefault();
                    alert('يرجى اختيار تقييم');
                }
            });
        });
    </script>
</body>
</html> 