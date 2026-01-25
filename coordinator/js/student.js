// Coordinator Student Management JavaScript
let currentTab = 'pending';
let pendingAction = null;

// Load data on page load
document.addEventListener('DOMContentLoaded', function() {
    loadStats();
    loadStudents('pending');
    
    // Initialize modal event listeners
    initializeModal();
});

// Initialize modal event listeners
function initializeModal() {
    const modal = document.getElementById('confirmModal');
    const closeBtn = document.getElementById('closeConfirmModal');
    const cancelBtn = document.getElementById('confirmModalCancel');
    const confirmBtn = document.getElementById('confirmModalConfirm');
    
    // Close modal when clicking X or Cancel
    if (closeBtn) {
        closeBtn.addEventListener('click', closeConfirmModal);
    }
    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeConfirmModal);
    }
    
    // Execute action when clicking Confirm
    if (confirmBtn) {
        confirmBtn.addEventListener('click', executePendingAction);
    }
    
    // Close modal when clicking outside
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeConfirmModal();
        }
    });
}

// Show confirmation modal
function showConfirmModal(title, message, confirmText, confirmClass, onConfirm) {
    const modal = document.getElementById('confirmModal');
    const titleEl = document.getElementById('confirmModalTitle');
    const messageEl = document.getElementById('confirmModalMessage');
    const confirmBtn = document.getElementById('confirmModalConfirm');
    
    titleEl.textContent = title;
    messageEl.textContent = message;
    confirmBtn.textContent = confirmText;
    
    // Remove all classes and add the new one
    confirmBtn.className = 'px-4 py-2 rounded-md focus:outline-none focus:ring-2 ' + confirmClass;
    
    // Store the action to execute
    pendingAction = onConfirm;
    
    // Show modal
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

// Close confirmation modal
function closeConfirmModal() {
    const modal = document.getElementById('confirmModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    pendingAction = null;
}

// Execute pending action
function executePendingAction() {
    if (pendingAction) {
        pendingAction();
        closeConfirmModal();
    }
}

// Load student statistics for coordinator's section
function loadStats() {
    console.log('Coordinator ID:', coordinatorId);
    
    fetch(window.APP_CONFIG.API_BASE_URL + 'coordinator.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `operation=get_coordinator_stats&json=${encodeURIComponent(JSON.stringify({coordinator_id: coordinatorId}))}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const pendingCount = document.getElementById('pending-count');
            const approvedCount = document.getElementById('approved-count');
            const declinedCount = document.getElementById('declined-count');
            
            if (pendingCount) pendingCount.textContent = data.data.pending;
            if (approvedCount) approvedCount.textContent = data.data.approved;
            if (declinedCount) declinedCount.textContent = data.data.declined;
        }
    })
    .catch(error => {
        console.error('Error loading stats:', error);
    });
}

// Load students by approval status for coordinator's section
function loadStudents(approvalStatus) {
    const loadingEl = document.getElementById(approvalStatus + '-loading');
    const tableEl = document.getElementById(approvalStatus + '-table');
    
    if (loadingEl) loadingEl.classList.remove('hidden');
    if (tableEl) tableEl.classList.add('hidden');
    
    fetch(window.APP_CONFIG.API_BASE_URL + 'coordinator.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `operation=read_coordinator_students&json=${encodeURIComponent(JSON.stringify({
            approval_status: approvalStatus,
            coordinator_id: coordinatorId
        }))}`
    })
    .then(response => response.json())
    .then(data => {
        if (loadingEl) loadingEl.classList.add('hidden');
        
        if (data.success && data.data && data.data.length > 0) {
            if (tableEl) tableEl.innerHTML = createStudentTable(data.data, approvalStatus);
            if (tableEl) tableEl.classList.remove('hidden');
        } else {
            if (tableEl) tableEl.innerHTML = createEmptyState(approvalStatus);
            if (tableEl) tableEl.classList.remove('hidden');
        }
    })
    .catch(error => {
        console.error('Error loading students:', error);
        if (loadingEl) loadingEl.classList.add('hidden');
        if (tableEl) {
            tableEl.innerHTML = '<div class="text-center py-8 text-red-500">Error loading data</div>';
            tableEl.classList.remove('hidden');
        }
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
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
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${escapeHtml(student.section_name || 'N/A')}</td>
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
            text: 'There are no student registration requests pending for your section.'
        },
        approved: {
            icon: 'fa-check-circle',
            title: 'No approved students',
            text: 'No students have been approved in your section yet.'
        },
        declined: {
            icon: 'fa-times-circle',
            title: 'No declined requests',
            text: 'No student requests have been declined in your section.'
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
    return '';
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
    const selectedContent = document.getElementById(tabName + '-content');
    if (selectedContent) selectedContent.classList.remove('hidden');
    
    // Add active state to selected tab
    const activeTab = document.getElementById(tabName + '-tab');
    if (activeTab) {
        activeTab.classList.remove('border-transparent', 'text-gray-500');
        activeTab.classList.add('border-blue-500', 'text-blue-600');
    }
    
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

// Approve student
function approveStudent(schoolId) {
    showConfirmModal(
        'Approve Student',
        'Are you sure you want to approve this student?',
        'Approve',
        'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
        function() {
            performApproveStudent(schoolId);
        }
    );
}

// Perform the actual approve action
function performApproveStudent(schoolId) {
    fetch(window.APP_CONFIG.API_BASE_URL + 'coordinator.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `operation=approve&json=${encodeURIComponent(JSON.stringify({
            school_id: schoolId,
            coordinator_id: coordinatorId
        }))}`
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
    showConfirmModal(
        'Decline Student',
        'Are you sure you want to decline this student?',
        'Decline',
        'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
        function() {
            performDeclineStudent(schoolId);
        }
    );
}

// Perform the actual decline action
function performDeclineStudent(schoolId) {
    fetch(window.APP_CONFIG.API_BASE_URL + 'coordinator.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `operation=decline&json=${encodeURIComponent(JSON.stringify({
            school_id: schoolId,
            coordinator_id: coordinatorId
        }))}`
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
    
    fetch(window.APP_CONFIG.API_BASE_URL + 'coordinator.php', {
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
    if (refreshIcon) refreshIcon.classList.add('fa-spin');
    
    loadStats();
    loadStudents(currentTab);
    
    // Stop spinning after 1 second
    setTimeout(() => {
        if (refreshIcon) refreshIcon.classList.remove('fa-spin');
    }, 1000);
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

// Logout function
function logout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = '../../login.php';
    }
}
