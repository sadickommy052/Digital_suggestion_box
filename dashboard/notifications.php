<?php
session_start();
include("../config/db.php");

$user_id = $_SESSION['user_id'] ?? 0;

if ($user_id == 0) {
    header("Location: ../login.php");
    exit();
}

/* =========================
   OPTIONAL: MARK AS READ
========================= */
$update = $conn->prepare("
    UPDATE notifications
    SET is_read = 1
    WHERE user_id = ?
");
$update->bind_param("i", $user_id);
$update->execute();

/* =========================
   FETCH NOTIFICATIONS
========================= */
$stmt = $conn->prepare("
    SELECT *
    FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Notifications</title>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>

/* ===== PAGE BACKGROUND ===== */
body{
    margin:0;
    font-family:Segoe UI;
    background:#f1f5f9;
    padding:20px;
}

/* ===== TITLE ===== */
.title{
    text-align:center;
    margin-bottom:25px;
}

.title h2{
    color:#1e293b;
}

/* ===== GRID ===== */
.container{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(300px,1fr));
    gap:20px;
    max-width:1100px;
    margin:auto;
}

/* ===== CARD ===== */
.card{
    background:white;
    border-radius:15px;
    padding:20px;
    box-shadow:0 5px 20px rgba(0,0,0,0.08);
    transition:0.3s;
    position:relative;
}

.card:hover{
    transform:translateY(-5px);
    box-shadow:0 10px 30px rgba(0,0,0,0.15);
}

/* ICON */
.icon{
    width:50px;
    height:50px;
    border-radius:50%;
    background:#dbeafe;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#2563eb;
    font-size:20px;
    margin-bottom:10px;
}

/* TITLE */
.card h3{
    margin:0;
    color:#0f172a;
    font-size:18px;
}

/* MESSAGE */
.card p{
    color:#475569;
    font-size:14px;
    margin:8px 0;
    line-height:1.5;
}

/* TIME */
.time{
    font-size:12px;
    color:#94a3b8;
}

/* NEW BADGE */
.badge{
    position:absolute;
    top:15px;
    right:15px;
    background:#ef4444;
    color:white;
    padding:5px 10px;
    border-radius:20px;
    font-size:12px;
}

/* EMPTY */
.empty{
    text-align:center;
    color:gray;
    margin-top:60px;
}

</style>
</head>

<body>

<div class="title">
    <h2><i class="fas fa-bell"></i> Notifications</h2>
</div>

<div class="container">

<?php if ($result->num_rows > 0): ?>

    <?php while ($row = $result->fetch_assoc()): ?>

        <div class="card">

            <!-- ICON -->
            <div class="icon">
                <i class="fas fa-bell"></i>
            </div>

            <!-- CONTENT -->
            <h3><?php echo htmlspecialchars($row['title']); ?></h3>

            <p><?php echo htmlspecialchars($row['message']); ?></p>

            <div class="time">
                <i class="fas fa-clock"></i>
                <?php echo $row['created_at']; ?>
            </div>

            <!-- NEW BADGE -->
            <?php if ($row['is_read'] == 0): ?>
                <div class="badge">New</div>
            <?php endif; ?>

        </div>

    <?php endwhile; ?>

<?php else: ?>

    <div class="empty">
        No notifications found
    </div>

<?php endif; ?>

</div>

</body>
</html>