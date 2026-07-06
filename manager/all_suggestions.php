<?php
session_start();
include("../config/db.php");

error_reporting(E_ALL);
ini_set('display_errors',1);

if(!isset($_SESSION['user_id']) || $_SESSION['role']!='suggestion_manager'){
header("Location: ../login.php");
exit();
}

/* ================= ACTIONS WITH NOTIFICATIONS ================= */

if(isset($_GET['approve'])){
$id=intval($_GET['approve']);

// Get suggestion owner and title
$q=$conn->prepare("SELECT user_id, title FROM suggestions WHERE suggestion_id=?");
$q->bind_param("i",$id);
$q->execute();
$owner=$q->get_result()->fetch_assoc();

if($owner){
    $user_id = $owner['user_id'];
    $suggestion_title = $owner['title'];
    
    // Update status
    $conn->query("UPDATE suggestions SET status='approved' WHERE suggestion_id=$id");
    
    // Send notification
    $title = "Suggestion Approved";
    $msg = "Your suggestion '$suggestion_title' has been approved.";
    $type = "suggestion_approved";
    
    $n=$conn->prepare("INSERT INTO notifications(user_id,title,message,type,is_read,created_at)VALUES(?,?,?,?,0,NOW())");
    $n->bind_param("isss",$user_id,$title,$msg,$type);
    $n->execute();
}

header("Location: all_suggestions.php?msg=approved");
exit();
}

if(isset($_GET['reject'])){
$id=intval($_GET['reject']);

$q=$conn->prepare("SELECT user_id, title FROM suggestions WHERE suggestion_id=?");
$q->bind_param("i",$id);
$q->execute();
$owner=$q->get_result()->fetch_assoc();

if($owner){
    $user_id = $owner['user_id'];
    $suggestion_title = $owner['title'];
    
    $conn->query("UPDATE suggestions SET status='rejected' WHERE suggestion_id=$id");
    
    $title = "Suggestion Rejected";
    $msg = "Your suggestion '$suggestion_title' has been rejected.";
    $type = "suggestion_rejected";
    
    $n=$conn->prepare("INSERT INTO notifications(user_id,title,message,type,is_read,created_at)VALUES(?,?,?,?,0,NOW())");
    $n->bind_param("isss",$user_id,$title,$msg,$type);
    $n->execute();
}

header("Location: all_suggestions.php?msg=rejected");
exit();
}

if(isset($_GET['implement'])){
$id=intval($_GET['implement']);

$q=$conn->prepare("SELECT user_id, title FROM suggestions WHERE suggestion_id=?");
$q->bind_param("i",$id);
$q->execute();
$owner=$q->get_result()->fetch_assoc();

if($owner){
    $user_id = $owner['user_id'];
    $suggestion_title = $owner['title'];
    
    $conn->query("UPDATE suggestions SET status='implemented' WHERE suggestion_id=$id");
    
    $title = "Suggestion Implemented";
    $msg = "Your suggestion '$suggestion_title' has been implemented.";
    $type = "suggestion_implemented";
    
    $n=$conn->prepare("INSERT INTO notifications(user_id,title,message,type,is_read,created_at)VALUES(?,?,?,?,0,NOW())");
    $n->bind_param("isss",$user_id,$title,$msg,$type);
    $n->execute();
}

header("Location: all_suggestions.php?msg=implemented");
exit();
}

if(isset($_GET['delete'])){
$id=intval($_GET['delete']);

$q=$conn->prepare("SELECT user_id, title FROM suggestions WHERE suggestion_id=?");
$q->bind_param("i",$id);
$q->execute();
$owner=$q->get_result()->fetch_assoc();

if($owner){
    $user_id = $owner['user_id'];
    $suggestion_title = $owner['title'];
    
    $conn->query("DELETE FROM suggestions WHERE suggestion_id=$id");
    
    $title = "Suggestion Deleted";
    $msg = "Your suggestion '$suggestion_title' was deleted.";
    $type = "suggestion_deleted";
    
    $n=$conn->prepare("INSERT INTO notifications(user_id,title,message,type,is_read,created_at)VALUES(?,?,?,?,0,NOW())");
    $n->bind_param("isss",$user_id,$title,$msg,$type);
    $n->execute();
}

header("Location: all_suggestions.php?msg=deleted");
exit();
}

/* FILTERS */
$status=$_GET['status'] ?? '';
$search=$_GET['search'] ?? '';
$category=$_GET['category'] ?? '';

$sql="
SELECT 
    suggestions.*,
    users.full_name,
    categories.category_name,
    attachments.file_name,
    attachments.file_path,
    attachments.file_type
FROM suggestions
JOIN users ON users.user_id = suggestions.user_id
LEFT JOIN categories ON categories.category_id = suggestions.category_id
LEFT JOIN attachments ON attachments.suggestion_id = suggestions.suggestion_id
WHERE 1=1
";

$params=[];
$types="";

/* STATUS FILTER */
if($status!=""){
$sql.=" AND suggestions.status=?";
$params[]=$status;
$types.="s";
}

/* SEARCH */
if($search!=""){
$sql.=" AND (
    suggestions.message LIKE ? 
    OR suggestions.title LIKE ? 
    OR users.full_name LIKE ?
)";
$value="%$search%";
$params[]=$value;
$params[]=$value;
$params[]=$value;
$types.="sss";
}

/* CATEGORY */
if($category!=""){
$sql.=" AND suggestions.category_id=?";
$params[]=$category;
$types.="i";
}

$sql.=" ORDER BY suggestions.suggestion_id DESC";

$stmt=$conn->prepare($sql);

if($params){
$stmt->bind_param($types,...$params);
}

$stmt->execute();
$suggestions=$stmt->get_result();

$categories=$conn->query("SELECT * FROM categories ORDER BY category_name");
?>

<!DOCTYPE html>
<html>
<head>
<title>All Suggestions</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
body{
margin:0;
font-family:Segoe UI;
background:linear-gradient(135deg,#f4f7fc,#eef2ff);
color:#111827;
}

.content{
margin-left:250px;
padding:30px;
padding-top:90px;
}

h3{
font-size:22px;
font-weight:700;
margin-bottom:20px;
color:#1e3a8a;
}

.card{
background:#ffffff;
border-radius:16px;
padding:20px;
box-shadow:0 10px 25px rgba(0,0,0,0.08);
margin-bottom:20px;
border:1px solid #e5e7eb;
}

.filter-box{
display:grid;
grid-template-columns:2fr 1fr 1fr 0.8fr;
gap:12px;
}

.form-control{
padding:10px;
border-radius:10px;
border:1px solid #d1d5db;
}

.table thead{
background:linear-gradient(135deg,#1e3a8a,#2563eb);
color:white;
}

.table td{
padding:14px;
border-bottom:1px solid #e5e7eb;
}

.vote-box{
display:inline-flex;
align-items:center;
gap:6px;
background:linear-gradient(135deg,#dbeafe,#bfdbfe);
color:#1e3a8a;
padding:6px 12px;
border-radius:20px;
font-weight:700;
margin-right:6px;
}

.vote-disagree{
background:linear-gradient(135deg,#fee2e2,#fecaca);
color:#991b1b;
}

.status-badge{
padding:6px 12px;
border-radius:999px;
font-size:12px;
font-weight:700;
display:inline-block;
text-transform:capitalize;
}

.approved{background:#dcfce7;color:#166534;}
.pending{background:#fef3c7;color:#92400e;}
.rejected{background:#fee2e2;color:#991b1b;}
.implemented{background:#dbeafe;color:#1e40af;}

.attach-btn{
display:inline-block;
padding:6px 10px;
border-radius:8px;
background:#111827;
color:white;
text-decoration:none;
font-size:12px;
}

.attach-empty{
color:#94a3b8;
font-size:13px;
}

.btn-action{
width:36px;
height:36px;
display:inline-flex;
align-items:center;
justify-content:center;
border-radius:10px;
margin-right:5px;
text-decoration:none;
}

.btn-approve{background:#22c55e;color:white;}
.btn-reject{background:#ef4444;color:white;}
.btn-delete{background:#111827;color:white;}
.btn-implement{background:#3b82f6;color:white;}

/* Alert messages */
.alert-msg{
padding:12px 20px;
border-radius:8px;
margin-bottom:15px;
display:flex;
align-items:center;
gap:10px;
}
.alert-success{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;}
.alert-error{background:#fee2e2;color:#991b1b;border:1px solid #fecaca;}

@media(max-width:900px){
.content{margin-left:0;padding:15px}
.filter-box{grid-template-columns:1fr}
}
</style>
</head>

<body>

<?php include("../sider/sider.php"); ?>
<?php include("../toper/toper.php"); ?>

<div class="content">

<h3>All Suggestions</h3>

<!-- ================= ALERT MESSAGES ================= -->
<?php if(isset($_GET['msg'])): 
    $msg = $_GET['msg'];
    $text = '';
    $icon = 'fa-check-circle';
    
    if($msg == 'approved') {
        $text = '✅ Suggestion approved successfully! Notification sent to suggester.';
    } elseif($msg == 'rejected') {
        $text = '✅ Suggestion rejected successfully! Notification sent to suggester.';
    } elseif($msg == 'implemented') {
        $text = '✅ Suggestion implemented successfully! Notification sent to suggester.';
    } elseif($msg == 'deleted') {
        $text = '✅ Suggestion deleted successfully! Notification sent to suggester.';
    }
?>
<div class="alert-msg alert-success">
    <i class="fas <?=$icon?>"></i> <?=$text?>
</div>
<?php endif; ?>

<div class="card">
<form method="GET" class="filter-box">

<input name="search" class="form-control" placeholder="Search" value="<?=htmlspecialchars($search)?>">

<select name="status" class="form-control">
<option value="">All Status</option>
<option value="pending" <?=($status=='pending')?'selected':''?>>Pending</option>
<option value="approved" <?=($status=='approved')?'selected':''?>>Approved</option>
<option value="rejected" <?=($status=='rejected')?'selected':''?>>Rejected</option>
<option value="implemented" <?=($status=='implemented')?'selected':''?>>Implemented</option>
</select>

<select name="category" class="form-control">
<option value="">All Category</option>
<?php while($c=$categories->fetch_assoc()){ ?>
<option value="<?=$c['category_id']?>" <?=($category==$c['category_id'])?'selected':''?>><?=$c['category_name']?></option>
<?php } ?>
</select>

<button class="btn btn-primary">Filter</button>

</form>
</div>

<div class="card">
<table class="table table-hover">

<thead>
<tr>
<th>ID</th>
<th>User</th>
<th>Category</th>
<th>Message</th>
<th>Attachment</th>
<th>Votes</th>
<th>Status</th>
<th>Action</th>
</tr>
</thead>

<tbody>

<?php while($row=$suggestions->fetch_assoc()){ ?>

<tr>
<td><?=$row['suggestion_id']?></td>
<td><?=htmlspecialchars($row['full_name'])?></td>
<td><?=htmlspecialchars($row['category_name'])?></td>
<td><?=htmlspecialchars($row['message'])?></td>

<td>
<?php if(!empty($row['file_path'])){ ?>
<a class="attach-btn" href="../<?=htmlspecialchars($row['file_path'])?>" target="_blank">
<i class="fas fa-paperclip"></i> View
</a>
<?php }else{ ?>
<span class="attach-empty">No file</span>
<?php } ?>
</td>

<td>
<?php
$id = $row['suggestion_id'];

$agree = $conn->query("SELECT COUNT(*) c FROM votes WHERE suggestion_id=$id AND response='agree'")->fetch_assoc()['c'] ?? 0;
$disagree = $conn->query("SELECT COUNT(*) c FROM votes WHERE suggestion_id=$id AND response='disagree'")->fetch_assoc()['c'] ?? 0;
?>

<span class="vote-box"><i class="fas fa-thumbs-up"></i> <?=$agree?></span>
<span class="vote-box vote-disagree"><i class="fas fa-thumbs-down"></i> <?=$disagree?></span>
</td>

<td>
<span class="status-badge <?=$row['status']?>">
<?=$row['status']?>
</span>
</td>

<td>
<a href="?approve=<?=$row['suggestion_id']?>" class="btn-action btn-approve" title="Approve"><i class="fas fa-check"></i></a>
<a href="?reject=<?=$row['suggestion_id']?>" class="btn-action btn-reject" title="Reject"><i class="fas fa-times"></i></a>
<a href="?implement=<?=$row['suggestion_id']?>" class="btn-action btn-implement" title="Implement"><i class="fas fa-check-double"></i></a>
<a href="?delete=<?=$row['suggestion_id']?>" class="btn-action btn-delete" title="Delete" onclick="return confirm('Delete this suggestion?')"><i class="fas fa-trash"></i></a>
</td>

</tr>

<?php } ?>

</tbody>
</table>
</div>

</div>

</body>
</html>