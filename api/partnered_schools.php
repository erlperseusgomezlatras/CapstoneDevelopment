<?php
include "headers.php";

class PartneredSchools {
    
    // Create new partnered school
    function create($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        
        try {
            // Validate required fields
            $required_fields = ['name', 'address', 'latitude', 'longitude'];
            $errors = [];
            
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
                }
            }
            
            // Validate latitude and longitude
            if (!empty($data['latitude']) && (!is_numeric($data['latitude']) || $data['latitude'] < -90 || $data['latitude'] > 90)) {
                $errors['latitude'] = 'Valid latitude is required (-90 to 90)';
            }
            
            if (!empty($data['longitude']) && (!is_numeric($data['longitude']) || $data['longitude'] < -180 || $data['longitude'] > 180)) {
                $errors['longitude'] = 'Valid longitude is required (-180 to 180)';
            }
            
            // Validate geofencing radius
            if (!empty($data['geofencing_radius']) && (!is_numeric($data['geofencing_radius']) || $data['geofencing_radius'] < 10 || $data['geofencing_radius'] > 1000)) {
                $errors['geofencing_radius'] = 'Geofence radius must be between 10 and 1000 meters';
            }
            
            // Check if school name already exists
            $check_name_sql = "SELECT id FROM partnered_schools WHERE name = ?";
            $stmt = $conn->prepare($check_name_sql);
            $stmt->execute([$data['name']]);
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                $errors['name'] = 'School name already exists';
            }
            
            if (!empty($errors)) {
                return json_encode([
                    'success' => false,
                    'message' => 'Validation errors occurred',
                    'errors' => $errors
                ]);
            }
            
            // Insert partnered school
            $sql = "INSERT INTO partnered_schools (name, address, latitude, longitude, geofencing_radius) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $geofencing_radius = !empty($data['geofencing_radius']) ? $data['geofencing_radius'] : 80;
            
            $result = $stmt->execute([
                $data['name'], 
                $data['address'], 
                $data['latitude'], 
                $data['longitude'], 
                $geofencing_radius
            ]);
            
            if ($result) {
                return json_encode([
                    'success' => true,
                    'message' => 'Partnered school created successfully'
                ]);
            } else {
                return json_encode([
                    'success' => false,
                    'message' => 'Failed to create partnered school'
                ]);
            }
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Read partnered schools
    function read($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $search = isset($data['search']) ? $data['search'] : '';
        
        try {
            // Base query
            $sql = "SELECT * FROM partnered_schools WHERE 1=1";
            $params = [];
            
            // Add search condition
            if (!empty($search)) {
                $sql .= " AND (name LIKE ? OR address LIKE ?)";
                $search_param = "%$search%";
                $params = array_merge($params, array_fill(0, 2, $search_param));
            }
            
            $sql .= " ORDER BY name";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return json_encode([
                'success' => true,
                'data' => $schools
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Update partnered school
    function update($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        
        try {
            // Validate required fields
            $required_fields = ['id', 'name', 'address', 'latitude', 'longitude'];
            $errors = [];
            
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
                }
            }
            
            // Validate latitude and longitude
            if (!empty($data['latitude']) && (!is_numeric($data['latitude']) || $data['latitude'] < -90 || $data['latitude'] > 90)) {
                $errors['latitude'] = 'Valid latitude is required (-90 to 90)';
            }
            
            if (!empty($data['longitude']) && (!is_numeric($data['longitude']) || $data['longitude'] < -180 || $data['longitude'] > 180)) {
                $errors['longitude'] = 'Valid longitude is required (-180 to 180)';
            }
            
            // Validate geofencing radius
            if (!empty($data['geofencing_radius']) && (!is_numeric($data['geofencing_radius']) || $data['geofencing_radius'] < 10 || $data['geofencing_radius'] > 1000)) {
                $errors['geofencing_radius'] = 'Geofence radius must be between 10 and 1000 meters';
            }
            
            // Check if partnered school exists
            $check_sql = "SELECT id FROM partnered_schools WHERE id = ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->execute([$data['id']]);
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                $errors['id'] = 'Partnered school not found';
            }
            
            // Check if school name already exists (excluding current)
            $check_name_sql = "SELECT id FROM partnered_schools WHERE name = ? AND id != ?";
            $stmt = $conn->prepare($check_name_sql);
            $stmt->execute([$data['name'], $data['id']]);
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                $errors['name'] = 'School name already exists';
            }
            
            if (!empty($errors)) {
                return json_encode([
                    'success' => false,
                    'message' => 'Validation errors occurred',
                    'errors' => $errors
                ]);
            }
            
            // Update partnered school
            $sql = "UPDATE partnered_schools SET name = ?, address = ?, latitude = ?, longitude = ?, geofencing_radius = ? WHERE id = ?";
            
            $stmt = $conn->prepare($sql);
            $geofencing_radius = !empty($data['geofencing_radius']) ? $data['geofencing_radius'] : 80;
            
            $result = $stmt->execute([
                $data['name'], 
                $data['address'], 
                $data['latitude'], 
                $data['longitude'], 
                $geofencing_radius,
                $data['id']
            ]);
            
            if ($result) {
                return json_encode([
                    'success' => true,
                    'message' => 'Partnered school updated successfully'
                ]);
            } else {
                return json_encode([
                    'success' => false,
                    'message' => 'Failed to update partnered school'
                ]);
            }
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Delete partnered school
    function delete($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        
        if (empty($data['id'])) {
            return json_encode([
                'success' => false,
                'message' => 'ID is required'
            ]);
        }
        
        try {
            // Check if partnered school exists
            $check_sql = "SELECT id FROM partnered_schools WHERE id = ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->execute([$data['id']]);
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                return json_encode([
                    'success' => false,
                    'message' => 'Partnered school not found'
                ]);
            }
            
            // Check if there are sections using this partnered school
            $check_sections_sql = "SELECT COUNT(*) as count FROM sections WHERE school_id = ?";
            $stmt = $conn->prepare($check_sections_sql);
            $stmt->execute([$data['id']]);
            $sections_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($sections_count > 0) {
                return json_encode([
                    'success' => false,
                    'message' => 'Cannot delete partnered school. There are sections assigned to this school.'
                ]);
            }
            
            // Delete partnered school
            $sql = "DELETE FROM partnered_schools WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([$data['id']]);
            
            if ($result) {
                return json_encode([
                    'success' => true,
                    'message' => 'Partnered school deleted successfully'
                ]);
            } else {
                return json_encode([
                    'success' => false,
                    'message' => 'Failed to delete partnered school'
                ]);
            }
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
}

$operation = isset($_POST["operation"]) ? $_POST["operation"] : "0";
$json = isset($_POST["json"]) ? $_POST["json"] : "0";

$partneredSchools = new PartneredSchools();

switch ($operation) {
    case 'create':
        echo $partneredSchools->create($json);
        break;
        
    case 'read':
        echo $partneredSchools->read($json);
        break;
        
    case 'update':
        echo $partneredSchools->update($json);
        break;
        
    case 'delete':
        echo $partneredSchools->delete($json);
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid operation'
        ]);
        http_response_code(400);
        break;
}
?>
