<?php
require_once 'config/database.php';
require_once 'includes/reminder_functions.php';

$db = new Database();
$conn = $db->getConnection();

$user_id = 2; // Assuming user ID 2 exists and is a patient

echo "Starting Verification for User ID: $user_id\n";

// 1. Add a reminder
echo "1. Adding a test reminder...\n";
$data = [
    'medication_name' => 'Test Aspirin',
    'dosage' => '100mg',
    'frequency' => 'daily',
    'time_of_day' => date('H:i'), // Current time to ensure it triggers
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d', strtotime('+1 week'))
];

if (add_medication_reminder($conn, $user_id, $data)) {
    echo "   [PASS] Reminder added successfully.\n";
} else {
    echo "   [FAIL] Failed to add reminder.\n";
    exit;
}

// 2. Verify in DB
echo "2. Verifying reminder in database...\n";
$reminders = get_patient_reminders($conn, $user_id);
$found = false;
$reminder_id = 0;
foreach ($reminders as $r) {
    if ($r['medication_name'] === 'Test Aspirin') {
        $found = true;
        $reminder_id = $r['id'];
        echo "   [PASS] Reminder found in DB (ID: $reminder_id).\n";
        break;
    }
}

if (!$found) {
    echo "   [FAIL] Reminder not found in DB.\n";
    exit;
}

// 3. Simulate Notifications
echo "3. Simulating notifications...\n";
// We need to make sure process_due_reminders picks it up.
// The logic in process_due_reminders is simple: if active, send it (once per day).
$count = process_due_reminders($conn);
echo "   Processed $count reminders.\n";

if ($count > 0) {
    echo "   [PASS] Simulation processed reminders.\n";
} else {
    echo "   [WARN] Simulation processed 0 reminders (maybe already sent today?).\n";
}

// 4. Check Logs
echo "4. Checking medication logs...\n";
$stmt = $conn->prepare("SELECT * FROM medication_logs WHERE reminder_id = ?");
$stmt->execute([$reminder_id]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($logs) > 0) {
    echo "   [PASS] Log entry found:\n";
    print_r($logs[0]);
} else {
    echo "   [FAIL] No log entry found.\n";
}

// 5. Cleanup
echo "5. Cleaning up...\n";
if (delete_reminder($conn, $reminder_id, $user_id)) {
    echo "   [PASS] Test reminder deleted.\n";
} else {
    echo "   [FAIL] Failed to delete test reminder.\n";
}

echo "Verification Complete.\n";
?>
