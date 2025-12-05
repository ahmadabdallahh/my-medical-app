<?php
// export_report.php
// Lightweight export endpoint for reports (CSV for Excel, print-friendly HTML for PDF)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$root_dir = dirname(dirname(__DIR__));
require_once $root_dir . '/config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Params
$type = isset($_GET['type']) ? trim($_GET['type']) : 'appointments';
$format = isset($_GET['format']) ? strtolower(trim($_GET['format'])) : 'excel';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$hospital_id = isset($_SESSION['hospital_id']) ? (int)$_SESSION['hospital_id'] : (isset($_GET['hospital_id']) ? (int)$_GET['hospital_id'] : 1);

// Fetch data based on report type
$data = [];
$headers = [];
$title = '';

try {
    switch ($type) {
        case 'appointments':
            $title = 'تقرير المواعيد';
            $headers = ['#', 'المريض', 'الهاتف', 'الطبيب', 'العيادة', 'التاريخ', 'الوقت', 'الحالة'];
            $stmt = $conn->prepare("SELECT a.*, 
                    CONCAT(p.first_name,' ',p.last_name) AS patient_name, p.phone AS patient_phone,
                    CONCAT(d.first_name,' ',d.last_name) AS doctor_name,
                    c.name AS clinic_name
                FROM appointments a
                JOIN patients p ON a.patient_id = p.id
                JOIN doctors d ON a.doctor_id = d.id
                JOIN clinics c ON a.clinic_id = c.id
                WHERE c.hospital_id = ? AND (a.appointment_date BETWEEN ? AND ?)
                ORDER BY a.appointment_date DESC, a.appointment_time DESC");
            $stmt->execute([$hospital_id, $start_date, $end_date]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $idx = 1;
            foreach ($rows as $r) {
                $data[] = [
                    $idx++,
                    $r['patient_name'],
                    $r['patient_phone'],
                    $r['doctor_name'],
                    $r['clinic_name'],
                    date('Y-m-d', strtotime($r['appointment_date'])),
                    date('H:i', strtotime($r['appointment_time'])),
                    $r['status']
                ];
            }
            break;

        case 'patients':
            $title = 'تقرير المرضى';
            $headers = ['#', 'الاسم', 'البريد', 'الهاتف', 'الجنس', 'تاريخ الميلاد', 'تاريخ التسجيل'];
            $stmt = $conn->prepare("SELECT full_name AS name, email, phone, gender, date_of_birth, created_at
                FROM users WHERE role = 'patient' AND DATE(created_at) BETWEEN ? AND ?
                ORDER BY created_at DESC");
            $stmt->execute([$start_date, $end_date]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $idx = 1;
            foreach ($rows as $r) {
                $data[] = [
                    $idx++,
                    $r['name'],
                    $r['email'],
                    $r['phone'],
                    $r['gender'] === 'male' ? 'ذكر' : 'أنثى',
                    $r['date_of_birth'] ? date('Y-m-d', strtotime($r['date_of_birth'])) : '',
                    date('Y-m-d', strtotime($r['created_at']))
                ];
            }
            break;

        case 'financial':
        default:
            $title = 'التقارير المالية';
            $headers = ['#', 'التاريخ', 'عدد الكشوفات', 'الإيراد اليومي (ج.م)'];
            $stmt = $conn->prepare("SELECT DATE(a.appointment_date) AS dt, COUNT(a.id) AS cnt, SUM(a.fee) AS rev
                FROM appointments a JOIN clinics c ON a.clinic_id = c.id
                WHERE c.hospital_id = ? AND a.status = 'completed' AND (a.appointment_date BETWEEN ? AND ?)
                GROUP BY DATE(a.appointment_date)
                ORDER BY dt");
            $stmt->execute([$hospital_id, $start_date, $end_date]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $idx = 1;
            foreach ($rows as $r) {
                $data[] = [
                    $idx++,
                    $r['dt'],
                    (int)$r['cnt'],
                    number_format((float)$r['rev'], 2)
                ];
            }
            break;
    }
} catch (PDOException $e) {
    error_log('Export query error: ' . $e->getMessage());
    $data = [];
}

if ($format === 'excel' || $format === 'csv') {
    // Output CSV (Excel compatible)
    $filename = $type . '_report_' . $start_date . '_to_' . $end_date . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    // UTF-8 BOM for Excel
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    // headers
    fputcsv($out, $headers);
    foreach ($data as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

// Print-friendly HTML for PDF via browser print dialog
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> - تصدير</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; }
        @media print { .no-print { display: none; } }
        .container { max-width: 1100px; margin: 0 auto; }
        table { width: 100%; }
    </style>
</head>
<body class="bg-white">
<div class="container p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($title); ?></h1>
            <p class="text-gray-600 text-sm">من <span dir="ltr"><?php echo $start_date; ?></span> إلى <span dir="ltr"><?php echo $end_date; ?></span></p>
        </div>
        <button onclick="window.print()" class="no-print px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">طباعة</button>
    </div>

    <div class="overflow-x-auto border rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <?php foreach ($headers as $h): ?>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider"><?php echo htmlspecialchars($h); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($data)): ?>
                    <tr>
                        <td colspan="<?php echo count($headers); ?>" class="px-4 py-8 text-center text-gray-500">لا توجد بيانات لعرضها</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($data as $row): ?>
                        <tr class="hover:bg-gray-50">
                            <?php foreach ($row as $cell): ?>
                                <td class="px-4 py-3 text-sm text-gray-800"><?php echo htmlspecialchars((string)$cell); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
