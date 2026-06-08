<?php
$host = "localhost";
$user = "Amigo";
$password = "Amigo@1234";
$database = "db_box";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>