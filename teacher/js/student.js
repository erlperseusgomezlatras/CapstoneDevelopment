// Student Management JavaScript
let currentTab = 'pending';

// Load data on page load
document.addEventListener('DOMContentLoaded', function() {
    loadStats();
    loadStudents('pending');
});

// Load student statistics
function loadStats() {
    fetch(window.APP_CONFIG.API_BASE_URL + 'students.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'operation=get_stats&json={}'
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

// Load students by approval status
function loadStudents(approvalStatus) {
    const loadingEl = document.getElementById(approvalStatus + '-loading');
    const tableEl = document.getElementById(approvalStatus + '-table');
    
    if (loadingEl) loadingEl.classList.remove('hidden');
    if (tableEl) tableEl.classList.add('hidden');
    
    fetch(window.APP_CONFIG.API_BASE_URL + 'students.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `operation=read&json=${encodeURIComponent(JSON.stringify({approval_status: approvalStatus}))}`
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
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${student.section_name ? escapeHtml(student.section_name) : '<span class="text-yellow-600 italic">No section assigned</span>'}
                </td>
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
        const hasSection = student.section_id && student.section_id !== '0' && student.section_name;
        
        if (hasSection) {
            // Student has section - direct approve
            return `
                <button onclick="approveStudent('${escapeHtml(student.school_id)}')" class="text-green-600 hover:text-green-900 mr-3">
                    <i class="fas fa-check"></i> Approve
                </button>
                <button onclick="declineStudent('${escapeHtml(student.school_id)}')" class="text-red-600 hover:text-red-900">
                    <i class="fas fa-times"></i> Decline
                </button>
            `;
        } else {
            // Student has no section - approve with assignment
            return `
                <button onclick="approveWithSection('${escapeHtml(student.school_id)}')" class="text-blue-600 hover:text-blue-900 mr-3">
                    <i class="fas fa-user-plus"></i> Assign & Approve
                </button>
                <button onclick="declineStudent('${escapeHtml(student.school_id)}')" class="text-red-600 hover:text-red-900">
                    <i class="fas fa-times"></i> Decline
                </button>
            `;
        }
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

// Approve student with section assignment
function approveWithSection(schoolId) {
    // Set the school ID in the modal
    document.getElementById('modalSchoolId').value = schoolId;
    
    // Load sections for the dropdown
    loadSectionsForModal();
    
    // Show the modal
    document.getElementById('sectionModal').classList.remove('hidden');
}

// Load sections for modal dropdown
function loadSectionsForModal() {
    fetch(window.APP_CONFIG.API_BASE_URL + 'students.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'operation=get_sections&json={}'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data) {
            const select = $('#sectionSelect'); // Use jQuery for Select2
            select.html('<option value="">Select Section</option>' +
                data.data.map(section => 
                    `<option value="${section.id}">${section.section_name}</option>`
                ).join(''));
            
            // Initialize or reinitialize Select2
            if (select.hasClass('select2-hidden-accessible')) {
                select.select2('destroy');
            }
            
            select.select2({
                placeholder: 'Select Section',
                allowClear: false,
                width: '100%'
            });
        }
    })
    .catch(error => {
        console.error('Error loading sections:', error);
        showNotification('Error loading sections', 'error');
    });
}

// Close section modal
function closeSectionModal() {
    document.getElementById('sectionModal').classList.add('hidden');
    document.getElementById('sectionForm').reset();
}

// Submit section assignment and approval
function approveWithSectionSubmit(event) {
    event.preventDefault();
    
    const schoolId = document.getElementById('modalSchoolId').value;
    const sectionId = $('#sectionSelect').val(); // Use jQuery to get Select2 value
    
    if (!sectionId) {
        showNotification('Please select a section', 'error');
        return;
    }
    
    fetch(window.APP_CONFIG.API_BASE_URL + 'students.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `operation=approve&json=${encodeURIComponent(JSON.stringify({
            school_id: schoolId,
            section_id: sectionId
        }))}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Student assigned to section and approved successfully', 'success');
            closeSectionModal();
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

// Custom confirmation dialog
function showConfirmationDialog(title, message, onConfirm) {
    const dialog = document.getElementById('confirmationDialog');
    const titleEl = document.getElementById('dialogTitle');
    const messageEl = document.getElementById('dialogMessage');
    const confirmBtn = document.getElementById('confirmButton');
    const cancelBtn = document.getElementById('cancelButton');
    
    // Set dialog content
    titleEl.textContent = title;
    messageEl.textContent = message;
    
    // Show dialog
    dialog.classList.remove('hidden');
    
    // Remove existing event listeners
    const newConfirmBtn = confirmBtn.cloneNode(true);
    const newCancelBtn = cancelBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
    cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);
    
    // Add event listeners
    newConfirmBtn.addEventListener('click', function() {
        dialog.classList.add('hidden');
        onConfirm();
    });
    
    newCancelBtn.addEventListener('click', function() {
        dialog.classList.add('hidden');
    });
    
    // Close dialog when clicking outside
    dialog.addEventListener('click', function(e) {
        if (e.target === dialog) {
            dialog.classList.add('hidden');
        }
    });
}

// Approve student
function approveStudent(schoolId) {
    showConfirmationDialog(
        'Approve Student',
        'Are you sure you want to approve this student?',
        function() {
            fetch(window.APP_CONFIG.API_BASE_URL + 'students.php', {
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
    );
}

// Decline student
function declineStudent(schoolId) {
    showConfirmationDialog(
        'Decline Student',
        'Are you sure you want to decline this student?',
        function() {
            fetch(window.APP_CONFIG.API_BASE_URL + 'students.php', {
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
    );
}

// Delete student
function deleteStudent(schoolId) {
    showConfirmationDialog(
        'Delete Student',
        'Are you sure you want to delete this student record? This action cannot be undone.',
        function() {
            fetch(window.APP_CONFIG.API_BASE_URL + 'students.php', {
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
    );
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

// Open Create Student Modal
function openCreateStudentModal() {
    const modal = document.getElementById('createStudentModal');
    if (modal) {
        modal.classList.remove('hidden');
        loadSections();
    }
}

// Close Create Student Modal
function closeCreateStudentModal() {
    const modal = document.getElementById('createStudentModal');
    if (modal) {
        modal.classList.add('hidden');
        document.getElementById('createStudentForm').reset();
    }
}

// Load sections for dropdown
function loadSections() {
    fetch(window.APP_CONFIG.API_BASE_URL + 'students.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'operation=get_sections&json={}'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data) {
            const select = document.getElementById('section_id');
            if (select) {
                // Clear existing options except the first one
                select.innerHTML = '<option value="">Select a section</option>';
                
                data.data.forEach(section => {
                    const option = document.createElement('option');
                    option.value = section.id;
                    option.textContent = `${section.section_name} - ${section.school_name || 'No School'}`;
                    select.appendChild(option);
                });
            }
        } else {
            showNotification('Failed to load sections', 'error');
        }
    })
    .catch(error => {
        console.error('Error loading sections:', error);
        showNotification('Error loading sections', 'error');
    });
}

// Create student
function createStudent(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const studentData = {
        school_id: formData.get('school_id'),
        firstname: formData.get('firstname'),
        lastname: formData.get('lastname'),
        middlename: formData.get('middlename'),
        email: formData.get('email'),
        section_id: formData.get('section_id')
        // Password will be automatically set to School ID in the backend
    };
    
    fetch(window.APP_CONFIG.API_BASE_URL + 'students.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `operation=create&json=${encodeURIComponent(JSON.stringify(studentData))}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message || 'Student created successfully', 'success');
            closeCreateStudentModal();
            loadStats();
            loadStudents(currentTab);
        } else {
            showNotification(data.message || 'Failed to create student', 'error');
        }
    })
    .catch(error => {
        console.error('Error creating student:', error);
        showNotification('Error creating student', 'error');
    });
}