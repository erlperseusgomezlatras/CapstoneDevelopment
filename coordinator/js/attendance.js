// Global variables
let currentFilters = {
    dateRange: 'all',
    fromDate: null,
    toDate: null,
    section: 'all',
    academicSession: 'all',
    coordinator_id: coordinatorId // Pass this to backend
};

let allSectionsData = [];
let allStudentsData = {};

// Initialize Page
document.addEventListener('DOMContentLoaded', function () {
    loadSections();
    loadAcademicSessions();
});

// Load coordinator's assigned sections for filter dropdown
function loadSections() {
    const formData = new FormData();
    formData.append('operation', 'get_sections');
    formData.append('json', JSON.stringify({ coordinator_id: coordinatorId }));

    fetch(window.APP_CONFIG.API_BASE_URL + 'attendance.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const sectionFilter = $('#sectionFilter');
                sectionFilter.empty().append('<option value="all">All Assigned Sections</option>');

                data.sections.forEach(section => {
                    sectionFilter.append(`<option value="${section.section_id}">${section.section_name}</option>`);
                });

                // Initial load of overview
                loadSectionOverview();
            }
        })
        .catch(error => {
            console.error('Error loading sections:', error);
        });
}

// Load academic sessions for filter dropdown
function loadAcademicSessions() {
    const formData = new FormData();
    formData.append('operation', 'get_academic_sessions');
    formData.append('json', '{}');

    fetch(window.APP_CONFIG.API_BASE_URL + 'attendance.php', {
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

                    if (session.is_Active == 1) {
                        activeSessionId = session.academic_session_id;
                    }
                });

                if (activeSessionId) {
                    academicSessionFilter.val(activeSessionId).trigger('change.select2');
                    currentFilters.academicSession = activeSessionId;
                }
            }
        })
        .catch(error => {
            console.error('Error loading academic sessions:', error);
        });
}

// Load section overview
function loadSectionOverview() {
    const sectionOverview = document.getElementById('section-overview');
    sectionOverview.innerHTML = `
        <div class="text-center py-8 text-gray-500 col-span-full">
            <i class="fas fa-spinner fa-spin"></i>
            <p class="mt-2">Loading section data...</p>
        </div>
    `;

    const formData = new FormData();
    formData.append('operation', 'get_section_overview');

    // Ensure coordinator_id is included in the filters
    const filtersToSend = { ...currentFilters };

    formData.append('json', JSON.stringify({
        filters: filtersToSend
    }));

    fetch(window.APP_CONFIG.API_BASE_URL + 'attendance.php', {
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
                <p>No assigned sections found for the selected filters</p>
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

// Apply filters
function applyFilters() {
    const dateFilter = document.getElementById('dateFilter').value;
    const sectionFilter = $('#sectionFilter').val();
    const academicSessionFilter = $('#academicSessionFilter').val();

    currentFilters.dateRange = dateFilter;
    currentFilters.section = sectionFilter;
    currentFilters.academicSession = academicSessionFilter;

    if (dateFilter === 'custom') {
        currentFilters.fromDate = document.getElementById('fromDate').value;
        currentFilters.toDate = document.getElementById('toDate').value;
    } else {
        currentFilters.fromDate = null;
        currentFilters.toDate = null;
    }

    loadSectionOverview();
    closeStudentDetails();
}

// View section details
function viewSectionDetails(sectionName, sectionId) {
    const studentSection = document.getElementById('studentAttendanceSection');
    const sectionNameSpan = document.getElementById('sectionName');

    sectionNameSpan.textContent = sectionName;
    studentSection.classList.remove('hidden');

    loadStudentAttendance(sectionId);
    studentSection.scrollIntoView({ behavior: 'smooth' });
}

// Load student attendance for a section
function loadStudentAttendance(sectionId) {
    const studentTable = document.getElementById('studentAttendanceTable');
    studentTable.innerHTML = `
        <tr>
            <td colspan="7" class="px-6 py-4 text-center">
                <i class="fas fa-spinner fa-spin"></i>
                <span class="ml-2">Loading student data...</span>
            </td>
        </tr>
    `;

    const formData = new FormData();
    formData.append('operation', 'get_student_attendance');
    formData.append('json', JSON.stringify({
        section_id: sectionId,
        filters: currentFilters
    }));

    fetch(window.APP_CONFIG.API_BASE_URL + 'attendance.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
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
                <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                    <i class="fas fa-user-slash text-2xl mb-2"></i>
                    <p>No students found for this section</p>
                </td>
            </tr>
        `;
        return;
    }

    studentTable.innerHTML = students.map(student => {
        let liveRendered = parseFloat(student.total_rendered_hours || 0);
        let publicRendered = parseFloat(student.public_rendered_hours || 0);
        let privateRendered = parseFloat(student.private_rendered_hours || 0);

        if (student.current_time_in && student.ongoing_date) {
            const extraHours = calculateLiveHours(student.ongoing_date, student.current_time_in, null, 0);
            const extra = parseFloat(extraHours);
            liveRendered += extra;

            // Add live hours to appropriate school type
            if (student.ongoing_school_type === 'Public') {
                publicRendered += extra;
            } else if (student.ongoing_school_type === 'Private') {
                privateRendered += extra;
            }
        }

        const requiredTotal = parseFloat(student.total_required_hours || 360);
        const remainingTotal = Math.max(0, requiredTotal - liveRendered);
        const progressPercentage = requiredTotal > 0 ? (liveRendered / requiredTotal * 100) : 0;

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
                   <div class="flex flex-col">
                        <span class="font-bold ${student.current_time_in ? 'text-blue-600' : ''}">
                            ${liveRendered.toFixed(2)} hrs
                            ${student.current_time_in ? '<i class="fas fa-sync-alt fa-spin text-blue-500 text-xs ml-1"></i>' : ''}
                        </span>
                        <span class="text-xs text-gray-500">
                            Pub: ${publicRendered.toFixed(2)} | Priv: ${privateRendered.toFixed(2)}
                        </span>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <div class="flex flex-col">
                        <span class="font-medium">${requiredTotal.toFixed(2)} hrs</span>
                        <span class="text-xs text-gray-500">Pub: 180.00 | Priv: 180.00</span>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <div class="flex flex-col">
                        <span class="font-bold ${remainingTotal <= 0 ? 'text-green-600' : 'text-orange-600'}">
                            ${remainingTotal.toFixed(2)} hrs
                        </span>
                         <span class="text-xs text-gray-500">
                            Pub: ${Math.max(0, 180 - publicRendered).toFixed(2)} | Priv: ${Math.max(0, 180 - privateRendered).toFixed(2)}
                        </span>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="w-20 bg-gray-200 rounded-full h-2 mr-2">
                            <div class="${progressColor} h-2 rounded-full" style="width: ${Math.min(progressPercentage, 100)}%"></div>
                        </div>
                        <span class="text-sm text-gray-900">${progressPercentage.toFixed(1)}%</span>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button onclick="viewStudentDetails('${student.student_id}')" class="text-green-600 hover:text-green-900">
                        <i class="fas fa-eye"></i> Details
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

// Close student details
function closeStudentDetails() {
    document.getElementById('studentAttendanceSection').classList.add('hidden');
}

// View detailed student information (Modal)
function viewStudentDetails(studentId) {
    openAttendanceModal();

    // Reset modal content
    document.getElementById('modalStudentName').textContent = 'Loading...';
    document.getElementById('modalStudentId').textContent = studentId;
    document.getElementById('modalRenderedHours').textContent = '-- hrs';
    document.getElementById('modalRequiredHours').textContent = '360.00 hrs';
    document.getElementById('modalRemainingHours').textContent = '-- hrs';

    const historyTable = document.getElementById('modalAttendanceHistory');
    historyTable.innerHTML = `<tr><td colspan="5" class="px-6 py-4 text-center"><i class="fas fa-spinner fa-spin mr-2"></i>Loading...</td></tr>`;

    // Find student info - robust search
    let studentInfo = null;
    // Iterate through all sections in the data store
    Object.values(allStudentsData).forEach(students => {
        if (studentInfo) return; // Already found
        const found = students.find(s => String(s.student_id).trim() === String(studentId).trim());
        if (found) studentInfo = found;
    });

    if (studentInfo) {
        document.getElementById('modalStudentName').textContent = `${studentInfo.firstname} ${studentInfo.lastname}`;

        // Get live data if updated
        const renderedTotal = parseFloat(studentInfo.total_rendered_hours || 0);
        const requiredTotal = parseFloat(studentInfo.total_required_hours || 360);
        const remainingTotal = Math.max(0, requiredTotal - renderedTotal);

        document.getElementById('modalRenderedHours').innerHTML = `
            ${renderedTotal.toFixed(2)} hrs<br>
            <span class="text-xs font-normal text-gray-500">
                Pub: ${(parseFloat(studentInfo.public_rendered_hours || 0)).toFixed(2)} | 
                Priv: ${(parseFloat(studentInfo.private_rendered_hours || 0)).toFixed(2)}
            </span>
        `;
        document.getElementById('modalRequiredHours').innerHTML = `
            ${requiredTotal.toFixed(2)} hrs<br>
            <span class="text-xs font-normal text-gray-500">Pub: 180.00 | Priv: 180.00</span>
        `;
        document.getElementById('modalRemainingHours').innerHTML = `
            ${remainingTotal.toFixed(2)} hrs<br>
            <span class="text-xs font-normal text-gray-500">
                Pub: ${Math.max(0, 180 - (parseFloat(studentInfo.public_rendered_hours || 0))).toFixed(2)} | 
                Priv: ${Math.max(0, 180 - (parseFloat(studentInfo.private_rendered_hours || 0))).toFixed(2)}
            </span>
        `;
    } else {
        console.warn('Student info not found for ID:', studentId);
        document.getElementById('modalStudentName').textContent = 'Student Not Found';
    }

    const formData = new FormData();
    formData.append('operation', 'get_student_attendance_history');
    formData.append('json', JSON.stringify({
        student_id: studentId,
        filters: currentFilters
    }));

    fetch(window.APP_CONFIG.API_BASE_URL + 'attendance.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    displayModalHistory(data.attendance_history);
                } else {
                    historyTable.innerHTML = `<tr><td colspan="5" class="px-6 py-4 text-center text-red-500">Error: ${data.message}</td></tr>`;
                }
            } catch (e) {
                console.error('JSON Parse Error:', e, text);
                historyTable.innerHTML = `<tr><td colspan="5" class="px-6 py-4 text-center text-red-500">Failed to load data. Server response invalid.</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Error fetching history:', error);
            historyTable.innerHTML = `<tr><td colspan="5" class="px-6 py-4 text-center text-red-500">Failed to fetch history</td></tr>`;
        });
}

function displayModalHistory(history) {
    const historyTable = document.getElementById('modalAttendanceHistory');

    if (!history || history.length === 0) {
        historyTable.innerHTML = `<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">No records found.</td></tr>`;
        document.getElementById('modalRenderedHours').textContent = '0.00 hrs';
        document.getElementById('modalRemainingHours').textContent = document.getElementById('modalRequiredHours').textContent;
        return;
    }

    let totalLiveHours = 0;

    historyTable.innerHTML = history.map(record => {
        const liveHours = calculateLiveHours(record.attendance_date, record.check_in_time, record.check_out_time, record.hours_rendered);
        totalLiveHours += parseFloat(liveHours);
        const isOngoing = !record.check_out_time && record.status === 'Present';
        const schoolType = record.school_type || 'Public';
        const schoolTypeBadge = schoolType === 'Public'
            ? '<span class="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-50 text-green-700 border border-green-200">Public</span>'
            : '<span class="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-50 text-blue-700 border border-blue-200">Private</span>';

        return `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                    ${formatDate(record.attendance_date)}
                    <div class="mt-1">${schoolTypeBadge}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${formatTimeTo12Hour(record.check_in_time)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${record.check_out_time ? formatTimeTo12Hour(record.check_out_time) : (isOngoing ? '<span class="text-blue-500 italic"><i class="fas fa-sync-alt fa-spin mr-1"></i>In Progress</span>' : '--')}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs font-semibold rounded-full ${record.status === 'Present' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                        ${record.status}
                    </span>
                    <div class="text-xs text-gray-500 mt-1 truncate max-w-[150px]" title="${record.school_name}">${record.school_name}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">${liveHours} hrs</td>
            </tr>
        `;
    }).join('');

    // Update the total summary fields with live totals - OPTIONAL since we have detail split
    // const required = parseFloat(document.getElementById('modalRequiredHours').textContent) || 360;
    // const remaining = Math.max(0, required - totalLiveHours);
    // document.getElementById('modalRenderedHours').textContent = `${totalLiveHours.toFixed(2)} hrs`;
    // document.getElementById('modalRemainingHours').textContent = `${remaining.toFixed(2)} hrs`;
}

// Helpers
function calculateLiveHours(dateStr, checkInTime, checkOutTime, hoursRendered) {
    if (checkOutTime) return parseFloat(hoursRendered || 0).toFixed(2);
    if (!checkInTime) return '0.00';
    try {
        const now = new Date();
        const [y, m, d] = dateStr.split('-').map(Number);
        const [h, mi, s] = checkInTime.split(':').map(Number);
        const inDate = new Date(y, m - 1, d, h, mi, s);
        const diffMs = now - inDate;
        if (diffMs < 0 || diffMs > 24 * 60 * 60 * 1000) return parseFloat(hoursRendered || 0).toFixed(2);
        return (diffMs / (1000 * 60 * 60)).toFixed(2);
    } catch (e) { return parseFloat(hoursRendered || 0).toFixed(2); }
}

function formatDate(ds) {
    return new Date(ds).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function formatTimeTo12Hour(t) {
    if (!t) return '--';
    let [h, m] = t.split(':');
    h = parseInt(h);
    const ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    return `${h}:${m} ${ampm}`;
}

function openAttendanceModal() { document.getElementById('attendanceModal').classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
function closeAttendanceModal() { document.getElementById('attendanceModal').classList.add('hidden'); document.body.style.overflow = ''; }

function showError(message) {
    const toast = document.createElement('div');
    toast.className = 'fixed top-4 right-4 z-[100] bg-red-600 text-white px-6 py-3 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full';
    toast.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <span class="text-sm font-medium">${message}</span>
        </div>
    `;
    document.body.appendChild(toast);
    setTimeout(() => toast.classList.remove('translate-x-full'), 100);
    setTimeout(() => {
        toast.classList.add('translate-x-full');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}