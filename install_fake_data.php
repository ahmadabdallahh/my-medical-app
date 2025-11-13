<?php
// ملف تثبيت البيانات الوهمية
// Fake Data Installation Script

require_once 'config/database.php';

echo "<h1>تثبيت البيانات الوهمية</h1>";
echo "<h2>Installing Fake Data</h2>";

$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    echo "<p style='color: red;'>خطأ في الاتصال بقاعدة البيانات</p>";
    exit();
}

try {
    // قراءة ملف البيانات الوهمية
    $sql_file = 'fake_data.sql';

    if (!file_exists($sql_file)) {
        echo "<p style='color: red;'>ملف البيانات الوهمية غير موجود</p>";
        exit();
    }

    $sql_content = file_get_contents($sql_file);

    // تقسيم الأوامر SQL
    $queries = explode(';', $sql_content);

    $success_count = 0;
    $error_count = 0;

    echo "<h3>بدء تثبيت البيانات...</h3>";

    foreach ($queries as $query) {
        $query = trim($query);

        if (empty($query) || strpos($query, '--') === 0) {
            continue; // تخطي التعليقات والأسطر الفارغة
        }

        try {
            $stmt = $conn->prepare($query);
            $result = $stmt->execute();

            if ($result) {
                $success_count++;
                echo "<p style='color: green;'>✅ تم تنفيذ الأمر بنجاح</p>";
            } else {
                $error_count++;
                echo "<p style='color: red;'>❌ فشل في تنفيذ الأمر</p>";
            }
        } catch (PDOException $e) {
            $error_count++;
            echo "<p style='color: red;'>❌ خطأ: " . $e->getMessage() . "</p>";
        }
    }

    echo "<h3>نتيجة التثبيت:</h3>";
    echo "<p style='color: green;'>✅ الأوامر الناجحة: $success_count</p>";
    echo "<p style='color: red;'>❌ الأوامر الفاشلة: $error_count</p>";

    if ($error_count == 0) {
        echo "<h3 style='color: green;'>🎉 تم تثبيت البيانات الوهمية بنجاح!</h3>";
        echo "<p>الآن يمكنك:</p>";
        echo "<ul>";
        echo "<li>عرض المستشفيات في صفحة <a href='hospitals.php'>المستشفيات</a></li>";
        echo "<li>البحث عن الأطباء في صفحة <a href='search.php'>البحث</a></li>";
        echo "<li>حجز المواعيد من صفحة <a href='book.php'>الحجز</a></li>";
        echo "</ul>";
    } else {
        echo "<h3 style='color: orange;'>⚠️ تم تثبيت بعض البيانات مع وجود أخطاء</h3>";
        echo "<p>يرجى مراجعة الأخطاء أعلاه</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>خطأ عام: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>معلومات البيانات المثبتة:</h3>";
echo "<ul>";
echo "<li>20 تخصص طبي</li>";
echo "<li>15 مستشفى</li>";
echo "<li>30 عيادة</li>";
echo "<li>46 طبيب</li>";
echo "<li>أوقات عمل للأطباء</li>";
echo "<li>مواعيد تجريبية</li>";
echo "</ul>";

echo "<p><strong>ملاحظة:</strong> يمكنك تشغيل هذا الملف مرة واحدة فقط لتجنب تكرار البيانات.</p>";
?>
