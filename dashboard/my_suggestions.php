<?php
// ================= WEKA TIMEZONE MWANZO =================
date_default_timezone_set('Africa/Dar_es_Salaam');

session_start();
include("../config/db.php");

ini_set('display_errors', 1);
error_reporting(E_ALL);

/* ================= PROTECTION ================= */
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../login.php");
    exit();
}
if ($_SESSION['role'] !== 'suggester') {
    header("Location: ../login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

/* ================= DELETE FILE ================= */
if (isset($_GET['delete_file'])) {

    $file_id = (int) $_GET['delete_file'];

    $stmt = $conn->prepare("SELECT file_path FROM attachments WHERE attachment_id = ?");
    $stmt->bind_param("i", $file_id);
    $stmt->execute();
    $file = $stmt->get_result()->fetch_assoc();

    if ($file) {
        $fullPath = "../" . $file['file_path'];

        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        $stmt = $conn->prepare("DELETE FROM attachments WHERE attachment_id = ?");
        $stmt->bind_param("i", $file_id);
        $stmt->execute();
    }

    header("Location: my_suggestions.php");
    exit();
}

/* ================= DELETE SUGGESTION ================= */
if (isset($_GET['delete_suggestion'])) {

    $id = (int) $_GET['delete_suggestion'];

    $att = $conn->prepare("SELECT file_path FROM attachments WHERE suggestion_id = ?");
    $att->bind_param("i", $id);
    $att->execute();
    $files = $att->get_result();

    while ($f = $files->fetch_assoc()) {
        $fullPath = "../" . $f['file_path'];
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }

    $stmt = $conn->prepare("DELETE FROM attachments WHERE suggestion_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $stmt = $conn->prepare("DELETE FROM suggestions WHERE suggestion_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();

    header("Location: my_suggestions.php");
    exit();
}

/* ================= UPDATE ================= */
if (isset($_POST['update_suggestion'])) {

    $id = (int) $_POST['id'];
    $title = trim($_POST['title']);
    $desc = trim($_POST['message']);
    $category = (int) $_POST['category'];

    $stmt = $conn->prepare("
        UPDATE suggestions
        SET title = ?, message = ?, category_id = ?
        WHERE suggestion_id = ? AND user_id = ? AND status = 'pending'
    ");
    $stmt->bind_param("ssiii", $title, $desc, $category, $id, $user_id);
    $stmt->execute();

    if (!empty($_FILES['attachment']['name'])) {

        $allowed = ['jpg','jpeg','png','pdf','docx'];

        $name = $_FILES['attachment']['name'];
        $tmp  = $_FILES['attachment']['tmp_name'];
        $size = $_FILES['attachment']['size'];

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed) && $size <= 2 * 1024 * 1024) {

            $newName = uniqid("file_") . "." . $ext;

            $dir = "../uploads/";
            if (!is_dir($dir)) mkdir($dir, 0777, true);

            $path = $dir . $newName;

            if (move_uploaded_file($tmp, $path)) {

                $dbPath = "uploads/" . $newName;

                $stmt = $conn->prepare("
                    INSERT INTO attachments (suggestion_id, file_name, file_path, file_type)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->bind_param("isss", $id, $name, $dbPath, $ext);
                $stmt->execute();
            }
        }
    }

    header("Location: my_suggestions.php");
    exit();
}

/* ================= EDIT DATA ================= */
$editData = null;

if (isset($_GET['edit'])) {

    $edit_id = (int) $_GET['edit'];

    $stmt = $conn->prepare("
        SELECT * FROM suggestions 
        WHERE suggestion_id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $edit_id, $user_id);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc();

    if (!$editData || $editData['status'] !== 'pending') {
        header("Location: my_suggestions.php");
        exit();
    }
}

/* ================= FETCH SUGGESTIONS ================= */
$stmt = $conn->prepare("
    SELECT 
        s.suggestion_id,
        s.title,
        s.message,
        s.status,
        s.created_at,
        c.category_name
    FROM suggestions s
    LEFT JOIN categories c ON c.category_id = s.category_id
    WHERE s.user_id = ?
    ORDER BY s.suggestion_id DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>My Suggestions</title>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
/* ================= ONLY STYLE CONTENT, NOT SIDER ================= */
body{
    margin:0;
    font-family:'Segoe UI',sans-serif;
    background:#f8fafc;
    color:#1e293b;
}

/* ================= CONTENT - USE SPECIFIC CLASS ================= */
.content {
    margin-left: 250px;
    padding: 30px;
    padding-top: 100px;
}

.card-box{
    background:white;
    padding:25px;
    border-radius:16px;
    border:1px solid #e2e8f0;
    box-shadow:0 4px 12px rgba(0,0,0,.06);
    margin-bottom:25px;
}

h4{
    margin-top:0;
    color:#111827;
    display:flex;
    gap:10px;
    align-items:center;
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
}

td{
    padding:14px;
    border-bottom:1px solid #e2e8f0;
}

tr:hover{
    background:#f8fafc;
}

.message-cell{
    white-space:normal;
    word-break:break-word;
    line-height:1.6;
}

.btn{
    border-radius:8px;
    padding:6px 12px;
    font-size:12px;
    text-decoration:none;
    display:inline-block;
    border:none;
    cursor:pointer;
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

.btn-secondary{
    background:#64748b;
    color:white;
}

.btn-secondary:hover{
    background:#475569;
}

.btn-submit{
    background:#111827;
    color:white;
}

.btn-submit:hover{
    background:#1f2937;
}

.badge{
    padding:6px 12px;
    border-radius:999px;
    font-size:12px;
    font-weight:600;
    display:inline-block;
}

.badge-warning{
    background:#fef3c7;
    color:#92400e;
}

.badge-success{
    background:#dcfce7;
    color:#166534;
}

.badge-danger{
    background:#fee2e2;
    color:#991b1b;
}

.text-muted{
    color:#94a3b8;
}

.text-center{
    text-align:center;
}

.py-4{
    padding-top:30px;
    padding-bottom:30px;
}

.d-block{
    display:block;
}

.mb-2{
    margin-bottom:10px;
}

.w-100{
    width:100%;
}

.mt-2{
    margin-top:10px;
}

.mb-3{
    margin-bottom:15px;
}

.fw-bold{
    font-weight:600;
}

.form-control{
    width:100%;
    padding:10px;
    border-radius:8px;
    border:1px solid #cbd5e1;
    box-sizing:border-box;
}

.submitted-date{
    font-size:13px;
    color:#374151;
}

.submitted-date .date{
    font-weight:600;
    color:#1e3a8a;
}

.submitted-date .date i{
    margin-right:4px;
}

.submitted-date .time{
    font-size:12px;
    color:#6b7280;
}

.submitted-date .time i{
    margin-right:4px;
}

.submitted-date .day-diff{
    font-size:11px;
    color:#dc2626;
    font-weight:600;
    margin-top:2px;
}

.header-actions{
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.action-buttons{
    display:flex;
    gap:5px;
    flex-wrap:wrap;
}

@media(max-width:900px){
    .content{
        margin-left:0;
    }
    .header-actions{
        flex-direction:column;
        gap:10px;
        align-items:flex-start;
    }
    .action-buttons{
        flex-direction:column;
    }
}

@media(max-width:600px){
    .content{
        padding:10px;
        padding-top:80px;
    }
    .action-buttons .btn{
        width:100%;
        text-align:center;
    }
}
</style>
</head>

<body>

<?php include("../sider/sider.php"); ?>
<?php include("../toper/toper.php"); ?>

<div class="content">

    <div class="card-box">
        <div class="header-actions">
            <h4><i class="fas fa-list"></i> My Suggestions</h4>
            <a href="submit_suggestion.php" class="btn btn-submit">
                <i class="fas fa-plus"></i> Add Suggestion
            </a>
        </div>
    </div>

    <?php if($editData){ ?>
    <div class="card-box">
        <h4><i class="fas fa-edit"></i> Edit Suggestion</h4>
        <hr>

        <form method="POST" enctype="multipart/form-data">

            <input type="hidden" name="id" value="<?= $editData['suggestion_id'] ?>">

            <div class="mb-3">
                <label class="fw-bold">Title</label>
                <input type="text" name="title" class="form-control"
                value="<?= htmlspecialchars($editData['title']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="fw-bold">Description</label>
                <textarea name="message" class="form-control" rows="5" required><?= htmlspecialchars($editData['message']) ?></textarea>
            </div>

            <div class="mb-3">
                <label class="fw-bold">Category</label>
                <select name="category" class="form-control" required>
                    <?php
                    $cat = $conn->query("SELECT * FROM categories");
                    while($c = $cat->fetch_assoc()){
                    ?>
                    <option value="<?= $c['category_id'] ?>"
                        <?= $editData['category_id']==$c['category_id']?'selected':'' ?>>
                        <?= $c['category_name'] ?>
                    </option>
                    <?php } ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="fw-bold">Attach File (Optional)</label>
                <input type="file" name="attachment" class="form-control">
                <small class="text-muted">Max 2MB (JPG, PNG, PDF, DOCX)</small>
            </div>

            <button type="submit" name="update_suggestion" class="btn btn-primary w-100">
                <i class="fas fa-save"></i> Update Suggestion
            </button>
            
            <a href="my_suggestions.php" class="btn btn-secondary w-100 mt-2">
                <i class="fas fa-times"></i> Cancel
            </a>

        </form>
    </div>
    <?php } ?>

    <div class="card-box">
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Message</th>
                    <th>Submitted On</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): 
                        date_default_timezone_set('Africa/Dar_es_Salaam');
                        
                        $timestamp = strtotime($row['created_at']);
                        
                        $full_date = date('l, F j, Y', $timestamp);
                        $time = date('g:i A', $timestamp);
                        $day_name = date('l', $timestamp);
                        $month_name = date('F', $timestamp);
                        $day_number = date('j', $timestamp);
                        $year = date('Y', $timestamp);
                        
                        $today = date('Y-m-d');
                        $submitted_date = date('Y-m-d', $timestamp);
                        $is_today = ($today == $submitted_date);
                        $is_yesterday = (date('Y-m-d', strtotime('-1 day')) == $submitted_date);
                        
                        if($is_today) {
                            $display_full = 'Today at ' . $time;
                        } elseif($is_yesterday) {
                            $display_full = 'Yesterday at ' . $time;
                        } else {
                            $display_full = $full_date . ' at ' . $time;
                        }
                        
                        $now = time();
                        $diff = $now - $timestamp;
                        
                        $days = floor($diff / (60 * 60 * 24));
                        $hours = floor(($diff % (60 * 60 * 24)) / (60 * 60));
                        $minutes = floor(($diff % (60 * 60)) / 60);
                        
                        if($days > 30) {
                            $months = floor($days / 30);
                            if($months > 12) {
                                $years = floor($months / 12);
                                $time_ago = $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
                            } else {
                                $time_ago = $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
                            }
                        } elseif($days > 0) {
                            $time_ago = $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
                        } elseif($hours > 0) {
                            $time_ago = $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
                        } elseif($minutes > 0) {
                            $time_ago = $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
                        } else {
                            $time_ago = 'Just now';
                        }
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($row['title']) ?></strong></td>
                        <td><?= htmlspecialchars($row['category_name']) ?></td>
                        <td class="message-cell"><?= htmlspecialchars(substr($row['message'], 0, 80)) ?></td>
                        <td>
                            <div class="submitted-date">
                                <div class="date">
                                    <i class="fas fa-calendar-day"></i>
                                    <?= $display_full ?>
                                </div>
                                <div class="time">
                                    <i class="fas fa-clock"></i>
                                    <?= $time_ago ?>
                                </div>
                                <div class="day-diff">
                                    <i class="fas fa-hourglass-half"></i>
                                    <?= $day_name ?>, <?= $month_name ?> <?= $day_number ?>, <?= $year ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php
                            if($row['status'] == 'pending')
                                echo '<span class="badge badge-warning">Pending</span>';
                            elseif($row['status'] == 'approved')
                                echo '<span class="badge badge-success">Approved</span>';
                            else
                                echo '<span class="badge badge-danger">Rejected</span>';
                            ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <?php if($row['status'] == 'pending'): ?>
                                    <a href="?edit=<?= $row['suggestion_id'] ?>" class="btn btn-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="?delete_suggestion=<?= $row['suggestion_id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this suggestion?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                <?php else: ?>
                                    <a href="view_suggestion.php?id=<?= $row['suggestion_id'] ?>" class="btn btn-success">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <span class="text-muted"><i class="fas fa-lock"></i> Locked</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <i class="fas fa-inbox fa-2x text-muted d-block mb-2"></i>
                            <p class="text-muted">You haven't submitted any suggestions yet.</p>
                            <a href="submit_suggestion.php" class="btn btn-submit">
                                <i class="fas fa-plus"></i> Add Suggestion
                            </a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?php include("../footer/footer.php"); ?>

</body>
</html>