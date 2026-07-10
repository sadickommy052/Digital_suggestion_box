<?php
session_start();
include("../config/db.php");
include("../config/functions.php"); // ← IMEONGEZWA

// =====================
// REKODI LOGOUT ACTIVITY
// =====================
if(isset($_SESSION['user_id'])){
    logActivity(
        $_SESSION['user_id'], 
        $_SESSION['full_name'] ?? 'User', 
        'User Logout', 
        'User logged out successfully'
    );
}

// Remove all session variables
$_SESSION = [];

// Destroy session
session_destroy();

// Redirect to login page outside the folder
header("Location: ../login.php");
exit();
?>