<?php
require_once __DIR__ . '/../../controllers/ChairController.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();

// Get current semester
$currentSemester = $this->db->query("SELECT semester_id, semester_name, academic_year 
                                    FROM semesters 
                                    WHERE is_current = 1")->fetch(PDO::FETCH_ASSOC);

$error = null;
$success = null;

// Filter for previous semesters
$showPrevious = isset($_GET['show_previous']) && $_GET['show_previous'] === '1';
$whereClause = $showPrevious ? "" : "AND s.semester = :current_semester AND s.academic_year = :current_year";
$sections = $this->db->prepare("SELECT s.*, p.program_name 
                               FROM sections s 
                               JOIN programs p ON s.department_id = p.department_id 
                               WHERE s.department_id = :department_id $whereClause 
                               ORDER BY s.year_level, s.section_name");
$params = [':department_id' => $departmentId];
if (!$showPrevious) {
    $params[':current_semester'] = $currentSemester['semester_name'];
    $params[':current_year'] = $currentSemester['academic_year'];
}
$sections->execute($params);
$sections = $sections->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section_name = $_POST['section_name'];
    $year_level = $_POST['year_level'];
    $max_students = $_POST['max_students'] ?? 40;

    // Set current semester and academic year automatically
    $semester = $currentSemester['semester_name'];
    $academic_year = $currentSemester['academic_year'];

    $stmt = $this->db->prepare("INSERT INTO sections (section_name, department_id, year_level, semester, academic_year, max_students, is_active, created_at, updated_at) 
                                VALUES (:section_name, :department_id, :year_level, :semester, :academic_year, :max_students, 1, NOW(), NOW()) 
                                ON DUPLICATE KEY UPDATE section_name = VALUES(section_name), 
                                year_level = VALUES(year_level), semester = VALUES(semester), academic_year = VALUES(academic_year), 
                                max_students = VALUES(max_students), updated_at = NOW()");

    $stmt->execute([
        ':section_name' => $section_name,
        ':department_id' => $departmentId,
        ':year_level' => $year_level,
        ':semester' => $semester,
        ':academic_year' => $academic_year,
        ':max_students' => $max_students
    ]);

    header("Location: sections" . ($showPrevious ? "?show_previous=1" : ""));
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Sections</title>
    <link rel="stylesheet" href="/css/output.css">
</head>

<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Manage Sections - <?= htmlspecialchars($currentSemester['semester_name'] . ' ' . $currentSemester['academic_year']) ?></h1>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Filter and Add Section Button -->
        <div class="mb-6 flex space-x-4 justify-end">
            <a href="?show_previous=<?= $showPrevious ? '0' : '1' ?>" class="px-6 py-2 bg-gold-500 text-white font-medium rounded-md hover:bg-gold-600 focus:outline-none focus:ring-2 focus:ring-gold-500 focus:ring-offset-2">
                <?= $showPrevious ? 'Show Current Semester Only' : 'Show Previous Semesters' ?>
            </a>
            <button onclick="openModal('addSectionModal')" class="px-6 py-2 bg-gold-500 text-white font-medium rounded-md hover:bg-gold-600 focus:outline-none focus:ring-2 focus:ring-gold-500 focus:ring-offset-2">
                Add New Section
            </button>
        </div>

        <!-- Add Section Modal -->
        <div id="addSectionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
            <div class="bg-white rounded-lg shadow-md w-full max-w-2xl">
                <div class="bg-gray-800 text-white px-6 py-4 rounded-t-lg flex justify-between items-center">
                    <h5 class="text-xl font-semibold">Add New Section</h5>
                    <button onclick="closeModal('addSectionModal')" class="text-white hover:text-gray-300 focus:outline-none">×</button>
                </div>
                <div class="p-6">
                    <form method="POST">
                        <div class="grid grid-cols-1 gap-6">
                            <div class="mb-4">
                                <label for="section_name" class="block text-gray-700 font-medium mb-2">Section Name</label>
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500"
                                    id="section_name" name="section_name" required>
                            </div>
                            <div class="grid grid-cols-2 gap-6">
                                <div class="mb-4">
                                    <label for="year_level" class="block text-gray-700 font-medium mb-2">Year Level</label>
                                    <select class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500"
                                        id="year_level" name="year_level" required>
                                        <option value="1st Year">1st Year</option>
                                        <option value="2nd Year">2nd Year</option>
                                        <option value="3rd Year">3rd Year</option>
                                        <option value="4th Year">4th Year</option>
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label for="max_students" class="block text-gray-700 font-medium mb-2">Max Students</label>
                                    <input type="number" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500"
                                        id="max_students" name="max_students" value="40" min="1" required>
                                </div>
                            </div>
                        </div>
                        <div class="mt-8 flex space-x-4">
                            <button type="submit" class="px-6 py-2 bg-gold-500 text-white font-medium rounded-md hover:bg-gold-600 focus:outline-none focus:ring-2 focus:ring-gold-500 focus:ring-offset-2">
                                Add Section
                            </button>
                            <button type="button" onclick="closeModal('addSectionModal')" class="px-6 py-2 bg-gray-500 text-white font-medium rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Section Modal -->
        <div id="editSectionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
            <div class="bg-white rounded-lg shadow-md w-full max-w-2xl">
                <div class="bg-gray-800 text-white px-6 py-4 rounded-t-lg flex justify-between items-center">
                    <h5 class="text-xl font-semibold">Edit Section</h5>
                    <button onclick="closeModal('editSectionModal')" class="text-white hover:text-gray-300 focus:outline-none">×</button>
                </div>
                <div class="p-6">
                    <form method="POST">
                        <input type="hidden" id="edit_section_id" name="section_id">
                        <div class="grid grid-cols-1 gap-6">
                            <div class="mb-4">
                                <label for="edit_section_name" class="block text-gray-700 font-medium mb-2">Section Name</label>
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500"
                                    id="edit_section_name" name="section_name" required>
                            </div>
                            <div class="grid grid-cols-3 gap-6">
                                <div class="mb-4">
                                    <label for="edit_year_level" class="block text-gray-700 font-medium mb-2">Year Level</label>
                                    <select class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500"
                                        id="edit_year_level" name="year_level" required>
                                        <option value="1st Year">1st Year</option>
                                        <option value="2nd Year">2nd Year</option>
                                        <option value="3rd Year">3rd Year</option>
                                        <option value="4th Year">4th Year</option>
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label for="edit_semester" class="block text-gray-700 font-medium mb-2">Semester</label>
                                    <select class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500"
                                        id="edit_semester" name="semester" required>
                                        <option value="1st">1st</option>
                                        <option value="2nd">2nd</option>
                                        <option value="Summer">Summer</option>
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label for="edit_academic_year" class="block text-gray-700 font-medium mb-2">Academic Year</label>
                                    <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500"
                                        id="edit_academic_year" name="academic_year" required>
                                </div>
                                <div class="mb-4">
                                    <label for="edit_max_students" class="block text-gray-700 font-medium mb-2">Max Students</label>
                                    <input type="number" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500"
                                        id="edit_max_students" name="max_students" min="1" required>
                                </div>
                            </div>
                        </div>
                        <div class="mt-8 flex space-x-4">
                            <button type="submit" class="px-6 py-2 bg-gold-500 text-white font-medium rounded-md hover:bg-gold-600 focus:outline-none focus:ring-2 focus:ring-gold-500 focus:ring-offset-2">
                                Update Section
                            </button>
                            <button type="button" onclick="closeModal('editSectionModal')" class="px-6 py-2 bg-gray-500 text-white font-medium rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sections Table -->
        <div class="bg-white rounded-lg shadow-md">
            <div class="bg-gray-800 text-white px-6 py-4 rounded-t-lg">
                <h5 class="text-xl font-semibold">Sections List</h5>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <?php
                    $yearLevels = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
                    foreach ($yearLevels as $level): ?>
                        <div class="mb-6">
                            <h3 class="text-xl font-semibold text-gray-800 mb-4"><?= htmlspecialchars($level) ?></h3>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Semester</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Academic Year</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Max Students</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php
                                    $levelSections = array_filter($sections, fn($s) => $s['year_level'] === $level);
                                    if (empty($levelSections)): ?>
                                        <tr>
                                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">No sections found for <?= htmlspecialchars($level) ?>.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($levelSections as $section): ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($section['section_name']) ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($section['semester']) ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($section['academic_year']) ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($section['max_students']) ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <button onclick="editSection(<?= htmlspecialchars(json_encode($section)) ?>)" class="text-gold-600 hover:text-gold-900 mr-3">Edit</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach; ?>
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

        function editSection(section) {
            document.getElementById('edit_section_id').value = section.section_id;
            document.getElementById('edit_section_name').value = section.section_name;
            document.getElementById('edit_year_level').value = section.year_level;
            document.getElementById('edit_semester').value = section.semester;
            document.getElementById('edit_academic_year').value = section.academic_year;
            document.getElementById('edit_max_students').value = section.max_students;
            openModal('editSectionModal');
        }
    </script>
</body>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>

</html>