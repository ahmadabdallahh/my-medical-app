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

// Handle form submission for updating user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $user_type = trim($_POST['user_type']);

    if (update_user($conn, $user_id, $full_name, $email, $user_type)) {
        // Redirect with success message (optional)
        redirect('manage_users.php');
    } else {
        $error_message = "حدث خطأ أثناء تحديث بيانات المستخدم.";
    }
}

// Fetch user data for the form
$user = get_user_by_id($conn, $user_id);

if (!$user) {
    // User not found, redirect
    redirect('manage_users.php');
}

$page_title = "تعديل المستخدم";
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
                    <h3 class="text-xl font-bold text-gray-800 mb-4"><?php echo $page_title; ?>: <?php echo htmlspecialchars($user['full_name']); ?></h3>

                    <?php if (isset($error_message)): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline"><?php echo $error_message; ?></span>
                        </div>
                    <?php endif; ?>

                    <form action="edit_user.php?id=<?php echo $user_id; ?>" method="POST">
                        <div class="mb-4">
                            <label for="full_name" class="block text-gray-700 text-sm font-bold mb-2">الاسم الكامل</label>
                            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>

                        <div class="mb-4">
                            <label for="email" class="block text-gray-700 text-sm font-bold mb-2">البريد الإلكتروني</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>

                        <div class="mb-6">
                            <label for="user_type" class="block text-gray-700 text-sm font-bold mb-2">نوع المستخدم</label>
                            <select id="user_type" name="user_type" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="patient" <?php echo ($user['user_type'] === 'patient') ? 'selected' : ''; ?>>مريض</option>
                                <option value="doctor" <?php echo ($user['user_type'] === 'doctor') ? 'selected' : ''; ?>>طبيب</option>
                                <option value="admin" <?php echo ($user['user_type'] === 'admin') ? 'selected' : ''; ?>>مسؤول</option>
                                <option value="hospital" <?php echo ($user['user_type'] === 'hospital') ? 'selected' : ''; ?>>مستشفى</option>
                            </select>
                        </div>

                        <div class="flex items-center justify-between">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                تحديث البيانات
                            </button>
                            <a href="manage_users.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
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
