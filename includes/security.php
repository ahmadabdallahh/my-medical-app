<?php
/**
 * ملف الأمان الشامل
 * Comprehensive Security Functions
 */

class SecurityManager {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * تنظيف المدخلات بشكل شامل
     * Comprehensive input sanitization
     */
    public function sanitize_input($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitize_input'], $data);
        }

        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        $data = strip_tags($data);
        $data = preg_replace('/[<>]/', '', $data);

        return $data;
    }

    /**
     * التحقق من CSRF token
     * CSRF token validation
     */
    public function validate_csrf_token($token) {
        if (!isset($_SESSION['csrf_token']) || !isset($token)) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * إنشاء CSRF token
     * Generate CSRF token
     */
    public function generate_csrf_token() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * التحقق من معدل الطلبات (Rate Limiting)
     * Rate limiting
     */
    public function check_rate_limit($identifier, $max_attempts = 5, $time_window = 300) {
        $key = 'rate_limit_' . md5($identifier);

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
        }

        $current_time = time();
        $time_elapsed = $current_time - $_SESSION[$key]['first_attempt'];

        if ($time_elapsed > $time_window) {
            $_SESSION[$key] = ['count' => 0, 'first_attempt' => $current_time];
        }

        if ($_SESSION[$key]['count'] >= $max_attempts) {
            return false;
        }

        $_SESSION[$key]['count']++;
        return true;
    }

    /**
     * التحقق من صحة البريد الإلكتروني
     * Enhanced email validation
     */
    public function validate_email($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // التحقق من النطاقات المؤقتة
        $blocked_domains = ['tempmail.com', '10minutemail.com', 'guerrillamail.com'];
        $domain = substr(strrchr($email, "@"), 1);

        if (in_array(strtolower($domain), $blocked_domains)) {
            return false;
        }

        return true;
    }

    /**
     * التحقق من قوة كلمة المرور
     * Enhanced password validation
     */
    public function validate_password($password) {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'كلمة المرور يجب أن تكون 8 أحرف على الأقل';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'يجب أن تحتوي على حرف كبير واحد على الأقل';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'يجب أن تحتوي على حرف صغير واحد على الأقل';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'يجب أن تحتوي على رقم واحد على الأقل';
        }

        if (!preg_match('/[@$!%*?&]/', $password)) {
            $errors[] = 'يجب أن تحتوي على رمز خاص واحد على الأقل';
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }

    /**
     * تسجيل محاولات تسجيل الدخول
     * Log login attempts
     */
    public function log_login_attempt($email, $ip_address, $success = false) {
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("INSERT INTO login_attempts (email, ip_address, success, attempt_time) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$email, $ip_address, $success ? 1 : 0]);
        } catch (Exception $e) {
            error_log("Login attempt logging failed: " . $e->getMessage());
        }
    }

    /**
     * التحقق من IP address
     * IP address validation
     */
    public function validate_ip_address($ip) {
        return filter_var($ip, FILTER_VALIDATE_IP);
    }

    /**
     * إنشاء رمز عشوائي آمن
     * Generate secure random token
     */
    public function generate_secure_token($length = 32) {
        return bin2hex(random_bytes($length));
    }

    /**
     * التحقق من صحة رقم الهاتف
     * Enhanced phone validation
     */
    public function validate_phone($phone) {
        // إزالة المسافات والرموز
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // التحقق من التنسيق المصري
        if (preg_match('/^(01)[0-9]{9}$/', $phone)) {
            return true;
        }

        // التحقق من التنسيق الدولي
        if (preg_match('/^\+[0-9]{10,15}$/', $phone)) {
            return true;
        }

        return false;
    }

    /**
     * منع XSS attacks
     * XSS prevention
     */
    public function prevent_xss($data) {
        if (is_array($data)) {
            return array_map([$this, 'prevent_xss'], $data);
        }

        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }

    /**
     * التحقق من صحة التاريخ
     * Date validation
     */
    public function validate_date($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
}

// دالة مساعدة للحصول على رمز CSRF
function get_csrf_token() {
    $security = new SecurityManager();
    return $security->generate_csrf_token();
}

// دالة مساعدة للتحقق من صحة المدخلات
function validate_and_sanitize($data, $rules = []) {
    $security = new SecurityManager();
    $errors = [];

    foreach ($rules as $field => $rule) {
        $value = $data[$field] ?? '';

        if ($rule['required'] && empty($value)) {
            $errors[$field] = $rule['required_message'] ?? 'هذا الحقل مطلوب';
            continue;
        }

        if ($rule['type'] === 'email' && !$security->validate_email($value)) {
            $errors[$field] = $rule['message'] ?? 'البريد الإلكتروني غير صحيح';
        }

        if ($rule['type'] === 'phone' && !$security->validate_phone($value)) {
            $errors[$field] = $rule['message'] ?? 'رقم الهاتف غير صحيح';
        }

        if ($rule['type'] === 'date' && !$security->validate_date($value)) {
            $errors[$field] = $rule['message'] ?? 'التاريخ غير صحيح';
        }
    }

    return ['valid' => empty($errors), 'errors' => $errors];
}
