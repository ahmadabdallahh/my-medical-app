<?php
require_once '../includes/functions.php';
require_once '../config/database.php';

// Ensure only admins can access this page
if (!check_user_role('admin')) {
    redirect('../login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Handle POST requests for updating status or deleting appointments
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $appointment_id = $_POST['appointment_id'];
        $new_status = $_POST['new_status'];
        update_appointment_status($conn, $appointment_id, $new_status);
    } elseif (isset($_POST['delete_appointment'])) {
        $appointment_id = $_POST['appointment_id'];
        delete_appointment($conn, $appointment_id);
    }
    // Redirect to the same page to see changes and prevent form resubmission
    redirect('manage_appointments.php');
}

// Fetch all appointments for display
$appointments = get_all_appointments($conn);

$page_title = "إدارة الحجوزات";
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Health Tech</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Alpine.js for dropdown menu -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body {
            font-family: 'Cairo', sans-serif;
        }
        .status-select {
            min-width: 120px;
        }
    </style>
</head>
<body class="bg-gray-100">

<div class="flex h-screen">
    <!-- Sidebar -->
    <?php include '../includes/dashboard_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden lg:mr-64">
        <?php include '../includes/dashboard_header.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
            <div class="container mx-auto">
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4"><?php echo $page_title; ?></h3>

                    <!-- Appointments Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3">اسم المريض</th>
                                    <th scope="col" class="px-6 py-3">اسم الطبيب</th>
                                    <th scope="col" class="px-6 py-3">التاريخ</th>
                                    <th scope="col" class="px-6 py-3">الوقت</th>
                                    <th scope="col" class="px-6 py-3">الحالة</th>
                                    <th scope="col" class="px-6 py-3">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($appointments)):
                                    foreach ($appointments as $appointment):
                                        $status_classes = [
                                            'confirmed' => 'text-green-700 bg-green-100',
                                            'pending' => 'text-yellow-700 bg-yellow-100',
                                            'cancelled' => 'text-red-700 bg-red-100',
                                        ];
                                        $status_class = $status_classes[$appointment['status']] ?? 'text-gray-700 bg-gray-100';
                                ?>
                                        <tr class="bg-white border-b hover:bg-gray-50">
                                            <td class="px-6 py-4 font-medium text-gray-900">
                                                <?php echo htmlspecialchars($appointment['patient_name']); ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php echo htmlspecialchars($appointment['doctor_name']); ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php echo date('d-m-Y', strtotime($appointment['appointment_date'])); ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="px-2 py-1 font-semibold leading-tight rounded-full <?php echo $status_class; ?>">
                                                    <?php echo htmlspecialchars($appointment['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 flex items-center space-x-2">
                                                <form action="manage_appointments.php" method="POST" class="inline-block">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                    <select name="new_status" class="p-1 border border-gray-300 rounded-md status-select" onchange="this.form.submit()">
                                                        <option value="pending" <?php echo $appointment['status'] === 'pending' ? 'selected' : ''; ?>>قيد الانتظار</option>
                                                        <option value="confirmed" <?php echo $appointment['status'] === 'confirmed' ? 'selected' : ''; ?>>مؤكد</option>
                                                        <option value="cancelled" <?php echo $appointment['status'] === 'cancelled' ? 'selected' : ''; ?>>ملغي</option>
                                                    </select>
                                                    <input type="hidden" name="update_status" value="1">
                                                </form>
                                                <form action="manage_appointments.php" method="POST" onsubmit="return confirm('هل أنت متأكد من رغبتك في حذف هذا الموعد؟');" class="inline-block">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                    <input type="hidden" name="delete_appointment" value="1">
                                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                <?php
                                    endforeach;
                                else:
                                ?>
                                    <tr class="bg-white border-b">
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                            لا توجد حجوزات لعرضها.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </main>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('sidebar');

        if (sidebarToggle && sidebar) {
            // Event to toggle sidebar visibility
            sidebarToggle.addEventListener('click', function (e) {
                e.stopPropagation();
                sidebar.classList.toggle('hidden');
            });

            // Event to hide sidebar when clicking outside
            document.addEventListener('click', function (e) {
                const isClickInsideSidebar = sidebar.contains(e.target);
                const isClickOnToggle = sidebarToggle.contains(e.target);

                if (!sidebar.classList.contains('hidden') && !isClickInsideSidebar && !isClickOnToggle) {
                    sidebar.classList.add('hidden');
                }
            });
        }
    });
</script>

</body>
</html>
