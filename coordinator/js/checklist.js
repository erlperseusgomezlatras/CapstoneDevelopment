$(document).ready(function() {
    // Load initial data
    loadSections();
    loadPeriods();
    
    // Initialize Select2
    $('#sectionFilter, #studentFilter, #periodFilter').select2({
        width: '100%'
    });
    
    // Setup event listeners
    setupEventListeners();
});

let currentChecklistData = [];
let currentStudentData = null;

function setupEventListeners() {
    // Section change event
    $('#sectionFilter').on('change', function() {
        const sectionId = $(this).val();
        if (sectionId) {
            loadStudents(sectionId);
        } else {
            $('#studentFilter').empty().append('<option value="">Select Student</option>').prop('disabled', true);
            $('#studentFilter').trigger('change.select2');
        }
    });
}

// Load coordinator's sections
function loadSections() {
    console.log('Loading sections for coordinator:', coordinatorId);
    const formData = new FormData();
    formData.append('operation', 'get_coordinator_sections');
    formData.append('json', JSON.stringify({
        coordinator_id: coordinatorId
    }));
    
    axios.post(`${window.APP_CONFIG.API_BASE_URL}coordinator.php`, formData)
    .then(function(response) {
        console.log('Sections response:', response.data);
        if (response.data.success) {
            const select = $('#sectionFilter');
            select.html('<option value="">Select Section</option>');
            
            response.data.sections.forEach(section => {
                const option = $('<option></option>').val(section.section_id).text(section.section_name);
                select.append(option);
            });
            
            select.trigger('change.select2');
        } else {
            console.error('Sections API error:', response.data.message);
            showAlert('Error loading sections: ' + (response.data.message || 'Unknown error'), 'danger');
        }
    })
    .catch(function(error) {
        console.error('Error loading sections:', error);
        showAlert('Error loading sections: ' + error.message, 'danger');
    });
}

// Load students in a section
function loadStudents(sectionId) {
    const formData = new FormData();
    formData.append('operation', 'get_section_students');
    formData.append('json', JSON.stringify({
        section_id: sectionId
    }));
    
    axios.post(`${window.APP_CONFIG.API_BASE_URL}coordinator.php`, formData)
    .then(function(response) {
        if (response.data.success) {
            const select = $('#studentFilter');
            select.empty().append('<option value="">Select Student</option>').prop('disabled', false);
            
            response.data.students.forEach(student => {
                const option = $('<option></option>').val(student.student_id).text(`${student.lastname}, ${student.firstname} ${student.middlename || ''}`);
                select.append(option);
            });
            
            select.trigger('change.select2');
        } else {
            showAlert('Error loading students', 'danger');
        }
    })
    .catch(function(error) {
        console.error('Error loading students:', error);
        showAlert('Error loading students', 'danger');
    });
}

// Get current coordinator ID from session
function getCurrentCoordinatorId() {
    return $('#coordinatorId').val() || 'COORD-001';
}

// Load periods
function loadPeriods() {
    console.log('Loading periods...');
    const formData = new FormData();
    formData.append('operation', 'getPeriods');
    formData.append('json', JSON.stringify({}));
    
    axios.post(`${window.APP_CONFIG.API_BASE_URL}coordinator.php`, formData)
    .then(function(response) {
        console.log('Periods response:', response.data);
        if (response.data.success) {
            const select = $('#periodFilter');
            select.html('<option value="">Select Period</option>');
            
            response.data.data.forEach(period => {
                const option = $('<option></option>').val(period.id).text(`${period.period_name} (${period.period_weeks} weeks)`);
                select.append(option);
            });
            
            select.trigger('change.select2');
        } else {
            console.error('Periods API error:', response.data.message);
            showAlert('Error loading periods: ' + (response.data.message || 'Unknown error'), 'danger');
        }
    })
    .catch(function(error) {
        console.error('Error loading periods:', error);
        showAlert('Error loading periods: ' + error.message, 'danger');
    });
}

// Load student checklist
function loadStudentChecklist() {
    const studentId = $('#studentFilter').val();
    const periodId = $('#periodFilter').val();
    
    if (!studentId || !periodId) {
        showAlert('Please select both student and period', 'warning');
        return;
    }
    
    // Load student info and current week from checklist API (uses raw JSON)
    axios.post(`${window.APP_CONFIG.API_BASE_URL}checklist.php`, {
        action: 'getStudentInfo',
        student_id: studentId,
        period_id: periodId
    })
    .then(function(response) {
        console.log('Student info response:', response.data);
        if (response.data.success) {
            currentStudentData = response.data.data;
            displayStudentInfo();
            
            // Load checklist items
            loadChecklistItems(studentId, periodId);
        } else {
            showAlert(response.data.message || 'Error loading student information', 'danger');
        }
    })
    .catch(function(error) {
        console.error('Error loading student info:', error);
        showAlert('Error loading student information', 'danger');
    });
}

// Display student information
function displayStudentInfo() {
    if (!currentStudentData) return;
    
    $('#studentName').text(`${currentStudentData.student.firstname} ${currentStudentData.student.lastname}`);
    $('#studentDetails').text(`ID: ${currentStudentData.student.school_id} | Section: ${currentStudentData.student.section_name}`);
    $('#currentWeek').text(`Week ${currentStudentData.current_week}`);
    
    // Check if checklist is already completed for this week
    if (currentStudentData.week_completed) {
        $('#saveButton').prop('disabled', true).html('<i class="fas fa-check mr-2"></i>Already Completed');
        showAlert('Checklist already completed for this week', 'info');
    } else {
        $('#saveButton').prop('disabled', false).html('<i class="fas fa-save mr-2"></i>Save Results');
    }
    
    $('#studentInfoCard').removeClass('hidden');
}

// Load checklist items for student
function loadChecklistItems(studentId, periodId) {
    axios.post(`${window.APP_CONFIG.API_BASE_URL}checklist.php`, {
        action: 'getStudentChecklist',
        student_id: studentId,
        period_id: periodId
    })
    .then(function(response) {
        console.log('Checklist items response:', response.data);
        if (response.data.success) {
            currentChecklistData = response.data.data;
            displayChecklistItems();
            
            $('#checklistContainer').removeClass('hidden');
            $('#noResultsMessage').addClass('hidden');
        } else {
            $('#checklistContainer').addClass('hidden');
            $('#noResultsMessage').removeClass('hidden');
            showAlert(response.data.message || 'No checklist items found', 'warning');
        }
    })
    .catch(function(error) {
        console.error('Error loading checklist items:', error);
        showAlert('Error loading checklist items', 'danger');
    });
}

// Display checklist items
function displayChecklistItems() {
    console.log('displayChecklistItems called with data:', currentChecklistData);
    const container = $('#checklistItems');
    container.empty();
    
    if (!currentChecklistData || currentChecklistData.length === 0) {
        container.html('<p class="text-gray-500">No checklist items found</p>');
        return;
    }
    
    let currentCategory = '';
    
    currentChecklistData.forEach((item, index) => {
        console.log(`Processing item ${index}:`, item);
        
        // Add category header if it's a new category
        if (item.category_name !== currentCategory) {
            currentCategory = item.category_name;
            const categoryId = item.category_name.replace(/\s+/g, '-'); // Replace spaces with dashes
            console.log('Creating new category:', currentCategory, 'with ID:', categoryId);
            container.append(`
                <div class="mb-6">
                    <h4 class="text-lg font-semibold text-gray-800 mb-3">${item.category_name}</h4>
                    <div class="space-y-3" id="category-${categoryId}">
                    </div>
                </div>
            `);
        }
        
        const categoryId = item.category_name.replace(/\s+/g, '-');
        const categoryContainer = $(`#category-${categoryId}`);
        console.log('Category container found:', categoryContainer.length > 0);
        
        // Check if this category uses rating score
        const isRatingScore = parseInt(item.is_ratingscore) === 1;
        console.log('isRatingScore for item:', item.checklist_criteria, 'is', isRatingScore);
        
        if (isRatingScore) {
            // Rating score display (1 to points based on checklist table) - Mobile responsive
            const existingScore = parseInt(item.points_earned) || 0; // Convert to number
            const maxRating = parseInt(item.points) || 5; // Convert points to number
            const stars = Array.from({length: maxRating}, (_, i) => i + 1);
            console.log('Creating rating display with maxRating:', maxRating, 'stars:', stars, 'existingScore:', existingScore);
            
            categoryContainer.append(`
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-3 bg-gray-50 rounded-lg gap-3">
                    <div class="flex-1">
                        <label class="text-sm font-medium text-gray-700 block mb-2">${item.checklist_criteria}</label>
                        <div class="flex items-center gap-2">
                            <input type="hidden" name="rating_${item.id}" id="rating_${item.id}" value="${existingScore}" data-checklist-id="${item.id}" data-points="${existingScore}">
                            <div class="rating-stars flex gap-1" data-checklist-id="${item.id}">
                                ${stars.map(star => `
                                    <button type="button" class="star-btn text-xl sm:text-2xl text-gray-300 hover:text-yellow-400 transition-colors ${existingScore >= star ? 'text-yellow-400' : ''}" 
                                            data-rating="${star}" data-points="${star}">
                                        ★
                                    </button>
                                `).join('')}
                            </div>
                            <div class="text-xs sm:text-sm text-gray-600 ml-2">
                                <span class="font-medium">Score:</span> <span id="score_display_${item.id}">${existingScore}/${maxRating}</span>
                            </div>
                        </input>
                    </div>
                </div>
            `);
            console.log('Rating display added for:', item.checklist_criteria, 'with existing score:', existingScore);
        } else {
            // Checkbox display (original) - Mobile responsive with better layout
            const isChecked = item.points_earned > 0;
            console.log('Creating checkbox display for:', item.checklist_criteria, 'checked:', isChecked);
            categoryContainer.append(`
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg gap-3">
                    <label class="flex items-center cursor-pointer flex-1 gap-3">
                        <input type="checkbox" 
                               class="checklist-checkbox form-checkbox h-5 w-5 text-blue-600 rounded flex-shrink-0 mt-0.5" 
                               data-checklist-id="${item.id}" 
                               data-points="${item.points}"
                               ${isChecked ? 'checked' : ''}>
                        <span class="text-sm font-medium text-gray-700 leading-tight">${item.checklist_criteria}</span>
                    </label>
                    <div class="flex-shrink-0">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            ${item.points} pts
                        </span>
                    </div>
                </div>
            `);
            console.log('Checkbox display added for:', item.checklist_criteria);
        }
    });
    
    // Update progress after displaying all items
    updateChecklistProgress();
    
    // Check if star elements exist and are clickable
    setTimeout(() => {
        console.log('Star elements found:', $('.star-btn').length);
        $('.star-btn').each(function(index) {
            console.log(`Star ${index}:`, $(this).data());
        });
    }, 100);
    
    // Add event listeners for rating stars using event delegation
    $(document).on('click', '.star-btn', function() {
        const rating = parseInt($(this).data('rating'));
        const points = parseInt($(this).data('points'));
        const container = $(this).closest('.rating-stars');
        const checklistId = container.data('checklist-id'); // Get from container
        
        console.log(`Star clicked: rating=${rating}, points=${points}, checklistId=${checklistId}`);
        
        // Update star display
        container.find('.star-btn').each(function(index) {
            if (index < rating) {
                $(this).addClass('text-yellow-400').removeClass('text-gray-300');
            } else {
                $(this).removeClass('text-yellow-400').addClass('text-gray-300');
            }
        });
        
        // Update hidden input
        const hiddenInput = $(`#rating_${checklistId}`);
        hiddenInput.val(rating).data('points', points);
        console.log(`Updated hidden input #rating_${checklistId}: value=${hiddenInput.val()}, data-points=${hiddenInput.data('points')}`);
        
        // Update score display
        const maxRating = container.find('.star-btn').length;
        $(`#score_display_${checklistId}`).text(`${rating}/${maxRating}`);
        
        // Update progress
        updateChecklistProgress();
    });
    
    // Add checkbox change event
    $(document).on('change', '.checklist-checkbox', function() {
        // Update progress when checkbox changes
        updateChecklistProgress();
    });
}

// Update checklist progress
function updateChecklistProgress() {
    if (!currentChecklistData || currentChecklistData.length === 0) {
        $('#checklistProgress').text('0/0 completed');
        return;
    }
    
    let completedCount = 0;
    const checkboxCount = $('.checklist-checkbox:checked').length;
    const ratingCount = 0;
    
    // Count completed checkboxes
    $('.checklist-checkbox:checked').each(function() {
        completedCount++;
        console.log('Found checked checkbox:', $(this).data('checklist-id'));
    });
    
    // Count completed ratings (rating > 0)
    $('input[id^="rating_"]').each(function() {
        const ratingValue = parseInt($(this).val());
        console.log(`Rating input #${$(this).attr('id')}: value=${ratingValue}`);
        if (ratingValue > 0) {
            completedCount++;
            console.log('Counted rating:', $(this).data('checklist-id'));
        }
    });
    
    const totalItems = currentChecklistData.length;
    console.log(`Progress: ${completedCount}/${totalItems} (checkboxes: ${checkboxCount}, ratings: ${ratingCount})`);
    $('#checklistProgress').text(`${completedCount}/${totalItems} completed`);
}

// Save checklist results
function saveChecklistResults() {
    const studentId = $('#studentFilter').val();
    const periodId = $('#periodFilter').val();
    
    if (!studentId || !periodId) {
        showAlert('Please select both student and period', 'warning');
        return;
    }
    
    if (currentStudentData && currentStudentData.week_completed) {
        showAlert('Checklist already completed for this week', 'warning');
        return;
    }
    
    // Collect checked items and rating scores
    const checkedItems = [];
    
    // Collect checkbox items
    $('.checklist-checkbox:checked').each(function() {
        const checklistId = $(this).data('checklist-id');
        const points = $(this).data('points');
        checkedItems.push({
            checklist_id: checklistId,
            points_earned: points
        });
    });
    
    console.log('Collected checkbox items:', checkedItems);
    
    // Collect rating scores
    $('input[id^="rating_"]').each(function() {
        const checklistId = $(this).data('checklist-id');
        const ratingValue = parseInt($(this).val()); // Get the actual rating value
        console.log(`Rating input for checklist ${checklistId}: value=${$(this).val()}, ratingValue=${ratingValue}`);
        
        // Only include if rating is greater than 0
        if (ratingValue > 0) {
            checkedItems.push({
                checklist_id: checklistId,
                points_earned: ratingValue
            });
        }
    });
    
    console.log('Final collected items:', checkedItems);
    
    if (checkedItems.length === 0) {
        showAlert('Please select at least one checklist item or provide a rating', 'warning');
        return;
    }
    
    // Save results using checklist API (uses raw JSON)
    axios.post(`${window.APP_CONFIG.API_BASE_URL}checklist.php`, {
        action: 'saveChecklistResults',
        student_id: studentId,
        period_id: periodId,
        results: checkedItems,
        checked_by: coordinatorId
    })
    .then(function(response) {
        console.log('Save results response:', response.data);
        if (response.data.success) {
            showAlert(response.data.message, 'success');
            // Reload checklist to show updated status
            loadStudentChecklist();
        } else {
            showAlert(response.data.message || 'Error saving checklist results', 'danger');
        }
    })
    .catch(function(error) {
        console.error('Error saving checklist results:', error);
        showAlert('Error saving checklist results', 'danger');
    });
}

// Show alert message
function showAlert(message, type) {
    const alertHtml = `
        <div class="alert alert-${type}">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-circle' : 'info-circle'} mr-2"></i>
            ${message}
        </div>
    `;
    
    $('#alertContainer').append(alertHtml);
    
    // Auto remove after 5 seconds
    setTimeout(function() {
        $('.alert').first().fadeOut(300, function() {
            $(this).remove();
        });
    }, 5000);
}