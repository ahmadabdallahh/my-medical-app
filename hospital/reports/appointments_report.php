<?php
$page_title = 'تقرير المواعيد';
require_once 'base_report.php';

list($start_date, $end_date) = getDateRange(30);

try {
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
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error in appointments_report.php: " . $e->getMessage());
    $load_error = 'حدث خطأ في تحميل التقرير. يرجى المحاولة لاحقاً.';
    $appointments = [];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Health Tech</title>
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
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800"><?php echo $page_title; ?></h1>
            <p class="text-gray-600">
                من <?php echo date('Y-m-d', strtotime($start_date)); ?> إلى <?php echo date('Y-m-d', strtotime($end_date)); ?>
            </p>
        </div>
    </div>
    
    <?php renderDateFilter($start_date, $end_date); ?>
    
    <!-- Appointments Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <?php if (empty($appointments)): ?>
                <div class="p-8 text-center">
                    <i class="fas fa-inbox text-4xl text-gray-300 mb-2"></i>
                    <p class="text-gray-500">لا توجد مواعيد متاحة للعرض في الفترة المحددة</p>
                </div>
            <?php else: ?>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المريض</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الطبيب</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">العيادة</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">التاريخ والوقت</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الحالة</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($appointments as $index => $appointment): 
                            $status_class = '';
                            $status_text = '';
                            
                            switch($appointment['status']) {
                                case 'completed':
                                    $status_class = 'bg-green-100 text-green-800';
                                    $status_text = 'مكتمل';
                                    break;
                                case 'cancelled':
                                    $status_class = 'bg-red-100 text-red-800';
                                    $status_text = 'ملغي';
                                    break;
                                case 'pending':
                                default:
                                    $status_class = 'bg-yellow-100 text-yellow-800';
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
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <?php renderExportButtons('appointments', $start_date, $end_date); ?>
</div>

<!-- Navigation Button -->
<div class="container mx-auto px-4 py-4">
    <a href="../index.php" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
        <i class="fas fa-arrow-right ml-2"></i>
        العودة للوحة التحكم
    </a>
</div>

</body>
</html>