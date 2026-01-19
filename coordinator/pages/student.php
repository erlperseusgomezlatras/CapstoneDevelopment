<?php
// Include configuration
require_once '../../config/config.php';

$current_page = 'student';

// Start session and check authentication
session_start();
if (!isset($_SESSION['user_id']) && !(isset($_COOKIE['authToken']) && isset($_COOKIE['userData']))) {
    header('Location: ../../login.php');
    exit();
}

// Get user data
$userData = null;
if (isset($_SESSION['user_id'])) {
    $userData = [
        'level' => $_SESSION['user_role'] ?? 'Coordinator',
        'firstname' => $_SESSION['first_name'] ?? 'Coordinator',
        'email' => $_SESSION['email'] ?? '',
        'school_id' => $_SESSION['school_id'] ?? '',
        'section_id' => $_SESSION['section_id'] ?? ''
    ];
} else {
    $userData = json_decode($_COOKIE['userData'], true);
}

// Verify user is a coordinator
if ($userData['level'] !== 'Coordinator') {
    header('Location: ../../login.php');
    exit();
}

$coordinator_name = $userData['firstname'] ?? 'Coordinator';
$coordinator_section_id = $userData['section_id'] ?? '';

error_log("Coordinator section_id: $coordinator_section_id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management | PHINMA Practicum Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Include Sidebar -->
        <?php require_once '../sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto">
            <!-- Top Header -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Student Management</h1>
                            <p class="text-sm text-gray-600 mt-1">Manage students in your assigned sections</p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-600">Welcome, <?php echo htmlspecialchars($coordinator_name); ?></span>
                            <button class="p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                                <i class="fas fa-bell"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Content Area -->
            <div class="p-6">
                <!-- Section Info -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-info-circle text-blue-600 mr-3"></i>
                        <div>
                            <h3 class="text-sm font-medium text-blue-900">Your Assigned Section</h3>
                            <p class="text-sm text-blue-700">Showing students from your assigned section(s) only</p>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="bg-white rounded-lg shadow">
                    <div class="flex justify-between items-center border-b border-gray-200">
                        <nav class="flex -mb-px">
                            <button onclick="showTab('pending')" id="pending-tab" class="tab-button py-3 px-6 border-b-2 border-blue-500 font-medium text-sm text-blue-600 focus:outline-none">
                                <i class="fas fa-clock mr-2"></i>
                                Pending Approval
                                <span id="pending-count" class="ml-2 bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs">0</span>
                            </button>
                            <button onclick="showTab('approved')" id="approved-tab" class="tab-button py-3 px-6 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none">
                                <i class="fas fa-check-circle mr-2"></i>
                                Approved Students
                                <span id="approved-count" class="ml-2 bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">0</span>
                            </button>
                            <button onclick="showTab('declined')" id="declined-tab" class="tab-button py-3 px-6 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none">
                                <i class="fas fa-times-circle mr-2"></i>
                                Declined Requests
                                <span id="declined-count" class="ml-2 bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs">0</span>
                            </button>
                        </nav>
                        <button onclick="refreshAllData()" class="py-3 px-4 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none" title="Refresh All Data">
                            <i id="refresh-icon" class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                    
                    <!-- Tab Content -->
                    <div class="p-6">
                        <!-- Pending Approval Tab -->
                        <div id="pending-content" class="tab-content">
                            <div id="pending-loading" class="text-center py-8">
                                <i class="fas fa-spinner fa-spin text-gray-400 text-2xl"></i>
                                <p class="text-gray-500 mt-2">Loading...</p>
                            </div>
                            <div id="pending-table" class="hidden">
                                <!-- Table will be populated by JavaScript -->
                            </div>
                        </div>
                        
                        <!-- Approved Students Tab -->
                        <div id="approved-content" class="tab-content hidden">
                            <div id="approved-loading" class="text-center py-8">
                                <i class="fas fa-spinner fa-spin text-gray-400 text-2xl"></i>
                                <p class="text-gray-500 mt-2">Loading...</p>
                            </div>
                            <div id="approved-table" class="hidden">
                                <!-- Table will be populated by JavaScript -->
                            </div>
                        </div>
                        
                        <!-- Declined Requests Tab -->
                        <div id="declined-content" class="tab-content hidden">
                            <div id="declined-loading" class="text-center py-8">
                                <i class="fas fa-spinner fa-spin text-gray-400 text-2xl"></i>
                                <p class="text-gray-500 mt-2">Loading...</p>
                            </div>
                            <div id="declined-table" class="hidden">
                                <!-- Table will be populated by JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Global JavaScript Variables -->
    <script>
        const coordinatorId = '<?php echo $userData['school_id']; ?>';
    </script>
    
    <script src="../../assets/js/config.js"></script>
    <script src="../js/student.js"></script>
</body>
</html>