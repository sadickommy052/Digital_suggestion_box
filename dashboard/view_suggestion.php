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
$role = $_SESSION['role'] ?? '';
$suggestion_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ================= CHECK IF SUGGESTION EXISTS =================
$check = $conn->prepare("
    SELECT s.*, u.full_name, u.profile_picture, c.category_name
    FROM suggestions s
    JOIN users u ON u.user_id = s.user_id
    LEFT JOIN categories c ON c.category_id = s.category_id
    WHERE s.suggestion_id = ?
");
$check->bind_param("i", $suggestion_id);
$check->execute();
$suggestion = $check->get_result()->fetch_assoc();

if(!$suggestion) {
    if($role == 'admin' || $role == 'suggestion_manager') {
        header("Location: all_suggestions.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

// ================= CHECK PERMISSIONS =================
if($role == 'suggester' && $suggestion['user_id'] != $user_id) {
    header("Location: dashboard.php");
    exit();
}

if($role != 'admin' && $role != 'suggestion_manager' && $role != 'suggester') {
    header("Location: ../login.php");
    exit();
}

// ================= ADD COMMENT =================
if(isset($_POST['add_comment'])){
    $comment = trim($_POST['comment']);
    
    if(!empty($comment)){
        $stmt = $conn->prepare("
            INSERT INTO comments (suggestion_id, user_id, comment, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->bind_param("iis", $suggestion_id, $user_id, $comment);
        $stmt->execute();
        $stmt->close();
        
        // Send notification to suggestion owner
        if($suggestion['user_id'] != $user_id){
            $notify_title = "New Comment";
            $notify_msg = "Someone commented on your suggestion: " . $suggestion['title'];
            $notify_type = "new_comment";
            
            $n = $conn->prepare("
                INSERT INTO notifications (user_id, title, message, type, is_read, created_at)
                VALUES (?, ?, ?, ?, 0, NOW())
            ");
            $n->bind_param("isss", $suggestion['user_id'], $notify_title, $notify_msg, $notify_type);
            $n->execute();
            $n->close();
        }
        
        header("Location: view_suggestion.php?id=" . $suggestion_id);
        exit();
    }
}

// ================= DELETE COMMENT =================
if(isset($_GET['delete_comment'])){
    $comment_id = (int)$_GET['delete_comment'];
    
    $check_comment = $conn->prepare("SELECT user_id FROM comments WHERE comment_id=?");
    $check_comment->bind_param("i", $comment_id);
    $check_comment->execute();
    $comment_owner = $check_comment->get_result()->fetch_assoc();
    
    if($comment_owner && ($comment_owner['user_id'] == $user_id || $role == 'admin' || $role == 'suggestion_manager')){
        $stmt = $conn->prepare("DELETE FROM comments WHERE comment_id=?");
        $stmt->bind_param("i", $comment_id);
        $stmt->execute();
        $stmt->close();
    }
    
    header("Location: view_suggestion.php?id=" . $suggestion_id);
    exit();
}

// ================= EDIT COMMENT =================
if(isset($_POST['edit_comment'])){
    $comment_id = (int)$_POST['comment_id'];
    $comment = trim($_POST['comment']);
    
    $check_comment = $conn->prepare("SELECT user_id FROM comments WHERE comment_id=?");
    $check_comment->bind_param("i", $comment_id);
    $check_comment->execute();
    $comment_owner = $check_comment->get_result()->fetch_assoc();
    
    if($comment_owner && $comment_owner['user_id'] == $user_id && !empty($comment)){
        $stmt = $conn->prepare("
            UPDATE comments 
            SET comment=?, updated_at=NOW() 
            WHERE comment_id=?
        ");
        $stmt->bind_param("si", $comment, $comment_id);
        $stmt->execute();
        $stmt->close();
    }
    
    header("Location: view_suggestion.php?id=" . $suggestion_id);
    exit();
}

// ================= FETCH COMMENTS =================
$comments = $conn->prepare("
    SELECT 
        c.*,
        u.full_name,
        u.profile_picture,
        u.role
    FROM comments c
    JOIN users u ON u.user_id = c.user_id
    WHERE c.suggestion_id = ?
    ORDER BY c.created_at ASC
");
$comments->bind_param("i", $suggestion_id);
$comments->execute();
$comments_result = $comments->get_result();
$comment_count = $comments_result->num_rows;
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>View Suggestion</title>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
/* ================= RESET - USIATHIRI SIDER ================= */
/* Usiweke * { margin: 0; padding: 0; box-sizing: border-box; } hapa */

body{
    margin:0;
    font-family:'Segoe UI',sans-serif;
    background:#f8fafc;
    color:#1e293b;
}

/* ================= CONTENT ================= */
.content{
    margin-left:250px;
    padding:30px;
    padding-top:100px;
}

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
}

.suggestion-meta{
    display:flex;
    gap:20px;
    flex-wrap:wrap;
    margin-bottom:15px;
    font-size:14px;
    color:#64748b;
}

.suggestion-meta i{
    margin-right:5px;
}

.suggestion-meta .badge{
    padding:4px 12px;
    border-radius:999px;
    font-size:12px;
    font-weight:600;
}

.badge-pending{ background:#fef3c7; color:#92400e; }
.badge-approved{ background:#dcfce7; color:#166534; }
.badge-rejected{ background:#fee2e2; color:#991b1b; }
.badge-implemented{ background:#dbeafe; color:#1e40af; }

.suggestion-body{
    font-size:15px;
    line-height:1.8;
    color:#1e293b;
}

/* ================= COMMENTS SECTION ================= */
.comments-section {
    margin-top: 0;
}

.comments-section h4 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #111827;
}

.comment-count{
    color:#64748b;
    font-size:14px;
    font-weight:400;
}

.comment-box {
    padding: 15px;
    border-bottom: 1px solid #e2e8f0;
    margin-bottom: 15px;
}

.comment-box:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.comment-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 8px;
}

.comment-user {
    display: flex;
    align-items: center;
    gap: 10px;
}

.avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
}

.avatar-placeholder {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #111827;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
}

.role-badge {
    background: #e0e7ff;
    color: #3730a3;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 600;
    margin-left: 5px;
}

.comment-time {
    font-size: 11px;
    color: #94a3b8;
}

.edited {
    color: #94a3b8;
    font-style: italic;
    margin-left: 5px;
}

.comment-body {
    margin-left: 46px;
    color: #1e293b;
    line-height: 1.6;
}

.comment-actions {
    display: flex;
    gap: 5px;
}

.btn-edit-comment,
.btn-delete-comment {
    background: none;
    border: none;
    color: #94a3b8;
    font-size: 12px;
    cursor: pointer;
    padding: 2px 8px;
    text-decoration:none;
}

.btn-edit-comment:hover {
    color: #2563eb;
}

.btn-delete-comment:hover {
    color: #dc2626;
}

/* ================= ADD COMMENT ================= */
.add-comment {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
}

.add-comment h5 {
    margin-top: 0;
    margin-bottom: 10px;
    color: #111827;
}

.add-comment textarea {
    width: 100%;
    padding: 10px 14px;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    min-height: 80px;
    resize: vertical;
    font-family: inherit;
    font-size: 14px;
    box-sizing: border-box;
}

.add-comment textarea:focus {
    outline: none;
    border-color: #111827;
    box-shadow: 0 0 0 3px rgba(17,24,39,0.08);
}

.add-comment button {
    margin-top: 10px;
    padding: 10px 24px;
    background: #111827;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
}

.add-comment button:hover {
    background: #1f2937;
}

.no-comments {
    text-align: center;
    padding: 40px 20px;
    color: #94a3b8;
}

.no-comments i {
    font-size: 40px;
    display: block;
    margin-bottom: 10px;
}

/* ================= BUTTONS ================= */
.btn-back {
    display: inline-block;
    padding: 10px 20px;
    background: #e2e8f0;
    color: #1e293b;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
}

.btn-back:hover {
    background: #cbd5e1;
}

.btn-back-manager {
    background: #2563eb;
    color: white;
}

.btn-back-manager:hover {
    background: #1d4ed8;
}

/* ================= MODAL ================= */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}

.modal.show {
    display: flex;
}

.modal-content {
    background: white;
    padding: 30px;
    border-radius: 16px;
    width: 500px;
    max-width: 90%;
}

.modal-content h4 {
    margin-top: 0;
    color: #111827;
}

.modal-content textarea {
    width: 100%;
    padding: 10px 14px;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    min-height: 100px;
    font-family: inherit;
    font-size: 14px;
    box-sizing: border-box;
    margin: 10px 0;
}

.modal-content textarea:focus {
    outline: none;
    border-color: #111827;
}

.modal-buttons {
    display: flex;
    gap: 10px;
}

.modal-buttons button {
    padding: 10px 24px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
}

.modal-buttons .btn-update {
    background: #111827;
    color: white;
}

.modal-buttons .btn-update:hover {
    background: #1f2937;
}

.modal-buttons .btn-cancel {
    background: #e2e8f0;
    color: #1e293b;
}

.modal-buttons .btn-cancel:hover {
    background: #cbd5e1;
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
}

@media(max-width:600px){
    .content{
        padding:15px;
        padding-top:80px;
    }
    .comment-header{
        flex-direction:column;
        gap:5px;
    }
    .comment-body{
        margin-left:0;
    }
    .modal-content{
        padding:20px;
    }
}
</style>
</head>

<body>

<?php include("../sider/sider.php"); ?>
<?php include("../toper/toper.php"); ?>

<div class="content">

    <!-- SUGGESTION DETAILS -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-lightbulb" style="color:#2563eb;"></i> <?= htmlspecialchars($suggestion['title']) ?></h3>
            
            <?php if($role == 'admin' || $role == 'suggestion_manager'): ?>
                <a href="../manager/all_suggestions.php" class="btn-back btn-back-manager">
                    <i class="fas fa-arrow-left"></i> Back to All Suggestions
                </a>
            <?php else: ?>
              
            <?php endif; ?>
        </div>
        
        <div class="suggestion-meta">
            <span><i class="fas fa-user"></i> <?= htmlspecialchars($suggestion['full_name']) ?></span>
            <span><i class="fas fa-tag"></i> <?= htmlspecialchars($suggestion['category_name'] ?? 'Uncategorized') ?></span>
            <span><i class="fas fa-calendar-alt"></i> <?= date('M j, Y g:i A', strtotime($suggestion['created_at'])) ?></span>
            <span>
                <span class="badge badge-<?= $suggestion['status'] ?>">
                    <?= ucfirst($suggestion['status']) ?>
                </span>
            </span>
        </div>
        
        <div class="suggestion-body">
            <?= nl2br(htmlspecialchars($suggestion['message'])) ?>
        </div>
    </div>

    <!-- COMMENTS SECTION -->
    <div class="card comments-section">
        <h4>
            <i class="fas fa-comments" style="color:#2563eb;"></i> 
            Comments <span class="comment-count">(<?= $comment_count ?>)</span>
        </h4>
        
        <?php if($comment_count > 0): ?>
            <?php while($comment = $comments_result->fetch_assoc()): ?>
            <div class="comment-box" id="comment-<?= $comment['comment_id'] ?>">
                
                <div class="comment-header">
                    <div class="comment-user">
                        <?php if(!empty($comment['profile_picture'])): ?>
                            <img src="/Digital_suggestion_box/<?= $comment['profile_picture'] ?>" class="avatar">
                        <?php else: ?>
                            <div class="avatar-placeholder">
                                <?= strtoupper(substr($comment['full_name'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <div>
                            <strong><?= htmlspecialchars($comment['full_name']) ?></strong>
                            <?php if($comment['role'] == 'admin' || $comment['role'] == 'suggestion_manager'): ?>
                                <span class="role-badge"><?= ucfirst($comment['role']) ?></span>
                            <?php endif; ?>
                            <div class="comment-time">
                                <i class="fas fa-clock"></i>
                                <?= date('M j, Y g:i A', strtotime($comment['created_at'])) ?>
                                <?php if($comment['created_at'] != $comment['updated_at']): ?>
                                    <span class="edited">(Edited)</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if($user_id == $comment['user_id'] || $role == 'admin' || $role == 'suggestion_manager'): ?>
                    <div class="comment-actions">
                        <?php if($user_id == $comment['user_id']): ?>
                            <button class="btn-edit-comment" onclick="openEditComment(<?= $comment['comment_id'] ?>, '<?= addslashes($comment['comment']) ?>')">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                        <?php endif; ?>
                        <a href="?delete_comment=<?= $comment['comment_id'] ?>" class="btn-delete-comment" onclick="return confirm('Delete this comment?')">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="comment-body">
                    <?= nl2br(htmlspecialchars($comment['comment'])) ?>
                </div>
                
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-comments">
                <i class="fas fa-comment-slash"></i>
                <p>No comments yet. Be the first to comment!</p>
            </div>
        <?php endif; ?>
        
        <!-- ADD COMMENT FORM -->
        <div class="add-comment">
            <h5><i class="fas fa-pen"></i> Add Comment</h5>
            <form method="POST">
                <input type="hidden" name="suggestion_id" value="<?= $suggestion_id ?>">
                <textarea name="comment" placeholder="Write your comment here..." required></textarea>
                <button type="submit" name="add_comment">
                    <i class="fas fa-paper-plane"></i> Post Comment
                </button>
            </form>
        </div>
    </div>

</div>

<!-- EDIT COMMENT MODAL -->
<div id="editCommentModal" class="modal">
    <div class="modal-content">
        <h4><i class="fas fa-edit"></i> Edit Comment</h4>
        <form method="POST">
            <input type="hidden" name="comment_id" id="edit_comment_id">
            <textarea name="comment" id="edit_comment_text" required></textarea>
            <div class="modal-buttons">
                <button type="submit" name="edit_comment" class="btn-update">
                    <i class="fas fa-save"></i> Update
                </button>
                <button type="button" class="btn-cancel" onclick="closeEditComment()">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditComment(id, text){
    document.getElementById('edit_comment_id').value = id;
    document.getElementById('edit_comment_text').value = text;
    document.getElementById('editCommentModal').classList.add('show');
}

function closeEditComment(){
    document.getElementById('editCommentModal').classList.remove('show');
}

window.onclick = function(event) {
    let modal = document.getElementById('editCommentModal');
    if (event.target == modal) {
        modal.classList.remove('show');
    }
}
</script>

<?php include("../footer/footer.php"); ?>

</body>
</html> 