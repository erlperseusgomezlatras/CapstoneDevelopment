/**
 * Profile Management for Students
 */

function initProfile() {
    console.log('Initializing Profile...');
    loadProfileData();
    setupOtpInputs();
    setupNameUpdateForm();
    setupPasswordValidation();
}

// Load student profile data from API
function loadProfileData() {
    const schoolId = typeof studentSchoolId !== 'undefined' ? studentSchoolId : '';
    if (!schoolId) {
        console.error('School ID not found');
        return;
    }

    fetch('../api/profile.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'get_profile',
            school_id: schoolId
        })
    })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const user = result.data;

                // Fill Display Fields
                const fullName = `${user.firstname} ${user.middlename ? user.middlename + ' ' : ''}${user.lastname}`;
                document.getElementById('displayFullName').textContent = fullName;
                document.getElementById('displaySchoolId').textContent = user.school_id;
                document.getElementById('displayEmail').textContent = user.email;
                document.getElementById('displaySection').textContent = user.section_name || 'Not assigned';

                // Set Initials
                const initials = `${user.firstname.charAt(0)}${user.lastname.charAt(0)}`;
                document.getElementById('profileAvatar').textContent = initials;

                // Fill Form Fields
                document.getElementById('inputFirstName').value = user.firstname;
                document.getElementById('inputLastName').value = user.lastname;
                document.getElementById('inputMiddleName').value = user.middlename || '';

                // Save email for OTP request
                window.studentEmail = user.email;
            } else {
                showNotification(result.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error fetching profile:', error);
            showNotification('Failed to load profile data', 'error');
        });
}

// Setup Name Update Form Submission
function setupNameUpdateForm() {
    const form = document.getElementById('profileUpdateNameForm');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const btn = document.getElementById('btnUpdateName');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving...';

        const data = {
            action: 'update_name',
            school_id: studentSchoolId,
            firstname: document.getElementById('inputFirstName').value,
            lastname: document.getElementById('inputLastName').value,
            middlename: document.getElementById('inputMiddleName').value
        };

        fetch('../api/profile.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
            .then(response => response.json())
            .then(result => {
                btn.disabled = false;
                btn.innerHTML = originalText;

                if (result.success) {
                    showNotification('Profile updated successfully!', 'success');
                    loadProfileData(); // Refresh display

                    // Update header name if it exists (for dashboard UI)
                    const welcomeText = document.querySelector('header p.text-sm');
                    if (welcomeText) {
                        welcomeText.textContent = `Welcome back, ${data.firstname}!`;
                    }
                } else {
                    showNotification(result.message, 'error');
                }
            })
            .catch(error => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                console.error('Error updating name:', error);
                showNotification('An error occurred. Please try again.', 'error');
            });
    });
}

// Setup real-time password requirements validation
function setupPasswordValidation() {
    const passwordInput = document.getElementById('inputNewPassword');
    if (!passwordInput) return;

    passwordInput.addEventListener('input', function () {
        const val = this.value;

        // Validation rules
        const rules = {
            length: val.length >= 8,
            upper: /[A-Z]/.test(val),
            lower: /[a-z]/.test(val),
            number: /[0-9]/.test(val),
            special: /[^A-Za-z0-9\s]/.test(val),
            space: val.length > 0 ? !/\s/.test(val) : true
        };

        let allMet = true;

        // Update UI for each rule
        for (const [key, met] of Object.entries(rules)) {
            const el = document.getElementById(`req-${key}`);
            const icon = document.getElementById(`icon-${key}`);
            if (!el || !icon) continue;

            if (met) {
                el.classList.replace('text-red-500', 'text-green-500');
                icon.classList.replace('fa-times-circle', 'fa-check-circle');
            } else {
                el.classList.replace('text-green-500', 'text-red-500');
                icon.classList.replace('fa-check-circle', 'fa-times-circle');
                allMet = false;
            }
        }

        // Track valid state globally in the window context for the profile
        window.isPasswordComplexEnough = allMet && val.length >= 8;
    });
}

// Setup OTP Input Auto-advance logic
function setupOtpInputs() {
    const inputs = document.querySelectorAll('.otp-input');

    inputs.forEach((input, index) => {
        // Handle numerical input only
        input.addEventListener('input', function (e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length === 1 && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
        });

        // Handle backspace
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace' && this.value.length === 0 && index > 0) {
                inputs[index - 1].focus();
            }
        });

        // Handle paste
        input.addEventListener('paste', function (e) {
            e.preventDefault();
            const pasteData = e.clipboardData.getData('text').slice(0, 6);
            if (!/^\d+$/.test(pasteData)) return;

            const digits = pasteData.split('');
            digits.forEach((digit, i) => {
                if (inputs[index + i]) {
                    inputs[index + i].value = digit;
                }
            });

            if (inputs[index + digits.length - 1]) {
                inputs[index + digits.length - 1].focus();
            } else {
                inputs[inputs.length - 1].focus();
            }
        });
    });
}

// Global function to request OTP
function handleRequestOtp() {
    const btn = document.getElementById('btnRequestOtp');
    const originalContent = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Sending...';

    fetch('../api/profile.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'request_password_otp',
            school_id: studentSchoolId,
            email: window.studentEmail
        })
    })
        .then(response => response.json())
        .then(result => {
            btn.disabled = false;
            btn.innerHTML = originalContent;

            if (result.success) {
                showNotification('Verification code sent to your email!', 'success');
                document.getElementById('stepRequestOtp').classList.add('hidden');
                document.getElementById('stepVerifyOtp').classList.remove('hidden');
            } else {
                showNotification(result.message, 'error');
            }
        })
        .catch(error => {
            btn.disabled = false;
            btn.innerHTML = originalContent;
            console.error('Error requesting OTP:', error);
            showNotification('Failed to send verification code.', 'error');
        });
}

// Global function to cancel password change
function cancelChangePassword() {
    document.getElementById('stepRequestOtp').classList.remove('hidden');
    document.getElementById('stepVerifyOtp').classList.add('hidden');

    // Clear fields
    document.getElementById('inputNewPassword').value = '';
    document.querySelectorAll('.otp-input').forEach(input => input.value = '');
}

// Global function for password update
function handleUpdatePassword() {
    const newPassword = document.getElementById('inputNewPassword').value.trim();

    // Collect OTP
    let otp = '';
    document.querySelectorAll('.otp-input').forEach(input => {
        otp += input.value;
    });

    // Validation
    if (!window.isPasswordComplexEnough) {
        showNotification('Please satisfy all password requirements.', 'warning');
        return;
    }
    if (otp.length < 6) {
        showNotification('Please enter the full 6-digit verification code.', 'warning');
        return;
    }

    const btn = document.getElementById('btnUpdatePassword');
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Updating...';

    fetch('../api/profile.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'update_password',
            school_id: studentSchoolId,
            otp: otp,
            new_password: newPassword
        })
    })
        .then(response => response.json())
        .then(result => {
            btn.disabled = false;
            btn.textContent = originalText;

            if (result.success) {
                showNotification('Password updated successfully!', 'success');
                cancelChangePassword(); // Reset UI
            } else {
                showNotification(result.message, 'error');
            }
        })
        .catch(error => {
            btn.disabled = false;
            btn.textContent = originalText;
            console.error('Error updating password:', error);
            showNotification('Failed to update password.', 'error');
        });
}

// Global function for password visibility
function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    const eye = document.getElementById('eye-' + inputId);

    if (input.type === 'password') {
        input.type = 'text';
        eye.classList.remove('fa-eye');
        eye.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        eye.classList.remove('fa-eye-slash');
        eye.classList.add('fa-eye');
    }
}
