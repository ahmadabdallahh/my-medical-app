<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>معاينة - تفاصيل الموعد</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 25%, #f093fb 50%, #4facfe 75%, #43e97b 100%);
            background-size: 400% 400%;
            animation: gradientShift 20s ease infinite;
            min-height: 100vh;
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .preview-container {
            max-width: 1000px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .preview-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .preview-header h1 {
            color: white;
            font-size: 2.5rem;
            margin-bottom: 1rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .preview-button {
            display: inline-block;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
        }

        .preview-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.6);
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .feature {
            background: rgba(255, 255, 255, 0.9);
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .feature i {
            font-size: 2rem;
            color: #667eea;
            margin-bottom: 1rem;
        }

        .feature h3 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .feature p {
            color: #666;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="preview-container">
        <div class="preview-header">
            <h1>✨ صفحة تفاصيل الموعد الجديدة</h1>
            <p style="color: white; font-size: 1.2rem; margin-bottom: 2rem;">
                تصميم عصري وألوان رائعة تجعل الصفحة "روشة وحلوة"!
            </p>
            <a href="appointment-details.php?id=77" class="preview-button">
                <i class="fas fa-eye"></i>
                عرض الصفحة المحسنة
            </a>
        </div>

        <div class="features">
            <div class="feature">
                <i class="fas fa-palette"></i>
                <h3>ألوان رائعة</h3>
                <p>تدرجات لونية حديثة مع ألوان بنفسجي ووردي وأزرق وأخضر</p>
            </div>
            <div class="feature">
                <i class="fas fa-magic"></i>
                <h3>تأثيرات حركية</h3>
                <p>انيميشنات سلسة وتأثيرات hover رائعة</p>
            </div>
            <div class="feature">
                <i class="fas fa-glass"></i>
                <h3>تصميم زجاجي</h3>
                <p>تأثير glassmorphism عصري وجذاب</p>
            </div>
            <div class="feature">
                <i class="fas fa-mobile-alt"></i>
                <h3>متجاوب بالكامل</h3>
                <p>يعمل بشكل مثالي على جميع الأجهزة</p>
            </div>
        </div>
    </div>
</body>
</html>
