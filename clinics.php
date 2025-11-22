<?php
require_once 'includes/functions.php';

$hospital_id = isset($_GET['hospital']) ? (int)$_GET['hospital'] : 0;

if (!$hospital_id) {
    header("Location: hospitals.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    $hospital = null;
    $clinics = [];
} else {
    try {
        $stmt = $conn->prepare("SELECT * FROM hospitals WHERE id = ?");
        $stmt->execute([$hospital_id]);
        $hospital = $stmt->fetch();

        $stmt = $conn->prepare("
            SELECT c.*, s.name AS specialty_name, s.description AS specialty_description
            FROM clinics c
            LEFT JOIN specialties s ON c.specialty_id = s.id
            WHERE c.hospital_id = ?
            ORDER BY c.name
        ");
        $stmt->execute([$hospital_id]);
        $clinics = $stmt->fetchAll();
    } catch (PDOException $e) {
        $hospital = null;
        $clinics = [];
    }
}

if (!$hospital) {
    header("Location: hospitals.php");
    exit();
}

$page_title = "عيادات " . htmlspecialchars($hospital['name'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Health Tech</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
<style>
        body {
            font-family: 'Cairo', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white/90 backdrop-blur-md shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-20">
                <a href="index.php" class="text-3xl font-black text-blue-600 hover:text-blue-700 transition-colors duration-300">
                    Health Tech
                </a>

                <nav class="hidden md:flex items-center space-x-8 space-x-reverse">
                    <a href="index.php#features" class="text-gray-600 hover:text-blue-600 transition-colors duration-300">المميزات</a>
                    <a href="index.php#specialties" class="text-gray-600 hover:text-blue-600 transition-colors duration-300">التخصصات</a>
                    <a href="index.php#contact" class="text-gray-600 hover:text-blue-600 transition-colors duration-300">تواصل معنا</a>
                </nav>

                <div class="flex items-center space-x-2 space-x-reverse">
                    <?php if (is_logged_in()): ?>
                        <?php
                        $user_type = $_SESSION['user_type'] ?? '';
                        $dashboard_link = 'dashboard.php';
                        if ($user_type === 'doctor') {
                            $dashboard_link = 'doctor/index.php';
                        } elseif ($user_type === 'admin') {
                            $dashboard_link = 'admin/index.php';
                        } elseif ($user_type === 'hospital') {
                            $dashboard_link = 'hospital/index.php';
                        }
                        ?>
                        <a href="<?php echo $dashboard_link; ?>"
                           class="px-4 py-2 text-sm font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-all duration-300 shadow-md">
                            لوحة التحكم
                        </a>
                        <a href="logout.php"
                           class="px-4 py-2 text-sm font-bold text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-all duration-300">
                            تسجيل الخروج
                        </a>
                    <?php else: ?>
                        <a href="login.php"
                           class="px-5 py-2.5 text-sm font-bold text-white bg-green-500 rounded-lg hover:bg-green-600 focus:ring-4 focus:ring-green-300 transition-all duration-300 shadow-md hover:shadow-lg">
                            تسجيل الدخول
                        </a>
                        <a href="register.php"
                           class="px-5 py-2.5 text-sm font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 transition-all duration-300 shadow-md hover:shadow-lg">
                            إنشاء حساب
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <main>
        <!-- Hero -->
        <section class="relative overflow-hidden bg-gradient-to-br from-blue-600 via-blue-500 to-teal-500 text-white">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(255,255,255,0.12),_transparent_55%)]"></div>
            <div class="container mx-auto px-4 py-20 relative">
                <nav class="flex items-center gap-2 text-sm text-white/80 mb-6">
                    <a href="index.php" class="hover:text-white transition-colors">الرئيسية</a>
                    <i class="fas fa-chevron-left text-xs"></i>
                    <a href="hospitals.php" class="hover:text-white transition-colors">المستشفيات</a>
                    <i class="fas fa-chevron-left text-xs"></i>
                    <span class="text-white font-semibold"><?php echo htmlspecialchars($hospital['name']); ?></span>
                </nav>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
                    <div>
                        <span class="inline-flex items-center px-4 py-1.5 bg-white/20 text-white font-semibold rounded-full mb-4">
                            <i class="fas fa-hospital ml-2"></i>
                            مستشفى موصى به
                        </span>
                        <h1 class="text-4xl md:text-5xl font-black leading-snug mb-4">
                            عيادات <?php echo htmlspecialchars($hospital['name']); ?>
                        </h1>
                        <p class="text-lg md:text-xl text-white/90 leading-relaxed mb-6">
                            اكتشف العيادات المتاحة داخل المستشفى، اختر التخصص المناسب واحجز موعدك بكل سهولة وراحة.
                        </p>

                        <div class="space-y-4 text-white/90">
                            <div class="flex items-center gap-3">
                                <span class="flex items-center justify-center w-12 h-12 rounded-xl bg-white/15 backdrop-blur">
                                    <i class="fas fa-map-marker-alt text-xl"></i>
                                </span>
                                <div>
                                    <p class="text-sm text-white/60">العنوان</p>
                                    <p class="font-semibold"><?php echo htmlspecialchars($hospital['address']); ?></p>
                                </div>
                            </div>

                            <?php if (!empty($hospital['phone'])): ?>
                                <div class="flex items-center gap-3">
                                    <span class="flex items-center justify-center w-12 h-12 rounded-xl bg-white/15 backdrop-blur">
                                        <i class="fas fa-phone text-xl"></i>
                                    </span>
                                    <div>
                                        <p class="text-sm text-white/60">رقم الهاتف</p>
                                        <p class="font-semibold"><?php echo htmlspecialchars($hospital['phone']); ?></p>
                                    </div>
                                </div>
                        <?php endif; ?>

                            <?php if (!empty($hospital['email'])): ?>
                                <div class="flex items-center gap-3">
                                    <span class="flex items-center justify-center w-12 h-12 rounded-xl bg-white/15 backdrop-blur">
                                        <i class="fas fa-envelope text-xl"></i>
                                    </span>
                                    <div>
                                        <p class="text-sm text-white/60">البريد الإلكتروني</p>
                                        <p class="font-semibold"><?php echo htmlspecialchars($hospital['email']); ?></p>
                                    </div>
                                </div>
                        <?php endif; ?>
                        </div>
                    </div>
                    <div class="rounded-3xl bg-white/10 backdrop-blur-lg border border-white/20 p-6 md:p-8 shadow-xl flex flex-col justify-between">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <p class="text-white/80 text-sm">تقييم المستشفى</p>
                                <p class="text-4xl font-black mt-1"><?php echo number_format((float)($hospital['rating'] ?? 0), 1); ?></p>
                </div>
                            <div class="flex items-center gap-2 text-yellow-300 text-xl">
                        <?php
                                $rating = (float)($hospital['rating'] ?? 0);
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
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-white/80">
                            <div class="px-4 py-3 rounded-2xl bg-white/10 border border-white/20">
                                <p class="text-white font-semibold mb-1"><i class="fas fa-briefcase-medical ml-2"></i>نوع المستشفى</p>
                                <p><?php echo htmlspecialchars($hospital['type'] ?? 'حكومي'); ?></p>
                            </div>
                            <div class="px-4 py-3 rounded-2xl bg-white/10 border border-white/20">
                                <p class="text-white font-semibold mb-1"><i class="fas fa-clock ml-2"></i>ساعات العمل</p>
                                <p><?php echo !empty($hospital['is_24h']) ? 'مفتوح 24 ساعة' : 'ساعات عمل محددة'; ?></p>
                            </div>
                            <div class="px-4 py-3 rounded-2xl bg-white/10 border border-white/20">
                                <p class="text-white font-semibold mb-1"><i class="fas fa-ambulance ml-2"></i>خدمات الطوارئ</p>
                                <p><?php echo !empty($hospital['has_emergency']) ? 'متوفرة' : 'غير متوفرة'; ?></p>
                            </div>
                            <div class="px-4 py-3 rounded-2xl bg-white/10 border border-white/20">
                                <p class="text-white font-semibold mb-1"><i class="fas fa-shield-alt ml-2"></i>التأمين الطبي</p>
                                <p><?php echo !empty($hospital['has_insurance']) ? 'يدعم التأمين' : 'لا يدعم التأمين'; ?></p>
                            </div>
                        </div>
                        <div class="mt-6">
                            <a href="book.php?hospital=<?php echo $hospital_id; ?>" class="inline-flex items-center justify-center gap-2 w-full px-4 py-3 rounded-xl bg-white text-blue-600 font-bold hover:bg-blue-50 transition">
                                <i class="fas fa-calendar-check"></i>
                                حجز موعد سريع
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Clinics -->
        <section class="py-16 bg-gray-50" id="clinics">
            <div class="container mx-auto px-4">
                <div class="text-center mb-12">
                    <h2 class="text-3xl md:text-4xl font-black text-gray-900 mb-4">العيادات المتاحة</h2>
                    <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                        اختر العيادة المناسبة حسب التخصص، الرسوم، وسائل التواصل، وتعرّف على وصف التخصصات بالتفصيل.
                    </p>
            </div>

            <?php if (empty($clinics)): ?>
                    <div class="bg-white rounded-3xl shadow-lg p-12 text-center border border-dashed border-gray-200">
                        <div class="w-20 h-20 mx-auto rounded-full bg-blue-50 text-blue-500 flex items-center justify-center mb-6">
                            <i class="fas fa-stethoscope text-3xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-2">لا توجد عيادات مسجلة</h3>
                        <p class="text-gray-500 mb-6">
                            جاري تحديث بيانات العيادات في هذا المستشفى. يمكنك العودة لاحقًا أو تصفح مستشفيات أخرى.
                        </p>
                        <a href="hospitals.php" class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white font-semibold rounded-full hover:bg-blue-700 transition">
                            <i class="fas fa-arrow-left"></i>
                            العودة إلى قائمة المستشفيات
                        </a>
                </div>
            <?php else: ?>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <?php foreach ($clinics as $clinic): ?>
                            <div class="bg-white rounded-3xl shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100">
                                <div class="p-6 md:p-8">
                                    <div class="flex items-start justify-between gap-4 mb-6">
                                        <div>
                                            <h3 class="text-2xl font-bold text-gray-900 mb-2">
                                                <?php echo htmlspecialchars($clinic['name']); ?>
                                            </h3>
                                            <?php if (!empty($clinic['specialty_name'])): ?>
                                                <span class="inline-flex items-center gap-2 px-4 py-1.5 bg-blue-50 text-blue-600 rounded-full text-sm font-semibold">
                                                    <i class="fas fa-heartbeat"></i>
                                                    <?php echo htmlspecialchars($clinic['specialty_name']); ?>
                                                </span>
                                    <?php endif; ?>
                                </div>
                                        <div class="text-center">
                                            <div class="flex items-center justify-center gap-1 text-yellow-400 text-lg mb-1">
                                        <?php
                                                $clinic_rating = (float)($clinic['rating'] ?? 0);
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $clinic_rating) {
                                                echo '<i class="fas fa-star"></i>';
                                            } elseif ($i - $clinic_rating < 1) {
                                                echo '<i class="fas fa-star-half-alt"></i>';
                                            } else {
                                                echo '<i class="far fa-star"></i>';
                                            }
                                        }
                                        ?>
                                    </div>
                                            <span class="text-sm font-semibold text-gray-700"><?php echo number_format($clinic_rating, 1); ?></span>
                                </div>
                            </div>

                                    <?php if (!empty($clinic['description'])): ?>
                                        <p class="text-gray-600 leading-relaxed mb-6">
                                            <?php echo htmlspecialchars($clinic['description']); ?>
                                        </p>
                                <?php endif; ?>

                                    <div class="space-y-3 mb-6">
                                        <?php if (!empty($clinic['phone'])): ?>
                                            <div class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-gray-50 border border-gray-100">
                                                <span class="flex items-center justify-center w-10 h-10 rounded-xl bg-blue-100 text-blue-600">
                                            <i class="fas fa-phone"></i>
                                                </span>
                                                <span class="text-gray-700 font-semibold"><?php echo htmlspecialchars($clinic['phone']); ?></span>
                                        </div>
                                    <?php endif; ?>

                                        <?php if (!empty($clinic['email'])): ?>
                                            <div class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-gray-50 border border-gray-100">
                                                <span class="flex items-center justify-center w-10 h-10 rounded-xl bg-blue-100 text-blue-600">
                                            <i class="fas fa-envelope"></i>
                                                </span>
                                                <span class="text-gray-700 font-semibold"><?php echo htmlspecialchars($clinic['email']); ?></span>
                                        </div>
                                    <?php endif; ?>

                                        <?php if (!empty($clinic['consultation_fee'])): ?>
                                            <div class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-gray-50 border border-gray-100">
                                                <span class="flex items-center justify-center w-10 h-10 rounded-xl bg-green-100 text-green-600">
                                            <i class="fas fa-money-bill-wave"></i>
                                                </span>
                                                <span class="text-gray-700 font-semibold">رسوم الاستشارة: <?php echo number_format((float)$clinic['consultation_fee']); ?> جنيه</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                    <?php if (!empty($clinic['specialty_description'])): ?>
                                        <div class="bg-blue-50 border border-blue-100 rounded-2xl p-4 mb-6">
                                            <h4 class="text-blue-700 font-semibold mb-2">
                                                <i class="fas fa-notes-medical ml-2"></i>
                                                عن التخصص
                                            </h4>
                                            <p class="text-blue-600 leading-relaxed">
                                                <?php echo htmlspecialchars($clinic['specialty_description']); ?>
                                            </p>
                                    </div>
                                <?php endif; ?>

                                    <div class="flex flex-col md:flex-row gap-4">
                                        <a href="doctors.php?clinic=<?php echo $clinic['id']; ?>"
                                           class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition">
                                    <i class="fas fa-user-md"></i>
                                    عرض الأطباء
                                </a>
                                        <a href="book.php?clinic=<?php echo $clinic['id']; ?>"
                                           class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 bg-white text-blue-600 font-semibold rounded-xl border border-blue-100 hover:bg-blue-50 transition">
                                    <i class="fas fa-calendar-plus"></i>
                                    حجز موعد
                                </a>
                                    </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        </section>
    </main>

    <footer class="bg-gray-900 text-white">
        <div class="container mx-auto px-4 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 text-center md:text-right">
                <div>
                    <h3 class="text-2xl font-bold text-blue-400 mb-3">Health Tech</h3>
                    <p class="text-gray-400 text-sm leading-relaxed">
                        نظام متكامل لإدارة المواعيد الطبية، يوفر تجربة سلسة للمرضى والأطباء والمستشفيات.
                    </p>
                    <div class="flex justify-center md:justify-start mt-4 space-x-4 space-x-reverse text-gray-400">
                        <a href="#" class="hover:text-white"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="hover:text-white"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="hover:text-white"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="hover:text-white"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div>
                    <h4 class="font-bold text-lg mb-4">روابط سريعة</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="index.php" class="hover:text-white transition">الرئيسية</a></li>
                        <li><a href="search.php" class="hover:text-white transition">البحث عن طبيب</a></li>
                        <li><a href="hospitals.php" class="hover:text-white transition">المستشفيات</a></li>
                        <li><a href="about.php" class="hover:text-white transition">من نحن</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-lg mb-4">الخدمات</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="book.php" class="hover:text-white transition">حجز موعد</a></li>
                        <li><a href="clinics.php?hospital=<?php echo $hospital_id; ?>" class="hover:text-white transition">عيادات المستشفى</a></li>
                        <li><a href="doctors.php" class="hover:text-white transition">قائمة الأطباء</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-lg mb-4">تواصل معنا</h4>
                    <ul class="space-y-3 text-gray-400">
                        <li class="flex items-center justify-center md:justify-start gap-3">
                            <i class="fas fa-map-marker-alt text-blue-400"></i>
                            <span>دمنهور، البحيرة</span>
                        </li>
                        <li class="flex items-center justify-center md:justify-start gap-3">
                            <i class="fas fa-phone text-blue-400"></i>
                            <span>+20 123 456 7890</span>
                        </li>
                        <li class="flex items-center justify-center md:justify-start gap-3">
                            <i class="fas fa-envelope text-blue-400"></i>
                            <span>healthh.tech404@gmail.com</span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-white/10 mt-10 pt-6 text-center text-gray-500 text-sm">
                <?php echo date('Y'); ?> Health Tech. جميع الحقوق محفوظة.
            </div>
    </div>
    </footer>
</body>

</html>

    