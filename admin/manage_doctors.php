<?php
require_once '../includes/functions.php';
require_once '../config/database.php';

// Ensure only admins can access this page
if (!check_user_role('admin')) {
    redirect('../login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Generate a new CSRF token for the forms on this page
$csrf_token = generate_csrf_token();

// Handle POST requests for updating status or deleting a doctor
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Validate CSRF Token
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        set_flash_message('خطأ في التحقق من الأمان. يرجى المحاولة مرة أخرى.', 'error');
        redirect('manage_doctors.php');
    }

    if (isset($_POST['update_status'])) {
        $user_id = $_POST['user_id'];
        $new_status = $_POST['new_status'];

        if (update_doctor_account_status($conn, $user_id, $new_status)) {
            set_flash_message('تم تحديث حالة الطبيب بنجاح.');
        } else {
            set_flash_message('حدث خطأ أثناء تحديث حالة الطبيب.', 'error');
        }

        header("Location: manage_doctors.php");
        exit();

    } elseif (isset($_POST['delete_doctor'])) {
        if (!validate_csrf_token($_POST['csrf_token'])) {
            die('Invalid CSRF token');
        }
        $user_id = $_POST['user_id'];
        if (delete_doctor_by_user_id($conn, $user_id)) {
            set_flash_message('تم حذف الطبيب بنجاح.');
        } else {
            set_flash_message('حدث خطأ أثناء حذف الطبيب.', 'error');
        }
    }
    // Redirect to the same page to see changes and prevent form resubmission
    redirect('manage_doctors.php');
}

// Fetch all doctors for display
$doctors = get_all_doctors_with_details($conn);

$page_title = "إدارة الأطباء";
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
        table {
            border-collapse: separate;
            border-spacing: 0;
        }
        thead th {
            position: sticky;
            top: 0;
            z-index: 10;
        }
        tbody tr {
            transition: all 0.2s ease;
        }
        tbody tr:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body class="bg-gray-100">

<div class="flex h-screen">
    <?php include '../includes/dashboard_sidebar.php'; ?>

    <div class="flex-1 flex flex-col overflow-hidden lg:mr-64">
        <?php include '../includes/dashboard_header.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
            <div class="container mx-auto">
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4"><?php echo $page_title; ?></h3>

                    <!-- Doctors Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3">اسم الطبيب</th>
                                    <th scope="col" class="px-6 py-3">التخصص</th>
                                    <th scope="col" class="px-6 py-3">البريد الإلكتروني</th>
                                    <th scope="col" class="px-6 py-3">الحالة</th>
                                    <th scope="col" class="px-6 py-3">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($doctors)):
                                    foreach ($doctors as $doctor):
                                        // Simplified and corrected status mapping
                                        $status = $doctor['status'] ?? 'pending';
                                        $status_map = [
                                            'approved' => [
                                                'text' => 'معتمد',
                                                'class' => 'text-green-800 bg-green-100 border border-green-200',
                                                'icon' => 'fa-check-circle'
                                            ],
                                            'pending'  => [
                                                'text' => 'معلق',
                                                'class' => 'text-yellow-800 bg-yellow-100 border border-yellow-200',
                                                'icon' => 'fa-clock'
                                            ],
                                            'suspended'=> [
                                                'text' => 'موقوف',
                                                'class' => 'text-red-800 bg-red-100 border border-red-200',
                                                'icon' => 'fa-ban'
                                            ]
                                        ];
                                        $status_info = $status_map[$status] ?? $status_map['pending'];
                                        $status_text = $status_info['text'];
                                        $status_class = $status_info['class'];
                                        $status_icon = $status_info['icon'];
                                ?>
                                        <tr class="bg-white border-b hover:bg-gray-50 transition-colors duration-150">
                                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <i class="fas fa-user-md text-blue-400 ml-2"></i>
                                                    <span><?php echo htmlspecialchars($doctor['full_name']); ?></span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center text-gray-600">
                                                    <i class="fas fa-stethoscope text-gray-400 ml-2"></i>
                                                    <span><?php echo htmlspecialchars($doctor['specialization'] ?? 'غير محدد'); ?></span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center text-gray-600">
                                                    <i class="fas fa-envelope text-gray-400 ml-2"></i>
                                                    <span><?php echo htmlspecialchars($doctor['email']); ?></span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="px-3 py-1.5 text-xs font-semibold leading-tight rounded-full <?php echo $status_class; ?> inline-flex items-center">
                                                    <i class="fas <?php echo $status_icon; ?> ml-1"></i>
                                                    <?php echo htmlspecialchars($status_text); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center space-x-2 space-x-reverse gap-2">
                                                    <form method="POST" action="manage_doctors.php" class="flex items-center gap-2">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                        <input type="hidden" name="user_id" value="<?php echo $doctor['user_id']; ?>">
                                                        <select name="new_status" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 px-3 py-2 min-w-[140px] shadow-sm hover:border-gray-400 transition-colors">
                                                            <option value="approved" <?php if (isset($doctor['status']) && $doctor['status'] == 'approved') echo 'selected'; ?>>معتمد</option>
                                                            <option value="pending" <?php if (!isset($doctor['status']) || $doctor['status'] == 'pending') echo 'selected'; ?>>معلق</option>
                                                            <option value="suspended" <?php if (isset($doctor['status']) && $doctor['status'] == 'suspended') echo 'selected'; ?>>موقوف</option>
                                                        </select>
                                                        <button type="submit" name="update_status" class="text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 transition-all duration-200 shadow-sm hover:shadow-md flex items-center gap-1">
                                                            <i class="fas fa-save text-xs"></i>
                                                            <span>تحديث</span>
                                                        </button>
                                                    </form>
                                                    <form method="POST" action="manage_doctors.php" onsubmit="return confirm('هل أنت متأكد من رغبتك في حذف هذا الطبيب؟ سيتم حذف حسابه بالكامل.');" class="inline">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                        <input type="hidden" name="user_id" value="<?php echo $doctor['user_id']; ?>">
                                                        <input type="hidden" name="delete_doctor" value="1">
                                                        <button type="submit" class="text-red-600 hover:text-red-800 hover:bg-red-50 p-2 rounded-lg transition-all duration-200" title="حذف الطبيب">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                <?php
                                    endforeach;
                                else:
                                ?>
                                    <tr class="bg-white border-b">
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                            لا يوجد أطباء لعرضهم.
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
            sidebarToggle.addEventListener('click', function (e) {
                e.stopPropagation();
                sidebar.classList.toggle('hidden');
            });

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
