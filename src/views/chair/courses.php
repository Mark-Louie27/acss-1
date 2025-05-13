<?php
require_once __DIR__ . '/../../controllers/ChairController.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();

// Assuming $error, $success, $programs, $courses, $editCourse, $page, $offset, $perPage, $totalCourses, $totalPages are set by ChairController
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses | ACSS</title>
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
    </style>
</head>

<body class="bg-gray-light font-sans antialiased">
    <div id="toast-container" class="fixed top-5 right-5 z-50"></div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- Header -->
        <header class="mb-8 slide-in-left">
            <h1 class="text-4xl font-bold text-gray-dark">Manage Courses</h1>
            <p class="text-gray-dark mt-2">Add, edit, and manage courses for your department</p>
        </header>

        <!-- Add Course Button -->
        <div class="mb-6 flex justify-end fade-in">
            <button id="openAddCourseModalBtn"
                class="btn-gold px-6 py-3 rounded-lg shadow-md hover:shadow-lg flex items-center transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-gold focus:ring-opacity-50">
                <i class="fas fa-plus mr-2"></i> Add New Course
            </button>
        </div>

        <!-- Add Course Modal -->
        <div id="addCourseModal" class="modal fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl mx-4 transform modal-content scale-95">
                <!-- Modal Header -->
                <div class="flex justify-between items-center p-6 border-b border-gray-light bg-gradient-to-r from-white to-gray-50 rounded-t-xl">
                    <h5 class="text-xl font-bold text-gray-dark">Add New Course</h5>
                    <button id="closeAddCourseModalBtn"
                        class="text-gray-dark hover:text-gray-700 focus:outline-none bg-gray-light hover:bg-gray-200 rounded-full h-8 w-8 flex items-center justify-center transition-all duration-200"
                        aria-label="Close modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Form Content -->
                <form method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6" id="addCourseForm">
                    <!-- Course Name -->
                    <div>
                        <label for="course_name_add" class="block text-sm font-medium text-gray-dark mb-1">Course Name <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-book text-gray-dark"></i>
                            </div>
                            <input type="text" id="course_name_add" name="course_name" required
                                class="pl-10 pr-4 py-3 w-full rounded-lg border-gray-light bg-white shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50"
                                placeholder="e.g., Introduction to Programming" aria-required="true">
                        </div>
                        <p class="text-red-500 text-xs mt-1 hidden error-message">Course name is required.</p>
                    </div>
                    
                    <!-- Course Code -->
                    <div>
                        <label for="course_code_add" class="block text-sm font-medium text-gray-dark mb-1">Course Code <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-tag text-gray-dark"></i>
                            </div>
                            <input type="text" id="course_code_add" name="course_code" required
                                class="pl-10 pr-4 py-3 w-full rounded-lg border-gray-light bg-white shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50"
                                placeholder="e.g., CS101" aria-required="true">
                        </div>
                        <p class="text-red-500 text-xs mt-1 hidden error-message">Course code is required.</p>
                    </div>

                    <!-- Program -->
                    <div>
                        <label for="program_id_add" class="block text-sm font-medium text-gray-dark mb-1">Program</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-graduation-cap text-gray-dark"></i>
                            </div>
                            <select id="program_id_add" name="program_id"
                                class="pl-10 pr-10 py-3 w-full rounded-lg border-gray-light bg-white shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50 appearance-none">
                                <option value="">Select Program (Optional)</option>
                                <?php foreach ($programs as $program): ?>
                                    <option value="<?php echo htmlspecialchars($program['program_id']); ?>">
                                        <?php echo htmlspecialchars($program['program_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <i class="fas fa-chevron-down text-gray-dark"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Total Units -->
                    <div>
                        <label for="units_add" class="block text-sm font-medium text-gray-dark mb-1">Total Units <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-calculator text-gray-dark"></i>
                            </div>
                            <input type="number" id="units_add" name="units" value="3" min="1" required
                                class="pl-10 pr-4 py-3 w-full rounded-lg border-gray-light bg-white shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50"
                                aria-required="true">
                        </div>
                        <p class="text-red-500 text-xs mt-1 hidden error-message">Total units must be at least 1.</p>
                    </div>

                    <!-- Lecture Units -->
                    <div>
                        <label for="lecture_units_add" class="block text-sm font-medium text-gray-dark mb-1">Lecture Units</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-chalkboard-teacher text-gray-dark"></i>
                            </div>
                            <input type="number" id="lecture_units_add" name="lecture_units" value="0" min="0"
                                class="pl-10 pr-4 py-3 w-full rounded-lg border-gray-light bg-white shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50">
                        </div>
                    </div>

                    <!-- Lab Units -->
                    <div>
                        <label for="lab_units_add" class="block text-sm font-medium text-gray-dark mb-1">Lab Units</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-flask text-gray-dark"></i>
                            </div>
                            <input type="number" id="lab_units_add" name="lab_units" value="0" min="0"
                                class="pl-10 pr-4 py-3 w-full rounded-lg border-gray-light bg-white shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50">
                        </div>
                    </div>

                    <!-- Lecture Hours -->
                    <div>
                        <label for="lecture_hours_add" class="block text-sm font-medium text-gray-dark mb-1">Lecture Hours</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-clock text-gray-dark"></i>
                            </div>
                            <input type="number" id="lecture_hours_add" name="lecture_hours" value="0" min="0"
                                class="pl-10 pr-4 py-3 w-full rounded-lg border-gray-light bg-white shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50">
                        </div>
                    </div>

                    <!-- Lab Hours -->
                    <div>
                        <label for="lab_hours_add" class="block text-sm font-medium text-gray-dark mb-1">Lab Hours</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-clock text-gray-dark"></i>
                            </div>
                            <input type="number" id="lab_hours_add" name="lab_hours" value="0" min="0"
                                class="pl-10 pr-4 py-3 w-full rounded-lg border-gray-light bg-white shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50">
                        </div>
                    </div>

                    <!-- Is Active -->
                    <div class="md:col-span-2">
                        <div class="flex items-center bg-gray-50 p-4 rounded-lg border border-gray-light">
                            <input type="checkbox" id="is_active_add" name="is_active" checked
                                class="h-5 w-5 text-gold focus:ring-gold border-gray-light rounded">
                            <label for="is_active_add" class="ml-2 text-sm text-gray-dark">Active</label>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="md:col-span-2 flex justify-end space-x-3 pt-4 border-t border-gray-light">
                        <button type="button" id="cancelAddCourseModalBtn"
                            class="bg-gray-light text-gray-dark px-5 py-3 rounded-lg hover:bg-gray-200 transition-all duration-200 font-medium">Cancel</button>
                        <button type="submit" class="btn-gold px-5 py-3 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 font-medium">Add Course</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Course Modal -->
        <?php if ($editCourse): ?>
            <div id="editCourseModal" class="modal fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50">
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl mx-4 transform modal-content scale-95">
                    <!-- Modal Header -->
                    <div class="flex justify-between items-center p-6 border-b border-gray-light bg-gradient-to-r from-white to-gray-50 rounded-t-xl">
                        <h5 class="text-xl font-bold text-gray-dark">Edit Course</h5>
                        <button id="closeEditCourseModalBtn"
                            class="text-gray-dark hover:text-gray-700 focus:outline-none bg-gray-light hover:bg-gray-200 rounded-full h-8 w-8 flex items-center justify-center transition-all duration-200"
                            aria-label="Close modal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <!-- Form Content -->
                    <form method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6" id="editCourseForm">
                        <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($editCourse['course_id']); ?>">
                        <!-- Course Code -->
                        <div>
                            <label for="course_code_edit" class="block text-sm font-medium text-gray-dark mb-1">Course Code <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-tag text-gray-dark"></i>
                                </div>
                                <input type="text" id="course_code_edit" name="course_code" required
                                    value="<?php echo htmlspecialchars($editCourse['course_code'] ?? ''); ?>"
                                    class="pl-10 pr-4 py-3 w-full rounded-lg border-gray-light bg-white shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50"
                                    placeholder="e.g., CS101" aria-required="true">
                            </div>
                            <p class="text-red-500 text-xs mt-1 hidden error-message">Course code is required.</p>
                        </div>

                        <!-- Course Name -->
                        <div>
                            <label for="course_name_edit" class="block text-sm font-medium text-gray-dark mb-1">Course Name <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-book text-gray-dark"></i>
                                </div>
                                <input type="text" id="course_name_edit" name="course_name" required
                                    value="<?php echo htmlspecialchars($editCourse['course_name'] ?? ''); ?>"
                                    class="pl-10 pr-4 py-3 w-full rounded-lg border-gray-light bg-white shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50"
                                    placeholder="e.g., Introduction to Programming" aria-required="true">
                            </div>
                            <p class="text-red-500 text-xs mt-1 hidden error-message">Course name is required.</p>
                        </div>

                        <!-- Program -->
                        <div>
                            <label for="program_id_edit" class="block text-sm font-medium text-gray-dark mb-1">Program</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-graduation-cap text-gray-dark"></i>
                                </div>
                                <select id="program_id_edit" name="program_id"
                                    class="pl-10 pr-10 py-3 w-full rounded-lg border-gray-light bg-white shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50 appearance-none">
                                    <option value="">Select Program (Optional)</option>
                                    <?php foreach ($programs as $program): ?>
                                        <option value="<?php echo htmlspecialchars($program['program_id']); ?>"
                                            <?php echo (isset($editCourse['program_id']) && $editCourse['program_id'] == $program['program_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($program['program_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <i class="fas fa-chevron-down text-gray-dark"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Total Units -->
                        <div>
                            <label for="units_edit" class="block text-sm font-medium text-gray-dark mb-1">Total Units <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-calculator text-gray-dark"></i>
                                </div>
                                <input type="number" id="units_edit" name="units" min="1" required
                                    value="<?php echo htmlspecialchars($editCourse['units'] ?? '3'); ?>"
                                    class="pl-10 pr-4 py-3 w-full rounded-lg border-gray-light bg-white shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50"
                                    aria-required="true">
                            </div>
                            <p class="text-red-500 text-xs mt-1 hidden error-message">Total units must be at least 1.</p>
                        </div>

                        <!-- Lecture Units -->
                        <div>
                            <label for="lecture_units_edit" class="block text-sm font-medium text-gray-dark mb-1">Lecture Units</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-chalkboard-teacher text-gray-dark"></i>
                                </div>
                                <input type="number" id="lecture_units_edit" name="lecture_units" min="0"
                                    value="<?php echo htmlspecialchars($editCourse['lecture_units'] ?? '0'); ?>"
                                    class="pl-10 pr-4 py-3 w-full rounded-lg border-gray-light bg-white shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50">
                            </div>
                        </div>

                        <!-- Lab Units -->
                        <div>
                            <label for="lab_units_edit" class="block text-sm font-medium text-gray-dark mb-1">Lab Units</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-flask text-gray-dark"></i>
                                </div>
                                <input type="number" id="lab_units_edit" name="lab_units" min="0"
                                    value="<?php echo htmlspecialchars($editCourse['lab_units'] ?? '0'); ?>"
                                    class="pl-10 pr-4 py-3 w-full rounded-lg border-gray-light bg-white shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50">
                            </div>
                        </div>

                        <!-- Lecture Hours -->
                        <div>
                            <label for="lecture_hours_edit" class="block text-sm font-medium text-gray-dark mb-1">Lecture Hours</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-clock text-gray-dark"></i>
                                </div>
                                <input type="number" id="lecture_hours_edit" name="lecture_hours" min="0"
                                    value="<?php echo htmlspecialchars($editCourse['lecture_hours'] ?? '0'); ?>"
                                    class="pl-10 pr-4 py-3 w-full rounded-lg border-gray-light bg-white shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50">
                            </div>
                        </div>

                        <!-- Lab Hours -->
                        <div>
                            <label for="lab_hours_edit" class="block text-sm font-medium text-gray-dark mb-1">Lab Hours</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-clock text-gray-dark"></i>
                                </div>
                                <input type="number" id="lab_hours_edit" name="lab_hours" min="0"
                                    value="<?php echo htmlspecialchars($editCourse['lab_hours'] ?? '0'); ?>"
                                    class="pl-10 pr-4 py-3 w-full rounded-lg border-gray-light bg-white shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50">
                            </div>
                        </div>

                        <!-- Is Active -->
                        <div class="md:col-span-2">
                            <div class="flex items-center bg-gray-50 p-4 rounded-lg border border-gray-light">
                                <input type="checkbox" id="is_active_edit" name="is_active"
                                    <?php echo (isset($editCourse['is_active']) && $editCourse['is_active']) ? 'checked' : ''; ?>
                                    class="h-5 w-5 text-gold focus:ring-gold border-gray-light rounded">
                                <label for="is_active_edit" class="ml-2 text-sm text-gray-dark">Active</label>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="md:col-span-2 flex justify-end space-x-3 pt-4 border-t border-gray-light">
                            <button type="button" id="cancelEditCourseModalBtn"
                                class="bg-gray-light text-gray-dark px-5 py-3 rounded-lg hover:bg-gray-200 transition-all duration-200 font-medium">Cancel</button>
                            <button type="submit" class="btn-gold px-5 py-3 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 font-medium">Update Course</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Courses Table -->
        <div class="bg-white rounded-xl shadow-lg fade-in">
            <div class="flex justify-between items-center p-6 border-b border-gray-light bg-gradient-to-r from-white to-gray-50 rounded-t-xl">
                <h5 class="text-xl font-bold text-gray-dark">Courses List</h5>
                <span class="text-sm font-medium text-gray-dark bg-gray-light px-3 py-1 rounded-full"><?php echo $totalCourses; ?> Courses</span>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-light">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-dark uppercase tracking-wider">Course Name</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-dark uppercase tracking-wider">Course Code</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-dark uppercase tracking-wider">Program</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-dark uppercase tracking-wider">Units</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-dark uppercase tracking-wider">Lecture</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-dark uppercase tracking-wider">Lab</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-dark uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-dark uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-light">
                            <?php if (empty($courses)): ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-4 text-center text-gray-dark">
                                        <i class="fas fa-book-open text-gray-dark text-2xl mb-2"></i>
                                        <p>No courses found.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($courses as $course): ?>
                                    <tr class="hover:bg-gray-50 transition-all duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-dark"><?php echo htmlspecialchars($course['course_name']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-dark"><?php echo htmlspecialchars($course['course_code']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-dark"><?php echo htmlspecialchars($course['program_name'] ?? 'N/A'); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-dark"><?php echo htmlspecialchars($course['units']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-dark">
                                            <?php echo htmlspecialchars($course['lecture_units']); ?> units<br>
                                            <?php echo htmlspecialchars($course['lecture_hours']); ?> hours
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-dark">
                                            <?php echo htmlspecialchars($course['lab_units']); ?> units<br>
                                            <?php echo htmlspecialchars($course['lab_hours']); ?> hours
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $course['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo $course['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="courses?edit=<?php echo htmlspecialchars($course['course_id']); ?>&page=<?php echo $page; ?>"
                                                class="text-gold group relative hover:text-gold-900 mr-3">
                                                Edit
                                                <span class="tooltip absolute bg-gray-dark text-white text-xs rounded py-1 px-2 -top-8 left-1/2 transform -translate-x-1/2">Edit Course</span>
                                            </a>
                                            <a href="courses?toggle_status=<?php echo htmlspecialchars($course['course_id']); ?>&page=<?php echo $page; ?>"
                                                class="text-blue-600 group relative hover:text-blue-900"
                                                onclick="return confirm('Are you sure you want to toggle the status?');">
                                                <?php echo $course['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                                <span class="tooltip absolute bg-gray-dark text-white text-xs rounded py-1 px-2 -top-8 left-1/2 transform -translate-x-1/2">
                                                    <?php echo $course['is_active'] ? 'Deactivate Course' : 'Activate Course'; ?>
                                                </span>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Controls -->
                <div class="mt-6 flex justify-between items-center">
                    <div class="text-sm text-gray-dark">
                        Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $perPage, $totalCourses); ?> of <?php echo $totalCourses; ?> courses
                    </div>
                    <div class="flex space-x-2">
                        <?php if ($page > 1): ?>
                            <a href="courses?page=<?php echo $page - 1; ?>"
                                class="px-4 py-2 bg-gray-light text-gray-dark rounded-lg hover:bg-gray-200 transition-all duration-200">
                                Previous
                            </a>
                        <?php else: ?>
                            <span class="px-4 py-2 bg-gray-50 text-gray-dark rounded-lg cursor-not-allowed">
                                Previous
                            </span>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="courses?page=<?php echo $i; ?>"
                                class="px-4 py-2 rounded-lg <?php echo $i === $page ? 'btn-gold text-white' : 'bg-gray-light text-gray-dark hover:bg-gray-200'; ?> transition-all duration-200">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="courses?page=<?php echo $page + 1; ?>"
                                class="px-4 py-2 bg-gray-light text-gray-dark rounded-lg hover:bg-gray-200 transition-all duration-200">
                                Next
                            </a>
                        <?php else: ?>
                            <span class="px-4 py-2 bg-gray-50 text-gray-dark rounded-lg cursor-not-allowed">
                                Next
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for Toast Notifications and Functionality -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Toast Notifications
            <?php if ($success): ?>
                showToast('<?php echo htmlspecialchars($success); ?>', 'bg-green-500');
            <?php endif; ?>
            <?php if ($error): ?>
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
                    // Reset form validation
                    const form = modal.querySelector('form');
                    if (form) {
                        form.reset();
                        form.querySelectorAll('.error-message').forEach(msg => msg.classList.add('hidden'));
                        form.querySelectorAll('input, select').forEach(input => input.classList.remove('border-red-500'));
                    }
                }, 200);
            }

            // Event Listeners for Add Course Modal
            const openAddCourseModalBtn = document.getElementById('openAddCourseModalBtn');
            const closeAddCourseModalBtn = document.getElementById('closeAddCourseModalBtn');
            const cancelAddCourseModalBtn = document.getElementById('cancelAddCourseModalBtn');

            if (openAddCourseModalBtn) {
                openAddCourseModalBtn.addEventListener('click', () => openModal('addCourseModal'));
            }
            if (closeAddCourseModalBtn) {
                closeAddCourseModalBtn.addEventListener('click', () => closeModal('addCourseModal'));
            }
            if (cancelAddCourseModalBtn) {
                cancelAddCourseModalBtn.addEventListener('click', () => closeModal('addCourseModal'));
            }

            // Event Listeners for Edit Course Modal
            const closeEditCourseModalBtn = document.getElementById('closeEditCourseModalBtn');
            const cancelEditCourseModalBtn = document.getElementById('cancelEditCourseModalBtn');

            if (closeEditCourseModalBtn) {
                closeEditCourseModalBtn.addEventListener('click', () => closeModal('editCourseModal'));
            }
            if (cancelEditCourseModalBtn) {
                cancelEditCourseModalBtn.addEventListener('click', () => closeModal('editCourseModal'));
            }

            // Automatically open edit modal if editCourse exists
            <?php if ($editCourse): ?>
                openModal('editCourseModal');
            <?php endif; ?>

                // Close modals on backdrop click
                ['addCourseModal', 'editCourseModal'].forEach(modalId => {
                    const modal = document.getElementById(modalId);
                    if (modal) {
                        modal.addEventListener('click', (e) => {
                            if (e.target === modal) closeModal(modalId);
                        });
                    }
                });

            // Close modals on ESC key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    ['addCourseModal', 'editCourseModal'].forEach(modalId => {
                        const modal = document.getElementById(modalId);
                        if (modal && !modal.classList.contains('hidden')) closeModal(modalId);
                    });
                }
            });

            // Form validation for both modals
            ['addCourseForm', 'editCourseForm'].forEach(formId => {
                const form = document.getElementById(formId);
                if (form) {
                    form.addEventListener('submit', (e) => {
                        let isValid = true;
                        form.querySelectorAll('input[required], select[required]').forEach(input => {
                            const errorMessage = input.nextElementSibling;
                            if (!input.value.trim()) {
                                input.classList.add('border-red-500');
                                errorMessage.classList.remove('hidden');
                                isValid = false;
                            } else {
                                input.classList.remove('border-red-500');
                                errorMessage.classList.add('hidden');
                            }
                        });

                        const unitsInput = form.querySelector('[name="units"]');
                        const unitsError = unitsInput.nextElementSibling;
                        if (unitsInput.value < 1) {
                            unitsInput.classList.add('border-red-500');
                            unitsError.classList.remove('hidden');
                            isValid = false;
                        } else {
                            unitsInput.classList.remove('border-red-500');
                            unitsError.classList.add('hidden');
                        }

                        if (!isValid) e.preventDefault();
                    });

                    form.querySelectorAll('input[required], select[required]').forEach(input => {
                        input.addEventListener('input', () => {
                            const errorMessage = input.nextElementSibling;
                            if (input.value.trim()) {
                                input.classList.remove('border-red-500');
                                errorMessage.classList.add('hidden');
                            }
                        });
                    });

                    form.querySelector('[name="units"]').addEventListener('input', function() {
                        const errorMessage = this.nextElementSibling;
                        if (this.value >= 1) {
                            this.classList.remove('border-red-500');
                            errorMessage.classList.add('hidden');
                        }
                    });
                }
            });
        });
    </script>
</body>

</html>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>