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
            // Base query for students
            $sql = "SELECT school_id, firstname, lastname, email, isApproved 
                    FROM users 
                    WHERE level_id = 4";
            
            $params = [];
            
            // Add approval status filter
            if ($approval_status !== 'all' && isset($status_map[$approval_status])) {
                $sql .= " AND isApproved = ?";
                $params[] = $status_map[$approval_status];
            }
            
            $sql .= " ORDER BY school_id";
            
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
            $check_sql = "SELECT school_id FROM users WHERE school_id = ? AND level_id = 4 AND isApproved = 0";
            $stmt = $conn->prepare($check_sql);
            $stmt->execute([$data['school_id']]);
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                return json_encode([
                    'success' => false,
                    'message' => 'Student not found or already processed'
                ]);
            }
            
            // Approve student
            $sql = "UPDATE users SET isApproved = 1 WHERE school_id = ? AND level_id = 4";
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([$data['school_id']]);
            
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
            
            // Check if attendance already marked today
            $check_sql = "SELECT id FROM attendance WHERE student_id = ? AND attendance_date = CURDATE()";
            $stmt = $conn->prepare($check_sql);
            $stmt->execute([$student_id]);
            $existing_attendance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_attendance) {
                return json_encode([
                    'success' => false,
                    'message' => 'Attendance already marked for today'
                ]);
            }
            
            // Mark attendance
            $insert_sql = "INSERT INTO attendance (student_id, attendance_date, attendance_timeIn, attendance_timeOut) VALUES (?, CURDATE(), CURTIME(), NULL)";
            $stmt = $conn->prepare($insert_sql);
            $result = $stmt->execute([$student_id]);
            
            if ($result) {
                return json_encode([
                    'success' => true,
                    'message' => 'Attendance marked successfully',
                    'data' => [
                        'distance' => round($distance, 2),
                        'geofence_radius' => $student_info['geofencing_radius'],
                        'time' => date('H:i:s')
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
            
            // Check if attendance exists for today with time in but no time out
            $check_sql = "SELECT id, attendance_timeIn FROM attendance WHERE student_id = ? AND attendance_date = CURDATE() AND attendance_timeOut IS NULL";
            $stmt = $conn->prepare($check_sql);
            $stmt->execute([$student_id]);
            $existing_attendance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$existing_attendance) {
                return json_encode([
                    'success' => false,
                    'message' => 'No active time in found for today. Please mark your time in first.'
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
            // Check today's attendance status
            $sql = "SELECT attendance_timeIn, attendance_timeOut 
                    FROM attendance 
                    WHERE student_id = ? AND attendance_date = CURDATE()";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$student_id]);
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
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid operation'
        ]);
        http_response_code(400);
        break;
}
?>
