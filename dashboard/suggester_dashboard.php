<?php
session_start();
include("../config/db.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

// =====================
// AUTH CHECK
// =====================
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] !== 'suggester') {
    header("Location: ../login.php");
    exit();
}

$full_name = $_SESSION['full_name'];
$user_id = $_SESSION['user_id'];

// =====================
// QUERY (USER STATS)
// =====================
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total,
        COALESCE(SUM(status='pending'),0) as pending,
        COALESCE(SUM(status='in_review'),0) as in_review,
        COALESCE(SUM(status='approved'),0) as approved,
        COALESCE(SUM(status='rejected'),0) as rejected,
        COALESCE(SUM(status='implemented'),0) as implemented
    FROM suggestions
    WHERE user_id = ?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

$total = $data['total'] ?? 0;
$pending = $data['pending'] ?? 0;
$in_review = $data['in_review'] ?? 0;
$approved = $data['approved'] ?? 0;
$rejected = $data['rejected'] ?? 0;
$implemented = $data['implemented'] ?? 0;

// =====================
// OTHER USERS SUGGESTIONS + VOTES
// =====================
$otherStmt = $conn->prepare("
    SELECT
        s.suggestion_id,
        s.title,
        s.message,
        s.priority,
        s.status,
        s.created_at,
        u.full_name,

        COUNT(sv.vote_id) AS votes

    FROM suggestions s
    INNER JOIN users u ON s.user_id = u.user_id
    LEFT JOIN votes sv ON s.suggestion_id = sv.suggestion_id
    WHERE s.user_id != ?
    GROUP BY s.suggestion_id
    ORDER BY s.created_at DESC
");

$otherStmt->bind_param("i", $user_id);
$otherStmt->execute();
$otherSuggestions = $otherStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<title>User Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
body{
    background:#f4f7fc;
    font-family:Segoe UI;
}
.content{
    margin-left:220px;
    padding:20px;
}
.card{
    border:none;
    border-radius:14px;
    box-shadow:0 5px 20px rgba(0,0,0,0.08);
}
</style>

</head>

<body>

<?php include("../sider/sider.php"); ?>
<?php include("../toper/toper.php"); ?>

<div class="content">

    <h4>Welcome, <?php echo htmlspecialchars($full_name); ?></h4>
    <hr>

    <div class="row">

        <div class="col-md-2"><div class="card p-3"><h6>Total</h6><h3><?php echo $total; ?></h3></div></div>
        <div class="col-md-2"><div class="card p-3"><h6>Pending</h6><h3><?php echo $pending; ?></h3></div></div>
        <div class="col-md-2"><div class="card p-3"><h6>In Review</h6><h3><?php echo $in_review; ?></h3></div></div>
        <div class="col-md-2"><div class="card p-3"><h6>Approved</h6><h3><?php echo $approved; ?></h3></div></div>
        <div class="col-md-2"><div class="card p-3"><h6>Rejected</h6><h3><?php echo $rejected; ?></h3></div></div>
        <div class="col-md-2"><div class="card p-3"><h6>Implemented</h6><h3><?php echo $implemented; ?></h3></div></div>

    </div>

    <hr class="mt-5">

    <div class="card p-3 mt-4">

        <h4><i class="fas fa-users"></i> Other Users Suggestions</h4>

        <div class="table-responsive">

            <table class="table table-bordered table-striped">

                <thead>
                    <tr>
                        <th>User</th>
                        <th>Title</th>
                        <th>Message</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Votes</th>
                        <th>Date</th>
                    </tr>
                </thead>

                <tbody>

                <?php if($otherSuggestions->num_rows > 0): ?>

                    <?php while($row = $otherSuggestions->fetch_assoc()): ?>

                    <tr>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                        <td><?php echo htmlspecialchars($row['message']); ?></td>
                        <td><?php echo ucfirst($row['priority']); ?></td>
                        <td><?php echo ucfirst(str_replace('_',' ',$row['status'])); ?></td>
                        <td><?php echo $row['votes']; ?></td>
                        <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                    </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="7" class="text-center">No suggestions found.</td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

</body>
</html>