// Global variables
let attendanceRecords = [];
let filteredRecords = [];

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    initializePage();
});

// Initialize page
function initializePage() {
    // Set default date range to today only
    const today = new Date();
    
    document.getElementById('dateFrom').value = today.toISOString().split('T')[0];
    document.getElementById('dateTo').value = today.toISOString().split('T')[0];
    
    // Load attendance records
    loadAttendanceRecords();
    
    // Add automatic event listeners for real-time filtering
    document.getElementById('searchStudent').addEventListener('input', filterRecords);
    document.getElementById('dateFrom').addEventListener('change', loadAttendanceRecords);
    document.getElementById('dateTo').addEventListener('change', loadAttendanceRecords);
}

// Load attendance records from API
function loadAttendanceRecords() {
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    
    showLoading();
    
    fetch(window.APP_CONFIG.API_BASE_URL + 'coordinator.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            operation: 'get_attendance_records',
            json: JSON.stringify({
                coordinator_id: coordinatorId,
                date_from: dateFrom,
                date_to: dateTo
            })
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            attendanceRecords = data.data;
            filteredRecords = [...attendanceRecords];
            displayRecords(filteredRecords);
            updateStatistics();
        } else {
            showError('Failed to load attendance records: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Error loading attendance records. Please try again.');
    });
}

// Display attendance records in table
function displayRecords(records) {
    const tableBody = document.getElementById('attendanceTableBody');
    
    if (records.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                    <i class="fas fa-inbox text-4xl mb-2"></i>
                    <p>No attendance records found for the selected criteria.</p>
                </td>
            </tr>
        `;
        return;
    }
    
    tableBody.innerHTML = records.map(record => `
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${record.student_id}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${record.firstname} ${record.lastname}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${record.section_name || 'N/A'}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${formatDate(record.attendance_date)}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${formatTime(record.attendance_timeIn)}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${record.attendance_timeOut ? formatTime(record.attendance_timeOut) : '<span class="text-yellow-600">Not out</span>'}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
                ${getAttendanceStatus(record.attendance_timeIn, record.attendance_timeOut)}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                <button onclick="viewDetails('${record.id}')" class="text-blue-600 hover:text-blue-800 mr-3">
                    <i class="fas fa-eye"></i>
                </button>
                ${!record.attendance_timeOut ? `
                    <button onclick="verifyAttendance('${record.id}')" class="text-green-600 hover:text-green-800">
                        <i class="fas fa-check"></i>
                    </button>
                ` : ''}
            </td>
        </tr>
    `).join('');
}

// Filter records based on search input
function filterRecords() {
    const searchTerm = document.getElementById('searchStudent').value.toLowerCase();
    
    if (searchTerm === '') {
        filteredRecords = [...attendanceRecords];
    } else {
        filteredRecords = attendanceRecords.filter(record => 
            record.student_id.toLowerCase().includes(searchTerm) ||
            record.firstname.toLowerCase().includes(searchTerm) ||
            record.lastname.toLowerCase().includes(searchTerm) ||
            `${record.firstname} ${record.lastname}`.toLowerCase().includes(searchTerm)
        );
    }
    
    displayRecords(filteredRecords);
}

// Update statistics cards
function updateStatistics() {
    const today = new Date().toISOString().split('T')[0];
    const todayRecords = attendanceRecords.filter(record => record.attendance_date === today);
    
    const totalStudents = [...new Set(attendanceRecords.map(r => r.student_id))].length;
    const presentToday = todayRecords.filter(r => r.attendance_timeIn).length;
    const timeInOnly = todayRecords.filter(r => r.attendance_timeIn && !r.attendance_timeOut).length;
    const absentToday = totalStudents - presentToday;
    
    document.getElementById('totalStudents').textContent = totalStudents;
    document.getElementById('presentToday').textContent = presentToday;
    document.getElementById('timeInOnly').textContent = timeInOnly;
    document.getElementById('absentToday').textContent = absentToday;
}

// Get attendance status badge
function getAttendanceStatus(timeIn, timeOut) {
    if (!timeIn) {
        return '<span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Absent</span>';
    } else if (!timeOut) {
        return '<span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Time In Only</span>';
    } else {
        return '<span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Complete</span>';
    }
}

// Format date for display
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Format time for display
function formatTime(timeString) {
    if (!timeString) return '';
    
    const [hour, minute, second] = timeString.split(':');
    const date = new Date();
    date.setHours(parseInt(hour), parseInt(minute), parseInt(second));
    
    let hours = date.getHours();
    let minutes = date.getMinutes();
    const ampm = hours >= 12 ? 'PM' : 'AM';

    hours = hours % 12;
    hours = hours ? hours : 12;
    minutes = minutes < 10 ? '0' + minutes : minutes;

    return `${hours}:${minutes} ${ampm}`;
}

// Show loading state
function showLoading() {
    const tableBody = document.getElementById('attendanceTableBody');
    tableBody.innerHTML = `
        <tr>
            <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                <i class="fas fa-spinner fa-spin mr-2"></i>
                Loading attendance records...
            </td>
        </tr>
    `;
}

// Show error message
function showError(message) {
    const tableBody = document.getElementById('attendanceTableBody');
    tableBody.innerHTML = `
        <tr>
            <td colspan="8" class="px-6 py-8 text-center text-red-500">
                <i class="fas fa-exclamation-triangle text-4xl mb-2"></i>
                <p>${message}</p>
            </td>
        </tr>
    `;
}

// View attendance details
function viewDetails(attendanceId) {
    const record = attendanceRecords.find(r => r.id == attendanceId);
    if (!record) return;
    
    // Create modal with attendance details
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50';
    modal.innerHTML = `
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Attendance Details</h3>
                <div class="space-y-3">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Student ID</label>
                        <p class="text-gray-900">${record.student_id}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Name</label>
                        <p class="text-gray-900">${record.firstname} ${record.lastname}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Section</label>
                        <p class="text-gray-900">${record.section_name || 'N/A'}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Date</label>
                        <p class="text-gray-900">${formatDate(record.attendance_date)}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Time In</label>
                        <p class="text-gray-900">${formatTime(record.attendance_timeIn)}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Time Out</label>
                        <p class="text-gray-900">${record.attendance_timeOut ? formatTime(record.attendance_timeOut) : 'Not recorded'}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Status</label>
                        <p>${getAttendanceStatus(record.attendance_timeIn, record.attendance_timeOut)}</p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end">
                    <button onclick="this.closest('.fixed').remove()" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                        Close
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

// Verify attendance (for time-in only records)
function verifyAttendance(attendanceId) {
    if (!confirm('Are you sure you want to verify this attendance record?')) {
        return;
    }
    
    fetch(window.APP_CONFIG.API_BASE_URL + 'coordinator.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            operation: 'verify_attendance',
            json: JSON.stringify({
                attendance_id: attendanceId,
                coordinator_id: coordinatorId
            })
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Attendance verified successfully!', 'success');
            loadAttendanceRecords(); // Reload records
        } else {
            showNotification('Failed to verify attendance: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error verifying attendance. Please try again.', 'error');
    });
}

// Show notification
function showNotification(message, type = 'info') {
    const container = document.getElementById('notificationContainer');
    const notification = document.createElement('div');
    
    const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';
    
    notification.className = `${bgColor} text-white px-6 py-4 rounded-lg shadow-lg mb-4 transform transition-all duration-300 translate-x-full`;
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'} mr-3"></i>
            <span>${message}</span>
        </div>
    `;
    
    container.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
        notification.classList.add('translate-x-0');
    }, 100);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Toggle mobile sidebar (if needed)
function toggleMobileSidebar() {
    const sidebar = document.getElementById('mobileSidebar');
    if (sidebar) {
        sidebar.classList.toggle('hidden');
    }
}