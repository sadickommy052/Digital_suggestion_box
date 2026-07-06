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
    padding-bottom:15px;
    border-bottom:1px solid #374151;
}

.profile h4{
    margin:0;
    font-size:16px;
    font-weight:600;
    color:#ffffff;
}

.profile small{
    color:#9ca3af;
    font-size:12px;
    display:block;
    margin-top:4px;
}

/* MENU TITLE */
.menu-title{
    font-size:12px;
    font-weight:600;
    color:#6b7280;
    text-transform:uppercase;
    letter-spacing:1px;
    margin-bottom:15px;
    padding-left:10px;
}

/* LINKS */
.sidebar a{
    display:flex;
    align-items:center;
    gap:12px;
    padding:10px 12px;
    color:#d1d5db;
    text-decoration:none;
    border-radius:8px;
    margin-bottom:4px;
    transition:all 0.2s ease;
}

.sidebar a i{
    width:20px;
    text-align:center;
    font-size:16px;
    color:#9ca3af;
}

.sidebar a span{
    font-size:14px;
}

.sidebar a:hover{
    background:#1f2937;
    color:white;
    transform:translateX(4px);
}

.sidebar a:hover i{
    color:#ffffff;
}

/* ACTIVE LINK */
.sidebar a.active{
    background:#1e3a8a;
    color:white;
}

.sidebar a.active i{
    color:white;
}

hr{
    border:0;
    height:1px;
    background:#374151;
    margin:12px 0;
}

.logout{
    margin-top:auto;
}

.logout a{
    background:#1f2937;
}

.logout a:hover{
    background:#dc2626;
    color:white;
}

.logout a:hover i{
    color:white;
}

/* SCROLLBAR */
.sidebar::-webkit-scrollbar{
    width:4px;
}

.sidebar::-webkit-scrollbar-thumb{
    background:#374151;
    border-radius:10px;
}

</style>

<div class="sidebar">

    <!-- PROFILE -->
   <div class="profile">
    <h4><i class="fas fa-box"></i> Digital Suggestion Box</h4>
    <small><i class="fas fa-user"></i> suggester</small>
    
    
    </a>
</div>
    <!-- MENU -->
    <div class="menu-title">Menu</div>

    <?php if ($role == 'admin') { ?>

        <a href="/Digital_suggestion_box/admin/admin_dashboard.php">
            <i class="fas fa-gauge-high"></i>
            <span>Dashboard</span>
        </a>

        <a href="/Digital_suggestion_box/admin/manage_users.php">
            <i class="fas fa-users"></i>
            <span>Manage Users</span>
        </a>

        <a href="/Digital_suggestion_box/admin/system_settings.php">
            <i class="fas fa-gear"></i>
            <span>Settings</span>
        </a>

        <a href="/Digital_suggestion_box/admin/system_activity.php">
            <i class="fas fa-chart-line"></i>
            <span>Activity</span>
        </a>

        <a href="/Digital_suggestion_box/admin/backup_system.php">
            <i class="fas fa-database"></i>
            <span>Backup</span>
        </a>

    <?php } ?>

    <?php if ($role == 'suggestion_manager') { ?>

        <a href="/Digital_suggestion_box/manager/manager_dashboard.php">
            <i class="fas fa-gauge-high"></i>
            <span>Dashboard</span>
        </a>

        <a href="/Digital_suggestion_box/manager/all_suggestions.php">
            <i class="fas fa-comments"></i>
            <span>Suggestions</span>
        </a>

        <a href="/Digital_suggestion_box/manager/categories.php">
            <i class="fas fa-list"></i>
            <span>Categories</span>
        </a>

        <a href="/Digital_suggestion_box/manager/reports.php">
            <i class="fas fa-file-lines"></i>
            <span>Reports</span>
        </a>

    <?php } ?>

    <?php if ($role == 'suggester') { ?>

         <a href="/Digital_suggestion_box/manager/manager_dashboard.php">
            <i class="fas fa-gauge-high"></i>
            <span>Dashboard</span>
        </a>

        <a href="/Digital_suggestion_box/dashboard/submit_suggestion.php">
            <i class="fas fa-pen-to-square"></i>
            <span>Submit</span>
        </a>

        <a href="/Digital_suggestion_box/dashboard/my_suggestions.php">
            <i class="fas fa-list-check"></i>
            <span>My Suggestions</span>
        </a>

    <?php } ?>

    <hr>
    

</div>