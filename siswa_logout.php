<?php
session_start();

// Clear student session variables
unset($_SESSION['student_id']);
unset($_SESSION['student_username']);

// Redirect to login page
header("Location: siswa_login.php");
exit;
?>
