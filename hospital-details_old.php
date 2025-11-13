<?php
require_once 'includes/functions.php';

// التحقق من تسجيل الدخول
if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}

$user = get_logged_in_user();

// الحصول على معرف المستشفى
$hospital_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$hospital_id) {
    header("Location: hospitals.php");
    exit();
}

// الحصول على تفاصيل المستشفى
$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    header("Location: hospitals.php");
    exit();
}

try {
    $stmt = $conn->prepare("SELECT * FROM hospitals WHERE id = ?");
    $stmt->execute([$hospital_id]);
    $hospital = $stmt->fetch();
    
    if (!$hospital) {
        header("Location: hospitals.php");
        exit();
    }
    
    // الحصول على العيادات
    $stmt = $conn->prepare("
        SELECT c.*, s.name as specialty_name, COUNT(d.id) as doctors_count
        FROM clinics c
        LEFT JOIN specialties s ON c.specialty_id = s.id
        LEFT JOIN doctors d ON c.id = d.clinic_id
        WHERE c.hospital_id = ?
        GROUP BY c.id
        ORDER BY c.name
    ");
    $stmt->execute([$hospital_id]);
    $clinics = $stmt->fetchAll();
    
    // الحصول على الأطباء
    $stmt = $conn->prepare("
        SELECT d.*, s.name as specialty_name, c.name as clinic_name
        FROM doctors d
        LEFT JOIN specialties s ON d.specialty_id = s.id
        LEFT JOIN clinics c ON d.clinic_id = c.id
        WHERE c.hospital_id = ?
        ORDER BY d.full_name
    ");
    $stmt->execute([$hospital_id]);
    $doctors = $stmt->fetchAll();
    
} catch (PDOException $e) {
    header("Location: hospitals.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hospital['name']); ?> - صحة</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .hospital-details-page {
            padding-top: 80px;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);
        }
        
        .hospital-details-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 20px;
        }
        
        .hospital-hero {
            background: var(--bg-primary);
            border-radius: var(--radius-2xl);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            margin-bottom: 3rem;
            border: 1px solid rgba(79, 70, 229, 0.1);
        }
        
        .hospital-hero-image {
            height: 300px;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-purple) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 4rem;
            position: relative;
        }
        
        .hospital-hero-content {
            padding: 2rem;
        }
        
        .hospital-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .hospital-title {
            flex: 1;
        }
        
        .hospital-name {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .hospital-type {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-full);
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 1rem;
        }
        
        .hospital-type.government {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }
        
        .hospital-type.private {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }
        
        .hospital-rating {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: var(--bg-secondary);
            padding: 1rem;
            border-radius: var(--radius-xl);
            min-width: 200px;
        }
        
        .rating-stars {
            color: #fbbf24;
            font-size: 1.25rem;
        }
        
        .rating-text {
            font-weight: 700;
            color: var(--text-primary);
            font-size: 1.125rem;
        }
        
        .hospital-description {
            color: var(--text-secondary);
            line-height: 1.8;
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }
        
        .hospital-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .info-card {
            background: var(--bg-secondary);
            padding: 1.5rem;
            border-radius: var(--radius-xl);
            border: 1px solid var(--border-color);
        }
        
        .info-card h4 {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--primary-blue);
            font-size: 1.125rem;
            margin-bottom: 1rem;
        }
        
        .info-card p {
            color: var(--text-primary);
            font-weight: 600;
        }
        
        .hospital-features {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 2rem;
        }
        
        .feature-tag {
            padding: 0.5rem 1rem;
            background: rgba(79, 70, 229, 0.1);
            color: var(--primary-blue);
            border-radius: var(--radius-full);
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .sections-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            background: var(--bg-primary);
            padding: 1rem;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
        }
        
        .tab-button {
            padding: 0.75rem 1.5rem;
            background: transparent;
            color: var(--text-secondary);
            border: none;
            border-radius: var(--radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .tab-button.active {
            background: var(--primary-blue);
            color: white;
        }
        
        .tab-button:hover {
            background: var(--primary-blue);
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .clinics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .clinic-card {
            background: var(--bg-primary);
            border-radius: var(--radius-xl);
            padding: 1.5rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            transition: var(--transition);
        }
        
        .clinic-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-xl);
        }
        
        .clinic-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .clinic-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .clinic-specialty {
            color: var(--primary-blue);
            font-weight: 600;
        }
        
        .clinic-fee {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-full);
            font-weight: 600;
        }
        
        .clinic-description {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        
        .clinic-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-blue);
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        .clinic-actions {
            display: flex;
            gap: 1rem;
        }
        
        .btn-book {
            flex: 1;
            padding: 0.75rem;
            background: var(--primary-blue);
            color: white;
            text-decoration: none;
            text-align: center;
            border-radius: var(--radius);
            font-weight: 600;
            transition: var(--transition);
        }
        
        .btn-book:hover {
            background: var(--primary-dark);
        }
        
        .btn-doctors {
            flex: 1;
            padding: 0.75rem;
            background: var(--bg-secondary);
            color: var(--text-primary);
            text-decoration: none;
            text-align: center;
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            font-weight: 600;
            transition: var(--transition);
        }
        
        .btn-doctors:hover {
            background: var(--border-color);
        }
        
        .doctors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        .doctor-card {
            background: var(--bg-primary);
            border-radius: var(--radius-xl);
            padding: 1.5rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            transition: var(--transition);
        }
        
        .doctor-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-xl);
        }
        
        .doctor-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .doctor-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-purple) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        
        .doctor-info h4 {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }
        
        .doctor-specialty {
            color: var(--primary-blue);
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .doctor-rating {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .doctor-stars {
            color: #fbbf24;
        }
        
        .doctor-rating-text {
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .doctor-details {
            margin-bottom: 1.5rem;
        }
        
        .doctor-detail {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        
        .doctor-detail i {
            color: var(--primary-blue);
            width: 16px;
        }
        
        .doctor-actions {
            display: flex;
            gap: 1rem;
        }
        
        .btn-appointment {
            flex: 1;
            padding: 0.75rem;
            background: var(--primary-blue);
            color: white;
            text-decoration: none;
            text-align: center;
            border-radius: var(--radius);
            font-weight: 600;
            transition: var(--transition);
        }
        
        .btn-appointment:hover {
            background: var(--primary-dark);
        }
        
        .btn-profile {
            flex: 1;
            padding: 0.75rem;
            background: var(--bg-secondary);
            color: var(--text-primary);
            text-decoration: none;
            text-align: center;
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            font-weight: 600;
            transition: var(--transition);
        }
        
        .btn-profile:hover {
            background: var(--border-color);
        }
        
        @media (max-width: 768px) {
            .hospital-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .hospital-rating {
                align-self: stretch;
                justify-content: center;
            }
            
            .sections-tabs {
                flex-direction: column;
            }
            
            .clinics-grid,
            .doctors-grid {
                grid-template-columns: 1fr;
            }
            
            .clinic-actions,
            .doctor-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include 'includes/header.php'; ?>

    <!-- Main Content -->
    <div class="hospital-details-page">
        <div class="hospital-details-container">
            <!-- Hospital Hero Section -->
            <div class="hospital-hero">
                <div class="hospital-hero-image">
                    <i class="fas fa-hospital"></i>
                </div>
                
                <div class="hospital-hero-content">
                    <div class="hospital-header">
                        <div class="hospital-title">
                            <h1 class="hospital-name"><?php echo htmlspecialchars($hospital['name']); ?></h1>
                            <span class="hospital-type <?php echo $hospital['type'] === 'حكومي' ? 'government' : 'private'; ?>">
                                <?php echo htmlspecialchars($hospital['type']); ?>
                            </span>
                        </div>
                        
                        <div class="hospital-rating">
                            <div class="rating-stars">
                                <?php
                                $rating = $hospital['rating'];
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $rating) {
                                        echo '<i class="fas fa-star"></i>';
                                    } elseif ($i - $rating < 1) {
                                        echo '<i class="fas fa-star-half-alt"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                            </div>
                            <span class="rating-text"><?php echo number_format($rating, 1); ?></span>
                        </div>
                    </div>
                    
                    <p class="hospital-description"><?php echo htmlspecialchars($hospital['description']); ?></p>
                    
                    <div class="hospital-info-grid">
                        <div class="info-card">
                            <h4><i class="fas fa-map-marker-alt"></i> العنوان</h4>
                            <p><?php echo htmlspecialchars($hospital['address']); ?></p>
                        </div>
                        
                        <div class="info-card">
                            <h4><i class="fas fa-phone"></i> الهاتف</h4>
                            <p><?php echo htmlspecialchars($hospital['phone']); ?></p>
                        </div>
                        
                        <div class="info-card">
                            <h4><i class="fas fa-envelope"></i> البريد الإلكتروني</h4>
                            <p><?php echo htmlspecialchars($hospital['email']); ?></p>
                        </div>
                        
                        <div class="info-card">
                            <h4><i class="fas fa-clock"></i> ساعات العمل</h4>
                            <p><?php echo $hospital['is_24h'] ? 'مفتوح 24 ساعة' : 'ساعات عمل محددة'; ?></p>
                        </div>
                    </div>
                    
                    <div class="hospital-features">
                        <?php if ($hospital['is_24h']): ?>
                            <span class="feature-tag">24 ساعة</span>
                        <?php endif; ?>
                        <span class="feature-tag">خدمات طبية متكاملة</span>
                        <span class="feature-tag">أطباء متخصصون</span>
                        <span class="feature-tag">أحدث التقنيات</span>
                        <?php if ($hospital['type'] === 'خاص'): ?>
                            <span class="feature-tag">خدمات فاخرة</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Tabs Section -->
            <div class="sections-tabs">
                <button class="tab-button active" onclick="showTab('clinics')">
                    <i class="fas fa-stethoscope"></i>
                    العيادات (<?php echo count($clinics); ?>)
                </button>
                <button class="tab-button" onclick="showTab('doctors')">
                    <i class="fas fa-user-md"></i>
                    الأطباء (<?php echo count($doctors); ?>)
                </button>
            </div>

            <!-- Clinics Tab -->
            <div id="clinics" class="tab-content active">
                <?php if (empty($clinics)): ?>
                    <div class="no-content">
                        <i class="fas fa-stethoscope"></i>
                        <h3>لا توجد عيادات</h3>
                        <p>لم يتم العثور على عيادات في هذا المستشفى.</p>
                    </div>
                <?php else: ?>
                    <div class="clinics-grid">
                        <?php foreach ($clinics as $clinic): ?>
                            <div class="clinic-card">
                                <div class="clinic-header">
                                    <div>
                                        <h3 class="clinic-name"><?php echo htmlspecialchars($clinic['name']); ?></h3>
                                        <p class="clinic-specialty"><?php echo htmlspecialchars($clinic['specialty_name']); ?></p>
                                    </div>
                                    <div class="clinic-fee">
                                        <?php echo $clinic['consultation_fee']; ?> ج.م
                                    </div>
                                </div>
                                
                                <p class="clinic-description"><?php echo htmlspecialchars($clinic['description']); ?></p>
                                
                                <div class="clinic-stats">
                                    <div class="stat-item">
                                        <div class="stat-number"><?php echo $clinic['doctors_count']; ?></div>
                                        <div class="stat-label">طبيب</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-number"><?php echo $clinic['consultation_fee']; ?></div>
                                        <div class="stat-label">ج.م للكشف</div>
                                    </div>
                                </div>
                                
                                <div class="clinic-actions">
                                    <a href="book.php?clinic=<?php echo $clinic['id']; ?>" class="btn-book">
                                        <i class="fas fa-calendar-plus"></i>
                                        حجز موعد
                                    </a>
                                    <a href="doctors.php?clinic=<?php echo $clinic['id']; ?>" class="btn-doctors">
                                        <i class="fas fa-user-md"></i>
                                        الأطباء
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Doctors Tab -->
            <div id="doctors" class="tab-content">
                <?php if (empty($doctors)): ?>
                    <div class="no-content">
                        <i class="fas fa-user-md"></i>
                        <h3>لا يوجد أطباء</h3>
                        <p>لم يتم العثور على أطباء في هذا المستشفى.</p>
                    </div>
                <?php else: ?>
                    <div class="doctors-grid">
                        <?php foreach ($doctors as $doctor): ?>
                            <div class="doctor-card">
                                <div class="doctor-header">
                                    <div class="doctor-avatar">
                                        <i class="fas fa-user-md"></i>
                                    </div>
                                    <div class="doctor-info">
                                        <h4><?php echo htmlspecialchars($doctor['full_name']); ?></h4>
                                        <p class="doctor-specialty"><?php echo htmlspecialchars($doctor['specialty_name']); ?></p>
                                    </div>
                                </div>
                                
                                <div class="doctor-rating">
                                    <div class="doctor-stars">
                                        <?php
                                        $rating = $doctor['rating'];
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $rating) {
                                                echo '<i class="fas fa-star"></i>';
                                            } elseif ($i - $rating < 1) {
                                                echo '<i class="fas fa-star-half-alt"></i>';
                                            } else {
                                                echo '<i class="far fa-star"></i>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    <span class="doctor-rating-text"><?php echo number_format($rating, 1); ?></span>
                                </div>
                                
                                <div class="doctor-details">
                                    <div class="doctor-detail">
                                        <i class="fas fa-graduation-cap"></i>
                                        <span><?php echo htmlspecialchars($doctor['education']); ?></span>
                                    </div>
                                    <div class="doctor-detail">
                                        <i class="fas fa-clock"></i>
                                        <span><?php echo $doctor['experience_years']; ?> سنوات خبرة</span>
                                    </div>
                                    <div class="doctor-detail">
                                        <i class="fas fa-money-bill"></i>
                                        <span><?php echo $doctor['consultation_fee']; ?> ج.م للكشف</span>
                                    </div>
                                </div>
                                
                                <div class="doctor-actions">
                                    <a href="book.php?doctor=<?php echo $doctor['id']; ?>" class="btn-appointment">
                                        <i class="fas fa-calendar-plus"></i>
                                        حجز موعد
                                    </a>
                                    <a href="doctor-profile.php?id=<?php echo $doctor['id']; ?>" class="btn-profile">
                                        <i class="fas fa-user"></i>
                                        الملف الشخصي
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/script.js"></script>
    <script>
        function showTab(tabName) {
            // إخفاء جميع المحتويات
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => {
                content.classList.remove('active');
            });
            
            // إزالة الفئة النشطة من جميع الأزرار
            const tabButtons = document.querySelectorAll('.tab-button');
            tabButtons.forEach(button => {
                button.classList.remove('active');
            });
            
            // إظهار المحتوى المحدد
            document.getElementById(tabName).classList.add('active');
            
            // إضافة الفئة النشطة للزر المحدد
            event.target.classList.add('active');
        }
    </script>
</body>
</html> 