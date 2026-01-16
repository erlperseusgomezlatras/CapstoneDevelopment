<?php
include "headers.php";

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
    
    // Create new student account
    function createStudentAccount($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        
        try {
            // Generate school ID (you might want to implement a better system)
            $school_id = 'STU-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            $sql = "INSERT INTO users (school_id, level_id, firstname, lastname, middlename, email, password, isApproved) 
                    VALUES (?, 4, ?, ?, ?, ?, ?, 0)";
            $stmt = $conn->prepare($sql);
            $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
            $result = $stmt->execute([
                $school_id,
                $data['firstname'],
                $data['lastname'],
                $data['middlename'] ?? null,
                $data['email'],
                $hashed_password
            ]);
            
            if ($result) {
                return json_encode([
                    'success' => true,
                    'message' => 'Account created successfully. Please wait for approval before accessing your dashboard.',
                    'school_id' => $school_id
                ]);
            } else {
                return json_encode([
                    'success' => false,
                    'message' => 'Failed to create account'
                ]);
            }
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
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
