$(document).ready(function() {
    loadSections();
    loadAcademicSessions();
    loadSectionOverview();
    
    // Initialize Select2
    $('#sectionFilter, #academicSessionFilter').select2({
        width: '100%'
    });
    
    // Show/hide custom date range
    $('#dateRange').on('change', function() {
        if ($(this).val() === 'custom') {
            $('#customDateRange').removeClass('hidden');
        } else {
            $('#customDateRange').addClass('hidden');
        }
        loadSectionOverview(); // Auto-refresh when date range changes
    });
    
    // Auto-refresh on filter changes
    $('#sectionFilter, #academicSessionFilter').on('change', function() {
        loadSectionOverview();
    });
    
    // Auto-refresh on custom date changes
    $('#startDate, #endDate').on('change', function() {
        if ($('#dateRange').val() === 'custom') {
            loadSectionOverview();
        }
    });
    
    // Student search functionality
    $('#studentSearch').on('input', function() {
        var searchTerm = $(this).val().toLowerCase();
        filterStudentTable(searchTerm);
    });
});

// Load sections for filter
function loadSections() {
    $.ajax({
        url: '../../api/journal.php',
        type: 'POST',
        data: JSON.stringify({
            action: 'get_sections'
        }),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                var select = $('#sectionFilter');
                select.empty().append('<option value="all">All Sections</option>');
                
                response.data.forEach(function(section) {
                    select.append('<option value="' + section.id + '">' + 
                                section.section_name + ' - ' + section.school_name + '</option>');
                });
                
                select.trigger('change');
            }
        },
        error: function() {
            showNotification('Error loading sections', 'error');
        }
    });
}

// Load academic sessions for filter
function loadAcademicSessions() {
    $.ajax({
        url: '../../api/journal.php',
        type: 'POST',
        data: JSON.stringify({
            action: 'get_academic_sessions'
        }),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                var select = $('#academicSessionFilter');
                select.empty().append('<option value="all">All Sessions</option>');
                
                var activeSessionId = null;
                response.data.forEach(function(session) {
                    var label = session.session_name;
                    if (session.is_Active == 1) {
                        label += ' (Active)';
                        activeSessionId = session.academic_session_id;
                    }
                    select.append('<option value="' + session.academic_session_id + '">' + label + '</option>');
                });
                
                // Set active session as default, otherwise fall back to "all"
                if (activeSessionId) {
                    select.val(activeSessionId);
                } else {
                    select.val('all');
                }
                
                select.trigger('change');
            }
        },
        error: function() {
            showNotification('Error loading academic sessions', 'error');
        }
    });
}

// Load section overview
function loadSectionOverview() {
    var filters = getFilters();
    
    $.ajax({
        url: '../../api/journal.php',
        type: 'POST',
        data: JSON.stringify({
            action: 'get_section_overview',
            filters: filters
        }),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                displaySectionOverview(response.data);
            } else {
                showNotification(response.message || 'Error loading section overview', 'error');
            }
        },
        error: function() {
            showNotification('Error loading section overview', 'error');
        }
    });
}

// Display section overview cards
function displaySectionOverview(sections) {
    var container = $('#sectionOverview');
    container.empty();
    
    if (sections.length === 0) {
        container.html('<div class="col-span-full text-center py-8 text-gray-500">No journal entries found for the selected criteria.</div>');
        return;
    }
    
    sections.forEach(function(section) {
        var submissionRate = section.submission_rate || 0;
        var rateColor = submissionRate >= 80 ? 'text-green-600' : submissionRate >= 60 ? 'text-yellow-600' : 'text-red-600';
        var rateBg = submissionRate >= 80 ? 'bg-green-100' : submissionRate >= 60 ? 'bg-yellow-100' : 'bg-red-100';
        
        var card = `
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 card-hover cursor-pointer" 
                 onclick="viewSectionDetails(${section.section_id}, '${section.section_name}')">
                <div class="flex flex-col items-center justify-between mb-4 text-center">
                    <h3 class="text-lg font-semibold text-gray-900">${section.section_name}</h3>
                    <span class="text-sm text-gray-500">${section.school_name}</span>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">${section.total_students}</div>
                        <div class="text-xs text-gray-500">Total Students</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">${section.students_submitted}</div>
                        <div class="text-xs text-gray-500">Submitted</div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm text-gray-600">Submission Rate</span>
                        <span class="text-sm font-medium ${rateColor}">${submissionRate}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="${rateBg} h-2 rounded-full" style="width: ${submissionRate}%"></div>
                    </div>
                </div>
                
                <div class="flex justify-between text-sm text-gray-600">
                    <span>Latest Week: ${section.latest_week || 'N/A'}</span>
                    <span>Weeks Covered: ${section.weeks_covered || 0}</span>
                </div>
            </div>
        `;
        
        container.append(card);
    });
}

// View section details
function viewSectionDetails(sectionId, sectionName) {
    $('#modalSectionName').text(sectionName);
    $('#sectionDetailsModal').removeClass('hidden');
    
    var filters = getFilters();
    
    $.ajax({
        url: '../../api/journal.php',
        type: 'POST',
        data: JSON.stringify({
            action: 'get_section_journals',
            section_id: sectionId,
            filters: filters
        }),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                displaySectionJournals(response.data);
            } else {
                showNotification(response.message || 'Error loading journal entries', 'error');
            }
        },
        error: function() {
            showNotification('Error loading journal entries', 'error');
        }
    });
}

// Display section journals in table
function displaySectionJournals(journals) {
    var tbody = $('#sectionJournalsTableBody');
    tbody.empty();
    
    if (journals.length === 0) {
        tbody.html('<tr><td colspan="7" class="px-6 py-8 text-center text-gray-500"><div class="py-4"><i class="fas fa-journal-whills text-4xl text-gray-300 mb-2"></i><p>No journal entries found for this section.</p></div></td></tr>');
        return;
    }
    
    journals.forEach(function(journal) {
        var feelingBadge = getFeelingBadge(journal.felt_this_week);
        var gratefulText = journal.grateful ? (journal.grateful.length > 50 ? journal.grateful.substring(0, 50) + '...' : journal.grateful) : 'N/A';
        var proudText = journal.proud_of ? (journal.proud_of.length > 50 ? journal.proud_of.substring(0, 50) + '...' : journal.proud_of) : 'N/A';
        
        var row = `
            <tr>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">${journal.firstname} ${journal.lastname}</div>
                    <div class="text-sm text-gray-500">${journal.email}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                        Week ${journal.week}
                    </span>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm text-gray-900" title="${journal.grateful || ''}">${gratefulText}</div>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm text-gray-900" title="${journal.proud_of || ''}">${proudText}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    ${feelingBadge}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${formatDate(journal.createdAt)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <button onclick="viewJournalDetails(${journal.id})" 
                            class="text-blue-600 hover:text-blue-900 mr-3">
                        <i class="fas fa-eye"></i> View
                    </button>
                </td>
            </tr>
        `;
        
        tbody.append(row);
    });
}

// View journal details
function viewJournalDetails(journalId) {
    $.ajax({
        url: '../../api/journal.php',
        type: 'POST',
        data: JSON.stringify({
            action: 'get_journal_details',
            journal_id: journalId
        }),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                displayJournalDetails(response.data);
                $('#journalDetailsModal').removeClass('hidden');
            } else {
                showNotification(response.message || 'Error loading journal details', 'error');
            }
        },
        error: function() {
            showNotification('Error loading journal details', 'error');
        }
    });
}

// Display journal details
function displayJournalDetails(journal) {
    var content = $('#journalDetailsContent');
    
    var inspireWordsHtml = '';
    if (journal.inspire_words && journal.inspire_words.length > 0) {
        inspireWordsHtml = journal.inspire_words.map(function(word) {
            if (word.length > 50) {
                return `<div class="bg-purple-50 border border-purple-200 p-3 rounded-lg mb-2">
                    <p class="text-sm text-purple-800 whitespace-pre-wrap">${word}</p>
                </div>`;
            } else {
                return `<span class="inline-block px-3 py-1 m-1 text-sm bg-purple-100 text-purple-800 rounded-full">${word}</span>`;
            }
        }).join('');
    } else {
        inspireWordsHtml = '<span class="text-gray-500">No inspiring words added</span>';
    }
    
    var affirmationWordsHtml = '';
    if (journal.affirmation_words && journal.affirmation_words.length > 0) {
        affirmationWordsHtml = journal.affirmation_words.map(function(word) {
            if (word.length > 50) {
                return `<div class="bg-green-50 border border-green-200 p-3 rounded-lg mb-2">
                    <p class="text-sm text-green-800 whitespace-pre-wrap">${word}</p>
                </div>`;
            } else {
                return `<span class="inline-block px-3 py-1 m-1 text-sm bg-green-100 text-green-800 rounded-full">${word}</span>`;
            }
        }).join('');
    } else {
        affirmationWordsHtml = '<span class="text-gray-500">No affirmation words added</span>';
    }
    
    var detailsHtml = `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h4 class="text-sm font-medium text-gray-500 mb-1">Student Information</h4>
                <p class="text-lg font-medium text-gray-900">${journal.firstname} ${journal.lastname}</p>
                <p class="text-sm text-gray-600">${journal.email}</p>
                <p class="text-sm text-gray-600">${journal.section_name} - ${journal.school_name}</p>
            </div>
            
            <div>
                <h4 class="text-sm font-medium text-gray-500 mb-1">Submission Details</h4>
                <p class="text-lg font-medium text-gray-900">Week ${journal.week}</p>
                <p class="text-sm text-gray-600">Session: ${journal.session_name || 'N/A'}</p>
                <p class="text-sm text-gray-600">Submitted: ${formatDate(journal.createdAt)}</p>
            </div>
        </div>
        
        <div class="mt-6 space-y-6">
            <div>
                <h4 class="text-sm font-medium text-gray-500 mb-2">I'm Grateful For</h4>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-gray-900 whitespace-pre-wrap">${journal.grateful || 'No content provided'}</p>
                </div>
            </div>
            
            <div>
                <h4 class="text-sm font-medium text-gray-500 mb-2">Something I'm Proud Of</h4>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-gray-900 whitespace-pre-wrap">${journal.proud_of || 'No content provided'}</p>
                </div>
            </div>
            
            <div>
                <h4 class="text-sm font-medium text-gray-500 mb-2">Next Week I Look Forward To</h4>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-gray-900">${journal.look_forward || 'No content provided'}</p>
                </div>
            </div>
            
            <div>
                <h4 class="text-sm font-medium text-gray-500 mb-2">How I Felt This Week</h4>
                <div class="flex items-center">
                    ${getFeelingBadge(journal.felt_this_week)}
                </div>
            </div>
            
            <div>
                <h4 class="text-sm font-medium text-gray-500 mb-2">Words to Inspire</h4>
                <div class="flex flex-wrap">
                    ${inspireWordsHtml}
                </div>
            </div>
            
            <div>
                <h4 class="text-sm font-medium text-gray-500 mb-2">Words of Affirmation</h4>
                <div class="flex flex-wrap">
                    ${affirmationWordsHtml}
                </div>
            </div>
        </div>
    `;
    
    content.html(detailsHtml);
}

// Get feeling badge HTML
function getFeelingBadge(feeling) {
    var badges = {
        'Good': '<span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Good</span>',
        'Lean toward Good': '<span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">Lean Good</span>',
        'Middle/Neutral': '<span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">Neutral</span>',
        'Lean toward Not Good': '<span class="px-2 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-800">Lean Not Good</span>',
        'Not Good': '<span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">Not Good</span>'
    };
    
    return badges[feeling] || '<span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">N/A</span>';
}

// Format date
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    
    var date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Get current filters
function getFilters() {
    var filters = {
        dateRange: $('#dateRange').val(),
        section: $('#sectionFilter').val(),
        academicSession: $('#academicSessionFilter').val()
    };
    
    if (filters.dateRange === 'custom') {
        filters.startDate = $('#startDate').val();
        filters.endDate = $('#endDate').val();
    }
    
    return filters;
}

// Filter student table based on search term
function filterStudentTable(searchTerm) {
    $('#sectionJournalsTableBody tr').each(function() {
        var row = $(this);
        var studentName = row.find('td:first').text().toLowerCase();
        var studentEmail = row.find('td:first .text-sm').text().toLowerCase();
        
        if (studentName.includes(searchTerm) || studentEmail.includes(searchTerm)) {
            row.show();
        } else {
            row.hide();
        }
    });
}

// Close section details modal
function closeSectionDetails() {
    $('#sectionDetailsModal').addClass('hidden');
    // Clear search when closing modal
    $('#studentSearch').val('');
}

// Close journal details modal
function closeJournalDetails() {
    $('#journalDetailsModal').addClass('hidden');
}

// Show notification
function showNotification(message, type = 'info') {
    var colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        warning: 'bg-yellow-500',
        info: 'bg-blue-500'
    };
    
    var notification = $(`
        <div class="fixed top-4 right-4 z-50 px-4 py-3 rounded-lg text-white ${colors[type]} shadow-lg">
            <div class="flex items-center">
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `);
    
    $('body').append(notification);
    
    setTimeout(function() {
        notification.fadeOut(function() {
            notification.remove();
        });
    }, 5000);
}