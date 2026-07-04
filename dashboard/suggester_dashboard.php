<?php
session_start();
include("../config/db.php");

if(!isset($_SESSION['user_id'])||$_SESSION['role']!='suggester'){
header("Location: ../login.php");
exit();
}

$user_id=$_SESSION['user_id'];

/* VOTE ACTION */
if(isset($_GET['vote'])&&isset($_GET['type'])){
$id=(int)$_GET['vote'];
$type=$_GET['type'];

if(in_array($type,['agree','disagree'])){
$q=$conn->prepare("SELECT user_id FROM suggestions WHERE suggestion_id=?");
$q->bind_param("i",$id);
$q->execute();
$owner=$q->get_result()->fetch_assoc();

if($owner&&$owner['user_id']!=$user_id){
$check=$conn->prepare("SELECT vote_id FROM votes WHERE suggestion_id=? AND user_id=?");
$check->bind_param("ii",$id,$user_id);
$check->execute();

if($check->get_result()->num_rows==0){
$vote=$conn->prepare("INSERT INTO votes(suggestion_id,user_id,response) VALUES(?,?,?)");
$vote->bind_param("iis",$id,$user_id,$type);
$vote->execute();
}
}
}
header("Location: ".$_SERVER['PHP_SELF']);
exit();
}

/* STATS */
$stmt=$conn->prepare("
SELECT 
COUNT(*) total,
SUM(status='pending') pending,
SUM(status='approved') approved,
SUM(status='rejected') rejected,
SUM(status='implemented') implemented
FROM suggestions 
WHERE user_id=?
");
$stmt->bind_param("i",$user_id);
$stmt->execute();
$data=$stmt->get_result()->fetch_assoc();

/* EXTRA VARIABLES (ADDED FOR YOUR NEW BLOCK) */
$total = $data['total'];
$pending = $data['pending'];
$approved = $data['approved'];
$rejected = $data['rejected'];
$implemented = $data['implemented'];

/* SUGGESTIONS */
$sql=$conn->prepare("
SELECT s.*,u.full_name,u.profile_picture,
(SELECT COUNT(*) FROM votes v WHERE v.suggestion_id=s.suggestion_id AND v.response='agree') agree_votes,
(SELECT COUNT(*) FROM votes v WHERE v.suggestion_id=s.suggestion_id AND v.response='disagree') disagree_votes
FROM suggestions s 
JOIN users u ON u.user_id=s.user_id 
WHERE s.user_id!=? 
ORDER BY s.suggestion_id DESC
");

$sql->bind_param("i",$user_id);
$sql->execute();
$suggestions=$sql->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>Dashboard</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
body{
    margin:0;
    font-family:Segoe UI, Arial, sans-serif;
    background:#eef3f9;
    color:#1f2937;
}

:root{
    --sidebar:#1e3a8a;
    --sidebar-dark:#1e40af;
    --soft:#eaf2ff;
    --border:#e5eaf2;
}

/* CONTENT */
.content{
    margin-left:200px;
    padding:120px;
    margin: top 100px;
}

/* STATS */
.stats{
    display:grid;
    grid-template-columns:repeat(5,1fr);
    gap:12px;
    margin-bottom:20px;
}

.card{
    background:#fff;
    border-radius:10px;
    padding:14px;
    text-align:center;
    border:1px solid var(--border);
}

.card p{
    font-size:20px;
    font-weight:700;
    margin:0;
}

.card small{
    color:#6b7280;
}

/* GRID */
.box{
    background:#fff;
    border-radius:10px;
    padding:15px;
    border:1px solid var(--border);
}

.grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
    gap:12px;
    margin-top:12px;
}

.card-box{
    background:#fff;
    border:1px solid var(--border);
    border-radius:10px;
    padding:12px;
}

/* VOTE UI */
.top{
    display:flex;
    align-items:center;
    gap:10px;
}

.avatar{
    width:36px;
    height:36px;
    border-radius:50%;
    overflow:hidden;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#e5eaf2;
}

.avatar img{
    width:100%;
    height:100%;
    object-fit:cover;
}

.title{
    font-weight:600;
    margin-top:6px;
    font-size:14px;
}

.msg{
    font-size:12.5px;
    color:#6b7280;
    margin-top:4px;
}

.badges{
    display:flex;
    justify-content:space-between;
    flex-wrap:wrap;
    font-size:12px;
    margin:10px 0;
}

.agree{color:var(--sidebar);}
.disagree{color:#64748b;}

.bar{
    height:6px;
    background:#eef2f7;
    border-radius:10px;
    overflow:hidden;
    display:flex;
}

.agree-bar{background:var(--sidebar);}
.disagree-bar{background:#cbd5e1;}

.btns{
    display:flex;
    gap:10px;
    margin-top:12px;
    flex-wrap:wrap;
}

.btns a{
    flex:1;
    min-width:120px;
    padding:9px;
    text-align:center;
    border-radius:8px;
    text-decoration:none;
    font-size:12px;
    font-weight:600;
}

.agree-btn{
    background:var(--sidebar);
    color:#fff;
}

.disagree-btn{
    background:#f1f5f9;
    color:#334155;
    border:1px solid #e2e8f0;
}

@media(max-width:900px){
    .content{margin-left:0;}
    .stats{grid-template-columns:repeat(2,1fr);}
}
</style>
</head>

<body>

<?php include("../sider/sider.php");?>
<?php include("../toper/toper.php");?>

<div class="content">

<!-- 🔥 NEW STATS BLOCK ADDED -->
<div class="stats">

<div class="card">
<h4>Total</h4>
<p><?=$total?></p>
</div>

<div class="card">
<h4>Pending</h4>
<p><?=$pending?></p>
</div>

<div class="card">
<h4>Approved</h4>
<p><?=$approved?></p>
</div>

<div class="card">
<h4>Rejected</h4>
<p><?=$rejected?></p>
</div>

<div class="card">
<h4>Implemented</h4>
<p><?=$implemented?></p>
</div>

</div>

<div class="box">
<h3>Other Users Suggestions</h3>

<div class="grid">

<?php while($row=$suggestions->fetch_assoc()){

$agree=(int)$row['agree_votes'];
$disagree=(int)$row['disagree_votes'];
$totalVotes=$agree+$disagree;

if($totalVotes==0){
$ap=0;$dp=0;
}else{
$ap=round(($agree/$totalVotes)*100);
$dp=100-$ap;
}
?>

<div class="card-box">

<div class="top">

<div class="avatar">
<?php if(!empty($row['profile_picture'])){?>
<img src="../<?=$row['profile_picture']?>">
<?php }else{?>
<i class="fa fa-user"></i>
<?php }?>
</div>

<strong><?=htmlspecialchars($row['full_name'])?></strong>

</div>

<div class="title"><?=htmlspecialchars($row['title'])?></div>
<div class="msg"><?=htmlspecialchars($row['message'])?></div>

<div class="badges">
<span class="agree"><i class="fa fa-thumbs-up"></i> <?=$agree?> (<?=$ap?>%)</span>
<span class="disagree"><i class="fa fa-thumbs-down"></i> <?=$disagree?> (<?=$dp?>%)</span>
</div>

<div class="bar">
<div class="agree-bar" style="width:<?=$ap?>%"></div>
<div class="disagree-bar" style="width:<?=$dp?>%"></div>
</div>

<div class="btns">
<a class="agree-btn" href="?vote=<?=$row['suggestion_id']?>&type=agree">Agree</a>
<a class="disagree-btn" href="?vote=<?=$row['suggestion_id']?>&type=disagree">Disagree</a>
</div>

</div>

<?php } ?>

</div>
</div>

</div>

</body>
</html>