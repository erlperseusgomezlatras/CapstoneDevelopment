<?php
// Include configuration
require_once '../config/config.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !(isset($_COOKIE['authToken']) && isset($_COOKIE['userData']))) {
    header('Location: ../login.php');
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
    header('Location: ../login.php');
    exit();
}

$student_name = $userData['firstname'] ?? 'Student';
$student_email = $userData['email'] ?? '';
$student_school_id = $userData['school_id'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | PHINMA Practicum Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.js"></script>
    <link href="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.css" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/student.css">
    <link rel="stylesheet" href="../assets/css/journal.css">
</head>
<body>
    <div class="min-h-screen bg-gray-100">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
            <div class="container mx-auto px-4 py-3 md:py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <img src="../assets/images/logo_college.png" alt="PHINMA Logo" class="h-8 md:h-10 mr-2 md:mr-3">
                        <div class="hidden md:block">
                            <p class="text-sm text-gray-600">Welcome back, <?php echo htmlspecialchars($student_name); ?>!</p>
                        </div>
                        <div class="md:hidden">
                            <p class="text-xs text-gray-600">Hi, <?php echo htmlspecialchars($student_name); ?></p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2 md:space-x-4">
                        <span class="hidden md:inline text-sm text-gray-600">
                            <i class="fas fa-id-card mr-2"></i>
                            <?php echo htmlspecialchars($student_school_id); ?>
                        </span>
                        <button onclick="showLogoutModal()" class="text-sm md:text-sm text-red-600 hover:text-red-800">
                            <i class="fas fa-sign-out-alt mr-1 md:mr-2"></i>
                            <span class="hidden md:inline">Logout</span>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="container mx-auto px-4 py-4 md:py-8 mobile-content">
            <!-- Desktop Tabs Navigation -->
            <div class="desktop-tabs bg-white rounded-lg shadow mb-6">
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px">
                        <button onclick="switchTab('attendance')" 
                                class="tab-button active px-6 py-3 text-sm font-medium text-green-700 hover:text-green-800 focus:outline-none focus:text-green-800">
                            <i class="fas fa-clock mr-2"></i>
                            Attendance
                        </button>
                        <button onclick="switchTab('journal')" 
                                class="tab-button px-6 py-3 text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700">
                            <i class="fas fa-book mr-2"></i>
                            Journal
                        </button>
                        <button onclick="switchTab('activity-checklist')" 
                                class="tab-button px-6 py-3 text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700">
                            <i class="fas fa-tasks mr-2"></i>
                            Activity Checklist
                        </button>
                    </nav>
                </div>
            </div>

            <!-- Attendance Tab Content -->
            <div id="attendance" class="tab-content active">
                <div id="attendanceContent">
                    <!-- Content will be loaded dynamically based on student info -->
                    <div class="bg-white rounded-lg shadow p-8 text-center">
                        <div class="spinner mx-auto mb-4"></div>
                        <p class="text-gray-600">Loading your information...</p>
                    </div>
                </div>
            </div>

            <!-- Journal Tab Content -->
            <div id="journal" class="tab-content">
                <div id="journalContent">
                    <!-- Journal component will be loaded here -->
                    <div class="bg-white rounded-lg shadow p-8 text-center">
                        <div class="spinner mx-auto mb-4"></div>
                        <p class="text-gray-600">Loading journal...</p>
                    </div>
                </div>
            </div>

            <!-- Activity Checklist Tab Content -->
            <div id="activity-checklist" class="tab-content">
                <div class="bg-white rounded-lg shadow p-8 text-center">
                    <i class="fas fa-tasks text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">Activity Checklist</h3>
                    <p class="text-gray-500">This feature is coming soon...</p>
                </div>
            </div>
        </main>
        
        <!-- Mobile Floating Navigation -->
        <nav class="mobile-nav">
            <a href="#" onclick="switchTab('attendance'); return false;" class="mobile-nav-item active" id="nav-attendance">
                <i class="fas fa-clock"></i>
                <span>Attendance</span>
            </a>
            <a href="#" onclick="switchTab('journal'); return false;" class="mobile-nav-item" id="nav-journal">
                <i class="fas fa-book"></i>
                <span>Journal</span>
            </a>
            <a href="#" onclick="switchTab('activity-checklist'); return false;" class="mobile-nav-item" id="nav-activity-checklist">
                <i class="fas fa-tasks"></i>
                <span>Tasks</span>
            </a>
        </nav>
    </div>

    <!-- Notification Container -->
    <div id="notificationContainer"></div>

    <!-- Global JavaScript Variables -->
    <script>
        const studentSchoolId = '<?php echo $student_school_id; ?>';
    </script>

    <!-- JavaScript Files -->
    <script src="../assets/js/config.js"></script>
    <script src="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.js"></script>
    <script src="js/attendance.js"></script>
    <script src="js/journal.js"></script>
    <script src="js/dashboard.js"></script>
</body>
</html>