$(document).ready(function() {
    loadAcademicSessions();
    loadJournalEntries();
    
    // Initialize Select2
    $('#academicSessionFilter').select2({
        width: '100%'
    });
    
    // Show/hide custom date range
    $('#dateRange').on('change', function() {
        if ($(this).val() === 'custom') {
            $('#customDateRange').removeClass('hidden');
        } else {
            $('#customDateRange').addClass('hidden');
        }
        loadJournalEntries(); // Auto-refresh when date range changes
    });
    
    // Auto-refresh on filter changes
    $('#academicSessionFilter').on('change', function() {
        loadJournalEntries();
    });
    
    // Auto-refresh on custom date changes
    $('#startDate, #endDate').on('change', function() {
        if ($('#dateRange').val() === 'custom') {
            loadJournalEntries();
        }
    });
    
    // Student search functionality
    $('#searchStudent').on('input', function() {
        var searchTerm = $(this).val().toLowerCase();
        filterJournalTable(searchTerm);
    });
});

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

// Load journal entries for coordinator's section
function loadJournalEntries() {
    var filters = getFilters();
    
    $.ajax({
        url: '../../api/journal.php',
        type: 'POST',
        data: JSON.stringify({
            action: 'get_coordinator_journals',
            coordinator_id: coordinatorId,
            filters: filters
        }),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                displayJournalEntries(response.data);
                updateStatistics(response.statistics);
            } else {
                showNotification(response.message || 'Error loading journal entries', 'error');
            }
        },
        error: function() {
            showNotification('Error loading journal entries', 'error');
        }
    });
}

// Display journal entries in table
function displayJournalEntries(journals) {
    var tbody = $('#journalTableBody');
    tbody.empty();
    
    if (journals.length === 0) {
        tbody.html('<tr><td colspan="7" class="px-6 py-8 text-center text-gray-500"><div class="py-4"><i class="fas fa-journal-whills text-4xl text-gray-300 mb-2"></i><p>No journal entries found for the selected criteria.</p></div></td></tr>');
        return;
    }
    
    journals.forEach(function(journal) {
        var feelingBadge = getFeelingBadge(journal.felt_this_week);
        var gratefulText = journal.grateful ? (journal.grateful.length > 50 ? journal.grateful.substring(0, 50) + '...' : journal.grateful) : 'N/A';
        
        var row = `
            <tr>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">${journal.student_id}</div>
                </td>
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
                <td class="px-6 py-4 whitespace-nowrap">
                    ${feelingBadge}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${formatDate(journal.createdAt)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <button onclick="viewJournalDetails(${journal.id})" 
                            class="text-green-600 hover:text-green-900 mr-3">
                        <i class="fas fa-eye"></i> View
                    </button>
                </td>
            </tr>
        `;
        
        tbody.append(row);
    });
}

// Update statistics cards
function updateStatistics(stats) {
    $('#totalStudents').text(stats.total_students || 0);
    $('#submittedCount').text(stats.submitted_count || 0);
    $('#submissionRate').text(stats.submission_rate + '%' || '0%');
    $('#latestWeek').text(stats.latest_week || 0);
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
                <p class="text-sm text-gray-600">ID: ${journal.student_id}</p>
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
        academicSession: $('#academicSessionFilter').val()
    };
    
    if (filters.dateRange === 'custom') {
        filters.startDate = $('#startDate').val();
        filters.endDate = $('#endDate').val();
    }
    
    return filters;
}

// Filter journal table based on search term
function filterJournalTable(searchTerm) {
    $('#journalTableBody tr').each(function() {
        var row = $(this);
        var studentId = row.find('td:first').text().toLowerCase();
        var studentName = row.find('td:nth-child(2)').text().toLowerCase();
        
        if (studentId.includes(searchTerm) || studentName.includes(searchTerm)) {
            row.show();
        } else {
            row.hide();
        }
    });
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
