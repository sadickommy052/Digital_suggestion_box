<?php
session_start();
include("../config/db.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* AUTH */
if(!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') != 'suggestion_manager'){
    header("Location: ../login.php");
    exit();
}

/* ================= AJAX DELETE ================= */
if(isset($_POST['ajax_delete'])){
    $id = (int)$_POST['id'];

    $stmt = $conn->prepare("DELETE FROM categories WHERE category_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(["status"=>"success"]);
    exit();
}

/* ================= AJAX UPDATE ================= */
if(isset($_POST['ajax_update'])){
    $id = (int)$_POST['id'];
    $name = trim($_POST['name']);

    $stmt = $conn->prepare("UPDATE categories SET category_name=? WHERE category_id=?");
    $stmt->bind_param("si", $name, $id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(["status"=>"success"]);
    exit();
}

/* ADD */
if(isset($_POST['add_category'])){
    $name = trim($_POST['category_name']);

    if($name != ""){
        $stmt = $conn->prepare("INSERT INTO categories(category_name) VALUES(?)");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: categories.php");
    exit();
}

/* FETCH */
$categories = $conn->query("SELECT * FROM categories ORDER BY category_id DESC");
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
}


/* CARD */

.card{

    background:white;

    padding:25px;

    border-radius:16px;

    border:1px solid #e2e8f0;

    box-shadow:0 4px 12px rgba(0,0,0,.06);

    margin-bottom:25px;

}



/* TITLE */

h3{

    margin-top:0;

    color:#2563eb;

    display:flex;

    gap:10px;

    align-items:center;

}



/* INPUT */

input{

    width:100%;

    padding:13px;

    border-radius:10px;

    border:1px solid #cbd5e1;

    background:white;

    color:#1e293b;

    outline:none;

    margin-bottom:10px;

    box-sizing:border-box;

}




/* BUTTON */

button{

    margin-top:10px;

    background:#2563eb;

    color:white;

    border:none;

    padding:12px 20px;

    border-radius:10px;

    cursor:pointer;

    font-weight:600;

}



/* TABLE */

table{

    width:100%;

    border-collapse:collapse;

    background:white;

}



th{

    background:#2563eb;

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




/* ACTION */

.action{

    display:flex;

    gap:10px;

}



.btn{

    width:36px;

    height:36px;

    display:flex;

    justify-content:center;

    align-items:center;

    border-radius:8px;

    cursor:pointer;

    background:#f1f5f9;

}



.edit{

    color:none;

   

}


.delete{

    color:none;


}



/* MODAL */

.modal{

    display:none;

    position:fixed;

    inset:0;

    background:rgba(0,0,0,.4);

    align-items:center;

    justify-content:center;

    z-index:9999;

}



.modal-content{

    background:white;

    padding:25px;

    border-radius:15px;

    width:350px;

    border:1px solid #e2e8f0;

}



/* RESPONSIVE */

@media(max-width:900px){

.content{

margin-left:0;

}

}


</style>
</head>

<body>

<?php include("../sider/sider.php"); ?>
<?php include("../toper/toper.php"); ?>

<div class="content">

<!-- ADD -->
<div class="card">
<h3><i class="fas fa-folder-plus"></i> Add Category</h3>

<form method="POST">
    <input type="text" name="category_name" placeholder="Enter category name" required>
    <button type="submit" name="add_category">
        <i class="fas fa-plus"></i> Add
    </button>
</form>
</div>

<!-- LIST -->
<div class="card">
<h3><i class="fas fa-list"></i> Categories</h3>

<table id="catTable">
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Action</th>
</tr>

<?php while($row = $categories->fetch_assoc()){ ?>
<tr id="row-<?= $row['category_id'] ?>">
    <td><?= $row['category_id'] ?></td>
    <td class="cat-name"><?= htmlspecialchars($row['category_name']) ?></td>

    <td class="action">

        <div class="btn edit"
             onclick="openEdit(<?= $row['category_id'] ?>,'<?= htmlspecialchars($row['category_name']) ?>')">
            <i class="fas fa-pen"></i>
        </div>

        <div class="btn delete"
             onclick="deleteCategory(<?= $row['category_id'] ?>)">
            <i class="fas fa-trash"></i>
        </div>

    </td>
</tr>
<?php } ?>

</table>
</div>

</div>

<!-- MODAL -->
<div class="modal" id="editModal">
    <div class="modal-content">

        <h3>Edit Category</h3>

        <input type="hidden" id="edit_id">
        <input type="text" id="edit_name">

        <button onclick="saveUpdate()">Update</button>
        <button onclick="closeModal()" style="margin-top:10px;background:#374151;">Close</button>

    </div>
</div>

<script>

/* DELETE AJAX */
function deleteCategory(id){
    if(!confirm("Delete this category?")) return;

    fetch("",{
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:"ajax_delete=1&id="+id
    })
    .then(r=>r.json())
    .then(data=>{
        if(data.status==="success"){
            document.getElementById("row-"+id).remove();
        }
    });
}

/* OPEN EDIT */
function openEdit(id,name){
    document.getElementById("edit_id").value=id;
    document.getElementById("edit_name").value=name;
    document.getElementById("editModal").style.display="flex";
}

/* SAVE UPDATE */
function saveUpdate(){

    let id=document.getElementById("edit_id").value;
    let name=document.getElementById("edit_name").value;

    fetch("",{
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:"ajax_update=1&id="+id+"&name="+encodeURIComponent(name)
    })
    .then(r=>r.json())
    .then(data=>{
        if(data.status==="success"){
            location.reload(); // can also update row live
        }
    });

}

/* CLOSE MODAL */
function closeModal(){
    document.getElementById("editModal").style.display="none";
}

/* CLICK OUTSIDE */
window.onclick=function(e){
    let m=document.getElementById("editModal");
    if(e.target==m){
        m.style.display="none";
    }
}

</script>

</body>
</html>