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
    min-height:100vh;
    display:flex;
    flex-direction:column;
}

main{
    flex:1;
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

.hero-left p{
    color:#111827;
    line-height:1.8;
    margin-bottom:12px;
    font-size:16px;
    font-weight:bold;
}

/* RIGHT LOGIN */

.hero-right{
    flex:1;
    display:flex;
    justify-content:center;
    align-items:center;
}

.login-container{
    width:100%;
    max-width:400px;
    background:#fff;
    padding:30px;
    border-radius:18px;
    box-shadow:0 15px 30px rgba(0,0,0,0.18);
}

.login-container h2{
    text-align:center;
    margin-bottom:20px;
    color:#111827;
}

.login-container input{
    width:100%;
    padding:12px;
    margin-bottom:15px;
    border:1px solid #ccc;
    border-radius:8px;
    font-size:15px;
}

.login-container button{
    width:100%;
    padding:12px;
    background:#111827;
    color:#fff;
    border:none;
    border-radius:8px;
    font-size:16px;
    cursor:pointer;
}

.login-container button:hover{
    background:#1f2937;
}

.register-link{
    text-align:center;
    margin-top:15px;
    font-size:14px;
    color:#555;
}

.register-link a{
    color:#1e3a8a;
    text-decoration:none;
    font-weight:bold;
}

.register-link a:hover{
    text-decoration:underline;
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

    .login-container{
        margin-top:20px;
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

<main>

<!-- ================= HERO ================= -->

<section class="hero">

    <div class="hero-left">

        <h1>Digital Suggestion Box System</h1>

        <p>
            A Digital Suggestion Box System is a web-based platform that allows users to easily submit ideas, feedback, and complaints in a secure and organized way. Instead of using physical suggestion boxes, everything is done online, making it faster and more efficient for both users and administrators. The system helps organizations collect opinions, review suggestions, and improve services through transparent communication and better decision-making.
        </p>

    </div>

    <!-- LOGIN FORM -->

    <div class="hero-right">

        <div class="login-container">

            <h2>Login</h2>

            <form action="login.php" method="POST">

                <input type="text" name="username" placeholder="Username" required>

                <input type="password" name="password" placeholder="Password" required>

                <button type="submit">Login</button>

                <p class="register-link">
                    Don't have an account?
                    <a href="register.php">Register here</a>
                </p>

            </form>

        </div>

    </div>

</section>

</main>

<!-- Footer -->
<?php include 'footer/footer.php'; ?>

</body>
</html>