<?php
require_once __DIR__ . '/../../controllers/ChairController.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();

$searchTerm = $_GET['search'] ?? '';
$error = $error ?? null;
$availableClassrooms = $availableClassrooms ?? [];

// Ensure $this->db is available
$controller = new ChairController();
$db = $controller->db;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $room_name = $_POST['room_name'] ?? '';
    $building = $_POST['building'] ?? '';
    $capacity = (int)($_POST['capacity'] ?? 0);
    $room_type = $_POST['room_type'] ?? 'classroom';
    $shared = isset($_POST['shared']) ? 1 : 0;
    $availability = $_POST['availability'] ?? 'available';
    $department_id = $departmentId;

    try {
        if ($action === 'add') {
            $stmt = $db->prepare("
                INSERT INTO classrooms 
                (room_name, building, capacity, room_type, shared, availability, department_id, created_at, updated_at) 
                VALUES (:room_name, :building, :capacity, :room_type, :shared, :availability, :department_id, NOW(), NOW())
            ");
            $stmt->execute([
                ':room_name' => $room_name,
                ':building' => $building,
                ':capacity' => $capacity,
                ':room_type' => $room_type,
                ':shared' => $shared,
                ':availability' => $availability,
                ':department_id' => $department_id
            ]);
        } elseif ($action === 'edit') {
            $room_id = (int)($_POST['room_id'] ?? 0);
            $stmt = $db->prepare("
                UPDATE classrooms SET 
                    room_name = :room_name,
                    building = :building,
                    capacity = :capacity,
                    room_type = :room_type,
                    shared = :shared,
                    availability = :availability,
                    updated_at = NOW()
                WHERE room_id = :room_id AND department_id = :department_id
            ");
            $stmt->execute([
                ':room_id' => $room_id,
                ':room_name' => $room_name,
                ':building' => $building,
                ':capacity' => $capacity,
                ':room_type' => $room_type,
                ':shared' => $shared,
                ':availability' => $availability,
                ':department_id' => $department_id
            ]);
        } elseif ($action === 'search') {
            $search_date = $_POST['search_date'] ?? date('Y-m-d');
            $search_time = $_POST['search_time'] ?? '08:00:00';
            $search_department = $_POST['search_department'] ?? '%';
            $min_capacity = (int)($_POST['min_capacity'] ?? 0);
            $room_type = $_POST['room_type'] ?? '';

            $query = "
                SELECT DISTINCT c.*, d.department_name, cl.college_name
                FROM classrooms c
                JOIN departments d ON c.department_id = d.department_id
                JOIN colleges cl ON d.college_id = cl.college_id
                LEFT JOIN schedules s ON c.room_id = s.room_id 
                    AND s.schedule_date = :search_date 
                    AND s.start_time <= :search_time 
                    AND s.end_time > :search_time
                WHERE (c.department_id LIKE :search_department OR c.shared = 1)
                    AND (s.room_id IS NULL OR s.status = 'Rejected')
                    AND c.availability = 'available'
            ";
            $params = [
                ':search_date' => $search_date,
                ':search_time' => $search_time,
                ':search_department' => $search_department
            ];

            if ($min_capacity > 0) {
                $query .= " AND c.capacity >= :min_capacity";
                $params[':min_capacity'] = $min_capacity;
            }
            if (!empty($room_type)) {
                $query .= " AND c.room_type = :room_type";
                $params[':room_type'] = $room_type;
            }

            $query .= " ORDER BY c.room_name";
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $availableClassrooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        header("Location: classroom");
        exit;
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
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
                        <?php if ($departmentInfo): ?>
                            <div class="mb-4 bg-gray-50 p-4 rounded-lg">
                                <p class="text-sm text-gray-600">
                                    This classroom will be assigned to:
                                    <span class="font-medium">
                                        <?= htmlspecialchars($departmentInfo['department_name']) ?>
                                        (<?= htmlspecialchars($departmentInfo['college_name']) ?>)
                                    </span>
                                </p>
                            </div>
                        <?php endif; ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="mb-4">
                                <label for="room_name" class="block text-gray-700 font-medium mb-2">Room Name</label>
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-md" id="room_name" name="room_name" required>
                            </div>
                            <div class="mb-4">
                                <label for="building" class="block text-gray-700 font-medium mb-2">Building</label>
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-md" id="building" name="building" required>
                            </div>
                            <div class="mb-4">
                                <label for="capacity" class="block text-gray-700 font-medium mb-2">Capacity</label>
                                <input type="number" class="w-full px-4 py-2 border border-gray-300 rounded-md" id="capacity" name="capacity" min="1" required>
                            </div>
                            <div class="mb-4">
                                <label for="room_type" class="block text-gray-700 font-medium mb-2">Room Type</label>
                                <select class="w-full px-4 py-2 border border-gray-300 rounded-md" id="room_type" name="room_type" required>
                                    <option value="classroom">Classroom</option>
                                    <option value="laboratory">Laboratory</option>
                                    <option value="auditorium">Auditorium</option>
                                    <option value="seminar_room">Seminar Room</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="block text-gray-700 font-medium mb-2">Shared</label>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="shared" value="1" class="form-checkbox text-gold-500">
                                    <span class="ml-2 text-gray-700">Share with other departments</span>
                                </label>
                            </div>
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
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-md" id="edit_room_name" name="room_name" required>
                            </div>
                            <div class="mb-4">
                                <label for="edit_building" class="block text-gray-700 font-medium mb-2">Building</label>
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-md" id="edit_building" name="building" required>
                            </div>
                            <div class="mb-4">
                                <label for="edit_capacity" class="block text-gray-700 font-medium mb-2">Capacity</label>
                                <input type="number" class="w-full px-4 py-2 border border-gray-300 rounded-md" id="edit_capacity" name="capacity" min="1" required>
                            </div>
                            <div class="mb-4">
                                <label for="edit_room_type" class="block text-gray-700 font-medium mb-2">Room Type</label>
                                <select class="w-full px-4 py-2 border border-gray-300 rounded-md" id="edit_room_type" name="room_type" required>
                                    <option value="classroom">Classroom</option>
                                    <option value="laboratory">Laboratory</option>
                                    <option value="auditorium">Auditorium</option>
                                    <option value="seminar_room">Seminar Room</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="block text-gray-700 font-medium mb-2">Shared</label>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" id="edit_shared" name="shared" value="1" class="form-checkbox text-gold-500">
                                    <span class="ml-2 text-gray-700">Share with other departments</span>
                                </label>
                            </div>
                            <div class="mb-4">
                                <label class="block text-gray-700 font-medium mb-2">Availability</label>
                                <select class="w-full px-4 py-2 border border-gray-300 rounded-md" id="edit_availability" name="availability" required>
                                    <option value="available">Available</option>
                                    <option value="unavailable">Unavailable</option>
                                    <option value="under_maintenance">Under Maintenance</option>
                                </select>
                            </div>
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
                    <h5 class="text-xl font-semibold">Search Available Classrooms</h5>
                    <button onclick="closeModal('searchClassroomModal')" class="text-white hover:text-gray-300 focus:outline-none">×</button>
                </div>
                <div class="p-6">
                    <form method="POST">
                        <input type="hidden" name="action" value="search">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="mb-4">
                                <label for="search_date" class="block text-gray-700 font-medium mb-2">Date</label>
                                <input type="date" class="w-full px-4 py-2 border border-gray-300 rounded-md" id="search_date" name="search_date" required>
                            </div>
                            <div class="mb-4">
                                <label for="search_time" class="block text-gray-700 font-medium mb-2">Time</label>
                                <input type="time" class="w-full px-4 py-2 border border-gray-300 rounded-md" id="search_time" name="search_time" required>
                            </div>
                            <div class="mb-4">
                                <label for="search_department" class="block text-gray-700 font-medium mb-2">Department</label>
                                <select class="w-full px-4 py-2 border border-gray-300 rounded-md" id="search_department" name="search_department">
                                    <option value="%">All Departments</option>
                                    <?php
                                    $departments = $db->query("SELECT department_id, department_name FROM departments ORDER BY department_name")->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($departments as $dept): ?>
                                        <option value="<?= htmlspecialchars($dept['department_id']) ?>">
                                            <?= htmlspecialchars($dept['department_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label for="min_capacity" class="block text-gray-700 font-medium mb-2">Minimum Capacity</label>
                                <input type="number" class="w-full px-4 py-2 border border-gray-300 rounded-md" id="min_capacity" name="min_capacity" min="0" value="0">
                            </div>
                            <div class="mb-4">
                                <label for="room_type" class="block text-gray-700 font-medium mb-2">Room Type</label>
                                <select class="w-full px-4 py-2 border border-gray-300 rounded-md" id="room_type" name="room_type">
                                    <option value="">Any</option>
                                    <option value="classroom">Classroom</option>
                                    <option value="laboratory">Laboratory</option>
                                    <option value="auditorium">Auditorium</option>
                                    <option value="seminar_room">Seminar Room</option>
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
                    <?php if (!empty($availableClassrooms)): ?>
                        <div class="mt-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Available Classrooms</h3>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Building</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Capacity</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shared</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($availableClassrooms as $room): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($room['room_name']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($room['building']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($room['capacity']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars(ucfirst($room['room_type'])) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($room['department_name']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $room['shared'] ? 'Yes' : 'No' ?></td>
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
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Building</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Capacity</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Room Type</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Department</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">College</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Shared</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Status</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($classrooms as $classroom): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3 text-sm text-gray-900 font-medium">
                                            <?= htmlspecialchars($classroom['room_name']) ?>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            <?= htmlspecialchars($classroom['building']) ?>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            <?= htmlspecialchars($classroom['capacity']) ?>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            <?= htmlspecialchars(ucfirst($classroom['room_type'])) ?>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            <?= htmlspecialchars($classroom['department_name'] ?? 'N/A') ?>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            <?= htmlspecialchars($classroom['college_name'] ?? 'N/A') ?>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            <?= $classroom['shared'] ? 'Yes' : 'No' ?>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $classroom['availability'] === 'available' ? 'bg-green-100 text-green-800' : ($classroom['availability'] === 'unavailable' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') ?>">
                                                <?= htmlspecialchars(ucfirst($classroom['availability'])) ?>
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
            document.getElementById('edit_building').value = classroom.building;
            document.getElementById('edit_capacity').value = classroom.capacity;
            document.getElementById('edit_room_type').value = classroom.room_type;
            document.getElementById('edit_availability').value = classroom.availability;
            document.getElementById('edit_shared').checked = classroom.shared == 1;
            openModal('editClassroomModal');
        }
    </script>
</body>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>

</html>