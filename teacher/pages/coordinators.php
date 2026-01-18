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
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
        }
        .modal.show {
            display: flex;
        }
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 2000;
            max-width: 400px;
        }
        .dropdown {
            position: relative;
            display: inline-block;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1001;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
        }
        .dropdown-content.show {
            display: block;
        }
        .dropdown-item {
            padding: 8px 12px;
            text-decoration: none;
            display: block;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .dropdown-item:hover {
            background-color: #f3f4f6;
        }
        .dropdown-item:first-child {
            border-radius: 6px 6px 0 0;
        }
        .dropdown-item:last-child {
            border-radius: 0 0 6px 6px;
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

    <script>
        // Mobile Sidebar
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileSidebar = document.getElementById('mobileSidebar');
        const closeMobileSidebar = document.getElementById('closeMobileSidebar');
        const mobileSidebarOverlay = document.getElementById('mobileSidebarOverlay');

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

        // Dropdown functions
        function toggleDropdown(schoolId) {
            // Close all other dropdowns
            document.querySelectorAll('.dropdown-content').forEach(dropdown => {
                if (dropdown.id !== `dropdown-content-${schoolId}`) {
                    dropdown.classList.remove('show');
                }
            });
            
            // Toggle current dropdown
            const currentDropdown = document.getElementById(`dropdown-content-${schoolId}`);
            currentDropdown.classList.toggle('show');
        }

        function closeDropdown(schoolId) {
            const dropdown = document.getElementById(`dropdown-content-${schoolId}`);
            if (dropdown) {
                dropdown.classList.remove('show');
            }
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.matches('.dropdown button') && !event.target.closest('.dropdown-content')) {
                document.querySelectorAll('.dropdown-content').forEach(dropdown => {
                    dropdown.classList.remove('show');
                });
            }
        });

        // Coordinator Management Functions
        let currentEditingCoordinator = null;

        function openAddModal() {
            currentEditingCoordinator = null;
            document.getElementById('modalTitle').textContent = 'Add New Coordinator';
            document.getElementById('coordinatorForm').reset();
            clearValidationErrors();
            
            // Show password field for adding
            document.getElementById('passwordField').style.display = 'block';
            document.getElementById('password').required = true;
            
            // Hide reset password button for adding
            document.getElementById('resetPasswordBtn').style.display = 'none';
            
            // Auto-fill password with school_id when it's entered
            const schoolIdInput = document.getElementById('schoolId');
            schoolIdInput.addEventListener('input', function() {
                const schoolId = this.value.trim();
                if (schoolId) {
                    document.getElementById('password').value = schoolId;
                    document.getElementById('passwordHint').textContent = 'Password auto-filled with School ID';
                } else {
                    document.getElementById('password').value = '';
                    document.getElementById('passwordHint').textContent = '';
                }
            });
            
            document.getElementById('coordinatorModal').classList.add('show');
            loadSections();
        }

        function openEditModal(coordinator) {
            currentEditingCoordinator = coordinator;
            document.getElementById('modalTitle').textContent = 'Edit Coordinator';
            
            // Show reset password button for editing
            document.getElementById('resetPasswordBtn').style.display = 'inline-flex';
            
            // Hide password field for editing (use separate reset modal)
            document.getElementById('passwordField').style.display = 'none';
            document.getElementById('password').required = false;
            
            // Populate form fields
            document.getElementById('schoolId').value = coordinator.school_id;
            document.getElementById('firstname').value = coordinator.firstname;
            document.getElementById('lastname').value = coordinator.lastname;
            document.getElementById('middlename').value = coordinator.middlename || '';
            document.getElementById('email').value = coordinator.email || '';
            document.getElementById('isActive').checked = coordinator.isActive == 1;
            
            // Load sections and set selected section
            loadSections().then(() => {
                document.getElementById('section').value = coordinator.section_id || '';
                updatePartneredSchoolInfo(coordinator.section_id);
            });
            
            clearValidationErrors();
            document.getElementById('coordinatorModal').classList.add('show');
        }

        function closeModal() {
            document.getElementById('coordinatorModal').classList.remove('show');
            document.getElementById('coordinatorForm').reset();
            clearValidationErrors();
            currentEditingCoordinator = null;
        }

        function clearValidationErrors() {
            document.querySelectorAll('.text-red-500').forEach(el => el.textContent = '');
        }

        function showNotification(message, type = 'info') {
            const container = document.getElementById('notificationContainer');
            const notification = document.createElement('div');
            
            const bgColor = type === 'success' ? 'bg-green-500' : 
                          type === 'error' ? 'bg-red-500' : 'bg-blue-500';
            
            notification.className = `${bgColor} text-white px-4 py-3 rounded-md shadow-lg mb-2`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            
            container.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        // Reset Password Functions
        let currentResetCoordinator = null;

        function openResetPasswordModal() {
            if (!currentEditingCoordinator) return;
            
            currentResetCoordinator = currentEditingCoordinator;
            document.getElementById('resetCoordinatorName').textContent = 
                `${currentEditingCoordinator.firstname} ${currentEditingCoordinator.lastname}`;
            
            // Auto-fill password with school_id by default
            const schoolId = currentEditingCoordinator.school_id;
            document.getElementById('resetPassword').value = schoolId;
            
            // Clear previous values and set hints
            document.getElementById('resetPasswordError').textContent = '';
            document.getElementById('resetPasswordHint').textContent = 
                `Password set to School ID: ${schoolId}. You can edit this if needed.`;
            
            document.getElementById('resetPasswordModal').classList.add('show');
        }

        function closeResetPasswordModal() {
            document.getElementById('resetPasswordModal').classList.remove('show');
            currentResetCoordinator = null;
        }

        function toggleResetPasswordVisibility() {
            const passwordInput = document.getElementById('resetPassword');
            const toggleIcon = document.getElementById('resetPasswordToggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        function setResetPasswordToSchoolId() {
            if (currentResetCoordinator) {
                const schoolId = currentResetCoordinator.school_id;
                document.getElementById('resetPassword').value = schoolId;
                document.getElementById('resetPasswordHint').textContent = `Password reset to School ID: ${schoolId}`;
                document.getElementById('resetPasswordError').textContent = '';
            }
        }

        async function confirmResetPassword() {
            const newPassword = document.getElementById('resetPassword').value.trim();
            
            if (!newPassword) {
                document.getElementById('resetPasswordError').textContent = 'Password is required';
                return;
            }
            
            // Skip length validation if password is the default school_id
            const isDefaultSchoolId = newPassword === currentResetCoordinator.school_id;
            
            if (!isDefaultSchoolId && newPassword.length < 6) {
                document.getElementById('resetPasswordError').textContent = 'Password must be at least 6 characters';
                return;
            }

            try {
                const formData = new FormData();
                formData.append('operation', 'update');
                formData.append('json', JSON.stringify({
                    school_id: currentResetCoordinator.school_id,
                    password: newPassword,
                    firstname: currentResetCoordinator.firstname,
                    lastname: currentResetCoordinator.lastname,
                    middlename: currentResetCoordinator.middlename || '',
                    email: currentResetCoordinator.email || '',
                    section_id: currentResetCoordinator.section_id || '',
                    isActive: currentResetCoordinator.isActive
                }));

                const response = await fetch('../../api/teachers.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showNotification('Password reset successfully!', 'success');
                    closeResetPasswordModal();
                } else {
                    showNotification(result.message || 'Failed to reset password', 'error');
                }
            } catch (error) {
                console.error('Error resetting password:', error);
                showNotification('An error occurred while resetting password', 'error');
            }
        }

        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('passwordToggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        function resetPasswordToSchoolId() {
            const schoolId = document.getElementById('schoolId').value.trim();
            if (schoolId) {
                document.getElementById('password').value = schoolId;
                document.getElementById('passwordHint').textContent = 'Password reset to School ID';
                document.getElementById('passwordError').textContent = '';
            } else {
                document.getElementById('passwordError').textContent = 'Please enter School ID first';
            }
        }

        function validateForm() {
            clearValidationErrors();
            let isValid = true;
            
            const schoolId = document.getElementById('schoolId').value.trim();
            const firstname = document.getElementById('firstname').value.trim();
            const lastname = document.getElementById('lastname').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('passwordField').style.display !== 'none' ? 
                           document.getElementById('password').value : '';
            const sectionId = document.getElementById('section').value;
            
            if (!schoolId) {
                document.getElementById('schoolIdError').textContent = 'School ID is required';
                isValid = false;
            }
            
            if (!firstname) {
                document.getElementById('firstnameError').textContent = 'First name is required';
                isValid = false;
            }
            
            if (!lastname) {
                document.getElementById('lastnameError').textContent = 'Last name is required';
                isValid = false;
            }
            
            if (!email) {
                document.getElementById('emailError').textContent = 'Email is required';
                isValid = false;
            } else if (!/\S+@\S+\.\S+/.test(email)) {
                document.getElementById('emailError').textContent = 'Invalid email format';
                isValid = false;
            }
            
            // Password validation only for adding new coordinators
            if (document.getElementById('passwordField').style.display !== 'none') {
                if (!password) {
                    document.getElementById('passwordError').textContent = 'Password is required';
                    isValid = false;
                } else if (password.length < 6) {
                    document.getElementById('passwordError').textContent = 'Password must be at least 6 characters';
                    isValid = false;
                }
            }
            
            // Section is now optional - no validation needed
            
            return isValid;
        }

        async function saveCoordinator(formData) {
            try {
                const apiFormData = new FormData();
                apiFormData.append('operation', currentEditingCoordinator ? 'update' : 'create');
                apiFormData.append('json', JSON.stringify(formData));
                
                const response = await fetch('../../api/teachers.php', {
                    method: 'POST',
                    body: apiFormData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification(result.message, 'success');
                    closeModal();
                    loadCoordinators();
                } else {
                    showNotification(result.message, 'error');
                    
                    // Show validation errors if any
                    if (result.errors) {
                        Object.keys(result.errors).forEach(field => {
                            const errorEl = document.getElementById(field + 'Error');
                            if (errorEl) {
                                errorEl.textContent = result.errors[field];
                            }
                        });
                    }
                }
            } catch (error) {
                showNotification('An error occurred while saving coordinator', 'error');
                console.error('Error:', error);
            }
        }

        async function deleteCoordinator(schoolId) {
            if (!confirm('Are you sure you want to delete this coordinator? This action cannot be undone.')) {
                return;
            }
            
            try {
                const apiFormData = new FormData();
                apiFormData.append('operation', 'delete');
                apiFormData.append('json', JSON.stringify({ school_id: schoolId }));
                
                const response = await fetch('../../api/teachers.php', {
                    method: 'POST',
                    body: apiFormData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification(result.message, 'success');
                    loadCoordinators();
                } else {
                    showNotification(result.message, 'error');
                }
            } catch (error) {
                showNotification('An error occurred while deleting coordinator', 'error');
                console.error('Error:', error);
            }
        }

        async function toggleCoordinatorStatus(schoolId, currentStatus) {
            try {
                const apiFormData = new FormData();
                apiFormData.append('operation', 'toggle_status');
                apiFormData.append('json', JSON.stringify({ 
                    school_id: schoolId, 
                    isActive: currentStatus === 1 ? 0 : 1 
                }));
                
                const response = await fetch('../../api/teachers.php', {
                    method: 'POST',
                    body: apiFormData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification(result.message, 'success');
                    loadCoordinators();
                } else {
                    showNotification(result.message, 'error');
                }
            } catch (error) {
                showNotification('An error occurred while updating coordinator status', 'error');
                console.error('Error:', error);
            }
        }

        let allCoordinators = [];

        async function loadCoordinators() {
            try {
                const formData = new FormData();
                formData.append('operation', 'read');
                formData.append('json', JSON.stringify({
                    user_level: 'coordinator' // Add this to filter coordinators only
                }));
                
                const response = await fetch('../../api/teachers.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    allCoordinators = result.data;
                    filterCoordinators();
                } else {
                    showNotification(result.message, 'error');
                    allCoordinators = [];
                    filterCoordinators();
                }
            } catch (error) {
                showNotification('An error occurred while loading coordinators', 'error');
                console.error('Error:', error);
                allCoordinators = [];
                filterCoordinators();
            }
        }

        function filterCoordinators() {
            const searchTerm = document.getElementById('searchInput').value.trim().toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            
            let filteredCoordinators = allCoordinators;
            
            // Filter by search term
            if (searchTerm) {
                filteredCoordinators = filteredCoordinators.filter(coordinator => {
                    const fullName = `${coordinator.firstname} ${coordinator.middlename ? coordinator.middlename + ' ' : ''}${coordinator.lastname}`.toLowerCase();
                    return coordinator.school_id.toLowerCase().includes(searchTerm) ||
                           fullName.includes(searchTerm) ||
                           (coordinator.email && coordinator.email.toLowerCase().includes(searchTerm));
                });
            }
            
            // Filter by status
            if (statusFilter !== '') {
                filteredCoordinators = filteredCoordinators.filter(coordinator => coordinator.isActive == statusFilter);
            }
            
            renderCoordinatorsTable(filteredCoordinators);
        }

        function renderCoordinatorsTable(coordinators) {
            const tbody = document.getElementById('coordinatorsTableBody');
            const noDataMessage = document.getElementById('noDataMessage');
            
            if (coordinators.length === 0) {
                tbody.innerHTML = '';
                noDataMessage.classList.remove('hidden');
                return;
            }
            
            noDataMessage.classList.add('hidden');
            
            tbody.innerHTML = coordinators.map(coordinator => `
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        ${coordinator.school_id}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">
                            ${coordinator.firstname} ${coordinator.middlename ? coordinator.middlename + ' ' : ''}${coordinator.lastname}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        ${coordinator.email || 'N/A'}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        ${coordinator.section_name || 'N/A'}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        ${coordinator.partnered_school_name || 'No partnered school assigned'}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                            coordinator.isActive == 1 
                                ? 'bg-green-100 text-green-800' 
                                : 'bg-red-100 text-red-800'
                        }">
                            ${coordinator.isActive == 1 ? 'Active' : 'Inactive'}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="dropdown" id="dropdown-${coordinator.school_id}">
                            <button onclick="toggleDropdown('${coordinator.school_id}')" 
                                    class="text-gray-600 hover:text-gray-900 p-2 rounded-md hover:bg-gray-100">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <div class="dropdown-content" id="dropdown-content-${coordinator.school_id}">
                                <div class="dropdown-item text-indigo-600 hover:text-indigo-900" 
                                     onclick="closeDropdown('${coordinator.school_id}'); openEditModal(${JSON.stringify(coordinator).replace(/"/g, '&quot;')})">
                                    <i class="fas fa-edit mr-2"></i> Edit
                                </div>
                                <div class="dropdown-item text-${coordinator.isActive == 1 ? 'yellow' : 'green'}-600 hover:text-${coordinator.isActive == 1 ? 'yellow' : 'green'}-900" 
                                     onclick="closeDropdown('${coordinator.school_id}'); toggleCoordinatorStatus('${coordinator.school_id}', ${coordinator.isActive})">
                                    <i class="fas fa-${coordinator.isActive == 1 ? 'toggle-off' : 'toggle-on'} mr-2"></i> 
                                    ${coordinator.isActive == 1 ? 'Deactivate' : 'Activate'}
                                </div>
                                <div class="dropdown-item text-red-600 hover:text-red-900" 
                                     onclick="closeDropdown('${coordinator.school_id}'); deleteCoordinator('${coordinator.school_id}')">
                                    <i class="fas fa-trash mr-2"></i> Delete
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        async function loadSections() {
            try {
                const formData = new FormData();
                formData.append('operation', 'get_sections');
                formData.append('json', JSON.stringify({}));
                
                const response = await fetch('../../api/teachers.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    const select = document.getElementById('section');
                    select.innerHTML = '<option value="">Select Section</option>' +
                        result.data.map(section => 
                            `<option value="${section.id}">${section.section_name}</option>`
                        ).join('');
                        
                    // Add change event listener to show partnered school info
                    select.addEventListener('change', function() {
                        updatePartneredSchoolInfo(this.value);
                    });
                }
            } catch (error) {
                showNotification('An error occurred while loading sections', 'error');
                console.error('Error:', error);
            }
        }

        async function updatePartneredSchoolInfo(sectionId) {
            const infoDiv = document.getElementById('partneredSchoolInfo');
            
            if (!sectionId) {
                infoDiv.innerHTML = '';
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('operation', 'get_partnered_school');
                formData.append('json', JSON.stringify({ section_id: sectionId }));
                
                const response = await fetch('../../api/teachers.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success && result.data) {
                    infoDiv.innerHTML = `
                        <div class="bg-blue-50 border border-blue-200 rounded-md p-2">
                            <i class="fas fa-school text-blue-600 mr-2"></i>
                            <span class="text-blue-800">Partnered School: ${result.data.name}</span>
                        </div>
                    `;
                } else {
                    infoDiv.innerHTML = `
                        <div class="bg-yellow-50 border border-yellow-200 rounded-md p-2">
                            <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
                            <span class="text-yellow-800">No partnered school assigned to this section</span>
                        </div>
                    `;
                }
            } catch (error) {
                infoDiv.innerHTML = `
                    <div class="bg-red-50 border border-red-200 rounded-md p-2">
                        <i class="fas fa-exclamation-circle text-red-600 mr-2"></i>
                        <span class="text-red-800">Error loading partnered school information</span>
                    </div>
                `;
            }
        }

        // Form submission
        document.getElementById('coordinatorForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!validateForm()) {
                return;
            }
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            
            // Set level_id to 3 for Coordinator
            data.level_id = 3;
            
            // Convert checkbox to boolean
            data.isActive = data.isActive ? 1 : 0;
            
            // Remove password if empty and editing
            if (currentEditingCoordinator && !data.password) {
                delete data.password;
            }
            
            saveCoordinator(data);
        });

        // Auto-search on input change
        document.getElementById('searchInput').addEventListener('input', filterCoordinators);
        document.getElementById('statusFilter').addEventListener('change', filterCoordinators);

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadCoordinators();
        });

        // Close modal when clicking outside
        document.getElementById('coordinatorModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
