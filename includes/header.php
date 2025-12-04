<?php
// It's a good practice to ensure session is started and functions are included.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('BASE_URL')) {
    // This ensures BASE_URL is defined, which is crucial for correct pathing.
    // You might need to adjust the path depending on your project's root file structure.
    require_once __DIR__ . '/../config.php';
}
if (!function_exists('is_logged_in')) {
    require_once __DIR__ . '/functions.php';
}

$script_path = $_SERVER['SCRIPT_NAME'] ?? '';
// Determine if we're in a subdirectory and need relative path adjustment
$is_subdir = preg_match('#/(doctor|admin|hospital|patient)/#', $script_path);
$base_path = $is_subdir ? '../' : '';
?>

<!-- AlpineJS for mobile menu interactivity -->
<script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>

<header class="bg-gradient-to-r from-blue-700 to-indigo-800 text-white shadow-lg sticky top-0 z-50" x-data="{ mobileMenuOpen: false }">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Logo -->
            <div class="flex-shrink-0">
                <a href="<?php echo $base_path; ?>index.php" class="flex items-center text-white hover:text-gray-200 transition">
                    <i class="fas fa-heartbeat text-2xl ml-2"></i>
                    <span class="font-bold text-xl">Health Tech</span>
                </a>
            </div>

            <!-- Desktop Menu -->
            <div class="hidden md:flex items-center space-x-4 space-x-reverse">
                <a href="<?php echo $base_path; ?>index.php" class="text-gray-200 hover:text-white px-3 py-2 rounded-md text-sm font-medium">الرئيسية</a>
                <a href="<?php echo $base_path; ?>search.php" class="text-gray-200 hover:text-white px-3 py-2 rounded-md text-sm font-medium">البحث</a>
                <?php if (is_logged_in()): ?>
                    <?php if ($_SESSION['user_type'] === 'patient'): ?>
                        <a href="<?php echo $base_path; ?>dashboard.php" class="text-gray-200 hover:text-white px-3 py-2 rounded-md text-sm font-medium">لوحة التحكم</a>
                        <a href="<?php echo $base_path; ?>patient/appointments.php" class="text-gray-200 hover:text-white px-3 py-2 rounded-md text-sm font-medium">مواعيدي</a>
                        <a href="<?php echo $base_path; ?>patient/reminders.php" class="text-gray-200 hover:text-white px-3 py-2 rounded-md text-sm font-medium">التذكيرات</a>
                    <?php elseif ($_SESSION['user_type'] === 'doctor'): ?>
                        <a href="<?php echo $base_path; ?>doctor/index.php" class="text-gray-200 hover:text-white px-3 py-2 rounded-md text-sm font-medium">لوحة التحكم</a>
                    <?php elseif ($_SESSION['user_type'] === 'admin'): ?>
                        <a href="<?php echo $base_path; ?>admin/index.php" class="text-gray-200 hover:text-white px-3 py-2 rounded-md text-sm font-medium">لوحة التحكم</a>
                    <?php elseif ($_SESSION['user_type'] === 'hospital'): ?>
                        <a href="<?php echo $base_path; ?>hospital/index.php" class="text-gray-200 hover:text-white px-3 py-2 rounded-md text-sm font-medium">لوحة التحكم</a>
                    <?php endif; ?>
                    
                    <!-- Notification Bell -->
                    <div class="relative" x-data="{ notifOpen: false }">
                        <button @click="notifOpen = !notifOpen" id="notificationBell" class="relative text-gray-200 hover:text-white p-2 rounded-md transition">
                            <i class="fas fa-bell text-xl"></i>
                            <span id="notificationBadge" class="hidden absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">0</span>
                        </button>
                        
                        <!-- Notification Dropdown -->
                        <div x-show="notifOpen" @click.away="notifOpen = false" id="notificationDropdown" class="absolute left-0 mt-2 w-80 bg-white rounded-lg shadow-xl z-50 hidden" style="display: none;">
                            <div class="p-3 border-b border-gray-200 flex justify-between items-center">
                                <h3 class="text-gray-800 font-semibold">الإشعارات</h3>
                                <button id="markAllAsRead" class="text-xs text-blue-600 hover:text-blue-800">تحديد الكل كمقروء</button>
                            </div>
                            <div id="notificationList" class="max-h-96 overflow-y-auto">
                                <!-- Notifications will be loaded here -->
                                <div class="p-4 text-center text-gray-500">
                                    <i class="fas fa-spinner fa-spin text-2xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <a href="<?php echo $base_path; ?>logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium transition">تسجيل الخروج</a>
                <?php else: ?>
                    <a href="<?php echo $base_path; ?>login.php" class="text-gray-200 hover:text-white px-3 py-2 rounded-md text-sm font-medium">تسجيل الدخول</a>
                    <a href="<?php echo $base_path; ?>register.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition">تسجيل جديد</a>
                <?php endif; ?>
            </div>

            <!-- Mobile menu button -->
            <div class="-mr-2 flex md:hidden">
                <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="bg-white inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500" aria-controls="mobile-menu" aria-expanded="false">
                    <span class="sr-only">Open main menu</span>
                    <!-- Icon when menu is closed. -->
                    <svg x-show="!mobileMenuOpen" class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <!-- Icon when menu is open. -->
                    <svg x-show="mobileMenuOpen" class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile menu, show/hide based on menu state. -->
    <div x-show="mobileMenuOpen" @click.away="mobileMenuOpen = false" class="md:hidden" id="mobile-menu">
        <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-gray-800">
            <a href="<?php echo $base_path; ?>index.php" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">الرئيسية</a>
            <a href="<?php echo $base_path; ?>search.php" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">البحث</a>
            <?php if (is_logged_in()): ?>
                <?php if ($_SESSION['user_type'] === 'patient'): ?>
                    <a href="<?php echo $base_path; ?>dashboard.php" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">لوحة التحكم</a>
                    <a href="<?php echo $base_path; ?>patient/appointments.php" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">مواعيدي</a>
                <?php elseif ($_SESSION['user_type'] === 'doctor'): ?>
                    <a href="<?php echo $base_path; ?>doctor/index.php" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">لوحة التحكم</a>
                <?php elseif ($_SESSION['user_type'] === 'admin'): ?>
                    <a href="<?php echo $base_path; ?>admin/index.php" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">لوحة التحكم</a>
                <?php elseif ($_SESSION['user_type'] === 'hospital'): ?>
                    <a href="<?php echo $base_path; ?>hospital/index.php" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">لوحة التحكم</a>
                <?php endif; ?>
                <a href="<?php echo $base_path; ?>logout.php" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">تسجيل الخروج</a>
            <?php else: ?>
                <a href="<?php echo $base_path; ?>login.php" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">تسجيل الدخول</a>
                <a href="<?php echo $base_path; ?>register.php" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">تسجيل جديد</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php
// Load notification system for logged-in users
if (is_logged_in()): ?>
<script src="<?php echo $base_path; ?>assets/js/notifications.js"></script>
<style>
.notification-item.unread {
    background-color: #eff6ff;
}
.notification-item:hover {
    background-color: #f9fafb;
}
</style>
<?php endif; ?>

<?php
// This section is no longer needed as styles are handled by Tailwind on each page
// or through specific CSS files linked in the head.
?>
