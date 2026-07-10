<?php
// ================= SESSION MWANZO KABISA =================
session_start();

// ================= CHECK LOGIN =================
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// ================= CHECK ROLE =================
if($_SESSION['role'] != 'suggester') {
    header("Location: ../login.php");
    exit();
}

include("../config/db.php");
include("../config/functions.php"); // ← IMEONGEZWA

$user_id = $_SESSION['user_id'];

/* ================= STATS ================= */
$stmt = $conn->prepare("
SELECT 
    COUNT(*) total,
    SUM(status='pending') pending,
    SUM(status='approved') approved,
    SUM(status='rejected') rejected,
    SUM(status='implemented') implemented
FROM suggestions 
WHERE user_id=?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

$total = $data['total'] ?? 0;
$pending = $data['pending'] ?? 0;
$approved = $data['approved'] ?? 0;
$rejected = $data['rejected'] ?? 0;
$implemented = $data['implemented'] ?? 0;

// =====================
// REKODI DASHBOARD VIEW (OPTIONAL - INAWEZA KUONDOKA IKIWA INAZIDI LOGS)
// =====================
// logActivity(
//     $_SESSION['user_id'],
//     $_SESSION['full_name'],
//     'Dashboard Viewed',
//     'User viewed suggester dashboard'
// );
?>

<!DOCTYPE html>
<html>
<head>
<title>Dashboard</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
body{
    margin:0;
    font-family:'Segoe UI',sans-serif;
    background:#f8fafc;
    color:#1e293b;
}

.content{
    margin-left:250px;
    padding:30px;
    padding-top:100px;
    min-height:calc(100vh - 180px);
}

.stats{
    display:grid;
    grid-template-columns:repeat(5,1fr);
    gap:18px;
    margin-bottom:25px;
}

.card{
    background:white;
    padding:18px;
    border-radius:14px;
    text-align:center;
    border:1px solid #e2e8f0;
    box-shadow:0 4px 12px rgba(0,0,0,.06);
    transition:transform 0.2s, box-shadow 0.2s;
}

.card:hover{
    transform:translateY(-3px);
    box-shadow:0 8px 25px rgba(0,0,0,0.1);
}

.card p{
    font-size:26px;
    font-weight:700;
    margin:10px 0 0;
}

.card h4{
    margin:0;
    font-size:14px;
    color:#64748b;
}

/* ================= CARD COLORS ================= */
.card-total{ border-left:4px solid #2563eb; }
.card-pending{ border-left:4px solid #f59e0b; }
.card-approved{ border-left:4px solid #22c55e; }
.card-rejected{ border-left:4px solid #ef4444; }
.card-implemented{ border-left:4px solid #3b82f6; }

@media(max-width:900px){
    .content{
        margin-left:0;
    }
    .stats{
        grid-template-columns:repeat(2,1fr);
    }
}

@media(max-width:600px){
    .stats{
        grid-template-columns:1fr;
    }
}
</style>
</head>

<body>

<?php include("../sider/sider.php"); ?>
<?php include("../toper/toper.php"); ?>

<div class="content">

<!-- ================= STATS ================= -->
<div class="stats">
    <div class="card card-total">
        <h4>Total</h4>
        <p><?= $total ?></p>
    </div>
    <div class="card card-pending">
        <h4>Pending</h4>
        <p><?= $pending ?></p>
    </div>
    <div class="card card-approved">
        <h4>Approved</h4>
        <p><?= $approved ?></p>
    </div>
    <div class="card card-rejected">
        <h4>Rejected</h4>
        <p><?= $rejected ?></p>
    </div>
    <div class="card card-implemented">
        <h4>Implemented</h4>
        <p><?= $implemented ?></p>
    </div>
</div>

</div>

<?php include("../footer/footer.php"); ?>

</body>
</html>