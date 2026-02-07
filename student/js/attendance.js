// Global variables for attendance functionality
let map = null;
let userMarker = null;
let schoolMarker = null;
let geofenceCircle = null;
let userLocation = null;
let partneredSchool = null;
let hasSection = false;
let canMarkAttendance = false;
let currentStudentId = null;

// Helper function to convert 24-hour time to 12-hour format
function formatTimeTo12Hour(time24) {
    if (!time24) return '';

    const [hour, minute, second] = time24.split(':');
    const date = new Date();
    date.setHours(parseInt(hour), parseInt(minute), parseInt(second));

    let hours = date.getHours();
    let minutes = date.getMinutes();
    let seconds = date.getSeconds();
    const ampm = hours >= 12 ? 'PM' : 'AM';

    hours = hours % 12;
    hours = hours ? hours : 12; // the hour '0' should be '12'
    minutes = minutes < 10 ? '0' + minutes : minutes;
    seconds = seconds < 10 ? '0' + seconds : seconds;

    return `${hours}:${minutes}:${seconds} ${ampm}`;
}

// Helper function to get today's date in readable format
function getTodayDate() {
    const today = new Date();
    const options = {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    };
    return today.toLocaleDateString('en-US', options);
}

// Initialize attendance functionality
function initializeAttendance(studentId) {
    currentStudentId = studentId;
    loadStudentInfo(studentId);
}

// Load student information from API
function loadStudentInfo(studentSchoolId) {
    fetch(window.APP_CONFIG.API_BASE_URL + 'students.php', {
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
        const todayDate = getTodayDate();
        attendanceContent.innerHTML = `
            <div class="mb-6">
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <h2 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-calendar-day mr-2 text-green-600"></i>
                        ${todayDate}
                    </h2>
                </div>
            </div>
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

        // Check attendance status to show correct button
        checkAttendanceStatus();
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

    map.on('load', function () {
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
        function (position) {
            handleLocationSuccess(position);
        },
        function (error) {
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
        function (position) {
            handleLocationSuccess(position);
        },
        function (error) {
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

    // Check attendance status to show correct button
    checkAttendanceStatus();

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

    switch (error.code) {
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
        function (position) {
            userLocation = {
                lat: position.coords.latitude,
                lng: position.coords.longitude,
                accuracy: position.coords.accuracy
            };

            updateUserLocation();
            checkGeofence();
        },
        function (error) {
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

        // Check attendance status to determine which button to show
        checkAttendanceStatus();
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

    const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
        Math.cos(φ1) * Math.cos(φ2) *
        Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    return R * c; // Distance in meters
}

// Check attendance status for today
function checkAttendanceStatus() {
    fetch(window.APP_CONFIG.API_BASE_URL + 'students.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            operation: 'check_attendance_status',
            json: JSON.stringify({
                student_id: currentStudentId
            })
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateAttendanceButton(data.data);
            } else {
                console.error('Failed to check attendance status:', data.message);
                // Fallback to time in button
                showTimeInButton();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Fallback to time in button
            showTimeInButton();
        });
}

// Update attendance button based on status
function updateAttendanceButton(status) {
    const attendanceButton = document.getElementById('attendanceButton');

    if (status.has_time_in && !status.has_time_out) {
        // Show time out button
        showTimeOutButton(status.time_in);
    } else if (status.has_time_in && status.has_time_out) {
        // Show completed attendance
        showCompletedAttendance(status.time_in, status.time_out, status.hours_rendered);
    } else {
        // Show time in button
        showTimeInButton();
    }
}

// Show time in button
function showTimeInButton() {
    const attendanceButton = document.getElementById('attendanceButton');
    attendanceButton.innerHTML = `
        <button onclick="markAttendance('${currentStudentId}')" class="w-full px-4 py-3 bg-green-600 text-white rounded-md hover:bg-green-700 font-medium">
            <i class="fas fa-sign-in-alt mr-2"></i>
            Time In
        </button>
    `;
}

// Show time out button
function showTimeOutButton(timeIn) {
    if (!timeIn) {
        console.error('showTimeOutButton called with empty timeIn');
        checkAttendanceStatus();
        return;
    }
    const attendanceButton = document.getElementById('attendanceButton');
    const formattedTimeIn = formatTimeTo12Hour(timeIn);

    // Calculate remaining time until 8 hours have passed
    const timeInDate = new Date();
    const [hours, minutes, seconds] = timeIn.split(':');
    timeInDate.setHours(parseInt(hours), parseInt(minutes), parseInt(seconds), 0);

    const currentTime = new Date();
    const hoursDiff = (currentTime - timeInDate) / (1000 * 60 * 60);

    let buttonHtml = `
        <div class="mb-2 p-2 bg-green-50 border border-green-200 rounded text-sm">
            <i class="fas fa-clock text-green-600 mr-1"></i>
            Time In: ${formattedTimeIn}
        </div>
    `;

    if (hoursDiff < 8) {
        // Calculate exact time out time (8 hours after time in)
        const timeOutTime = new Date(timeInDate);
        timeOutTime.setHours(timeOutTime.getHours() + 8);
        const formattedTimeOut = formatTimeTo12Hour(timeOutTime.toTimeString().split(' ')[0]);

        // Calculate remaining duration
        const remainingTotalMinutes = Math.ceil((8 - hoursDiff) * 60);
        const remainingHours = Math.floor(remainingTotalMinutes / 60);
        const remainingMinutes = remainingTotalMinutes % 60;
        let durationText = '';
        if (remainingHours > 0) {
            durationText = `~${remainingHours}h ${remainingMinutes}m`;
        } else {
            durationText = `~${remainingMinutes}m`;
        }

        let timeMessage = `You can time out at ${formattedTimeOut}. `;

        buttonHtml += `
            <div class="mb-2 p-2 bg-yellow-50 border border-yellow-200 rounded text-sm text-yellow-800">
                <div class="flex justify-between items-center">
                    <div class="flex items-center">
                        <i class="fas fa-hourglass-half mr-1"></i>
                        <span>${timeMessage}</span>
                    </div>
                    <div class="text-xs bg-yellow-100 px-2 py-1 rounded text-yellow-700 font-medium">
                        ${durationText}
                    </div>
                </div>
            </div>
            <button disabled class="w-full px-4 py-3 bg-gray-400 text-white rounded-md cursor-not-allowed font-medium">
                <i class="fas fa-sign-out-alt mr-2"></i>
                Time Out (Not Available Yet)
            </button>
        `;
    } else {
        buttonHtml += `
            <button onclick="markTimeOut('${currentStudentId}')" class="w-full px-4 py-3 bg-orange-600 text-white rounded-md hover:bg-orange-700 font-medium">
                <i class="fas fa-sign-out-alt mr-2"></i>
                Time Out
            </button>
        `;
    }

    attendanceButton.innerHTML = buttonHtml;
}

// Show completed attendance
function showCompletedAttendance(timeIn, timeOut, hoursRendered) {
    const attendanceButton = document.getElementById('attendanceButton');
    const formattedTimeIn = formatTimeTo12Hour(timeIn);
    const formattedTimeOut = formatTimeTo12Hour(timeOut);
    const displayHours = hoursRendered ? parseFloat(hoursRendered).toFixed(2) : '--';

    attendanceButton.innerHTML = `
        <div class="p-3 bg-gray-50 border border-gray-200 rounded">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-600">Today's Attendance</span>
                <span class="text-xs px-2 py-1 bg-green-100 text-green-800 rounded-full">Complete</span>
            </div>
            <div class="grid grid-cols-2 gap-2 text-sm mb-2">
                <div>
                    <span class="text-gray-500">Time In:</span>
                    <div class="font-medium">${formattedTimeIn}</div>
                </div>
                <div>
                    <span class="text-gray-500">Time Out:</span>
                    <div class="font-medium">${formattedTimeOut}</div>
                </div>
            </div>
            <div class="pt-2 border-t border-gray-200">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-500">Rendered Hours:</span>
                    <span class="text-sm font-bold text-green-600">${displayHours} hrs</span>
                </div>
            </div>
        </div>
    `;
}

// Mark time out
function markTimeOut(studentSchoolId) {
    if (!canMarkAttendance) {
        showNotification('You must be within the attendance area to mark time out', 'error');
        return;
    }

    // Check if 8 hours have passed since time in
    fetch(window.APP_CONFIG.API_BASE_URL + 'students.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            operation: 'check_attendance_status',
            json: JSON.stringify({
                student_id: studentSchoolId
            })
        })
    })
        .then(response => response.json())
        .then(statusData => {
            if (statusData.success && statusData.data.has_time_in && !statusData.data.has_time_out) {
                if (!statusData.data.time_in) {
                    proceedWithTimeOut(studentSchoolId);
                    return;
                }
                const timeIn = new Date();
                const [hours, minutes, seconds] = statusData.data.time_in.split(':');
                timeIn.setHours(parseInt(hours), parseInt(minutes), parseInt(seconds), 0);

                const currentTime = new Date();
                const hoursDiff = (currentTime - timeIn) / (1000 * 60 * 60);

                if (hoursDiff < 8) {
                    // Calculate exact time out time (8 hours after time in)
                    const timeOutTime = new Date(timeIn);
                    timeOutTime.setHours(timeOutTime.getHours() + 8);
                    const formattedTimeOut = formatTimeTo12Hour(timeOutTime.toTimeString().split(' ')[0]);

                    let timeMessage = `You can time out at ${formattedTimeOut}. Please wait until then.`;

                    showNotification(timeMessage, 'warning');
                    return;
                }
            }

            // If validation passes, proceed with time out
            proceedWithTimeOut(studentSchoolId);
        })
        .catch(error => {
            console.error('Error checking attendance status:', error);
            // If status check fails, still proceed with time out (server will validate)
            proceedWithTimeOut(studentSchoolId);
        });
}

// Proceed with time out after validation
function proceedWithTimeOut(studentSchoolId) {
    // Show loading state
    const attendanceButton = document.getElementById('attendanceButton');
    attendanceButton.innerHTML = `
        <button disabled class="w-full px-4 py-3 bg-yellow-500 text-white rounded-md cursor-not-allowed font-medium">
            <i class="fas fa-spinner fa-spin mr-2"></i>
            Marking Time Out...
        </button>
    `;

    // Send time out data to server
    fetch(window.APP_CONFIG.API_BASE_URL + 'students.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            operation: 'mark_timeout',
            json: JSON.stringify({
                student_id: studentSchoolId,
                latitude: userLocation.lat,
                longitude: userLocation.lng
            })
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Time out marked successfully!', 'success');
                showCompletedAttendance(data.data.time_in, data.data.time_out, data.data.hours_rendered);
            } else {
                showNotification(data.message || 'Failed to mark time out', 'error');
                // Reset button
                checkAttendanceStatus();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error marking time out. Please try again.', 'error');
            // Reset button
            checkAttendanceStatus();
        });
}

// Mark attendance
function markAttendance(studentSchoolId) {
    if (!canMarkAttendance) {
        showNotification('You must be within the attendance area to mark attendance', 'error');
        return;
    }

    // Show loading state
    const attendanceButton = document.getElementById('attendanceButton');
    attendanceButton.innerHTML = `
        <button disabled class="w-full px-4 py-3 bg-yellow-500 text-white rounded-md cursor-not-allowed font-medium">
            <i class="fas fa-spinner fa-spin mr-2"></i>
            Marking Time In...
        </button>
    `;

    // Send attendance data to server
    fetch(window.APP_CONFIG.API_BASE_URL + 'students.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            operation: 'mark_attendance',
            json: JSON.stringify({
                student_id: studentSchoolId,
                latitude: userLocation.lat,
                longitude: userLocation.lng
            })
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Time in marked successfully!', 'success');
                showTimeOutButton(data.data.time);
            } else {
                showNotification(data.message || 'Failed to mark time in', 'error');
                // Reset button
                checkAttendanceStatus();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error marking time in. Please try again.', 'error');
            // Reset button
            checkAttendanceStatus();
        });
}