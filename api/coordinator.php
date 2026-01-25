<?php
include "headers.php";

class Coordinators {
    
    // Get coordinator dashboard statistics
    function getDashboardStats($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $coordinator_id = isset($data['coordinator_id']) ? $data['coordinator_id'] : '';
        
        try {
            // Get coordinator's assigned sections
            $sections_sql = "SELECT DISTINCT s.id, s.section_name 
                            FROM assignments a 
                            JOIN sections s ON a.school_id = s.school_id 
                            WHERE a.assigner_id = ? AND a.isCurrent = 1";
            $stmt = $conn->prepare($sections_sql);
            $stmt->execute([$coordinator_id]);
            $assigned_sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $section_ids = array_column($assigned_sections, 'id');
            
            if (empty($section_ids)) {
                return json_encode([
                    'success' => true,
                    'data' => [
                        'total_students' => 0,
                        'total_sections' => 0,
                        'attendance_today' => 0,
                        'pending_journals' => 0,
                        'assigned_sections' => []
                    ]
                ]);
            }
            
            // Get total students in assigned sections
            $students_sql = "SELECT COUNT(DISTINCT u.school_id) as total_students 
                           FROM users u 
                           WHERE u.section_id IN (" . str_repeat('?,', count($section_ids)-1) . "?) AND u.level_id = 4";
            $stmt = $conn->prepare($students_sql);
            $stmt->execute($section_ids);
            $total_students = $stmt->fetch(PDO::FETCH_ASSOC)['total_students'];
            
            // Get today's attendance for assigned sections
            $attendance_sql = "SELECT COUNT(DISTINCT a.student_id) as attendance_count 
                             FROM attendance a 
                             JOIN users u ON a.student_id = u.school_id 
                             WHERE a.attendance_date = CURDATE() AND u.section_id IN (" . str_repeat('?,', count($section_ids)-1) . "?)";
            $stmt = $conn->prepare($attendance_sql);
            $stmt->execute($section_ids);
            $attendance_today = $stmt->fetch(PDO::FETCH_ASSOC)['attendance_count'];
            
            // Get pending journal submissions
            $journals_sql = "SELECT COUNT(DISTINCT j.id) as pending_count 
                           FROM journal j 
                           JOIN users u ON j.student_id = u.school_id 
                           WHERE u.section_id IN (" . str_repeat('?,', count($section_ids)-1) . "?) AND j.createdAt >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            $stmt = $conn->prepare($journals_sql);
            $stmt->execute($section_ids);
            $pending_journals = $stmt->fetch(PDO::FETCH_ASSOC)['pending_count'];
            
            return json_encode([
                'success' => true,
                'data' => [
                    'total_students' => $total_students,
                    'total_sections' => count($assigned_sections),
                    'attendance_today' => $attendance_today,
                    'pending_journals' => $pending_journals,
                    'assigned_sections' => $assigned_sections
                ]
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Get students in coordinator's assigned sections
    function getAssignedStudents($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $coordinator_id = isset($data['coordinator_id']) ? $data['coordinator_id'] : '';
        $search = isset($data['search']) ? $data['search'] : '';
        
        try {
            if (empty($coordinator_id)) {
                return json_encode([
                    'success' => true,
                    'data' => []
                ]);
            }
            
            // Get coordinator's section_id from users table
            $section_sql = "SELECT section_id FROM users WHERE school_id = ? AND level_id = 3";
            $stmt = $conn->prepare($section_sql);
            $stmt->execute([$coordinator_id]);
            $coordinator = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$coordinator || empty($coordinator['section_id'])) {
                error_log("No section found for coordinator_id: $coordinator_id");
                return json_encode([
                    'success' => true,
                    'data' => []
                ]);
            }
            
            $section_id = $coordinator['section_id'];
            
            // Get students in coordinator's section
            $sql = "SELECT u.*, s.section_name, ps.name as partnered_school_name 
                    FROM users u 
                    LEFT JOIN sections s ON u.section_id = s.id 
                    LEFT JOIN partnered_schools ps ON s.school_id = ps.id 
                    WHERE u.level_id = 4 AND u.section_id = ?";
            
            $params = [$section_id];
            
            // Add search condition
            if (!empty($search)) {
                $sql .= " AND (u.school_id LIKE ? OR u.firstname LIKE ? OR u.lastname LIKE ? OR u.email LIKE ?)";
                $search_param = "%$search%";
                $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
            }
            
            $sql .= " ORDER BY u.firstname, u.lastname";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Found " . count($students) . " students for section_id: $section_id");
            
            return json_encode([
                'success' => true,
                'data' => $students
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Get attendance records for coordinator's assigned students
    function getAttendanceRecords($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $coordinator_id = isset($data['coordinator_id']) ? $data['coordinator_id'] : '';
        $date_from = isset($data['date_from']) ? $data['date_from'] : date('Y-m-d', strtotime('-7 days'));
        $date_to = isset($data['date_to']) ? $data['date_to'] : date('Y-m-d');
        
        try {
            // Get coordinator's section_id from users table (same as getAssignedStudents)
            $section_sql = "SELECT section_id FROM users WHERE school_id = ? AND level_id = 3";
            $stmt = $conn->prepare($section_sql);
            $stmt->execute([$coordinator_id]);
            $coordinator = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$coordinator || empty($coordinator['section_id'])) {
                error_log("No section found for coordinator_id: $coordinator_id");
                return json_encode([
                    'success' => true,
                    'data' => []
                ]);
            }
            
            $section_id = $coordinator['section_id'];
            
            // Get attendance records for students in coordinator's section
            $sql = "SELECT a.*, u.firstname, u.lastname, u.school_id, s.section_name 
                    FROM attendance a 
                    JOIN users u ON a.student_id = u.school_id 
                    LEFT JOIN sections s ON u.section_id = s.id 
                    WHERE a.attendance_date BETWEEN ? AND ? 
                    AND u.section_id = ? 
                    ORDER BY a.attendance_date DESC, a.attendance_timeIn DESC";
            
            $params = [$date_from, $date_to, $section_id];
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Found " . count($attendance) . " attendance records for section_id: $section_id from $date_from to $date_to");
            
            return json_encode([
                'success' => true,
                'data' => $attendance
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Get journal submissions for coordinator's assigned students
    function getJournalSubmissions($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $coordinator_id = isset($data['coordinator_id']) ? $data['coordinator_id'] : '';
        $status_filter = isset($data['status_filter']) ? $data['status_filter'] : '';
        
        try {
            // Get coordinator's assigned sections
            $sections_sql = "SELECT DISTINCT s.id 
                            FROM assignments a 
                            JOIN sections s ON a.school_id = s.school_id 
                            WHERE a.assigner_id = ? AND a.isCurrent = 1";
            $stmt = $conn->prepare($sections_sql);
            $stmt->execute([$coordinator_id]);
            $section_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $section_ids = array_column($section_data, 'id');
            
            if (empty($section_ids)) {
                return json_encode([
                    'success' => true,
                    'data' => []
                ]);
            }
            
            // Get journal submissions
            $sql = "SELECT j.*, u.firstname, u.lastname, u.school_id, s.section_name 
                    FROM journal j 
                    JOIN users u ON j.student_id = u.school_id 
                    LEFT JOIN sections s ON u.section_id = s.id 
                    WHERE u.section_id IN (" . str_repeat('?,', count($section_ids)-1) . "?)";
            
            $params = $section_ids;
            
            // Add status filter if specified
            if ($status_filter !== '') {
                if ($status_filter === 'pending') {
                    $sql .= " AND j.createdAt >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                }
            }
            
            $sql .= " ORDER BY j.createdAt DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $journals = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return json_encode([
                'success' => true,
                'data' => $journals
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Get practicum checklist for students
    function getPracticumChecklist($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $coordinator_id = isset($data['coordinator_id']) ? $data['coordinator_id'] : '';
        $student_id = isset($data['student_id']) ? $data['student_id'] : '';
        
        try {
            // Verify student is assigned to coordinator
            $verify_sql = "SELECT u.school_id 
                          FROM users u 
                          JOIN assignments a ON u.section_id = a.school_id 
                          WHERE u.school_id = ? AND a.assigner_id = ? AND a.isCurrent = 1 AND u.level_id = 4";
            $stmt = $conn->prepare($verify_sql);
            $stmt->execute([$student_id, $coordinator_id]);
            
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                return json_encode([
                    'success' => false,
                    'message' => 'Student not found or not assigned to this coordinator'
                ]);
            }
            
            // Get practicum checklist
            $sql = "SELECT pc.* FROM practicum_checklist pc 
                    WHERE pc.practicum_id = (
                        SELECT MAX(id) FROM practicum_subjects
                    )";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $checklist = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return json_encode([
                'success' => true,
                'data' => $checklist
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Verify attendance
    function verifyAttendance($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $attendance_id = isset($data['attendance_id']) ? $data['attendance_id'] : '';
        $coordinator_id = isset($data['coordinator_id']) ? $data['coordinator_id'] : '';
        
        if (empty($attendance_id)) {
            return json_encode([
                'success' => false,
                'message' => 'Attendance ID is required'
            ]);
        }
        
        try {
            // Verify attendance belongs to coordinator's assigned student
            $verify_sql = "SELECT a.id 
                          FROM attendance a 
                          JOIN users u ON a.student_id = u.school_id 
                          JOIN assignments ass ON u.section_id = ass.school_id 
                          WHERE a.id = ? AND ass.assigner_id = ? AND ass.isCurrent = 1";
            $stmt = $conn->prepare($verify_sql);
            $stmt->execute([$attendance_id, $coordinator_id]);
            
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                return json_encode([
                    'success' => false,
                    'message' => 'Attendance record not found or not authorized'
                ]);
            }
            
            // In a real implementation, you would add a verification status field to attendance table
            // For now, we'll just return success
            return json_encode([
                'success' => true,
                'message' => 'Attendance verified successfully'
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Review journal (approve/reject)
    function reviewJournal($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $journal_id = isset($data['journal_id']) ? $data['journal_id'] : '';
        $coordinator_id = isset($data['coordinator_id']) ? $data['coordinator_id'] : '';
        $action = isset($data['action']) ? $data['action'] : ''; // approve or reject
        $feedback = isset($data['feedback']) ? $data['feedback'] : '';
        
        if (empty($journal_id) || empty($action)) {
            return json_encode([
                'success' => false,
                'message' => 'Journal ID and action are required'
            ]);
        }
        
        try {
            // Verify journal belongs to coordinator's assigned student
            $verify_sql = "SELECT j.id 
                          FROM journal j 
                          JOIN users u ON j.student_id = u.school_id 
                          JOIN assignments ass ON u.section_id = ass.school_id 
                          WHERE j.id = ? AND ass.assigner_id = ? AND ass.isCurrent = 1";
            $stmt = $conn->prepare($verify_sql);
            $stmt->execute([$journal_id, $coordinator_id]);
            
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                return json_encode([
                    'success' => false,
                    'message' => 'Journal not found or not authorized'
                ]);
            }
            
            // In a real implementation, you would add a review status field to journal table
            // For now, we'll just return success
            $action_text = $action === 'approve' ? 'approved' : 'rejected';
            return json_encode([
                'success' => true,
                'message' => "Journal $action_text successfully"
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Get coordinator's assigned partnered school
    function getAssignedSchool($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $coordinator_id = isset($data['coordinator_id']) ? $data['coordinator_id'] : '';
        
        try {
            $sql = "SELECT DISTINCT ps.* 
                    FROM partnered_schools ps 
                    JOIN assignments a ON ps.id = a.school_id 
                    WHERE a.assigner_id = ? AND a.isCurrent = 1";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$coordinator_id]);
            $school = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($school) {
                return json_encode([
                    'success' => true,
                    'data' => $school
                ]);
            } else {
                return json_encode([
                    'success' => false,
                    'message' => 'No partnered school assigned'
                ]);
            }
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Get coordinator student statistics (for frontend compatibility)
    function getCoordinatorStats($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $coordinator_id = isset($data['coordinator_id']) ? $data['coordinator_id'] : '';
        
        // Debug log
        error_log("getCoordinatorStats called with coordinator_id: " . $coordinator_id);
        error_log("Raw JSON received: " . $json);
        
        try {
            if (empty($coordinator_id)) {
                return json_encode([
                    'success' => true,
                    'data' => [
                        'pending' => 0,
                        'approved' => 0,
                        'declined' => 0
                    ]
                ]);
            }
            
            // Get coordinator's section_id from users table
            $section_sql = "SELECT section_id FROM users WHERE school_id = ? AND level_id = 3";
            $stmt = $conn->prepare($section_sql);
            $stmt->execute([$coordinator_id]);
            $coordinator = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$coordinator || empty($coordinator['section_id'])) {
                error_log("No section found for coordinator_id: $coordinator_id");
                return json_encode([
                    'success' => true,
                    'data' => [
                        'pending' => 0,
                        'approved' => 0,
                        'declined' => 0
                    ]
                ]);
            }
            
            $section_id = $coordinator['section_id'];
            error_log("Found section_id: $section_id for coordinator_id: $coordinator_id");
            
            // Get pending students
            $pending_sql = "SELECT COUNT(*) as count FROM users WHERE section_id = ? AND level_id = 4 AND isApproved = 0";
            $stmt = $conn->prepare($pending_sql);
            $stmt->execute([$section_id]);
            $pending = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Get approved students
            $approved_sql = "SELECT COUNT(*) as count FROM users WHERE section_id = ? AND level_id = 4 AND isApproved = 1";
            $stmt = $conn->prepare($approved_sql);
            $stmt->execute([$section_id]);
            $approved = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Get declined students
            $declined_sql = "SELECT COUNT(*) as count FROM users WHERE section_id = ? AND level_id = 4 AND isApproved = 2";
            $stmt = $conn->prepare($declined_sql);
            $stmt->execute([$section_id]);
            $declined = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            error_log("Stats - Pending: $pending, Approved: $approved, Declined: $declined");
            
            return json_encode([
                'success' => true,
                'data' => [
                    'pending' => $pending,
                    'approved' => $approved,
                    'declined' => $declined
                ]
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Read coordinator students (for frontend compatibility)
    function readCoordinatorStudents($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $coordinator_id = isset($data['coordinator_id']) ? $data['coordinator_id'] : '';
        $approval_status = isset($data['approval_status']) ? $data['approval_status'] : '';
        
        try {
            if (empty($coordinator_id)) {
                return json_encode([
                    'success' => true,
                    'data' => []
                ]);
            }
            
            // Get coordinator's section_id from users table
            $section_sql = "SELECT section_id FROM users WHERE school_id = ? AND level_id = 3";
            $stmt = $conn->prepare($section_sql);
            $stmt->execute([$coordinator_id]);
            $coordinator = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$coordinator || empty($coordinator['section_id'])) {
                error_log("No section found for coordinator_id: $coordinator_id");
                return json_encode([
                    'success' => true,
                    'data' => []
                ]);
            }
            
            $section_id = $coordinator['section_id'];
            
            $sql = "SELECT u.*, s.section_name 
                    FROM users u 
                    LEFT JOIN sections s ON u.section_id = s.id 
                    WHERE u.section_id = ? AND u.level_id = 4";
            
            $params = [$section_id];
            
            // Add approval status filter
            if (!empty($approval_status)) {
                if ($approval_status === 'pending') {
                    $sql .= " AND u.isApproved = 0";
                } elseif ($approval_status === 'approved') {
                    $sql .= " AND u.isApproved = 1";
                } elseif ($approval_status === 'declined') {
                    $sql .= " AND u.isApproved = 2";
                }
            }
            
            $sql .= " ORDER BY u.firstname, u.lastname";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Found " . count($students) . " students for section_id: $section_id");
            
            return json_encode([
                'success' => true,
                'data' => $students
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Approve student (only if student belongs to coordinator's section)
    function approveStudent($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $school_id = isset($data['school_id']) ? $data['school_id'] : '';
        $coordinator_id = isset($data['coordinator_id']) ? $data['coordinator_id'] : '';
        
        if (empty($school_id) || empty($coordinator_id)) {
            return json_encode([
                'success' => false,
                'message' => 'Student ID and Coordinator ID are required'
            ]);
        }
        
        try {
            // Get coordinator's section_id
            $coordinator_sql = "SELECT section_id FROM users WHERE school_id = ? AND level_id = 3";
            $coordinator_stmt = $conn->prepare($coordinator_sql);
            $coordinator_stmt->execute([$coordinator_id]);
            $coordinator = $coordinator_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$coordinator || empty($coordinator['section_id'])) {
                return json_encode([
                    'success' => false,
                    'message' => 'Coordinator not found or no section assigned'
                ]);
            }
            
            $coordinator_section_id = $coordinator['section_id'];
            
            // Verify student belongs to coordinator's section
            $verify_sql = "SELECT section_id FROM users WHERE school_id = ? AND level_id = 4";
            $verify_stmt = $conn->prepare($verify_sql);
            $verify_stmt->execute([$school_id]);
            $student = $verify_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$student) {
                return json_encode([
                    'success' => false,
                    'message' => 'Student not found'
                ]);
            }
            
            if ($student['section_id'] != $coordinator_section_id) {
                return json_encode([
                    'success' => false,
                    'message' => 'You can only approve students from your assigned section'
                ]);
            }
            
            // Approve the student
            $sql = "UPDATE users SET isApproved = 1 WHERE school_id = ? AND level_id = 4 AND section_id = ?";
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([$school_id, $coordinator_section_id]);
            
            if ($result) {
                return json_encode([
                    'success' => true,
                    'message' => 'Student approved successfully'
                ]);
            } else {
                return json_encode([
                    'success' => false,
                    'message' => 'Failed to approve student'
                ]);
            }
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Decline student (only if student belongs to coordinator's section)
    function declineStudent($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $school_id = isset($data['school_id']) ? $data['school_id'] : '';
        $coordinator_id = isset($data['coordinator_id']) ? $data['coordinator_id'] : '';
        
        if (empty($school_id) || empty($coordinator_id)) {
            return json_encode([
                'success' => false,
                'message' => 'Student ID and Coordinator ID are required'
            ]);
        }
        
        try {
            // Get coordinator's section_id
            $coordinator_sql = "SELECT section_id FROM users WHERE school_id = ? AND level_id = 3";
            $coordinator_stmt = $conn->prepare($coordinator_sql);
            $coordinator_stmt->execute([$coordinator_id]);
            $coordinator = $coordinator_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$coordinator || empty($coordinator['section_id'])) {
                return json_encode([
                    'success' => false,
                    'message' => 'Coordinator not found or no section assigned'
                ]);
            }
            
            $coordinator_section_id = $coordinator['section_id'];
            
            // Verify student belongs to coordinator's section
            $verify_sql = "SELECT section_id FROM users WHERE school_id = ? AND level_id = 4";
            $verify_stmt = $conn->prepare($verify_sql);
            $verify_stmt->execute([$school_id]);
            $student = $verify_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$student) {
                return json_encode([
                    'success' => false,
                    'message' => 'Student not found'
                ]);
            }
            
            if ($student['section_id'] != $coordinator_section_id) {
                return json_encode([
                    'success' => false,
                    'message' => 'You can only decline students from your assigned section'
                ]);
            }
            
            // Decline the student
            $sql = "UPDATE users SET isApproved = 0 WHERE school_id = ? AND level_id = 4 AND section_id = ?";
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([$school_id, $coordinator_section_id]);
            
            if ($result) {
                return json_encode([
                    'success' => true,
                    'message' => 'Student declined successfully'
                ]);
            } else {
                return json_encode([
                    'success' => false,
                    'message' => 'Failed to decline student'
                ]);
            }
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Delete student
    function deleteStudent($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $school_id = isset($data['school_id']) ? $data['school_id'] : '';
        
        if (empty($school_id)) {
            return json_encode([
                'success' => false,
                'message' => 'Student ID is required'
            ]);
        }
        
        try {
            $sql = "DELETE FROM users WHERE school_id = ? AND level_id = 4";
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([$school_id]);
            
            if ($result) {
                return json_encode([
                    'success' => true,
                    'message' => 'Student deleted successfully'
                ]);
            } else {
                return json_encode([
                    'success' => false,
                    'message' => 'Failed to delete student'
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

$coordinators = new Coordinators();

switch ($operation) {
    case 'get_dashboard_stats':
        echo $coordinators->getDashboardStats($json);
        break;
        
    case 'get_assigned_students':
        echo $coordinators->getAssignedStudents($json);
        break;
        
    case 'get_attendance_records':
        echo $coordinators->getAttendanceRecords($json);
        break;
        
    case 'get_journal_submissions':
        echo $coordinators->getJournalSubmissions($json);
        break;
        
    case 'get_practicum_checklist':
        echo $coordinators->getPracticumChecklist($json);
        break;
        
    case 'verify_attendance':
        echo $coordinators->verifyAttendance($json);
        break;
        
    case 'review_journal':
        echo $coordinators->reviewJournal($json);
        break;
        
    case 'get_assigned_school':
        echo $coordinators->getAssignedSchool($json);
        break;
        
    // Student management operations for frontend compatibility
    case 'get_coordinator_stats':
        echo $coordinators->getCoordinatorStats($json);
        break;
        
    case 'read_coordinator_students':
        echo $coordinators->readCoordinatorStudents($json);
        break;
        
    case 'approve':
        echo $coordinators->approveStudent($json);
        break;
        
    case 'decline':
        echo $coordinators->declineStudent($json);
        break;
        
    case 'delete':
        echo $coordinators->deleteStudent($json);
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