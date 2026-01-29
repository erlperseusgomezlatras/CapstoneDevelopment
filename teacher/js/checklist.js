$(document).ready(function() {
    // Load initial data
    loadCategories();
    loadTypes();
    loadCriteria();
    
    // Setup event listeners
    setupEventListeners();
});

function setupEventListeners() {
    // Initialize Select2
    $('#categorySelect, #typeSelect').select2({
        width: '100%'
    });
    
    // Category form events
    $('#hasType').on('change', function() {
        // This will be handled when saving the category
    });
    
    // Criteria form events
    $('#categorySelect').on('change', function() {
        const categoryId = $(this).val();
        const category = categories.find(c => c.id == categoryId);
        
        if (category && (category.is_type === '1' || category.is_type === 1)) {
            // Show type selection and load types
            $('#typeGroup').show();
            loadTypesForCriteria();
            // Set default points to 1 for categories with types
            $('#criteriaPoints').val(1);
        } else {
            // Hide type selection
            $('#typeGroup').hide();
            $('#typeSelect').val('').trigger('change');
            // Set default points to 3 for categories without types
            $('#criteriaPoints').val(3);
        }
    });
}

function switchTab(tabName) {
    // Hide all tab contents
    $('.tab-content').removeClass('active');
    
    // Remove active class from all desktop tab buttons
    $('.tab-button').removeClass('active text-green-700').addClass('text-gray-500');
    
    // Remove active class from all mobile tab buttons
    $('.mobile-tab-button').removeClass('active text-green-700 border-green-700').addClass('text-gray-500 border-transparent');
    
    // Show selected tab content
    $('#' + tabName).addClass('active');
    
    // Activate selected desktop tab button
    $(`.tab-button[onclick="switchTab('${tabName}')"]`).addClass('active').removeClass('text-gray-500').addClass('text-green-700');
    
    // Activate selected mobile tab button
    $(`.mobile-tab-button[onclick="switchTab('${tabName}')"]`).addClass('active').removeClass('text-gray-500 border-transparent').addClass('text-green-700 border-green-700');
}

// Modal functions
function openCriteriaModal() {
    $('#criteriaModal').removeClass('hidden');
}

function closeCriteriaModal() {
    $('#criteriaModal').addClass('hidden');
    $('#criteriaForm')[0].reset();
    $('#criteriaId').val('');
    $('#typeGroup').hide();
}

function openCategoryModal() {
    $('#categoryModal').removeClass('hidden');
}

function closeCategoryModal() {
    $('#categoryModal').addClass('hidden');
    $('#categoryForm')[0].reset();
    $('#categoryId').val('');
}

function openTypeModal() {
    $('#typeModal').removeClass('hidden');
}

function closeTypeModal() {
    $('#typeModal').addClass('hidden');
    $('#typeForm')[0].reset();
    $('#typeId').val('');
}

let categories = [];
let types = [];

// Category Functions
function loadCategories() {
    axios.post(`${window.APP_CONFIG.API_BASE_URL}checklist.php`, {
        action: 'getCategories'
    })
    .then(function(response) {
        if (response.data.success) {
            categories = response.data.data;
            displayCategories();
            updateCategorySelect();
        } else {
            showAlert('Error loading categories', 'danger');
        }
    })
    .catch(function(error) {
        console.error('Error loading categories:', error);
        showAlert('Error loading categories', 'danger');
    });
}

function displayCategories() {
    const container = document.getElementById('categoryList');
    container.innerHTML = '';
    
    if (categories.length === 0) {
        container.innerHTML = '<div class="col-span-full"><p class="text-gray-500 text-center py-8">No categories found. Add your first category!</p></div>';
        return;
    }
    
    categories.forEach(category => {
        const card = document.createElement('div');
        card.className = 'category-card bg-white rounded-lg p-4';
        card.innerHTML = `
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <h3 class="font-semibold text-gray-900 mb-2">${category.category_name}</h3>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${category.is_type === '1' || category.is_type === 1 ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'}">
                        ${category.is_type === '1' || category.is_type === 1 ? 'Has Types' : 'No Types'}
                    </span>
                </div>
                <div class="flex space-x-2 ml-4">
                    <button onclick="editCategory(${category.id})" class="text-blue-600 hover:text-blue-800 transition-colors">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="deleteCategory(${category.id})" class="text-red-600 hover:text-red-800 transition-colors">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        container.appendChild(card);
    });
}

function updateCategorySelect() {
    const select = $('#categorySelect');
    select.html('<option value="">Select Category</option>');
    
    categories.forEach(category => {
        const option = $('<option></option>').val(category.id).text(category.category_name);
        select.append(option);
    });
    
    // Reinitialize Select2 to update options
    select.trigger('change.select2');
}

function saveCategory() {
    const id = $('#categoryId').val();
    const name = $('#categoryName').val();
    const hasType = $('#hasType').is(':checked') ? 1 : 0;
    
    if (!name.trim()) {
        showAlert('Please enter a category name', 'warning');
        return;
    }
    
    const requestData = {
        action: id ? 'updateCategory' : 'addCategory',
        ...(id && { id: parseInt(id) }),
        name: name,
        is_type: hasType
    };
    
    axios.post(`${window.APP_CONFIG.API_BASE_URL}checklist.php`, requestData)
        .then(function(response) {
            if (response.data.success) {
                showAlert(response.data.message, 'success');
                closeCategoryModal();
                loadCategories();
            } else {
                showAlert(response.data.message, 'danger');
            }
        })
        .catch(function(error) {
            console.error('Error saving category:', error);
            showAlert('Error saving category', 'danger');
        });
}

function editCategory(id) {
    const category = categories.find(c => c.id == id);
    if (category) {
        document.getElementById('categoryId').value = category.id;
        document.getElementById('categoryName').value = category.category_name;
        document.getElementById('hasType').checked = category.is_type === '1' || category.is_type === 1;
        
        openCategoryModal();
    }
}

function deleteCategory(id) {
    if (!confirm('Are you sure you want to delete this category? This will also delete all associated criteria.')) {
        return;
    }
    
    axios.post(`${window.APP_CONFIG.API_BASE_URL}checklist.php`, {
        action: 'deleteCategory',
        id: parseInt(id)
    })
    .then(function(response) {
        if (response.data.success) {
            showAlert(response.data.message, 'success');
            loadCategories();
            loadCriteria();
        } else {
            showAlert(response.data.message, 'danger');
        }
    })
    .catch(function(error) {
        console.error('Error deleting category:', error);
        showAlert('Error deleting category', 'danger');
    });
}

// Type Functions
function loadTypes() {
    axios.post(`${window.APP_CONFIG.API_BASE_URL}checklist.php`, {
        action: 'getTypes'
    })
    .then(function(response) {
        if (response.data.success) {
            types = response.data.data;
            displayTypes();
        } else {
            showAlert('Error loading types', 'danger');
        }
    })
    .catch(function(error) {
        console.error('Error loading types:', error);
        showAlert('Error loading types', 'danger');
    });
}

function loadTypesForCriteria() {
    const select = $('#typeSelect');
    select.html('<option value="">Select Type</option>');
    
    types.forEach(type => {
        const option = $('<option></option>').val(type.id).text(type.type_name);
        select.append(option);
    });
    
    // Reinitialize Select2 to update options
    select.trigger('change.select2');
}

function displayTypes() {
    const container = document.getElementById('typeList');
    container.innerHTML = '';
    
    if (types.length === 0) {
        container.innerHTML = '<div class="col-span-full"><p class="text-gray-500 text-center py-8">No types found. Add your first type!</p></div>';
        return;
    }
    
    types.forEach(type => {
        const card = document.createElement('div');
        card.className = 'type-card bg-white rounded-lg p-4';
        card.innerHTML = `
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <h3 class="font-semibold text-gray-900">${type.type_name}</h3>
                </div>
                <div class="flex space-x-2 ml-4">
                    <button onclick="editType(${type.id})" class="text-blue-600 hover:text-blue-800 transition-colors">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="deleteType(${type.id})" class="text-red-600 hover:text-red-800 transition-colors">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        container.appendChild(card);
    });
}

function saveType() {
    const id = $('#typeId').val();
    const name = $('#typeName').val();
    
    if (!name.trim()) {
        showAlert('Please enter a type name', 'warning');
        return;
    }
    
    const requestData = {
        action: id ? 'updateType' : 'addType',
        ...(id && { id: parseInt(id) }),
        name: name
    };
    
    axios.post(`${window.APP_CONFIG.API_BASE_URL}checklist.php`, requestData)
        .then(function(response) {
            if (response.data.success) {
                showAlert(response.data.message, 'success');
                closeTypeModal();
                loadTypes();
            } else {
                showAlert(response.data.message, 'danger');
            }
        })
        .catch(function(error) {
            console.error('Error saving type:', error);
            showAlert('Error saving type', 'danger');
        });
}

function editType(id) {
    const type = types.find(t => t.id == id);
    if (type) {
        document.getElementById('typeId').value = type.id;
        document.getElementById('typeName').value = type.type_name;
        
        openTypeModal();
    }
}

function deleteType(id) {
    if (!confirm('Are you sure you want to delete this type?')) {
        return;
    }
    
    axios.post(`${window.APP_CONFIG.API_BASE_URL}checklist.php`, {
        action: 'deleteType',
        id: parseInt(id)
    })
    .then(function(response) {
        if (response.data.success) {
            showAlert(response.data.message, 'success');
            loadTypes();
        } else {
            showAlert(response.data.message, 'danger');
        }
    })
    .catch(function(error) {
        console.error('Error deleting type:', error);
        showAlert('Error deleting type', 'danger');
    });
}

// Criteria Functions
let criteriaList = [];

function loadCriteria() {
    axios.post(`${window.APP_CONFIG.API_BASE_URL}checklist.php`, {
        action: 'getCriteria'
    })
    .then(function(response) {
        if (response.data.success) {
            criteriaList = response.data.data;
            displayCriteria();
        } else {
            showAlert('Error loading criteria', 'danger');
        }
    })
    .catch(function(error) {
        console.error('Error loading criteria:', error);
        showAlert('Error loading criteria', 'danger');
    });
}

function displayCriteria() {
    const container = document.getElementById('criteriaList');
    container.innerHTML = '';
    
    if (criteriaList.length === 0) {
        container.innerHTML = '<div class="col-span-full"><p class="text-gray-500 text-center py-8">No criteria found. Add your first criteria!</p></div>';
        return;
    }
    
    criteriaList.forEach(criteria => {
        const card = document.createElement('div');
        card.className = 'criteria-card bg-white rounded-lg p-4';
        
        const category = categories.find(c => c.id == criteria.category_id);
        const type = criteria.type_id ? types.find(t => t.id == criteria.type_id) : null;
        
        card.innerHTML = `
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <h3 class="font-semibold text-gray-900 mb-2">${criteria.checklist_criteria}</h3>
                    <div class="flex flex-wrap gap-2 mb-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            ${category ? category.category_name : 'Unknown'}
                        </span>
                        ${type ? `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">${type.type_name}</span>` : ''}
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                        Points: ${criteria.points}
                    </span>
                </div>
                <div class="flex space-x-2 ml-4">
                    <button onclick="editCriteria(${criteria.id})" class="text-blue-600 hover:text-blue-800 transition-colors">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="deleteCriteria(${criteria.id})" class="text-red-600 hover:text-red-800 transition-colors">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        container.appendChild(card);
    });
}

function saveCriteria() {
    const id = $('#criteriaId').val();
    const categoryId = $('#categorySelect').val();
    const typeId = $('#typeSelect').val() || null;
    const name = $('#criteriaName').val();
    const points = $('#criteriaPoints').val();
    
    if (!categoryId || !name.trim() || !points) {
        showAlert('Please fill all required fields', 'warning');
        return;
    }
    
    const requestData = {
        action: id ? 'updateCriteria' : 'addCriteria',
        ...(id && { id: parseInt(id) }),
        category_id: parseInt(categoryId),
        ...(typeId && { type_id: parseInt(typeId) }),
        name: name,
        points: parseInt(points)
    };
    
    axios.post(`${window.APP_CONFIG.API_BASE_URL}checklist.php`, requestData)
        .then(function(response) {
            if (response.data.success) {
                showAlert(response.data.message, 'success');
                closeCriteriaModal();
                loadCriteria();
            } else {
                showAlert(response.data.message, 'danger');
            }
        })
        .catch(function(error) {
            console.error('Error saving criteria:', error);
            showAlert('Error saving criteria', 'danger');
        });
}

function editCriteria(id) {
    const criteria = criteriaList.find(c => c.id == id);
    if (criteria) {
        document.getElementById('criteriaId').value = criteria.id;
        document.getElementById('categorySelect').value = criteria.category_id;
        document.getElementById('criteriaName').value = criteria.checklist_criteria;
        document.getElementById('criteriaPoints').value = criteria.points;
        
        // Trigger category change to show/hide type field
        document.getElementById('categorySelect').dispatchEvent(new Event('change'));
        
        // Set type if exists
        if (criteria.type_id) {
            setTimeout(() => {
                document.getElementById('typeSelect').value = criteria.type_id;
            }, 100);
        }
        
        openCriteriaModal();
    }
}

function deleteCriteria(id) {
    if (!confirm('Are you sure you want to delete this criteria?')) {
        return;
    }
    
    axios.post(`${window.APP_CONFIG.API_BASE_URL}checklist.php`, {
        action: 'deleteCriteria',
        id: parseInt(id)
    })
    .then(function(response) {
        if (response.data.success) {
            showAlert(response.data.message, 'success');
            loadCriteria();
        } else {
            showAlert(response.data.message, 'danger');
        }
    })
    .catch(function(error) {
        console.error('Error deleting criteria:', error);
        showAlert('Error deleting criteria', 'danger');
    });
}

// Utility Functions
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.parentNode.removeChild(alertDiv);
        }
    }, 5000);
}