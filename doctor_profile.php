<?php
session_start();

// Mark this page as a Tailwind CSS page to prevent old CSS from loading.
$use_tailwind = true;

require_once 'config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Get doctor ID from URL
$doctor_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($doctor_id === 0) {
    header('Location: search.php');
    exit();
}

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Fetch doctor details
$doctor = get_doctor_by_id($doctor_id);

if (!$doctor) {
    header('Location: search.php');
    exit();
}

// Get doctor's user_id (used in doctor_ratings table)
$doctor_user_id = $doctor['user_id'] ?? $doctor_id;

// Get rating statistics (use user_id, not doctor id)
$rating_stats = get_doctor_rating_stats($conn, $doctor_user_id);

// Get recent ratings (use user_id, not doctor id)
$recent_ratings = get_doctor_ratings($conn, $doctor_user_id, 5);

// Check if current user can rate (use user_id, not doctor id)
$can_rate = ['can_rate' => false, 'reason' => ''];
$user_rating = null;
if (is_logged_in()) {
    $user_id = $_SESSION['user_id'];
    $can_rate = can_user_rate_doctor($conn, $user_id, $doctor_user_id);
    $user_rating = get_user_rating_for_doctor($conn, $user_id, $doctor_user_id);
}

// Handle rating submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_rating'])) {
    if (!is_logged_in()) {
        $error_message = 'يجب تسجيل الدخول لإضافة تقييم';
    } else {
        // Make sure doctor_user_id is available
        if (!isset($doctor_user_id)) {
            $doctor_user_id = $doctor['user_id'] ?? $doctor_id;
        }

        $user_id = $_SESSION['user_id'];
        $rating = (int)$_POST['rating'];
        $review = sanitize_input($_POST['review']);
        $is_anonymous = isset($_POST['anonymous']) ? 1 : 0;

        $result = submit_doctor_rating($conn, $doctor_user_id, $user_id, $rating, $review, $is_anonymous);

        if ($result['success']) {
            $success_message = $result['message'];
            // Refresh data
            $rating_stats = get_doctor_rating_stats($conn, $doctor_user_id);
            $recent_ratings = get_doctor_ratings($conn, $doctor_user_id, 5);
            $can_rate = can_user_rate_doctor($conn, $user_id, $doctor_user_id);
            $user_rating = get_user_rating_for_doctor($conn, $user_id, $doctor_user_id);
        } else {
            $error_message = $result['message'];
        }
    }
}

$pageTitle = $doctor ? 'ملف الطبيب: ' . htmlspecialchars($doctor['full_name']) : 'خطأ';

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>

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
    <style type="text/tailwindcss">
        .prose {
            text-align: right;
        }
    </style>
</head>
<body class="font-cairo bg-slate-50">

<?php require_once 'includes/header.php'; ?>

<?php if (!$doctor): ?>
    <div class='container mx-auto px-4 py-16 text-center'>
        <h1 class='text-3xl font-bold text-red-600'>لم يتم العثور على الطبيب</h1>
        <p class='text-gray-600 mt-4'>قد يكون الرابط غير صحيح أو تم حذف الطبيب.</p>
        <a href='search.php' class='mt-6 inline-block bg-blue-600 text-white px-6 py-2 rounded-lg'>العودة للبحث</a>
    </div>
<?php else: ?>
    <main class="container mx-auto px-4 sm:px-6 lg:px-8 py-12">

        <!-- Doctor Profile Card -->
        <div class="bg-white rounded-2xl shadow-xl p-6 md:p-8 flex flex-col md:flex-row gap-8">
            <!-- Doctor Image -->
            <div class="flex-shrink-0 text-center md:text-right">
                <img src="<?php echo htmlspecialchars($doctor['image'] ?? 'assets/images/default-avatar.png'); ?>"
                     alt="صورة الطبيب <?php echo htmlspecialchars($doctor['full_name']); ?>"
                     class="w-40 h-40 rounded-full object-cover mx-auto md:mx-0 border-4 border-blue-100 shadow-lg">
            </div>

            <div class="flex-grow text-center md:text-right">
                <h1 class="text-3xl md:text-4xl font-extrabold text-gray-800"><?php echo htmlspecialchars($doctor['full_name']); ?></h1>
                <p class="text-lg font-semibold text-blue-600 mt-2"><?php echo htmlspecialchars($doctor['specialty_name']); ?></p>

                <!-- Rating -->
                <div class="flex items-center justify-center md:justify-start mt-4 text-yellow-500 space-x-1 space-x-reverse">
                    <?php
                    $avg_rating = $rating_stats['average_rating'] ?: 0;
                    $full_stars = floor($avg_rating);
                    $has_half_star = ($avg_rating - $full_stars) >= 0.5;

                    for ($i = 1; $i <= 5; $i++) {
                        if ($i <= $full_stars) {
                            echo '<i class="fas fa-star"></i>';
                        } elseif ($i == $full_stars + 1 && $has_half_star) {
                            echo '<i class="fas fa-star-half-alt"></i>';
                        } else {
                            echo '<i class="far fa-star"></i>';
                        }
                    }
                    ?>
                    <span class="text-gray-600 font-bold mr-2 text-md"><?php echo number_format($avg_rating, 1); ?></span>
                    <span class="text-gray-500 text-sm">(<?php echo $rating_stats['total_ratings']; ?> تقييم)</span>
                </div>

                <!-- Clinic Info -->
                <div class="mt-5 pt-4 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center gap-4 text-gray-600">
                    <div class="flex items-center justify-center md:justify-start">
                        <i class="fas fa-clinic-medical text-blue-500 fa-lg ml-3"></i>
                        <span class="font-semibold"><?php echo htmlspecialchars($doctor['clinic_name']); ?></span>
                    </div>
                    <div class="flex items-center justify-center md:justify-start">
                        <i class="fas fa-map-marker-alt text-blue-500 fa-lg ml-3"></i>
                        <span><?php echo htmlspecialchars($doctor['clinic_address']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Booking Section -->
            <div class="md:border-r md:border-gray-200 md:pr-8 flex-shrink-0 text-center">
                <div class="bg-slate-50 rounded-lg p-5">
                    <h3 class="text-lg font-bold text-gray-700">سعر الكشف</h3>
                    <p class="text-4xl font-black text-blue-600 my-2">
                        <?php
                        if (!empty($doctor['consultation_fee']) && is_numeric($doctor['consultation_fee'])) {
                            echo htmlspecialchars($doctor['consultation_fee']) . ' <span class="text-xl font-bold">جنيه</span>';
                        } else {
                            echo '<span class="text-2xl font-semibold">يُحدد لاحقًا</span>';
                        }
                        ?>
                    </p>
                    <a href="book_appointment.php?doctor_id=<?php echo $doctor['id']; ?>"
                       class="block w-full text-center bg-orange-500 text-white font-bold py-3 px-6 rounded-lg hover:bg-orange-600 transition transform hover:-translate-y-1 shadow-lg">
                        <i class="fas fa-calendar-check ml-2"></i>
                        احجز موعد الآن
                    </a>
                </div>
            </div>
        </div>

        <!-- Doctor Details Tabs -->
        <div class="mt-12">
            <div class="bg-white rounded-2xl shadow-xl p-6 md:p-8">
                <!-- Tabs Navigation (Future Feature) -->
                <div class="border-b border-gray-200 mb-6">
                    <nav class="-mb-px flex space-x-6 space-x-reverse" aria-label="Tabs">
                        <a href="#" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-lg text-blue-600 border-blue-500">
                            عن الطبيب
                        </a>
                        <a href="#" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-lg text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300">
                            آراء المرضى
                        </a>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">نبذة تعريفية</h2>
                    <div class="prose prose-lg max-w-none text-gray-600 leading-relaxed">
                        <p>
                            <?php echo !empty($doctor['about']) ? htmlspecialchars($doctor['about']) : 'طبيب متخصص ومؤهل يقدم أفضل الرعاية الطبية للمرضى.'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rating and Reviews Section -->
        <div class="mt-12">
            <div class="bg-white rounded-2xl shadow-xl p-6 md:p-8">
                <div class="border-b border-gray-200 mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">آراء المرضى</h2>
                    <div class="flex items-center gap-4">
                        <div class="flex items-center">
                            <?php
                            $avg_rating = $rating_stats['average_rating'] ?: 0;
                            $full_stars = floor($avg_rating);
                            $has_half_star = ($avg_rating - $full_stars) >= 0.5;

                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $full_stars) {
                                    echo '<i class="fas fa-star text-yellow-400 text-xl"></i>';
                                } elseif ($i == $full_stars + 1 && $has_half_star) {
                                    echo '<i class="fas fa-star-half-alt text-yellow-400 text-xl"></i>';
                                } else {
                                    echo '<i class="far fa-star text-yellow-400 text-xl"></i>';
                                }
                            }
                            ?>
                        </div>
                        <span class="text-2xl font-bold text-gray-800"><?php echo number_format($avg_rating, 1); ?></span>
                        <span class="text-gray-500">(<?php echo $rating_stats['total_ratings']; ?> تقييم)</span>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <?php if ($success_message): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                        <i class="fas fa-check-circle ml-2"></i>
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                        <i class="fas fa-exclamation-circle ml-2"></i>
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Rating Form -->
                    <div>
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">
                            <?php echo $user_rating ? 'تقييمك الحالي' : 'أضف تقييمك'; ?>
                        </h3>

                        <?php if (!is_logged_in()): ?>
                            <div class="bg-gray-50 p-6 rounded-lg text-center">
                                <i class="fas fa-user-circle text-gray-400 text-4xl mb-3"></i>
                                <p class="text-gray-600 mb-4">يجب تسجيل الدخول لإضافة تقييم</p>
                                <a href="login.php" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                    تسجيل الدخول
                                </a>
                            </div>
                        <?php elseif (!$can_rate['can_rate'] && !$user_rating): ?>
                            <div class="bg-yellow-50 p-6 rounded-lg">
                                <i class="fas fa-info-circle text-yellow-600 text-2xl mb-3"></i>
                                <p class="text-yellow-800"><?php echo $can_rate['reason']; ?></p>
                            </div>
                        <?php else: ?>
                            <?php if ($user_rating): ?>
                                <div class="bg-blue-50 p-6 rounded-lg mb-4">
                                    <p class="text-blue-800 font-medium mb-2">لقد قمت بتقييم هذا الدكتور مسبقاً:</p>
                                    <div class="flex items-center mb-2">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="<?php echo $i <= $user_rating['rating'] ? 'fas' : 'far'; ?> fa-star text-yellow-400"></i>
                                        <?php endfor; ?>
                                        <span class="mr-2 font-bold"><?php echo $user_rating['rating']; ?>/5</span>
                                    </div>
                                    <?php if ($user_rating['review']): ?>
                                        <p class="text-gray-700"><?php echo htmlspecialchars($user_rating['review']); ?></p>
                                    <?php endif; ?>
                                    <p class="text-sm text-gray-500 mt-2">
                                        <?php echo date('Y/m/d', strtotime($user_rating['created_at'])); ?>
                                    </p>
                                </div>
                            <?php else: ?>
                                <form method="POST" action="" class="space-y-4">
                                    <input type="hidden" name="submit_rating" value="1">

                                    <!-- Rating Stars -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">التقييم</label>
                                        <div class="flex gap-1" id="rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <button type="button"
                                                        class="star-btn text-3xl text-gray-300 hover:text-yellow-400 transition-colors"
                                                        data-rating="<?php echo $i; ?>"
                                                        onclick="setRating(<?php echo $i; ?>)">
                                                    <i class="far fa-star"></i>
                                                </button>
                                            <?php endfor; ?>
                                        </div>
                                        <input type="hidden" name="rating" id="rating-value" value="5" required>
                                    </div>

                                    <!-- Review Text -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">تعليق (اختياري)</label>
                                        <textarea name="review" rows="4"
                                                  class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                  placeholder="شاركنا رأيك في تجربتك مع الدكتور..."></textarea>
                                    </div>

                                    <!-- Anonymous Option -->
                                    <div class="flex items-center">
                                        <input type="checkbox" name="anonymous" id="anonymous" class="ml-2">
                                        <label for="anonymous" class="text-sm text-gray-700">نشر التقييم بشكل مجهول</label>
                                    </div>

                                    <button type="submit"
                                            class="w-full bg-blue-600 text-white font-medium py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors">
                                        <i class="fas fa-star ml-2"></i>
                                        إضافة التقييم
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Reviews List -->
                    <div>
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">آخر التقييمات</h3>

                        <?php if (empty($recent_ratings)): ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-comments text-4xl mb-3"></i>
                                <p>لا توجد تقييمات حتى الآن</p>
                                <p class="text-sm">كن أول من يقيّم هذا الدكتور</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4 max-h-96 overflow-y-auto">
                                <?php foreach ($recent_ratings as $rating): ?>
                                    <div class="border-b border-gray-100 pb-4 last:border-b-0">
                                        <div class="flex items-start justify-between mb-2">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center ml-3">
                                                    <i class="fas fa-user text-blue-600 text-sm"></i>
                                                </div>
                                                <div>
                                                    <p class="font-medium text-gray-900">
                                                        <?php echo $rating['is_anonymous'] ? 'مريض مجهول' : htmlspecialchars($rating['user_name'] ?? 'مريض'); ?>
                                                    </p>
                                                    <div class="flex items-center">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="<?php echo $i <= $rating['rating'] ? 'fas' : 'far'; ?> fa-star text-yellow-400 text-sm"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <span class="text-sm text-gray-500">
                                                <?php echo date('Y/m/d', strtotime($rating['created_at'])); ?>
                                            </span>
                                        </div>

                                        <?php if ($rating['review']): ?>
                                            <p class="text-gray-700 pr-11"><?php echo htmlspecialchars($rating['review']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Rating Breakdown -->
                <?php if ($rating_stats['total_ratings'] > 0): ?>
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">تفاصيل التقييمات</h3>
                        <div class="space-y-2">
                            <?php
                            $stars = [5, 4, 3, 2, 1];
                            foreach ($stars as $star):
                                $count = $rating_stats['five_star'];
                                if ($star == 4) $count = $rating_stats['four_star'];
                                elseif ($star == 3) $count = $rating_stats['three_star'];
                                elseif ($star == 2) $count = $rating_stats['two_star'];
                                elseif ($star == 1) $count = $rating_stats['one_star'];

                                $percentage = $rating_stats['total_ratings'] > 0 ? ($count / $rating_stats['total_ratings']) * 100 : 0;
                            ?>
                                <div class="flex items-center gap-3">
                                    <span class="text-sm text-gray-600 w-12"><?php echo $star; ?> نجوم</span>
                                    <div class="flex-1 bg-gray-200 rounded-full h-2">
                                        <div class="bg-yellow-400 h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                    <span class="text-sm text-gray-600 w-12 text-left"><?php echo $count; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </main>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>

<!-- JavaScript for Rating Stars -->
<script>
function setRating(rating) {
    // Update hidden input
    document.getElementById('rating-value').value = rating;

    // Update star display
    const stars = document.querySelectorAll('.star-btn i');
    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.remove('far');
            star.classList.add('fas');
            star.parentElement.classList.add('text-yellow-400');
            star.parentElement.classList.remove('text-gray-300');
        } else {
            star.classList.remove('fas');
            star.classList.add('far');
            star.parentElement.classList.remove('text-yellow-400');
            star.parentElement.classList.add('text-gray-300');
        }
    });
}

// Initialize with 5 stars
document.addEventListener('DOMContentLoaded', function() {
    setRating(5);
});
</script>

</body>
</html>
