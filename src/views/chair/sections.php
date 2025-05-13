<?php
ob_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Section Management | ACSS</title>
    <link rel="stylesheet" href="/css/output.css">
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
    <style>
        :root {
            --gold: #D4AF37;
            --white: #FFFFFF;
            --gray-dark: #4B5563;
            --gray-light: #E5E7EB;
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

        .input-focus {
            transition: all 0.2s ease;
        }

        .input-focus:focus {
            border-color: var(--gold);
            ring-color: var(--gold);
        }

        .btn-gold {
            background-color: var(--gold);
            color: var(--white);
        }

        .btn-gold:hover {
            background-color: #b8972e;
        }

        .tooltip {
            display: none;
        }

        .group:hover .tooltip {
            display: block;
        }

        .loading::after {
            content: '';
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid var(--gold);
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-left: 8px;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .collapsible-header {
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .collapsible-header:hover {
            background-color: #f9fafb;
        }
    </style>
</head>

<body class="bg-gray-light font-sans antialiased">
    <div id="toast-container" class="fixed top-5 right-5 z-50"></div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- Header -->
        <header class="mb-8 slide-in-left">
            <h1 class="text-4xl font-bold text-gray-dark">Section Management</h1>
            <p class="text-gray-dark mt-2">Manage sections for your department</p>
        </header>

        <!-- Sections Table -->
        <div class="bg-white rounded-xl shadow-lg fade-in">
            <div class="flex justify-between items-center p-6 border-b border-gray-light bg-gradient-to-r from-white to-gray-50 rounded-t-xl">
                <h3 class="text-xl font-bold text-gray-dark">Your Department's Sections</h3>
                <div class="flex items-center space-x-4">
                    <span class="text-sm font-medium text-gray-dark bg-gray-light px-3 py-1 rounded-full">
                        <?php echo array_sum(array_map('count', $groupedSections)); ?> Sections
                    </span>
                    <button id="openAddModalBtn" class="btn-gold px-5 py-2 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 font-medium">
                        <i class="fas fa-plus mr-2"></i>Add Section
                    </button>
                </div>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <?php if (empty($groupedSections['1st Year']) && empty($groupedSections['2nd Year']) && empty($groupedSections['3rd Year']) && empty($groupedSections['4th Year'])): ?>
                        <div class="text-center py-8 text-gray-dark">
                            <i class="fas fa-layer-group text-gray-dark text-2xl mb-2"></i>
                            <p class="font-medium">No sections found in your department</p>
                            <p class="text-sm mt-1">Add a new section to get started</p>
                        </div>
                    <?php else: ?>
                        <table class="min-w-full divide-y divide-gray-light">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-dark uppercase tracking-wider">Section Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-dark uppercase tracking-wider">Program</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-dark uppercase tracking-wider">Year Level</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-dark uppercase tracking-wider">Semester</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-dark uppercase tracking-wider">Academic Year</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-dark uppercase tracking-wider">Max Students</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-dark uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-light">
                                <?php foreach (['1st Year', '2nd Year', '3rd Year', '4th Year'] as $yearLevel): ?>
                                    <?php if (!empty($groupedSections[$yearLevel])): ?>
                                        <tr class="collapsible-header bg-gray-100">
                                            <td colspan="7" class="px-6 py-3 text-sm font-semibold text-gray-dark">
                                                <div class="flex items-center">
                                                    <i class="fas fa-chevron-down mr-2 transition-transform duration-200"></i>
                                                    <?php echo htmlspecialchars($yearLevel); ?> (<?php echo count($groupedSections[$yearLevel]); ?> Sections)
                                                </div>
                                            </td>
                                        </tr>
                            <tbody class="collapsible-content">
                                <?php foreach ($groupedSections[$yearLevel] as $section): ?>
                                    <tr class="hover:bg-gray-50 transition-all duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-dark"><?php echo htmlspecialchars($section['section_name']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-dark"><?php echo htmlspecialchars($section['program_name']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-dark"><?php echo htmlspecialchars($section['year_level']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-dark"><?php echo htmlspecialchars($section['semester']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-dark"><?php echo htmlspecialchars($section['academic_year']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-dark"><?php echo htmlspecialchars($section['max_students']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button class="edit-btn text-blue-600 group relative hover:text-blue-700 transition-all duration-200 mr-4"
                                                data-id="<?php echo $section['section_id']; ?>"
                                                data-name="<?php echo htmlspecialchars($section['section_name']); ?>"
                                                data-year="<?php echo htmlspecialchars($section['year_level']); ?>"
                                                data-max="<?php echo htmlspecialchars($section['max_students']); ?>">
                                                Edit
                                                <span class="tooltip absolute bg-gray-dark text-white text-xs rounded py-1 px-2 -top-8 left-1/2 transform -translate-x-1/2">Edit Section</span>
                                            </button>
                                            <button class="remove-btn text-red-600 group relative hover:text-red-700 transition-all duration-200"
                                                data-id="<?php echo $section['section_id']; ?>"
                                                data-name="<?php echo htmlspecialchars($section['section_name']); ?>">
                                                Remove
                                                <span class="tooltip absolute bg-gray-dark text-white text-xs rounded py-1 px-2 -top-8 left-1/2 transform -translate-x-1/2">Remove Section</span>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Add Section Modal -->
        <div id="add-modal" class="modal fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 transform modal-content scale-95">
                <div class="flex justify-between items-center p-6 border-b border-gray-light bg-gradient-to-r from-white to-gray-50 rounded-t-xl">
                    <h3 class="text-xl font-bold text-gray-dark">Add New Section</h3>
                    <button id="closeAddModalBtn"
                        class="text-gray-dark hover:text-gray-700 focus:outline-none bg-gray-light hover:bg-gray-200 rounded-full h-8 w-8 flex items-center justify-center transition-all duration-200"
                        aria-label="Close modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="addSectionForm" action="/chair/sections" method="POST">
                    <div class="p-6">
                        <div class="mb-4">
                            <label for="section_name" class="block text-sm font-medium text-gray-dark mb-1">Section Name</label>
                            <input type="text" id="section_name" name="section_name"
                                class="input-focus w-full px-4 py-2 border border-gray-light rounded-lg focus:outline-none focus:ring-2 focus:ring-gold"
                                required placeholder="e.g., BSIT-1A">
                        </div>
                        <div class="mb-4">
                            <label for="year_level" class="block text-sm font-medium text-gray-dark mb-1">Year Level</label>
                            <select id="year_level" name="year_level"
                                class="input-focus w-full px-4 py-2 border border-gray-light rounded-lg focus:outline-none focus:ring-2 focus:ring-gold"
                                required>
                                <option value="" disabled selected>Select year level</option>
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="max_students" class="block text-sm font-medium text-gray-dark mb-1">Max Students</label>
                            <input type="number" id="max_students" name="max_students"
                                class="input-focus w-full px-4 py-2 border border-gray-light rounded-lg focus:outline-none focus:ring-2 focus:ring-gold"
                                required min="1" max="100" value="40">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-dark mb-1">Semester</label>
                            <p class="text-gray-dark">
                                <?php echo $currentSemester ? htmlspecialchars($currentSemester['semester_name'] . ' ' . $currentSemester['academic_year']) : 'Not set'; ?>
                            </p>
                            <input type="hidden" name="add_section" value="1">
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3 p-6 border-t border-gray-light">
                        <button type="button" id="cancelAddBtn"
                            class="bg-gray-light text-gray-dark px-5 py-3 rounded-lg hover:bg-gray-200 transition-all duration-200 font-medium">
                            Cancel
                        </button>
                        <button type="submit"
                            class="btn-gold px-5 py-3 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 font-medium">
                            Add Section
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Section Modal -->
        <div id="edit-modal" class="modal fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 transform modal-content scale-95">
                <div class="flex justify-between items-center p-6 border-b border-gray-light bg-gradient-to-r from-white to-gray-50 rounded-t-xl">
                    <h3 class="text-xl font-bold text-gray-dark">Edit Section</h3>
                    <button id="closeEditModalBtn"
                        class="text-gray-dark hover:text-gray-700 focus:outline-none bg-gray-light hover:bg-gray-200 rounded-full h-8 w-8 flex items-center justify-center transition-all duration-200"
                        aria-label="Close modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="editSectionForm" action="/chair/sections" method="POST">
                    <div class="p-6">
                        <div class="mb-4">
                            <label for="edit_section_name" class="block text-sm font-medium text-gray-dark mb-1">Section Name</label>
                            <input type="text" id="edit_section_name" name="section_name"
                                class="input-focus w-full px-4 py-2 border border-gray-light rounded-lg focus:outline-none focus:ring-2 focus:ring-gold"
                                required placeholder="e.g., BSIT-1A">
                        </div>
                        <div class="mb-4">
                            <label for="edit_year_level" class="block text-sm font-medium text-gray-dark mb-1">Year Level</label>
                            <select id="edit_year_level" name="year_level"
                                class="input-focus w-full px-4 py-2 border border-gray-light rounded-lg focus:outline-none focus:ring-2 focus:ring-gold"
                                required>
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="edit_max_students" class="block text-sm font-medium text-gray-dark mb-1">Max Students</label>
                            <input type="number" id="edit_max_students" name="max_students"
                                class="input-focus w-full px-4 py-2 border border-gray-light rounded-lg focus:outline-none focus:ring-2 focus:ring-gold"
                                required min="1" max="100">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-dark mb-1">Semester</label>
                            <p class="text-gray-dark" id="edit_semester"></p>
                            <input type="hidden" id="edit_section_id" name="section_id">
                            <input type="hidden" name="edit_section" value="1">
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3 p-6 border-t border-gray-light">
                        <button type="button" id="cancelEditBtn"
                            class="bg-gray-light text-gray-dark px-5 py-3 rounded-lg hover:bg-gray-200 transition-all duration-200 font-medium">
                            Cancel
                        </button>
                        <button type="submit"
                            class="btn-gold px-5 py-3 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 font-medium">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Remove Section Modal -->
        <div id="remove-modal" class="modal fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 transform modal-content scale-95">
                <div class="flex justify-between items-center p-6 border-b border-gray-light bg-gradient-to-r from-white to-gray-50 rounded-t-xl">
                    <h3 class="text-xl font-bold text-gray-dark">Remove Section</h3>
                    <button id="closeRemoveModalBtn"
                        class="text-gray-dark hover:text-gray-700 focus:outline-none bg-gray-light hover:bg-gray-200 rounded-full h-8 w-8 flex items-center justify-center transition-all duration-200"
                        aria-label="Close modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="removeSectionForm" action="/chair/sections" method="POST">
                    <div class="p-6">
                        <p class="text-gray-dark mb-6">Are you sure you want to remove <strong id="remove-modal-section-name"></strong> from your department?</p>
                        <input type="hidden" id="remove-modal-section-id" name="section_id">
                        <input type="hidden" name="remove_section" value="1">
                    </div>
                    <div class="flex justify-end space-x-3 p-6 border-t border-gray-light">
                        <button type="button" id="cancelRemoveBtn"
                            class="bg-gray-light text-gray-dark px-5 py-3 rounded-lg hover:bg-gray-200 transition-all duration-200 font-medium">
                            Cancel
                        </button>
                        <button type="submit"
                            class="bg-red-600 text-white px-5 py-3 rounded-lg hover:bg-red-700 transition-all duration-200 font-medium">
                            Confirm
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Toast Notifications
            <?php if (isset($success)): ?>
                showToast('<?php echo htmlspecialchars($success); ?>', 'bg-green-500');
            <?php endif; ?>
            <?php if (isset($error)): ?>
                showToast('<?php echo htmlspecialchars($error); ?>', 'bg-red-500');
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
            function openModal(modalId) {
                const modal = document.getElementById(modalId);
                const modalContent = modal.querySelector('.modal-content');
                modal.classList.remove('hidden');
                modalContent.classList.remove('scale-95');
                modalContent.classList.add('scale-100');
                document.body.style.overflow = 'hidden';
            }

            function closeModal(modalId) {
                const modal = document.getElementById(modalId);
                const modalContent = modal.querySelector('.modal-content');
                modalContent.classList.remove('scale-100');
                modalContent.classList.add('scale-95');
                setTimeout(() => {
                    modal.classList.add('hidden');
                    document.body.style.overflow = 'auto';
                }, 200);
            }

            // Add Section Modal
            document.getElementById('openAddModalBtn').addEventListener('click', () => {
                openModal('add-modal');
            });

            document.getElementById('closeAddModalBtn').addEventListener('click', () => {
                closeModal('add-modal');
            });

            document.getElementById('cancelAddBtn').addEventListener('click', () => {
                closeModal('add-modal');
            });

            // Edit Section Modal
            document.querySelectorAll('.edit-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const sectionId = btn.dataset.id;
                    const sectionName = btn.dataset.name;
                    const yearLevel = btn.dataset.year;
                    const maxStudents = btn.dataset.max;
                    const semester = btn.closest('tr').querySelector('td:nth-child(4)').textContent + ' ' +
                        btn.closest('tr').querySelector('td:nth-child(5)').textContent;

                    document.getElementById('edit_section_id').value = sectionId;
                    document.getElementById('edit_section_name').value = sectionName;
                    document.getElementById('edit_year_level').value = yearLevel;
                    document.getElementById('edit_max_students').value = maxStudents;
                    document.getElementById('edit_semester').textContent = semester;

                    openModal('edit-modal');
                });
            });

            document.getElementById('closeEditModalBtn').addEventListener('click', () => {
                closeModal('edit-modal');
            });

            document.getElementById('cancelEditBtn').addEventListener('click', () => {
                closeModal('edit-modal');
            });

            // Remove Section Modal
            document.querySelectorAll('.remove-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const sectionId = btn.dataset.id;
                    const sectionName = btn.dataset.name;
                    document.getElementById('remove-modal-section-id').value = sectionId;
                    document.getElementById('remove-modal-section-name').textContent = sectionName;
                    openModal('remove-modal');
                });
            });

            document.getElementById('closeRemoveModalBtn').addEventListener('click', () => {
                closeModal('remove-modal');
            });

            document.getElementById('cancelRemoveBtn').addEventListener('click', () => {
                closeModal('remove-modal');
            });

            // Close modals on backdrop click
            ['add-modal', 'edit-modal', 'remove-modal'].forEach(modalId => {
                const modal = document.getElementById(modalId);
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) closeModal(modalId);
                });
            });

            // Close modals on ESC key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    ['add-modal', 'edit-modal', 'remove-modal'].forEach(modalId => {
                        const modal = document.getElementById(modalId);
                        if (!modal.classList.contains('hidden')) closeModal(modalId);
                    });
                }
            });

            // Collapsible Year Level Sections
            document.querySelectorAll('.collapsible-header').forEach(header => {
                header.addEventListener('click', () => {
                    const content = header.nextElementSibling;
                    const icon = header.querySelector('.fas');
                    content.classList.toggle('hidden');
                    icon.classList.toggle('fa-chevron-down');
                    icon.classList.toggle('fa-chevron-up');
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