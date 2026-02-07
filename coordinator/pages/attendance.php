<?php
// Include configuration
require_once '../../config/config.php';

$current_page = 'attendance';

// Start session and check authentication
session_start();
if (!isset($_SESSION['user_id']) && !(isset($_COOKIE['authToken']) && isset($_COOKIE['userData']))) {
    header('Location: ../../login.php');
    exit();
}

// Get user data
$userData = null;
if (isset($_SESSION['user_id'])) {
    // Use session data if available
    $userData = [
        'level' => $_SESSION['user_role'] ?? 'Coordinator',
        'firstname' => $_SESSION['first_name'] ?? 'Coordinator',
        'email' => $_SESSION['email'] ?? '',
        'school_id' => $_SESSION['school_id'] ?? '',
        'section_id' => $_SESSION['section_id'] ?? ''
    ];
} else {
    // Use cookie data
    $userData = json_decode($_COOKIE['userData'], true);
}

// Verify user is a coordinator
if ($userData['level'] !== 'Coordinator') {
    header('Location: ../../login.php');
    exit();
}

$coordinator_name = $userData['firstname'] ?? 'Coordinator';
$coordinator_id = $userData['school_id'] ?? '';
$coordinator_section_id = $userData['section_id'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Monitoring | PHINMA Practicum Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Select2 CSS and JS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <style>
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        /* Select2 custom styling to match Tailwind */
        .select2-container--default .select2-selection--single {
            height: 38px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            outline: none;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
            padding-left: 12px;
            color: #374151;
            font-size: 14px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
        .select2-dropdown {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen">
        <!-- Include Sidebar -->
        <?php require_once '../sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto md:ml-72">
            <!-- Top Header -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Attendance Monitoring</h1>
                            <p class="text-sm text-gray-600 mt-1">Monitor and verify attendance records for your assigned students</p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-600">Welcome, <?php echo htmlspecialchars($coordinator_name); ?></span>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Content Area -->
            <div class="p-6">
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
                                    <option value="all" selected>All Time</option>
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
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Section</label>
                                <select id="sectionFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="all">All Assigned Sections</option>
                                </select>
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

                <!-- Student Attendance Details (Initially Hidden) -->
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

    <!-- Attendance Details Modal -->
    <div id="attendanceModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-900 bg-opacity-60 transition-opacity" onclick="closeAttendanceModal()"></div>
        
        <!-- Modal Positioner -->
        <div class="fixed inset-0 flex items-center justify-center p-4 sm:p-6">
            <!-- Modal Content -->
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl h-[90vh] flex flex-col overflow-hidden transform transition-all">
                <!-- Modal Header (Fixed) -->
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-white">
                    <h3 class="text-xl font-bold text-gray-900">Student Attendance Details</h3>
                    <button onclick="closeAttendanceModal()" class="text-gray-400 hover:text-gray-500 transition-colors p-2">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <!-- Modal Body (Scrollable) -->
                <div class="p-6 overflow-y-auto flex-1">
                    <div id="modalStudentInfo" class="mb-6 bg-blue-50 p-4 rounded-lg grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 border border-blue-100">
                        <div>
                            <span class="text-xs font-semibold text-blue-600 uppercase tracking-wider block">Student Name</span>
                            <span id="modalStudentName" class="text-sm font-medium text-gray-900">--</span>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-blue-600 uppercase tracking-wider block">Student ID</span>
                            <span id="modalStudentId" class="text-sm font-medium text-gray-900">--</span>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-blue-600 uppercase tracking-wider block">Required Hours</span>
                            <span id="modalRequiredHours" class="text-sm font-medium text-gray-900">360.00 hrs</span>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-blue-600 uppercase tracking-wider block">Current Rendered</span>
                            <span id="modalRenderedHours" class="text-sm font-bold text-green-600">-- hrs</span>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-blue-600 uppercase tracking-wider block">Remaining Hours</span>
                            <span id="modalRemainingHours" class="text-sm font-bold text-orange-600">-- hrs</span>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-lg font-semibold text-gray-900">Attendance History</h4>
                            <div class="text-xs text-gray-500 italic">
                                <i class="fas fa-info-circle mr-1"></i>
                                Live calculation for ongoing sessions
                            </div>
                        </div>

                        <div class="overflow-x-auto border border-gray-100 rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 sticky top-0 z-10">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time In</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Out</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hours</th>
                                    </tr>
                                </thead>
                                <tbody id="modalAttendanceHistory" class="bg-white divide-y divide-gray-200"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Global JavaScript Variables -->
    <script>
        const coordinatorId = '<?php echo $coordinator_id; ?>';
        const coordinatorSectionId = '<?php echo $coordinator_section_id; ?>';
    </script>
    
    <script src="../../assets/js/config.js"></script>
    <script src="../js/attendance.js"></script>
    <script>
        $(document).ready(function() {
            $('#dateFilter').select2({ width: '100%' });
            $('#academicSessionFilter').select2({ width: '100%' });
            $('#sectionFilter').select2({ width: '100%' });
            
            $('#dateFilter').on('change', function() {
                const val = $(this).val();
                if (val === 'custom') {
                    $('#customDateFrom, #customDateTo').removeClass('hidden');
                } else {
                    $('#customDateFrom, #customDateTo').addClass('hidden');
                }
                applyFilters();
            });
            
            $('#academicSessionFilter, #sectionFilter').on('change', applyFilters);
            $('#fromDate, #toDate').on('change', applyFilters);
        });
    </script>
</body>
</html>