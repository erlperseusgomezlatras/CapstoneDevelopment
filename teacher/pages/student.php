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
        'level' => $_SESSION['user_role'] ?? 'Head Teacher',
        'firstname' => $_SESSION['first_name'] ?? 'Teacher',
        'email' => $_SESSION['email'] ?? ''
    ];
} else {
    $userData = json_decode($_COOKIE['userData'], true);
}

// Verify user is a teacher
if ($userData['level'] !== 'Head Teacher') {
    header('Location: ../../login.php');
    exit();
}

$teacher_name = $userData['firstname'] ?? 'Teacher';
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
        <div class="flex-1 flex flex-col overflow-hidden md:ml-72">
            <!-- Top Header -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Student Management</h1>
                            <p class="text-sm text-gray-600 mt-1">Manage student accounts and approval requests</p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <button onclick="openCreateStudentModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center">
                                <i class="fas fa-plus mr-2"></i>
                                Create Student
                            </button>
                            <span class="text-sm text-gray-600">Welcome, <?php echo htmlspecialchars($teacher_name); ?></span>
                            <button class="p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                                <i class="fas fa-bell"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Content Area -->
            <div class="p-6">
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
    
    <!-- Create Student Modal -->
    <div id="createStudentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-[700px] shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Create New Student Account</h3>
                    <button onclick="closeCreateStudentModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form id="createStudentForm" onsubmit="createStudent(event)">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">School ID *</label>
                        <input type="text" id="school_id" name="school_id" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                        <input type="text" id="firstname" name="firstname" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                        <input type="text" id="lastname" name="lastname" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                        <input type="text" id="middlename" name="middlename" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                        <input type="email" id="email" name="email" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Must be from approved domain (@phinmaed.com)</p>
                    </div>
                    
                    <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
                        <p class="text-sm text-blue-700">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>Password:</strong> Will be automatically set to the School ID
                        </p>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Section *</label>
                        <select id="section_id" name="section_id" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select a section</option>
                        </select>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeCreateStudentModal()" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Create Student
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="../../assets/js/config.js"></script>
    <script src="../js/student.js"></script>
</body>
</html>
