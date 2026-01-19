<?php
// Include configuration
require_once '../../config/config.php';

session_start();

// Check if user is logged in and is a Head Teacher (level_id = 2)
if (!isset($_SESSION['user_id']) && !(isset($_COOKIE['authToken']) && isset($_COOKIE['userData']))) {
    header('Location: ../../login.php');
    exit();
}

// Get user data from session or cookie
$userData = null;
if (isset($_SESSION['user_id'])) {
    // Use session data if available
    $userData = [
        'level' => $_SESSION['user_role'] ?? 'Head Teacher',
        'firstname' => $_SESSION['first_name'] ?? 'Teacher',
        'email' => $_SESSION['email'] ?? ''
    ];
} else {
    // Use cookie data if session is not available
    $userData = json_decode($_COOKIE['userData'], true);
}

// Check if user is a Head Teacher
if ($userData['level'] !== 'Head Teacher') {
    header('Location: ../../login.php');
    exit();
}

// Set current page for sidebar
$current_page = 'system';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Configuration - Head Teacher Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.js"></script>
    <link href="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.css" rel="stylesheet" />
    <script src="https://unpkg.com/@mapbox/mapbox-gl-geocoder@4.7.4/dist/mapbox-gl-geocoder.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/@mapbox/mapbox-gl-geocoder@4.7.4/dist/mapbox-gl-geocoder.css" type="text/css" />
    <link rel="stylesheet" href="../../assets/css/system.css">
</head>
<body class="bg-gray-100">
    <!-- Main Container -->
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php require_once '../sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Header -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="px-4 sm:px-6 lg:px-8 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <button id="mobileSidebarToggle" class="md:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                                <i class="fas fa-bars h-6 w-6"></i>
                            </button>
                            <h1 class="ml-4 text-xl font-semibold text-gray-900">System Configuration</h1>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-600">
                                <span class="text-sm text-gray-600">Welcome, <?php echo htmlspecialchars($userData['firstname']); ?></span>
                                <button class="p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                                    <i class="fas fa-bell"></i>
                                </button>
                            </span>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Main Page Content -->
            <main class="flex-1 overflow-y-auto bg-gray-50 p-4 sm:p-6 lg:p-8">
                <!-- Tabs Navigation -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="border-b border-gray-200">
                        <nav class="flex -mb-px">
                            <button onclick="switchTab('partnered-schools')" 
                                    class="tab-button active px-6 py-3 text-sm font-medium text-green-700 hover:text-green-800 focus:outline-none focus:text-green-800">
                                <i class="fas fa-school mr-2"></i>
                                Partnered Schools
                            </button>
                            <button onclick="switchTab('sections')" 
                                    class="tab-button px-6 py-3 text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700">
                                <i class="fas fa-users mr-2"></i>
                                Sections
                            </button>
                            <button onclick="switchTab('email-domains')" 
                                    class="tab-button px-6 py-3 text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700">
                                <i class="fas fa-envelope mr-2"></i>
                                Email Domains
                            </button>
                            <button onclick="switchTab('system-settings')" 
                                    class="tab-button px-6 py-3 text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700">
                                <i class="fas fa-cogs mr-2"></i>
                                System Settings
                            </button>
                        </nav>
                    </div>
                </div>
                
                <!-- Partnered Schools Tab Content -->
                <div id="partnered-schools" class="tab-content active">
                    <!-- Search and Actions -->
                    <div class="bg-white rounded-lg shadow p-4 mb-6">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div class="flex-1 max-w-lg">
                                <div class="relative">
                                    <input type="text" id="searchInput" placeholder="Search partnered schools..." 
                                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button onclick="openAddModal()" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                    <i class="fas fa-plus mr-2"></i>
                                    Add Partnered School
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Partnered Schools Table -->
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="overflow-x-auto min-h-[600px] max-h-[800px] overflow-y-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            School ID Code
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            School Name
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Address
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Location
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Geofence Radius
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="partneredSchoolsTableBody" class="bg-white divide-y divide-gray-200">
                                    <!-- Partnered schools will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                        <div id="noDataMessage" class="hidden p-8 text-center text-gray-500">
                            <i class="fas fa-school text-4xl mb-4 text-gray-300"></i>
                            <p class="text-lg font-medium mb-2">No partnered schools found</p>
                            <p class="text-sm text-gray-400">Start by adding your first partnered school to the system</p>
                        </div>
                    </div>
                </div>
                
                <!-- Sections Tab Content -->
                <div id="sections" class="tab-content">
                    <!-- Search and Actions -->
                    <div class="bg-white rounded-lg shadow p-4 mb-6">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div class="flex-1 max-w-lg">
                                <div class="relative">
                                    <input type="text" id="sectionsSearchInput" placeholder="Search sections..." 
                                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button onclick="openAddSectionModal()" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                    <i class="fas fa-plus mr-2"></i>
                                    Add Section
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sections Table -->
                    <div class="bg-white rounded-lg shadow overflow-hidden relative">
                        <div class="overflow-x-auto min-h-[600px] max-h-[800px] overflow-y-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Section Name
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Assigned Partnered School
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            School ID Code
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="sectionsTableBody" class="bg-white divide-y divide-gray-200">
                                    <!-- Sections will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                        <div id="sectionsNoDataMessage" class="hidden absolute inset-0 flex items-center justify-center bg-white">
                            <div class="text-center text-gray-500">
                                <i class="fas fa-users text-4xl mb-4 text-gray-300"></i>
                                <p class="text-lg font-medium mb-2">No sections found</p>
                                <p class="text-sm text-gray-400">Start by adding your first section to the system</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Email Domains Tab Content -->
                <div id="email-domains" class="tab-content">
                    <div class="bg-white rounded-lg shadow p-8 text-center">
                        <i class="fas fa-envelope text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-700 mb-2">Email Domains Management</h3>
                        <p class="text-gray-500">This feature is coming soon...</p>
                    </div>
                </div>
                
                <!-- System Settings Tab Content -->
                <div id="system-settings" class="tab-content">
                    <div class="bg-white rounded-lg shadow p-8 text-center">
                        <i class="fas fa-cogs text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-700 mb-2">System Settings</h3>
                        <p class="text-gray-500">This feature is coming soon...</p>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Add/Edit Partnered School Modal -->
    <div id="partneredSchoolModal" class="modal">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl w-[800px] max-w-[90vw] max-h-[90vh] overflow-y-auto relative">
                <div class="px-6 py-4 border-b border-gray-200 sticky top-0 bg-white z-10">
                    <h3 class="text-lg font-semibold text-gray-900" id="modalTitle">Add Partnered School</h3>
                </div>
                
                <form id="partneredSchoolForm" class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">School ID Code *</label>
                            <input type="text" id="schoolIdCode" name="school_id_code" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            <span class="text-xs text-red-500" id="schoolIdCodeError"></span>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">School Name *</label>
                            <input type="text" id="schoolName" name="name" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            <span class="text-xs text-red-500" id="schoolNameError"></span>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Address *</label>
                            <textarea id="address" name="address" rows="3" required
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
                            <span class="text-xs text-red-500" id="addressError"></span>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Latitude *</label>
                            <input type="number" id="latitude" name="latitude" step="any" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            <span class="text-xs text-gray-500" id="latitudeError"></span>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Longitude *</label>
                            <input type="number" id="longitude" name="longitude" step="any" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            <span class="text-xs text-red-500" id="longitudeError"></span>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Geofence Radius (meters)</label>
                            <input type="number" id="geofencingRadius" name="geofencing_radius" value="80" min="10" max="1000"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            <span class="text-xs text-gray-500">Default: 80 meters</span>
                        </div>
                    </div>
                    
                    <!-- Map Preview -->
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Location Preview</label>
                        
                        <!-- Search input for geocoding -->
                        <div class="mb-3">
                            <div class="relative">
                                <input type="text" id="mapSearchInput" placeholder="Search for address..." 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 pr-10"
                                       onkeypress="if(event.key === 'Enter') searchAddress()">
                                <button onclick="searchAddress()" 
                                        class="absolute right-2 top-2 text-gray-500 hover:text-gray-700">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div id="map" style="height: 400px; border-radius: 8px; border: 1px solid #e5e7eb;"></div>
                        <p class="text-xs text-gray-500 mt-2">Click on the map to set location or search for an address above</p>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeModal()" 
                                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                            <i class="fas fa-save mr-2"></i>
                            Save Partnered School
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Section Modal -->
    <div id="sectionModal" class="modal">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl w-[600px] max-w-[90vw] max-h-[90vh] overflow-y-auto relative">
                <div class="px-6 py-4 border-b border-gray-200 sticky top-0 bg-white z-10">
                    <h3 class="text-lg font-semibold text-gray-900" id="sectionModalTitle">Add Section</h3>
                </div>
                
                <form id="sectionForm" class="p-6">
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Section Name *</label>
                            <input type="text" id="sectionName" name="section_name" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            <span class="text-xs text-red-500" id="sectionNameError"></span>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Assigned Partnered School</label>
                            <select id="sectionSchoolId" name="school_id" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">-- No Partnered School Assigned --</option>
                                <!-- Partnered schools will be loaded here -->
                            </select>
                            <span class="text-xs text-gray-500">Optional: Assign a partnered school to this section</span>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeSectionModal()" 
                                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                            <i class="fas fa-save mr-2"></i>
                            Save Section
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Notification Container -->
    <div id="notificationContainer"></div>
    
    <script src="../../assets/js/config.js"></script>
    <script src="../js/system.js"></script>
    </body>
</html>
