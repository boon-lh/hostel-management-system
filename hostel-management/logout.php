<?php
// Common logout script for all user roles
session_start();

// Clear all session variables
$_SESSION = array();

// Invalidate the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Clear any other cookies if they exist
setcookie('remember_me', '', time() - 3600, '/');

// Determine the correct redirect path
$redirect = 'index.php';
if (strpos($_SERVER['HTTP_REFERER'], '/admin/') !== false) {
    $redirect = 'admin/login.php';
} elseif (strpos($_SERVER['HTTP_REFERER'], '/student/') !== false) {
    $redirect = 'student/login.php';
}

// Redirect to the appropriate login page
header("Location: " . $redirect);
exit();
?>