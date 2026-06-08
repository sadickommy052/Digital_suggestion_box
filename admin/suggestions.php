<?php
session_start();
include("../config/db.php");

// =====================
// ADMIN PROTECTION
// =====================
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// =====================
// ACTION APPROVE / REJECT
// =====================
if (isset($_GET['action']) && isset($_GET['id'])) {

    $id = $_GET['id'];
    $action = $_GET['action'];

    if ($action == "approve") {
        $status = "approved";
    } elseif ($action == "reject") {
        $status = "rejected";
    } else {
        $status = "pending";
    }

    $stmt = $conn->prepare("UPDATE suggestions SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();

    header("Location: suggestions.php");
    exit();
}

// =====================
// FILTERS
// =====================
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'all';
$user_id = $_GET['user_id'] ?? '';

$where = "WHERE 1";

if ($search != "") {
    $where .= " AND (s.title LIKE '%$search%' OR s.message LIKE '%$search%')";
}

if ($filter != "all") {
    $where .= " AND s.status='$filter'";
}

if ($user_id != "") {
    $where .= " AND s.user_id='$user_id'";
}

// =====================
// DATA
// =====================
$result = $conn->query("
    SELECT s.*, u.full_name, u.email
    FROM suggestions s
    JOIN users u ON s.user_id = u.user_id
    $where
    ORDER BY s.id DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Suggestions System</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
    background:#f5f7fb;
    font-family:Segoe UI;
}

/* SIDEBAR */
.sidebar{
    position:fixed;
    width:240px;
    height:100vh;
    background:white;
    padding:20px;
    box-shadow:0 0 10px rgba(0,0,0,0.05);
}

/* CONTENT */
.container-box{
    margin-left:260px;
    padding:20px;
}

/* CARDS */
.card-box{
    background:white;
    padding:15px;
    border-radius:15px;
    box-shadow:0 5px 15px rgba(0,0,0,0.05);
    margin-bottom:15px;
}

.suggestion-card{
    background:white;
    padding:15px;
    border-radius:15px;
    box-shadow:0 5px 15px rgba(0,0,0,0.05);
    margin-bottom:15px;
}

/* STATUS */
.status{
    padding:4px 10px;
    border-radius:20px;
    color:white;
    font-size:12px;
}

.pending{background:orange;}
.approved{background:green;}
.rejected{background:red;}

.user-card{
    background:#fff;
    padding:10px;
    border-radius:12px;
    box-shadow:0 3px 10px rgba(0,0,0,0.05);
    text-align:center;
    margin-bottom:10px;
    transition:0.3s;
}

.user-card:hover{
    transform:scale(1.03);
    background:#f1f5f9;
}

.user-card a{
    text-decoration:none;
    color:#000;
}

</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">

    <h4>Admin Panel</h4>
    <hr>

    <a href="admin_dashboard.php">Dashboard</a><br>
    <a href="suggestions.php">Suggestions</a><br>
    <a href="../logout.php">Logout</a>

</div>

<!-- CONTENT -->
<div class="container-box">

    <h4>Suggestions Management System</h4>

    <!-- RESET -->
    <a href="suggestions.php" class="btn btn-dark btn-sm mb-3">View All</a>

    <!-- USERS BOXES -->
    <div class="card-box">

        <h5>Users</h5>

        <div class="row">

            <?php
            $users = $conn->query("SELECT * FROM users WHERE role='user'");
            while($u = $users->fetch_assoc()) {
            ?>

            <div class="col-md-3">

                <a href="suggestions.php?user_id=<?php echo $u['user_id']; ?>">

                    <div class="user-card">

                        <b><?php echo $u['full_name']; ?></b><br>
                        <small><?php echo $u['email']; ?></small>

                    </div>

                </a>

            </div>

            <?php } ?>

        </div>

    </div>

    <!-- SEARCH -->
    <form method="GET" class="row g-2 mb-3">

        <div class="col-md-5">
            <input type="text" name="search" class="form-control" placeholder="Search..." value="<?php echo $search; ?>">
        </div>

        <div class="col-md-3">
            <select name="filter" class="form-control">
                <option value="all">All</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>

        <div class="col-md-2">
            <button class="btn btn-primary w-100">Filter</button>
        </div>

    </form>

    <!-- SUGGESTION CARDS -->
    <div class="row">

        <?php while($row = $result->fetch_assoc()) { ?>

        <div class="col-md-6">

            <div class="suggestion-card">

                <h5><?php echo $row['title']; ?></h5>

                <p class="text-muted">
                    <?php echo $row['message']; ?>
                </p>

                <small>
                    <b>User:</b> <?php echo $row['full_name']; ?>
                </small>

                <br><br>

                <?php if($row['status']=="pending"){ ?>
                    <span class="status pending">Pending</span>
                <?php } elseif($row['status']=="approved"){ ?>
                    <span class="status approved">Approved</span>
                <?php } else { ?>
                    <span class="status rejected">Rejected</span>
                <?php } ?>

                <hr>

                <a href="?action=approve&id=<?php echo $row['id']; ?>" class="btn btn-success btn-sm">Approve</a>
                <a href="?action=reject&id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm">Reject</a>

            </div>

        </div>

        <?php } ?>

    </div>

</div>

</body>
</html>