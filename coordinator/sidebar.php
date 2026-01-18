<?php
// Sidebar component for coordinator pages
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
<aside class="hidden md:flex md:flex-col md:w-72 md:bg-[#1e40af] md:border-r md:border-gray-200">
    <div class="flex flex-col h-full">
        <!-- Sidebar Header -->
        <div class="p-6">
            <div class="flex flex-col items-center">
                <img src="<?php echo $base_url; ?>/assets/images/coc-white.png" class="w-55 h-16 mb-3" alt="PHINMA Logo">
                <h2 class="text-xl font-bold text-white text-center">Coordinator Portal</h2>
            </div>
            <p class="text-sm text-gray-300 mt-2 text-center">PHINMA Education Administration</p>
        </div>
        
        <!-- Navigation -->
        <nav class="flex-1 px-4 pb-4">
            <ul class="space-y-2">
                <li>
                    <a href="<?php echo $base_url; ?>/coordinator/dashboard.php" class="sidebar-item <?php echo $current_page === 'dashboard' ? 'active' : ''; ?> flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo $current_page === 'dashboard' ? 'text-white bg-white/10' : 'text-gray-300 hover:text-white hover:bg-white/10'; ?>">
                        <i class="fas fa-home mr-3 h-4 w-4"></i>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_url; ?>/coordinator/student.php" class="sidebar-item <?php echo $current_page === 'student' ? 'active' : ''; ?> flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo $current_page === 'student' ? 'text-white bg-white/10' : 'text-gray-300 hover:text-white hover:bg-white/10'; ?>">
                        <i class="fas fa-user-graduate mr-3 h-4 w-4"></i>
                        Student Management
                    </a>
                </li>
                <li>
                    <a href="#" class="sidebar-item <?php echo $current_page === 'reports' ? 'active' : ''; ?> flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo $current_page === 'reports' ? 'text-white bg-white/10' : 'text-gray-300 hover:text-white hover:bg-white/10'; ?>">
                        <i class="fas fa-chart-bar mr-3 h-4 w-4"></i>
                        Reports
                    </a>
                </li>
                <li>
                    <a href="#" class="sidebar-item <?php echo $current_page === 'settings' ? 'active' : ''; ?> flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo $current_page === 'settings' ? 'text-white bg-white/10' : 'text-gray-300 hover:text-white hover:bg-white/10'; ?>">
                        <i class="fas fa-cog mr-3 h-4 w-4"></i>
                        Settings
                    </a>
                </li>
            </ul>
        </nav>
        
        <!-- Logout Button -->
        <div class="p-4 border-t">
            <button type="button" onclick="logout()" class="w-full flex items-center px-3 py-2 text-sm font-medium rounded-md text-gray-300 hover:text-white hover:bg-white/10">
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
                    <h2 class="text-xl font-bold text-gray-900">Coordinator Portal</h2>
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
                        <a href="<?php echo $base_url; ?>/coordinator/dashboard.php" class="sidebar-item <?php echo $current_page === 'dashboard' ? 'active' : ''; ?> flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo $current_page === 'dashboard' ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'; ?>">
                            <i class="fas fa-home mr-3 h-4 w-4"></i>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $base_url; ?>/coordinator/student.php" class="sidebar-item <?php echo $current_page === 'student' ? 'active' : ''; ?> flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo $current_page === 'student' ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'; ?>">
                            <i class="fas fa-user-graduate mr-3 h-4 w-4"></i>
                            Student Management
                        </a>
                    </li>
                    <li>
                        <a href="#" class="sidebar-item <?php echo $current_page === 'reports' ? 'active' : ''; ?> flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo $current_page === 'reports' ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'; ?>">
                            <i class="fas fa-chart-bar mr-3 h-4 w-4"></i>
                            Reports
                        </a>
                    </li>
                    <li>
                        <a href="#" class="sidebar-item <?php echo $current_page === 'settings' ? 'active' : ''; ?> flex items-center px-3 py-2 text-sm font-medium rounded-md <?php echo $current_page === 'settings' ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'; ?>">
                            <i class="fas fa-cog mr-3 h-4 w-4"></i>
                            Settings
                        </a>
                    </li>
                </ul>
            </nav>
            
            <!-- Mobile Logout Button -->
            <div class="p-4 border-t">
                <button type="button" onclick="logout()" class="w-full flex items-center px-3 py-2 text-sm font-medium rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-50">
                    <i class="fas fa-sign-out-alt mr-3 h-4 w-4"></i>
                    Logout
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Menu Button -->
<div class="md:hidden fixed top-4 left-4 z-40">
    <button id="mobileMenuButton" class="p-2 rounded-md text-gray-600 bg-white shadow-md hover:text-gray-900 hover:bg-gray-50">
        <i class="fas fa-bars h-6 w-6"></i>
    </button>
</div>

<script>
// Mobile sidebar functionality
document.getElementById('mobileMenuButton').addEventListener('click', function() {
    document.getElementById('mobileSidebar').classList.remove('hidden');
});

document.getElementById('closeMobileSidebar').addEventListener('click', function() {
    document.getElementById('mobileSidebar').classList.add('hidden');
});

document.getElementById('mobileSidebarOverlay').addEventListener('click', function() {
    document.getElementById('mobileSidebar').classList.add('hidden');
});

function logout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = '../login.php';
    }
}
</script>
