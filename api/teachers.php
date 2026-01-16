<?php
include "headers.php";

class Teachers {
    
    // Create new teacher/coordinator
    function create($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        
        try {
            // Validate required fields
            $required_fields = ['school_id', 'firstname', 'lastname', 'email', 'password'];
            $errors = [];
            
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
                }
            }
            
            // Validate email format
            if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format';
            }
            
            // Validate email domain against allowed domains
            if (!empty($data['email'])) {
                $email_domain = substr(strrchr($data['email'], "@"), 0);
                $domain_check_sql = "SELECT domain_name FROM allowed_email_domains WHERE domain_name = ?";
                $stmt = $conn->prepare($domain_check_sql);
                $stmt->execute([$email_domain]);
                if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                    $errors['email'] = 'Email domain is not allowed. Only @phinmaed.com domain is permitted.';
                }
            }
            
            // Validate password length
            if (!empty($data['password']) && strlen($data['password']) < 6) {
                $errors['password'] = 'Password must be at least 6 characters';
            }
            
            // Check if school ID already exists
            $check_sql = "SELECT school_id FROM users WHERE school_id = ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->execute([$data['school_id']]);
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                $errors['school_id'] = 'School ID already exists';
            }
            
            // Check if email already exists
            if (!empty($data['email'])) {
                $check_email_sql = "SELECT email FROM users WHERE email = ? AND email IS NOT NULL";
                $stmt = $conn->prepare($check_email_sql);
                $stmt->execute([$data['email']]);
                if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                    $errors['email'] = 'Email already exists';
                }
            }
            
            if (!empty($errors)) {
                return json_encode([
                    'success' => false,
                    'message' => 'Validation errors occurred',
                    'errors' => $errors
                ]);
            }
            
            // Hash password
            $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Insert teacher/coordinator
            $sql = "INSERT INTO users (school_id, level_id, firstname, lastname, middlename, email, section_id, isActive, password) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $section_id = !empty($data['section_id']) ? $data['section_id'] : null;
            $isActive = isset($data['isActive']) ? $data['isActive'] : 1;
            
            $result = $stmt->execute([
                $data['school_id'], 
                $data['level_id'], 
                $data['firstname'], 
                $data['lastname'], 
                $data['middlename'] ?? null, 
                $data['email'], 
                $section_id, 
                $isActive, 
                $hashed_password
            ]);
            
            if ($result) {
                return json_encode([
                    'success' => true,
                    'message' => 'User created successfully'
                ]);
            } else {
                return json_encode([
                    'success' => false,
                    'message' => 'Failed to create user'
                ]);
            }
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Read teachers with search and filter
    function read($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $search = isset($data['search']) ? $data['search'] : '';
        $status_filter = isset($data['status_filter']) ? $data['status_filter'] : '';
        $user_level = isset($data['user_level']) ? $data['user_level'] : 'teacher';
        
        try {
            // Determine level_id based on user_level
            $level_id = $user_level === 'coordinator' ? 3 : 2;
            
            // Base query for teachers/coordinators
            $sql = "SELECT u.*, s.section_name, ps.name as partnered_school_name 
                    FROM users u 
                    LEFT JOIN sections s ON u.section_id = s.id 
                    LEFT JOIN partnered_schools ps ON s.school_id = ps.id 
                    WHERE u.level_id = ?";
            
            $params = [$level_id];
            
            // Add search conditions
            if (!empty($search)) {
                $sql .= " AND (u.school_id LIKE ? OR u.firstname LIKE ? OR u.lastname LIKE ? OR u.email LIKE ?)";
                $search_param = "%$search%";
                $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
            }
            
            // Add status filter
            if ($status_filter !== '') {
                $sql .= " AND u.isActive = ?";
                $params[] = $status_filter;
            }
            
            $sql .= " ORDER BY u.firstname, u.lastname";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return json_encode([
                'success' => true,
                'data' => $teachers
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Update teacher information
    function update($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        
        try {
            // Validate required fields
            $required_fields = ['school_id', 'firstname', 'lastname', 'email'];
            $errors = [];
            
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
                }
            }
            
            // Validate email format
            if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format';
            }
            
            // Validate email domain against allowed domains
            if (!empty($data['email'])) {
                $email_domain = substr(strrchr($data['email'], "@"), 0);
                $domain_check_sql = "SELECT domain_name FROM allowed_email_domains WHERE domain_name = ?";
                $stmt = $conn->prepare($domain_check_sql);
                $stmt->execute([$email_domain]);
                if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                    $errors['email'] = 'Email domain is not allowed. Only @phinmaed.com domain is permitted.';
                }
            }
            
            // Validate password if provided
            if (!empty($data['password']) && strlen($data['password']) < 6) {
                $errors['password'] = 'Password must be at least 6 characters';
            }
            
            // Check if user exists (could be teacher or coordinator)
            $check_sql = "SELECT school_id FROM users WHERE school_id = ? AND level_id IN (2, 3)";
            $stmt = $conn->prepare($check_sql);
            $stmt->execute([$data['school_id']]);
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                $errors['school_id'] = 'User not found';
            }
            
            // Check if email already exists (excluding current user)
            if (!empty($data['email'])) {
                $check_email_sql = "SELECT email FROM users WHERE email = ? AND email IS NOT NULL AND school_id != ?";
                $stmt = $conn->prepare($check_email_sql);
                $stmt->execute([$data['email'], $data['school_id']]);
                if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                    $errors['email'] = 'Email already exists';
                }
            }
            
            if (!empty($errors)) {
                return json_encode([
                    'success' => false,
                    'message' => 'Validation errors occurred',
                    'errors' => $errors
                ]);
            }
            
            // Build update query
            $sql = "UPDATE users SET firstname = ?, lastname = ?, middlename = ?, email = ?, section_id = ?, isActive = ?";
            $params = [$data['firstname'], $data['lastname'], $data['middlename'] ?? null, $data['email']];
            
            // Add section_id (can be null)
            $section_id = !empty($data['section_id']) ? $data['section_id'] : null;
            $params[] = $section_id;
            
            $params[] = isset($data['isActive']) ? $data['isActive'] : 1;
            
            // Add password if provided
            if (!empty($data['password'])) {
                $sql .= ", password = ?";
                $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
                $params[] = $hashed_password;
            }
            
            $sql .= " WHERE school_id = ? AND level_id IN (2, 3)";
            $params[] = $data['school_id'];
            
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                return json_encode([
                    'success' => true,
                    'message' => 'User updated successfully'
                ]);
            } else {
                return json_encode([
                    'success' => false,
                    'message' => 'Failed to update user'
                ]);
            }
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Delete teacher/coordinator
    function delete($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        
        if (empty($data['school_id'])) {
            return json_encode([
                'success' => false,
                'message' => 'School ID is required'
            ]);
        }
        
        try {
            // Check if user exists (could be teacher or coordinator)
            $check_sql = "SELECT school_id FROM users WHERE school_id = ? AND level_id IN (2, 3)";
            $stmt = $conn->prepare($check_sql);
            $stmt->execute([$data['school_id']]);
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                return json_encode([
                    'success' => false,
                    'message' => 'User not found'
                ]);
            }
            
            // Delete user
            $sql = "DELETE FROM users WHERE school_id = ? AND level_id IN (2, 3)";
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([$data['school_id']]);
            
            if ($result) {
                return json_encode([
                    'success' => true,
                    'message' => 'User deleted successfully'
                ]);
            } else {
                return json_encode([
                    'success' => false,
                    'message' => 'Failed to delete user'
                ]);
            }
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Toggle teacher/coordinator status
    function toggleStatus($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        
        if (empty($data['school_id']) || !isset($data['isActive'])) {
            return json_encode([
                'success' => false,
                'message' => 'School ID and status are required'
            ]);
        }
        
        try {
            // Check if user exists (could be teacher or coordinator)
            $check_sql = "SELECT school_id FROM users WHERE school_id = ? AND level_id IN (2, 3)";
            $stmt = $conn->prepare($check_sql);
            $stmt->execute([$data['school_id']]);
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                return json_encode([
                    'success' => false,
                    'message' => 'User not found'
                ]);
            }
            
            // Update status
            $sql = "UPDATE users SET isActive = ? WHERE school_id = ? AND level_id IN (2, 3)";
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([$data['isActive'], $data['school_id']]);
            
            if ($result) {
                $status_text = $data['isActive'] == 1 ? 'activated' : 'deactivated';
                return json_encode([
                    'success' => true,
                    'message' => "User $status_text successfully"
                ]);
            } else {
                return json_encode([
                    'success' => false,
                    'message' => 'Failed to update user status'
                ]);
            }
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Get sections for dropdown
    function getSections($json) {
        include "connection.php";
        
        try {
            $sql = "SELECT id, section_name FROM sections ORDER BY section_name";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return json_encode([
                'success' => true,
                'data' => $sections
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Get partnered school for a section
    function getPartneredSchool($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        
        if (empty($data['section_id'])) {
            return json_encode([
                'success' => false,
                'message' => 'Section ID is required'
            ]);
        }
        
        try {
            $sql = "SELECT ps.id, ps.name, ps.school_id_code 
                    FROM partnered_schools ps 
                    INNER JOIN sections s ON s.school_id = ps.id 
                    WHERE s.id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$data['section_id']]);
            $partnered_school = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($partnered_school) {
                return json_encode([
                    'success' => true,
                    'data' => $partnered_school
                ]);
            } else {
                return json_encode([
                    'success' => false,
                    'message' => 'No partnered school assigned to this section'
                ]);
            }
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
}

$operation = isset($_POST["operation"]) ? $_POST["operation"] : "0";
$json = isset($_POST["json"]) ? $_POST["json"] : "0";

$teachers = new Teachers();

switch ($operation) {
    case 'create':
        echo $teachers->create($json);
        break;
        
    case 'read':
        echo $teachers->read($json);
        break;
        
    case 'update':
        echo $teachers->update($json);
        break;
        
    case 'delete':
        echo $teachers->delete($json);
        break;
        
    case 'toggle_status':
        echo $teachers->toggleStatus($json);
        break;
        
    case 'get_sections':
        echo $teachers->getSections($json);
        break;
        
    case 'get_partnered_school':
        echo $teachers->getPartneredSchool($json);
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid operation'
        ]);
        http_response_code(400);
        break;
}
?>
