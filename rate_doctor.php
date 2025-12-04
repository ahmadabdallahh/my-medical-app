<?php
session_start();
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'config/database.php';

// Ensure user is logged in as a patient
if (!is_logged_in() || $_SESSION['user_type'] !== 'patient') {
    header('Location: login.php');
    exit();
}

$appointment_id = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : 0;
if (!$appointment_id) {
    header('Location: patient/appointments.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Verify appointment
$stmt = $conn->prepare("
    SELECT a.*, d.full_name as doctor_name, d.id as doctor_id, d.user_id as doctor_user_id, c.name as clinic_name
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    JOIN clinics c ON a.clinic_id = c.id
    WHERE a.id = ? AND a.user_id = ? AND a.status = 'completed'
");
$stmt->execute([$appointment_id, $_SESSION['user_id']]);
$appointment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$appointment) {
    // Appointment not found or not eligible for rating
    $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'لا يمكن تقييم هذا الموعد. يجب أن يكون الموعد مكتملاً.'];
    header('Location: patient/appointments.php');
    exit();
}

// Check if already rated (optional, but good practice if we want unique ratings per appointment)
// Since we don't have appointment_id in ratings table, we can just allow adding a new rating
// or check if user rated this doctor recently. For now, let's allow it.

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rating = (int)$_POST['rating'];
    $review = trim($_POST['review']);
    $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;

    if ($rating < 1 || $rating > 5) {
        $error = 'الرجاء اختيار تقييم صحيح (1-5 نجوم)';
    } else {
        try {
            $conn->beginTransaction();

            // Insert Rating - Use doctor_user_id because doctor_ratings.doctor_id references users.id
            $stmt = $conn->prepare("INSERT INTO doctor_ratings (doctor_id, user_id, rating, review, is_anonymous) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$appointment['doctor_user_id'], $_SESSION['user_id'], $rating, $review, $is_anonymous]);

            // Update Doctor Average Rating
            // First, get new stats
            $stmt = $conn->prepare("SELECT COUNT(*) as total, AVG(rating) as average FROM doctor_ratings WHERE doctor_id = ?");
            $stmt->execute([$appointment['doctor_user_id']]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            $new_total = $stats['total'];
            $new_avg = $stats['average'];

            // Update Doctor Table - Use doctor_id (doctors.id) for WHERE clause
            $stmt = $conn->prepare("UPDATE doctors SET calculated_rating = ?, total_ratings = ? WHERE id = ?");
            $stmt->execute([$new_avg, $new_total, $appointment['doctor_id']]);

            $conn->commit();

            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'تم إرسال تقييمك بنجاح. شكراً لك!'];
            header('Location: patient/appointments.php');
            exit();

        } catch (PDOException $e) {
            $conn->rollBack();
            $error = 'حدث خطأ أثناء حفظ التقييم: ' . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقييم الطبيب - <?php echo htmlspecialchars($appointment['doctor_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
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
        .star-rating input { display: none; }
        .star-rating label { cursor: pointer; color: #ddd; font-size: 2rem; transition: color 0.2s; }
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label { color: #fbbf24; }
        .star-rating { direction: ltr; display: inline-flex; }
    </style>
</head>
<body class="font-cairo bg-gray-50">
    <?php require_once 'includes/header.php'; ?>

    <div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md mx-auto bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-blue-600 px-6 py-4">
                <h2 class="text-xl font-bold text-white text-center">تقييم تجربتك</h2>
            </div>
            
            <div class="p-6">
                <div class="text-center mb-6">
                    <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-user-md text-3xl text-blue-600"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">د. <?php echo htmlspecialchars($appointment['doctor_name']); ?></h3>
                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($appointment['clinic_name']); ?></p>
                    <p class="text-xs text-gray-400 mt-1"><?php echo date('Y/m/d', strtotime($appointment['appointment_date'])); ?></p>
                </div>

                <?php if ($error): ?>
                    <div class="bg-red-100 border-r-4 border-red-500 text-red-700 p-4 mb-4 rounded text-sm">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div class="flex flex-col items-center">
                        <label class="block text-sm font-medium text-gray-700 mb-2">كيف كانت تجربتك؟</label>
                        <div class="star-rating">
                            <input type="radio" id="star5" name="rating" value="5" required /><label for="star5" title="ممتاز"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star4" name="rating" value="4" /><label for="star4" title="جيد جداً"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star3" name="rating" value="3" /><label for="star3" title="جيد"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star2" name="rating" value="2" /><label for="star2" title="مقبول"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star1" name="rating" value="1" /><label for="star1" title="سيء"><i class="fas fa-star"></i></label>
                        </div>
                    </div>

                    <div>
                        <label for="review" class="block text-sm font-medium text-gray-700 mb-2">ملاحظاتك (اختياري)</label>
                        <textarea id="review" name="review" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="اكتب رأيك هنا..."></textarea>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" id="is_anonymous" name="is_anonymous" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_anonymous" class="mr-2 block text-sm text-gray-900">
                            إخفاء هويتي في التقييم
                        </label>
                    </div>

                    <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        إرسال التقييم
                    </button>
                    
                    <a href="patient/appointments.php" class="block text-center text-sm text-gray-500 hover:text-gray-700">
                        إلغاء وعودة
                    </a>
                </form>
            </div>
        </div>
    </div>

    <?php require_once 'includes/footer.php'; ?>
</body>
</html>
