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
        <!-- Hero Section -->
        <section class="relative h-screen min-h-[600px] flex items-center justify-center text-center overflow-hidden">
            <!-- Background Image with Overlay -->
            <div class="absolute inset-0 z-0">
                <img src="https://images.unsplash.com/photo-1505751172876-fa1923c5c528?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80"
                    alt="Medical Background" class="w-full h-full object-cover object-center" />
                <div class="absolute inset-0 bg-gradient-to-r from-blue-900/90 to-teal-800/80 mix-blend-multiply"></div>
            </div>

            <!-- Animated Shapes -->
            <div class="absolute top-0 left-0 w-full h-full overflow-hidden z-0 pointer-events-none">
                <div class="absolute top-10 left-10 w-32 h-32 bg-white/10 rounded-full blur-3xl animate-pulse"></div>
                <div class="absolute bottom-20 right-20 w-64 h-64 bg-blue-400/20 rounded-full blur-3xl animate-pulse"
                    style="animation-duration: 4s;"></div>
            </div>

            <div class="relative z-10 container mx-auto px-4 flex flex-col items-center">
                <!-- Badge -->
                <div
                    class="mb-6 inline-flex items-center bg-white/10 backdrop-blur-md border border-white/20 rounded-full px-4 py-1.5 text-white text-sm font-medium animate-fade-in-up">
                    <span class="w-2 h-2 bg-green-400 rounded-full ml-2 animate-pulse"></span>
                    منصتك الطبية الأولى في الشرق الأوسط
                </div>

                <h1 class="text-5xl md:text-7xl font-black leading-tight mb-6 text-white drop-shadow-lg tracking-tight">
                    رعايتك الصحية.. <br class="md:hidden" />
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-200 to-teal-200">بين
                        يديك</span>
                </h1>

                <p class="text-lg md:text-2xl text-blue-50 mb-10 max-w-3xl leading-relaxed font-light">
                    احجز موعدك مع أفضل الأطباء في مدينتك، بكل سهولة وأمان.
                    <span class="hidden md:inline">نحن نجمع لك الخبرة الطبية والتكنولوجيا الحديثة لخدمة صحتك.</span>
                </p>

                <!-- Search Bar Container -->
                <div class="w-full max-w-3xl transform hover:scale-[1.01] transition-transform duration-300">
                    <form action="search.php" method="GET" class="relative group" dir="rtl">
                        <div
                            class="absolute -inset-1 bg-gradient-to-r from-blue-400 to-teal-400 rounded-full blur opacity-25 group-hover:opacity-50 transition duration-1000 group-hover:duration-200">
                        </div>
                        <div class="relative flex items-center bg-white rounded-full shadow-2xl p-2">
                            <div class="flex-grow relative">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                    <i class="fas fa-search text-gray-400 text-xl"></i>
                                </div>
                                <input type="text" name="query" placeholder="ابحث عن دكتور، تخصص، أو مستشفى..."
                                    class="w-full h-14 pr-12 pl-4 text-lg text-gray-800 bg-transparent border-none focus:ring-0 placeholder-gray-400" />
                            </div>
                            <button type="submit"
                                class="h-14 px-8 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-full transition-all duration-300 shadow-lg hover:shadow-blue-500/30 flex items-center gap-2">
                                <span>بحث</span>
                                <i
                                    class="fas fa-arrow-left text-sm transform group-hover:-translate-x-1 transition-transform"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Quick Stats -->
                <div class="mt-12 grid grid-cols-3 gap-8 md:gap-16 text-white/90 border-t border-white/10 pt-8">
                    <div class="text-center">
                        <div class="text-3xl font-bold mb-1">+250</div>
                        <div class="text-sm text-blue-200">طبيب متخصص</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold mb-1">+500</div>
                        <div class="text-sm text-blue-200">حجز ناجح</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold mb-1">24/7</div>
                        <div class="text-sm text-blue-200">خدمة متواصلة</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- About Section -->
        <section id="about" class="py-20 bg-white">
            <div class="container mx-auto px-4">
                <div class="flex flex-col md:flex-row items-center gap-12">
                    <div class="md:w-1/2">
                        <div class="relative">
                            <div class="absolute -top-4 -right-4 w-24 h-24 bg-blue-100 rounded-full -z-10"></div>
                            <div class="absolute -bottom-4 -left-4 w-32 h-32 bg-teal-100 rounded-full -z-10"></div>
                            <div
                                class="bg-gradient-to-br from-blue-600 to-teal-500 rounded-2xl p-1 shadow-2xl rotate-2 hover:rotate-0 transition-transform duration-500">
                                <div
                                    class="bg-white rounded-xl p-8 text-center h-full flex flex-col justify-center items-center min-h-[300px]">
                                    <i
                                        class="fas fa-heartbeat text-6xl text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-teal-500 mb-6"></i>
                                    <h3 class="text-2xl font-bold text-gray-800">رعايتك أولويتنا</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="md:w-1/2 text-right">
                        <h2 class="text-4xl font-black text-gray-900 mb-6">نبذة عن <span
                                class="text-blue-600">المشروع</span></h2>
                        <p class="text-lg text-gray-600 mb-6 leading-relaxed">
                            نظام Health Tech هو منصة طبية شاملة تم تطويرها لتسهيل عملية حجز المواعيد الطبية وإدارة
                            العيادات. يهدف المشروع إلى سد الفجوة بين المرضى ومقدمي الرعاية الصحية من خلال تقنيات ويب
                            حديثة وآمنة.
                        </p>
                        <div class="space-y-4">
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 flex-shrink-0">
                                    <i class="fas fa-check"></i>
                                </div>
                                <p class="text-gray-700 font-medium">نظام آمن ومحمي بأحدث التقنيات</p>
                            </div>
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 flex-shrink-0">
                                    <i class="fas fa-check"></i>
                                </div>
                                <p class="text-gray-700 font-medium">تجربة مستخدم سهلة وسلسة</p>
                            </div>
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 flex-shrink-0">
                                    <i class="fas fa-check"></i>
                                </div>
                                <p class="text-gray-700 font-medium">دعم فني متواصل</p>
                            </div>
                        </div>
                    </div>
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

        <!-- How It Works Section -->
        <section class="py-20 bg-white">
            <div class="container mx-auto px-4 text-center">
                <h2 class="text-4xl font-black text-gray-900 mb-16">كيف يعمل <span class="text-blue-600">النظام</span>؟
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8 relative">
                    <!-- Connecting Line (Desktop) -->
                    <div class="hidden md:block absolute top-1/2 left-0 w-full h-1 bg-blue-100 -z-10 -translate-y-1/2">
                    </div>

                    <!-- Step 1 -->
                    <div
                        class="relative bg-white p-6 rounded-xl shadow-lg hover:-translate-y-2 transition-transform duration-300">
                        <div
                            class="w-16 h-16 bg-blue-600 text-white rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4 border-4 border-white shadow-md">
                            1</div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">أنشئ حسابك</h3>
                        <p class="text-gray-600 text-sm">سجل دخولك كمريض أو طبيب في خطوات بسيطة.</p>
                    </div>

                    <!-- Step 2 -->
                    <div
                        class="relative bg-white p-6 rounded-xl shadow-lg hover:-translate-y-2 transition-transform duration-300">
                        <div
                            class="w-16 h-16 bg-blue-600 text-white rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4 border-4 border-white shadow-md">
                            2</div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">ابحث عن طبيب</h3>
                        <p class="text-gray-600 text-sm">اختر التخصص والموقع المناسب لك.</p>
                    </div>

                    <!-- Step 3 -->
                    <div
                        class="relative bg-white p-6 rounded-xl shadow-lg hover:-translate-y-2 transition-transform duration-300">
                        <div
                            class="w-16 h-16 bg-blue-600 text-white rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4 border-4 border-white shadow-md">
                            3</div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">احجز موعدك</h3>
                        <p class="text-gray-600 text-sm">اختر الوقت المناسب من الجدول المتاح.</p>
                    </div>

                    <!-- Step 4 -->
                    <div
                        class="relative bg-white p-6 rounded-xl shadow-lg hover:-translate-y-2 transition-transform duration-300">
                        <div
                            class="w-16 h-16 bg-green-500 text-white rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4 border-4 border-white shadow-md">
                            4</div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">تم الحجز!</h3>
                        <p class="text-gray-600 text-sm">احصل على تأكيد فوري وتذكير بموعدك.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Specialties Section -->
        <section id="specialties" class="py-20 bg-gray-50">
            <div class="container mx-auto px-4 text-center">
                <h2 class="text-4xl font-black text-gray-800 mb-4">تصفح التخصصات الطبية</h2>
                <p class="text-lg text-gray-600 mb-12 max-w-2xl mx-auto">
                    اختر التخصص المناسب لحالتك من بين مجموعة واسعة من التخصصات الطبية المتاحة لدينا.
                </p>

                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <?php
                    $get_icon = function ($name) {
                        $name = function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);

                        $contains = function ($haystack, $needle) {
                            return (function_exists('mb_strpos') ? mb_strpos($haystack, $needle) : strpos($haystack, $needle)) !== false;
                        };

                        if ($contains($name, 'أسنان'))
                            return 'fas fa-tooth';
                        if ($contains($name, 'قلب'))
                            return 'fas fa-heartbeat';
                        if ($contains($name, 'عيون'))
                            return 'fas fa-eye';
                        if ($contains($name, 'أطفال'))
                            return 'fas fa-baby';
                        if ($contains($name, 'عظام'))
                            return 'fas fa-bone';
                        if ($contains($name, 'جلد'))
                            return 'fas fa-allergies';
                        if ($contains($name, 'نفس') || $contains($name, 'مخ'))
                            return 'fas fa-brain';
                        if ($contains($name, 'جراح'))
                            return 'fas fa-scalpel';
                        if ($contains($name, 'نساء'))
                            return 'fas fa-female';
                        if ($contains($name, 'باطن'))
                            return 'fas fa-stethoscope';
                        if ($contains($name, 'أنف'))
                            return 'fas fa-deaf';
                        return 'fas fa-user-md';
                    };

                    foreach (array_slice($specialties, 0, 8) as $specialty):
                        $icon = $get_icon($specialty['name']);
                        $count = $specialty['doctor_count'] ?? 0;
                    ?>
                        <a href="search.php?specialty=<?php echo $specialty['id']; ?>"
                            class="group block bg-white p-6 rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 hover:border-blue-100 hover:-translate-y-1">
                            <div
                                class="w-16 h-16 mx-auto bg-blue-50 rounded-full flex items-center justify-center mb-4 group-hover:bg-blue-600 transition-colors duration-300">
                                <i
                                    class="<?php echo $icon; ?> text-2xl text-blue-600 group-hover:text-white transition-colors duration-300"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-2 group-hover:text-blue-600 transition-colors">
                                <?php echo htmlspecialchars($specialty['name']); ?></h3>
                            <p class="text-sm text-gray-500">
                                <?php echo $count; ?> دكتور متاح
                            </p>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="mt-12">
                    <a href="search.php"
                        class="inline-flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-blue-600 bg-blue-100 hover:bg-blue-200 transition-colors duration-300">
                        عرض كل التخصصات
                        <i class="fas fa-arrow-left mr-2"></i>
                    </a>
                </div>
            </div>
        </section>

        <!-- For Doctors Section -->
        <section class="py-20 bg-white">
            <div class="container mx-auto px-4">
                <div class="bg-blue-900 rounded-3xl overflow-hidden shadow-2xl">
                    <div class="flex flex-col md:flex-row items-center">
                        <div class="md:w-1/2 p-12 text-white">
                            <h2 class="text-3xl md:text-4xl font-black mb-6">هل أنت مقدم رعاية صحية؟</h2>
                            <p class="text-blue-200 text-lg mb-8">انضم إلى شبكتنا الطبية المتنامية وقم بإدارة عيادتك
                                بكفاءة عالية. نوفر لك أدوات متقدمة لإدارة المواعيد وملفات المرضى.</p>
                            <ul class="space-y-4 mb-8">
                                <li class="flex items-center gap-3">
                                    <i class="fas fa-check-circle text-green-400 text-xl"></i>
                                    <span>إدارة جدول المواعيد بسهولة</span>
                                </li>
                                <li class="flex items-center gap-3">
                                    <i class="fas fa-check-circle text-green-400 text-xl"></i>
                                    <span>ملف تعريفي احترافي</span>
                                </li>
                                <li class="flex items-center gap-3">
                                    <i class="fas fa-check-circle text-green-400 text-xl"></i>
                                    <span>نظام تقييمات ومراجعات</span>
                                </li>
                            </ul>
                            <a href="register.php?type=doctor"
                                class="inline-block bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-8 rounded-lg transition-colors duration-300">
                                انضم كطبيب الآن
                            </a>
                        </div>
                        <div
                            class="md:w-1/2 bg-blue-800 h-full min-h-[400px] flex items-center justify-center relative overflow-hidden">
                            <!-- Decorative Elements -->
                            <div
                                class="absolute top-0 right-0 w-64 h-64 bg-blue-700 rounded-full -translate-y-1/2 translate-x-1/2 opacity-50">
                            </div>
                            <div
                                class="absolute bottom-0 left-0 w-48 h-48 bg-blue-600 rounded-full translate-y-1/2 -translate-x-1/2 opacity-50">
                            </div>

                            <div class="relative z-10 text-center p-8">
                                <i class="fas fa-user-md text-9xl text-blue-400 opacity-80"></i>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Call to Action Section
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
        </section> -->

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
