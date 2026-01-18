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
        <main class="flex-1 overflow-y-auto">
            <!-- Top Header -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Student Management</h1>
                            <p class="text-sm text-gray-600 mt-1">Manage student accounts and approval requests</p>
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
    
    <script>
        let currentTab = 'pending';
        
        // Load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadStats();
            loadStudents('pending');
        });
        
        // Load student statistics
        function loadStats() {
            fetch('../../api/students.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'operation=get_stats&json={}'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('pending-count').textContent = data.data.pending;
                    document.getElementById('approved-count').textContent = data.data.approved;
                    document.getElementById('declined-count').textContent = data.data.declined;
                }
            })
            .catch(error => {
                console.error('Error loading stats:', error);
            });
        }
        
        // Load students by approval status
        function loadStudents(approvalStatus) {
            const loadingEl = document.getElementById(approvalStatus + '-loading');
            const tableEl = document.getElementById(approvalStatus + '-table');
            
            loadingEl.classList.remove('hidden');
            tableEl.classList.add('hidden');
            
            fetch('../../api/students.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `operation=read&json=${encodeURIComponent(JSON.stringify({approval_status: approvalStatus}))}`
            })
            .then(response => response.json())
            .then(data => {
                loadingEl.classList.add('hidden');
                
                if (data.success && data.data.length > 0) {
                    tableEl.innerHTML = createStudentTable(data.data, approvalStatus);
                    tableEl.classList.remove('hidden');
                } else {
                    tableEl.innerHTML = createEmptyState(approvalStatus);
                    tableEl.classList.remove('hidden');
                }
            })
            .catch(error => {
                console.error('Error loading students:', error);
                loadingEl.classList.add('hidden');
                tableEl.innerHTML = '<div class="text-center py-8 text-red-500">Error loading data</div>';
                tableEl.classList.remove('hidden');
            });
        }
        
        // Create student table HTML
        function createStudentTable(students, approvalStatus) {
            let html = `
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
            `;
            
            students.forEach(student => {
                html += `
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${escapeHtml(student.school_id)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${escapeHtml(student.firstname + ' ' + student.lastname)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${escapeHtml(student.email)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${getStatusText(approvalStatus)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            ${getActionButtons(student, approvalStatus)}
                        </td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
            
            return html;
        }
        
        // Create empty state HTML
        function createEmptyState(approvalStatus) {
            const messages = {
                pending: {
                    icon: 'fa-clock',
                    title: 'No pending approvals',
                    text: 'All student registration requests have been processed.'
                },
                approved: {
                    icon: 'fa-check-circle',
                    title: 'No approved students',
                    text: 'No students have been approved yet.'
                },
                declined: {
                    icon: 'fa-times-circle',
                    title: 'No declined requests',
                    text: 'No student requests have been declined.'
                }
            };
            
            const msg = messages[approvalStatus];
            
            return `
                <div class="text-center py-12">
                    <i class="fas ${msg.icon} text-gray-400 text-5xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">${msg.title}</h3>
                    <p class="text-gray-500">${msg.text}</p>
                </div>
            `;
        }
        
        // Get action buttons based on status
        function getActionButtons(student, approvalStatus) {
            if (approvalStatus === 'pending') {
                return `
                    <button onclick="approveStudent('${escapeHtml(student.school_id)}')" class="text-green-600 hover:text-green-900 mr-3">
                        <i class="fas fa-check"></i> Approve
                    </button>
                    <button onclick="declineStudent('${escapeHtml(student.school_id)}')" class="text-red-600 hover:text-red-900">
                        <i class="fas fa-times"></i> Decline
                    </button>
                `;
            } else if (approvalStatus === 'approved') {
                return '<span class="text-green-600"><i class="fas fa-check-circle"></i> Approved</span>';
            } else if (approvalStatus === 'declined') {
                return `
                    <button onclick="deleteStudent('${escapeHtml(student.school_id)}')" class="text-red-600 hover:text-red-900">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                `;
            }
        }
        
        // Get status text
        function getStatusText(approvalStatus) {
            const statusTexts = {
                pending: 'Pending',
                approved: 'Approved',
                declined: 'Declined'
            };
            return statusTexts[approvalStatus] || 'Unknown';
        }
        
        // Approve student
        function approveStudent(schoolId) {
            if (!confirm('Approve this student?')) return;
            
            fetch('../../api/students.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `operation=approve&json=${encodeURIComponent(JSON.stringify({school_id: schoolId}))}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Student approved successfully', 'success');
                    loadStats();
                    loadStudents(currentTab);
                } else {
                    showNotification(data.message || 'Failed to approve student', 'error');
                }
            })
            .catch(error => {
                console.error('Error approving student:', error);
                showNotification('Error approving student', 'error');
            });
        }
        
        // Decline student
        function declineStudent(schoolId) {
            if (!confirm('Decline this student?')) return;
            
            fetch('../../api/students.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `operation=decline&json=${encodeURIComponent(JSON.stringify({school_id: schoolId}))}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Student declined successfully', 'success');
                    loadStats();
                    loadStudents(currentTab);
                } else {
                    showNotification(data.message || 'Failed to decline student', 'error');
                }
            })
            .catch(error => {
                console.error('Error declining student:', error);
                showNotification('Error declining student', 'error');
            });
        }
        
        // Delete student
        function deleteStudent(schoolId) {
            if (!confirm('Delete this student record?')) return;
            
            fetch('../../api/students.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `operation=delete&json=${encodeURIComponent(JSON.stringify({school_id: schoolId}))}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Student deleted successfully', 'success');
                    loadStats();
                    loadStudents(currentTab);
                } else {
                    showNotification(data.message || 'Failed to delete student', 'error');
                }
            })
            .catch(error => {
                console.error('Error deleting student:', error);
                showNotification('Error deleting student', 'error');
            });
        }
        
        // Refresh all data
        function refreshAllData() {
            const refreshIcon = document.getElementById('refresh-icon');
            refreshIcon.classList.add('fa-spin');
            
            loadStats();
            loadStudents(currentTab);
            
            // Stop spinning after 1 second
            setTimeout(() => {
                refreshIcon.classList.remove('fa-spin');
            }, 1000);
        }
        
        // Show tab
        function showTab(tabName) {
            currentTab = tabName;
            
            // Hide all content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            // Remove active state from all tabs
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('border-blue-500', 'text-blue-600');
                button.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Show selected content
            document.getElementById(tabName + '-content').classList.remove('hidden');
            
            // Add active state to selected tab
            const activeTab = document.getElementById(tabName + '-tab');
            activeTab.classList.remove('border-transparent', 'text-gray-500');
            activeTab.classList.add('border-blue-500', 'text-blue-600');
            
            // Load data for this tab
            loadStudents(tabName);
        }
        
        // Show notification
        function showNotification(message, type) {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 px-4 py-3 rounded-md shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
            }`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            // Remove after 3 seconds
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
        
        // Escape HTML to prevent XSS
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }
        
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '../../login.php';
            }
        }
    </script>
</body>
</html>
