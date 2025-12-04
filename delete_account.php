<?php
require_once 'config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();
$user = get_logged_in_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // 1. Delete user's profile image if it's not the default one
        if (!empty($user['profile_image']) && 
            file_exists($user['profile_image']) && 
            strpos($user['profile_image'], 'default-avatar.png') === false) {
            @unlink($user['profile_image']);
        }
        
        // 2. Delete user's appointments
        $stmt = $conn->prepare("DELETE FROM appointments WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        
        // 3. Delete any other related data (add more queries if needed)
        // Example: $stmt = $conn->prepare("DELETE FROM user_other_data WHERE user_id = ?");
        // $stmt->execute([$user['id']]);
        
        // 4. Finally, delete the user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // Commit transaction
        $conn->commit();
        
        // Logout and destroy session
        session_destroy();
        
        // Redirect to home page with success message
        $_SESSION['success'] = 'تم حذف حسابك بنجاح. نأسف لرؤيتك تغادرنا!';
        header("Location: index.php");
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $_SESSION['error'] = 'حدث خطأ أثناء محاولة حذف الحساب. يرجى المحاولة مرة أخرى.';
        header("Location: profile.php");
        exit();
    }
} else {
    // If someone tries to access this page directly without POST data
    header("Location: profile.php");
    exit();
}
