<?php

// =====================
// SAFE SESSION START
// =====================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../config/db.php");

// =====================
// SAFE SESSION VARIABLES
// =====================
$user_id  = $_SESSION['suggester_id'] ?? 0;
$fullname = $_SESSION['full_name'] ?? '';
$email = "";
$db_password = "";

// =====================
// GET USER INFO
// =====================
if ($user_id > 0) {

    $stmt = $conn->prepare("
        SELECT full_name, email, password 
        FROM suggesters 
        WHERE suggester_id = ?
    ");

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result()->fetch_assoc();

    if ($result) {
        $fullname = $result['full_name'] ?? $fullname;
        $email = $result['email'] ?? '';
        $db_password = $result['password'] ?? '';
    }
}

// =====================
// UPDATE PROFILE
// =====================
$message = "";

if (isset($_POST['update_profile'])) {

    $new_name = $_POST['full_name'];
    $new_email = $_POST['email'];

    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];

    $update_password = false;

    if (!empty($old_password) && !empty($new_password)) {

        if (!empty($db_password) && password_verify($old_password, $db_password)) {

            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_password = true;

        } else {
            $message = "Old password is incorrect!";
        }
    }

    if ($message == "") {

        if ($update_password) {
            $stmt = $conn->prepare("
                UPDATE suggesters 
                SET full_name=?, email=?, password=? 
                WHERE suggester_id=?
            ");
            $stmt->bind_param("sssi", $new_name, $new_email, $hashed_new_password, $user_id);
        } else {
            $stmt = $conn->prepare("
                UPDATE suggesters 
                SET full_name=?, email=? 
                WHERE suggester_id=?
            ");
            $stmt->bind_param("ssi", $new_name, $new_email, $user_id);
        }

        $stmt->execute();

        $_SESSION['full_name'] = $new_name;

        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// =====================
// NOTIFICATIONS (FIXED)
// =====================
$notification_count = 0;

if ($user_id > 0) {

    $notify = $conn->prepare("
        SELECT COUNT(*) as total_notify 
        FROM notifications 
        WHERE suggester_id = ? AND is_read = 0
    ");

    $notify->bind_param("i", $user_id);
    $notify->execute();

    $notification_count = $notify->get_result()->fetch_assoc()['total_notify'] ?? 0;
}

?>

<style>

/* =======================
   TOPBAR DESIGN
======================= */

.topbar{
    background:white;
    padding:12px 20px;
    margin-left:220px;
    box-shadow:0 3px 12px rgba(0,0,0,0.05);
    border-radius:10px;
}

.profile-box{
    position:relative;
}

.profile-card{
    display:none;
    position:absolute;
    right:0;
    top:45px;
    width:200px;
    background:white;
    box-shadow:0 5px 20px rgba(0,0,0,0.15);
    border-radius:10px;
    overflow:hidden;
    z-index:999;
}

.profile-card a{
    display:block;
    padding:10px;
    text-decoration:none;
    color:black;
    border-bottom:1px solid gainsboro;
}

.profile-card a:hover{
    background:darkblue;
    color:white;
}

.profile-letter{
    width:40px;
    height:40px;
    border-radius:50%;
    border:none;
    background:darkblue;
    color:white;
    font-weight:bold;
    font-size:18px;
    text-transform:uppercase;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
}

.profile-modal{
    display:none;
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.3);
    justify-content:center;
    align-items:center;
    z-index:9999;
}

.profile-box-form{
    background:white;
    padding:20px;
    width:350px;
    border-radius:12px;
    box-shadow:0 5px 20px rgba(0,0,0,0.2);
}

.profile-box-form input{
    width:100%;
    padding:10px;
    margin-bottom:10px;
    border:1px solid gray;
    border-radius:8px;
}

.btn-primary{
    width:100%;
    padding:10px;
    border:none;
    background:darkblue;
    color:white;
    border-radius:8px;
}

.btn-primary:hover{
    background:blue;
}

</style>

<div class="topbar d-flex justify-content-between align-items-center">

    <h5>
        Welcome, <?php echo htmlspecialchars($fullname); ?>
    </h5>

    <div class="d-flex gap-3 align-items-center">

        <!-- NOTIFICATIONS -->
        <a href="notifications.php" class="btn btn-light position-relative">

            <i class="fas fa-bell"></i>

            <?php if ($notification_count > 0) { ?>
                <span class="badge bg-danger position-absolute top-0 start-100">
                    <?php echo $notification_count; ?>
                </span>
            <?php } ?>

        </a>

        <!-- PROFILE -->
        <div class="profile-box">

            <button class="profile-letter" onclick="toggleProfile()">
                <?php echo strtoupper(substr($fullname, 0, 1)); ?>
            </button>

            <div class="profile-card" id="profileCard">

                <a href="#" onclick="openProfile()">
                    <i class="fas fa-user-edit"></i> Edit Profile
                </a>

                <a href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>

            </div>

        </div>

    </div>
</div>

<!-- PROFILE MODAL -->
<div class="profile-modal" id="profileModal">

    <div class="profile-box-form">

        <h5>Edit Profile</h5>

        <?php if ($message != "") { ?>
            <div style="color:red; margin-bottom:10px;">
                <?php echo $message; ?>
            </div>
        <?php } ?>

        <form method="POST">

            <input type="text" name="full_name" value="<?php echo $fullname; ?>" required>
            <input type="email" name="email" value="<?php echo $email; ?>" required>

            <hr>

            <small>Change Password (optional)</small>

            <input type="password" name="old_password" placeholder="Old Password">
            <input type="password" name="new_password" placeholder="New Password">

            <button type="submit" name="update_profile" class="btn-primary">
                Save Changes
            </button>

        </form>

    </div>

</div>

<script>

function toggleProfile(){
    let card = document.getElementById("profileCard");
    card.style.display = (card.style.display === "block") ? "none" : "block";
}

function openProfile(){
    document.getElementById("profileModal").style.display = "flex";
    document.getElementById("profileCard").style.display = "none";
}

window.onclick = function(e){
    let modal = document.getElementById("profileModal");
    if(e.target === modal){
        modal.style.display = "none";
    }
}

</script>