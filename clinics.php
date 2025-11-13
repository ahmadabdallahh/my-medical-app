<?php
require_once 'includes/functions.php';

// التحقق من وجود معرف المستشفى
$hospital_id = isset($_GET['hospital']) ? (int)$_GET['hospital'] : 0;

if (!$hospital_id) {
    header("Location: hospitals.php");
    exit();
}

// الحصول على معلومات المستشفى
$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    $hospital = null;
    $clinics = [];
} else {
    try {
        // الحصول على معلومات المستشفى
        $stmt = $conn->prepare("SELECT * FROM hospitals WHERE id = ?");
        $stmt->execute([$hospital_id]);
        $hospital = $stmt->fetch();

        // الحصول على العيادات
        $stmt = $conn->prepare("
            SELECT c.*, s.name as specialty_name, s.description as specialty_description
            FROM clinics c
            LEFT JOIN specialties s ON c.specialty_id = s.id
            WHERE c.hospital_id = ?
            ORDER BY c.name
        ");
        $stmt->execute([$hospital_id]);
        $clinics = $stmt->fetchAll();
    } catch (PDOException $e) {
        $hospital = null;
        $clinics = [];
    }
}

// إذا لم يتم العثور على المستشفى
if (!$hospital) {
    header("Location: hospitals.php");
    exit();
}

$page_title = "عيادات " . htmlspecialchars($hospital['name']);
?>

<?php include 'includes/dashboard_header.php'; ?>

<style>
        :root {
            --primary-blue: #0EA5E9;
            --medical-green: #10B981;
            --primary-blue-dark: #0284C7;
            --medical-green-dark: #059669;
            --accent-purple: #8B5CF6;
            --accent-pink: #EC4899;
            --gradient-1: linear-gradient(135deg, #0EA5E9 0%, #10B981 100%);
            --gradient-2: linear-gradient(135deg, #7DD3FC 0%, #34D399 100%);
            --gradient-3: linear-gradient(135deg, #38BDF8 0%, #2DD4BF 100%);
            --gradient-4: linear-gradient(135deg, #BAE6FD 0%, #A7F3D0 100%);
            --soft-blue: #E0F2FE;
            --soft-green: #F0FDF4;
            --warm-gray: #F8FAFC;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 25%, #f0fdf4 50%, #ecfdf5 75%, #f0f9ff 100%);
            background-size: 400% 400%;
            animation: gradientShift 25s ease infinite;
            min-height: 100vh;
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 20px;
            position: relative;
            z-index: 2;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.9));
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(14, 165, 233, 0.1);
            border: 1px solid rgba(226, 232, 240, 0.8);
        }

        .breadcrumb a {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .breadcrumb a:hover {
            color: var(--primary-blue-dark);
            transform: translateX(-3px);
        }

        .breadcrumb i {
            color: var(--text-secondary);
            font-size: 0.8rem;
        }

        .breadcrumb span {
            color: var(--text-primary);
            font-weight: 600;
        }

        .hospital-info-section {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.9));
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 3rem;
            margin-bottom: 3rem;
            box-shadow: 0 20px 40px rgba(14, 165, 233, 0.1);
            border: 1px solid rgba(226, 232, 240, 0.8);
            position: relative;
            overflow: hidden;
        }

        .hospital-info-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-1);
        }

        .hospital-info-section::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(14, 165, 233, 0.08) 0%, transparent 70%);
            animation: float 20s infinite linear;
        }

        @keyframes float {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        .hospital-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .hospital-basic-info h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            background: var(--gradient-1);
            background-size: 200% 200%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradientText 3s ease infinite;
            font-weight: 800;
        }

        @keyframes gradientText {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .hospital-address {
            font-size: 1.2rem;
            color: var(--text-secondary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .hospital-address i {
            color: var(--primary-blue);
        }

        .hospital-contact {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .hospital-contact span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .hospital-contact i {
            color: var(--medical-green);
        }

        .hospital-rating {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.5rem;
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.05), rgba(16, 185, 129, 0.05));
            border-radius: 16px;
            border: 1px solid rgba(226, 232, 240, 0.5);
            transition: all 0.3s ease;
        }

        .hospital-rating:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(14, 165, 233, 0.15);
        }

        .rating-stars {
            display: flex;
            gap: 0.25rem;
            margin-bottom: 0.5rem;
        }

        .rating-stars i {
            color: #fbbf24;
            font-size: 1.2rem;
        }

        .rating-text {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-blue);
        }

        .clinics-section {
            margin-bottom: 3rem;
        }

        .section-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-header h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            background: var(--gradient-1);
            background-size: 200% 200%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradientText 3s ease infinite;
            font-weight: 800;
        }

        .section-header p {
            font-size: 1.2rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .clinics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
        }

        .clinic-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.9));
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(14, 165, 233, 0.08);
            border: 1px solid rgba(226, 232, 240, 0.8);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .clinic-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: var(--gradient-3);
            border-radius: 50%;
            opacity: 0.1;
            transform: translate(30px, -30px);
        }

        .clinic-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(14, 165, 233, 0.15);
            border-color: var(--primary-blue);
        }

        .clinic-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            gap: 1rem;
        }

        .clinic-info h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            background: var(--gradient-1);
            background-size: 200% 200%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradientText 3s ease infinite;
            font-weight: 700;
        }

        .specialty-tag {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: var(--gradient-2);
            color: white;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .clinic-rating {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0.75rem;
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.05), rgba(16, 185, 129, 0.05));
            border-radius: 12px;
            border: 1px solid rgba(226, 232, 240, 0.5);
            transition: all 0.3s ease;
        }

        .clinic-rating:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(14, 165, 233, 0.1);
        }

        .clinic-rating .rating-stars i {
            font-size: 1rem;
        }

        .clinic-rating .rating-text {
            font-size: 1.2rem;
        }

        .clinic-content {
            margin-bottom: 1.5rem;
        }

        .clinic-description {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 1rem;
            font-size: 1.05rem;
        }

        .clinic-details {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.05), rgba(16, 185, 129, 0.05));
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .detail-item:hover {
            background: linear-gradient(135deg, var(--primary-blue), var(--medical-green));
            color: white;
            transform: translateX(-5px);
        }

        .detail-item:hover i {
            color: white;
        }

        .detail-item i {
            color: var(--primary-blue);
            font-size: 1.1rem;
            width: 20px;
            transition: all 0.3s ease;
        }

        .specialty-info {
            background: linear-gradient(135deg, rgba(240, 249, 255, 0.8), rgba(240, 253, 244, 0.8));
            border-right: 4px solid var(--medical-green);
            padding: 1rem;
            border-radius: 10px;
            margin-top: 1rem;
        }

        .specialty-info h4 {
            color: var(--medical-green);
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .specialty-info p {
            color: var(--text-secondary);
            line-height: 1.5;
        }

        .clinic-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn-view, .btn-book {
            flex: 1;
            min-width: 140px;
            padding: 0.875rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 1rem;
            position: relative;
            overflow: hidden;
        }

        .btn-view::before, .btn-book::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .btn-view:hover::before, .btn-book:hover::before {
            left: 100%;
        }

        .btn-view {
            background: var(--gradient-1);
            color: white;
        }

        .btn-view:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 20px rgba(14, 165, 233, 0.4);
        }

        .btn-book {
            background: var(--gradient-2);
            color: white;
        }

        .btn-book:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 20px rgba(125, 211, 252, 0.4);
        }

        .no-clinics {
            text-align: center;
            padding: 4rem 2rem;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.9));
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(14, 165, 233, 0.1);
            border: 1px solid rgba(226, 232, 240, 0.8);
        }

        .no-clinics i {
            font-size: 4rem;
            color: var(--primary-blue);
            margin-bottom: 1rem;
        }

        .no-clinics h3 {
            font-size: 1.8rem;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .no-clinics p {
            color: var(--text-secondary);
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }

        .btn-primary {
            display: inline-block;
            padding: 1rem 2rem;
            background: var(--gradient-1);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(14, 165, 233, 0.4);
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem 15px;
            }

            .hospital-header {
                flex-direction: column;
                text-align: center;
            }

            .hospital-basic-info h1 {
                font-size: 2rem;
            }

            .hospital-contact {
                justify-content: center;
            }

            .clinics-grid {
                grid-template-columns: 1fr;
            }

            .clinic-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .clinic-actions {
                flex-direction: column;
            }

            .btn-view, .btn-book {
                width: 100%;
            }
        }
    </style>

    <div class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="index.php">الرئيسية</a>
            <i class="fas fa-chevron-left"></i>
            <a href="hospitals.php">المستشفيات</a>
            <i class="fas fa-chevron-left"></i>
            <span>عيادات <?php echo htmlspecialchars($hospital['name']); ?></span>
        </div>

        <!-- Hospital Info -->
        <div class="hospital-info-section">
            <div class="hospital-header">
                <div class="hospital-basic-info">
                    <h1><?php echo htmlspecialchars($hospital['name']); ?></h1>
                    <p class="hospital-address">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo htmlspecialchars($hospital['address']); ?>
                    </p>
                    <div class="hospital-contact">
                        <?php if (isset($hospital['phone']) && $hospital['phone']): ?>
                            <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($hospital['phone']); ?></span>
                        <?php endif; ?>
                        <?php if (isset($hospital['email']) && $hospital['email']): ?>
                            <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($hospital['email']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="hospital-rating">
                    <div class="rating-stars">
                        <?php
                        $rating = isset($hospital['rating']) ? $hospital['rating'] : 0;
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
        </div>

        <!-- Clinics Section -->
        <div class="clinics-section">
            <div class="section-header">
                <h2>العيادات المتاحة</h2>
                <p>اختر العيادة المناسبة لحجز موعدك الطبي</p>
            </div>

            <?php if (empty($clinics)): ?>
                <div class="no-clinics">
                    <i class="fas fa-stethoscope"></i>
                    <h3>لا توجد عيادات</h3>
                    <p>لم يتم العثور على عيادات في هذا المستشفى حالياً.</p>
                    <a href="hospitals.php" class="btn-primary">العودة للمستشفيات</a>
                </div>
            <?php else: ?>
                <div class="clinics-grid">
                    <?php foreach ($clinics as $clinic): ?>
                        <div class="clinic-card">
                            <div class="clinic-header">
                                <div class="clinic-info">
                                    <h3 class="clinic-name"><?php echo htmlspecialchars($clinic['name']); ?></h3>
                                    <?php if (isset($clinic['specialty_name']) && $clinic['specialty_name']): ?>
                                        <span class="specialty-tag"><?php echo htmlspecialchars($clinic['specialty_name']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="clinic-rating">
                                    <div class="rating-stars">
                                        <?php
                                        $clinic_rating = isset($clinic['rating']) ? $clinic['rating'] : 0;
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $clinic_rating) {
                                                echo '<i class="fas fa-star"></i>';
                                            } elseif ($i - $clinic_rating < 1) {
                                                echo '<i class="fas fa-star-half-alt"></i>';
                                            } else {
                                                echo '<i class="far fa-star"></i>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    <span class="rating-text"><?php echo number_format($clinic_rating, 1); ?></span>
                                </div>
                            </div>

                            <div class="clinic-content">
                                <?php if (isset($clinic['description']) && $clinic['description']): ?>
                                    <p class="clinic-description"><?php echo htmlspecialchars($clinic['description']); ?></p>
                                <?php endif; ?>

                                <div class="clinic-details">
                                    <?php if (isset($clinic['phone']) && $clinic['phone']): ?>
                                        <div class="detail-item">
                                            <i class="fas fa-phone"></i>
                                            <span><?php echo htmlspecialchars($clinic['phone']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (isset($clinic['email']) && $clinic['email']): ?>
                                        <div class="detail-item">
                                            <i class="fas fa-envelope"></i>
                                            <span><?php echo htmlspecialchars($clinic['email']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (isset($clinic['consultation_fee']) && $clinic['consultation_fee']): ?>
                                        <div class="detail-item">
                                            <i class="fas fa-money-bill-wave"></i>
                                            <span>رسوم الاستشارة: <?php echo number_format($clinic['consultation_fee']); ?> جنيه</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if (isset($clinic['specialty_description']) && $clinic['specialty_description']): ?>
                                    <div class="specialty-info">
                                        <h4>التخصص:</h4>
                                        <p><?php echo htmlspecialchars($clinic['specialty_description']); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="clinic-actions">
                                <a href="doctors.php?clinic=<?php echo $clinic['id']; ?>" class="btn-view">
                                    <i class="fas fa-user-md"></i>
                                    عرض الأطباء
                                </a>
                                <a href="book.php?clinic=<?php echo $clinic['id']; ?>" class="btn-book">
                                    <i class="fas fa-calendar-plus"></i>
                                    حجز موعد
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
    
    <?php include 'includes/dashboard_footer.php'; ?>