<?php
require_once 'includes/functions.php';

// التحقق من وجود معرف العيادة
$clinic_id = isset($_GET['clinic']) ? (int) $_GET['clinic'] : 0;

if (!$clinic_id) {
    header("Location: hospitals.php");
    exit();
}

// الحصول على معلومات العيادة والمستشفى
$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    $clinic = null;
    $hospital = null;
    $doctors = [];
} else {
    try {
        // الحصول على معلومات العيادة والمستشفى
        $stmt = $conn->prepare("
            SELECT c.*, h.name as hospital_name, h.address as hospital_address, s.name as specialty_name
            FROM clinics c
            LEFT JOIN hospitals h ON c.hospital_id = h.id
            LEFT JOIN specialties s ON c.specialty_id = s.id
            WHERE c.id = ?
        ");
        $stmt->execute([$clinic_id]);
        $clinic = $stmt->fetch();

        if ($clinic) {
            $hospital = [
                'name' => $clinic['hospital_name'],
                'address' => $clinic['hospital_address']
            ];
        }

        // الحصول على الأطباء
        $stmt = $conn->prepare("
            SELECT d.*, s.name as specialty_name
            FROM doctors d
            LEFT JOIN specialties s ON d.specialty_id = s.id
            WHERE d.clinic_id = ?
            ORDER BY d.full_name
        ");
        $stmt->execute([$clinic_id]);
        $doctors = $stmt->fetchAll();
    } catch (PDOException $e) {
        $clinic = null;
        $hospital = null;
        $doctors = [];
    }
}

// إذا لم يتم العثور على العيادة
if (!$clinic) {
    header("Location: hospitals.php");
    exit();
}

$page_title = "أطباء " . htmlspecialchars($clinic['name']);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>

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
                    <li><a href="clinics.php?hospital=<?php echo $clinic['hospital_id']; ?>"
                            class="hover:text-blue-600 transition">عيادات
                            <?php echo htmlspecialchars($hospital['name']); ?></a></li>
                    <li><i class="fas fa-chevron-left text-xs"></i></li>
                    <li class="text-gray-800 font-semibold">أطباء <?php echo htmlspecialchars($clinic['name']); ?></li>
                </ol>
            </nav>

            <!-- Clinic Info -->
            <div class="bg-white p-6 md:p-8 rounded-2xl shadow-xl mb-8">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-6">
                    <div class="flex-1">
                        <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">
                            <?php echo htmlspecialchars($clinic['name']); ?>
                        </h1>
                        <div class="space-y-2 mb-4">
                            <p class="flex items-center text-gray-600">
                                <i class="fas fa-hospital text-blue-600 ml-2"></i>
                                <?php echo htmlspecialchars($hospital['name']); ?>
                            </p>
                            <p class="flex items-center text-gray-600">
                                <i class="fas fa-map-marker-alt text-blue-600 ml-2"></i>
                                <?php echo htmlspecialchars($hospital['address']); ?>
                            </p>
                        </div>
                        <?php if (isset($clinic['specialty_name']) && $clinic['specialty_name']): ?>
                            <span
                                class="inline-block bg-blue-100 text-blue-800 px-4 py-2 rounded-full text-sm font-semibold">
                                <?php echo htmlspecialchars($clinic['specialty_name']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="flex flex-col items-center bg-gray-50 p-4 rounded-xl min-w-[120px]">
                        <div class="flex items-center gap-1 text-yellow-400 mb-2">
                            <?php
                            $rating = isset($clinic['rating']) ? $clinic['rating'] : 0;
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
                        <span class="text-2xl font-bold text-gray-800"><?php echo number_format($rating, 1); ?></span>
                    </div>
                </div>

                <?php if (isset($clinic['description']) && $clinic['description']): ?>
                    <div class="bg-gray-50 p-4 rounded-xl border-r-4 border-green-500">
                        <p class="text-gray-700 leading-relaxed"><?php echo htmlspecialchars($clinic['description']); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Doctors Section -->
            <div class="bg-white p-6 md:p-8 rounded-2xl shadow-xl">
                <div class="text-center mb-8">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-2">الأطباء المتاحون</h2>
                    <p class="text-gray-600 text-lg">اختر الطبيب المناسب لحجز موعدك الطبي</p>
                </div>

                <?php if (empty($doctors)): ?>
                    <div class="text-center py-16">
                        <i class="fas fa-user-md fa-4x text-gray-300 mb-4"></i>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">لا يوجد أطباء</h3>
                        <p class="text-gray-500 mb-6">لم يتم العثور على أطباء في هذه العيادة حالياً.</p>
                        <a href="clinics.php?hospital=<?php echo $clinic['hospital_id']; ?>"
                            class="inline-block bg-blue-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-blue-700 transition">
                            العودة للعيادات
                        </a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($doctors as $doctor): ?>
                            <?php
                            // Get doctor image or generate avatar
                            $doctor_image = null;
                            if (isset($doctor['image']) && !empty($doctor['image']) && file_exists($doctor['image'])) {
                                $doctor_image = $doctor['image'];
                            }
                            $doctor_name = htmlspecialchars($doctor['full_name']);
                            $avatar_url = $doctor_image ? $doctor_image : "https://ui-avatars.com/api/?name=" . urlencode($doctor_name) . "&background=2563eb&color=fff&size=128&bold=true";

                            $doctor_rating = isset($doctor['rating']) && $doctor['rating'] > 0 ? (float) $doctor['rating'] : 0;
                            ?>
                            <div
                                class="bg-white border-2 border-gray-100 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden group">
                                <!-- Doctor Card Header -->
                                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 p-6 pb-8">
                                    <div class="flex items-start justify-between mb-4">
                                        <!-- Doctor Avatar -->
                                        <div class="relative">
                                            <div class="w-20 h-20 rounded-full overflow-hidden border-4 border-white shadow-lg">
                                                <img src="<?php echo htmlspecialchars($avatar_url); ?>"
                                                    alt="<?php echo $doctor_name; ?>" class="w-full h-full object-cover"
                                                    onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($doctor_name); ?>&background=2563eb&color=fff&size=128&bold=true'">
                                            </div>
                                            <div
                                                class="absolute -bottom-1 -right-1 bg-green-500 rounded-full p-1.5 border-2 border-white">
                                                <i class="fas fa-check text-white text-xs"></i>
                                            </div>
                                        </div>

                                        <!-- Rating -->
                                        <div class="flex flex-col items-center bg-white rounded-xl p-3 shadow-md min-w-[80px]">
                                            <div class="flex items-center gap-0.5 mb-1">
                                                <?php
                                                for ($i = 1; $i <= 5; $i++) {
                                                    if ($i <= floor($doctor_rating)) {
                                                        echo '<i class="fas fa-star text-yellow-400 text-sm"></i>';
                                                    } elseif ($i - $doctor_rating < 1 && $doctor_rating > 0) {
                                                        echo '<i class="fas fa-star-half-alt text-yellow-400 text-sm"></i>';
                                                    } else {
                                                        echo '<i class="far fa-star text-gray-300 text-sm"></i>';
                                                    }
                                                }
                                                ?>
                                            </div>
                                            <span
                                                class="text-lg font-bold text-gray-800"><?php echo number_format($doctor_rating, 1); ?></span>
                                        </div>
                                    </div>

                                    <!-- Doctor Name -->
                                    <h3 class="text-xl font-bold text-gray-900 mb-3"><?php echo $doctor_name; ?></h3>

                                    <!-- Specialty & Experience -->
                                    <div class="flex flex-wrap gap-2">
                                        <?php if (isset($doctor['specialty_name']) && $doctor['specialty_name']): ?>
                                            <span
                                                class="inline-flex items-center bg-blue-100 text-blue-800 px-4 py-1.5 rounded-full text-sm font-semibold">
                                                <i class="fas fa-stethoscope ml-1 text-xs"></i>
                                                <?php echo htmlspecialchars($doctor['specialty_name']); ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (isset($doctor['experience_years']) && $doctor['experience_years']): ?>
                                            <span
                                                class="inline-flex items-center bg-gray-100 text-gray-700 px-4 py-1.5 rounded-full text-sm font-semibold">
                                                <i class="fas fa-briefcase ml-1 text-xs"></i>
                                                <?php echo $doctor['experience_years']; ?> سنوات خبرة
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Doctor Card Body -->
                                <div class="p-6">
                                    <?php if (isset($doctor['education']) && $doctor['education']): ?>
                                        <div
                                            class="bg-gradient-to-r from-blue-50 to-indigo-50 p-4 rounded-xl mb-4 border-r-4 border-blue-500">
                                            <h4 class="text-xs font-bold text-blue-600 mb-2 flex items-center">
                                                <i class="fas fa-graduation-cap ml-1"></i>
                                                المؤهلات العلمية
                                            </h4>
                                            <p class="text-sm text-gray-700 leading-relaxed">
                                                <?php echo htmlspecialchars($doctor['education']); ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (isset($doctor['bio']) && $doctor['bio']): ?>
                                        <div class="mb-4">
                                            <p class="text-sm text-gray-600 leading-relaxed line-clamp-2">
                                                <?php echo htmlspecialchars($doctor['bio']); ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Contact Info -->
                                    <div class="space-y-2 mb-4">
                                        <?php if (isset($doctor['phone']) && $doctor['phone']): ?>
                                            <div
                                                class="flex items-center gap-2 text-sm text-gray-600 bg-gray-50 p-2.5 rounded-lg hover:bg-blue-50 transition-colors">
                                                <i class="fas fa-phone text-blue-600 w-5"></i>
                                                <span><?php echo htmlspecialchars($doctor['phone']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (isset($doctor['email']) && $doctor['email']): ?>
                                            <div
                                                class="flex items-center gap-2 text-sm text-gray-600 bg-gray-50 p-2.5 rounded-lg hover:bg-blue-50 transition-colors">
                                                <i class="fas fa-envelope text-blue-600 w-5"></i>
                                                <span class="truncate"><?php echo htmlspecialchars($doctor['email']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="flex gap-3 mt-6">
                                        <a href="doctor-details.php?id=<?php echo $doctor['id']; ?>"
                                            class="flex-1 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold py-3 px-4 rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all duration-300 text-center shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                                            <i class="fas fa-eye ml-2"></i>
                                            التفاصيل
                                        </a>
                                        <a href="book_appointment.php?doctor_id=<?php echo $doctor['id']; ?>"
                                            class="flex-1 bg-gradient-to-r from-green-600 to-green-700 text-white font-semibold py-3 px-4 rounded-xl hover:from-green-700 hover:to-green-800 transition-all duration-300 text-center shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                                            <i class="fas fa-calendar-plus ml-2"></i>
                                            حجز موعد
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php require_once 'includes/footer.php'; ?>

</body>

</html>
