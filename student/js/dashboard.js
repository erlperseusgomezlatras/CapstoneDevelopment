// Initialize the page
document.addEventListener('DOMContentLoaded', function () {
    // Initialize attendance functionality
    if (typeof initializeAttendance === 'function') {
        initializeAttendance(studentSchoolId);
    }

    // Check URL hash for tab persistence
    const currentHash = window.location.hash.replace('#', '');
    const validTabs = ['attendance', 'journal', 'activity-checklist', 'profile'];

    if (currentHash && validTabs.includes(currentHash)) {
        switchTab(currentHash);
    }
});

// Tab switching function
function switchTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });

    // Remove active class from all desktop tab buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active');
        button.classList.remove('text-green-700');
        button.classList.add('text-gray-500');
    });

    // Remove active class from all mobile nav items
    document.querySelectorAll('.mobile-nav-item').forEach(item => {
        item.classList.remove('active');
    });

    // Show selected tab content
    const selectedTab = document.getElementById(tabName);
    if (selectedTab) {
        selectedTab.classList.add('active');
    }

    // Add active class to desktop tab button
    const activeButton = document.querySelector(`.tab-button[onclick="switchTab('${tabName}')"]`);
    if (activeButton) {
        activeButton.classList.add('active');
        activeButton.classList.remove('text-gray-500');
        activeButton.classList.add('text-green-700');
    }

    // Add active class to mobile nav item
    const activeNavItem = document.getElementById(`nav-${tabName}`);
    if (activeNavItem) {
        activeNavItem.classList.add('active');
    }

    // Update URL hash without refreshing the page
    window.location.hash = tabName;

    // Load tab-specific content
    if (tabName === 'journal') {
        loadJournalComponent();
    } else if (tabName === 'profile') {
        loadProfileComponent();
    }
}

// Load journal component
function loadJournalComponent() {
    const journalContent = document.getElementById('journalContent');
    if (!journalContent) return;

    // Show loading state
    journalContent.innerHTML = `
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <div class="spinner mx-auto mb-4"></div>
            <p class="text-gray-600">Loading journal...</p>
        </div>
    `;

    // Load the journal component
    fetch('components/journal.php')
        .then(response => response.text())
        .then(html => {
            journalContent.innerHTML = html;

            // Initialize journal functionality
            if (typeof JournalManager !== 'undefined') {
                new JournalManager();
            }
        })
        .catch(error => {
            console.error('Error loading journal component:', error);
            journalContent.innerHTML = `
                <div class="bg-white rounded-lg shadow p-8 text-center">
                    <i class="fas fa-exclamation-triangle text-6xl text-red-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">Error Loading Journal</h3>
                    <p class="text-gray-500">Unable to load the journal component. Please try again.</p>
                </div>
            `;
        });
}

// Load profile component
function loadProfileComponent() {
    const profileContent = document.getElementById('profileContent');
    if (!profileContent) return;

    // Show loading state
    profileContent.innerHTML = `
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <div class="spinner mx-auto mb-4"></div>
            <p class="text-gray-600">Loading profile...</p>
        </div>
    `;

    // Load the profile component
    fetch('components/profile.php')
        .then(response => response.text())
        .then(html => {
            profileContent.innerHTML = html;

            // Initialize profile functionality if the script is loaded
            if (typeof initProfile === 'function') {
                initProfile();
            }
        })
        .catch(error => {
            console.error('Error loading profile component:', error);
            profileContent.innerHTML = `
                <div class="bg-white rounded-lg shadow p-8 text-center">
                    <i class="fas fa-exclamation-triangle text-6xl text-red-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">Error Loading Profile</h3>
                    <p class="text-gray-500">Unable to load the profile component. Please try again.</p>
                </div>
            `;
        });
}

// Logout Modal
function showLogoutModal() {
    const modalHtml = `
        <div id="logoutModal" class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
            <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 transform transition-all">
                <div class="p-6">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                        <i class="fas fa-sign-out-alt text-red-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Confirm Logout</h3>
                    <p class="text-sm text-gray-600 text-center mb-6">Are you sure you want to logout? You will need to sign in again to access your account.</p>
                    <div class="flex space-x-3">
                        <button type="button" onclick="closeLogoutModal()" class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                            Cancel
                        </button>
                        <button type="button" onclick="confirmLogout()" class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors">
                            Logout
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Add modal to body
    const modalContainer = document.createElement('div');
    modalContainer.innerHTML = modalHtml;
    document.body.appendChild(modalContainer);

    // Add escape key listener
    document.addEventListener('keydown', handleEscapeKey);
}

function closeLogoutModal() {
    const modal = document.getElementById('logoutModal');
    if (modal) {
        modal.remove();
        document.removeEventListener('keydown', handleEscapeKey);
    }
}

function handleEscapeKey(e) {
    if (e.key === 'Escape') {
        closeLogoutModal();
    }
}

function confirmLogout() {
    closeLogoutModal();
    performLogout();
}

// Logout implementation
async function performLogout() {
    try {
        const response = await fetch(window.APP_CONFIG.API_BASE_URL + 'auth.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'logout'
            })
        });

        const text = await response.text();
        let result;

        try {
            result = JSON.parse(text);
        } catch (e) {
            console.error('Raw logout response:', text);
            throw new Error('Invalid server response');
        }

        if (result.success) {
            // Redirect to login page
            window.location.href = '../login.php';
        } else {
            showErrorToast('Logout failed: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Logout error:', error);
        showErrorToast('An error occurred during logout: ' + error.message);
    }
}

// Error toast function
function showErrorToast(message) {
    const toastHtml = `
        <div id="errorToast" class="fixed top-4 right-4 z-50 bg-red-600 text-white px-6 py-3 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <span class="text-sm font-medium">${message}</span>
            </div>
        </div>
    `;

    const toastContainer = document.createElement('div');
    toastContainer.innerHTML = toastHtml;
    document.body.appendChild(toastContainer);

    // Animate in
    setTimeout(() => {
        const toast = document.getElementById('errorToast');
        if (toast) {
            toast.classList.remove('translate-x-full');
        }
    }, 100);

    // Remove after 3 seconds
    setTimeout(() => {
        const toast = document.getElementById('errorToast');
        if (toast) {
            toast.classList.add('translate-x-full');
            setTimeout(() => toast.remove(), 300);
        }
    }, 3000);
}

// Show notification
function showNotification(message, type = 'info') {
    const container = document.getElementById('notificationContainer');
    const notification = document.createElement('div');

    const bgColor = type === 'success' ? 'bg-green-500' :
        type === 'error' ? 'bg-red-500' :
            type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500';

    notification.className = `notification ${bgColor} text-white px-6 py-4 rounded-lg shadow-lg mb-4`;
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle'} mr-3"></i>
            <span>${message}</span>
        </div>
    `;

    container.appendChild(notification);

    // Remove notification after 5 seconds
    setTimeout(() => {
        notification.remove();
    }, 5000);
}
