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

$clinic_id = $_GET['id'] ?? null;
$clinic = null;
$is_edit = false;

// Fetch Specialties
$specialties = $conn->query("SELECT * FROM specialties ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

if ($clinic_id) {
    $is_edit = true;
    $stmt = $conn->prepare("SELECT * FROM clinics WHERE id = ? AND hospital_id = ?");
    $stmt->execute([$clinic_id, $hospital_id]);
    $clinic = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$clinic) {
        echo "<div class='p-6'>عيادة غير موجودة</div>";
        require_once 'includes/footer.php';
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $specialty_id = $_POST['specialty_id'];
    $description = $_POST['description'];
    $consultation_fee = $_POST['consultation_fee'];
    
    if ($is_edit) {
        // Update Clinic
        $stmt = $conn->prepare("
            UPDATE clinics SET 
                name = ?, specialty_id = ?, description = ?, consultation_fee = ?
            WHERE id = ?
        ");
        $stmt->execute([$name, $specialty_id, $description, $consultation_fee, $clinic_id]);
        $success_msg = "تم تحديث بيانات العيادة بنجاح";
        
        // Refresh data
        $stmt = $conn->prepare("SELECT * FROM clinics WHERE id = ?");
        $stmt->execute([$clinic_id]);
        $clinic = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } else {
        // Add New Clinic
        $stmt = $conn->prepare("
            INSERT INTO clinics (hospital_id, name, specialty_id, description, consultation_fee)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$hospital_id, $name, $specialty_id, $description, $consultation_fee]);
        
        $success_msg = "تم إضافة العيادة بنجاح";
        $is_edit = true;
        $clinic_id = $conn->lastInsertId();
        // Refresh data
        $stmt = $conn->prepare("SELECT * FROM clinics WHERE id = ?");
        $stmt->execute([$clinic_id]);
        $clinic = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>

<div class="max-w-4xl mx-auto">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-800"><?php echo $is_edit ? 'تعديل بيانات العيادة' : 'إضافة عيادة جديدة'; ?></h1>
        <a href="clinics.php" class="text-gray-600 hover:text-gray-800">
            <i class="fas fa-arrow-right ml-2"></i> عودة للقائمة
        </a>
    </div>

    <?php if (isset($success_msg)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
            <?php echo $success_msg; ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-lg p-8 border border-gray-100">
        <form method="POST">
            <div class="grid grid-cols-1 gap-8">
                <!-- Clinic Name -->
                <div class="relative">
                    <label class="block text-sm font-bold text-gray-700 mb-2">اسم العيادة</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <i class="fas fa-clinic-medical text-gray-400"></i>
                        </div>
                        <input type="text" name="name" value="<?php echo $clinic['name'] ?? ''; ?>" required 
                               class="w-full pr-10 pl-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white"
                               placeholder="مثال: عيادة الباطنة">
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
                                <option value="<?php echo $spec['id']; ?>" <?php echo ($clinic['specialty_id'] ?? '') == $spec['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($spec['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="relative">
                    <label class="block text-sm font-bold text-gray-700 mb-2">الوصف</label>
                    <div class="relative">
                        <div class="absolute top-3 right-0 pr-3 pointer-events-none">
                            <i class="fas fa-align-right text-gray-400"></i>
                        </div>
                        <textarea name="description" rows="4" 
                                  class="w-full pr-10 pl-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white"
                                  placeholder="اكتب وصفاً مختصراً للعيادة..."><?php echo $clinic['description'] ?? ''; ?></textarea>
                    </div>
                </div>

                <!-- Consultation Fee -->
                <div class="relative">
                    <label class="block text-sm font-bold text-gray-700 mb-2">سعر الكشف الافتراضي</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <i class="fas fa-money-bill-wave text-gray-400"></i>
                        </div>
                        <input type="number" name="consultation_fee" value="<?php echo $clinic['consultation_fee'] ?? ''; ?>" 
                               class="w-full pr-10 pl-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white"
                               placeholder="0.00">
                    </div>
                </div>
            </div>

            <div class="mt-8 flex justify-end pt-6 border-t border-gray-100">
                <button type="submit" class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-8 py-3 rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg hover:shadow-blue-500/30 flex items-center gap-2 font-bold">
                    <i class="fas fa-save"></i>
                    <span><?php echo $is_edit ? 'حفظ التغييرات' : 'إضافة العيادة'; ?></span>
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
