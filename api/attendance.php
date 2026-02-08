<?php
include "headers.php";
date_default_timezone_set('Asia/Manila');

class Attendance {
    
    // Get section overview with attendance statistics
    function getSectionOverview($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $filters = $data['filters'] ?? [];
        $dateFilter = $filters['dateRange'] ?? 'today';
        $sectionFilter = $filters['section'] ?? 'all';
        $academicSessionFilter = $filters['academicSession'] ?? 'all';
        
        try {
            // Build date condition
            $dateCondition = $this->buildDateCondition($dateFilter, $filters);
            
            // Build section condition
            $sectionCondition = '';
            if ($sectionFilter !== 'all') {
                $sectionCondition = "AND s.id = " . intval($sectionFilter);
            }
            
            // Build academic session condition
            $academicSessionCondition = '';
            if ($academicSessionFilter !== 'all') {
                $academicSessionCondition = "AND a.session_id = " . intval($academicSessionFilter);
            }

            // Build coordinator condition
            $coordinatorId = $filters['coordinator_id'] ?? null;
            $coordinatorCondition = '';
            if ($coordinatorId) {
                $coordinatorCondition = " AND (s.id IN (SELECT section_id FROM users WHERE school_id = '$coordinatorId') 
                                           OR s.id IN (SELECT section_id FROM section_schools WHERE school_id IN (SELECT school_id FROM assignments WHERE assigner_id = '$coordinatorId' AND isCurrent = 1)))";
            }
            
            // Query to get sections with actual attendance statistics and school information
            $sql = "
                SELECT 
                    s.id as section_id,
                    s.section_name,
                    GROUP_CONCAT(DISTINCT ps.name SEPARATOR ', ') as school_name,
                    COUNT(DISTINCT u.school_id) as total_students,
                    COUNT(DISTINCT CASE WHEN a.attendance_timeIn IS NOT NULL THEN u.school_id END) as present_today,
                    COUNT(DISTINCT CASE WHEN a.attendance_timeIn IS NULL THEN u.school_id END) as absent_today,
                    COUNT(DISTINCT CASE WHEN a.attendance_timeIn IS NOT NULL 
                        AND TIME(a.attendance_timeIn) > '08:00:00' THEN u.school_id END) as late_today,
                    CASE 
                        WHEN COUNT(DISTINCT u.school_id) = 0 THEN 0
                        ELSE ROUND(
                            (COUNT(DISTINCT CASE WHEN a.attendance_timeIn IS NOT NULL THEN u.school_id END) * 100.0) / 
                            COUNT(DISTINCT u.school_id), 2
                        )
                    END as attendance_rate
                FROM sections s
                LEFT JOIN section_schools ss ON s.id = ss.section_id
                LEFT JOIN partnered_schools ps ON ss.school_id = ps.id
                LEFT JOIN users u ON s.id = u.section_id AND u.level_id = 4 AND u.isActive = 1
                LEFT JOIN attendance a ON u.school_id = a.student_id AND $dateCondition $academicSessionCondition
                WHERE 1=1
                $sectionCondition
                $coordinatorCondition
                GROUP BY s.id, s.section_name
                ORDER BY s.section_name
            ";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return json_encode([
                'success' => true,
                'sections' => $sections
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Get all academic sessions
    function getAcademicSessions($json) {
        include "connection.php";
        
        try {
            $sql = "
                SELECT 
                    acs.academic_session_id,
                    sy.school_year,
                    s.semester_name,
                    CONCAT(sy.school_year, ' - ', s.semester_name) as session_name,
                    acs.is_Active
                FROM academic_sessions acs
                LEFT JOIN school_years sy ON acs.school_year_id = sy.school_year_id
                LEFT JOIN semesters s ON acs.semester_id = s.semester_id
                ORDER BY sy.school_year DESC, s.semester_id
            ";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return json_encode([
                'success' => true,
                'sessions' => $sessions
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Get all sections
    function getSections($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $coordinatorId = $data['coordinator_id'] ?? null;
        
        try {
            $coordinatorCondition = '';
            if ($coordinatorId) {
                $coordinatorCondition = " WHERE (id IN (SELECT section_id FROM users WHERE school_id = '$coordinatorId') 
                                           OR id IN (SELECT section_id FROM section_schools WHERE school_id IN (SELECT school_id FROM assignments WHERE assigner_id = '$coordinatorId' AND isCurrent = 1)))";
            }

            $sql = "
                SELECT id as section_id, section_name 
                FROM sections 
                $coordinatorCondition
                ORDER BY section_name
            ";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return json_encode([
                'success' => true,
                'sections' => $sections
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Get student attendance for a specific section
    function getStudentAttendance($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $sectionId = intval($data['section_id']);
        $filters = $data['filters'] ?? [];
        
        try {
            // Build date condition for attendance filtering
            $dateFilter = $filters['dateRange'] ?? 'all';
            $dateCondition = $this->buildDateCondition($dateFilter, $filters);
            
            // Build academic session condition
            $academicSessionFilter = $filters['academicSession'] ?? 'all';
            $academicSessionCondition = '';
            if ($academicSessionFilter !== 'all') {
                $academicSessionCondition = "AND a.session_id = " . intval($academicSessionFilter);
            }
            
            // Query to get students with their attendance and hours data
            // Use subquery for section school names to avoid Cartesian product
            $sql = "
                SELECT 
                    u.school_id as student_id,
                    u.firstname,
                    u.lastname,
                    u.email,
                    360 as total_required_hours,
                    180 as public_required_hours,
                    180 as private_required_hours,
                    COALESCE(
                        SUM(CASE WHEN a.hours_rendered IS NOT NULL AND a.hours_rendered > 0 
                            AND ($dateCondition OR '$dateFilter' = 'all') $academicSessionCondition THEN a.hours_rendered ELSE 0 END), 
                        0
                    ) as total_rendered_hours,
                    COALESCE(
                        SUM(CASE WHEN ps.school_type = 'Public' AND a.hours_rendered IS NOT NULL AND a.hours_rendered > 0 
                            AND ($dateCondition OR '$dateFilter' = 'all') $academicSessionCondition THEN a.hours_rendered ELSE 0 END), 
                        0
                    ) as public_rendered_hours,
                    COALESCE(
                        SUM(CASE WHEN ps.school_type = 'Private' AND a.hours_rendered IS NOT NULL AND a.hours_rendered > 0 
                            AND ($dateCondition OR '$dateFilter' = 'all') $academicSessionCondition THEN a.hours_rendered ELSE 0 END), 
                        0
                    ) as private_rendered_hours,
                    MAX(CASE WHEN a.attendance_timeOut IS NULL AND a.attendance_date = CURDATE() THEN a.attendance_timeIn END) as current_time_in,
                    MAX(CASE WHEN a.attendance_timeOut IS NULL AND a.attendance_date = CURDATE() THEN a.attendance_date END) as ongoing_date,
                    MAX(CASE WHEN a.attendance_timeOut IS NULL AND a.attendance_date = CURDATE() THEN ps.school_type END) as ongoing_school_type,
                    (
                        SELECT GROUP_CONCAT(ps_sub.name SEPARATOR ', ')
                        FROM section_schools ss_sub
                        JOIN partnered_schools ps_sub ON ss_sub.school_id = ps_sub.id
                        WHERE ss_sub.section_id = u.section_id
                    ) as section_school_names
                FROM users u
                LEFT JOIN attendance a ON u.school_id = a.student_id
                LEFT JOIN partnered_schools ps ON a.school_id = ps.id
                WHERE u.section_id = :section_id AND u.level_id = 4 AND u.isActive = 1
                GROUP BY u.school_id, u.firstname, u.lastname, u.email, u.section_id
                ORDER BY u.lastname, u.firstname
            ";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':section_id', $sectionId, PDO::PARAM_INT);
            $stmt->execute();
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate remaining hours for each student
            foreach ($students as &$student) {
                $student['remaining_public_hours'] = max(0, 180 - $student['public_rendered_hours']);
                $student['remaining_private_hours'] = max(0, 180 - $student['private_rendered_hours']);
                $student['remaining_total_hours'] = max(0, 360 - $student['total_rendered_hours']);
            }
            
            return json_encode([
                'success' => true,
                'students' => $students
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Get detailed attendance history for a student
    function getStudentAttendanceHistory($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $studentId = $data['student_id']; // Keep as string (e.g. STU-2026-XXXX)
        $filters = $data['filters'] ?? [];
        
        try {
            // Build date condition
            $dateFilter = $filters['dateRange'] ?? 'today';
            $dateCondition = $this->buildDateCondition($dateFilter, $filters);
            
            // Build academic session condition
            $academicSessionFilter = $filters['academicSession'] ?? 'all';
            $academicSessionCondition = '';
            if ($academicSessionFilter !== 'all') {
                $academicSessionCondition = "AND a.session_id = " . intval($academicSessionFilter);
            }
            
            $sql = "
                SELECT 
                    a.id as attendance_id,
                    a.attendance_date,
                    a.attendance_timeIn as check_in_time,
                    a.attendance_timeOut as check_out_time,
                    CASE 
                        WHEN a.attendance_timeIn IS NOT NULL THEN 'Present'
                        ELSE 'Absent'
                    END as status,
                    a.hours_rendered,
                    '' as remarks,
                    COALESCE(ps.name, 'Unknown School') as school_name,
                    ps.school_type
                FROM attendance a
                LEFT JOIN users u ON a.student_id = u.school_id
                LEFT JOIN partnered_schools ps ON a.school_id = ps.id
                WHERE a.student_id = :student_id AND $dateCondition $academicSessionCondition
                ORDER BY a.attendance_date DESC, a.attendance_timeIn DESC
            ";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':student_id', $studentId, PDO::PARAM_STR);
            $stmt->execute();
            $attendanceHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return json_encode([
                'success' => true,
                'attendance_history' => $attendanceHistory
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Helper function to build date condition
    private function buildDateCondition($dateFilter, $filters) {
        $today = date('Y-m-d');
        
        switch ($dateFilter) {
            case 'today':
                return "DATE(a.attendance_date) = '$today'";
                
            case 'week':
                $weekStart = date('Y-m-d', strtotime('monday this week'));
                $weekEnd = date('Y-m-d', strtotime('sunday this week'));
                return "DATE(a.attendance_date) BETWEEN '$weekStart' AND '$weekEnd'";
                
            case 'month':
                $monthStart = date('Y-m-01');
                $monthEnd = date('Y-m-t');
                return "DATE(a.attendance_date) BETWEEN '$monthStart' AND '$monthEnd'";
                
            case 'custom':
                $fromDate = $filters['fromDate'] ?? $today;
                $toDate = $filters['toDate'] ?? $today;
                return "DATE(a.attendance_date) BETWEEN '$fromDate' AND '$toDate'";
                
            case 'all':
            default:
                return "1=1"; // No date filtering
        }
    }
}

$operation = isset($_POST["operation"]) ? $_POST["operation"] : "0";
$json = isset($_POST["json"]) ? $_POST["json"] : "0";

$attendance = new Attendance();

switch ($operation) {
    case 'get_section_overview':
        echo $attendance->getSectionOverview($json);
        break;
        
    case 'get_academic_sessions':
        echo $attendance->getAcademicSessions($json);
        break;
        
    case 'get_sections':
        echo $attendance->getSections($json);
        break;
        
    case 'get_student_attendance':
        echo $attendance->getStudentAttendance($json);
        break;
        
    case 'get_student_attendance_history':
        echo $attendance->getStudentAttendanceHistory($json);
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
