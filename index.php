<?php
// Include configuration
require_once 'config/config.php';

session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id']) || (isset($_COOKIE['authToken']) && isset($_COOKIE['userData']))) {
    // Get user data from cookie if session is not set
    $userData = null;
    if (isset($_COOKIE['userData'])) {
        $userData = json_decode($_COOKIE['userData'], true);
    }
    
    // Redirect based on user level
    $dashboardRoutes = [
        'Head Teacher' => 'teacher/dashboard.php',
        'Coordinator' => 'coordinator/dashboard.php',
        'Student' => 'student/dashboard.php'
    ];
    
    $userLevel = $userData['level'] ?? 'Student';
    $redirectUrl = $dashboardRoutes[$userLevel] ?? 'index.php';
    
    header("Location: $redirectUrl");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PHINMA | Practicum Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/homepage.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg bg-white shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="#">
            <img src="assets/images/logo.png" width="40">
            <div class="lh-sm">
                <strong>PHINMA Education</strong> <br>
                <small class="text-muted">Practicum Management System</small>
            </div>
        </a>
        <a href="login.php" class="btn btn-emerald">Login</a>
    </div>
</nav>

<!-- HERO -->
<section class="hero-section position-relative overflow-hidden">
    <div class="hero-shape shape-1"></div>
    <div class="hero-shape shape-2"></div>
    <div class="hero-shape shape-3"></div>

    <div class="container py-5">
        <div class="row align-items-center gy-5">
            <div class="col-lg-6">
                <h1 class="display-5 fw-bold">
                    Practicum Management &
                    <span class="text-emerald">Monitoring System</span>
                </h1>
                <p class="text-muted fs-5 mt-3">
                    A comprehensive platform for managing and tracking OJT students,
                    ensuring seamless coordination between students, supervisors, and coordinators.
                </p>

                <div class="d-flex gap-3 mt-4">
                    <a href="index.php" class="btn btn-emerald btn-lg">
                        Get Started <i class="bi bi-arrow-right"></i>
                    </a>
                    <a href="#features" class="btn btn-outline-emerald btn-lg">
                        Learn More
                    </a>
                </div>
            </div>

            <div class="col-lg-6 text-center">
                <div class="logo-glow">
                    <img src="assets/images/logo_college.png" class="img-fluid hero-logo">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FEATURES -->
<section id="features" class="py-5 bg-white position-relative shape-continuation">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">
                Powerful Features for <span class="text-emerald">Better Management</span>
            </h2>
        </div>

        <div class="row g-4">
            <?php
            $features = [
                ["Time Tracking","bi-clock"],
                ["Report Management","bi-file-earmark-check"],
                ["Progress Analytics","bi-bar-chart"],
                ["Multi-Role Access","bi-people"],
                ["Evaluation System","bi-check-circle"],
                ["Secure & Reliable","bi-shield-lock"]
            ];
            foreach ($features as $f) {
                echo "
                <div class='col-md-6 col-lg-4'>
                    <div class='feature-card'>
                        <div class='icon-box'><i class='bi {$f[1]}'></i></div>
                        <h5 class='fw-bold mt-3'>{$f[0]}</h5>
                        <p class='text-muted small'>Enterprise-grade practicum tools.</p>
                    </div>
                </div>";
            }
            ?>
        </div>
    </div>
</section>

<!-- ABOUT -->
<section class="py-5 bg-emerald text-white">
    <div class="container">
        <div class="row align-items-center gy-4">

            <!-- LEFT TEXT -->
            <div class="col-lg-6 ">
                <h2 class="text-white fw-bold mb-3">
                    About PHINMA Cagayan De Oro College
                </h2>

                <p class="text-white mb-3">
                    Max Suniel St. Carmen, Cagayan de Oro City,
                    Misamis Oriental, Philippines 9000
                </p>

                <p class="text-white">
                    PHINMA Cagayan De Oro College is committed to providing quality
                    education and practical training opportunities for students.
                    Our Practicum Management System ensures that students receive
                    proper guidance and monitoring throughout their On-the-Job
                    Training experience.
                </p>

                <p class="mt-3 text-white">
                    We partner with leading companies to provide real-world
                    experience that prepares our students for successful careers
                    in their chosen fields.
                </p>
            </div>

            <!-- CHECKLIST CARD (RESTORED) -->
            <div class="col-lg-6">
                <div class="checklist-card">
                    <h4 class="fw-bold mb-4">Why Choose Us?</h4>

                    <ul class="checklist">
                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Comprehensive monitoring and support system</span>
                        </li>
                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Strong industry partnerships and connections</span>
                        </li>
                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Dedicated coordinators and supervisors</span>
                        </li>
                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Regular feedback and performance evaluations</span>
                        </li>
                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Career development and job placement assistance</span>
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</section>


<!-- FOOTER -->
<footer class="footer-emerald position-relative" id="footer">
    <div class="container py-5">
        <div class="row gy-4 align-items-center">

            <!-- Carmen -->
            <div class="col-md-4">
                <h6 class="footer-title">CONTACT US</h6>
                <h5>Carmen Campus</h5>
                <p><i class="bi bi-geo-alt"></i> Max Suniel St., Carmen, CDO, Misamis Oriental</p>
                <p><i class="bi bi-phone"></i> 0917-376-5105</p>
                <p><i class="bi bi-telephone"></i> (088) 858-5867 to 69</p>
                <p><i class="bi bi-envelope"></i> info.coc@phinmaed.com</p>
            </div>

            <!-- Puerto -->
            <div class="col-md-4">
                <h5>Puerto Campus</h5>
                <p><i class="bi bi-geo-alt"></i> Purok 6, Puerto, CDO, Misamis Oriental</p>
                <p><i class="bi bi-phone"></i> 0916-131-8980</p>
                <p><i class="bi bi-telephone"></i> (088) 858-5867 to 69</p>
                <p><i class="bi bi-envelope"></i> info.coc@phinmaed.com</p>
            </div>

            <!-- Branding (FIXED SIZE & BALANCE) -->
            <div class="col-md-4 footer-branding">
                <img src="assets/images/coc-white.png" class="footer-logo coc-logo" alt="COC Logo">
                <img src="assets/images/phinma_white.png" class="footer-logo phinma-logo" alt="PHINMA Logo">
            </div>

        </div>

        <div class="text-center mt-4 small opacity-75">
            © 2026 PHINMA Cagayan De Oro College. All rights reserved.
        </div>
    </div>
</footer>

<!-- BACK TO TOP -->
<button id="backToTop" class="back-to-top">
    <i class="bi bi-chevron-up"></i>
</button>

<script>
const btn = document.getElementById("backToTop");
const footer = document.getElementById("footer");

window.addEventListener("scroll", () => {
    const footerTop = footer.getBoundingClientRect().top;
    const windowHeight = window.innerHeight;

    btn.style.display = window.scrollY > 300 ? "flex" : "none";

    if (footerTop < windowHeight - 80) {
        btn.classList.add("footer-mode");
    } else {
        btn.classList.remove("footer-mode");
    }
});

btn.onclick = () => window.scrollTo({ top: 0, behavior: "smooth" });
</script>

</body>
</html>
