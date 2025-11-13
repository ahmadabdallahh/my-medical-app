<?php
session_start();
require_once '../includes/functions.php';
require_once '../config/database.php';

// Ensure only admins can access this page
if (!check_user_role('admin')) {
    redirect('../login.php');
}

// Get database connection
$db = new Database();
$conn = $db->getConnection();

// Fetch statistics
$total_patients = get_user_type_count($conn, 'patient');
$total_doctors = get_user_type_count($conn, 'doctor');
$total_appointments = get_total_count($conn, 'appointments');
$estimated_revenue = get_estimated_revenue($conn); // Dynamic calculation from database

// Fetch recent patients
$recent_patients = get_recent_patients($conn, 5);

$page_title = "لوحة تحكم المسؤول";
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
        .content-area {
            transition: margin-right 0.3s ease;
        }
        .stat-card {
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
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
<body class="bg-gray-100 font-sans">

<div class="flex h-screen">
    <!-- Sidebar -->
    <?php include '../includes/dashboard_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden lg:mr-64">
        <?php include '../includes/dashboard_header.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-4 md:p-6">
            <div class="container mx-auto">
                <h3 class="text-2xl font-bold text-gray-800 mb-6">لوحة التحكم الرئيسية</h3>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6 mb-6">
                    <!-- Total Patients -->
                    <div class="stat-card bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-xl shadow-lg p-6 flex items-center justify-between cursor-pointer">
                        <div>
                            <h3 class="text-lg font-semibold mb-2">إجمالي المرضى</h3>
                            <p class="text-4xl font-bold"><?php echo number_format($total_patients); ?></p>
                            <p class="text-sm opacity-75 mt-1">مستخدم نشط</p>
                        </div>
                        <div class="bg-white/20 rounded-full p-4">
                            <i class="fas fa-users fa-3x opacity-90"></i>
                        </div>
                    </div>
                    <!-- Total Doctors -->
                    <div class="stat-card bg-gradient-to-br from-green-400 to-green-600 text-white rounded-xl shadow-lg p-6 flex items-center justify-between cursor-pointer">
                        <div>
                            <h3 class="text-lg font-semibold mb-2">إجمالي الأطباء</h3>
                            <p class="text-4xl font-bold"><?php echo number_format($total_doctors); ?></p>
                            <p class="text-sm opacity-75 mt-1">طبيب مسجل</p>
                        </div>
                        <div class="bg-white/20 rounded-full p-4">
                            <i class="fas fa-stethoscope fa-3x opacity-90"></i>
                        </div>
                    </div>
                    <!-- Total Appointments -->
                    <div class="stat-card bg-gradient-to-br from-yellow-400 to-yellow-600 text-white rounded-xl shadow-lg p-6 flex items-center justify-between cursor-pointer">
                        <div>
                            <h3 class="text-lg font-semibold mb-2">إجمالي الحجوزات</h3>
                            <p class="text-4xl font-bold"><?php echo number_format($total_appointments); ?></p>
                            <p class="text-sm opacity-75 mt-1">موعد محجوز</p>
                        </div>
                        <div class="bg-white/20 rounded-full p-4">
                            <i class="fas fa-calendar-check fa-3x opacity-90"></i>
                        </div>
                    </div>
                    <!-- Estimated Revenue -->
                    <div class="stat-card bg-gradient-to-br from-red-400 to-red-600 text-white rounded-xl shadow-lg p-6 flex items-center justify-between cursor-pointer">
                        <div>
                            <h3 class="text-lg font-semibold mb-2">الأرباح المقدرة</h3>
                            <p class="text-4xl font-bold">$<?php echo number_format($estimated_revenue, 2); ?></p>
                            <p class="text-sm opacity-75 mt-1">إجمالي الإيرادات</p>
                        </div>
                        <div class="bg-white/20 rounded-full p-4">
                            <i class="fas fa-dollar-sign fa-3x opacity-90"></i>
                        </div>
                    </div>
                </div>

                <!-- Recent Patients Table -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-xl font-bold text-gray-800">أحدث المرضى المسجلين</h4>
                        <a href="manage_users.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center gap-1">
                            <span>عرض الكل</span>
                            <i class="fas fa-arrow-left"></i>
                        </a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3">اسم المريض</th>
                                    <th scope="col" class="px-6 py-3">تاريخ التسجيل</th>
                                    <th scope="col" class="px-6 py-3">الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recent_patients)): ?>
                                    <?php foreach ($recent_patients as $patient): ?>
                                        <tr class="bg-white border-b hover:bg-gray-50 transition-colors duration-150">
                                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <i class="fas fa-user-circle text-gray-400 ml-2"></i>
                                                    <span><?php echo htmlspecialchars($patient['full_name']); ?></span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center text-gray-600">
                                                    <i class="fas fa-calendar text-gray-400 ml-2"></i>
                                                    <span><?php echo date('d-m-Y', strtotime($patient['created_at'])); ?></span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="px-3 py-1.5 text-xs font-semibold leading-tight rounded-full text-green-800 bg-green-100 border border-green-200 inline-flex items-center">
                                                    <i class="fas fa-check-circle ml-1"></i>
                                                    نشط
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr class="bg-white border-b">
                                        <td colspan="3" class="px-6 py-4 text-center text-gray-500">
                                            لا يوجد مرضى لعرضهم حالياً.
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
                e.stopPropagation(); // Prevents the click from bubbling up to the document
                sidebar.classList.toggle('hidden');
            });

            // Event to hide sidebar when clicking outside
            document.addEventListener('click', function (e) {
                const isClickInsideSidebar = sidebar.contains(e.target);
                const isClickOnToggle = sidebarToggle.contains(e.target);

                // If sidebar is visible and click is outside both sidebar and toggle button
                if (!sidebar.classList.contains('hidden') && !isClickInsideSidebar && !isClickOnToggle) {
                    sidebar.classList.add('hidden');
                }
            });
        }
    });
</script>

</body>
</html>
