<?php
session_start();
include("config/db.php");

if($_SERVER["REQUEST_METHOD"]=="POST"){

    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if($password != $confirm_password){
        $error = "Passwords do not match!";
    } else {

        // check email exists (UPDATED TABLE)
        $stmt = $conn->prepare("
            SELECT user_id 
            FROM users 
            WHERE email = ?
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows > 0){
            $error = "Email already exists!";
        } else {

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // DEFAULT ROLE = suggester
            $role = "suggester";

            // DEFAULT STATUS = active
            $status = "active";

            $stmt = $conn->prepare("
                INSERT INTO users
                (full_name, email, password, role, status, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");

            $stmt->bind_param(
                "sssss",
                $full_name,
                $email,
                $hashed_password,
                $role,
                $status
            );

            if($stmt->execute()){
                header("Location: login.php");
                exit();
            } else {
                $error = "Registration failed!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>

<style>

body{
    margin:0;
    font-family:Segoe UI;
    background:#f4f7fc;
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
}

.container{
    width:420px;
    background:#ffffff;
    padding:30px;
    border-radius:15px;
    box-shadow:0 8px 25px rgba(0,0,0,0.08);
    border:1px solid #e5e7eb;
}

h2{
    text-align:center;
    color:#111827;
    margin-bottom:20px;
}

input{
    width:100%;
    padding:12px;
    margin:10px 0;
    border-radius:8px;
    border:1px solid #d1d5db;
    background:white;
    box-sizing:border-box;
}

input:focus{
    outline:none;
    border-color:#1f4b99;
    box-shadow:0 0 0 3px rgba(31,75,153,0.1);
}

button{
    width:100%;
    padding:12px;
    background:#1f4b99;
    color:white;
    border:none;
    border-radius:8px;
    cursor:pointer;
    font-weight:600;
}

button:hover{
    background:#163a77;
}

.error{
    background:#fef2f2;
    padding:10px;
    color:#dc2626;
    border:1px solid #fecaca;
    border-radius:8px;
    text-align:center;
    margin-bottom:10px;
}

</style>
</head>

<body>

<div class="container">

<h2>Create Account</h2>

<?php if(!empty($error)) echo "<div class='error'>$error</div>"; ?>

<form method="POST">

<input type="text" name="full_name" placeholder="Full Name" required>
<input type="email" name="email" placeholder="Email" required>
<input type="password" name="password" placeholder="Password" required>
<input type="password" name="confirm_password" placeholder="Confirm Password" required>

<button type="submit">Register</button>

</form>

</div>

</body>
</html>