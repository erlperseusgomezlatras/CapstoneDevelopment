// Global variables
let currentFilters = {
    dateRange: 'today',
    fromDate: null,
    toDate: null,
    section: 'all',
    academicSession: 'all'
};

let allSectionsData = [];
let allStudentsData = {};

// Load section overview
function loadSectionOverview() {
    const sectionOverview = document.getElementById('section-overview');
    
    const formData = new FormData();
    formData.append('operation', 'get_section_overview');
    formData.append('json', JSON.stringify({
        filters: currentFilters // Use current filters for server-side processing
    }));
    
    fetch(`${baseUrl}/api/attendance.php`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            allSectionsData = data.sections;
            displaySectionOverview(data.sections);
        } else {
            showError('Failed to load section data: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error loading section overview:', error);
        showError('An error occurred while loading section data');
    });
}

// Display section overview cards
function displaySectionOverview(sections) {
    const sectionOverview = document.getElementById('section-overview');
    
    if (sections.length === 0) {
        sectionOverview.innerHTML = `
            <div class="text-center py-8 text-gray-500 col-span-full">
                <i class="fas fa-users-slash text-4xl mb-2"></i>
                <p>No sections found for the selected filters</p>
            </div>
        `;
        return;
    }
    
    sectionOverview.innerHTML = sections.map(section => `
        <div class="bg-white border border-gray-200 rounded-lg p-6 card-hover cursor-pointer" onclick="viewSectionDetails('${section.section_name}', '${section.section_id}')">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-lg font-semibold text-gray-900">${section.section_name}</h4>
                <span class="text-sm bg-blue-100 text-blue-800 px-2 py-1 rounded-full">
                    ${section.total_students} students
                </span>
            </div>
            
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Present Today</span>
                    <span class="text-sm font-medium text-green-600">${section.present_today}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Absent Today</span>
                    <span class="text-sm font-medium text-red-600">${section.absent_today}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Late Today</span>
                    <span class="text-sm font-medium text-yellow-600">${section.late_today}</span>
                </div>
            </div>
            
            <div class="mt-4 pt-4 border-t border-gray-200">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Attendance Rate</span>
                    <div class="flex items-center">
                        <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                            <div class="bg-green-500 h-2 rounded-full" style="width: ${section.attendance_rate}%"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-900">${section.attendance_rate}%</span>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <button class="w-full bg-blue-600 text-white px-3 py-2 rounded-md hover:bg-blue-700 transition-colors text-sm">
                    <i class="fas fa-eye mr-2"></i>View Details
                </button>
            </div>
        </div>
    `).join('');
}

// Load academic sessions for filter dropdown
function loadAcademicSessions() {
    const formData = new FormData();
    formData.append('operation', 'get_academic_sessions');
    formData.append('json', '{}');
    
    fetch(`${baseUrl}/api/attendance.php`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const academicSessionFilter = $('#academicSessionFilter');
            academicSessionFilter.empty().append('<option value="all">All Academic Sessions</option>');
            
            let activeSessionId = null;
            
            data.sessions.forEach(session => {
                const optionText = `${session.session_name} ${session.is_Active == 1 ? '(Active)' : ''}`;
                academicSessionFilter.append(`<option value="${session.academic_session_id}">${optionText}</option>`);
                
                // Store active session ID
                if (session.is_Active == 1) {
                    activeSessionId = session.academic_session_id;
                }
            });
            
            // Initialize Select2 for academic session filter
            academicSessionFilter.select2({
                placeholder: 'Select Academic Session',
                allowClear: false,
                width: '100%'
            });
            
            // Set active session as default if found
            if (activeSessionId) {
                academicSessionFilter.val(activeSessionId).trigger('change');
                currentFilters.academicSession = activeSessionId;
            }
        }
    })
    .catch(error => {
        console.error('Error loading academic sessions:', error);
    });
}

// Load sections for filter dropdown
function loadSections() {
    const formData = new FormData();
    formData.append('operation', 'get_sections');
    formData.append('json', '{}');
    
    fetch(`${baseUrl}/api/attendance.php`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const sectionFilter = $('#sectionFilter');
            sectionFilter.empty().append('<option value="all">All Sections</option>');
            
            data.sections.forEach(section => {
                sectionFilter.append(`<option value="${section.section_id}">${section.section_name}</option>`);
            });
            
            // Initialize Select2 for section filter
            sectionFilter.select2({
                placeholder: 'Select Section',
                allowClear: false,
                width: '100%'
            });
        }
    })
    .catch(error => {
        console.error('Error loading sections:', error);
    });
}

// Apply filters (now uses server-side filtering)
function applyFilters() {
    const dateFilter = document.getElementById('dateFilter').value;
    const sectionFilter = $('#sectionFilter').val();
    const academicSessionFilter = $('#academicSessionFilter').val();
    
    currentFilters.dateRange = dateFilter;
    currentFilters.section = sectionFilter;
    currentFilters.academicSession = academicSessionFilter;
    
    // Handle custom date range
    if (dateFilter === 'custom') {
        const fromDate = document.getElementById('fromDate').value;
        const toDate = document.getElementById('toDate').value;
        
        if (!fromDate || !toDate) {
            showError('Please select both from and to dates for custom range');
            return;
        }
        
        currentFilters.fromDate = fromDate;
        currentFilters.toDate = toDate;
    } else {
        currentFilters.fromDate = null;
        currentFilters.toDate = null;
    }
    
    // Reload data with new filters
    loadSectionOverview();
    
    // Close student details if open
    closeStudentDetails();
}

// Filter sections data based on current filters
function filterSectionsData(sections) {
    if (!sections || sections.length === 0) return [];
    
    return sections.filter(section => {
        // Filter by section
        if (currentFilters.section !== 'all' && section.section_id != currentFilters.section) {
            return false;
        }
        
        // Date filtering is handled at display level since we have all data
        return true;
    });
}

// View section details
function viewSectionDetails(sectionName, sectionId) {
    const studentSection = document.getElementById('studentAttendanceSection');
    const sectionNameSpan = document.getElementById('sectionName');
    
    sectionNameSpan.textContent = sectionName;
    studentSection.classList.remove('hidden');
    
    // Load student attendance data for this section
    loadStudentAttendance(sectionId);
    
    // Scroll to student details
    studentSection.scrollIntoView({ behavior: 'smooth' });
}

// Load student attendance for a section
function loadStudentAttendance(sectionId) {
    const studentTable = document.getElementById('studentAttendanceTable');
    
    studentTable.innerHTML = `
        <tr>
            <td colspan="8" class="px-6 py-4 text-center">
                <i class="fas fa-spinner fa-spin"></i>
                <span class="ml-2">Loading student data...</span>
            </td>
        </tr>
    `;
    
    const formData = new FormData();
    formData.append('operation', 'get_student_attendance');
    formData.append('json', JSON.stringify({
        section_id: sectionId,
        filters: currentFilters // Use current filters for server-side processing
    }));
    
    fetch(`${baseUrl}/api/attendance.php`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Store the data for future use
            allStudentsData[sectionId] = data.students;
            displayStudentAttendance(data.students);
        } else {
            showError('Failed to load student data: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error loading student attendance:', error);
        showError('An error occurred while loading student data');
    });
}

// Display student attendance table
function displayStudentAttendance(students) {
    const studentTable = document.getElementById('studentAttendanceTable');
    
    if (students.length === 0) {
        studentTable.innerHTML = `
            <tr>
                <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                    <i class="fas fa-user-slash text-2xl mb-2"></i>
                    <p>No students found for this section</p>
                </td>
            </tr>
        `;
        return;
    }
    
    studentTable.innerHTML = students.map(student => {
        const progressPercentage = student.required_hours > 0 ? 
            (student.rendered_hours / student.required_hours * 100) : 0;
        
        const progressColor = progressPercentage >= 80 ? 'bg-green-500' : 
                            progressPercentage >= 50 ? 'bg-yellow-500' : 'bg-red-500';
        
        return `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10">
                            <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                <i class="fas fa-user text-gray-600"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">${student.firstname} ${student.lastname}</div>
                            <div class="text-sm text-gray-500">${student.email}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    ${student.student_id}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    ${student.school_name}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <span class="font-medium">${student.rendered_hours}</span> hrs
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <span class="font-medium">${student.required_hours}</span> hrs
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <span class="font-medium ${student.remaining_hours <= 0 ? 'text-green-600' : 'text-orange-600'}">
                        ${student.remaining_hours} hrs
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="w-20 bg-gray-200 rounded-full h-2 mr-2">
                            <div class="${progressColor} h-2 rounded-full transition-all duration-300" 
                                 style="width: ${Math.min(progressPercentage, 100)}%"></div>
                        </div>
                        <span class="text-sm text-gray-900">${progressPercentage.toFixed(1)}%</span>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button onclick="viewStudentHistory(${student.student_id})" 
                            class="text-blue-600 hover:text-blue-900 mr-3">
                        <i class="fas fa-history"></i> History
                    </button>
                    <button onclick="viewStudentDetails(${student.student_id})" 
                            class="text-green-600 hover:text-green-900">
                        <i class="fas fa-eye"></i> Details
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

// Close student details section
function closeStudentDetails() {
    document.getElementById('studentAttendanceSection').classList.add('hidden');
}

// View student attendance history
function viewStudentHistory(studentId) {
    // This would open a modal or navigate to a detailed history view
    alert(`View attendance history for student ID: ${studentId}`);
}

// View detailed student information
function viewStudentDetails(studentId) {
    // This would open a modal or navigate to detailed student view
    alert(`View detailed information for student ID: ${studentId}`);
}

// Show error message
function showError(message) {
    // Create toast notification
    const toast = document.createElement('div');
    toast.className = 'fixed top-4 right-4 z-50 bg-red-600 text-white px-6 py-3 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full';
    toast.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <span class="text-sm font-medium">${message}</span>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    // Animate in
    setTimeout(() => {
        toast.classList.remove('translate-x-full');
    }, 100);
    
    // Remove after 3 seconds
    setTimeout(() => {
        toast.classList.add('translate-x-full');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Show success message
function showSuccess(message) {
    const toast = document.createElement('div');
    toast.className = 'fixed top-4 right-4 z-50 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full';
    toast.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <span class="text-sm font-medium">${message}</span>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    // Animate in
    setTimeout(() => {
        toast.classList.remove('translate-x-full');
    }, 100);
    
    // Remove after 3 seconds
    setTimeout(() => {
        toast.classList.add('translate-x-full');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Utility function to format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Utility function to calculate date ranges
function getDateRange(range) {
    const today = new Date();
    let fromDate, toDate;
    
    switch (range) {
        case 'today':
            fromDate = toDate = today.toISOString().split('T')[0];
            break;
        case 'week':
            const weekStart = new Date(today);
            weekStart.setDate(today.getDate() - today.getDay());
            fromDate = weekStart.toISOString().split('T')[0];
            toDate = today.toISOString().split('T')[0];
            break;
        case 'month':
            const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
            fromDate = monthStart.toISOString().split('T')[0];
            toDate = today.toISOString().split('T')[0];
            break;
        case 'all':
            fromDate = null;
            toDate = null;
            break;
        default:
            fromDate = toDate = today.toISOString().split('T')[0];
    }
    
    return { fromDate, toDate };
}