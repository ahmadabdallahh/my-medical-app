<?php
// Decoupled from auth_check: reports should be viewable without forcing login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get database connection
$root_dir = dirname(dirname(__DIR__));
require_once $root_dir . '/config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Resolve hospital_id: prefer session, allow GET override, fallback to 1
$hospital_id = isset($_SESSION['hospital_id']) ? (int)$_SESSION['hospital_id'] : null;
if ($hospital_id === null) {
    $hospital_id = isset($_GET['hospital_id']) ? (int)$_GET['hospital_id'] : 1;
}

// Common functions for all reports
function getDateRange($default_days = 30) {
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime("-$default_days days"));
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
    return [$start_date, $end_date];
}

function renderDateFilter($start_date, $end_date) {
    ?>
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="get" action="" class="flex flex-col md:flex-row gap-4 items-end">
            <div class="w-full md:w-auto">
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">من تاريخ</label>
                <input type="date" id="start_date" name="start_date" 
                       value="<?php echo $start_date; ?>" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div class="w-full md:w-auto">
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">إلى تاريخ</label>
                <input type="date" id="end_date" name="end_date" 
                       value="<?php echo $end_date; ?>" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div class="w-full md:w-auto">
                <button type="submit" class="w-full md:w-auto bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition-colors">
                    <i class="fas fa-filter ml-2"></i>
                    تصفية النتائج
                </button>
            </div>
            
            <div class="w-full md:w-auto">
                <button type="button" onclick="window.print()" class="w-full md:w-auto bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-6 rounded-lg transition-colors">
                    <i class="fas fa-print ml-2"></i>
                    طباعة التقرير
                </button>
            </div>
        </form>
    </div>
    <?php
}

function renderExportButtons($report_type, $start_date, $end_date) {
    ?>
    <div class="mt-6 flex flex-col sm:flex-row justify-end gap-3">
        <a href="export_report.php?type=<?php echo $report_type; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&format=pdf" 
           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
            <i class="fas fa-file-pdf ml-2"></i>
            تصدير PDF
        </a>
        <a href="export_report.php?type=<?php echo $report_type; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&format=excel" 
           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
            <i class="fas fa-file-excel ml-2"></i>
            تصدير Excel
        </a>
    </div>
    <?php
}
?>
