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
        // Calculate live hours if student is currently clocked in
        let liveRendered = parseFloat(student.rendered_hours || 0);
        if (student.current_time_in && student.ongoing_date) {
            const extraHours = calculateLiveHours(student.ongoing_date, student.current_time_in, null, 0);
            liveRendered += parseFloat(extraHours);
        }

        const required = parseFloat(student.required_hours || 360);
        const remaining = Math.max(0, required - liveRendered);
        const progressPercentage = required > 0 ? (liveRendered / required * 100) : 0;

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
                    <span class="font-medium ${student.current_time_in ? 'text-blue-600' : ''}">${liveRendered.toFixed(2)}</span> hrs
                    ${student.current_time_in ? '<i class="fas fa-sync-alt fa-spin text-blue-500 text-xs ml-1" title="Live Update"></i>' : ''}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <span class="font-medium">${required.toFixed(2)}</span> hrs
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <span class="font-medium ${remaining <= 0 ? 'text-green-600' : 'text-orange-600'}">
                        ${remaining.toFixed(2)} hrs
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
                    <button onclick="viewStudentDetails('${student.student_id}')" 
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


// View detailed student information
function viewStudentDetails(studentId) {
    // Open modal
    openAttendanceModal();

    // Reset modal content
    document.getElementById('modalStudentName').textContent = 'Loading...';
    document.getElementById('modalStudentId').textContent = studentId;
    document.getElementById('modalRenderedHours').textContent = '-- hrs';
    document.getElementById('modalRequiredHours').textContent = '360.00 hrs';
    document.getElementById('modalRemainingHours').textContent = '-- hrs';

    const historyTable = document.getElementById('modalAttendanceHistory');
    historyTable.innerHTML = `
        <tr>
            <td colspan="5" class="px-6 py-4 text-center">
                <i class="fas fa-spinner fa-spin mr-2"></i>Loading history...
            </td>
        </tr>
    `;

    // Find student in local storage to get basic info
    let studentInfo = null;
    for (const sectionId in allStudentsData) {
        const student = allStudentsData[sectionId].find(s => s.student_id == studentId);
        if (student) {
            studentInfo = student;
            break;
        }
    }

    if (studentInfo) {
        const rendered = parseFloat(studentInfo.rendered_hours || 0);
        const required = parseFloat(studentInfo.required_hours || 360);
        const remaining = Math.max(0, required - rendered);

        document.getElementById('modalStudentName').textContent = `${studentInfo.firstname} ${studentInfo.lastname}`;
        document.getElementById('modalRenderedHours').textContent = `${rendered.toFixed(2)} hrs`;
        document.getElementById('modalRequiredHours').textContent = `${required.toFixed(2)} hrs`;
        document.getElementById('modalRemainingHours').textContent = `${remaining.toFixed(2)} hrs`;
    }

    // Fetch detailed history
    const formData = new FormData();
    formData.append('operation', 'get_student_attendance_history');
    formData.append('json', JSON.stringify({
        student_id: studentId,
        filters: currentFilters
    }));

    fetch(`${baseUrl}/api/attendance.php`, {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayModalHistory(data.attendance_history);
            } else {
                historyTable.innerHTML = `<tr><td colspan="5" class="px-6 py-4 text-center text-red-500">Error: ${data.message}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Error fetching history:', error);
            historyTable.innerHTML = `<tr><td colspan="5" class="px-6 py-4 text-center text-red-500">Failed to fetch history</td></tr>`;
        });
}

// Display history in modal
function displayModalHistory(history) {
    const historyTable = document.getElementById('modalAttendanceHistory');

    if (!history || history.length === 0) {
        historyTable.innerHTML = `
            <tr>
                <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                    No attendance records found for this student.
                </td>
            </tr>
        `;
        document.getElementById('modalRenderedHours').textContent = '0.00 hrs';
        document.getElementById('modalRemainingHours').textContent = document.getElementById('modalRequiredHours').textContent;
        return;
    }

    let totalLiveHours = 0;

    historyTable.innerHTML = history.map(record => {
        const liveHours = calculateLiveHours(record.attendance_date, record.check_in_time, record.check_out_time, record.hours_rendered);
        totalLiveHours += parseFloat(liveHours);

        const isOngoing = !record.check_out_time && record.status === 'Present';

        return `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                    ${formatDate(record.attendance_date)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    ${record.check_in_time ? formatTimeTo12Hour(record.check_in_time) : '--'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    ${record.check_out_time ? formatTimeTo12Hour(record.check_out_time) : (isOngoing ? '<span class="text-blue-500 italic"><i class="fas fa-sync-alt fa-spin mr-1"></i>In Progress</span>' : '--')}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${record.status === 'Present' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                        ${record.status}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-bold">
                    ${liveHours} hrs
                </td>
            </tr>
        `;
    }).join('');

    // Update the total rendered hours at the top of the modal
    const required = parseFloat(document.getElementById('modalRequiredHours').textContent) || 360;
    const remaining = Math.max(0, required - totalLiveHours);

    document.getElementById('modalRenderedHours').textContent = `${totalLiveHours.toFixed(2)} hrs`;
    document.getElementById('modalRemainingHours').textContent = `${remaining.toFixed(2)} hrs`;
}

// Calculate live rendered hours
function calculateLiveHours(dateStr, checkInTime, checkOutTime, hoursRendered) {
    if (checkOutTime) return parseFloat(hoursRendered || 0).toFixed(2);
    if (!checkInTime) return '0.00';

    try {
        // Parse dates
        const now = new Date();
        const [year, month, day] = dateStr.split('-').map(Number);
        const [hours, minutes, seconds] = checkInTime.split(':').map(Number);

        const inDate = new Date(year, month - 1, day, hours, minutes, seconds);

        // If it's a future date (shouldn't happen) or if it's more than 24h old, don't show live calc
        const diffMs = now - inDate;

        if (diffMs < 0) return '0.00';

        // Only calculate live if it's within the last 24 hours (for ongoing graveyard or long shifts)
        if (diffMs > 24 * 60 * 60 * 1000) return parseFloat(hoursRendered || 0).toFixed(2);

        const diffHours = diffMs / (1000 * 60 * 60);
        return diffHours.toFixed(2);

    } catch (e) {
        console.error('Error calculating live hours:', e);
        return parseFloat(hoursRendered || 0).toFixed(2);
    }
}

// Modal controls
function openAttendanceModal() {
    const modal = document.getElementById('attendanceModal');
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden'; // Prevent scrolling
}

function closeAttendanceModal() {
    const modal = document.getElementById('attendanceModal');
    modal.classList.add('hidden');
    document.body.style.overflow = ''; // Restore scrolling
}

// Helper to format time to 12 hour
function formatTimeTo12Hour(time24) {
    if (!time24) return '--';
    const [hours, minutes, seconds] = time24.split(':');
    let hr = parseInt(hours);
    const ampm = hr >= 12 ? 'PM' : 'AM';
    hr = hr % 12;
    hr = hr ? hr : 12;
    return `${hr}:${minutes} ${ampm}`;
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