<?php
// SAFE SESSION CHECK
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
}

.sidebar h3{
    color:white;
    text-align:center;
    margin-bottom:20px;
}

.sidebar a{
    display:block;
    padding:10px;
    color:#d1d5db;
    text-decoration:none;
    border-radius:8px;
    margin-bottom:5px;
}

.sidebar a:hover{
    background:#1f2937;
    color:white;
}

</style>

<div class="sidebar">

<h3>Menu</h3>

<!-- ADMIN MENU -->
<?php if ($role == 'admin') { ?>

    <a href="../admin/admin_dashboard.php">
        Dashboard
    </a>

    <a href="../admin/manage_users.php">
        Manage User Accounts
    </a>

    <a href="../admin/system_settings.php">
        System Settings
    </a>

    <a href="../admin/system_activity.php">
        System Activity
    </a>

    <a href="../admin/backup_system.php">
        Backup & Maintenance
    </a>

<?php } ?>


<!-- MANAGER MENU -->
<?php if ($role == 'suggestion_manager') { ?>

    <a href="../manager/manager_dashboard.php">
        Manager Dashboard
    </a>

    <a href="../manager/all_suggestions.php">
        All Suggestions
    </a>

    <a href="../manager/categories.php">
        Manage Categories
    </a>

    <a href="../manager/reports.php">
        Reports
    </a>

<?php } ?>


<!-- SUGGESTER MENU -->
<?php if ($role == 'suggester') { ?>

    <a href="../dashboard/suggester_dashboard.php">
        My Dashboard
    </a>

    <a href="../dashboard/submit_suggestion.php">
        Submit Suggestion
    </a>

    <a href="../dashboard/my_suggestions.php">
        My Suggestions
    </a>

<?php } ?>


<hr style="background:#374151;">


<!-- LOGOUT -->
<a href="../dashboard/logout.php">
    Logout
</a>

</div>