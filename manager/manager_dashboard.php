<?php
session_start();
include("../config/db.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

// =====================
// AUTH CHECK
// =====================
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'suggestion_manager') {
    header("Location: ../login.php");
    exit();
}

$full_name = $_SESSION['full_name'] ?? 'Manager';

// =====================
// ACTION HANDLER (APPROVE / REJECT / DELETE)
// =====================

// APPROVE
if (isset($_GET['approve'])) {
    $id = intval($_GET['approve']);

    $stmt = $conn->prepare("UPDATE suggestions SET status='approved' WHERE suggestion_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: manager_dashboard.php");
    exit();
}

// REJECT
if (isset($_GET['reject'])) {
    $id = intval($_GET['reject']);

    $stmt = $conn->prepare("UPDATE suggestions SET status='rejected' WHERE suggestion_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: manager_dashboard.php");
    exit();
}

// DELETE
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    $stmt = $conn->prepare("DELETE FROM suggestions WHERE suggestion_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: manager_dashboard.php");
    exit();
}

// REPLY
if (isset($_POST['reply_submit'])) {
    $id = intval($_POST['suggestion_id']);
    $reply = trim($_POST['reply']);

    $stmt = $conn->prepare("UPDATE suggestions SET reply=? WHERE suggestion_id=?");
    $stmt->bind_param("si", $reply, $id);
    $stmt->execute();

    header("Location: manager_dashboard.php");
    exit();
}

// =====================
// FILTER
// =====================
$status = $_GET['status'] ?? '';

if ($status != '') {
    $stmt = $conn->prepare("SELECT * FROM suggestions WHERE status=? ORDER BY suggestion_id DESC");
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $suggestions = $stmt->get_result();
} else {
    $suggestions = $conn->query("SELECT * FROM suggestions ORDER BY suggestion_id DESC");
}

// =====================
// STATS
// =====================
$total = $conn->query("SELECT COUNT(*) as total FROM suggestions")->fetch_assoc()['total'] ?? 0;
$pending = $conn->query("SELECT COUNT(*) as total FROM suggestions WHERE status='pending'")->fetch_assoc()['total'] ?? 0;
$approved = $conn->query("SELECT COUNT(*) as total FROM suggestions WHERE status='approved'")->fetch_assoc()['total'] ?? 0;
$rejected = $conn->query("SELECT COUNT(*) as total FROM suggestions WHERE status='rejected'")->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html>
<head>
<title>Manager Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{background:#f5f7fb;font-family:Segoe UI;}
.content{margin-left:220px;padding:25px;}
.card{border:none;border-radius:15px;padding:20px;box-shadow:0 5px 18px rgba(0,0,0,.08);margin-bottom:18px;}
</style>

</head>

<body>

<?php include("../sider/sider.php"); ?>
<?php include("../toper/toper.php"); ?>

<div class="content">

<!-- HEADER -->
<div class="card">
<h4>📊 Suggestion Manager Dashboard</h4>
<p>Welcome, <?= htmlspecialchars($full_name) ?></p>
</div>

<!-- STATS -->
<div class="row">

<div class="col-md-3"><div class="card text-center"><h6>Total</h6><h3><?= $total ?></h3></div></div>
<div class="col-md-3"><div class="card text-center"><h6>Pending</h6><h3><?= $pending ?></h3></div></div>
<div class="col-md-3"><div class="card text-center"><h6>Approved</h6><h3><?= $approved ?></h3></div></div>
<div class="col-md-3"><div class="card text-center"><h6>Rejected</h6><h3><?= $rejected ?></h3></div></div>

</div>

<!-- TABLE -->
<div class="card">
<h5>All Suggestions</h5>

<table class="table table-bordered table-hover">
<thead class="table-dark">
<tr>
<th>ID</th>
<th>Message</th>
<th>Status</th>
<th>Action</th>
</tr>
</thead>

<tbody>

<?php while($row = $suggestions->fetch_assoc()){ ?>

<tr>
<td><?= $row['suggestion_id'] ?></td>
<td><?= htmlspecialchars($row['message']) ?></td>

<td>
<?php if($row['status']=='approved'){ ?>
<span class="badge bg-success">Approved</span>
<?php } elseif($row['status']=='rejected'){ ?>
<span class="badge bg-danger">Rejected</span>
<?php } else { ?>
<span class="badge bg-warning text-dark">Pending</span>
<?php } ?>
</td>

<td>

<a href="?approve=<?= $row['suggestion_id'] ?>" class="btn btn-success btn-sm">Approve</a>

<a href="?reject=<?= $row['suggestion_id'] ?>" class="btn btn-danger btn-sm">Reject</a>

<a href="?delete=<?= $row['suggestion_id'] ?>" class="btn btn-dark btn-sm"
onclick="return confirm('Delete this suggestion?')">Delete</a>

<!-- Reply button -->
<button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#reply<?= $row['suggestion_id'] ?>">
Reply
</button>

<!-- Reply Modal -->
<div class="modal fade" id="reply<?= $row['suggestion_id'] ?>">
<div class="modal-dialog">
<div class="modal-content">

<form method="POST">

<div class="modal-header">
<h5>Reply Suggestion</h5>
</div>

<div class="modal-body">

<input type="hidden" name="suggestion_id" value="<?= $row['suggestion_id'] ?>">

<textarea name="reply" class="form-control" required></textarea>

</div>

<div class="modal-footer">
<button type="submit" name="reply_submit" class="btn btn-primary">Send Reply</button>
</div>

</form>

</div>
</div>
</div>

</td>
</tr>

<?php } ?>

</tbody>
</table>

</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>