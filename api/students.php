<?php
include "headers.php";

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
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid operation'
        ]);
        http_response_code(400);
        break;
}
?>
