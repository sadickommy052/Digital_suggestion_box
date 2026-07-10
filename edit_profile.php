<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

include(__DIR__ . "/config/db.php");
include(__DIR__ . "/config/functions.php"); // ← IMEBADILISHWA NJIA


if(!isset($conn)){
    die("Database connection failed. Check db.php");
}

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* =========================
   SAFE USER FETCH (NO get_result)
========================= */

$stmt = $conn->prepare("
SELECT full_name, email, profile_picture
FROM users
WHERE user_id=?
LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

/* bind results safely */
$stmt->bind_result($full_name, $email, $profile_picture);

if(!$stmt->fetch()){
    die("User not found");
}

$stmt->close();

/* =========================
   UPDATE PROFILE
========================= */

$message = "";
$messageType = "";

if(isset($_POST['update'])){

    $new_name  = trim($_POST['full_name']);
    $new_email = trim($_POST['email']);

    $new_profile = $profile_picture;

    /* IMAGE UPLOAD */
    if(!empty($_FILES['profile_picture']['name'])){

        $upload_dir = "uploads/";

        if(!is_dir($upload_dir)){
            mkdir($upload_dir, 0777, true);
        }

        $file_name = time() . "_" . basename($_FILES['profile_picture']['name']);
        $target_path = $upload_dir . $file_name;

        if(move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_path)){
            $new_profile = $target_path;
        }
    }

    /* PASSWORD CHANGE (OLD + NEW) */
    if(!empty($_POST['old_password']) && !empty($_POST['new_password'])){

        /* CHECK OLD PASSWORD */
        $stmtCheck = $conn->prepare("SELECT password FROM users WHERE user_id=?");
        $stmtCheck->bind_param("i", $user_id);
        $stmtCheck->execute();
        $stmtCheck->bind_result($db_password);
        $stmtCheck->fetch();
        $stmtCheck->close();

        if(!password_verify($_POST['old_password'], $db_password)){
            $message = "Old password is incorrect!";
            $messageType = "error";
        }else{

            $hashed = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
            UPDATE users 
            SET full_name=?, email=?, profile_picture=?, password=?
            WHERE user_id=?
            ");

            $stmt->bind_param("ssssi",
                $new_name,
                $new_email,
                $new_profile,
                $hashed,
                $user_id
            );
        }

    }else{

        $stmt = $conn->prepare("
        UPDATE users 
        SET full_name=?, email=?, profile_picture=?
        WHERE user_id=?
        ");

        $stmt->bind_param("sssi",
            $new_name,
            $new_email,
            $new_profile,
            $user_id
        );
    }

    if(isset($stmt) && $stmt->execute()){
        
        // =====================
        // REKODI PROFILE UPDATE
        // =====================
        logActivity(
            $_SESSION['user_id'],
            $_SESSION['full_name'],
            'Profile Updated',
            'User updated their profile (Name: ' . $new_name . ', Email: ' . $new_email . ')'
        );
        
        $message = "Profile updated successfully!";
        $messageType = "success";
        // Update variables to reflect new values
        $full_name = $new_name;
        $email = $new_email;
        $profile_picture = $new_profile;
    }

    if(isset($stmt)){
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Profile</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* ================= NO RESET - USIATHIRI SIDER ================= */
body{
    margin:0;
    font-family:'Segoe UI',sans-serif;
    background:#f8fafc;
    color:#1e293b;
}

.container{
    margin-left:250px;
    padding:30px;
    padding-top:100px;
    min-height:calc(100vh - 180px);
}

.card{
    max-width:750px;
    margin:auto;
    background:#ffffff;
    border-radius:16px;
    box-shadow:0 4px 12px rgba(0,0,0,0.06);
    overflow:hidden;
    border:1px solid #e2e8f0;
}

/* ================= HEADER ================= */
.header{
    background:#111827;
    color:white;
    padding:25px 30px;
    border-bottom:1px solid #1f2937;
}

.header h2{
    margin:0;
    font-size:20px;
    font-weight:600;
}

.header h2 i{
    margin-right:10px;
    color:#9ca3af;
}

.header p{
    margin:6px 0 0 0;
    font-size:13px;
    color:#9ca3af;
}

/* ================= BODY ================= */
.body{
    padding:30px;
}

/* ================= PROFILE PREVIEW ================= */
.profile-preview{
    text-align:center;
    margin-bottom:25px;
    padding-bottom:25px;
    border-bottom:1px solid #e2e8f0;
}

.profile-preview .avatar{
    width:100px;
    height:100px;
    border-radius:50%;
    object-fit:cover;
    border:3px solid #111827;
    background:#e5e7eb;
}

.profile-preview .avatar-letter{
    width:100px;
    height:100px;
    border-radius:50%;
    background:#111827;
    display:flex;
    align-items:center;
    justify-content:center;
    color:white;
    font-size:40px;
    font-weight:600;
    margin:0 auto;
    border:3px solid #111827;
}

.profile-preview .avatar-wrapper{
    display:inline-block;
    position:relative;
}

.profile-preview .camera-icon{
    position:absolute;
    bottom:2px;
    right:2px;
    background:#374151;
    color:white;
    width:32px;
    height:32px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    border:2px solid white;
    font-size:12px;
}

/* ================= FORM ================= */
.form-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:18px;
}

.form-group{
    display:flex;
    flex-direction:column;
    gap:5px;
}

.form-group.full-width{
    grid-column:1 / -1;
}

.form-group label{
    font-size:13px;
    font-weight:600;
    color:#374151;
    display:flex;
    align-items:center;
    gap:8px;
}

.form-group label i{
    color:#6b7280;
    width:16px;
    font-size:13px;
}

.form-group input{
    padding:10px 14px;
    border:1px solid #cbd5e1;
    border-radius:8px;
    outline:none;
    font-size:14px;
    transition:all 0.2s ease;
    background:#f8fafc;
}

.form-group input:focus{
    border-color:#111827;
    box-shadow:0 0 0 3px rgba(17,24,39,0.08);
    background:white;
}

.form-group input::placeholder{
    color:#94a3b8;
    font-size:13px;
}

.form-group input[type="file"]{
    padding:8px 12px;
    background:white;
    border:1px dashed #cbd5e1;
}

.form-group input[type="file"]:hover{
    border-color:#111827;
    background:#f8fafc;
}

/* ================= BUTTON ================= */
.btn-submit{
    grid-column:1 / -1;
    padding:12px;
    background:#111827;
    color:white;
    border:none;
    border-radius:8px;
    font-size:15px;
    font-weight:600;
    cursor:pointer;
    transition:all 0.2s ease;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:10px;
    margin-top:5px;
}

.btn-submit:hover{
    background:#1f2937;
}

.btn-submit:active{
    transform:scale(0.98);
}

/* ================= MESSAGE ================= */
.message{
    padding:12px 16px;
    border-radius:8px;
    font-weight:600;
    font-size:14px;
    margin-bottom:20px;
    display:flex;
    align-items:center;
    gap:10px;
}

.message.success{
    background:#dcfce7;
    color:#166534;
    border:1px solid #bbf7d0;
}

.message.error{
    background:#fee2e2;
    color:#991b1b;
    border:1px solid #fecaca;
}

.message i{
    font-size:16px;
}

/* ================= RESPONSIVE ================= */
@media(max-width:768px){
    .container{
        margin-left:0;
        padding:80px 15px 15px 15px;
    }

    .form-grid{
        grid-template-columns:1fr;
    }

    .form-group.full-width{
        grid-column:1;
    }

    .btn-submit{
        grid-column:1;
    }

    .card{
        border-radius:10px;
    }

    .header h2{
        font-size:18px;
    }

    .profile-preview .avatar,
    .profile-preview .avatar-letter{
        width:80px;
        height:80px;
        font-size:32px;
    }

    .profile-preview .camera-icon{
        width:28px;
        height:28px;
        font-size:11px;
    }
}

@media(max-width:480px){
    .body{
        padding:20px;
    }

    .header{
        padding:18px 20px;
    }

    .form-group input{
        padding:8px 12px;
        font-size:13px;
    }

    .btn-submit{
        padding:10px;
        font-size:14px;
    }
}

</style>
</head>

<body>

<?php include(__DIR__."/sider/sider.php"); ?>
<?php include(__DIR__."/toper/toper.php"); ?>

<div class="container">

    <div class="card">

        <!-- HEADER -->
        <div class="header">
            <h2><i class="fas fa-user-edit"></i> Edit Profile</h2>
            <p>Update your personal information</p>
        </div>

        <!-- BODY -->
        <div class="body">

            <?php if($message){ ?>
                <div class="message <?=$messageType?>">
                    <i class="fas <?= $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                    <?=$message?>
                </div>
            <?php } ?>

            <!-- PROFILE PREVIEW -->
            <div class="profile-preview">
                <div class="avatar-wrapper">
                    <?php if(!empty($profile_picture) && file_exists(__DIR__."/".$profile_picture)){ ?>
                        <img src="<?=$profile_picture?>" class="avatar" alt="Profile Picture">
                    <?php }else{ ?>
                        <div class="avatar-letter">
                            <?=strtoupper(substr($full_name,0,1))?>
                        </div>
                    <?php } ?>
                    <div class="camera-icon">
                        <i class="fas fa-camera"></i>
                    </div>
                </div>
            </div>

            <!-- FORM -->
            <form method="POST" enctype="multipart/form-data">
                <div class="form-grid">

                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Full Name</label>
                        <input type="text" name="full_name" value="<?=htmlspecialchars($full_name)?>" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" name="email" value="<?=htmlspecialchars($email)?>" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Old Password</label>
                        <input type="password" name="old_password" placeholder="Enter old password">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-key"></i> New Password</label>
                        <input type="password" name="new_password" placeholder="Enter new password">
                    </div>

                    <div class="form-group full-width">
                        <label><i class="fas fa-image"></i> Profile Picture</label>
                        <input type="file" name="profile_picture" accept="image/*">
                    </div>

                    <button type="submit" name="update" class="btn-submit">
                        <i class="fas fa-save"></i> Update Profile
                    </button>

                </div>
            </form>

        </div>

    </div>

</div>

</body>
</html>