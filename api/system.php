<?php
include "headers.php";
date_default_timezone_set('Asia/Manila');

class System {
    
    // ==================== ACADEMIC SESSIONS ====================
    
    // Read all academic sessions
    function readAcademicSessions($json) {
        include "connection.php";
        
        try {
            $sql = "SELECT 
                        asession.academic_session_id,
                        asession.school_year_id,
                        asession.semester_id,
                        asession.is_Active,
                        asession.created_at,
                        asession.updated_at,
                        sy.school_year,
                        s.semester_name
                    FROM academic_sessions asession
                    INNER JOIN school_years sy ON asession.school_year_id = sy.school_year_id
                    INNER JOIN semesters s ON asession.semester_id = s.semester_id
                    ORDER BY sy.school_year DESC, s.semester_name ASC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return json_encode([
                'success' => true,
                'message' => 'Academic sessions retrieved successfully',
                'data' => $sessions
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Read active academic session
    function readActiveAcademicSession($json) {
        include "connection.php";
        
        try {
            $sql = "SELECT 
                        asession.academic_session_id,
                        asession.school_year_id,
                        asession.semester_id,
                        asession.created_at,
                        asession.updated_at,
                        sy.school_year,
                        s.semester_name
                    FROM academic_sessions asession
                    INNER JOIN school_years sy ON asession.school_year_id = sy.school_year_id
                    INNER JOIN semesters s ON asession.semester_id = s.semester_id
                    WHERE asession.is_Active = 1
                    LIMIT 1";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $activeSession = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($activeSession) {
                return json_encode([
                    'success' => true,
                    'message' => 'Active academic session retrieved successfully',
                    'data' => $activeSession
                ]);
            } else {
                return json_encode([
                    'success' => true,
                    'message' => 'No active academic session found',
                    'data' => null
                ]);
            }
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Set active academic session
    function setActiveAcademicSession($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        
        if (!isset($data['academic_session_id'])) {
            return json_encode([
                'success' => false,
                'message' => 'Academic session ID is required'
            ]);
        }
        
        try {
            // Begin transaction
            $conn->beginTransaction();
            
            // First, set all sessions to inactive
            $updateAllSql = "UPDATE academic_sessions SET is_Active = 0";
            $conn->exec($updateAllSql);
            
            // Then, set the selected session to active
            $updateSql = "UPDATE academic_sessions 
                         SET is_Active = 1, updated_at = CURRENT_TIMESTAMP 
                         WHERE academic_session_id = ?";
            $stmt = $conn->prepare($updateSql);
            $stmt->execute([$data['academic_session_id']]);
            
            if ($stmt->rowCount() > 0) {
                $conn->commit();
                return json_encode([
                    'success' => true,
                    'message' => 'Active academic session updated successfully'
                ]);
            } else {
                $conn->rollBack();
                return json_encode([
                    'success' => false,
                    'message' => 'Failed to update active academic session'
                ]);
            }
            
        } catch(PDOException $e) {
            $conn->rollBack();
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // ==================== PARTNERED SCHOOLS ====================
    
    // Create partnered school
    function createPartneredSchool($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        
        try {
            // Validate required fields
            $required_fields = ['name', 'address', 'latitude', 'longitude'];
            $errors = [];
            
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
                }
            }
            
            if (!empty($errors)) {
                return json_encode([
                    'success' => false,
                    'message' => 'Validation errors occurred',
                    'errors' => $errors
                ]);
            }
            
            // Insert partnered school
            $sql = "INSERT INTO partnered_schools (name, address, latitude, longitude, geofencing_radius, isActive) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $geofencing_radius = isset($data['geofencing_radius']) ? $data['geofencing_radius'] : 80;
            $isActive = isset($data['isActive']) ? $data['isActive'] : 1;
            
            $result = $stmt->execute([
                $data['name'],
                $data['address'],
                $data['latitude'],
                $data['longitude'],
                $geofencing_radius,
                $isActive
            ]);
            
            if ($result) {
                return json_encode([
                    'success' => true,
                    'message' => 'Partnered school created successfully'
                ]);
            } else {
                return json_encode([
                    'success' => false,
                    'message' => 'Failed to create partnered school'
                ]);
            }
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Read partnered schools
    function readPartneredSchools($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $search = isset($data['search']) ? $data['search'] : '';
        $status_filter = isset($data['status_filter']) ? $data['status_filter'] : '';
        
        try {
            $sql = "SELECT * FROM partnered_schools WHERE 1=1";
            $params = [];
            
            // Add search conditions
            if (!empty($search)) {
                $sql .= " AND (name LIKE ? OR address LIKE ?)";
                $search_param = "%$search%";
                $params = array_merge($params, [$search_param, $search_param]);
            }
            
            // Add status filter
            if ($status_filter !== '') {
                $sql .= " AND isActive = ?";
                $params[] = $status_filter;
            }
            
            $sql .= " ORDER BY name ASC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
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
    
    // Update partnered school
    function updatePartneredSchool($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        
        try {
            // Validate required fields
            $required_fields = ['id', 'name', 'address', 'latitude', 'longitude'];
            $errors = [];
            
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
                }
            }
            
            if (!empty($errors)) {
                return json_encode([
                    'success' => false,
                    'message' => 'Validation errors occurred',
                    'errors' => $errors
                ]);
            }
            
            // Update partnered school
            $sql = "UPDATE partnered_schools 
                    SET name = ?, address = ?, latitude = ?, longitude = ?, geofencing_radius = ?
                    WHERE id = ?";
            
            $stmt = $conn->prepare($sql);
            $geofencing_radius = isset($data['geofencing_radius']) ? $data['geofencing_radius'] : 80;
            
            $result = $stmt->execute([
                $data['name'],
                $data['address'],
                $data['latitude'],
                $data['longitude'],
                $geofencing_radius,
                $data['id']
            ]);
            
            if ($result) {
                return json_encode([
                    'success' => true,
                    'message' => 'Partnered school updated successfully'
                ]);
            } else {
                return json_encode([
                    'success' => false,
                    'message' => 'Failed to update partnered school'
                ]);
            }
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Delete partnered school
    function deletePartneredSchool($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        
        if (empty($data['id'])) {
            return json_encode([
                'success' => false,
                'message' => 'School ID is required'
            ]);
        }
        
        try {
            // Check if school is being used by sections
            $check_sql = "SELECT COUNT(*) as count FROM sections WHERE school_id = ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->execute([$data['id']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                return json_encode([
                    'success' => false,
                    'message' => 'Cannot delete partnered school. It is assigned to ' . $result['count'] . ' section(s).'
                ]);
            }
            
            // Delete partnered school
            $sql = "DELETE FROM partnered_schools WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([$data['id']]);
            
            if ($result) {
                return json_encode([
                    'success' => true,
                    'message' => 'Partnered school deleted successfully'
                ]);
            } else {
                return json_encode([
                    'success' => false,
                    'message' => 'Failed to delete partnered school'
                ]);
            }
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Get partnered schools for dropdown
    function getPartneredSchoolsDropdown($json) {
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
    
    // ==================== SECTIONS ====================
    
    // Create section
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
            if (!empty($data['school_id'])) {
                $school_check_sql = "SELECT section_name FROM sections WHERE school_id = ?";
                $stmt = $conn->prepare($school_check_sql);
                $stmt->execute([$data['school_id']]);
                $existing_section = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($existing_section) {
                    $errors['school_id'] = 'This partnered school is already assigned to section: ' . $existing_section['section_name'];
                }
            }
            
            if (!empty($errors)) {
                return json_encode([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors
                ]);
            }
            
            // Insert new section
            $sql = "INSERT INTO sections (section_name, school_id) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $data['section_name'],
                !empty($data['school_id']) ? $data['school_id'] : null
            ]);
            
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
            $sql = "SELECT s.id, s.section_name, s.school_id,
                           ps.name as school_name
                    FROM sections s
                    LEFT JOIN partnered_schools ps ON s.school_id = ps.id
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
            if (!empty($data['school_id'])) {
                $school_check_sql = "SELECT section_name FROM sections WHERE school_id = ? AND id != ?";
                $stmt = $conn->prepare($school_check_sql);
                $stmt->execute([$data['school_id'], $data['id']]);
                $existing_section = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($existing_section) {
                    $errors['school_id'] = 'This partnered school is already assigned to section: ' . $existing_section['section_name'];
                }
            }
            
            if (!empty($errors)) {
                return json_encode([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors
                ]);
            }
            
            // Update section
            $sql = "UPDATE sections SET section_name = ?, school_id = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $data['section_name'],
                !empty($data['school_id']) ? $data['school_id'] : null,
                $data['id']
            ]);
            
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
    
    // ==================== EMAIL DOMAINS ====================
    
    // Read email domains
    function readEmailDomains($json) {
        include "connection.php";
        
        try {
            $sql = "SELECT * FROM allowed_email_domains ORDER BY domain_name";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $domains = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return json_encode([
                'success' => true,
                'data' => $domains
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Create email domain
    function createEmailDomain($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        
        try {
            // Validate required fields
            if (empty($data['domain_name'])) {
                return json_encode([
                    'success' => false,
                    'message' => 'Domain name is required'
                ]);
            }
            
            // Check if domain already exists
            $check_sql = "SELECT domain_name FROM allowed_email_domains WHERE domain_name = ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->execute([$data['domain_name']]);
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                return json_encode([
                    'success' => false,
                    'message' => 'Domain name already exists'
                ]);
            }
            
            // Insert email domain
            $sql = "INSERT INTO allowed_email_domains (domain_name, description) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $data['domain_name'],
                $data['description'] ?? null
            ]);
            
            return json_encode([
                'success' => true,
                'message' => 'Email domain created successfully'
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Update email domain
    function updateEmailDomain($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        
        if (empty($data['id'])) {
            return json_encode([
                'success' => false,
                'message' => 'Domain ID is required'
            ]);
        }
        
        try {
            // Validate required fields
            if (empty($data['domain_name'])) {
                return json_encode([
                    'success' => false,
                    'message' => 'Domain name is required'
                ]);
            }
            
            // Check if domain already exists (excluding current domain)
            $check_sql = "SELECT domain_name FROM allowed_email_domains WHERE domain_name = ? AND id != ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->execute([$data['domain_name'], $data['id']]);
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                return json_encode([
                    'success' => false,
                    'message' => 'Domain name already exists'
                ]);
            }
            
            // Update email domain
            $sql = "UPDATE allowed_email_domains SET domain_name = ?, description = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $data['domain_name'],
                $data['description'] ?? null,
                $data['id']
            ]);
            
            return json_encode([
                'success' => true,
                'message' => 'Email domain updated successfully'
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Delete email domain
    function deleteEmailDomain($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        
        if (empty($data['id'])) {
            return json_encode([
                'success' => false,
                'message' => 'Domain ID is required'
            ]);
        }
        
        try {
            // Delete email domain
            $sql = "DELETE FROM allowed_email_domains WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$data['id']]);
            
            return json_encode([
                'success' => true,
                'message' => 'Email domain deleted successfully'
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // ==================== PRACTICUM SUBJECTS ====================
    
    // Read all practicum subjects
    function readPracticumSubjects($json) {
        include "connection.php";
        
        try {
            $sql = "SELECT id, subject_name, total_hours_required, shift_hours_required, practicum_startDate 
                    FROM practicum_subjects 
                    ORDER BY subject_name";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return json_encode([
                'success' => true,
                'data' => $subjects
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Update practicum subject
    function updatePracticumSubject($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        
        if (empty($data['id'])) {
            return json_encode([
                'success' => false,
                'message' => 'Subject ID is required'
            ]);
        }
        
        try {
            // Validate required fields
            $required_fields = ['subject_name', 'total_hours_required', 'shift_hours_required', 'practicum_startDate'];
            $errors = [];
            
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
                }
            }
            
            if (!empty($errors)) {
                return json_encode([
                    'success' => false,
                    'message' => 'Validation errors occurred',
                    'errors' => $errors
                ]);
            }
            
            // Update practicum subject
            $sql = "UPDATE practicum_subjects 
                    SET subject_name = ?, total_hours_required = ?, shift_hours_required = ?, practicum_startDate = ?
                    WHERE id = ?";
            
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([
                $data['subject_name'],
                $data['total_hours_required'],
                $data['shift_hours_required'],
                $data['practicum_startDate'],
                $data['id']
            ]);
            
            if ($result) {
                return json_encode([
                    'success' => true,
                    'message' => 'Practicum subject updated successfully'
                ]);
            } else {
                return json_encode([
                    'success' => false,
                    'message' => 'Failed to update practicum subject'
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

$system = new System();

switch ($operation) {
    // Academic Sessions operations
    case 'read_academic_sessions':
        echo $system->readAcademicSessions($json);
        break;
        
    case 'read_active_session':
        echo $system->readActiveAcademicSession($json);
        break;
        
    case 'set_active_session':
        echo $system->setActiveAcademicSession($json);
        break;
    
    // Partnered Schools operations
    case 'create_partnered_school':
        echo $system->createPartneredSchool($json);
        break;
        
    case 'read_partnered_schools':
        echo $system->readPartneredSchools($json);
        break;
        
    case 'update_partnered_school':
        echo $system->updatePartneredSchool($json);
        break;
        
    case 'delete_partnered_school':
        echo $system->deletePartneredSchool($json);
        break;
        
    case 'get_partnered_schools_dropdown':
        echo $system->getPartneredSchoolsDropdown($json);
        break;
    
    // Sections operations
    case 'create_section':
        echo $system->createSection($json);
        break;
        
    case 'read_sections':
        echo $system->readSections($json);
        break;
        
    case 'update_section':
        echo $system->updateSection($json);
        break;
        
    case 'delete_section':
        echo $system->deleteSection($json);
        break;
    
    // Email Domains operations
    case 'read_email_domains':
        echo $system->readEmailDomains($json);
        break;
        
    case 'create_email_domain':
        echo $system->createEmailDomain($json);
        break;
        
    case 'update_email_domain':
        echo $system->updateEmailDomain($json);
        break;
        
    case 'delete_email_domain':
        echo $system->deleteEmailDomain($json);
        break;

    // Practicum Subjects operations
    case 'read_practicum_subjects':
        echo $system->readPracticumSubjects($json);
        break;
        
    case 'update_practicum_subject':
        echo $system->updatePracticumSubject($json);
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
