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
    left:265px;
    right:0;
    height:60px;
    z-index:9999;

    /* Add border */
    border:1px solid #374151;
    border-radius:12px;
}

.right{
    display:flex;
    gap:15px;
    align-items:center;
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
    width:320px;
    max-height:500px;
    overflow-y:auto;
    background:white;
    border-radius:10px;
    overflow:hidden;
    box-shadow:0 10px 25px rgba(0,0,0,0.2);
    z-index:99999;
}

.profile-dropdown::-webkit-scrollbar{
    width:5px;
}

.profile-dropdown::-webkit-scrollbar-thumb{
    background:#888;
    border-radius:10px;
}

.profile-info{
    padding:12px;
    background:#1e3a8a;
    color:white;
    position:sticky;
    top:0;
    z-index:1;
}

.profile-info small{
    display:block;
    font-size:11px;
    opacity:0.8;
}

/* ================= DROPDOWN MENU ITEMS ================= */
.dropdown-menu a{
    display:flex;
    gap:10px;
    padding:10px 15px;
    text-decoration:none;
    color:#111;
    font-size:13px;
    transition:background 0.2s;
    border-bottom:1px solid #f0f0f0;
}

.dropdown-menu a:hover{
    background:#f3f4f6;
}

.dropdown-menu a:last-child{
    border-bottom:none;
}

.dropdown-menu .logout{
    color:#dc2626;
}

.dropdown-menu .logout:hover{
    background:#fef2f2;
}

/* ================= NOTIFICATIONS SUB-DROPDOWN ================= */
.notif-sub-dropdown {
    display: none;
    background: #f8fafc;
    max-height: 300px;
    overflow-y: auto;
}

.notif-sub-dropdown.show {
    display: block;
}

.notif-sub-dropdown::-webkit-scrollbar{
    width:5px;
}

.notif-sub-dropdown::-webkit-scrollbar-thumb{
    background:#888;
    border-radius:10px;
}

.notif-item-link{
    text-decoration:none;
    color:inherit;
    display:block;
}

.notif-item-link:hover .notif-item{
    background:#e5e7eb;
}

.notif-item-link:hover .notif-item.unread{
    background:#dbeafe;
}

.notif-item{
    padding:10px 15px 10px 35px;
    border-bottom:1px solid #e5e7eb;
    display:flex;
    align-items:flex-start;
    gap:8px;
    transition:background 0.2s;
}

.notif-item.unread{
    background:#f0f7ff;
}

.notif-item.read{
    opacity:0.7;
}

.notif-item .notif-icon{
    font-size:14px;
    min-width:20px;
    margin-top:1px;
}

.notif-item .notif-content{
    flex:1;
}

.notif-item .notif-title{
    font-size:13px;
    font-weight:600;
    color:#111;
}

.notif-item .notif-message{
    font-size:12px;
    color:#555;
    line-height:1.3;
    margin-top:1px;
}

.notif-item .notif-time{
    font-size:10px;
    color:#999;
    margin-top:2px;
    display:block;
}

.notif-empty-sub{
    padding:20px;
    text-align:center;
    color:#999;
    font-size:13px;
}

.notif-empty-sub i{
    font-size:24px;
    display:block;
    margin-bottom:8px;
    color:#d1d5db;
}

/* ================= NOTIFICATION BADGE ON MENU ITEM ================= */
.notif-badge-icon {
    position: relative;
}

.notif-badge-icon .badge {
    position: absolute;
    top: -6px;
    right: -6px;
    background: #dc2626;
    color: white;
    border-radius: 50%;
    padding: 2px 6px;
    font-size: 10px;
    font-weight: bold;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

/* Fix for larger numbers */
.notif-badge-icon .badge.badge-large {
    min-width: 20px;
    padding: 2px 5px;
}

/* ================= RESPONSIVE ================= */
@media(max-width:900px){
    .topbar{
        left:0;
        padding:15px;
    }
    
    .profile-dropdown{
        width:280px;
        right:-10px;
    }
    
    .welcome{
        font-size:14px;
    }
}

@media(max-width:600px){
    .profile-dropdown{
        width:260px;
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

        <!-- PROFILE WITH NOTIFICATIONS INSIDE -->
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

            <!-- DROPDOWN WITH NOTIFICATIONS -->
            <div id="profileDropdown" class="profile-dropdown">

                <!-- PROFILE INFO -->
                <div class="profile-info">
                    <b><?=htmlspecialchars($fullname)?></b>
                    <small><?=htmlspecialchars($role)?></small>
                </div>

                <!-- MENU ITEMS -->
                <div class="dropdown-menu">
                    <!-- My Profile -->
                    <a href="/Digital_suggestion_box/view.php">
                        <i class="fas fa-user"></i> My Profile
                    </a>

                    <!-- Settings -->
                    <a href="/Digital_suggestion_box/edit_profile.php">
                        <i class="fas fa-gear"></i> Settings
                    </a>

                    <!-- Notifications (with sub-dropdown) -->
                    <a href="#" id="notifToggle" class="notif-badge-icon">
                        <i class="fas fa-bell"></i> Notifications
                        <?php if(isset($unread_count) && $unread_count > 0){ ?>
                            <span class="badge <?=($unread_count > 9) ? 'badge-large' : ''?>"><?=$unread_count?></span>
                        <?php } ?>
                        <i class="fas fa-chevron-right" style="margin-left:auto;font-size:11px;color:#999;"></i>
                    </a>
                    
                    <!-- Notification Sub-Dropdown -->
                    <div id="notifSubDropdown" class="notif-sub-dropdown">
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
                                <a href="<?=$base_path?>mark_read.php?id=<?=$notif['notification_id']?>" class="notif-item-link">
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
                                            <span style="background:#2563eb;border-radius:50%;width:6px;height:6px;display:inline-block;min-width:6px;margin-top:5px;"></span>
                                        <?php } ?>
                                    </div>
                                </a>
                            <?php } ?>
                            
                            <?php if(isset($unread_count) && $unread_count > 0){ ?>
                                <div style="padding:8px 15px;text-align:center;background:#f8fafc;border-top:1px solid #e5e7eb;">
                                    <a href="<?=$base_path?>mark_all_read.php" style="color:#1e3a8a;font-size:12px;text-decoration:none;">
                                        <i class="fas fa-check-double"></i> Mark all as read
                                    </a>
                                </div>
                            <?php } ?>
                            
                        <?php }else{ ?>
                            <div class="notif-empty-sub">
                                <i class="fas fa-bell-slash"></i>
                                <p>No notifications yet</p>
                            </div>
                        <?php } ?>
                    </div>

                    <!-- Logout -->
                    <a href="/Digital_suggestion_box/dashboard/logout.php" class="logout">
                        <i class="fas fa-right-from-bracket"></i> Logout
                    </a>
                </div>

            </div>

        </div>

    </div>

</div>

<script>

/* ================= TOGGLE DROPDOWN ================= */
document.addEventListener("DOMContentLoaded", function(){

const profileBtn = document.getElementById("profileBtn");
const profileDropdown = document.getElementById("profileDropdown");
const notifToggle = document.getElementById("notifToggle");
const notifSubDropdown = document.getElementById("notifSubDropdown");

/* PROFILE TOGGLE */
if(profileBtn){
profileBtn.addEventListener("click", function(e){
    e.stopPropagation();
    if (profileDropdown.style.display === "block") {
        profileDropdown.style.display = "none";
        notifSubDropdown.classList.remove("show");
    } else {
        profileDropdown.style.display = "block";
    }
});
}

/* NOTIFICATIONS TOGGLE */
if(notifToggle){
notifToggle.addEventListener("click", function(e){
    e.preventDefault();
    e.stopPropagation();
    notifSubDropdown.classList.toggle("show");
    
    // Rotate chevron
    const chevron = this.querySelector('.fa-chevron-right');
    if(chevron) {
        chevron.style.transform = notifSubDropdown.classList.contains("show") ? "rotate(90deg)" : "rotate(0deg)";
        chevron.style.transition = "transform 0.3s";
    }
});
}

/* CLOSE OUTSIDE */
window.addEventListener("click", function(e){
    if(profileDropdown && !profileDropdown.contains(e.target) && e.target !== profileBtn && !profileBtn.contains(e.target)){
        profileDropdown.style.display = "none";
        if(notifSubDropdown) {
            notifSubDropdown.classList.remove("show");
            const chevron = document.querySelector('.fa-chevron-right');
            if(chevron) {
                chevron.style.transform = "rotate(0deg)";
            }
        }
    }
});

});

</script>