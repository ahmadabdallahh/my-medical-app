<?php
// includes/notification_functions.php

/**
 * Create a new notification for a user
 */
function create_notification($conn, $user_id, $title, $message, $type = 'system', $related_id = null, $action_url = null) {
    try {
        $sql = "INSERT INTO push_notifications (user_id, title, message, type, related_id, action_url, is_read, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 0, NOW())";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([$user_id, $title, $message, $type, $related_id, $action_url]);
    } catch (PDOException $e) {
        error_log("Create notification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user notifications
 */
function get_user_notifications($conn, $user_id, $limit = 10, $unread_only = false) {
    try {
        $sql = "SELECT * FROM push_notifications WHERE user_id = ?";
        if ($unread_only) {
            $sql .= " AND is_read = 0";
        }
        $sql .= " ORDER BY created_at DESC LIMIT ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get notifications error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get unread notification count
 */
function get_unread_count($conn, $user_id) {
    try {
        $sql = "SELECT COUNT(*) FROM push_notifications WHERE user_id = ? AND is_read = 0";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user_id]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Get unread count error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Mark notification as read
 */
function mark_notification_as_read($conn, $notification_id, $user_id) {
    try {
        $sql = "UPDATE push_notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([$notification_id, $user_id]);
    } catch (PDOException $e) {
        error_log("Mark notification as read error: " . $e->getMessage());
        return false;
    }
}

/**
 * Mark all notifications as read
 */
function mark_all_as_read($conn, $user_id) {
    try {
        $sql = "UPDATE push_notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([$user_id]);
    } catch (PDOException $e) {
        error_log("Mark all as read error: " . $e->getMessage());
        return false;
    }
}

/**
 * Create appointment status notification
 */
function notify_appointment_status($conn, $appointment_id, $status) {
    try {
        // Get appointment details
        $sql = "SELECT a.user_id, d.full_name as doctor_name, a.appointment_date, a.appointment_time
                FROM appointments a
                JOIN doctors d ON a.doctor_id = d.id
                WHERE a.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$appointment_id]);
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$appointment) {
            return false;
        }
        
        $user_id = $appointment['user_id'];
        $doctor_name = $appointment['doctor_name'];
        $date = date('Y-m-d', strtotime($appointment['appointment_date']));
        $time = date('H:i', strtotime($appointment['appointment_time']));
        
        if ($status === 'confirmed') {
            $title = 'تم قبول موعدك';
            $message = "تم قبول موعدك مع الدكتور $doctor_name بتاريخ $date الساعة $time";
        } elseif ($status === 'cancelled') {
            $title = 'تم رفض موعدك';
            $message = "تم رفض موعدك مع الدكتور $doctor_name. يرجى اختيار موعد آخر.";
        } else {
            return false;
        }
        
        $action_url = 'patient/appointments.php';
        
        return create_notification($conn, $user_id, $title, $message, 'appointment', $appointment_id, $action_url);
        
    } catch (PDOException $e) {
        error_log("Notify appointment status error: " . $e->getMessage());
        return false;
    }
}
?>
