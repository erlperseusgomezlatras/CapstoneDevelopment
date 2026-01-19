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
    
    if (!notification || !icon || !messageEl) return;
    
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
    const notification = document.getElementById('notification');
    if (notification) {
        notification.classList.add('hidden');
    }
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
        
        const response = await fetch(window.APP_CONFIG.API_BASE_URL + 'teachers.php', {
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
        
        const response = await fetch(window.APP_CONFIG.API_BASE_URL + 'teachers.php', {
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
        
        const response = await fetch(window.APP_CONFIG.API_BASE_URL + 'teachers.php', {
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
        
        const response = await fetch(window.APP_CONFIG.API_BASE_URL + 'teachers.php', {
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
    
    if (!tbody) return;
    
    if (teachers.length === 0) {
        tbody.innerHTML = '';
        if (noDataMessage) noDataMessage.classList.remove('hidden');
        return;
    }
    
    if (noDataMessage) noDataMessage.classList.add('hidden');
    
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
            }
        }
    } catch (error) {
        console.error('Error loading sections:', error);
    }
}

// Form submission
document.addEventListener('DOMContentLoaded', function() {
    const teacherForm = document.getElementById('teacherForm');
    if (teacherForm) {
        teacherForm.addEventListener('submit', function(e) {
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
    }
    
    // Auto-search on input change
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    
    if (searchInput) searchInput.addEventListener('input', filterTeachers);
    if (statusFilter) statusFilter.addEventListener('change', filterTeachers);
    
    // Initialize
    loadTeachers();
    loadSections();
});

// Close modal when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    const teacherModal = document.getElementById('teacherModal');
    if (teacherModal) {
        teacherModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    }
});