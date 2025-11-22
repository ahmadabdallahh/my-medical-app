<?php
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// Get hospital ID
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT h.id FROM hospitals h JOIN users u ON h.email = u.email WHERE u.id = ?");
$stmt->execute([$user_id]);
$hospital = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$hospital) {
    $stmt = $conn->query("SELECT id FROM hospitals LIMIT 1");
    $hospital = $stmt->fetch(PDO::FETCH_ASSOC);
}
$hospital_id = $hospital['id'];

$doctor_id = $_GET['id'] ?? null;
$doctor = null;
$is_edit = false;

// Fetch Specialties and Clinics for dropdowns
$specialties = $conn->query("SELECT * FROM specialties ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$stmt = $conn->prepare("SELECT * FROM clinics WHERE hospital_id = ? ORDER BY name");
$stmt->execute([$hospital_id]);
$clinics = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($doctor_id) {
    $is_edit = true;
    $stmt = $conn->prepare("SELECT * FROM doctors WHERE id = ? AND hospital_id = ?");
    $stmt->execute([$doctor_id, $hospital_id]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$doctor) {
        echo "<div class='p-6'>طبيب غير موجود</div>";
        require_once 'includes/footer.php';
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $specialty_id = $_POST['specialty_id'];
    $clinic_id = $_POST['clinic_id'];
    $consultation_fee = $_POST['consultation_fee'];
    
    if ($is_edit) {
        // Update Doctor
        $stmt = $conn->prepare("
            UPDATE doctors SET 
                full_name = ?, email = ?, phone = ?, specialty_id = ?, 
                clinic_id = ?, consultation_fee = ?
            WHERE id = ?
        ");
        $stmt->execute([$full_name, $email, $phone, $specialty_id, $clinic_id, $consultation_fee, $doctor_id]);
        $success_msg = "تم تحديث بيانات الطبيب بنجاح";
        
        // Refresh doctor data
        $stmt = $conn->prepare("SELECT * FROM doctors WHERE id = ?");
        $stmt->execute([$doctor_id]);
        $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } else {
        // Add New Doctor
        $password = $_POST['password'];
        
        // 1. Create User
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, full_name, email, password, phone, user_type, role) VALUES (?, ?, ?, ?, ?, 'doctor', 'doctor')");
        try {
            $stmt->execute([$email, $full_name, $email, $hashed_password, $phone]);
            $new_user_id = $conn->lastInsertId();
            
            // 2. Create Doctor
            $stmt = $conn->prepare("
                INSERT INTO doctors (user_id, full_name, email, phone, specialty_id, clinic_id, hospital_id, consultation_fee, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([$new_user_id, $full_name, $email, $phone, $specialty_id, $clinic_id, $hospital_id, $consultation_fee]);
            
            $success_msg = "تم إضافة الطبيب بنجاح";
            $is_edit = true; // Switch to edit mode
            $doctor_id = $conn->lastInsertId();
             // Refresh doctor data
            $stmt = $conn->prepare("SELECT * FROM doctors WHERE id = ?");
            $stmt->execute([$doctor_id]);
            $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            $error_msg = "خطأ: " . $e->getMessage();
        }
    }
}
?>

<div class="max-w-4xl mx-auto">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-800"><?php echo $is_edit ? 'تعديل بيانات الطبيب' : 'إضافة طبيب جديد'; ?></h1>
        <a href="doctors.php" class="text-gray-600 hover:text-gray-800">
            <i class="fas fa-arrow-right ml-2"></i> عودة للقائمة
        </a>
    </div>

    <?php if (isset($success_msg)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
            <?php echo $success_msg; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error_msg)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            <?php echo $error_msg; ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-lg p-8 border border-gray-100">
        <form method="POST">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Full Name -->
                <div class="relative">
                    <label class="block text-sm font-bold text-gray-700 mb-2">الاسم الكامل</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <input type="text" name="full_name" value="<?php echo $doctor['full_name'] ?? ''; ?>" required 
                               class="w-full pr-10 pl-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white"
                               placeholder="د. محمد أحمد">
                    </div>
                </div>
                
                <!-- Email -->
                <div class="relative">
                    <label class="block text-sm font-bold text-gray-700 mb-2">البريد الإلكتروني</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </div>
                        <input type="email" name="email" value="<?php echo $doctor['email'] ?? ''; ?>" required 
                               class="w-full pr-10 pl-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white"
                               placeholder="doctor@example.com">
                    </div>
                </div>

                <?php if (!$is_edit): ?>
                <!-- Password -->
                <div class="relative">
                    <label class="block text-sm font-bold text-gray-700 mb-2">كلمة المرور</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" name="password" required 
                               class="w-full pr-10 pl-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white"
                               placeholder="********">
                    </div>
                </div>
                <?php endif; ?>

                <!-- Phone -->
                <div class="relative">
                    <label class="block text-sm font-bold text-gray-700 mb-2">رقم الهاتف</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <i class="fas fa-phone text-gray-400"></i>
                        </div>
                        <input type="text" name="phone" value="<?php echo $doctor['phone'] ?? ''; ?>" 
                               class="w-full pr-10 pl-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white"
                               placeholder="01xxxxxxxxx">
                    </div>
                </div>

                <!-- Specialty -->
                <div class="relative">
                    <label class="block text-sm font-bold text-gray-700 mb-2">التخصص</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <i class="fas fa-stethoscope text-gray-400"></i>
                        </div>
                        <select name="specialty_id" class="w-full pr-10 pl-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white appearance-none">
                            <option value="">اختر التخصص</option>
                            <?php foreach ($specialties as $spec): ?>
                                <option value="<?php echo $spec['id']; ?>" <?php echo ($doctor['specialty_id'] ?? '') == $spec['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($spec['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                        </div>
                    </div>
                </div>

                <!-- Clinic -->
                <div class="relative">
                    <label class="block text-sm font-bold text-gray-700 mb-2">العيادة</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <i class="fas fa-clinic-medical text-gray-400"></i>
                        </div>
                        <select name="clinic_id" class="w-full pr-10 pl-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white appearance-none">
                            <option value="">اختر العيادة</option>
                            <?php foreach ($clinics as $clinic): ?>
                                <option value="<?php echo $clinic['id']; ?>" <?php echo ($doctor['clinic_id'] ?? '') == $clinic['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($clinic['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                        </div>
                    </div>
                </div>

                <!-- Consultation Fee -->
                <div class="relative">
                    <label class="block text-sm font-bold text-gray-700 mb-2">سعر الكشف</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <i class="fas fa-money-bill-wave text-gray-400"></i>
                        </div>
                        <input type="number" name="consultation_fee" value="<?php echo $doctor['consultation_fee'] ?? ''; ?>" 
                               class="w-full pr-10 pl-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white"
                               placeholder="0.00">
                    </div>
                </div>
            </div>

            <div class="mt-8 flex justify-end pt-6 border-t border-gray-100">
                <button type="submit" class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-8 py-3 rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg hover:shadow-blue-500/30 flex items-center gap-2 font-bold">
                    <i class="fas fa-save"></i>
                    <span><?php echo $is_edit ? 'حفظ التغييرات' : 'إضافة الطبيب'; ?></span>
                </button>
            </div>
        </form>
    </div>

<?php require_once 'includes/footer.php'; ?>
