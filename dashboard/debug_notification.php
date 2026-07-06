<?php
session_start();
include("../config/db.php");

echo "<h1>🔍 NOTIFICATION DEBUG</h1>";

// Check all users
echo "<h2>📋 All Users:</h2>";
$users = $conn->query("SELECT user_id, full_name, role FROM users");
echo "<table border='1' cellpadding='8'>";
echo "<tr><th>User ID</th><th>Name</th><th>Role</th></tr>";
while($u = $users->fetch_assoc()){
    echo "<tr><td>{$u['user_id']}</td><td>{$u['full_name']}</td><td>{$u['role']}</td></tr>";
}
echo "</table>";

// Check all suggestions
echo "<h2>📝 All Suggestions:</h2>";
$sugs = $conn->query("SELECT suggestion_id, user_id, title, status FROM suggestions ORDER BY suggestion_id DESC");
echo "<table border='1' cellpadding='8'>";
echo "<tr><th>Suggestion ID</th><th>Owner (user_id)</th><th>Title</th><th>Status</th></tr>";
while($s = $sugs->fetch_assoc()){
    echo "<tr><td>{$s['suggestion_id']}</td><td>{$s['user_id']}</td><td>{$s['title']}</td><td>{$s['status']}</td></tr>";
}
echo "</table>";

// Check ALL notifications
echo "<h2>🔔 All Notifications:</h2>";
$notifs = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC");
if($notifs->num_rows > 0){
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>ID</th><th>User ID</th><th>Title</th><th>Message</th><th>Type</th><th>is_read</th><th>Created</th></tr>";
    while($n = $notifs->fetch_assoc()){
        $read = $n['is_read'] ? '✅ Read' : '❌ UNREAD';
        echo "<tr>
                <td>{$n['notification_id']}</td>
                <td><strong>{$n['user_id']}</strong></td>
                <td>{$n['title']}</td>
                <td>{$n['message']}</td>
                <td>{$n['type']}</td>
                <td>$read</td>
                <td>{$n['created_at']}</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "❌ No notifications found!";
}

// Check if notifications table exists
echo "<h2>📊 Table Check:</h2>";
$table_check = $conn->query("SHOW TABLES LIKE 'notifications'");
if($table_check->num_rows > 0){
    echo "✅ Notifications table exists<br>";
    
    // Show table structure
    $structure = $conn->query("DESCRIBE notifications");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while($col = $structure->fetch_assoc()){
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>{$col['Default']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "❌ Notifications table does NOT exist!";
}

// If user is logged in, show their notifications
if(isset($_SESSION['user_id'])){
    $user_id = $_SESSION['user_id'];
    echo "<h2>👤 Your Notifications (user_id: $user_id):</h2>";
    $my_notifs = $conn->query("SELECT * FROM notifications WHERE user_id = $user_id ORDER BY created_at DESC");
    if($my_notifs->num_rows > 0){
        echo "<table border='1' cellpadding='8'>";
        echo "<tr><th>ID</th><th>Title</th><th>Message</th><th>is_read</th><th>Created</th></tr>";
        while($mn = $my_notifs->fetch_assoc()){
            $read = $mn['is_read'] ? '✅ Read' : '❌ UNREAD';
            echo "<tr>
                    <td>{$mn['notification_id']}</td>
                    <td>{$mn['title']}</td>
                    <td>{$mn['message']}</td>
                    <td>$read</td>
                    <td>{$mn['created_at']}</td>
                  </tr>";
        }
        echo "</table>";
    } else {
        echo "❌ No notifications for your user ID";
    }
}
?>