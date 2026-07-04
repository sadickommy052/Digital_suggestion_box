<?php
session_start();
include("../config/db.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = intval($_POST['category'] ?? 0);

    if ($title === '' || $description === '' || $category === 0) {
        $message = "Please fill all required fields!";
        $messageType = "warning";
    } else {

        $stmt = $conn->prepare("
            INSERT INTO suggestions (user_id, category_id, title, message, status)
            VALUES (?, ?, ?, ?, 'pending')
        ");

        $stmt->bind_param("iiss", $user_id, $category, $title, $description);
        $stmt->execute();

        $suggestion_id = $stmt->insert_id;
        $stmt->close();

        if (!empty($_FILES['attachment']['name'])) {

            $allowed = ['jpg','jpeg','png','pdf','docx'];

            $name = $_FILES['attachment']['name'];
            $tmp  = $_FILES['attachment']['tmp_name'];
            $size = $_FILES['attachment']['size'];

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed) && $size <= 2*1024*1024) {

                $newName = uniqid("file_", true).".".$ext;

                $dir = "../uploads/";
                if (!is_dir($dir)) mkdir($dir, 0777, true);

                move_uploaded_file($tmp, $dir.$newName);

                $dbPath = "uploads/".$newName;

                $att = $conn->prepare("
                    INSERT INTO attachments (suggestion_id, file_name, file_path, file_type)
                    VALUES (?, ?, ?, ?)
                ");

                $att->bind_param("isss", $suggestion_id, $name, $dbPath, $ext);
                $att->execute();
                $att->close();
            }
        }

        $message = "Suggestion submitted successfully!";
        $messageType = "success";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Submit Suggestion</title>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
<style>

/* ================= GLOBAL THEME ================= */
body{
    margin:0;
    font-family:'Segoe UI', sans-serif;
    background:#f5f7fb;
    color:#111827;
}

/* ================= TOPBAR ================= */
.topbar{
    position:fixed;
    top:0;
    left:0;
    right:0;
    height:60px;
    background:#111827;
    border-bottom:1px solid #1f2937;
    z-index:9999;
    color:#fff;
}

/* ================= SIDEBAR ================= */
.sidebar{
    position:fixed;
    top:60px;
    left:0;
    width:250px;
    height:calc(100vh - 60px);
    background:#111827;
    border-right:1px solid #1f2937;
    z-index:9998;
    color:#fff;
}

/* ================= CONTENT ================= */
.content{
    margin-left:250px;
    padding:120px 130px 150px;
}

/* ================= CENTER WRAPPER ================= */
.wrapper{
    max-width:650px;
    margin:auto;
}

/* ================= CARD ================= */
.card{
    background:#ffffff;
    border:1px solid #e5e7eb;
    border-radius:14px;
    box-shadow:0 10px 25px rgba(0,0,0,0.06);
    overflow:hidden;
}

/* HEADER (SIDEBAR ACCENT STYLE) */
.card-header{
    background:#1e3a8a; /* blue accent like sidebar active */
    color:#fff;
    padding:16px;
    font-weight:600;
    display:flex;
    align-items:center;
    gap:10px;
}

/* BODY */
.card-body{
    padding:22px;
}

/* LABEL */
label{
    display:block;
    margin-bottom:6px;
    font-size:13px;
    font-weight:600;
    color:#374151;
}

/* INPUTS */
input, select, textarea{
    width:100%;
    padding:12px;
    border-radius:10px;
    border:1px solid #d1d5db;
    margin-bottom:15px;
    background:#fff;
    font-size:14px;
    transition:0.2s;
}

/* FOCUS STATE (MATCH SIDEBAR BLUE) */
input:focus, select:focus, textarea:focus{
    border-color:#2563eb;
    box-shadow:0 0 0 3px rgba(37,99,235,0.15);
    outline:none;
}

/* BUTTON (PRIMARY SIDEBAR BLUE) */
button{
    width:100%;
    padding:12px;
    border:none;
    border-radius:10px;
    background:#2563eb;
    color:#fff;
    font-weight:600;
    cursor:pointer;
    transition:0.2s;
}

/* BUTTON HOVER (DARK SIDEBAR STYLE) */
button:hover{
    background:#1f2937;
}

/* ALERT BOXES */
.alert{
    padding:10px;
    border-radius:10px;
    margin-bottom:15px;
    font-size:14px;
}

/* STATUS COLORS */
.success{background:#dcfce7;color:#166534;}
.warning{background:#fef3c7;color:#92400e;}
.danger{background:#fee2e2;color:#991b1b;}

/* OPTIONAL MODERN TOUCH */
input, select, textarea, button{
    box-shadow:0 2px 6px rgba(0,0,0,0.04);
}

</style>
</head>

<body>

<?php include("../toper/toper.php"); ?>
<?php include("../sider/sider.php"); ?>

<div class="content">

    <div class="wrapper">

        <div class="card">

            <div class="card-header">
                <i class="fa fa-paper-plane"></i>
                Submit Suggestion
            </div>

            <div class="card-body">

                <?php if($message): ?>
                    <div class="alert <?= $messageType ?>">
                        <?= $message ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">

                    <label>Category</label>
                    <select name="category" required>
                        <option value="">Select category</option>
                        <?php
                        $cat = $conn->query("SELECT * FROM categories");
                        while($c = $cat->fetch_assoc()){
                        ?>
                        <option value="<?= $c['category_id'] ?>">
                            <?= htmlspecialchars($c['category_name']) ?>
                        </option>
                        <?php } ?>
                    </select>

                    <label>Title</label>
                    <input type="text" name="title" required>

                    <label>Description</label>
                    <textarea name="description" rows="4" required></textarea>

                    <label>Attachment (optional)</label>
                    <input type="file" name="attachment">

                    <button type="submit">
                        <i class="fa fa-paper-plane"></i> Submit
                    </button>

                </form>

            </div>

        </div>

    </div>

</div>

</body>
</html>