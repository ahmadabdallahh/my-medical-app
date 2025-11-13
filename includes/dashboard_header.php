<?php
// Ensure functions are loaded
if (!function_exists('is_logged_in')) {
    require_once __DIR__ . '/functions.php';
}

// Get user info for display
$user_name = $_SESSION['user_name'] ?? $_SESSION['full_name'] ?? 'المستخدم';
$user_email = $_SESSION['email'] ?? '';

// Get user profile image from database
$profile_image = null;
if (isset($_SESSION['user_id'])) {
    try {
        if (!class_exists('Database')) {
            require_once __DIR__ . '/../config/database.php';
        }
        $db = new Database();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user_data && !empty($user_data['profile_image'])) {
            $profile_image_path = __DIR__ . '/../' . $user_data['profile_image'];
            // Check if file exists
            if (file_exists($profile_image_path)) {
                $profile_image = $user_data['profile_image'];
            }
        }
    } catch (Exception $e) {
        error_log("Error getting profile image: " . $e->getMessage());
    }
}

// Determine base URL for images
$script_path = $_SERVER['SCRIPT_NAME'] ?? '';
// Determine if we're in a subdirectory and need relative path adjustment
$is_subdir = preg_match('#/(doctor|admin|hospital|patient)/#', $script_path);
$base_path = $is_subdir ? '../' : '';

// If no profile image, use default or generate from name
if (empty($profile_image)) {
    // Use ui-avatars as fallback
    $profile_image_url = "https://ui-avatars.com/api/?name=" . urlencode($user_name) . "&background=4F46E5&color=fff&size=128";
} else {
    // Use actual profile image
    $profile_image_url = $base_path . $profile_image;
}
?>
<header class="bg-white shadow-md sticky top-0 z-40">
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Left side: Hamburger Menu for mobile -->
            <div class="flex items-center">
                <button id="sidebar-toggle" class="text-gray-600 hover:text-gray-800 focus:outline-none lg:hidden p-2 rounded-md hover:bg-gray-100 transition-colors">
                    <i class="fas fa-bars fa-lg"></i>
                </button>

                <!-- Logo/Title for mobile -->
                <a href="<?php echo $base_path; ?>index.php" class="lg:hidden ml-3 text-xl font-bold text-blue-600 hover:text-blue-700 transition-colors">
                    Health Tech
                </a>
            </div>

            <!-- Center: Search bar (hidden on mobile) -->
            <div class="hidden md:flex flex-1 max-w-md mx-4">
                <div class="relative w-full">
                    <input
                        type="text"
                        class="w-full bg-gray-100 border-2 border-gray-200 rounded-full py-2 px-4 pr-10 focus:outline-none focus:bg-white focus:border-blue-500 transition-colors"
                        placeholder="بحث..."
                    >
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                </div>
            </div>

            <!-- Right side: Profile Dropdown & Actions -->
            <div class="flex items-center space-x-4 space-x-reverse">
                <!-- Notifications (optional) -->
                <button class="hidden md:block text-gray-600 hover:text-gray-800 p-2 rounded-full hover:bg-gray-100 transition-colors relative">
                    <i class="fas fa-bell fa-lg"></i>
                    <span class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full"></span>
                </button>

                <!-- Profile Dropdown -->
                <div class="relative" x-data="{ open: false }">
                    <button
                        @click="open = !open"
                        class="flex items-center space-x-3 space-x-reverse focus:outline-none p-2 rounded-lg hover:bg-gray-100 transition-colors"
                    >
                        <img
                            src="<?php echo htmlspecialchars($profile_image_url); ?>"
                            alt="User Avatar"
                            class="w-10 h-10 rounded-full object-cover border-2 border-gray-200"
                            onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=4F46E5&color=fff&size=128'"
                        >
                        <div class="hidden md:block text-right">
                            <span class="block font-semibold text-gray-700 text-sm"><?php echo htmlspecialchars($user_name); ?></span>
                            <span class="block text-xs text-gray-500"><?php echo htmlspecialchars($user_email); ?></span>
                        </div>
                        <i class="fas fa-chevron-down text-gray-400 text-xs hidden md:block"></i>
                    </button>

                    <!-- Dropdown menu -->
                    <div
                        x-show="open"
                        @click.away="open = false"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-200"
                        style="display: none;"
                    >
                        <?php
                        // Use the consistent base_path logic
                        $profile_link = $base_path . 'profile.php';
                        $home_link = $base_path . 'index.php';
                        $logout_link = $base_path . 'logout.php';
                        ?>
                        <a href="<?php echo $profile_link; ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                            <i class="fas fa-user ml-2"></i>الملف الشخصي
                        </a>
                        <a href="<?php echo $home_link; ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                            <i class="fas fa-home ml-2"></i>الصفحة الرئيسية
                        </a>
                        <hr class="my-1">
                        <a href="<?php echo $logout_link; ?>" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                            <i class="fas fa-sign-out-alt ml-2"></i>تسجيل الخروج
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Flash Message Display -->
<?php if (function_exists('display_flash_message')): ?>
    <div class="px-4 sm:px-6 lg:px-8 pt-4">
        <?php display_flash_message(); ?>
    </div>
<?php endif; ?>
