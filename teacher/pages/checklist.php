<?php
// Include configuration
require_once '../../config/config.php';

session_start();

// Check if user is logged in
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
    // Use cookie data
    $userData = json_decode($_COOKIE['userData'], true);
}

// Verify user is a teacher/coordinator
if (!in_array($userData['level'], ['Head Teacher', 'Coordinator'])) {
    header('Location: ../../login.php');
    exit();
}

$teacher_name = $userData['firstname'] ?? 'Teacher';
$teacher_email = $userData['email'] ?? '';

// Set current page for sidebar
$current_page = 'checklist';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checklist Management | PHINMA Practicum Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Select2 CSS and JS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- Axios -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    
    <!-- App Configuration -->
    <script src="../../assets/js/config.js"></script>
    
    <style>
        /* Custom animations and transitions */
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .sidebar-item {
            transition: all 0.2s ease;
        }
        .sidebar-item:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }
        .sidebar-item.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: rgb(255, 255, 255);
            border-left: 4px solid rgb(255, 255, 255);
        }
        
        /* Select2 custom styling to match Tailwind */
        .select2-container--default .select2-selection--single {
            height: 48px !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0.5rem !important;
            padding: 0.75rem 1rem !important;
            display: flex !important;
            align-items: center !important;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 1.5 !important;
            padding-left: 0 !important;
            color: #111827 !important;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 46px !important;
            right: 10px !important;
        }
        
        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #058643 !important;
            box-shadow: 0 0 0 3px rgba(5, 134, 67, 0.1) !important;
        }
        
        .select2-dropdown {
            border: 1px solid #d1d5db !important;
            border-radius: 0.5rem !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
        }
        
        .select2-search--dropdown .select2-search__field {
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            padding: 0.5rem !important;
        }
        
        .select2-search--dropdown .select2-search__field:focus {
            border-color: #058643 !important;
            outline: none !important;
        }
        
        .select2-results__option {
            padding: 8px 12px !important;
            color: #374151 !important;
            font-size: 14px !important;
        }
        
        .select2-results__option--highlighted {
            background-color: #058643 !important;
            color: white !important;
        }
        
        .select2-results__option[aria-selected="true"] {
            background-color: #eff6ff !important;
            color: #1e40af !important;
        }
        
        /* Tab styling */
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
        
        /* Card styling */
        .category-card, .type-card, .criteria-card {
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
        }
        .category-card:hover, .type-card:hover, .criteria-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-1px);
        }
        
        /* Modal styling */
        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }
        
        /* Form input styling */
        .form-input {
            transition: all 0.2s ease;
        }
        .form-input:focus {
            border-color: rgb(59, 130, 246);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        /* Button styling */
        .btn-primary {
            background-color: rgb(59, 130, 246);
            transition: all 0.2s ease;
        }
        .btn-primary:hover {
            background-color: rgb(37, 99, 235);
            transform: translateY(-1px);
        }
        
        /* Alert styling */
        .alert {
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
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
<body class="bg-gray-50">
    <div class="min-h-screen flex">
        <?php require_once '../sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden md:ml-72">
            <!-- Mobile Header -->
            <header class="bg-white shadow-sm border-b border-gray-200 md:hidden">
                <div class="px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center h-16">
                        <div class="flex items-center space-x-3">
                            <button id="mobileMenuBtn" class="p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                                <i class="fas fa-bars h-5 w-5"></i>
                            </button>
                            <h1 class="text-lg font-semibold text-gray-900">Checklist Management</h1>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-600">Welcome, <?php echo htmlspecialchars($teacher_name); ?></span>
                            <div class="w-8 h-8 bg-green-600 rounded-full flex items-center justify-center">
                                <span class="text-white text-sm font-medium"><?php echo strtoupper(substr($teacher_name, 0, 1)); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Desktop Header -->
            <header class="bg-white shadow-sm border-b border-gray-200 hidden md:block">
                <div class="px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center h-16">
                        <div class="flex items-center">
                            <img src="../../assets/images/logo_college.png" class="h-10" alt="College Logo">
                        </div>
                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-600">Welcome, <?php echo htmlspecialchars($teacher_name); ?></span>
                            <div class="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center">
                                <span class="text-white text-lg font-medium"><?php echo strtoupper(substr($teacher_name, 0, 1)); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto bg-gray-50 p-4 sm:p-6 lg:p-8">
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">Checklist Management</h1>
                    <p class="text-sm text-gray-600 mt-1">Manage checklist categories, types, and criteria for student evaluation</p>
                </div>
                
                <!-- Tabs Navigation -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="border-b border-gray-200">
                        <!-- Desktop Tabs -->
                        <nav class="hidden md:flex -mb-px">
                            <button onclick="switchTab('criteria')" 
                                    class="tab-button active px-6 py-3 text-sm font-medium text-green-700 hover:text-green-800 focus:outline-none focus:text-green-800">
                                <i class="fas fa-list-check mr-2"></i>
                                Checklist Criteria
                            </button>
                            <button onclick="switchTab('categories')" 
                                    class="tab-button px-6 py-3 text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700">
                                <i class="fas fa-tags mr-2"></i>
                                Categories
                            </button>
                            <button onclick="switchTab('types')" 
                                    class="tab-button px-6 py-3 text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700">
                                <i class="fas fa-layer-group mr-2"></i>
                                Types
                            </button>
                        </nav>
                        
                        <!-- Mobile Tabs -->
                        <nav class="flex md:hidden -mb-px">
                            <div class="flex flex-1 overflow-x-auto">
                                <button onclick="switchTab('criteria')" 
                                        class="mobile-tab-button active flex-shrink-0 px-4 py-3 text-sm font-medium text-green-700 hover:text-green-800 focus:outline-none focus:text-green-800 border-b-2 border-green-700">
                                    <i class="fas fa-list-check mr-1"></i>
                                    <span class="hidden sm:inline">Checklist</span>
                                    <span class="sm:hidden">Checklist</span>
                                </button>
                                <button onclick="switchTab('categories')" 
                                        class="mobile-tab-button flex-shrink-0 px-4 py-3 text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700 border-b-2 border-transparent">
                                    <i class="fas fa-tags mr-1"></i>
                                    <span class="hidden sm:inline">Categories</span>
                                    <span class="sm:hidden">Categories</span>
                                </button>
                                <button onclick="switchTab('types')" 
                                        class="mobile-tab-button flex-shrink-0 px-4 py-3 text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700 border-b-2 border-transparent">
                                    <i class="fas fa-layer-group mr-1"></i>
                                    <span class="hidden sm:inline">Types</span>
                                    <span class="sm:hidden">Types</span>
                                </button>
                            </div>
                        </nav>
                    </div>
                </div>
                
                <!-- Criteria Tab Content -->
                <div id="criteria" class="tab-content active">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-semibold text-gray-900">Checklist Criteria</h2>
                        <button onclick="openCriteriaModal()" class="btn-primary text-white px-4 py-2 rounded-md text-sm font-medium flex items-center">
                            <i class="fas fa-plus mr-2"></i>
                            Add New Criteria
                        </button>
                    </div>
                    
                    <div id="criteriaList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Criteria cards will be loaded here -->
                    </div>
                </div>
                
                <!-- Categories Tab Content -->
                <div id="categories" class="tab-content">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-semibold text-gray-900">Categories</h2>
                        <button onclick="openCategoryModal()" class="btn-primary text-white px-4 py-2 rounded-md text-sm font-medium flex items-center">
                            <i class="fas fa-plus mr-2"></i>
                            Add New Category
                        </button>
                    </div>
                    
                    <div id="categoryList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Category cards will be loaded here -->
                    </div>
                </div>
                
                <!-- Types Tab Content -->
                <div id="types" class="tab-content">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-semibold text-gray-900">Types</h2>
                        <button onclick="openTypeModal()" class="btn-primary text-white px-4 py-2 rounded-md text-sm font-medium flex items-center">
                            <i class="fas fa-plus mr-2"></i>
                            Add New Type
                        </button>
                    </div>
                    
                    <div id="typeList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Type cards will be loaded here -->
                    </div>
                </div>

                <!-- Criteria Modal -->
                <div id="criteriaModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                        <div class="fixed inset-0 transition-opacity modal-backdrop" onclick="closeCriteriaModal()"></div>
                        
                        <div class="relative inline-block w-full max-w-lg text-left align-middle transition-all transform bg-white shadow-xl rounded-lg">
                            <div class="flex justify-between items-center p-6 border-b">
                                <h3 class="text-lg font-medium text-gray-900">Add/Edit Checklist Criteria</h3>
                                <button onclick="closeCriteriaModal()" class="text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            
                            <form id="criteriaForm" class="p-6">
                                <input type="hidden" id="criteriaId">
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                                    <select id="categorySelect" class="form-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                        <option value="">Select Category</option>
                                    </select>
                                </div>

                                <div class="mb-4" id="typeGroup" style="display: none;">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                                    <select id="typeSelect" class="form-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Select Type</option>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Criteria Name</label>
                                    <input type="text" id="criteriaName" class="form-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Points</label>
                                    <input type="number" id="criteriaPoints" class="form-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" min="0" required>
                                </div>
                            </form>
                            
                            <div class="flex justify-end space-x-3 p-6 border-t bg-gray-50">
                                <button type="button" onclick="closeCriteriaModal()" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    Cancel
                                </button>
                                <button type="button" onclick="saveCriteria()" class="btn-primary text-white px-4 py-2 rounded-md text-sm font-medium">
                                    Save
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Category Modal -->
                <div id="categoryModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                        <div class="fixed inset-0 transition-opacity modal-backdrop" onclick="closeCategoryModal()"></div>
                        
                        <div class="relative inline-block w-full max-w-lg text-left align-middle transition-all transform bg-white shadow-xl rounded-lg">
                            <div class="flex justify-between items-center p-6 border-b">
                                <h3 class="text-lg font-medium text-gray-900">Add/Edit Category</h3>
                                <button onclick="closeCategoryModal()" class="text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            
                            <form id="categoryForm" class="p-6">
                                <input type="hidden" id="categoryId">
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Category Name</label>
                                    <input type="text" id="categoryName" class="form-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                </div>

                                <div class="mb-4">
                                    <div class="flex items-center">
                                        <input type="checkbox" id="hasType" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                        <label for="hasType" class="ml-2 block text-sm text-gray-900">
                                            This category requires types (like Personal Kit, Teacher's Kit)
                                        </label>
                                    </div>
                                </div>
                            </form>
                            
                            <div class="flex justify-end space-x-3 p-6 border-t bg-gray-50">
                                <button type="button" onclick="closeCategoryModal()" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    Cancel
                                </button>
                                <button type="button" onclick="saveCategory()" class="btn-primary text-white px-4 py-2 rounded-md text-sm font-medium">
                                    Save
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Type Modal -->
                <div id="typeModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                        <div class="fixed inset-0 transition-opacity modal-backdrop" onclick="closeTypeModal()"></div>
                        
                        <div class="relative inline-block w-full max-w-lg text-left align-middle transition-all transform bg-white shadow-xl rounded-lg">
                            <div class="flex justify-between items-center p-6 border-b">
                                <h3 class="text-lg font-medium text-gray-900">Add/Edit Type</h3>
                                <button onclick="closeTypeModal()" class="text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            
                            <form id="typeForm" class="p-6">
                                <input type="hidden" id="typeId">
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Type Name</label>
                                    <input type="text" id="typeName" class="form-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                </div>
                            </form>
                            
                            <div class="flex justify-end space-x-3 p-6 border-t bg-gray-50">
                                <button type="button" onclick="closeTypeModal()" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    Cancel
                                </button>
                                <button type="button" onclick="saveType()" class="btn-primary text-white px-4 py-2 rounded-md text-sm font-medium">
                                    Save
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="../js/checklist.js"></script>
</body>
</html>
