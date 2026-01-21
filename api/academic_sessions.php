<?php
require_once 'headers.php';
require_once 'connection.php';

// Get the operation from the request
$operation = $_POST['operation'] ?? '';

try {
    switch ($operation) {
        case 'read':
            readAcademicSessions();
            break;
        case 'read_active':
            readActiveAcademicSession();
            break;
        case 'set_active':
            setActiveAcademicSession();
            break;
        default:
            sendResponse(false, 'Invalid operation');
            break;
    }
} catch (Exception $e) {
    sendResponse(false, 'Error: ' . $e->getMessage());
}

function readAcademicSessions() {
    global $conn;
    
    $query = "SELECT 
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
    
    $result = $conn->query($query);
    
    if ($result) {
        $sessions = [];
        while ($row = $result->fetch_assoc()) {
            $sessions[] = $row;
        }
        sendResponse(true, 'Academic sessions retrieved successfully', $sessions);
    } else {
        sendResponse(false, 'Failed to retrieve academic sessions');
    }
}

function readActiveAcademicSession() {
    global $conn;
    
    $query = "SELECT 
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
    
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $activeSession = $result->fetch_assoc();
        sendResponse(true, 'Active academic session retrieved successfully', $activeSession);
    } else {
        sendResponse(true, 'No active academic session found', null);
    }
}

function setActiveAcademicSession() {
    global $conn;
    
    // Get JSON data
    $json = $_POST['json'] ?? '';
    $data = json_decode($json, true);
    
    if (!$data || !isset($data['academic_session_id'])) {
        sendResponse(false, 'Academic session ID is required');
        return;
    }
    
    $sessionId = $data['academic_session_id'];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // First, set all sessions to inactive
        $updateAllQuery = "UPDATE academic_sessions SET is_Active = 0";
        $conn->query($updateAllQuery);
        
        // Then, set the selected session to active
        $updateQuery = "UPDATE academic_sessions SET is_Active = 1, updated_at = CURRENT_TIMESTAMP WHERE academic_session_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param('i', $sessionId);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $conn->commit();
            sendResponse(true, 'Active academic session updated successfully');
        } else {
            $conn->rollback();
            sendResponse(false, 'Failed to update active academic session');
        }
        
        $stmt->close();
    } catch (Exception $e) {
        $conn->rollback();
        sendResponse(false, 'Error updating active academic session: ' . $e->getMessage());
    }
}

function sendResponse($success, $message, $data = null) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>
