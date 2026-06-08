<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Digital Suggestion Box System</title>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>

/* =========================
   BASE STYLE
========================= */

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI',sans-serif;
}

body{
    background:#f8fafc;
    color:#1e293b;
}

/* =========================
   NAVBAR (PROFESSIONAL)
========================= */

nav{
    background:#ffffff;
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:18px 60px;
    box-shadow:0 2px 20px rgba(0,0,0,.08);
    position:sticky;
    top:0;
    z-index:1000;
}

.logo{
    display:flex;
    align-items:center;
    gap:10px;
    font-size:24px;
    font-weight:700;
    color:#2563eb;
}

.nav-links{
    display:flex;
    align-items:center;
    gap:25px;
}

.nav-links a{
    text-decoration:none;
    color:#475569;
    font-weight:600;
    transition:.3s;
}

.nav-links a:hover{
    color:#2563eb;
}

.login-btn{
    border:2px solid #2563eb;
    padding:10px 18px;
    border-radius:8px;
}

.login-btn:hover{
    background:#2563eb;
    color:white;
}

.register-btn{
    background:#2563eb;
    color:white;
    padding:10px 18px;
    border-radius:8px;
}

.register-btn:hover{
    background:#1d4ed8;
}

/* =========================
   HERO SECTION
========================= */

.hero{
    padding:100px 40px;
    text-align:center;
    background:linear-gradient(135deg,#eff6ff,#ffffff);
}

.hero h1{
    font-size:52px;
    margin-bottom:15px;
}

.hero p{
    max-width:750px;
    margin:auto;
    font-size:18px;
    line-height:1.7;
    color:#64748b;
}

.btn-area{
    margin-top:30px;
}

.btn{
    display:inline-block;
    padding:14px 28px;
    border-radius:10px;
    text-decoration:none;
    font-weight:bold;
    margin:10px;
}

.btn-primary{
    background:#2563eb;
    color:white;
}

.btn-primary:hover{
    background:#1d4ed8;
}

.btn-outline{
    border:2px solid #2563eb;
    color:#2563eb;
}

.btn-outline:hover{
    background:#2563eb;
    color:white;
}

/* =========================
   FEATURES
========================= */

.section-title{
    text-align:center;
    margin:60px 0 40px;
}

.section-title h2{
    font-size:35px;
}

.features{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
    gap:25px;
    padding:0 60px 60px;
}

.card{
    background:white;
    padding:30px;
    border-radius:15px;
    text-align:center;
    box-shadow:0 5px 20px rgba(0,0,0,.06);
}

.card i{
    font-size:40px;
    color:#2563eb;
    margin-bottom:15px;
}

/* =========================
   HOW IT WORKS
========================= */

.steps{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
    gap:25px;
    padding:0 60px 60px;
}

.step{
    background:white;
    padding:25px;
    border-radius:15px;
    box-shadow:0 5px 20px rgba(0,0,0,.06);
}

.step span{
    font-size:28px;
    font-weight:bold;
    color:#2563eb;
}

/* =========================
   FOOTER
========================= */

footer{
    background:#0f172a;
    color:white;
    text-align:center;
    padding:25px;
    margin-top:40px;
}

</style>

</head>

<body>

<!-- ================= NAVBAR ================= -->

<nav>

    <div class="logo">
        <i class="fas fa-lightbulb"></i>
        Digital Suggestion Box
    </div>

    <div class="nav-links">

        <a href="index.php" class="active">Home</a>
        <a href="#features">Features</a>
        <a href="#how">How It Works</a>

        <a href="login.php" class="login-btn">Login</a>

    </div>

</nav>

<!-- ================= HERO ================= -->
<section class="hero">

    <div class="hero-container">

        <div class="hero-text">

            <h1>Digital Suggestion Box System</h1>

            <p>
                A secure platform that allows users to submit suggestions, feedback,
                complaints and ideas. Organizations can easily manage feedback and improve services.
            </p><br><br>

           

        </div>

        <div class="hero-image">

            <img src="suggestion.jpeg" alt="Suggestion System">

        </div>
         <div class="btn-area">
                <a href="register.php" class="btn btn-primary">Get Started</a>
                <a href="login.php" class= "btn btn-primary">submit suggestion</a>
            </div>

    </div>

</section>
<!-- ================= FEATURES ================= -->

<div class="section-title" id="features">
    <h2>Key Features</h2>
</div>

<section class="features">

    <div class="card">
        <i class="fas fa-lightbulb"></i>
        <h3>Smart Suggestions</h3>
        <p>Submit ideas easily anytime.</p>
    </div>

    <div class="card">
        <i class="fas fa-user-shield"></i>
        <h3>Secure System</h3>
        <p>Role-based access control.</p>
    </div>

    <div class="card">
        <i class="fas fa-chart-line"></i>
        <h3>Analytics</h3>
        <p>Track and monitor feedback.</p>
    </div>

    <div class="card">
        <i class="fas fa-cogs"></i>
        <h3>Admin Control</h3>
        <p>Manage users and system settings.</p>
    </div>

</section>

<!-- ================= HOW IT WORKS ================= -->

<div class="section-title" id="how">
    <h2>How It Works</h2>
</div>

<section class="steps">

    <div class="step">
        <span>1</span>
        <h3>Create Account</h3>
        <p>Register as a user or admin.</p>
    </div>

    <div class="step">
        <span>2</span>
        <h3>Submit Feedback</h3>
        <p>Send suggestions easily.</p>
    </div>

    <div class="step">
        <span>3</span>
        <h3>Admin Review</h3>
        <p>Suggestions are reviewed.</p>
    </div>

    <div class="step">
        <span>4</span>
        <h3>Improvement</h3>
        <p>System is improved continuously.</p>
    </div>

</section>

<!-- ================= FOOTER ================= -->

<footer>

    <h3>Digital Suggestion Box System</h3>
    <p>Built for better communication between users and organizations</p>

    <br>

    &copy; <?php echo date("Y"); ?> All Rights Reserved

</footer>

</body>
</html>