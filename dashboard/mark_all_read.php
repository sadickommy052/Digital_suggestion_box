<?php
session_start();
include("../config/db.php");

if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$update = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
$update->bind_param("i", $user_id);
$update->execute();
$update->close();

// Go back to previous page
header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>