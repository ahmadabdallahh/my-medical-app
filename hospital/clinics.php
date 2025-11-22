<?php
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// Get hospital ID
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
if (isset($_POST['delete_clinic'])) {
    $clinic_id = $_POST['clinic_id'];
    // Verify clinic belongs to this hospital
    $check = $conn->prepare("SELECT id FROM clinics WHERE id = ? AND hospital_id = ?");
    $check->execute([$clinic_id, $hospital_id]);
    if ($check->fetch()) {
        $stmt = $conn->prepare("DELETE FROM clinics WHERE id = ?");
        $stmt->execute([$clinic_id]);
        $success_msg = "تم حذف العيادة بنجاح";
    }
}

// Fetch Clinics
$stmt = $conn->prepare("
    SELECT c.*, s.name as specialty_name, 
           (SELECT COUNT(*) FROM doctors d WHERE d.clinic_id = c.id) as doctor_count
    FROM clinics c
    LEFT JOIN specialties s ON c.specialty_id = s.id
    WHERE c.hospital_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$hospital_id]);
$clinics = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">إدارة العيادات</h1>
        <p class="text-gray-600 mt-2">قائمة بجميع العيادات في المستشفى</p>
    </div>
    <a href="clinic_form.php" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2">
        <i class="fas fa-plus"></i>
        <span>إضافة عيادة جديدة</span>
    </a>
</div>

<?php if (isset($success_msg)): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline"><?php echo $success_msg; ?></span>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($clinics)): ?>
        <div class="col-span-full text-center py-12 bg-white rounded-xl shadow-sm">
            <div class="text-gray-400 mb-4">
                <i class="fas fa-clinic-medical text-6xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">لا توجد عيادات</h3>
            <p class="text-gray-500">لم يتم إضافة أي عيادات بعد</p>
        </div>
    <?php else: ?>
        <?php foreach ($clinics as $clinic): ?>
            <div class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600">
                            <i class="fas fa-clinic-medical text-xl"></i>
                        </div>
                        <div class="flex gap-2">
                            <a href="clinic_form.php?id=<?php echo $clinic['id']; ?>" class="text-gray-400 hover:text-blue-600 transition-colors">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذه العيادة؟');" class="inline">
                                <input type="hidden" name="clinic_id" value="<?php echo $clinic['id']; ?>">
                                <button type="submit" name="delete_clinic" class="text-gray-400 hover:text-red-600 transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <h3 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($clinic['name']); ?></h3>
                    <p class="text-sm text-gray-500 mb-4 line-clamp-2"><?php echo htmlspecialchars($clinic['description'] ?? 'لا يوجد وصف'); ?></p>
                    
                    <div class="space-y-2 text-sm text-gray-600">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-stethoscope w-5 text-center"></i>
                            <span><?php echo htmlspecialchars($clinic['specialty_name'] ?? 'عام'); ?></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-user-md w-5 text-center"></i>
                            <span><?php echo $clinic['doctor_count']; ?> أطباء</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-money-bill-wave w-5 text-center"></i>
                            <span><?php echo number_format($clinic['consultation_fee'], 2); ?> ج.م</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
