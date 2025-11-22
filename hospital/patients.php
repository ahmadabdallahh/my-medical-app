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

// Fetch Patients (Unique users who have appointments in this hospital)
$stmt = $conn->prepare("
    SELECT DISTINCT u.id, u.full_name, u.email, u.phone, u.created_at,
           (SELECT COUNT(*) FROM appointments a 
            JOIN clinics c ON a.clinic_id = c.id 
            WHERE a.user_id = u.id AND c.hospital_id = ?) as appointment_count,
           (SELECT MAX(appointment_date) FROM appointments a 
            JOIN clinics c ON a.clinic_id = c.id 
            WHERE a.user_id = u.id AND c.hospital_id = ?) as last_visit
    FROM users u
    JOIN appointments a ON u.id = a.user_id
    JOIN clinics c ON a.clinic_id = c.id
    WHERE c.hospital_id = ?
    ORDER BY last_visit DESC
");
$stmt->execute([$hospital_id, $hospital_id, $hospital_id]);
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-800">سجل المرضى</h1>
    <p class="text-gray-600 mt-2">قائمة بالمرضى الذين قاموا بزيارة المستشفى</p>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-right">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">المريض</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">معلومات الاتصال</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">عدد الزيارات</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">آخر زيارة</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">تاريخ التسجيل</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (empty($patients)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">لا يوجد مرضى مسجلين حتى الآن</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($patients as $patient): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                            <span class="font-bold text-sm"><?php echo mb_substr($patient['full_name'], 0, 1, 'UTF-8'); ?></span>
                                        </div>
                                    </div>
                                    <div class="mr-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($patient['full_name']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><i class="fas fa-phone ml-1 text-gray-400"></i> <?php echo htmlspecialchars($patient['phone'] ?? 'غير متوفر'); ?></div>
                                <div class="text-sm text-gray-500"><i class="fas fa-envelope ml-1 text-gray-400"></i> <?php echo htmlspecialchars($patient['email']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    <?php echo $patient['appointment_count']; ?> زيارات
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $patient['last_visit']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('Y-m-d', strtotime($patient['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="appointments.php?patient_id=<?php echo $patient['id']; ?>" class="text-blue-600 hover:text-blue-900">
                                    سجل المواعيد
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
