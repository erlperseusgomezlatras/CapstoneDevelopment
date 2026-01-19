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
        'level' => $_SESSION['user_role'] ?? 'Coordinator',
        'firstname' => $_SESSION['first_name'] ?? 'Coordinator',
        'email' => $_SESSION['email'] ?? '',
        'school_id' => $_SESSION['school_id'] ?? ''
    ];
} else {
    // Use cookie data
    $userData = json_decode($_COOKIE['userData'], true);
}

// Verify user is a coordinator
if ($userData['level'] !== 'Coordinator') {
    header('Location: ../login.php');
    exit();
}

$coordinator_name = $userData['firstname'] ?? 'Coordinator';
$coordinator_email = $userData['email'] ?? '';
$coordinator_id = $userData['school_id'] ?? '';

// Set current page for sidebar
$current_page = 'dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coordinator Dashboard | PHINMA Practicum Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Custom animations and transitions */
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .sidebar-item {
            transition: all 0.2s ease;
        }
        .sidebar-item:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }
        .sidebar-item.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: rgb(255, 255, 255);
            border-left: 4px solid rgb(255, 255, 255);
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex">
        <?php require_once 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Mobile Header -->
            <header class="bg-white shadow-sm border-b border-gray-200 md:hidden">
                <div class="px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center h-16">
                        <div class="flex items-center space-x-3">
                            <button id="mobileMenuBtn" class="p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                                <i class="fas fa-bars h-5 w-5"></i>
                            </button>
                            <img src="../assets/images/logo_college.png" class="h-8" alt="College Logo">
                        </div>
                    </div>
                </div>
            </header>

            <!-- Desktop Header -->
            <header class="bg-white shadow-sm border-b border-gray-200 hidden md:block">
                <div class="px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center h-16">
                        <div class="flex items-center">
                            <img src="../assets/images/logo_college.png" class="h-10" alt="College Logo">
                        </div>
                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-600">Welcome, <?php echo htmlspecialchars($coordinator_name); ?></span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto bg-gray-50">
                <div class="px-4 sm:px-6 lg:px-8 py-8">
                    <!-- Welcome Section -->
                    <div class="mb-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-2">
                            Welcome to Coordinator Portal
                        </h2>
                        <p class="text-gray-600">
                            Monitor and verify student attendance, review journal submissions, and track practicum compliance for your assigned sections.
                        </p>
                    </div>

                    <!-- Dashboard Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                        <div class="card-hover bg-white p-6 rounded-lg shadow cursor-pointer" onclick="loadStudents()">
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                                <i class="fas fa-user-graduate text-blue-600 text-xl"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Student Management</h3>
                            <p class="text-gray-600 text-sm mb-4">View and manage students in your assigned sections</p>
                            <button class="w-full px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                Manage Students
                            </button>
                        </div>

                        <div class="card-hover bg-white p-6 rounded-lg shadow cursor-pointer" onclick="loadAttendance()">
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                                <i class="fas fa-clock text-green-600 text-xl"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Attendance Monitoring</h3>
                            <p class="text-gray-600 text-sm mb-4">Verify attendance accuracy and completeness</p>
                            <button class="w-full px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                View Attendance
                            </button>
                        </div>

                        <div class="card-hover bg-white p-6 rounded-lg shadow cursor-pointer" onclick="loadJournals()">
                            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
                                <i class="fas fa-book text-purple-600 text-xl"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Journal Review</h3>
                            <p class="text-gray-600 text-sm mb-4">Review and approve practicum journal submissions</p>
                            <button class="w-full px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                Review Journals
                            </button>
                        </div>

                        <div class="card-hover bg-white p-6 rounded-lg shadow cursor-pointer" onclick="loadChecklist()">
                            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mb-4">
                                <i class="fas fa-tasks text-orange-600 text-xl"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Practicum Checklist</h3>
                            <p class="text-gray-600 text-sm mb-4">Track practicum checklist completion</p>
                            <button class="w-full px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                View Checklists
                            </button>
                        </div>

                        <div class="card-hover bg-white p-6 rounded-lg shadow cursor-pointer" onclick="loadReports()">
                            <div class="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center mb-4">
                                <i class="fas fa-chart-line text-teal-600 text-xl"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Reports</h3>
                            <p class="text-gray-600 text-sm mb-4">Generate compliance and progress reports</p>
                            <button class="w-full px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                View Reports
                            </button>
                        </div>

                        <div class="card-hover bg-white p-6 rounded-lg shadow cursor-pointer" onclick="loadSchoolInfo()">
                            <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-4">
                                <i class="fas fa-school text-indigo-600 text-xl"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Partnered School</h3>
                            <p class="text-gray-600 text-sm mb-4">View assigned partnered school information</p>
                            <button class="w-full px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                School Details
                            </button>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-xl font-semibold text-gray-900 mb-6">Coordinator Overview</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div class="text-center">
                                <div class="text-3xl font-bold text-blue-600 mb-2" id="totalStudents">-</div>
                                <div class="text-gray-600">Total Students</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-green-600 mb-2" id="totalSections">-</div>
                                <div class="text-gray-600">Assigned Sections</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-purple-600 mb-2" id="attendanceToday">-</div>
                                <div class="text-gray-600">Today's Attendance</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-orange-600 mb-2" id="pendingJournals">-</div>
                                <div class="text-gray-600">Pending Journals</div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Mobile sidebar functionality
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileSidebar = document.getElementById('mobileSidebar');
        const mobileSidebarOverlay = document.getElementById('mobileSidebarOverlay');
        const closeMobileSidebar = document.getElementById('closeMobileSidebar');

        function openMobileSidebar() {
            mobileSidebar.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeMobileSidebarFunc() {
            mobileSidebar.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        mobileMenuBtn.addEventListener('click', openMobileSidebar);
        closeMobileSidebar.addEventListener('click', closeMobileSidebarFunc);
        mobileSidebarOverlay.addEventListener('click', closeMobileSidebarFunc);

        // Load dashboard statistics
        function loadDashboardStats() {
            const coordinatorId = '<?php echo $coordinator_id; ?>';
            
            fetch('<?php 
                $base_url = dirname(dirname($_SERVER['SCRIPT_NAME']));
                if ($base_url == '/') $base_url = '';
                $base_url = 'http://' . $_SERVER['HTTP_HOST'] . $base_url;
                echo $base_url; 
            ?>/api/coordinator.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    operation: 'get_dashboard_stats',
                    json: JSON.stringify({ coordinator_id: coordinatorId })
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    const data = result.data;
                    document.getElementById('totalStudents').textContent = data.total_students;
                    document.getElementById('totalSections').textContent = data.total_sections;
                    document.getElementById('attendanceToday').textContent = data.attendance_today;
                    document.getElementById('pendingJournals').textContent = data.pending_journals;
                } else {
                    console.error('Failed to load stats:', result.message);
                }
            })
            .catch(error => {
                console.error('Error loading stats:', error);
            });
        }

        // Navigation functions
        <?php 
            $base_url = dirname(dirname($_SERVER['SCRIPT_NAME']));
            if ($base_url == '/') $base_url = '';
            $base_url = 'http://' . $_SERVER['HTTP_HOST'] . $base_url;
        ?>
        function loadStudents() {
            window.location.href = '<?php echo $base_url; ?>/coordinator/pages/student.php';
        }

        function loadAttendance() {
            window.location.href = '<?php echo $base_url; ?>/coordinator/attendance.php';
        }

        function loadJournals() {
            window.location.href = '<?php echo $base_url; ?>/coordinator/journals.php';
        }

        function loadReports() {
            window.location.href = '<?php echo $base_url; ?>/coordinator/reports.php';
        }

        function loadChecklist() {
            window.location.href = '<?php echo $base_url; ?>/coordinator/checklist.php';
        }

        function loadSchoolInfo() {
            window.location.href = '<?php echo $base_url; ?>/coordinator/school.php';
        }

        // Load stats on page load
        document.addEventListener('DOMContentLoaded', loadDashboardStats);
    </script>
</body>
</html>