<?php
session_start();
require_once '../includes/functions.php';
require_once '../config.php';

// Check if the user is a doctor, otherwise redirect to login
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

$pageTitle = "Doctor Dashboard";

// Initialize PDO connection
$pdo = $conn;

// Get Doctor's name
$doctor_id = $_SESSION['user_id'];
$doctor_name = get_user_name($pdo, $doctor_id);

// Fetch dashboard data
$stats = get_doctor_dashboard_stats($pdo, $doctor_id);
$upcoming_appointments = get_doctor_upcoming_appointments($pdo, $doctor_id, 5);

?>

<?php include '../includes/dashboard_header.php'; ?>

<div class="flex h-screen bg-gray-100 dark:bg-gray-900">
    <!-- Sidebar -->
    <?php include '../includes/doctor_sidebar.php'; ?>

    <!-- Main content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Navbar -->
        <?php include '../includes/dashboard_navbar.php'; ?>

        <!-- Page content -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-200 dark:bg-gray-800">
            <div class="container mx-auto px-6 py-8">
                <h3 class="text-3xl font-medium text-gray-700 dark:text-gray-200">Welcome, Dr. <?php echo htmlspecialchars($doctor_name); ?>!</h3>

                <!-- Statistics Cards -->
                <div class="mt-4">
                    <div class="grid gap-6 mb-8 md:grid-cols-2 xl:grid-cols-4">
                        <!-- Card 1: Today's Appointments -->
                        <div class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800 border-l-4 border-blue-500">
                            <div class="p-3 mr-4 text-blue-500 bg-blue-100 rounded-full dark:text-blue-100 dark:bg-blue-500">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 2a8 8 0 100 16 8 8 0 000-16zm0 14a6 6 0 110-12 6 6 0 010 12zM9 9a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1zm1-4a1 1 0 100 2 1 1 0 000-2z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">
                                    Today's Appointments
                                </p>
                                <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                                    <?php echo htmlspecialchars($stats['today_appointments']); ?>
                                </p>
                            </div>
                        </div>

                        <!-- Card 2: Upcoming Appointments -->
                        <div class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800 border-l-4 border-green-500">
                            <div class="p-3 mr-4 text-green-500 bg-green-100 rounded-full dark:text-green-100 dark:bg-green-500">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm10 5H4v8h12V7z" clip-rule="evenodd"></path>
                            </div>
                            <div>
                                <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">
                                    Upcoming Appointments
                                </p>
                                <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                                    <?php echo htmlspecialchars($stats['upcoming_appointments']); ?>
                                </p>
                            </div>
                        </div>

                        <!-- Card 3: Total Patients -->
                        <div class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800 border-l-4 border-orange-500">
                            <div class="p-3 mr-4 text-orange-500 bg-orange-100 rounded-full dark:text-orange-100 dark:bg-orange-500">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">
                                    Total Unique Patients
                                </p>
                                <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                                    <?php echo htmlspecialchars($stats['total_patients']); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Appointments & Quick Actions -->
                <div class="grid gap-6 md:grid-cols-2">
                    <!-- Recent Appointments Table -->
                    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-xs">
                        <h4 class="font-semibold text-gray-800 dark:text-gray-200">Upcoming Appointments</h4>
                        <div class="mt-4 overflow-x-auto">
                            <table class="w-full whitespace-no-wrap">
                                <thead>
                                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800">
                                        <th class="px-4 py-3">Patient</th>
                                        <th class="px-4 py-3">Date</th>
                                        <th class="px-4 py-3">Time</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y dark:divide-gray-700 dark:bg-gray-800">
                                    <?php if (empty($upcoming_appointments)): ?>
                                        <tr>
                                            <td colspan="3" class="px-4 py-3 text-center text-gray-500 dark:text-gray-400">
                                                No upcoming appointments.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($upcoming_appointments as $appointment): ?>
                                            <tr class="text-gray-700 dark:text-gray-400">
                                                <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                                <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars(date('M d, Y', strtotime($appointment['appointment_date']))); ?></td>
                                                <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars(date('h:i A', strtotime($appointment['appointment_time']))); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-xs">
                        <h4 class="font-semibold text-gray-800 dark:text-gray-200">Quick Actions</h4>
                        <div class="mt-4 space-y-4">
                            <a href="appointments.php" class="block w-full text-center px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-blue-600 border border-transparent rounded-lg active:bg-blue-600 hover:bg-blue-700 focus:outline-none focus:shadow-outline-blue">
                                View All Appointments
                            </a>
                            <a href="schedule.php" class="block w-full text-center px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-gray-600 border border-transparent rounded-lg active:bg-gray-600 hover:bg-gray-700 focus:outline-none focus:shadow-outline-gray">
                                Manage Schedule & Availability
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>
</div>

<?php include '../includes/dashboard_footer.php'; ?>
