<?php
session_start();
require_once '../config.php';
require_once '../includes/functions.php';

// Ensure user is logged in as an admin
if (!is_logged_in() || $_SESSION['user_type'] !== 'admin') {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

require_once '../includes/functions.php';
require_once '../config/database.php';

// Ensure only admins can access this page
if (!check_user_role('admin')) {
    redirect('../login.php');
}

// Fetch all users for display
$db = new Database();
$conn = $db->getConnection();
$users = get_all_users($conn);

$page_title = "إدارة المستخدمين";
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
    <!-- Sidebar -->
    <?php require_once '../includes/dashboard_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden lg:mr-64">
        <?php require_once '../includes/dashboard_header.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-4 md:p-6">
            <div class="container mx-auto">
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4"><?php echo $page_title; ?></h3>

                    <!-- Users Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3">الاسم الكامل</th>
                                    <th scope="col" class="px-6 py-3">البريد الإلكتروني</th>
                                    <th scope="col" class="px-6 py-3">نوع المستخدم</th>
                                    <th scope="col" class="px-6 py-3">تاريخ التسجيل</th>
                                    <th scope="col" class="px-6 py-3">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($users)): ?>
                                    <?php foreach ($users as $user):
                                        $role = $user['role'] ?? 'غير محدد';
                                        $role_arabic = [
                                            'admin' => ['text' => 'مسؤول', 'class' => 'text-purple-800 bg-purple-100 border border-purple-200', 'icon' => 'fa-user-shield'],
                                            'doctor' => ['text' => 'طبيب', 'class' => 'text-blue-800 bg-blue-100 border border-blue-200', 'icon' => 'fa-user-md'],
                                            'patient' => ['text' => 'مريض', 'class' => 'text-green-800 bg-green-100 border border-green-200', 'icon' => 'fa-user'],
                                            'hospital' => ['text' => 'مستشفى', 'class' => 'text-orange-800 bg-orange-100 border border-orange-200', 'icon' => 'fa-hospital']
                                        ];
                                        $role_info = $role_arabic[$role] ?? ['text' => $role, 'class' => 'text-gray-800 bg-gray-100 border border-gray-200', 'icon' => 'fa-user-circle'];
                                    ?>
                                        <tr class="bg-white border-b hover:bg-gray-50 transition-colors duration-150">
                                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <i class="fas fa-user-circle text-gray-400 ml-2"></i>
                                                    <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center text-gray-600">
                                                    <i class="fas fa-envelope text-gray-400 ml-2"></i>
                                                    <span><?php echo htmlspecialchars($user['email']); ?></span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="px-3 py-1.5 text-xs font-semibold leading-tight rounded-full <?php echo $role_info['class']; ?> inline-flex items-center">
                                                    <i class="fas <?php echo $role_info['icon']; ?> ml-1"></i>
                                                    <?php echo htmlspecialchars($role_info['text']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center text-gray-600">
                                                    <i class="fas fa-calendar text-gray-400 ml-2"></i>
                                                    <span><?php echo date('d-m-Y', strtotime($user['created_at'])); ?></span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-2">
                                                    <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="text-blue-600 hover:text-blue-800 hover:bg-blue-50 px-3 py-1.5 rounded-lg transition-all duration-200 flex items-center gap-1" title="تعديل المستخدم">
                                                        <i class="fas fa-edit text-sm"></i>
                                                        <span>تعديل</span>
                                                    </a>
                                                    <a href="delete_user.php?id=<?php echo $user['id']; ?>" class="text-red-600 hover:text-red-800 hover:bg-red-50 px-3 py-1.5 rounded-lg transition-all duration-200 flex items-center gap-1" title="حذف المستخدم" onclick="return confirm('هل أنت متأكد من رغبتك في حذف هذا المستخدم؟');">
                                                        <i class="fas fa-trash-alt text-sm"></i>
                                                        <span>حذف</span>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr class="bg-white border-b">
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                            لا يوجد مستخدمين لعرضهم.
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

<?php
require_once '../includes/dashboard_footer.php';
?>


<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

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
