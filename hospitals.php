<?php
session_start();
require_once 'config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Get search and filter parameters
$search_query = isset($_GET['q']) ? sanitize_input($_GET['q']) : '';
$hospital_type = sanitize_input($_GET['type']);
$is_24h = isset($_GET['24h']) ? (int)$_GET['24h'] : 0;
$min_rating = isset($_GET['rating']) ? (float)$_GET['rating'] : 0;

// Get hospitals
$hospitals = get_all_hospitals($conn);

// Apply filters
if ($search_query || $hospital_type || $is_24h || $min_rating) {
    $filtered_hospitals = [];
    foreach ($hospitals as $hospital) {
        $matches = true;

        // Search filter
        if ($search_query && !preg_match("/$search_query/i", $hospital['name'] . ' ' . $hospital['description'])) {
            $matches = false;
        }

        // Type filter
        if ($hospital_type && $hospital['type'] !== $hospital_type) {
            $matches = false;
        }

        // 24h filter
        if ($is_24h && !$hospital['is_24h']) {
            $matches = false;
        }

        // Rating filter
        if ($min_rating && $hospital['rating'] < $min_rating) {
            $matches = false;
        }

        if ($matches) {
            $filtered_hospitals[] = $hospital;
        }
    }
    $hospitals = $filtered_hospitals;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المستشفيات والعيادات - Health Tech</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts (Cairo) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Cairo', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="flex items-center">
                        <i class="fas fa-heartbeat text-blue-600 text-2xl ml-2"></i>
                        <span class="text-xl font-bold text-gray-800">Health Tech</span>
                    </a>
                </div>

                <div class="flex items-center space-x-4 space-x-reverse">
                    <a href="search.php" class="text-gray-600 hover:text-blue-600 transition-colors">
                        <i class="fas fa-search text-lg"></i>
                    </a>
                    <a href="doctors.php" class="text-gray-600 hover:text-blue-600 transition-colors">
                        <i class="fas fa-user-md text-lg"></i>
                    </a>
                    <?php if (is_logged_in()): ?>
                        <a href="dashboard.php" class="text-gray-600 hover:text-blue-600 transition-colors">
                            <i class="fas fa-tachometer-alt text-lg"></i>
                        </a>
                        <a href="logout.php" class="text-gray-600 hover:text-red-600 transition-colors">
                            <i class="fas fa-sign-out-alt text-lg"></i>
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="px-4 py-2 text-sm font-bold text-white bg-green-500 rounded-lg hover:bg-green-600">تسجيل الدخول</a>
                        <a href="register.php" class="px-4 py-2 text-sm font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700">إنشاء حساب</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="min-h-screen pt-20 pb-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Header -->
            <div class="text-center mb-12">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-blue-100 rounded-full mb-4">
                    <i class="fas fa-hospital text-blue-600 text-3xl"></i>
                </div>
                <h1 class="text-4xl font-bold text-gray-900 mb-4">المستشفيات والعيادات</h1>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    اكتشف أفضل المستشفيات والعيادات الطبية. اختر من بين مجموعة واسعة من المرافق الطبية المتخصصة
                </p>
            </div>

            <!-- Filters Section -->
            <div class="bg-white rounded-2xl shadow-xl p-8 mb-12">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">البحث والتصفية</h2>
                <form method="GET" action="" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">البحث</label>
                            <div class="relative">
                                <input type="text"
                                       id="q"
                                       name="q"
                                       value="<?php echo htmlspecialchars($search_query); ?>"
                                       placeholder="ابحث عن مستشفى..."
                                       class="w-full pr-10 pl-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">نوع المستشفى</label>
                            <select id="type"
                                    name="type"
                                    class="w-full pr-4 pl-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">جميع الأنواع</option>
                                <option value="حكومي" <?php echo $hospital_type === 'حكومي' ? 'selected' : ''; ?>>حكومي</option>
                                <option value="خاص" <?php echo $hospital_type === 'خاص' ? 'selected' : ''; ?>>خاص</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ساعات العمل</label>
                            <select id="24h"
                                    name="24h"
                                    class="w-full pr-4 pl-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="0">جميع المستشفيات</option>
                                <option value="1" <?php echo $is_24h ? 'selected' : ''; ?>>مفتوح 24 ساعة فقط</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">الحد الأدنى للتقييم</label>
                            <select id="rating"
                                    name="rating"
                                    class="w-full pr-4 pl-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="0">جميع التقييمات</option>
                                <option value="4.5" <?php echo $min_rating == 4.5 ? 'selected' : ''; ?>>4.5+ نجوم</option>
                                <option value="4.0" <?php echo $min_rating == 4.0 ? 'selected' : ''; ?>>4.0+ نجوم</option>
                                <option value="3.5" <?php echo $min_rating == 3.5 ? 'selected' : ''; ?>>3.5+ نجوم</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-center space-x-4 space-x-reverse">
                        <button type="submit"
                                class="px-8 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors flex items-center space-x-2 space-x-reverse">
                            <i class="fas fa-search"></i>
                            <span>تطبيق الفلاتر</span>
                        </button>
                        <a href="hospitals.php"
                           class="px-8 py-3 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 transition-colors flex items-center space-x-2 space-x-reverse">
                            <i class="fas fa-times"></i>
                            <span>مسح الفلاتر</span>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Results Count -->
            <div class="mb-8 flex items-center justify-between">
                <p class="text-gray-600">
                    تم العثور على <span class="font-bold text-blue-600"><?php echo count($hospitals); ?></span> مستشفى
                </p>
                <div class="flex items-center text-sm text-gray-500">
                    <i class="fas fa-sort-amount-down ml-2"></i>
                    <span>مرتب حسب التقييم (الأعلى أولاً)</span>
                </div>
            </div>

            <!-- Hospitals Grid -->
            <?php if (empty($hospitals)): ?>
                <div class="text-center py-16 bg-white rounded-2xl shadow-xl">
                    <div class="inline-flex items-center justify-center w-24 h-24 bg-gray-100 rounded-full mb-6">
                        <i class="fas fa-hospital text-gray-400 text-4xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">لا توجد مستشفيات</h3>
                    <p class="text-gray-600 mb-8">لم يتم العثور على مستشفيات تطابق معايير البحث المحددة.</p>
                    <a href="hospitals.php" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-redo ml-2"></i>
                        عرض جميع المستشفيات
                    </a>
                </div>
            <?php else: ?>
                <?php $default_hospital_image = get_default_hospital_image(); ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($hospitals as $hospital): ?>
                        <?php
                        $hospital_image = get_hospital_display_image($hospital);
                        $hospital_type = $hospital['type'] ?? 'حكومي';
                        $rating = (float) ($hospital['rating'] ?? 0);
                        $doctor_count = (int) ($hospital['doctor_count'] ?? 0);
                        ?>
                        <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transition-shadow duration-300">
                            <!-- Hospital Image -->
                            <div class="relative h-48 overflow-hidden">
                                <img src="<?php echo htmlspecialchars($hospital_image); ?>"
                                     alt="صورة <?php echo htmlspecialchars($hospital['name']); ?>"
                                     class="w-full h-full object-cover"
                                     onerror="this.onerror=null;this.src='<?php echo htmlspecialchars($default_hospital_image); ?>';"
                                     loading="lazy">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent"></div>
                                <div class="absolute top-4 right-4">
                                    <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full <?php echo $hospital_type === 'حكومي' ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800'; ?>">
                                        <i class="fas fa-hospital ml-1 text-xs"></i>
                                        <?php echo htmlspecialchars($hospital_type); ?>
                                    </span>
                                </div>
                                <div class="absolute top-4 left-4">
                                    <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-black/50 text-white backdrop-blur">
                                        <i class="fas fa-star text-yellow-300 ml-1"></i>
                                        <?php echo number_format($rating, 1); ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Hospital Content -->
                            <div class="p-6">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h3 class="text-xl font-bold text-gray-900 mb-2">
                                            <?php echo htmlspecialchars($hospital['name']); ?>
                                        </h3>
                                        <p class="text-sm text-gray-600 mb-2 overflow-hidden" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">
                                            <?php echo htmlspecialchars($hospital['description']); ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center text-sm text-gray-500 gap-4 mb-4">
                                    <span class="flex items-center gap-1">
                                        <i class="fas fa-user-md text-blue-500"></i>
                                        <?php echo max($doctor_count, 0); ?> طبيب
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <i class="fas fa-shield-alt text-blue-400"></i>
                                        <?php echo (isset($hospital['has_insurance']) && $hospital['has_insurance']) ? 'تأمين معتمد' : 'خدمات رعاية'; ?>
                                    </span>
                                </div>

                                <!-- Details -->
                                <div class="space-y-2 mb-4 text-sm text-gray-600">
                                    <div class="flex items-center">
                                        <i class="fas fa-map-marker-alt text-blue-500 ml-2"></i>
                                        <span><?php echo htmlspecialchars($hospital['address']); ?></span>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-phone text-blue-500 ml-2"></i>
                                        <span><?php echo htmlspecialchars($hospital['phone']); ?></span>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-clock text-blue-500 ml-2"></i>
                                        <span><?php echo (!empty($hospital['is_24h'])) ? 'مفتوح 24 ساعة' : 'ساعات عمل محددة'; ?></span>
                                    </div>
                                </div>

                                <!-- Features -->
                                <div class="flex flex-wrap gap-2 mb-6">
                                    <?php if (!empty($hospital['is_24h'])): ?>
                                        <span class="px-2 py-1 bg-orange-100 text-orange-700 text-xs font-medium rounded-full">24 ساعة</span>
                                    <?php endif; ?>
                                    <?php if (!empty($hospital['has_emergency'])): ?>
                                        <span class="px-2 py-1 bg-red-100 text-red-700 text-xs font-medium rounded-full">طوارئ</span>
                                    <?php endif; ?>
                                    <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded-full">خدمات متكاملة</span>
                                    <span class="px-2 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full">أطباء متخصصون</span>
                                </div>

                                <!-- Actions -->
                                <div class="flex space-x-3 space-x-reverse">
                                    <a href="hospital-details.php?id=<?php echo $hospital['id']; ?>"
                                       class="flex-1 text-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                        <i class="fas fa-eye ml-1"></i>
                                        التفاصيل
                                    </a>
                                    <a href="clinics.php?hospital=<?php echo $hospital['id']; ?>"
                                       class="flex-1 text-center px-4 py-2 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 transition-colors">
                                        <i class="fas fa-stethoscope ml-1"></i>
                                        العيادات
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="text-center">
                <div class="flex justify-center items-center mb-4">
                    <i class="fas fa-heartbeat text-blue-400 text-2xl ml-2"></i>
                    <span class="text-xl font-bold">Health Tech</span>
                </div>
                <p class="text-gray-400">© <?php echo date('Y'); ?> جميع الحقوق محفوظة</p>
            </div>
        </div>
    </footer>

</body>
</html>
