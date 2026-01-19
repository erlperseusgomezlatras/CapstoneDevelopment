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

// Logout function
function logout() {
    if (confirm('Are you sure you want to logout?')) {
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
                alert('Logout failed: ' + result.message);
            }
        })
        .catch(error => {
            console.error('Logout error:', error);
            alert('An error occurred during logout');
        });
    }
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
