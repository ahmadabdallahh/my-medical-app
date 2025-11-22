<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ensure the config file is included to have access to get_db_connection()
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/error_handler.php';

// ====================================================================
// CONFIGURATION & CONSTANTS
// ====================================================================

// Define Base URL for asset linking if not already defined
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $script_name = str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
    define('BASE_URL', $protocol . $host . $script_name);
}

// ====================================================================
// DATABASE & CORE FUNCTIONS
// ====================================================================

require_once __DIR__ . '/../config/database.php';

/**
 * Redirect to a specific page.
 * @param string $url
 */
function redirect($url)
{
    // If the URL is relative, prepend BASE_URL
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        $url = BASE_URL . $url;
    }
    header("Location: {$url}");
    exit();
}

// ====================================================================
// USER & AUTHENTICATION FUNCTIONS
// ====================================================================

/**
 * Check if a user is logged in.
 * @return bool
 */
function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

/**
 * Get the full data record for the currently logged-in user.
 * @return array|false The user's data as an associative array, or false if not found.
 */
function get_logged_in_user()
{
    if (!is_logged_in()) {
        return false;
    }

    try {
        $db = new Database();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // If profile image is empty or file does not exist, set default avatar
            $profile_image_path = __DIR__ . '/../' . $user['profile_image'];
            if (empty($user['profile_image']) || !file_exists($profile_image_path)) {
                $user['profile_image'] = 'assets/images/default-avatar.png';
            }
        }

        return $user ? $user : null;

    } catch (Exception $e) {
        error_log("Get user error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check user role. Helper function for role checks.
 * @param string $role
 * @return bool
 */
function check_user_role($role)
{
    return is_logged_in() && isset($_SESSION['user_type']) && $_SESSION['user_type'] === $role;
}

/**
 * Check if the logged-in user is an admin.
 * @return bool
 */
function is_admin()
{
    return check_user_role('admin');
}

/**
 * Check if the logged-in user is a doctor.
 * @return bool
 */
function is_doctor()
{
    return check_user_role('doctor');
}

/**
 * Check if the logged-in user is a patient.
 * @return bool
 */
function is_patient()
{
    return check_user_role('patient');
}

/**
 * Check if the logged-in user is a hospital representative.
 * @return bool
 */
function is_hospital()
{
    return check_user_role('hospital');
}

/**
 * Log out the current user.
 */
function logout_user()
{
    session_unset();
    session_destroy();
    redirect('login.php');
}

// ====================================================================
// INPUT SANITIZATION & VALIDATION FUNCTIONS
// ====================================================================

/**
 * Validate email format.
 * @param string $email
 * @return bool
 */
function validate_email($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate password strength.
 * @param string $password
 * @return bool
 */
function validate_password($password)
{
    // Password must be at least 8 characters, with 1 uppercase, 1 lowercase, and 1 number.
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password);
}

/**
 * Hash a password.
 * @param string $password
 * @return string|false
 */
function hash_password($password)
{
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify a password against its hash.
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verify_password($password, $hash)
{
    return password_verify($password, $hash);
}

/**
 * Validate date format and check if date is valid.
 * @param string $date
 * @param string $format
 * @return bool
 */
function validate_date($date, $format = 'Y-m-d')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// ====================================================================
// OTHER APPLICATION-SPECIFIC FUNCTIONS
// ====================================================================

/**
 * Check for appointment conflicts.
 * @param int $doctor_id
 * @param string $appointment_date
 * @return bool
 */
function check_appointment_conflict($doctor_id, $appointment_date)
{
    try {
        $db = new Database();
        $conn = $db->getConnection();

        // Check for appointments within a 30-minute window of the requested time
        $stmt = $conn->prepare("SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date BETWEEN DATE_SUB(?, INTERVAL 29 MINUTE) AND DATE_ADD(?, INTERVAL 29 MINUTE)");
        $stmt->execute([$doctor_id, $appointment_date, $appointment_date]);

        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        // Log error or handle it gracefully
        return true; // Assume conflict on DB error to be safe
    }
}

function can_cancel_appointment($appointment_id, $user_id)
{
    try {
        $db = new Database();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("
            SELECT appointment_date, status FROM appointments
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$appointment_id, $user_id]);
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$appointment) {
            return false;
        }

        // Can't cancel completed or cancelled appointments
        if ($appointment['status'] === 'completed' || $appointment['status'] === 'cancelled') {
            return false;
        }

        // Can't cancel appointments that are less than 24 hours away
        $appointment_datetime = new DateTime($appointment['appointment_date']);
        $now = new DateTime();
        $interval = $now->diff($appointment_datetime);

        return $interval->days > 0 || ($interval->days == 0 && $interval->h > 24);

    } catch (PDOException $e) {
        return false;
    }
}

function cancel_appointment($appointment_id, $user_id)
{
    try {
        $db = new Database();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("
            UPDATE appointments
            SET status = 'cancelled', updated_at = NOW()
            WHERE id = ? AND user_id = ? AND status != 'completed'
        ");

        return $stmt->execute([$appointment_id, $user_id]);

    } catch (PDOException $e) {
        return false;
    }
}

// Note: Other functions like register_user, book_appointment, etc., would go here.
// They are removed for this fix to avoid clutter, assuming they exist elsewhere or are not causing re-declaration issues.

// Enhanced input sanitization
function sanitize_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Validate phone number with international format support
function validate_phone_enhanced($phone)
{
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);

    // Check if it's a valid Egyptian phone number
    if (preg_match('/^(01)[0-9]{9}$/', $phone)) {
        return true;
    }

    // Check if it's a valid international format
    if (preg_match('/^\+[0-9]{10,15}$/', $phone)) {
        return true;
    }

    return false;
}

// Enhanced email validation
function validate_email_enhanced($email)
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    // Check for common typos and disposable email domains
    $disposable_domains = ['tempmail.com', '10minutemail.com', 'guerrillamail.com'];
    $domain = substr(strrchr($email, "@"), 1);

    if (in_array(strtolower($domain), $disposable_domains)) {
        return false;
    }

    return true;
}

// Enhanced password validation
function validate_password_enhanced($password)
{
    // متطلبات بسيطة: فقط 6 أحرف على الأقل
    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل'];
    }

    return ['success' => true, 'message' => 'كلمة المرور صحيحة'];
}



// Validate appointment booking to prevent double booking
function validate_appointment_booking($conn, $doctor_id, $clinic_id, $date, $time, $user_id)
{

    try {
        // Check if slot is available
        $stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status != 'cancelled'");
        $stmt->execute([$doctor_id, $date, $time]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            return ['success' => false, 'message' => 'هذا الوقت محجوز بالفعل'];
        }

        // Check if user already has appointment at this time
        $stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE user_id = ? AND appointment_date = ? AND appointment_time = ? AND status != 'cancelled'");
        $stmt->execute([$user_id, $date, $time]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            return ['success' => false, 'message' => 'لديك موعد آخر في نفس الوقت'];
        }

        // Check if appointment is in the past
        $appointment_datetime = new DateTime($date . ' ' . $time);
        $now = new DateTime();

        if ($appointment_datetime < $now) {
            return ['success' => false, 'message' => 'لا يمكن حجز موعد في الماضي'];
        }

        return ['success' => true, 'message' => 'الموعد متاح'];

    } catch (PDOException $e) {
        if (function_exists('log_error')) {
            log_error('DATABASE_ERROR', 'Appointment validation failed: ' . $e->getMessage(), ['doctor_id' => $doctor_id, 'date' => $date, 'time' => $time]);
        }
        return ['success' => false, 'message' => 'حدث خطأ أثناء التحقق من الموعد'];
    }
}

// إنشاء رسالة تنبيه HTML
function create_alert($message, $type = 'info')
{
    $icons = [
        'success' => 'fas fa-check-circle',
        'error' => 'fas fa-exclamation-circle',
        'warning' => 'fas fa-exclamation-triangle',
        'info' => 'fas fa-info-circle'
    ];

    $icon = $icons[$type] ?? $icons['info'];

    return "
    <div class=\"message-{$type}\">
        <i class=\"{$icon}\"></i>
        <span>{$message}</span>
    </div>";
}

// التحقق من صلاحية المستخدم
function check_user_permission($required_type)
{
    if (!is_logged_in()) {
        return false;
    }

    $user_type = $_SESSION['user_type'] ?? '';

    // الأدمن لديه جميع الصلاحيات
    if ($user_type === 'admin') {
        return true;
    }

    return $user_type === $required_type;
}

// التحقق من انتهاء صلاحية الجلسة
function is_session_expired($timeout_minutes = 30)
{
    if (!isset($_SESSION['login_time'])) {
        return true;
    }

    $timeout_seconds = $timeout_minutes * 60;
    return (time() - $_SESSION['login_time']) > $timeout_seconds;
}

// تسجيل دخول المستخدم - محسن للأمان
function login_user($email, $password)
{
    // التحقق من محاولات تسجيل الدخول المتكررة
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $attempt_key = 'login_attempts_' . md5($ip_address);

    if (!isset($_SESSION[$attempt_key])) {
        $_SESSION[$attempt_key] = ['count' => 0, 'last_attempt' => time()];
    }

    // إعادة تعيين العداد بعد 15 دقيقة
    if (time() - $_SESSION[$attempt_key]['last_attempt'] > 900) {
        $_SESSION[$attempt_key] = ['count' => 0, 'last_attempt' => time()];
    }

    // منع بعد 5 محاولات فاشلة
    if ($_SESSION[$attempt_key]['count'] >= 5) {
        return ['success' => false, 'message' => 'تم حظرك مؤقتاً بسبب محاولات تسجيل دخول متكررة. حاول بعد 15 دقيقة.'];
    }

    try {
        $db = new Database();
        $conn = $db->getConnection();

        if (!$conn) {
            return ['success' => false, 'message' => 'خطأ في الاتصال بقاعدة البيانات'];
        }

        // التحقق من صحة البيانات
        if (!validate_email($email)) {
            $_SESSION[$attempt_key]['count']++;
            $_SESSION[$attempt_key]['last_attempt'] = time();
            return ['success' => false, 'message' => 'البريد الإلكتروني غير صحيح'];
        }

        if (empty($password)) {
            $_SESSION[$attempt_key]['count']++;
            $_SESSION[$attempt_key]['last_attempt'] = time();
            return ['success' => false, 'message' => 'كلمة المرور مطلوبة'];
        }

        // البحث عن المستخدم
        $stmt = $conn->prepare("SELECT id, full_name, email, password, user_type, is_active, failed_login_attempts, last_failed_login FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $_SESSION[$attempt_key]['count']++;
            $_SESSION[$attempt_key]['last_attempt'] = time();
            return ['success' => false, 'message' => 'البريد الإلكتروني غير موجود أو الحساب غير مفعل'];
        }

        // التحقق من محاولات تسجيل الدخول الفاشلة
        if (
            $user['failed_login_attempts'] >= 5 &&
            strtotime($user['last_failed_login']) > strtotime('-15 minutes')
        ) {
            return ['success' => false, 'message' => 'تم حظر الحساب مؤقتاً بسبب محاولات تسجيل دخول متكررة'];
        }

        if (!verify_password($password, $user['password'])) {
            // تحديث عدد المحاولات الفاشلة
            $update_stmt = $conn->prepare("UPDATE users SET failed_login_attempts = failed_login_attempts + 1, last_failed_login = NOW() WHERE id = ?");
            $update_stmt->execute([$user['id']]);

            $_SESSION[$attempt_key]['count']++;
            $_SESSION[$attempt_key]['last_attempt'] = time();
            return ['success' => false, 'message' => 'كلمة المرور غير صحيحة'];
        }

        // إعادة تعيين عدد المحاولات الفاشلة
        $update_stmt = $conn->prepare("UPDATE users SET failed_login_attempts = 0, last_login = NOW() WHERE id = ?");
        $update_stmt->execute([$user['id']]);

        // تسجيل الدخول بنجاح - إعادة توليد معرف الجلسة
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['login_time'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // تسجيل نشاط تسجيل الدخول
        $log_stmt = $conn->prepare("INSERT INTO login_logs (user_id, ip_address, user_agent, login_time) VALUES (?, ?, ?, NOW())");
        $log_stmt->execute([$user['id'], $_SESSION['ip_address'], $_SESSION['user_agent']]);

        // مسح محاولات تسجيل الدخول
        unset($_SESSION[$attempt_key]);

        return ['success' => true, 'message' => 'تم تسجيل الدخول بنجاح'];

    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return ['success' => false, 'message' => 'حدث خطأ أثناء تسجيل الدخول'];
    }
}

// تسجيل مستخدم جديد
function register_user($data)
{
    try {
        $db = new Database();
        $conn = $db->getConnection();

        if (!$conn) {
            return ['success' => false, 'message' => 'خطأ في الاتصال بقاعدة البيانات'];
        }

        // التحقق من صحة البيانات
        $validation_errors = [];

        if (empty($data['full_name']) || strlen($data['full_name']) < 2) {
            $validation_errors[] = 'الاسم الكامل مطلوب ويجب أن يكون حرفين على الأقل';
        }

        if (!validate_email($data['email'])) {
            $validation_errors[] = 'البريد الإلكتروني غير صحيح';
        }

        if (!validate_phone_enhanced($data['phone'])) {
            $validation_errors[] = 'رقم الهاتف غير صحيح';
        }

        $password_validation = validate_password_enhanced($data['password']);
        if (!$password_validation['success']) {
            $validation_errors = array_merge($validation_errors, $password_validation['messages']);
        }

        if ($data['password'] !== $data['confirm_password']) {
            $validation_errors[] = 'كلمة المرور غير متطابقة';
        }

        if (!validate_date($data['birth_date'])) {
            $validation_errors[] = 'تاريخ الميلاد غير صحيح';
        }

        if (!empty($validation_errors)) {
            return ['success' => false, 'message' => implode('<br>', $validation_errors)];
        }

        // التحقق من عدم وجود المستخدم مسبقاً
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
        $check_stmt->execute([$data['email'], $data['phone']]);

        if ($check_stmt->fetch()) {
            return ['success' => false, 'message' => 'البريد الإلكتروني أو رقم الهاتف موجود مسبقاً'];
        }

        // إنشاء المستخدم الجديد
        $hashed_password = hash_password($data['password']);

        $stmt = $conn->prepare("
            INSERT INTO users (full_name, email, phone, password, birth_date, gender, user_type, created_at, is_active)
            VALUES (?, ?, ?, ?, ?, ?, 'patient', NOW(), 1)
        ");

        $result = $stmt->execute([
            sanitize_input($data['full_name']),
            sanitize_input($data['email']),
            sanitize_input($data['phone']),
            $hashed_password,
            $data['birth_date'],
            $data['gender'] ?? 'male'
        ]);

        if ($result) {
            return ['success' => true, 'message' => 'تم إنشاء الحساب بنجاح'];
        } else {
            return ['success' => false, 'message' => 'حدث خطأ أثناء إنشاء الحساب'];
        }

    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'message' => 'حدث خطأ أثناء إنشاء الحساب'];
    }
}

// ====================================================================
// DOCTOR DASHBOARD HELPER FUNCTIONS
// ====================================================================

/**
 * Get detailed information for a single doctor.
 * @param int $doctor_id
 * @return array|false
 */
function get_doctor_details($doctor_id)
{
    try {
        $db = new Database();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("
            SELECT
                d.id, d.full_name, d.email, d.phone, d.bio, d.image, d.rating, d.specialty_id, d.clinic_id, d.is_active,
                d.consultation_fee,
                s.name as specialty_name,
                c.name as clinic_name,
                c.address as clinic_address
            FROM doctors d
            LEFT JOIN specialties s ON d.specialty_id = s.id
            LEFT JOIN clinics c ON d.clinic_id = c.id
            WHERE d.id = ?
        ");

        $stmt->execute([$doctor_id]);
        $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($doctor && (empty($doctor['image']) || !file_exists($doctor['image']))) {
            $doctor['image'] = 'assets/images/default-avatar.png';
        }

        return $doctor;

    } catch (Exception $e) {
        error_log("Get doctor details error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if a specific appointment slot is already taken.
 * @param int $doctor_id
 * @param string $appointment_date
 * @param string $appointment_time
 * @return bool
 */
function is_appointment_slot_taken($doctor_id, $appointment_date, $appointment_time)
{
    try {
        $db = new Database();
        $conn = $db->getConnection();

        // Check for appointments with 'confirmed' or 'pending' status
        $stmt = $conn->prepare("
            SELECT COUNT(*)
            FROM appointments
            WHERE doctor_id = ?
            AND appointment_date = ?
            AND appointment_time = ?
            AND status IN ('confirmed', 'pending')
        ");

        $stmt->execute([$doctor_id, $appointment_date, $appointment_time]);
        return $stmt->fetchColumn() > 0;

    } catch (Exception $e) {
        error_log("Check appointment slot error: " . $e->getMessage());
        return true; // Fail safe: assume it's taken if there's a DB error
    }
}

/**
 * Create a new appointment in the database.
 * @param int $patient_id
 * @param int $doctor_id
 * @param string $appointment_date
 * @param string $appointment_time
 * @param string $reason
 * @return int|false The new appointment ID or false on failure.
 */
function create_appointment($patient_id, $doctor_id, $appointment_date, $appointment_time, $reason)
{
    try {
        $db = new Database();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("
            INSERT INTO appointments (user_id, doctor_id, appointment_date, appointment_time, reason, status, created_at)
            VALUES (?, ?, ?, ?, ?, 'pending', NOW())
        ");

        if ($stmt->execute([$patient_id, $doctor_id, $appointment_date, $appointment_time, $reason])) {
            return $conn->lastInsertId();
        }

        return false;

    } catch (Exception $e) {
        error_log("Create appointment error: " . $e->getMessage());
        return false;
    }
}

// ====================================================================
// FORMATTING & LOCALIZATION FUNCTIONS
// ====================================================================

/**
 * Format a date into a readable Arabic format.
 * @param string $date_string (e.g., '2025-08-08')
 * @return string
 */
function format_date_arabic($date_string)
{
    if (empty($date_string))
        return '';
    $timestamp = strtotime($date_string);
    $months = [
        "يناير",
        "فبراير",
        "مارس",
        "أبريل",
        "مايو",
        "يونيو",
        "يوليو",
        "أغسطس",
        "سبتمبر",
        "أكتوبر",
        "نوفمبر",
        "ديسمبر"
    ];
    $day = date('d', $timestamp);
    $month = $months[date('n', $timestamp) - 1];
    $year = date('Y', $timestamp);
    return "$day $month $year";
}

/**
 * Format a time into a readable Arabic format.
 * @param string $time_string (e.g., '14:30:00')
 * @return string
 */
function format_time_arabic($time_string)
{
    if (empty($time_string))
        return '';
    $timestamp = strtotime($time_string);
    return date('h:i A', $timestamp);
}

/**
 * Translate appointment status to Arabic.
 * @param string $status The status from the database.
 * @return string The translated status.
 */
function translate_status($status)
{
    switch ($status) {
        case 'confirmed':
            return 'مؤكد';
        case 'pending':
            return 'قيد الانتظار';
        case 'cancelled':
            return 'ملغي';
        default:
            return ucfirst($status);
    }
}

/**
 * Get the Bootstrap badge class for a given status.
 * @param string $status The status from the database.
 * @return string The CSS class for the badge.
 */
function get_status_badge_class($status)
{
    switch ($status) {
        case 'confirmed':
            return 'success';
        case 'pending':
            return 'warning';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
}

// ====================================================================
// OTHER FUNCTIONS
// ====================================================================

// Function to search for doctors
function search_doctors($search_query, $specialty_id)
{
    global $conn;
    if (!$conn) {
        return []; // Return empty array if connection is not available
    }

    $sql = "SELECT
                d.id,
                d.full_name,
                d.image,
                d.rating,
                s.name as specialty_name,
                c.name as clinic_name,
                c.address as clinic_address
            FROM doctors d
            LEFT JOIN specialties s ON d.specialty_id = s.id
            LEFT JOIN clinics c ON d.clinic_id = c.id
            WHERE 1=1";

    $params = [];

    if (!empty($search_query)) {
        $sql .= " AND (d.full_name LIKE ? OR c.name LIKE ?)";
        $params[] = '%' . $search_query . '%';
        $params[] = '%' . $search_query . '%';
    }

    if (!empty($specialty_id)) {
        $sql .= " AND d.specialty_id = ?";
        $params[] = $specialty_id;
    }

    try {
        $sql .= " ORDER BY d.rating DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Search doctors error: ' . $e->getMessage());
        return []; // Return empty array on error
    }
}

// Get a single doctor by their ID.
//  * @param int $doctor_id The ID of the doctor.
//  * @return array|false The doctor's data or false if not found.

function get_doctor_by_id($doctor_id)
{
    global $conn;
    if (!$conn) {
        return false;
    }

    $sql = "SELECT
                d.id, d.user_id, d.full_name, d.email, d.phone, d.bio, d.image, d.rating, d.specialty_id, d.clinic_id, d.is_active,
                d.consultation_fee,
                s.name as specialty_name,
                c.name as clinic_name,
                c.address as clinic_address
            FROM doctors d
            LEFT JOIN specialties s ON d.specialty_id = s.id
            LEFT JOIN clinics c ON d.clinic_id = c.id
            WHERE d.id = ?";

    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute([$doctor_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log('Get doctor by ID error: ' . $e->getMessage());
        return false;
    }
}

function get_appointments_by_user_id($user_id)
{
    global $conn;
    if (!$conn) {
        return [];
    }

    $sql = "SELECT
                a.id, a.appointment_date, a.appointment_time, a.status,
                d.full_name as doctor_name,
                s.name as specialty_name,
                c.name as clinic_name,
                c.address as clinic_address
            FROM appointments a
            JOIN doctors d ON a.doctor_id = d.id
            JOIN specialties s ON d.specialty_id = s.id
            JOIN clinics c ON d.clinic_id = c.id
            WHERE a.user_id = ?
            ORDER BY a.appointment_date DESC, a.appointment_time DESC";

    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Get appointments by user ID error: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get all doctors ordered by rating (highest first)
 */
function get_all_doctors_by_rating($conn)
{
    try {
        $stmt = $conn->prepare("
            SELECT
                d.id,
                d.full_name,
                d.image,
                d.rating,
                d.consultation_fee,
                d.experience_years,
                d.education,
                s.name as specialty_name,
                c.name as clinic_name,
                c.address as clinic_address,
                h.name as hospital_name
            FROM doctors d
            LEFT JOIN specialties s ON d.specialty_id = s.id
            LEFT JOIN clinics c ON d.clinic_id = c.id
            LEFT JOIN hospitals h ON d.hospital_id = h.id
            ORDER BY d.rating DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get doctors error: " . $e->getMessage());
        return [];
    }
}

function get_all_doctors($limit = 10, $offset = 0)
{
    global $conn;
    if (!$conn) {
        return []; // Return empty array if connection is not available
    }

    $sql = "SELECT
                d.id,
                d.full_name,
                d.image,
                d.rating,
                s.name as specialty_name,
                c.name as clinic_name,
                c.address as clinic_address
            FROM doctors d
            LEFT JOIN specialties s ON d.specialty_id = s.id
            LEFT JOIN clinics c ON d.clinic_id = c.id
            WHERE d.is_active = 1
            ORDER BY d.full_name ASC
            LIMIT ? OFFSET ?";

    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Get all doctors error: ' . $e->getMessage());
        return []; // Return empty array on error
    }
}

// ==========================================================================
// User Authentication Functions
// ==========================================================================

// ... rest of the code remains the same ...

// Get available appointment slots for a doctor on a specific date.
/**
 * @param int $doctor_id The ID of the doctor.
 * @param string $date The date in 'Y-m-d' format.
 * @return array An array of available time slots in 'H:i:s' format.
 */
function get_available_slots($doctor_id, $date)
{
    global $conn;
    if (!$conn) {
        return [];
    }

    try {
        // Step 1: Define doctor's general availability (can be moved to a DB table later)
        $work_start_time = new DateTime('09:00:00');
        $work_end_time = new DateTime('17:00:00');
        $slot_duration = new DateInterval('PT30M'); // 30 minutes slots

        // Step 2: Get all booked appointments for the doctor on the given date
        $sql = "SELECT appointment_time FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status = 'confirmed'";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$doctor_id, $date]);
        $booked_slots_raw = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $booked_slots = [];
        foreach ($booked_slots_raw as $slot) {
            $booked_slots[] = (new DateTime($slot))->format('H:i');
        }

        // Step 3: Generate all possible slots for the day
        $all_slots = [];
        $current_slot_time = clone $work_start_time;

        while ($current_slot_time < $work_end_time) {
            $all_slots[] = $current_slot_time->format('H:i');
            $current_slot_time->add($slot_duration);
        }

        // Step 4: Filter out the booked slots
        $available_slots = array_diff($all_slots, $booked_slots);

        // Step 5: Filter out past slots for the current day
        $today_date = new DateTime('now', new DateTimeZone('UTC')); // Use your server's timezone or a specific one
        if ($date === $today_date->format('Y-m-d')) {
            $current_time = $today_date->format('H:i');
            $available_slots = array_filter($available_slots, function ($slot) use ($current_time) {
                return $slot > $current_time;
            });
        }

        return array_values($available_slots); // Return re-indexed array

    } catch (Exception $e) {
        error_log("Get available slots error: " . $e->getMessage());
        return []; // Return empty array on error
    }
}

// Alias function for backward compatibility
function get_available_times($doctor_id, $date)
{
    return get_available_slots($doctor_id, $date);
}

// ==========================================================================
// User Authentication Functions
// ==========================================================================

// ... rest of the code remains the same ...

/**
 * Get all appointments for a specific doctor.
 * @param int $doctor_id The ID of the doctor.
 * @return array An array of appointments.
 */
function get_appointments_by_doctor_id($doctor_id)
{
    global $conn;
    if (!$conn) {
        return [];
    }

    try {
        $sql = "SELECT
                    a.id,
                    a.appointment_date,
                    a.appointment_time,
                    a.status,
                    u.full_name as patient_name
                FROM appointments a
                JOIN users u ON a.user_id = u.id
                WHERE a.doctor_id = ?
                ORDER BY a.appointment_date DESC, a.appointment_time DESC";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$doctor_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Get appointments by doctor ID error: ' . $e->getMessage());
        return [];
    }
}

// ==============================================
// DASHBOARD STATS FUNCTIONS
// ==============================================

function get_total_count($pdo, $table)
{
    try {
        // Sanitize table name to prevent SQL injection
        $allowed_tables = ['users', 'doctors', 'appointments', 'hospitals', 'specialties', 'clinics'];
        if (!in_array($table, $allowed_tables)) {
            error_log("Invalid table name: {$table}");
            return 0;
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$table}`");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('Count query failed: ' . $e->getMessage());
        return 0;
    }
}

function get_user_type_count($pdo, $user_type)
{
    try {
        // Check both user_type and role columns
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (user_type = ? OR role = ?)");
        $stmt->execute([$user_type, $user_type]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('Get user type count error: ' . $e->getMessage());
        return 0;
    }
}

function get_doctor_appointment_count($pdo, $doctor_id, $status)
{
    try {
        $sql = "SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND status = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$doctor_id, $status]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

function get_doctor_patient_count($pdo, $doctor_id)
{
    try {
        // Counts distinct patients who have had appointments with this doctor
        $sql = "SELECT COUNT(DISTINCT user_id) FROM appointments WHERE doctor_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$doctor_id]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

function get_patient_appointment_count($pdo, $user_id, $status)
{
    try {
        $sql = "SELECT COUNT(*) FROM appointments WHERE user_id = ? AND status = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $status]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

function get_recent_patients($conn, $limit = 5)
{
    try {
        // Check both user_type and role columns for patient
        $sql = "SELECT id, full_name, email, created_at, is_active
                FROM users
                WHERE (user_type = 'patient' OR role = 'patient')
                ORDER BY created_at DESC
                LIMIT :limit";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Get recent patients error: ' . $e->getMessage());
        return [];
    }
}

/**
 * Calculate estimated revenue from completed appointments
 * @param PDO $conn
 * @return float
 */
function get_estimated_revenue($conn)
{
    try {
        // Try to get revenue from appointments with doctor consultation fees
        $sql = "SELECT COALESCE(SUM(d.consultation_fee), 0) as total_revenue
                FROM appointments a
                INNER JOIN doctors d ON a.doctor_id = d.id
                WHERE a.status IN ('confirmed', 'completed')";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && $result['total_revenue'] > 0) {
            return (float) $result['total_revenue'];
        }

        // Fallback: calculate from average consultation fee
        $sql = "SELECT AVG(consultation_fee) as avg_fee, COUNT(*) as appointment_count
                FROM appointments a
                INNER JOIN doctors d ON a.doctor_id = d.id
                WHERE a.status IN ('confirmed', 'completed')";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && $result['appointment_count'] > 0) {
            $avg_fee = (float) ($result['avg_fee'] ?? 0);
            $count = (int) $result['appointment_count'];
            return $avg_fee * $count;
        }

        // Last fallback: use average fee from all doctors
        $sql = "SELECT AVG(consultation_fee) as avg_fee FROM doctors WHERE consultation_fee > 0";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $avg_fee = (float) ($result['avg_fee'] ?? 50);
        $appointment_count = get_total_count($conn, 'appointments');

        return $avg_fee * $appointment_count;
    } catch (PDOException $e) {
        error_log('Get estimated revenue error: ' . $e->getMessage());
        // Fallback to simple calculation
        $appointment_count = get_total_count($conn, 'appointments');
        return $appointment_count * 50;
    }
}

function get_users_by_type($conn, $user_type)
{
    try {
        $stmt = $conn->prepare("SELECT id, full_name, email, created_at, status FROM users WHERE user_type = ?");
        $stmt->execute([$user_type]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Get users by type error: ' . $e->getMessage());
        return [];
    }
}

// Function to get all users from the database
function get_all_users($pdo)
{
    $sql = "SELECT id, full_name, email, role, created_at FROM users ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get all appointments for the admin dashboard
function get_all_appointments($conn)
{
    try {
        $sql = "SELECT
                    a.id,
                    a.appointment_date,
                    a.appointment_time,
                    a.status,
                    p.full_name AS patient_name,
                    d.full_name AS doctor_name
                FROM
                    appointments a
                JOIN
                    users p ON a.user_id = p.id
                JOIN
                    doctors d ON a.doctor_id = d.id
                ORDER BY
                    a.appointment_date DESC, a.appointment_time DESC";

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error in get_all_appointments: ' . $e->getMessage());
        return [];
    }
}

// Function to get a single user by their ID
function get_user_by_id($conn, $user_id)
{
    try {
        $stmt = $conn->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Get user by ID error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get user's full name by user ID
 * @param PDO $pdo
 * @param int $user_id
 * @return string
 */
function get_user_name($pdo, $user_id)
{
    try {
        $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['name'] : '';
    } catch (PDOException $e) {
        error_log('Get user name error: ' . $e->getMessage());
        return '';
    }
}

// Function to update user data
function update_user($conn, $user_id, $name, $email, $role)
{
    try {
        $sql = "UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([$name, $email, $role, $user_id]);
    } catch (PDOException $e) {
        error_log('Update user error: ' . $e->getMessage());
        return false;
    }
}

// Function to delete a user by their ID
function delete_user($conn, $user_id)
{
    try {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$user_id]);
    } catch (PDOException $e) {
        error_log('Delete user error: ' . $e->getMessage());
        return false;
    }
}

function update_appointment_status($pdo, $appointment_id, $status)
{
    $sql = "UPDATE appointments SET status = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$status, $appointment_id]);
}

function delete_appointment($pdo, $appointment_id)
{
    $sql = "DELETE FROM appointments WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$appointment_id]);
}

// Function to get all appointments with patient and doctor names
function get_all_appointments_with_patient_doctor($pdo)
{
    $sql = "SELECT
                a.id,
                a.appointment_date,
                a.appointment_time,
                a.status,
                p.full_name as patient_name,
                d.full_name as doctor_name
            FROM appointments a
            JOIN users p ON a.user_id = p.id
            JOIN doctors doc ON a.doctor_id = doc.id
            JOIN users d ON doc.user_id = d.id
            ORDER BY a.appointment_date DESC, a.appointment_time DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ====================================================================
// DOCTOR MANAGEMENT FUNCTIONS (Admin)
// ====================================================================

/**
 * Get all doctors with their specialization and account status.
 * @param PDO $pdo
 * @return array
 */
function get_all_doctors_with_details($pdo)
{
    $sql = "SELECT
                u.id as user_id,
                u.full_name,
                u.email,
                u.phone,
                u.created_at,
                d.id as doctor_id,
                d.specialty_id,
                s.name as specialization,
                d.is_active,
                d.rating,
                d.consultation_fee,
                CASE
                    WHEN d.id IS NULL THEN 'pending'
                    WHEN d.is_active = 1 THEN 'approved'
                    ELSE 'suspended'
                END as status
            FROM users u
            LEFT JOIN doctors d ON u.id = d.user_id
            LEFT JOIN specialties s ON d.specialty_id = s.id
            WHERE u.user_type = 'doctor' OR u.role = 'doctor'
            ORDER BY d.is_active DESC, u.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Update the status of a doctor's account (e.g., pending, approved, suspended).
 * @param PDO $pdo
 * @param int $doctor_id
 * @param string $status
 * @return bool
 */
function update_doctor_account_status($conn, $user_id, $new_status)
{
    try {
        // Map status to is_active value
        $is_active = 1; // default to active
        if ($new_status === 'suspended') {
            $is_active = 0;
        } elseif ($new_status === 'pending') {
            // For pending, we might want to keep is_active as 0 or handle differently
            $is_active = 0;
        }

        // Check if a doctor profile exists for the user_id
        $stmt_check = $conn->prepare("SELECT id FROM doctors WHERE user_id = ?");
        $stmt_check->execute([$user_id]);
        $doctor_profile = $stmt_check->fetch();

        if ($doctor_profile) {
            // If profile exists, update its is_active status
            $doctor_id = $doctor_profile['id'];
            $stmt_update = $conn->prepare("UPDATE doctors SET is_active = ? WHERE id = ?");
            return $stmt_update->execute([$is_active, $doctor_id]);
        } elseif ($new_status === 'approved') {
            // If no profile exists AND we are approving, create one with default values
            $stmt_create = $conn->prepare("INSERT INTO doctors (user_id, full_name, is_active, specialty_id)
                                          SELECT ?, full_name, 1, NULL FROM users WHERE id = ?");
            return $stmt_create->execute([$user_id, $user_id]);
        }
        // If no profile exists and we are not approving (e.g. setting to pending), there is nothing to do.
        // This case can be considered a success.
        return true;
    } catch (PDOException $e) {
        error_log("DATABASE ERROR: " . $e->getMessage());
        return false;
    }
}

/**
 * Deletes a doctor and their corresponding user account.
 * @param PDO $pdo
 * @param int $user_id
 * @return bool
 */
function delete_doctor_by_user_id($pdo, $user_id)
{
    $pdo->beginTransaction();
    try {
        // First, delete from the doctors table
        $sql_doctors = "DELETE FROM doctors WHERE user_id = ?";
        $stmt_doctors = $pdo->prepare($sql_doctors);
        $stmt_doctors->execute([$user_id]);

        // Then, delete from the users table
        $sql_users = "DELETE FROM users WHERE id = ?";
        $stmt_users = $pdo->prepare($sql_users);
        $stmt_users->execute([$user_id]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        // You might want to log the error message: $e->getMessage()
        return false;
    }
}

// ====================================================================
// FLASH MESSAGE HELPER FUNCTIONS
// ====================================================================

/**
 * Sets a flash message to be displayed on the next page load.
 * @param string $message The message to display.
 * @param string $type The type of message (success, error, info).
 */
function set_flash_message($message, $type = 'success')
{
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Displays the flash message if one is set, then clears it.
 */
function display_flash_message()
{
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message']['message'];
        $type = $_SESSION['flash_message']['type'];

        $color_classes = [
            'success' => 'bg-green-100 border-green-400 text-green-700',
            'error' => 'bg-red-100 border-red-400 text-red-700',
            'info' => 'bg-blue-100 border-blue-400 text-blue-700'
        ];

        $class = $color_classes[$type] ?? $color_classes['info'];

        echo "<div class='border px-4 py-3 rounded-lg relative mb-4 {$class}' role='alert'>";
        echo "<span class='block sm:inline'>" . htmlspecialchars($message) . "</span>";
        echo "</div>";

        unset($_SESSION['flash_message']);
    }
}

// ====================================================================
// CSRF PROTECTION HELPER FUNCTIONS
// ====================================================================

/**
 * Generates and stores a CSRF token in the session.
 * @return string The generated token.
 */
function generate_csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates the submitted CSRF token.
 * @param string $token The token from the form submission.
 * @return bool True if valid, false otherwise.
 */
function validate_csrf_token($token)
{
    if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        // Token is valid, unset it to prevent reuse
        unset($_SESSION['csrf_token']);
        return true;
    }
    return false;
}

// ====================================================================
// USER & AUTHENTICATION HELPER FUNCTIONS
// ====================================================================

/**
 * Fetches a doctor's profile by their user_id.
 * @param PDO $pdo
 * @param int $user_id
 * @return array|false
 */
function get_doctor_by_user_id($pdo, $user_id)
{
    try {
        $sql = "SELECT * FROM doctors WHERE user_id = ? LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // In a real app, you'd log this error.
        error_log('Error fetching doctor by user_id: ' . $e->getMessage());
        return false;
    }
}

// ====================================================================
// DOCTOR DASHBOARD HELPER FUNCTIONS
// ====================================================================

//<editor-fold desc="Doctor Dashboard Functions">
function get_doctor_dashboard_stats($pdo, $doctor_user_id)
{
    $stats = [
        'today_appointments' => 0,
        'upcoming_appointments' => 0,
        'total_patients' => 0
    ];

    try {
        // Get doctor_id from user_id
        $stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
        $stmt->execute([$doctor_user_id]);
        $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$doctor)
            return $stats;
        $doctor_id = $doctor['id'];

        // Today's appointments
        $today = date('Y-m-d');
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status = 'confirmed'");
        $stmt->execute([$doctor_id, $today]);
        $stats['today_appointments'] = $stmt->fetchColumn();

        // Upcoming appointments
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date >= ? AND status = 'confirmed'");
        $stmt->execute([$doctor_id, $today]);
        $stats['upcoming_appointments'] = $stmt->fetchColumn();

        // Total unique patients
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM appointments WHERE doctor_id = ?");
        $stmt->execute([$doctor_id]);
        $stats['total_patients'] = $stmt->fetchColumn();

    } catch (PDOException $e) {
        // Log error or handle it
        error_log($e->getMessage());
    }

    return $stats;
}

function get_doctor_upcoming_appointments($pdo, $doctor_user_id, $limit = 5)
{
    try {
        // Get doctor_id from user_id
        $stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
        $stmt->execute([$doctor_user_id]);
        $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$doctor)
            return [];
        $doctor_id = $doctor['id'];

        $today = date('Y-m-d');
        $stmt = $pdo->prepare("
            SELECT a.appointment_date, a.appointment_time, u.full_name as patient_name
            FROM appointments a
            JOIN users u ON a.user_id = u.id
            WHERE a.doctor_id = ? AND a.appointment_date >= ? AND a.status = 'confirmed'
            ORDER BY a.appointment_date, a.appointment_time
            LIMIT ?
        ");
        $stmt->execute([$doctor_id, $today, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return [];
    }
}

function get_doctor_schedule($doctor_id)
{
    try {
        $db = new Database();
        $conn = $db->getConnection();

        // doctor_availability table uses user_id as doctor_id
        // So we need to join with doctors table to match the doctor_id (PK) passed to this function
        $stmt = $conn->prepare("
            SELECT da.* 
            FROM doctor_availability da
            JOIN doctors d ON da.doctor_id = d.user_id
            WHERE d.id = ? AND da.is_active = 1
            ORDER BY FIELD(da.day_of_week, 'saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday')
        ");
        $stmt->execute([$doctor_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get doctor schedule error: " . $e->getMessage());
        return [];
    }
}
//</editor-fold>

// ====================================================================
// FRONTEND/PUBLIC HELPER FUNCTIONS (Restored)
// ====================================================================

/**
 * Gets all active hospitals for the frontend.
 * @param PDO $pdo
 * @return array
 */
function get_all_hospitals($pdo)
{
    try {
        // Check if the hospitals table exists
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'hospitals'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            // Table doesn't exist, return empty array
            return [];
        }

        $stmt = $pdo->prepare("
            SELECT h.*, COUNT(d.id) as doctor_count
            FROM hospitals h
            LEFT JOIN doctors d ON h.id = d.hospital_id
            GROUP BY h.id
            ORDER BY h.rating DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get hospitals error: " . $e->getMessage());
        return [];
    }
}

/**
 * Resolve a display image for a hospital with graceful fallbacks.
 *
 * @param array $hospital
 * @return string
 */
function get_hospital_display_image(array $hospital): string
{
    $image = $hospital['image'] ?? '';

    if (!empty($image)) {
        if (preg_match('/^https?:\/\//', $image)) {
            return $image;
        }

        $normalized = ltrim($image, '/');
        $candidateDirectories = [
            '',
            'uploads/hospitals/',
            'assets/images/hospitals/',
            'assets/images/',
        ];

        foreach ($candidateDirectories as $directory) {
            $relativePath = $directory ? $directory . $normalized : $normalized;
            $absolutePath = __DIR__ . '/../' . $relativePath;
            if (file_exists($absolutePath)) {
                return $relativePath;
            }
        }
    }

    $name = $hospital['name'] ?? '';
    $default_image = get_default_hospital_image();
    $imageMap = [
        'مستشفى دمنهور العام' => 'https://images.pexels.com/photos/2324837/pexels-photo-2324837.jpeg?auto=compress&cs=tinysrgb&w=1200',
        'مستشفى المعادي' => 'https://images.pexels.com/photos/247786/pexels-photo-247786.jpeg?auto=compress&cs=tinysrgb&w=1200',
        'مستشفى مصر الجديدة' => 'https://images.pexels.com/photos/263402/pexels-photo-263402.jpeg?auto=compress&cs=tinysrgb&w=1200',
        'مستشفى الإسكندرية العام' => 'https://images.pexels.com/photos/208490/pexels-photo-208490.jpeg?auto=compress&cs=tinysrgb&w=1200',
        'مستشفى الجيزة التخصصي' => 'https://images.pexels.com/photos/40568/medical-appointment-doctor-healthcare-40568.jpeg?auto=compress&cs=tinysrgb&w=1200',
        'مستشفى الملك فهد' => 'https://images.pexels.com/photos/1139665/pexels-photo-1139665.jpeg?auto=compress&cs=tinysrgb&w=1200',
        'مركز الأمير سلطان الطبي' => 'https://images.pexels.com/photos/892246/pexels-photo-892246.jpeg?auto=compress&cs=tinysrgb&w=1200',
        'مستشفى الملك خالد' => 'https://images.pexels.com/photos/1250655/pexels-photo-1250655.jpeg?auto=compress&cs=tinysrgb&w=1200',
        'مستشفى الملك عبدالعزيز' => 'https://images.pexels.com/photos/236380/pexels-photo-236380.jpeg?auto=compress&cs=tinysrgb&w=1200',
        'مركز الملك فهد الطبي' => 'https://images.pexels.com/photos/356040/pexels-photo-356040.jpeg?auto=compress&cs=tinysrgb&w=1200',
    ];

    if ($name && isset($imageMap[$name])) {
        return $imageMap[$name];
    }

    if ($name) {
        $slug = preg_replace('/\s+/', '-', trim($name));
        if (!empty($slug)) {
            return $default_image;
        }
    }

    return $default_image;
}

/**
 * Default image used when no hospital-specific image is available.
 *
 * @return string
 */
function get_default_hospital_image(): string
{
    return 'https://images.pexels.com/photos/708852/pexels-photo-708852.jpeg?auto=compress&cs=tinysrgb&w=1200';
}

/**
 * Gets all active specialties for the frontend.
 * @param PDO $pdo
 * @return array
 */
function get_all_specialties($pdo)
{
    try {
        // Check if the specialties table exists
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'specialties'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            // Table doesn't exist, return empty array
            return [];
        }

        $stmt = $pdo->prepare("
            SELECT s.*, COUNT(d.id) as doctor_count
            FROM specialties s
            LEFT JOIN doctors d ON s.id = d.specialty_id
            WHERE s.is_active = 1 OR s.is_active IS NULL
            GROUP BY s.id
            ORDER BY s.name
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get specialties error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all appointments for a specific user.
 * @param PDO $pdo
 * @param int $user_id
 * @return array
 */
function get_appointment_details($pdo, $appointment_id)
{
    try {
        $stmt = $pdo->prepare("
            SELECT a.*,
                   d.full_name as doctor_name,
                   d.specialty_id,
                   d.rating,
                   d.total_ratings,
                   c.name as clinic_name,
                   c.phone as clinic_phone,
                   c.consultation_fee,
                   h.name as hospital_name,
                   s.name as specialty_name
            FROM appointments a
            LEFT JOIN doctors d ON a.doctor_id = d.id
            LEFT JOIN clinics c ON a.clinic_id = c.id
            LEFT JOIN hospitals h ON c.hospital_id = h.id
            LEFT JOIN specialties s ON d.specialty_id = s.id
            WHERE a.id = ?
        ");
        $stmt->execute([$appointment_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get appointment details error: " . $e->getMessage());
        return null;
    }
}

function get_user_appointments($pdo, $user_id)
{
    try {
        // Enhanced query with hospital and clinic information
        $stmt = $pdo->prepare("
            SELECT a.*,
                   d.full_name as doctor_name,
                   d.specialty_id,
                   c.name as clinic_name,
                   h.name as hospital_name
            FROM appointments a
            LEFT JOIN doctors d ON a.doctor_id = d.id
            LEFT JOIN clinics c ON a.clinic_id = c.id
            LEFT JOIN hospitals h ON c.hospital_id = h.id
            WHERE a.user_id = ?
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
        ");
        $stmt->execute([$user_id]);
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add specialty names
        foreach ($appointments as &$app) {
            if ($app['specialty_id']) {
                $spec_stmt = $pdo->prepare("SELECT name FROM specialties WHERE id = ?");
                $spec_stmt->execute([$app['specialty_id']]);
                $spec = $spec_stmt->fetch();
                $app['specialty_name'] = $spec ? $spec['name'] : 'غير محدد';
            } else {
                $app['specialty_name'] = 'غير محدد';
            }

            // Ensure hospital and clinic names are set
            $app['hospital_name'] = $app['hospital_name'] ?: 'غير محدد';
            $app['clinic_name'] = $app['clinic_name'] ?: 'غير محدد';
        }

        return $appointments;
    } catch (PDOException $e) {
        error_log("Get user appointments error: " . $e->getMessage());
        return [];
    }
}

// ====================================================================
// RATING AND REVIEW FUNCTIONS

function get_doctor_reviews($doctor_id, $limit = 5)
{
    try {
        $db = new Database();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("
            SELECT dr.*, u.full_name as user_name 
            FROM doctor_ratings dr
            JOIN users u ON dr.user_id = u.id
            WHERE dr.doctor_id = ?
            ORDER BY dr.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$doctor_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get doctor reviews error: " . $e->getMessage());
        return [];
    }
}

// ====================================================================
// RATING AND REVIEW FUNCTIONS

function get_doctor_ratings($pdo, $doctor_id, $limit = 10, $offset = 0)
{
    try {
        $stmt = $pdo->prepare("
            SELECT dr.*, u.full_name as user_name, u.created_at as user_created_at
            FROM doctor_ratings dr
            LEFT JOIN users u ON dr.user_id = u.id
            WHERE dr.doctor_id = ?
            ORDER BY dr.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$doctor_id, $limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get doctor ratings error: " . $e->getMessage());
        return [];
    }
}

function get_doctor_rating_stats($pdo, $doctor_id)
{
    try {
        $stmt = $pdo->prepare("
            SELECT
                COUNT(*) as total_ratings,
                AVG(rating) as average_rating,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
            FROM doctor_ratings
            WHERE doctor_id = ?
        ");
        $stmt->execute([$doctor_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get doctor rating stats error: " . $e->getMessage());
        return [
            'total_ratings' => 0,
            'average_rating' => 0,
            'five_star' => 0,
            'four_star' => 0,
            'three_star' => 0,
            'two_star' => 0,
            'one_star' => 0
        ];
    }
}

function can_user_rate_doctor($pdo, $user_id, $doctor_id)
{
    try {
        // Check if user has already rated
        $stmt = $pdo->prepare("SELECT id FROM doctor_ratings WHERE user_id = ? AND doctor_id = ?");
        $stmt->execute([$user_id, $doctor_id]);
        if ($stmt->fetch()) {
            return ['can_rate' => false, 'reason' => 'لقد قمت بتقييم هذا الدكتور من قبل'];
        }

        // Check if user has had appointments with this doctor
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM appointments
            WHERE user_id = ? AND doctor_id = ? AND status = 'completed'
        ");
        $stmt->execute([$user_id, $doctor_id]);
        $appointment_count = $stmt->fetch()['count'];

        if ($appointment_count == 0) {
            return ['can_rate' => false, 'reason' => 'يمكنك فقط تقييم الأطباء الذين حجزت معهم مواعيد مكتملة'];
        }

        return ['can_rate' => true, 'reason' => ''];
    } catch (PDOException $e) {
        error_log("Can user rate doctor error: " . $e->getMessage());
        return ['can_rate' => false, 'reason' => 'حدث خطأ في التحقق'];
    }
}

function submit_doctor_rating($pdo, $doctor_id, $user_id, $rating, $review = '', $is_anonymous = 0)
{
    try {
        // Validate rating
        if ($rating < 1 || $rating > 5) {
            return ['success' => false, 'message' => 'التقييم يجب أن يكون بين 1 و 5'];
        }

        // Check if user can rate
        $can_rate = can_user_rate_doctor($pdo, $user_id, $doctor_id);
        if (!$can_rate['can_rate']) {
            return ['success' => false, 'message' => $can_rate['reason']];
        }

        // Insert rating
        $stmt = $pdo->prepare("
            INSERT INTO doctor_ratings (doctor_id, user_id, rating, review, is_anonymous)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$doctor_id, $user_id, $rating, $review, $is_anonymous]);

        // Update doctor's calculated rating
        update_doctor_calculated_rating($pdo, $doctor_id);

        return ['success' => true, 'message' => 'تم إضافة التقييم بنجاح'];
    } catch (PDOException $e) {
        error_log("Submit doctor rating error: " . $e->getMessage());
        return ['success' => false, 'message' => 'حدث خطأ أثناء إضافة التقييم'];
    }
}

function update_doctor_calculated_rating($pdo, $doctor_id)
{
    try {
        $stmt = $pdo->prepare("
            UPDATE doctors d SET
                d.calculated_rating = (
                    SELECT COALESCE(AVG(rating), 0)
                    FROM doctor_ratings
                    WHERE doctor_id = ?
                ),
                d.total_ratings = (
                    SELECT COUNT(*)
                    FROM doctor_ratings
                    WHERE doctor_id = ?
                )
            WHERE d.user_id = ?
        ");
        $stmt->execute([$doctor_id, $doctor_id, $doctor_id]);
    } catch (PDOException $e) {
        error_log("Update doctor calculated rating error: " . $e->getMessage());
    }
}

function get_user_rating_for_doctor($pdo, $user_id, $doctor_id)
{
    try {
        $stmt = $pdo->prepare("
            SELECT rating, review, is_anonymous, created_at
            FROM doctor_ratings
            WHERE user_id = ? AND doctor_id = ?
        ");
        $stmt->execute([$user_id, $doctor_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get user rating for doctor error: " . $e->getMessage());
        return null;
    }
}

function get_status_arabic($status)
{
    $status_map = [
        'confirmed' => 'مؤكد',
        'pending' => 'في الانتظار',
        'cancelled' => 'ملغي',
        'completed' => 'مكتمل',
        'rescheduled' => 'تمت إعادة الجدولة'
    ];

    return $status_map[$status] ?? $status;
}


// ====================================================================
// BOOKING HELPER FUNCTIONS

function is_appointment_available($doctor_id, $date, $time)
{
    try {
        $db = new Database();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status != 'cancelled'");
        $stmt->execute([$doctor_id, $date, $time]);
        
        return !$stmt->fetch();
    } catch (PDOException $e) {
        error_log("Availability check error: " . $e->getMessage());
        return false;
    }
}

function book_appointment($user_id, $doctor_id, $clinic_id, $date, $time, $notes = '')
{
    try {
        $db = new Database();
        $conn = $db->getConnection();

        $sql = "INSERT INTO appointments (user_id, doctor_id, clinic_id, appointment_date, appointment_time, notes, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'confirmed', NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user_id, $doctor_id, $clinic_id, $date, $time, $notes]);

        return $conn->lastInsertId();
    } catch (PDOException $e) {
        error_log("Booking error: " . $e->getMessage());
        return false;
    }
}

function send_notification($user_id, $title, $message, $type = 'system')
{
    try {
        $db = new Database();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("INSERT INTO push_notifications (user_id, title, message, type, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $title, $message, $type]);

        return true;
    } catch (PDOException $e) {
        error_log("Notification error: " . $e->getMessage());
        return false;
    }
}

?>

