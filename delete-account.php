<?php
session_start();
require_once 'config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Ensure user is logged in
if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Start transaction
    $conn->beginTransaction();

    // Delete user (cascading delete should handle related data like appointments, etc. if set up correctly in DB)
    // If not cascading, we might need to manually delete related records first.
    // Assuming foreign keys are set to ON DELETE CASCADE for simplicity and robustness.
    
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);

    // Commit transaction
    $conn->commit();

    // Destroy session and redirect to home
    session_destroy();
    header("Location: index.php?message=account_deleted");
    exit();

} catch (PDOException $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Delete account error: " . $e->getMessage());
    // Redirect back to profile with error
    header("Location: profile.php?error=delete_failed");
    exit();
}
?>
