<?php
session_start();
include("../config/db.php");

// =====================
// PROTECTION
// =====================
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] !== 'suggester') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// =====================
// UPDATE SUGGESTION
// =====================
if (isset($_POST['update_suggestion'])) {

    $id = $_POST['id'];
    $title = $_POST['title'];
    $message = $_POST['message'];
    $category = $_POST['category'];

    $stmt = $conn->prepare("
        UPDATE suggestions
        SET title = ?, message = ?, category_id = ?
        WHERE suggestion_id = ? AND user_id = ? AND status = 'pending'
    ");

    $stmt->bind_param("ssiii", $title, $message, $category, $id, $user_id);
    $stmt->execute();

    header("Location: my_suggestions.php");
    exit();
}

// =====================
// LOAD EDIT DATA
// =====================
$editData = null;

if (isset($_GET['edit'])) {

    $edit_id = $_GET['edit'];

    $stmt = $conn->prepare("
        SELECT suggestion_id, title, message, category_id
        FROM suggestions
        WHERE suggestion_id = ? AND user_id = ? AND status = 'pending'
    ");

    $stmt->bind_param("ii", $edit_id, $user_id);
    $stmt->execute();

    $editData = $stmt->get_result()->fetch_assoc();
}

// =====================
// FETCH ALL SUGGESTIONS
// =====================
$stmt = $conn->prepare("
    SELECT s.suggestion_id, s.title, s.message, s.status, s.created_at, c.category_name
    FROM suggestions s
    LEFT JOIN categories c ON s.category_id = c.category_id
    WHERE s.user_id = ?
    ORDER BY s.created_at DESC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<title>My Suggestions</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>

body{
    margin:0;
    font-family:'Segoe UI', sans-serif;
    background:whitesmoke;
}

.content{
    margin-left:220px;
    padding:20px;
}

.card{
    border:none;
    border-radius:16px;
    box-shadow:0 5px 25px rgba(0,0,0,0.08);
    background:white;
}

.text-muted{
    color:gray;
}

.table thead{
    background:whitesmoke;
}

</style>

</head>

<body>

<?php include("../sider/sider.php"); ?>

<div class="content">

<div class="card p-3 mb-3">
    <h4 class="mb-0">
        <i class="fas fa-list text-primary"></i>
        My Suggestions
    </h4>
</div>

<!-- EDIT FORM -->
<?php if ($editData) { ?>

<div class="card p-4 mb-4">

    <h5 class="mb-2">Edit Suggestion</h5>

    <form method="POST">

        <input type="hidden" name="id" value="<?php echo $editData['suggestion_id']; ?>">

        <div class="mb-3">
            <label>Title</label>
            <input type="text" name="title" class="form-control"
                   value="<?php echo htmlspecialchars($editData['title']); ?>" required>
        </div>

        <div class="mb-3">
            <label>Description</label>
            <textarea name="message" class="form-control" rows="5" required>
                <?php echo htmlspecialchars($editData['message']); ?>
            </textarea>
        </div>

        <div class="mb-3">
            <label>Category</label>
            <select name="category" class="form-control" required>

                <?php
                $cat = $conn->query("SELECT * FROM categories");
                while($c = $cat->fetch_assoc()){
                ?>
                    <option value="<?php echo $c['category_id']; ?>"
                        <?php if($editData['category_id'] == $c['category_id']) echo "selected"; ?>>
                        <?php echo $c['category_name']; ?>
                    </option>
                <?php } ?>

            </select>
        </div>

        <button type="submit" name="update_suggestion" class="btn btn-primary">
            Update Suggestion
        </button>

    </form>

</div>

<?php } ?>

<!-- TABLE -->
<div class="card p-3">

<table class="table table-hover align-middle">

<thead>
<tr>
    <th>Title</th>
    <th>Category</th>
    <th>Message</th>
    <th>Date</th>
    <th>Status</th>
    <th>Action</th>
</tr>
</thead>

<tbody>

<?php while ($row = $result->fetch_assoc()) { ?>

<tr>

    <td><?php echo htmlspecialchars($row['title']); ?></td>

    <td><?php echo htmlspecialchars($row['category_name']); ?></td>

    <td><?php echo htmlspecialchars($row['message']); ?></td>

    <td><?php echo date("d M Y", strtotime($row['created_at'])); ?></td>

    <td>
        <?php
        $status = strtolower($row['status']);

        if ($status == 'pending') {
            echo '<span class="badge bg-warning text-dark">Pending</span>';
        } elseif ($status == 'approved') {
            echo '<span class="badge bg-success">Approved</span>';
        } else {
            echo '<span class="badge bg-danger">Rejected</span>';
        }
        ?>
    </td>

    <td>
        <?php if ($status == 'pending') { ?>
            <a href="?edit=<?php echo $row['suggestion_id']; ?>" class="btn btn-sm btn-primary">Edit</a>
        <?php } else { ?>
            <span class="text-muted">Locked</span>
        <?php } ?>
    </td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

</body>
</html>