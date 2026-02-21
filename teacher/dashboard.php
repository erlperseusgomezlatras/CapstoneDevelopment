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
    header('Location: ../login.php');
    exit();
}

$teacher_name = $userData['firstname'] ?? 'Teacher';
$teacher_email = $userData['email'] ?? '';

// Set current page for sidebar
$current_page = 'dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard | PHINMA Practicum Management System</title>
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
            background-color: #006633;
            color: rgb(255, 255, 255);
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex">
        <?php require_once 'sidebar.php'; ?>

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
                            <span class="text-sm text-gray-600">Welcome, <?php echo htmlspecialchars($teacher_name); ?></span>
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
                            Welcome to Head Teacher Portal
                        </h2>
                        <p class="text-gray-600">
                            Monitor recent activities and manage student attendance across all sections.
                        </p>
                    </div>

                    <!-- Recent Activity Section -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                        <!-- Recent Student Registration Approval Request -->
                        <div class="bg-white rounded-lg shadow">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                    <i class="fas fa-user-check text-yellow-500 mr-2"></i>
                                    Recent Student Registration Requests
                                </h3>
                                <p class="text-sm text-gray-600 mt-1">Pending approval requests</p>
                            </div>
                            <div class="p-6">
                                <div id="recent-registrations" class="space-y-2">
                                    <div class="text-center py-4 text-gray-500">
                                        <i class="fas fa-spinner fa-spin"></i>
                                        <p class="mt-2">Loading...</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Latest Attendance Logs (Realtime) -->
                        <div class="bg-white rounded-lg shadow">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                            <i class="fas fa-clock text-green-500 mr-2"></i>
                                            Latest Attendance Logs
                                            <span class="ml-2 text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">Live</span>
                                        </h3>
                                        <p class="text-sm text-gray-600 mt-1">Today's attendance activities</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs text-gray-500">Academic Session</p>
                                        <p id="attendance-session-info" class="text-sm font-semibold text-blue-600">Loading...</p>
                                    </div>
                                </div>
                            </div>
                            <div class="p-6">
                                <div id="attendance-logs" class="space-y-2">
                                    <div class="text-center py-4 text-gray-500">
                                        <i class="fas fa-spinner fa-spin"></i>
                                        <p class="mt-2">Loading...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Student Attendance Overview -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-users text-blue-500 mr-2"></i>
                                Section Student Attendance Overview
                            </h3>
                            <p class="text-sm text-gray-600 mt-1">
                                <span id="current-session">2025-2026</span> OJT Students - Day 1
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
                </div>
            </main>
        </div>
    </div>

    <!-- Section Details Modal -->
    <div id="sectionDetailsModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex justify-center items-center">
        <div class="relative p-5 border w-11/12 md:w-2/3 lg:w-1/2 xl:w-2/5 shadow-lg rounded-lg bg-white mx-4 my-8">
            <div class="flex justify-between items-center pb-3 border-b border-gray-200 mb-4">
                <h3 id="sectionModalTitle" class="text-xl font-semibold text-gray-900"></h3>
                <button id="closeModalBtn" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            <div id="sectionModalBody" class="max-h-[70vh] overflow-y-auto pr-2">
                <!-- Content will be dynamically loaded here -->
            </div>
        </div>
    </div>

    <!-- Include dashboard.js -->
    <script src="js/dashboard.js"></script>

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

        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', openMobileSidebar);
        }
        if (closeMobileSidebar) {
            closeMobileSidebar.addEventListener('click', closeMobileSidebarFunc);
        }
        if (mobileSidebarOverlay) {
            mobileSidebarOverlay.addEventListener('click', closeMobileSidebarFunc);
        }
    </script>
</body>
</html>