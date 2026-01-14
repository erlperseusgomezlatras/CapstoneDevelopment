<?php
session_start();

function generateCaptcha() {
    $a = rand(10, 25);
    $b = rand(1, 9);
    $_SESSION['captcha'] = $a + $b;
    return [$a, $b];
}

[$a, $b] = generateCaptcha();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PHINMA | Practicum Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>

<div class="login-wrapper">

    <!-- LEFT EMERALD PANEL -->
    <div class="emerald-panel">
        <div class="emerald-overlay"></div>

        <div class="emerald-content">
            <img src="assets/images/logo_college.png" class="emerald-logo">

            <h1 class="emerald-title">Cagayan De Oro College</h1>

            <p class="emerald-subtitle">
                Practicum Management System with Attendance Monitoring for<br>
                Education Practicum Students
            </p>

            <p class="emerald-institution">
                PHINMA Cagayan de Oro College
            </p>
        </div>
    </div>

    <!-- RIGHT PANEL -->
    <div class="form-panel">
        <div class="login-card">

            <a href="homepage.php" class="back-home">← Back to Homepage</a>

            <img src="assets/images/logo.png" class="login-logo">

            <h2>Sign In</h2>

            <form action="auth/login.php" method="POST" id="loginForm">

                <div class="form-group">
                    <label><span>*</span> Username</label>
                    <input type="text" name="email" required>
                </div>

                <div class="form-group">
                    <label><span>*</span> Password</label>
                    <input type="password" name="password" required>
                </div>

                <!-- CAPTCHA -->
                <div class="captcha-row">
                    <div class="captcha-box" id="capA"><?= $a ?></div>
                    <span>+</span>
                    <div class="captcha-box" id="capB"><?= $b ?></div>
                    <span>=</span>
                    <input type="number" name="captcha" id="captchaInput" required>
                    <button type="button" class="captcha-refresh" id="refreshCaptcha">⟳</button>
                </div>

                <button type="submit" class="btn-login" id="loginBtn" disabled>
                    Sign In
                </button>

                <a href="#" class="forgot">Forgot Password</a>
            </form>

        </div>
    </div>

</div>

<script>
/* ENABLE LOGIN BUTTON ONLY WHEN CAPTCHA HAS INPUT */
const captchaInput = document.getElementById("captchaInput");
const loginBtn = document.getElementById("loginBtn");

captchaInput.addEventListener("input", () => {
    if (captchaInput.value.trim() !== "") {
        loginBtn.disabled = false;
        loginBtn.classList.add("active");
    } else {
        loginBtn.disabled = true;
        loginBtn.classList.remove("active");
    }
});

/* REFRESH CAPTCHA WITHOUT PAGE RELOAD */
document.getElementById("refreshCaptcha").addEventListener("click", () => {
    fetch("refresh_captcha.php")
        .then(res => res.json())
        .then(data => {
            document.getElementById("capA").textContent = data.a;
            document.getElementById("capB").textContent = data.b;
            captchaInput.value = "";
            loginBtn.disabled = true;
            loginBtn.classList.remove("active");
        });
});
</script>

</body>
</html>
