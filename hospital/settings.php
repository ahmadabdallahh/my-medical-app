<?php
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// Get hospital information
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT h.* 
    FROM hospitals h 
    JOIN users u ON h.email = u.email 
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$hospital = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hospital) {
    // Fallback to first hospital for testing
    $stmt = $conn->query("SELECT * FROM hospitals LIMIT 1");
    $hospital = $stmt->fetch(PDO::FETCH_ASSOC);
}

$hospital_id = $hospital['id'];

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_info'])) {
        // Update hospital information
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $description = trim($_POST['description']);
        
        if (empty($name) || empty($phone) || empty($address)) {
            $error = 'يرجى ملء جميع الحقول المطلوبة';
        } else {
            try {
                // Handle Image Upload
                $image_path = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                    $filename = $_FILES['image']['name'];
                    $filetype = pathinfo($filename, PATHINFO_EXTENSION);
                    if (in_array(strtolower($filetype), $allowed)) {
                        $new_filename = uniqid() . '.' . $filetype;
                        $upload_dir = '../assets/images/hospitals/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_filename)) {
                            $image_path = 'assets/images/hospitals/' . $new_filename;
                        }
                    }
                }

                // Update hospitals table
                $sql = "UPDATE hospitals SET name = ?, phone = ?, address = ?, description = ?";
                $params = [$name, $phone, $address, $description];
                
                if ($image_path) {
                    $sql .= ", image = ?";
                    $params[] = $image_path;
                }
                
                $sql .= " WHERE id = ?";
                $params[] = $hospital_id;
                
                $stmt = $conn->prepare($sql);
                $stmt->execute($params);

                // Update users table (full_name) to sync with hospital name
                $stmt = $conn->prepare("UPDATE users SET full_name = ? WHERE id = ?");
                $stmt->execute([$name, $user_id]);

                // Update session variable immediately
                $_SESSION['user_name'] = $name;

                $success = 'تم تحديث معلومات المستشفى بنجاح';
                
                // Refresh hospital data
                $stmt = $conn->prepare("SELECT * FROM hospitals WHERE id = ?");
                $stmt->execute([$hospital_id]);
                $hospital = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $error = 'حدث خطأ أثناء تحديث البيانات';
                error_log("Update hospital error: " . $e->getMessage());
            }
        }
    } elseif (isset($_POST['update_password'])) {
        // Update password
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'يرجى ملء جميع حقول كلمة المرور';
        } elseif ($new_password !== $confirm_password) {
            $error = 'كلمة المرور الجديدة غير متطابقة';
        } elseif (strlen($new_password) < 8) {
            $error = 'يجب أن تكون كلمة المرور 8 أحرف على الأقل';
        } else {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($current_password, $user['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);
                $success = 'تم تحديث كلمة المرور بنجاح';
            } else {
                $error = 'كلمة المرور الحالية غير صحيحة';
            }
        }
    }
}
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">الإعدادات</h1>
    <p class="text-gray-600 mt-2">إدارة معلومات المستشفى والإعدادات</p>
</div>

<!-- Messages -->
<?php if ($success): ?>
    <div class="bg-green-100 border-r-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg">
        <div class="flex items-center">
            <i class="fas fa-check-circle ml-2"></i>
            <span><?php echo htmlspecialchars($success); ?></span>
        </div>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="bg-red-100 border-r-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle ml-2"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Settings Navigation -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4">أقسام الإعدادات</h2>
            <nav class="space-y-2">
                <a href="#info" class="flex items-center gap-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors settings-nav-link active">
                    <i class="fas fa-hospital w-6"></i>
                    <span>معلومات المستشفى</span>
                </a>
                <a href="#security" class="flex items-center gap-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors settings-nav-link">
                    <i class="fas fa-lock w-6"></i>
                    <span>الأمان</span>
                </a>
            </nav>
        </div>
    </div>

    <!-- Settings Content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Hospital Information -->
        <div id="info-section" class="bg-white rounded-xl shadow-sm p-6 settings-section">
            <div class="flex items-center gap-3 mb-6 pb-4 border-b-2 border-gray-200">
                <i class="fas fa-hospital text-blue-600 text-2xl"></i>
                <h3 class="text-2xl font-bold text-gray-900">معلومات المستشفى</h3>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <div class="space-y-6">
                    <!-- Image Upload -->
                    <div class="flex items-center gap-6">
                        <div class="relative">
                            <?php if (!empty($hospital['image'])): ?>
                                <img src="../<?php echo htmlspecialchars($hospital['image']); ?>" alt="Hospital Logo" class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-lg">
                            <?php else: ?>
                                <div class="w-24 h-24 rounded-full bg-blue-100 flex items-center justify-center border-4 border-white shadow-lg">
                                    <i class="fas fa-hospital text-3xl text-blue-500"></i>
                                </div>
                            <?php endif; ?>
                            <label for="image" class="absolute bottom-0 right-0 bg-blue-600 text-white p-2 rounded-full shadow-md cursor-pointer hover:bg-blue-700 transition-colors">
                                <i class="fas fa-camera text-sm"></i>
                                <input type="file" id="image" name="image" accept="image/*" class="hidden">
                            </label>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-800">شعار المستشفى</h4>
                            <p class="text-sm text-gray-500">JPG, GIF or PNG. Max size of 2MB</p>
                        </div>
                    </div>

                    <div>
                        <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">اسم المستشفى *</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($hospital['name']); ?>"
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:outline-none focus:border-blue-500 transition-colors" required>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">البريد الإلكتروني</label>
                        <input type="email" id="email" value="<?php echo htmlspecialchars($hospital['email']); ?>"
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl bg-gray-100 cursor-not-allowed" disabled>
                        <p class="text-xs text-gray-500 mt-1">لا يمكن تغيير البريد الإلكتروني</p>
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">رقم الهاتف *</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($hospital['phone']); ?>"
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:outline-none focus:border-blue-500 transition-colors" required>
                    </div>

                    <div>
                        <label for="address" class="block text-sm font-semibold text-gray-700 mb-2">العنوان *</label>
                        <textarea id="address" name="address" rows="3"
                                  class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:outline-none focus:border-blue-500 transition-colors" required><?php echo htmlspecialchars($hospital['address']); ?></textarea>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">الوصف</label>
                        <textarea id="description" name="description" rows="4"
                                  class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:outline-none focus:border-blue-500 transition-colors"><?php echo htmlspecialchars($hospital['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="pt-4">
                        <button type="submit" name="update_info"
                                class="bg-gradient-to-r from-blue-600 to-blue-700 text-white font-bold py-3 px-8 rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                            <i class="fas fa-save ml-2"></i>
                            حفظ التغييرات
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Security Settings -->
        <div id="security-section" class="bg-white rounded-xl shadow-sm p-6 settings-section hidden">
            <div class="flex items-center gap-3 mb-6 pb-4 border-b-2 border-gray-200">
                <i class="fas fa-lock text-blue-600 text-2xl"></i>
                <h3 class="text-2xl font-bold text-gray-900">الأمان وكلمة المرور</h3>
            </div>

            <form method="POST">
                <div class="space-y-6">
                    <div>
                        <label for="current_password" class="block text-sm font-semibold text-gray-700 mb-2">كلمة المرور الحالية *</label>
                        <input type="password" id="current_password" name="current_password" placeholder="أدخل كلمة المرور الحالية"
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:outline-none focus:border-blue-500 transition-colors">
                    </div>

                    <div>
                        <label for="new_password" class="block text-sm font-semibold text-gray-700 mb-2">كلمة المرور الجديدة *</label>
                        <input type="password" id="new_password" name="new_password" placeholder="أدخل كلمة المرور الجديدة"
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:outline-none focus:border-blue-500 transition-colors">
                        <p class="text-xs text-gray-500 mt-1">يجب أن تكون 8 أحرف على الأقل</p>
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-semibold text-gray-700 mb-2">تأكيد كلمة المرور *</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="أعد إدخال كلمة المرور الجديدة"
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:outline-none focus:border-blue-500 transition-colors">
                    </div>

                    <div class="pt-4">
                        <button type="submit" name="update_password"
                                class="bg-gradient-to-r from-blue-600 to-blue-700 text-white font-bold py-3 px-8 rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                            <i class="fas fa-key ml-2"></i>
                            تحديث كلمة المرور
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.settings-nav-link');
    const sections = document.querySelectorAll('.settings-section');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all links
            navLinks.forEach(l => l.classList.remove('active', 'bg-blue-50', 'text-blue-600', 'font-bold'));
            
            // Add active class to clicked link
            this.classList.add('active', 'bg-blue-50', 'text-blue-600', 'font-bold');
            
            // Hide all sections
            sections.forEach(s => s.classList.add('hidden'));
            
            // Show corresponding section
            const target = this.getAttribute('href').substring(1);
            document.getElementById(target + '-section').classList.remove('hidden');
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
