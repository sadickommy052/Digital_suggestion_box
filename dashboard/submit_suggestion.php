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

        // ============================================================
        // 🔔 SEND NOTIFICATION TO ALL MANAGERS
        // ============================================================
        
        // Get suggester's name
        $user_query = $conn->prepare("SELECT full_name FROM users WHERE user_id = ?");
        $user_query->bind_param("i", $user_id);
        $user_query->execute();
        $user_data = $user_query->get_result()->fetch_assoc();
        $suggester_name = $user_data['full_name'] ?? 'A user';
        $user_query->close();
        
        // Get all managers
        $managers = $conn->query("SELECT user_id FROM users WHERE role = 'suggestion_manager'");
        
        if($managers->num_rows > 0){
            $notify_title = "New Suggestion Submitted";
            $notify_message = "A new suggestion '$title' has been submitted by $suggester_name. Please review it.";
            $notify_type = "new_suggestion";
            
            while($manager = $managers->fetch_assoc()){
                $manager_id = $manager['user_id'];
                
                $notify = $conn->prepare("
                    INSERT INTO notifications (user_id, title, message, type, is_read, created_at) 
                    VALUES (?, ?, ?, ?, 0, NOW())
                ");
                
                $notify->bind_param("isss", $manager_id, $notify_title, $notify_message, $notify_type);
                $notify->execute();
                $notify->close();
            }
        }
        
        // ============================================================

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

/* ================= GLOBAL THEME ================= */
body{
    margin:0;
    font-family:'Segoe UI', sans-serif;
    background:#f4f6f9;
    color:#111827;
}

/* ================= CONTENT ================= */
.content{
    margin-left:220px;
    padding:100px 30px 30px 30px;
    min-height:100vh;
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
    border-radius:12px;
    box-shadow:0 2px 10px rgba(0,0,0,0.06);
    overflow:hidden;
}

/* ================= HEADER ================= */
.card-header{
    background:#111827;
    color:white;
    padding:18px 24px;
    font-weight:600;
    font-size:18px;
    display:flex;
    align-items:center;
    gap:12px;
    border-bottom:1px solid #1f2937;
}

.card-header i{
    color:#9ca3af;
}

/* ================= BODY ================= */
.card-body{
    padding:28px 30px;
}

/* ================= LABEL ================= */
label{
    display:block;
    margin-bottom:6px;
    font-size:13px;
    font-weight:600;
    color:#374151;
}

label i{
    margin-right:8px;
    color:#6b7280;
    width:16px;
}

/* ================= INPUTS ================= */
input, select, textarea{
    width:100%;
    padding:10px 14px;
    border-radius:8px;
    border:1px solid #d1d5db;
    margin-bottom:18px;
    background:#fafbfc;
    font-size:14px;
    transition:all 0.2s ease;
    color:#111827;
    box-sizing:border-box;
}

input:focus, select:focus, textarea:focus{
    border-color:#111827;
    box-shadow:0 0 0 3px rgba(17,24,39,0.08);
    outline:none;
    background:white;
}

input::placeholder, textarea::placeholder{
    color:#9ca3af;
}

textarea{
    resize:vertical;
    min-height:120px;
}

select{
    appearance:none;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat:no-repeat;
    background-position:right 14px center;
    cursor:pointer;
}

input[type="file"]{
    padding:8px 12px;
    background:white;
    border:1px dashed #d1d5db;
    cursor:pointer;
}

input[type="file"]:hover{
    border-color:#111827;
    background:#f8fafc;
}

/* ================= BUTTON ================= */
button{
    width:100%;
    padding:12px;
    border:none;
    border-radius:8px;
    background:#111827;
    color:#fff;
    font-weight:600;
    font-size:15px;
    cursor:pointer;
    transition:all 0.2s ease;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:10px;
    margin-top:5px;
}

button:hover{
    background:#1f2937;
}

button:active{
    transform:scale(0.98);
}

button i{
    font-size:16px;
}

/* ================= ALERT BOXES ================= */
.alert{
    padding:12px 16px;
    border-radius:8px;
    margin-bottom:20px;
    font-size:14px;
    font-weight:500;
    display:flex;
    align-items:center;
    gap:10px;
}

.alert i{
    font-size:16px;
}

.success{
    background:#f0fdf4;
    color:#166534;
    border:1px solid #bbf7d0;
}

.warning{
    background:#fffbeb;
    color:#92400e;
    border:1px solid #fde68a;
}

.danger{
    background:#fef2f2;
    color:#991b1b;
    border:1px solid #fecaca;
}

/* ================= RESPONSIVE ================= */
@media(max-width:768px){
    .content{
        margin-left:0;
        padding:80px 15px 15px 15px;
    }

    .card-body{
        padding:20px;
    }

    .card-header{
        padding:14px 18px;
        font-size:16px;
    }

    input, select, textarea{
        padding:8px 12px;
        font-size:13px;
    }

    button{
        padding:10px;
        font-size:14px;
    }
}

@media(max-width:480px){
    .card-body{
        padding:16px;
    }

    .card-header{
        padding:12px 16px;
        font-size:15px;
    }
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
                <i class="fas fa-paper-plane"></i>
                Submit Suggestion
            </div>

            <div class="card-body">

                <?php if($message): ?>
                    <div class="alert <?= $messageType ?>">
                        <i class="fas <?= $messageType == 'success' ? 'fa-check-circle' : ($messageType == 'warning' ? 'fa-exclamation-triangle' : 'fa-exclamation-circle') ?>"></i>
                        <?= $message ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">

                    <label><i class="fas fa-list"></i> Category</label>
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

                    <label><i class="fas fa-heading"></i> Title</label>
                    <input type="text" name="title" placeholder="Enter suggestion title" required>

                    <label><i class="fas fa-align-left"></i> Description</label>
                    <textarea name="description" rows="4" placeholder="Describe your suggestion in detail" required></textarea>

                    <label><i class="fas fa-paperclip"></i> Attachment (optional)</label>
                    <input type="file" name="attachment">

                    <button type="submit">
                        <i class="fas fa-paper-plane"></i> Submit Suggestion
                    </button>

                </form>

            </div>

        </div>

    </div>

</div>
<?php include("../footer/footer.php"); ?>

</body>
</html>