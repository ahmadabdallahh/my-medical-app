<?php
// إعدادات PHPMailer - استخدم بيانات خادم SMTP الخاص بك
// يمكنك استخدام حساب Gmail للاختبار، ولكن تأكد من تفعيل " الوصول للتطبيقات الأقل أمانًا "
// أو استخدام "كلمات مرور التطبيقات"

define('SMTP_HOST', 'smtp.gmail.com'); // مثال: smtp.gmail.com
define('SMTP_USERNAME', 'healthh.tech404@gmail.com');
define('SMTP_PASSWORD', 'rkaxfjgeyaiaxmpp');
define('SMTP_PORT', 587); // 587 لـ TLS، 465 لـ SSL
define('SMTP_SECURE', 'tls'); // 'tls' أو 'ssl'

define('MAIL_FROM_ADDRESS', 'healthh.tech404@gmail.com');
define('MAIL_FROM_NAME', 'Health Tech');
define('MAIL_IS_HTML', true); // إرسال الرسائل كـ HTML
