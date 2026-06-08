<?php
session_start();
include("../config/db.php");

// =====================
// AUTH CHECK
// =====================
if(
!isset($_SESSION['user_id']) ||
$_SESSION['role']!='admin'
){
    header("Location: ../login.php");
    exit();
}

$full_name = $_SESSION['full_name'];


// =====================
// SAFE COUNT FUNCTION
// =====================

function getCount($conn,$sql){

$result = $conn->query($sql);

if($result && $row=$result->fetch_assoc()){
return $row['total'];
}

return 0;

}


// =====================
// STATISTICS
// =====================

// USERS (UPDATED TABLE)
$totalUsers=getCount(
$conn,
"SELECT COUNT(*) as total FROM users"
);

$totalSuggesters=getCount(
$conn,
"SELECT COUNT(*) as total FROM users WHERE role='suggester'"
);

$totalManagers=getCount(
$conn,
"SELECT COUNT(*) as total FROM users WHERE role='suggestion_manager'"
);

$totalAdmins=getCount(
$conn,
"SELECT COUNT(*) as total FROM users WHERE role='admin'"
);

// SUGGESTIONS
$totalSuggestions=getCount(
$conn,
"SELECT COUNT(*) as total FROM suggestions"
);

$pending=getCount(
$conn,
"SELECT COUNT(*) as total FROM suggestions WHERE status='pending'"
);

$approved=getCount(
$conn,
"SELECT COUNT(*) as total FROM suggestions WHERE status='approved'"
);

$rejected=getCount(
$conn,
"SELECT COUNT(*) as total FROM suggestions WHERE status='rejected'"
);

?>

<!DOCTYPE html>
<html>

<head>

<title>Admin Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>

body{
background:#f5f7fb;
font-family:Segoe UI;
}

.content{
margin-left:240px;
padding:25px;
}

.card{
border:none;
border-radius:15px;
padding:20px;
box-shadow:0 5px 15px rgba(0,0,0,.08);
transition:.3s;
}

.card:hover{
transform:translateY(-5px);
}

.card i{
font-size:25px;
margin-bottom:10px;
}

h3{
font-weight:bold;
}

.header{
background:white;
padding:20px;
border-radius:15px;
margin-bottom:20px;
box-shadow:0 5px 15px rgba(0,0,0,.08);
}

</style>

</head>

<body>

<?php include("../sider/sider.php"); ?>

<div class="content">

<div class="header">

<h3>
<i class="fas fa-user-shield"></i>
Admin Dashboard
</h3>

<p>
Welcome,
<b><?php echo $full_name; ?></b>
</p>

</div>


<div class="row">

<div class="col-md-3 mb-3">

<div class="card">

<i class="fas fa-users"></i>

<h6>Total Users</h6>

<h3>
<?php echo $totalUsers; ?>
</h3>

</div>

</div>


<div class="col-md-3 mb-3">

<div class="card">

<i class="fas fa-user"></i>

<h6>Suggesters</h6>

<h3>
<?php echo $totalSuggesters; ?>
</h3>

</div>

</div>


<div class="col-md-3 mb-3">

<div class="card">

<i class="fas fa-user-tie"></i>

<h6>Managers</h6>

<h3>
<?php echo $totalManagers; ?>
</h3>

</div>

</div>


<div class="col-md-3 mb-3">

<div class="card">

<i class="fas fa-user-shield"></i>

<h6>Admins</h6>

<h3>
<?php echo $totalAdmins; ?>
</h3>

</div>

</div>

</div>


<div class="row">

<div class="col-md-3 mb-3">

<div class="card">

<i class="fas fa-comment"></i>

<h6>Total Suggestions</h6>

<h3>
<?php echo $totalSuggestions; ?>
</h3>

</div>

</div>


<div class="col-md-3 mb-3">

<div class="card">

<i class="fas fa-clock"></i>

<h6>Pending</h6>

<h3>
<?php echo $pending; ?>
</h3>

</div>

</div>


<div class="col-md-3 mb-3">

<div class="card">

<i class="fas fa-check-circle"></i>

<h6>Approved</h6>

<h3>
<?php echo $approved; ?>
</h3>

</div>

</div>


<div class="col-md-3 mb-3">

<div class="card">

<i class="fas fa-times-circle"></i>

<h6>Rejected</h6>

<h3>
<?php echo $rejected; ?>
</h3>

</div>

</div>

</div>

</div>

</body>

</html>