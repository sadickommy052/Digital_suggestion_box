<?php
session_start();
include("../config/db.php");

if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$notification_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($notification_id > 0){
    $update = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?");
    $update->bind_param("ii", $notification_id, $user_id);
    $update->execute();
    $update->close();
}

// Go back to previous page
header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>