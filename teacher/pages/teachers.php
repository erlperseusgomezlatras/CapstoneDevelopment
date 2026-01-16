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
$current_page = 'teachers';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Management | PHINMA Practicum Management System</title>
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
                                    Teacher Management
                                </h2>
                                <p class="text-gray-600">
                                    Create, update, and manage teacher accounts in the system
                                </p>
                            </div>
                            <button onclick="openAddModal()" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                                <i class="fas fa-plus mr-2"></i>
                                Add New Teacher
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

                    <!-- Teachers Table -->
                    <div class="bg-white rounded-lg shadow overflow-hidden">
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
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="teachersTableBody" class="bg-white divide-y divide-gray-200">
                                    <!-- Teachers will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                        <div id="noDataMessage" class="hidden p-8 text-center text-gray-500">
                            <i class="fas fa-users text-4xl mb-4 text-gray-300"></i>
                            <p class="text-lg font-medium mb-2">No teachers found</p>
                            <p class="text-sm text-gray-400">Start by adding your first teacher to the system</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add/Edit Teacher Modal -->
    <div id="teacherModal" class="modal">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl w-[600px] max-w-[90vw]">
                <div class="p-6">
                    <h3 id="modalTitle" class="text-lg font-semibold text-gray-900 mb-4">Add New Teacher</h3>
                    <form id="teacherForm">
                        <input type="hidden" id="teacherId" name="school_id">
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">School ID *</label>
                                <input type="text" id="schoolId" name="school_id" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                <span class="text-xs text-red-500" id="schoolIdError"></span>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                                <input type="text" id="firstName" name="firstname" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                <span class="text-xs text-red-500" id="firstNameError"></span>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                                <input type="text" id="lastName" name="lastname" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                <span class="text-xs text-red-500" id="lastNameError"></span>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                                <input type="text" id="middleName" name="middlename"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                                <input type="email" id="email" name="email" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                <span class="text-xs text-red-500" id="emailError"></span>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                                <input type="password" id="password" name="password" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                <span class="text-xs text-red-500" id="passwordError"></span>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Section (Optional)</label>
                                <select id="section" name="section_id" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                    <option value="">Select Section</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" id="isActive" name="isActive" checked
                                           class="mr-2 rounded border-gray-300 text-green-600 focus:ring-green-500">
                                    <span class="text-sm text-gray-700">Active</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="mt-6 flex justify-end space-x-3">
                            <button type="button" onclick="closeModal()" 
                                    class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                Save Teacher
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification -->
    <div id="notification" class="notification hidden">
        <div class="bg-white rounded-lg shadow-lg p-4">
            <div class="flex items-center">
                <div id="notificationIcon" class="flex-shrink-0 mr-3"></div>
                <div class="flex-1">
                    <p id="notificationMessage" class="text-sm text-gray-900"></p>
                </div>
                <button onclick="hideNotification()" class="ml-3 text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
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

        // Teacher Management Functions
        let currentEditingTeacher = null;

        function openAddModal() {
            currentEditingTeacher = null;
            document.getElementById('modalTitle').textContent = 'Add New Teacher';
            document.getElementById('teacherForm').reset();
            clearValidationErrors();
            document.getElementById('teacherModal').classList.add('show');
        }

        function openEditModal(teacher) {
            currentEditingTeacher = teacher;
            document.getElementById('modalTitle').textContent = 'Edit Teacher';
            
            // Populate form fields
            document.getElementById('schoolId').value = teacher.school_id;
            document.getElementById('firstName').value = teacher.firstname;
            document.getElementById('lastName').value = teacher.lastname;
            document.getElementById('middleName').value = teacher.middlename || '';
            document.getElementById('email').value = teacher.email || '';
            document.getElementById('password').value = ''; // Don't populate password for security
            document.getElementById('section').value = teacher.section_id || '';
            document.getElementById('isActive').checked = teacher.isActive == 1;
            
            clearValidationErrors();
            document.getElementById('teacherModal').classList.add('show');
        }

        function closeModal() {
            document.getElementById('teacherModal').classList.remove('show');
            document.getElementById('teacherForm').reset();
            clearValidationErrors();
            currentEditingTeacher = null;
        }

        function clearValidationErrors() {
            document.querySelectorAll('.text-red-500').forEach(el => el.textContent = '');
        }

        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            const icon = document.getElementById('notificationIcon');
            const messageEl = document.getElementById('notificationMessage');
            
            messageEl.textContent = message;
            
            if (type === 'success') {
                icon.innerHTML = '<i class="fas fa-check-circle text-green-500 text-xl"></i>';
            } else if (type === 'error') {
                icon.innerHTML = '<i class="fas fa-exclamation-circle text-red-500 text-xl"></i>';
            } else {
                icon.innerHTML = '<i class="fas fa-info-circle text-blue-500 text-xl"></i>';
            }
            
            notification.classList.remove('hidden');
            
            setTimeout(() => {
                hideNotification();
            }, 5000);
        }

        function hideNotification() {
            document.getElementById('notification').classList.add('hidden');
        }

        function validateForm() {
            let isValid = true;
            clearValidationErrors();
            
            const schoolId = document.getElementById('schoolId').value.trim();
            const firstName = document.getElementById('firstName').value.trim();
            const lastName = document.getElementById('lastName').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            
            if (!schoolId) {
                document.getElementById('schoolIdError').textContent = 'School ID is required';
                isValid = false;
            }
            
            if (!firstName) {
                document.getElementById('firstNameError').textContent = 'First name is required';
                isValid = false;
            }
            
            if (!lastName) {
                document.getElementById('lastNameError').textContent = 'Last name is required';
                isValid = false;
            }
            
            if (!email) {
                document.getElementById('emailError').textContent = 'Email is required';
                isValid = false;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                document.getElementById('emailError').textContent = 'Invalid email format';
                isValid = false;
            }
            
            if (!currentEditingTeacher && !password) {
                document.getElementById('passwordError').textContent = 'Password is required for new teachers';
                isValid = false;
            } else if (password && password.length < 6) {
                document.getElementById('passwordError').textContent = 'Password must be at least 6 characters';
                isValid = false;
            }
            
            return isValid;
        }

        async function saveTeacher(formData) {
            try {
                const apiFormData = new FormData();
                apiFormData.append('operation', currentEditingTeacher ? 'update' : 'create');
                apiFormData.append('json', JSON.stringify(formData));
                
                const response = await fetch('../../api/teachers.php', {
                    method: 'POST',
                    body: apiFormData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification(result.message, 'success');
                    closeModal();
                    loadTeachers();
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
                showNotification('An error occurred while saving teacher', 'error');
                console.error('Error:', error);
            }
        }

        async function deleteTeacher(schoolId) {
            if (!confirm('Are you sure you want to delete this teacher? This action cannot be undone.')) {
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
                    loadTeachers();
                } else {
                    showNotification(result.message, 'error');
                }
            } catch (error) {
                showNotification('An error occurred while deleting teacher', 'error');
                console.error('Error:', error);
            }
        }

        async function toggleTeacherStatus(schoolId, currentStatus) {
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
                    loadTeachers();
                } else {
                    showNotification(result.message, 'error');
                }
            } catch (error) {
                showNotification('An error occurred while updating teacher status', 'error');
                console.error('Error:', error);
            }
        }

        let allTeachers = [];

        async function loadTeachers() {
            try {
                const formData = new FormData();
                formData.append('operation', 'read');
                formData.append('json', JSON.stringify({}));
                
                const response = await fetch('../../api/teachers.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    allTeachers = result.data;
                    filterTeachers();
                } else {
                    showNotification(result.message, 'error');
                    allTeachers = [];
                    filterTeachers();
                }
            } catch (error) {
                showNotification('An error occurred while loading teachers', 'error');
                console.error('Error:', error);
                allTeachers = [];
                filterTeachers();
            }
        }

        function filterTeachers() {
            const searchTerm = document.getElementById('searchInput').value.trim().toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            
            let filteredTeachers = allTeachers;
            
            // Filter by search term
            if (searchTerm) {
                filteredTeachers = filteredTeachers.filter(teacher => {
                    const fullName = `${teacher.firstname} ${teacher.middlename ? teacher.middlename + ' ' : ''}${teacher.lastname}`.toLowerCase();
                    return teacher.school_id.toLowerCase().includes(searchTerm) ||
                           fullName.includes(searchTerm) ||
                           (teacher.email && teacher.email.toLowerCase().includes(searchTerm));
                });
            }
            
            // Filter by status
            if (statusFilter !== '') {
                filteredTeachers = filteredTeachers.filter(teacher => teacher.isActive == statusFilter);
            }
            
            renderTeachersTable(filteredTeachers);
        }

        function renderTeachersTable(teachers) {
            const tbody = document.getElementById('teachersTableBody');
            const noDataMessage = document.getElementById('noDataMessage');
            
            if (teachers.length === 0) {
                tbody.innerHTML = '';
                noDataMessage.classList.remove('hidden');
                return;
            }
            
            noDataMessage.classList.add('hidden');
            
            tbody.innerHTML = teachers.map(teacher => `
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        ${teacher.school_id}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">
                            ${teacher.firstname} ${teacher.middlename ? teacher.middlename + ' ' : ''}${teacher.lastname}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        ${teacher.email || 'N/A'}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        ${teacher.section_name || 'N/A'}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                            teacher.isActive == 1 
                                ? 'bg-green-100 text-green-800' 
                                : 'bg-red-100 text-red-800'
                        }">
                            ${teacher.isActive == 1 ? 'Active' : 'Inactive'}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="dropdown" id="dropdown-${teacher.school_id}">
                            <button onclick="toggleDropdown('${teacher.school_id}')" 
                                    class="text-gray-600 hover:text-gray-900 p-2 rounded-md hover:bg-gray-100">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <div class="dropdown-content" id="dropdown-content-${teacher.school_id}">
                                <div class="dropdown-item text-indigo-600 hover:text-indigo-900" 
                                     onclick="closeDropdown('${teacher.school_id}'); openEditModal(${JSON.stringify(teacher).replace(/"/g, '&quot;')})">
                                    <i class="fas fa-edit mr-2"></i> Edit
                                </div>
                                <div class="dropdown-item text-${teacher.isActive == 1 ? 'yellow' : 'green'}-600 hover:text-${teacher.isActive == 1 ? 'yellow' : 'green'}-900" 
                                     onclick="closeDropdown('${teacher.school_id}'); toggleTeacherStatus('${teacher.school_id}', ${teacher.isActive})">
                                    <i class="fas fa-${teacher.isActive == 1 ? 'toggle-off' : 'toggle-on'} mr-2"></i> 
                                    ${teacher.isActive == 1 ? 'Deactivate' : 'Activate'}
                                </div>
                                <div class="dropdown-item text-red-600 hover:text-red-900" 
                                     onclick="closeDropdown('${teacher.school_id}'); deleteTeacher('${teacher.school_id}')">
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
                }
            } catch (error) {
                console.error('Error loading sections:', error);
            }
        }

        // Form submission
        document.getElementById('teacherForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!validateForm()) {
                return;
            }
            
            const formData = {
                school_id: document.getElementById('schoolId').value.trim(),
                firstname: document.getElementById('firstName').value.trim(),
                lastname: document.getElementById('lastName').value.trim(),
                middlename: document.getElementById('middleName').value.trim(),
                email: document.getElementById('email').value.trim(),
                password: document.getElementById('password').value,
                section_id: document.getElementById('section').value || null,
                isActive: document.getElementById('isActive').checked ? 1 : 0,
                level_id: 2 // Head Teacher level
            };
            
            saveTeacher(formData);
        });

        // Auto-search on input change
        document.getElementById('searchInput').addEventListener('input', filterTeachers);
        document.getElementById('statusFilter').addEventListener('change', filterTeachers);

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadTeachers();
            loadSections();
        });

        // Close modal when clicking outside
        document.getElementById('teacherModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
