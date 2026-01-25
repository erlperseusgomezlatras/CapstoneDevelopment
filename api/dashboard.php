<?php
include "headers.php";
date_default_timezone_set('Asia/Manila');

class Dashboard {
    
    // Get current academic session info including day calculation
    function getCurrentSessionInfo($json) {
        include "connection.php";
        
        try {
            // Get active academic session
            $session_sql = "SELECT asession.academic_session_id, asession.school_year_id, asession.semester_id, 
                                   asession.updated_at, sy.school_year, s.semester_name
                            FROM academic_sessions asession
                            INNER JOIN school_years sy ON asession.school_year_id = sy.school_year_id
                            INNER JOIN semesters s ON asession.semester_id = s.semester_id
                            WHERE asession.is_Active = 1
                            LIMIT 1";
            
            $stmt = $conn->prepare($session_sql);
            $stmt->execute();
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$session) {
                return json_encode([
                    'success' => false,
                    'message' => 'No active academic session found'
                ]);
            }
            
            // Calculate day number (days since session started)
            $session_start = new DateTime($session['updated_at']);
            $current_date = new DateTime();
            $day_number = $current_date->diff($session_start)->days + 1; // +1 to make it 1-based
            
            return json_encode([
                'success' => true,
                'data' => [
                    'session_id' => $session['academic_session_id'],
                    'school_year' => $session['school_year'],
                    'semester' => $session['semester_name'],
                    'day_number' => $day_number,
                    'session_start_date' => $session['updated_at']
                ]
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Get recent student registration approval requests
    function getRecentStudentRegistrations($json) {
        include "connection.php";
        
        try {
            // Get pending student registrations (last 10)
            $sql = "SELECT school_id, firstname, lastname, email, created_at 
                    FROM users 
                    WHERE level_id = 4 AND isApproved = 0 
                    ORDER BY created_at DESC 
                    LIMIT 10";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return json_encode([
                'success' => true,
                'data' => $registrations
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Get latest attendance logs (realtime, dynamic based on user level)
    function getLatestAttendanceLogs($json) {
        include "connection.php";
        
        // Get user data from session or cookie to determine user level and ID
        $user_id = $_SESSION['user_id'] ?? null;
        $user_role = $_SESSION['user_role'] ?? null;
        
        if (!$user_id && isset($_COOKIE['userData'])) {
            $userData = json_decode($_COOKIE['userData'], true);
            $user_id = $userData['school_id'] ?? null;
            $user_role = $userData['level'] ?? null;
        }
        
        try {
            // Dynamic query based on user level
            if ($user_role === 'Coordinator') {
                // Get coordinator's section_id from users table
                $section_sql = "SELECT section_id FROM users WHERE school_id = ? AND level_id = 3";
                $section_stmt = $conn->prepare($section_sql);
                $section_stmt->execute([$user_id]);
                $coordinator = $section_stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$coordinator || empty($coordinator['section_id'])) {
                    return json_encode([
                        'success' => true,
                        'data' => [],
                        'user_level' => $user_role,
                        'message' => 'No section assigned to coordinator'
                    ]);
                }
                
                $section_id = $coordinator['section_id'];
                
                // Get latest attendance logs for coordinator's section only
                $sql = "SELECT a.student_id, a.attendance_date, a.attendance_timeIn, a.attendance_timeOut,
                               u.firstname, u.lastname, s.section_name
                        FROM attendance a
                        INNER JOIN users u ON a.student_id = u.school_id
                        LEFT JOIN sections s ON u.section_id = s.id
                        WHERE a.attendance_date = CURDATE() AND u.section_id = ?
                        ORDER BY a.attendance_timeIn DESC, a.attendance_timeOut DESC
                        LIMIT 20";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([$section_id]);
            } else {
                // Get latest attendance logs for all sections (Head Teacher)
                $sql = "SELECT a.student_id, a.attendance_date, a.attendance_timeIn, a.attendance_timeOut,
                               u.firstname, u.lastname, s.section_name
                        FROM attendance a
                        INNER JOIN users u ON a.student_id = u.school_id
                        LEFT JOIN sections s ON u.section_id = s.id
                        WHERE a.attendance_date = CURDATE()
                        ORDER BY a.attendance_timeIn DESC, a.attendance_timeOut DESC
                        LIMIT 20";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute();
            }
            
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return json_encode([
                'success' => true,
                'data' => $logs,
                'user_level' => $user_role
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Get section attendance overview (dynamic based on user level)
    function getSectionAttendanceOverview($json) {
        include "connection.php";
        
        // Get user data from session or cookie to determine user level and ID
        $user_id = $_SESSION['user_id'] ?? null;
        $user_role = $_SESSION['user_role'] ?? null;
        
        if (!$user_id && isset($_COOKIE['userData'])) {
            $userData = json_decode($_COOKIE['userData'], true);
            $user_id = $userData['school_id'] ?? null;
            $user_role = $userData['level'] ?? null;
        }
        
        try {
            // Get active academic session
            $session_sql = "SELECT academic_session_id FROM academic_sessions WHERE is_Active = 1 LIMIT 1";
            $session_stmt = $conn->prepare($session_sql);
            $session_stmt->execute();
            $active_session = $session_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$active_session) {
                return json_encode([
                    'success' => false,
                    'message' => 'No active academic session found'
                ]);
            }
            
            $session_id = $active_session['academic_session_id'];
            
            // Dynamic query based on user level
            if ($user_role === 'Coordinator') {
                // Get coordinator's section_id from users table
                $section_sql = "SELECT section_id FROM users WHERE school_id = ? AND level_id = 3";
                $section_stmt = $conn->prepare($section_sql);
                $section_stmt->execute([$user_id]);
                $coordinator = $section_stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$coordinator || empty($coordinator['section_id'])) {
                    return json_encode([
                        'success' => true,
                        'data' => [],
                        'user_level' => $user_role,
                        'message' => 'No section assigned to coordinator'
                    ]);
                }
                
                $section_id = $coordinator['section_id'];
                
                // Get only the coordinator's assigned section
                $sql = "SELECT s.id as section_id, s.section_name,
                               COUNT(DISTINCT u.school_id) as total_students,
                               COUNT(DISTINCT CASE WHEN a.student_id IS NOT NULL THEN u.school_id END) as present_students
                        FROM sections s
                        LEFT JOIN users u ON s.id = u.section_id AND u.level_id = 4 AND u.isApproved = 1
                        LEFT JOIN attendance a ON u.school_id = a.student_id 
                                              AND a.attendance_date = CURDATE() 
                                              AND a.session_id = ?
                        WHERE s.id = ?
                        GROUP BY s.id, s.section_name
                        ORDER BY s.section_name";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([$session_id, $section_id]);
            } else {
                // Get all sections for Head Teacher
                $sql = "SELECT s.id as section_id, s.section_name,
                               COUNT(DISTINCT u.school_id) as total_students,
                               COUNT(DISTINCT CASE WHEN a.student_id IS NOT NULL THEN u.school_id END) as present_students
                        FROM sections s
                        LEFT JOIN users u ON s.id = u.section_id AND u.level_id = 4 AND u.isApproved = 1
                        LEFT JOIN attendance a ON u.school_id = a.student_id 
                                              AND a.attendance_date = CURDATE() 
                                              AND a.session_id = ?
                        WHERE s.id IS NOT NULL
                        GROUP BY s.id, s.section_name
                        ORDER BY s.section_name";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([$session_id]);
            }
            
            $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return json_encode([
                'success' => true,
                'data' => $sections,
                'user_level' => $user_role
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Get detailed attendance for a specific section
    function getSectionAttendanceDetails($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        
        if (!isset($data['section_id'])) {
            return json_encode([
                'success' => false,
                'message' => 'Section ID is required'
            ]);
        }
        
        try {
            // Get active academic session
            $session_sql = "SELECT academic_session_id FROM academic_sessions WHERE is_Active = 1 LIMIT 1";
            $session_stmt = $conn->prepare($session_sql);
            $session_stmt->execute();
            $active_session = $session_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$active_session) {
                return json_encode([
                    'success' => false,
                    'message' => 'No active academic session found'
                ]);
            }
            
            $session_id = $active_session['academic_session_id'];
            $section_id = $data['section_id'];
            
            // Get all students in the section with their attendance status for today
            $sql = "SELECT u.school_id, u.firstname, u.lastname, u.email,
                           a.attendance_timeIn, a.attendance_timeOut,
                           CASE WHEN a.student_id IS NOT NULL THEN 'Present' ELSE 'Absent' END as status
                    FROM users u
                    LEFT JOIN attendance a ON u.school_id = a.student_id 
                                          AND a.attendance_date = CURDATE() 
                                          AND a.session_id = ?
                    WHERE u.section_id = ? AND u.level_id = 4 AND u.isApproved = 1
                    ORDER BY u.lastname, u.firstname";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$session_id, $section_id]);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get section name
            $section_sql = "SELECT section_name FROM sections WHERE id = ?";
            $section_stmt = $conn->prepare($section_sql);
            $section_stmt->execute([$section_id]);
            $section_info = $section_stmt->fetch(PDO::FETCH_ASSOC);
            
            return json_encode([
                'success' => true,
                'data' => [
                    'section_name' => $section_info['section_name'] ?? 'Unknown Section',
                    'students' => $students
                ]
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

$dashboard = new Dashboard();

switch ($operation) {
    case 'get_current_session_info':
        echo $dashboard->getCurrentSessionInfo($json);
        break;
        
    case 'get_recent_registrations':
        echo $dashboard->getRecentStudentRegistrations($json);
        break;
        
    case 'get_latest_attendance_logs':
        echo $dashboard->getLatestAttendanceLogs($json);
        break;
        
    case 'get_section_attendance_overview':
        echo $dashboard->getSectionAttendanceOverview($json);
        break;
        
    case 'get_section_attendance_details':
        echo $dashboard->getSectionAttendanceDetails($json);
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
