<?php
include "headers.php";

// Geocoding function using Nominatim API
function geocodeAddress($address) {
    // Try multiple search strategies
    $searchQueries = [
        $address,                                    // Original address
        $address . ', Philippines',                 // With country
        str_replace('Philippines', '', $address),     // Without country (if already included)
        extractCity($address),                       // City only
        extractStreet($address)                        // Street only
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

// Extract city from address
function extractCity($address) {
    $patterns = [
        '/,\s*([^,]+),\s*[^,]*$/i',           // City, Province
        '/,\s*([^,]+)\s*city$/i',            // City keyword
        '/\b(City|City)\b([^,]+)/i'           // City word
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
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['address'])) {
    $address = $_GET['address'];
    $result = geocodeAddress($address);
    
    header('Content-Type: application/json');
    echo json_encode($result);
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Address parameter is required'
    ]);
}
?>
