<?php
/**
 * نظام منع تعارض الحجوزات
 * Booking Conflict Prevention System
 */

class BookingValidator {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * التحقق من توافر الموعد قبل الحجز
     * Check appointment availability before booking
     */
    public function check_availability($doctor_id, $date, $time, $exclude_booking_id = null) {
        try {
            $conn = $this->db->getConnection();

            // التحقق من عدم وجود حجز في نفس الوقت
            $sql = "SELECT COUNT(*) FROM appointments
                     WHERE doctor_id = ?
                     AND appointment_date = ?
                     AND appointment_time = ?
                     AND status IN ('confirmed', 'pending')
                     AND appointment_date >= CURDATE()";

            if ($exclude_booking_id) {
                $sql .= " AND id != ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$doctor_id, $date, $time, $exclude_booking_id]);
            } else {
                $stmt = $conn->prepare($sql);
                $stmt->execute([$doctor_id, $date, $time]);
            }

            $count = $stmt->fetchColumn();

            return $count == 0;

        } catch (Exception $e) {
            error_log("Availability check failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * منع الحجز المزدوج باستخدام قفل قاعدة البيانات
     * Prevent double booking using database locking
     */
    public function book_appointment($data) {
        try {
            $conn = $this->db->getConnection();

            // بدء معاملة قاعدة البيانات
            $conn->beginTransaction();

            // التحقق النهائي من التوافر
            if (!$this->check_availability($data['doctor_id'], $data['date'], $data['time'])) {
                $conn->rollBack();
                return ['success' => false, 'message' => 'عذراً، هذا الموعد تم حجزه للتو'];
            }

            // إنشاء الحجز
            $stmt = $conn->prepare("INSERT INTO appointments
                                  (user_id, doctor_id, appointment_date, appointment_time, status, created_at)
                                  VALUES (?, ?, ?, ?, 'pending', NOW())");

            $stmt->execute([
                $data['user_id'],
                $data['doctor_id'],
                $data['date'],
                $data['time']
            ]);

            $booking_id = $conn->lastInsertId();

            // إنهاء المعاملة
            $conn->commit();

            return ['success' => true, 'booking_id' => $booking_id];

        } catch (Exception $e) {
            $conn->rollBack();
            error_log("Booking failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ أثناء الحجز'];
        }
    }

    /**
     * التحقق من صلاحية الموعد (عدم حجز موعد في الماضي)
     * Validate appointment date/time
     */
    public function validate_appointment_time($date, $time) {
        $appointment_datetime = new DateTime($date . ' ' . $time);
        $now = new DateTime();

        // إضافة 30 دقيقة كحد أدنى للحجز المسبق
        $min_booking_time = (clone $now)->add(new DateInterval('PT30M'));

        if ($appointment_datetime < $min_booking_time) {
            return ['valid' => false, 'message' => 'يجب حجز الموعد قبل 30 دقيقة على الأقل'];
        }

        // التحقق من ساعات العمل (8 صباحاً - 10 مساءً)
        $hour = (int)$appointment_datetime->format('H');
        if ($hour < 8 || $hour >= 22) {
            return ['valid' => false, 'message' => 'ساعات العمل من 8 صباحاً حتى 10 مساءً'];
        }

        return ['valid' => true];
    }

    /**
     * منع حجز أكثر من موعد في نفس الوقت للمريض الواحد
     * Prevent patient double booking
     */
    public function check_patient_availability($user_id, $date, $time) {
        try {
            $conn = $this->db->getConnection();

            $stmt = $conn->prepare("SELECT COUNT(*) FROM appointments
                                 WHERE user_id = ?
                                 AND appointment_date = ?
                                 AND appointment_time = ?
                                 AND status IN ('confirmed', 'pending')");

            $stmt->execute([$user_id, $date, $time]);
            $count = $stmt->fetchColumn();

            return $count == 0;

        } catch (Exception $e) {
            error_log("Patient availability check failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * التحقق من أقصى عدد من الحجوزات للطبيب في اليوم
     * Check doctor daily booking limit
     */
    public function check_doctor_daily_limit($doctor_id, $date, $max_bookings = 20) {
        try {
            $conn = $this->db->getConnection();

            $stmt = $conn->prepare("SELECT COUNT(*) FROM appointments
                                 WHERE doctor_id = ?
                                 AND appointment_date = ?
                                 AND status IN ('confirmed', 'pending')");

            $stmt->execute([$doctor_id, $date]);
            $count = $stmt->fetchColumn();

            return $count < $max_bookings;

        } catch (Exception $e) {
            error_log("Doctor daily limit check failed: " . $e->getMessage());
            return false;
        }
    }
}

// دالة مساعدة للتحقق من الحجز
function validate_booking($data) {
    $validator = new BookingValidator();

    $checks = [
        'time_validation' => $validator->validate_appointment_time($data['date'], $data['time']),
        'availability' => $validator->check_availability($data['doctor_id'], $data['date'], $data['time']),
        'patient_availability' => $validator->check_patient_availability($data['user_id'], $data['date'], $data['time']),
        'doctor_limit' => $validator->check_doctor_daily_limit($data['doctor_id'], $data['date'])
    ];

    foreach ($checks as $check => $result) {
        if (is_array($result) && !$result['valid']) {
            return $result;
        }
        if ($result === false) {
            return ['valid' => false, 'message' => 'التحقق من ' . $check . ' فشل'];
        }
    }

    return ['valid' => true];
}
