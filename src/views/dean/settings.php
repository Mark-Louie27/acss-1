<?php
ob_start();

// Fetch current college details
$collegeId = $controller->getDeanCollegeId($_SESSION['user_id']);
$query = "SELECT college_name, logo_path FROM colleges WHERE college_id = :college_id";
$stmt = $controller->db->prepare($query);
$stmt->execute([':college_id' => $collegeId]);
$college = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['college_name' => '', 'logo_path' => null];

// Initialize departments and programs if not set
$departments = $departments ?? [];
$programs = $programs ?? [];

// Check for success/error messages
$success = isset($success) ? htmlspecialchars($success, ENT_QUOTES, 'UTF-8') : null;
$error = isset($error) ? htmlspecialchars($error, ENT_QUOTES, 'UTF-8') : null;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | ACSS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <link rel="stylesheet" href="/css/settings.css">
</head>

<body class="bg-gray-50 font-sans antialiased min-h-screen">
    <!-- Toast Container -->
    <div id="toast-container"></div>

    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <!-- College Settings Section -->
        <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 mb-8 fade-in">
            <div class="border-b border-gray-200 pb-4 mb-6">
                <h2 class="text-2xl font-bold text-dark-gray">College Settings</h2>
                <p class="text-gray-600 mt-2">Manage your college information and branding</p>
            </div>

            <form action="/dean/settings" method="POST" enctype="multipart/form-data" id="settingsForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div class="lg:col-span-2">
                        <label for="college_name" class="block text-sm font-semibold text-dark-gray mb-2">
                            College Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                            id="college_name"
                            name="college_name"
                            value="<?php echo htmlspecialchars($college['college_name']); ?>"
                            required
                            maxlength="100"
                            class="form-input">
                    </div>

                    <div class="lg:col-span-2">
                        <label for="college_logo" class="block text-sm font-semibold text-dark-gray mb-2">
                            College Logo
                        </label>
                        <input type="file"
                            id="college_logo"
                            name="college_logo"
                            accept="image/png,image/jpeg,image/gif"
                            class="form-input"
                            onchange="previewImage(event)">
                        <p class="mt-2 text-xs text-gray-500">
                            Accepted formats: PNG, JPEG, GIF. Maximum file size: 2MB
                        </p>

                        <!-- Image Preview -->
                        <div id="imagePreview" class="image-preview mt-6" style="display: none;">
                            <h4 class="text-sm font-semibold text-dark-gray mb-3">Logo Preview</h4>
                            <div class="bg-white border-2 border-dashed border-primary-yellow rounded-lg p-6 text-center">
                                <img id="previewImage"
                                    src=""
                                    alt="Logo Preview"
                                    class="max-h-32 w-auto object-contain mx-auto">
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-2 flex justify-end pt-4">
                        <button type="submit" name="update_settings" class="btn-primary">
                            <i class="fas fa-save"></i>
                            Update Settings
                        </button>
                    </div>
                </div>
            </form>

            <!-- Current Logo Display -->
            <?php if ($college['logo_path']): ?>
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <h4 class="text-sm font-semibold text-dark-gray mb-3">Current Logo</h4>
                    <div class="bg-white border border-gray-200 rounded-lg p-6 text-center">
                        <img src="<?php echo htmlspecialchars($college['logo_path'], ENT_QUOTES, 'UTF-8'); ?>"
                            alt="College Logo"
                            class="max-h-32 w-auto object-contain mx-auto">
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <!-- Departments & Programs Section -->
        <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 fade-in">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center border-b border-gray-200 pb-4 mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-dark-gray">Departments & Programs</h2>
                    <p class="text-gray-600 mt-2">Manage academic departments and their programs</p>
                </div>
                <button onclick="openModal('addDepartmentProgramModal')" class="btn-primary mt-4 sm:mt-0">
                    <i class="fas fa-plus"></i>
                    Add Department & Program
                </button>
            </div>

            <?php if (empty($departments)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-university text-4xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500 text-lg">No departments found</p>
                    <p class="text-gray-400 text-sm">Create your first department with at least one program to get started</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-6 py-4 text-left text-sm font-semibold text-dark-gray">Department Name</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-dark-gray">Programs</th>
                                <th class="px-6 py-4 text-right text-sm font-semibold text-dark-gray">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($departments as $dept): ?>
                                <tr class="table-row">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-dark-gray">
                                            <?php echo htmlspecialchars($dept['department_name'], ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-600">
                                            <?php
                                            $deptPrograms = array_filter($programs, fn($p) => $p['department_id'] == $dept['department_id']);
                                            $programDetails = array_map(fn($p) => htmlspecialchars("{$p['program_code']} - {$p['program_name']}"), $deptPrograms);
                                            echo !empty($programDetails) ? implode(', ', $programDetails) : 'No programs';
                                            ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end space-x-3">
                                            <button class="text-primary-yellow hover:text-primary-yellow-dark transition-colors edit-dept-btn"
                                                data-dept-id="<?php echo $dept['department_id']; ?>"
                                                data-dept-name="<?php echo htmlspecialchars($dept['department_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                                data-programs="<?php echo htmlspecialchars(json_encode(array_column(array_filter($programs, fn($p) => $p['department_id'] == $dept['department_id']), 'program_id')), ENT_QUOTES, 'UTF-8'); ?>"
                                                data-program-codes="<?php echo htmlspecialchars(json_encode(array_column(array_filter($programs, fn($p) => $p['department_id'] == $dept['department_id']), 'program_code')), ENT_QUOTES, 'UTF-8'); ?>"
                                                data-program-names="<?php echo htmlspecialchars(json_encode(array_column(array_filter($programs, fn($p) => $p['department_id'] == $dept['department_id']), 'program_name')), ENT_QUOTES, 'UTF-8'); ?>"
                                                title="Edit Department">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="text-red-500 hover:text-red-700 transition-colors delete-dept-btn"
                                                data-dept-id="<?php echo $dept['department_id']; ?>"
                                                data-dept-name="<?php echo htmlspecialchars($dept['department_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                                title="Delete Department">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- Add Department & Program Modal -->
    <div id="addDepartmentProgramModal" class="modal-overlay">
        <div class="modal-content">
            <div class="flex justify-between items-center p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-dark-gray">Add New Department & Program</h3>
                <button onclick="closeModal('addDepartmentProgramModal')"
                    class="text-gray-500 hover:text-gray-700 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form action="/dean/settings" method="POST" class="p-6" id="addDepartmentProgramForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

                <div class="mb-6">
                    <label for="new_department_name" class="block text-sm font-semibold text-dark-gray mb-2">
                        Department Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                        id="new_department_name"
                        name="department_name"
                        required
                        maxlength="100"
                        class="form-input">
                </div>

                <div id="programFields">
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-dark-gray mb-2">
                            Program Details <span class="text-red-500">*</span>
                        </label>
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <input type="text"
                                    name="program_code[]"
                                    required
                                    maxlength="100"
                                    placeholder="Program Code"
                                    class="form-input">
                            </div>
                            <div class="flex-1">
                                <input type="text"
                                    name="program_names[]"
                                    required
                                    maxlength="100"
                                    placeholder="Program Name"
                                    class="form-input">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200">
                    <button type="button"
                        onclick="closeModal('addDepartmentProgramModal')"
                        class="btn-secondary">
                        Cancel
                    </button>
                    <button type="submit"
                        name="add_department_program"
                        class="btn-primary">
                        <i class="fas fa-check"></i>
                        Create Department
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Department & Program Modal -->
    <div id="editDepartmentProgramModal" class="modal-overlay">
        <div class="modal-content">
            <div class="flex justify-between items-center p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-dark-gray">Edit Department & Programs</h3>
                <button onclick="closeModal('editDepartmentProgramModal')"
                    class="text-gray-500 hover:text-gray-700 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form action="/dean/settings" method="POST" class="p-6" id="editDepartmentProgramForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="department_id" id="edit_department_id">

                <div class="mb-6">
                    <label for="edit_department_name" class="block text-sm font-semibold text-dark-gray mb-2">
                        Department Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                        id="edit_department_name"
                        name="department_name"
                        required
                        maxlength="100"
                        class="form-input">
                </div>

                <div id="editProgramFields">
                    <!-- Program fields will be dynamically populated -->
                </div>

                <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200">
                    <button type="button"
                        onclick="closeModal('editDepartmentProgramModal')"
                        class="btn-secondary">
                        Cancel
                    </button>
                    <button type="submit"
                        name="edit_department_program"
                        class="btn-primary">
                        <i class="fas fa-save"></i>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Department Modal -->
    <div id="deleteDepartmentModal" class="modal-overlay">
        <div class="modal-content">
            <div class="flex justify-between items-center p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-dark-gray">Delete Department</h3>
                <button onclick="closeModal('deleteDepartmentModal')"
                    class="text-gray-500 hover:text-gray-700 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form action="/dean/settings" method="POST" class="p-6">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="department_id" id="delete_department_id">

                <div class="mb-6">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle text-red-500 mr-3"></i>
                            <div>
                                <p class="text-sm font-medium text-red-800">Warning: This action cannot be undone</p>
                                <p class="text-sm text-red-600 mt-1">All associated programs will also be deleted.</p>
                            </div>
                        </div>
                    </div>
                    <p class="text-gray-700">
                        Are you sure you want to delete
                        <span id="delete_department_name" class="font-semibold text-dark-gray"></span>?
                    </p>
                </div>

                <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200">
                    <button type="button"
                        onclick="closeModal('deleteDepartmentModal')"
                        class="btn-secondary">
                        Cancel
                    </button>
                    <button type="submit"
                        name="delete_department"
                        class="btn-danger">
                        <i class="fas fa-trash"></i>
                        Delete Department
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Show toast notifications
            <?php if ($success): ?>
                showToast('<?php echo addslashes($success); ?>', 'success');
            <?php endif; ?>
            <?php if ($error): ?>
                showToast('<?php echo addslashes($error); ?>', 'error');
            <?php endif; ?>

            // Initialize event listeners
            initializeEventListeners();
        });

        // Toast notification system
        function showToast(message, type) {
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} mr-3"></i>
                    <span>${message}</span>
                </div>
            `;

            document.getElementById('toast-container').appendChild(toast);

            setTimeout(() => {
                toast.style.animation = 'slideOutRight 0.3s ease-in';
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }

        // Modal management
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = 'auto';

                // Reset form if exists
                const form = modal.querySelector('form');
                if (form) {
                    form.reset();
                    clearValidationErrors(form);
                }

                // Reset dynamic program fields
                resetProgramFields();
            }
        }

        // Image preview functionality
        function previewImage(event) {
            const file = event.target.files[0];
            const previewContainer = document.getElementById('imagePreview');
            const previewImage = document.getElementById('previewImage');

            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewContainer.style.display = 'block';
                    setTimeout(() => {
                        previewContainer.classList.add('active');
                    }, 10);
                };
                reader.readAsDataURL(file);
            } else {
                previewContainer.style.display = 'none';
                previewContainer.classList.remove('active');
                previewImage.src = '';
            }
        }

        // Program field management
        let programFieldCount = 1;

        function addProgramField() {
            const programFields = document.getElementById('programFields');
            const fieldDiv = document.createElement('div');
            fieldDiv.className = 'mb-4 program-field';
            fieldDiv.innerHTML = `
                <label class="block text-sm font-semibold text-dark-gray mb-2">
                    Program Details <span class="text-red-500">*</span>
                </label>
                <div class="flex gap-2">
                    <div class="flex-1">
                        <input type="text" 
                               name="program_code[]" 
                               required 
                               maxlength="100" 
                               placeholder="Program Code"
                               class="form-input">
                    </div>
                    <div class="flex-1">
                        <input type="text" 
                               name="program_names[]" 
                               required 
                               maxlength="100" 
                               placeholder="Program Name"
                               class="form-input">
                    </div>
                    <button type="button" 
                            onclick="removeProgramField(this)" 
                            class="btn-danger px-3">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            programFields.appendChild(fieldDiv);
            programFieldCount++;
        }

        function addEditProgramField() {
            const editProgramFields = document.getElementById('editProgramFields');
            const fieldDiv = document.createElement('div');
            fieldDiv.className = 'mb-4 program-field';
            fieldDiv.innerHTML = `
                <label class="block text-sm font-semibold text-dark-gray mb-2">
                    Program Details <span class="text-red-500">*</span>
                </label>
                <div class="flex gap-2">
                    <div class="flex-1">
                        <input type="text" 
                               name="program_code[]" 
                               required 
                               maxlength="100" 
                               placeholder="Program Code"
                               class="form-input">
                        <input type="hidden" name="program_ids[]" value="">
                    </div>
                    <div class="flex-1">
                        <input type="text" 
                               name="program_names[]" 
                               required 
                               maxlength="100" 
                               placeholder="Program Name"
                               class="form-input">
                        <input type="hidden" name="program_ids[]" value="">
                    </div>
                    <button type="button" 
                            onclick="removeProgramField(this)" 
                            class="btn-danger px-3">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            editProgramFields.appendChild(fieldDiv);
        }

        function removeProgramField(button) {
            const fieldDiv = button.closest('.program-field');
            fieldDiv.remove();
        }

        function resetProgramFields() {
            const programFields = document.getElementById('programFields');
            const editProgramFields = document.getElementById('editProgramFields');

            if (programFields) {
                programFields.innerHTML = `
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-dark-gray mb-2">
                            Program Details <span class="text-red-500">*</span>
                        </label>
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <input type="text" 
                                       name="program_code[]" 
                                       required 
                                       maxlength="100" 
                                       placeholder="Program Code"
                                       class="form-input">
                            </div>
                            <div class="flex-1">
                                <input type="text" 
                                       name="program_names[]" 
                                       required 
                                       maxlength="100" 
                                       placeholder="Program Name"
                                       class="form-input">
                            </div>
                        </div>
                    </div>
                `;
            }

            if (editProgramFields) {
                editProgramFields.innerHTML = '';
            }

            programFieldCount = 1;
        }

        // Department modal functions
        function openEditDepartmentProgramModal(deptId, deptName, programIds, programCodes, programNames) {
            document.getElementById('edit_department_id').value = deptId || '';
            document.getElementById('edit_department_name').value = deptName || '';

            const editProgramFields = document.getElementById('editProgramFields');
            editProgramFields.innerHTML = '';

            try {
                const ids = Array.isArray(programIds) ? programIds : [];
                const codes = Array.isArray(programCodes) ? programCodes : [];
                const names = Array.isArray(programNames) ? programNames : [];
                const maxLength = Math.max(ids.length, codes.length, names.length);

                for (let i = 0; i < maxLength; i++) {
                    const fieldDiv = document.createElement('div');
                    fieldDiv.className = 'mb-4 program-field';
                    fieldDiv.innerHTML = `
                        <label class="block text-sm font-semibold text-dark-gray mb-2">
                            Program Details <span class="text-red-500">*</span>
                        </label>
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <input type="text" 
                                       name="program_code[]" 
                                       value="${codes[i] || ''}" 
                                       required 
                                       maxlength="100" 
                                       class="form-input">
                                <input type="hidden" name="program_ids[]" value="${ids[i] || ''}">
                            </div>
                            <div class="flex-1">
                                <input type="text" 
                                       name="program_names[]" 
                                       value="${names[i] || ''}" 
                                       required 
                                       maxlength="100" 
                                       class="form-input">
                                <input type="hidden" name="program_ids[]" value="${ids[i] || ''}">
                            </div>
                            ${i > 0 ? `
                                <button type="button" 
                                        onclick="removeProgramField(this)" 
                                        class="btn-danger px-3">
                                    <i class="fas fa-trash"></i>
                                </button>
                            ` : ''}
                        </div>
                    `;
                    editProgramFields.appendChild(fieldDiv);
                }

                openModal('editDepartmentProgramModal');
            } catch (error) {
                console.error('Error populating edit modal:', error);
                showToast('Error loading department data', 'error');
            }
        }

        function openDeleteDepartmentModal(deptId, deptName) {
            document.getElementById('delete_department_id').value = deptId || '';
            document.getElementById('delete_department_name').textContent = deptName || '';
            openModal('deleteDepartmentModal');
        }

        // Form validation
        function validateForm(form) {
            let isValid = true;
            const requiredFields = form.querySelectorAll('input[required], select[required]');

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('error');
                    isValid = false;
                } else {
                    field.classList.remove('error');
                }
            });

            return isValid;
        }

        function clearValidationErrors(form) {
            const errorFields = form.querySelectorAll('.form-input.error');
            errorFields.forEach(field => {
                field.classList.remove('error');
            });
        }

        // Event listeners
        function initializeEventListeners() {
            // Modal close on overlay click
            document.querySelectorAll('.modal-overlay').forEach(modal => {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeModal(modal.id);
                    }
                });
            });

            // Keyboard navigation
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const activeModals = document.querySelectorAll('.modal-overlay.active');
                    activeModals.forEach(modal => {
                        closeModal(modal.id);
                    });
                }
            });

            // Edit department buttons
            document.querySelectorAll('.edit-dept-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const deptId = this.dataset.deptId;
                    const deptName = this.dataset.deptName;
                    const programs = JSON.parse(this.dataset.programs || '[]');
                    const programCodes = JSON.parse(this.dataset.programCodes || '[]');
                    const programNames = JSON.parse(this.dataset.programNames || '[]');
                    openEditDepartmentProgramModal(deptId, deptName, programs, programCodes, programNames);
                });
            });

            // Delete department buttons
            document.querySelectorAll('.delete-dept-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const deptId = this.dataset.deptId;
                    const deptName = this.dataset.deptName;
                    openDeleteDepartmentModal(deptId, deptName);
                });
            });

            // Form submissions with validation
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (!validateForm(form)) {
                        e.preventDefault();
                        showToast('Please fill in all required fields', 'error');
                    }
                });

                // Remove error styling on input
                form.querySelectorAll('.form-input').forEach(input => {
                    input.addEventListener('input', function() {
                        if (this.classList.contains('error') && this.value.trim()) {
                            this.classList.remove('error');
                        }
                    });
                });
            });

            // File input validation
            const logoInput = document.getElementById('college_logo');
            if (logoInput) {
                logoInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        // Check file size (2MB limit)
                        if (file.size > 2 * 1024 * 1024) {
                            showToast('File size must be less than 2MB', 'error');
                            this.value = '';
                            return;
                        }

                        // Check file type
                        const allowedTypes = ['image/png', 'image/jpeg', 'image/gif'];
                        if (!allowedTypes.includes(file.type)) {
                            showToast('Please select a valid image file (PNG, JPEG, or GIF)', 'error');
                            this.value = '';
                            return;
                        }
                    }
                });
            }
        }

        // Global functions for inline event handlers
        window.openModal = openModal;
        window.closeModal = closeModal;
        window.previewImage = previewImage;
        window.addProgramField = addProgramField;
        window.addEditProgramField = addEditProgramField;
        window.removeProgramField = removeProgramField;
        window.openEditDepartmentProgramModal = openEditDepartmentProgramModal;
        window.openDeleteDepartmentModal = openDeleteDepartmentModal;
    </script>
</body>

</html>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>