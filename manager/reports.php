<?php
session_start();
include("../config/db.php");

error_reporting(E_ALL);
ini_set('display_errors',1);

/* AUTH */
if(!isset($_SESSION['user_id']) || ($_SESSION['role']??'')!='suggestion_manager'){
    header("Location: ../login.php");
    exit();
}

/* ================= TCPDF ================= */
require_once('/usr/share/php/tcpdf/tcpdf.php');

if(!class_exists('TCPDF')){
    die("TCPDF not installed correctly");
}

/* ================= DATA ================= */

$total = $conn->query("SELECT COUNT(*) total FROM suggestions")->fetch_assoc()['total'];

$pending = $conn->query("SELECT COUNT(*) total FROM suggestions WHERE status='pending'")->fetch_assoc()['total'];

$approved = $conn->query("SELECT COUNT(*) total FROM suggestions WHERE status='approved'")->fetch_assoc()['total'];

$rejected = $conn->query("SELECT COUNT(*) total FROM suggestions WHERE status='rejected'")->fetch_assoc()['total'];

$implemented = $conn->query("SELECT COUNT(*) total FROM suggestions WHERE status='implemented'")->fetch_assoc()['total'];

/* ================= TOP VOTED (FIXED) ================= */
$top = $conn->query("
SELECT 
s.suggestion_id,
s.title,
SUM(CASE WHEN v.response='agree' THEN 1 ELSE 0 END) AS agree_votes,
SUM(CASE WHEN v.response='disagree' THEN 1 ELSE 0 END) AS disagree_votes,
COUNT(v.vote_id) AS total_votes
FROM suggestions s
LEFT JOIN votes v ON s.suggestion_id = v.suggestion_id
GROUP BY s.suggestion_id, s.title
ORDER BY total_votes DESC
LIMIT 10
");

/* ================= PDF GENERATION ================= */
if(isset($_GET['pdf'])){

    $pdf = new TCPDF();

    $pdf->SetCreator("Digital Suggestion Box");
    $pdf->SetTitle("Suggestion Report");

    $pdf->AddPage();
    $pdf->SetFont('helvetica','',12);

    $html = "
    <h2>Digital Suggestion Box Report</h2>

    <table border='1' cellpadding='6'>
        <tr><th>Total Suggestions</th><td>$total</td></tr>
        <tr><th>Pending</th><td>$pending</td></tr>
        <tr><th>Approved</th><td>$approved</td></tr>
        <tr><th>Rejected</th><td>$rejected</td></tr>
        <tr><th>Implemented</th><td>$implemented</td></tr>
    </table>

    <br><br>

    <h3>Top Suggestions By Votes</h3>

    <table border='1' cellpadding='5'>
        <tr>
            <th>Suggestion</th>
            <th>Votes</th>
        </tr>
    ";

    $top_pdf = $conn->query("
    SELECT 
    s.suggestion_id,
    s.title,
    SUM(CASE WHEN v.response='agree' THEN 1 ELSE 0 END) AS agree_votes,
    SUM(CASE WHEN v.response='disagree' THEN 1 ELSE 0 END) AS disagree_votes,
    COUNT(v.vote_id) AS total_votes
    FROM suggestions s
    LEFT JOIN votes v ON s.suggestion_id=v.suggestion_id
    GROUP BY s.suggestion_id, s.title
    ORDER BY total_votes DESC
    LIMIT 10
    ");

    while($r = $top_pdf->fetch_assoc()){
        $html .= "
        <tr>
            <td>".htmlspecialchars($r['title'])."</td>
            <td>
                Agree: ".$r['agree_votes']." | Disagree: ".$r['disagree_votes']."
            </td>
        </tr>
        ";
    }

    $html .= "</table>";

    $pdf->writeHTML($html);
    $pdf->Output("suggestion_report.pdf","I");
    exit();
}

?>

<!DOCTYPE html>
<html>
<head>
<title>Reports</title>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
body{
    margin:0;
    font-family:Segoe UI, sans-serif;
    background:#f4f7fb;
    color:#1e293b;
}

.content{
    margin-left:250px;
    padding:30px;
    padding-top:100px;
}

.cards{
    display:grid;
    grid-template-columns:repeat(5,1fr);
    gap:18px;
}

.card{
    padding:22px;
    border-radius:16px;
    text-align:center;
    background:white;
    border:1px solid #e2e8f0;
    box-shadow:0 8px 20px rgba(15,23,42,.08);
}

.card h2{
    font-size:30px;
    margin:0;
    color:#0f172a;
}

.report-btn{
    margin-top:30px;
    display:inline-block;
    background:#2563eb;
    padding:12px 22px;
    border-radius:10px;
    color:white;
    text-decoration:none;
    font-weight:600;
}

.box{
    background:white;
    margin-top:25px;
    padding:25px;
    border-radius:16px;
    border:1px solid #e2e8f0;
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:#eff6ff;
    color:#2563eb;
    padding:14px;
    text-align:left;
}

td{
    padding:14px;
    border-bottom:1px solid #e2e8f0;
}

/* VOTE UI (UNCHANGED STYLE) */
.vote-box{
    display:inline-flex;
    align-items:center;
    gap:6px;
    margin-right:10px;
    font-weight:600;
    color:#111827;
}

.agree{
    color:none;
}

.disagree{
    color:none;
}
</style>
</head>

<body>

<?php include("../sider/sider.php"); ?>
<?php include("../toper/toper.php"); ?>

<div class="content">

<h2><i class="fas fa-chart-line"></i> Suggestion Reports</h2>

<div class="cards">

<div class="card"><h2><?=$total?></h2>Total</div>
<div class="card"><h2><?=$pending?></h2>Pending</div>
<div class="card"><h2><?=$approved?></h2>Approved</div>
<div class="card"><h2><?=$rejected?></h2>Rejected</div>
<div class="card"><h2><?=$implemented?></h2>Implemented</div>

</div>

<a class="report-btn" href="?pdf=1">
<i class="fas fa-file-pdf"></i> Generate PDF
</a>

<div class="box">

<h3>Top Voted Suggestions</h3>

<table>
<tr>
<th>Suggestion</th>
<th>Votes</th>
</tr>

<?php while($r = $top->fetch_assoc()): ?>

<tr>
<td><?=htmlspecialchars($r['title'])?></td>

<td>
    <span class="vote-box agree">
        <i class="fas fa-thumbs-up"></i> <?=$r['agree_votes']?>
    </span>

    <span class="vote-box disagree">
        <i class="fas fa-thumbs-down"></i> <?=$r['disagree_votes']?>
    </span>
</td>

</tr>

<?php endwhile; ?>

</table>

</div>

</div>

</body>
</html>