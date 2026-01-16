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
                            Manage the entire practicum system with comprehensive administrative tools for accounts, configurations, and oversight.
                        </p>
                    </div>

                    <!-- Dashboard Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                        <div class="card-hover bg-white p-6 rounded-lg shadow cursor-pointer">
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                                <i class="fas fa-user-tie text-green-600 text-xl"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Teacher Management</h3>
                            <p class="text-gray-600 text-sm mb-4">Create, update, and deactivate teacher accounts</p>
                            <button class="w-full px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                Manage Teachers
                            </button>
                        </div>

                        <div class="card-hover bg-white p-6 rounded-lg shadow cursor-pointer">
                            <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center mb-4">
                                <i class="fas fa-user-check text-emerald-600 text-xl"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Coordinator Management</h3>
                            <p class="text-gray-600 text-sm mb-4">Create, update, and deactivate coordinator accounts</p>
                            <button class="w-full px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                Manage Coordinators
                            </button>
                        </div>

                        <div class="card-hover bg-white p-6 rounded-lg shadow cursor-pointer">
                            <div class="w-12 h-12 bg-lime-100 rounded-lg flex items-center justify-center mb-4">
                                <i class="fas fa-cogs text-lime-600 text-xl"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">System Configuration</h3>
                            <p class="text-gray-600 text-sm mb-4">Academic year, practicum term, and system settings</p>
                            <button class="w-full px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                Configure System
                            </button>
                        </div>

                        <div class="card-hover bg-white p-6 rounded-lg shadow cursor-pointer">
                            <div class="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center mb-4">
                                <i class="fas fa-shield-alt text-teal-600 text-xl"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Security & Permissions</h3>
                            <p class="text-gray-600 text-sm mb-4">Assign roles and manage access permissions</p>
                            <button class="w-full px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                Manage Security
                            </button>
                        </div>

                        <div class="card-hover bg-white p-6 rounded-lg shadow cursor-pointer">
                            <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center mb-4">
                                <i class="fas fa-database text-green-700 text-xl"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Data & Backups</h3>
                            <p class="text-gray-600 text-sm mb-4">Maintain system security, backups, and data integrity</p>
                            <button class="w-full px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                Manage Data
                            </button>
                        </div>

                        <div class="card-hover bg-white p-6 rounded-lg shadow cursor-pointer">
                            <div class="w-12 h-12 bg-emerald-50 rounded-lg flex items-center justify-center mb-4">
                                <i class="fas fa-graduation-cap text-emerald-700 text-xl"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Practicum Subjects</h3>
                            <p class="text-gray-600 text-sm mb-4">Manage and oversee all practicum subjects</p>
                            <button class="w-full px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                Manage Subjects
                            </button>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-xl font-semibold text-gray-900 mb-6">System Overview</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div class="text-center">
                                <div class="text-3xl font-bold text-green-600 mb-2">0</div>
                                <div class="text-gray-600">Total Teachers</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-emerald-600 mb-2">0</div>
                                <div class="text-gray-600">Total Coordinators</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-lime-600 mb-2">0</div>
                                <div class="text-gray-600">Active Sections</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-teal-600 mb-2">100%</div>
                                <div class="text-gray-600">System Health</div>
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

        // Add click handlers to dashboard cards
        document.querySelectorAll('.card-hover').forEach(card => {
            card.addEventListener('click', function() {
                const button = this.querySelector('button');
                if (button) {
                    // You can add navigation logic here
                    console.log('Card clicked:', this.querySelector('h3').textContent);
                }
            });
        });

        // Add click handlers to sidebar buttons
        document.querySelectorAll('.card-hover button').forEach(button => {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                // You can add navigation logic here
                console.log('Button clicked:', this.textContent);
            });
        });
    </script>
</body>
</html>