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
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
    <style>
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .modal {
            transition: opacity 0.3s ease;
        }

        .modal.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .modal-content {
            transition: transform 0.3s ease;
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .slide-in-left {
            animation: slideInLeft 0.5s ease-in;
        }

        @keyframes slideInLeft {
            from {
                transform: translateX(-20px);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</head>

<body class="bg-gray-100 font-sans antialiased">
    <div id="toast-container" class="fixed top-5 right-5 z-50"></div>

    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <h2 class="text-3xl font-bold text-gray-800 mb-6 slide-in-left">Settings</h2>

        <!-- College Settings -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-8 fade-in">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">College Settings</h3>
            <form action="/dean/settings" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <div class="md:col-span-2">
                    <label for="college_name" class="block text-sm font-medium text-gray-700">College Name <span class="text-red-500">*</span></label>
                    <input type="text" id="college_name" name="college_name" value="<?php echo htmlspecialchars($college['college_name']); ?>" required maxlength="100" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-yellow-500 focus:ring focus:ring-yellow-500 focus:ring-opacity-50">
                </div>
                <div class="md:col-span-2">
                    <label for="college_logo" class="block text-sm font-medium text-gray-700">College Logo</label>
                    <input type="file" id="college_logo" name="college_logo" accept="image/png,image/jpeg,image/gif" class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-yellow-500 file:text-white hover:file:bg-yellow-600">
                    <p class="mt-1 text-xs text-gray-500">Accepted formats: PNG, JPEG, GIF. Max size: 2MB.</p>
                </div>
                <div class="md:col-span-2 flex justify-end">
                    <button type="submit" name="update_settings" class="bg-yellow-500 text-white px-6 py-2 rounded-md hover:bg-yellow-600 transition duration-200">Update Settings</button>
                </div>
            </form>
            <?php if ($college['logo_path']): ?>
                <div class="mt-6">
                    <h4 class="text-lg font-medium text-gray-700 mb-2">Current Logo</h4>
                    <img src="<?php echo htmlspecialchars($college['logo_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="College Logo" class="h-32 w-auto object-contain border-2 border-yellow-500 rounded-lg">
                </div>
            <?php endif; ?>
        </div>

        <!-- Departments Management -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-8 fade-in">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-gray-800">Departments</h3>
                <button onclick="openModal('addDepartmentModal')" class="bg-yellow-500 text-white px-4 py-2 rounded-md hover:bg-yellow-600 transition duration-200"><i class="fas fa-plus mr-2"></i>Add Department</button>
            </div>
            <?php if (empty($departments)): ?>
                <p class="text-gray-600">No departments found. Add one to get started.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department Name</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($departments as $dept): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($dept['department_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button onclick="openEditDepartmentModal(<?php echo $dept['department_id']; ?>, '<?php echo htmlspecialchars($dept['department_name'], ENT_QUOTES, 'UTF-8'); ?>')" class="text-yellow-600 hover:text-yellow-800 mr-4"><i class="fas fa-edit"></i></button>
                                        <button onclick="openDeleteDepartmentModal(<?php echo $dept['department_id']; ?>, '<?php echo htmlspecialchars($dept['department_name'], ENT_QUOTES, 'UTF-8'); ?>')" class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Programs Management -->
        <div class="bg-white p-6 rounded-lg shadow-md fade-in">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-gray-800">Programs</h3>
                <button onclick="openModal('addProgramModal')" class="bg-yellow-500 text-white px-4 py-2 rounded-md hover:bg-yellow-600 transition duration-200"><i class="fas fa-plus mr-2"></i>Add Program</button>
            </div>
            <?php if (empty($programs)): ?>
                <p class="text-gray-600">No programs found. Add one to get started.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Program Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($programs as $prog): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($prog['program_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($prog['department_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button onclick="openEditProgramModal(<?php echo $prog['program_id']; ?>, '<?php echo htmlspecialchars($prog['program_name'], ENT_QUOTES, 'UTF-8'); ?>', <?php echo $prog['department_id']; ?>)" class="text-yellow-600 hover:text-yellow-800 mr-4"><i class="fas fa-edit"></i></button>
                                        <button onclick="openDeleteProgramModal(<?php echo $prog['program_id']; ?>, '<?php echo htmlspecialchars($prog['program_name'], ENT_QUOTES, 'UTF-8'); ?>')" class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Add Department Modal -->
        <div id="addDepartmentModal" class="modal fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-lg shadow-2xl w-full max-w-md mx-4 modal-content transform scale-95">
                <div class="flex justify-between items-center p-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Add Department</h3>
                    <button onclick="closeModal('addDepartmentModal')" class="text-gray-600 hover:text-gray-800"><i class="fas fa-times"></i></button>
                </div>
                <form action="/dean/settings" method="POST" class="p-4">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="mb-4">
                        <label for="department_name" class="block text-sm font-medium text-gray-700">Department Name <span class="text-red-500">*</span></label>
                        <input type="text" id="department_name" name="department_name" required maxlength="100" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-yellow-500 focus:ring focus:ring-yellow-500 focus:ring-opacity-50">
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="closeModal('addDepartmentModal')" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300">Cancel</button>
                        <button type="submit" name="add_department" class="bg-yellow-500 text-white px-4 py-2 rounded-md hover:bg-yellow-600">Add</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Department Modal -->
        <div id="editDepartmentModal" class="modal fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-lg shadow-2xl w-full max-w-md mx-4 modal-content transform scale-95">
                <div class="flex justify-between items-center p-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Edit Department</h3>
                    <button onclick="closeModal('editDepartmentModal')" class="text-gray-600 hover:text-gray-800"><i class="fas fa-times"></i></button>
                </div>
                <form action="/dean/settings" method="POST" class="p-4">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="department_id" id="edit_department_id">
                    <div class="mb-4">
                        <label for="edit_department_name" class="block text-sm font-medium text-gray-700">Department Name <span class="text-red-500">*</span></label>
                        <input type="text" id="edit_department_name" name="department_name" required maxlength="100" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-yellow-500 focus:ring focus:ring-yellow-500 focus:ring-opacity-50">
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="closeModal('editDepartmentModal')" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300">Cancel</button>
                        <button type="submit" name="edit_department" class="bg-yellow-500 text-white px-4 py-2 rounded-md hover:bg-yellow-600">Save</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Department Modal -->
        <div id="deleteDepartmentModal" class="modal fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-lg shadow-2xl w-full max-w-md mx-4 modal-content transform scale-95">
                <div class="flex justify-between items-center p-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Delete Department</h3>
                    <button onclick="closeModal('deleteDepartmentModal')" class="text-gray-600 hover:text-gray-800"><i class="fas fa-times"></i></button>
                </div>
                <form action="/dean/settings" method="POST" class="p-4">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="department_id" id="delete_department_id">
                    <p class="text-gray-700 mb-4">Are you sure you want to delete <span id="delete_department_name" class="font-medium"></span>? This action cannot be undone.</p>
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="closeModal('deleteDepartmentModal')" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300">Cancel</button>
                        <button type="submit" name="delete_department" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">Delete</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Program Modal -->
        <div id="addProgramModal" class="modal fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-lg shadow-2xl w-full max-w-md mx-4 modal-content transform scale-95">
                <div class="flex justify-between items-center p-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Add Program</h3>
                    <button onclick="closeModal('addProgramModal')" class="text-gray-600 hover:text-gray-800"><i class="fas fa-times"></i></button>
                </div>
                <form action="/dean/settings" method="POST" class="p-4">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="mb-4">
                        <label for="program_name" class="block text-sm font-medium text-gray-700">Program Name <span class="text-red-500">*</span></label>
                        <input type="text" id="program_name" name="program_name" required maxlength="100" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-yellow-500 focus:ring focus:ring-yellow-500 focus:ring-opacity-50">
                    </div>
                    <div class="mb-4">
                        <label for="department_id" class="block text-sm font-medium text-gray-700">Department <span class="text-red-500">*</span></label>
                        <select id="department_id" name="department_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-yellow-500 focus:ring focus:ring-yellow-500 focus:ring-opacity-50">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['department_id']; ?>"><?php echo htmlspecialchars($dept['department_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="closeModal('addProgramModal')" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300">Cancel</button>
                        <button type="submit" name="add_program" class="bg-yellow-500 text-white px-4 py-2 rounded-md hover:bg-yellow-600">Add</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Program Modal -->
        <div id="editProgramModal" class="modal fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-lg shadow-2xl w-full max-w-md mx-4 modal-content transform scale-95">
                <div class="flex justify-between items-center p-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Edit Program</h3>
                    <button onclick="closeModal('editProgramModal')" class="text-gray-600 hover:text-gray-800"><i class="fas fa-times"></i></button>
                </div>
                <form action="/dean/settings" method="POST" class="p-4">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="program_id" id="edit_program_id">
                    <div class="mb-4">
                        <label for="edit_program_name" class="block text-sm font-medium text-gray-700">Program Name <span class="text-red-500">*</span></label>
                        <input type="text" id="edit_program_name" name="program_name" required maxlength="100" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-yellow-500 focus:ring focus:ring-yellow-500 focus:ring-opacity-50">
                    </div>
                    <div class="mb-4">
                        <label for="edit_department_id" class="block text-sm font-medium text-gray-700">Department <span class="text-red-500">*</span></label>
                        <select id="edit_department_id" name="department_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-yellow-500 focus:ring focus:ring-yellow-500 focus:ring-opacity-50">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['department_id']; ?>"><?php echo htmlspecialchars($dept['department_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="closeModal('editProgramModal')" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300">Cancel</button>
                        <button type="submit" name="edit_program" class="bg-yellow-500 text-white px-4 py-2 rounded-md hover:bg-yellow-600">Save</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Program Modal -->
        <div id="deleteProgramModal" class="modal fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-lg shadow-2xl w-full max-w-md mx-4 modal-content transform scale-95">
                <div class="flex justify-between items-center p-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Delete Program</h3>
                    <button onclick="closeModal('deleteProgramModal')" class="text-gray-600 hover:text-gray-800"><i class="fas fa-times"></i></button>
                </div>
                <form action="/dean/settings" method="POST" class="p-4">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="program_id" id="delete_program_id">
                    <p class="text-gray-700 mb-4">Are you sure you want to delete <span id="delete_program_name" class="font-medium"></span>? This action cannot be undone.</p>
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="closeModal('deleteProgramModal')" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300">Cancel</button>
                        <button type="submit" name="delete_program" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Toast Notifications
            <?php if ($success): ?>
                showToast('<?php echo $success; ?>', 'bg-green-500');
            <?php endif; ?>
            <?php if ($error): ?>
                showToast('<?php echo $error; ?>', 'bg-red-500');
            <?php endif; ?>

            function showToast(message, bgColor) {
                const toast = document.createElement('div');
                toast.className = `toast ${bgColor} text-white px-4 py-2 rounded-lg shadow-lg`;
                toast.textContent = message;
                toast.setAttribute('role', 'alert');
                document.getElementById('toast-container').appendChild(toast);
                setTimeout(() => {
                    toast.style.opacity = '0';
                    setTimeout(() => toast.remove(), 300);
                }, 5000);
            }

            // Modal Functions
            window.openModal = function(modalId) {
                const modal = document.getElementById(modalId);
                const modalContent = modal.querySelector('.modal-content');
                modal.classList.remove('hidden');
                modalContent.classList.remove('scale-95');
                modalContent.classList.add('scale-100');
                document.body.style.overflow = 'hidden';
            };

            window.closeModal = function(modalId) {
                const modal = document.getElementById(modalId);
                const modalContent = modal.querySelector('.modal-content');
                modalContent.classList.remove('scale-100');
                modalContent.classList.add('scale-95');
                setTimeout(() => {
                    modal.classList.add('hidden');
                    document.body.style.overflow = 'auto';
                    modal.querySelector('form')?.reset();
                }, 200);
            };

            window.openEditDepartmentModal = function(id, name) {
                document.getElementById('edit_department_id').value = id;
                document.getElementById('edit_department_name').value = name;
                openModal('editDepartmentModal');
            };

            window.openDeleteDepartmentModal = function(id, name) {
                document.getElementById('delete_department_id').value = id;
                document.getElementById('delete_department_name').textContent = name;
                openModal('deleteDepartmentModal');
            };

            window.openEditProgramModal = function(id, name, departmentId) {
                document.getElementById('edit_program_id').value = id;
                document.getElementById('edit_program_name').value = name;
                document.getElementById('edit_department_id').value = departmentId;
                openModal('editProgramModal');
            };

            window.openDeleteProgramModal = function(id, name) {
                document.getElementById('delete_program_id').value = id;
                document.getElementById('delete_program_name').textContent = name;
                openModal('deleteProgramModal');
            };

            // Close modals on backdrop click
            document.querySelectorAll('.modal').forEach(modal => {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) closeModal(modal.id);
                });
            });

            // Close modals on ESC key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    document.querySelectorAll('.modal').forEach(modal => {
                        if (!modal.classList.contains('hidden')) closeModal(modal.id);
                    });
                }
            });

            // Form validation
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