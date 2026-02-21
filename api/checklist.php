<?php
include "headers.php";
date_default_timezone_set('Asia/Manila');

class Checklist {
    
    // Get all categories
    function getCategories($json) {
        include "connection.php";
        
        try {
            $sql = "SELECT * FROM checklist_category ORDER BY category_name";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return json_encode([
                'success' => true,
                'data' => $categories
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Add new category
    function addCategory($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $name = $data['name'] ?? '';
        $is_type = $data['is_type'] ?? 0;
        
        if (empty($name)) {
            return json_encode([
                'success' => false,
                'message' => 'Category name is required'
            ]);
        }
        
        try {
            $sql = "INSERT INTO checklist_category (category_name, is_type) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$name, $is_type]);
            
            return json_encode([
                'success' => true,
                'message' => 'Category added successfully'
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Update category
    function updateCategory($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $id = $data['id'] ?? '';
        $name = $data['name'] ?? '';
        $is_type = $data['is_type'] ?? 0;
        
        if (empty($id) || empty($name)) {
            return json_encode([
                'success' => false,
                'message' => 'ID and name are required'
            ]);
        }
        
        try {
            $sql = "UPDATE checklist_category SET category_name = ?, is_type = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$name, $is_type, $id]);
            
            return json_encode([
                'success' => true,
                'message' => 'Category updated successfully'
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Delete category
    function deleteCategory($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $id = $data['id'] ?? '';
        
        if (empty($id)) {
            return json_encode([
                'success' => false,
                'message' => 'ID is required'
            ]);
        }
        
        try {
            // First delete all criteria associated with this category
            $sql = "DELETE FROM checklist WHERE category_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$id]);
            
            // Then delete the category
            $sql = "DELETE FROM checklist_category WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$id]);
            
            return json_encode([
                'success' => true,
                'message' => 'Category deleted successfully'
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Get all types
    function getTypes($json) {
        include "connection.php";
        
        try {
            $sql = "SELECT * FROM checklist_type ORDER BY type_name";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return json_encode([
                'success' => true,
                'data' => $types
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Add new type
    function addType($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $name = $data['name'] ?? '';
        
        if (empty($name)) {
            return json_encode([
                'success' => false,
                'message' => 'Type name is required'
            ]);
        }
        
        try {
            $sql = "INSERT INTO checklist_type (type_name) VALUES (?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$name]);
            
            return json_encode([
                'success' => true,
                'message' => 'Type added successfully'
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Update type
    function updateType($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $id = $data['id'] ?? '';
        $name = $data['name'] ?? '';
        
        if (empty($id) || empty($name)) {
            return json_encode([
                'success' => false,
                'message' => 'ID and name are required'
            ]);
        }
        
        try {
            $sql = "UPDATE checklist_type SET type_name = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$name, $id]);
            
            return json_encode([
                'success' => true,
                'message' => 'Type updated successfully'
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Delete type
    function deleteType($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $id = $data['id'] ?? '';
        
        if (empty($id)) {
            return json_encode([
                'success' => false,
                'message' => 'ID is required'
            ]);
        }
        
        try {
            // Check if type is being used in any criteria
            $sql = "SELECT COUNT(*) as count FROM checklist WHERE type_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                return json_encode([
                    'success' => false,
                    'message' => 'Cannot delete type. It is being used by criteria.'
                ]);
            }
            
            $sql = "DELETE FROM checklist_type WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$id]);
            
            return json_encode([
                'success' => true,
                'message' => 'Type deleted successfully'
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Get all criteria with category and type names
    function getCriteria($json) {
        include "connection.php";
        
        try {
            $sql = "SELECT c.*, cat.category_name, t.type_name 
                    FROM checklist c 
                    LEFT JOIN checklist_category cat ON c.category_id = cat.id 
                    LEFT JOIN checklist_type t ON c.type_id = t.id 
                    ORDER BY cat.category_name, c.checklist_criteria";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $criteria = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return json_encode([
                'success' => true,
                'data' => $criteria
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Add new criteria
    function addCriteria($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $category_id = $data['category_id'] ?? '';
        $type_id = !empty($data['type_id']) ? $data['type_id'] : null;
        $name = $data['name'] ?? '';
        $points = $data['points'] ?? 0;
        
        if (empty($category_id) || empty($name)) {
            return json_encode([
                'success' => false,
                'message' => 'Category and name are required'
            ]);
        }
        
        try {
            $sql = "INSERT INTO checklist (category_id, type_id, checklist_criteria, points) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$category_id, $type_id, $name, $points]);
            
            return json_encode([
                'success' => true,
                'message' => 'Criteria added successfully'
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Update criteria
    function updateCriteria($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $id = $data['id'] ?? '';
        $category_id = $data['category_id'] ?? '';
        $type_id = !empty($data['type_id']) ? $data['type_id'] : null;
        $name = $data['name'] ?? '';
        $points = $data['points'] ?? 0;
        
        if (empty($id) || empty($category_id) || empty($name)) {
            return json_encode([
                'success' => false,
                'message' => 'ID, category, and name are required'
            ]);
        }
        
        try {
            $sql = "UPDATE checklist SET category_id = ?, type_id = ?, checklist_criteria = ?, points = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$category_id, $type_id, $name, $points, $id]);
            
            return json_encode([
                'success' => true,
                'message' => 'Criteria updated successfully'
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Delete criteria
    function deleteCriteria($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $id = $data['id'] ?? '';
        
        if (empty($id)) {
            return json_encode([
                'success' => false,
                'message' => 'ID is required'
            ]);
        }
        
        try {
            // Check if criteria has any results
            $sql = "SELECT COUNT(*) as count FROM checklist_results WHERE checklist_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                return json_encode([
                    'success' => false,
                    'message' => 'Cannot delete criteria. It has associated results.'
                ]);
            }
            
            $sql = "DELETE FROM checklist WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$id]);
            
            return json_encode([
                'success' => true,
                'message' => 'Criteria deleted successfully'
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Get periods
    function getPeriods($json) {
        include "connection.php";
        
        try {
            $query = "SELECT id, period_name, period_weeks FROM period ORDER BY id";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            
            $periods = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return json_encode([
                'success' => true,
                'data' => $periods
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Get student information and current week
    function getStudentInfo($json) {
        include "connection.php";
        
        try {
            $data = json_decode($json, true);
            $studentId = $data['student_id'];
            $periodId = $data['period_id'];
            
            // Get student information
            $query = "SELECT u.school_id, u.firstname, u.lastname, u.middlename, s.section_name 
                     FROM users u 
                     LEFT JOIN sections s ON u.section_id = s.id 
                     WHERE u.school_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$studentId]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$student) {
                return json_encode([
                    'success' => false,
                    'message' => 'Student not found'
                ]);
            }
            
            // Calculate current week based on practicum start date
            $query = "SELECT ps.practicum_startDate, p.period_weeks 
                     FROM practicum_subjects ps 
                     JOIN period p ON ps.id = p.id 
                     WHERE p.id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$periodId]);
            $practicumInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$practicumInfo) {
                return json_encode([
                    'success' => false,
                    'message' => 'Period information not found'
                ]);
            }
            
            // Calculate current week
            $startDate = new DateTime($practicumInfo['practicum_startDate']);
            $currentDate = new DateTime();
            $weeksDiff = floor($currentDate->diff($startDate)->days / 7) + 1;
            $currentWeek = min($weeksDiff, $practicumInfo['period_weeks']);
            
            // Check if checklist is already completed for this week (by checking if any record exists for this student/period in current week)
            $weekStartDate = clone $startDate;
            $weekStartDate->add(new DateInterval('P' . ($currentWeek - 1) . 'W'));
            $weekEndDate = clone $weekStartDate;
            $weekEndDate->add(new DateInterval('P6D')); // Add 6 days to get the week range
            
            $query = "SELECT COUNT(*) as completed_count 
                     FROM checklist_results 
                     WHERE student_id = ? AND period_id = ? 
                     AND date_checked >= ? AND date_checked <= ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$studentId, $periodId, $weekStartDate->format('Y-m-d'), $weekEndDate->format('Y-m-d')]);
            $completedCount = $stmt->fetch(PDO::FETCH_ASSOC)['completed_count'];
            
            // Get coordinator ID (assuming it's stored in session)
            $coordinatorId = $_SESSION['coordinator_id'] ?? 'COORD-001';
            
            return json_encode([
                'success' => true,
                'data' => [
                    'student' => $student,
                    'current_week' => $currentWeek,
                    'period_weeks' => $practicumInfo['period_weeks'],
                    'week_completed' => $completedCount > 0,
                    'coordinator_id' => $coordinatorId
                ]
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Get student checklist items
    function getStudentChecklist($json) {
        include "connection.php";
        
        try {
            $data = json_decode($json, true);
            $studentId = $data['student_id'];
            $periodId = $data['period_id'];
            $selectedWeek = $data['week'] ?? null;
            
            // Get all checklist items with category and type info
            $query = "SELECT c.id, c.checklist_criteria, c.points, 
                             cc.category_name, cc.is_ratingscore, ct.type_name 
                     FROM checklist c 
                     LEFT JOIN checklist_category cc ON c.category_id = cc.id 
                     LEFT JOIN checklist_type ct ON c.type_id = ct.id 
                     ORDER BY cc.category_name, ct.type_name, c.checklist_criteria";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $checklistItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
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
            
            // Get existing results for this student, period, and session
            // If week is specified, filter by that week
            $query = "SELECT cr.checklist_id, cr.points_earned 
                     FROM checklist_results cr
                     JOIN period p ON cr.period_id = p.id
                     JOIN practicum_subjects ps ON p.id = ps.id
                     WHERE cr.student_id = ? AND cr.period_id = ? AND cr.session_id = ?";
            
            $params = [$studentId, $periodId, $session_id];
            
            if ($selectedWeek) {
                $query .= " AND FLOOR(DATEDIFF(cr.date_checked, ps.practicum_startDate) / 7) + 1 = ?";
                $params[] = $selectedWeek;
            }
            
            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            $existingResults = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // Merge results
            foreach ($checklistItems as &$item) {
                $item['points_earned'] = $existingResults[$item['id']] ?? 0;
            }
            
            // Check if THIS specific week is completed
            $week_completed = false;
            if ($selectedWeek) {
                $query_comp = "SELECT COUNT(*) as completed_count 
                              FROM checklist_results cr
                              JOIN period p ON cr.period_id = p.id
                              JOIN practicum_subjects ps ON p.id = ps.id
                              WHERE cr.student_id = ? AND cr.period_id = ? AND cr.session_id = ?
                              AND FLOOR(DATEDIFF(cr.date_checked, ps.practicum_startDate) / 7) + 1 = ?";
                $stmt_comp = $conn->prepare($query_comp);
                $stmt_comp->execute([$studentId, $periodId, $session_id, $selectedWeek]);
                $week_completed = ($stmt_comp->fetch(PDO::FETCH_ASSOC)['completed_count'] > 0);
            }
            
            return json_encode([
                'success' => true,
                'data' => $checklistItems,
                'week_completed' => $week_completed
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Save checklist results
    function saveChecklistResults($json) {
        include "connection.php";
        
        try {
            $data = json_decode($json, true);
            $studentId = $data['student_id'];
            $periodId = $data['period_id'];
            $results = $data['results'];
            $checkedBy = $data['checked_by'];
            
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
            $selectedWeek = $data['week'] ?? null;
            $dateToSave = date('Y-m-d');
            
            if ($selectedWeek) {
                // Get practicum_startDate to ensure we save for the correct week
                $sd_sql = "SELECT ps.practicum_startDate FROM practicum_subjects ps WHERE ps.id = ?";
                $sd_stmt = $conn->prepare($sd_sql);
                $sd_stmt->execute([$periodId]);
                $startDateStr = $sd_stmt->fetchColumn();
                
                if ($startDateStr) {
                    $startDate = new DateTime($startDateStr);
                    $startDate->add(new DateInterval('P' . ($selectedWeek - 1) . 'W'));
                    $dateToSave = $startDate->format('Y-m-d');
                }
            }
            
            $conn->beginTransaction();
            
            // Insert new results with session_id
            $query = "INSERT INTO checklist_results 
                     (student_id, checklist_id, period_id, session_id, points_earned, checked_by, date_checked) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            
            foreach ($results as $result) {
                $stmt->execute([
                    $studentId,
                    $result['checklist_id'],
                    $periodId,
                    $session_id,
                    $result['points_earned'],
                    $checkedBy,
                    $dateToSave
                ]);
            }
            
            $conn->commit();
            
            return json_encode([
                'success' => true,
                'message' => 'Checklist results saved successfully'
            ]);
            
        } catch(PDOException $e) {
            $conn->rollBack();
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Get checklist records with filtering
    function getChecklistRecords($json) {
        include "connection.php";
        
        try {
            $data = json_decode($json, true);
            $sessionId = $data['session_id'] ?? 'all';
            $sectionId = $data['section_id'] ?? 'all';
            $periodId = $data['period_id'] ?? 'all';
            $week = $data['week'] ?? 'all';
            
            // Build base query
            $query = "SELECT 
                        u.school_id,
                        CONCAT(u.firstname, ' ', COALESCE(u.middlename, ''), ' ', u.lastname) as student_name,
                        sec.section_name,
                        CONCAT(cu.firstname, ' ', cu.lastname) as coordinator_name,
                        cr.date_checked,
                        cr.period_id,
                        cr.session_id,
                        p.period_name,
                        p.period_weeks,
                        CONCAT(sy.school_year, ' - ', sem.semester_name) as academic_session_name,
                        SUM(cr.points_earned) as total_score,
                        COUNT(*) as criteria_count,
                        -- Calculate week number based on period start date
                        CASE 
                            WHEN ps.practicum_startDate IS NOT NULL THEN
                                FLOOR(DATEDIFF(cr.date_checked, ps.practicum_startDate) / 7) + 1
                            ELSE NULL
                        END as week_number
                      FROM checklist_results cr
                      JOIN users u ON cr.student_id = u.school_id
                      JOIN sections sec ON u.section_id = sec.id
                      JOIN users cu ON cr.checked_by = cu.school_id
                      LEFT JOIN period p ON cr.period_id = p.id
                      LEFT JOIN practicum_subjects ps ON p.id = ps.id
                      LEFT JOIN academic_sessions ac ON cr.session_id = ac.academic_session_id
                      LEFT JOIN school_years sy ON ac.school_year_id = sy.school_year_id
                      LEFT JOIN semesters sem ON ac.semester_id = sem.semester_id
                      WHERE 1=1";
            
            $params = [];
            
            // Add session filter
            if ($sessionId !== 'all') {
                $query .= " AND cr.session_id = ?";
                $params[] = $sessionId;
            }
            
            // Add section filter
            if ($sectionId !== 'all') {
                $query .= " AND u.section_id = ?";
                $params[] = $sectionId;
            }
            
            // Add period filter
            if ($periodId !== 'all') {
                $query .= " AND cr.period_id = ?";
                $params[] = $periodId;
            }
            
            // Add week filter - this should work regardless of period selection
            if ($week !== 'all') {
                if ($periodId !== 'all') {
                    // Specific period selected: calculate relative week
                    $periodQuery = "SELECT 
                                     p.period_weeks,
                                     (SELECT COALESCE(SUM(prev_p.period_weeks), 0) 
                                      FROM period prev_p 
                                      WHERE prev_p.id < p.id) as start_week
                                   FROM period p 
                                   WHERE p.id = ?";
                    $periodStmt = $conn->prepare($periodQuery);
                    $periodStmt->execute([$periodId]);
                    $periodInfo = $periodStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($periodInfo) {
                        $startWeek = $periodInfo['start_week'] + 1;
                        $selectedWeek = $startWeek + intval($week) - 1;
                        
                        $query .= " AND FLOOR(DATEDIFF(cr.date_checked, ps.practicum_startDate) / 7) + 1 = ?";
                        $params[] = $selectedWeek;
                    }
                } else {
                    // All periods selected: filter by absolute week number
                    $query .= " AND FLOOR(DATEDIFF(cr.date_checked, ps.practicum_startDate) / 7) + 1 = ?";
                    $params[] = intval($week);
                }
            }
            
            $query .= " GROUP BY cr.student_id, cr.period_id, cr.session_id, cr.date_checked
                        ORDER BY cr.date_checked DESC, u.lastname, u.firstname";
            
            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return json_encode([
                'success' => true,
                'data' => $records
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Get sections for filtering
    function getSections($json) {
        include "connection.php";
        
        try {
            $query = "SELECT id, section_name FROM sections ORDER BY section_name";
            $stmt = $conn->prepare($query);
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
    
    // Get academic sessions for filtering
    function getAcademicSessions($json) {
        include "connection.php";
        
        try {
            $query = "SELECT asess.academic_session_id, sy.school_year, s.semester_name,
                            CASE WHEN asess.is_Active = 1 THEN ' (Active)' ELSE '' END as status_label
                     FROM academic_sessions asess
                     JOIN school_years sy ON asess.school_year_id = sy.school_year_id
                     JOIN semesters s ON asess.semester_id = s.semester_id
                     ORDER BY asess.is_Active DESC, sy.school_year DESC, s.semester_name";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return json_encode([
                'success' => true,
                'data' => $sessions
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Get active academic session
    function getActiveAcademicSession($json) {
        include "connection.php";
        
        try {
            $query = "SELECT asess.academic_session_id, asess.school_year_id, asess.semester_id,
                            sy.school_year, s.semester_name
                     FROM academic_sessions asess
                     JOIN school_years sy ON asess.school_year_id = sy.school_year_id
                     JOIN semesters s ON asess.semester_id = s.semester_id
                     WHERE asess.is_Active = 1
                     LIMIT 1";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $active_session = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$active_session) {
                return json_encode([
                    'success' => false,
                    'message' => 'No active academic session found'
                ]);
            }
            
            return json_encode([
                'success' => true,
                'data' => $active_session
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Get period summary for all students in a period
    function getPeriodSummaryForAll($json) {
        include "connection.php";
        
        try {
            $data = json_decode($json, true);
            $sessionId = $data['session_id'];
            $periodId = $data['period_id'];
            $sectionId = $data['section_id'];
            
            // Build base query
            $query = "SELECT 
                        u.school_id,
                        CONCAT(u.firstname, ' ', COALESCE(u.middlename, ''), ' ', u.lastname) as student_name,
                        s.section_name,
                        COUNT(DISTINCT cr.date_checked) as weeks_evaluated,
                        SUM(cr.points_earned) as total_score,
                        GROUP_CONCAT(
                            DISTINCT CASE 
                                WHEN ps.practicum_startDate IS NOT NULL THEN
                                    FLOOR(DATEDIFF(cr.date_checked, ps.practicum_startDate) / 7) + 1
                                ELSE NULL
                            END 
                            ORDER BY cr.date_checked ASC
                        ) as evaluated_weeks
                      FROM checklist_results cr
                      JOIN users u ON cr.student_id = u.school_id
                      JOIN sections s ON u.section_id = s.id
                      LEFT JOIN practicum_subjects ps ON cr.period_id = ps.id
                      WHERE cr.session_id = ? 
                        AND cr.period_id = ?";
            
            $params = [$sessionId, $periodId];
            
            // Add section filter if not all
            if ($sectionId !== 'all') {
                $query .= " AND u.section_id = ?";
                $params[] = $sectionId;
            }
            
            $query .= " GROUP BY u.school_id, u.firstname, u.middlename, u.lastname, s.section_name
                        ORDER BY u.lastname, u.firstname";
            
            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            $summaryData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return json_encode([
                'success' => true,
                'data' => $summaryData
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Get detailed checklist results for a specific record
    function getChecklistRecordDetails($json) {
        include "connection.php";
        
        try {
            $data = json_decode($json, true);
            $studentId = $data['student_id'];
            $periodId = $data['period_id'];
            $dateChecked = $data['date_checked'];
            
            $query = "SELECT 
                        cr.checklist_id,
                        cr.points_earned,
                        c.checklist_criteria,
                        CASE 
                            WHEN c.checklist_criteria = 'Well-pressed prescribed ST uniform' AND cc.is_ratingscore = 1 THEN 2
                            ELSE c.points
                        END as max_points,
                        cc.category_name,
                        ct.type_name,
                        CASE 
                            WHEN cc.is_type = 1 THEN 1
                            ELSE 0
                        END as has_type
                      FROM checklist_results cr
                      JOIN checklist c ON cr.checklist_id = c.id
                      JOIN checklist_category cc ON c.category_id = cc.id
                      LEFT JOIN checklist_type ct ON c.type_id = ct.id
                      WHERE cr.student_id = ? 
                        AND cr.period_id = ? 
                        AND cr.date_checked = ?
                      ORDER BY cc.category_name, ct.type_name, c.checklist_criteria";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([$studentId, $periodId, $dateChecked]);
            $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return json_encode([
                'success' => true,
                'data' => $details
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Get period info for week calculation
    function getPeriodInfo($json) {
        include "connection.php";
        
        try {
            $data = json_decode($json, true);
            $periodId = $data['period_id'];
            
            // Debug: Get all periods to see the data
            $debugQuery = "SELECT id, period_name, period_weeks FROM period ORDER BY id";
            $debugStmt = $conn->prepare($debugQuery);
            $debugStmt->execute();
            $allPeriods = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get the current period info
            $query = "SELECT 
                        p.id,
                        p.period_name,
                        p.period_weeks,
                        (SELECT COALESCE(SUM(prev_p.period_weeks), 0) 
                         FROM period prev_p 
                         WHERE prev_p.id < p.id) as start_week
                      FROM period p 
                      WHERE p.id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$periodId]);
            $periodInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return json_encode([
                'success' => true,
                'data' => $periodInfo
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    $action = $data['action'] ?? '';
    
    $checklist = new Checklist();
    
    switch ($action) {
        case 'getCategories':
            echo $checklist->getCategories($json);
            break;
        case 'addCategory':
            echo $checklist->addCategory($json);
            break;
        case 'updateCategory':
            echo $checklist->updateCategory($json);
            break;
        case 'deleteCategory':
            echo $checklist->deleteCategory($json);
            break;
        case 'getTypes':
            echo $checklist->getTypes($json);
            break;
        case 'addType':
            echo $checklist->addType($json);
            break;
        case 'updateType':
            echo $checklist->updateType($json);
            break;
        case 'deleteType':
            echo $checklist->deleteType($json);
            break;
        case 'getCriteria':
            echo $checklist->getCriteria($json);
            break;
        case 'addCriteria':
            echo $checklist->addCriteria($json);
            break;
        case 'updateCriteria':
            echo $checklist->updateCriteria($json);
            break;
        case 'deleteCriteria':
            echo $checklist->deleteCriteria($json);
            break;
        case 'getPeriods':
            echo $checklist->getPeriods($json);
            break;
        case 'getStudentInfo':
            echo $checklist->getStudentInfo($json);
            break;
        case 'getStudentChecklist':
            echo $checklist->getStudentChecklist($json);
            break;
        case 'saveChecklistResults':
            echo $checklist->saveChecklistResults($json);
            break;
        case 'getChecklistRecords':
            echo $checklist->getChecklistRecords($json);
            break;
        case 'getSections':
            echo $checklist->getSections($json);
            break;
        case 'getAcademicSessions':
            echo $checklist->getAcademicSessions($json);
            break;
        case 'getActiveAcademicSession':
            echo $checklist->getActiveAcademicSession($json);
            break;
        case 'getPeriodSummaryForAll':
            echo $checklist->getPeriodSummaryForAll($json);
            break;
        case 'getChecklistRecordDetails':
            echo $checklist->getChecklistRecordDetails($json);
            break;
        case 'getPeriodInfo':
            echo $checklist->getPeriodInfo($json);
            break;
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
            break;
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>