<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Digital Suggestion Box System</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

/* ================= GLOBAL ================= */

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI',sans-serif;
}

body{
    background:#f4f6f9;
    color:#111827;
}

/* ================= NAV ================= */

nav{
    background:#111827;
    color:#fff;
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:16px 50px;
}

.logo{
    font-size:22px;
    font-weight:bold;
}

.logo i{
    color:#60a5fa;
    margin-right:8px;
}

.nav-btns{
    display:flex;
    gap:12px;
}

.nav-btns a{
    text-decoration:none;
    padding:10px 20px;
    border-radius:8px;
    font-weight:600;
}

.login{
    border:1px solid #fff;
    color:#fff;
}

.login:hover{
    background:#fff;
    color:#111827;
}

.register{
    background:#1e3a8a;
    color:#fff;
}

.register:hover{
    background:#1e40af;
}

/* ================= HERO ================= */

.hero{
    max-width:1100px;
    margin:50px auto;
    display:flex;
    gap:30px;
    align-items:center;
    justify-content:space-between;
    padding:40px;
}

/* LEFT TEXT */
.hero-left{
    flex:1;
}

.hero-left h1{
    font-size:42px;
    margin-bottom:15px;
}

/* 🔥 BOLD PARAGRAPHS */
.hero-left p{
    color:#111827;
    line-height:1.8;
    margin-bottom:12px;
    font-size:16px;
    font-weight:bold;
}

/* BUTTON */
.hero-left a{
    display:inline-block;
    background:#111827;
    color:#fff;
    padding:12px 25px;
    border-radius:8px;
    text-decoration:none;
    margin-top:10px;
}

.hero-left a:hover{
    background:#1f2937;
}

/* RIGHT IMAGE (RESIZED FIX) */
.hero-right{
    flex:1;
    display:flex;
    justify-content:center;
    align-items:center;
}

.hero-right img{
    width:100%;
    max-width:250px;   /* 🔥 resized smaller */
    height:auto;
    border-radius:18px;
    box-shadow:0 15px 30px rgba(0,0,0,0.18);
    object-fit:cover;
  
}

/* ================= FOOTER ================= */

footer{
    margin-top:100px;
    background:#111827;
    color:#fff;
    text-align:center;
    padding:10px;
}

/* ================= RESPONSIVE ================= */

@media(max-width:900px){

    nav{
        flex-direction:column;
        gap:10px;
    }

    .hero{
        flex-direction:column;
        text-align:center;
    }

}

</style>

</head>

<body>

<!-- ================= NAV ================= -->

<nav>

<div class="logo">
<i class="fas fa-lightbulb"></i> Digital Suggestion Box
</div>

</nav>

<!-- ================= HERO ================= -->

<section class="hero">

<div class="hero-left">

<h1>Digital Suggestion box system</h1>

<p>
A Digital Suggestion Box System is a web-based platform that allows users to easily submit ideas, feedback, and complaints in a secure and organized way. Instead of using physical suggestion boxes, everything is done online, making it faster and more efficient for both users and administrators. The system helps organizations collect opinions, review suggestions, and improve services through transparent communication and better decision-making.
</p>


<a href="login.php">Get Started</a>

</div>

<div class="hero-right">

<!-- IMAGE -->
<img src="ideas.jpeg" alt="Suggestion Box Image">

</div>

</section>

<!-- ================= FOOTER ================= -->

<footer>
© 2026 Digital Suggestion Box System
</footer>

</body>
</html>