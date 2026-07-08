<?php
session_start();
include("../config/db.php");

// =====================
// AUTH CHECK
// =====================
if(
    !isset($_SESSION['user_id']) ||
    $_SESSION['role'] != 'admin'
){
    header("Location: ../login.php");
    exit();
}


// =====================
// ADD USER
// =====================
if(isset($_POST['add_user'])){

    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Check if email exists
    $check = $conn->prepare("
        SELECT user_id
        FROM users
        WHERE email=?
    ");

    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if($result->num_rows == 0){

        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
            INSERT INTO users
            (full_name, email, password, role, status, created_at)
            VALUES (?, ?, ?, ?, 'active', NOW())
        ");

        $stmt->bind_param(
            "ssss",
            $full_name,
            $email,
            $hashed,
            $role
        );

        $stmt->execute();

        header("Location: manage_users.php");
        exit();

    } else {
        echo "<script>alert('Email already exists');</script>";
    }
}


// =====================
// UPDATE ROLE
// =====================
if(isset($_POST['update_role'])){

    $user_id = $_POST['suggester_id'];
    $role = $_POST['role'];

    $stmt = $conn->prepare("
        UPDATE users
        SET role=?
        WHERE user_id=?
    ");

    $stmt->bind_param("si", $role, $user_id);
    $stmt->execute();

    header("Location: manage_users.php");
    exit();
}


// =====================
// ACTIVATE USER
// =====================
if(isset($_GET['activate'])){

    $id = intval($_GET['activate']);

    $stmt = $conn->prepare("
        UPDATE users
        SET status='active'
        WHERE user_id=?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: manage_users.php");
    exit();
}


// =====================
// DEACTIVATE USER
// =====================
if(isset($_GET['deactivate'])){

    $id = intval($_GET['deactivate']);

    $stmt = $conn->prepare("
        UPDATE users
        SET status='inactive'
        WHERE user_id=?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: manage_users.php");
    exit();
}


// =====================
// GET USERS
// =====================
$users = $conn->query("
    SELECT *
    FROM users
    ORDER BY user_id DESC
");
?>

<!DOCTYPE html>
<html>
<head>

<title>Manage Users</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#f5f7fb;
    font-family:Segoe UI;
}

.content{
    margin-left:240px;
    padding:25px;
}

.card{
    background:white;
    padding:25px;
    border-radius:15px;
    box-shadow:0 5px 20px rgba(0,0,0,.08);
    margin-bottom:20px;
}

.btn-sm{
    margin:2px;
}
</style>

</head>

<body>

<?php include("../sider/sider.php"); ?>
<?php include("../toper/toper.php"); ?>

<div class="content">

<!-- ADD USER -->
<div class="card">
<h3>Add New User</h3>

<form method="POST">
<div class="row">

<div class="col-md-3">
<input type="text" name="full_name" class="form-control" placeholder="Full Name" required>
</div>

<div class="col-md-3">
<input type="email" name="email" class="form-control" placeholder="Email" required>
</div>

<div class="col-md-2">
<input type="password" name="password" class="form-control" placeholder="Password" required>
</div>

<div class="col-md-2">
<select name="role" class="form-select" required>
    <option value="">Select Role</option>
    <option value="suggester">Suggester</option>
    <option value="suggestion_manager">Suggestion Manager</option>
    <option value="admin">Admin</option>
</select>
</div>

<div class="col-md-2">
<button type="submit" name="add_user" class="btn btn-primary w-100">
Add User
</button>
</div>

</div>
</form>
</div>


<!-- USER TABLE -->
<div class="card">

<h3>Manage Users</h3>

<table class="table table-bordered table-hover">

<thead class="table-dark">
<tr>
<th>ID</th>
<th>Name</th>
<th>Email</th>
<th>Role</th>
<th>Status</th>
<th>Actions</th>
</tr>
</thead>

<tbody>

<?php while($row = $users->fetch_assoc()){ ?>

<tr>

<td><?= $row['user_id'] ?></td>
<td><?= $row['full_name'] ?></td>
<td><?= $row['email'] ?></td>

<td>
<form method="POST">
<input type="hidden" name="suggester_id" value="<?= $row['user_id'] ?>">

<select name="role" class="form-select">
    <option value="suggester" <?= ($row['role']=="suggester")?"selected":"" ?>>Suggester</option>
    <option value="suggestion_manager" <?= ($row['role']=="suggestion_manager")?"selected":"" ?>>Manager</option>
    <option value="admin" <?= ($row['role']=="admin")?"selected":"" ?>>Admin</option>
</select>

<br>

<button type="submit" name="update_role" class="btn btn-primary btn-sm">
Update Role
</button>

</form>
</td>

<td>
<span class="badge bg-<?= $row['status']=='active' ? 'success' : 'danger' ?>">
    <?= $row['status'] ?? 'active' ?>
</span>
</td>

<td>

<?php if(($row['status'] ?? 'active') == 'active'){ ?>

<a href="?deactivate=<?= $row['user_id'] ?>"
   class="btn btn-warning btn-sm">
   Deactivate
</a>

<?php } else { ?>

<a href="?activate=<?= $row['user_id'] ?>"
   class="btn btn-success btn-sm">
   Activate
</a>

<?php } ?>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>
<?php include("../footer/footer.php"); ?>

</body>
</html>