<?php
session_start();
include("../config/db.php");
include("../config/functions.php");


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
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
/* ================= NO RESET - USIATHIRI SIDER ================= */
body{
    margin:0;
    font-family:'Segoe UI',sans-serif;
    background:#f8fafc;
    color:#1e293b;
}

/* ================= CONTENT ================= */
.content{
    margin-left:250px;
    padding:30px;
    padding-top:100px;
    min-height:calc(100vh - 180px);
}

/* ================= HEADER ================= */
.header{
    background:#111827;
    color:#fff;
    padding:25px 30px;
    border-radius:12px;
    margin-bottom:25px;
}

.header h3{
    margin:0;
    font-size:20px;
    font-weight:600;
}

.header p{
    margin-top:5px;
    color:#cbd5e1;
    font-size:14px;
}

.header i{
    margin-right:10px;
}

/* ================= STATS CARDS ================= */
.stats-row{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:18px;
    margin-bottom:25px;
}

.stats-row-2{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:18px;
    margin-bottom:25px;
}

.stat-card{
    background:white;
    padding:20px;
    border-radius:12px;
    text-align:center;
    border:1px solid #e2e8f0;
    box-shadow:0 4px 12px rgba(0,0,0,0.05);
    transition:all 0.3s ease;
}

.stat-card:hover{
    transform:translateY(-4px);
    box-shadow:0 8px 25px rgba(0,0,0,0.1);
}

.stat-card i{
    font-size:28px;
    color:#111827;
    margin-bottom:8px;
    display:block;
}

.stat-card h6{
    margin:5px 0;
    color:#64748b;
    font-size:13px;
    font-weight:600;
    text-transform:uppercase;
    letter-spacing:0.5px;
}

.stat-card h3{
    margin:0;
    font-size:26px;
    font-weight:700;
    color:#111827;
}

/* ================= RESPONSIVE ================= */
@media(max-width:992px){
    .stats-row{
        grid-template-columns:repeat(2,1fr);
    }
    .stats-row-2{
        grid-template-columns:repeat(2,1fr);
    }
}

@media(max-width:768px){
    .content{
        margin-left:0;
        padding:15px;
        padding-top:80px;
    }
    .stats-row{
        grid-template-columns:1fr;
    }
    .stats-row-2{
        grid-template-columns:1fr;
    }
    .header{
        padding:20px;
    }
}
</style>
</head>

<body>

<?php include("../sider/sider.php"); ?>
<?php include("../toper/toper.php"); ?>

<div class="content">



    <!-- ================= USER STATS ================= -->
    <div class="stats-row">
        <div class="stat-card">
            <i class="fas fa-users"></i>
            <h6>Total Users</h6>
            <h3><?php echo $totalUsers; ?></h3>
        </div>

        <div class="stat-card">
            <i class="fas fa-user"></i>
            <h6>Suggesters</h6>
            <h3><?php echo $totalSuggesters; ?></h3>
        </div>

        <div class="stat-card">
            <i class="fas fa-user-tie"></i>
            <h6>Managers</h6>
            <h3><?php echo $totalManagers; ?></h3>
        </div>

        <div class="stat-card">
            <i class="fas fa-user-shield"></i>
            <h6>Admins</h6>
            <h3><?php echo $totalAdmins; ?></h3>
        </div>
    </div>

    <!-- ================= SUGGESTION STATS ================= -->
    <div class="stats-row-2">
        <div class="stat-card">
            <i class="fas fa-comment"></i>
            <h6>Total Suggestions</h6>
            <h3><?php echo $totalSuggestions; ?></h3>
        </div>

        <div class="stat-card">
            <i class="fas fa-clock" style="color:#f59e0b;"></i>
            <h6>Pending</h6>
            <h3><?php echo $pending; ?></h3>
        </div>

        <div class="stat-card">
            <i class="fas fa-check-circle" style="color:#22c55e;"></i>
            <h6>Approved</h6>
            <h3><?php echo $approved; ?></h3>
        </div>

        <div class="stat-card">
            <i class="fas fa-times-circle" style="color:#ef4444;"></i>
            <h6>Rejected</h6>
            <h3><?php echo $rejected; ?></h3>
        </div>
    </div>

</div>

<?php include("../footer/footer.php"); ?>

</body>
</html>