// Journal functionality
class JournalManager {
    constructor() {
        this.currentWeek = 1;
        this.periodId = null;
        this.periodWeeks = null;
        this.alreadySubmitted = false;
        this.init();
    }

    init() {
        this.loadCurrentWeek();
        this.setupEventListeners();
        this.loadExistingJournal();
        this.checkWeekEligibility();
    }

    async loadCurrentWeek() {
        try {
            const response = await fetch(`${window.APP_CONFIG.API_BASE_URL}students.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    operation: 'get_current_week',
                    json: JSON.stringify({
                        student_id: studentSchoolId
                    })
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const text = await response.text();
            
            // Check if response is valid JSON
            if (!text.trim()) {
                throw new Error('Empty response from server');
            }
            
            let result;
            try {
                result = JSON.parse(text);
            } catch (parseError) {
                console.error('Raw response:', text);
                throw new Error('Invalid JSON response from server');
            }
            
            if (result.success) {
                this.currentWeek = result.week;
                this.periodId = result.period_id;
                this.periodWeeks = result.period_weeks;
                this.alreadySubmitted = result.already_submitted || false;
                
                // Update week display with period information
                const weekDisplay = this.periodWeeks ? 
                    `Week ${this.currentWeek} of ${this.periodWeeks}` : 
                    `Week ${this.currentWeek}`;
                document.getElementById('weekNumber').value = weekDisplay;
                
                if (result.already_submitted) {
                    // Show message that student already submitted for this week
                    showNotification('You already submitted your journal for this week. Ready for next week!', 'info');
                }
                
                this.checkWeekEligibility();
            } else {
                console.error('Failed to get current week:', result.message);
                document.getElementById('weekNumber').value = 'Week 1';
            }
        } catch(error) {
            console.error('Error loading current week:', error);
            document.getElementById('weekNumber').value = 'Week 1';
        }
    }

    async loadExistingJournal() {
        try {
            const response = await fetch(`${window.APP_CONFIG.API_BASE_URL}students.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    operation: 'get_journal',
                    json: JSON.stringify({
                        student_id: studentSchoolId,
                        week: this.currentWeek.toString()
                    })
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const text = await response.text();
            
            if (!text.trim()) {
                throw new Error('Empty response from server');
            }
            
            let result;
            try {
                result = JSON.parse(text);
            } catch (parseError) {
                console.error('Raw response:', text);
                throw new Error('Invalid JSON response from server');
            }
            
            if (result.success && result.journal) {
                this.populateJournalForm(result.journal);
            }
        } catch (error) {
            console.error('Error loading existing journal:', error);
        }
    }

    populateJournalForm(journal) {
        // Populate main journal fields
        document.getElementById('grateful').value = journal.grateful || '';
        document.getElementById('proudOf').value = journal.proud_of || '';
        document.getElementById('lookForward').value = journal.look_forward || '';
        document.getElementById('affirmation').value = journal.affirmation || '';
        
        // Set feeling scale
        if (journal.felt_this_week) {
            this.setFeelingScale(journal.felt_this_week);
        }

        // Populate inspire words
        if (journal.words_inspire && journal.words_inspire.length > 0) {
            const inspireContainer = document.getElementById('inspireWords');
            inspireContainer.innerHTML = ''; // Clear existing inputs
            
            journal.words_inspire.forEach((word, index) => {
                const input = document.createElement('input');
                input.type = 'text';
                input.name = `inspire${index + 1}`;
                input.className = 'inspire-word-input';
                input.value = word.inspire_words || '';
                input.placeholder = `Enter inspiring word #${index + 1}`;
                inspireContainer.appendChild(input);
            });
            
            // Ensure we have at least 3 inputs
            while (inspireContainer.children.length < 3) {
                const newIndex = inspireContainer.children.length + 1;
                const input = document.createElement('input');
                input.type = 'text';
                input.name = `inspire${newIndex}`;
                input.className = 'inspire-word-input';
                input.placeholder = `Enter inspiring word #${newIndex}`;
                inspireContainer.appendChild(input);
            }
        }

        // Populate affirmation words
        if (journal.words_affirmation && journal.words_affirmation.length > 0) {
            const affirmationContainer = document.getElementById('affirmationWords');
            affirmationContainer.innerHTML = ''; // Clear existing inputs
            
            journal.words_affirmation.forEach((affirmation, index) => {
                const input = document.createElement('input');
                input.type = 'text';
                input.name = `affirmation${index + 1}`;
                input.className = 'affirmation-word-input';
                input.value = affirmation.affirmation_word || '';
                input.placeholder = `Enter affirmation sentence #${index + 1}`;
                affirmationContainer.appendChild(input);
            });
            
            // Ensure we have at least 3 inputs
            while (affirmationContainer.children.length < 3) {
                const newIndex = affirmationContainer.children.length + 1;
                const input = document.createElement('input');
                input.type = 'text';
                input.name = `affirmation${newIndex}`;
                input.className = 'affirmation-word-input';
                input.placeholder = `Enter affirmation sentence #${newIndex}`;
                affirmationContainer.appendChild(input);
            }
        }
    }

    setFeelingScale(value) {
        // Clear all selections
        document.querySelectorAll('.feeling-circle').forEach(circle => {
            circle.classList.remove('selected');
        });

        // Set selected value
        const selectedCircle = document.querySelector(`.feeling-circle[data-value="${value}"]`);
        if (selectedCircle) {
            selectedCircle.classList.add('selected');
            document.getElementById('feltThisWeek').value = value;
        }
    }

    setupEventListeners() {
        // Form submission
        document.getElementById('journalForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveJournal();
        });

        // Feeling scale selection
        document.querySelectorAll('.feeling-circle').forEach(circle => {
            circle.addEventListener('click', () => {
                document.querySelectorAll('.feeling-circle').forEach(c => c.classList.remove('selected'));
                circle.classList.add('selected');
                document.getElementById('feltThisWeek').value = circle.dataset.value;
            });
        });

        // Add affirmation button
        const addAffirmationBtn = document.getElementById('addAffirmationBtn');
        if (addAffirmationBtn) {
            addAffirmationBtn.addEventListener('click', () => this.addAffirmationInput());
        }

        // Add inspire button
        const addInspireBtn = document.getElementById('addInspireBtn');
        if (addInspireBtn) {
            addInspireBtn.addEventListener('click', () => this.addInspireInput());
        }

        // Auto-resize textareas
        const textareas = document.querySelectorAll('textarea');
        textareas.forEach(textarea => {
            textarea.addEventListener('input', () => {
                textarea.style.height = 'auto';
                textarea.style.height = textarea.scrollHeight + 'px';
            });
        });
    }

    addAffirmationInput() {
        const affirmationWords = document.getElementById('affirmationWords');
        const currentInputs = affirmationWords.querySelectorAll('input');
        const newIndex = currentInputs.length + 1;
        
        const newInput = document.createElement('input');
        newInput.type = 'text';
        newInput.name = `affirmation${newIndex}`;
        newInput.className = 'affirmation-word-input';
        newInput.placeholder = `Enter affirmation sentence #${newIndex}`;
        
        affirmationWords.appendChild(newInput);
        
        // Focus the new input
        newInput.focus();
    }

    addInspireInput() {
        const inspireWords = document.getElementById('inspireWords');
        const currentInputs = inspireWords.querySelectorAll('input');
        const newIndex = currentInputs.length + 1;
        
        const newInput = document.createElement('input');
        newInput.type = 'text';
        newInput.name = `inspire${newIndex}`;
        newInput.className = 'inspire-word-input';
        newInput.placeholder = `Enter inspiring word #${newIndex}`;
        
        inspireWords.appendChild(newInput);
        
        // Focus the new input
        newInput.focus();
    }

    addAffirmationInput() {
        const affirmationWords = document.getElementById('affirmationWords');
        const currentInputs = affirmationWords.querySelectorAll('input');
        const newIndex = currentInputs.length + 1;
        
        const newInput = document.createElement('input');
        newInput.type = 'text';
        newInput.name = `affirmation${newIndex}`;
        newInput.className = 'affirmation-word-input';
        newInput.placeholder = `Enter affirmation sentence #${newIndex}`;
        
        affirmationWords.appendChild(newInput);
        
        // Focus the new input
        newInput.focus();
    }

    checkWeekEligibility() {
        const today = new Date();
        const currentDay = today.getDay(); // 0 = Sunday, 6 = Saturday, 5 = Friday
        
        // Check if it's Friday (submission day)
        const isSubmissionDay = (currentDay === 5); // 5 = Friday
        
        const saveBtn = document.getElementById('saveBtn');
        const journalForm = document.getElementById('journalForm');
        const weekStatus = document.getElementById('weekStatus');
        
        if (!weekStatus) {
            // Create status indicator if it doesn't exist
            const statusDiv = document.createElement('div');
            statusDiv.id = 'weekStatus';
            statusDiv.className = 'mb-4 p-3 rounded-lg text-center';
            document.querySelector('.journal-container').insertBefore(statusDiv, document.getElementById('journalForm'));
        }
        
        const statusElement = document.getElementById('weekStatus');
        
        if (isSubmissionDay) {
            // Allow journal entry
            saveBtn.disabled = false;
            journalForm.style.opacity = '1';
            journalForm.style.pointerEvents = 'auto';
            
            if (this.alreadySubmitted) {
                // Show already submitted state
                statusElement.className = 'mb-4 p-3 rounded-lg text-center bg-blue-100 border border-blue-300 text-blue-800';
                statusElement.innerHTML = `
                    <i class="fas fa-check-double mr-2"></i>
                    <strong>Journal for This Friday Already Submitted</strong><br>
                    <small>You can submit again next Friday for Week ${this.currentWeek + 1}</small>
                `;
            } else {
                // Show normal open state
                statusElement.className = 'mb-4 p-3 rounded-lg text-center bg-green-100 border border-green-300 text-green-800';
                statusElement.innerHTML = `
                    <i class="fas fa-check-circle mr-2"></i>
                    <strong>Journal Period Open - Friday Submission</strong><br>
                    <small>You can now submit your journal for Week ${this.currentWeek}</small>
                `;
            }
        } else {
            // Disable journal entry
            saveBtn.disabled = true;
            journalForm.style.opacity = '0.6';
            journalForm.style.pointerEvents = 'none';
            
            // Calculate next available time
            let nextAvailableTime;
            if (currentDay < 5) { // Before Friday
                const daysUntilFriday = 5 - currentDay;
                nextAvailableTime = `This Friday (${daysUntilFriday} day${daysUntilFriday > 1 ? 's' : ''} from now)`;
            } else { // After Friday (Saturday, Sunday, Monday, etc.)
                const daysUntilFriday = 7 - currentDay + 5; // Next Friday
                nextAvailableTime = `Next Friday (${daysUntilFriday} days from now)`;
            }
            
            statusElement.className = 'mb-4 p-3 rounded-lg text-center bg-yellow-100 border border-yellow-300 text-yellow-800';
            statusElement.innerHTML = `
                <i class="fas fa-clock mr-2"></i>
                <strong>Journal Period Closed</strong><br>
                <small>You can submit your next journal on ${nextAvailableTime}</small>
            `;
        }
    }

    clearForm() {
        // Clear all form fields
        document.getElementById('grateful').value = '';
        document.getElementById('proudOf').value = '';
        document.getElementById('lookForward').value = '';
        document.getElementById('feltThisWeek').value = '';
        
        // Clear feeling scale selection
        document.querySelectorAll('.feeling-circle').forEach(circle => {
            circle.classList.remove('selected');
        });
        
        // Clear inspire words (keep first 3, clear others)
        const inspireContainer = document.getElementById('inspireWords');
        const inspireInputs = inspireContainer.querySelectorAll('input');
        inspireInputs.forEach((input, index) => {
            if (index >= 3) {
                input.remove();
            } else {
                input.value = '';
            }
        });
        
        // Clear affirmation words (keep first 3, clear others)
        const affirmationContainer = document.getElementById('affirmationWords');
        const affirmationInputs = affirmationContainer.querySelectorAll('input');
        affirmationInputs.forEach((input, index) => {
            if (index >= 3) {
                input.remove();
            } else {
                input.value = '';
            }
        });
    }

    async saveJournal() {
        const formData = new FormData(document.getElementById('journalForm'));
        
        // Collect all affirmation inputs
        const affirmationInputs = document.querySelectorAll('input[name^="affirmation"]');
        const affirmations = Array.from(affirmationInputs)
            .map(input => input.value)
            .filter(value => value.trim() !== '');
        
        // Collect all inspire inputs
        const inspireInputs = document.querySelectorAll('input[name^="inspire"]');
        const inspireWords = Array.from(inspireInputs)
            .map(input => input.value)
            .filter(value => value.trim() !== '');
        
        // Collect form data
        const journalData = {
            student_id: studentSchoolId,
            week: this.currentWeek.toString(),
            period_id: this.periodId,
            grateful: formData.get('grateful') || '',
            proud_of: formData.get('proud_of') || '',
            look_forward: formData.get('look_forward') || '',
            felt_this_week: formData.get('felt_this_week') || '',
            words_inspire: inspireWords,
            words_affirmation: affirmations
        };

        // Validate required fields
        if (!journalData.grateful || !journalData.proud_of || !journalData.felt_this_week) {
            showNotification('Please fill in all required fields', 'error');
            return;
        }

        // Validate period_id
        if (!journalData.period_id) {
            showNotification('Period information not available. Please refresh the page.', 'error');
            return;
        }

        // Disable save button
        const saveBtn = document.getElementById('saveBtn');
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>SAVING...';

        try {
            const response = await fetch(`${window.APP_CONFIG.API_BASE_URL}students.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    operation: 'save_journal',
                    json: JSON.stringify(journalData)
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const text = await response.text();
            
            if (!text.trim()) {
                throw new Error('Empty response from server');
            }
            
            let result;
            try {
                result = JSON.parse(text);
            } catch (parseError) {
                console.error('Raw response:', text);
                throw new Error('Invalid JSON response from server');
            }
            
            if (result.success) {
                showNotification('Journal saved successfully!', 'success');
                
                // Clear the form
                this.clearForm();
                
                // Increment week immediately after successful save (but don't exceed period weeks)
                if (this.periodWeeks && this.currentWeek < this.periodWeeks) {
                    this.currentWeek++;
                }
                
                // Update week display
                const weekDisplay = this.periodWeeks ? 
                    `Week ${this.currentWeek} of ${this.periodWeeks}` : 
                    `Week ${this.currentWeek}`;
                document.getElementById('weekNumber').value = weekDisplay;
                
                // Reset already submitted flag since we just saved and moved to next week
                this.alreadySubmitted = false;
                
                // Recheck eligibility with new week
                this.checkWeekEligibility();
                
                // Show success message with next week info
                setTimeout(() => {
                    if (this.periodWeeks && this.currentWeek <= this.periodWeeks) {
                        showNotification(`Ready for Week ${this.currentWeek} journal!`, 'info');
                    } else {
                        showNotification('You have completed all weeks for this period!', 'success');
                    }
                }, 2000);
            } else {
                showNotification(result.message || 'Failed to save journal', 'error');
            }
        } catch (error) {
            console.error('Error saving journal:', error);
            showNotification('Error saving journal', 'error');
        } finally {
            // Re-enable save button
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-save mr-2"></i>SAVE JOURNAL';
        }
    }
}

// Initialize journal manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('.journal-template')) {
        new JournalManager();
    }
});

// Helper function for notifications
function showNotification(message, type = 'info') {
    const container = document.getElementById('notificationContainer');
    if (!container) return;

    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        </div>
    `;

    // Add notification styles if not already present
    if (!document.querySelector('#notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            .notification {
                position: fixed;
                top: 20px;
                right: 20px;
                background: white;
                border-radius: 10px;
                padding: 1rem 1.5rem;
                box-shadow: 0 10px 25px rgba(0,0,0,0.1);
                z-index: 1000;
                animation: slideIn 0.3s ease;
                max-width: 350px;
            }
            .notification.success {
                border-left: 4px solid #10b981;
            }
            .notification.error {
                border-left: 4px solid #ef4444;
            }
            .notification.info {
                border-left: 4px solid #3b82f6;
            }
            .notification-content {
                display: flex;
                align-items: center;
                gap: 0.75rem;
            }
            .notification.success i {
                color: #10b981;
            }
            .notification.error i {
                color: #ef4444;
            }
            .notification.info i {
                color: #3b82f6;
            }
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    }

    container.appendChild(notification);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 5000);
}
