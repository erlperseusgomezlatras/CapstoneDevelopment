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
document.addEventListener('click', function (event) {
    if (!event.target.matches('.dropdown button') && !event.target.closest('.dropdown-content')) {
        document.querySelectorAll('.dropdown-content').forEach(dropdown => {
            dropdown.classList.remove('show');
        });
    }
});

// Teacher Management Functions
let currentEditingTeacher = null;
let currentResetTeacher = null;

function openAddModal() {
    currentEditingTeacher = null;
    const modalTitle = document.getElementById('modalTitle');
    const teacherForm = document.getElementById('teacherForm');
    const resetPasswordBtn = document.getElementById('resetPasswordBtn');

    if (modalTitle) modalTitle.textContent = 'Add New Teacher';
    if (teacherForm) teacherForm.reset();
    clearValidationErrors();

    // Hide reset password button for adding
    if (resetPasswordBtn) resetPasswordBtn.style.display = 'none';

    document.getElementById('teacherModal').classList.add('show');
}

function openEditModal(teacher) {
    currentEditingTeacher = teacher;

    // Check if modal elements exist
    const modalTitle = document.getElementById('modalTitle');
    const resetPasswordBtn = document.getElementById('resetPasswordBtn');
    const teacherModal = document.getElementById('teacherModal');

    if (modalTitle) modalTitle.textContent = 'Edit Teacher';

    // Show reset password button for editing
    if (resetPasswordBtn) resetPasswordBtn.style.display = 'inline-flex';

    // Check form elements
    const schoolIdEl = document.getElementById('schoolId');
    const firstNameEl = document.getElementById('firstName');
    const lastNameEl = document.getElementById('lastName');
    const middleNameEl = document.getElementById('middleName');
    const emailEl = document.getElementById('email');
    const isActiveEl = document.getElementById('isActive');

    // Populate form fields
    if (schoolIdEl) schoolIdEl.value = teacher.school_id;
    if (firstNameEl) firstNameEl.value = teacher.firstname;
    if (lastNameEl) lastNameEl.value = teacher.lastname;
    if (middleNameEl) middleNameEl.value = teacher.middlename || '';
    if (emailEl) emailEl.value = teacher.email || '';
    if (isActiveEl) isActiveEl.checked = teacher.isActive == 1;

    // Store original school_id for validation
    if (schoolIdEl) schoolIdEl.dataset.originalSchoolId = teacher.school_id;

    clearValidationErrors();
    if (teacherModal) teacherModal.classList.add('show');
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

    const schoolIdEl = document.getElementById('schoolId');
    const firstNameEl = document.getElementById('firstName');
    const lastNameEl = document.getElementById('lastName');
    const emailEl = document.getElementById('email');

    const schoolId = schoolIdEl ? schoolIdEl.value.trim() : '';
    const firstName = firstNameEl ? firstNameEl.value.trim() : '';
    const lastName = lastNameEl ? lastNameEl.value.trim() : '';
    const email = emailEl ? emailEl.value.trim() : '';

    if (!schoolId) {
        const schoolIdError = document.getElementById('schoolIdError');
        if (schoolIdError) schoolIdError.textContent = 'School ID is required';
        isValid = false;
    }

    if (!firstName) {
        const firstNameError = document.getElementById('firstNameError');
        if (firstNameError) firstNameError.textContent = 'First name is required';
        isValid = false;
    }

    if (!lastName) {
        const lastNameError = document.getElementById('lastNameError');
        if (lastNameError) lastNameError.textContent = 'Last name is required';
        isValid = false;
    }

    if (!email) {
        const emailError = document.getElementById('emailError');
        if (emailError) emailError.textContent = 'Email is required';
        isValid = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        const emailError = document.getElementById('emailError');
        if (emailError) emailError.textContent = 'Invalid email format';
        isValid = false;
    }

    return isValid;
}

async function saveTeacher(formData) {
    try {
        console.log('Saving teacher with data:', formData);

        const apiFormData = new FormData();
        apiFormData.append('operation', currentEditingTeacher ? 'update' : 'create');
        apiFormData.append('json', JSON.stringify(formData));

        const response = await fetch(window.APP_CONFIG.API_BASE_URL + 'teachers.php', {
            method: 'POST',
            body: apiFormData
        });

        const result = await response.json();
        console.log('API response:', result);

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

// Reset Password Functions
function openResetPasswordModal() {
    if (!currentEditingTeacher) return;

    currentResetTeacher = currentEditingTeacher;
    const resetTeacherName = document.getElementById('resetTeacherName');
    const resetPassword = document.getElementById('resetPassword');
    const resetPasswordHint = document.getElementById('resetPasswordHint');
    const resetPasswordModal = document.getElementById('resetPasswordModal');

    if (resetTeacherName) {
        resetTeacherName.textContent =
            `${currentEditingTeacher.firstname} ${currentEditingTeacher.lastname}`;
    }

    // Auto-fill password with school_id by default
    const schoolId = currentEditingTeacher.school_id;
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
    currentResetTeacher = null;
}

function openResetPasswordModalForTeacher(teacher) {
    currentResetTeacher = teacher;
    const resetTeacherName = document.getElementById('resetTeacherName');
    const resetPassword = document.getElementById('resetPassword');
    const resetPasswordHint = document.getElementById('resetPasswordHint');
    const resetPasswordModal = document.getElementById('resetPasswordModal');

    if (resetTeacherName) {
        resetTeacherName.textContent =
            `${teacher.firstname} ${teacher.lastname}`;
    }

    // Auto-fill password with school_id by default
    const schoolId = teacher.school_id;
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
    if (currentResetTeacher) {
        const schoolId = currentResetTeacher.school_id;
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

    if (!resetPassword || !currentResetTeacher) return;

    const newPassword = resetPassword.value.trim();

    if (!newPassword) {
        if (resetPasswordError) resetPasswordError.textContent = 'Password is required';
        return;
    }

    // Skip length validation if password is default school_id
    const isDefaultSchoolId = newPassword === currentResetTeacher.school_id;

    if (!isDefaultSchoolId && newPassword.length < 6) {
        if (resetPasswordError) resetPasswordError.textContent = 'Password must be at least 6 characters';
        return;
    }

    try {
        const formData = new FormData();
        formData.append('operation', 'update');
        formData.append('json', JSON.stringify({
            school_id: currentResetTeacher.school_id,
            password: newPassword,
            firstname: currentResetTeacher.firstname,
            lastname: currentResetTeacher.lastname,
            middlename: currentResetTeacher.middlename || '',
            email: currentResetTeacher.email || '',
            section_id: currentResetTeacher.section_id || '',
            isActive: currentResetTeacher.isActive
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
                        <div class="dropdown-item text-orange-600 hover:text-orange-900" 
                             onclick="closeDropdown('${teacher.school_id}'); openResetPasswordModalForTeacher(${JSON.stringify(teacher).replace(/"/g, '&quot;')})">
                            <i class="fas fa-key mr-2"></i> Reset Password
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

// Form submission
document.addEventListener('DOMContentLoaded', function () {
    const teacherForm = document.getElementById('teacherForm');
    if (teacherForm) {
        teacherForm.addEventListener('submit', function (e) {
            e.preventDefault();

            if (!validateForm()) {
                return;
            }

            const schoolIdEl = document.getElementById('schoolId');
            const firstNameEl = document.getElementById('firstName');
            const lastNameEl = document.getElementById('lastName');
            const middleNameEl = document.getElementById('middleName');
            const emailEl = document.getElementById('email');
            const isActiveEl = document.getElementById('isActive');

            const formData = {
                school_id: schoolIdEl ? schoolIdEl.value.trim() : '',
                original_school_id: schoolIdEl ? (schoolIdEl.dataset.originalSchoolId || '') : '',
                firstname: firstNameEl ? firstNameEl.value.trim() : '',
                lastname: lastNameEl ? lastNameEl.value.trim() : '',
                middlename: middleNameEl ? middleNameEl.value.trim() : '',
                email: emailEl ? emailEl.value.trim() : '',
                isActive: isActiveEl ? (isActiveEl.checked ? 1 : 0) : 0,
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
});

// Close modal when clicking outside
document.addEventListener('DOMContentLoaded', function () {
    const teacherModal = document.getElementById('teacherModal');
    if (teacherModal) {
        teacherModal.addEventListener('click', function (e) {
            if (e.target === this) {
                closeModal();
            }
        });
    }
});