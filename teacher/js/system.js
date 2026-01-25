// Global variables
let currentEditingSchool = null;
let currentEditingSection = null;
let map = null;
let marker = null;
let geofenceCircle = null;
let allPartneredSchools = [];
let allSections = [];
let allAcademicSessions = [];
let currentActiveSession = null;

// Pagination states
const ITEMS_PER_PAGE = 10;
let partneredCurrentPage = 1;
let sectionsCurrentPage = 1;
let academicCurrentPage = 1;

// Global filtered arrays for pagination
let filteredSchools = [];
let filteredSectionsData = [];
let filteredAcademicData = [];

// Initialize page
document.addEventListener('DOMContentLoaded', function () {
    loadPartneredSchools();
    loadSections();
    initializeMap();

    // Auto-search functionality for partnered schools
    document.getElementById('searchInput').addEventListener('input', () => {
        partneredCurrentPage = 1;
        filterPartneredSchools();
    });

    // Auto-search functionality for sections
    document.getElementById('sectionsSearchInput').addEventListener('input', () => {
        sectionsCurrentPage = 1;
        filterSections();
    });

    // Address input geocoding
    let addressTimeout;
    document.getElementById('address').addEventListener('input', function () {
        clearTimeout(addressTimeout);
        const address = this.value.trim();

        if (address.length > 5) {
            addressTimeout = setTimeout(() => {
                geocodeAddress(address);
            }, 1000); // Wait 1 second after user stops typing
        }
    });

    // Form submission for partnered schools
    document.getElementById('partneredSchoolForm').addEventListener('submit', function (e) {
        e.preventDefault(); // Prevent browser default validation
        savePartneredSchool();
    });

    // Prevent browser default validation messages
    document.getElementById('partneredSchoolForm').addEventListener('invalid', function(e) {
        e.preventDefault(); // Prevent default validation bubble
    }, true);

    // Form submission for sections
    document.getElementById('sectionForm').addEventListener('submit', function (e) {
        e.preventDefault();
        saveSection();
    });

    // Mobile sidebar
    const mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
    const mobileSidebar = document.getElementById('mobileSidebar');
    const mobileSidebarOverlay = document.getElementById('mobileSidebarOverlay');
    const closeMobileSidebar = document.getElementById('closeMobileSidebar');

    mobileSidebarToggle.addEventListener('click', function () {
        mobileSidebar.classList.remove('hidden');
    });

    function closeMobileSidebarFunc() {
        mobileSidebar.classList.add('hidden');
    }

    closeMobileSidebar.addEventListener('click', closeMobileSidebarFunc);
    mobileSidebarOverlay.addEventListener('click', closeMobileSidebarFunc);
});

// Dropdown functions
function toggleDropdown(schoolId) {
    // Close all other dropdowns
    document.querySelectorAll('.dropdown-content').forEach(dropdown => {
        if (dropdown.id !== `dropdown-content-${schoolId}`) {
            dropdown.classList.remove('show');
        }
    });

    // Toggle current dropdown
    const currentDropdown = document.getElementById(`dropdown-content-${schoolId}`);
    currentDropdown.classList.toggle('show');
}

function closeDropdown(schoolId) {
    const dropdown = document.getElementById(`dropdown-content-${schoolId}`);
    if (dropdown) {
        dropdown.classList.remove('show');
    }
}

// Close dropdowns when clicking outside
document.addEventListener('click', function (event) {
    if (!event.target.matches('.dropdown button') && !event.target.closest('.dropdown-content')) {
        document.querySelectorAll('.dropdown-content').forEach(dropdown => {
            dropdown.classList.remove('show');
        });
    }
});

// Update geofence circle
function updateGeofenceCircle(lat, lng) {
    if (map.getLayer('geofence-circle')) {
        const radius = parseInt(document.getElementById('geofencingRadius').value) || 80;

        // Remove existing geofence layers
        map.removeLayer('geofence-circle');
        map.removeLayer('geofence-circle-outline');
        map.removeSource('geofence-circle');

        // Create new geofence circle
        const circleGeoJSON = createCircleGeoJSON(lng, lat, radius);

        map.addSource('geofence-circle', {
            'type': 'geojson',
            'data': circleGeoJSON
        });

        map.addLayer({
            'id': 'geofence-circle',
            'type': 'fill',
            'source': 'geofence-circle',
            'layout': {},
            'paint': {
                'fill-color': '#004d23',
                'fill-opacity': 0.2
            }
        });

        map.addLayer({
            'id': 'geofence-circle-outline',
            'type': 'line',
            'source': 'geofence-circle',
            'layout': {},
            'paint': {
                'line-color': '#004d23',
                'line-width': 2
            }
        });
    }
}

// Tab switching
function switchTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });

    // Remove active class from all desktop tab buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active', 'text-green-700');
        button.classList.add('text-gray-500');
    });

    // Remove active class from all mobile tab buttons
    document.querySelectorAll('.mobile-tab-button').forEach(button => {
        button.classList.remove('active', 'text-green-700', 'border-green-700');
        button.classList.add('text-gray-500', 'border-transparent');
    });

    // Show selected tab content
    document.getElementById(tabName).classList.add('active');

    // Add active class to clicked tab button (desktop or mobile)
    if (event.target) {
        event.target.classList.add('active', 'text-green-700');
        event.target.classList.remove('text-gray-500');
        
        // For mobile tabs, also update border
        if (event.target.classList.contains('mobile-tab-button')) {
            event.target.classList.add('border-green-700');
            event.target.classList.remove('border-transparent');
        }
    }
    
    // Initialize academic sessions if switching to system-settings
    if (tabName === 'system-settings') {
        setTimeout(() => {
            loadAcademicSessions();
        }, 100);
    }
    
    // Close mobile dropdown if open
    closeMobileTabDropdown();
}

// Mobile tab dropdown functions
function toggleMobileTabDropdown() {
    const dropdown = document.getElementById('mobileTabDropdown');
    dropdown.classList.toggle('hidden');
}

function closeMobileTabDropdown() {
    const dropdown = document.getElementById('mobileTabDropdown');
    if (!dropdown.classList.contains('hidden')) {
        dropdown.classList.add('hidden');
    }
}

function switchTabFromDropdown(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });

    // Remove active class from all desktop tab buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active', 'text-green-700');
        button.classList.add('text-gray-500');
    });

    // Remove active class from all mobile tab buttons
    document.querySelectorAll('.mobile-tab-button').forEach(button => {
        button.classList.remove('active', 'text-green-700', 'border-green-700');
        button.classList.add('text-gray-500', 'border-transparent');
    });

    // Show selected tab content
    document.getElementById(tabName).classList.add('active');
    
    // For dropdown tabs, we don't highlight a mobile tab button since it's in dropdown
    // But we do highlight the corresponding desktop tab if it exists
    const desktopTab = document.querySelector(`.tab-button[onclick="switchTab('${tabName}')"]`);
    if (desktopTab) {
        desktopTab.classList.add('active', 'text-green-700');
        desktopTab.classList.remove('text-gray-500');
    }
    
    // Initialize academic sessions if switching to system-settings
    if (tabName === 'system-settings') {
        setTimeout(() => {
            loadAcademicSessions();
        }, 100);
    }
    
    // Close dropdown
    closeMobileTabDropdown();
}

// Close mobile dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('mobileTabDropdown');
    const dropdownBtn = document.querySelector('.mobile-tab-dropdown-btn');
    
    if (dropdown && !dropdown.classList.contains('hidden') && 
        !dropdown.contains(event.target) && 
        !dropdownBtn.contains(event.target)) {
        closeMobileTabDropdown();
    }
});

// Show loading state on address field
function showAddressLoading(message = 'Detecting address...') {
    const addressField = document.getElementById('address');
    addressField.value = message;
    addressField.classList.add('animate-pulse', 'bg-yellow-50', 'border-yellow-300');
    addressField.classList.remove('bg-gray-50');
}

// Hide loading state on address field
function hideAddressLoading() {
    const addressField = document.getElementById('address');
    addressField.classList.remove('animate-pulse', 'bg-yellow-50', 'border-yellow-300');
    addressField.classList.add('bg-gray-50');
}

// Search address function with school search capability
async function searchAddress() {
    const address = document.getElementById('mapSearchInput').value.trim();
    if (!address) {
        showNotification('Please enter a school name or address to search', 'error');
        return;
    }

    showAddressLoading('Searching location...');
    showNotification('Searching for school/address...', 'info');

    try {
        // Determine if this is likely a school search
        const schoolKeywords = ['school', 'university', 'college', 'academy', 'institute', 'elementary', 'high school', 'primary', 'secondary'];
        const isLikelySchool = schoolKeywords.some(keyword => 
            address.toLowerCase().includes(keyword)
        ) || address.length < 30; // Short queries are likely school names

        // Use school search for likely school queries or general address search otherwise
        const searchType = isLikelySchool ? 'school' : 'address';
        const response = await fetch(window.APP_CONFIG.API_BASE_URL + `geocode.php?address=${encodeURIComponent(address)}&type=${searchType}`);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            const lat = parseFloat(data.lat);
            const lng = parseFloat(data.lng);

            if (!isNaN(lat) && !isNaN(lng)) {
                setMapLocation(lat, lng);
                
                // Show appropriate success message
                if (data.is_school || searchType === 'school') {
                    showNotification('School found and located on map', 'success');
                    
                    // Auto-populate school name field if it's empty or if this is a school search
                    const schoolNameField = document.getElementById('schoolName');
                    const currentSchoolName = schoolNameField.value.trim();
                    
                    if (!currentSchoolName || searchType === 'school') {
                        // Extract school name from display_name (remove address details)
                        let schoolName = data.display_name;
                        if (schoolName.includes(',')) {
                            schoolName = schoolName.split(',')[0].trim();
                        }
                        schoolNameField.value = schoolName;
                    }
                } else {
                    showNotification('Address found and located on map', 'success');
                }

                // Update address field with the found address
                if (data.display_name) {
                    document.getElementById('address').value = data.display_name;
                }
            } else {
                showNotification('Invalid coordinates received', 'error');
                hideAddressLoading();
            }
        } else {
            showNotification(data.message || 'Location not found. Try: 1) More specific school name, 2) Add "school" after name, 3) Complete address', 'error');
            hideAddressLoading();
        }
    } catch (error) {
        console.error('Geocoding error:', error);
        showNotification('Search failed. You can click on map to set location manually.', 'error');
        hideAddressLoading();
    } finally {
        hideAddressLoading();
    }
}

// Dedicated school search function
async function searchSchool() {
    const schoolName = document.getElementById('mapSearchInput').value.trim();
    if (!schoolName) {
        showNotification('Please enter a school name to search', 'error');
        return;
    }

    showNotification('Searching for school in Philippines...', 'info');

    try {
        const response = await fetch(window.APP_CONFIG.API_BASE_URL + `geocode.php?address=${encodeURIComponent(schoolName)}&type=school`);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            const lat = parseFloat(data.lat);
            const lng = parseFloat(data.lng);

            if (!isNaN(lat) && !isNaN(lng)) {
                setMapLocation(lat, lng);
                showNotification('School found and located on map', 'success');

                // Auto-populate school name field
                const schoolNameField = document.getElementById('schoolName');
                let schoolName = data.display_name;
                if (schoolName.includes(',')) {
                    schoolName = schoolName.split(',')[0].trim();
                }
                schoolNameField.value = schoolName;

                // Update address field with the found address
                if (data.display_name) {
                    document.getElementById('address').value = data.display_name;
                }
            } else {
                showNotification('Invalid coordinates received', 'error');
            }
        } else {
            showNotification(data.message || 'School not found. Try: 1) Complete school name, 2) Add "school" after name, 3) Try city name only', 'error');
        }
    } catch (error) {
        console.error('School search error:', error);
        showNotification('School search failed. You can click on map to set location manually.', 'error');
    }
}

// Initialize map
function initializeMap() {
    // Check if map container exists
    if (!document.getElementById('map')) {
        return;
    }

    // Initialize map with Philippines center using OpenStreetMap style
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
        center: [121.7740, 12.8797],
        zoom: 6
    });

    // Add navigation controls
    map.addControl(new maplibregl.NavigationControl());

    // Add click event to map
    map.on('click', function (e) {
        setMapLocation(e.lngLat.lat, e.lngLat.lng);
    });

    // Add search function
    function searchByCoordinates(lat, lng) {
        if (lat && lng) {
            const formData = new FormData();
            formData.append('operation', 'search_near');
            formData.append('json', JSON.stringify({ lat, lng }));

            fetch(window.APP_CONFIG.API_BASE_URL + 'partnered_schools.php', {
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
        const response = await fetch(window.APP_CONFIG.API_BASE_URL + `geocode.php?address=${encodeURIComponent(address)}&type=address`);

        console.log('API Response status:', response.status);
        const data = await response.json();
        console.log('API Response data:', data);

        if (data.success) {
            const lat = parseFloat(data.lat);
            const lng = parseFloat(data.lng);

            console.log('Setting location to:', lat, lng);
            setMapLocation(lat, lng, false); // Don't update address since we already have it
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

// Reverse geocode function to get address from coordinates
async function reverseGeocode(lat, lng) {
    try {
        console.log('Reverse geocoding coordinates:', lat, lng);

        // Use our server-side proxy to avoid CORS issues
        const response = await fetch(window.APP_CONFIG.API_BASE_URL + `geocode.php?lat=${encodeURIComponent(lat)}&lng=${encodeURIComponent(lng)}`);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log('Reverse geocoding response:', data);

        if (data.success && data.address) {
            // Update address field with the found address
            document.getElementById('address').value = data.address;
            showNotification('Address updated from map location', 'success');
        } else {
            showNotification(data.message || 'Could not find address for this location', 'warning');
        }

    } catch (error) {
        console.error('Reverse geocoding error:', error);
        // Don't show error for reverse geocoding as it's not critical
        console.log('Could not fetch address for coordinates');
    } finally {
        // Always hide loading indicator
        hideAddressLoading();
    }
}

// Set map location
function setMapLocation(lat, lng, updateAddress = true) {
    console.log('setMapLocation called with:', lat, lng, 'updateAddress:', updateAddress);

    // Update input fields
    document.getElementById('latitude').value = lat;
    document.getElementById('longitude').value = lng;

    // Show loading when updating address from map click
    if (updateAddress) {
        showAddressLoading('Detecting address from map...');
    }

    // Remove existing marker and layers
    if (marker) {
        marker.remove();
        console.log('Marker removed');
    }
    if (map.getLayer('geofence-circle')) {
        map.removeLayer('geofence-circle');
        map.removeLayer('geofence-circle-outline');
        map.removeSource('geofence-circle');
        console.log('Geofence layers removed');
    }

    // Add new marker
    marker = new maplibregl.Marker({
        draggable: true
    })
        .setLngLat([lng, lat])
        .addTo(map);

    // Add geofence circle using GeoJSON
    const radius = parseInt(document.getElementById('geofencingRadius').value) || 80;
    const circleGeoJSON = createCircleGeoJSON(lng, lat, radius);
    console.log('Creating geofence with radius:', radius, 'at coordinates:', lng, lat);

    map.addSource('geofence-circle', {
        'type': 'geojson',
        'data': circleGeoJSON
    });
    console.log('Geofence source added');

    map.addLayer({
        'id': 'geofence-circle',
        'type': 'fill',
        'source': 'geofence-circle',
        'layout': {},
        'paint': {
            'fill-color': '#004d23',
            'fill-opacity': 0.2
        }
    });
    console.log('Geofence fill layer added');

    map.addLayer({
        'id': 'geofence-circle-outline',
        'type': 'line',
        'source': 'geofence-circle',
        'layout': {},
        'paint': {
            'line-color': '#004d23',
            'line-width': 2
        }
    });
    console.log('Geofence outline layer added');

    // Center map on new location
    map.flyTo({
        center: [lng, lat],
        zoom: 15,
        essential: true
    });

    // Get address from coordinates using reverse geocoding
    if (updateAddress) {
        console.log('Calling reverseGeocode for:', lat, lng);
        reverseGeocode(lat, lng);
    }

    // Handle marker drag events
    marker.on('dragend', function (e) {
        const newLngLat = e.target.getLngLat();

        // Show loading when marker is dragged
        showAddressLoading('Detecting address from new location...');

        // Update input fields
        document.getElementById('latitude').value = newLngLat.lat;
        document.getElementById('longitude').value = newLngLat.lng;

        // Update geofence circle by removing and recreating it
        if (map.getLayer('geofence-circle')) {
            map.removeLayer('geofence-circle');
            map.removeLayer('geofence-circle-outline');
            map.removeSource('geofence-circle');
        }

        // Recreate geofence circle at new location
        const radius = parseInt(document.getElementById('geofencingRadius').value) || 80;
        const circleGeoJSON = createCircleGeoJSON(newLngLat.lng, newLngLat.lat, radius);

        map.addSource('geofence-circle', {
            'type': 'geojson',
            'data': circleGeoJSON
        });

        map.addLayer({
            'id': 'geofence-circle',
            'type': 'fill',
            'source': 'geofence-circle',
            'layout': {},
            'paint': {
                'fill-color': '#004d23',
                'fill-opacity': 0.2
            }
        });

        map.addLayer({
            'id': 'geofence-circle-outline',
            'type': 'line',
            'source': 'geofence-circle',
            'layout': {},
            'paint': {
                'line-color': '#004d23',
                'line-width': 2
            }
        });

        reverseGeocode(newLngLat.lat, newLngLat.lng);
    });
}

// Create circle GeoJSON for geofence
function createCircleGeoJSON(centerLng, centerLat, radiusInMeters) {
    const points = 64;
    const coordinates = [];
    const km = radiusInMeters / 1000;

    for (let i = 0; i <= points; i++) {
        const angle = (i * 2 * Math.PI) / points;
        const lat = centerLat + (km * Math.sin(angle)) / 111.32;
        const lng = centerLng + (km * Math.cos(angle)) / (111.32 * Math.cos(centerLat * Math.PI / 180));
        coordinates.push([lng, lat]);
    }

    return {
        'type': 'Feature',
        'geometry': {
            'type': 'Polygon',
            'coordinates': [coordinates]
        }
    };
}

// Update geofence radius
document.getElementById('geofencingRadius').addEventListener('input', function () {
    if (marker) {
        const lat = parseFloat(document.getElementById('latitude').value);
        const lng = parseFloat(document.getElementById('longitude').value);
        const radius = parseInt(this.value) || 80;

        // Remove existing geofence layers
        if (map.getLayer('geofence-circle')) {
            map.removeLayer('geofence-circle');
            map.removeLayer('geofence-circle-outline');
            map.removeSource('geofence-circle');
        }

        // Create new geofence circle
        const circleGeoJSON = createCircleGeoJSON(lng, lat, radius);

        map.addSource('geofence-circle', {
            'type': 'geojson',
            'data': circleGeoJSON
        });

        map.addLayer({
            'id': 'geofence-circle',
            'type': 'fill',
            'source': 'geofence-circle',
            'layout': {},
            'paint': {
                'fill-color': '#004d23',
                'fill-opacity': 0.2
            }
        });

        map.addLayer({
            'id': 'geofence-circle-outline',
            'type': 'line',
            'source': 'geofence-circle',
            'layout': {},
            'paint': {
                'line-color': '#004d23',
                'line-width': 2
            }
        });
    }
});

// Open add modal
function openAddModal() {
    currentEditingSchool = null;
    document.getElementById('modalTitle').textContent = 'Add Partnered School';
    document.getElementById('partneredSchoolForm').reset();
    document.getElementById('geofencingRadius').value = '80';

    document.getElementById('partneredSchoolModal').classList.add('show');

    // Initialize map after modal is shown
    setTimeout(() => {
        if (!map) {
            initializeMap();
        } else {
            // Refresh map to fix rendering issues
            map.resize();
        }
    }, 100);
}

// Load all partnered schools once
async function loadPartneredSchools() {
    try {
        const formData = new FormData();
        formData.append('operation', 'read_partnered_schools');
        formData.append('json', JSON.stringify({}));

        const response = await fetch(window.APP_CONFIG.API_BASE_URL + 'system.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            allPartneredSchools = result.data;
            filterPartneredSchools();
        } else {
            showNotification(result.message, 'error');
            allPartneredSchools = [];
            filterPartneredSchools();
        }
    } catch (error) {
        showNotification('An error occurred while loading partnered schools', 'error');
        console.error('Error:', error);
        allPartneredSchools = [];
        filterPartneredSchools();
    }
}

// Filter partnered schools based on search term
function filterPartneredSchools() {
    const searchTerm = document.getElementById('searchInput').value.trim().toLowerCase();

    filteredSchools = allPartneredSchools;

    // Filter by search term
    if (searchTerm) {
        filteredSchools = filteredSchools.filter(school => {
            return school.name.toLowerCase().includes(searchTerm) ||
                school.address.toLowerCase().includes(searchTerm) ||
                school.latitude.toString().includes(searchTerm) ||
                school.longitude.toString().includes(searchTerm) ||
                (school.geofencing_radius && school.geofencing_radius.toString().includes(searchTerm));
        });
    }

    renderPartneredSchoolsTable();
}

// Generic Pagination Renderer
function renderPagination(navId, totalItems, currentPage, onPageClick, infoIds) {
    const nav = document.getElementById(navId);
    const totalPages = Math.ceil(totalItems / ITEMS_PER_PAGE);

    // Update info text
    if (infoIds) {
        const start = totalItems === 0 ? 0 : (currentPage - 1) * ITEMS_PER_PAGE + 1;
        const end = Math.min(currentPage * ITEMS_PER_PAGE, totalItems);
        document.getElementById(infoIds.start).textContent = start;
        document.getElementById(infoIds.end).textContent = end;
        document.getElementById(infoIds.total).textContent = totalItems;
    }

    if (totalPages <= 1) {
        nav.innerHTML = '';
        return;
    }

    let html = '';

    // Previous Button
    html += `
        <button onclick="${onPageClick}(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''} 
                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
            <span class="sr-only">Previous</span>
            <i class="fas fa-chevron-left"></i>
        </button>
    `;

    // Page Numbers (Show max 5 pages with ellipsis if needed)
    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, startPage + 4);

    if (endPage - startPage < 4) {
        startPage = Math.max(1, endPage - 4);
    }

    if (startPage > 1) {
        html += `<button onclick="${onPageClick}(1)" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</button>`;
        if (startPage > 2) html += `<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-gray-50 text-sm font-medium text-gray-700">...</span>`;
    }

    for (let i = startPage; i <= endPage; i++) {
        html += `
            <button onclick="${onPageClick}(${i})" 
                    class="relative inline-flex items-center px-4 py-2 border ${i === currentPage ? 'z-10 bg-green-50 border-green-500 text-green-600' : 'bg-white border-gray-300 text-gray-700'} text-sm font-medium hover:bg-gray-50">
                ${i}
            </button>
        `;
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) html += `<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-gray-50 text-sm font-medium text-gray-700">...</span>`;
        html += `<button onclick="${onPageClick}(${totalPages})" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">${totalPages}</button>`;
    }

    // Next Button
    html += `
        <button onclick="${onPageClick}(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''} 
                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
            <span class="sr-only">Next</span>
            <i class="fas fa-chevron-right"></i>
        </button>
    `;

    nav.innerHTML = html;
}

// Render partnered schools table
function renderPartneredSchoolsTable() {
    const tableBody = document.getElementById('partneredSchoolsTableBody');
    const noDataMessage = document.getElementById('noDataMessage');
    const paginationContainer = document.getElementById('partneredPagination');

    if (filteredSchools.length === 0) {
        tableBody.innerHTML = '';
        noDataMessage.classList.remove('hidden');
        paginationContainer.classList.add('hidden');
        return;
    }

    noDataMessage.classList.add('hidden');
    paginationContainer.classList.remove('hidden');

    // Safety check: ensure current page is within bounds
    const totalPages = Math.ceil(filteredSchools.length / ITEMS_PER_PAGE);
    if (partneredCurrentPage > totalPages && totalPages > 0) {
        partneredCurrentPage = totalPages;
    }

    // Calculate pagination slice
    const startIndex = (partneredCurrentPage - 1) * ITEMS_PER_PAGE;
    const endIndex = startIndex + ITEMS_PER_PAGE;
    const paginatedSchools = filteredSchools.slice(startIndex, endIndex);

    tableBody.innerHTML = paginatedSchools.map(school => `
        <tr>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
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
                <div class="dropdown" id="dropdown-${school.id}">
                    <button onclick="toggleDropdown('${school.id}')" 
                            class="text-gray-600 hover:text-gray-900 p-2 rounded-md hover:bg-gray-100">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="dropdown-content" id="dropdown-content-${school.id}">
                        <div class="dropdown-item text-indigo-600 hover:text-indigo-900" 
                             onclick="closeDropdown('${school.id}'); openEditModal(${JSON.stringify(school).replace(/"/g, '&quot;')})">
                            <i class="fas fa-edit mr-2"></i> Edit
                        </div>
                        <div class="dropdown-item text-red-600 hover:text-red-900" 
                             onclick="closeDropdown('${school.id}'); deletePartneredSchool(${school.id})">
                            <i class="fas fa-trash mr-2"></i> Delete
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    `).join('');

    renderPagination(
        'partneredPaginationNav',
        filteredSchools.length,
        partneredCurrentPage,
        'goToPartneredPage',
        { start: 'partneredStart', end: 'partneredEnd', total: 'partneredTotal' }
    );
}

// Partnered Schools Pagination Navigation
function goToPartneredPage(page) {
    const totalPages = Math.ceil(filteredSchools.length / ITEMS_PER_PAGE);
    if (page < 1 || page > totalPages) return;
    partneredCurrentPage = page;
    renderPartneredSchoolsTable();
}

function previousPartneredPage() {
    goToPartneredPage(partneredCurrentPage - 1);
}

function nextPartneredPage() {
    goToPartneredPage(partneredCurrentPage + 1);
}

// Open edit modal
function openEditModal(school) {
    currentEditingSchool = school;
    document.getElementById('modalTitle').textContent = 'Edit Partnered School';

    // Fill form with school data
    document.getElementById('schoolName').value = school.name;
    document.getElementById('address').value = school.address;
    document.getElementById('latitude').value = school.latitude;
    document.getElementById('longitude').value = school.longitude;
    document.getElementById('geofencingRadius').value = school.geofencing_radius || 80;

    document.getElementById('partneredSchoolModal').classList.add('show');

    // Initialize map after modal is shown and set location
    setTimeout(() => {
        if (!map) {
            initializeMap();
            setTimeout(() => {
                setMapLocation(school.latitude, school.longitude, false); // Don't update address
            }, 1000);
        } else {
            map.resize();
            setTimeout(() => {
                setMapLocation(school.latitude, school.longitude, false); // Don't update address
            }, 500);
        }
    }, 100);
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

    const schoolName = document.getElementById('schoolName').value.trim();
    const address = document.getElementById('address').value.trim();
    const latitude = document.getElementById('latitude').value;
    const longitude = document.getElementById('longitude').value;

    if (!schoolName) {
        document.getElementById('schoolNameError').textContent = 'School name is required';
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
        apiFormData.append('operation', currentEditingSchool ? 'update_partnered_school' : 'create_partnered_school');
        apiFormData.append('json', JSON.stringify(formData));

        const response = await fetch(window.APP_CONFIG.API_BASE_URL + 'system.php', {
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
        formData.append('operation', 'delete_partnered_school');
        formData.append('json', JSON.stringify({ id: id }));

        const response = await fetch(window.APP_CONFIG.API_BASE_URL + 'system.php', {
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

// ==================== SECTIONS FUNCTIONS ====================

// Load all sections once
async function loadSections() {
    try {
        const formData = new FormData();
        formData.append('operation', 'read_sections');
        formData.append('json', JSON.stringify({}));

        const response = await fetch(window.APP_CONFIG.API_BASE_URL + 'system.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            allSections = result.data;
            filterSections();
        } else {
            showNotification(result.message, 'error');
            allSections = [];
            filterSections();
        }
    } catch (error) {
        showNotification('An error occurred while loading sections', 'error');
        console.error('Error:', error);
        allSections = [];
        filterSections();
    }
}

// Filter sections based on search term
function filterSections() {
    const searchTerm = document.getElementById('sectionsSearchInput').value.trim().toLowerCase();

    filteredSectionsData = allSections;

    // Filter by search term
    if (searchTerm) {
        filteredSectionsData = filteredSectionsData.filter(section => {
            return section.section_name.toLowerCase().includes(searchTerm) ||
                (section.school_name && section.school_name.toLowerCase().includes(searchTerm));
        });
    }

    renderSectionsTable();
}

// Render sections table
function renderSectionsTable() {
    const tableBody = document.getElementById('sectionsTableBody');
    const noDataMessage = document.getElementById('sectionsNoDataMessage');
    const paginationContainer = document.getElementById('sectionsPagination');

    if (filteredSectionsData.length === 0) {
        tableBody.innerHTML = '';
        noDataMessage.classList.remove('hidden');
        paginationContainer.classList.add('hidden');
        return;
    }

    noDataMessage.classList.add('hidden');
    paginationContainer.classList.remove('hidden');

    // Safety check: ensure current page is within bounds
    const totalPages = Math.ceil(filteredSectionsData.length / ITEMS_PER_PAGE);
    if (sectionsCurrentPage > totalPages && totalPages > 0) {
        sectionsCurrentPage = totalPages;
    }

    // Calculate pagination slice
    const startIndex = (sectionsCurrentPage - 1) * ITEMS_PER_PAGE;
    const endIndex = startIndex + ITEMS_PER_PAGE;
    const paginatedSections = filteredSectionsData.slice(startIndex, endIndex);

    tableBody.innerHTML = paginatedSections.map(section => `
        <tr>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                ${section.section_name}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${section.school_name || '<span class="text-gray-400 italic">No school assigned</span>'}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="dropdown" id="section-dropdown-${section.id}">
                    <button onclick="toggleSectionDropdown('${section.id}')" 
                            class="text-gray-600 hover:text-gray-900 p-2 rounded-md hover:bg-gray-100">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="dropdown-content" id="section-dropdown-content-${section.id}">
                        <div class="dropdown-item text-indigo-600 hover:text-indigo-900" 
                             onclick="closeSectionDropdown('${section.id}'); openEditSectionModal(${JSON.stringify(section).replace(/"/g, '&quot;')})">
                            <i class="fas fa-edit mr-2"></i> Edit
                        </div>
                        <div class="dropdown-item text-red-600 hover:text-red-900" 
                             onclick="closeSectionDropdown('${section.id}'); deleteSection(${section.id})">
                            <i class="fas fa-trash mr-2"></i> Delete
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    `).join('');

    renderPagination(
        'sectionsPaginationNav',
        filteredSectionsData.length,
        sectionsCurrentPage,
        'goToSectionsPage',
        { start: 'sectionsStart', end: 'sectionsEnd', total: 'sectionsTotal' }
    );
}

// Sections Pagination Navigation
function goToSectionsPage(page) {
    const totalPages = Math.ceil(filteredSectionsData.length / ITEMS_PER_PAGE);
    if (page < 1 || page > totalPages) return;
    sectionsCurrentPage = page;
    renderSectionsTable();
}

function previousSectionsPage() {
    goToSectionsPage(sectionsCurrentPage - 1);
}

function nextSectionsPage() {
    goToSectionsPage(sectionsCurrentPage + 1);
}

// Toggle section dropdown
function toggleSectionDropdown(sectionId) {
    // Close all other dropdowns
    document.querySelectorAll('.dropdown-content').forEach(dropdown => {
        if (dropdown.id !== `section-dropdown-content-${sectionId}`) {
            dropdown.classList.remove('show');
        }
    });

    // Toggle current dropdown
    const currentDropdown = document.getElementById(`section-dropdown-content-${sectionId}`);
    currentDropdown.classList.toggle('show');
}

// Close section dropdown
function closeSectionDropdown(sectionId) {
    const dropdown = document.getElementById(`section-dropdown-content-${sectionId}`);
    if (dropdown) {
        dropdown.classList.remove('show');
    }
}

// Open add section modal
function openAddSectionModal() {
    currentEditingSection = null;
    document.getElementById('sectionModalTitle').textContent = 'Add Section';
    document.getElementById('sectionForm').reset();

    // Load partnered schools for dropdown
    loadPartneredSchoolsForSectionDropdown();

    document.getElementById('sectionModal').classList.add('show');
}

// Open edit section modal
function openEditSectionModal(section) {
    currentEditingSection = section;
    document.getElementById('sectionModalTitle').textContent = 'Edit Section';

    // Fill form with section data
    document.getElementById('sectionName').value = section.section_name;
    document.getElementById('sectionSchoolId').value = section.school_id || '';

    // Load partnered schools for dropdown
    loadPartneredSchoolsForSectionDropdown(section.school_id);

    document.getElementById('sectionModal').classList.add('show');
}

// Close section modal
function closeSectionModal() {
    document.getElementById('sectionModal').classList.remove('show');
    clearSectionValidationErrors();
}

// Clear section validation errors
function clearSectionValidationErrors() {
    const errorElements = document.querySelectorAll('[id$="Error"]');
    errorElements.forEach(element => {
        element.textContent = '';
    });
}

// Load partnered schools for section dropdown
async function loadPartneredSchoolsForSectionDropdown(selectedSchoolId = null) {
    try {
        const formData = new FormData();
        formData.append('operation', 'get_partnered_schools_dropdown');
        formData.append('json', JSON.stringify({}));

        const response = await fetch(window.APP_CONFIG.API_BASE_URL + 'system.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            const select = document.getElementById('sectionSchoolId');
            select.innerHTML = '<option value="">-- No Partnered School Assigned --</option>';

            result.data.forEach(school => {
                const option = document.createElement('option');
                option.value = school.id;
                option.textContent = `${school.name}`;
                if (selectedSchoolId && school.id == selectedSchoolId) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading partnered schools:', error);
    }
}

// Validate section form
function validateSectionForm() {
    clearSectionValidationErrors();
    let isValid = true;

    const sectionName = document.getElementById('sectionName').value.trim();

    if (!sectionName) {
        document.getElementById('sectionNameError').textContent = 'Section Name is required';
        isValid = false;
    }

    return isValid;
}

// Save section
async function saveSection() {
    if (!validateSectionForm()) {
        return;
    }

    try {
        const formData = {
            section_name: document.getElementById('sectionName').value.trim(),
            school_id: document.getElementById('sectionSchoolId').value || null
        };

        if (currentEditingSection) {
            formData.id = currentEditingSection.id;
        }

        const apiFormData = new FormData();
        apiFormData.append('operation', currentEditingSection ? 'update_section' : 'create_section');
        apiFormData.append('json', JSON.stringify(formData));

        const response = await fetch(window.APP_CONFIG.API_BASE_URL + 'system.php', {
            method: 'POST',
            body: apiFormData
        });

        const result = await response.json();

        if (result.success) {
            showNotification(result.message, 'success');
            closeSectionModal();
            loadSections();
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
        showNotification('An error occurred while saving section', 'error');
        console.error('Error:', error);
    }
}

// Delete section
async function deleteSection(id) {
    if (!confirm('Are you sure you want to delete this section?')) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('operation', 'delete_section');
        formData.append('json', JSON.stringify({ id: id }));

        const response = await fetch(window.APP_CONFIG.API_BASE_URL + 'system.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showNotification(result.message, 'success');
            loadSections();
        } else {
            showNotification(result.message, 'error');
        }
    } catch (error) {
        showNotification('An error occurred while deleting section', 'error');
        console.error('Error:', error);
    }
}

// ==================== ACADEMIC SESSIONS FUNCTIONS ====================

// Load academic sessions when System Settings tab is opened
async function loadAcademicSessions() {
    try {
        // Load all sessions
        const sessionsFormData = new FormData();
        sessionsFormData.append('operation', 'read_academic_sessions');
        sessionsFormData.append('json', JSON.stringify({}));

        const activeFormData = new FormData();
        activeFormData.append('operation', 'read_active_session');
        activeFormData.append('json', JSON.stringify({}));

        const [sessionsResult, activeResult] = await Promise.all([
            fetch(window.APP_CONFIG.API_BASE_URL + 'system.php', {
                method: 'POST',
                body: sessionsFormData
            }).then(res => res.json()),
            fetch(window.APP_CONFIG.API_BASE_URL + 'system.php', {
                method: 'POST',
                body: activeFormData
            }).then(res => res.json())
        ]);

        if (sessionsResult.success) {
            allAcademicSessions = sessionsResult.data;
            filteredAcademicData = allAcademicSessions; // Use global for table
            populateAcademicSessionDropdown();
            renderAcademicSessionsTable();
        } else {
            showNotification(sessionsResult.message, 'error');
        }

        if (activeResult.success && activeResult.data) {
            currentActiveSession = activeResult.data;
            updateCurrentSessionDisplay(currentActiveSession);
            updateSelectedSession(currentActiveSession.academic_session_id);
        } else {
            currentActiveSession = null;
            updateCurrentSessionDisplay(null);
        }

        // Initialize button state
        updateSaveButtonState();

    } catch (error) {
        showNotification('An error occurred while loading academic sessions', 'error');
        console.error('Error:', error);
    }
}

// Populate academic session dropdown
function populateAcademicSessionDropdown() {
    const select = $('#academicSessionSelect'); // Use jQuery for Select2
    select.html('<option value="">Select an academic session...</option>');

    allAcademicSessions.forEach(session => {
        const option = new Option(
            `${session.school_year} - ${session.semester_name}`,
            session.academic_session_id,
            parseInt(session.is_Active) === 1,
            parseInt(session.is_Active) === 1
        );
        select.append(option);
    });

    // Initialize or reinitialize Select2
    if (select.hasClass('select2-hidden-accessible')) {
        select.select2('destroy');
    }

    select.select2({
        placeholder: 'Select an academic session...',
        allowClear: false,
        width: '100%',
        minimumResultsForSearch: 5, // Show search box if more than 5 items
        theme: 'default'
    });

    // Add change listener to enable/disable save button
    select.off('change').on('change', function () {
        updateSaveButtonState();
    });
}

// Update selected session in dropdown
function updateSelectedSession(sessionId) {
    $('#academicSessionSelect').val(sessionId).trigger('change');
}

// Update save button state based on selection
function updateSaveButtonState() {
    const selectedSessionId = $('#academicSessionSelect').val();
    const saveBtn = document.getElementById('saveSessionBtn');

    if (!saveBtn) return;

    // Get current active session ID
    const currentActiveId = currentActiveSession ? currentActiveSession.academic_session_id : null;

    // Enable button only if selection is different from current active session
    if (selectedSessionId && selectedSessionId != currentActiveId) {
        saveBtn.disabled = false;
        saveBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    } else {
        saveBtn.disabled = true;
        saveBtn.classList.add('opacity-50', 'cursor-not-allowed');
    }
}

// Update current session display
function updateCurrentSessionDisplay(session) {
    const display = document.getElementById('currentSessionDisplay');

    if (session) {
        display.innerHTML = `
            <div class="text-green-600">
                <i class="fas fa-check-circle text-2xl mb-2"></i>
                <p class="font-medium">${session.school_year} - ${session.semester_name}</p>
                <p class="text-sm text-gray-600 mt-1">Currently Active</p>
            </div>
        `;
    } else {
        display.innerHTML = `
            <div class="text-orange-600">
                <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                <p class="font-medium">No Active Session</p>
                <p class="text-sm text-gray-600 mt-1">Please select an academic session</p>
            </div>
        `;
    }
}

// Render academic sessions table
function renderAcademicSessionsTable() {
    const tableBody = document.getElementById('academicSessionsTableBody');
    const paginationContainer = document.getElementById('academicPagination');

    if (filteredAcademicData.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                    <i class="fas fa-calendar-times text-2xl mb-2"></i>
                    <p>No academic sessions found</p>
                </td>
            </tr>
        `;
        paginationContainer.classList.add('hidden');
        return;
    }

    paginationContainer.classList.remove('hidden');

    // Safety check: ensure current page is within bounds
    const totalPages = Math.ceil(filteredAcademicData.length / ITEMS_PER_PAGE);
    if (academicCurrentPage > totalPages && totalPages > 0) {
        academicCurrentPage = totalPages;
    }

    // Calculate pagination slice
    const startIndex = (academicCurrentPage - 1) * ITEMS_PER_PAGE;
    const endIndex = startIndex + ITEMS_PER_PAGE;
    const paginatedSessions = filteredAcademicData.slice(startIndex, endIndex);

    tableBody.innerHTML = paginatedSessions.map(session => `
        <tr>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                ${session.school_year}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${session.semester_name}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
                ${parseInt(session.is_Active) === 1 ?
            '<span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Active</span>' :
            '<span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">Inactive</span>'
        }
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                ${new Date(session.created_at).toLocaleDateString()}
            </td>
        </tr>
    `).join('');

    renderPagination(
        'academicPaginationNav',
        filteredAcademicData.length,
        academicCurrentPage,
        'goToAcademicPage',
        { start: 'academicStart', end: 'academicEnd', total: 'academicTotal' }
    );
}

// Academic Sessions Pagination Navigation
function goToAcademicPage(page) {
    const totalPages = Math.ceil(filteredAcademicData.length / ITEMS_PER_PAGE);
    if (page < 1 || page > totalPages) return;
    academicCurrentPage = page;
    renderAcademicSessionsTable();
}

function previousAcademicPage() {
    goToAcademicPage(academicCurrentPage - 1);
}

function nextAcademicPage() {
    goToAcademicPage(academicCurrentPage + 1);
}

// Save active academic session
async function saveActiveAcademicSession() {
    const selectedSessionId = document.getElementById('academicSessionSelect').value;

    if (!selectedSessionId) {
        showNotification('Please select an academic session', 'error');
        return;
    }

    // Check if selected session is already active
    if (currentActiveSession && currentActiveSession.academic_session_id == selectedSessionId) {
        showNotification('This session is already active', 'info');
        return;
    }

    const saveBtn = document.getElementById('saveSessionBtn');
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';

    try {
        const formData = new FormData();
        formData.append('operation', 'set_active_session');
        formData.append('json', JSON.stringify({
            academic_session_id: parseInt(selectedSessionId)
        }));

        const response = await fetch(window.APP_CONFIG.API_BASE_URL + 'system.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showNotification('Active academic session updated successfully', 'success');
            // Reload data to reflect changes
            loadAcademicSessions();
        } else {
            showNotification(result.message, 'error');
        }
    } catch (error) {
        showNotification('An error occurred while saving active academic session', 'error');
        console.error('Error:', error);
    } finally {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Save Active Session';
    }
}

// Initialize academic sessions when System Settings tab is clicked
document.addEventListener('DOMContentLoaded', function () {
    // Add event listener for System Settings tab
    const systemSettingsTab = document.querySelector('button[onclick*="system-settings"]');
    if (systemSettingsTab) {
        systemSettingsTab.addEventListener('click', function () {
            // Load academic sessions when switching to System Settings tab
            setTimeout(() => {
                loadAcademicSessions();
            }, 100);
        });
    }
});
