<?php
session_start();
require_once 'config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Get search parameters
$search_query = isset($_GET['q']) ? sanitize_input($_GET['q']) : '';
$specialty_id = isset($_GET['specialty']) ? (int)$_GET['specialty'] : 0;

// Fetch search results or all doctors
$is_searching = !empty($search_query) || !empty($specialty_id);
if ($is_searching) {
    $doctors = search_doctors($search_query, $specialty_id);
    $pageTitle = 'نتائج البحث';
} else {
    // Get all doctors ordered by rating
    $doctors = get_all_doctors_by_rating($conn);
    $pageTitle = 'جميع الأطباء';
}

// Get all specialties for the filter dropdown
$specialties = get_all_specialties($conn);
$featured_hospitals = array_slice(get_all_hospitals($conn), 0, 6);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Health Tech</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; }
        .space-x-reverse > :not([hidden]) ~ :not([hidden]) { --tw-space-x-reverse: 1; margin-right: calc(1rem * var(--tw-space-x-reverse)); margin-left: calc(1rem * calc(1 - var(--tw-space-x-reverse))); }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Header -->
    <header class="bg-white shadow-md sticky top-0 z-50">
        <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
            <a href="index.php" class="text-3xl font-black text-blue-600">Health Tech</a>
            <div class="hidden md:flex items-center space-x-8 space-x-reverse">
                <a href="index.php#features" class="text-gray-600 hover:text-blue-600 transition">المميزات</a>
                <a href="index.php#specialties" class="text-gray-600 hover:text-blue-600 transition">التخصصات</a>
                <a href="index.php#contact" class="text-gray-600 hover:text-blue-600 transition">تواصل معنا</a>
            </div>
            <div class="flex items-center space-x-2 space-x-reverse">
                <?php if (isset($_SESSION['user_name'])): ?>
                    <a href="<?php echo ($_SESSION['user_type'] === 'doctor' ? 'doctor' : 'patient'); ?>/index.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">لوحة التحكم</a>
                    <a href="logout.php" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-300 transition">تسجيل الخروج</a>
                <?php else: ?>
                    <a href="login.php" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition">تسجيل الدخول</a>
                    <a href="register.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">إنشاء حساب</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <!-- Hero Section with Search -->
    <section class="bg-gradient-to-br from-blue-600 to-blue-800 text-white shadow-inner">
        <div class="container mx-auto px-4 py-16 text-center">
            <h1 class="text-4xl md:text-5xl font-black mb-3">ابحث عن أفضل الأطباء</h1>
            <p class="text-lg md:text-xl text-blue-200 mb-8">احجز موعدك بكل سهولة وسرعة من بين نخبة من الأطباء.</p>

            <div class="max-w-4xl mx-auto bg-white/10 backdrop-blur-sm p-4 rounded-xl shadow-lg border border-white/20">
                <form action="search.php" method="get" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                    <div class="md:col-span-5 relative">
                        <i class="fas fa-user-md absolute top-1/2 right-4 transform -translate-y-1/2 text-gray-400"></i>
                        <input type="text" name="q" id="q" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="اسم الطبيب أو العيادة..." class="w-full p-4 pr-12 text-gray-800 bg-white border-2 border-transparent rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 transition">
                    </div>
                    <div class="md:col-span-4 relative">
                        <i class="fas fa-stethoscope absolute top-1/2 right-4 transform -translate-y-1/2 text-gray-400"></i>
                        <select name="specialty" id="specialty" class="w-full p-4 pr-12 text-gray-800 bg-white border-2 border-transparent rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 appearance-none cursor-pointer">
                            <option value="">كل التخصصات</option>
                            <?php foreach ($specialties as $spec): ?>
                                <option value="<?php echo $spec['id']; ?>" <?php if ($specialty_id == $spec['id']) echo 'selected'; ?>><?php echo htmlspecialchars($spec['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down absolute top-1/2 left-4 transform -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                    </div>
                    <div class="md:col-span-3 flex space-x-2 space-x-reverse">
                        <button type="submit" class="w-full flex-1 bg-orange-500 text-white py-4 px-6 rounded-lg font-bold hover:bg-orange-600 transition shadow-md hover:shadow-lg transform hover:-translate-y-0.5"><i class="fas fa-search ml-2"></i>بحث</button>
                        <a href="search.php" class="bg-white/20 text-white py-4 px-5 rounded-lg hover:bg-white/30 transition" title="مسح الفلاتر">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Main Content: Search Results -->
    <main class="container mx-auto px-4 py-10">
        <section>
            <h2 class="text-3xl font-bold text-gray-800 mb-4"><?php echo $pageTitle; ?> <span class="text-blue-600">(<?php echo count($doctors); ?>)</span></h2>
            <?php if (!$is_searching): ?>
                <p class="text-gray-600 mb-8 flex items-center">
                    <i class="fas fa-sort-amount-down ml-2"></i>
                    مرتب حسب التقييم (الأعلى أولاً)
                </p>
            <?php endif; ?>

            <?php if (!empty($doctors)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($doctors as $doctor): ?>
                        <div class="bg-white rounded-xl shadow-lg overflow-hidden transform hover:-translate-y-2 transition-all duration-300 group">
                            <div class="relative">
                                <img src="<?php echo htmlspecialchars($doctor['image'] ?? 'assets/images/default-avatar.png'); ?>" 
                                     alt="<?php echo htmlspecialchars($doctor['full_name']); ?>" 
                                     class="w-full h-48 object-cover"
                                     onerror="this.onerror=null; this.src='assets/images/default-avatar.png';">
                                <div class="absolute top-4 right-4 bg-blue-600 text-white text-sm font-bold px-3 py-1 rounded-full shadow-md">
                                    <?php echo htmlspecialchars($doctor['specialty_name']); ?>
                                </div>
                            </div>
                            <div class="p-6">
                                <div class="flex items-center mb-4">
                                    <h3 class="text-xl font-bold text-gray-900 flex-1"><?php echo htmlspecialchars($doctor['full_name']); ?></h3>
                                    <div class="text-yellow-500 flex items-center">
                                        <?php
                                        $rating = $doctor['rating'];
                                        if ($rating > 0) {
                                            echo '<i class="fas fa-star"></i>';
                                            echo '<span class="text-gray-700 font-bold mr-1">' . number_format($rating, 1) . '</span>';
                                        } else {
                                            echo '<span class="text-gray-500 text-sm">لا يوجد تقييم</span>';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="space-y-3 text-gray-600">
                                    <p class="flex items-center"><i class="fas fa-clinic-medical text-gray-400 ml-3"></i> <?php echo htmlspecialchars($doctor['clinic_name'] ?? 'عيادة خاصة'); ?></p>
                                    <p class="flex items-center"><i class="fas fa-map-marker-alt text-gray-400 ml-3"></i> <?php echo htmlspecialchars($doctor['clinic_address'] ?? 'العنوان غير متوفر'); ?></p>
                                </div>
                                <div class="mt-6">
                                    <a href="doctor_profile.php?id=<?php echo $doctor['id']; ?>" class="block w-full text-center bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700 transition transform group-hover:scale-105">
                                        عرض الملف الشخصي والحجز
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($is_searching): ?>
                <div class="text-center bg-white p-16 rounded-xl shadow-lg border">
                    <div class="w-24 h-24 bg-red-100 text-red-500 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-search-minus fa-3x"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800">لا توجد نتائج مطابقة لبحثك</h3>
                    <p class="text-gray-500 mt-2 max-w-md mx-auto">حاول تعديل كلمات البحث أو تغيير الفلاتر للحصول على نتائج أفضل. قد يكون الطبيب الذي تبحث عنه غير متاح حالياً.</p>
                </div>
            <?php else: ?>
                 <div class="text-center bg-white p-16 rounded-xl shadow-lg border">
                    <div class="w-24 h-24 bg-blue-100 text-blue-500 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-hand-pointer fa-3x"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800">ابدأ البحث عن طبيبك المثالي</h3>
                    <p class="text-gray-500 mt-2 max-w-md mx-auto">استخدم شريط البحث في الأعلى للعثور على الطبيب المناسب حسب الاسم أو التخصص.</p>
                </div>
            <?php endif; ?>
        </section>

        <?php if (!empty($featured_hospitals)): ?>
        <section class="mt-16">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-800">أبرز المستشفيات الموصى بها</h2>
                    <p class="text-gray-500 mt-2">مجموعة مختارة من المستشفيات ذات التقييمات المرتفعة والخدمات المتكاملة</p>
                </div>
                <a href="hospitals.php" class="hidden md:inline-flex items-center gap-2 px-5 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                    <span>عرض جميع المستشفيات</span>
                    <i class="fas fa-arrow-left text-sm"></i>
                </a>
            </div>

            <?php $default_hospital_image = get_default_hospital_image(); ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($featured_hospitals as $hospital): ?>
                    <?php
                    $hospital_image = get_hospital_display_image($hospital);
                    $hospital_type = $hospital['type'] ?? 'حكومي';
                    $rating = (float) ($hospital['rating'] ?? 0);
                    ?>
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transition-all duration-300 group">
                        <div class="relative h-48 overflow-hidden">
                            <img src="<?php echo htmlspecialchars($hospital_image); ?>"
                                 alt="صورة <?php echo htmlspecialchars($hospital['name']); ?>"
                                 class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                 onerror="this.onerror=null;this.src='<?php echo htmlspecialchars($default_hospital_image); ?>';"
                                 loading="lazy">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/10 to-transparent"></div>
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
                        <div class="p-6 space-y-4">
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($hospital['name']); ?></h3>
                                <p class="text-gray-600 text-sm" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                    <?php echo htmlspecialchars($hospital['description']); ?>
                                </p>
                            </div>
                            <div class="space-y-2 text-sm text-gray-600">
                                <div class="flex items-center">
                                    <i class="fas fa-map-marker-alt text-blue-500 ml-2"></i>
                                    <span><?php echo htmlspecialchars($hospital['address']); ?></span>
                                </div>
                                <?php if (!empty($hospital['phone'])): ?>
                                <div class="flex items-center">
                                    <i class="fas fa-phone text-blue-500 ml-2"></i>
                                    <span><?php echo htmlspecialchars($hospital['phone']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center gap-3 pt-2">
                                <a href="hospital-details.php?id=<?php echo $hospital['id']; ?>" class="flex-1 text-center px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                                    <i class="fas fa-eye ml-1"></i>
                                    التفاصيل
                                </a>
                                <a href="clinics.php?hospital=<?php echo $hospital['id']; ?>" class="flex-1 text-center px-4 py-2 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition-colors">
                                    <i class="fas fa-stethoscope ml-1"></i>
                                    العيادات
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-6 md:hidden">
                <a href="hospitals.php" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors w-full justify-center">
                    <span>عرض جميع المستشفيات</span>
                    <i class="fas fa-arrow-left text-sm"></i>
                </a>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-12">
        <div class="container mx-auto px-4 py-12">
            <div class="border-t border-gray-700 pt-6 text-center text-gray-500">
                <p>&copy; <?php echo date('Y'); ?> Health Tech. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </footer>

</body>
</html>
