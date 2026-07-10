<?php
session_start();
include("../config/db.php");
include("../config/functions.php"); // ← IMEONGEZWA

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* AUTH */
if(!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') != 'suggestion_manager'){
    header("Location: ../login.php");
    exit();
}

/* ================= ADD CATEGORY ================= */
if(isset($_POST['add_category'])){
    $category_name = trim($_POST['category_name']);
    
    if($category_name != ""){
        $stmt = $conn->prepare("INSERT INTO categories (category_name, created_at) VALUES (?, NOW())");
        $stmt->bind_param("s", $category_name);
        $stmt->execute();
        $stmt->close();
        
        // =====================
        // REKODI CATEGORY ADDED
        // =====================
        logActivity(
            $_SESSION['user_id'],
            $_SESSION['full_name'],
            'Category Added',
            'Added new category: ' . $category_name
        );
        
        header("Location: categories.php?msg=added");
        exit();
    }
}

/* ================= DELETE CATEGORY ================= */
if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];
    
    // Get category name before deleting
    $cat_query = $conn->query("SELECT category_name FROM categories WHERE category_id = $id");
    $cat_name = $cat_query->fetch_assoc()['category_name'] ?? 'Unknown';
    
    // Check if category has suggestions
    $check = $conn->prepare("SELECT COUNT(*) as total FROM suggestions WHERE category_id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $result = $check->get_result()->fetch_assoc();
    
    if($result['total'] > 0){
        header("Location: categories.php?msg=error_has_suggestions");
        exit();
    }
    
    $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    // =====================
    // REKODI CATEGORY DELETED
    // =====================
    logActivity(
        $_SESSION['user_id'],
        $_SESSION['full_name'],
        'Category Deleted',
        'Deleted category: ' . $cat_name
    );
    
    header("Location: categories.php?msg=deleted");
    exit();
}

/* ================= EDIT CATEGORY ================= */
if(isset($_POST['edit_category'])){
    $id = (int)$_POST['category_id'];
    $category_name = trim($_POST['category_name']);
    
    if($category_name != ""){
        // Get old name before updating
        $old_query = $conn->query("SELECT category_name FROM categories WHERE category_id = $id");
        $old_name = $old_query->fetch_assoc()['category_name'] ?? 'Unknown';
        
        $stmt = $conn->prepare("UPDATE categories SET category_name = ? WHERE category_id = ?");
        $stmt->bind_param("si", $category_name, $id);
        $stmt->execute();
        $stmt->close();
        
        // =====================
        // REKODI CATEGORY UPDATED
        // =====================
        logActivity(
            $_SESSION['user_id'],
            $_SESSION['full_name'],
            'Category Updated',
            'Updated category: ' . $old_name . ' → ' . $category_name
        );
        
        header("Location: categories.php?msg=updated");
        exit();
    }
}

/* ================= FETCH CATEGORIES ================= */
$categories = $conn->query("SELECT * FROM categories ORDER BY category_id DESC");

/* ================= GET MESSAGE ================= */
$msg = $_GET['msg'] ?? '';
?>

<!DOCTYPE html>
<html>
<head>
<title>Manage Categories</title>

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

/* ================= ALERT MESSAGES ================= */
.alert-msg{
    padding:12px 20px;
    border-radius:8px;
    margin-bottom:20px;
    display:flex;
    align-items:center;
    gap:10px;
}

.alert-success{
    background:#d1fae5;
    color:#065f46;
    border:1px solid #a7f3d0;
}

.alert-error{
    background:#fee2e2;
    color:#991b1b;
    border:1px solid #fecaca;
}

/* ================= CARDS ================= */
.card{
    background:white;
    padding:25px;
    border-radius:16px;
    border:1px solid #e2e8f0;
    box-shadow:0 4px 12px rgba(0,0,0,.06);
    margin-bottom:25px;
}

.card-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:15px;
}

.card-header h3{
    margin:0;
    color:#111827;
    display:flex;
    gap:10px;
    align-items:center;
}

/* ================= FORM ================= */
.form-group{
    margin-bottom:15px;
}

.form-group label{
    display:block;
    font-weight:600;
    margin-bottom:5px;
    color:#374151;
    font-size:13px;
}

.form-group input{
    width:100%;
    padding:10px 14px;
    border-radius:8px;
    border:1px solid #cbd5e1;
    font-size:14px;
    box-sizing:border-box;
    transition:border-color 0.2s;
}

.form-group input:focus{
    outline:none;
    border-color:#111827;
    box-shadow:0 0 0 3px rgba(17,24,39,0.08);
}

.btn{
    padding:10px 24px;
    border:none;
    border-radius:8px;
    cursor:pointer;
    font-weight:600;
    font-size:14px;
    transition:background 0.2s;
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

.action-buttons{
    display:flex;
    gap:5px;
    flex-wrap:wrap;
}

/* ================= MODAL ================= */
.modal{
    display:none;
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.5);
    z-index:9999;
    align-items:center;
    justify-content:center;
}

.modal.show{
    display:flex;
}

.modal-content{
    background:white;
    padding:30px;
    border-radius:16px;
    width:500px;
    max-width:90%;
}

.modal-content h4{
    margin-top:0;
    color:#111827;
}

.modal-buttons{
    display:flex;
    gap:10px;
    margin-top:15px;
}

.modal-buttons .btn-cancel{
    background:#e2e8f0;
    color:#1e293b;
}

.modal-buttons .btn-cancel:hover{
    background:#cbd5e1;
}

/* ================= RESPONSIVE ================= */
@media(max-width:900px){
    .content{
        margin-left:0;
    }
    .card-header{
        flex-direction:column;
        align-items:flex-start;
        gap:10px;
    }
    .action-buttons{
        flex-direction:column;
    }
}
</style>
</head>

<body>

<?php include("../sider/sider.php"); ?>
<?php include("../toper/toper.php"); ?>

<div class="content">

    <!-- ================= ALERT MESSAGES ================= -->
    <?php if($msg == 'added'): ?>
        <div class="alert-msg alert-success">
            <i class="fas fa-check-circle"></i> Category added successfully!
        </div>
    <?php elseif($msg == 'deleted'): ?>
        <div class="alert-msg alert-success">
            <i class="fas fa-check-circle"></i> Category deleted successfully!
        </div>
    <?php elseif($msg == 'updated'): ?>
        <div class="alert-msg alert-success">
            <i class="fas fa-check-circle"></i> Category updated successfully!
        </div>
    <?php elseif($msg == 'error_has_suggestions'): ?>
        <div class="alert-msg alert-error">
            <i class="fas fa-exclamation-circle"></i> Cannot delete category because it has suggestions!
        </div>
    <?php endif; ?>

    <!-- ================= ADD CATEGORY CARD ================= -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-folder-plus" style="color:#2563eb;"></i> Add Category</h3>
        </div>

        <form method="POST">
            <div class="form-group">
                <label><i class="fas fa-tag"></i> Category Name</label>
                <input type="text" name="category_name" placeholder="Enter category name" required>
            </div>
            <button type="submit" name="add_category" class="btn btn-success">
                <i class="fas fa-plus"></i> Add Category
            </button>
        </form>
    </div>

    <!-- ================= CATEGORIES LIST CARD ================= -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-list" style="color:#2563eb;"></i> Categories</h3>
            <span style="color:#64748b;font-size:14px;">Total: <?= $categories->num_rows ?></span>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Category Name</th>
                    <th>Created At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if($categories && $categories->num_rows > 0): ?>
                    <?php while($row = $categories->fetch_assoc()): ?>
                    <tr>
                        <td>#<?= $row['category_id'] ?></td>
                        <td><strong><?= htmlspecialchars($row['category_name']) ?></strong></td>
                        <td><?= date('M j, Y g:i A', strtotime($row['created_at'])) ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-warning btn-sm" onclick="openEditModal(<?= $row['category_id'] ?>, '<?= addslashes($row['category_name']) ?>')">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <a href="?delete=<?= $row['category_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this category?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align:center;padding:30px;color:#94a3b8;">
                            <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                            No categories found. Add your first category!
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- ================= EDIT CATEGORY MODAL ================= -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <h4><i class="fas fa-edit" style="color:#2563eb;"></i> Edit Category</h4>
        <form method="POST">
            <input type="hidden" name="category_id" id="edit_category_id">
            <div class="form-group">
                <label><i class="fas fa-tag"></i> Category Name</label>
                <input type="text" name="category_name" id="edit_category_name" required>
            </div>
            <div class="modal-buttons">
                <button type="submit" name="edit_category" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update
                </button>
                <button type="button" class="btn btn-cancel" onclick="closeEditModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// ================= EDIT MODAL =================
function openEditModal(id, name){
    document.getElementById('edit_category_id').value = id;
    document.getElementById('edit_category_name').value = name;
    document.getElementById('editModal').classList.add('show');
}

function closeEditModal(){
    document.getElementById('editModal').classList.remove('show');
}

// ================= CLOSE MODAL ON OUTSIDE CLICK =================
window.onclick = function(event) {
    let modal = document.getElementById('editModal');
    if (event.target == modal) {
        modal.classList.remove('show');
    }
}

// ================= CLOSE MODAL WITH ESC KEY =================
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeEditModal();
    }
});
</script>

<?php include("../footer/footer.php"); ?>

</body>
</html>