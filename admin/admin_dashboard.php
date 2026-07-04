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
    margin:0;
    font-family:Segoe UI, sans-serif;
    background:#f4f7fc;
    color:#111827;
}

/* ================= CONTENT ================= */
.content{
    margin-left:200px;
    padding:80px;
}

/* ================= HEADER ================= */
.header{
    background:#111827;
    color:#fff;
    padding:50px;
    border-radius:12px;
    margin-bottom:20px;
}

.header h3{
    margin:0;
    font-size:20px;
}

.header p{
    margin-top:5px;
    color:#cbd5e1;
}

/* ================= GRID ================= */
.grid{
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));
    gap:15px;
}

/* ================= CARD ================= */
.card{
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:12px;
    padding:20px;
    text-align:center;
    box-shadow:0 6px 15px rgba(0,0,0,0.05);
    transition:0.2s;
}

.card:hover{
    transform:translateY(-3px);
}

/* ICON */
.card i{
    font-size:22px;
    color:#111827;
    margin-bottom:10px;
}

/* TITLE */
.card h6{
    margin:5px 0;
    color:#6b7280;
    font-size:13px;
}

/* NUMBER */
.card h3{
    margin:0;
    font-size:22px;
    color:#111827;
}

/* ================= RESPONSIVE ================= */
@media(max-width:900px){
    .content{
        margin-left:0;
        padding:15px;
    }
}

</style>
</head>

<body>

<?php include("../sider/sider.php"); ?>
<?php include("../toper/toper.php"); ?>

<div class="content">

<div class="header">



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