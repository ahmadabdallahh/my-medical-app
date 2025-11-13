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

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Get all specialties for the dropdown
$specialties = get_all_specialties($conn);

// Get selected values
$selected_specialty = isset($_POST['specialty_id']) ? (int)$_POST['specialty_id'] : 0;
$selected_doctor = isset($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : 0;

// Get doctors if specialty is selected
$doctors = [];
if ($selected_specialty > 0) {
    $stmt = $conn->prepare("
        SELECT d.*, s.name as specialty_name, h.name as hospital_name
        FROM doctors d
        LEFT JOIN specialties s ON d.specialty_id = s.id
        LEFT JOIN hospitals h ON d.hospital_id = h.id
        WHERE d.specialty_id = ?
        ORDER BY d.rating DESC
    ");
    $stmt->execute([$selected_specialty]);
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get doctor details if doctor is selected
$doctor_details = null;
if ($selected_doctor > 0) {
    $stmt = $conn->prepare("
        SELECT d.*, s.name as specialty_name, c.name as clinic_name, 
               h.name as hospital_name, h.address as hospital_address,
               c.consultation_fee
        FROM doctors d
        LEFT JOIN specialties s ON d.specialty_id = s.id
        LEFT JOIN clinics c ON d.clinic_id = c.id
        LEFT JOIN hospitals h ON d.hospital_id = h.id
        WHERE d.id = ?
    ");
    $stmt->execute([$selected_doctor]);
    $doctor_details = $stmt->fetch(PDO::FETCH_ASSOC);
}

$pageTitle = 'حجز موعد جديد';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Health Tech</title>
    
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
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="min-h-screen pt-20 pb-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Header -->
            <div class="text-center mb-12">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-blue-100 rounded-full mb-4">
                    <i class="fas fa-calendar-plus text-blue-600 text-3xl"></i>
                </div>
                <h1 class="text-4xl font-bold text-gray-900 mb-4">حجز موعد جديد</h1>
                <p class="text-xl text-gray-600">اختر التخصص ثم الطبيب المناسب لك</p>
            </div>

            <!-- Booking Form -->
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <form method="POST" action="" class="space-y-8">
                    
                    <!-- Specialty Selection -->
                    <div>
                        <label class="block text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-stethoscope text-blue-600 ml-2"></i>
                            اختر التخصص
                        </label>
                        <select name="specialty_id" 
                                id="specialty_id"
                                onchange="this.form.submit()"
                                class="w-full p-4 text-gray-800 bg-white border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg">
                            <option value="">-- اختر التخصص --</option>
                            <?php foreach ($specialties as $specialty): ?>
                                <option value="<?php echo $specialty['id']; ?>" 
                                        <?php echo $selected_specialty == $specialty['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($specialty['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Doctor Selection -->
                    <?php if ($selected_specialty > 0): ?>
                        <div>
                            <label class="block text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-user-md text-blue-600 ml-2"></i>
                                اختر الطبيب
                            </label>
                            <select name="doctor_id" 
                                    id="doctor_id"
                                    onchange="this.form.submit()"
                                    class="w-full p-4 text-gray-800 bg-white border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg">
                                <option value="">-- اختر الطبيب --</option>
                                <?php foreach ($doctors as $doctor): ?>
                                    <option value="<?php echo $doctor['id']; ?>" 
                                            <?php echo $selected_doctor == $doctor['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($doctor['full_name']); ?> 
                                        (<?php echo number_format($doctor['rating'], 1); ?> ⭐) - 
                                        <?php echo htmlspecialchars($doctor['hospital_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <!-- Doctor Details -->
                    <?php if ($doctor_details): ?>
                        <div class="border-2 border-blue-200 rounded-xl p-6 bg-blue-50">
                            <h3 class="text-xl font-bold text-gray-900 mb-4">تفاصيل الطبيب</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <p class="text-gray-600">الاسم:</p>
                                    <p class="font-semibold"><?php echo htmlspecialchars($doctor_details['full_name']); ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-600">التخصص:</p>
                                    <p class="font-semibold"><?php echo htmlspecialchars($doctor_details['specialty_name']); ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-600">المستشفى:</p>
                                    <p class="font-semibold"><?php echo htmlspecialchars($doctor_details['hospital_name']); ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-600">سعر الكشف:</p>
                                    <p class="font-semibold text-green-600"><?php echo $doctor_details['consultation_fee']; ?> ج.م</p>
                                </div>
                                <div>
                                    <p class="text-gray-600">التقييم:</p>
                                    <p class="font-semibold">
                                        <?php echo number_format($doctor_details['rating'], 1); ?> ⭐
                                    </p>
                                </div>
                                <div>
                                    <p class="text-gray-600">الخبرة:</p>
                                    <p class="font-semibold"><?php echo $doctor_details['experience_years']; ?> سنوات</p>
                                </div>
                            </div>
                            
                            <div class="mt-6">
                                <a href="book_appointment.php?doctor_id=<?php echo $doctor_details['id']; ?>" 
                                   class="w-full md:w-auto px-8 py-3 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition-colors inline-block text-center">
                                    <i class="fas fa-calendar-check ml-2"></i>
                                    متابعة حجز الموعد
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                </form>
            </div>

            <!-- Quick Links -->
            <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                <a href="search.php" class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow text-center">
                    <i class="fas fa-search text-blue-600 text-2xl mb-3"></i>
                    <h3 class="font-semibold text-gray-900">البحث عن أطباء</h3>
                    <p class="text-gray-600 text-sm mt-2">ابحث عن الطبيب المناسب</p>
                </a>
                
                <a href="hospitals.php" class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow text-center">
                    <i class="fas fa-hospital text-blue-600 text-2xl mb-3"></i>
                    <h3 class="font-semibold text-gray-900">المستشفيات</h3>
                    <p class="text-gray-600 text-sm mt-2">استعرض المستشفيات المتاحة</p>
                </a>
                
                <a href="dashboard.php" class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow text-center">
                    <i class="fas fa-tachometer-alt text-blue-600 text-2xl mb-3"></i>
                    <h3 class="font-semibold text-gray-900">لوحة التحكم</h3>
                    <p class="text-gray-600 text-sm mt-2">إدارة مواعيدك</p>
                </a>
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

</body>
</html>
