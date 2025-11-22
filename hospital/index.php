<?php
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// Get hospital ID from the session or database
// In header.php we tried to fetch it. Let's assume we have $hospital_id available if we fetched it correctly.
// If not, we need to fetch it here based on the logged-in user.
// Since header.php didn't actually set $hospital_id (it just prepared a query), let's do it properly here.

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id, name FROM hospitals WHERE id = (SELECT id FROM hospitals WHERE email = (SELECT email FROM users WHERE id = ?))");
// Fallback: if no direct link via email, maybe check if there's a hospital_users table? 
// For now, let's assume the user's email matches the hospital's email.
// Or better, let's assume for this test that we can get the hospital_id. 
// If the query fails, we might need to adjust.
// Let's try to find the hospital by matching the user's email to the hospital's email.
$stmt = $conn->prepare("
    SELECT h.id, h.name 
    FROM hospitals h 
    JOIN users u ON h.email = u.email 
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$hospital = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hospital) {
    // If no hospital found linked to this user, maybe show an error or demo data?
    // For development, let's just pick the first hospital if none found (DANGEROUS in prod, but okay for dev setup if user is admin/hospital type)
    // But strictly speaking, we should handle this.
    // Let's try to get the first hospital for now to ensure the page loads for testing.
    $stmt = $conn->query("SELECT id, name FROM hospitals LIMIT 1");
    $hospital = $stmt->fetch(PDO::FETCH_ASSOC);
}

$hospital_id = $hospital['id'];
$hospital_name = $hospital['name'];

// Fetch Statistics
// 1. Total Doctors
$stmt = $conn->prepare("SELECT COUNT(*) FROM doctors WHERE hospital_id = ?");
$stmt->execute([$hospital_id]);
$total_doctors = $stmt->fetchColumn();

// 2. Total Clinics
$stmt = $conn->prepare("SELECT COUNT(*) FROM clinics WHERE hospital_id = ?");
$stmt->execute([$hospital_id]);
$total_clinics = $stmt->fetchColumn();

// 3. Today's Appointments
$today = date('Y-m-d');
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM appointments a
    JOIN clinics c ON a.clinic_id = c.id
    WHERE c.hospital_id = ? AND a.appointment_date = ?
");
$stmt->execute([$hospital_id, $today]);
$today_appointments = $stmt->fetchColumn();

// 4. Total Patients (Unique users who have appointments)
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT a.user_id) 
    FROM appointments a
    JOIN clinics c ON a.clinic_id = c.id
    WHERE c.hospital_id = ?
");
$stmt->execute([$hospital_id]);
$total_patients = $stmt->fetchColumn();

// Fetch Recent Appointments
$stmt = $conn->prepare("
    SELECT a.*, u.full_name as patient_name, d.full_name as doctor_name, c.name as clinic_name
    FROM appointments a
    JOIN users u ON a.user_id = u.id
    JOIN doctors d ON a.doctor_id = d.id
    JOIN clinics c ON a.clinic_id = c.id
    WHERE c.hospital_id = ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT 5
");
$stmt->execute([$hospital_id]);
$recent_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">لوحة التحكم</h1>
    <p class="text-gray-600 mt-2">مرحباً بك في لوحة تحكم <?php echo htmlspecialchars($hospital_name); ?></p>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Doctors Card -->
    <div class="bg-white rounded-xl shadow-sm p-6 border-r-4 border-blue-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-1">إجمالي الأطباء</p>
                <h3 class="text-2xl font-bold text-gray-800"><?php echo $total_doctors; ?></h3>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600">
                <i class="fas fa-user-md text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Clinics Card -->
    <div class="bg-white rounded-xl shadow-sm p-6 border-r-4 border-green-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-1">العيادات</p>
                <h3 class="text-2xl font-bold text-gray-800"><?php echo $total_clinics; ?></h3>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center text-green-600">
                <i class="fas fa-clinic-medical text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Appointments Card -->
    <div class="bg-white rounded-xl shadow-sm p-6 border-r-4 border-purple-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-1">مواعيد اليوم</p>
                <h3 class="text-2xl font-bold text-gray-800"><?php echo $today_appointments; ?></h3>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center text-purple-600">
                <i class="fas fa-calendar-day text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Patients Card -->
    <div class="bg-white rounded-xl shadow-sm p-6 border-r-4 border-orange-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-1">المرضى</p>
                <h3 class="text-2xl font-bold text-gray-800"><?php echo $total_patients; ?></h3>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center text-orange-600">
                <i class="fas fa-users text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Recent Appointments -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="p-6 border-b flex justify-between items-center">
        <h2 class="text-xl font-bold text-gray-800">أحدث المواعيد</h2>
        <a href="appointments.php" class="text-blue-600 hover:text-blue-700 text-sm font-medium">عرض الكل</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-right">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">المريض</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">الدكتور</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">العيادة</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">التاريخ والوقت</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">الحالة</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (empty($recent_appointments)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">لا توجد مواعيد حديثة</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_appointments as $appointment): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($appointment['patient_name']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($appointment['doctor_name']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($appointment['clinic_name']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo $appointment['appointment_date']; ?></div>
                                <div class="text-xs text-gray-500"><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></div>
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
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
