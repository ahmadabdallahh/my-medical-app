<?php
session_start();

// Include necessary files
require_once '../includes/functions.php';
require_once '../config/database.php';

// Access Control: Check if user is logged in and is a hospital representative
if (!is_logged_in() || !is_hospital()) {
    // Redirect to login page if not authorized
    redirect('../login.php');
}

// Get hospital details from session
$hospital_id = $_SESSION['user_id'];
$hospital_name = $_SESSION['user_name'];

// Page-specific variables
$page_title = 'لوحة تحكم المستشفى';

// Include Dashboard Header
require_once '../includes/dashboard_header.php';
?>

<div class="dashboard-container">
    <?php
    // Include Dashboard Sidebar
    require_once '../includes/dashboard_sidebar.php';
    ?>

    <main class="dashboard-content">
        <div class="dashboard-header">
            <h2><?php echo htmlspecialchars($page_title); ?></h2>
            <div class="user-info">
                <span>مرحباً، <?php echo htmlspecialchars($hospital_name); ?></span>
                <a href="../logout.php" class="btn btn-outline">تسجيل الخروج</a>
            </div>
        </div>

        <div class="dashboard-widgets">
            <!-- Widget 1: Total Doctors -->
            <div class="widget">
                <div class="widget-icon"><i class="fas fa-user-md"></i></div>
                <div class="widget-info">
                    <h4>الأطباء التابعون</h4>
                    <p>25</p> <!-- Placeholder data -->
                </div>
            </div>

            <!-- Widget 2: Total Appointments -->
            <div class="widget">
                <div class="widget-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="widget-info">
                    <h4>إجمالي المواعيد</h4>
                    <p>1,200</p> <!-- Placeholder data -->
                </div>
            </div>

            <!-- Widget 3: Patient Reviews -->
            <div class="widget">
                <div class="widget-icon"><i class="fas fa-star"></i></div>
                <div class="widget-info">
                    <h4>متوسط التقييم</h4>
                    <p>4.8</p> <!-- Placeholder data -->
                </div>
            </div>

            <!-- Widget 4: Manage Profile -->
            <div class="widget">
                <div class="widget-icon"><i class="fas fa-hospital"></i></div>
                <div class="widget-info">
                    <h4>إدارة ملف المستشفى</h4>
                    <a href="#" class="btn btn-sm">تعديل البيانات</a>
                </div>
            </div>
        </div>

        <div class="dashboard-main-content">
            <h3>إدارة الأطباء</h3>
            <p>هنا يمكنك عرض وإدارة الأطباء المسجلين تحت مستشفاكم.</p>
            <!-- Doctors list and management tools will go here -->
        </div>

    </main>
</div>

<?php
// Include Dashboard Footer
require_once '../includes/dashboard_footer.php';
?>
