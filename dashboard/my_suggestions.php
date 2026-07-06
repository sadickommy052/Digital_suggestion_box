<?php
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

/* ================= FETCH ================= */
$stmt = $conn->prepare("
    SELECT 
        s.suggestion_id,
        s.user_id,
        s.title,
        s.message,
        s.status,
        s.created_at,
        c.category_name
    FROM suggestions s
    LEFT JOIN categories c 
        ON c.category_id = s.category_id
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

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
body{
    background:#eef2f7;
    font-family:'Segoe UI', sans-serif;
}

.sidebar-space{
    margin-left:200px;
    padding:100px;
}

.card-box{
    background:#fff;
    border-radius:16px;
    box-shadow:0 8px 25px rgba(0,0,0,0.08);
    padding:20px;
    margin-bottom:20px;
}

.table thead{
    background:#2563eb;
    color:white;
}

.message-cell{
    white-space: normal;
    word-break: break-word;
    line-height: 1.6;
}

.btn{
    border-radius:10px;
}

.badge{
    padding:6px 10px;
    border-radius:8px;
}
</style>
</head>

<body>

<?php include("../sider/sider.php"); ?>
<?php include("../toper/toper.php"); ?>

<div class="sidebar-space">

<div class="card-box">
    <h4><i class="fas fa-list text-primary"></i> My Suggestions</h4>
</div>

<?php if($editData){ ?>
<div class="card-box">
    <h5>Edit Suggestion</h5>

    <form method="POST" enctype="multipart/form-data">

        <input type="hidden" name="id" value="<?= $editData['suggestion_id'] ?>">

        <input type="text" name="title" class="form-control mb-2"
        value="<?= htmlspecialchars($editData['title']) ?>">

        <textarea name="message" class="form-control mb-2" rows="5"><?= htmlspecialchars($editData['message']) ?></textarea>

        <select name="category" class="form-control mb-2">
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

        <input type="file" name="attachment" class="form-control mb-2">

        <button name="update_suggestion" class="btn btn-primary w-100">
            Update
        </button>

    </form>
</div>
<?php } ?>

<div class="card-box">

<table class="table table-hover align-middle">
<thead>
<tr>
<th>Title</th>
<th>Category</th>
<th>Message</th>
<th>Status</th>
<th>Attachment</th>
<th>Action</th>
</tr>
</thead>

<tbody>

<?php while($row=$result->fetch_assoc()){ ?>

<tr>
<td><?= htmlspecialchars($row['title']) ?></td>
<td><?= htmlspecialchars($row['category_name']) ?></td>

<td class="message-cell">
<?= htmlspecialchars($row['message']) ?>
</td>

<td>
<?php
if($row['status']=='pending'){
echo "<span class='badge bg-warning text-dark'>Pending</span>";
}elseif($row['status']=='approved'){
echo "<span class='badge bg-success'>Approved</span>";
}else{
echo "<span class='badge bg-danger'>Rejected</span>";
}
?>
</td>

<td>
<?php
$att = $conn->prepare("SELECT * FROM attachments WHERE suggestion_id=?");
$att->bind_param("i",$row['suggestion_id']);
$att->execute();
$files=$att->get_result();

while($f=$files->fetch_assoc()){
echo "<a href='../".$f['file_path']."' target='_blank' class='btn btn-sm btn-outline-primary mb-1'>View</a> ";
}
?>
</td>

<td>
<?php if($row['status']=='pending'){ ?>
<a href="?edit=<?= $row['suggestion_id'] ?>" class="btn btn-sm btn-primary">Edit</a>
<a href="?delete_suggestion=<?= $row['suggestion_id'] ?>" class="btn btn-sm btn-danger"
onclick="return confirm('Delete?')">Delete</a>
<?php } else { echo "<span class='text-muted'>Locked</span>"; } ?>
</td>

</tr>

<?php } ?>

</tbody>
</table>

</div>

</div>

</body>
</html>