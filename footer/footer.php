<!-- ================= FOOTER ================= -->

<footer id="footer-main">

    <div class="container">

        <hr>

        <div class="footer-content">

            <div class="footer-left">
                <p>
                    &copy; <?php echo date("Y"); ?>
                    Digital Suggestion Box System
                    All Rights Reserved.
                </p>
            </div>

            <div class="footer-right">

                <ul class="footer-nav">

                    <li>
                        <a href="#" id="openTerms">Terms of Use</a>
                    </li>

                </ul>

            </div>

        </div>

    </div>

</footer>

<!-- ================= TERMS MODAL ================= -->

<div id="termsModal" class="modal">

    <div class="modal-card">

        <span class="close">&times;</span>

        <h2>Terms of Use</h2>

        <hr>

        <h4>1. Acceptance of Terms</h4>
        <p>
            By using the Digital Suggestion Box System, you agree to these Terms of Use.
            If you do not agree, please do not use the system.
        </p>

        <h4>2. Purpose</h4>
        <p>
            This system enables users to submit suggestions, complaints, compliments,
            and feedback to improve organizational services.
        </p>

        <h4>3. User Responsibilities</h4>

        <ul>
            <li>Provide accurate information.</li>
            <li>Respect other users.</li>
            <li>Do not post abusive or offensive content.</li>
            <li>Do not submit false information.</li>
            <li>Do not attempt unauthorized access.</li>
        </ul>

        <h4>4. Privacy</h4>

        <p>
            Your personal information and submitted suggestions will be treated
            confidentially and used only for service improvement.
        </p>

        <h4>5. Limitation of Liability</h4>

        <p>
            The administrators are not responsible for losses resulting from misuse
            of the system.
        </p>

        <h4>6. Changes to Terms</h4>

        <p>
            These Terms of Use may change at any time without prior notice.
            Continued use of the system means you accept the updated terms.
        </p>

        <div class="modal-footer">

            <button id="closeBtn">Close</button>

        </div>

    </div>

</div>

<style>

/* ================= FOOTER ================= */

#footer-main{
    margin-left:220px;
    width:calc(100% - 220px);
    background:#fff;
    border-top:1px solid #e5e7eb;
    padding:20px 30px;
    box-sizing:border-box;
}

#footer-main .container{
    width:100%;
    max-width:1200px;
    margin:auto;
}

#footer-main hr{
    border:none;
    height:1px;
    background:#e5e7eb;
    margin-bottom:20px;
}

.footer-content{
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    gap:20px;
}

.footer-left p{
    margin:0;
    font-size:14px;
    color:#6b7280;
}

.footer-left a{
    color:#2563eb;
    text-decoration:none;
    font-weight:600;
}

.footer-left a:hover{
    text-decoration:underline;
}

.footer-nav{
    display:flex;
    list-style:none;
    gap:20px;
    margin:0;
    padding:0;
}

.footer-nav a{
    color:#6b7280;
    text-decoration:none;
}

.footer-nav a:hover{
    color:#111827;
}

@media(max-width:900px){

    #footer-main{
        margin-left:0;
        width:100%;
    }

    .footer-content{
        flex-direction:column;
        text-align:center;
    }

}

/* ================= MODAL ================= */

.modal{

    display:none;
    position:fixed;
    left:0;
    top:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,.55);
    z-index:9999;

}

.modal-card{

    background:#fff;
    width:90%;
    max-width:750px;
    margin:60px auto;
    padding:30px;
    border-radius:12px;
    position:relative;
    max-height:80vh;
    overflow-y:auto;
    animation:popup .3s ease;

}

@keyframes popup{

from{

transform:scale(.9);
opacity:0;

}

to{

transform:scale(1);
opacity:1;

}

}

.close{

position:absolute;
right:20px;
top:15px;
font-size:30px;
cursor:pointer;
color:#555;

}

.close:hover{

color:red;

}

.modal h2{

margin-bottom:15px;

}

.modal h4{

margin-top:20px;
margin-bottom:8px;
color:#111827;

}

.modal p,
.modal li{

line-height:1.8;
color:#555;

}

.modal ul{

margin-left:20px;

}

.modal-footer{

text-align:right;
margin-top:25px;

}

.modal-footer button{

background:#111827;
color:#fff;
border:none;
padding:10px 22px;
border-radius:6px;
cursor:pointer;

}

.modal-footer button:hover{

background:#1f2937;

}

@media(max-width:768px){

.footer-content{

flex-direction:column;
text-align:center;
gap:15px;

}

.footer-nav{

justify-content:center;

}

.modal-card{

margin:30px auto;

}

}

</style>

<script>

const modal=document.getElementById("termsModal");

const open=document.getElementById("openTerms");

const close=document.querySelector(".close");

const btn=document.getElementById("closeBtn");

open.onclick=function(e){

e.preventDefault();

modal.style.display="block";

}

close.onclick=function(){

modal.style.display="none";

}

btn.onclick=function(){

modal.style.display="none";

}

window.onclick=function(e){

if(e.target==modal){

modal.style.display="none";

}

}

</script>