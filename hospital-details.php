<?php
session_start();
require_once 'config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}

// Get hospital ID
$hospital_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$hospital_id) {
    header("Location: hospitals.php");
    exit();
}

// Get database connection
$db = new Database();
$conn = $db->getConnection();

try {
    // Get hospital details
    $stmt = $conn->prepare("SELECT * FROM hospitals WHERE id = ?");
    $stmt->execute([$hospital_id]);
    $hospital = $stmt->fetch();

    if (!$hospital) {
        header("Location: hospitals.php");
        exit();
    }

    // Get clinics
    $stmt = $conn->prepare("
        SELECT c.*, s.name as specialty_name, COUNT(d.id) as doctors_count
        FROM clinics c
        LEFT JOIN specialties s ON c.specialty_id = s.id
        LEFT JOIN doctors d ON c.id = d.clinic_id
        WHERE c.hospital_id = ?
        GROUP BY c.id
        ORDER BY c.name
    ");
    $stmt->execute([$hospital_id]);
    $clinics = $stmt->fetchAll();

    // Get doctors
    $stmt = $conn->prepare("
        SELECT d.*, s.name as specialty_name, c.name as clinic_name
        FROM doctors d
        LEFT JOIN specialties s ON d.specialty_id = s.id
        LEFT JOIN clinics c ON d.clinic_id = c.id
        WHERE d.hospital_id = ?
        ORDER BY d.full_name
    ");
    $stmt->execute([$hospital_id]);
    $doctors = $stmt->fetchAll();

} catch (PDOException $e) {
    header("Location: hospitals.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital['name']); ?> - Health Tech</title>

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

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
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
                    <a href="hospitals.php" class="text-gray-600 hover:text-blue-600 transition-colors">
                        <i class="fas fa-arrow-left text-lg"></i>
                        <span class="ml-1">رجوع</span>
                    </a>
                    <?php if (is_logged_in()): ?>
                        <a href="dashboard.php" class="text-gray-600 hover:text-blue-600 transition-colors">
                            <i class="fas fa-tachometer-alt text-lg"></i>
                        </a>
                        <a href="logout.php" class="text-gray-600 hover:text-red-600 transition-colors">
                            <i class="fas fa-sign-out-alt text-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="min-h-screen pt-20 pb-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Hospital Hero Section -->
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden mb-8">
                <!-- Hero Image -->
                <?php
                $hospital_image = get_hospital_display_image($hospital);
                $default_hospital_image = get_default_hospital_image();

                // Add BASE_URL if the image is a local path (not a full URL)
                if (!preg_match('/^https?:\/\//', $hospital_image)) {
                    $hospital_image = BASE_URL . ltrim($hospital_image, '/');
                }
                ?>
                <div class="relative h-64 md:h-80 overflow-hidden">
                    <img src="<?php echo htmlspecialchars($hospital_image); ?>"
                         alt="صورة <?php echo htmlspecialchars($hospital['name']); ?>"
                         class="w-full h-full object-cover"
                         onerror="this.onerror=null;this.src='<?php echo htmlspecialchars($default_hospital_image); ?>';"
                         loading="lazy">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent"></div>
                </div>

                <!-- Hospital Content -->
                <div class="p-8">
                    <!-- Header -->
                    <div class="flex flex-col lg:flex-row lg:justify-between lg:items-start mb-6">
                        <div class="flex-1 mb-4 lg:mb-0">
                            <h1 class="text-3xl font-bold text-gray-900 mb-3">
                                <?php echo htmlspecialchars($hospital['name']); ?>
                            </h1>
                            <span class="inline-block px-4 py-2 text-sm font-medium rounded-full <?php echo $hospital['type'] === 'حكومي' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'; ?>">
                                <?php echo htmlspecialchars($hospital['type']); ?>
                            </span>
                        </div>

                        <!-- Rating -->
                        <div class="bg-gray-50 p-4 rounded-xl text-center min-w-[200px]">
                            <div class="flex text-yellow-400 justify-center mb-2">
                                <?php
                                $rating = $hospital['rating'];
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $rating) {
                                        echo '<i class="fas fa-star text-xl"></i>';
                                    } elseif ($i - $rating < 1) {
                                        echo '<i class="fas fa-star-half-alt text-xl"></i>';
                                    } else {
                                        echo '<i class="far fa-star text-xl"></i>';
                                    }
                                }
                                ?>
                            </div>
                            <span class="text-2xl font-bold text-gray-900"><?php echo number_format($rating, 1); ?></span>
                            <p class="text-sm text-gray-600">تقييم</p>
                        </div>
                    </div>

                    <!-- Description -->
                    <p class="text-gray-600 text-lg leading-relaxed mb-8">
                        <?php echo htmlspecialchars($hospital['description']); ?>
                    </p>

                    <!-- Info Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div class="bg-gray-50 p-6 rounded-xl">
                            <div class="flex items-center text-blue-600 mb-3">
                                <i class="fas fa-map-marker-alt text-xl"></i>
                            </div>
                            <h4 class="font-semibold text-gray-900 mb-2">العنوان</h4>
                            <p class="text-gray-700"><?php echo htmlspecialchars($hospital['address']); ?></p>
                        </div>

                        <div class="bg-gray-50 p-6 rounded-xl">
                            <div class="flex items-center text-blue-600 mb-3">
                                <i class="fas fa-phone text-xl"></i>
                            </div>
                            <h4 class="font-semibold text-gray-900 mb-2">الهاتف</h4>
                            <p class="text-gray-700"><?php echo htmlspecialchars($hospital['phone']); ?></p>
                        </div>

                        <div class="bg-gray-50 p-6 rounded-xl">
                            <div class="flex items-center text-blue-600 mb-3">
                                <i class="fas fa-envelope text-xl"></i>
                            </div>
                            <h4 class="font-semibold text-gray-900 mb-2">البريد الإلكتروني</h4>
                            <p class="text-gray-700"><?php echo htmlspecialchars($hospital['email']); ?></p>
                        </div>

                        <div class="bg-gray-50 p-6 rounded-xl">
                            <div class="flex items-center text-blue-600 mb-3">
                                <i class="fas fa-clock text-xl"></i>
                            </div>
                            <h4 class="font-semibold text-gray-900 mb-2">ساعات العمل</h4>
                            <p class="text-gray-700"><?php echo $hospital['is_24h'] ? 'مفتوح 24 ساعة' : 'ساعات عمل محددة'; ?></p>
                        </div>
                    </div>

                    <!-- Features -->
                    <div class="flex flex-wrap gap-3">
                        <?php if ($hospital['is_24h']): ?>
                            <span class="px-4 py-2 bg-orange-100 text-orange-700 text-sm font-medium rounded-full">
                                <i class="fas fa-clock ml-1"></i>
                                24 ساعة
                            </span>
                        <?php endif; ?>
                        <span class="px-4 py-2 bg-blue-100 text-blue-700 text-sm font-medium rounded-full">
                            <i class="fas fa-medkit ml-1"></i>
                            خدمات متكاملة
                        </span>
                        <span class="px-4 py-2 bg-green-100 text-green-700 text-sm font-medium rounded-full">
                            <i class="fas fa-user-md ml-1"></i>
                            أطباء متخصصون
                        </span>
                        <span class="px-4 py-2 bg-purple-100 text-purple-700 text-sm font-medium rounded-full">
                            <i class="fas fa-microscope ml-1"></i>
                            أحدث التقنيات
                        </span>
                        <?php if ($hospital['type'] === 'خاص'): ?>
                            <span class="px-4 py-2 bg-yellow-100 text-yellow-700 text-sm font-medium rounded-full">
                                <i class="fas fa-star ml-1"></i>
                                خدمات فاخرة
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Tabs Section -->
            <div class="bg-white rounded-2xl shadow-xl p-2 mb-8">
                <div class="flex flex-col sm:flex-row">
                    <button onclick="showTab('clinics')"
                            class="tab-btn flex-1 px-6 py-3 text-center font-medium rounded-xl transition-all duration-200 bg-blue-600 text-white"
                            data-tab="clinics">
                        <i class="fas fa-stethoscope ml-2"></i>
                        العيادات (<?php echo count($clinics); ?>)
                    </button>
                    <button onclick="showTab('doctors')"
                            class="tab-btn flex-1 px-6 py-3 text-center font-medium rounded-xl transition-all duration-200 text-gray-600 hover:bg-gray-100"
                            data-tab="doctors">
                        <i class="fas fa-user-md ml-2"></i>
                        الأطباء (<?php echo count($doctors); ?>)
                    </button>
                </div>
            </div>

            <!-- Clinics Tab -->
            <div id="clinics" class="tab-content active">
                <?php if (empty($clinics)): ?>
                    <div class="text-center py-16 bg-white rounded-2xl shadow-xl">
                        <div class="inline-flex items-center justify-center w-24 h-24 bg-gray-100 rounded-full mb-6">
                            <i class="fas fa-stethoscope text-gray-400 text-4xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">لا توجد عيادات</h3>
                        <p class="text-gray-600 mb-8">لم يتم العثور على عيادات في هذا المستشفى.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($clinics as $clinic): ?>
                            <div class="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-shadow duration-300">
                                <!-- Header -->
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h3 class="text-xl font-bold text-gray-900 mb-2">
                                            <?php echo htmlspecialchars($clinic['name']); ?>
                                        </h3>
                                        <p class="text-blue-600 font-medium">
                                            <?php echo htmlspecialchars($clinic['specialty_name']); ?>
                                        </p>
                                    </div>
                                    <div class="bg-green-100 text-green-700 px-3 py-1 rounded-full font-bold">
                                        <?php echo $clinic['consultation_fee']; ?> ج.م
                                    </div>
                                </div>

                                <!-- Description -->
                                <p class="text-gray-600 mb-4 line-clamp-3" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;">
                                    <?php echo htmlspecialchars($clinic['description']); ?>
                                </p>

                                <!-- Stats -->
                                <div class="flex justify-between mb-6 p-4 bg-gray-50 rounded-lg">
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-blue-600"><?php echo $clinic['doctors_count']; ?></div>
                                        <div class="text-sm text-gray-600">طبيب</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-green-600"><?php echo $clinic['consultation_fee']; ?></div>
                                        <div class="text-sm text-gray-600">ج.م للكشف</div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex space-x-3 space-x-reverse">
                                    <a href="doctors.php?clinic=<?php echo $clinic['id']; ?>"
                                       class="flex-1 text-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                        <i class="fas fa-calendar-plus ml-1"></i>
                                        حجز موعد
                                    </a>
                                    <a href="doctors.php?clinic=<?php echo $clinic['id']; ?>"
                                       class="flex-1 text-center px-4 py-2 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 transition-colors">
                                        <i class="fas fa-user-md ml-1"></i>
                                        الأطباء
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Doctors Tab -->
            <div id="doctors" class="tab-content">
                <?php if (empty($doctors)): ?>
                    <div class="text-center py-16 bg-white rounded-2xl shadow-xl">
                        <div class="inline-flex items-center justify-center w-24 h-24 bg-gray-100 rounded-full mb-6">
                            <i class="fas fa-user-md text-gray-400 text-4xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">لا يوجد أطباء</h3>
                        <p class="text-gray-600 mb-8">لم يتم العثور على أطباء في هذا المستشفى.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($doctors as $doctor): ?>
                            <div class="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-shadow duration-300">
                                <!-- Header -->
                                <div class="flex items-center mb-4">
                                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center ml-4">
                                        <i class="fas fa-user-md text-white text-xl"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <?php echo htmlspecialchars($doctor['full_name']); ?>
                                        </h3>
                                        <p class="text-blue-600 font-medium">
                                            <?php echo htmlspecialchars($doctor['specialty_name']); ?>
                                        </p>
                                    </div>
                                </div>

                                <!-- Rating -->
                                <div class="flex items-center mb-4">
                                    <div class="flex text-yellow-400 ml-2">
                                        <?php
                                        $rating = $doctor['rating'];
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $rating) {
                                                echo '<i class="fas fa-star text-sm"></i>';
                                            } elseif ($i - $rating < 1) {
                                                echo '<i class="fas fa-star-half-alt text-sm"></i>';
                                            } else {
                                                echo '<i class="far fa-star text-sm"></i>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    <span class="text-sm font-medium text-gray-600"><?php echo number_format($rating, 1); ?></span>
                                </div>

                                <!-- Details -->
                                <div class="space-y-2 mb-4">
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-graduation-cap text-blue-500 w-5"></i>
                                        <span><?php echo htmlspecialchars($doctor['education']); ?></span>
                                    </div>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-clock text-blue-500 w-5"></i>
                                        <span><?php echo $doctor['experience_years']; ?> سنوات خبرة</span>
                                    </div>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-money-bill text-blue-500 w-5"></i>
                                        <span><?php echo $doctor['consultation_fee']; ?> ج.م للكشف</span>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex space-x-3 space-x-reverse">
                                    <a href="book_appointment.php?doctor_id=<?php echo $doctor['id']; ?>"
                                       class="flex-1 text-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                        <i class="fas fa-calendar-plus ml-1"></i>
                                        حجز موعد
                                    </a>
                                    <a href="doctor-profile.php?id=<?php echo $doctor['id']; ?>"
                                       class="flex-1 text-center px-4 py-2 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 transition-colors">
                                        <i class="fas fa-user ml-1"></i>
                                        الملف الشخصي
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
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

    <script>
        function showTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => {
                content.classList.remove('active');
            });

            // Remove active class from all buttons
            const tabButtons = document.querySelectorAll('.tab-btn');
            tabButtons.forEach(button => {
                button.classList.remove('bg-blue-600', 'text-white');
                button.classList.add('text-gray-600', 'hover:bg-gray-100');
            });

            // Show selected tab content
            document.getElementById(tabName).classList.add('active');

            // Add active class to selected button
            const activeButton = document.querySelector(`[data-tab="${tabName}"]`);
            activeButton.classList.remove('text-gray-600', 'hover:bg-gray-100');
            activeButton.classList.add('bg-blue-600', 'text-white');
        }
    </script>
</body>
</html>
