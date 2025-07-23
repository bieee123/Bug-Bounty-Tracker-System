<?php
// logout.php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page with success message
session_start();
$_SESSION['success_message'] = "You have been successfully logged out.";
header("Location: login.php");
exit();
?>