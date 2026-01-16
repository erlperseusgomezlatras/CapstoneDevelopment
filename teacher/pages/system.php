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
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
    <style>
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s;
        }
        
        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* Map container */
        #map {
            height: 400px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        
        /* Tab styles */
        .tab-button {
            position: relative;
            transition: all 0.3s ease;
        }
        
        .tab-button.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background-color: #004d23;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Notification styles */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 2000;
            max-width: 400px;
            animation: slideInRight 0.3s ease;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
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
                                <i class="fas fa-user-circle mr-2"></i>
                                <?php echo htmlspecialchars($userData['firstname']); ?>
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
                                <button onclick="loadPartneredSchools()" class="w-full px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                                    <i class="fas fa-search mr-2"></i>
                                    Search
                                </button>
                                <button onclick="openAddModal()" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                    <i class="fas fa-plus mr-2"></i>
                                    Add Partnered School
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Partnered Schools Table -->
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="overflow-x-auto">
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
            <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto relative">
                <div class="px-6 py-4 border-b border-gray-200">
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
                        <div id="map"></div>
                        <p class="text-xs text-gray-500 mt-2">Click on the map to set location or enter coordinates manually</p>
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
    
    <!-- Notification Container -->
    <div id="notificationContainer"></div>
    
    <script>
        // Global variables
        let currentEditingSchool = null;
        let map = null;
        let marker = null;
        let geofenceCircle = null;
        
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadPartneredSchools();
            initializeMap();
            
            // Search functionality
            document.getElementById('searchInput').addEventListener('input', function() {
                loadPartneredSchools();
            });
            
            // Address input geocoding
            let addressTimeout;
            document.getElementById('address').addEventListener('input', function() {
                clearTimeout(addressTimeout);
                const address = this.value.trim();
                
                if (address.length > 5) {
                    addressTimeout = setTimeout(() => {
                        geocodeAddress(address);
                    }, 1000); // Wait 1 second after user stops typing
                }
            });
            
            // Form submission
            document.getElementById('partneredSchoolForm').addEventListener('submit', function(e) {
                e.preventDefault();
                savePartneredSchool();
            });
            
            // Mobile sidebar
            const mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
            const mobileSidebar = document.getElementById('mobileSidebar');
            const mobileSidebarOverlay = document.getElementById('mobileSidebarOverlay');
            const closeMobileSidebar = document.getElementById('closeMobileSidebar');
            
            mobileSidebarToggle.addEventListener('click', function() {
                mobileSidebar.classList.remove('hidden');
            });
            
            function closeMobileSidebarFunc() {
                mobileSidebar.classList.add('hidden');
            }
            
            closeMobileSidebar.addEventListener('click', closeMobileSidebarFunc);
            mobileSidebarOverlay.addEventListener('click', closeMobileSidebarFunc);
        });
        
        // Update geofence circle
        function updateGeofenceCircle(lat, lng) {
            if (geofenceCircle) {
                const radius = parseInt(document.getElementById('geofencingRadius').value) || 80;
                geofenceCircle.setRadius(radius);
                geofenceCircle.setCenter({ lat: parseFloat(lat), lng: parseFloat(lng) });
            }
        }
        
        // Tab switching
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active', 'text-green-700');
                button.classList.add('text-gray-500');
            });
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked tab button
            event.target.classList.add('active', 'text-green-700');
            event.target.classList.remove('text-gray-500');
        }
        
        // Initialize map
        function initializeMap() {
            // Initialize map with Philippines center
            map = L.map('map').setView([12.8797, 121.7740], 6);
            
            // Add tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);
            
            // Add search control to map
            const geocoder = L.Control.geocoder({
                defaultMarkGeocode: false,
                placeholder: 'Search by address or coordinates...',
                collapsed: true,
                position: 'topright'
            }).addTo(map);
            
            // Handle geocoding results
            geocoder.on('markgeocode', function(e) {
                const lat = e.geocode.center.lat;
                const lng = e.geocode.center.lng;
                setMapLocation(lat, lng);
                showNotification('Address found and located on map', 'success');
            });
            
            // Add click event to map
            map.on('click', function(e) {
                setMapLocation(e.latlng.lat, e.latlng.lng);
            });
            
            // Add search function
            function searchByCoordinates(lat, lng) {
                if (lat && lng) {
                    const formData = new FormData();
                    formData.append('operation', 'search_near');
                    formData.append('json', JSON.stringify({ lat, lng }));
                    
                    fetch('../../api/partnered_schools.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            renderPartneredSchoolsTable(result.data);
                            showNotification(`Found ${result.data.length} schools near coordinates`, 'success');
                        } else {
                            showNotification(result.message, 'error');
                        }
                    })
                    .catch(error => {
                        showNotification('Error searching near location', 'error');
                    });
                }
            }
        }
        
        // Geocode address function
        async function geocodeAddress(address) {
            try {
                console.log('Geocoding address:', address);
                
                // Use our server-side proxy to avoid CORS issues
                const response = await fetch(`../../api/geocode.php?address=${encodeURIComponent(address)}`);
                
                console.log('API Response status:', response.status);
                const data = await response.json();
                console.log('API Response data:', data);
                
                if (data.success) {
                    const lat = parseFloat(data.lat);
                    const lng = parseFloat(data.lng);
                    
                    console.log('Setting location to:', lat, lng);
                    setMapLocation(lat, lng);
                    showNotification('Address found and located on map', 'success');
                    return;
                }
                
                console.log('No results found, showing error');
                showNotification(data.message || 'Address not found. Try: 1) Simpler address format, 2) Try just city name, 3) Click on map directly', 'error');
                
            } catch (error) {
                console.error('Geocoding error:', error);
                showNotification('Error finding address. Please try again.', 'error');
            }
        }
        
        // Update geofence when radius changes
        function updateGeofenceCircle(lat, lng) {
            if (geofenceCircle) {
                const radius = parseInt(document.getElementById('geofencingRadius').value) || 80;
                geofenceCircle.setRadius(radius);
                geofenceCircle.setCenter({ lat: parseFloat(lat), lng: parseFloat(lng) });
            }
        }
        
        // Set map location
        function setMapLocation(lat, lng) {
            // Update input fields
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;
            
            // Remove existing marker and circle
            if (marker) {
                map.removeLayer(marker);
            }
            if (geofenceCircle) {
                map.removeLayer(geofenceCircle);
            }
            
            // Add new marker
            marker = L.marker([lat, lng]).addTo(map);
            
            // Add geofence circle
            const radius = parseInt(document.getElementById('geofencingRadius').value) || 80;
            geofenceCircle = L.circle([lat, lng], {
                color: '#004d23',
                fillColor: '#004d23',
                fillOpacity: 0.2,
                radius: radius
            }).addTo(map);
            
            // Center map on new location
            map.setView([lat, lng], 15);
            
            // Make marker draggable to update coordinates
            marker.on('dragend', function(e) {
                const newLat = e.latLng.lat();
                const newLng = e.latLng.lng();
                document.getElementById('latitude').value = newLat;
                document.getElementById('longitude').value = newLng;
                updateGeofenceCircle(newLat, newLng);
            });
        }
        
        // Update geofence radius
        document.getElementById('geofencingRadius').addEventListener('input', function() {
            if (marker && geofenceCircle) {
                const lat = parseFloat(document.getElementById('latitude').value);
                const lng = parseFloat(document.getElementById('longitude').value);
                const radius = parseInt(this.value) || 80;
                
                map.removeLayer(geofenceCircle);
                geofenceCircle = L.circle([lat, lng], {
                    color: '#004d23',
                    fillColor: '#004d23',
                    fillOpacity: 0.2,
                    radius: radius
                }).addTo(map);
            }
        });
        
        // Open add modal
        function openAddModal() {
            currentEditingSchool = null;
            document.getElementById('modalTitle').textContent = 'Add Partnered School';
            document.getElementById('partneredSchoolForm').reset();
            document.getElementById('geofencingRadius').value = '80';
            
            // Reset map
            if (marker) {
                map.removeLayer(marker);
            }
            if (geofenceCircle) {
                map.removeLayer(geofenceCircle);
            }
            
            document.getElementById('partneredSchoolModal').classList.add('show');
        }
        
        // Load partnered schools
        async function loadPartneredSchools() {
            try {
                const searchTerm = document.getElementById('searchInput').value.trim();
                
                const formData = new FormData();
                formData.append('operation', 'read');
                formData.append('json', JSON.stringify({
                    search: searchTerm
                }));
                
                const response = await fetch('../../api/partnered_schools.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    renderPartneredSchoolsTable(result.data);
                } else {
                    showNotification(result.message, 'error');
                    renderPartneredSchoolsTable([]);
                }
            } catch (error) {
                showNotification('An error occurred while loading partnered schools', 'error');
                console.error('Error:', error);
                renderPartneredSchoolsTable([]);
            }
        }
        
        // Render partnered schools table
        function renderPartneredSchoolsTable(schools) {
            const tableBody = document.getElementById('partneredSchoolsTableBody');
            const noDataMessage = document.getElementById('noDataMessage');
            
            if (schools.length === 0) {
                tableBody.innerHTML = '';
                noDataMessage.classList.remove('hidden');
                return;
            }
            
            noDataMessage.classList.add('hidden');
            
            tableBody.innerHTML = schools.map(school => `
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        ${school.school_id_code}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        ${school.name}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        <div class="max-w-xs truncate" title="${school.address}">
                            ${school.address}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <div class="text-xs">
                            <div>Lat: ${school.latitude}</div>
                            <div>Lng: ${school.longitude}</div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        ${school.geofencing_radius || 80}m
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button onclick="openEditModal(${JSON.stringify(school).replace(/"/g, '&quot;')})" 
                                class="text-indigo-600 hover:text-indigo-900 mr-3">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deletePartneredSchool(${school.id})" 
                                class="text-red-600 hover:text-red-900">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }
        
        // Open add modal
        function openAddModal() {
            currentEditingSchool = null;
            document.getElementById('modalTitle').textContent = 'Add Partnered School';
            document.getElementById('partneredSchoolForm').reset();
            document.getElementById('geofencingRadius').value = '80';
            
            // Reset map
            if (marker) {
                map.removeLayer(marker);
            }
            if (geofenceCircle) {
                map.removeLayer(geofenceCircle);
            }
            
            document.getElementById('partneredSchoolModal').classList.add('show');
        }
        
        // Open edit modal
        function openEditModal(school) {
            currentEditingSchool = school;
            document.getElementById('modalTitle').textContent = 'Edit Partnered School';
            
            // Fill form with school data
            document.getElementById('schoolIdCode').value = school.school_id_code;
            document.getElementById('schoolName').value = school.name;
            document.getElementById('address').value = school.address;
            document.getElementById('latitude').value = school.latitude;
            document.getElementById('longitude').value = school.longitude;
            document.getElementById('geofencingRadius').value = school.geofencing_radius || 80;
            
            // Set map location
            setMapLocation(school.latitude, school.longitude);
            
            document.getElementById('partneredSchoolModal').classList.add('show');
        }
        
        // Close modal
        function closeModal() {
            document.getElementById('partneredSchoolModal').classList.remove('show');
            clearValidationErrors();
        }
        
        // Clear validation errors
        function clearValidationErrors() {
            const errorElements = document.querySelectorAll('[id$="Error"]');
            errorElements.forEach(element => {
                element.textContent = '';
            });
        }
        
        // Validate form
        function validateForm() {
            clearValidationErrors();
            let isValid = true;
            
            const schoolIdCode = document.getElementById('schoolIdCode').value.trim();
            const schoolName = document.getElementById('schoolName').value.trim();
            const address = document.getElementById('address').value.trim();
            const latitude = document.getElementById('latitude').value;
            const longitude = document.getElementById('longitude').value;
            
            if (!schoolIdCode) {
                document.getElementById('schoolIdCodeError').textContent = 'School ID Code is required';
                isValid = false;
            }
            
            if (!schoolName) {
                document.getElementById('schoolNameError').textContent = 'School Name is required';
                isValid = false;
            }
            
            if (!address) {
                document.getElementById('addressError').textContent = 'Address is required';
                isValid = false;
            }
            
            if (!latitude || isNaN(latitude)) {
                document.getElementById('latitudeError').textContent = 'Valid latitude is required';
                isValid = false;
            }
            
            if (!longitude || isNaN(longitude)) {
                document.getElementById('longitudeError').textContent = 'Valid longitude is required';
                isValid = false;
            }
            
            return isValid;
        }
        
        // Save partnered school
        async function savePartneredSchool() {
            if (!validateForm()) {
                return;
            }
            
            try {
                const formData = {
                    school_id_code: document.getElementById('schoolIdCode').value.trim(),
                    name: document.getElementById('schoolName').value.trim(),
                    address: document.getElementById('address').value.trim(),
                    latitude: parseFloat(document.getElementById('latitude').value),
                    longitude: parseFloat(document.getElementById('longitude').value),
                    geofencing_radius: parseInt(document.getElementById('geofencingRadius').value) || 80
                };
                
                if (currentEditingSchool) {
                    formData.id = currentEditingSchool.id;
                }
                
                const apiFormData = new FormData();
                apiFormData.append('operation', currentEditingSchool ? 'update' : 'create');
                apiFormData.append('json', JSON.stringify(formData));
                
                const response = await fetch('../../api/partnered_schools.php', {
                    method: 'POST',
                    body: apiFormData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification(result.message, 'success');
                    closeModal();
                    loadPartneredSchools();
                } else {
                    showNotification(result.message, 'error');
                    
                    // Show validation errors if any
                    if (result.errors) {
                        Object.keys(result.errors).forEach(field => {
                            const errorEl = document.getElementById(field + 'Error');
                            if (errorEl) {
                                errorEl.textContent = result.errors[field];
                            }
                        });
                    }
                }
            } catch (error) {
                showNotification('An error occurred while saving partnered school', 'error');
                console.error('Error:', error);
            }
        }
        
        // Delete partnered school
        async function deletePartneredSchool(id) {
            if (!confirm('Are you sure you want to delete this partnered school?')) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('operation', 'delete');
                formData.append('json', JSON.stringify({ id: id }));
                
                const response = await fetch('../../api/partnered_schools.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification(result.message, 'success');
                    loadPartneredSchools();
                } else {
                    showNotification(result.message, 'error');
                }
            } catch (error) {
                showNotification('An error occurred while deleting partnered school', 'error');
                console.error('Error:', error);
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
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} mr-3"></i>
                    <span>${message}</span>
                </div>
            `;
            
            container.appendChild(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => {
                    container.removeChild(notification);
                }, 300);
            }, 5000);
        }
    </script>
</body>
</html>
