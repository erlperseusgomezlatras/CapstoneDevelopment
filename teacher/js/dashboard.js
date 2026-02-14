class DashboardManager {
    constructor() {
        this.init();
    }

    init() {
        this.loadDashboardData();
        // Auto-refresh attendance logs every 30 seconds
        setInterval(() => {
            this.loadLatestAttendanceLogs();
        }, 30000);
    }

    async loadDashboardData() {
        try {
            await Promise.all([
                this.loadCurrentSessionInfo(),
                this.loadRecentRegistrations(),
                this.loadLatestAttendanceLogs(),
                this.loadSectionAttendanceOverview()
            ]);
        } catch (error) {
            console.error('Error loading dashboard data:', error);
        }
    }

    async loadCurrentSessionInfo() {
        try {
            const response = await fetch('../api/dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'operation=get_current_session_info'
            });

            const result = await response.json();
            
            if (result.success) {
                this.updateSessionDisplay(result.data);
            } else {
                console.error('Error loading session info:', result.message);
                // Fallback to default display
                this.updateSessionDisplay(null);
            }
        } catch (error) {
            console.error('Error loading session info:', error);
            // Fallback to default display
            this.updateSessionDisplay(null);
        }
    }

    updateSessionDisplay(sessionData) {
        const sessionElement = document.getElementById('current-session');
        const sessionInfoElement = document.getElementById('session-info');
        const attendanceSessionElement = document.getElementById('attendance-session-info');
        
        if (sessionData && sessionElement) {
            // Update the main session display
            sessionElement.textContent = sessionData.school_year;
            
            // Update the full session info if element exists
            if (sessionInfoElement) {
                sessionInfoElement.textContent = `${sessionData.school_year} ${sessionData.semester} - Day ${sessionData.day_number}`;
            }
            
            // Update the attendance session info
            if (attendanceSessionElement) {
                attendanceSessionElement.textContent = `${sessionData.school_year} ${sessionData.semester}`;
            }
            
            // Update the subtitle in the section overview
            const subtitleElement = document.querySelector('#section-overview').closest('.bg-white').querySelector('.text-sm.text-gray-600');
            if (subtitleElement) {
                subtitleElement.innerHTML = `
                    <span id="current-session">${sessionData.school_year}</span> OJT Students - Day <span class="font-semibold text-blue-600">${sessionData.day_number}</span>
                `;
            }
        } else {
            // Fallback display
            if (sessionElement) {
                sessionElement.textContent = '2025-2026';
            }
            
            if (attendanceSessionElement) {
                attendanceSessionElement.textContent = '2025-2026';
            }
            
            const subtitleElement = document.querySelector('#section-overview').closest('.bg-white').querySelector('.text-sm.text-gray-600');
            if (subtitleElement) {
                subtitleElement.innerHTML = `
                    <span id="current-session">2025-2026</span> OJT Students - Day <span class="font-semibold text-blue-600">1</span>
                `;
            }
        }
    }

    async loadRecentRegistrations() {
        try {
            const response = await fetch('../api/dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'operation=get_recent_registrations'
            });

            const result = await response.json();
            
            if (result.success) {
                this.renderRecentRegistrations(result.data);
            } else {
                console.error('Error loading recent registrations:', result.message);
            }
        } catch (error) {
            console.error('Error loading recent registrations:', error);
        }
    }

    async loadLatestAttendanceLogs() {
        try {
            const response = await fetch('../api/dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'operation=get_latest_attendance_logs'
            });

            const result = await response.json();
            
            if (result.success) {
                this.renderAttendanceLogs(result.data);
                
                // Update session info from attendance logs response
                if (result.session_info) {
                    this.updateSessionDisplay(result.session_info);
                }
            } else {
                console.error('Error loading attendance logs:', result.message);
            }
        } catch (error) {
            console.error('Error loading attendance logs:', error);
        }
    }

    async loadSectionAttendanceOverview() {
        try {
            const response = await fetch('../api/dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'operation=get_section_attendance_overview'
            });

            const result = await response.json();
            
            if (result.success) {
                this.renderSectionOverview(result.data);
            } else {
                console.error('Error loading section overview:', result.message);
            }
        } catch (error) {
            console.error('Error loading section overview:', error);
        }
    }

    renderRecentRegistrations(registrations) {
        const container = document.getElementById('recent-registrations');
        
        if (!registrations || registrations.length === 0) {
            container.innerHTML = `
                <div class="text-center py-4 text-gray-500">
                    <i class="fas fa-user-check text-2xl mb-2"></i>
                    <p>No pending registration requests</p>
                </div>
            `;
            return;
        }

        const html = registrations.map(reg => `
            <div class="flex items-center justify-between py-3 px-2 border-b border-gray-100 last:border-0 hover:bg-gray-50 transition-colors">
                <div class="flex-1">
                    <p class="font-medium text-sm text-gray-900">${reg.firstname} ${reg.lastname}</p>
                    <p class="text-xs text-gray-500">${reg.school_id} • ${reg.email}</p>
                </div>
                <div class="text-right ml-4">
                    <p class="text-xs text-gray-400 mb-1">${new Date(reg.created_at).toLocaleDateString()}</p>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                        Pending
                    </span>
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
    }

    renderAttendanceLogs(logs) {
        const container = document.getElementById('attendance-logs');
        
        if (!logs || logs.length === 0) {
            container.innerHTML = `
                <div class="text-center py-4 text-gray-500">
                    <i class="fas fa-clock text-2xl mb-2"></i>
                    <p>No attendance logs for today</p>
                </div>
            `;
            return;
        }

        const html = logs.map(log => `
            <div class="flex items-center justify-between py-3 px-2 border-b border-gray-100 last:border-0 hover:bg-gray-50 transition-colors">
                <div class="flex-1">
                    <p class="font-medium text-sm text-gray-900">${log.firstname} ${log.lastname}</p>
                    <p class="text-xs text-gray-500">${log.section_name || 'No Section'}</p>
                </div>
                <div class="text-right ml-4">
                    <p class="text-xs text-green-600 font-medium">
                        <i class="fas fa-sign-in-alt"></i> ${this.convertTo12Hour(log.attendance_timeIn)}
                    </p>
                    ${log.attendance_timeOut ? `<p class="text-xs text-red-600 font-medium"><i class="fas fa-sign-out-alt"></i> ${this.convertTo12Hour(log.attendance_timeOut)}</p>` : ''}
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
    }

    renderSectionOverview(sections) {
        const container = document.getElementById('section-overview');
        
        if (!sections || sections.length === 0) {
            container.innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-users text-2xl mb-2"></i>
                    <p>No sections found</p>
                </div>
            `;
            return;
        }

        const html = sections.map(section => {
            const attendanceRate = section.total_students > 0 
                ? Math.round((section.present_students / section.total_students) * 100) 
                : 0;
            
            return `
                <div class="bg-white p-4 rounded-lg border border-gray-200 hover:shadow-md transition-shadow cursor-pointer"
                     onclick="dashboard.showSectionDetails(${section.section_id})">
                    <div class="flex justify-between items-start mb-2">
                        <h4 class="font-semibold text-gray-900">${section.section_name}</h4>
                        <span class="text-sm font-medium ${attendanceRate >= 80 ? 'text-green-600' : attendanceRate >= 60 ? 'text-yellow-600' : 'text-red-600'}">
                            ${attendanceRate}%
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <p class="text-sm text-gray-600">
                            <span class="font-medium text-green-600">${section.present_students}</span> / 
                            <span class="font-medium">${section.total_students}</span> Students
                        </p>
                        <span class="text-xs text-gray-500">Present/Absent</span>
                    </div>
                    <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-500 h-2 rounded-full transition-all duration-300" 
                             style="width: ${attendanceRate}%"></div>
                    </div>
                </div>
            `;
        }).join('');

        container.innerHTML = html;
    }

    async showSectionDetails(sectionId) {
        try {
            const response = await fetch('../api/dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `operation=get_section_attendance_details&json=${JSON.stringify({section_id: sectionId})}`
            });

            const result = await response.json();
            
            if (result.success) {
                this.renderSectionDetailsModal(result.data);
            } else {
                alert('Error loading section details: ' + result.message);
            }
        } catch (error) {
            console.error('Error loading section details:', error);
            alert('Error loading section details');
        }
    }

    renderSectionDetailsModal(data) {
        const modal = document.getElementById('sectionDetailsModal');
        const modalTitle = document.getElementById('sectionModalTitle');
        const modalBody = document.getElementById('sectionModalBody');

        modalTitle.textContent = data.section_name;

        const presentStudents = data.students.filter(s => s.status === 'Present');
        const absentStudents = data.students.filter(s => s.status === 'Absent');

        const html = `
            <div class="mb-6">
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                        <p class="text-green-800 font-semibold text-sm">Present Students</p>
                        <p class="text-3xl font-bold text-green-600">${presentStudents.length}</p>
                    </div>
                    <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                        <p class="text-red-800 font-semibold text-sm">Absent Students</p>
                        <p class="text-3xl font-bold text-red-600">${absentStudents.length}</p>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <h4 class="font-semibold text-green-700 mb-3 flex items-center justify-between">
                        <span class="flex items-center">
                            <i class="fas fa-user-check mr-2"></i>
                            Present Students (${presentStudents.length})
                        </span>
                        ${presentStudents.length > 5 ? `<span class="text-xs text-green-600 font-normal">Scroll for more</span>` : ''}
                    </h4>
                    <div class="max-h-80 overflow-y-auto border border-green-200 rounded-lg bg-white shadow-inner">
                        <div class="divide-y divide-green-100">
                            ${presentStudents.length > 0 ? presentStudents.map(student => `
                                <div class="flex justify-between items-center p-3 hover:bg-green-50 transition-colors sticky top-0 bg-white">
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-gray-900 truncate">${student.firstname} ${student.lastname}</p>
                                        <p class="text-sm text-gray-600">${student.school_id}</p>
                                    </div>
                                    <div class="text-right ml-4 flex-shrink-0">
                                        <p class="text-sm text-green-600 font-medium whitespace-nowrap">
                                            <i class="fas fa-sign-in-alt"></i> ${this.convertTo12Hour(student.attendance_timeIn)}
                                        </p>
                                        ${student.attendance_timeOut ? `<p class="text-xs text-red-600 font-medium whitespace-nowrap"><i class="fas fa-sign-out-alt"></i> ${this.convertTo12Hour(student.attendance_timeOut)}</p>` : ''}
                                    </div>
                                </div>
                            `).join('') : '<div class="text-center py-8 text-gray-500"><i class="fas fa-user-check text-2xl mb-2"></i><p>No present students</p></div>'}
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="font-semibold text-red-700 mb-3 flex items-center justify-between">
                        <span class="flex items-center">
                            <i class="fas fa-user-times mr-2"></i>
                            Absent Students (${absentStudents.length})
                        </span>
                        ${absentStudents.length > 5 ? `<span class="text-xs text-red-600 font-normal">Scroll for more</span>` : ''}
                    </h4>
                    <div class="max-h-80 overflow-y-auto border border-red-200 rounded-lg bg-white shadow-inner">
                        <div class="divide-y divide-red-100">
                            ${absentStudents.length > 0 ? absentStudents.map(student => `
                                <div class="flex justify-between items-center p-3 hover:bg-red-50 transition-colors sticky top-0 bg-white">
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-gray-900 truncate">${student.firstname} ${student.lastname}</p>
                                        <p class="text-sm text-gray-600">${student.school_id}</p>
                                    </div>
                                    <span class="px-3 py-1 bg-red-100 text-red-800 text-sm font-medium rounded-full flex-shrink-0">Absent</span>
                                </div>
                            `).join('') : '<div class="text-center py-8 text-gray-500"><i class="fas fa-user-times text-2xl mb-2"></i><p>No absent students</p></div>'}
                        </div>
                    </div>
                </div>
            </div>
        `;

        modalBody.innerHTML = html;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    closeModal() {
        const modal = document.getElementById('sectionDetailsModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // Helper function to convert 24-hour time to 12-hour format
    convertTo12Hour(time24) {
        if (!time24) return '--:--';
        
        const [hours, minutes, seconds] = time24.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const hour12 = hour % 12 || 12;
        
        return `${hour12.toString().padStart(2, '0')}:${minutes}${seconds ? ':' + seconds : ''} ${ampm}`;
    }
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.dashboard = new DashboardManager();
    
    // Close modal when clicking outside
    document.getElementById('sectionDetailsModal').addEventListener('click', function(e) {
        if (e.target === this) {
            dashboard.closeModal();
        }
    });
    
    // Close modal when clicking close button
    document.getElementById('closeModalBtn').addEventListener('click', function() {
        dashboard.closeModal();
    });
});