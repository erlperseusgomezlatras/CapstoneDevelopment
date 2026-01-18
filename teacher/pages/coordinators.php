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
$current_page = 'coordinators';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coordinator Management | PHINMA Practicum Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/coordinator.css">
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
                            <button class="p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                                <i class="fas fa-bell"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto bg-gray-50">
                <div class="px-4 sm:px-6 lg:px-8 py-8">
                    <!-- Header Section -->
                    <div class="mb-8">
                        <div class="flex justify-between items-center">
                            <div>
                                <h2 class="text-3xl font-bold text-gray-900 mb-2">
                                    Coordinator Management
                                </h2>
                                <p class="text-gray-600">
                                    Assign coordinators to partnered schools through sections
                                </p>
                            </div>
                            <button onclick="openAddModal()" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                                <i class="fas fa-plus mr-2"></i>
                                Add New Coordinator
                            </button>
                        </div>
                    </div>

                    <!-- Search and Filter Section -->
                    <div class="bg-white p-4 rounded-lg shadow mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <input type="text" id="searchInput" placeholder="Search by name, email, or school ID..." 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            </div>
                            <div>
                                <select id="statusFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                    <option value="">All Status</option>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Coordinators Table -->
                    <div class="bg-white rounded-lg shadow overflow-hidden relative">
                        <div class="overflow-x-auto min-h-[600px] max-h-[800px] overflow-y-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            School ID
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Name
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Email
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Section
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Partnered School
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="coordinatorsTableBody" class="bg-white divide-y divide-gray-200">
                                    <!-- Coordinators will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                        <div id="noDataMessage" class="hidden absolute inset-0 flex items-center justify-center bg-white">
                            <div class="text-center text-gray-500">
                                <i class="fas fa-users text-4xl mb-4 text-gray-300"></i>
                                <p class="text-lg font-medium mb-2">No coordinators found</p>
                                <p class="text-sm text-gray-400">Start by adding your first coordinator to the system</p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add/Edit Coordinator Modal -->
    <div id="coordinatorModal" class="modal">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl w-[600px] max-w-[90vw]">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 id="modalTitle" class="text-lg font-semibold text-gray-900">Add New Coordinator</h3>
                        <button id="resetPasswordBtn" onclick="openResetPasswordModal()" 
                                class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 hidden"
                                style="display: none;">
                            <i class="fas fa-key mr-2"></i>
                            Reset Password
                        </button>
                    </div>
                    <form id="coordinatorForm">
                        <input type="hidden" id="coordinatorId" name="school_id">
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">School ID *</label>
                                <input type="text" id="schoolId" name="school_id" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                <span class="text-xs text-red-500" id="schoolIdError"></span>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                                    <input type="text" id="firstname" name="firstname" required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                    <span class="text-xs text-red-500" id="firstnameError"></span>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                                    <input type="text" id="lastname" name="lastname" required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                    <span class="text-xs text-red-500" id="lastnameError"></span>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                                <input type="text" id="middlename" name="middlename"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                                <input type="email" id="email" name="email" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                <span class="text-xs text-red-500" id="emailError"></span>
                            </div>
                            
                            <div id="passwordField">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                                <div class="flex space-x-2">
                                    <div class="relative flex-1">
                                        <input type="password" id="password" name="password" required
                                               class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                        <button type="button" onclick="togglePasswordVisibility()" 
                                                class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-gray-700"
                                                title="Show/Hide password">
                                            <i id="passwordToggleIcon" class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <button type="button" onclick="resetPasswordToSchoolId()" 
                                            class="px-3 py-2 bg-gray-500 text-white text-sm rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500"
                                            title="Reset password to School ID">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                </div>
                                <span class="text-xs text-red-500" id="passwordError"></span>
                                <span class="text-xs text-gray-500" id="passwordHint"></span>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Section</label>
                                <select id="section" name="section_id"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                    <option value="">Select Section (Optional)</option>
                                </select>
                                <span class="text-xs text-red-500" id="sectionError"></span>
                                <div id="partneredSchoolInfo" class="mt-2 text-sm text-gray-600"></div>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" id="isActive" name="isActive" value="1" checked
                                       class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                                <label for="isActive" class="ml-2 block text-sm text-gray-900">
                                    Active
                                </label>
                            </div>
                        </div>
                        
                        <div class="mt-6 flex justify-end space-x-3">
                            <button type="button" onclick="closeModal()" 
                                    class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                                Save Coordinator
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div id="resetPasswordModal" class="modal">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl w-[500px] max-w-[90vw]">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Reset Coordinator Password</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Reset password for <strong id="resetCoordinatorName"></strong>
                    </p>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                        <div class="flex space-x-2">
                            <div class="relative flex-1">
                                <input type="password" id="resetPassword" name="resetPassword" 
                                       class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                                <button type="button" onclick="toggleResetPasswordVisibility()" 
                                        class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-gray-700"
                                        title="Show/Hide password">
                                    <i id="resetPasswordToggleIcon" class="fas fa-eye"></i>
                                </button>
                            </div>
                            <button type="button" onclick="setResetPasswordToSchoolId()" 
                                    class="px-3 py-2 bg-gray-500 text-white text-sm rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500"
                                    title="Use School ID as password">
                                <i class="fas fa-undo"></i>
                            </button>
                        </div>
                        <span class="text-xs text-red-500" id="resetPasswordError"></span>
                        <span class="text-xs text-gray-500" id="resetPasswordHint"></span>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button onclick="closeResetPasswordModal()" 
                                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button onclick="confirmResetPassword()" 
                                class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500">
                            Reset Password
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Container -->
    <div id="notificationContainer" class="notification"></div>

    <!-- JavaScript -->
    <script src="../js/coordinator.js"></script>
</body>
</html>
