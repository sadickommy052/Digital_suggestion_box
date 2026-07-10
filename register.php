<?php
session_start();
include("config/db.php");
include("config/functions.php"); // ← IMEONGEZWA


// ================= WEKA TIMEZONE =================
date_default_timezone_set('Africa/Dar_es_Salaam');

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password != $confirm_password) {
        $error = "Passwords do not match!";
    } else {

        // Check if email exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Email already exists!";
        } else {

            $profile_picture = "";

            // ================= HANDLE PROFILE PICTURE =================
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {

                $file = $_FILES['profile_picture']['name'];
                $tmp = $_FILES['profile_picture']['tmp_name'];
                $size = $_FILES['profile_picture']['size'];
                $error_code = $_FILES['profile_picture']['error'];

                // ================= CHECK FILE UPLOAD ERROR =================
                if ($error_code !== 0) {
                    $upload_errors = [
                        1 => "File exceeds upload_max_filesize directive in php.ini",
                        2 => "File exceeds MAX_FILE_SIZE directive in HTML form",
                        3 => "File was only partially uploaded",
                        4 => "No file was uploaded",
                        6 => "Missing a temporary folder",
                        7 => "Failed to write file to disk",
                        8 => "A PHP extension stopped the file upload"
                    ];
                    $error = "Upload error: " . ($upload_errors[$error_code] ?? "Unknown error");
                } else {

                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'gif'];

                    if (in_array($ext, $allowed)) {

                        if ($size <= 2 * 1024 * 1024) {

                            // ================= NJIA SAHIHI YA FOLDER =================
                            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . "/Digital_suggestion_box/uploads/";
                            
                            // ================= UNDA FOLDER KAMA HAIPO =================
                            if (!is_dir($upload_dir)) {
                                if (!mkdir($upload_dir, 0777, true)) {
                                    $error = "Failed to create uploads folder. Please check permissions.";
                                }
                            }

                            // ================= ANGALIA KAMA FOLDER INAWEZA KUANDIKA =================
                            if (empty($error) && !is_writable($upload_dir)) {
                                $error = "Uploads folder is not writable. Please set permissions to 755 or 777.";
                            }

                            if (empty($error)) {

                                // ================= JINA LA PICHA =================
                                $new_name = time() . "_" . uniqid() . "." . $ext;

                                // ================= NJIA ZA PICHA =================
                                $file_path = $upload_dir . $new_name;
                                $db_path = "uploads/" . $new_name;

                                // ================= HAMISHA PICHA =================
                                if (move_uploaded_file($tmp, $file_path)) {
                                    $profile_picture = $db_path;
                                } else {
                                    $error = "Failed to upload image. Please check folder permissions.";
                                }
                            }
                        } else {
                            $error = "File size must be less than 2MB.";
                        }
                    } else {
                        $error = "Only images (JPG, PNG, GIF) are allowed.";
                    }
                }
            } else {
                $error = "Please choose a profile picture.";
            }

            // ================= INSERT USER =================
            if (empty($error)) {

                $hash = password_hash($password, PASSWORD_DEFAULT);
                $role = "suggester";
                $status = "active";

                $stmt = $conn->prepare("
                    INSERT INTO users (full_name, email, password, role, status, profile_picture, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->bind_param("ssssss", $full_name, $email, $hash, $role, $status, $profile_picture);

                if ($stmt->execute()) {
                    
                    // =====================
                    // REKODI USER REGISTRATION
                    // =====================
                    logActivity(
                        0, // No user_id yet, but we can use 0
                        $full_name,
                        'User Registered',
                        'New user registered: ' . $full_name . ' (Email: ' . $email . ')'
                    );
                    
                    header("Location: login.php");
                    exit();
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Register</title>

<style>
body{
    margin:0;
    font-family:'Segoe UI', sans-serif;
    background:#f8fafc;
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
}

.container{
    width:420px;
    background:#ffffff;
    padding:25px;
    border-radius:16px;
    box-shadow:0 4px 12px rgba(0,0,0,0.06);
    border:1px solid #e2e8f0;
}

h2{
    text-align:center;
    color:#111827;
    margin-bottom:10px;
}

input,button{
    width:100%;
    padding:12px;
    margin:8px 0;
    box-sizing:border-box;
    border-radius:8px;
    font-size:14px;
}

input{
    border:1px solid #cbd5e1;
    outline:none;
    transition:0.2s;
    background:#f8fafc;
}

input:focus{
    border-color:#111827;
    box-shadow:0 0 0 3px rgba(17,24,39,0.08);
    background:white;
}

input[type="file"]{
    padding:10px;
    background:#f8fafc;
    border:1px dashed #cbd5e1;
    cursor:pointer;
}

input[type="file"]:hover{
    border-color:#111827;
    background:#f1f5f9;
}

button{
    background:#111827;
    color:white;
    border:0;
    cursor:pointer;
    font-weight:600;
    transition:0.2s;
    margin-top:10px;
}

button:hover{
    background:#1f2937;
}

.error{
    background:#fee2e2;
    color:#991b1b;
    padding:10px;
    border-radius:8px;
    text-align:center;
    font-size:14px;
    border:1px solid #fecaca;
    margin-bottom:10px;
}

.login-link{
    text-align:center;
    margin-top:15px;
    font-size:14px;
    color:#6b7280;
}

.login-link a{
    color:#2563eb;
    text-decoration:none;
}

.login-link a:hover{
    text-decoration:underline;
}
</style>

</head>

<body>

<div class="container">

<h2>Create Account</h2>

<?php if(!empty($error)) echo "<div class='error'>$error</div>"; ?>

<form method="POST" enctype="multipart/form-data">

<input type="text" name="full_name" placeholder="Full Name" required>

<input type="email" name="email" placeholder="Email" required>

<input type="file" name="profile_picture" accept="image/*" required>

<input type="password" name="password" placeholder="Password" required>

<input type="password" name="confirm_password" placeholder="Confirm Password" required>

<button type="submit">Register</button>

</form>

<div class="login-link">
    Already have an account? <a href="login.php">Login</a>
</div>

</div>

</body>
</html>