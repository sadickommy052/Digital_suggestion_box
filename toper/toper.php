<?php
if(session_status()===PHP_SESSION_NONE){
    session_start();
}

include(__DIR__."/../config/db.php");

$user_id = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['role'] ?? '';

$fullname = "Guest";
$profile_picture = "";

/* ================= GET USER ================= */
if($user_id > 0 && isset($conn)){

$stmt = $conn->prepare("
SELECT full_name, profile_picture 
FROM users 
WHERE user_id=? 
LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

if($row = $res->fetch_assoc()){
    $fullname = $row['full_name'];
    $profile_picture = $row['profile_picture'];
}

$stmt->close();
}

/* ================= FIX IMAGE PATH ================= */
$img = !empty($profile_picture)
    ? "/Digital_suggestion_box/" . $profile_picture
    : "";
    
?>

<style>

/* ================= TOPBAR ================= */
.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:15px 25px;
    background:#111827;
    color:#fff;
    position:fixed;
    top:0;
    left:210px;
    
    right:0;
    height:60px;
    z-index:9999;
}

.right{
    display:flex;
    gap:15px;
    align-items:center;
}

/* ================= NOTIFICATIONS ================= */
.notif-wrapper{
    position:relative;
}

.notif-btn{
    background:#1f2937;
    border:1px solid #374151;
    color:#fff;
    padding:10px;
    border-radius:10px;
    cursor:pointer;
}

.notif-dropdown{
    display:none;
    position:absolute;
    right:0;
    top:45px;
    width:300px;
    background:white;
    color:black;
    border-radius:10px;
    box-shadow:0 10px 25px rgba(0,0,0,0.2);
    z-index:99999;
}

/* ================= PROFILE ================= */
.profile-wrapper{
    position:relative;
    cursor:pointer;
}

.profile-image,
.profile-letter{
    width:40px;
    height:40px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
}

.profile-image{
    width:40px;
    height:40px;
    border-radius:50%;
    object-fit:cover;
    display:block;
    border:2px solid #374151;
}

.profile-letter{
    background:#1e3a8a;
    color:white;
    font-weight:bold;
}

/* ================= DROPDOWN ================= */
.profile-dropdown{
    display:none;
    position:absolute;
    right:0;
    top:45px;
    width:200px;
    background:white;
    border-radius:10px;
    overflow:hidden;
    box-shadow:0 10px 25px rgba(0,0,0,0.2);
    z-index:99999;
}

.profile-info{
    padding:12px;
    background:#1e3a8a;
    color:white;
}

.profile-info small{
    display:block;
    font-size:11px;
    opacity:0.8;
}

.profile-dropdown a{
    display:flex;
    gap:10px;
    padding:10px;
    text-decoration:none;
    color:#111;
    font-size:13px;
}

.profile-dropdown a:hover{
    background:#f3f4f6;
}

</style>

<div class="topbar">

    <!-- WELCOME -->
    <div class="welcome">
        <i class="fas fa-user-circle"></i>
        Welcome, <?=htmlspecialchars($fullname)?>
    </div>

    <div class="right">

        <!-- NOTIFICATIONS -->
        <div class="notif-wrapper">

            <button class="notif-btn" id="notifBtn">
                <i class="fas fa-bell"></i>
            </button>

            <div id="notifDropdown" class="notif-dropdown">
                <div style="padding:10px;font-weight:bold;background:#1e3a8a;color:white;">
                    Notifications
                </div>
                <div style="padding:10px;">
                    <p>No notifications</p>
                </div>
            </div>

        </div>

        <!-- PROFILE -->
        <div class="profile-wrapper">

            <div id="profileBtn">

                <?php if(!empty($img)){ ?>
                   <?php
$profile_img = !empty($profile_picture)
    ? "/Digital_suggestion_box/" . ltrim($profile_picture, "/")
    : "";
?>

<?php if(!empty($profile_img)){ ?>
    <img src="<?=htmlspecialchars($profile_img)?>" class="profile-image" alt="Profile">
<?php }else{ ?>
    <div class="profile-letter">
        <?=strtoupper(substr($fullname,0,1))?>
    </div>
<?php } ?>
                <?php }else{ ?>
                    <div class="profile-letter">
                        <?=strtoupper(substr($fullname,0,1))?>
                    </div>
                <?php } ?>

            </div>

            <!-- DROPDOWN -->
            <div id="profileDropdown" class="profile-dropdown">

                <div class="profile-info">
                    <b><?=htmlspecialchars($fullname)?></b>
                    <small><?=htmlspecialchars($role)?></small>
                </div>

                <a href="/Digital_suggestion_box/view.php">
                    <i class="fas fa-user"></i> My Profile
                </a>

                <a href="/Digital_suggestion_box/edit_profile.php">
                    <i class="fas fa-gear"></i> Settings
                </a>

                <a href="/Digital_suggestion_box/dashboard/logout.php">
                    <i class="fas fa-right-from-bracket"></i> Logout
                </a>

            </div>

        </div>

    </div>

</div>

<script>

document.addEventListener("DOMContentLoaded", function(){

const profileBtn = document.getElementById("profileBtn");
const profileDropdown = document.getElementById("profileDropdown");

const notifBtn = document.getElementById("notifBtn");
const notifDropdown = document.getElementById("notifDropdown");

/* PROFILE TOGGLE */
if(profileBtn){
profileBtn.addEventListener("click", function(e){
    e.stopPropagation();
    profileDropdown.style.display =
        (profileDropdown.style.display === "block") ? "none" : "block";
});
}

/* NOTIF TOGGLE */
if(notifBtn){
notifBtn.addEventListener("click", function(e){
    e.stopPropagation();
    notifDropdown.style.display =
        (notifDropdown.style.display === "block") ? "none" : "block";
});
}

/* CLOSE OUTSIDE */
window.addEventListener("click", function(){
    if(profileDropdown) profileDropdown.style.display = "none";
    if(notifDropdown) notifDropdown.style.display = "none";
});

});

</script>