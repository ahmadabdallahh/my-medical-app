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

// Handle Status Update
if (isset($_POST['update_status'])) {
    $appointment_id = $_POST['appointment_id'];
    $new_status = $_POST['status'];
    
    // Verify appointment belongs to this hospital
    $check = $conn->prepare("
        SELECT a.id 
        FROM appointments a 
        JOIN clinics c ON a.clinic_id = c.id 
        WHERE a.id = ? AND c.hospital_id = ?
    ");
    $check->execute([$appointment_id, $hospital_id]);
    
    if ($check->fetch()) {
        $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $appointment_id]);
        $success_msg = "تم تحديث حالة الموعد بنجاح";
    }
}

// Filters
$status_filter = $_GET['status'] ?? '';
$date_filter = $_GET['date'] ?? '';
$doctor_filter = $_GET['doctor_id'] ?? '';

// Build Query
$query = "
    SELECT a.*, u.full_name as patient_name, u.phone as patient_phone,
           d.full_name as doctor_name, c.name as clinic_name
    FROM appointments a
    JOIN users u ON a.user_id = u.id
    JOIN doctors d ON a.doctor_id = d.id
    JOIN clinics c ON a.clinic_id = c.id
    WHERE c.hospital_id = ?
";
$params = [$hospital_id];

if ($status_filter) {
    $query .= " AND a.status = ?";
    $params[] = $status_filter;
}
if ($date_filter) {
    $query .= " AND a.appointment_date = ?";
    $params[] = $date_filter;
}
if ($doctor_filter) {
    $query .= " AND a.doctor_id = ?";
    $params[] = $doctor_filter;
}

$query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Doctors for Filter
$stmt = $conn->prepare("SELECT id, full_name FROM doctors WHERE hospital_id = ?");
$stmt->execute([$hospital_id]);
$doctors_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-800">إدارة المواعيد</h1>
    <p class="text-gray-600 mt-2">عرض وإدارة جميع المواعيد في المستشفى</p>
</div>

<?php if (isset($success_msg)): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline"><?php echo $success_msg; ?></span>
    </div>
<?php endif; ?>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">الحالة</label>
            <select name="status" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                <option value="">الكل</option>
                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>قيد الانتظار</option>
                <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>مؤكد</option>
                <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>مكتمل</option>
                <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>ملغي</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">التاريخ</label>
            <input type="date" name="date" value="<?php echo $date_filter; ?>" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">الطبيب</label>
            <select name="doctor_id" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                <option value="">الكل</option>
                <?php foreach ($doctors_list as $doc): ?>
                    <option value="<?php echo $doc['id']; ?>" <?php echo $doctor_filter == $doc['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($doc['full_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-filter ml-2"></i> تصفية
            </button>
        </div>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-right">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">المريض</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">الطبيب / العيادة</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">الموعد</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">الحالة</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (empty($appointments)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">لا توجد مواعيد مطابقة للبحث</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($appointments as $appointment): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($appointment['patient_name']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($appointment['patient_phone']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($appointment['doctor_name']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($appointment['clinic_name']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo $appointment['appointment_date']; ?></div>
                                <div class="text-sm text-gray-500"><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $status_colors = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'confirmed' => 'bg-green-100 text-green-800',
                                    'completed' => 'bg-blue-100 text-blue-800',
                                    'cancelled' => 'bg-red-100 text-red-800'
                                ];
                                $status_text = [
                                    'pending' => 'قيد الانتظار',
                                    'confirmed' => 'مؤكد',
                                    'completed' => 'مكتمل',
                                    'cancelled' => 'ملغي'
                                ];
                                $status = $appointment['status'];
                                $color = $status_colors[$status] ?? 'bg-gray-100 text-gray-800';
                                $text = $status_text[$status] ?? $status;
                                ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $color; ?>">
                                    <?php echo $text; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <form method="POST" class="inline-flex gap-2">
                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                    <?php if ($status == 'pending'): ?>
                                        <button type="submit" name="update_status" value="confirmed" class="text-green-600 hover:text-green-900" title="تأكيد">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button type="submit" name="update_status" value="cancelled" class="text-red-600 hover:text-red-900" title="إلغاء">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php elseif ($status == 'confirmed'): ?>
                                        <button type="submit" name="update_status" value="completed" class="text-blue-600 hover:text-blue-900" title="إكمال">
                                            <i class="fas fa-check-double"></i>
                                        </button>
                                        <button type="submit" name="update_status" value="cancelled" class="text-red-600 hover:text-red-900" title="إلغاء">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php endif; ?>
                                    <!-- Hidden input to pass status value when button clicked -->
                                    <input type="hidden" name="status" id="status_<?php echo $appointment['id']; ?>">
                                </form>
                                <script>
                                    // Simple script to set status before submit
                                    document.querySelectorAll('button[name="update_status"]').forEach(btn => {
                                        btn.addEventListener('click', function(e) {
                                            e.preventDefault();
                                            const form = this.closest('form');
                                            const statusInput = form.querySelector('input[name="status"]');
                                            statusInput.value = this.value;
                                            form.submit();
                                        });
                                    });
                                </script>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
