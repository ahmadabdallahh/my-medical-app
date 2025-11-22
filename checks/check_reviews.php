<?php
require_once 'includes/functions.php';

$doctor_id = 222; // Using a doctor ID that has reviews in the dump
echo "Testing get_doctor_reviews for doctor ID: $doctor_id\n";

$reviews = get_doctor_reviews($doctor_id);

if (empty($reviews)) {
    echo "No reviews found or error occurred.\n";
} else {
    echo "Reviews found: " . count($reviews) . "\n";
    foreach ($reviews as $review) {
        echo "User: " . $review['user_name'] . ", Rating: " . $review['rating'] . ", Comment: " . $review['review'] . "\n";
    }
}
?>
