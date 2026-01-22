<?php
include "headers.php";

// School-specific geocoding function for Philippines
function geocodeSchool($schoolName) {
    // School-related keywords to enhance search
    $schoolKeywords = ['school', 'university', 'college', 'academy', 'institute', 'elementary', 'high school', 'primary', 'secondary'];
    
    $searchQueries = [
        $schoolName . ' school, Philippines',
        $schoolName . ' university, Philippines',
        $schoolName . ' college, Philippines',
        $schoolName . ' academy, Philippines',
        $schoolName . ', Philippines',
        $schoolName
    ];
    
    // If query doesn't contain school keywords, add them
    $hasSchoolKeyword = false;
    foreach ($schoolKeywords as $keyword) {
        if (stripos($schoolName, $keyword) !== false) {
            $hasSchoolKeyword = true;
            break;
        }
    }
    
    if (!$hasSchoolKeyword) {
        array_unshift($searchQueries, $schoolName . ' school');
    }
    
    foreach ($searchQueries as $index => $searchQuery) {
        if (empty(trim($searchQuery))) continue;
        
        // Use OpenStreetMap Nominatim with educational institution filter
        $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($searchQuery) . "&limit=5&countrycodes=ph&addressdetails=1";
        
        // Add amenity=school parameter for better school search results
        if ($index < 3) {
            $url .= "&featuretype=amenity";
        }
        
        $options = [
            'http' => [
                'header' => "User-Agent: PHINMA-Education-System/1.0\r\n"
            ]
        ];
        
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        
        if ($response !== false) {
            $data = json_decode($response, true);
            
            if ($data && count($data) > 0) {
                // Prioritize results that are educational institutions
                $bestMatch = null;
                $schoolScore = 0;
                
                foreach ($data as $result) {
                    $currentScore = 0;
                    $displayName = strtolower($result['display_name'] ?? '');
                    $address = $result['address'] ?? [];
                    
                    // Check if it's a school/educational institution
                    if (isset($address['amenity']) && in_array($address['amenity'], ['school', 'university', 'college'])) {
                        $currentScore += 10;
                    }
                    
                    // Check if school name appears in display name
                    if (strpos($displayName, strtolower($schoolName)) !== false) {
                        $currentScore += 5;
                    }
                    
                    // Check for school-related keywords
                    foreach ($schoolKeywords as $keyword) {
                        if (strpos($displayName, $keyword) !== false) {
                            $currentScore += 2;
                        }
                    }
                    
                    // Ensure it's in Philippines
                    if (isset($address['country']) && strtolower($address['country']) === 'philippines') {
                        $currentScore += 3;
                    }
                    
                    // Update best match if this has higher score
                    if ($currentScore > $schoolScore) {
                        $schoolScore = $currentScore;
                        $bestMatch = $result;
                    }
                }
                
                // If no school found with good score, take the first result that's in Philippines
                if ($schoolScore < 3) {
                    foreach ($data as $result) {
                        if (isset($result['address']['country']) && strtolower($result['address']['country']) === 'philippines') {
                            $bestMatch = $result;
                            break;
                        }
                    }
                }
                
                if ($bestMatch) {
                    return [
                        'success' => true,
                        'lat' => floatval($bestMatch['lat']),
                        'lng' => floatval($bestMatch['lon']),
                        'display_name' => $bestMatch['display_name'] ?? '',
                        'address' => $bestMatch['address'] ?? [],
                        'search_method' => 'school_' . ($index + 1),
                        'is_school' => $schoolScore >= 5
                    ];
                }
            }
        }
        
        // Small delay between requests to avoid rate limiting
        if ($index < count($searchQueries) - 1) {
            usleep(500000); // 0.5 second delay
        }
    }
    
    return [
        'success' => false,
        'message' => 'School not found in Philippines. Try: 1) Complete school name, 2) Add "school" after name, 3) Try city name only'
    ];
}

// Geocoding function using Nominatim API
function geocodeAddress($address) {
    $searchQueries = [
        $address,
        $address . ', Philippines',                 
        str_replace('Philippines', '', $address),   
        extractCity($address),                       
        extractStreet($address)                     
    ];
    
    foreach ($searchQueries as $index => $searchQuery) {
        if (empty(trim($searchQuery))) continue;
        
        $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($searchQuery) . "&limit=3&countrycodes=ph&addressdetails=1";
        
        $options = [
            'http' => [
                'header' => "User-Agent: PHINMA-Education-System/1.0\r\n"
            ]
        ];
        
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        
        if ($response !== false) {
            $data = json_decode($response, true);
            
            if ($data && count($data) > 0) {
                // Try to find the best match
                $bestMatch = $data[0];
                
                // Look for exact matches first
                foreach ($data as $result) {
                    if (isset($result['address']) && 
                        isset($result['address']['country']) && 
                        strtolower($result['address']['country']) === 'philippines') {
                        $bestMatch = $result;
                        break;
                    }
                }
                
                return [
                    'success' => true,
                    'lat' => floatval($bestMatch['lat']),
                    'lng' => floatval($bestMatch['lon']),
                    'display_name' => $bestMatch['display_name'] ?? '',
                    'address' => $bestMatch['address'] ?? [],
                    'search_method' => $index + 1
                ];
            }
        }
        
        // Small delay between requests to avoid rate limiting
        if ($index < count($searchQueries) - 1) {
            usleep(500000); // 0.5 second delay
        }
    }
    
    return [
        'success' => false,
        'message' => 'Address not found in Philippines. Try: 1) Use simpler address format, 2) Try just city name, 3) Click on map directly'
    ];
}

// Reverse geocoding function using Nominatim API
function reverseGeocode($lat, $lng) {
    $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat=" . urlencode($lat) . "&lon=" . urlencode($lng) . "&zoom=18&addressdetails=1";
    
    $options = [
        'http' => [
            'header' => "User-Agent: PHINMA-Education-System/1.0\r\n"
        ]
    ];
    
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    
    if ($response !== false) {
        $data = json_decode($response, true);
        
        if ($data && isset($data['display_name'])) {
            return [
                'success' => true,
                'address' => $data['display_name'],
                'address_details' => $data['address'] ?? []
            ];
        }
    }
    
    return [
        'success' => false,
        'message' => 'Could not find address for these coordinates'
    ];
}

// Extract city from address
function extractCity($address) {
    $patterns = [
        '/,\s*([^,]+),\s*[^,]*$/i',           
        '/,\s*([^,]+)\s*city$/i',            
        '/\b(City|City)\b([^,]+)/i'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $address, $matches)) {
            return trim($matches[1]);
        }
    }
    
    return $address;
}

// Extract street from address
function extractStreet($address) {
    $parts = explode(',', $address);
    return trim($parts[0] ?? $address);
}

// Handle the request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['address'])) {
        if (isset($_GET['type']) && $_GET['type'] === 'school') {
            // School-specific geocoding
            $address = $_GET['address'];
            $result = geocodeSchool($address);
            
            header('Content-Type: application/json');
            echo json_encode($result);
        } else {
            // Regular address geocoding
            $address = $_GET['address'];
            $result = geocodeAddress($address);
            
            header('Content-Type: application/json');
            echo json_encode($result);
        }
    } elseif (isset($_GET['lat']) && isset($_GET['lng'])) {
        // Reverse geocoding
        $lat = $_GET['lat'];
        $lng = $_GET['lng'];
        $result = reverseGeocode($lat, $lng);
        
        header('Content-Type: application/json');
        echo json_encode($result);
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Address parameter (for forward geocoding) or lat and lng parameters (for reverse geocoding) are required'
        ]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Only GET requests are supported'
    ]);
}
?>
