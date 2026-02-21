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

class Profile {
    // Get student profile details
    function getProfile($json) {
        include "connection.php";
        $data = json_decode($json, true);
        $school_id = $data['school_id'];

        try {
            $sql = "SELECT u.school_id, u.firstname, u.lastname, u.middlename, u.email, u.level_id, ul.name as level_name, s.section_name 
                    FROM users u 
                    LEFT JOIN user_levels ul ON u.level_id = ul.id 
                    LEFT JOIN sections s ON u.section_id = s.id
                    WHERE u.school_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$school_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                return json_encode([
                    'success' => true,
                    'data' => $user
                ]);
            } else {
                return json_encode([
                    'success' => false,
                    'message' => 'User not found'
                ]);
            }
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }

    // Update student name
    function updateName($json) {
        include "connection.php";
        $data = json_decode($json, true);
        $school_id = $data['school_id'];
        $firstname = $data['firstname'];
        $lastname = $data['lastname'];
        $middlename = $data['middlename'];

        try {
            $sql = "UPDATE users SET firstname = ?, lastname = ?, middlename = ? WHERE school_id = ?";
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([$firstname, $lastname, $middlename, $school_id]);

            if ($result) {
                // Update session if it's the current user
                if (isset($_SESSION['school_id']) && $_SESSION['school_id'] === $school_id) {
                    $_SESSION['first_name'] = $firstname;
                }
                
                return json_encode([
                    'success' => true,
                    'message' => 'Name updated successfully'
                ]);
            } else {
                return json_encode([
                    'success' => false,
                    'message' => 'Failed to update name'
                ]);
            }
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }

    // Request OTP for password update
    function requestPasswordOTP($json) {
        include "connection.php";
        $data = json_decode($json, true);
        $school_id = $data['school_id'];
        $email = $data['email'];

        try {
            // Generate 6-digit OTP
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // Store password update data and OTP in session
            $_SESSION['pending_password_update'] = [
                'school_id' => $school_id,
                'otp' => $otp,
                'timestamp' => time()
            ];
            
            // Send OTP email
            if ($this->sendOTPEmail($email, $otp)) {
                return json_encode([
                    'success' => true,
                    'message' => 'OTP sent to your email'
                ]);
            } else {
                return json_encode([
                    'success' => false,
                    'message' => 'Failed to send OTP. Please try again.'
                ]);
            }
        } catch(Exception $e) {
            return json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    // Verify OTP and update password
    function updatePassword($json) {
        include "connection.php";
        $data = json_decode($json, true);
        $inputOtp = $data['otp'];
        $newPassword = $data['new_password'];
        $school_id = $data['school_id'];

        if (!isset($_SESSION['pending_password_update'])) {
            return json_encode([
                'success' => false,
                'message' => 'Session expired. Please request a new OTP.'
            ]);
        }

        $pending = $_SESSION['pending_password_update'];

        if ($pending['school_id'] !== $school_id) {
            return json_encode([
                'success' => false,
                'message' => 'Invalid session for this user.'
            ]);
        }

        if ($inputOtp === $pending['otp']) {
            // Check if OTP is within 10 minutes
            if (time() - $pending['timestamp'] > 600) {
                return json_encode([
                    'success' => false,
                    'message' => 'OTP has expired.'
                ]);
            }

            try {
                $hashed_password = password_hash($newPassword, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET password = ? WHERE school_id = ?";
                $stmt = $conn->prepare($sql);
                $result = $stmt->execute([$hashed_password, $school_id]);

                if ($result) {
                    unset($_SESSION['pending_password_update']);
                    return json_encode([
                        'success' => true,
                        'message' => 'Password updated successfully'
                    ]);
                } else {
                    return json_encode([
                        'success' => false,
                        'message' => 'Failed to update password'
                    ]);
                }
            } catch(PDOException $e) {
                return json_encode([
                    'success' => false,
                    'message' => 'Database error: ' . $e->getMessage()
                ]);
            }
        } else {
            return json_encode([
                'success' => false,
                'message' => 'Invalid OTP'
            ]);
        }
    }

    // Helper to send email
    private function sendOTPEmail($email, $otp) {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = SMTP_SECURE === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = SMTP_PORT;

            $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Security Code for PHINMA PMS';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; padding: 20px; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #eee; border-radius: 10px;'>
                    <div style='text-align: center; margin-bottom: 20px;'>
                        <h2 style='color: #058643; margin: 0;'>Security Verification</h2>
                        <p style='color: #666; font-size: 14px;'>PHINMA Practicum Management System</p>
                    </div>
                    <p>Hello,</p>
                    <p>You have requested to change your password. Please use the following verification code to complete the process:</p>
                    <div style='background-color: #f9f9f9; border: 2px dashed #058643; padding: 20px; font-size: 32px; font-weight: bold; text-align: center; border-radius: 8px; letter-spacing: 8px; margin: 25px 0; color: #058643;'>
                        $otp
                    </div>
                    <p style='color: #e53e3e; font-size: 14px; font-weight: bold;'>Important: This code will expire in 10 minutes.</p>
                    <p>If you did not request this change, please ignore this email and ensure your account is secure.</p>
                    <hr style='border: none; border-top: 1px solid #eee; margin: 25px 0;'>
                    <p style='font-size: 12px; color: #888; text-align: center;'>This is an automated security notification. Please do not reply to this email.</p>
                </div>";
            $mail->AltBody = "Your verification code for PHINMA PMS password update is: $otp";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
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
    
    $profile = new Profile();
    
    switch ($data['action']) {
        case 'get_profile':
            echo $profile->getProfile($json);
            break;
        case 'update_name':
            echo $profile->updateName($json);
            break;
        case 'request_password_otp':
            echo $profile->requestPasswordOTP($json);
            break;
        case 'update_password':
            echo $profile->updatePassword($json);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
}
