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
    <link rel="stylesheet" href="/css/output.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .bg-navy-700 {
            background-color: #1e3a8a;
        }

        .bg-navy-800 {
            background-color: #172554;
        }

        .bg-gold-400 {
            background-color: #f59e0b;
        }

        .bg-gold-50 {
            background-color: #fefce8;
        }

        .text-gold-400 {
            color: #f59e0b;
        }

        .border-gold-400 {
            border-color: #f59e0b;
        }

        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            transition: all 0.3s ease-in-out;
        }

        .modal {
            transition: opacity 0.3s ease-in-out;
            z-index: 50;
        }

        .modal.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .modal-content {
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
            border-radius: 0.75rem;
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .slide-in {
            animation: slideIn 0.5s ease-in-out;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-10px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @media (max-width: 640px) {
            .grid-cols-2 {
                grid-template-columns: 1fr;
            }

            .text-3xl {
                font-size: 2rem;
            }

            .px-8 {
                padding-left: 1rem;
                padding-right: 1rem;
            }
        }
    </style>
</head>

<body class="bg-gradient-to-br from-gray-50 to-gray-200 font-sans antialiased min-h-screen">
    <div id="toast-container" class="fixed top-5 right-5 z-50"></div>

    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <header class="mb-8">
            <h1 class="text-3xl lg:text-4xl font-bold text-gray-800 slide-in">Settings</h1>
            <p class="text-gray-600 mt-2">Manage your college, departments, and programs efficiently.</p>
        </header>

        <!-- College Settings -->
        <section class="bg-white p-6 lg:p-8 rounded-xl shadow-lg mb-8 fade-in">
            <h2 class="text-xl lg:text-2xl font-semibold text-gray-800 mb-6 border-b border-gray-200 pb-2">College Settings</h2>
            <form action="/dean/settings" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <div class="md:col-span-2">
                    <label for="college_name" class="block text-sm font-medium text-gray-700">College Name <span class="text-red-500">*</span></label>
                    <input type="text" id="college_name" name="college_name" value="<?php echo htmlspecialchars($college['college_name']); ?>" required maxlength="100" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gold-400 focus:ring focus:ring-gold-400/50">
                </div>
                <div class="md:col-span-2">
                    <label for="college_logo" class="block text-sm font-medium text-gray-700">College Logo</label>
                    <input type="file" id="college_logo" name="college_logo" accept="image/png,image/jpeg,image/gif" class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-gold-400 file:text-white hover:file:bg-gold-500">
                    <p class="mt-1 text-xs text-gray-500">Accepted formats: PNG, JPEG, GIF. Max size: 2MB.</p>
                </div>
                <div class="md:col-span-2 flex justify-end">
                    <button type="submit" name="update_settings" class="bg-gold-400 text-white px-6 py-2.5 rounded-md hover:bg-gold-500 transition duration-200 shadow-md">Update Settings</button>
                </div>
            </form>
            <?php if ($college['logo_path']): ?>
                <div class="mt-6">
                    <h4 class="text-lg font-medium text-gray-700 mb-2">Current Logo</h4>
                    <img src="<?php echo htmlspecialchars($college['logo_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="College Logo" class="h-32 w-auto object-contain border-2 border-gold-400 rounded-lg shadow-sm">
                </div>
            <?php endif; ?>
            <?php if ($success || $error): ?>
                <div class="mt-4 text-center">
                    <p class="text-sm <?php echo $success ? 'text-green-600' : 'text-red-600'; ?>"><?php echo $success ?: $error; ?></p>
                </div>
            <?php endif; ?>
        </section>

        <!-- Departments & Programs Management -->
        <section class="bg-white p-6 lg:p-8 rounded-xl shadow-lg fade-in">
            <div class="flex justify-between items-center mb-6 border-b border-gray-200 pb-2">
                <h2 class="text-xl lg:text-2xl font-semibold text-gray-800">Departments & Programs</h2>
                <button onclick="openModal('addDepartmentProgramModal')" class="bg-gold-400 text-white px-4 py-2 rounded-md hover:bg-gold-500 transition duration-200 shadow-md flex items-center"><i class="fas fa-plus mr-2"></i>Add Department & Program</button>
            </div>
            <?php if (empty($departments)): ?>
                <p class="text-gray-600 text-center py-4">No departments or programs found. Add a department with at least one program to get started.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Programs</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($departments as $dept): ?>
                                <tr class="hover:bg-gold-50 transition-colors duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($dept['department_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <?php
                                        $deptPrograms = array_filter($programs, fn($p) => $p['department_id'] == $dept['department_id']);
                                        echo implode(', ', array_map(fn($p) => htmlspecialchars($p['program_name']), $deptPrograms)) ?: 'No programs';
                                        ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button
                                            class="text-gold-400 hover:text-gold-600 mr-4 transition-colors edit-dept-btn"
                                            data-dept-id="<?php echo $dept['department_id']; ?>"
                                            data-dept-name="<?php echo htmlspecialchars($dept['department_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-programs="<?php echo htmlspecialchars(json_encode(array_column(array_filter($programs, fn($p) => $p['department_id'] == $dept['department_id']), 'program_id')), ENT_QUOTES, 'UTF-8'); ?>"
                                            data-program-names="<?php echo htmlspecialchars(json_encode(array_column(array_filter($programs, fn($p) => $p['department_id'] == $dept['department_id']), 'program_name')), ENT_QUOTES, 'UTF-8'); ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button
                                            class="text-red-600 hover:text-red-700 transition-colors delete-dept-btn"
                                            data-dept-id="<?php echo $dept['department_id']; ?>"
                                            data-dept-name="<?php echo htmlspecialchars($dept['department_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <!-- Add Department & Program Modal -->
        <div id="addDepartmentProgramModal" class="modal fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 modal-content transform scale-95">
                <div class="flex justify-between items-center p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Add Department & Program</h3>
                    <button onclick="closeModal('addDepartmentProgramModal')" class="text-gray-600 hover:text-gray-800 transition-colors"><i class="fas fa-times"></i></button>
                </div>
                <form action="/dean/settings" method="POST" class="p-6" id="addDepartmentProgramForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="mb-6">
                        <label for="new_department_name" class="block text-sm font-medium text-gray-700">Department Name <span class="text-red-500">*</span></label>
                        <input type="text" id="new_department_name" name="department_name" required maxlength="100" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gold-400 focus:ring focus:ring-gold-400/50">
                    </div>
                    <div id="programFields">
                        <div class="mb-6">
                            <label for="program_name_0" class="block text-sm font-medium text-gray-700">Program Name <span class="text-red-500">*</span></label>
                            <input type="text" id="program_name_0" name="program_names[]" required maxlength="100" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gold-400 focus:ring focus:ring-gold-400/50">
                        </div>
                    </div>
                    <button type="button" onclick="addProgramField()" class="mb-6 bg-gold-50 text-gold-400 px-4 py-2 rounded-md hover:bg-gold-100 transition-colors"><i class="fas fa-plus"></i> Add Another Program</button>
                    <div class="flex justify-end space-x-4">
                        <button type="button" onclick="closeModal('addDepartmentProgramModal')" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300 transition-colors">Cancel</button>
                        <button type="submit" name="add_department_program" class="bg-gold-400 text-white px-4 py-2.5 rounded-md hover:bg-gold-500 shadow-md transition-colors">Add</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Department & Program Modal -->
        <div id="editDepartmentProgramModal" class="modal fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 modal-content transform scale-95">
                <div class="flex justify-between items-center p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Edit Department & Programs</h3>
                    <button onclick="closeModal('editDepartmentProgramModal')" class="text-gray-600 hover:text-gray-800 transition-colors"><i class="fas fa-times"></i></button>
                </div>
                <form action="/dean/settings" method="POST" class="p-6" id="editDepartmentProgramForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="department_id" id="edit_department_id">
                    <div class="mb-6">
                        <label for="edit_department_name" class="block text-sm font-medium text-gray-700">Department Name <span class="text-red-500">*</span></label>
                        <input type="text" id="edit_department_name" name="department_name" required maxlength="100" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gold-400 focus:ring focus:ring-gold-400/50">
                    </div>
                    <div id="editProgramFields">
                        <!-- Program fields will be dynamically populated by JavaScript -->
                    </div>
                    <button type="button" onclick="addEditProgramField()" class="mb-6 bg-gold-50 text-gold-400 px-4 py-2 rounded-md hover:bg-gold-100 transition-colors"><i class="fas fa-plus"></i> Add Another Program</button>
                    <div class="flex justify-end space-x-4">
                        <button type="button" onclick="closeModal('editDepartmentProgramModal')" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300 transition-colors">Cancel</button>
                        <button type="submit" name="edit_department_program" class="bg-gold-400 text-white px-4 py-2.5 rounded-md hover:bg-gold-500 shadow-md transition-colors">Save</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Department Modal -->
        <div id="deleteDepartmentModal" class="modal fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 modal-content transform scale-95">
                <div class="flex justify-between items-center p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Delete Department</h3>
                    <button onclick="closeModal('deleteDepartmentModal')" class="text-gray-600 hover:text-gray-800 transition-colors"><i class="fas fa-times"></i></button>
                </div>
                <form action="/dean/settings" method="POST" class="p-6">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="department_id" id="delete_department_id">
                    <p class="text-gray-700 mb-6">Are you sure you want to delete <span id="delete_department_name" class="font-medium text-gray-900"></span>? This will also remove all associated programs. This action cannot be undone.</p>
                    <div class="flex justify-end space-x-4">
                        <button type="button" onclick="closeModal('deleteDepartmentModal')" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300 transition-colors">Cancel</button>
                        <button type="submit" name="delete_department" class="bg-red-600 text-white px-4 py-2.5 rounded-md hover:bg-red-700 shadow-md transition-colors">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Toast Notifications
            <?php if ($success): ?>
                showToast('<?php echo $success; ?>', 'bg-green-600');
            <?php endif; ?>
            <?php if ($error): ?>
                showToast('<?php echo $error; ?>', 'bg-red-600');
            <?php endif; ?>

            function showToast(message, bgColor) {
                const toast = document.createElement('div');
                toast.className = `toast ${bgColor} text-white px-5 py-3 rounded-lg shadow-lg mb-3`;
                toast.textContent = message;
                toast.setAttribute('role', 'alert');
                document.getElementById('toast-container').appendChild(toast);
                setTimeout(() => {
                    toast.style.opacity = '0';
                    setTimeout(() => toast.remove(), 300);
                }, 5000);
            }

            // Add event listeners for the buttons
            document.addEventListener('DOMContentLoaded', function() {
                // Edit button handlers
                document.querySelectorAll('.edit-dept-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const deptId = this.dataset.deptId;
                        const deptName = this.dataset.deptName;
                        const programs = JSON.parse(this.dataset.programs);
                        const programNames = JSON.parse(this.dataset.programNames);

                        openEditDepartmentProgramModal(deptId, deptName, programs, programNames);
                    });
                });

                // Delete button handlers
                document.querySelectorAll('.delete-dept-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const deptId = this.dataset.deptId;
                        const deptName = this.dataset.deptName;

                        openDeleteDepartmentModal(deptId, deptName);
                    });
                });
            });

            window.openModal = function(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) {
                    const modalContent = modal.querySelector('.modal-content');
                    modal.classList.remove('hidden');
                    modalContent.classList.remove('scale-95');
                    modalContent.classList.add('scale-100', 'shadow-2xl');
                    document.body.style.overflow = 'hidden';
                }
            };

            window.closeModal = function(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) {
                    const modalContent = modal.querySelector('.modal-content');
                    modalContent.classList.remove('scale-100', 'shadow-2xl');
                    modalContent.classList.add('scale-95');
                    setTimeout(() => {
                        modal.classList.add('hidden');
                        document.body.style.overflow = 'auto';
                        const form = modal.querySelector('form');
                        if (form) form.reset();
                        const programFields = document.getElementById('programFields');
                        if (programFields) {
                            programFields.innerHTML = '<div class="mb-6"><label for="program_name_0" class="block text-sm font-medium text-gray-700">Program Name <span class="text-red-500">*</span></label><input type="text" id="program_name_0" name="program_names[]" required maxlength="100" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gold-400 focus:ring focus:ring-gold-400/50"></div>';
                        }
                        const editProgramFields = document.getElementById('editProgramFields');
                        if (editProgramFields) editProgramFields.innerHTML = '';
                    }, 200);
                }
            };

            window.openEditDepartmentProgramModal = function(deptId, deptName, programIds, programNames) {
                document.getElementById('edit_department_id').value = deptId || '';
                document.getElementById('edit_department_name').value = deptName || '';
                const editProgramFields = document.getElementById('editProgramFields');
                if (editProgramFields) {
                    editProgramFields.innerHTML = '';
                    try {
                        const ids = Array.isArray(programIds) ? programIds : [];
                        const names = Array.isArray(programNames) ? programNames : [];
                        const maxLength = Math.max(ids.length, names.length);
                        for (let index = 0; index < maxLength; index++) {
                            const div = document.createElement('div');
                            div.className = 'mb-6 flex items-end gap-2';
                            const id = ids[index] || '';
                            const name = names[index] || '';
                            div.innerHTML = `<label for="edit_program_name_${index}" class="block text-sm font-medium text-gray-700">Program Name <span class="text-red-500">*</span></label><input type="text" id="edit_program_name_${index}" name="program_names[]" value="${name}" required maxlength="100" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gold-400 focus:ring focus:ring-gold-400/50"><input type="hidden" name="program_ids[]" value="${id}">`;
                            editProgramFields.appendChild(div);
                        }
                        openModal('editDepartmentProgramModal');
                    } catch (e) {
                        console.error('Error populating edit modal:', e);
                    }
                }
            };

            window.openDeleteDepartmentModal = function(id, name) {
                document.getElementById('delete_department_id').value = id || '';
                document.getElementById('delete_department_name').textContent = name || '';
                openModal('deleteDepartmentModal');
            };

            document.querySelectorAll('.modal').forEach(modal => {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) closeModal(modal.id);
                });
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    document.querySelectorAll('.modal').forEach(modal => {
                        if (!modal.classList.contains('hidden')) closeModal(modal.id);
                    });
                }
            });

            let programCount = 1;

            function addProgramField() {
                const programFields = document.getElementById('programFields');
                if (programFields) {
                    const div = document.createElement('div');
                    div.className = 'mb-6 flex items-end gap-2';
                    div.innerHTML = `<input type="text" name="program_names[]" required maxlength="100" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gold-400 focus:ring focus:ring-gold-400/50"><button type="button" onclick="this.parentElement.remove()" class="bg-red-600 text-white px-2 py-1 rounded-md hover:bg-red-700 transition-colors"><i class="fas fa-trash"></i></button>`;
                    programFields.appendChild(div);
                    programCount++;
                }
            }

            let editProgramCount = 0;

            function addEditProgramField() {
                const editProgramFields = document.getElementById('editProgramFields');
                if (editProgramFields) {
                    const div = document.createElement('div');
                    div.className = 'mb-6 flex items-end gap-2';
                    div.innerHTML = `<input type="text" name="program_names[]" required maxlength="100" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gold-400 focus:ring focus:ring-gold-400/50"><button type="button" onclick="this.parentElement.remove()" class="bg-red-600 text-white px-2 py-1 rounded-md hover:bg-red-700 transition-colors"><i class="fas fa-trash"></i></button>`;
                    editProgramFields.appendChild(div);
                    editProgramCount++;
                }
            }

            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', (e) => {
                    let isValid = true;
                    form.querySelectorAll('input[required], select[required]').forEach(input => {
                        if (!input.value.trim()) {
                            input.classList.add('border-red-500');
                            isValid = false;
                        } else {
                            input.classList.remove('border-red-500');
                        }
                    });
                    if (form.id === 'addDepartmentProgramForm' && document.querySelectorAll('#programFields input[required]').length === 0) {
                        isValid = false;
                        alert('At least one program is required for the department.');
                    }
                    if (!isValid) e.preventDefault();
                });

                form.querySelectorAll('input[required], select[required]').forEach(input => {
                    input.addEventListener('input', () => {
                        if (input.value.trim()) input.classList.remove('border-red-500');
                    });
                });
            });
        });
    </script>

</body>

</html>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>