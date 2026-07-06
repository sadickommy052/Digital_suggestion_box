<?php
session_start();
include("../config/db.php");

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

if(!isset($_SESSION['user_id']) || ($_SESSION['role']??'')!='suggestion_manager'){
    header("Location: ../login.php");
    exit();
}

$user_id_session = (int)$_SESSION['user_id'];

/* GET USER INFO (NAME + PROFILE PICTURE FIXED) */
$user=$conn->prepare("
SELECT full_name, profile_picture 
FROM users 
WHERE user_id=?
");
$user->bind_param("i",$user_id_session);
$user->execute();
$name=$user->get_result()->fetch_assoc();

$full_name = $name['full_name'] ?? "User";
$profile_picture = $name['profile_picture'] ?? "";

/* ================= ACTIONS ================= */
if(isset($_GET['approve']) || isset($_GET['reject']) || isset($_GET['delete']) || isset($_GET['implement'])){

$id=(int)($_GET['approve'] ?? $_GET['reject'] ?? $_GET['delete'] ?? $_GET['implement']);

$q=$conn->prepare("SELECT user_id FROM suggestions WHERE suggestion_id=?");
$q->bind_param("i",$id);
$q->execute();

$owner=$q->get_result()->fetch_assoc();

if($owner){

$user_id=$owner['user_id'];

if(isset($_GET['approve'])){
$conn->query("UPDATE suggestions SET status='approved' WHERE suggestion_id=$id");
$title="Suggestion Approved";
$msg="Your suggestion has been approved.";
$type="suggestion_approved";
}
elseif(isset($_GET['reject'])){
$conn->query("UPDATE suggestions SET status='rejected' WHERE suggestion_id=$id");
$title="Suggestion Rejected";
$msg="Your suggestion has been rejected.";
$type="suggestion_rejected";
}
elseif(isset($_GET['implement'])){
$conn->query("UPDATE suggestions SET status='implemented' WHERE suggestion_id=$id");
$title="Suggestion Implemented";
$msg="Your suggestion has been implemented.";
$type="suggestion_implemented";
}
else{
$conn->query("DELETE FROM suggestions WHERE suggestion_id=$id");
$title="Suggestion Deleted";
$msg="Your suggestion was deleted.";
$type="suggestion_deleted";
}

$n=$conn->prepare("INSERT INTO notifications(user_id,title,message,type,is_read,created_at)VALUES(?,?,?,?,0,NOW())");
$n->bind_param("isss",$user_id,$title,$msg,$type);
$n->execute();
}

header("Location: manager_dashboard.php");
exit();
}

/* ================= STATS ================= */
$total = 0;
$pending = 0;
$approved = 0;
$rejected = 0;
$implemented = 0;

$total_result = $conn->query("SELECT COUNT(*) c FROM suggestions");
if($total_result){ $total = $total_result->fetch_assoc()['c']; }

$pending_result = $conn->query("SELECT COUNT(*) c FROM suggestions WHERE status='pending'");
if($pending_result){ $pending = $pending_result->fetch_assoc()['c']; }

$approved_result = $conn->query("SELECT COUNT(*) c FROM suggestions WHERE status='approved'");
if($approved_result){ $approved = $approved_result->fetch_assoc()['c']; }

$rejected_result = $conn->query("SELECT COUNT(*) c FROM suggestions WHERE status='rejected'");
if($rejected_result){ $rejected = $rejected_result->fetch_assoc()['c']; }

$implemented_result = $conn->query("SELECT COUNT(*) c FROM suggestions WHERE status='implemented'");
if($implemented_result){ $implemented = $implemented_result->fetch_assoc()['c']; }

/* ================= RECENT ================= */
$recent = $conn->query("
SELECT 
    s.*,
    u.full_name,
    c.category_name,

    SUM(CASE WHEN v.response='agree' THEN 1 ELSE 0 END) AS total_agree,
    SUM(CASE WHEN v.response='disagree' THEN 1 ELSE 0 END) AS total_disagree

FROM suggestions s
JOIN users u ON u.user_id = s.user_id
LEFT JOIN categories c ON c.category_id = s.category_id
LEFT JOIN votes v ON v.suggestion_id = s.suggestion_id

GROUP BY s.suggestion_id
ORDER BY s.suggestion_id DESC
LIMIT 10
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Manager Dashboard</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body{
margin:0;
font-family:Segoe UI;
background:#f4f6fb;
}

.content{
margin-left:200px;
padding:100px;
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
box-shadow:0 4px 15px rgba(0,0,0,0.08);
}

.card p{font-size:26px;font-weight:bold;margin:10px 0 0;}

.card.total{border-left:5px solid #111827;}
.card.pending{border-left:5px solid #f59e0b;}
.card.approved{border-left:5px solid #22c55e;}
.card.rejected{border-left:5px solid #ef4444;}
.card.implemented{border-left:5px solid #3b82f6;}

.box{
background:white;
padding:22px;
border-radius:14px;
margin-top:20px;
box-shadow:0 4px 15px rgba(0,0,0,0.08);
}

.recent-grid{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
gap:15px;
}

.recent-card{
background:#fff;
padding:15px;
border-radius:12px;
box-shadow:0 3px 10px rgba(0,0,0,0.06);
display:flex;
flex-direction:column;
gap:10px;
}

.preview{
width:100%;
height:120px;
object-fit:cover;
border-radius:8px;
}

.status{
padding:6px 10px;
border-radius:999px;
font-size:12px;
font-weight:600;
display:inline-block;
}

.approved{background:#dcfce7;color:#166534;}
.pending{background:#fef3c7;color:#92400e;}
.rejected{background:#fee2e2;color:#991b1b;}
.implemented{background:#dbeafe;color:#1e40af;}

.vote-box{
display:flex;
gap:15px;
font-size:13px;
color:#374151;
align-items:center;
}
</style>
</head>

<body>

<?php include("../sider/sider.php"); ?>
<?php include("../toper/toper.php"); ?>

<div class="content">

<!-- STATS -->
<div class="stats">
<div class="card"><h4>Total</h4><p><?=$total?></p></div>
<div class="card"><h4>Pending</h4><p><?=$pending?></p></div>
<div class="card"><h4>Approved</h4><p><?=$approved?></p></div>
<div class="card"><h4>Rejected</h4><p><?=$rejected?></p></div>
<div class="card"><h4>Implemented</h4><p><?=$implemented?></p></div>
</div>

<!-- RECENT -->
<div class="box">
<h3>Recent Suggestions</h3>

<div class="recent-grid">

<?php if($recent && $recent->num_rows > 0): ?>
<?php while($r=$recent->fetch_assoc()): ?>

<div class="recent-card">

<strong><?=htmlspecialchars($r['full_name'])?></strong>

<div><?=htmlspecialchars($r['category_name'] ?? 'No category')?></div>

<div><?=htmlspecialchars($r['message'])?></div>

<div class="vote-box">
    <span><i class="fas fa-thumbs-up"></i> <?=$r['total_agree']?></span>
    <span><i class="fas fa-thumbs-down"></i> <?=$r['total_disagree']?></span>
</div>

<span class="status <?=$r['status']?>">
<?=ucfirst($r['status'])?>
</span>

</div>

<?php endwhile; ?>
<?php else: ?>
<div class="recent-card">
    <p style="color:#999;text-align:center;">No suggestions found</p>
</div>
<?php endif; ?>

</div>

</div>

</div>

</body>
</html>