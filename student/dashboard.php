<?php
// Include configuration
require_once '../config/config.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !(isset($_COOKIE['authToken']) && isset($_COOKIE['userData']))) {
    header('Location: ../login.php');
    exit();
}

// Get user data from session or cookie
$userData = null;
if (isset($_SESSION['user_id'])) {
    // Use session data if available
    $userData = [
        'level' => $_SESSION['user_role'] ?? 'Student',
        'firstname' => $_SESSION['first_name'] ?? 'Student',
        'email' => $_SESSION['email'] ?? '',
        'school_id' => $_SESSION['school_id'] ?? ''
    ];
} else {
    // Use cookie data
    $userData = json_decode($_COOKIE['userData'], true);
}

// Verify user is a student
if ($userData['level'] !== 'Student') {
    header('Location: ../login.php');
    exit();
}

$student_name = $userData['firstname'] ?? 'Student';
$student_email = $userData['email'] ?? '';
$student_school_id = $userData['school_id'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | PHINMA Practicum Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.js"></script>
    <link href="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.css" rel="stylesheet" />
    <style>
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
        
        /* Map container */
        #map {
            height: 400px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
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
        
        /* Loading spinner */
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #004d23;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="min-h-screen bg-gray-100">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="container mx-auto px-4 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Student Dashboard</h1>
                        <p class="text-sm text-gray-600">Welcome back, <?php echo htmlspecialchars($student_name); ?>!</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-600">
                            <i class="fas fa-id-card mr-2"></i>
                            <?php echo htmlspecialchars($student_school_id); ?>
                        </span>
                        <button onclick="logout()" class="text-sm text-red-600 hover:text-red-800">
                            <i class="fas fa-sign-out-alt mr-2"></i>
                            Logout
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="container mx-auto px-4 py-8">
            <!-- Tabs Navigation -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px">
                        <button onclick="switchTab('attendance')" 
                                class="tab-button active px-6 py-3 text-sm font-medium text-green-700 hover:text-green-800 focus:outline-none focus:text-green-800">
                            <i class="fas fa-clock mr-2"></i>
                            Attendance
                        </button>
                        <button onclick="switchTab('journal')" 
                                class="tab-button px-6 py-3 text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700">
                            <i class="fas fa-book mr-2"></i>
                            Journal
                        </button>
                        <button onclick="switchTab('activity-checklist')" 
                                class="tab-button px-6 py-3 text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700">
                            <i class="fas fa-tasks mr-2"></i>
                            Activity Checklist
                        </button>
                    </nav>
                </div>
            </div>

            <!-- Attendance Tab Content -->
            <div id="attendance" class="tab-content active">
                <div id="attendanceContent">
                    <!-- Content will be loaded dynamically based on student info -->
                    <div class="bg-white rounded-lg shadow p-8 text-center">
                        <div class="spinner mx-auto mb-4"></div>
                        <p class="text-gray-600">Loading your information...</p>
                    </div>
                </div>
            </div>

            <!-- Journal Tab Content -->
            <div id="journal" class="tab-content">
                <div class="bg-white rounded-lg shadow p-8 text-center">
                    <i class="fas fa-book text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">Journal</h3>
                    <p class="text-gray-500">This feature is coming soon...</p>
                </div>
            </div>

            <!-- Activity Checklist Tab Content -->
            <div id="activity-checklist" class="tab-content">
                <div class="bg-white rounded-lg shadow p-8 text-center">
                    <i class="fas fa-tasks text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">Activity Checklist</h3>
                    <p class="text-gray-500">This feature is coming soon...</p>
                </div>
            </div>
        </main>
    </div>

    <!-- Notification Container -->
    <div id="notificationContainer"></div>

    <script>
        // Global variables
        let map = null;
        let userMarker = null;
        let schoolMarker = null;
        let geofenceCircle = null;
        let userLocation = null;
        let partneredSchool = null;
        let hasSection = false;
        let canMarkAttendance = false;
        const studentSchoolId = '<?php echo $student_school_id; ?>';

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            loadStudentInfo();
        });

        // Load student information from API
        function loadStudentInfo() {
            fetch('../api/students.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    operation: 'get_student_info',
                    json: JSON.stringify({
                        student_id: studentSchoolId
                    })
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    hasSection = data.data.has_section;
                    partneredSchool = data.data.partnered_school;
                    renderAttendanceContent();
                } else {
                    showError('Failed to load student information');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Error loading student information');
            });
        }

        // Render attendance content based on student info
        function renderAttendanceContent() {
            const attendanceContent = document.getElementById('attendanceContent');
            
            if (!hasSection) {
                attendanceContent.innerHTML = `
                    <div class="bg-white rounded-lg shadow p-8 text-center">
                        <i class="fas fa-exclamation-triangle text-6xl text-yellow-400 mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-700 mb-2">No Section Assigned</h3>
                        <p class="text-gray-500">You haven't been assigned to a section yet. Please contact your coordinator for assistance.</p>
                    </div>
                `;
            } else if (!partneredSchool) {
                attendanceContent.innerHTML = `
                    <div class="bg-white rounded-lg shadow p-8 text-center">
                        <i class="fas fa-school text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-700 mb-2">No Partnered School Assigned</h3>
                        <p class="text-gray-500">Your section doesn't have a partnered school assigned yet. Please contact your coordinator.</p>
                    </div>
                `;
            } else {
                attendanceContent.innerHTML = `
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- School Information -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-school mr-2 text-green-600"></i>
                                Assigned School
                            </h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Section</label>
                                    <p class="text-gray-900">${partneredSchool.section_name || 'N/A'}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">School Name</label>
                                    <p class="text-gray-900">${partneredSchool.school_name || 'N/A'}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Address</label>
                                    <p class="text-gray-900">${partneredSchool.address || 'N/A'}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Geofence Radius</label>
                                    <p class="text-gray-900">${partneredSchool.geofencing_radius || 0} meters</p>
                                </div>
                            </div>
                        </div>

                        <!-- Location Status -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-map-marker-alt mr-2 text-green-600"></i>
                                Location Status
                            </h3>
                            <div id="locationStatus" class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div id="locationSpinner" class="spinner mr-3 hidden"></div>
                                        <span id="locationText" class="text-gray-600">Getting your location...</span>
                                    </div>
                                    <button onclick="refreshLocation()" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                        <i class="fas fa-sync-alt mr-1"></i>
                                        Refresh
                                    </button>
                                </div>
                                <div id="distanceInfo" class="hidden">
                                    <label class="text-sm font-medium text-gray-500">Distance from School</label>
                                    <p id="distanceText" class="text-gray-900">--</p>
                                </div>
                                <div id="networkStatus" class="hidden mt-3">
                                    <label class="text-sm font-medium text-gray-500">Network Status</label>
                                    <p id="networkText" class="text-gray-900">Checking connectivity...</p>
                                </div>
                                <div id="attendanceButton" class="mt-4">
                                    <!-- Attendance button will be inserted here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Map -->
                    <div class="bg-white rounded-lg shadow p-6 mt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-map mr-2 text-green-600"></i>
                            Attendance Location
                        </h3>
                        <div id="map"></div>
                        <p class="text-sm text-gray-500 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            You must be within the geofence radius to mark your attendance.
                        </p>
                    </div>
                `;
                
                // Initialize map and location services
                initializeMap();
                getUserLocation();
            }
        }

        // Show error message
        function showError(message) {
            const attendanceContent = document.getElementById('attendanceContent');
            attendanceContent.innerHTML = `
                <div class="bg-white rounded-lg shadow p-8 text-center">
                    <i class="fas fa-exclamation-triangle text-6xl text-red-400 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">Error</h3>
                    <p class="text-gray-500">${message}</p>
                </div>
            `;
        }

        // Tab switching function
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
                button.classList.remove('text-green-700');
                button.classList.add('text-gray-500');
            });
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked tab button
            event.target.classList.add('active');
            event.target.classList.remove('text-gray-500');
            event.target.classList.add('text-green-700');
        }

        // Initialize map
        function initializeMap() {
            if (!partneredSchool || !partneredSchool.latitude || !partneredSchool.longitude) {
                return;
            }

            // Initialize map with OpenStreetMap style (same as system.php)
            map = new maplibregl.Map({
                container: 'map',
                style: {
                    'version': 8,
                    'sources': {
                        'osm': {
                            'type': 'raster',
                            'tiles': ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
                            'tileSize': 256,
                            'attribution': '© OpenStreetMap contributors'
                        }
                    },
                    'layers': [
                        {
                            'id': 'osm',
                            'type': 'raster',
                            'source': 'osm'
                        }
                    ]
                },
                center: [partneredSchool.longitude, partneredSchool.latitude],
                zoom: 15
            });

            // Add navigation controls
            map.addControl(new maplibregl.NavigationControl());

            map.on('load', function() {
                // Add school marker
                schoolMarker = new maplibregl.Marker({
                    color: '#10b981',
                    scale: 1.2
                })
                .setLngLat([partneredSchool.longitude, partneredSchool.latitude])
                .addTo(map);

                // Add geofence circle
                addGeofenceCircle();

                // Add school popup
                const popup = new maplibregl.Popup({
                    offset: 25
                }).setHTML(`
                    <div class="p-2">
                        <h4 class="font-semibold">${partneredSchool.school_name}</h4>
                        <p class="text-sm text-gray-600">Geofence: ${partneredSchool.geofencing_radius}m</p>
                    </div>
                `);
                
                schoolMarker.setPopup(popup);
            });
        }

        // Add geofence circle to map
        function addGeofenceCircle() {
            if (!map || !partneredSchool) return;

            // Ensure latitude and longitude are numbers
            const schoolLat = parseFloat(partneredSchool.latitude);
            const schoolLng = parseFloat(partneredSchool.longitude);
            const radius = parseFloat(partneredSchool.geofencing_radius) || 80; // Default to 80m if not set

            // Validate radius and coordinates
            if (isNaN(radius) || radius <= 0) {
                console.error('Invalid geofence radius:', partneredSchool.geofencing_radius);
                return;
            }
            if (isNaN(schoolLat) || isNaN(schoolLng)) {
                console.error('Invalid school coordinates:', partneredSchool.latitude, partneredSchool.longitude);
                return;
            }

            // Create circle points
            const points = [];
            const numPoints = 64;
            for (let i = 0; i < numPoints; i++) {
                const angle = (i / numPoints) * 2 * Math.PI;
                // Ensure all arithmetic operations are performed on numbers
                const lat = schoolLat + (radius / 111320) * Math.cos(angle);
                const lng = schoolLng + (radius / (111320 * Math.cos(schoolLat * Math.PI / 180))) * Math.sin(angle);
                points.push([lng, lat]);
            }
            points.push(points[0]); // Close the circle

            console.log('Circle points sample:', points.slice(0, 3));

            // Remove existing geofence layers if they exist
            if (map.getLayer('geofence')) {
                map.removeLayer('geofence');
                map.removeSource('geofence');
            }
            if (map.getLayer('geofence-border')) {
                map.removeLayer('geofence-border');
                map.removeSource('geofence-border');
            }

            // Add geofence source
            map.addSource('geofence', {
                type: 'geojson',
                data: {
                    type: 'Feature',
                    geometry: {
                        type: 'Polygon',
                        coordinates: [points]
                    }
                }
            });

            // Add geofence fill layer (more visible)
            map.addLayer({
                id: 'geofence',
                type: 'fill',
                source: 'geofence',
                paint: {
                    'fill-color': '#10b981',
                    'fill-opacity': 0.3  // Increased opacity for better visibility
                }
            });

            // Add geofence border (more prominent)
            map.addLayer({
                id: 'geofence-border',
                type: 'line',
                source: 'geofence',
                paint: {
                    'line-color': '#059669',
                    'line-width': 3,  // Thicker border
                    'line-opacity': 1   // Full opacity
                }
            });

            console.log('Geofence circle added successfully');
            console.log('Final circle bounds:', {
                firstPoint: points[0],
                lastPoint: points[points.length - 2]
            });
        }

        // Get user location with progressive fallback
        function getUserLocation() {
            const locationSpinner = document.getElementById('locationSpinner');
            const locationText = document.getElementById('locationText');
            const networkStatus = document.getElementById('networkStatus');
            const networkText = document.getElementById('networkText');
            
            locationSpinner.classList.remove('hidden');
            locationText.textContent = 'Checking network connectivity...';
            networkStatus.classList.remove('hidden');

            if (!navigator.geolocation) {
                locationSpinner.classList.add('hidden');
                locationText.textContent = 'Geolocation is not supported by your browser';
                networkStatus.classList.add('hidden');
                return;
            }

            // Check network connectivity first
            checkNetworkConnectivity()
                .then(isConnected => {
                    if (isConnected) {
                        networkText.textContent = 'Connected';
                        networkText.classList.add('text-green-600');
                        locationText.textContent = 'Getting your location...';
                        // Try high accuracy first
                        tryHighAccuracyLocation();
                    } else {
                        networkText.textContent = 'No internet connection';
                        networkText.classList.add('text-red-600');
                        locationText.textContent = 'Network connectivity issue. Please check your internet connection.';
                        locationText.classList.add('text-red-600');
                        locationSpinner.classList.add('hidden');
                    }
                })
                .catch(error => {
                    console.error('Network check failed:', error);
                    networkText.textContent = 'Unable to check network';
                    networkText.classList.add('text-yellow-600');
                    // Proceed with location attempt anyway
                    locationText.textContent = 'Getting your location...';
                    tryHighAccuracyLocation();
                });
        }

        // Check network connectivity
        function checkNetworkConnectivity() {
            return new Promise((resolve) => {
                // Check navigator.onLine first (quick check)
                if (!navigator.onLine) {
                    resolve(false);
                    return;
                }

                // Try to fetch a small resource to verify actual connectivity
                const startTime = Date.now();
                fetch('../api/connection.php', {
                    method: 'HEAD',
                    cache: 'no-cache',
                    timeout: 5000
                })
                .then(response => {
                    const responseTime = Date.now() - startTime;
                    if (response.ok) {
                        // Update network status with response time
                        const networkText = document.getElementById('networkText');
                        if (networkText) {
                            networkText.textContent = `Connected (${responseTime}ms)`;
                            networkText.classList.add('text-green-600');
                        }
                        resolve(true);
                    } else {
                        resolve(false);
                    }
                })
                .catch(() => {
                    resolve(false);
                });

                // Fallback timeout
                setTimeout(() => resolve(false), 5000);
            });
        }

        // Try high accuracy location first
        function tryHighAccuracyLocation() {
            const options = {
                enableHighAccuracy: true,
                timeout: 15000,           // 15 seconds for high accuracy
                maximumAge: 60000         // Allow 1 minute cached data
            };

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    handleLocationSuccess(position);
                },
                function(error) {
                    if (error.code === error.TIMEOUT) {
                        // Fallback to lower accuracy
                        tryLowAccuracyLocation();
                    } else {
                        handleLocationError(error);
                    }
                },
                options
            );
        }

        // Fallback to lower accuracy location
        function tryLowAccuracyLocation() {
            const locationText = document.getElementById('locationText');
            locationText.textContent = 'Trying alternative location method...';

            const options = {
                enableHighAccuracy: false,  // Use less accurate but faster methods
                timeout: 10000,             // 10 seconds for low accuracy
                maximumAge: 300000          // Allow 5 minutes cached data
            };

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    handleLocationSuccess(position);
                },
                function(error) {
                    handleLocationError(error);
                },
                options
            );
        }

        // Handle successful location retrieval
        function handleLocationSuccess(position) {
            const locationSpinner = document.getElementById('locationSpinner');
            const locationText = document.getElementById('locationText');
            
            locationSpinner.classList.add('hidden');
            userLocation = {
                lat: position.coords.latitude,
                lng: position.coords.longitude,
                accuracy: position.coords.accuracy,
                altitude: position.coords.altitude,
                altitudeAccuracy: position.coords.altitudeAccuracy,
                heading: position.coords.heading,
                speed: position.coords.speed
            };
            
            // Show accuracy info
            const accuracyInMeters = Math.round(position.coords.accuracy);
            const accuracyLevel = position.coords.accuracy < 50 ? 'High' : position.coords.accuracy < 100 ? 'Medium' : 'Low';
            locationText.textContent = `Location found (${accuracyLevel} accuracy: ±${accuracyInMeters}m)`;
            
            updateUserLocation();
            checkGeofence();
            
            // Start watching position for real-time updates
            startLocationWatching();
        }

        // Handle location errors
        function handleLocationError(error) {
            const locationSpinner = document.getElementById('locationSpinner');
            const locationText = document.getElementById('locationText');
            const networkStatus = document.getElementById('networkStatus');
            
            locationSpinner.classList.add('hidden');
            let message = 'Unable to get your location';
            let suggestion = '';
            
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    message = 'Location access denied';
                    suggestion = 'Please allow location access in your browser settings and refresh.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    message = 'Location information unavailable';
                    suggestion = 'Try moving to an area with better GPS signal or check your device location services.';
                    break;
                case error.TIMEOUT:
                    message = 'Location request timed out';
                    suggestion = 'Try again or check your internet connection and GPS settings.';
                    break;
            }
            
            locationText.innerHTML = `${message}. <span class="text-xs">${suggestion}</span>`;
            locationText.classList.add('text-red-600');
            
            // Keep network status visible for troubleshooting
            if (networkStatus) {
                networkStatus.classList.remove('hidden');
            }
        }

        // Start watching location for real-time updates
        function startLocationWatching() {
            if (!navigator.geolocation) return;

            const watchOptions = {
                enableHighAccuracy: true,
                timeout: 5000,
                maximumAge: 30000  // Allow 30 seconds cached data for watching
            };

            navigator.geolocation.watchPosition(
                function(position) {
                    userLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                        accuracy: position.coords.accuracy
                    };
                    
                    updateUserLocation();
                    checkGeofence();
                },
                function(error) {
                    console.error('Watch position error:', error);
                },
                watchOptions
            );
        }

        // Update user location on map
        function updateUserLocation() {
            if (!map || !userLocation) return;

            // Remove existing user marker
            if (userMarker) {
                userMarker.remove();
            }

            // Add user marker with accuracy circle
            userMarker = new maplibregl.Marker({
                color: '#3b82f6',
                scale: 1
            })
            .setLngLat([userLocation.lng, userLocation.lat])
            .addTo(map);

            // Add accuracy circle if accuracy is available
            if (userLocation.accuracy && map.getLayer('user-accuracy')) {
                map.removeLayer('user-accuracy');
                map.removeSource('user-accuracy');
            }

            if (userLocation.accuracy) {
                // Create accuracy circle
                const accuracyCircle = createAccuracyCircle(userLocation.lat, userLocation.lng, userLocation.accuracy);
                
                map.addSource('user-accuracy', {
                    type: 'geojson',
                    data: {
                        type: 'Feature',
                        geometry: {
                            type: 'Polygon',
                            coordinates: [accuracyCircle]
                        }
                    }
                });

                map.addLayer({
                    id: 'user-accuracy',
                    type: 'fill',
                    source: 'user-accuracy',
                    paint: {
                        'fill-color': '#3b82f6',
                        'fill-opacity': 0.1
                    }
                });

                map.addLayer({
                    id: 'user-accuracy-border',
                    type: 'line',
                    source: 'user-accuracy',
                    paint: {
                        'line-color': '#3b82f6',
                        'line-width': 1,
                        'line-opacity': 0.3
                    }
                });
            }

            // Center map on user and school
            const bounds = new maplibregl.LngLatBounds();
            bounds.extend([partneredSchool.longitude, partneredSchool.latitude]);
            bounds.extend([userLocation.lng, userLocation.lat]);
            map.fitBounds(bounds, { padding: 100 });
        }

        // Create accuracy circle points
        function createAccuracyCircle(lat, lng, radiusInMeters) {
            const points = [];
            const numPoints = 32;
            
            for (let i = 0; i <= numPoints; i++) {
                const angle = (i / numPoints) * 2 * Math.PI;
                const latOffset = (radiusInMeters / 111320) * Math.cos(angle);
                const lngOffset = (radiusInMeters / (111320 * Math.cos(lat * Math.PI / 180))) * Math.sin(angle);
                points.push([lng + lngOffset, lat + latOffset]);
            }
            
            return points;
        }

        // Manual location refresh
        function refreshLocation() {
            const locationText = document.getElementById('locationText');
            locationText.textContent = 'Refreshing location...';
            getUserLocation();
        }

        // Check if user is within geofence
        function checkGeofence() {
            if (!userLocation || !partneredSchool) return;

            const distance = calculateDistance(
                userLocation.lat,
                userLocation.lng,
                partneredSchool.latitude,
                partneredSchool.longitude
            );

            const locationText = document.getElementById('locationText');
            const distanceInfo = document.getElementById('distanceInfo');
            const distanceText = document.getElementById('distanceText');
            const attendanceButton = document.getElementById('attendanceButton');

            distanceInfo.classList.remove('hidden');
            distanceText.textContent = `${distance.toFixed(2)} meters`;

            canMarkAttendance = distance <= partneredSchool.geofencing_radius;

            if (canMarkAttendance) {
                locationText.textContent = 'You are within the attendance area';
                locationText.classList.remove('text-red-600');
                locationText.classList.add('text-green-600');
                
                attendanceButton.innerHTML = `
                    <button onclick="markAttendance()" class="w-full px-4 py-3 bg-green-600 text-white rounded-md hover:bg-green-700 font-medium">
                        <i class="fas fa-check-circle mr-2"></i>
                        Mark Attendance
                    </button>
                `;
            } else {
                locationText.textContent = 'You are outside the attendance area';
                locationText.classList.remove('text-green-600');
                locationText.classList.add('text-red-600');
                
                attendanceButton.innerHTML = `
                    <button disabled class="w-full px-4 py-3 bg-gray-400 text-white rounded-md cursor-not-allowed font-medium">
                        <i class="fas fa-times-circle mr-2"></i>
                        Outside Attendance Area (${(partneredSchool.geofencing_radius - distance).toFixed(0)}m away)
                    </button>
                `;
            }
        }

        // Calculate distance between two points
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371e3; // Earth's radius in meters
            const φ1 = lat1 * Math.PI / 180;
            const φ2 = lat2 * Math.PI / 180;
            const Δφ = (lat2 - lat1) * Math.PI / 180;
            const Δλ = (lon2 - lon1) * Math.PI / 180;

            const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
                    Math.cos(φ1) * Math.cos(φ2) *
                    Math.sin(Δλ/2) * Math.sin(Δλ/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

            return R * c; // Distance in meters
        }

        // Mark attendance
        function markAttendance() {
            if (!canMarkAttendance) {
                showNotification('You must be within the attendance area to mark attendance', 'error');
                return;
            }

            // Show loading state
            const attendanceButton = document.getElementById('attendanceButton');
            attendanceButton.innerHTML = `
                <button disabled class="w-full px-4 py-3 bg-yellow-500 text-white rounded-md cursor-not-allowed font-medium">
                    <i class="fas fa-spinner fa-spin mr-2"></i>
                    Marking Attendance...
                </button>
            `;

            // Send attendance data to server
            fetch('../api/students.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    operation: 'mark_attendance',
                    json: JSON.stringify({
                        student_id: '<?php echo $student_school_id; ?>',
                        latitude: userLocation.lat,
                        longitude: userLocation.lng
                    })
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Attendance marked successfully!', 'success');
                    attendanceButton.innerHTML = `
                        <button disabled class="w-full px-4 py-3 bg-green-600 text-white rounded-md cursor-not-allowed font-medium">
                            <i class="fas fa-check mr-2"></i>
                            Attendance Marked
                        </button>
                    `;
                } else {
                    showNotification(data.message || 'Failed to mark attendance', 'error');
                    // Reset button
                    checkGeofence();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error marking attendance. Please try again.', 'error');
                // Reset button
                checkGeofence();
            });
        }

        // Logout function
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                fetch('../api/auth.php', {
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
    </script>
</body>
</html>