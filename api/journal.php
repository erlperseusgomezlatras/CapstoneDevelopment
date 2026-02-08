<?php
include "headers.php";
date_default_timezone_set('Asia/Manila');

class Journal {
    
    // Get section overview with journal statistics
    function getSectionOverview($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $filters = $data['filters'] ?? [];
        $dateFilter = $filters['dateRange'] ?? 'this_week';
        $sectionFilter = $filters['section'] ?? 'all';
        $academicSessionFilter = $filters['academicSession'] ?? 'all';
        
        try {
            // Build date condition
            $dateCondition = $this->buildJournalDateCondition($dateFilter, $filters);
            
            // Build section condition
            $sectionCondition = '';
            if ($sectionFilter !== 'all') {
                $sectionCondition = "AND s.id = " . intval($sectionFilter);
            }
            
            // Build academic session condition
            $academicSessionCondition = '';
            if ($academicSessionFilter !== 'all') {
                $academicSessionCondition = "AND j.session_id = " . intval($academicSessionFilter);
            }
            
            // Query to get sections with journal statistics
            $sql = "
                SELECT 
                    s.id as section_id,
                    s.section_name,
                    GROUP_CONCAT(DISTINCT CONCAT(ps.name, ' (', ps.school_type, ')') SEPARATOR ', ') as school_name,
                    COUNT(DISTINCT u.school_id) as total_students,
                    COUNT(DISTINCT j.student_id) as students_submitted,
                    COUNT(DISTINCT CASE WHEN j.week IS NOT NULL THEN j.student_id END) as total_entries,
                    CASE 
                        WHEN COUNT(DISTINCT u.school_id) = 0 THEN 0
                        ELSE ROUND(
                            (COUNT(DISTINCT j.student_id) * 100.0) / 
                            COUNT(DISTINCT u.school_id), 2
                        )
                    END as submission_rate,
                    MAX(j.week) as latest_week,
                    COUNT(DISTINCT j.week) as weeks_covered
                FROM sections s
                LEFT JOIN users u ON s.id = u.section_id AND u.level_id = 4 AND u.isActive = 1
                LEFT JOIN section_schools ss ON s.id = ss.section_id
                LEFT JOIN partnered_schools ps ON ss.school_id = ps.id
                LEFT JOIN journal j ON u.school_id = j.student_id 
                    $dateCondition 
                    $academicSessionCondition
                WHERE 1=1 
                    $sectionCondition
                GROUP BY s.id, s.section_name
                ORDER BY s.section_name
            ";
            
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
    
    // Get journal entries for a specific section
    function getSectionJournals($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $section_id = $data['section_id'];
        $filters = $data['filters'] ?? [];
        $dateFilter = $filters['dateRange'] ?? 'this_week';
        $academicSessionFilter = $filters['academicSession'] ?? 'all';
        
        try {
            // Build date condition
            $dateCondition = $this->buildJournalDateCondition($dateFilter, $filters);
            
            // Build academic session condition
            $academicSessionCondition = '';
            if ($academicSessionFilter !== 'all') {
                $academicSessionCondition = "AND j.session_id = " . intval($academicSessionFilter);
            }
            
            // Query to get journal entries for the section
            $sql = "
                SELECT 
                    j.id,
                    j.student_id,
                    j.week,
                    j.grateful,
                    j.proud_of,
                    j.look_forward,
                    j.felt_this_week,
                    j.createdAt,
                    u.firstname,
                    u.lastname,
                    u.email,
                    s.section_name,
                    (
                        SELECT GROUP_CONCAT(DISTINCT CONCAT(ps.name, ' (', ps.school_type, ')') SEPARATOR ', ')
                        FROM section_schools ss
                        JOIN partnered_schools ps ON ss.school_id = ps.id
                        WHERE ss.section_id = s.id
                    ) as school_name,
                    GROUP_CONCAT(DISTINCT wi.inspire_words SEPARATOR '|') as inspire_words,
                    GROUP_CONCAT(DISTINCT wa.affirmation_word SEPARATOR '|') as affirmation_words,
                    CONCAT(sy.school_year, ' - ', sem.semester_name) as session_name
                FROM journal j
                LEFT JOIN users u ON j.student_id = u.school_id
                LEFT JOIN sections s ON u.section_id = s.id
                LEFT JOIN words_inspire wi ON j.id = wi.journal_id
                LEFT JOIN words_affirmation wa ON j.id = wa.journal_id
                LEFT JOIN academic_sessions acs ON j.session_id = acs.academic_session_id
                LEFT JOIN school_years sy ON acs.school_year_id = sy.school_year_id
                LEFT JOIN semesters sem ON acs.semester_id = sem.semester_id
                WHERE s.id = ? 
                    $dateCondition 
                    $academicSessionCondition
                GROUP BY j.id, j.student_id, j.week, j.grateful, j.proud_of, j.look_forward, j.felt_this_week, 
                         j.createdAt, u.firstname, u.lastname, u.email, s.section_name, s.id, session_name
                ORDER BY j.week DESC, j.createdAt DESC
            ";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$section_id]);
            $journals = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Process the results to format the words arrays
            foreach ($journals as &$journal) {
                $journal['inspire_words'] = $journal['inspire_words'] ? explode('|', $journal['inspire_words']) : [];
                $journal['affirmation_words'] = $journal['affirmation_words'] ? explode('|', $journal['affirmation_words']) : [];
            }
            
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
    
    // Get detailed journal entry
    function getJournalDetails($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $journal_id = $data['journal_id'];
        
        try {
            // Get journal details
            $sql = "
                SELECT 
                    j.*,
                    u.firstname,
                    u.lastname,
                    u.email,
                    s.section_name,
                    (
                        SELECT GROUP_CONCAT(DISTINCT CONCAT(ps.name, ' (', ps.school_type, ')') SEPARATOR ', ')
                        FROM section_schools ss
                        JOIN partnered_schools ps ON ss.school_id = ps.id
                        WHERE ss.section_id = s.id
                    ) as school_name,
                    CONCAT(sy.school_year, ' - ', sem.semester_name) as session_name
                FROM journal j
                LEFT JOIN users u ON j.student_id = u.school_id
                LEFT JOIN sections s ON u.section_id = s.id
                LEFT JOIN academic_sessions acs ON j.session_id = acs.academic_session_id
                LEFT JOIN school_years sy ON acs.school_year_id = sy.school_year_id
                LEFT JOIN semesters sem ON acs.semester_id = sem.semester_id
                WHERE j.id = ?
            ";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$journal_id]);
            $journal = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$journal) {
                return json_encode([
                    'success' => false,
                    'message' => 'Journal entry not found'
                ]);
            }
            
            // Get inspire words
            $inspire_sql = "SELECT inspire_words FROM words_inspire WHERE journal_id = ?";
            $inspire_stmt = $conn->prepare($inspire_sql);
            $inspire_stmt->execute([$journal_id]);
            $inspire_words = $inspire_stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Get affirmation words
            $affirmation_sql = "SELECT affirmation_word FROM words_affirmation WHERE journal_id = ?";
            $affirmation_stmt = $conn->prepare($affirmation_sql);
            $affirmation_stmt->execute([$journal_id]);
            $affirmation_words = $affirmation_stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $journal['inspire_words'] = $inspire_words;
            $journal['affirmation_words'] = $affirmation_words;
            
            return json_encode([
                'success' => true,
                'data' => $journal
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Get academic sessions for filters
    function getAcademicSessions($json) {
        include "connection.php";
        
        try {
            $sql = "
                SELECT 
                    acs.academic_session_id, 
                    acs.is_Active,
                    sy.school_year,
                    s.semester_name,
                    CONCAT(sy.school_year, ' - ', s.semester_name) as session_name
                FROM academic_sessions acs
                LEFT JOIN school_years sy ON acs.school_year_id = sy.school_year_id
                LEFT JOIN semesters s ON acs.semester_id = s.semester_id
                ORDER BY acs.is_Active DESC, sy.school_year DESC, s.semester_id
            ";
            
            $stmt = $conn->prepare($sql);
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
    
    // Get sections for filters
    function getSections($json) {
        include "connection.php";
        
        try {
            $sql = "
                SELECT 
                    s.id, 
                    s.section_name, 
                    GROUP_CONCAT(DISTINCT CONCAT(ps.name, ' (', ps.school_type, ')') SEPARATOR ', ') as school_name 
                FROM sections s
                LEFT JOIN section_schools ss ON s.id = ss.section_id
                LEFT JOIN partnered_schools ps ON ss.school_id = ps.id
                GROUP BY s.id, s.section_name
                ORDER BY s.section_name
            ";
            
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
    
    // Get coordinator's journal entries (section-specific)
    function getCoordinatorJournals($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $coordinator_id = $data['coordinator_id'];
        $filters = $data['filters'] ?? [];
        $dateFilter = $filters['dateRange'] ?? 'this_week';
        $academicSessionFilter = $filters['academicSession'] ?? 'all';
        
        try {
            // First get coordinator's section_id from users table
            $section_sql = "SELECT section_id FROM users WHERE school_id = ? AND level_id = 3 LIMIT 1";
            $section_stmt = $conn->prepare($section_sql);
            $section_stmt->execute([$coordinator_id]);
            $section_result = $section_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$section_result || !$section_result['section_id']) {
                return json_encode([
                    'success' => false,
                    'message' => 'Coordinator section not found'
                ]);
            }
            
            $section_id = $section_result['section_id'];
            
            // Build date condition
            $dateCondition = $this->buildJournalDateCondition($dateFilter, $filters);
            
            // Build academic session condition
            $academicSessionCondition = '';
            if ($academicSessionFilter !== 'all') {
                $academicSessionCondition = "AND j.session_id = " . intval($academicSessionFilter);
            }
            
            // Query to get journal entries for coordinator's section
            $sql = "
                SELECT 
                    j.id,
                    j.student_id,
                    j.week,
                    j.grateful,
                    j.proud_of,
                    j.look_forward,
                    j.felt_this_week,
                    j.createdAt,
                    u.firstname,
                    u.lastname,
                    u.email,
                    CONCAT(sy.school_year, ' - ', sem.semester_name) as session_name
                FROM journal j
                LEFT JOIN users u ON j.student_id = u.school_id
                LEFT JOIN academic_sessions acs ON j.session_id = acs.academic_session_id
                LEFT JOIN school_years sy ON acs.school_year_id = sy.school_year_id
                LEFT JOIN semesters sem ON acs.semester_id = sem.semester_id
                WHERE u.section_id = ? 
                    $dateCondition 
                    $academicSessionCondition
                ORDER BY j.week DESC, j.createdAt DESC
            ";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$section_id]);
            $journals = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get statistics
            $stats_sql = "
                SELECT 
                    COUNT(DISTINCT u.school_id) as total_students,
                    COUNT(DISTINCT j.student_id) as submitted_count,
                    CASE 
                        WHEN COUNT(DISTINCT u.school_id) = 0 THEN 0
                        ELSE ROUND(
                            (COUNT(DISTINCT j.student_id) * 100.0) / 
                            COUNT(DISTINCT u.school_id), 2
                        )
                    END as submission_rate,
                    MAX(CAST(j.week AS UNSIGNED)) as latest_week
                FROM users u
                LEFT JOIN journal j ON u.school_id = j.student_id 
                    $dateCondition 
                    $academicSessionCondition
                WHERE u.section_id = ? AND u.level_id = 4 AND u.isApproved = 1
            ";
            
            $stats_stmt = $conn->prepare($stats_sql);
            $stats_stmt->execute([$section_id]);
            $statistics = $stats_stmt->fetch(PDO::FETCH_ASSOC);
            
            return json_encode([
                'success' => true,
                'data' => $journals,
                'statistics' => $statistics
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }

// Helper function to build date condition for journals
    private function buildJournalDateCondition($dateFilter, $filters) {
        switch ($dateFilter) {
            case 'today':
                return "AND DATE(j.createdAt) = CURDATE()";
            case 'this_week':
                return "AND YEARWEEK(j.createdAt) = YEARWEEK(CURDATE())";
            case 'last_week':
                return "AND YEARWEEK(j.createdAt) = YEARWEEK(CURDATE() - INTERVAL 1 WEEK)";
            case 'this_month':
                return "AND MONTH(j.createdAt) = MONTH(CURDATE()) AND YEAR(j.createdAt) = YEAR(CURDATE())";
            case 'last_month':
                return "AND MONTH(j.createdAt) = MONTH(CURDATE() - INTERVAL 1 MONTH) AND YEAR(j.createdAt) = YEAR(CURDATE() - INTERVAL 1 MONTH)";
            case 'custom':
                if (isset($filters['startDate']) && isset($filters['endDate'])) {
                    return "AND DATE(j.createdAt) BETWEEN '" . $filters['startDate'] . "' AND '" . $filters['endDate'] . "'";
                }
                return "";
            default:
                return "";
        }
    }
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    $action = $data['action'] ?? '';
    
    $journal = new Journal();
    
    switch ($action) {
        case 'get_section_overview':
            echo $journal->getSectionOverview($json);
            break;
        case 'get_section_journals':
            echo $journal->getSectionJournals($json);
            break;
        case 'get_coordinator_journals':
            echo $journal->getCoordinatorJournals($json);
            break;
        case 'get_journal_details':
            echo $journal->getJournalDetails($json);
            break;
        case 'get_academic_sessions':
            echo $journal->getAcademicSessions($json);
            break;
        case 'get_sections':
            echo $journal->getSections($json);
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
