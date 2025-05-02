<?php
require_once __DIR__ . '/../../controllers/ChairController.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();

$searchTerm = $_GET['search'] ?? '';
$error = $error ?? null; // From controller
$availableClassrooms = $availableClassrooms ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $room_name = $_POST['room_name'] ?? '';
    $capacity = $_POST['capacity'] ?? 0;
    $department_id = $departmentId;
    $availability = isset($_POST['availability']) ? 1 : 0;

    if ($action === 'add') {
        $stmt = $this->db->prepare("INSERT INTO classrooms (room_name, capacity, department_id, availability, created_at, updated_at) 
                                    VALUES (:room_name, :capacity, :department_id, :availability, NOW(), NOW()) 
                                    ON DUPLICATE KEY UPDATE capacity = VALUES(capacity), availability = VALUES(availability), updated_at = NOW()");
        $stmt->execute([
            ':room_name' => $room_name,
            ':capacity' => $capacity,
            ':department_id' => $department_id,
            ':availability' => $availability
        ]);
    } elseif ($action === 'edit') {
        $room_id = $_POST['room_id'];
        $stmt = $this->db->prepare("UPDATE classrooms SET room_name = :room_name, capacity = :capacity, availability = :availability, updated_at = NOW() 
                                    WHERE room_id = :room_id AND department_id = :department_id");
        $stmt->execute([
            ':room_id' => $room_id,
            ':room_name' => $room_name,
            ':capacity' => $capacity,
            ':availability' => $availability,
            ':department_id' => $department_id
        ]);
    } elseif ($action === 'search') {
        $search_date = $_POST['search_date'] ?? date('Y-m-d');
        $search_time = $_POST['search_time'] ?? '08:00:00';
        $search_department = $_POST['search_department'] ?? '%';
        $stmt = $this->db->prepare("SELECT c.* 
                            FROM classrooms c 
                            LEFT JOIN schedules s ON c.room_id = s.room_id 
                            AND s.schedule_date = :search_date 
                            AND s.start_time <= :search_time 
                            AND s.end_time > :search_time 
                            WHERE c.department_id LIKE :search_department 
                            AND (s.room_id IS NULL OR s.status = 'Rejected') 
                            AND c.availability = 'available' 
                            ORDER BY c.room_name");
        $stmt->execute([
            ':search_date' => $search_date,
            ':search_time' => $search_time,
            ':search_department' => $search_department
        ]);
        $availableClassrooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    header("Location: classroom");
    exit;
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Classrooms</title>
    <link rel="stylesheet" href="/css/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Manage Classrooms</h1>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Search and Action Bar -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
            <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
                <form method="GET" class="w-full md:w-auto">
                    <div class="relative">
                        <input type="text" name="search" placeholder="Search classrooms..."
                            class="w-full md:w-96 pl-10 pr-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-gold-500"
                            value="<?= htmlspecialchars($searchTerm) ?>">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </form>
                <div class="flex gap-2 w-full md:w-auto">
                    <button onclick="openModal('addClassroomModal')"
                        class="w-full md:w-auto px-4 py-2 bg-gold-500 text-white rounded-lg hover:bg-gold-600 transition-colors">
                        <i class="fas fa-plus mr-2"></i> Add Classroom
                    </button>
                    <button onclick="openModal('searchClassroomModal')"
                        class="w-full md:w-auto px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700 transition-colors">
                        <i class="fas fa-search mr-2"></i> Check Availability
                    </button>
                </div>
            </div>
        </div>

        <!-- Add Classroom Modal -->
        <div id="addClassroomModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
            <div class="bg-white rounded-lg shadow-md w-full max-w-2xl">
                <div class="bg-gray-800 text-white px-6 py-4 rounded-t-lg flex justify-between items-center">
                    <h5 class="text-xl font-semibold">Add Classroom</h5>
                    <button onclick="closeModal('addClassroomModal')" class="text-white hover:text-gray-300 focus:outline-none">×</button>
                </div>
                <div class="p-6">
                    <form method="POST">
                        <input type="hidden" name="action" value="add">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="mb-4">
                                <label for="room_name" class="block text-gray-700 font-medium mb-2">Room Name</label>
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500" id="room_name" name="room_name" required>
                            </div>
                            <div class="mb-4">
                                <label for="capacity" class="block text-gray-700 font-medium mb-2">Capacity</label>
                                <input type="number" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500" id="capacity" name="capacity" min="1" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 font-medium mb-2">Availability</label>
                            <label class="inline-flex items-center mr-4">
                                <input type="radio" name="availability" value="1" checked class="form-radio text-gold-500">
                                <span class="ml-2 text-gray-700">Available</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="availability" value="0" class="form-radio text-gold-500">
                                <span class="ml-2 text-gray-700">Unavailable</span>
                            </label>
                        </div>
                        <div class="mt-8 flex space-x-4">
                            <button type="submit" class="px-6 py-2 bg-gold-500 text-white font-medium rounded-md hover:bg-gold-600 focus:outline-none focus:ring-2 focus:ring-gold-500 focus:ring-offset-2">
                                Add Classroom
                            </button>
                            <button type="button" onclick="closeModal('addClassroomModal')" class="px-6 py-2 bg-gray-500 text-white font-medium rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Classroom Modal -->
        <div id="editClassroomModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
            <div class="bg-white rounded-lg shadow-md w-full max-w-2xl">
                <div class="bg-gray-800 text-white px-6 py-4 rounded-t-lg flex justify-between items-center">
                    <h5 class="text-xl font-semibold">Edit Classroom</h5>
                    <button onclick="closeModal('editClassroomModal')" class="text-white hover:text-gray-300 focus:outline-none">×</button>
                </div>
                <div class="p-6">
                    <form method="POST">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" id="edit_room_id" name="room_id">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="mb-4">
                                <label for="edit_room_name" class="block text-gray-700 font-medium mb-2">Room Name</label>
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500" id="edit_room_name" name="room_name" required>
                            </div>
                            <div class="mb-4">
                                <label for="edit_capacity" class="block text-gray-700 font-medium mb-2">Capacity</label>
                                <input type="number" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500" id="edit_capacity" name="capacity" min="1" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 font-medium mb-2">Availability</label>
                            <label class="inline-flex items-center mr-4">
                                <input type="radio" name="availability" value="1" class="form-radio text-gold-500">
                                <span class="ml-2 text-gray-700">Available</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="availability" value="0" class="form-radio text-gold-500">
                                <span class="ml-2 text-gray-700">Unavailable</span>
                            </label>
                        </div>
                        <div class="mt-8 flex space-x-4">
                            <button type="submit" class="px-6 py-2 bg-gold-500 text-white font-medium rounded-md hover:bg-gold-600 focus:outline-none focus:ring-2 focus:ring-gold-500 focus:ring-offset-2">
                                Update Classroom
                            </button>
                            <button type="button" onclick="closeModal('editClassroomModal')" class="px-6 py-2 bg-gray-500 text-white font-medium rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Search Classroom Modal -->
        <div id="searchClassroomModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
            <div class="bg-white rounded-lg shadow-md w-full max-w-2xl">
                <div class="bg-gray-800 text-white px-6 py-4 rounded-t-lg flex justify-between items-center">
                    <h5 class="text-xl font-semibold">Search Available Classroom</h5>
                    <button onclick="closeModal('searchClassroomModal')" class="text-white hover:text-gray-300 focus:outline-none">×</button>
                </div>
                <div class="p-6">
                    <form method="POST">
                        <input type="hidden" name="action" value="search">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="mb-4">
                                <label for="search_date" class="block text-gray-700 font-medium mb-2">Date</label>
                                <input type="date" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500" id="search_date" name="search_date" required>
                            </div>
                            <div class="mb-4">
                                <label for="search_time" class="block text-gray-700 font-medium mb-2">Time</label>
                                <input type="time" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500" id="search_time" name="search_time" required>
                            </div>
                            <div class="mb-4">
                                <label for="search_department" class="block text-gray-700 font-medium mb-2">Department</label>
                                <select class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold-500" id="search_department" name="search_department">
                                    <option value="%">All Departments</option>
                                    <?php
                                    $departments = $this->db->query("SELECT department_id, department_name FROM departments")->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($departments as $dept): ?>
                                        <option value="<?= htmlspecialchars($dept['department_id']) ?>">
                                            <?= htmlspecialchars($dept['department_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mt-8 flex space-x-4">
                            <button type="submit" class="px-6 py-2 bg-gold-500 text-white font-medium rounded-md hover:bg-gold-600 focus:outline-none focus:ring-2 focus:ring-gold-500 focus:ring-offset-2">
                                Search
                            </button>
                            <button type="button" onclick="closeModal('searchClassroomModal')" class="px-6 py-2 bg-gray-500 text-white font-medium rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                                Cancel
                            </button>
                        </div>
                    </form>
                    <?php if (isset($availableClassrooms) && !empty($availableClassrooms)): ?>
                        <div class="mt-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Available Classrooms</h3>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Capacity</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php
                                    $departments = array_column($this->db->query("SELECT department_id, department_name FROM departments")->fetchAll(PDO::FETCH_ASSOC), 'department_name', 'department_id');
                                    foreach ($availableClassrooms as $room): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($room['room_name']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($room['capacity']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($departments[$room['department_id']] ?? 'Unknown') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Classrooms Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-gray-800 text-white px-6 py-4">
                <h5 class="text-xl font-semibold">Classrooms List</h5>
            </div>
            <div class="p-6">
                <?php if (empty($classrooms)): ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-door-open text-4xl mb-4"></i>
                        <p>No classrooms found.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full table-auto">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Room Name</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Capacity</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Department</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">College</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Status</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($classrooms as $classroom): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3 text-sm text-gray-900 font-medium"><?= htmlspecialchars($classroom['room_name']) ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-600"><?= htmlspecialchars($classroom['capacity']) ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-600"><?= htmlspecialchars($classroom['department_name'] ?? 'N/A') ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-600"><?= htmlspecialchars($classroom['college_name'] ?? 'N/A') ?></td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $classroom['availability'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                                <?= $classroom['availability'] ? 'Available' : 'Unavailable' ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 space-x-2">
                                            <button onclick="editClassroom(<?= htmlspecialchars(json_encode($classroom)) ?>)" class="text-gold-600 hover:text-gold-800">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
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

        function editClassroom(classroom) {
            document.getElementById('edit_room_id').value = classroom.room_id;
            document.getElementById('edit_room_name').value = classroom.room_name;
            document.getElementById('edit_capacity').value = classroom.capacity;
            document.querySelector(`input[name="availability"][value="${classroom.availability}"]`).checked = true;
            openModal('editClassroomModal');
        }
    </script>
</body>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>

</html>