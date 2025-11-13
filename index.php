<?php
session_start();
require_once 'config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Redirect logged-in users to their appropriate dashboards
if (is_logged_in()) {
    $user_type = $_SESSION['user_type'] ?? '';

    switch ($user_type) {
        case 'admin':
            header('Location: admin/index.php');
            exit();
        case 'doctor':
            header('Location: doctor/index.php');
            exit();
        case 'patient':
            header('Location: dashboard.php');
            exit();
        case 'hospital':
            header('Location: hospital/index.php');
            exit();
    }
}

$db = new Database();
$conn = $db->getConnection();

$hospitals = get_all_hospitals($conn);
$specialties = get_all_specialties($conn);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Tech - حجز المواعيد الطبية</title>

    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Fonts (Cairo) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">

    <!-- Font Awesome for Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        /* Apply Cairo font to the entire page */
        body {
            font-family: 'Cairo', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50">

    <!-- Sticky Navbar -->
    <header class="bg-white/80 backdrop-blur-md shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-20">
                <!-- Logo -->
                <a href="index.php"
                    class="text-3xl font-black text-blue-600 hover:text-blue-700 transition-colors duration-300">
                    Health Tech
                </a>

                <!-- Centered Nav Links (for Desktop) -->
                <nav class="hidden md:flex items-center space-x-8 space-x-reverse">
                    <a href="#features"
                        class="text-gray-600 hover:text-blue-600 transition-colors duration-300">المميزات</a>
                    <a href="#specialties"
                        class="text-gray-600 hover:text-blue-600 transition-colors duration-300">التخصصات</a>
                    <a href="#contact" class="text-gray-600 hover:text-blue-600 transition-colors duration-300">تواصل
                        معنا</a>
                </nav>

                <!-- Action Buttons -->
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
                            class="px-4 py-2 text-sm font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-all duration-300 shadow-md">لوحة
                            التحكم</a>
                        <a href="logout.php"
                            class="px-4 py-2 text-sm font-bold text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-all duration-300">تسجيل
                            الخروج</a>
                    <?php else: ?>
                        <a href="login.php"
                            class="px-5 py-2.5 text-sm font-bold text-white bg-green-500 rounded-lg hover:bg-green-600 focus:ring-4 focus:ring-green-300 transition-all duration-300 shadow-md hover:shadow-lg">تسجيل
                            الدخول</a>
                        <a href="register.php"
                            class="px-5 py-2.5 text-sm font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 transition-all duration-300 shadow-md hover:shadow-lg">إنشاء
                            حساب</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        <!-- Hero Section -->
        <section
            class="relative h-screen flex items-center justify-center text-center bg-gradient-to-br from-teal-400 to-blue-600 text-white p-4">
            <div class="z-10 flex flex-col items-center">
                <h1 class="text-4xl md:text-6xl font-black leading-tight mb-4">
                    أهلاً بك! احجز موعدك الطبي بكل سهولة
                </h1>
                <p class="text-lg md:text-xl text-white/80 mb-8 max-w-3xl">
                    نظام Health Tech هو بوابتك الأولى للوصول إلى أفضل الأطباء والمستشفيات. ابحث، قارن، واحجز موعدك في
                    دقائق معدودة.
                </p>

                <!-- Search Bar -->
                <div class="w-full max-w-2xl">
                    <form action="search.php" method="GET" class="relative" dir="rtl">
                        <input type="text" name="query" placeholder="ابحث عن طبيب، تخصص، أو مستشفى..."
                            class="w-full h-16 pr-16 pl-4 text-lg text-gray-800 bg-white rounded-full shadow-2xl focus:outline-none focus:ring-4 focus:ring-blue-300 transition-shadow duration-300 text-right placeholder-gray-400" />
                        <button type="submit"
                            class="absolute top-0 right-0 h-16 w-16 flex items-center justify-center text-white bg-blue-600 rounded-full hover:bg-blue-700 transition-colors duration-300 shadow-md"
                            aria-label="بدء البحث">
                            <i class="fas fa-search text-xl"></i>
                        </button>
                    </form>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="py-20 bg-gray-50">
            <div class="container mx-auto px-4">
                <div class="text-center mb-16">
                    <h2 class="text-4xl md:text-5xl font-black text-gray-900">لماذا تختار <span
                            class="text-blue-600">Health Tech</span>؟</h2>
                    <p class="text-lg text-gray-600 mt-4 max-w-2xl mx-auto">نحن نقدم لك تجربة متكاملة وسلسة للعثور على
                        أفضل الأطباء وحجز المواعيد بكل سهولة.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
                    <!-- Feature 1 -->
                    <div
                        class="bg-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transition-shadow duration-300 transform hover:-translate-y-2">
                        <div
                            class="bg-blue-100 text-blue-600 w-16 h-16 rounded-full flex items-center justify-center mb-6">
                            <i class="fas fa-search-plus fa-2x"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-3">بحث متقدم وذكي</h3>
                        <p class="text-gray-600">ابحث عن الطبيب المناسب حسب التخصص، الموقع، وحتى اسم المستشفى. نتائج
                            دقيقة في ثوانٍ.</p>
                    </div>
                    <!-- Feature 2 -->
                    <div
                        class="bg-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transition-shadow duration-300 transform hover:-translate-y-2">
                        <div
                            class="bg-green-100 text-green-600 w-16 h-16 rounded-full flex items-center justify-center mb-6">
                            <i class="fas fa-calendar-check fa-2x"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-3">حجز فوري ومؤكد</h3>
                        <p class="text-gray-600">اختر الموعد المناسب لك من جدول الطبيب مباشرة واحصل على تأكيد فوري
                            لحجزك.</p>
                    </div>
                    <!-- Feature 3 -->
                    <div
                        class="bg-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transition-shadow duration-300 transform hover:-translate-y-2">
                        <div
                            class="bg-orange-100 text-orange-600 w-16 h-16 rounded-full flex items-center justify-center mb-6">
                            <i class="fas fa-user-shield fa-2x"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-3">ملفات شخصية موثوقة</h3>
                        <p class="text-gray-600">اطلع على تقييمات المرضى الآخرين، خبرات الطبيب، والشهادات قبل اتخاذ
                            قرارك.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Specialties Section -->
        <section id="specialties" class="py-20">
            <div class="container mx-auto px-4 text-center">
                <h2 class="text-4xl font-black text-gray-800 mb-4">ابحث حسب التخصص</h2>
                <p class="text-lg text-gray-600 mb-12 max-w-2xl mx-auto">نغطي كافة التخصصات الطبية لمساعدتك في العثور
                    على الطبيب المناسب لحالتك الصحية.</p>

                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <?php
                    $specialty_icons = [
                        'fas fa-tooth',
                        'fas fa-heartbeat',
                        'fas fa-brain',
                        'fas fa-baby',
                        'fas fa-bone',
                        'fas fa-allergies',
                        'fas fa-eye',
                        'fas fa-user-md'
                    ];
                    $i = 0;
                    foreach (array_slice($specialties, 0, 8) as $specialty):
                        ?>
                        <a href="search.php?specialty=<?php echo $specialty['id']; ?>"
                            class="block bg-gray-50 p-6 rounded-xl shadow-md hover:shadow-xl hover:bg-blue-50 hover:-translate-y-2 transition-all duration-300 group">
                            <div
                                class="text-5xl text-blue-600 mb-4 transition-transform duration-300 group-hover:scale-110">
                                <i class="<?php echo $specialty_icons[$i++ % count($specialty_icons)]; ?>"></i>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($specialty['name']); ?>
                            </h3>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Call to Action Section -->
        <section id="cta" class="bg-blue-600">
            <div class="container mx-auto px-4 py-20 text-center">
                <h2 class="text-4xl md:text-5xl font-black text-white">جاهز لبدء رحلتك نحو صحة أفضل؟</h2>
                <p class="text-lg text-blue-100 mt-4 max-w-2xl mx-auto">انضم إلى آلاف المستخدمين الذين يثقون في Health
                    Tech للعثور على الرعاية الصحية التي يستحقونها، أو ساهم بخبرتك وانضم إلينا كطبيب.</p>
                <div class="mt-10 flex flex-col sm:flex-row justify-center items-center gap-4">
                    <a href="search.php"
                        class="w-full sm:w-auto bg-white text-blue-600 font-bold text-lg px-8 py-4 rounded-lg hover:bg-blue-50 transition-transform transform hover:scale-105 shadow-lg">
                        <i class="fas fa-search ml-2"></i> ابحث عن طبيب الآن
                    </a>
                    <a href="register.php?type=doctor"
                        class="w-full sm:w-auto bg-green-500 text-white font-bold text-lg px-8 py-4 rounded-lg hover:bg-green-600 transition-transform transform hover:scale-105 shadow-lg">
                        <i class="fas fa-user-md ml-2"></i> انضم إلينا كطبيب
                    </a>
                </div>
            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white">
        <div class="container mx-auto px-6 py-12">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- About Section -->
                <div class="md:col-span-1">
                    <h3 class="text-2xl font-bold mb-4">Health Tech</h3>
                    <p class="text-gray-400">منصة رائدة تهدف إلى تسهيل الوصول للرعاية الصحية عبر ربط المرضى بأفضل
                        الأطباء والمستشفيات بكفاءة وسهولة.</p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">روابط سريعة</h3>
                    <ul class="space-y-2">
                        <li><a href="#features" class="text-gray-400 hover:text-white transition">المميزات</a></li>
                        <li><a href="#specialties" class="text-gray-400 hover:text-white transition">التخصصات</a></li>
                        <li><a href="search.php" class="text-gray-400 hover:text-white transition">ابحث عن طبيب</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition">تواصل معنا</a></li>
                    </ul>
                </div>

                <!-- Social Media -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">تابعنا</h3>
                    <div class="flex space-x-4 space-x-reverse">
                        <a href="#" class="text-gray-400 hover:text-white transition"><i
                                class="fab fa-facebook-f fa-lg"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white transition"><i
                                class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white transition"><i
                                class="fab fa-instagram fa-lg"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white transition"><i
                                class="fab fa-linkedin-in fa-lg"></i></a>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-10 pt-6 text-center text-gray-500">
                <p>&copy; <?php echo date('Y'); ?> Health Tech. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </footer>

</body>

</html>
