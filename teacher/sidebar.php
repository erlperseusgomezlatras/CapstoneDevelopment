<?php
// Sidebar component for teacher pages
// Usage: require_once 'sidebar.php'; 
// Make sure $current_page variable is set before including

// Default current page if not set
$current_page = $current_page ?? 'dashboard';

// Base URL - Change this for different environments
// Localhost: "http://localhost/CapstoneDevelopment"
// Production: "https://yourdomain.com"
$base_url = "http://localhost/CapstoneDevelopment";
?>

<!-- Desktop Sidebar -->
<aside class="hidden md:flex md:flex-col md:w-72 md:bg-[#004d23] md:border-r md:border-gray-200 md:fixed md:h-screen md:z-30">
    <div class="flex flex-col h-full">
        <!-- Sidebar Header -->
        <div class="p-6">
            <div class="flex flex-col items-center">
                <img src="<?php echo $base_url; ?>/assets/images/coc-white.png" class="w-55 h-16 mb-3" alt="PHINMA Logo">
                <h2 class="text-xl font-bold text-white text-center">Head Teacher Portal</h2>
            </div>
            <p class="text-sm text-gray-300 mt-2 text-center">PHINMA Education Administration</p>
        </div>
        
        <!-- Navigation -->
        <nav class="flex-1 px-4 pb-4">
            <ul class="space-y-2">
                <li>
                    <a href="<?php echo $base_url; ?>/teacher/dashboard.php" class="sidebar-item <?php echo $current_page === 'dashboard' ? 'active' : ''; ?> flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo $current_page === 'dashboard' ? 'text-white bg-white/10' : 'text-gray-300 hover:text-white hover:bg-white/10'; ?>">
                        <i class="fas fa-home mr-3 h-4 w-4"></i>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>/teacher/pages/teachers.php" class="sidebar-item <?php echo $current_page === 'teachers' ? 'active' : ''; ?> flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo $current_page === 'teachers' ? 'text-white bg-white/10' : 'text-gray-300 hover:text-white hover:bg-white/10'; ?>">
                        <i class="fas fa-user-tie mr-3 h-4 w-4"></i>
                        Teacher Management
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>/teacher/pages/coordinators.php" class="sidebar-item <?php echo $current_page === 'coordinators' ? 'active' : ''; ?> flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo $current_page === 'coordinators' ? 'text-white bg-white/10' : 'text-gray-300 hover:text-white hover:bg-white/10'; ?>">
                        <i class="fas fa-user-check mr-3 h-4 w-4"></i>
                        Coordinator Management
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>/teacher/pages/attendance.php" class="sidebar-item <?php echo $current_page === 'attendance' ? 'active' : ''; ?> flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo $current_page === 'attendance' ? 'text-white bg-white/10' : 'text-gray-300 hover:text-white hover:bg-white/10'; ?>">
                        <i class="fas fa-clock mr-3 h-4 w-4"></i>
                        Attendance Management
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>/teacher/pages/student.php" class="sidebar-item <?php echo $current_page === 'student' ? 'active' : ''; ?> flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo $current_page === 'student' ? 'text-white bg-white/10' : 'text-gray-300 hover:text-white hover:bg-white/10'; ?>">
                        <i class="fas fa-user-graduate mr-3 h-4 w-4"></i>
                        Student Management
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>/teacher/pages/journal.php" class="sidebar-item <?php echo $current_page === 'journal' ? 'active' : ''; ?> flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo $current_page === 'journal' ? 'text-white bg-white/10' : 'text-gray-300 hover:text-white hover:bg-white/10'; ?>">
                        <i class="fas fa-book mr-3 h-4 w-4"></i>
                        Journal Management
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>/teacher/pages/system.php" class="sidebar-item <?php echo $current_page === 'system' ? 'active' : ''; ?> flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo $current_page === 'system' ? 'text-white bg-white/10' : 'text-gray-300 hover:text-white hover:bg-white/10'; ?>">
                        <i class="fas fa-cogs mr-3 h-4 w-4"></i>
                        System Configuration
                    </a>
                </li>
                <li>
                    <a href="#" class="sidebar-item <?php echo $current_page === 'security' ? 'active' : ''; ?> flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo $current_page === 'security' ? 'text-white bg-white/10' : 'text-gray-300 hover:text-white hover:bg-white/10'; ?>">
                        <i class="fas fa-shield-alt mr-3 h-4 w-4"></i>
                        Security & Permissions
                    </a>
                </li>
                <li>
                    <a href="#" class="sidebar-item <?php echo $current_page === 'data' ? 'active' : ''; ?> flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo $current_page === 'data' ? 'text-white bg-white/10' : 'text-gray-300 hover:text-white hover:bg-white/10'; ?>">
                        <i class="fas fa-database mr-3 h-4 w-4"></i>
                        Data & Backups
                    </a>
                </li>
                <li>
                    <a href="#" class="sidebar-item <?php echo $current_page === 'subjects' ? 'active' : ''; ?> flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo $current_page === 'subjects' ? 'text-white bg-white/10' : 'text-gray-300 hover:text-white hover:bg-white/10'; ?>">
                        <i class="fas fa-graduation-cap mr-3 h-4 w-4"></i>
                        Practicum Subjects
                    </a>
                </li>
            </ul>
        </nav>
        
        <!-- Logout Button -->
        <div class="p-4 border-t">
            <button type="button" onclick="showLogoutModal()" class="w-full flex items-center px-3 py-2 text-sm font-medium rounded-md text-gray-300 hover:text-white hover:bg-white/10">
                <i class="fas fa-sign-out-alt mr-3 h-4 w-4"></i>
                Logout
            </button>
        </div>
    </div>
</aside>

<!-- Mobile Sidebar Overlay -->
<div id="mobileSidebar" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-black bg-opacity-50" id="mobileSidebarOverlay"></div>
    <div class="fixed left-0 top-0 h-full w-64 bg-white shadow-lg">
        <div class="flex flex-col h-full">
            <!-- Mobile Sidebar Header -->
            <div class="p-6 border-b flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Head Teacher Portal</h2>
                    <p class="text-sm text-gray-600 mt-1">PHINMA Education Administration</p>
                </div>
                <button id="closeMobileSidebar" class="p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                    <i class="fas fa-times h-5 w-5"></i>
                </button>
            </div>
            
            <!-- Mobile Navigation -->
            <nav class="flex-1 px-4 pb-4">
                <ul class="space-y-2">
                    <li>
                        <a href="<?php echo $base_url; ?>/teacher/dashboard.php" class="sidebar-item <?php echo $current_page === 'dashboard' ? 'active' : ''; ?> flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo $current_page === 'dashboard' ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'; ?>">
                            <i class="fas fa-home mr-3 h-4 w-4"></i>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $base_url; ?>/teacher/pages/teachers.php" class="sidebar-item <?php echo $current_page === 'teachers' ? 'active' : ''; ?> flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo $current_page === 'teachers' ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'; ?>">
                            <i class="fas fa-user-tie mr-3 h-4 w-4"></i>
                            Teacher Management
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $base_url; ?>/teacher/pages/coordinators.php" class="sidebar-item <?php echo $current_page === 'coordinators' ? 'active' : ''; ?> flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo $current_page === 'coordinators' ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'; ?>">
                            <i class="fas fa-user-check mr-3 h-4 w-4"></i>
                            Coordinator Management
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $base_url; ?>/teacher/pages/journal.php" class="sidebar-item <?php echo $current_page === 'journal' ? 'active' : ''; ?> flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo $current_page === 'journal' ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'; ?>">
                            <i class="fas fa-book mr-3 h-4 w-4"></i>
                            Journal Management
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $base_url; ?>/teacher/pages/system.php" class="sidebar-item <?php echo $current_page === 'system' ? 'active' : ''; ?> flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo $current_page === 'system' ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'; ?>">
                            <i class="fas fa-cogs mr-3 h-4 w-4"></i>
                            System Configuration
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $base_url; ?>/teacher/pages/attendance.php" class="sidebar-item <?php echo $current_page === 'attendance' ? 'active' : ''; ?> flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo $current_page === 'attendance' ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'; ?>">
                            <i class="fas fa-clock mr-3 h-4 w-4"></i>
                            Attendance Management
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $base_url; ?>/teacher/pages/student.php" class="sidebar-item <?php echo $current_page === 'student' ? 'active' : ''; ?> flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo $current_page === 'student' ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'; ?>">
                            <i class="fas fa-user-graduate mr-3 h-4 w-4"></i>
                            Student Management
                        </a>
                    </li>
                    <li>
                        <a href="#" class="sidebar-item <?php echo $current_page === 'security' ? 'active' : ''; ?> flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo $current_page === 'security' ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'; ?>">
                            <i class="fas fa-shield-alt mr-3 h-4 w-4"></i>
                            Security & Permissions
                        </a>
                    </li>
                    <li>
                        <a href="#" class="sidebar-item <?php echo $current_page === 'data' ? 'active' : ''; ?> flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo $current_page === 'data' ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'; ?>">
                            <i class="fas fa-database mr-3 h-4 w-4"></i>
                            Data & Backups
                        </a>
                    </li>
                    <li>
                        <a href="#" class="sidebar-item <?php echo $current_page === 'subjects' ? 'active' : ''; ?> flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo $current_page === 'subjects' ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'; ?>">
                            <i class="fas fa-graduation-cap mr-3 h-4 w-4"></i>
                            Practicum Subjects
                        </a>
                    </li>
                </ul>
            </nav>
            
            <!-- Mobile Logout -->
            <div class="p-4 border-t">
                <button type="button" onclick="showLogoutModal()" class="w-full flex items-center px-3 py-2 text-sm font-medium rounded-md text-red-600 hover:text-red-700 hover:bg-red-50">
                    <i class="fas fa-sign-out-alt mr-3 h-4 w-4"></i>
                    Logout
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Base URL from PHP
    const baseUrl = "<?php echo $base_url; ?>";
    
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
    fetch(baseUrl + '/api/auth.php', {
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
            window.location.href = baseUrl + '/login.php';
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
</script>
