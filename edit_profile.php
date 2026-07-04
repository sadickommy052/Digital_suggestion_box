<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

include(__DIR__ . "/config/db.php");

if(!isset($conn)){
    die("Database connection failed. Check db.php");
}

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* =========================
   SAFE USER FETCH (NO get_result)
========================= */

$stmt = $conn->prepare("
SELECT full_name, email, profile_picture
FROM users
WHERE user_id=?
LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

/* bind results safely */
$stmt->bind_result($full_name, $email, $profile_picture);

if(!$stmt->fetch()){
    die("User not found");
}

$stmt->close();

/* =========================
   UPDATE PROFILE
========================= */

$message = "";

if(isset($_POST['update'])){

    $new_name  = trim($_POST['full_name']);
    $new_email = trim($_POST['email']);

    $new_profile = $profile_picture;

    /* IMAGE UPLOAD */
    if(!empty($_FILES['profile_picture']['name'])){

        $upload_dir = "uploads/";

        if(!is_dir($upload_dir)){
            mkdir($upload_dir, 0777, true);
        }

        $file_name = time() . "_" . basename($_FILES['profile_picture']['name']);
        $target_path = $upload_dir . $file_name;

        if(move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_path)){
            $new_profile = $target_path;
        }
    }

    /* PASSWORD CHANGE (OLD + NEW) */
    if(!empty($_POST['old_password']) && !empty($_POST['new_password'])){

        /* CHECK OLD PASSWORD */
        $stmtCheck = $conn->prepare("SELECT password FROM users WHERE user_id=?");
        $stmtCheck->bind_param("i", $user_id);
        $stmtCheck->execute();
        $stmtCheck->bind_result($db_password);
        $stmtCheck->fetch();
        $stmtCheck->close();

        if(!password_verify($_POST['old_password'], $db_password)){
            $message = "Old password is incorrect!";
        }else{

            $hashed = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
            UPDATE users 
            SET full_name=?, email=?, profile_picture=?, password=?
            WHERE user_id=?
            ");

            $stmt->bind_param("ssssi",
                $new_name,
                $new_email,
                $new_profile,
                $hashed,
                $user_id
            );
        }

    }else{

        $stmt = $conn->prepare("
        UPDATE users 
        SET full_name=?, email=?, profile_picture=?
        WHERE user_id=?
        ");

        $stmt->bind_param("sssi",
            $new_name,
            $new_email,
            $new_profile,
            $user_id
        );
    }

    if(isset($stmt) && $stmt->execute()){
        $message = "Profile updated successfully!";
    }

    if(isset($stmt)){
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Profile</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

body{
margin:0;
font-family:Segoe UI, Arial;
background:#f4f6f9;
}

.container{
margin-left:250px;
padding:100px;
}

.card{
max-width:650px;
margin:auto;
background:white;
border-radius:10px;
box-shadow:0 2px 10px rgba(0,0,0,0.08);
overflow:hidden;
}

/* HEADER */
.header{
background:#111827;
color:white;
text-align:center;
padding:25px;
}

.header h2{
margin:0;
}

/* FORM */
form{
    display:flex;
    flex-direction:column;
    gap:15px;
    margin-top:15px;
}

/* each input block */
form > div{
    display:flex;
    flex-direction:column;
    gap:6px;
}

/* labels */
label{
    font-size:13px;
    font-weight:600;
    color:#374151;
}

/* inputs */
input{
    padding:12px;
    border:1px solid #e5e7eb;
    border-radius:8px;
    outline:none;
    font-size:14px;
    transition:0.2s;
}

/* focus effect */
input:focus{
    border-color:#1e3a8a;
    box-shadow:0 0 0 2px rgba(30,58,138,0.15);
}

/* layout improvement (2 columns on big screens) */
@media(min-width:768px){
    form{
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:15px;
    }

    /* full width fields if needed */
    form div:nth-child(1),
    form div:nth-child(2){
        grid-column:span 1;
    }
}

/* PROFILE PREVIEW */
.preview{
text-align:center;
margin-bottom:10px;
}

.preview img{
width:100px;
height:100px;
border-radius:50%;
object-fit:cover;
border:3px solid #1e3a8a;
}

.preview .letter{
width:100px;
height:100px;
border-radius:50%;
background:#1e3a8a;
display:flex;
align-items:center;
justify-content:center;
color:white;
font-size:40px;
margin:auto;
}

/* BUTTON */
.btn{
background:#1e3a8a;
color:white;
padding:12px;
border:none;
border-radius:8px;
cursor:pointer;
font-weight:bold;
}

.btn:hover{
opacity:0.9;
}

.message{
text-align:center;
color:green;
font-weight:bold;
}

@media(max-width:768px){
.container{margin-left:0;}
}

</style>
</head>

<body>

<?php include(__DIR__."/sider/sider.php"); ?>
<?php include(__DIR__."/toper/toper.php"); ?>

<div class="container">

<div class="card">

<div class="header">
<h2>Edit Profile</h2>
</div>

<?php if($message){ ?>
<p class="message"><?=$message?></p>
<?php } ?>

<div class="form">

<div class="preview">

<?php if(!empty($profile_picture) && file_exists(__DIR__."/".$profile_picture)){ ?>
    <img src="<?=$profile_picture?>">
<?php }else{ ?>
    <div class="letter">
        <?=strtoupper(substr($full_name,0,1))?>
    </div>
<?php } ?>

</div>

<form method="POST" enctype="multipart/form-data">
<div>
<label>Full Name</label>
<input type="text" name="full_name" value="<?=htmlspecialchars($full_name)?>" required>
</div>
<div>
<label>Email</label>
<input type="email" name="email" value="<?=htmlspecialchars($email)?>" required>
</div>
<div>
<label>Old Password</label>
<input type="password" name="old_password" placeholder="Enter old password">
</div>
<div>
<label>New Password</label>
<input type="password" name="new_password" placeholder="Enter new password">
</div>
<div>
<label>Profile Picture</label>
<input type="file" name="profile_picture">
</div>

<button type="submit" name="update" class="btn">
<i class="fas fa-save"></i> Update Profile
</button>

</form>

</div>

</div>

</div>

</body>
</html>