<?php
if(session_status()===PHP_SESSION_NONE){
    session_start();
}

include(__DIR__."/../config/db.php");

$user_id = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['role'] ?? '';

$fullname = "Guest";
$profile_picture = "";
$notifications = [];
$unread_count = 0;

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

/* ================= GET ALL NOTIFICATIONS (READ + UNREAD) ================= */
$notif_stmt = $conn->prepare("
    SELECT notification_id, title, message, type, is_read, created_at 
    FROM notifications 
    WHERE user_id=?
    ORDER BY created_at DESC 
    LIMIT 10
");
$notif_stmt->bind_param("i", $user_id);
$notif_stmt->execute();
$notif_result = $notif_stmt->get_result();

$notifications = [];
while($row = $notif_result->fetch_assoc()){
    $notifications[] = $row;
}
$notif_stmt->close();

/* ================= GET UNREAD COUNT ================= */
$unread_stmt = $conn->prepare("
    SELECT COUNT(*) as unread_count 
    FROM notifications 
    WHERE user_id=? AND is_read=0
");
$unread_stmt->bind_param("i", $user_id);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result();
$unread_data = $unread_result->fetch_assoc();
$unread_count = $unread_data['unread_count'] ?? 0;
$unread_stmt->close();
}

/* ================= FIX IMAGE PATH ================= */
$img = !empty($profile_picture)
    ? "/Digital_suggestion_box/" . $profile_picture
    : "";

// ================= BASE PATH FOR LINKS =================
$base_path = "/Digital_suggestion_box/dashboard/";
    
?>

<style>

/* ================= TOPBAR ================= */
.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:15px 30px;
    background:#111827;
    color:#fff;
    position:fixed;
    top:0;
    left:253px;
    
    right: 0;
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
    position:relative;
}

.notif-badge{
    position:absolute;
    top:-5px;
    right:-5px;
    background:#dc2626;
    color:white;
    border-radius:50%;
    padding:2px 6px;
    font-size:10px;
    font-weight:bold;
    min-width:18px;
    text-align:center;
}

.notif-dropdown{
    display:none;
    position:absolute;
    right:0;
    top:45px;
    width:350px;
    max-height:400px;
    overflow-y:auto;
    background:white;
    color:black;
    border-radius:10px;
    box-shadow:0 10px 25px rgba(0,0,0,0.2);
    z-index:99999;
}

.notif-dropdown::-webkit-scrollbar{
    width:5px;
}

.notif-dropdown::-webkit-scrollbar-thumb{
    background:#888;
    border-radius:10px;
}

.notif-header{
    padding:12px 15px;
    font-weight:bold;
    background:#1e3a8a;
    color:white;
    display:flex;
    justify-content:space-between;
    align-items:center;
    position:sticky;
    top:0;
    z-index:1;
}

.notif-header .mark-all{
    color:#93c5fd;
    font-size:12px;
    text-decoration:none;
    cursor:pointer;
}

.notif-header .mark-all:hover{
    text-decoration:underline;
}

.notif-link{
    text-decoration:none;
    color:inherit;
    display:block;
}

.notif-link:hover .notif-item{
    background:#f3f4f6;
}

.notif-link:hover .notif-item.unread{
    background:#dbeafe;
}

.notif-item{
    padding:12px 15px;
    border-bottom:1px solid #f0f0f0;
    display:flex;
    align-items:flex-start;
    gap:10px;
    transition:background 0.2s;
}

.notif-item.unread{
    background:#f0f7ff;
}

.notif-item.read{
    opacity:0.7;
}

.notif-icon{
    font-size:16px;
    min-width:24px;
    margin-top:2px;
}

.notif-content{
    flex:1;
}

.notif-title{
    font-size:14px;
    font-weight:600;
    color:#111;
}

.notif-message{
    font-size:13px;
    color:#555;
    line-height:1.4;
    margin-top:2px;
}

.notif-time{
    font-size:11px;
    color:#999;
    margin-top:3px;
    display:block;
}

.notif-empty{
    padding:30px 20px;
    text-align:center;
    color:#999;
}

.notif-empty i{
    font-size:30px;
    display:block;
    margin-bottom:10px;
    color:#d1d5db;
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

/* ================= RESPONSIVE ================= */
@media(max-width:900px){
    .topbar{
        left:0;
        padding:15px;
    }
    
    .notif-dropdown{
        width:300px;
        right:-10px;
    }
    
    .welcome{
        font-size:14px;
    }
}

@media(max-width:600px){
    .notif-dropdown{
        width:280px;
        right:-20px;
    }
    
    .welcome{
        display:none;
    }
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
                <?php if(isset($unread_count) && $unread_count > 0){ ?>
                    <span class="notif-badge"><?=$unread_count?></span>
                <?php } ?>
            </button>

            <div id="notifDropdown" class="notif-dropdown">

                <div class="notif-header">
                    <span><i class="fas fa-bell"></i> Notifications (<?=$unread_count?> unread)</span>
                    <?php if(isset($unread_count) && $unread_count > 0){ ?>
                        <a href="<?=$base_path?>mark_all_read.php" class="mark-all">
                            Mark all as read
                        </a>
                    <?php } ?>
                </div>

                <?php if(isset($notifications) && count($notifications) > 0){ ?>
                    
                    <?php foreach($notifications as $notif){ 
                        $icon = $notif['type'] == 'suggestion_approved' ? 'fa-check-circle' : 
                                ($notif['type'] == 'suggestion_rejected' ? 'fa-times-circle' : 
                                ($notif['type'] == 'suggestion_implemented' ? 'fa-check-double' : 
                                ($notif['type'] == 'new_suggestion' ? 'fa-plus-circle' : 'fa-trash')));
                        
                        $color = $notif['type'] == 'suggestion_approved' ? '#16a34a' : 
                                ($notif['type'] == 'suggestion_rejected' ? '#dc2626' : 
                                ($notif['type'] == 'suggestion_implemented' ? '#2563eb' : 
                                ($notif['type'] == 'new_suggestion' ? '#f59e0b' : '#6b7280')));
                        
                        $is_read = $notif['is_read'] ? 'read' : 'unread';
                    ?>
                        <a href="<?=$base_path?>mark_read.php?id=<?=$notif['notification_id']?>" class="notif-link">
                            <div class="notif-item <?=$is_read?>">
                                
                                <div class="notif-icon">
                                    <i class="fas <?=$icon?>" style="color:<?=$color?>;"></i>
                                </div>
                                
                                <div class="notif-content">
                                    <div class="notif-title"><?=htmlspecialchars($notif['title'])?></div>
                                    <div class="notif-message"><?=htmlspecialchars($notif['message'])?></div>
                                    <span class="notif-time">
                                        <?=date('M j, g:i A', strtotime($notif['created_at']))?>
                                    </span>
                                </div>
                                
                                <?php if(!$notif['is_read']){ ?>
                                    <span style="background:#2563eb;border-radius:50%;width:8px;height:8px;display:inline-block;min-width:8px;margin-top:5px;"></span>
                                <?php } ?>
                            </div>
                        </a>
                    <?php } ?>
                    
                <?php }else{ ?>
                    <div class="notif-empty">
                        <i class="fas fa-bell-slash"></i>
                        <p>No notifications yet</p>
                    </div>
                <?php } ?>

            </div>

        </div>

        <!-- PROFILE -->
        <div class="profile-wrapper">

            <div id="profileBtn">

                <?php if(!empty($profile_picture)){ ?>
                    <img src="<?=htmlspecialchars($img)?>" class="profile-image" alt="Profile">
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

/* ================= TOGGLE DROPDOWNS ================= */
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
    if(notifDropdown) notifDropdown.style.display = "none";
});
}

/* NOTIF TOGGLE */
if(notifBtn){
notifBtn.addEventListener("click", function(e){
    e.stopPropagation();
    notifDropdown.style.display =
        (notifDropdown.style.display === "block") ? "none" : "block";
    if(profileDropdown) profileDropdown.style.display = "none";
});
}

/* CLOSE OUTSIDE */
window.addEventListener("click", function(){
    if(profileDropdown) profileDropdown.style.display = "none";
    if(notifDropdown) notifDropdown.style.display = "none";
});

});

</script>