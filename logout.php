<?php
session_start();

// Check if logout type is specified (student or admin)
$logout_type = $_GET['type'] ?? 'all';

if ($logout_type === 'student') {
    // Only clear student session variables
    unset($_SESSION['student_id']);
    unset($_SESSION['student_username']);
    header("Location: index.php");
} elseif ($logout_type === 'admin') {
    // Only clear admin session variables
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_username']);
    header("Location: admin_login.php");
} else {
    // Clear all session variables
    session_unset();
    session_destroy();
    header("Location: index.php");
}
exit;
?>