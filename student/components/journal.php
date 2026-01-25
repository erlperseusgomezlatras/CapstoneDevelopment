<?php
// Include configuration
require_once '../../config/config.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !(isset($_COOKIE['authToken']) && isset($_COOKIE['userData']))) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Get user data from session or cookie
$userData = null;
if (isset($_SESSION['user_id'])) {
    // Use session data if available
    $userData = [
        'level' => $_SESSION['user_role'] ?? 'Student',
        'firstname' => $_SESSION['first_name'] ?? 'Student',
        'email' => $_SESSION['email'] ?? '',
        'school_id' => $_SESSION['school_id'] ?? ''
    ];
} else {
    // Use cookie data
    $userData = json_decode($_COOKIE['userData'], true);
}

// Verify user is a student
if ($userData['level'] !== 'Student') {
    echo json_encode(['success' => false, 'message' => 'Not a student']);
    exit();
}

$student_name = $userData['firstname'] ?? 'Student';
$student_email = $userData['email'] ?? '';
$student_school_id = $userData['school_id'] ?? '';
?>

<div class="journal-template">
    <div class="journal-container">
        <div class="journal-header">
            <h1 class="journal-title">GRATITUDE JOURNAL</h1>
        </div>
        
        <div class="journal-info">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">NAME</label>
                <input type="text" id="studentName" value="<?php echo htmlspecialchars($student_name); ?>" readonly class="w-full">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">WEEK</label>
                <input type="text" id="weekNumber" readonly class="w-full">
            </div>
        </div>
        
        <form id="journalForm">
            <div class="journal-content">
                <!-- Left Column -->
                <div class="space-y-4">
                    <div class="journal-section">
                        <div class="section-title">I'M GRATEFUL FOR</div>
                        <textarea id="grateful" name="grateful" class="grateful-list w-full" placeholder="List things you're grateful for this week..." rows="6"></textarea>
                    </div>
                    
                    <div class="journal-section">
                        <div class="section-title">WORDS TO INSPIRE</div>
                        <div id="inspireWords" class="inspire-words">
                            <input type="text" name="inspire1" class="inspire-word-input" placeholder="Enter inspiring word #1">
                            <input type="text" name="inspire2" class="inspire-word-input" placeholder="Enter inspiring word #2">
                            <input type="text" name="inspire3" class="inspire-word-input" placeholder="Enter inspiring word #3">
                        </div>
                        <button type="button" id="addInspireBtn" class="add-inspire-btn">
                            <i class="fas fa-plus mr-2"></i>Add Word
                        </button>
                    </div>
                    
                    <div class="journal-section">
                        <div class="section-title">NEXT WEEK I LOOK FORWARD TO</div>
                        <input type="text" id="lookForward" name="look_forward" class="look-forward-input w-full" placeholder="What are you looking forward to next week?">
                    </div>
                </div>
                
                <!-- Right Column -->
                <div class="space-y-4">
                    <div class="journal-section">
                        <div class="section-title">SOMETHING I'M PROUD OF</div>
                        <textarea id="proudOf" name="proud_of" class="grateful-list w-full" placeholder="What are you proud of this week?" rows="4"></textarea>
                    </div>
                    
                    <div class="journal-section">
                        <div class="section-title">WORDS OF AFFIRMATION</div>
                        <div id="affirmationWords" class="affirmation-words">
                            <input type="text" name="affirmation1" class="affirmation-word-input" placeholder="Enter affirmation sentence #1">
                            <input type="text" name="affirmation2" class="affirmation-word-input" placeholder="Enter affirmation sentence #2">
                            <input type="text" name="affirmation3" class="affirmation-word-input" placeholder="Enter affirmation sentence #3">
                        </div>
                        <button type="button" id="addAffirmationBtn" class="add-affirmation-btn">
                            <i class="fas fa-plus mr-2"></i>Add Affirmation
                        </button>
                    </div>
                    
                    <div class="journal-section">
                        <div class="section-title">HOW HAVE I FELT THIS WEEK?</div>
                        <div class="feeling-scale">
                            <div class="text-center">
                                <div class="feeling-circle" data-value="Good"></div>
                                <div class="feeling-label">GOOD</div>
                            </div>
                            <div class="text-center">
                                <div class="feeling-circle" data-value="Lean toward Good"></div>
                                <div class="feeling-label">LEAN GOOD</div>
                            </div>
                            <div class="text-center">
                                <div class="feeling-circle" data-value="Middle/Neutral"></div>
                                <div class="feeling-label">NEUTRAL</div>
                            </div>
                            <div class="text-center">
                                <div class="feeling-circle" data-value="Lean toward Not Good"></div>
                                <div class="feeling-label">LEAN NOT</div>
                            </div>
                            <div class="text-center">
                                <div class="feeling-circle" data-value="Not Good"></div>
                                <div class="feeling-label">NOT GOOD</div>
                            </div>
                        </div>
                        <input type="hidden" id="feltThisWeek" name="felt_this_week">
                    </div>
                </div>
            </div>
            
            <button type="submit" class="save-btn" id="saveBtn">
                <i class="fas fa-save mr-2"></i>SAVE JOURNAL
            </button>
        </form>
    </div>
</div>

<script>
const studentSchoolId = '<?php echo $student_school_id; ?>';
</script>
