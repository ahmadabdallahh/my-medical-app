<?php
// includes/reminder_functions.php

/**
 * Add a new medication reminder
 */
function add_medication_reminder($conn, $user_id, $data) {
    try {
        $sql = "INSERT INTO medication_reminders (user_id, medication_name, dosage, frequency, time_of_day, start_date, end_date, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([
            $user_id,
            $data['medication_name'],
            $data['dosage'],
            $data['frequency'],
            $data['time_of_day'],
            $data['start_date'],
            $data['end_date']
        ]);
    } catch (PDOException $e) {
        error_log("Add reminder error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all reminders for a patient
 */
function get_patient_reminders($conn, $user_id) {
    try {
        $sql = "SELECT * FROM medication_reminders WHERE user_id = ? ORDER BY created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get reminders error: " . $e->getMessage());
        return [];
    }
}

/**
 * Update reminder status (active/inactive)
 */
function update_reminder_status($conn, $reminder_id, $user_id, $is_active) {
    try {
        $sql = "UPDATE medication_reminders SET is_active = ? WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([$is_active, $reminder_id, $user_id]);
    } catch (PDOException $e) {
        error_log("Update reminder status error: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a reminder
 */
function delete_reminder($conn, $reminder_id, $user_id) {
    try {
        $sql = "DELETE FROM medication_reminders WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([$reminder_id, $user_id]);
    } catch (PDOException $e) {
        error_log("Delete reminder error: " . $e->getMessage());
        return false;
    }
}

/**
 * Simulate sending a notification
 */
function send_notification_simulation($conn, $user_id, $message, $channels = ['sms', 'email', 'push']) {
    // In a real app, this would integrate with Twilio, SendGrid, Firebase, etc.
    // Here we just log it to a file and the database.

    $log_file = __DIR__ . '/../logs/notifications.log';
    $timestamp = date('Y-m-d H:i:s');
    
    foreach ($channels as $channel) {
        $log_entry = "[$timestamp] [$channel] To User ID $user_id: $message\n";
        file_put_contents($log_file, $log_entry, FILE_APPEND);
        
        // Log to DB if it's related to a specific reminder (this function is generic though)
        // For generic notifications, we might use a different table or just file logs.
    }
    
    return true;
}

/**
 * Process due reminders (Simulation)
 * This would typically be run by a cron job.
 * For demo purposes, we can call this when the user visits the dashboard or a specific test page.
 */
function process_due_reminders($conn) {
    // 1. Find active reminders that match current time (simplified logic)
    // For demo: just find any active reminder and simulate sending if not sent today
    
    try {
        $sql = "SELECT r.*, u.full_name, u.email, u.phone 
                FROM medication_reminders r
                JOIN users u ON r.user_id = u.id
                WHERE r.is_active = 1";
        $stmt = $conn->query($sql);
        $reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $count = 0;
        foreach ($reminders as $reminder) {
            // Check if we already sent a log for this reminder today
            $today = date('Y-m-d');
            $checkSql = "SELECT COUNT(*) FROM medication_logs 
                         WHERE reminder_id = ? AND DATE(sent_at) = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->execute([$reminder['id'], $today]);
            
            if ($checkStmt->fetchColumn() == 0) {
                // Send notification
                $message = "تذكير: حان موعد أخذ الدواء " . $reminder['medication_name'] . " (" . $reminder['dosage'] . ")";
                
                // Log to DB
                $logSql = "INSERT INTO medication_logs (reminder_id, status, channel) VALUES (?, 'sent', 'push')";
                $logStmt = $conn->prepare($logSql);
                $logStmt->execute([$reminder['id']]);
                
                send_notification_simulation($conn, $reminder['user_id'], $message, ['push']);
                $count++;
            }
        }
        return $count;
    } catch (PDOException $e) {
        error_log("Process reminders error: " . $e->getMessage());
        return 0;
    }
}
?>
