<?php
require_once 'includes/functions.php';

$doctor_id = 37; // Existing doctor
$user_id = 2; // Existing user (patient)
$clinic_id = 1; // Existing clinic
$date = date('Y-m-d', strtotime('+1 day'));
$time = '10:00:00';

echo "Testing booking functions...\n";

// Test is_appointment_available
echo "Checking availability for Doctor $doctor_id on $date at $time...\n";
$is_available = is_appointment_available($doctor_id, $date, $time);
echo "Availability: " . ($is_available ? "Yes" : "No") . "\n";

if ($is_available) {
    // Test book_appointment
    echo "Booking appointment...\n";
    $appointment_id = book_appointment($user_id, $doctor_id, $clinic_id, $date, $time, 'Test booking');
    
    if ($appointment_id) {
        echo "Appointment booked successfully. ID: $appointment_id\n";
        
        // Test send_notification
        echo "Sending notification...\n";
        $notif_result = send_notification($user_id, 'Booking Confirmed', 'Your appointment has been booked.', 'appointment');
        echo "Notification sent: " . ($notif_result ? "Yes" : "No") . "\n";
    } else {
        echo "Failed to book appointment.\n";
    }
} else {
    echo "Slot not available, skipping booking test.\n";
}

// Test get_available_times
echo "Fetching available times for Doctor $doctor_id on $date...\n";
$times = get_available_times($doctor_id, $date);
echo "Available times count: " . count($times) . "\n";
if (!empty($times)) {
    echo "First available time: " . $times[0] . "\n";
}
?>
