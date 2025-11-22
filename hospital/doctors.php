<?php
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// Get hospital ID (Same logic as index.php)
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT h.id, h.name 
    FROM hospitals h 
    JOIN users u ON h.email = u.email 
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$hospital = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hospital) {
    $stmt = $conn->query("SELECT id, name FROM hospitals LIMIT 1");
    $hospital = $stmt->fetch(PDO::FETCH_ASSOC);
}
$hospital_id = $hospital['id'];

// Handle Delete Action
if (isset($_POST['delete_doctor'])) {
    $doctor_id = $_POST['doctor_id'];
    // Verify doctor belongs to this hospital
    $check = $conn->prepare("SELECT id FROM doctors WHERE id = ? AND hospital_id = ?");
    $check->execute([$doctor_id, $hospital_id]);
    if ($check->fetch()) {
        $stmt = $conn->prepare("DELETE FROM doctors WHERE id = ?");
        $stmt->execute([$doctor_id]);
        $success_msg = "تم حذف الطبيب بنجاح";
    }
}

// Fetch Doctors
$stmt = $conn->prepare("
    SELECT d.*, s.name as specialty_name, c.name as clinic_name
    FROM doctors d
    LEFT JOIN specialties s ON d.specialty_id = s.id
    LEFT JOIN clinics c ON d.clinic_id = c.id
    WHERE d.hospital_id = ?
    ORDER BY d.created_at DESC
");
$stmt->execute([$hospital_id]);
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">إدارة الأطباء</h1>
        <p class="text-gray-600 mt-2">قائمة بجميع الأطباء في المستشفى</p>
    </div>
    <a href="doctor_form.php" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2">
        <i class="fas fa-plus"></i>
        <span>إضافة طبيب جديد</span>
    </a>
</div>

<?php if (isset($success_msg)): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline"><?php echo $success_msg; ?></span>
    </div>
<?php endif; ?>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-right">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">الطبيب</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">التخصص</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">العيادة</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">التقييم</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">الحالة</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (empty($doctors)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">لا يوجد أطباء مسجلين حالياً</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($doctors as $doctor): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <?php if ($doctor['image']): ?>
                                            <img class="h-10 w-10 rounded-full object-cover" src="../<?php echo htmlspecialchars($doctor['image']); ?>" alt="">
                                        <?php else: ?>
                                            <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500">
                                                <i class="fas fa-user-md"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mr-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($doctor['full_name']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($doctor['email'] ?? ''); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($doctor['specialty_name'] ?? 'غير محدد'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($doctor['clinic_name'] ?? 'غير محدد'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center text-yellow-400">
                                    <span class="text-gray-600 ml-1 text-sm"><?php echo $doctor['rating']; ?></span>
                                    <i class="fas fa-star"></i>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $doctor['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo $doctor['is_active'] ? 'نشط' : 'غير نشط'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center gap-3">
                                    <a href="doctor_form.php?id=<?php echo $doctor['id']; ?>" class="text-blue-600 hover:text-blue-900" title="تعديل">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا الطبيب؟');" class="inline">
                                        <input type="hidden" name="doctor_id" value="<?php echo $doctor['id']; ?>">
                                        <button type="submit" name="delete_doctor" class="text-red-600 hover:text-red-900" title="حذف">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
