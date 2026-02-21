<?php
// Include configuration
require_once '../../config/config.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !(isset($_COOKIE['authToken']) && isset($_COOKIE['userData']))) {
    header('Location: ../../login.php');
    exit();
}

// Get user data from session or cookie
$userData = null;
if (isset($_SESSION['user_id'])) {
    // Use session data if available
    $userData = [
        'level' => $_SESSION['user_role'] ?? 'Head Teacher',
        'firstname' => $_SESSION['first_name'] ?? 'Teacher',
        'email' => $_SESSION['email'] ?? ''
    ];
} else {
    // Use cookie data
    $userData = json_decode($_COOKIE['userData'], true);
}

// Verify user is a teacher/coordinator
if (!in_array($userData['level'], ['Head Teacher', 'Coordinator'])) {
    header('Location: ../../login.php');
    exit();
}

$teacher_name = $userData['firstname'] ?? 'Teacher';
$teacher_email = $userData['email'] ?? '';

// Set current page for sidebar
$current_page = 'journal';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Journal Management | PHINMA Practicum Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- jQuery (required for Select2) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Select2 CSS and JS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
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
            background-color: #006633;
            color: rgb(255, 255, 255);
        }
        
        /* Select2 custom styling to match Tailwind */
        .select2-container--default .select2-selection--single {
            height: 38px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            outline: none;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        .select2-container--default .select2-selection--single:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
            padding-left: 12px;
            padding-right: 12px;
            color: #374151;
            font-size: 14px;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
            right: 8px;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__arrow b {
            border-color: #6b7280 transparent transparent transparent;
            border-width: 6px 6px 0 6px;
        }
        
        .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
            border-color: transparent transparent #6b7280 transparent;
            border-width: 0 6px 6px 6px;
        }
        
        .select2-dropdown {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .select2-results__option {
            padding: 8px 12px;
            color: #374151;
            font-size: 14px;
        }
        
        .select2-results__option--highlighted {
            background-color: #3b82f6;
            color: white;
        }
        
        .select2-results__option[aria-selected="true"] {
            background-color: #eff6ff;
            color: #1e40af;
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
                            <h1 class="text-lg font-semibold text-gray-900">Journal Management</h1>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-600">Welcome, <?php echo htmlspecialchars($teacher_name); ?></span>
                            <div class="w-8 h-8 bg-green-600 rounded-full flex items-center justify-center">
                                <span class="text-white text-sm font-medium"><?php echo strtoupper(substr($teacher_name, 0, 1)); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Desktop Header -->
            <header class="bg-white shadow-sm border-b border-gray-200 hidden md:block">
                <div class="px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center h-16">
                        <div class="flex items-center">
                            <img src="../../assets/images/logo_college.png" class="h-10" alt="College Logo">
                        </div>
                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-600">Welcome, <?php echo htmlspecialchars($teacher_name); ?></span>
                            <div class="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center">
                                <span class="text-white text-lg font-medium"><?php echo strtoupper(substr($teacher_name, 0, 1)); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto bg-gray-50 p-4 sm:p-6 lg:p-8">
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">Journal Management</h1>
                    <p class="text-sm text-gray-600 mt-1">Monitor and review student journal entries by section</p>
                </div>
                
                <!-- Filters Section -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                            <select id="dateRange" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="this_week">This Week</option>
                                <option value="last_week">Last Week</option>
                                <option value="this_month">This Month</option>
                                <option value="last_month">Last Month</option>
                                <option value="custom">Custom Range</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Section</label>
                            <select id="sectionFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="all">All Sections</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Academic Session</label>
                            <select id="academicSessionFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="all">All Sessions</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Custom Date Range (hidden by default) -->
                    <div id="customDateRange" class="hidden grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                            <input type="date" id="startDate" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                            <input type="date" id="endDate" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                </div>

                <!-- Section Overview Cards -->
                <div id="sectionOverview" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                    <!-- Section cards will be loaded here -->
                </div>

                <!-- Section Details Modal -->
                <div id="sectionDetailsModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" onclick="closeSectionDetails()"></div>
                        
                        <div class="relative inline-block w-full max-w-6xl text-left align-middle transition-all transform bg-white shadow-xl rounded-lg" style="height: 80vh; max-height: 700px;">
                            <div class="flex justify-between items-center p-6 border-b">
                                <h3 id="modalSectionName" class="text-lg font-medium text-gray-900"></h3>
                                <button onclick="closeSectionDetails()" class="text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            
                            <div class="p-4 border-b bg-gray-50">
                                <div class="relative">
                                    <input type="text" id="studentSearch" placeholder="Search students by name or email..." 
                                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="overflow-y-auto" style="height: calc(100% - 133px);">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Week</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Grateful For</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Proud Of</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Feeling</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="sectionJournalsTableBody" class="bg-white divide-y divide-gray-200">
                                        <!-- Journal entries will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Journal Details Modal -->
                <div id="journalDetailsModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" onclick="closeJournalDetails()"></div>
                        
                        <div class="relative inline-block w-full max-w-5xl text-left align-middle transition-all transform bg-white shadow-xl rounded-lg" style="height: 80vh; max-height: 900px;">
                            <div class="flex justify-between items-center p-6 border-b">
                                <h3 class="text-lg font-medium text-gray-900">Journal Entry Details</h3>
                                <button onclick="closeJournalDetails()" class="text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            
                            <div id="journalDetailsContent" class="overflow-y-auto p-6" style="height: calc(100% - 73px);">
                                <!-- Journal details will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="../js/journal.js"></script>
</body>
</html>