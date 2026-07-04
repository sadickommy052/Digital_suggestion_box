<?php
session_start();
include("config/db.php");

$error="";

if($_SERVER["REQUEST_METHOD"]=="POST"){

$email=trim($_POST['email']);
$password=$_POST['password'];

$stmt=$conn->prepare("
SELECT user_id,full_name,password,role,status,profile_picture
FROM users
WHERE email=?
LIMIT 1
");

$stmt->bind_param("s",$email);
$stmt->execute();

$result=$stmt->get_result();

if($result && $result->num_rows===1){

$user=$result->fetch_assoc();

if($user['status']!="active"){

$error="❌ Your account is inactive. Contact admin.";

}elseif(password_verify($password,$user['password'])){


session_regenerate_id(true);

$_SESSION['user_id']=$user['user_id'];
$_SESSION['full_name']=$user['full_name'];
$_SESSION['role']=$user['role'];
$_SESSION['profile_picture']=$user['profile_picture'];


if($user['role']=="admin"){

header("Location: admin/admin_dashboard.php");
exit();

}elseif($user['role']=="suggestion_manager"){

header("Location: manager/manager_dashboard.php");
exit();

}else{

header("Location: dashboard/suggester_dashboard.php");
exit();

}


}else{

$error="❌ Wrong password!";

}


}else{

$error="❌ Account not found!";

}

$stmt->close();

}

?>


<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<title>Login | Digital Suggestion Box</title>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>

body{
    margin:0;
    font-family:Segoe UI, sans-serif;
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background:#f4f7fc;
}

/* LOGIN BOX */
.login-box{
    width:380px;
    background:#ffffff;
    padding:35px;
    border-radius:14px;
    box-shadow:0 10px 25px rgba(0,0,0,0.08);
    border:1px solid #e5e7eb;
}

/* TITLE */
h2{
    text-align:center;
    color:#111827;
    margin-bottom:15px;
}

/* INPUT + BUTTON */
input,button{
    width:100%;
    padding:12px;
    margin:10px 0;
    box-sizing:border-box;
    border-radius:8px;
    font-size:14px;
}

/* INPUT STYLE */
input{
    border:1px solid #d1d5db;
    outline:none;
    transition:0.2s;
}

input:focus{
    border-color:#111827;
    box-shadow:0 0 0 3px rgba(17,24,39,0.15);
}

/* BUTTON (SIDEBAR COLOR) */
button{
    background:#111827;
    color:white;
    border:0;
    cursor:pointer;
    font-weight:bold;
    transition:0.2s;
}

button:hover{
    background:#1f2937;
}

/* ERROR BOX */
.error{
    background:#fee2e2;
    color:#991b1b;
    padding:10px;
    border-radius:8px;
    text-align:center;
    font-size:14px;
    border:1px solid #fecaca;
}

/* REGISTER AREA */
.register-area{
    text-align:center;
    margin-top:15px;
}

.register-link{
    color:#111827;
    font-weight:bold;
    text-decoration:none;
}

.register-link:hover{
    color:#2563eb;
}

</style>
</head>


<body>


<div class="login-box">


<h2>
<i class="fas fa-lock"></i> System Login
</h2>


<?php if($error!=""){ ?>

<div class="error">
<?php echo $error; ?>
</div>

<?php } ?>


<form method="POST">


<input 
type="email"
name="email"
placeholder="Enter Email"
required>


<input
type="password"
name="password"
placeholder="Enter Password"
required>


<button type="submit">
Login
</button>


<div class="register-area">

Don't have an account?

<a href="register.php" class="register-link">
Register here
</a>

</div>


</form>


</div>


</body>

</html>