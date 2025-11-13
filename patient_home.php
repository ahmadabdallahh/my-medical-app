<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Initialize PDO connection
$pdo = $conn;

if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}
$user = get_logged_in_user();

// جلب التخصصات والمدن (أو المواقع)
$specialties = get_all_specialties($pdo);
$hospitals = get_all_hospitals($pdo);

// البحث المتقدم
$search_query = isset($_GET['q']) ? sanitize_input($_GET['q']) : '';
$specialty_id = isset($_GET['specialty']) ? (int)$_GET['specialty'] : 0;
$hospital_id = isset($_GET['hospital']) ? (int)$_GET['hospital'] : 0;
$min_rating = isset($_GET['rating']) ? (float)$_GET['rating'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0;

// جلب الأطباء حسب الفلاتر
$doctors = search_doctors($search_query, $specialty_id);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الرئيسية - المرضى | Health Tech</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #f0f4ff 0%, #e0e7ff 100%); }
        .hero-section {
            background: linear-gradient(135deg, #6366f1 0%, #a5b4fc 100%);
            color: #fff;
            padding: 3rem 0 2rem 0;
            text-align: center;
        }
        .hero-section h1 { font-size: 2.7rem; margin-bottom: 1rem; }
        .hero-section p { font-size: 1.2rem; margin-bottom: 2rem; }
        .features-list { display: flex; flex-wrap: wrap; justify-content: center; gap: 2rem; margin-bottom: 2rem; }
        .feature-box {
            background: #fff;
            color: #333;
            border-radius: 1.5rem;
            box-shadow: 0 2px 16px rgba(99,102,241,0.08);
            padding: 1.5rem 2rem;
            min-width: 220px;
            max-width: 320px;
            text-align: center;
        }
        .feature-box i { font-size: 2.2rem; color: #6366f1; margin-bottom: 0.5rem; }
        .search-section {
            background: #fff;
            border-radius: 1.5rem;
            box-shadow: 0 2px 16px rgba(99,102,241,0.08);
            margin: -2rem auto 2rem auto;
            max-width: 900px;
            padding: 2rem;
        }
        .search-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.2rem; }
        .search-grid input, .search-grid select {
            padding: 0.7rem;
            border-radius: 0.7rem;
            border: 1.5px solid #c7d2fe;
            font-size: 1rem;
        }
        .search-actions { text-align: center; margin-top: 1.5rem; }
        .btn-search {
            background: #6366f1;
            color: #fff;
            border: none;
            border-radius: 0.7rem;
            padding: 0.8rem 2.5rem;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-search:hover { background: #4338ca; }
        .doctors-section { max-width: 1100px; margin: 0 auto 2rem auto; }
        .doctors-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem; }
        .doctor-card {
            background: #fff;
            border-radius: 1.2rem;
            box-shadow: 0 2px 16px rgba(99,102,241,0.08);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .doctor-header { display: flex; align-items: center; gap: 1rem; }
        .doctor-img {
            width: 70px; height: 70px; border-radius: 50%; object-fit: cover;
            border: 2.5px solid #6366f1; background: #f3f4f6;
        }
        .doctor-info { flex: 1; }
        .doctor-info h3 { margin: 0; font-size: 1.2rem; color: #3730a3; }
        .doctor-info .specialty { color: #6366f1; font-size: 1rem; }
        .doctor-rating { color: #fbbf24; font-size: 1.1rem; }
        .doctor-meta { display: flex; gap: 1.2rem; font-size: 0.97rem; color: #64748b; margin: 0.5rem 0; }
        .doctor-meta span { display: flex; align-items: center; gap: 0.3rem; }
        .doctor-actions { display: flex; gap: 1rem; }
        .btn-book {
            background: #22c55e;
            color: #fff;
            border: none;
            border-radius: 0.7rem;
            padding: 0.7rem 1.5rem;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-book:hover { background: #16a34a; }
        .btn-details {
            background: #f3f4f6;
            color: #6366f1;
            border: 1.5px solid #6366f1;
            border-radius: 0.7rem;
            padding: 0.7rem 1.5rem;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-details:hover { background: #e0e7ff; }
        @media (max-width: 700px) {
            .features-list { flex-direction: column; gap: 1rem; }
            .doctors-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="hero-section">
    <h1>احجز موعدك الطبي بسهولة</h1>
    <p>ابحث عن أفضل الأطباء والعيادات واحجز موعدك أونلاين في دقائق. تقييمات حقيقية، أوقات متاحة مباشرة، تذكيرات فورية، معلومات شاملة.</p>
    <div class="features-list">
        <div class="feature-box">
            <i class="fas fa-search"></i>
            <div>بحث متقدم حسب التخصص والموقع والتأمين والسعر والتقييم</div>
        </div>
        <div class="feature-box">
            <i class="fas fa-calendar-check"></i>
            <div>عرض أوقات الشواغر الفعلية للطبيب والحجز الفوري</div>
        </div>
        <div class="feature-box">
            <i class="fas fa-bell"></i>
            <div>تذكيرات بالموعد (إشعارات، SMS، إيميل)</div>
        </div>
        <div class="feature-box">
            <i class="fas fa-user-md"></i>
            <div>معلومات شاملة عن الطبيب والعيادة وتقييمات المرضى</div>
        </div>
    </div>
</div>
<div class="search-section">
    <form method="GET" action="">
        <div class="search-grid">
            <input type="text" name="q" placeholder="ابحث عن طبيب بالاسم أو التخصص..." value="<?php echo htmlspecialchars($search_query); ?>">
            <select name="specialty">
                <option value="0">كل التخصصات</option>
                <?php foreach ($specialties as $sp): ?>
                    <option value="<?php echo $sp['id']; ?>" <?php if ($specialty_id == $sp['id']) echo 'selected'; ?>><?php echo htmlspecialchars($sp['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="hospital">
                <option value="0">كل المواقع/المستشفيات</option>
                <?php foreach ($hospitals as $h): ?>
                    <option value="<?php echo $h['id']; ?>" <?php if ($hospital_id == $h['id']) echo 'selected'; ?>><?php echo htmlspecialchars($h['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="rating">
                <option value="0">كل التقييمات</option>
                <option value="4.5" <?php if ($min_rating == 4.5) echo 'selected'; ?>>4.5+ نجوم</option>
                <option value="4.0" <?php if ($min_rating == 4.0) echo 'selected'; ?>>4.0+ نجوم</option>
                <option value="3.5" <?php if ($min_rating == 3.5) echo 'selected'; ?>>3.5+ نجوم</option>
            </select>
            <input type="number" name="max_price" placeholder="أقصى سعر للكشف" min="0" value="<?php echo $max_price ?: ''; ?>">
        </div>
        <div class="search-actions">
            <button type="submit" class="btn-search"><i class="fas fa-search"></i> بحث</button>
            <a href="patient_home.php" class="btn-details"><i class="fas fa-times"></i> مسح الفلاتر</a>
        </div>
    </form>
</div>
<div class="doctors-section">
    <h2 style="text-align:center; color:#3730a3; margin-bottom:1.5rem;">الأطباء المتاحون</h2>
    <?php if (empty($doctors)): ?>
        <div style="text-align:center; color:#64748b; padding:2rem;">لا يوجد أطباء يطابقون معايير البحث.</div>
    <?php else: ?>
        <div class="doctors-grid">
            <?php foreach ($doctors as $doctor): ?>
                <div class="doctor-card">
                    <div class="doctor-header">
                        <img src="assets/images/doctor.png" class="doctor-img" alt="صورة الطبيب">
                        <div class="doctor-info">
                            <h3><?php echo htmlspecialchars($doctor['full_name']); ?></h3>
                            <div class="specialty"><?php echo htmlspecialchars($doctor['specialty_name'] ?? ''); ?></div>
                            <div class="doctor-rating">
                                <?php
                                $rating = $doctor['rating'] ?? 0;
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
                                <span style="color:#3730a3; font-weight:bold; margin-right:7px;">(<?php echo number_format($rating,1); ?>)</span>
                            </div>
                        </div>
                    </div>
                    <div class="doctor-meta">
                        <span><i class="fas fa-hospital"></i> <?php echo htmlspecialchars($doctor['hospital_name'] ?? ''); ?></span>
                        <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($doctor['address'] ?? ''); ?></span>
                        <span><i class="fas fa-money-bill"></i> <?php echo isset($doctor['consultation_fee']) ? $doctor['consultation_fee'] . ' جنيه' : ''; ?></span>
                    </div>
                    <div style="color:#6366f1; font-size:0.98rem; margin-bottom:0.7rem;">
                        <?php echo htmlspecialchars($doctor['bio'] ?? ''); ?>
                    </div>
                    <div class="doctor-actions">
                        <a href="doctor-details.php?id=<?php echo $doctor['id']; ?>" class="btn-details"><i class="fas fa-user-md"></i> تفاصيل الطبيب</a>
                        <a href="book.php?doctor=<?php echo $doctor['id']; ?>" class="btn-book"><i class="fas fa-calendar-plus"></i> احجز الآن</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
<script src="assets/js/script.js"></script>
</body>
</html>
