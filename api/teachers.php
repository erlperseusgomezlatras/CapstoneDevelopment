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
            $sql = "SELECT u.*, s.section_name, 
                           GROUP_CONCAT(DISTINCT CONCAT(ps.id, ':', ps.school_type, ':', ps.name) SEPARATOR '||') as partnered_school_name 
                    FROM users u 
                    LEFT JOIN sections s ON u.section_id = s.id 
                    LEFT JOIN section_schools ss ON s.id = ss.section_id
                    LEFT JOIN partnered_schools ps ON ss.school_id = ps.id 
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
            
            $sql .= " GROUP BY u.school_id, u.firstname, u.lastname, u.email, u.section_id, u.isActive, s.section_name";
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
            if (!empty($data['password'])) {
                // Skip length validation if password is the same as school_id
                if ($data['password'] !== $data['school_id'] && strlen($data['password']) < 6) {
                    $errors['password'] = 'Password must be at least 6 characters';
                }
            }
            
            // Check if user exists (could be teacher or coordinator)
            $check_sql = "SELECT school_id FROM users WHERE school_id = ? AND level_id IN (2, 3)";
            $stmt = $conn->prepare($check_sql);
            $stmt->execute([$data['school_id']]);
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                // If new school_id doesn't exist, check if we're updating and original exists
                if (isset($data['original_school_id'])) {
                    $check_original_sql = "SELECT school_id FROM users WHERE school_id = ? AND level_id IN (2, 3)";
                    $stmt = $conn->prepare($check_original_sql);
                    $stmt->execute([$data['original_school_id']]);
                    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                        $errors['school_id'] = 'Original user not found';
                    }
                } else {
                    $errors['school_id'] = 'User not found';
                }
            }
            
            // Check if email already exists (excluding current user)
            if (!empty($data['email'])) {
                // Use original school_id if available, otherwise use new school_id
                $exclude_school_id = isset($data['original_school_id']) ? $data['original_school_id'] : $data['school_id'];
                $check_email_sql = "SELECT email FROM users WHERE email = ? AND email IS NOT NULL AND school_id != ?";
                $stmt = $conn->prepare($check_email_sql);
                $stmt->execute([$data['email'], $exclude_school_id]);
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
            if ($data['school_id'] !== ($data['original_school_id'] ?? '')) {
                // School ID is changing, need to update it
                $sql = "UPDATE users SET school_id = ?, firstname = ?, lastname = ?, middlename = ?, email = ?, section_id = ?, isActive = ?";
                $params = [$data['school_id'], $data['firstname'], $data['lastname'], $data['middlename'] ?? null, $data['email']];
            } else {
                // School ID is not changing, exclude it from update
                $sql = "UPDATE users SET firstname = ?, lastname = ?, middlename = ?, email = ?, section_id = ?, isActive = ?";
                $params = [$data['firstname'], $data['lastname'], $data['middlename'] ?? null, $data['email']];
            }
            
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
            $params[] = $data['original_school_id'] ?? $data['school_id'];
            
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
            $sql = "SELECT GROUP_CONCAT(DISTINCT CONCAT(ps.id, ':', ps.school_type, ':', ps.name) SEPARATOR '||') as name 
                    FROM section_schools ss
                    JOIN partnered_schools ps ON ss.school_id = ps.id 
                    WHERE ss.section_id = ?";
            
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
    
    // Create new section
    function createSection($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        
        try {
            // Validate required fields
            $required_fields = ['section_name'];
            $errors = [];
            
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
                }
            }
            
            // Check if section name already exists
            $check_sql = "SELECT section_name FROM sections WHERE section_name = ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->execute([$data['section_name']]);
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                $errors['section_name'] = 'Section name already exists';
            }
            
            // Check if partnered school is already assigned to another section
            // Note: With multiple schools support, this check might need to be less strict or removed
            // For now, we'll skip this check or adapt it if necessary
            /*
            if (!empty($data['school_id'])) {
                $school_check_sql = "SELECT s.section_name 
                                     FROM sections s 
                                     JOIN section_schools ss ON s.id = ss.section_id 
                                     WHERE ss.school_id = ?";
                $stmt = $conn->prepare($school_check_sql);
                $stmt->execute([$data['school_id']]);
                $existing_section = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($existing_section) {
                    $errors['school_id'] = 'This partnered school is already assigned to section: ' . $existing_section['section_name'];
                }
            }
            */
            
            if (!empty($errors)) {
                return json_encode([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors
                ]);
            }
            
            // Insert new section
            $sql = "INSERT INTO sections (section_name) VALUES (?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$data['section_name']]);
            $section_id = $conn->lastInsertId();

            // Insert school relation if provided
            if (!empty($data['school_id'])) {
                $sql_school = "INSERT INTO section_schools (section_id, school_id) VALUES (?, ?)";
                $stmt_school = $conn->prepare($sql_school);
                $stmt_school->execute([$section_id, $data['school_id']]);
            }
            
            return json_encode([
                'success' => true,
                'message' => 'Section created successfully',
                'id' => $conn->lastInsertId()
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Read all sections
    function readSections($json) {
        include "connection.php";
        
        try {
            $sql = "SELECT s.id, s.section_name,
                           GROUP_CONCAT(DISTINCT CONCAT(ps.name, ' (', ps.school_type, ')') SEPARATOR ', ') as school_name
                    FROM sections s
                    LEFT JOIN section_schools ss ON s.id = ss.section_id
                    LEFT JOIN partnered_schools ps ON ss.school_id = ps.id
                    GROUP BY s.id, s.section_name
                    ORDER BY s.section_name";
            
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
    
    // Update section
    function updateSection($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        
        if (empty($data['id'])) {
            return json_encode([
                'success' => false,
                'message' => 'Section ID is required'
            ]);
        }
        
        try {
            // Validate required fields
            $required_fields = ['section_name'];
            $errors = [];
            
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
                }
            }
            
            // Check if section name already exists (excluding current section)
            $check_sql = "SELECT section_name FROM sections WHERE section_name = ? AND id != ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->execute([$data['section_name'], $data['id']]);
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                $errors['section_name'] = 'Section name already exists';
            }
            
            // Check if partnered school is already assigned to another section (excluding current section)
            // Skipped strict check for now to allow flexible assignment or manual management
            /*
            if (!empty($data['school_id'])) {
                $school_check_sql = "SELECT s.section_name FROM sections s 
                                     JOIN section_schools ss ON s.id = ss.section_id 
                                     WHERE ss.school_id = ? AND s.id != ?";
                $stmt = $conn->prepare($school_check_sql);
                $stmt->execute([$data['school_id'], $data['id']]);
                $existing_section = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($existing_section) {
                    $errors['school_id'] = 'This partnered school is already assigned to section: ' . $existing_section['section_name'];
                }
            }
            */
            
            if (!empty($errors)) {
                return json_encode([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors
                ]);
            }
            
            // Update section name
            $sql = "UPDATE sections SET section_name = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $data['section_name'],
                $data['id']
            ]);

            // Update school relation
            // First delete existing relations (simplified approach for single selection UI)
            $delete_sql = "DELETE FROM section_schools WHERE section_id = ?";
            $del_stmt = $conn->prepare($delete_sql);
            $del_stmt->execute([$data['id']]);

            // Insert new relation if provided
            if (!empty($data['school_id'])) {
                $insert_sql = "INSERT INTO section_schools (section_id, school_id) VALUES (?, ?)";
                $ins_stmt = $conn->prepare($insert_sql);
                $ins_stmt->execute([$data['id'], $data['school_id']]);
            }
            
            return json_encode([
                'success' => true,
                'message' => 'Section updated successfully'
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Delete section
    function deleteSection($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        
        if (empty($data['id'])) {
            return json_encode([
                'success' => false,
                'message' => 'Section ID is required'
            ]);
        }
        
        try {
            // Check if section is being used by users
            $check_sql = "SELECT COUNT(*) as count FROM users WHERE section_id = ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->execute([$data['id']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                return json_encode([
                    'success' => false,
                    'message' => 'Cannot delete section. It is assigned to ' . $result['count'] . ' user(s).'
                ]);
            }
            
            // Delete section
            $sql = "DELETE FROM sections WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$data['id']]);
            
            return json_encode([
                'success' => true,
                'message' => 'Section deleted successfully'
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Get partnered schools for dropdown
    function getPartneredSchoolsForDropdown($json) {
        include "connection.php";
        
        try {
            $sql = "SELECT id, name FROM partnered_schools WHERE isActive = 1 ORDER BY name";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return json_encode([
                'success' => true,
                'data' => $schools
            ]);
            
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
        
    // Section operations
    case 'create_section':
        echo $teachers->createSection($json);
        break;
        
    case 'read_sections':
        echo $teachers->readSections($json);
        break;
        
    case 'update_section':
        echo $teachers->updateSection($json);
        break;
        
    case 'delete_section':
        echo $teachers->deleteSection($json);
        break;
        
    case 'get_partnered_schools_dropdown':
        echo $teachers->getPartneredSchoolsForDropdown($json);
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
