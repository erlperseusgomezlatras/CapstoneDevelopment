<?php
include "headers.php";
require_once "../config/config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../vendor/autoload.php";
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class Auth {
    
    // Verify email domain and check user existence
    function verifyEmail($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $email = $data['email'];
        
        try {
            // Check if email domain is allowed
            $domain_sql = "SELECT * FROM allowed_email_domains WHERE domain_name = ?";
            $stmt = $conn->prepare($domain_sql);
            $email_domain = '@' . substr(strrchr($email, "@"), 1);
            $stmt->execute([$email_domain]);
            $domain_result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$domain_result) {
                return json_encode([
                    'success' => false,
                    'message' => 'Email domain is not allowed'
                ]);
            }
            
            // Check if user exists
            $user_sql = "SELECT u.*, ul.name as level_name FROM users u 
                        LEFT JOIN user_levels ul ON u.level_id = ul.id 
                        WHERE u.email = ?";
            $stmt = $conn->prepare($user_sql);
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // User exists, return user info for password verification
                return json_encode([
                    'success' => true,
                    'user_exists' => true,
                    'user_level' => $user['level_name'],
                    'level_id' => $user['level_id'],
                    'user_data' => [
                        'school_id' => $user['school_id'],
                        'firstname' => $user['firstname'],
                        'lastname' => $user['lastname'],
                        'email' => $user['email'],
                        'isApproved' => $user['isApproved']
                    ]
                ]);
            } else {
                // User doesn't exist, proceed to registration
                return json_encode([
                    'success' => true,
                    'user_exists' => false,
                    'message' => 'Email verified. Please complete your registration.'
                ]);
            }
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Create new student account - Now requires OTP
    function createStudentAccount($json) {
        $data = json_decode($json, true);
        
        try {
            // Validate required fields
            if (empty($data['schoolId'])) {
                return json_encode([
                    'success' => false,
                    'message' => 'School ID is required'
                ]);
            }
            
            if (empty($data['section_id'])) {
                return json_encode([
                    'success' => false,
                    'message' => 'Section is required'
                ]);
            }

            // Check if email already exists in users table
            include "connection.php";
            $check_sql = "SELECT school_id FROM users WHERE email = ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->execute([$data['email']]);
            if ($stmt->fetch()) {
                return json_encode([
                    'success' => false,
                    'message' => 'Email already registered'
                ]);
            }
            
            // Generate 6-digit OTP
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // Store registration data and OTP in session (temporary storage)
            $_SESSION['pending_registration'] = [
                'data' => $data,
                'otp' => $otp,
                'timestamp' => time()
            ];
            
            // Send OTP email
            if ($this->sendOTPEmail($data['email'], $otp)) {
                return json_encode([
                    'success' => true,
                    'requires_otp' => true,
                    'message' => 'OTP sent to your email'
                ]);
            } else {
                return json_encode([
                    'success' => false,
                    'message' => 'Failed to send OTP. Please try again.'
                ]);
            }
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }

    // Verify OTP and Save to Database
    function verifyOTP($json) {
        $data = json_decode($json, true);
        $inputOtp = $data['otp'] ?? '';
        
        if (!isset($_SESSION['pending_registration'])) {
            return json_encode([
                'success' => false,
                'message' => 'Session expired. Please start over.'
            ]);
        }
        
        $pending = $_SESSION['pending_registration'];
        
        if ($inputOtp === $pending['otp']) {
            // OTP is correct, save to database
            return $this->saveUserToDatabase($pending['data']);
        } else {
            return json_encode([
                'success' => false,
                'message' => 'Invalid OTP'
            ]);
        }
    }

    // Resend OTP
    function resendOTP() {
        if (!isset($_SESSION['pending_registration'])) {
            return json_encode([
                'success' => false,
                'message' => 'Session expired. Please start over.'
            ]);
        }
        
        $pending = $_SESSION['pending_registration'];
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Update OTP in session
        $_SESSION['pending_registration']['otp'] = $otp;
        
        if ($this->sendOTPEmail($pending['data']['email'], $otp)) {
            return json_encode([
                'success' => true,
                'message' => 'New OTP sent to your email'
            ]);
        } else {
            return json_encode([
                'success' => false,
                'message' => 'Failed to send OTP'
            ]);
        }
    }

    // Helper to save user to database
    private function saveUserToDatabase($userData) {
        include "connection.php";
        
        try {
            $sql = "INSERT INTO users (school_id, level_id, firstname, lastname, middlename, email, password, section_id, isApproved) 
                    VALUES (?, 4, ?, ?, ?, ?, ?, ?, 0)";
            $stmt = $conn->prepare($sql);
            $hashed_password = password_hash($userData['password'], PASSWORD_DEFAULT);
            $result = $stmt->execute([
                $userData['schoolId'],
                $userData['firstname'],
                $userData['lastname'],
                $userData['middlename'] ?? null,
                $userData['email'],
                $hashed_password,
                $userData['section_id']
            ]);
            
            if ($result) {
                // Clear session after successful registration
                unset($_SESSION['pending_registration']);
                
                return json_encode([
                    'success' => true,
                    'message' => 'Account created successfully. Please wait for approval.',
                    'school_id' => $userData['schoolId']
                ]);
            } else {
                return json_encode([
                    'success' => false,
                    'message' => 'Failed to create account in database'
                ]);
            }
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }

    // Helper to send email using PHPMailer and SMTP
    private function sendOTPEmail($email, $otp) {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            // $mail->SMTPDebug = SMTP::DEBUG_SERVER;              // Enable verbose debug output
            $mail->isSMTP();                                       // Send using SMTP
            $mail->Host       = SMTP_HOST;                         // Set the SMTP server to send through
            $mail->SMTPAuth   = true;                              // Enable SMTP authentication
            $mail->Username   = SMTP_USER;                         // SMTP username
            $mail->Password   = SMTP_PASS;                         // SMTP password
            $mail->SMTPSecure = SMTP_SECURE === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = SMTP_PORT;                         // TCP port to connect to

            // Recipients
            $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
            $mail->addAddress($email);                             // Add a recipient

            // Content
            $mail->isHTML(true);                                   // Set email format to HTML
            $mail->Subject = 'Verification Code for PHINMA PMS';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; padding: 20px; color: #333;'>
                    <h2 style='color: #058643;'>Email Verification</h2>
                    <p>Hello,</p>
                    <p>Your verification code for the PHINMA Practicum Management System is:</p>
                    <div style='background-color: #f4f4f4; padding: 15px; font-size: 24px; font-weight: bold; text-align: center; border-radius: 5px; letter-spacing: 5px;'>
                        $otp
                    </div>
                    <p>Please enter this code on the registration page to complete your account setup.</p>
                    <p>If you did not request this code, please ignore this email.</p>
                    <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                    <p style='font-size: 12px; color: #777;'>This is an automated message. Please do not reply to this email.</p>
                </div>";
            $mail->AltBody = "Your verification code is: $otp";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }
    
    // Login with password
    function login($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        
        try {
            $sql = "SELECT u.*, ul.name as level_name FROM users u 
                    LEFT JOIN user_levels ul ON u.level_id = ul.id 
                    WHERE u.email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$data['email']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return json_encode([
                    'success' => false,
                    'message' => 'User not found'
                ]);
            }
            
            if ($user['level_id'] == 4 && $user['isApproved'] == 0) {
                return json_encode([
                    'success' => false,
                    'message' => 'Your account is pending approval. Please wait for administrator approval.'
                ]);
            }
            
            if (password_verify($data['password'], $user['password'])) {
                // Generate JWT token
                $token = $this->generateJWTToken($user);
                
                return json_encode([
                    'success' => true,
                    'message' => 'Login successful',
                    'token' => $token,
                    'user' => [
                        'school_id' => $user['school_id'],
                        'firstname' => $user['firstname'],
                        'lastname' => $user['lastname'],
                        'email' => $user['email'],
                        'level' => $user['level_name'],
                        'level_id' => $user['level_id']
                    ]
                ]);
            } else {
                return json_encode([
                    'success' => false,
                    'message' => 'Incorrect password'
                ]);
            }
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Logout user
    function logout($json) {
        // Clear session and cookies
        session_start();
        session_destroy();
        
        // Clear cookies
        if (isset($_COOKIE['authToken'])) {
            setcookie('authToken', '', time() - 3600, '/');
        }
        if (isset($_COOKIE['userData'])) {
            setcookie('userData', '', time() - 3600, '/');
        }
        
        return json_encode([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }
    
    // Generate JWT Token
    private function generateJWTToken($user) {
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode([
            'school_id' => $user['school_id'],
            'firstname' => $user['firstname'],
            'lastname' => $user['lastname'],
            'email' => $user['email'],
            'level' => $user['level_name'],
            'level_id' => $user['level_id'],
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60) // 24 hours
        ]));
        
        // Simple token (in production, use proper HMAC signing)
        return $header . '.' . $payload;
    }
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!isset($data['action'])) {
        echo json_encode(['success' => false, 'message' => 'Action not specified']);
        exit;
    }
    
    $auth = new Auth();
    
    switch ($data['action']) {
        case 'verify_email':
            echo $auth->verifyEmail($json);
            break;
        case 'create_account':
            echo $auth->createStudentAccount($json);
            break;
        case 'verify_otp':
            echo $auth->verifyOTP($json);
            break;
        case 'resend_otp':
            echo $auth->resendOTP();
            break;
        case 'login':
            echo $auth->login($json);
            break;
        case 'logout':
            echo $auth->logout($json);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
}
?>
