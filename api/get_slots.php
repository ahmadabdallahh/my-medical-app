<?php
// Set content type to JSON
header('Content-Type: application/json');

// Include necessary files
require_once '../config.php';
require_once '../includes/functions.php';

// --- Input Validation ---
if (!isset($_GET['doctor_id']) || !isset($_GET['date'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Missing required parameters.']);
    exit();
}

$doctor_id = (int)$_GET['doctor_id'];
$date = $_GET['date'];

// Validate date format (Y-m-d)
$date_format = 'Y-m-d';
$d = DateTime::createFromFormat($date_format, $date);
if (!$d || $d->format($date_format) !== $date) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Invalid date format. Please use YYYY-MM-DD.']);
    exit();
}

if ($doctor_id <= 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Invalid doctor ID.']);
    exit();
}

// --- Fetch Available Slots ---
try {
    $available_slots = get_available_slots($doctor_id, $date);

    // --- Return JSON Response ---
    echo json_encode([
        'success' => true,
        'slots' => $available_slots
    ]);

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    error_log('API get_slots error: ' . $e->getMessage());
    echo json_encode(['error' => 'An internal server error occurred.']);
}
