<?php
session_start();
include("../config/db.php");

// =====================
// AUTH CHECK
// =====================
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../login.php");
    exit();
}

// =====================
// CREATE ACTIVITY LOGS TABLE IF NOT EXISTS
// =====================
$conn->query("
CREATE TABLE IF NOT EXISTS activity_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    username VARCHAR(100),
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// =====================
// GET FILTERS
// =====================
$search = $_GET['search'] ?? '';
$action_filter = $_GET['action'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

// =====================
// BUILD QUERY
// =====================
$sql = "SELECT * FROM activity_logs WHERE 1=1";
$params = [];
$types = "";

if($search != ""){
    $sql .= " AND (username LIKE ? OR action LIKE ? OR details LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

if($action_filter != ""){
    $sql .= " AND action LIKE ?";
    $params[] = "%$action_filter%";
    $types .= "s";
}

if($date_from != ""){
    $sql .= " AND DATE(created_at) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if($date_to != ""){
    $sql .= " AND DATE(created_at) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$sql .= " ORDER BY created_at DESC LIMIT ?";
$params[] = $limit;
$types .= "i";

$stmt = $conn->prepare($sql);
if($params){
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$logs = $stmt->get_result();

// =====================
// GET STATISTICS
// =====================
$total_logs = $conn->query("SELECT COUNT(*) as total FROM activity_logs")->fetch_assoc()['total'] ?? 0;

$today_logs = $conn->query("SELECT COUNT(*) as total FROM activity_logs WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['total'] ?? 0;

$unique_users = $conn->query("SELECT COUNT(DISTINCT user_id) as total FROM activity_logs WHERE user_id IS NOT NULL")->fetch_assoc()['total'] ?? 0;

// Get action types for filter
$action_types = $conn->query("SELECT DISTINCT action FROM activity_logs ORDER BY action");

// =====================
// DELETE LOGS
// =====================
if(isset($_GET['delete_all'])){
    $conn->query("DELETE FROM activity_logs");
    header("Location: system_activity.php?msg=cleared");
    exit();
}

if(isset($_GET['delete_old'])){
    $conn->query("DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    header("Location: system_activity.php?msg=old_cleared");
    exit();
}

$msg = $_GET['msg'] ?? '';
?>

<!DOCTYPE html>
<html>
<head>
<title>System Activity</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
/* ================= NO RESET - USIATHIRI SIDER ================= */
body{
    margin:0;
    font-family:'Segoe UI',sans-serif;
    background:#f8fafc;
    color:#1e293b;
}

.content{
    margin-left:250px;
    padding:30px;
    padding-top:100px;
    min-height:calc(100vh - 180px);
}

.header{
    background:#111827;
    color:white;
    padding:25px 30px;
    border-radius:16px;
    margin-bottom:25px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    gap:15px;
}

.header h2{
    margin:0;
    font-size:22px;
    font-weight:600;
}

.header h2 i{
    margin-right:10px;
    color:#9ca3af;
}

.header p{
    margin:5px 0 0 0;
    color:#9ca3af;
    font-size:14px;
}

/* ================= STATS CARDS ================= */
.stats-row{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:18px;
    margin-bottom:25px;
}

.stat-card{
    background:white;
    padding:20px;
    border-radius:12px;
    text-align:center;
    border:1px solid #e2e8f0;
    box-shadow:0 4px 12px rgba(0,0,0,0.05);
    transition:all 0.3s ease;
}

.stat-card:hover{
    transform:translateY(-4px);
    box-shadow:0 8px 25px rgba(0,0,0,0.1);
}

.stat-card i{
    font-size:28px;
    color:#2563eb;
    margin-bottom:8px;
    display:block;
}

.stat-card h6{
    margin:5px 0;
    color:#64748b;
    font-size:13px;
    font-weight:600;
    text-transform:uppercase;
    letter-spacing:0.5px;
}

.stat-card h3{
    margin:0;
    font-size:26px;
    font-weight:700;
    color:#111827;
}

/* ================= CARDS ================= */
.card{
    background:white;
    padding:25px;
    border-radius:16px;
    border:1px solid #e2e8f0;
    box-shadow:0 4px 12px rgba(0,0,0,0.06);
    margin-bottom:25px;
}

.card-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:20px;
    padding-bottom:15px;
    border-bottom:1px solid #e2e8f0;
    flex-wrap:wrap;
    gap:10px;
}

.card-header h3{
    margin:0;
    font-size:17px;
    font-weight:600;
    color:#111827;
}

.card-header h3 i{
    margin-right:8px;
    color:#2563eb;
}

/* ================= FILTER FORM ================= */
.filter-box{
    display:grid;
    grid-template-columns:2fr 1fr 1fr 1fr 0.8fr;
    gap:12px;
    align-items:end;
}

.filter-box .form-group{
    display:flex;
    flex-direction:column;
    gap:5px;
}

.filter-box .form-group label{
    font-size:12px;
    font-weight:600;
    color:#64748b;
    text-transform:uppercase;
    letter-spacing:0.5px;
}

.filter-box .form-group input,
.filter-box .form-group select{
    padding:10px 14px;
    border:1px solid #cbd5e1;
    border-radius:8px;
    font-size:14px;
    background:#f8fafc;
    transition:all 0.2s ease;
}

.filter-box .form-group input:focus,
.filter-box .form-group select:focus{
    outline:none;
    border-color:#111827;
    box-shadow:0 0 0 3px rgba(17,24,39,0.08);
    background:white;
}

/* ================= BUTTONS ================= */
.btn{
    padding:10px 20px;
    border:none;
    border-radius:8px;
    font-weight:600;
    font-size:14px;
    cursor:pointer;
    transition:all 0.2s ease;
    display:inline-flex;
    align-items:center;
    gap:8px;
    text-decoration:none;
}

.btn-primary{
    background:#111827;
    color:white;
}

.btn-primary:hover{
    background:#1f2937;
}

.btn-success{
    background:#22c55e;
    color:white;
}

.btn-success:hover{
    background:#16a34a;
}

.btn-danger{
    background:#dc2626;
    color:white;
}

.btn-danger:hover{
    background:#b91c1c;
}

.btn-warning{
    background:#f59e0b;
    color:white;
}

.btn-warning:hover{
    background:#d97706;
}

.btn-sm{
    padding:6px 12px;
    font-size:12px;
}

/* ================= TABLE ================= */
.table-responsive{
    overflow-x:auto;
}

table{
    width:100%;
    border-collapse:collapse;
    background:white;
}

th{
    background:#111827;
    color:white;
    padding:14px;
    text-align:left;
    font-weight:600;
    white-space:nowrap;
}

td{
    padding:14px;
    border-bottom:1px solid #e2e8f0;
    vertical-align:middle;
}

tr:hover{
    background:#f8fafc;
}

/* ================= LOG ENTRY ================= */
.log-icon{
    width:36px;
    height:36px;
    border-radius:50%;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    font-size:14px;
}

.log-icon.login{ background:#dbeafe; color:#2563eb; }
.log-icon.logout{ background:#fee2e2; color:#dc2626; }
.log-icon.suggestion{ background:#dcfce7; color:#22c55e; }
.log-icon.update{ background:#fef3c7; color:#f59e0b; }
.log-icon.delete{ background:#fee2e2; color:#dc2626; }
.log-icon.user{ background:#e0e7ff; color:#3730a3; }
.log-icon.system{ background:#f3e8ff; color:#8b5cf6; }

.log-action{
    font-weight:600;
    color:#111827;
}

.log-details{
    color:#64748b;
    font-size:13px;
}

.log-time{
    font-size:12px;
    color:#94a3b8;
    white-space:nowrap;
}

.log-user{
    font-weight:500;
    color:#111827;
}

.log-ip{
    font-size:12px;
    color:#94a3b8;
    font-family:monospace;
}

/* ================= EMPTY STATE ================= */
.empty-state{
    text-align:center;
    padding:60px 20px;
    color:#94a3b8;
}

.empty-state i{
    font-size:60px;
    color:#e2e8f0;
    display:block;
    margin-bottom:15px;
}

.empty-state h4{
    margin:0 0 10px 0;
    color:#1e293b;
}

/* ================= ALERT ================= */
.alert{
    padding:12px 16px;
    border-radius:8px;
    font-weight:600;
    font-size:14px;
    margin-bottom:20px;
    display:flex;
    align-items:center;
    gap:10px;
}

.alert-success{
    background:#dcfce7;
    color:#166534;
    border:1px solid #bbf7d0;
}

/* ================= RESPONSIVE ================= */
@media(max-width:992px){
    .stats-row{
        grid-template-columns:repeat(2,1fr);
    }
    .filter-box{
        grid-template-columns:1fr 1fr;
    }
}

@media(max-width:768px){
    .content{
        margin-left:0;
        padding:15px;
        padding-top:80px;
    }
    .stats-row{
        grid-template-columns:1fr;
    }
    .filter-box{
        grid-template-columns:1fr;
    }
    .header{
        flex-direction:column;
        align-items:flex-start;
    }
    .card-header{
        flex-direction:column;
        align-items:flex-start;
    }
}

@media(max-width:480px){
    .card{
        padding:15px;
    }
    .btn{
        width:100%;
        justify-content:center;
    }
}
</style>
</head>

<body>

<?php include("../sider/sider.php"); ?>
<?php include("../toper/toper.php"); ?>

<div class="content">

    <!-- HEADER -->
    <div class="header">
        <div>
            <h2><i class="fas fa-chart-line"></i> System Activity</h2>
            <p>Monitor all user activities and system events</p>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="?delete_old=1" class="btn btn-warning btn-sm" onclick="return confirm('Delete logs older than 30 days?')">
                <i class="fas fa-clock"></i> Delete Old Logs
            </a>
            <a href="?delete_all=1" class="btn btn-danger btn-sm" onclick="return confirm('Delete all activity logs? This cannot be undone!')">
                <i class="fas fa-trash"></i> Clear All
            </a>
        </div>
    </div>

    <!-- ALERT -->
    <?php if($msg == 'cleared'): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> All activity logs have been cleared.
        </div>
    <?php elseif($msg == 'old_cleared'): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> Logs older than 30 days have been deleted.
        </div>
    <?php endif; ?>

    <!-- STATS -->
    <div class="stats-row">
        <div class="stat-card">
            <i class="fas fa-list"></i>
            <h6>Total Activities</h6>
            <h3><?= number_format($total_logs) ?></h3>
        </div>
        <div class="stat-card">
            <i class="fas fa-calendar-day"></i>
            <h6>Today</h6>
            <h3><?= number_format($today_logs) ?></h3>
        </div>
        <div class="stat-card">
            <i class="fas fa-users"></i>
            <h6>Active Users</h6>
            <h3><?= number_format($unique_users) ?></h3>
        </div>
        <div class="stat-card">
            <i class="fas fa-clock"></i>
            <h6>Last Activity</h6>
            <h3>
                <?php
                $last = $conn->query("SELECT created_at FROM activity_logs ORDER BY created_at DESC LIMIT 1");
                if($last && $last->num_rows > 0){
                    $row = $last->fetch_assoc();
                    echo date('M j, Y g:i A', strtotime($row['created_at']));
                } else {
                    echo 'No activity';
                }
                ?>
            </h3>
        </div>
    </div>

    <!-- FILTERS -->
    <div class="card">
        <form method="GET" class="filter-box">
            <div class="form-group">
                <label><i class="fas fa-search"></i> Search</label>
                <input type="text" name="search" placeholder="Search user, action, details..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="form-group">
                <label><i class="fas fa-tag"></i> Action</label>
                <select name="action">
                    <option value="">All Actions</option>
                    <?php while($action = $action_types->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($action['action']) ?>" <?= $action_filter == $action['action'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($action['action']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label><i class="fas fa-calendar-alt"></i> From</label>
                <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
            </div>
            <div class="form-group">
                <label><i class="fas fa-calendar-alt"></i> To</label>
                <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
            </div>
            <div class="form-group" style="display:flex; gap:5px;">
                <button type="submit" class="btn btn-primary" style="width:100%;">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <a href="system_activity.php" class="btn btn-warning" style="width:100%;">
                    <i class="fas fa-undo"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <!-- LOGS TABLE -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-list"></i> Activity Logs</h3>
            <span style="color:#64748b;font-size:14px;">Showing <?= $logs->num_rows ?> of <?= number_format($total_logs) ?> logs</span>
        </div>

        <?php if($logs && $logs->num_rows > 0): ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>IP Address</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($log = $logs->fetch_assoc()): 
                        $icon_class = 'system';
                        $icon = 'fa-cog';
                        $action_lower = strtolower($log['action']);
                        
                        if(strpos($action_lower, 'login') !== false){
                            $icon_class = 'login';
                            $icon = 'fa-sign-in-alt';
                        } elseif(strpos($action_lower, 'logout') !== false){
                            $icon_class = 'logout';
                            $icon = 'fa-sign-out-alt';
                        } elseif(strpos($action_lower, 'suggestion') !== false || strpos($action_lower, 'submit') !== false){
                            $icon_class = 'suggestion';
                            $icon = 'fa-lightbulb';
                        } elseif(strpos($action_lower, 'update') !== false || strpos($action_lower, 'edit') !== false){
                            $icon_class = 'update';
                            $icon = 'fa-edit';
                        } elseif(strpos($action_lower, 'delete') !== false || strpos($action_lower, 'remove') !== false){
                            $icon_class = 'delete';
                            $icon = 'fa-trash';
                        } elseif(strpos($action_lower, 'user') !== false){
                            $icon_class = 'user';
                            $icon = 'fa-user';
                        }
                    ?>
                    <tr>
                        <td>
                            <span class="log-user">
                                <i class="fas fa-user"></i>
                                <?= htmlspecialchars($log['username'] ?? 'System') ?>
                            </span>
                        </td>
                        <td>
                            <span class="log-icon <?= $icon_class ?>">
                                <i class="fas <?= $icon ?>"></i>
                            </span>
                            <span class="log-action"><?= htmlspecialchars($log['action']) ?></span>
                        </td>
                        <td>
                            <div class="log-details"><?= htmlspecialchars($log['details'] ?? '-') ?></div>
                        </td>
                        <td>
                            <span class="log-ip"><?= htmlspecialchars($log['ip_address'] ?? '-') ?></span>
                        </td>
                        <td>
                            <div class="log-time">
                                <i class="fas fa-clock"></i>
                                <?= date('M j, Y g:i A', strtotime($log['created_at'])) ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h4>No Activity Logs Found</h4>
            <p>There are no activity logs yet. Activities will be recorded automatically when users interact with the system.</p>
            <br>
           
        </div>
        <?php endif; ?>
    </div>

</div>

<?php include("../footer/footer.php"); ?>

</body>
</html>