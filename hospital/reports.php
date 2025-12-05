<?php
require_once 'includes/auth_check.php';

// Get database connection
$root_dir = dirname(__DIR__);
require_once $root_dir . '/config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Get hospital ID from session (set by auth_check.php)
if (!isset($hospital_id)) {
    $_SESSION['error'] = 'لم يتم العثور على بيانات المستشفى';
    header('Location: index.php');
    exit();
}

// Get report type from URL parameter
try {
    $report_type = isset($_GET['type']) ? $_GET['type'] : 'appointments';
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
    
    $reports = [];
    $report_title = '';
    $total = 0;
    
    switch($report_type) {
        case 'patients':
            $report_title = 'تقرير المرضى';
            $stmt = $conn->prepare("
                SELECT 
                    p.id,
                    p.first_name,
                    p.last_name,
                    p.phone,
                    p.email,
                    COUNT(a.id) as appointment_count,
                    MAX(a.appointment_date) as last_visit
                FROM patients p
                LEFT JOIN appointments a ON p.id = a.patient_id
                WHERE a.hospital_id = ? 
                AND (a.appointment_date BETWEEN ? AND ? OR a.appointment_date IS NULL)
                GROUP BY p.id
                ORDER BY appointment_count DESC
            
            
            
            
            ");
            $stmt->execute([$hospital_id, $start_date, $end_date]);
            $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'revenue':
            $report_title = 'التقارير المالية';
            $stmt = $conn->prepare("
                SELECT 
                    DATE(a.appointment_date) as date,
                    COUNT(a.id) as total_appointments,
                    COALESCE(SUM(p.amount), 0) as total_revenue,
                    c.name as clinic_name
                FROM appointments a
                LEFT JOIN payments p ON a.id = p.appointment_id
                JOIN clinics c ON a.clinic_id = c.id
                WHERE c.hospital_id = ? 
                AND a.status = 'completed'
                AND (a.appointment_date BETWEEN ? AND ?)
                GROUP BY DATE(a.appointment_date), c.id
                ORDER BY date DESC
            ");
            $stmt->execute([$hospital_id, $start_date, $end_date]);
            $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate total revenue
            $total = array_sum(array_column($reports, 'total_revenue'));
            break;
            
        case 'appointments':
        default:
            $report_title = 'تقرير المواعيد';
            $stmt = $conn->prepare("
                SELECT 
                    a.*,
                    p.first_name as patient_first_name,
                    p.last_name as patient_last_name,
                    p.phone as patient_phone,
                    d.first_name as doctor_first_name,
                    d.last_name as doctor_last_name,
                    c.name as clinic_name
                FROM appointments a
                JOIN patients p ON a.patient_id = p.id
                JOIN doctors d ON a.doctor_id = d.id
                JOIN clinics c ON a.clinic_id = c.id
                WHERE c.hospital_id = ? 
                AND (a.appointment_date BETWEEN ? AND ?)
                ORDER BY a.appointment_date DESC, a.appointment_time DESC
            ");
            $stmt->execute([$hospital_id, $start_date, $end_date]);
            $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
    }
    
} catch (PDOException $e) {
    // Log error and show user-friendly message
    error_log("Error in reports.php: " . $e->getMessage());
    $_SESSION['error'] = 'حدث خطأ في تحميل التقارير';
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التقارير - Health Tech</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body class="bg-gray-100">

<div class="container mx-auto px-4 py-6">
    <!-- Header with title and date range -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800"><?php echo $report_title; ?></h1>
            <p class="text-gray-600">
                من <?php echo date('Y-m-d', strtotime($start_date)); ?> إلى <?php echo date('Y-m-d', strtotime($end_date)); ?>
            </p>
        </div>
        
        <!-- Report type selector -->
        <div class="mt-4 md:mt-0 no-print">
            <div class="flex flex-wrap gap-2">
                <a href="?type=appointments&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
                   class="px-4 py-2 rounded-lg text-sm font-medium <?php echo $report_type == 'appointments' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                    تقرير المواعيد
                </a>
                <a href="?type=patients&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
                   class="px-4 py-2 rounded-lg text-sm font-medium <?php echo $report_type == 'patients' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                    تقرير المرضى
                </a>
                <a href="?type=revenue&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
                   class="px-4 py-2 rounded-lg text-sm font-medium <?php echo $report_type == 'revenue' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                    التقارير المالية
                </a>
            </div>
        </div>
    </div>
    
    <!-- Date range filter -->
    <div class="bg-white rounded-lg shadow p-4 mb-6 no-print">

        <form method="get" action="" class="flex flex-col md:flex-row gap-4 items-end">
            <input type="hidden" name="type" value="<?php echo $report_type; ?>">
            
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
    
    <!-- Reports Summary Cards -->
    <?php if ($report_type == 'revenue'): ?>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">إجمالي الإيرادات</p>
                    <p class="text-2xl font-bold text-gray-800"><?php echo number_format($total, 2); ?> ج.م</p>
                </div>
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-money-bill-wave text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">عدد الجلسات المكتملة</p>
                    <p class="text-2xl font-bold text-gray-800">
                        <?php 
                        $total_sessions = array_sum(array_column($reports, 'total_appointments'));
                        echo number_format($total_sessions);
                        ?>
                    </p>
                </div>
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-calendar-check text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">متوسط الإيراد للجلسة</p>
                    <p class="text-2xl font-bold text-gray-800">
                        <?php 
                        $avg = $total_sessions > 0 ? $total / $total_sessions : 0;
                        echo number_format($avg, 2); 
                        ?> ج.م
                    </p>
                </div>
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <i class="fas fa-chart-line text-xl"></i>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Reports Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <?php if (empty($reports)): ?>
                <div class="p-8 text-center">
                    <i class="fas fa-inbox text-4xl text-gray-300 mb-2"></i>
                    <p class="text-gray-500">لا توجد بيانات متاحة للعرض في الفترة المحددة</p>
                </div>
            <?php else: ?>
                <?php if ($report_type == 'patients'): ?>
                    <!-- Patients Report -->
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">اسم المريض</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">رقم الهاتف</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">البريد الإلكتروني</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عدد الزيارات</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">آخر زيارة</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($reports as $index => $report): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $index + 1; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($report['phone']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($report['email']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        <?php echo $report['appointment_count']; ?> زيارة
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $report['last_visit'] ? date('Y-m-d', strtotime($report['last_visit'])) : 'لا توجد زيارات'; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="patient_details.php?id=<?php echo $report['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-eye"></i> عرض
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                <?php elseif ($report_type == 'revenue'): ?>
                    <!-- Financial Report -->
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">التاريخ</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">العيادة</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عدد الجلسات</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">إجمالي الإيراد</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($reports as $report): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('Y-m-d', strtotime($report['date'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($report['clinic_name']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $report['total_appointments']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo number_format($report['total_revenue'], 2); ?> ج.م
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <!-- Total Row -->
                            <tr class="bg-gray-50">
                                <td colspan="2" class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    الإجمالي
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo array_sum(array_column($reports, 'total_appointments')); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600">
                                    <?php echo number_format($total, 2); ?> ج.م
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                <?php else: ?>
                    <!-- Default Appointments Report -->
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المريض</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الطبيب</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">العيادة</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">التاريخ والوقت</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الحالة</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($reports as $index => $appointment): 
                                $status_class = '';
                                switch($appointment['status']) {
                                    case 'completed':
                                        $status_class = 'bg-green-100 text-green-800';
                                        break;
                                    case 'cancelled':
                                        $status_class = 'bg-red-100 text-red-800';
                                        break;
                                    case 'pending':
                                    default:
                                        $status_class = 'bg-yellow-100 text-yellow-800';
                                        break;
                                }
                                
                                $status_text = '';
                                switch($appointment['status']) {
                                    case 'completed':
                                        $status_text = 'مكتمل';
                                        break;
                                    case 'cancelled':
                                        $status_text = 'ملغي';
                                        break;
                                    case 'pending':
                                    default:
                                        $status_text = 'قيد الانتظار';
                                        break;
                                }
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $index + 1; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?php echo htmlspecialchars($appointment['patient_phone']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo htmlspecialchars($appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo htmlspecialchars($appointment['clinic_name']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo date('Y-m-d', strtotime($appointment['appointment_date'])); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="appointment_details.php?id=<?php echo $appointment['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-eye"></i> عرض
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <?php if (!empty($reports)): ?>
        <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
            <div class="flex-1 flex justify-between sm:hidden">
                <a href="#" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    السابق
                </a>
                <a href="#" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    التالي
                </a>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        عرض
                        <span class="font-medium">1</span>
                        إلى
                        <span class="font-medium"><?php echo count($reports); ?></span>
                        من
                        <span class="font-medium"><?php echo count($reports); ?></span>
                        نتائج
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <span class="sr-only">السابق</span>
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <a href="#" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-blue-600 hover:bg-gray-50">
                            1
                        </a>
                        <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <span class="sr-only">التالي</span>
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </nav>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Export Buttons -->
    <div class="mt-6 flex flex-col sm:flex-row justify-end gap-3 no-print">
    <?php if (!empty($reports)): ?>

        <a href="export.php?type=<?php echo $report_type; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&format=pdf" 
           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
            <i class="fas fa-file-pdf ml-2"></i>
            تصدير PDF
        </a>
        <a href="export.php?type=<?php echo $report_type; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&format=excel" 
           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
            <i class="fas fa-file-excel ml-2"></i>
            تصدير Excel
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Navigation Button -->
<div class="container mx-auto px-4 py-4">
    <a href="index.php" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
        <i class="fas fa-arrow-right ml-2"></i>
        العودة للوحة التحكم
    </a>
</div>

</body>
</html>
