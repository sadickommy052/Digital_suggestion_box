<?php
session_start();
include("../config/db.php");

// =====================
// AUTH CHECK (ADMIN ONLY)
// =====================
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// =====================
// GET SYSTEM SETTINGS
// =====================
$settings = $conn->query("SELECT * FROM system_settings LIMIT 1")->fetch_assoc();

// =====================
// CREATE DEFAULT SETTINGS IF NOT EXISTS
// =====================
if (!$settings) {

    $conn->query("
        INSERT INTO system_settings 
        (system_name, allow_registration, maintenance_mode)
        VALUES 
        ('Digital Suggestion Box', 'yes', 'no')
    ");

    header("Location: system_settings.php");
    exit();
}

// =====================
// UPDATE SETTINGS
// =====================
if (isset($_POST['update_settings'])) {

    $system_name = $_POST['system_name'];
    $allow_registration = $_POST['allow_registration'];
    $maintenance_mode = $_POST['maintenance_mode'];

    $stmt = $conn->prepare("
        UPDATE system_settings
        SET system_name=?, allow_registration=?, maintenance_mode=?
        WHERE setting_id=1
    ");

    $stmt->bind_param(
        "sss",
        $system_name,
        $allow_registration,
        $maintenance_mode
    );

    $stmt->execute();

    header("Location: system_settings.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>

<title>System Settings</title>

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
}

label{
    font-weight:600;
    margin-top:10px;
}

</style>

</head>

<body>

<?php include("../sider/sider.php"); ?>
<?php include("../toper/toper.php"); ?>

<div class="content">

<div class="card">

<h3>⚙️ System Settings</h3>
<p>Manage system configuration and behavior</p>

<form method="POST">

<!-- SYSTEM NAME -->
<label>System Name</label>
<input type="text" name="system_name"
class="form-control"
value="<?= $settings['system_name'] ?>" required>

<!-- REGISTRATION -->
<label>Allow User Registration</label>
<select name="allow_registration" class="form-select" required>
    <option value="yes" <?= ($settings['allow_registration']=="yes")?"selected":"" ?>>
        Yes
    </option>
    <option value="no" <?= ($settings['allow_registration']=="no")?"selected":"" ?>>
        No
    </option>
</select>

<!-- MAINTENANCE MODE -->
<label>Maintenance Mode</label>
<select name="maintenance_mode" class="form-select" required>
    <option value="no" <?= ($settings['maintenance_mode']=="no")?"selected":"" ?>>
        Off
    </option>
    <option value="yes" <?= ($settings['maintenance_mode']=="yes")?"selected":"" ?>>
        On
    </option>
</select>

<br>

<button type="submit" name="update_settings" class="btn btn-primary">
Save Settings
</button>

</form>

</div>

</div>

</body>
</html>