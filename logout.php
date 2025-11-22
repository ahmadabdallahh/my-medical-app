<?php
session_start();
require_once 'includes/functions.php';

// Clear all session variables
session_unset();
session_destroy();

// Redirect to home page
header('Location: /medical-app-test/index.php');
exit();
?>
