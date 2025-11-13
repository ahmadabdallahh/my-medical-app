<?php
require_once 'includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $date_of_birth = sanitize_input($_POST['date_of_birth']);
    $gender = sanitize_input($_POST['gender']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // التحقق من صحة البيانات
    if (empty($full_name) || empty($email) || empty($phone) || empty($date_of_birth) || empty($gender) || empty($password) || empty($confirm_password)) {
        $error = 'يرجى ملء جميع الحقول المطلوبة';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'البريد الإلكتروني غير صحيح';
    } elseif (strlen($password) < 6) {
        $error = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
    } elseif ($password !== $confirm_password) {
        $error = 'كلمة المرور غير متطابقة';
    } elseif (strtotime($date_of_birth) > strtotime('-12 years', time())) {
        $error = 'يجب أن يكون عمرك 12 سنة على الأقل';
    } else {
        // التحقق من عدم وجود البريد الإلكتروني مسبقاً
        $db = new Database();
        $conn = $db->getConnection();

        if (!$conn) {
            $error = 'خطأ في الاتصال بقاعدة البيانات';
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $error = 'البريد الإلكتروني مستخدم بالفعل';
            } else {
                // إنشاء المستخدم الجديد
                $hashed_password = hash_password($password);
                $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, date_of_birth, gender, password, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");

                if ($stmt->execute([$full_name, $email, $phone, $date_of_birth, $gender, $hashed_password])) {
                    $success = 'تم إنشاء الحساب بنجاح! يمكنك الآن تسجيل الدخول.';
                    // تفريغ الحقول
                    $full_name = $email = $phone = $date_of_birth = $gender = '';
                } else {
                    $error = 'خطأ في إنشاء الحساب';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء حساب جديد - مستشفى الأمل</title>
    <link rel="stylesheet" href="assets/css/tailwind.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-hospital-primary to-blue-700 min-h-screen py-8 px-4">
    <!-- Back to Home -->
    <a href="index_tailwind.php" class="absolute top-6 right-6 text-white hover:text-blue-200 transition-colors flex items-center space-x-2 space-x-reverse">
        <i class="fas fa-arrow-right"></i>
        <span>العودة للرئيسية</span>
    </a>

    <!-- Register Card -->
    <div class="bg-white rounded-hospital shadow-hospital-hover p-8 w-full max-w-2xl mx-auto">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="hospital-icon mx-auto mb-4">
                <i class="fas fa-user-plus"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">إنشاء حساب جديد</h1>
            <p class="text-gray-600">أدخل بياناتك لإنشاء حساب جديد</p>
        </div>

        <!-- Error/Success Messages -->
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-hospital mb-6 flex items-center space-x-2 space-x-reverse">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-hospital mb-6 flex items-center space-x-2 space-x-reverse">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success; ?></span>
            </div>
        <?php endif; ?>

        <!-- Register Form -->
        <form method="POST" action="" class="space-y-6">
            <div class="grid md:grid-cols-2 gap-6">
                <!-- Full Name -->
                <div>
                    <label for="full_name" class="block text-sm font-semibold text-gray-700 mb-2">الاسم الكامل</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <input
                            type="text"
                            id="full_name"
                            name="full_name"
                            value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-hospital focus:ring-2 focus:ring-hospital-primary focus:border-transparent transition-all"
                            placeholder="أدخل اسمك الكامل"
                            required
                        >
                    </div>
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">البريد الإلكتروني</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </div>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-hospital focus:ring-2 focus:ring-hospital-primary focus:border-transparent transition-all"
                            placeholder="أدخل بريدك الإلكتروني"
                            required
                        >
                    </div>
                </div>

                <!-- Phone -->
                <div>
                    <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">رقم الهاتف</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-phone text-gray-400"></i>
                        </div>
                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-hospital focus:ring-2 focus:ring-hospital-primary focus:border-transparent transition-all"
                            placeholder="أدخل رقم هاتفك"
                            required
                        >
                    </div>
                </div>

                <!-- Date of Birth -->
                <div>
                    <label for="date_of_birth" class="block text-sm font-semibold text-gray-700 mb-2">تاريخ الميلاد</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-calendar text-gray-400"></i>
                        </div>
                        <input
                            type="date"
                            id="date_of_birth"
                            name="date_of_birth"
                            value="<?php echo isset($_POST['date_of_birth']) ? htmlspecialchars($_POST['date_of_birth']) : ''; ?>"
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-hospital focus:ring-2 focus:ring-hospital-primary focus:border-transparent transition-all"
                            required
                        >
                    </div>
                    <div class="date-requirements text-sm text-gray-500 mt-1">
                        يجب أن يكون عمرك 12 سنة على الأقل
                    </div>
                </div>

                <!-- Gender -->
                <div>
                    <label for="gender" class="block text-sm font-semibold text-gray-700 mb-2">الجنس</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-venus-mars text-gray-400"></i>
                        </div>
                        <select
                            id="gender"
                            name="gender"
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-hospital focus:ring-2 focus:ring-hospital-primary focus:border-transparent transition-all"
                            required
                        >
                            <option value="">اختر الجنس</option>
                            <option value="ذكر" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'ذكر') ? 'selected' : ''; ?>>ذكر</option>
                            <option value="أنثى" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'أنثى') ? 'selected' : ''; ?>>أنثى</option>
                        </select>
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">كلمة المرور</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-hospital focus:ring-2 focus:ring-hospital-primary focus:border-transparent transition-all"
                            placeholder="أدخل كلمة المرور"
                            required
                        >
                    </div>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="confirm_password" class="block text-sm font-semibold text-gray-700 mb-2">تأكيد كلمة المرور</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-hospital focus:ring-2 focus:ring-hospital-primary focus:border-transparent transition-all"
                            placeholder="أعد إدخال كلمة المرور"
                            required
                        >
                    </div>
                </div>
            </div>

            <!-- Terms and Conditions -->
            <div class="flex items-start space-x-3 space-x-reverse">
                <input type="checkbox" id="terms" name="terms" class="mt-1 h-4 w-4 text-hospital-primary focus:ring-hospital-primary border-gray-300 rounded" required>
                <label for="terms" class="text-sm text-gray-600">
                    أوافق على <a href="#" class="text-hospital-primary hover:text-blue-700">الشروط والأحكام</a> و <a href="#" class="text-hospital-primary hover:text-blue-700">سياسة الخصوصية</a>
                </label>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="hospital-btn-success w-full text-lg py-4">
                <i class="fas fa-user-plus ml-2"></i>
                إنشاء الحساب
            </button>
        </form>

        <!-- Divider -->
        <div class="relative my-8">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-300"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="px-2 bg-white text-gray-500">أو</span>
            </div>
        </div>

        <!-- Social Register -->
        <div class="grid grid-cols-2 gap-4 mb-8">
            <a href="#" class="flex items-center justify-center px-4 py-3 border border-gray-300 rounded-hospital text-gray-700 hover:bg-gray-50 transition-colors">
                <i class="fab fa-google text-red-500 ml-2"></i>
                جوجل
            </a>
            <a href="#" class="flex items-center justify-center px-4 py-3 border border-gray-300 rounded-hospital text-gray-700 hover:bg-gray-50 transition-colors">
                <i class="fab fa-facebook-f text-blue-600 ml-2"></i>
                فيسبوك
            </a>
        </div>

        <!-- Login Link -->
        <div class="text-center">
            <p class="text-gray-600">
                لديك حساب بالفعل؟
                <a href="login_tailwind.php" class="text-hospital-primary hover:text-blue-700 font-semibold transition-colors">
                    سجل دخولك
                </a>
            </p>
        </div>
    </div>

    <script>
        // Date validation
        const dateInput = document.getElementById('date_of_birth');
        const dateRequirements = document.querySelector('.date-requirements');

        dateInput.addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const today = new Date();
            const minAgeDate = new Date();
            minAgeDate.setFullYear(today.getFullYear() - 12);

            if (selectedDate > minAgeDate) {
                dateRequirements.textContent = 'يجب أن يكون عمرك 12 سنة على الأقل';
                dateRequirements.className = 'date-requirements text-sm text-red-500 mt-1';
                this.setCustomValidity('يجب أن يكون عمرك 12 سنة على الأقل');
            } else {
                dateRequirements.textContent = 'تاريخ الميلاد صحيح';
                dateRequirements.className = 'date-requirements text-sm text-green-500 mt-1';
                this.setCustomValidity('');
            }
        });

        dateInput.addEventListener('input', function() {
            if (this.value === '') {
                dateRequirements.textContent = 'يجب أن يكون عمرك 12 سنة على الأقل';
                dateRequirements.className = 'date-requirements text-sm text-gray-500 mt-1';
            }
        });
    </script>
</body>
</html>
