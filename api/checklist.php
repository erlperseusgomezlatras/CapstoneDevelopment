<?php
include "headers.php";
date_default_timezone_set('Asia/Manila');

class Checklist {
    
    // Get all categories
    function getCategories($json) {
        include "connection.php";
        
        try {
            $sql = "SELECT * FROM checklist_category ORDER BY category_name";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return json_encode([
                'success' => true,
                'data' => $categories
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Add new category
    function addCategory($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $name = $data['name'] ?? '';
        $is_type = $data['is_type'] ?? 0;
        
        if (empty($name)) {
            return json_encode([
                'success' => false,
                'message' => 'Category name is required'
            ]);
        }
        
        try {
            $sql = "INSERT INTO checklist_category (category_name, is_type) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$name, $is_type]);
            
            return json_encode([
                'success' => true,
                'message' => 'Category added successfully'
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Update category
    function updateCategory($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $id = $data['id'] ?? '';
        $name = $data['name'] ?? '';
        $is_type = $data['is_type'] ?? 0;
        
        if (empty($id) || empty($name)) {
            return json_encode([
                'success' => false,
                'message' => 'ID and name are required'
            ]);
        }
        
        try {
            $sql = "UPDATE checklist_category SET category_name = ?, is_type = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$name, $is_type, $id]);
            
            return json_encode([
                'success' => true,
                'message' => 'Category updated successfully'
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Delete category
    function deleteCategory($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $id = $data['id'] ?? '';
        
        if (empty($id)) {
            return json_encode([
                'success' => false,
                'message' => 'ID is required'
            ]);
        }
        
        try {
            // First delete all criteria associated with this category
            $sql = "DELETE FROM checklist WHERE category_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$id]);
            
            // Then delete the category
            $sql = "DELETE FROM checklist_category WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$id]);
            
            return json_encode([
                'success' => true,
                'message' => 'Category deleted successfully'
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Get all types
    function getTypes($json) {
        include "connection.php";
        
        try {
            $sql = "SELECT * FROM checklist_type ORDER BY type_name";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return json_encode([
                'success' => true,
                'data' => $types
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Add new type
    function addType($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $name = $data['name'] ?? '';
        
        if (empty($name)) {
            return json_encode([
                'success' => false,
                'message' => 'Type name is required'
            ]);
        }
        
        try {
            $sql = "INSERT INTO checklist_type (type_name) VALUES (?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$name]);
            
            return json_encode([
                'success' => true,
                'message' => 'Type added successfully'
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Update type
    function updateType($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $id = $data['id'] ?? '';
        $name = $data['name'] ?? '';
        
        if (empty($id) || empty($name)) {
            return json_encode([
                'success' => false,
                'message' => 'ID and name are required'
            ]);
        }
        
        try {
            $sql = "UPDATE checklist_type SET type_name = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$name, $id]);
            
            return json_encode([
                'success' => true,
                'message' => 'Type updated successfully'
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Delete type
    function deleteType($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $id = $data['id'] ?? '';
        
        if (empty($id)) {
            return json_encode([
                'success' => false,
                'message' => 'ID is required'
            ]);
        }
        
        try {
            // Check if type is being used in any criteria
            $sql = "SELECT COUNT(*) as count FROM checklist WHERE type_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                return json_encode([
                    'success' => false,
                    'message' => 'Cannot delete type. It is being used by criteria.'
                ]);
            }
            
            $sql = "DELETE FROM checklist_type WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$id]);
            
            return json_encode([
                'success' => true,
                'message' => 'Type deleted successfully'
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Get all criteria with category and type names
    function getCriteria($json) {
        include "connection.php";
        
        try {
            $sql = "SELECT c.*, cat.category_name, t.type_name 
                    FROM checklist c 
                    LEFT JOIN checklist_category cat ON c.category_id = cat.id 
                    LEFT JOIN checklist_type t ON c.type_id = t.id 
                    ORDER BY cat.category_name, c.checklist_criteria";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $criteria = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return json_encode([
                'success' => true,
                'data' => $criteria
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Add new criteria
    function addCriteria($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $category_id = $data['category_id'] ?? '';
        $type_id = !empty($data['type_id']) ? $data['type_id'] : null;
        $name = $data['name'] ?? '';
        $points = $data['points'] ?? 0;
        
        if (empty($category_id) || empty($name)) {
            return json_encode([
                'success' => false,
                'message' => 'Category and name are required'
            ]);
        }
        
        try {
            $sql = "INSERT INTO checklist (category_id, type_id, checklist_criteria, points) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$category_id, $type_id, $name, $points]);
            
            return json_encode([
                'success' => true,
                'message' => 'Criteria added successfully'
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Update criteria
    function updateCriteria($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $id = $data['id'] ?? '';
        $category_id = $data['category_id'] ?? '';
        $type_id = !empty($data['type_id']) ? $data['type_id'] : null;
        $name = $data['name'] ?? '';
        $points = $data['points'] ?? 0;
        
        if (empty($id) || empty($category_id) || empty($name)) {
            return json_encode([
                'success' => false,
                'message' => 'ID, category, and name are required'
            ]);
        }
        
        try {
            $sql = "UPDATE checklist SET category_id = ?, type_id = ?, checklist_criteria = ?, points = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$category_id, $type_id, $name, $points, $id]);
            
            return json_encode([
                'success' => true,
                'message' => 'Criteria updated successfully'
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    // Delete criteria
    function deleteCriteria($json) {
        include "connection.php";
        
        $data = json_decode($json, true);
        $id = $data['id'] ?? '';
        
        if (empty($id)) {
            return json_encode([
                'success' => false,
                'message' => 'ID is required'
            ]);
        }
        
        try {
            // Check if criteria has any results
            $sql = "SELECT COUNT(*) as count FROM checklist_results WHERE checklist_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                return json_encode([
                    'success' => false,
                    'message' => 'Cannot delete criteria. It has associated results.'
                ]);
            }
            
            $sql = "DELETE FROM checklist WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$id]);
            
            return json_encode([
                'success' => true,
                'message' => 'Criteria deleted successfully'
            ]);
            
        } catch(PDOException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    $action = $data['action'] ?? '';
    
    $checklist = new Checklist();
    
    switch ($action) {
        case 'getCategories':
            echo $checklist->getCategories($json);
            break;
        case 'addCategory':
            echo $checklist->addCategory($json);
            break;
        case 'updateCategory':
            echo $checklist->updateCategory($json);
            break;
        case 'deleteCategory':
            echo $checklist->deleteCategory($json);
            break;
        case 'getTypes':
            echo $checklist->getTypes($json);
            break;
        case 'addType':
            echo $checklist->addType($json);
            break;
        case 'updateType':
            echo $checklist->updateType($json);
            break;
        case 'deleteType':
            echo $checklist->deleteType($json);
            break;
        case 'getCriteria':
            echo $checklist->getCriteria($json);
            break;
        case 'addCriteria':
            echo $checklist->addCriteria($json);
            break;
        case 'updateCriteria':
            echo $checklist->updateCriteria($json);
            break;
        case 'deleteCriteria':
            echo $checklist->deleteCriteria($json);
            break;
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
            break;
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>