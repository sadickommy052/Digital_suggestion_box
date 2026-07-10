<?php
session_start();
include("../config/db.php");
include("../config/functions.php");

// =====================
// AUTH CHECK
// =====================
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../login.php");
    exit();
}

// =====================
// VARIABLES
// =====================
$message = "";
$messageType = "";

// =====================
// GET CURRENT SETTINGS
// =====================
$settings = [];
$result = $conn->query("SELECT * FROM system_settings LIMIT 1");

// If table doesn't exist, create it
if(!$result){
    $conn->query("
        CREATE TABLE IF NOT EXISTS system_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            site_name VARCHAR(100) DEFAULT 'Digital Suggestion Box',
            site_description TEXT,
            timezone VARCHAR(50) DEFAULT 'Africa/Dar_es_Salaam',
            date_format VARCHAR(20) DEFAULT 'M j, Y',
            time_format VARCHAR(20) DEFAULT 'g:i A',
            maintenance_mode TINYINT(1) DEFAULT 0,
            allow_registration TINYINT(1) DEFAULT 1,
            allow_anonymous TINYINT(1) DEFAULT 0,
            max_attachments INT DEFAULT 5,
            max_file_size INT DEFAULT 2,
            allowed_file_types VARCHAR(100) DEFAULT 'jpg,jpeg,png,pdf,docx',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    
    $conn->query("
        INSERT INTO system_settings (
            site_name, site_description, timezone, date_format, time_format,
            maintenance_mode, allow_registration, allow_anonymous,
            max_attachments, max_file_size, allowed_file_types
        ) VALUES (
            'Digital Suggestion Box',
            'A platform for collecting and managing suggestions from users.',
            'Africa/Dar_es_Salaam',
            'M j, Y',
            'g:i A',
            0,
            1,
            0,
            5,
            2,
            'jpg,jpeg,png,pdf,docx'
        )
    ");
    
    $result = $conn->query("SELECT * FROM system_settings LIMIT 1");
}

if($result && $result->num_rows > 0){
    $settings = $result->fetch_assoc();
}

// =====================
// UPDATE SETTINGS
// =====================
if(isset($_POST['update_settings'])){
    
    $site_name = trim($_POST['site_name'] ?? 'Digital Suggestion Box');
    $site_description = trim($_POST['site_description'] ?? '');
    $timezone = $_POST['timezone'] ?? 'Africa/Dar_es_Salaam';
    $date_format = $_POST['date_format'] ?? 'M j, Y';
    $time_format = $_POST['time_format'] ?? 'g:i A';
    $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
    $allow_registration = isset($_POST['allow_registration']) ? 1 : 0;
    $allow_anonymous = isset($_POST['allow_anonymous']) ? 1 : 0;
    $max_attachments = intval($_POST['max_attachments'] ?? 5);
    $max_file_size = intval($_POST['max_file_size'] ?? 2);
    $allowed_file_types = trim($_POST['allowed_file_types'] ?? 'jpg,jpeg,png,pdf,docx');
    
    // Check if settings exist
    $check = $conn->query("SELECT COUNT(*) as count FROM system_settings");
    $row = $check->fetch_assoc();
    
    if($row['count'] > 0){
        // Update
        $stmt = $conn->prepare("
            UPDATE system_settings SET
                site_name = ?,
                site_description = ?,
                timezone = ?,
                date_format = ?,
                time_format = ?,
                maintenance_mode = ?,
                allow_registration = ?,
                allow_anonymous = ?,
                max_attachments = ?,
                max_file_size = ?,
                allowed_file_types = ?,
                updated_at = NOW()
            WHERE id = 1
        ");
        $stmt->bind_param(
            "sssssiiiiss",
            $site_name,
            $site_description,
            $timezone,
            $date_format,
            $time_format,
            $maintenance_mode,
            $allow_registration,
            $allow_anonymous,
            $max_attachments,
            $max_file_size,
            $allowed_file_types
        );
    } else {
        // Insert
        $stmt = $conn->prepare("
            INSERT INTO system_settings (
                site_name, site_description, timezone, date_format, time_format,
                maintenance_mode, allow_registration, allow_anonymous,
                max_attachments, max_file_size, allowed_file_types, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param(
            "sssssiiiiss",
            $site_name,
            $site_description,
            $timezone,
            $date_format,
            $time_format,
            $maintenance_mode,
            $allow_registration,
            $allow_anonymous,
            $max_attachments,
            $max_file_size,
            $allowed_file_types
        );
    }
    
    if($stmt->execute()){
        logActivity(
            $_SESSION['user_id'],
            $_SESSION['full_name'],
            'Settings Updated',
            'System settings were updated'
        );
        
        $message = "Settings updated successfully!";
        $messageType = "success";
        
        // Refresh settings
        $result = $conn->query("SELECT * FROM system_settings LIMIT 1");
        if($result && $result->num_rows > 0){
            $settings = $result->fetch_assoc();
        }
    } else {
        $message = "Failed to update settings!";
        $messageType = "error";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>System Settings</title>
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

.header .status-badge{
    padding:8px 16px;
    border-radius:999px;
    font-size:13px;
    font-weight:600;
    background:#22c55e;
    color:white;
}

.header .status-badge.maintenance{
    background:#dc2626;
}

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
    align-items:center;
    gap:10px;
    margin-bottom:20px;
    padding-bottom:15px;
    border-bottom:1px solid #e2e8f0;
}

.card-header h3{
    margin:0;
    font-size:17px;
    font-weight:600;
    color:#111827;
}

.card-header i{
    font-size:20px;
    color:#2563eb;
}

.form-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:20px;
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
    color:#64748b;
    font-size:13px;
    width:16px;
}

.form-group input,
.form-group select,
.form-group textarea{
    padding:10px 14px;
    border:1px solid #cbd5e1;
    border-radius:8px;
    font-size:14px;
    transition:all 0.2s ease;
    background:#f8fafc;
    font-family:inherit;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus{
    outline:none;
    border-color:#111827;
    box-shadow:0 0 0 3px rgba(17,24,39,0.08);
    background:white;
}

.form-group textarea{
    resize:vertical;
    min-height:80px;
}

.form-group .help-text{
    font-size:12px;
    color:#94a3b8;
    margin-top:3px;
}

.checkbox-group{
    display:flex;
    align-items:center;
    gap:10px;
    padding:10px 0;
}

.checkbox-group input[type="checkbox"]{
    width:18px;
    height:18px;
    cursor:pointer;
    accent-color:#111827;
}

.checkbox-group label{
    font-weight:500;
    color:#1e293b;
    cursor:pointer;
    margin:0;
}

.checkbox-group .help-text{
    font-size:12px;
    color:#94a3b8;
}

.btn{
    padding:12px 28px;
    border:none;
    border-radius:8px;
    font-weight:600;
    font-size:14px;
    cursor:pointer;
    transition:all 0.2s ease;
    display:inline-flex;
    align-items:center;
    gap:8px;
}

.btn-primary{
    background:#111827;
    color:white;
}

.btn-primary:hover{
    background:#1f2937;
}

.btn-warning{
    background:#f59e0b;
    color:white;
}

.btn-warning:hover{
    background:#d97706;
}

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

.alert-error{
    background:#fee2e2;
    color:#991b1b;
    border:1px solid #fecaca;
}

.alert i{
    font-size:16px;
}

@media(max-width:992px){
    .form-grid{
        grid-template-columns:1fr;
    }
    .form-group.full-width{
        grid-column:1;
    }
}

@media(max-width:768px){
    .content{
        margin-left:0;
        padding:15px;
        padding-top:80px;
    }
    .header{
        flex-direction:column;
        align-items:flex-start;
        text-align:left;
    }
    .header h2{
        font-size:19px;
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
            <h2><i class="fas fa-cog"></i> System Settings</h2>
            <p>Configure your Digital Suggestion Box System</p>
        </div>
        <div>
            <span class="status-badge <?= isset($settings['maintenance_mode']) && $settings['maintenance_mode'] ? 'maintenance' : '' ?>">
                <i class="fas <?= isset($settings['maintenance_mode']) && $settings['maintenance_mode'] ? 'fa-exclamation-triangle' : 'fa-check-circle' ?>"></i>
                <?= isset($settings['maintenance_mode']) && $settings['maintenance_mode'] ? 'Maintenance Mode' : 'System Online' ?>
            </span>
        </div>
    </div>

    <!-- ALERT MESSAGES -->
    <?php if($message): ?>
        <div class="alert alert-<?= $messageType ?>">
            <i class="fas <?= $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
            <?= $message ?>
        </div>
    <?php endif; ?>

    <!-- MAIN FORM -->
    <form method="POST">
        
        <!-- GENERAL SETTINGS -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-globe"></i>
                <h3>General Settings</h3>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label><i class="fas fa-building"></i> Site Name</label>
                    <input type="text" name="site_name" value="<?= htmlspecialchars($settings['site_name'] ?? 'Digital Suggestion Box') ?>" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-clock"></i> Timezone</label>
                    <select name="timezone">
                        <?php
                        $timezones = [
                            'Africa/Dar_es_Salaam' => 'Dar es Salaam (EAT)',
                            'Africa/Nairobi' => 'Nairobi (EAT)',
                            'Africa/Kampala' => 'Kampala (EAT)',
                            'Africa/Lagos' => 'Lagos (WAT)',
                            'Africa/Cairo' => 'Cairo (EET)',
                            'Europe/London' => 'London (GMT)',
                            'Europe/Paris' => 'Paris (CET)',
                            'America/New_York' => 'New York (EST)',
                            'America/Los_Angeles' => 'Los Angeles (PST)',
                            'Asia/Dubai' => 'Dubai (GST)',
                            'Asia/Tokyo' => 'Tokyo (JST)',
                            'UTC' => 'UTC'
                        ];
                        $current_timezone = $settings['timezone'] ?? 'Africa/Dar_es_Salaam';
                        foreach($timezones as $value => $label){
                            $selected = ($value == $current_timezone) ? 'selected' : '';
                            echo "<option value='$value' $selected>$label</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group full-width">
                    <label><i class="fas fa-align-left"></i> Site Description</label>
                    <textarea name="site_description" rows="3"><?= htmlspecialchars($settings['site_description'] ?? 'A platform for collecting and managing suggestions from users.') ?></textarea>
                </div>
            </div>
        </div>

        <!-- DATE & TIME SETTINGS -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-calendar-alt"></i>
                <h3>Date & Time Format</h3>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label><i class="fas fa-calendar-day"></i> Date Format</label>
                    <select name="date_format">
                        <?php
                        $date_formats = [
                            'M j, Y' => 'Jan 1, 2024',
                            'F j, Y' => 'January 1, 2024',
                            'Y-m-d' => '2024-01-01',
                            'd/m/Y' => '01/01/2024',
                            'm/d/Y' => '01/01/2024',
                            'd M Y' => '01 Jan 2024'
                        ];
                        $current_date_format = $settings['date_format'] ?? 'M j, Y';
                        foreach($date_formats as $value => $label){
                            $selected = ($value == $current_date_format) ? 'selected' : '';
                            echo "<option value='$value' $selected>$label</option>";
                        }
                        ?>
                    </select>
                    <div class="help-text">Example: <?= date($current_date_format) ?></div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-clock"></i> Time Format</label>
                    <select name="time_format">
                        <?php
                        $time_formats = [
                            'g:i A' => '5:30 PM (12-hour)',
                            'H:i' => '17:30 (24-hour)'
                        ];
                        $current_time_format = $settings['time_format'] ?? 'g:i A';
                        foreach($time_formats as $value => $label){
                            $selected = ($value == $current_time_format) ? 'selected' : '';
                            echo "<option value='$value' $selected>$label</option>";
                        }
                        ?>
                    </select>
                    <div class="help-text">Example: <?= date($current_time_format) ?></div>
                </div>
            </div>
        </div>

        <!-- SYSTEM FEATURES -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-sliders-h"></i>
                <h3>System Features</h3>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" name="maintenance_mode" id="maintenance_mode" value="1" <?= isset($settings['maintenance_mode']) && $settings['maintenance_mode'] ? 'checked' : '' ?>>
                        <div>
                            <label for="maintenance_mode"><i class="fas fa-tools"></i> Maintenance Mode</label>
                            <div class="help-text">When enabled, only admins can access the system</div>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" name="allow_registration" id="allow_registration" value="1" <?= isset($settings['allow_registration']) && $settings['allow_registration'] ? 'checked' : '' ?>>
                        <div>
                            <label for="allow_registration"><i class="fas fa-user-plus"></i> Allow Registration</label>
                            <div class="help-text">Allow new users to register</div>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" name="allow_anonymous" id="allow_anonymous" value="1" <?= isset($settings['allow_anonymous']) && $settings['allow_anonymous'] ? 'checked' : '' ?>>
                        <div>
                            <label for="allow_anonymous"><i class="fas fa-user-secret"></i> Allow Anonymous Submissions</label>
                            <div class="help-text">Allow users to submit suggestions anonymously</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ATTACHMENT SETTINGS -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-paperclip"></i>
                <h3>Attachment Settings</h3>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label><i class="fas fa-file"></i> Max Attachments</label>
                    <input type="number" name="max_attachments" min="1" max="10" value="<?= $settings['max_attachments'] ?? 5 ?>">
                    <div class="help-text">Maximum number of attachments per suggestion</div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-weight-hanging"></i> Max File Size (MB)</label>
                    <input type="number" name="max_file_size" min="1" max="20" value="<?= $settings['max_file_size'] ?? 2 ?>">
                    <div class="help-text">Maximum file size in megabytes</div>
                </div>
                <div class="form-group full-width">
                    <label><i class="fas fa-file-alt"></i> Allowed File Types</label>
                    <input type="text" name="allowed_file_types" value="<?= htmlspecialchars($settings['allowed_file_types'] ?? 'jpg,jpeg,png,pdf,docx') ?>">
                    <div class="help-text">Comma separated file extensions (e.g., jpg,png,pdf)</div>
                </div>
            </div>
        </div>

        <!-- SUBMIT BUTTON -->
        <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:30px;">
            <button type="submit" name="update_settings" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Settings
            </button>
            <button type="reset" class="btn btn-warning">
                <i class="fas fa-undo"></i> Reset Form
            </button>
        </div>

    </form>

</div>

<?php include("../footer/footer.php"); ?>

</body>
</html>