<?php
include "headers.php";
date_default_timezone_set('Asia/Manila');

class Students {
    
    // Read students with approval status filter
    function read($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $approval_status = isset($data['approval_status']) ? $data['approval_status'] : 'all';
        
        // Convert string approval status to numeric values
        $status_map = [
            'pending' => 0,
            'approved' => 1,
            'declined' => 2
        ];
        
        try {
            // Base query for students with section information
            $sql = "SELECT u.school_id, u.firstname, u.lastname, u.email, u.isApproved, u.section_id, s.section_name 
                    FROM users u 
                    LEFT JOIN sections s ON u.section_id = s.id
                    WHERE u.level_id = 4";
            
            $params = [];
            
            // Add approval status filter
            if ($approval_status !== 'all' && isset($status_map[$approval_status])) {
                $sql .= " AND u.isApproved = ?";
                $params[] = $status_map[$approval_status];
            }
            
            $sql .= " ORDER BY u.school_id";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
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
    
    // Approve student
    function approve($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        
        if (empty($data['school_id'])) {
            return json_encode([
                'success' => false,
                'message' => 'School ID is required'
            ]);
        }
        
        try {
            // Check if student exists and is pending
            $check_sql = "SELECT school_id, section_id FROM users WHERE school_id = ? AND level_id = 4 AND isApproved = 0";
            $stmt = $conn->prepare($check_sql);
            $stmt->execute([$data['school_id']]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$student) {
                return json_encode([
                    'success' => false,
                    'message' => 'Student not found or already processed'
                ]);
            }
            
            // If section_id is provided, update it along with approval
            if (isset($data['section_id']) && $data['section_id']) {
                $sql = "UPDATE users SET isApproved = 1, section_id = ? WHERE school_id = ? AND level_id = 4";
                $stmt = $conn->prepare($sql);
                $result = $stmt->execute([$data['section_id'], $data['school_id']]);
            } else {
                // Just approve without changing section
                $sql = "UPDATE users SET isApproved = 1 WHERE school_id = ? AND level_id = 4";
                $stmt = $conn->prepare($sql);
                $result = $stmt->execute([$data['school_id']]);
            }
            
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
    
    // Decline student
    function decline($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        
        if (empty($data['school_id'])) {
            return json_encode([
                'success' => false,
                'message' => 'School ID is required'
            ]);
        }
        
        try {
            // Check if student exists and is pending
            $check_sql = "SELECT school_id FROM users WHERE school_id = ? AND level_id = 4 AND isApproved = 0";
            $stmt = $conn->prepare($check_sql);
            $stmt->execute([$data['school_id']]);
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                return json_encode([
                    'success' => false,
                    'message' => 'Student not found or already processed'
                ]);
            }
            
            // Decline student
            $sql = "UPDATE users SET isApproved = 2 WHERE school_id = ? AND level_id = 4";
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([$data['school_id']]);
            
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
            // Check if student exists
            $check_sql = "SELECT school_id FROM users WHERE school_id = ? AND level_id = 4";
            $stmt = $conn->prepare($check_sql);
            $stmt->execute([$data['school_id']]);
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                return json_encode([
                    'success' => false,
                    'message' => 'Student not found'
                ]);
            }
            
            // Delete student
            $sql = "DELETE FROM users WHERE school_id = ? AND level_id = 4";
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([$data['school_id']]);
            
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
    
    // Get student counts by approval status
    function getStats($json) {
        include "connection.php";
        
        try {
            $sql = "SELECT isApproved, COUNT(*) as count 
                    FROM users 
                    WHERE level_id = 4 
                    GROUP BY isApproved";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Initialize counts
            $pending = 0;
            $approved = 0;
            $declined = 0;
            
            foreach ($stats as $stat) {
                switch ($stat['isApproved']) {
                    case 0:
                        $pending = $stat['count'];
                        break;
                    case 1:
                        $approved = $stat['count'];
                        break;
                    case 2:
                        $declined = $stat['count'];
                        break;
                }
            }
            
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
    
    // Mark attendance for student
    function markAttendance($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        
        if (!isset($data['student_id']) || !isset($data['latitude']) || !isset($data['longitude'])) {
            return json_encode([
                'success' => false,
                'message' => 'Missing required parameters'
            ]);
        }
        
        $student_id = $data['student_id'];
        $latitude = $data['latitude'];
        $longitude = $data['longitude'];
        
        try {
            // Get student information
            $sql = "SELECT s.geofencing_radius, sc.latitude, sc.longitude 
                    FROM students s 
                    JOIN partnered_schools sc ON s.partnered_school_id = sc.id 
                    WHERE s.school_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$student_id]);
            $student_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$student_info) {
                return json_encode([
                    'success' => false,
                    'message' => 'Student information not found'
                ]);
            }
            
            // Calculate distance
            $distance = $this->calculateDistance($latitude, $longitude, $student_info['latitude'], $student_info['longitude']);
            
            // Check if within geofence
            if ($distance > $student_info['geofencing_radius']) {
                return json_encode([
                    'success' => false,
                    'message' => 'You are outside the attendance area. Distance: ' . round($distance, 2) . 'm (Required: within ' . $student_info['geofencing_radius'] . 'm)'
                ]);
            }
            
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
            
            // Calculate current period based on practicum start date
            try {
                // Get practicum start date
                $query = "SELECT ps.practicum_startDate 
                         FROM practicum_subjects ps 
                         WHERE ps.practicum_startDate IS NOT NULL 
                         ORDER BY ps.practicum_startDate DESC 
                         LIMIT 1";
                $stmt = $conn->prepare($query);
                $stmt->execute();
                $practicumInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $period_id = null;
                if ($practicumInfo) {
                    $startDate = new DateTime($practicumInfo['practicum_startDate']);
                    $currentDate = new DateTime();
                    
                    // Calculate total weeks from start date
                    $weeksDiff = floor($currentDate->diff($startDate)->days / 7) + 1;
                    
                    // Get all periods to determine current period
                    $periodQuery = "SELECT id, period_weeks FROM period ORDER BY id";
                    $periodStmt = $conn->prepare($periodQuery);
                    $periodStmt->execute();
                    $periods = $periodStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $accumulatedWeeks = 0;
                    foreach ($periods as $period) {
                        $accumulatedWeeks += $period['period_weeks'];
                        if ($weeksDiff <= $accumulatedWeeks) {
                            $period_id = $period['id'];
                            break;
                        }
                    }
                    
                    // If beyond all periods, use the last period
                    if (!$period_id && !empty($periods)) {
                        $lastPeriod = end($periods);
                        $period_id = $lastPeriod['id'];
                    }
                }
            } catch(PDOException $e) {
                // If period calculation fails, continue without period_id
                $period_id = null;
            }

            // Check if attendance already marked for today in this session
            $check_sql = "SELECT id FROM attendance WHERE student_id = ? AND attendance_date = CURDATE() AND session_id = ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->execute([$student_id, $session_id]);
            $existing_attendance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_attendance) {
                return json_encode([
                    'success' => false,
                    'message' => 'Attendance already marked for today in the current session'
                ]);
            }

            // Mark attendance with period_id
            $insert_sql = "INSERT INTO attendance (student_id, attendance_date, attendance_timeIn, attendance_timeOut, session_id, period_id) VALUES (?, CURDATE(), CURTIME(), NULL, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $result = $stmt->execute([$student_id, $session_id, $period_id]);
            
            if ($result) {
                return json_encode([
                    'success' => true,
                    'message' => 'Attendance marked successfully',
                    'data' => [
                        'distance' => round($distance, 2),
                        'geofence_radius' => $student_info['geofencing_radius'],
                        'period_id' => $period_id
                    ]
                ]);
            } else {
                return json_encode([
                    'success' => false,
                    'message' => 'Failed to mark attendance'
                ]);
            }
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Get attendance records for student
    function getAttendance($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $student_id = isset($data['student_id']) ? $data['student_id'] : '';
        
        if (empty($student_id)) {
            return json_encode([
                'success' => false,
                'message' => 'Student ID is required'
            ]);
        }
        
        try {
            // Get attendance records for the current month
            $sql = "SELECT attendance_date, attendance_timeIn, attendance_timeOut 
                    FROM attendance 
                    WHERE student_id = ? 
                    AND attendance_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                    ORDER BY attendance_date DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$student_id]);
            $attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return json_encode([
                'success' => true,
                'data' => $attendance_records
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Get student information for dashboard
    function getStudentInfo($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $student_id = isset($data['student_id']) ? $data['student_id'] : '';
        
        if (empty($student_id)) {
            return json_encode([
                'success' => false,
                'message' => 'Student ID is required'
            ]);
        }
        
        try {
            // Get student's section and partnered school information
            $sql = "SELECT u.section_id, s.section_name, s.school_id as partnered_school_id, 
                           ps.name as school_name, ps.address, ps.latitude, ps.longitude, ps.geofencing_radius 
                    FROM users u
                    LEFT JOIN sections s ON u.section_id = s.id
                    LEFT JOIN partnered_schools ps ON s.school_id = ps.id
                    WHERE u.school_id = ? AND u.level_id = 4";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$student_id]);
            $student_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($student_info) {
                $has_section = !empty($student_info['section_id']);
                $partnered_school = null;
                
                if ($has_section && !empty($student_info['partnered_school_id'])) {
                    $partnered_school = $student_info;
                }
                
                return json_encode([
                    'success' => true,
                    'data' => [
                        'has_section' => $has_section,
                        'partnered_school' => $partnered_school
                    ]
                ]);
            } else {
                return json_encode([
                    'success' => true,
                    'data' => [
                        'has_section' => false,
                        'partnered_school' => null
                    ]
                ]);
            }
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Mark time out for student
    function markTimeOut($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        
        if (!isset($data['student_id']) || !isset($data['latitude']) || !isset($data['longitude'])) {
            return json_encode([
                'success' => false,
                'message' => 'Missing required parameters'
            ]);
        }
        
        $student_id = $data['student_id'];
        $latitude = $data['latitude'];
        $longitude = $data['longitude'];
        
        try {
            // Get student's section and partnered school information
            $sql = "SELECT u.section_id, s.school_id as partnered_school_id, ps.latitude, ps.longitude, ps.geofencing_radius
                    FROM users u
                    LEFT JOIN sections s ON u.section_id = s.id
                    LEFT JOIN partnered_schools ps ON s.school_id = ps.id
                    WHERE u.school_id = ? AND u.level_id = 4";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$student_id]);
            $student_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$student_info || !$student_info['section_id']) {
                return json_encode([
                    'success' => false,
                    'message' => 'Student is not assigned to any section'
                ]);
            }
            
            if (!$student_info['partnered_school_id'] || !$student_info['latitude'] || !$student_info['longitude']) {
                return json_encode([
                    'success' => false,
                    'message' => 'No partnered school assigned to your section'
                ]);
            }
            
            // Calculate distance from school
            $distance = $this->calculateDistance(
                $latitude,
                $longitude,
                $student_info['latitude'],
                $student_info['longitude']
            );
            
            // Check if student is within geofence radius
            if ($distance > $student_info['geofencing_radius']) {
                return json_encode([
                    'success' => false,
                    'message' => 'You are outside the attendance area. Distance: ' . round($distance, 2) . 'm (Required: within ' . $student_info['geofencing_radius'] . 'm)'
                ]);
            }
            
            // Get active academic session
            $session_sql = "SELECT academic_session_id FROM academic_sessions WHERE is_Active = 1 LIMIT 1";
            $session_stmt = $conn->prepare($session_sql);
            $session_stmt->execute();
            $active_session = $session_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$active_session) {
                return json_encode([
                    'success' => false,
                    'message' => 'No active academic session found. Please contact your coordinator.'
                ]);
            }
            
            $session_id = $active_session['academic_session_id'];
            
            // Check if attendance exists for today with time in but no time out in this session
            $check_sql = "SELECT id, attendance_timeIn FROM attendance WHERE student_id = ? AND attendance_date = CURDATE() AND session_id = ? AND attendance_timeOut IS NULL";
            $stmt = $conn->prepare($check_sql);
            $stmt->execute([$student_id, $session_id]);
            $existing_attendance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$existing_attendance) {
                return json_encode([
                    'success' => false,
                    'message' => 'No active time in found for today. Please mark your time in first.'
                ]);
            }
            
            // Check if 8 hours have passed since time in
            $time_in = new DateTime($existing_attendance['attendance_timeIn']);
            $current_time = new DateTime();
            $time_diff = $current_time->diff($time_in);
            $hours_diff = $time_diff->h + ($time_diff->days * 24);
            
            if ($hours_diff < 8) {
                // Calculate exact time out time (8 hours after time in)
                $time_out_time = clone $time_in;
                $time_out_time->add(new DateInterval('PT8H'));
                $formatted_time_out = $time_out_time->format('g:i A');
                
                return json_encode([
                    'success' => false,
                    'message' => "You can time out at {$formatted_time_out}. Please wait until then."
                ]);
            }
            
            // Update time out
            $update_sql = "UPDATE attendance SET attendance_timeOut = CURTIME() WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $result = $stmt->execute([$existing_attendance['id']]);
            
            if ($result) {
                return json_encode([
                    'success' => true,
                    'message' => 'Time out marked successfully',
                    'data' => [
                        'distance' => round($distance, 2),
                        'geofence_radius' => $student_info['geofencing_radius'],
                        'time_in' => $existing_attendance['attendance_timeIn'],
                        'time_out' => date('H:i:s')
                    ]
                ]);
            } else {
                return json_encode([
                    'success' => false,
                    'message' => 'Failed to mark time out'
                ]);
            }
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Check attendance status for student
    function checkAttendanceStatus($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $student_id = isset($data['student_id']) ? $data['student_id'] : '';
        
        if (empty($student_id)) {
            return json_encode([
                'success' => false,
                'message' => 'Student ID is required'
            ]);
        }
        
        try {
            // Get active academic session
            $session_sql = "SELECT academic_session_id FROM academic_sessions WHERE is_Active = 1 LIMIT 1";
            $session_stmt = $conn->prepare($session_sql);
            $session_stmt->execute();
            $active_session = $session_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$active_session) {
                // If no active session, we can't properly check status, but we return empty status
                return json_encode([
                    'success' => true,
                    'data' => [
                        'has_time_in' => false,
                        'has_time_out' => false,
                        'time_in' => null,
                        'time_out' => null
                    ]
                ]);
            }
            
            $session_id = $active_session['academic_session_id'];

            // Check today's attendance status for the active session
            $sql = "SELECT attendance_timeIn, attendance_timeOut 
                    FROM attendance 
                    WHERE student_id = ? AND attendance_date = CURDATE() AND session_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$student_id, $session_id]);
            $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $status = [
                'has_time_in' => false,
                'has_time_out' => false,
                'time_in' => null,
                'time_out' => null
            ];
            
            if ($attendance) {
                $status['has_time_in'] = !empty($attendance['attendance_timeIn']);
                $status['has_time_out'] = !empty($attendance['attendance_timeOut']);
                $status['time_in'] = $attendance['attendance_timeIn'];
                $status['time_out'] = $attendance['attendance_timeOut'];
            }
            
            return json_encode([
                'success' => true,
                'data' => $status
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Create new student account
    function create($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        
        // Validate required fields
        $required_fields = ['school_id', 'firstname', 'lastname', 'email', 'section_id'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return json_encode([
                    'success' => false,
                    'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required'
                ]);
            }
        }
        
        try {
            // Check if school ID already exists
            $check_sql = "SELECT school_id FROM users WHERE school_id = ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->execute([$data['school_id']]);
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                return json_encode([
                    'success' => false,
                    'message' => 'School ID already exists'
                ]);
            }
            
            // Check if email already exists
            $check_email_sql = "SELECT email FROM users WHERE email = ?";
            $stmt = $conn->prepare($check_email_sql);
            $stmt->execute([$data['email']]);
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                return json_encode([
                    'success' => false,
                    'message' => 'Email already exists'
                ]);
            }
            
            // Validate email domain against allowed_email_domains
            $email_parts = explode('@', $data['email']);
            if (count($email_parts) !== 2) {
                return json_encode([
                    'success' => false,
                    'message' => 'Invalid email format'
                ]);
            }
            
            $domain = '@' . $email_parts[1];
            $domain_check_sql = "SELECT domain_name FROM allowed_email_domains WHERE domain_name = ?";
            $stmt = $conn->prepare($domain_check_sql);
            $stmt->execute([$domain]);
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                return json_encode([
                    'success' => false,
                    'message' => 'Email domain is not allowed. Please use an approved email domain.'
                ]);
            }
            
            // Check if section exists
            $section_sql = "SELECT id FROM sections WHERE id = ?";
            $stmt = $conn->prepare($section_sql);
            $stmt->execute([$data['section_id']]);
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                return json_encode([
                    'success' => false,
                    'message' => 'Invalid section'
                ]);
            }
            
            // Set password as School ID and hash it
            $password = $data['school_id'];
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert student
            $insert_sql = "INSERT INTO users (school_id, level_id, firstname, lastname, middlename, email, section_id, password, isApproved, created_at) 
                          VALUES (?, 4, ?, ?, ?, ?, ?, ?, 1, NOW())";
            $stmt = $conn->prepare($insert_sql);
            $result = $stmt->execute([
                $data['school_id'],
                $data['firstname'],
                $data['lastname'],
                $data['middlename'] ?? null,
                $data['email'],
                $data['section_id'],
                $hashed_password
            ]);
            
            if ($result) {
                return json_encode([
                    'success' => true,
                    'message' => 'Student account created successfully. Password is set to School ID.'
                ]);
            } else {
                return json_encode([
                    'success' => false,
                    'message' => 'Failed to create student account'
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
            $sql = "SELECT s.id, s.section_name, ps.name as school_name 
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
    
    // Helper function to calculate distance between two points
    private function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $R = 6371e3; // Earth's radius in meters
        $φ1 = $lat1 * M_PI / 180;
        $φ2 = $lat2 * M_PI / 180;
        $Δφ = ($lat2 - $lat1) * M_PI / 180;
        $Δλ = ($lon2 - $lon1) * M_PI / 180;

        $a = sin($Δφ/2) * sin($Δφ/2) +
                cos($φ1) * cos($φ2) *
                sin($Δλ/2) * sin($Δλ/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $R * $c; // Distance in meters
    }

    // Get student's current period information
    function getStudentPeriodInfo($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $student_id = $data['student_id'];
        
        try {
            // Get practicum start date and calculate current period
            $query = "SELECT ps.practicum_startDate, ps.id as practicum_id 
                     FROM practicum_subjects ps 
                     WHERE ps.practicum_startDate IS NOT NULL 
                     ORDER BY ps.practicum_startDate DESC 
                     LIMIT 1";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $practicumInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$practicumInfo) {
                return json_encode([
                    'success' => false,
                    'message' => 'No practicum information found'
                ]);
            }
            
            $startDate = new DateTime($practicumInfo['practicum_startDate']);
            $currentDate = new DateTime();
            
            // Calculate total weeks from start date
            $weeksDiff = floor($currentDate->diff($startDate)->days / 7) + 1;
            
            // Get all periods to determine current period based on weeks
            $periodQuery = "SELECT id, period_name, period_weeks FROM period ORDER BY id";
            $periodStmt = $conn->prepare($periodQuery);
            $periodStmt->execute();
            $periods = $periodStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $currentPeriod = null;
            $currentWeekInPeriod = 0;
            $accumulatedWeeks = 0;
            
            foreach ($periods as $period) {
                $accumulatedWeeks += $period['period_weeks'];
                if ($weeksDiff <= $accumulatedWeeks) {
                    $currentPeriod = $period;
                    $currentWeekInPeriod = $weeksDiff - ($accumulatedWeeks - $period['period_weeks']);
                    break;
                }
            }
            
            // If beyond all periods, use the last period
            if (!$currentPeriod && !empty($periods)) {
                $lastPeriod = end($periods);
                $currentPeriod = $lastPeriod;
                $currentWeekInPeriod = min($weeksDiff - ($accumulatedWeeks - $lastPeriod['period_weeks']), $lastPeriod['period_weeks']);
            }
            
            return json_encode([
                'success' => true,
                'data' => [
                    'period_id' => $currentPeriod ? $currentPeriod['id'] : null,
                    'period_name' => $currentPeriod ? $currentPeriod['period_name'] : null,
                    'period_weeks' => $currentPeriod ? $currentPeriod['period_weeks'] : null,
                    'current_week_in_period' => $currentWeekInPeriod,
                    'total_weeks_from_start' => $weeksDiff,
                    'practicum_start_date' => $practicumInfo['practicum_startDate']
                ]
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }

    // Get current week for student journal
    function getCurrentWeek($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $student_id = $data['student_id'];
        
        try {
            // Get active academic session
            $session_sql = "SELECT academic_session_id FROM academic_sessions WHERE is_Active = 1 LIMIT 1";
            $session_stmt = $conn->prepare($session_sql);
            $session_stmt->execute();
            $active_session = $session_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$active_session) {
                return json_encode([
                    'success' => false,
                    'message' => 'No active academic session found. Please contact your coordinator.'
                ]);
            }
            
            $session_id = $active_session['academic_session_id'];
            
            // Get student's current period information (calculate directly)
            try {
                // Get practicum start date and calculate current period
                $query = "SELECT ps.practicum_startDate, ps.id as practicum_id 
                         FROM practicum_subjects ps 
                         WHERE ps.practicum_startDate IS NOT NULL 
                         ORDER BY ps.practicum_startDate DESC 
                         LIMIT 1";
                $stmt = $conn->prepare($query);
                $stmt->execute();
                $practicumInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$practicumInfo) {
                    return json_encode([
                        'success' => false,
                        'message' => 'No practicum information found'
                    ]);
                }
                
                $startDate = new DateTime($practicumInfo['practicum_startDate']);
                $currentDate = new DateTime();
                
                // Calculate total weeks from start date
                $weeksDiff = floor($currentDate->diff($startDate)->days / 7) + 1;
                
                // Get all periods to determine current period based on weeks
                $periodQuery = "SELECT id, period_name, period_weeks FROM period ORDER BY id";
                $periodStmt = $conn->prepare($periodQuery);
                $periodStmt->execute();
                $periods = $periodStmt->fetchAll(PDO::FETCH_ASSOC);
                
                $currentPeriod = null;
                $current_week_in_period = 0;
                $accumulatedWeeks = 0;
                
                foreach ($periods as $period) {
                    $accumulatedWeeks += $period['period_weeks'];
                    if ($weeksDiff <= $accumulatedWeeks) {
                        $currentPeriod = $period;
                        $current_week_in_period = $weeksDiff - ($accumulatedWeeks - $period['period_weeks']);
                        break;
                    }
                }
                
                // If beyond all periods, use the last period
                if (!$currentPeriod && !empty($periods)) {
                    $lastPeriod = end($periods);
                    $currentPeriod = $lastPeriod;
                    $current_week_in_period = min($weeksDiff - ($accumulatedWeeks - $lastPeriod['period_weeks']), $lastPeriod['period_weeks']);
                }
                
                $period_id = $currentPeriod ? $currentPeriod['id'] : null;
                $period_weeks = $currentPeriod ? $currentPeriod['period_weeks'] : null;
                
            } catch(PDOException $e) {
                return json_encode([
                    'success' => false,
                    'message' => 'Database error: ' . $e->getMessage()
                ]);
            }
            
            $current_date = date('Y-m-d');
            $current_day = date('w', strtotime($current_date)); // 0 = Sunday, 5 = Friday
            
            // Check if today is Friday (submission day)
            $is_friday = ($current_day == 5);
            
            if ($is_friday) {
                // Check if student already submitted today (Friday) in current session and period
                $check_sql = "SELECT id, week FROM journal WHERE student_id = ? AND DATE(createdAt) = ? AND session_id = ? AND period_id = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bindParam(1, $student_id);
                $check_stmt->bindParam(2, $current_date);
                $check_stmt->bindParam(3, $session_id);
                $check_stmt->bindParam(4, $period_id);
                $check_stmt->execute();
                $today_journal = $check_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($today_journal) {
                    // Already submitted today, show next week (if within period bounds)
                    $next_week = min((int)$today_journal['week'] + 1, $period_weeks);
                    return json_encode([
                        'success' => true,
                        'week' => $next_week,
                        'period_id' => $period_id,
                        'period_weeks' => $period_weeks,
                        'already_submitted' => true,
                        'message' => 'Already submitted for this Friday'
                    ]);
                } else {
                    // Check if student has any previous journals in current session and period to determine current week
                    $latest_sql = "SELECT MAX(CAST(week AS UNSIGNED)) as latest_week FROM journal WHERE student_id = ? AND session_id = ? AND period_id = ?";
                    $latest_stmt = $conn->prepare($latest_sql);
                    $latest_stmt->bindParam(1, $student_id);
                    $latest_stmt->bindParam(2, $session_id);
                    $latest_stmt->bindParam(3, $period_id);
                    $latest_stmt->execute();
                    $latest_result = $latest_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $latest_week = $latest_result['latest_week'] ? (int)$latest_result['latest_week'] : 0;
                    
                    // Use the calculated week from practicum start date, but ensure we don't go backwards
                    $current_week = max($current_week_in_period, $latest_week + 1);
                    
                    // Make sure we don't exceed the period weeks
                    $current_week = min($current_week, $period_weeks);
                    
                    return json_encode([
                        'success' => true,
                        'week' => $current_week,
                        'period_id' => $period_id,
                        'period_weeks' => $period_weeks,
                        'already_submitted' => false,
                        'message' => 'Ready for Friday submission'
                    ]);
                }
            } else {
                // Not Friday, get the latest week for display purposes in current session and period
                $latest_sql = "SELECT MAX(CAST(week AS UNSIGNED)) as latest_week FROM journal WHERE student_id = ? AND session_id = ? AND period_id = ?";
                $latest_stmt = $conn->prepare($latest_sql);
                $latest_stmt->bindParam(1, $student_id);
                $latest_stmt->bindParam(2, $session_id);
                $latest_stmt->bindParam(3, $period_id);
                $latest_stmt->execute();
                $latest_result = $latest_stmt->fetch(PDO::FETCH_ASSOC);
                
                $latest_week = $latest_result['latest_week'] ? (int)$latest_result['latest_week'] : 0;
                
                // Use the calculated week from practicum start date, but ensure we don't go backwards
                $next_week = max($current_week_in_period, $latest_week + 1);
                $next_week = min($next_week, $period_weeks);
                
                return json_encode([
                    'success' => true,
                    'week' => $next_week,
                    'period_id' => $period_id,
                    'period_weeks' => $period_weeks,
                    'already_submitted' => false,
                    'message' => 'Not submission day'
                ]);
            }
            
        } catch(Exception $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }

    // Save journal entry
    function saveJournal($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        
        $student_id = $data['student_id'];
        $week = $data['week'];
        $period_id = $data['period_id'];
        $grateful = $data['grateful'];
        $proud_of = $data['proud_of'];
        $look_forward = $data['look_forward'];
        $felt_this_week = $data['felt_this_week'];
        $words_inspire = $data['words_inspire'] ?? [];
        $words_affirmation = $data['words_affirmation'] ?? [];
        
        try {
            // Get active academic session
            $session_sql = "SELECT academic_session_id FROM academic_sessions WHERE is_Active = 1 LIMIT 1";
            $session_stmt = $conn->prepare($session_sql);
            $session_stmt->execute();
            $active_session = $session_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$active_session) {
                return json_encode([
                    'success' => false,
                    'message' => 'No active academic session found. Please contact your coordinator.'
                ]);
            }
            
            $session_id = $active_session['academic_session_id'];
            
            $conn->beginTransaction();
            
            // Check if journal entry already exists for this week, session, and period
            $check_sql = "SELECT id FROM journal WHERE student_id = ? AND week = ? AND session_id = ? AND period_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bindParam(1, $student_id);
            $check_stmt->bindParam(2, $week);
            $check_stmt->bindParam(3, $session_id);
            $check_stmt->bindParam(4, $period_id);
            $check_stmt->execute();
            $existing_row = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_row) {
                // Update existing journal
                $journal_id = $existing_row['id'];
                
                $sql = "UPDATE journal SET grateful = ?, proud_of = ?, look_forward = ?, felt_this_week = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(1, $grateful);
                $stmt->bindParam(2, $proud_of);
                $stmt->bindParam(3, $look_forward);
                $stmt->bindParam(4, $felt_this_week);
                $stmt->bindParam(5, $journal_id);
                $stmt->execute();
                
                // Delete existing words_inspire and words_affirmation
                $conn->query("DELETE FROM words_inspire WHERE journal_id = $journal_id");
                $conn->query("DELETE FROM words_affirmation WHERE journal_id = $journal_id");
                
            } else {
                // Insert new journal with session_id and period_id
                $sql = "INSERT INTO journal (student_id, week, grateful, proud_of, look_forward, felt_this_week, session_id, period_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(1, $student_id);
                $stmt->bindParam(2, $week);
                $stmt->bindParam(3, $grateful);
                $stmt->bindParam(4, $proud_of);
                $stmt->bindParam(5, $look_forward);
                $stmt->bindParam(6, $felt_this_week);
                $stmt->bindParam(7, $session_id);
                $stmt->bindParam(8, $period_id);
                $stmt->execute();
                
                $journal_id = $conn->lastInsertId();
            }
            
            // Insert words_inspire
            if (!empty($words_inspire)) {
                $inspire_sql = "INSERT INTO words_inspire (journal_id, inspire_words) VALUES (?, ?)";
                $inspire_stmt = $conn->prepare($inspire_sql);
                
                foreach ($words_inspire as $word) {
                    if (!empty(trim($word))) {
                        $inspire_stmt->bindParam(1, $journal_id);
                        $inspire_stmt->bindParam(2, $word);
                        $inspire_stmt->execute();
                    }
                }
            }
            
            // Insert words_affirmation
            if (!empty($words_affirmation)) {
                if (is_array($words_affirmation)) {
                    // Handle array of affirmations
                    $affirmation_sql = "INSERT INTO words_affirmation (journal_id, affirmation_word) VALUES (?, ?)";
                    $affirmation_stmt = $conn->prepare($affirmation_sql);
                    
                    foreach ($words_affirmation as $affirmation) {
                        if (!empty(trim($affirmation))) {
                            $affirmation_stmt->bindParam(1, $journal_id);
                            $affirmation_stmt->bindParam(2, $affirmation);
                            $affirmation_stmt->execute();
                        }
                    }
                } else {
                    // Handle string (legacy support)
                    $affirmation_lines = array_filter(array_map('trim', explode("\n", $words_affirmation)));
                    
                    if (!empty($affirmation_lines)) {
                        $affirmation_sql = "INSERT INTO words_affirmation (journal_id, affirmation_word) VALUES (?, ?)";
                        $affirmation_stmt = $conn->prepare($affirmation_sql);
                        
                        foreach ($affirmation_lines as $line) {
                            if (!empty($line)) {
                                $affirmation_stmt->bindParam(1, $journal_id);
                                $affirmation_stmt->bindParam(2, $line);
                                $affirmation_stmt->execute();
                            }
                        }
                    }
                }
            }
            
            $conn->commit();
            
            return json_encode([
                'success' => true,
                'message' => 'Journal saved successfully'
            ]);
            
        } catch(Exception $e) {
            $conn->rollBack();
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }

    // Get journal entry
    function getJournal($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $student_id = $data['student_id'];
        $week = $data['week'];
        
        try {
            // Get journal entry
            $sql = "SELECT * FROM journal WHERE student_id = ? AND week = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(1, $student_id);
            $stmt->bindParam(2, $week);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                return json_encode([
                    'success' => true,
                    'journal' => null
                ]);
            }
            
            $journal = $result;
            $journal_id = $journal['id'];
            
            // Get words_inspire
            $inspire_sql = "SELECT inspire_words FROM words_inspire WHERE journal_id = ?";
            $inspire_stmt = $conn->prepare($inspire_sql);
            $inspire_stmt->bindParam(1, $journal_id);
            $inspire_stmt->execute();
            $words_inspire = $inspire_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get words_affirmation
            $affirmation_sql = "SELECT affirmation_word FROM words_affirmation WHERE journal_id = ?";
            $affirmation_stmt = $conn->prepare($affirmation_sql);
            $affirmation_stmt->bindParam(1, $journal_id);
            $affirmation_stmt->execute();
            $words_affirmation = $affirmation_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $journal['words_inspire'] = $words_inspire;
            $journal['words_affirmation'] = $words_affirmation;
            
            return json_encode([
                'success' => true,
                'journal' => $journal
            ]);
            
        } catch(Exception $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
}

$operation = isset($_POST["operation"]) ? $_POST["operation"] : "0";
$json = isset($_POST["json"]) ? $_POST["json"] : "0";

$students = new Students();

switch ($operation) {
    case 'read':
        echo $students->read($json);
        break;
        
    case 'approve':
        echo $students->approve($json);
        break;
        
    case 'decline':
        echo $students->decline($json);
        break;
        
    case 'delete':
        echo $students->delete($json);
        break;
        
    case 'get_stats':
        echo $students->getStats($json);
        break;
        
    case 'mark_attendance':
        echo $students->markAttendance($json);
        break;
        
    case 'mark_timeout':
        echo $students->markTimeOut($json);
        break;
        
    case 'get_attendance':
        echo $students->getAttendance($json);
        break;
        
    case 'check_attendance_status':
        echo $students->checkAttendanceStatus($json);
        break;
        
    case 'get_student_info':
        echo $students->getStudentInfo($json);
        break;
        
    case 'create':
        echo $students->create($json);
        break;
        
    case 'get_sections':
        echo $students->getSections($json);
        break;
        
    case 'get_current_week':
        echo $students->getCurrentWeek($json);
        break;
        
    case 'get_student_period_info':
        echo getStudentPeriodInfo($json);
        break;
        
    case 'get_current_period':
        // Calculate current period for attendance (same logic as journal)
        try {
            $data = json_decode($json, true);
            $student_id = $data['student_id'];
            
            include "connection.php";
            
            // Get practicum start date and calculate current period
            $query = "SELECT ps.practicum_startDate, ps.id as practicum_id 
                     FROM practicum_subjects ps 
                     WHERE ps.practicum_startDate IS NOT NULL 
                     ORDER BY ps.practicum_startDate DESC 
                     LIMIT 1";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $practicumInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$practicumInfo) {
                echo json_encode([
                    'success' => false,
                    'message' => 'No practicum information found'
                ]);
                break;
            }
            
            $startDate = new DateTime($practicumInfo['practicum_startDate']);
            $currentDate = new DateTime();
            
            // Calculate total weeks from start date
            $weeksDiff = floor($currentDate->diff($startDate)->days / 7) + 1;
            
            // Get all periods to determine current period based on weeks
            $periodQuery = "SELECT id, period_name, period_weeks FROM period ORDER BY id";
            $periodStmt = $conn->prepare($periodQuery);
            $periodStmt->execute();
            $periods = $periodStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $currentPeriod = null;
            $currentWeekInPeriod = 0;
            $accumulatedWeeks = 0;
            
            foreach ($periods as $period) {
                $accumulatedWeeks += $period['period_weeks'];
                if ($weeksDiff <= $accumulatedWeeks) {
                    $currentPeriod = $period;
                    $currentWeekInPeriod = $weeksDiff - ($accumulatedWeeks - $period['period_weeks']);
                    break;
                }
            }
            
            // If beyond all periods, use the last period
            if (!$currentPeriod && !empty($periods)) {
                $lastPeriod = end($periods);
                $currentPeriod = $lastPeriod;
                $currentWeekInPeriod = min($weeksDiff - ($accumulatedWeeks - $lastPeriod['period_weeks']), $lastPeriod['period_weeks']);
            }
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'period_id' => $currentPeriod ? $currentPeriod['id'] : null,
                    'period_name' => $currentPeriod ? $currentPeriod['period_name'] : null,
                    'period_weeks' => $currentPeriod ? $currentPeriod['period_weeks'] : null,
                    'current_week_in_period' => $currentWeekInPeriod,
                    'total_weeks_from_start' => $weeksDiff,
                    'practicum_start_date' => $practicumInfo['practicum_startDate']
                ]
            ]);
            
        } catch(PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'save_journal':
        echo $students->saveJournal($json);
        break;
        
    case 'get_journal':
        echo $students->getJournal($json);
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
