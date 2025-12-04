<?php
require_once 'config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'user' => null
];

// Check if user is logged in
if (!is_logged_in()) {
    $response['message'] = 'يجب تسجيل الدخول أولاً';
    echo json_encode($response);
    exit();
}

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();
$user = get_logged_in_user();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    // Get form data
    $full_name = sanitize_input($_POST['full_name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $username = sanitize_input($_POST['username'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate required fields
    if (empty($full_name) || empty($email) || empty($username)) {
        $response['message'] = 'يرجى ملء جميع الحقول المطلوبة';
        echo json_encode($response);
        exit();
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'يرجى إدخال بريد إلكتروني صحيح';
        echo json_encode($response);
        exit();
    }
    
    // Validate username length
    if (strlen($username) < 3) {
        $response['message'] = 'اسم المستخدم يجب أن يكون 3 أحرف على الأقل';
        echo json_encode($response);
        exit();
    }
    
    // Check for duplicate username or email
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
    $check_stmt->execute([$username, $email, $user['id']]);
    if ($check_stmt->rowCount() > 0) {
        $response['message'] = 'اسم المستخدم أو البريد الإلكتروني مستخدم بالفعل';
        echo json_encode($response);
        exit();
    }
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Update basic info
        $sql = "UPDATE users SET full_name = ?, email = ?, username = ?, phone = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([$full_name, $email, $username, $phone, $user['id']]);
        
        if (!$result) {
            throw new Exception('فشل في تحديث البيانات الأساسية');
        }
        
        // Update password if provided
        if (!empty($current_password) && !empty($new_password)) {
            if (verify_password($current_password, $user['password'])) {
                if ($new_password === $confirm_password) {
                    if (strlen($new_password) >= 8) {
                        $hashed_password = hash_password($new_password);
                        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                        if (!$stmt->execute([$hashed_password, $user['id']])) {
                            throw new Exception('فشل في تحديث كلمة المرور');
                        }
                    } else {
                        throw new Exception('يجب أن تكون كلمة المرور الجديدة 8 أحرف على الأقل');
                    }
                } else {
                    throw new Exception('كلمتا المرور الجديدتان غير متطابقتين');
                }
            } else {
                throw new Exception('كلمة المرور الحالية غير صحيحة');
            }
        }
        
        // Handle profile image upload
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $target_dir = "uploads/profile_images/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $image_file_type = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
            $unique_name = 'user_' . $user['id'] . '_' . time() . '.' . $image_file_type;
            $target_file = $target_dir . $unique_name;

            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                // Delete old image if it exists and it's not the default avatar
                if (!empty($user['profile_image']) && file_exists($user['profile_image']) && 
                    strpos($user['profile_image'], 'default-avatar.png') === false) {
                    @unlink($user['profile_image']);
                }
                
                // Update profile image in database
                $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                if (!$stmt->execute([$target_file, $user['id']])) {
                    throw new Exception('فشل في تحديث صورة الملف الشخصي');
                }
                $user['profile_image'] = $target_file;
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        // Update session data
        $_SESSION['full_name'] = $full_name;
        $_SESSION['email'] = $email;
        $_SESSION['username'] = $username;
        
        // Get updated user data
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $updated_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Prepare response
        $response['success'] = true;
        $response['message'] = 'تم تحديث البيانات بنجاح';
        $response['user'] = [
            'full_name' => $updated_user['full_name'],
            'email' => $updated_user['email'],
            'username' => $updated_user['username'],
            'phone' => $updated_user['phone'],
            'profile_image' => !empty($updated_user['profile_image']) ? $updated_user['profile_image'] : ''
        ];
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $response['message'] = $e->getMessage();
    }
    
    echo json_encode($response);
    exit();
}

// If no action matched
$response['message'] = 'طلب غير صالح';
echo json_encode($response);
