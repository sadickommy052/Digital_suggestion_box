<?php
session_start();

// Remove all session variables
$_SESSION = [];

// Destroy session
session_destroy();

// Redirect to login page outside the folder
header("Location:../login.php");
exit();
?>