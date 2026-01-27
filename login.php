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
    <script src="assets/js/config.js"></script>
    
    <!-- jQuery (required for Select2) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Select2 CSS and JS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- Custom Select2 Styling -->
    <style>
        /* Select2 custom styling to match Tailwind */
        .select2-container--default .select2-selection--single {
            height: 48px !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0.5rem !important;
            padding: 0.75rem 1rem !important;
            display: flex !important;
            align-items: center !important;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 1.5 !important;
            padding-left: 0 !important;
            color: #111827 !important;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 46px !important;
            right: 10px !important;
        }
        
        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #058643 !important;
            box-shadow: 0 0 0 3px rgba(5, 134, 67, 0.1) !important;
        }
        
        .select2-dropdown {
            border: 1px solid #d1d5db !important;
            border-radius: 0.5rem !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
        }
        
        .select2-search--dropdown .select2-search__field {
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            padding: 0.5rem !important;
        }
        
        .select2-search--dropdown .select2-search__field:focus {
            border-color: #058643 !important;
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(5, 134, 67, 0.1) !important;
        }
        
        .select2-results__option {
            padding: 0.75rem 1rem !important;
        }
        
        .select2-results__option--highlighted {
            background-color: #058643 !important;
            color: white !important;
        }
        
        .select2-container--default .select2-results__option--selected {
            background-color: #e6f4ed !important;
            color: #058643 !important;
        }
        
        .select2-container {
            width: 100% !important;
        }
    </style>
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

            <a href="index.php" class="back-home">← Back to Homepage</a>

            <img src="assets/images/logo.png" class="login-logo">

            <h2>System Login</h2>

            <form id="loginForm">
                <!-- ERROR MESSAGE -->
                <div id="errorMessage" class="error-message" style="display: none;"></div>

                <!-- EMAIL STEP -->
                <div id="emailStep" class="form-step">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required placeholder="your.email@phinmaed.com">
                    </div>

                    <!-- CAPTCHA -->
                    <div class="captcha-row">
                        <div class="captcha-box" id="capA"><?= $a ?></div>
                        <span>+</span>
                        <div class="captcha-box" id="capB"><?= $b ?></div>
                        <span>=</span>
                        <input type="number" name="captcha" id="captchaInput" required placeholder="?">
                        <button type="button" class="captcha-refresh" id="refreshCaptcha">⟳</button>
                    </div>

                    <button type="button" id="verifyEmailBtn" class="btn-login" disabled>
                        PROCEED
                    </button>
                </div>

                <!-- PASSWORD STEP -->
                <div id="passwordStep" class="form-step" style="display: none;">
                    <div class="welcome-message">
                        <p>Welcome back, <span id="welcomeName"></span></p>
                        <p class="email-display" id="emailDisplay"></p>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" id="password" name="password" required placeholder="Enter your password">
                            <button type="button" id="togglePassword" class="toggle-password">
                                <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="button" id="loginBtn" class="btn-login active">
                        LOGIN
                    </button>

                    <button type="button" id="backToEmailBtn" class="btn-back">
                        Back
                    </button>
                </div>

                <!-- REGISTRATION STEP -->
                <div id="registrationStep" class="form-step" style="display: none;">
                    <div class="welcome-message">
                        <p>Create your student account</p>
                        <p class="email-display" id="regEmailDisplay"></p>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstname">First Name *</label>
                            <input type="text" id="firstname" name="firstname" required placeholder="First name">
                        </div>

                        <div class="form-group">
                            <label for="lastname">Last Name *</label>
                            <input type="text" id="lastname" name="lastname" required placeholder="Last name">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="middlename">Middle Name (Optional)</label>
                        <input type="text" id="middlename" name="middlename" placeholder="Middle name">
                    </div>

                    <div class="form-group">
                        <label for="schoolId">School ID *</label>
                        <input type="text" id="schoolId" name="schoolId" required placeholder="Enter your School ID">
                    </div>

                    <div class="form-group">
                        <label for="section">Section *</label>
                        <select id="section" name="section" required>
                            <option value="">Select Section</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="regPassword">Password *</label>
                        <div class="password-input-wrapper">
                            <input type="password" id="regPassword" name="regPassword" required placeholder="Create a password">
                            <button type="button" id="toggleRegPassword" class="toggle-password">
                                <svg id="eyeRegIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="button" id="createAccountBtn" class="btn-login active">
                        CREATE ACCOUNT
                    </button>

                    <button type="button" id="backToEmailRegBtn" class="btn-back">
                        Back
                    </button>
                </div>

                <!-- PENDING STEP -->
                <div id="pendingStep" class="form-step" style="display: none;">
                    <div class="pending-message">
                        <div class="success-icon">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <h3>Account Created Successfully!</h3>
                        <p>
                            Your student account has been created and is pending approval.
                            Please wait for administrator approval before accessing your dashboard.
                        </p>
                        <p class="note">
                            You will be notified once your account is approved.
                        </p>
                    </div>

                    <button type="button" id="backToLoginBtn" class="btn-back">
                        Back to Login
                    </button>
                </div>
            </form>

            <a href="#" class="forgot">Forgot Password</a>
        </div>
    </div>

</div>

<script>
// Global variables
let currentStep = 'email';
let userInfo = null;
let formData = {
    email: '',
    password: '',
    firstname: '',
    lastname: '',
    middlename: '',
    schoolId: ''
};

// API base URL
const API_URL = window.APP_CONFIG.API_BASE_URL;

// DOM elements
const loginForm = document.getElementById('loginForm');
const errorMessage = document.getElementById('errorMessage');

// Step elements
const emailStep = document.getElementById('emailStep');
const passwordStep = document.getElementById('passwordStep');
const registrationStep = document.getElementById('registrationStep');
const pendingStep = document.getElementById('pendingStep');

// Form elements
const emailInput = document.getElementById('email');
const passwordInput = document.getElementById('password');
const regPasswordInput = document.getElementById('regPassword');
const firstnameInput = document.getElementById('firstname');
const lastnameInput = document.getElementById('lastname');
const middlenameInput = document.getElementById('middlename');
const schoolIdInput = document.getElementById('schoolId');
const captchaInput = document.getElementById('captchaInput');
const capAElement = document.getElementById('capA');
const capBElement = document.getElementById('capB');
const refreshCaptchaBtn = document.getElementById('refreshCaptcha');

// Buttons
const verifyEmailBtn = document.getElementById('verifyEmailBtn');
const loginBtn = document.getElementById('loginBtn');
const createAccountBtn = document.getElementById('createAccountBtn');
const backToEmailBtn = document.getElementById('backToEmailBtn');
const backToEmailRegBtn = document.getElementById('backToEmailRegBtn');
const backToLoginBtn = document.getElementById('backToLoginBtn');

// Password toggle
const togglePassword = document.getElementById('togglePassword');
const toggleRegPassword = document.getElementById('toggleRegPassword');

// Show error message
function showError(message) {
    errorMessage.textContent = message;
    errorMessage.style.display = 'block';
    setTimeout(() => {
        errorMessage.style.display = 'none';
    }, 5000);
}

// Hide all steps
function hideAllSteps() {
    emailStep.style.display = 'none';
    passwordStep.style.display = 'none';
    registrationStep.style.display = 'none';
    pendingStep.style.display = 'none';
}

// Show specific step
function showStep(step) {
    hideAllSteps();
    currentStep = step;
    
    switch(step) {
        case 'email':
            emailStep.style.display = 'block';
            break;
        case 'password':
            passwordStep.style.display = 'block';
            break;
        case 'registration':
            registrationStep.style.display = 'block';
            loadSections(); // Load sections when registration step is shown
            break;
        case 'pending':
            pendingStep.style.display = 'block';
            break;
    }
}

// Update form data
function updateFormData() {
    formData.email = emailInput.value;
    formData.password = passwordInput.value;
    formData.firstname = firstnameInput.value;
    formData.lastname = lastnameInput.value;
    formData.middlename = middlenameInput.value;
}

// Reset form
function resetForm() {
    loginForm.reset();
    formData = {
        email: '',
        password: '',
        firstname: '',
        lastname: '',
        middlename: ''
    };
    userInfo = null;
    // Reset captcha
    refreshCaptcha();
    showStep('email');
}

// Check if email and captcha are filled to enable button
function checkEmailFormValidity() {
    const email = emailInput.value.trim();
    const captcha = captchaInput.value.trim();
    
    if (email && captcha) {
        // Verify captcha in real-time
        verifyCaptchaRealtime(captcha);
    } else {
        verifyEmailBtn.disabled = true;
        verifyEmailBtn.classList.remove('active');
        captchaInput.classList.remove('captcha-correct');
        captchaInput.classList.remove('captcha-incorrect');
    }
}

// Real-time captcha verification
async function verifyCaptchaRealtime(captchaAnswer) {
    try {
        const response = await fetch('verify_captcha.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                captcha: captchaAnswer
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Correct captcha
            captchaInput.classList.remove('captcha-incorrect');
            captchaInput.classList.add('captcha-correct');
            verifyEmailBtn.disabled = false;
            verifyEmailBtn.classList.add('active');
        } else {
            // Incorrect captcha
            captchaInput.classList.remove('captcha-correct');
            captchaInput.classList.add('captcha-incorrect');
            verifyEmailBtn.disabled = true;
            verifyEmailBtn.classList.remove('active');
        }
    } catch (err) {
        console.error('Real-time captcha verification error:', err);
        // On error, keep button disabled
        captchaInput.classList.remove('captcha-correct');
        captchaInput.classList.add('captcha-incorrect');
        verifyEmailBtn.disabled = true;
        verifyEmailBtn.classList.remove('active');
    }
}

// Refresh captcha
async function refreshCaptcha() {
    try {
        const response = await fetch('refresh_captcha.php');
        const data = await response.json();
        
        if (data.success) {
            capAElement.textContent = data.a;
            capBElement.textContent = data.b;
            captchaInput.value = '';
            // Remove validation classes when captcha is refreshed
            captchaInput.classList.remove('captcha-correct', 'captcha-incorrect');
            verifyEmailBtn.disabled = true;
            verifyEmailBtn.classList.remove('active');
        }
    } catch (err) {
        console.error('Error refreshing captcha:', err);
    }
}

// Verify email
async function verifyEmail() {
    const email = emailInput.value.trim();
    const captcha = captchaInput.value.trim();
    
    if (!email) {
        showError('Please enter your email address');
        return;
    }
    
    if (!captcha) {
        showError('Please complete the captcha');
        return;
    }
    
    // Check if captcha is already verified (real-time validation)
    if (!captchaInput.classList.contains('captcha-correct')) {
        showError('Please complete the captcha correctly');
        return;
    }
    
    verifyEmailBtn.disabled = true;
    verifyEmailBtn.textContent = 'Verifying...';
    
    try {
        const response = await fetch(`${API_URL}/auth.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'verify_email',
                email: email
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            userInfo = result;
            updateFormData();
            
            if (result.user_exists) {
                // User exists, check if can proceed to password
                if (['Head Teacher', 'Coordinator'].includes(result.user_level)) {
                    showStep('password');
                    document.getElementById('welcomeName').textContent = result.user_data.firstname;
                    document.getElementById('emailDisplay').textContent = email;
                } else if (result.user_level === 'Student') {
                    if (result.user_data?.isApproved) {
                        showStep('password');
                        document.getElementById('welcomeName').textContent = result.user_data.firstname;
                        document.getElementById('emailDisplay').textContent = email;
                    } else {
                        showError('Your account is pending approval. Please wait for administrator approval.');
                    }
                }
            } else {
                // User doesn't exist, proceed to registration
                showStep('registration');
                document.getElementById('regEmailDisplay').textContent = email;
            }
        } else {
            showError(result.message);
        }
    } catch (err) {
        console.error('Email verification error:', err);
        showError('Network error. Please try again.');
    } finally {
        verifyEmailBtn.disabled = false;
        verifyEmailBtn.textContent = 'PROCEED';
    }
}

// Load sections for registration dropdown
async function loadSections() {
    try {
        const formData = new FormData();
        formData.append('operation', 'get_sections');
        formData.append('json', JSON.stringify({}));
        
        const response = await fetch(`${API_URL}/teachers.php`, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            const select = document.getElementById('section');
            if (select) {
                select.innerHTML = '<option value="">Select Section</option>' +
                    result.data.map(section => 
                        `<option value="${section.id}">${section.section_name}</option>`
                    ).join('');
                
                // Initialize Select2
                $(select).select2({
                    placeholder: 'Select Section',
                    allowClear: false,
                    width: '100%'
                });
            }
        }
    } catch (error) {
        console.error('Error loading sections:', error);
    }
}

// Create account
async function createAccount() {
    const firstname = firstnameInput.value.trim();
    const lastname = lastnameInput.value.trim();
    const password = regPasswordInput.value.trim();
    const sectionSelect = document.getElementById('section');
    const section = sectionSelect ? $(sectionSelect).val() : '';
    
    if (!firstname || !lastname || !schoolIdInput.value.trim() || !password || !section) {
        showError('Please fill in all required fields including section');
        return;
    }
    
    createAccountBtn.disabled = true;
    createAccountBtn.textContent = 'Creating Account...';
    
    try {
        const response = await fetch(`${API_URL}/auth.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'create_account',
                email: formData.email,
                firstname: firstname,
                lastname: lastname,
                middlename: middlenameInput.value.trim(),
                schoolId: schoolIdInput.value.trim(),
                section_id: section,
                password: password
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showStep('pending');
        } else {
            showError(result.message);
        }
    } catch (err) {
        console.error('Account creation error:', err);
        showError('Network error. Please try again.');
    } finally {
        createAccountBtn.disabled = false;
        createAccountBtn.textContent = 'CREATE ACCOUNT';
    }
}

// Login
async function login() {
    const password = passwordInput.value.trim();
    
    if (!password) {
        showError('Please enter your password');
        return;
    }
    
    loginBtn.disabled = true;
    loginBtn.textContent = 'Signing in...';
    
    try {
        const response = await fetch(`${API_URL}/auth.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'login',
                email: formData.email,
                password: password
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Store token and user data
            if (result.token) {
                localStorage.setItem('authToken', result.token);
                localStorage.setItem('userData', JSON.stringify(result.user));
                
                // Also set cookies for PHP authentication check
                document.cookie = `authToken=${result.token}; path=/; max-age=${24*60*60}`;
                document.cookie = `userData=${encodeURIComponent(JSON.stringify(result.user))}; path=/; max-age=${24*60*60}`;
            }
            
            // Redirect to appropriate dashboard
            const dashboardRoutes = {
                'Head Teacher': 'teacher/dashboard.php',
                'Coordinator': 'coordinator/dashboard.php',
                'Student': 'student/dashboard.php'
            };
            
            const route = dashboardRoutes[result.user.level] || 'homepage.php';
            window.location.href = route;
        } else {
            showError(result.message);
        }
    } catch (err) {
        console.error('Login error:', err);
        showError('Network error. Please try again.');
    } finally {
        loginBtn.disabled = false;
        loginBtn.textContent = 'LOGIN';
    }
}

// Toggle password visibility
function togglePasswordVisibility(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
        `;
    } else {
        input.type = 'password';
        icon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
        `;
    }
}

// Event listeners
verifyEmailBtn.addEventListener('click', verifyEmail);
loginBtn.addEventListener('click', login);
createAccountBtn.addEventListener('click', createAccount);
backToEmailBtn.addEventListener('click', resetForm);
backToEmailRegBtn.addEventListener('click', resetForm);
backToLoginBtn.addEventListener('click', resetForm);

togglePassword.addEventListener('click', () => togglePasswordVisibility('password', 'eyeIcon'));
toggleRegPassword.addEventListener('click', () => togglePasswordVisibility('regPassword', 'eyeRegIcon'));
refreshCaptchaBtn.addEventListener('click', refreshCaptcha);

// Form validation listeners
emailInput.addEventListener('input', checkEmailFormValidity);
captchaInput.addEventListener('input', checkEmailFormValidity);

// Enter key support
emailInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') captchaInput.focus();
});

captchaInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') verifyEmail();
});

passwordInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') login();
});

regPasswordInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') createAccount();
});
</script>

</body>
</html>
