<?php
session_start();
include("../config/db.php");

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
SELECT full_name,email,role,profile_picture,created_at
FROM users
WHERE user_id=?
LIMIT 1
");

$stmt->bind_param("i",$user_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0){
    die("User not found.");
}

$user = $result->fetch_assoc();
$stmt->close();
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
background:#eef3f9;
}

.content{
margin-left:250px;
padding:30px;
}

.profile-card{
max-width:900px;
margin:auto;
background:#fff;
border-radius:15px;
overflow:hidden;
box-shadow:0 8px 25px rgba(0,0,0,.08);
}

.header{
background:linear-gradient(135deg,#111827,#1e3a8a);
color:white;
text-align:center;
padding:40px;
}

.profile-img{
width:120px;
height:120px;
border-radius:50%;
object-fit:cover;
border:4px solid white;
}

.profile-letter{
width:120px;
height:120px;
border-radius:50%;
background:#2563eb;
display:flex;
align-items:center;
justify-content:center;
font-size:45px;
font-weight:bold;
margin:auto;
border:4px solid white;
}

.header h2{margin:10px 0 5px;}
.header p{color:#cbd5e1;}

.body{
padding:25px;
}

.grid{
display:grid;
grid-template-columns:1fr 1fr;
gap:15px;
}

.box{
background:#f9fafb;
padding:15px;
border-radius:10px;
}

.box label{
font-weight:600;
color:#374151;
display:block;
margin-bottom:5px;
}

.box span{
color:#111827;
}

.actions{
display:grid;
grid-template-columns:repeat(2,1fr);
gap:15px;
margin-top:25px;
}

.btn{
display:flex;
align-items:center;
justify-content:center;
gap:8px;
padding:12px;
border-radius:10px;
text-decoration:none;
color:white;
font-weight:600;
transition:.3s;
}

.btn-blue{background:#1e3a8a;}
.btn-purple{background:#7c3aed;}
.btn-green{background:#059669;}
.btn-red{background:#dc2626;}

.btn:hover{
opacity:0.85;
transform:scale(1.03);
}

@media(max-width:768px){
.content{margin-left:0;padding:15px;}
.grid{grid-template-columns:1fr;}
.actions{grid-template-columns:1fr;}
}

</style>
</head>

<body>

<?php include("../sider/sider.php"); ?>
<?php include("../toper/toper.php"); ?>

<div class="content">

<div class="profile-card">

<!-- HEADER -->
<div class="header">

<?php if(!empty($user['profile_picture']) && file_exists("../".$user['profile_picture'])){ ?>
    <img src="../<?=htmlspecialchars($user['profile_picture'])?>" class="profile-img">
<?php }else{ ?>
    <div class="profile-letter">
        <?=strtoupper(substr($user['full_name'],0,1));?>
    </div>
<?php } ?>

<h2><?=htmlspecialchars($user['full_name'])?></h2>
<p><?=ucwords(str_replace("_"," ",$user['role']))?></p>

</div>

<!-- BODY -->
<div class="body">

<div class="grid">

<div class="box">
<label>Full Name</label>
<span><?=htmlspecialchars($user['full_name'])?></span>
</div>

<div class="box">
<label>Email</label>
<span><?=htmlspecialchars($user['email'])?></span>
</div>

<div class="box">
<label>Role</label>
<span><?=ucwords($user['role'])?></span>
</div>

<div class="box">
<label>Member Since</label>
<span><?=date("d M Y",strtotime($user['created_at']))?></span>
</div>

</div>

<!-- ACTIONS -->
<div class="actions">

<a href="profile.php" class="btn btn-blue">
<i class="fas fa-user"></i> My Profile
</a>

<a href="edit_profile.php" class="btn btn-purple">
<i class="fas fa-cog"></i> Profile Settings
</a>

<a href="my_suggestions.php" class="btn btn-green">
<i class="fas fa-lightbulb"></i> My Suggestions
</a>

<a href="../logout.php" class="btn btn-red">
<i class="fas fa-sign-out-alt"></i> Logout
</a>

</div>

</div>

</div>

</div>

</body>
</html>