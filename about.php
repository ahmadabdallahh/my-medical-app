<?php
require_once 'includes/functions.php';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>من نحن - نظام حجز المواعيد الطبية</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="index.php">الرئيسية</a>
            <i class="fas fa-chevron-left"></i>
            <span>من نحن</span>
        </div>

        <!-- About Section -->
        <div class="about-section">
            <div class="section-header">
                <h1>من نحن</h1>
                <p>نظام حجز المواعيد الطبية - خدمة رقمية متكاملة</p>
            </div>

            <div class="about-content">
                <div class="about-intro">
                    <div class="intro-text">
                        <h2>مرحباً بكم في نظام حجز المواعيد الطبية</h2>
                        <p>
                            نحن نقدم خدمة رقمية متكاملة لحجز المواعيد الطبية بسهولة وأمان.
                            هدفنا هو تسهيل عملية حجز المواعيد الطبية وتوفير الوقت والجهد على المرضى.
                        </p>
                        <p>
                            من خلال منصتنا، يمكنك البحث عن المستشفيات والعيادات والأطباء،
                            وحجز المواعيد الطبية بضغطة زر واحدة، وإدارة مواعيدك بسهولة.
                        </p>
                    </div>
                    <div class="intro-image">
                        <i class="fas fa-heartbeat"></i>
                    </div>
                </div>

                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h3>البحث السريع</h3>
                        <p>ابحث عن المستشفيات والعيادات والأطباء بسهولة وسرعة</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h3>حجز المواعيد</h3>
                        <p>احجز موعدك الطبي بسهولة وأمان من خلال منصتنا</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <h3>أطباء متخصصون</h3>
                        <p>تعرف على الأطباء المتخصصين وخبراتهم ومؤهلاتهم</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-hospital"></i>
                        </div>
                        <h3>مستشفيات معتمدة</h3>
                        <p>نعمل مع أفضل المستشفيات والعيادات المعتمدة</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3>خدمة 24/7</h3>
                        <p>خدمة متاحة على مدار الساعة لحجز المواعيد</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3>أمان وخصوصية</h3>
                        <p>نضمن أمان وخصوصية بياناتك الشخصية</p>
                    </div>
                </div>

                <div class="mission-vision">
                    <div class="mission">
                        <h3>مهمتنا</h3>
                        <p>
                            تسهيل عملية حجز المواعيد الطبية وتوفير خدمة رقمية متكاملة
                            تساعد المرضى على الوصول للرعاية الطبية بسهولة وسرعة.
                        </p>
                    </div>

                    <div class="vision">
                        <h3>رؤيتنا</h3>
                        <p>
                            أن نكون المنصة الرائدة في مجال حجز المواعيد الطبية في مصر،
                            ونقدم خدمة متميزة تساهم في تحسين جودة الرعاية الصحية.
                        </p>
                    </div>
                </div>

                <div class="stats-section">
                    <h3>إحصائيات النظام</h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-number">15+</div>
                            <div class="stat-label">مستشفى</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">50+</div>
                            <div class="stat-label">عيادة</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">100+</div>
                            <div class="stat-label">طبيب</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">1000+</div>
                            <div class="stat-label">موعد محجوز</div>
                        </div>
                    </div>
                </div>

                <div class="contact-section">
                    <h3>تواصل معنا</h3>
                    <div class="contact-grid">
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <div>
                                <h4>الهاتف</h4>
                                <p>+20 123 456 7890</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <h4>البريد الإلكتروني</h4>
                                <p>healthh.tech404@gmail.com</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <h4>العنوان</h4>
                                <p>القاهرة، مصر</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-clock"></i>
                            <div>
                                <h4>ساعات العمل</h4>
                                <p>24/7</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/script.js"></script>
</body>
</html>
