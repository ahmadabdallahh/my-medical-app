<?php
$page_title = 'التقارير المالية';
require_once 'base_report.php';

list($start_date, $end_date) = getDateRange(30);

try {
    // Get total revenue
    $stmt = $conn->prepare("
        SELECT 
            SUM(a.fee) as total_revenue,
            COUNT(a.id) as total_appointments,
            AVG(a.fee) as avg_revenue_per_visit
        FROM appointments a
        JOIN clinics c ON a.clinic_id = c.id
        WHERE c.hospital_id = ? 
        AND a.status = 'completed'
        AND (a.appointment_date BETWEEN ? AND ?)
    ");
    $stmt->execute([$hospital_id, $start_date, $end_date]);
    $revenue_summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get revenue by clinic
    $stmt = $conn->prepare("
        SELECT 
            c.name as clinic_name,
            COUNT(a.id) as appointment_count,
            SUM(a.fee) as total_revenue
        FROM appointments a
        JOIN clinics c ON a.clinic_id = c.id
        WHERE c.hospital_id = ? 
        AND a.status = 'completed'
        AND (a.appointment_date BETWEEN ? AND ?)
        GROUP BY c.id
        ORDER BY total_revenue DESC
    ");
    $stmt->execute([$hospital_id, $start_date, $end_date]);
    $revenue_by_clinic = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get revenue by date
    $stmt = $conn->prepare("
        SELECT 
            DATE(a.appointment_date) as date,
            COUNT(a.id) as appointment_count,
            SUM(a.fee) as daily_revenue
        FROM appointments a
        JOIN clinics c ON a.clinic_id = c.id
        WHERE c.hospital_id = ? 
        AND a.status = 'completed'
        AND (a.appointment_date BETWEEN ? AND ?)
        GROUP BY DATE(a.appointment_date)
        ORDER BY date
    ");
    $stmt->execute([$hospital_id, $start_date, $end_date]);
    $revenue_by_date = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error in financial_report.php: " . $e->getMessage());
    $load_error = 'حدث خطأ في تحميل التقرير. يرجى المحاولة لاحقاً.';
    $revenue_summary = ['total_revenue' => 0, 'total_appointments' => 0, 'avg_revenue_per_visit' => 0];
    $revenue_by_clinic = [];
    $revenue_by_date = [];
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">إجمالي الإيرادات</p>
                    <h3 class="text-2xl font-bold text-gray-900 mt-1">
                        <?php echo number_format($revenue_summary['total_revenue'] ?? 0, 2); ?> ج.م
                    </h3>
                </div>
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">عدد الكشوفات</p>
                    <h3 class="text-2xl font-bold text-gray-900 mt-1">
                        <?php echo number_format($revenue_summary['total_appointments'] ?? 0); ?>
                    </h3>
                </div>
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">متوسط سعر الكشف</p>
                    <h3 class="text-2xl font-bold text-gray-900 mt-1">
                        <?php echo number_format($revenue_summary['avg_revenue_per_visit'] ?? 0, 2); ?> ج.م
                    </h3>
                </div>
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Revenue by Clinic -->
    <div class="bg-white rounded-lg shadow overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">الإيرادات حسب العيادة</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">اسم العيادة</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عدد الكشوفات</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">إجمالي الإيرادات</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($revenue_by_clinic)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">لا توجد بيانات متاحة</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($revenue_by_clinic as $index => $clinic): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $index + 1; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($clinic['clinic_name']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo number_format($clinic['appointment_count']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-green-600">
                                        <?php echo number_format($clinic['total_revenue'], 2); ?> ج.م
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Revenue by Date -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">الإيرادات حسب التاريخ</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">التاريخ</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عدد الكشوفات</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">إجمالي الإيرادات</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($revenue_by_date)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">لا توجد بيانات متاحة</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($revenue_by_date as $index => $day): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $index + 1; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo date('Y-m-d', strtotime($day['date'])); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo number_format($day['appointment_count']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-green-600">
                                        <?php echo number_format($day['daily_revenue'], 2); ?> ج.م
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php renderExportButtons('financial', $start_date, $end_date); ?>
    
    <!-- Chart Section -->
    <div class="mt-8 bg-white p-6 rounded-lg shadow">
        <h3 class="text-lg font-medium text-gray-900 mb-4">مخطط الإيرادات</h3>
        <div class="h-64">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    const revenueData = <?php echo json_encode($revenue_by_date); ?>;
    
    const labels = revenueData.map(item => item.date);
    const data = revenueData.map(item => item.daily_revenue);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'الإيرادات اليومية',
                data: data,
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                borderColor: 'rgba(79, 70, 229, 1)',
                borderWidth: 2,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString() + ' ج.م';
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    rtl: true
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'الإيراد: ' + context.parsed.y.toLocaleString() + ' ج.م';
                        }
                    },
                    rtl: true
                }
            }
        }
    });
});
</script>

<!-- Navigation Button -->
<div class="container mx-auto px-4 py-4">
    <a href="../index.php" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
        <i class="fas fa-arrow-right ml-2"></i>
        العودة للوحة التحكم
    </a>
</div>

</body>
</html>