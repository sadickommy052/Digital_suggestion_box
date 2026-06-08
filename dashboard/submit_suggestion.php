<?php
session_start();
include("../config/db.php");

// =====================
// PROTECTION
// =====================  
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (
    !isset($_SESSION['role']) ||
    ($_SESSION['role'] !== 'suggester' && $_SESSION['role'] !== 'user')
) {
    header("Location: ../login.php");
    exit();
}

// Logged-in user ID
$user_id = $_SESSION['user_id'];

$message = "";
$messageType = "";

// =====================
// INSERT SUGGESTION
// =====================
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = $_POST['category'];

    if (!empty($title) && !empty($description) && !empty($category)) {

        $stmt = $conn->prepare("
            INSERT INTO suggestions
            (user_id, category_id, title, message, status)
            VALUES (?, ?, ?, ?, 'pending')
        ");

        $stmt->bind_param(
            "iiss",
            $user_id,
            $category,
            $title,
            $description
        );

        if ($stmt->execute()) {

            $message = "Suggestion submitted successfully!";
            $messageType = "success";

            header("refresh:2;url=suggester_dashboard.php");

        } else {
            $message = "Error submitting suggestion!";
            $messageType = "danger";
        }

    } else {
        $message = "All fields are required!";
        $messageType = "warning";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<title>Submit Suggestion</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>

/* =======================
   WHITE MODE CLEAN DESIGN
======================= */

body{
    margin:0;
    font-family:'Segoe UI',sans-serif;
    background:#ffffff;
    color:#111;
}

.content{
    margin-left:230px;
    padding:20px;
}

.card-box{
    background:#ffffff;
    border-radius:18px;
    padding:25px;
    box-shadow:0 10px 30px rgba(0,0,0,0.06);
    border:1px solid #f1f1f1;
}

.form-control{
    padding:12px;
    border-radius:10px;
    border:1px solid #e5e7eb;
    background:#fff;
    color:#111;
}

.form-control:focus{
    border-color:#9ca3af;
    box-shadow:0 0 0 3px rgba(156,163,175,0.15);
    outline:none;
}

.btn-primary{
    padding:12px;
    border-radius:10px;
    background:#111827;
    border:none;
    color:white;
}

.btn-primary:hover{
    background:#000;
}

.page-title{
    margin-bottom:15px;
    color:#111827;
    font-weight:600;
}

.form-label{
    color:#374151;
    font-weight:500;
}

.alert-success{
    background:#ecfdf5;
    color:#065f46;
    border:1px solid #a7f3d0;
}

.alert-danger{
    background:#fef2f2;
    color:#991b1b;
    border:1px solid #fecaca;
}

.alert-warning{
    background:#fffbeb;
    color:#92400e;
    border:1px solid #fde68a;
}

</style>

</head>

<body>

<?php include("../sider/sider.php"); ?>
<?php include("../toper/toper.php"); ?>

<div class="content">

<div class="card-box">

<h4 class="page-title">
<i class="fas fa-paper-plane"></i>
Submit Suggestion
</h4>

<?php if (!empty($message)) { ?>

<div class="alert alert-<?php echo $messageType; ?> text-center">
<?php echo $message; ?>
</div>

<?php } ?>

<form method="POST">

    <!-- ===================== -->
    <!-- CATEGORY DROPDOWN -->
    <!-- ===================== -->
    <div class="mb-3">
        <label class="form-label">Category</label>

        <select name="category" class="form-control" required>
            <option value="">Select Category</option>
           

            <?php
            $cat = $conn->prepare("SELECT category_id, category_name FROM categories ORDER BY category_name ASC");
            $cat->execute();
            $result = $cat->get_result();

            while($c = $result->fetch_assoc()){
            ?>
                <option value="<?php echo $c['category_id']; ?>">
                    <?php echo htmlspecialchars($c['category_name']); ?>
                </option>
            <?php } ?>

        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Title</label>
        <input type="text" name="title" class="form-control"
               placeholder="Enter suggestion title" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Description</label>
        <textarea name="description" rows="5" class="form-control"
                  placeholder="Write your suggestion..." required></textarea>
    </div>

    <button type="submit" class="btn btn-primary w-100">
        <i class="fas fa-paper-plane"></i>
        Submit Suggestion
    </button>

</form>

</div>

</div>

</body>
</html>