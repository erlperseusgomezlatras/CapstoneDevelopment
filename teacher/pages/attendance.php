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
$current_page = 'attendance';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management | PHINMA Practicum Management System</title>
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
            background-color: rgba(255, 255, 255, 0.1);
            color: rgb(255, 255, 255);
            border-left: 4px solid rgb(255, 255, 255);
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
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Mobile Header -->
            <header class="bg-white shadow-sm border-b border-gray-200 md:hidden">
                <div class="px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center h-16">
                        <div class="flex items-center space-x-3">
                            <button id="mobileMenuBtn" class="p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                                <i class="fas fa-bars h-5 w-5"></i>
                            </button>
                            <img src="../../assets/images/logo_college.png" class="h-8" alt="College Logo">
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
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto bg-gray-50">
                <div class="px-4 sm:px-6 lg:px-8 py-8">
                    <!-- Page Header -->
                    <div class="mb-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-2">
                            Attendance Management
                        </h2>
                        <p class="text-gray-600">
                            Monitor and manage student attendance across all sections with detailed filtering options.
                        </p>
                    </div>

                    <!-- Filter Section -->
                    <div class="bg-white rounded-lg shadow mb-6">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-filter text-blue-500 mr-2"></i>
                                Filter Options
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                                    <select id="dateFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="today">Today</option>
                                        <option value="week">This Week</option>
                                        <option value="month">This Month</option>
                                        <option value="custom">Custom Range</option>
                                        <option value="all">All Time</option>
                                    </select>
                                </div>
                                <div id="customDateFrom" class="hidden">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                                    <input type="date" id="fromDate" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div id="customDateTo" class="hidden">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                                    <input type="date" id="toDate" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Academic Session</label>
                                    <select id="academicSessionFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="all">All Academic Sessions</option>
                                        <!-- Options will be loaded dynamically by JavaScript -->
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Section</label>
                                    <select id="sectionFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="all">All Sections</option>
                                    </select>
                                </div>
                                <div class="flex items-end">
                                    <div class="text-sm text-gray-500 italic">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Filters apply automatically
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Attendance Overview -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-users text-blue-500 mr-2"></i>
                                Section Attendance Overview
                            </h3>
                            <p class="text-sm text-gray-600 mt-1">
                                <span id="current-session">2025-2026</span> OJT Students - Attendance Summary
                            </p>
                        </div>
                        <div class="p-6">
                            <div id="section-overview" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div class="text-center py-8 text-gray-500 col-span-full">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    <p class="mt-2">Loading section data...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Student Attendance Details -->
                    <div id="studentAttendanceSection" class="bg-white rounded-lg shadow mt-6 hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                    <i class="fas fa-user-clock text-green-500 mr-2"></i>
                                    Student Attendance Details
                                    <span id="sectionName" class="ml-2 text-sm bg-blue-100 text-blue-800 px-2 py-1 rounded-full"></span>
                                </h3>
                                <button onclick="closeStudentDetails()" class="text-gray-500 hover:text-gray-700">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student Name</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student ID</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">School</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rendered Hours</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Required Hours</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remaining Hours</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="studentAttendanceTable" class="bg-white divide-y divide-gray-200">
                                        <!-- Student data will be populated here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../js/attendance.js"></script>
    <script>
        // Mobile menu toggle
        document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            document.getElementById('mobileSidebar').classList.remove('hidden');
        });

        document.getElementById('closeMobileSidebar').addEventListener('click', function() {
            document.getElementById('mobileSidebar').classList.add('hidden');
        });

        document.getElementById('mobileSidebarOverlay').addEventListener('click', function() {
            document.getElementById('mobileSidebar').classList.add('hidden');
        });

        // Custom date change handlers - auto-apply
        document.getElementById('fromDate').addEventListener('change', function() {
            if (document.getElementById('dateFilter').value === 'custom') {
                applyFilters();
            }
        });

        document.getElementById('toDate').addEventListener('change', function() {
            if (document.getElementById('dateFilter').value === 'custom') {
                applyFilters();
            }
        });

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Select2 for date filter
            $('#dateFilter').select2({
                placeholder: 'Select Date Range',
                allowClear: false,
                width: '100%'
            });
            
            // Load data (academic sessions will trigger initial load when active session is set)
            loadSections();
            loadAcademicSessions();
            
            // Add change event listeners for Select2 dropdowns
            $('#academicSessionFilter').on('change', function() {
                applyFilters();
            });
            
            $('#sectionFilter').on('change', function() {
                applyFilters();
            });
            
            $('#dateFilter').on('change', function() {
                handleDateRangeChange();
                applyFilters();
            });
        });
        
        // Handle date range change for custom dates
        function handleDateRangeChange() {
            const dateFilter = document.getElementById('dateFilter').value;
            const customDateFrom = document.getElementById('customDateFrom');
            const customDateTo = document.getElementById('customDateTo');
            
            if (dateFilter === 'custom') {
                customDateFrom.classList.remove('hidden');
                customDateTo.classList.remove('hidden');
            } else {
                customDateFrom.classList.add('hidden');
                customDateTo.classList.add('hidden');
            }
        }
    </script>
</body>
</html>