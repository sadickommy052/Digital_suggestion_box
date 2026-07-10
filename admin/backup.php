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
// CREATE BACKUP DIRECTORY
// =====================
$backup_dir = "../backups/";
if(!is_dir($backup_dir)){
    if(!mkdir($backup_dir, 0777, true)){
        die("Failed to create backup directory. Please check permissions.");
    }
}

// =====================
// ANGALIA KAMA FOLDER INAWEZA KUANDIKA
// =====================
if(!is_writable($backup_dir)){
    die("Backup directory is not writable. Please set permissions to 755 or 777.");
}

// =====================
// GET BACKUP FILES
// =====================
$backup_files = [];
if($handle = opendir($backup_dir)){
    while(false !== ($entry = readdir($handle))){
        if($entry != "." && $entry != ".."){
            $ext = pathinfo($entry, PATHINFO_EXTENSION);
            if($ext == 'sql' || $ext == 'zip'){
                $backup_files[] = [
                    'name' => $entry,
                    'size' => filesize($backup_dir . $entry),
                    'modified' => filemtime($backup_dir . $entry),
                    'type' => $ext
                ];
            }
        }
    }
    closedir($handle);
}

// Sort backup files by date (newest first)
usort($backup_files, function($a, $b){
    return $b['modified'] - $a['modified'];
});

// =====================
// CREATE BACKUP
// =====================
if(isset($_GET['create'])){
    
    $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $filepath = $backup_dir . $filename;
    
    // Create database backup
    $sql = "-- ===========================================\n";
    $sql .= "-- Digital Suggestion Box System Backup\n";
    $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "-- ===========================================\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
    
    // Get all tables
    $tables = $conn->query("SHOW TABLES");
    $table_count = 0;
    
    while($row = $tables->fetch_row()){
        $table = $row[0];
        $table_count++;
        
        // Get create table statement
        $create = $conn->query("SHOW CREATE TABLE $table")->fetch_assoc();
        $sql .= "-- --------------------------------------------------------\n";
        $sql .= "-- Table: `$table`\n";
        $sql .= "-- --------------------------------------------------------\n";
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql .= $create['Create Table'] . ";\n\n";
        
        // Get table data
        $data = $conn->query("SELECT * FROM $table");
        $columns = $data->fetch_fields();
        $column_names = array_map(function($col){ return $col->name; }, $columns);
        $column_list = "`" . implode("`, `", $column_names) . "`";
        
        $values = [];
        while($row_data = $data->fetch_assoc()){
            $vals = [];
            foreach($row_data as $val){
                if($val === null){
                    $vals[] = "NULL";
                } else {
                    $vals[] = "'" . $conn->real_escape_string($val) . "'";
                }
            }
            $values[] = "(" . implode(", ", $vals) . ")";
        }
        
        if(!empty($values)){
            $sql .= "INSERT INTO `$table` ($column_list) VALUES\n" . implode(",\n", $values) . ";\n\n";
        }
    }
    
    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    
    // Save database backup
    if(file_put_contents($filepath, $sql)){
        
        // =====================
        // CREATE COMPLETE BACKUP WITH FILES
        // =====================
        $zip_filename = 'backup_' . date('Y-m-d_H-i-s') . '.zip';
        $zip_filepath = $backup_dir . $zip_filename;
        
        $zip = new ZipArchive();
        if($zip->open($zip_filepath, ZipArchive::CREATE) === TRUE){
            
            // Add SQL file to zip
            $zip->addFile($filepath, 'database/' . $filename);
            
            // Add uploads folder
            $uploads_dir = "../uploads/";
            if(is_dir($uploads_dir)){
                addFolderToZip($uploads_dir, $zip, 'uploads/');
            }
            
            // Add config files
            $config_files = ['../config/db.php', '../config/functions.php'];
            foreach($config_files as $config_file){
                if(file_exists($config_file)){
                    $zip->addFile($config_file, 'config/' . basename($config_file));
                }
            }
            
            // Add readme file
            $readme = "-- ===========================================\n";
            $readme .= "-- BACKUP INFORMATION\n";
            $readme .= "-- ===========================================\n";
            $readme .= "-- Backup Date: " . date('Y-m-d H:i:s') . "\n";
            $readme .= "-- Database: " . $db_name . "\n";
            $readme .= "-- Tables: " . $table_count . "\n";
            $readme .= "-- Files: Uploads and Config\n";
            $readme .= "-- ===========================================\n";
            $readme .= "-- To restore:\n";
            $readme .= "-- 1. Import database.sql\n";
            $readme .= "-- 2. Extract files to proper directories\n";
            $readme .= "-- ===========================================\n";
            
            $zip->addFromString('README.txt', $readme);
            
            $zip->close();
            
            // Delete the individual SQL file (keep zip only)
            @unlink($filepath);
            
            logActivity(
                $_SESSION['user_id'],
                $_SESSION['full_name'],
                'Backup Created',
                'Complete backup created: ' . $zip_filename . ' (' . $table_count . ' tables + files)'
            );
            
            // Keep only last 10 backups
            $backup_files = [];
            if($handle = opendir($backup_dir)){
                while(false !== ($entry = readdir($handle))){
                    if($entry != "." && $entry != ".." && pathinfo($entry, PATHINFO_EXTENSION) == 'zip'){
                        $backup_files[] = [
                            'name' => $entry,
                            'size' => filesize($backup_dir . $entry),
                            'modified' => filemtime($backup_dir . $entry),
                            'type' => 'zip'
                        ];
                    }
                }
                closedir($handle);
            }
            usort($backup_files, function($a, $b){
                return $b['modified'] - $a['modified'];
            });
            
            if(count($backup_files) > 10){
                $old_backups = array_slice($backup_files, 10);
                foreach($old_backups as $old){
                    @unlink($backup_dir . $old['name']);
                }
            }
            
            $msg = "success";
        } else {
            $msg = "error";
        }
    } else {
        $msg = "error";
    }
}

// =====================
// ADD FOLDER TO ZIP
// =====================
function addFolderToZip($folder, $zip, $zip_path = ''){
    $folder = rtrim($folder, '/');
    if(is_dir($folder)){
        $files = scandir($folder);
        foreach($files as $file){
            if($file != '.' && $file != '..'){
                $file_path = $folder . '/' . $file;
                if(is_dir($file_path)){
                    addFolderToZip($file_path, $zip, $zip_path . $file . '/');
                } else {
                    $zip->addFile($file_path, $zip_path . $file);
                }
            }
        }
    }
}

// =====================
// DOWNLOAD BACKUP
// =====================
if(isset($_GET['download'])){
    $file = basename($_GET['download']);
    $filepath = $backup_dir . $file;
    
    if(file_exists($filepath)){
        logActivity(
            $_SESSION['user_id'],
            $_SESSION['full_name'],
            'Backup Downloaded',
            'Downloaded backup: ' . $file
        );
        
        header('Content-Description: File Transfer');
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        readfile($filepath);
        exit();
    }
}

// =====================
// DELETE BACKUP
// =====================
if(isset($_GET['delete'])){
    $file = basename($_GET['delete']);
    $filepath = $backup_dir . $file;
    
    if(file_exists($filepath)){
        unlink($filepath);
        
        logActivity(
            $_SESSION['user_id'],
            $_SESSION['full_name'],
            'Backup Deleted',
            'Deleted backup: ' . $file
        );
        
        header("Location: backup.php?msg=deleted");
        exit();
    }
}

// =====================
// RESTORE BACKUP
// =====================
if(isset($_GET['restore'])){
    $file = basename($_GET['restore']);
    $filepath = $backup_dir . $file;
    
    if(file_exists($filepath) && pathinfo($file, PATHINFO_EXTENSION) == 'zip'){
        // Extract zip
        $zip = new ZipArchive();
        if($zip->open($filepath) === TRUE){
            $extract_dir = "../temp_restore/";
            if(!is_dir($extract_dir)){
                mkdir($extract_dir, 0777, true);
            }
            
            $zip->extractTo($extract_dir);
            $zip->close();
            
            // Find SQL file
            $sql_files = glob($extract_dir . 'database/*.sql');
            if(!empty($sql_files)){
                $sql_content = file_get_contents($sql_files[0]);
                $queries = array_filter(array_map('trim', explode(';', $sql_content)));
                
                $success = true;
                foreach($queries as $query){
                    if(!empty($query)){
                        if(!$conn->query($query)){
                            $success = false;
                            break;
                        }
                    }
                }
                
                if($success){
                    // Restore uploads
                    $uploads_dir = "../uploads/";
                    $backup_uploads = $extract_dir . 'uploads/';
                    if(is_dir($backup_uploads)){
                        copyFolder($backup_uploads, $uploads_dir);
                    }
                    
                    logActivity(
                        $_SESSION['user_id'],
                        $_SESSION['full_name'],
                        'Backup Restored',
                        'Restored backup: ' . $file
                    );
                    
                    // Clean up temp folder
                    deleteFolder($extract_dir);
                    
                    header("Location: backup.php?msg=restored");
                    exit();
                } else {
                    header("Location: backup.php?msg=restore_error");
                    exit();
                }
            }
        }
    }
}

// =====================
// COPY FOLDER
// =====================
function copyFolder($src, $dst){
    if(!is_dir($dst)){
        mkdir($dst, 0777, true);
    }
    $files = scandir($src);
    foreach($files as $file){
        if($file != '.' && $file != '..'){
            $src_path = $src . '/' . $file;
            $dst_path = $dst . '/' . $file;
            if(is_dir($src_path)){
                copyFolder($src_path, $dst_path);
            } else {
                copy($src_path, $dst_path);
            }
        }
    }
}

// =====================
// DELETE FOLDER
// =====================
function deleteFolder($folder){
    if(!is_dir($folder)) return;
    $files = array_diff(scandir($folder), array('.', '..'));
    foreach($files as $file){
        $path = $folder . '/' . $file;
        if(is_dir($path)){
            deleteFolder($path);
        } else {
            unlink($path);
        }
    }
    rmdir($folder);
}

$msg = $_GET['msg'] ?? '';
?>

<!DOCTYPE html>
<html>
<head>
<title>Backup System</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
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

.empty-state{
    text-align:center;
    padding:40px 20px;
    color:#94a3b8;
}

.empty-state i{
    font-size:40px;
    color:#e2e8f0;
    display:block;
    margin-bottom:10px;
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
    }
    .card-header{
        flex-direction:column;
        align-items:flex-start;
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
            <h2><i class="fas fa-database"></i> Backup System</h2>
            <p>Create and manage complete backups (database + files)</p>
        </div>
        <a href="?create=1" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create Backup
        </a>
    </div>

    <!-- ALERT MESSAGES -->
    <?php if($msg == 'success'): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> Complete backup created successfully! (Database + Files)
        </div>
    <?php elseif($msg == 'deleted'): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> Backup deleted successfully!
        </div>
    <?php elseif($msg == 'restored'): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> Backup restored successfully!
        </div>
    <?php elseif($msg == 'error'): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> Failed to create backup. Please check folder permissions.
        </div>
    <?php elseif($msg == 'restore_error'): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> Failed to restore backup.
        </div>
    <?php endif; ?>

    <!-- BACKUP FILES LIST -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-list"></i> Backup Files</h3>
            <span style="color:#64748b;font-size:14px;">Total: <?= count($backup_files) ?> backups</span>
        </div>

        <?php if(count($backup_files) > 0): ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>File Name</th>
                        <th>Size</th>
                        <th>Date Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($backup_files as $file): ?>
                    <tr>
                        <td><i class="fas fa-file-archive" style="color:#2563eb;"></i> <?= htmlspecialchars($file['name']) ?></td>
                        <td><?= number_format($file['size'] / 1024, 2) ?> KB</td>
                        <td><?= date('M j, Y g:i A', $file['modified']) ?></td>
                        <td>
                            <div style="display:flex; gap:5px; flex-wrap:wrap;">
                                <a href="?download=<?= urlencode($file['name']) ?>" class="btn btn-success btn-sm">
                                    <i class="fas fa-download"></i> Download
                                </a>
                                <a href="?restore=<?= urlencode($file['name']) ?>" class="btn btn-warning btn-sm" onclick="return confirm('Restore this backup? This will overwrite current data and files!')">
                                    <i class="fas fa-undo"></i> Restore
                                </a>
                                <a href="?delete=<?= urlencode($file['name']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this backup?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>No backup files found. Click "Create Backup" to create your first complete backup.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- BACKUP INFO -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-info-circle"></i> Backup Information</h3>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
            <div style="padding:15px; background:#f8fafc; border-radius:8px; border:1px solid #e2e8f0;">
                <strong><i class="fas fa-hdd" style="color:#2563eb;"></i> Backup Location</strong>
                <p style="margin:5px 0 0; color:#64748b; font-size:13px;"><?= realpath($backup_dir) ?></p>
            </div>
            <div style="padding:15px; background:#f8fafc; border-radius:8px; border:1px solid #e2e8f0;">
                <strong><i class="fas fa-file" style="color:#2563eb;"></i> Total Backups</strong>
                <p style="margin:5px 0 0; color:#64748b; font-size:13px;"><?= count($backup_files) ?> files</p>
            </div>
            <div style="padding:15px; background:#f8fafc; border-radius:8px; border:1px solid #e2e8f0;">
                <strong><i class="fas fa-clock" style="color:#2563eb;"></i> Latest Backup</strong>
                <p style="margin:5px 0 0; color:#64748b; font-size:13px;">
                    <?php if(!empty($backup_files)): ?>
                        <?= date('M j, Y g:i A', $backup_files[0]['modified']) ?>
                    <?php else: ?>
                        No backups yet
                    <?php endif; ?>
                </p>
            </div>
            <div style="padding:15px; background:#f8fafc; border-radius:8px; border:1px solid #e2e8f0;">
                <strong><i class="fas fa-database" style="color:#2563eb;"></i> Total Size</strong>
                <p style="margin:5px 0 0; color:#64748b; font-size:13px;">
                    <?php 
                    $total_size = 0;
                    foreach($backup_files as $file){
                        $total_size += $file['size'];
                    }
                    echo number_format($total_size / 1024, 2) . ' KB';
                    ?>
                </p>
            </div>
        </div>
    </div>

</div>

<?php include("../footer/footer.php"); ?>

</body>
</html>