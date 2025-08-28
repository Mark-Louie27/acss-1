<?php
require_once __DIR__ . '/../../controllers/ChairController.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();

$searchTerm = $_GET['search'] ?? '';
$error = $error ?? null;
$availableClassrooms = $availableClassrooms ?? [];
$classrooms = $classrooms ?? []; // Add this to ensure it's defined
$departmentInfo = $departmentInfo ?? null;
$departments = $departments ?? []; // Ensure departments is defined

// Ensure $this->db is available
$controller = new ChairController();
$db = $controller->db;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $room_name = $_POST['room_name'] ?? '';
    $building = $_POST['building'] ?? '';
    $capacity = (int)($_POST['capacity'] ?? 0);
    $room_type = $_POST['room_type'] ?? 'lecture';
    $shared = isset($_POST['shared']) ? 1 : 0;
    $availability = $_POST['availability'] ?? 'available';
    $department_id = $departmentId ?? ($departmentInfo['department_id'] ?? null); // Use departmentInfo if available

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
            $search_department = $_POST['search_department'] ?? '%'; // Default to % if not set
            $min_capacity = (int)($_POST['min_capacity'] ?? 0);
            $room_type = $_POST['room_type'] ?? '';

            // Log the input parameters for debugging
            error_log("Search parameters: search_date=$search_date, search_time=$search_time, search_department=$search_department, min_capacity=$min_capacity, room_type=$room_type");

            $query = "
        SELECT DISTINCT c.*, d.department_name, cl.college_name
        FROM classrooms c
        JOIN departments d ON c.department_id = d.department_id
        JOIN colleges cl ON d.college_id = cl.college_id
        LEFT JOIN schedules s ON c.room_id = s.room_id 
            AND s.schedule_type = :search_date 
            AND s.start_time <= :search_time 
            AND s.end_time > :search_time
        WHERE (c.department_id LIKE :search_department OR c.shared = 1)
            AND (s.room_id IS NULL OR s.status = 'Rejected')
            AND c.availability = 'available'
    ";
            $params = [
                ':search_date' => $search_date,
                ':search_time' => $search_time,
                ':search_department' => $search_department // Use as-is, but validate below
            ];

            // Validate and adjust search_department
            if (!is_string($search_department) || (strlen($search_department) > 0 && !preg_match('/^%|[0-9]+$/', $search_department))) {
                error_log("Invalid search_department value: $search_department");
                $search_department = '%'; // Fallback to all departments
                $params[':search_department'] = $search_department;
            }

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

            // Log the final query and parameters for debugging
            error_log("Executing query: $query with params: " . json_encode($params));

            try {
                $stmt->execute($params);
                $availableClassrooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log("Search query failed: " . $e->getMessage());
                $error = "Database error during search: Invalid parameter.";
                $availableClassrooms = [];
            }
        }

        header("Location: classroom");
        exit;
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Define modal content (unchanged)
ob_start();
?>
<!-- Add Classroom Modal -->
<div id="addClassroomModal" class="modal-overlay hidden fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-4">
    <div class="modal-content bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <!-- Modal Header -->
        <div class="bg-gray-800 text-white px-6 py-4 rounded-t-lg flex justify-between items-center sticky top-0 z-10">
            <h5 class="text-xl font-semibold">Add New Classroom</h5>
            <button onclick="closeModal('addClassroomModal')" class="text-white hover:text-indigo-200 focus:outline-none text-2xl transition-colors" aria-label="Close modal">
                &times;
            </button>
        </div>

        <!-- Modal Body -->
        <div class="p-6">
            <form method="POST" class="space-y-6">
                <input type="hidden" name="action" value="add">

                <?php if ($departmentInfo): ?>
                    <div class="bg-indigo-50 p-4 rounded-lg border border-indigo-100">
                        <p class="text-sm text-indigo-800">
                            <span class="font-medium">Department Assignment:</span><br>
                            <?= htmlspecialchars($departmentInfo['department_name']) ?>
                            (<?= htmlspecialchars($departmentInfo['college_name']) ?>)
                        </p>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Room Name -->
                    <div class="input-group">
                        <label for="room_name" class="block text-gray-700 font-medium mb-2">Room Name <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">
                                <i class="fas fa-door-open"></i>
                            </div>
                            <input type="text" id="room_name" name="room_name" required
                                class="pl-10 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-700 focus:border-gray-700 transition-all">
                        </div>
                    </div>

                    <!-- Building -->
                    <div class="input-group">
                        <label for="building" class="block text-gray-700 font-medium mb-2">Building <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">
                                <i class="fas fa-building"></i>
                            </div>
                            <input type="text" id="building" name="building" required
                                class="pl-10 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-700 focus:border-gray-700 transition-all">
                        </div>
                    </div>

                    <!-- Capacity -->
                    <div class="input-group">
                        <label for="capacity" class="block text-gray-700 font-medium mb-2">Capacity <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">
                                <i class="fas fa-users"></i>
                            </div>
                            <input type="number" id="capacity" name="capacity" min="1" required
                                class="pl-10 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-700 focus:border-gray-700 transition-all">
                        </div>
                    </div>

                    <!-- Room Type -->
                    <div class="input-group">
                        <label for="room_type" class="block text-gray-700 font-medium mb-2">Room Type <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">
                                <i class="fas fa-chalkboard"></i>
                            </div>
                            <select id="room_type" name="room_type" required
                                class="pl-10 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-700 focus:border-gray-700 appearance-none bg-white">
                                <option value="">Select Room Type</option>
                                <option value="lecture">Lecture Room</option>
                                <option value="laboratory">Laboratory</option>
                                <option value="avr">AVR/Multimedia Room</option>
                                <option value="seminar_room">Seminar Room</option>
                            </select>
                        </div>
                    </div>

                    <!-- Shared Checkbox -->
                    <div class="input-group">
                        <label class="block text-gray-700 font-medium mb-2">Sharing Options</label>
                        <label class="inline-flex items-center space-x-3 cursor-pointer">
                            <div class="relative">
                                <input type="checkbox" name="shared" value="1" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gray-800"></div>
                            </div>
                            <span class="text-gray-700">Share with other departments</span>
                        </label>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="mt-8 flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('addClassroomModal')"
                        class="px-6 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-700 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-6 py-2.5 bg-gray-800 text-white rounded-lg font-medium shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-700 transition-colors flex items-center space-x-2">
                        <i class="fas fa-plus"></i>
                        <span>Add Classroom</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Classroom Modal -->
<div id="editClassroomModal" class="modal-overlay hidden fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-4">
    <div class="modal-content bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <!-- Modal Header -->
        <div class="bg-gray-800 text-white px-6 py-4 rounded-t-lg flex justify-between items-center sticky top-0 z-10">
            <h5 class="text-xl font-semibold">Edit Classroom</h5>
            <button onclick="closeModal('editClassroomModal')" class="text-white hover:text-indigo-200 focus:outline-none text-2xl transition-colors" aria-label="Close modal">
                &times;
            </button>
        </div>

        <!-- Modal Body -->
        <div class="p-6">
            <form method="POST" class="space-y-6">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_room_id" name="room_id">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Room Name -->
                    <div class="input-group">
                        <label for="edit_room_name" class="block text-gray-700 font-medium mb-2">Room Name <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">
                                <i class="fas fa-door-open"></i>
                            </div>
                            <input type="text" id="edit_room_name" name="room_name" required
                                class="pl-10 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-700 focus:border-gray-700 transition-all">
                        </div>
                    </div>

                    <!-- Building -->
                    <div class="input-group">
                        <label for="edit_building" class="block text-gray-700 font-medium mb-2">Building <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">
                                <i class="fas fa-building"></i>
                            </div>
                            <input type="text" id="edit_building" name="building" required
                                class="pl-10 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-700 focus:border-gray-700 transition-all">
                        </div>
                    </div>

                    <!-- Capacity -->
                    <div class="input-group">
                        <label for="edit_capacity" class="block text-gray-700 font-medium mb-2">Capacity <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">
                                <i class="fas fa-users"></i>
                            </div>
                            <input type="number" id="edit_capacity" name="capacity" min="1" required
                                class="pl-10 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-700 focus:border-gray-700 transition-all">
                        </div>
                    </div>

                    <!-- Room Type -->
                    <div class="input-group">
                        <label for="edit_room_type" class="block text-gray-700 font-medium mb-2">Room Type <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">
                                <i class="fas fa-chalkboard"></i>
                            </div>
                            <select id="edit_room_type" name="room_type" required
                                class="pl-10 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-700 focus:border-gray-700 appearance-none bg-white">
                                <option value="lecture">Lecture Room</option>
                                <option value="laboratory">Laboratory</option>
                                <option value="avr">AVR/Multimedia Room</option>
                                <option value="seminar_room">Seminar Room</option>
                            </select>
                        </div>
                    </div>

                    <!-- Shared Checkbox -->
                    <div class="input-group">
                        <label class="block text-gray-700 font-medium mb-2">Sharing Options</label>
                        <label class="inline-flex items-center space-x-3 cursor-pointer">
                            <div class="relative">
                                <input type="checkbox" id="edit_shared" name="shared" value="1" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gray-800"></div>
                            </div>
                            <span class="text-gray-700">Share with other departments</span>
                        </label>
                    </div>

                    <!-- Availability -->
                    <div class="input-group">
                        <label for="edit_availability" class="block text-gray-700 font-medium mb-2">Status <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <select id="edit_availability" name="availability" required
                                class="pl-10 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-700 focus:border-gray-700 appearance-none bg-white">
                                <option value="available">Available</option>
                                <option value="unavailable">Unavailable</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="mt-8 flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('editClassroomModal')"
                        class="px-6 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-700 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-6 py-2.5 bg-gray-800 text-white rounded-lg font-medium shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-700 transition-colors flex items-center space-x-2">
                        <i class="fas fa-save"></i>
                        <span>Save Changes</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Search Classroom Modal -->
<div id="searchClassroomModal" class="modal-overlay hidden fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-4">
    <div class="modal-content bg-white rounded-lg shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <!-- Modal Header -->
        <div class="bg-gray-800 text-white px-6 py-4 rounded-t-lg flex justify-between items-center sticky top-0 z-10">
            <h5 class="text-xl font-semibold">Search Available Classrooms</h5>
            <button onclick="closeModal('searchClassroomModal')" class="text-white hover:text-indigo-200 focus:outline-none text-2xl transition-colors" aria-label="Close modal">
                &times;
            </button>
        </div>

        <!-- Modal Body -->
        <div class="p-6">
            <form method="POST" class="space-y-6">
                <input type="hidden" name="action" value="search">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Date -->
                    <div class="input-group">
                        <label for="search_date" class="block text-gray-700 font-medium mb-2">Date <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">
                                <i class="fas fa-calendar"></i>
                            </div>
                            <input type="date" id="search_date" name="search_date" required
                                class="pl-10 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-700 focus:border-gray-700 transition-all">
                        </div>
                    </div>

                    <!-- Time -->
                    <div class="input-group">
                        <label for="search_time" class="block text-gray-700 font-medium mb-2">Time <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">
                                <i class="fas fa-clock"></i>
                            </div>
                            <input type="time" id="search_time" name="search_time" required
                                class="pl-10 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-700 focus:border-gray-700 transition-all">
                        </div>
                    </div>

                    <!-- Department -->
                    <div class="input-group">
                        <label for="search_department" class="block text-gray-700 font-medium mb-2">Department</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">
                                <i class="fas fa-university"></i>
                            </div>
                            <select id="search_department" name="search_department"
                                class="pl-10 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-700 focus:border-gray-700 appearance-none bg-white">
                                <option value="%">All Departments</option>
                                <?php if (!empty($departments)): ?>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?= htmlspecialchars($dept['department_id']) ?>">
                                            <?= htmlspecialchars($dept['department_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="%" disabled>No departments available</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Minimum Capacity -->
                    <div class="input-group">
                        <label for="min_capacity" class="block text-gray-700 font-medium mb-2">Minimum Capacity</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">
                                <i class="fas fa-users"></i>
                            </div>
                            <input type="number" id="min_capacity" name="min_capacity" min="0" value="0"
                                class="pl-10 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-700 focus:border-gray-700 transition-all">
                        </div>
                    </div>

                    <!-- Room Type -->
                    <div class="input-group">
                        <label for="room_type" class="block text-gray-700 font-medium mb-2">Room Type</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">
                                <i class="fas fa-chalkboard"></i>
                            </div>
                            <select id="room_type" name="room_type"
                                class="pl-10 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-700 focus:border-gray-700 appearance-none bg-white">
                                <option value="">Any Type</option>
                                <option value="lecture">Lecture Room</option>
                                <option value="laboratory">Laboratory</option>
                                <option value="avr">AVR/Multimedia Room</option>
                                <option value="seminar_room">Seminar Room</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="mt-8 flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('searchClassroomModal')"
                        class="px-6 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-700 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-6 py-2.5 bg-gray-800 text-white rounded-lg font-medium shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-700 transition-colors flex items-center space-x-2">
                        <i class="fas fa-search"></i>
                        <span>Search Classrooms</span>
                    </button>
                </div>
            </form>

            <?php if (!empty($availableClassrooms)): ?>
                <div class="mt-8 border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Available Classrooms</h3>
                    <div class="overflow-x-auto rounded-lg border border-gray-200 shadow-sm">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Building</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Capacity</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shared</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($availableClassrooms as $room): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($room['room_name']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($room['building']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($room['capacity']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $room['room_type']))) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($room['department_name']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $room['shared'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                                <?= $room['shared'] ? 'Yes' : 'No' ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$modal_content = ob_get_clean();
ob_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Classrooms</title>
    <link rel="stylesheet" href="/css/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/css/custom.css">
</head>

<body class="bg-gray-100 font-sans antialiased">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Classrooms Management</h1>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 animate-fade-in">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Full width container that stretches edge-to-edge -->
        <div class="w-full px-4 sm:px-6 lg:px-8 bg-white py-4">
            <!-- The container for the search bar and button - now full width -->
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4 w-full">
                <!-- Search Button -->
                <div class="mt-6 text-right">
                    <button onclick="openModal('searchClassroomModal')" class="px-6 py-3 bg-gray-800 text-white rounded-lg font-medium shadow-lg hover:bg-gray-900 transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-gray-600 focus:ring-offset-2 flex items-center justify-center gap-2">
                        <i class="fas fa-search"></i>
                        <span>Search Available Classrooms</span>
                    </button>
                </div>

                <!-- Add Classroom Button - fixed width on larger screens -->
                <button
                    onclick="openModal('addClassroomModal')"
                    class="w-full sm:w-auto px-6 py-3 bg-gray-800 text-white rounded-lg font-medium
                   shadow-lg hover:bg-gray-900 transition-colors duration-300
                   focus:outline-none focus:ring-2 focus:ring-gray-600 focus:ring-offset-2
                   flex items-center justify-center gap-2 whitespace-nowrap">
                    <i class="fas fa-plus"></i>
                    <span>Add Classroom</span>
                </button>
            </div>
        </div>

        <!-- Classrooms Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden fade-in">
            <div class="bg-gray-800 text-white px-6 py-4">
                <h5 class="text-xl font-semibold">Classrooms List</h5>
            </div>
            <div class="p-6">
                <?php if (empty($classrooms)): ?>
                    <div class="text-center py-12 text-gray-500">
                        <i class="fas fa-door-open text-4xl mb-4"></i>
                        <p class="text-lg font-medium">No classrooms found.</p>
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
                                            <button onclick="editClassroom(<?= htmlspecialchars(json_encode($classroom)) ?>)" class="text-gold-600 hover:text-gold-800 focus:outline-none">
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
            const modal = document.getElementById(modalId);
            if (!modal) {
                console.error(`Modal with ID ${modalId} not found`);
                return;
            }
            modal.classList.remove('hidden');
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';

            // Disable sidebar interaction
            const sidebar = document.querySelector('#sidebar');
            if (sidebar) {
                sidebar.style.pointerEvents = 'none';
                const computedStyle = window.getComputedStyle(sidebar);
                console.log('Sidebar z-index:', computedStyle.zIndex);
                console.log('Sidebar position:', computedStyle.position);
                console.log('Sidebar transform:', computedStyle.transform);
                // Log parent elements
                let parent = sidebar.parentElement;
                while (parent && parent !== document.body) {
                    const parentStyle = window.getComputedStyle(parent);
                    console.log(`Sidebar parent <${parent.tagName.toLowerCase()}>: z-index=${parentStyle.zIndex}, transform=${parentStyle.transform}, opacity=${parentStyle.opacity}, position=${parentStyle.position}`);
                    parent = parent.parentElement;
                }
            } else {
                console.warn('No element with ID #sidebar found');
            }

            // Log modal details
            const modalOverlay = modal;
            const modalStyle = window.getComputedStyle(modalOverlay);
            console.log(`Modal (${modalId}) z-index:`, modalStyle.zIndex);
            console.log(`Modal position:`, modalStyle.position);
            let modalParent = modalOverlay.parentElement;
            while (modalParent && modalParent !== document.body) {
                const parentStyle = window.getComputedStyle(modalParent);
                console.log(`Modal parent <${modalParent.tagName.toLowerCase()}>: z-index=${parentStyle.zIndex}, transform=${parentStyle.transform}, opacity=${parentStyle.opacity}, position=${parentStyle.position}`);
                modalParent = modalParent.parentElement;
            }
            console.log(`Opened modal: ${modalId}`);
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) {
                console.error(`Modal with ID ${modalId} not found`);
                return;
            }
            modal.classList.remove('active');
            setTimeout(() => {
                modal.classList.add('hidden');
                document.body.style.overflow = 'auto';
                // Restore sidebar interaction
                const sidebar = document.querySelector('#sidebar');
                if (sidebar) {
                    sidebar.style.pointerEvents = 'auto';
                    console.log('Sidebar interaction restored');
                }
                console.log(`Closed modal: ${modalId}`);
            }, 300);
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

        document.addEventListener('DOMContentLoaded', () => {
            // Close modals on click outside
            ['addClassroomModal', 'editClassroomModal', 'searchClassroomModal'].forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.addEventListener('click', (e) => {
                        if (e.target === modal) {
                            closeModal(modalId);
                        }
                    });
                } else {
                    console.warn(`Modal with ID ${modalId} not found on DOM load`);
                }
            });

            // Close modals on Escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    ['addClassroomModal', 'editClassroomModal', 'searchClassroomModal'].forEach(modalId => {
                        const modal = document.getElementById(modalId);
                        if (modal && !modal.classList.contains('hidden')) {
                            closeModal(modalId);
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