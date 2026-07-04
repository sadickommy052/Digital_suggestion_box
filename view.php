<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

include(__DIR__ . "/config/db.php");

if(!isset($conn)){
    die("Database connection failed");
}

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

$stmt = $conn->prepare("
SELECT full_name,email,role,profile_picture,created_at
FROM users
WHERE user_id=?
LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 0){
    die("User not found");
}

$user = $result->fetch_assoc();
$stmt->close();

$full_name = $user['full_name'] ?? 'User';
$email = $user['email'] ?? '';
$role = $user['role'] ?? 'user';
$profile_picture = $user['profile_picture'] ?? '';
$created_at = $user['created_at'] ?? '';

$img = !empty($profile_picture)
    ? "/Digital_suggestion_box/" . $profile_picture
    : "";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

body{
    margin:0;
    font-family:Segoe UI, Arial;
    background:#f4f6f9;
}

.container{
    margin-left:220px;
    padding:25px;
}

.card{
    max-width:850px;
    margin:auto;
    margin-top:100px;
    background:#fff;
    border-radius:10px;
    border:1px solid #e5e7eb;
    box-shadow:0 2px 10px rgba(0,0,0,0.08);
    overflow:hidden;
}

.header{
    background:#111827;
    color:white;
    text-align:center;
    padding:30px;
}

.profile-img{
    width:110px;
    height:110px;
    border-radius:50%;
    object-fit:cover;
    border:3px solid #1e3a8a;
}

.profile-letter{
    width:110px;
    height:110px;
    border-radius:50%;
    background:#1e3a8a;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:40px;
    font-weight:bold;
    margin:auto;
}

.body{
    padding:20px;
}

/* 🔥 IMPROVED GRID */
.info{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:15px;
}

/* 🔥 CLEAN BOX */
.box{
    background:#f9fafb;
    padding:14px;
    border-radius:10px;
    border:1px solid #e5e7eb;
}

/* 🔥 LABEL STYLE */
.box label{
    display:block;
    font-size:12px;
    font-weight:600;
    color:#6b7280;
    margin-bottom:6px;
    text-transform:uppercase;
    letter-spacing:0.5px;
}

/* 🔥 VALUE STYLE */
.box span{
    font-size:14px;
    font-weight:500;
    color:#111827;
}

/* ACTIONS */
.actions{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:12px;
    margin-top:20px;
}

.btn{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    padding:10px;
    border-radius:8px;
    text-decoration:none;
    color:white;
    font-weight:600;
}

.btn-blue{background:#111827;}
.btn-purple{background:#111827;}
.btn-red{background:#dc2626;}

@media(max-width:768px){
    .container{margin-left:0;}
    .info{grid-template-columns:1fr;}
    .actions{grid-template-columns:1fr;}
}

</style>
</head>

<body>

<?php include(__DIR__."/sider/sider.php"); ?>
<?php include(__DIR__."/toper/toper.php"); ?>

<div class="container">

<div class="card">

<div class="header">

<?php if(!empty($img)){ ?>
    <img src="<?=$img?>" class="profile-img">
<?php }else{ ?>
    <div class="profile-letter">
        <?=strtoupper(substr($full_name,0,1))?>
    </div>
<?php } ?>

<h2><?=htmlspecialchars($full_name)?></h2>
<p><?=ucwords(str_replace("_"," ",$role))?></p>

</div>

<div class="body">

<div class="info">

<div class="box">
<label for="fullname">Full Name</label>
<span id="fullname"><?=htmlspecialchars($full_name)?></span>
</div>

<div class="box">
<label for="email">Email</label>
<span id="email"><?=htmlspecialchars($email)?></span>
</div>

<div class="box">
<label for="role">Role</label>
<span id="role"><?=ucwords($role)?></span>
</div>

<div class="box">
<label for="member">Member Since</label>
<span id="member">
<?= !empty($created_at) ? date("d M Y", strtotime($created_at)) : "N/A" ?>
</span>
</div>

</div>

</div>

</div>

</div>

</body>
</html>