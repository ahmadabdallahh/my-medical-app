<?php
require_once '../includes/functions.php';
require_once '../config/database.php';

// Ensure only admins can access this page
if (!check_user_role('admin')) {
    redirect('../login.php');
}

$db = new Database();
$conn = $db->getConnection();

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle form submission for deleting user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    if (delete_user($conn, $user_id)) {
        // Redirect with success message (optional)
        redirect('manage_users.php');
    } else {
        $error_message = "حدث خطأ أثناء حذف المستخدم.";
    }
}

// Fetch user data for the confirmation message
$user = get_user_by_id($conn, $user_id);

if (!$user) {
    // User not found, redirect
    redirect('manage_users.php');
}

$page_title = "حذف المستخدم";
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
    <style> body { font-family: 'Cairo', sans-serif; } </style>
</head>
<body class="bg-gray-100">

<div class="flex h-screen">
    <?php include '../includes/dashboard_sidebar.php'; ?>

    <div class="flex-1 flex flex-col overflow-hidden mr-64">
        <?php include '../includes/dashboard_header.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
            <div class="container mx-auto">
                <div class="bg-white rounded-xl shadow-lg p-6 max-w-2xl mx-auto">
                    <h3 class="text-xl font-bold text-gray-800 mb-4"><?php echo $page_title; ?></h3>

                    <?php if (isset($error_message)): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline"><?php echo $error_message; ?></span>
                        </div>
                    <?php endif; ?>

                    <p class="text-gray-700 mb-6">هل أنت متأكد من أنك تريد حذف المستخدم <strong class="text-red-600"><?php echo htmlspecialchars($user['full_name']); ?></strong>؟ لا يمكن التراجع عن هذا الإجراء.</p>

                    <form action="delete_user.php?id=<?php echo $user_id; ?>" method="POST">
                        <div class="flex items-center justify-start">
                            <button type="submit" name="confirm_delete" class="bg-red-600 hover:bg-red-800 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                نعم، قم بالحذف
                            </button>
                            <a href="manage_users.php" class="inline-block align-baseline font-bold text-sm text-gray-600 hover:text-gray-800 ml-4">
                                إلغاء
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

</body>
</html>
