<?php
// Include configuration
require_once '../../config/config.php';

$current_page = 'checklist';

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
$coordinator_email = $userData['email'] ?? '';
$coordinator_id = $userData['school_id'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Checklist | PHINMA Practicum Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Select2 CSS and JS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- Axios -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    
    <!-- App Configuration -->
    <script src="../../assets/js/config.js"></script>
    
    <style>
        /* Select2 custom styling to match Tailwind */
        .select2-container--default .select2-selection--single {
            height: 48px !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0.5rem !important;
            padding: 0.75rem 1rem !important;
            display: flex !important;
            align-items: center !important;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 1.5 !important;
            padding-left: 0 !important;
            color: #111827 !important;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 46px !important;
            right: 10px !important;
        }
        
        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #058643 !important;
            box-shadow: 0 0 0 3px rgba(5, 134, 67, 0.1) !important;
        }
        
        .select2-dropdown {
            border: 1px solid #d1d5db !important;
            border-radius: 0.5rem !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
        }
        
        .select2-search--dropdown .select2-search__field {
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            padding: 0.5rem !important;
        }
        
        .select2-search--dropdown .select2-search__field:focus {
            border-color: #058643 !important;
            outline: none !important;
        }
        
        .select2-results__option {
            padding: 8px 12px !important;
            color: #374151 !important;
            font-size: 14px !important;
        }
        
        .select2-results__option--highlighted {
            background-color: #058643 !important;
            color: white !important;
        }
        
        .select2-results__option[aria-selected="true"] {
            background-color: #eff6ff !important;
            color: #1e40af !important;
        }
        
        /* Checklist item styling */
        .checklist-item {
            transition: all 0.2s ease;
        }
        
        .checklist-item:hover {
            background-color: #f9fafb;
        }
        
        .checklist-item.completed {
            background-color: #f0fdf4;
            border-left: 4px solid #22c55e;
        }
        
        /* Card styling */
        .filter-card, .student-card, .checklist-card {
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
        }
        .filter-card:hover, .student-card:hover, .checklist-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-1px);
        }
        
        /* Alert styling */
        .alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            padding: 16px 24px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            animation: slideIn 0.3s ease;
        }
        
        .alert-success {
            background-color: #22c55e;
        }
        
        .alert-danger {
            background-color: #ef4444;
        }
        
        .alert-warning {
            background-color: #f59e0b;
        }
        
        .alert-info {
            background-color: #3b82f6;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex">
        <?php require_once '../sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden md:ml-72">
            <!-- Mobile Header -->
            <header class="bg-white shadow-sm border-b border-gray-200 md:hidden">
                <div class="px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center h-16">
                        <div class="flex items-center space-x-3">
                            <button id="mobileMenuBtn" class="p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                                <i class="fas fa-bars h-5 w-5"></i>
                            </button>
                            <h1 class="text-lg font-semibold text-gray-900">Student Checklist</h1>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-600">Welcome, <?php echo htmlspecialchars($coordinator_name); ?></span>
                            <div class="w-8 h-8 bg-green-600 rounded-full flex items-center justify-center">
                                <span class="text-white text-sm font-medium"><?php echo strtoupper(substr($coordinator_name, 0, 1)); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Desktop Header -->
            <header class="bg-white shadow-sm border-b border-gray-200 hidden md:block">
                <div class="px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center h-16">
                        <div>
                            <h1 class="text-xl font-semibold text-gray-900">Student Checklist</h1>
                            <p class="text-sm text-gray-600">Perform weekly checklist evaluations for students in your sections</p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-600">Welcome, <?php echo htmlspecialchars($coordinator_name); ?></span>
                            <div class="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center">
                                <span class="text-white font-medium"><?php echo strtoupper(substr($coordinator_name, 0, 1)); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto bg-gray-50 p-4 sm:p-6 lg:p-8">
                <!-- Alert Container -->
                <div id="alertContainer"></div>
                
                <!-- Hidden coordinator ID for JavaScript -->
                <input type="hidden" id="coordinatorId" value="<?php echo htmlspecialchars($coordinator_id); ?>">
                
                <!-- Filters Section -->
                <div class="filter-card bg-white rounded-lg shadow-sm p-4 sm:p-6 mb-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Section</label>
                            <select id="sectionFilter" class="w-full px-3 py-2 sm:px-4 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm">
                                <option value="">Select Section</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Student</label>
                            <select id="studentFilter" class="w-full px-3 py-2 sm:px-4 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm" disabled>
                                <option value="">Select Student</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Academic Session</label>
                            <select id="sessionFilter" class="w-full px-3 py-2 sm:px-4 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm">
                                <option value="">Select Session</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Period</label>
                            <select id="periodFilter" class="w-full px-3 py-2 sm:px-4 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm">
                                <option value="">Select Period</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-4 flex flex-col sm:flex-row gap-3">
                        <button onclick="loadStudentChecklist()" class="w-full sm:w-auto bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-colors text-sm font-medium">
                            <i class="fas fa-search mr-2"></i>Load Checklist
                        </button>
                    </div>
                </div>
                
                <!-- Student Info Card -->
                <div id="studentInfoCard" class="student-card bg-white rounded-lg shadow-sm p-4 sm:p-6 mb-6 hidden">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900" id="studentName">Student Name</h3>
                            <p class="text-gray-600 text-sm" id="studentDetails">Student Details</p>
                        </div>
                        <div class="text-center sm:text-right">
                            <div class="text-sm text-gray-500">Current Week</div>
                            <div class="text-2xl font-bold text-green-600" id="currentWeek">Week 1</div>
                        </div>
                    </div>
                </div>
                
                <!-- Checklist Items -->
                <div id="checklistContainer" class="checklist-card bg-white rounded-lg shadow-sm p-4 sm:p-6 hidden">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Checklist Items</h3>
                        <div class="flex flex-col sm:flex-row items-center gap-3 sm:gap-4">
                            <span class="text-sm text-gray-500" id="checklistProgress">0/0 completed</span>
                            <button onclick="saveChecklistResults()" class="w-full sm:w-auto bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-colors text-sm font-medium" id="saveButton">
                                <i class="fas fa-save mr-2"></i>Save Results
                            </button>
                        </div>
                    </div>
                    
                    <div id="checklistItems" class="space-y-3 sm:space-y-4">
                        <!-- Checklist items will be loaded here -->
                    </div>
                </div>
                
                <!-- No Results Message -->
                <div id="noResultsMessage" class="bg-white rounded-lg shadow-sm p-8 sm:p-12 text-center hidden">
                    <i class="fas fa-clipboard-check text-4xl sm:text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">No Checklist Available</h3>
                    <p class="text-gray-600 text-sm">Select a student and period to view checklist items</p>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Scripts -->
    <!-- Global JavaScript Variables -->
    <script>
        const coordinatorId = '<?php echo $coordinator_id; ?>';
    </script>
    
    <script src="../js/checklist.js"></script>
</body>
</html>
