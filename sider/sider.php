<?php
if(session_status()===PHP_SESSION_NONE){
    session_start();
}

$role = $_SESSION['role'] ?? '';
?>

<style>

.sidebar{
    width:220px;
    height:100vh;
    position:fixed;
    top:0;
    left:0;
    background:#111827;
    padding:15px;
    color:white;
    display:flex;
    flex-direction:column;
}

/* PROFILE (NOW SYSTEM NAME ONLY) */
.profile{
    text-align:center;
    margin-bottom:20px;
}

.profile h4{
    margin:0;
    font-size:14px;
}

.profile small{
    color:#9ca3af;
    font-size:12px;
}

/* LINKS */
.sidebar a{
    display:flex;
    align-items:center;
    gap:10px;
    padding:10px;
    color:#d1d5db;
    text-decoration:none;
    border-radius:8px;
    margin-bottom:5px;
    transition:0.2s;
}

.sidebar a i{
    width:18px;
    text-align:center;
}

.sidebar a:hover{
    background:#1f2937;
    color:white;
    transform:translateX(3px);
}

hr{
    border:0;
    height:1px;
    background:#374151;
    margin:10px 0;
}

.logout{
    margin-top:auto;
    background:#1f2937;
}

.logout:hover{
    background:#ef4444;
}

</style>

<div class="sidebar">

<div class="profile">

    <h4>Digital Suggestion Box</h4>

    <small><?=htmlspecialchars($role)?></small>

</div>

<h3 style="text-align:center;">Menu</h3>

<?php if ($role == 'admin') { ?>

    <a href="/Digital_suggestion_box/admin/admin_dashboard.php">
        <i class="fas fa-gauge"></i> Dashboard
    </a>

    <a href="/Digital_suggestion_box/admin/manage_users.php">
        <i class="fas fa-users"></i> Manage Users
    </a>

    <a href="/Digital_suggestion_box/admin/system_settings.php">
        <i class="fas fa-gear"></i> Settings
    </a>

    <a href="/Digital_suggestion_box/admin/system_activity.php">
        <i class="fas fa-chart-line"></i> Activity
    </a>

    <a href="/Digital_suggestion_box/admin/backup_system.php">
        <i class="fas fa-database"></i> Backup
    </a>

<?php } ?>

<?php if ($role == 'suggestion_manager') { ?>

    <a href="/Digital_suggestion_box/manager/manager_dashboard.php">
        <i class="fas fa-gauge"></i> Dashboard
    </a>

    <a href="/Digital_suggestion_box/manager/all_suggestions.php">
        <i class="fas fa-comments"></i> Suggestions
    </a>

    <a href="/Digital_suggestion_box/manager/categories.php">
        <i class="fas fa-list"></i> Categories
    </a>

    <a href="/Digital_suggestion_box/manager/reports.php">
        <i class="fas fa-file-lines"></i> Reports
    </a>

<?php } ?>

<?php if ($role == 'suggester') { ?>

    <a href="/Digital_suggestion_box/dashboard/suggester_dashboard.php">
        <i class="fas fa-house"></i> Dashboard
    </a>

    <a href="/Digital_suggestion_box/dashboard/submit_suggestion.php">
        <i class="fas fa-pen-to-square"></i> Submit
    </a>

    <a href="/Digital_suggestion_box/dashboard/my_suggestions.php">
        <i class="fas fa-list-check"></i> My Suggestions
    </a>

<?php } ?>

<hr>


</div>