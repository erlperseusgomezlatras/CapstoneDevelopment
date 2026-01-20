// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
    // Initialize attendance functionality
    if (typeof initializeAttendance === 'function') {
        initializeAttendance(studentSchoolId);
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
    document.getElementById(tabName).classList.add('active');
    
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

// Original logout function (renamed)
function performLogout() {
    fetch(window.APP_CONFIG.API_BASE_URL + 'auth.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'logout'
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Redirect to login page
            window.location.href = '../login.php';
        } else {
            showErrorToast('Logout failed: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Logout error:', error);
        showErrorToast('An error occurred during logout. Please try again.');
    });
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
                   type === 'error' ? 'bg-red-500' : 'bg-blue-500';
    
    notification.className = `notification ${bgColor} text-white px-6 py-4 rounded-lg shadow-lg mb-4`;
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'} mr-3"></i>
            <span>${message}</span>
        </div>
    `;
    
    container.appendChild(notification);
    
    // Remove notification after 5 seconds
    setTimeout(() => {
        notification.remove();
    }, 5000);
}
