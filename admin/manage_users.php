<?php
session_start();
include("../config/db.php");
include("../config/functions.php"); // ← Ongeza hii

// =====================
// ADD USER
// =====================
if(isset($_POST['add_user'])){
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    // ... code ya kuongeza user ...
    
    if($stmt->execute()){
        
        logActivity(
            $_SESSION['user_id'],
            $_SESSION['full_name'],
            'User Added',
            'Added new user: ' . $full_name . ' (Role: ' . $role . ', Email: ' . $email . ')'
        );
        
        header("Location: manage_users.php");
        exit();
    }
}

// =====================
// UPDATE ROLE
// =====================
if(isset($_POST['update_role'])){
    $user_id = $_POST['suggester_id'];
    $role = $_POST['role'];
    
    $user = $conn->query("SELECT full_name FROM users WHERE user_id = $user_id")->fetch_assoc();
    
    if($conn->query("UPDATE users SET role='$role' WHERE user_id=$user_id")){
        
        logActivity(
            $_SESSION['user_id'],
            $_SESSION['full_name'],
            'Role Updated',
            'Updated role for user: ' . $user['full_name'] . ' to ' . $role
        );
        
        header("Location: manage_users.php");
        exit();
    }
}

// =====================
// ACTIVATE USER
// =====================
if(isset($_GET['activate'])){
    $id = intval($_GET['activate']);
    
    $user = $conn->query("SELECT full_name FROM users WHERE user_id = $id")->fetch_assoc();
    
    if($conn->query("UPDATE users SET status='active' WHERE user_id=$id")){
        
        logActivity(
            $_SESSION['user_id'],
            $_SESSION['full_name'],
            'User Activated',
            'Activated user: ' . $user['full_name']
        );
        
        header("Location: manage_users.php");
        exit();
    }
}

// =====================
// DEACTIVATE USER
// =====================
if(isset($_GET['deactivate'])){
    $id = intval($_GET['deactivate']);
    
    $user = $conn->query("SELECT full_name FROM users WHERE user_id = $id")->fetch_assoc();
    
    if($conn->query("UPDATE users SET status='inactive' WHERE user_id=$id")){
        
        logActivity(
            $_SESSION['user_id'],
            $_SESSION['full_name'],
            'User Deactivated',
            'Deactivated user: ' . $user['full_name']
        );
        
        header("Location: manage_users.php");
        exit();
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

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
/* ================= NO RESET - USIATHIRI SIDER ================= */
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

.card{
    background:white;
    padding:25px;
    border-radius:16px;
    border:1px solid #e2e8f0;
    box-shadow:0 4px 12px rgba(0,0,0,0.06);
    margin-bottom:25px;
}

.card h3{
    margin-top:0;
    color:#111827;
    font-size:20px;
    font-weight:600;
}

.card h3 i{
    margin-right:10px;
    color:#2563eb;
}

/* ================= FORM ================= */
.form-group{
    margin-bottom:15px;
}

.form-group label{
    display:block;
    font-weight:600;
    font-size:13px;
    color:#374151;
    margin-bottom:5px;
}

.form-group input,
.form-group select{
    width:100%;
    padding:10px 14px;
    border:1px solid #cbd5e1;
    border-radius:8px;
    font-size:14px;
    transition:all 0.2s ease;
    background:#f8fafc;
    box-sizing:border-box;
}

.form-group input:focus,
.form-group select:focus{
    outline:none;
    border-color:#111827;
    box-shadow:0 0 0 3px rgba(17,24,39,0.08);
    background:white;
}

.form-row{
    display:grid;
    grid-template-columns:3fr 3fr 2fr 2fr 2fr;
    gap:12px;
    align-items:end;
}

.form-row .form-group{
    margin-bottom:0;
}

/* ================= BUTTONS ================= */
.btn{
    padding:10px 20px;
    border:none;
    border-radius:8px;
    font-weight:600;
    font-size:14px;
    cursor:pointer;
    transition:all 0.2s ease;
    display:inline-flex;
    align-items:center;
    gap:6px;
    text-decoration:none;
}

.btn-primary{
    background:#111827;
    color:white;
}

.btn-primary:hover{
    background:#1f2937;
}

.btn-success{
    background:#22c55e;
    color:white;
}

.btn-success:hover{
    background:#16a34a;
}

.btn-warning{
    background:#f59e0b;
    color:white;
}

.btn-warning:hover{
    background:#d97706;
}

.btn-danger{
    background:#dc2626;
    color:white;
}

.btn-danger:hover{
    background:#b91c1c;
}

.btn-sm{
    padding:6px 12px;
    font-size:12px;
}

.btn-block{
    width:100%;
}

/* ================= TABLE ================= */
table{
    width:100%;
    border-collapse:collapse;
    background:white;
}

th{
    background:#111827;
    color:white;
    padding:14px;
    text-align:left;
    font-weight:600;
}

td{
    padding:14px;
    border-bottom:1px solid #e2e8f0;
    vertical-align:middle;
}

tr:hover{
    background:#f8fafc;
}

/* ================= BADGE ================= */
.badge{
    padding:4px 12px;
    border-radius:999px;
    font-size:12px;
    font-weight:600;
    display:inline-block;
}

.badge-success{
    background:#dcfce7;
    color:#166534;
}

.badge-danger{
    background:#fee2e2;
    color:#991b1b;
}

/* ================= RESPONSIVE ================= */
@media(max-width:992px){
    .form-row{
        grid-template-columns:1fr 1fr;
    }
}

@media(max-width:768px){
    .content{
        margin-left:0;
        padding:15px;
        padding-top:80px;
    }
    .form-row{
        grid-template-columns:1fr;
    }
    .form-row .form-group{
        margin-bottom:10px;
    }
    .table-responsive{
        overflow-x:auto;
    }
}

@media(max-width:480px){
    .card{
        padding:15px;
    }
    .btn-sm{
        display:block;
        width:100%;
        margin-bottom:5px;
        text-align:center;
    }
}
</style>
</head>

<body>

<?php include("../sider/sider.php"); ?>
<?php include("../toper/toper.php"); ?>

<div class="content">

    <!-- ================= ADD USER CARD ================= -->
    <div class="card">
        <h3><i class="fas fa-user-plus"></i> Add New User</h3>

        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Full Name</label>
                    <input type="text" name="full_name" placeholder="Enter full name" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" name="email" placeholder="Enter email" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" placeholder="Enter password" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-user-tag"></i> Role</label>
                    <select name="role" required>
                        <option value="">Select Role</option>
                        <option value="suggester">Suggester</option>
                        <option value="suggestion_manager">Suggestion Manager</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div class="form-group">
                    <button type="submit" name="add_user" class="btn btn-primary btn-block">
                        <i class="fas fa-plus"></i> Add User
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- ================= USERS TABLE CARD ================= -->
    <div class="card">
        <h3><i class="fas fa-users"></i> Manage Users</h3>

        <div class="table-responsive">
            <table>
                <thead>
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
                        <td>#<?= $row['user_id'] ?></td>
                        <td><strong><?= htmlspecialchars($row['full_name']) ?></strong></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="suggester_id" value="<?= $row['user_id'] ?>">
                                <select name="role" class="form-control" style="padding:6px 10px;border-radius:6px;border:1px solid #cbd5e1;background:#f8fafc;">
                                    <option value="suggester" <?= ($row['role']=="suggester")?"selected":"" ?>>Suggester</option>
                                    <option value="suggestion_manager" <?= ($row['role']=="suggestion_manager")?"selected":"" ?>>Manager</option>
                                    <option value="admin" <?= ($row['role']=="admin")?"selected":"" ?>>Admin</option>
                                </select>
                                <button type="submit" name="update_role" class="btn btn-primary btn-sm" style="margin-top:5px;">
                                    <i class="fas fa-sync-alt"></i> Update
                                </button>
                            </form>
                        </td>
                        <td>
                            <span class="badge badge-<?= ($row['status'] ?? 'active') == 'active' ? 'success' : 'danger' ?>">
                                <?= $row['status'] ?? 'active' ?>
                            </span>
                        </td>
                        <td>
                            <?php if(($row['status'] ?? 'active') == 'active'){ ?>
                                <a href="?deactivate=<?= $row['user_id'] ?>" class="btn btn-warning btn-sm">
                                    <i class="fas fa-pause-circle"></i> Deactivate
                                </a>
                            <?php } else { ?>
                                <a href="?activate=<?= $row['user_id'] ?>" class="btn btn-success btn-sm">
                                    <i class="fas fa-play-circle"></i> Activate
                                </a>
                            <?php } ?>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php include("../footer/footer.php"); ?>

</body>
</html>