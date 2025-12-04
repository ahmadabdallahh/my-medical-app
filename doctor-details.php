<?php
require_once 'includes/functions.php';

// التحقق من وجود معرف الطبيب
$doctor_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$doctor_id) {
    header("Location: hospitals.php");
    exit();
}

// الحصول على معلومات الطبيب
$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    $doctor = null;
    $clinic = null;
    $hospital = null;
    $schedule = [];
} else {
    try {
        // الحصول على معلومات الطبيب والعيادة والمستشفى
        $stmt = $conn->prepare("
            SELECT d.*, c.name as clinic_name, c.description as clinic_description,
                   h.name as hospital_name, h.address as hospital_address,
                   s.name as specialty_name, s.description as specialty_description
            FROM doctors d
            LEFT JOIN clinics c ON d.clinic_id = c.id
            LEFT JOIN hospitals h ON c.hospital_id = h.id
            LEFT JOIN specialties s ON d.specialty_id = s.id
            WHERE d.id = ?
        ");
        $stmt->execute([$doctor_id]);
        $doctor = $stmt->fetch();

        if ($doctor) {
            $clinic = [
                'name' => $doctor['clinic_name'],
                'description' => $doctor['clinic_description'],
                'id' => $doctor['clinic_id']
            ];
            $hospital = [
                'name' => $doctor['hospital_name'],
                'address' => $doctor['hospital_address']
            ];
        }

        // الحصول على جدول عمل الطبيب
        $stmt = $conn->prepare("
            SELECT * FROM doctor_schedules
            WHERE doctor_id = ?
            ORDER BY FIELD(day_of_week, 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday')
        ");
        $stmt->execute([$doctor_id]);
        $schedule = $stmt->fetchAll();
    } catch (PDOException $e) {
        $doctor = null;
        $clinic = null;
        $hospital = null;
        $schedule = [];
    }
}

// إذا لم يتم العثور على الطبيب
if (!$doctor) {
    header("Location: hospitals.php");
    exit();
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
    <title>د. <?php echo htmlspecialchars($doctor['full_name']); ?> - نظام حجز المواعيد الطبية</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts (Cairo) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
</head>
<body class="font-cairo bg-gray-50">

<?php require_once 'includes/header.php'; ?>

<main class="bg-gray-50 py-12">
    <div class="container mx-auto px-4">
        <!-- Breadcrumb -->
        <nav class="mb-6">
            <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
                <li><a href="index.php" class="hover:text-blue-600 transition">الرئيسية</a></li>
                <li><i class="fas fa-chevron-left text-xs"></i></li>
                <li><a href="hospitals.php" class="hover:text-blue-600 transition">المستشفيات</a></li>
                <li><i class="fas fa-chevron-left text-xs"></i></li>
                <li><a href="clinics.php?hospital=<?php echo $doctor['hospital_id']; ?>" class="hover:text-blue-600 transition">عيادات <?php echo htmlspecialchars($hospital['name']); ?></a></li>
                <li><i class="fas fa-chevron-left text-xs"></i></li>
                <li><a href="doctors.php?clinic=<?php echo $doctor['clinic_id']; ?>" class="hover:text-blue-600 transition">أطباء <?php echo htmlspecialchars($clinic['name']); ?></a></li>
                <li><i class="fas fa-chevron-left text-xs"></i></li>
                <li class="text-gray-800 font-semibold">د. <?php echo htmlspecialchars($doctor['full_name']); ?></li>
            </ol>
        </nav>

        <!-- Doctor Profile -->
        <div class="bg-white p-6 md:p-8 rounded-2xl shadow-xl mb-8">
            <div class="flex flex-col md:flex-row gap-6 mb-6">
                <div class="flex-shrink-0">
                    <div class="w-32 h-32 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center text-white text-4xl font-bold overflow-hidden">
                        <?php if (isset($doctor['image']) && $doctor['image']): ?>
                            <img src="<?php echo htmlspecialchars($doctor['image']); ?>" alt="<?php echo htmlspecialchars($doctor['full_name']); ?>" class="w-full h-full rounded-full object-cover">
                        <?php else: ?>
                            <i class="fas fa-user-md"></i>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex-1">
                    <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">د. <?php echo htmlspecialchars($doctor['full_name']); ?></h1>
                    <div class="flex flex-wrap gap-2 mb-4">
                        <?php if (isset($doctor['specialty_name']) && $doctor['specialty_name']): ?>
                            <span class="inline-block bg-blue-100 text-blue-800 px-4 py-2 rounded-full text-sm font-semibold">
                                <?php echo htmlspecialchars($doctor['specialty_name']); ?>
                            </span>
                        <?php endif; ?>
                        <?php if (isset($doctor['experience_years']) && $doctor['experience_years']): ?>
                            <span class="inline-block bg-gray-100 text-gray-700 px-4 py-2 rounded-full text-sm font-semibold">
                                <?php echo $doctor['experience_years']; ?> سنوات خبرة
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="space-y-2 mb-4">
                        <p class="flex items-center text-gray-600">
                            <i class="fas fa-hospital text-blue-600 ml-2"></i>
                            <?php echo htmlspecialchars($hospital['name']); ?>
                        </p>
                        <p class="flex items-center text-gray-600">
                            <i class="fas fa-stethoscope text-blue-600 ml-2"></i>
                            <?php echo htmlspecialchars($clinic['name']); ?>
                        </p>
                        <p class="flex items-center text-gray-600">
                            <i class="fas fa-map-marker-alt text-blue-600 ml-2"></i>
                            <?php echo htmlspecialchars($hospital['address']); ?>
                        </p>
                    </div>
                </div>

                <div class="flex flex-col items-center bg-gray-50 p-6 rounded-xl min-w-[140px]">
                    <div class="flex items-center gap-1 text-yellow-400 mb-2 text-xl">
                        <?php
                        $rating = isset($doctor['rating']) ? $doctor['rating'] : 0;
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $rating) {
                                echo '<i class="fas fa-star"></i>';
                            } elseif ($i - $rating < 1) {
                                echo '<i class="fas fa-star-half-alt"></i>';
                            } else {
                                echo '<i class="far fa-star"></i>';
                            }
                        }
                        ?>
                    </div>
                    <span class="text-3xl font-bold text-gray-800 mb-1"><?php echo number_format($rating, 1); ?></span>
                    <span class="text-sm text-gray-600">تقييم المرضى</span>
                </div>
            </div>

            <div class="flex gap-4">
                <a href="book_appointment.php?doctor_id=<?php echo $doctor['id']; ?>" class="flex-1 bg-green-600 text-white font-semibold py-3 px-6 rounded-lg hover:bg-green-700 transition text-center">
                    <i class="fas fa-calendar-plus ml-2"></i>
                    حجز موعد
                </a>
                <?php if (isset($doctor['phone']) && $doctor['phone']): ?>
                    <a href="tel:<?php echo htmlspecialchars($doctor['phone']); ?>" class="flex-1 bg-blue-600 text-white font-semibold py-3 px-6 rounded-lg hover:bg-blue-700 transition text-center">
                        <i class="fas fa-phone ml-2"></i>
                        اتصل الآن
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Education & Experience -->
            <div class="bg-white p-6 rounded-2xl shadow-xl">
                <h3 class="text-2xl font-bold text-gray-800 mb-6">المؤهلات والخبرة</h3>
                <div class="space-y-4">
                    <?php if (isset($doctor['education']) && $doctor['education']): ?>
                        <div class="bg-gray-50 p-4 rounded-xl border-r-4 border-blue-500">
                            <h4 class="text-sm font-bold text-blue-600 mb-2">المؤهلات العلمية:</h4>
                            <p class="text-gray-700 leading-relaxed"><?php echo htmlspecialchars($doctor['education']); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($doctor['specialty_description']) && $doctor['specialty_description']): ?>
                        <div class="bg-gray-50 p-4 rounded-xl border-r-4 border-green-500">
                            <h4 class="text-sm font-bold text-green-600 mb-2">التخصص:</h4>
                            <p class="text-gray-700 leading-relaxed"><?php echo htmlspecialchars($doctor['specialty_description']); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="bg-white p-6 rounded-2xl shadow-xl">
                <h3 class="text-2xl font-bold text-gray-800 mb-6">معلومات التواصل</h3>
                <div class="space-y-3">
                    <?php if (isset($doctor['phone']) && $doctor['phone']): ?>
                        <div class="flex items-center gap-3 bg-gray-50 p-4 rounded-lg">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-phone text-blue-600"></i>
                            </div>
                            <div>
                                <span class="block text-xs text-gray-500 mb-1">الهاتف:</span>
                                <span class="block text-gray-800 font-semibold"><?php echo htmlspecialchars($doctor['phone']); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($doctor['email']) && $doctor['email']): ?>
                        <div class="flex items-center gap-3 bg-gray-50 p-4 rounded-lg">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-envelope text-blue-600"></i>
                            </div>
                            <div>
                                <span class="block text-xs text-gray-500 mb-1">البريد الإلكتروني:</span>
                                <span class="block text-gray-800 font-semibold"><?php echo htmlspecialchars($doctor['email']); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Working Schedule -->
            <div class="bg-white p-6 rounded-2xl shadow-xl">
                <h3 class="text-2xl font-bold text-gray-800 mb-6">جدول العمل</h3>
                <?php if (empty($schedule)): ?>
                    <p class="text-gray-500 text-center py-8">لم يتم تحديد جدول عمل بعد</p>
                <?php else: ?>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <?php foreach ($schedule as $day): ?>
                            <div class="bg-gray-50 p-4 rounded-lg text-center border border-gray-200">
                                <div class="font-bold text-gray-800 mb-2"><?php echo $days_arabic[$day['day_of_week']]; ?></div>
                                <div class="text-sm text-gray-600">
                                    <?php echo date('H:i', strtotime($day['start_time'])); ?> -
                                    <?php echo date('H:i', strtotime($day['end_time'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Clinic Information -->
            <div class="bg-white p-6 rounded-2xl shadow-xl">
                <h3 class="text-2xl font-bold text-gray-800 mb-6">معلومات العيادة</h3>
                <div class="space-y-4">
                    <h4 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($clinic['name']); ?></h4>
                    <?php if (isset($clinic['description']) && $clinic['description']): ?>
                        <p class="text-gray-700 leading-relaxed"><?php echo htmlspecialchars($clinic['description']); ?></p>
                    <?php endif; ?>
                    <div class="space-y-2 pt-4 border-t border-gray-200">
                        <p class="flex items-center text-gray-600">
                            <i class="fas fa-hospital text-blue-600 ml-2"></i>
                            <?php echo htmlspecialchars($hospital['name']); ?>
                        </p>
                        <p class="flex items-center text-gray-600">
                            <i class="fas fa-map-marker-alt text-blue-600 ml-2"></i>
                            <?php echo htmlspecialchars($hospital['address']); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Book Appointment Section -->
        <div class="bg-white p-6 md:p-8 rounded-2xl shadow-xl">
            <div class="text-center mb-8">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-2">حجز موعد مع د. <?php echo htmlspecialchars($doctor['full_name']); ?></h2>
                <p class="text-gray-600 text-lg">احجز موعدك الطبي بسهولة وأمان</p>
            </div>

            <div class="flex flex-col md:flex-row gap-4 max-w-2xl mx-auto">
                <a href="book_appointment.php?doctor_id=<?php echo $doctor['id']; ?>" class="flex-1 bg-green-600 text-white font-bold py-4 px-6 rounded-lg hover:bg-green-700 transition text-center text-lg">
                    <i class="fas fa-calendar-plus ml-2"></i>
                    حجز موعد جديد
                </a>

                <?php if (is_logged_in()): ?>
                    <a href="appointments.php" class="flex-1 bg-blue-600 text-white font-bold py-4 px-6 rounded-lg hover:bg-blue-700 transition text-center text-lg">
                        <i class="fas fa-list ml-2"></i>
                        عرض مواعيدي
                    </a>
                <?php else: ?>
                    <a href="login.php" class="flex-1 bg-gray-600 text-white font-bold py-4 px-6 rounded-lg hover:bg-gray-700 transition text-center text-lg">
                        <i class="fas fa-sign-in-alt ml-2"></i>
                        تسجيل الدخول لحجز موعد
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>

</body>
</html>
