<?php
session_start();
include("config/db.php");

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("
        SELECT user_id, full_name, password, role, status 
        FROM users 
        WHERE email = ?
        LIMIT 1
    ");

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {

        $user = $result->fetch_assoc();

        if ($user['status'] !== 'active') {

            $error = "❌ Your account is inactive. Contact admin.";

        } elseif (password_verify($password, $user['password'])) {

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];

            switch ($user['role']) {

                case 'admin':
                    header("Location: admin/admin_dashboard.php");
                    exit();

                case 'suggestion_manager':
                    header("Location: manager/manager_dashboard.php");
                    exit();

                default:
                    header("Location: dashboard/suggester_dashboard.php");
                    exit();
            }

        } else {
            $error = "❌ Wrong password!";
        }

    } else {
        $error = "❌ Account not found!";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login | Digital Suggestion Box</title>

<style>

/* ===== BACKGROUND ===== */
body{
    margin:0;
    font-family:Segoe UI;
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background: linear-gradient(135deg, #0f172a, #1e293b);
}

/* ===== LOGIN BOX ===== */
.login-box{
    width:380px;
    background:white;
    padding:35px;
    border-radius:16px;
    box-shadow:0 10px 30px rgba(0,0,0,0.3);
    animation: fadeIn 0.6s ease-in-out;
}

/* TITLE */
.login-box h2{
    text-align:center;
    margin-bottom:20px;
    color:#1e293b;
}

/* INPUTS */
input{
    width:100%;
    padding:12px;
    margin:10px 0;
    border:1px solid #e2e8f0;
    border-radius:10px;
    outline:none;
    transition:0.3s;
}

input:focus{
    border-color:#2563eb;
    box-shadow:0 0 8px rgba(37,99,235,0.2);
}

/* BUTTON */
button{
    width:100%;
    padding:12px;
    background:#2563eb;
    color:white;
    border:none;
    border-radius:10px;
    font-weight:bold;
    cursor:pointer;
    transition:0.3s;
}

button:hover{
    background:#1d4ed8;
}

/* ERROR */
.error{
    background:#fee2e2;
    color:#991b1b;
    padding:10px;
    border-radius:8px;
    text-align:center;
    margin-bottom:10px;
    font-size:14px;
}

/* ANIMATION */
@keyframes fadeIn{
    from{opacity:0; transform:translateY(20px);}
    to{opacity:1; transform:translateY(0);}
}

/* SMALL SCREEN */
@media(max-width:500px){
    .login-box{
        width:90%;
    }
}

</style>
</head>

<body>

<div class="login-box">

    <h2>🔐 System Login</h2>

    <?php if($error != "") { ?>
        <div class="error"><?= $error ?></div>
    <?php } ?>

    <form method="POST">

        <input type="email" name="email" placeholder="Enter Email" required>

        <input type="password" name="password" placeholder="Enter Password" required>

        <button type="submit">Login</button>

    </form>

</div>

</body>
</html>