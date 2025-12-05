<?php
$page_title = 'تقرير المرضى';
require_once 'base_report.php';

list($start_date, $end_date) = getDateRange(30);

try {
    // Get total patients count
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'patient'");
    $stmt->execute();
    $total_patients = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get new patients count (for the selected date range)
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users 
                           WHERE role = 'patient' 
                           AND DATE(created_at) BETWEEN ? AND ?");
    $stmt->execute([$start_date, $end_date]);
    $new_patients = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get patients by gender
    $stmt = $conn->prepare("SELECT 
                    SUM(CASE WHEN gender = 'male' THEN 1 ELSE 0 END) as male,
                    SUM(CASE WHEN gender = 'female' THEN 1 ELSE 0 END) as female,
                    COUNT(*) as total
                    FROM users 
                    WHERE role = 'patient'");
    $stmt->execute();
    $gender_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get patients by age group
    $stmt = $conn->prepare("SELECT 
                  SUM(CASE WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 18 THEN 1 ELSE 0 END) as under_18,
                  SUM(CASE WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 18 AND 40 THEN 1 ELSE 0 END) as age_18_40,
                  SUM(CASE WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) > 40 THEN 1 ELSE 0 END) as over_40
                  FROM users 
                  WHERE role = 'patient' AND date_of_birth IS NOT NULL");
    $stmt->execute();
    $age_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get recent patients (last 10)
    $stmt = $conn->prepare("SELECT id, full_name as name, email, phone, gender, date_of_birth, created_at 
                           FROM users 
                           WHERE role = 'patient' 
                           ORDER BY created_at DESC 
                           LIMIT 10");
    $stmt->execute();
    $recent_patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error in patients_report.php: " . $e->getMessage());
    $_SESSION['error'] = 'حدث خطأ في تحميل التقرير';
    header('Location: ../index.php');
    exit();
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

        .report-container {
            max-width: 1100px;
            margin: 0 auto;
        }

        .report-section {
            background-color: #ffffff;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 12px 28px -18px rgba(37, 99, 235, 0.45);
        }

        .report-card {
            border-radius: 1rem;
            border: 1px solid #f1f5f9;
            background: #f8fafc;
            transition: transform 0.16s ease, box-shadow 0.16s ease;
        }

        .report-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 30px -24px rgba(37, 99, 235, 0.55);
        }

        .table-enhanced tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .table-enhanced tbody tr:hover {
            background-color: #eff6ff;
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body class="bg-gray-100">

<div class="report-container mx-auto px-4 py-6 space-y-8">
    <div class="report-section p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800"><?php echo $page_title; ?></h1>
            <p class="text-gray-600">
                من <?php echo date('Y-m-d', strtotime($start_date)); ?> إلى <?php echo date('Y-m-d', strtotime($end_date)); ?>
            </p>
        </div>
    </div>
    
    <?php renderDateFilter($start_date, $end_date); ?>


<!-- Stats Cards -->
<section class="report-section p-6">
    <h2 class="section-title">
        <i class="fas fa-chart-pie text-blue-500"></i>
        نظرة سريعة
    </h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-6">
        <!-- Total Patients -->
        <div class="report-card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">إجمالي المرضى</p>
                    <p class="text-2xl font-semibold text-gray-800"><?php echo number_format($total_patients); ?></p>
                </div>
                <div class="p-3 rounded-full bg-blue-50 text-blue-600">
                    <i class="fas fa-users text-xl"></i>
                </div>
            </div>
        </div>

        <!-- New Patients -->
        <div class="report-card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">مرضى جدد</p>
                    <p class="text-2xl font-semibold text-gray-800"><?php echo number_format($new_patients); ?></p>
                    <p class="text-xs text-gray-500 mt-1">من <span dir="ltr"><?php echo $start_date; ?></span> إلى <span dir="ltr"><?php echo $end_date; ?></span></p>
                </div>
                <div class="p-3 rounded-full bg-green-50 text-green-600">
                    <i class="fas fa-user-plus text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Gender Distribution -->
        <div class="report-card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">توزيع النوع</p>
                    <div class="flex items-center gap-6 mt-2">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 bg-blue-500 rounded-full ml-1"></span>
                            <span class="text-sm text-gray-600">ذكور:</span>
                            <span class="text-sm font-semibold text-gray-800"><?php echo (int)$gender_stats['male']; ?></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 bg-pink-500 rounded-full ml-1"></span>
                            <span class="text-sm text-gray-600">إناث:</span>
                            <span class="text-sm font-semibold text-gray-800"><?php echo (int)$gender_stats['female']; ?></span>
                        </div>
                    </div>
                </div>
                <div class="p-3 rounded-full bg-purple-50 text-purple-600">
                    <i class="fas fa-venus-mars text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Age Groups -->
        <div class="report-card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">الفئات العمرية</p>
                    <div class="space-y-1.5 mt-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">أقل من 18</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-gray-100 text-gray-700 font-medium"><?php echo (int)$age_stats['under_18']; ?></span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">18 - 40 سنة</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-gray-100 text-gray-700 font-medium"><?php echo (int)$age_stats['age_18_40']; ?></span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">أكثر من 40 سنة</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-gray-100 text-gray-700 font-medium"><?php echo (int)$age_stats['over_40']; ?></span>
                        </div>
                    </div>
                </div>
                <div class="p-3 rounded-full bg-yellow-50 text-yellow-600">
                    <i class="fas fa-chart-pie text-xl"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Recent Patients Table -->
<section class="report-section overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 bg-white/70 backdrop-blur-sm flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-800">أحدث المرضى المسجلين</h3>
        <span class="text-sm text-gray-500 hidden sm:block">آخر 10 مرضى تم تسجيلهم</span>
    </div>
    <div class="overflow-x-auto">
        <table class="table-enhanced min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الاسم</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">العمر</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الجنس</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الهاتف</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاريخ التسجيل</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($recent_patients as $patient): 
                    $age = $patient['date_of_birth'] ? date_diff(date_create($patient['date_of_birth']), date_create('today'))->y : 'N/A';
                ?>
                <tr class="transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center shadow-sm">
                                <i class="fas fa-user text-blue-500"></i>
                            </div>
                            <div class="mr-4">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($patient['name']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($patient['email']); ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo $age; ?> سنة
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php 
                        $gender_icon = $patient['gender'] == 'male' ? 'mars' : 'venus';
                        $gender_color = $patient['gender'] == 'male' ? 'text-blue-500' : 'text-pink-500';
                        ?>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $gender_color; ?>">
                            <i class="fas fa-<?php echo $gender_icon; ?> ml-1"></i>
                            <?php echo $patient['gender'] == 'male' ? 'ذكر' : 'أنثى'; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo htmlspecialchars($patient['phone']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo date('Y-m-d', strtotime($patient['created_at'])); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="../patients/view.php?id=<?php echo $patient['id']; ?>" class="text-blue-600 hover:text-blue-900 ml-4">
                            <i class="fas fa-eye ml-1"></i> عرض
                        </a>
                        <a href="../patients/edit.php?id=<?php echo $patient['id']; ?>" class="text-indigo-600 hover:text-indigo-900">
                            <i class="fas fa-edit ml-1"></i> تعديل
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Patient Registration Chart -->
<section class="report-section p-6">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-semibold text-gray-800">تسجيلات المرضى حسب الشهر</h3>
        <div class="flex items-center space-x-2 rtl:space-x-reverse">
            <span class="text-sm text-gray-500">آخر 12 شهراً</span>
        </div>
    </div>
    <div class="h-80">
        <canvas id="patientRegistrationsChart"></canvas>
    </div>
</div>

<script>
// Patient Registrations Chart
const ctx = document.getElementById('patientRegistrationsChart').getContext('2d');
const chart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: [
            'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
            'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'
        ],
        datasets: [{
            label: 'تسجيلات المرضى',
            data: [12, 19, 15, 25, 22, 30, 28, 35, 32, 40, 38, 45],
            backgroundColor: 'rgba(59, 130, 246, 0.05)',
            borderColor: 'rgba(59, 130, 246, 1)',
            borderWidth: 2,
            tension: 0.3,
            fill: true,
            pointBackgroundColor: 'white',
            pointBorderColor: 'rgba(59, 130, 246, 1)',
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: 'white',
                titleColor: '#1F2937',
                bodyColor: '#4B5563',
                borderColor: '#E5E7EB',
                borderWidth: 1,
                padding: 12,
                displayColors: false,
                yAlign: 'bottom',
                callbacks: {
                    label: function(context) {
                        return 'عدد المرضى: ' + context.raw;
                    }
                }
            }
        },
        scales: {
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    color: '#6B7280'
                }
            },
            y: {
                beginAtZero: true,
                grid: {
                    color: '#F3F4F6'
                },
                ticks: {
                    color: '#6B7280',
                    stepSize: 10
                }
            }
        }
    }
});
</script>
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