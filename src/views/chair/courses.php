<?php
require_once __DIR__ . '/../../controllers/ChairController.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses</title>
    <link rel="stylesheet" href="/css/output.css">
</head>

<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Manage Courses</h1>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- Button to Open Add Course Modal -->
        <div class="mb-6 flex justify-end">
            <button onclick="openModal('addCourseModal')" class="px-6 py-2 bg-gold-500 text-white font-medium rounded-md hover:bg-gold-600 focus:outline-none focus:ring-2 focus:ring-gold-500 focus:ring-offset-2">
                Add New Course
            </button>
        </div>

        <!-- Add Course Modal -->
        <div id="addCourseModal" class="fixed inset-0 bg-opacity-100 flex items-center justify-center hidden -my-48">
            <div class="bg-white rounded-lg shadow-md w-full max-w-2xl">
                <div class="bg-gray-800 text-white px-6 py-4 rounded-t-lg flex justify-between items-center">
                    <h5 class="text-xl font-semibold">Add New Course</h5>
                    <button onclick="closeModal('addCourseModal')" class="text-white hover:text-gray-300 focus:outline-none">×</button>
                </div>
                <div class="p-6">
                    <form method="POST">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="mb-4">
                                <label for="course_code_add" class="block text-gray-700 font-medium mb-2">Course Code</label>
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500"
                                    id="course_code_add" name="course_code" required>
                            </div>
                            <div class="mb-4">
                                <label for="course_name_add" class="block text-gray-700 font-medium mb-2">Course Name</label>
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500"
                                    id="course_name_add" name="course_name" required>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4">
                            <div class="mb-4">
                                <label for="program_id_add" class="block text-gray-700 font-medium mb-2">Program</label>
                                <select class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500"
                                    id="program_id_add" name="program_id">
                                    <option value="">Select Program (Optional)</option>
                                    <?php foreach ($programs as $program): ?>
                                        <option value="<?= htmlspecialchars($program['program_id']) ?>">
                                            <?= htmlspecialchars($program['program_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label for="units_add" class="block text-gray-700 font-medium mb-2">Total Units</label>
                                <input type="number" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500"
                                    id="units_add" name="units" value="3" min="1" required>
                            </div>
                            <div class="mb-4">
                                <label for="lecture_units_add" class="block text-gray-700 font-medium mb-2">Lecture Units</label>
                                <input type="number" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500"
                                    id="lecture_units_add" name="lecture_units" value="0" min="0">
                            </div>
                            <div class="mb-4">
                                <label for="lab_units_add" class="block text-gray-700 font-medium mb-2">Lab Units</label>
                                <input type="number" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500"
                                    id="lab_units_add" name="lab_units" value="0" min="0">
                            </div>
                            <div class="mb-4">
                                <label for="lecture_hours_add" class="block text-gray-700 font-medium mb-2">Lecture Hours</label>
                                <input type="number" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500"
                                    id="lecture_hours_add" name="lecture_hours" value="0" min="0">
                            </div>
                            <div class="mb-4">
                                <label for="lab_hours_add" class="block text-gray-700 font-medium mb-2">Lab Hours</label>
                                <input type="number" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500"
                                    id="lab_hours_add" name="lab_hours" value="0" min="0">
                            </div>
                        </div>

                        <div class="flex items-center mt-6">
                            <input type="checkbox" class="h-4 w-4 text-gold-600 focus:ring-gold-500 border-gray-300 rounded"
                                id="is_active_add" name="is_active" checked>
                            <label for="is_active_add" class="ml-2 block text-gray-700">Active</label>
                        </div>

                        <div class="mt-8 flex space-x-4">
                            <button type="submit" class="px-6 py-2 bg-gold-500 text-white font-medium rounded-md hover:bg-gold-600 focus:outline-none focus:ring-2 focus:ring-gold-500 focus:ring-offset-2">
                                Add Course
                            </button>
                            <button type="button" onclick="closeModal('addCourseModal')" class="px-6 py-2 bg-gray-500 text-white font-medium rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Course Modal -->
        <?php if ($editCourse): ?>
            <div id="editCourseModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center">
                <div class="bg-white rounded-lg shadow-md w-full max-w-2xl">
                    <div class="bg-gray-800 text-white px-6 py-4 rounded-t-lg flex justify-between items-center">
                        <h5 class="text-xl font-semibold">Edit Course</h5>
                        <button onclick="closeModal('editCourseModal')" class="text-white hover:text-gray-300 focus:outline-none">×</button>
                    </div>
                    <div class="p-6">
                        <form method="POST">
                            <input type="hidden" name="course_id" value="<?= htmlspecialchars($editCourse['course_id']) ?>">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="mb-4">
                                    <label for="course_code_edit" class="block text-gray-700 font-medium mb-2">Course Code</label>
                                    <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500"
                                        id="course_code_edit" name="course_code"
                                        value="<?= htmlspecialchars($editCourse['course_code'] ?? '') ?>" required>
                                </div>
                                <div class="mb-4">
                                    <label for="course_name_edit" class="block text-gray-700 font-medium mb-2">Course Name</label>
                                    <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500"
                                        id="course_name_edit" name="course_name"
                                        value="<?= htmlspecialchars($editCourse['course_name'] ?? '') ?>" required>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4">
                                <div class="mb-4">
                                    <label for="program_id_edit" class="block text-gray-700 font-medium mb-2">Program</label>
                                    <select class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500"
                                        id="program_id_edit" name="program_id">
                                        <option value="">Select Program (Optional)</option>
                                        <?php foreach ($programs as $program): ?>
                                            <option value="<?= htmlspecialchars($program['program_id']) ?>"
                                                <?= (isset($editCourse['program_id']) && $editCourse['program_id'] == $program['program_id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($program['program_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label for="units_edit" class="block text-gray-700 font-medium mb-2">Total Units</label>
                                    <input type="number" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500"
                                        id="units_edit" name="units"
                                        value="<?= htmlspecialchars($editCourse['units'] ?? '3') ?>" min="1" required>
                                </div>
                                <div class="mb-4">
                                    <label for="lecture_units_edit" class="block text-gray-700 font-medium mb-2">Lecture Units</label>
                                    <input type="number" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500"
                                        id="lecture_units_edit" name="lecture_units"
                                        value="<?= htmlspecialchars($editCourse['lecture_units'] ?? '0') ?>" min="0">
                                </div>
                                <div class="mb-4">
                                    <label for="lab_units_edit" class="block text-gray-700 font-medium mb-2">Lab Units</label>
                                    <input type="number" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500"
                                        id="lab_units_edit" name="lab_units"
                                        value="<?= htmlspecialchars($editCourse['lab_units'] ?? '0') ?>" min="0">
                                </div>
                                <div class="mb-4">
                                    <label for="lecture_hours_edit" class="block text-gray-700 font-medium mb-2">Lecture Hours</label>
                                    <input type="number" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500"
                                        id="lecture_hours_edit" name="lecture_hours"
                                        value="<?= htmlspecialchars($editCourse['lecture_hours'] ?? '0') ?>" min="0">
                                </div>
                                <div class="mb-4">
                                    <label for="lab_hours_edit" class="block text-gray-700 font-medium mb-2">Lab Hours</label>
                                    <input type="number" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500"
                                        id="lab_hours_edit" name="lab_hours"
                                        value="<?= htmlspecialchars($editCourse['lab_hours'] ?? '0') ?>" min="0">
                                </div>
                            </div>

                            <div class="flex items-center mt-6">
                                <input type="checkbox" class="h-4 w-4 text-gold-600 focus:ring-gold-500 border-gray-300 rounded"
                                    id="is_active_edit" name="is_active"
                                    <?= (isset($editCourse['is_active']) && $editCourse['is_active']) ? 'checked' : '' ?>>
                                <label for="is_active_edit" class="ml-2 block text-gray-700">Active</label>
                            </div>

                            <div class="mt-8 flex space-x-4">
                                <button type="submit" class="px-6 py-2 bg-gold-500 text-white font-medium rounded-md hover:bg-gold-600 focus:outline-none focus:ring-2 focus:ring-gold-500 focus:ring-offset-2">
                                    Update Course
                                </button>
                                <button type="button" onclick="closeModal('editCourseModal')" class="px-6 py-2 bg-gray-500 text-white font-medium rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Courses Table -->
        <div class="bg-white rounded-lg shadow-md w-full">
            <div class="bg-gray-800 text-white px-6 py-4 rounded-t-lg">
                <h5 class="text-xl font-semibold">Courses List</h5>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Program</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Units</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lecture</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lab</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($courses)): ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-4 text-center text-gray-500">No courses found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($courses as $course): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($course['course_name']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($course['course_code']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($course['program_name'] ?? 'N/A') ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($course['units']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($course['lecture_units']) ?> units<br>
                                            <?= htmlspecialchars($course['lecture_hours']) ?> hours
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($course['lab_units']) ?> units<br>
                                            <?= htmlspecialchars($course['lab_hours']) ?> hours
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $course['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                                <?= $course['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="courses?edit=<?= htmlspecialchars($course['course_id']) ?>&page=<?= $page ?>" class="text-gold-600 hover:text-gold-900 mr-3">Edit</a>
                                            <a href="courses?toggle_status=<?= htmlspecialchars($course['course_id']) ?>&page=<?= $page ?>" class="text-blue-600 hover:text-blue-900"
                                                onclick="return confirm('Are you sure you want to toggle the status?');">
                                                <?= $course['is_active'] ? 'Deactivate' : 'Activate' ?>
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
                    <div class="text-sm text-gray-700">
                        Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $perPage, $totalCourses); ?> of <?php echo $totalCourses; ?> courses
                    </div>
                    <div class="flex space-x-2">
                        <?php if ($page > 1): ?>
                            <a href="courses?page=<?= $page - 1 ?>" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                                Previous
                            </a>
                        <?php else: ?>
                            <span class="px-4 py-2 bg-gray-100 text-gray-400 rounded-md cursor-not-allowed">
                                Previous
                            </span>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="courses?page=<?= $i ?>" class="px-4 py-2 rounded-md <?= $i === $page ? 'bg-gold-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="courses?page=<?= $page + 1 ?>" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                                Next
                            </a>
                        <?php else: ?>
                            <span class="px-4 py-2 bg-gray-100 text-gray-400 rounded-md cursor-not-allowed">
                                Next
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        // Automatically open edit modal if editCourse exists
        window.onload = function() {
            <?php if ($editCourse): ?>
                openModal('editCourseModal');
            <?php endif; ?>
        };
    </script>
</body>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>

</html>