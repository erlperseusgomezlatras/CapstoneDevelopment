// Mobile Sidebar
const mobileMenuBtn = document.getElementById('mobileMenuBtn');
const mobileSidebar = document.getElementById('mobileSidebar');
const closeMobileSidebar = document.getElementById('closeMobileSidebar');
const mobileSidebarOverlay = document.getElementById('mobileSidebarOverlay');

function openMobileSidebar() {
    if (mobileSidebar) mobileSidebar.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeMobileSidebarFunc() {
    if (mobileSidebar) mobileSidebar.classList.add('hidden');
    document.body.style.overflow = 'auto';
}

if (mobileMenuBtn) mobileMenuBtn.addEventListener('click', openMobileSidebar);
if (closeMobileSidebar) closeMobileSidebar.addEventListener('click', closeMobileSidebarFunc);
if (mobileSidebarOverlay) mobileSidebarOverlay.addEventListener('click', closeMobileSidebarFunc);

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
    if (currentDropdown) {
        currentDropdown.classList.toggle('show');
    }
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
    const modalTitle = document.getElementById('modalTitle');
    const coordinatorForm = document.getElementById('coordinatorForm');
    const passwordField = document.getElementById('passwordField');
    const password = document.getElementById('password');
    const resetPasswordBtn = document.getElementById('resetPasswordBtn');
    const schoolIdInput = document.getElementById('schoolId');
    const passwordHint = document.getElementById('passwordHint');
    
    if (modalTitle) modalTitle.textContent = 'Add New Coordinator';
    if (coordinatorForm) coordinatorForm.reset();
    clearValidationErrors();
    
    // Show password field for adding
    if (passwordField) passwordField.style.display = 'block';
    if (password) password.required = true;
    
    // Hide reset password button for adding
    if (resetPasswordBtn) resetPasswordBtn.style.display = 'none';
    
    // Auto-fill password with school_id when it's entered
    if (schoolIdInput) {
        schoolIdInput.addEventListener('input', function() {
            const schoolId = this.value.trim();
            if (password) {
                password.value = schoolId;
            }
            if (passwordHint) {
                passwordHint.textContent = schoolId ? 'Password auto-filled with School ID' : '';
            }
        });
    }
    
    const coordinatorModal = document.getElementById('coordinatorModal');
    if (coordinatorModal) coordinatorModal.classList.add('show');
    loadSections();
}

function openEditModal(coordinator) {
    currentEditingCoordinator = coordinator;
    const modalTitle = document.getElementById('modalTitle');
    const resetPasswordBtn = document.getElementById('resetPasswordBtn');
    const passwordField = document.getElementById('passwordField');
    const password = document.getElementById('password');
    
    if (modalTitle) modalTitle.textContent = 'Edit Coordinator';
    
    // Show reset password button for editing
    if (resetPasswordBtn) resetPasswordBtn.style.display = 'inline-flex';
    
    // Hide password field for editing (use separate reset modal)
    if (passwordField) passwordField.style.display = 'none';
    if (password) password.required = false;
    
    // Populate form fields
    const schoolId = document.getElementById('schoolId');
    const firstname = document.getElementById('firstname');
    const lastname = document.getElementById('lastname');
    const middlename = document.getElementById('middlename');
    const email = document.getElementById('email');
    const isActive = document.getElementById('isActive');
    
    if (schoolId) schoolId.value = coordinator.school_id;
    if (firstname) firstname.value = coordinator.firstname;
    if (lastname) lastname.value = coordinator.lastname;
    if (middlename) middlename.value = coordinator.middlename || '';
    if (email) email.value = coordinator.email || '';
    if (isActive) isActive.checked = coordinator.isActive == 1;
    
    // Load sections and set selected section
    loadSections().then(() => {
        const section = document.getElementById('section');
        if (section) {
            section.value = coordinator.section_id || '';
            updatePartneredSchoolInfo(coordinator.section_id);
        }
    });
    
    clearValidationErrors();
    const coordinatorModal = document.getElementById('coordinatorModal');
    if (coordinatorModal) coordinatorModal.classList.add('show');
}

function closeModal() {
    const coordinatorModal = document.getElementById('coordinatorModal');
    const coordinatorForm = document.getElementById('coordinatorForm');
    
    if (coordinatorModal) coordinatorModal.classList.remove('show');
    if (coordinatorForm) coordinatorForm.reset();
    clearValidationErrors();
    currentEditingCoordinator = null;
}

function clearValidationErrors() {
    document.querySelectorAll('.text-red-500').forEach(el => el.textContent = '');
}

function showNotification(message, type = 'info') {
    const container = document.getElementById('notificationContainer');
    if (!container) return;
    
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
    const resetCoordinatorName = document.getElementById('resetCoordinatorName');
    const resetPassword = document.getElementById('resetPassword');
    const resetPasswordHint = document.getElementById('resetPasswordHint');
    const resetPasswordModal = document.getElementById('resetPasswordModal');
    
    if (resetCoordinatorName) {
        resetCoordinatorName.textContent = 
            `${currentEditingCoordinator.firstname} ${currentEditingCoordinator.lastname}`;
    }
    
    // Auto-fill password with school_id by default
    const schoolId = currentEditingCoordinator.school_id;
    if (resetPassword) resetPassword.value = schoolId;
    
    // Clear previous values and set hints
    const resetPasswordError = document.getElementById('resetPasswordError');
    if (resetPasswordError) resetPasswordError.textContent = '';
    if (resetPasswordHint) {
        resetPasswordHint.textContent = 
            `Password set to School ID: ${schoolId}. You can edit this if needed.`;
    }
    
    if (resetPasswordModal) resetPasswordModal.classList.add('show');
}

function closeResetPasswordModal() {
    const resetPasswordModal = document.getElementById('resetPasswordModal');
    if (resetPasswordModal) resetPasswordModal.classList.remove('show');
    currentResetCoordinator = null;
}

function toggleResetPasswordVisibility() {
    const passwordInput = document.getElementById('resetPassword');
    const toggleIcon = document.getElementById('resetPasswordToggleIcon');
    
    if (!passwordInput || !toggleIcon) return;
    
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
        const resetPassword = document.getElementById('resetPassword');
        const resetPasswordHint = document.getElementById('resetPasswordHint');
        const resetPasswordError = document.getElementById('resetPasswordError');
        
        if (resetPassword) resetPassword.value = schoolId;
        if (resetPasswordHint) resetPasswordHint.textContent = `Password reset to School ID: ${schoolId}`;
        if (resetPasswordError) resetPasswordError.textContent = '';
    }
}

async function confirmResetPassword() {
    const resetPassword = document.getElementById('resetPassword');
    const resetPasswordError = document.getElementById('resetPasswordError');
    
    if (!resetPassword || !currentResetCoordinator) return;
    
    const newPassword = resetPassword.value.trim();
    
    if (!newPassword) {
        if (resetPasswordError) resetPasswordError.textContent = 'Password is required';
        return;
    }
    
    // Skip length validation if password is default school_id
    const isDefaultSchoolId = newPassword === currentResetCoordinator.school_id;
    
    if (!isDefaultSchoolId && newPassword.length < 6) {
        if (resetPasswordError) resetPasswordError.textContent = 'Password must be at least 6 characters';
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

        const response = await fetch(window.APP_CONFIG.API_BASE_URL + 'teachers.php', {
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
    
    if (!passwordInput || !toggleIcon) return;
    
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
    const schoolIdInput = document.getElementById('schoolId');
    const password = document.getElementById('password');
    const passwordHint = document.getElementById('passwordHint');
    const passwordError = document.getElementById('passwordError');
    
    if (!schoolIdInput) return;
    
    const schoolId = schoolIdInput.value.trim();
    if (password) password.value = schoolId;
    if (passwordHint) passwordHint.textContent = schoolId ? 'Password reset to School ID' : '';
    if (passwordError) {
        passwordError.textContent = schoolId ? '' : 'Please enter School ID first';
    }
}

function validateForm() {
    clearValidationErrors();
    let isValid = true;
    
    const schoolId = document.getElementById('schoolId')?.value.trim();
    const firstname = document.getElementById('firstname')?.value.trim();
    const lastname = document.getElementById('lastname')?.value.trim();
    const email = document.getElementById('email')?.value.trim();
    const passwordField = document.getElementById('passwordField');
    const password = passwordField?.style.display !== 'none' ? 
                   document.getElementById('password')?.value : '';
    
    if (!schoolId) {
        const schoolIdError = document.getElementById('schoolIdError');
        if (schoolIdError) schoolIdError.textContent = 'School ID is required';
        isValid = false;
    }
    
    if (!firstname) {
        const firstnameError = document.getElementById('firstnameError');
        if (firstnameError) firstnameError.textContent = 'First name is required';
        isValid = false;
    }
    
    if (!lastname) {
        const lastnameError = document.getElementById('lastnameError');
        if (lastnameError) lastnameError.textContent = 'Last name is required';
        isValid = false;
    }
    
    if (!email) {
        const emailError = document.getElementById('emailError');
        if (emailError) emailError.textContent = 'Email is required';
        isValid = false;
    } else if (!/\S+@\S+\.\S+/.test(email)) {
        const emailError = document.getElementById('emailError');
        if (emailError) emailError.textContent = 'Invalid email format';
        isValid = false;
    }
    
    // Password validation only for adding new coordinators
    if (passwordField && passwordField.style.display !== 'none') {
        if (!password) {
            const passwordError = document.getElementById('passwordError');
            if (passwordError) passwordError.textContent = 'Password is required';
            isValid = false;
        } else if (password.length < 6) {
            const passwordError = document.getElementById('passwordError');
            if (passwordError) passwordError.textContent = 'Password must be at least 6 characters';
            isValid = false;
        }
    }
    
    return isValid;
}

async function saveCoordinator(formData) {
    try {
        const apiFormData = new FormData();
        apiFormData.append('operation', currentEditingCoordinator ? 'update' : 'create');
        apiFormData.append('json', JSON.stringify(formData));
        
        const response = await fetch(window.APP_CONFIG.API_BASE_URL + 'teachers.php', {
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
        
        const response = await fetch(window.APP_CONFIG.API_BASE_URL + 'teachers.php', {
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
        
        const response = await fetch(window.APP_CONFIG.API_BASE_URL + 'teachers.php', {
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
        
        const response = await fetch(window.APP_CONFIG.API_BASE_URL + 'teachers.php', {
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
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    
    if (!searchInput || !statusFilter) return;
    
    const searchTerm = searchInput.value.trim().toLowerCase();
    const statusFilterValue = statusFilter.value;
    
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
    if (statusFilterValue !== '') {
        filteredCoordinators = filteredCoordinators.filter(coordinator => coordinator.isActive == statusFilterValue);
    }
    
    renderCoordinatorsTable(filteredCoordinators);
}

function renderCoordinatorsTable(coordinators) {
    const tbody = document.getElementById('coordinatorsTableBody');
    const noDataMessage = document.getElementById('noDataMessage');
    
    if (!tbody) return;
    
    if (coordinators.length === 0) {
        tbody.innerHTML = '';
        if (noDataMessage) noDataMessage.classList.remove('hidden');
        return;
    }
    
    if (noDataMessage) noDataMessage.classList.add('hidden');
    
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
        
        const response = await fetch(window.APP_CONFIG.API_BASE_URL + 'teachers.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            const select = document.getElementById('section');
            if (select) {
                select.innerHTML = '<option value="">Select Section</option>' +
                    result.data.map(section => 
                        `<option value="${section.id}">${section.section_name}</option>`
                    ).join('');
                
                // Add change event listener to show partnered school info
                select.addEventListener('change', function() {
                    updatePartneredSchoolInfo(this.value);
                });
            }
        }
    } catch (error) {
        showNotification('An error occurred while loading sections', 'error');
        console.error('Error:', error);
    }
}

async function updatePartneredSchoolInfo(sectionId) {
    const infoDiv = document.getElementById('partneredSchoolInfo');
    
    if (!sectionId) {
        if (infoDiv) infoDiv.innerHTML = '';
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('operation', 'get_partnered_school');
        formData.append('json', JSON.stringify({ section_id: sectionId }));
        
        const response = await fetch(window.APP_CONFIG.API_BASE_URL + 'teachers.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success && result.data) {
            if (infoDiv) infoDiv.innerHTML = `
                <div class="bg-blue-50 border border-blue-200 rounded-md p-2">
                    <i class="fas fa-school text-blue-600 mr-2"></i>
                    <span class="text-blue-800">Partnered School: ${result.data.name}</span>
                </div>
            `;
        } else {
            if (infoDiv) infoDiv.innerHTML = `
                <div class="bg-yellow-50 border border-yellow-200 rounded-md p-2">
                    <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
                    <span class="text-yellow-800">No partnered school assigned to this section</span>
                </div>
            `;
        }
    } catch (error) {
        if (infoDiv) infoDiv.innerHTML = `
            <div class="bg-red-50 border border-red-200 rounded-md p-2">
                <i class="fas fa-exclamation-circle text-red-600 mr-2"></i>
                <span class="text-red-800">Error loading partnered school information</span>
            </div>
        `;
    }
}

// Form submission
document.addEventListener('DOMContentLoaded', function() {
    const coordinatorForm = document.getElementById('coordinatorForm');
    if (coordinatorForm) {
        coordinatorForm.addEventListener('submit', function(e) {
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
    }
    
    // Auto-search on input change
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    
    if (searchInput) searchInput.addEventListener('input', filterCoordinators);
    if (statusFilter) statusFilter.addEventListener('change', filterCoordinators);
    
    // Initialize
    loadCoordinators();
});

// Close modal when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    const coordinatorModal = document.getElementById('coordinatorModal');
    if (coordinatorModal) {
        coordinatorModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    }
});