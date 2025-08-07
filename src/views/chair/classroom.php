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
    $room_type = $_POST['room_type'] ?? 'lecture';
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

// Define modal content
ob_start();
?>
<!-- Add Classroom Modal -->
<div id="addClassroomModal" class="modal-overlay hidden fixed inset-0 z-50">
    <div class="modal-content">
        <div class="bg-gray-800 text-white px-6 py-4 rounded-t-lg flex justify-between items-center">
            <h5 class="text-xl font-semibold">Add Classroom</h5>
            <button onclick="closeModal('addClassroomModal')" class="text-white hover:text-gray-300 focus:outline-none text-xl" aria-label="Close modal">
                &times;
            </button>
        </div>
        <div class="p-6">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <?php if ($departmentInfo): ?>
                    <div class="mb-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <p class="text-sm text-gray-600">
                            This classroom will be assigned to:
                            <span class="font-medium text-gray-800">
                                <?= htmlspecialchars($departmentInfo['department_name']) ?>
                                (<?= htmlspecialchars($departmentInfo['college_name']) ?>)
                            </span>
                        </p>
                    </div>
                <?php endif; ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="input-icon">
                        <label for="room_name" class="block text-gray-700 font-medium mb-2">Room Name</label>
                        <i class="fas fa-door-open"></i>
                        <input type="text" id="room_name" name="room_name" required
                            class="px-4 py-2 border border-gray-300 rounded-md input-focus focus:outline-none transition-all">
                    </div>
                    <div class="input-icon">
                        <label for="building" class="block text-gray-700 font-medium mb-2">Building</label>
                        <i class="fas fa-building"></i>
                        <input type="text" id="building" name="building" required
                            class="px-4 py-2 border border-gray-300 rounded-md input-focus focus:outline-none transition-all">
                    </div>
                    <div class="input-icon">
                        <label for="capacity" class="block text-gray-700 font-medium mb-2">Capacity</label>
                        <i class="fas fa-users"></i>
                        <input type="number" id="capacity" name="capacity" min="1" required
                            class="px-4 py-2 border border-gray-300 rounded-md input-focus focus:outline-none transition-all">
                    </div>
                    <div class="input-icon">
                        <label for="room_type" class="block text-gray-700 font-medium mb-2">Room Type</label>
                        <i class="fas fa-chalkboard"></i>
                        <select id="room_type" name="room_type" required
                            class="px-4 py-2 border border-gray-300 rounded-md input-focus focus:outline-none transition-all">
                            <option value="">Select Room Type</option>
                            <option value="lecture">Lecture</option>
                            <option value="laboratory">Laboratory</option>
                            <option value="avr">AVR/Multimedia Room</option>
                            <option value="seminar_room">Seminar Room</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Shared</label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="shared" value="1" class="form-checkbox h-5 w-5 text-gold-500">
                            <span class="ml-2 text-gray-700">Share with other departments</span>
                        </label>
                    </div>
                </div>
                <div class="mt-8 flex justify-end space-x-4">
                    <button type="button" onclick="closeModal('addClassroomModal')"
                        class="px-6 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-6 py-2 btn-gold rounded-md font-medium shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-gold-500 focus:ring-offset-2 transition-all">
                        Add Classroom
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Classroom Modal -->
<div id="editClassroomModal" class="modal-overlay hidden fixed inset-0 z-50">
    <div class="modal-content">
        <div class="bg-gray-800 text-white px-6 py-4 rounded-t-lg flex justify-between items-center">
            <h5 class="text-xl font-semibold">Edit Classroom</h5>
            <button onclick="closeModal('editClassroomModal')" class="text-white hover:text-gray-300 focus:outline-none text-xl" aria-label="Close modal">
                &times;
            </button>
        </div>
        <div class="p-6">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_room_id" name="room_id">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="input-icon">
                        <label for="edit_room_name" class="block text-gray-700 font-medium mb-2">Room Name</label>
                        <i class="fas fa-door-open"></i>
                        <input type="text" id="edit_room_name" name="room_name" required
                            class="px-4 py-2 border border-gray-300 rounded-md input-focus focus:outline-none transition-all">
                    </div>
                    <div class="input-icon">
                        <label for="edit_building" class="block text-gray-700 font-medium mb-2">Building</label>
                        <i class="fas fa-building"></i>
                        <input type="text" id="edit_building" name="building" required
                            class="px-4 py-2 border border-gray-300 rounded-md input-focus focus:outline-none transition-all">
                    </div>
                    <div class="input-icon">
                        <label for="edit_capacity" class="block text-gray-700 font-medium mb-2">Capacity</label>
                        <i class="fas fa-users"></i>
                        <input type="number" id="edit_capacity" name="capacity" min="1" required
                            class="px-4 py-2 border border-gray-300 rounded-md input-focus focus:outline-none transition-all">
                    </div>
                    <div class="input-icon">
                        <label for="edit_room_type" class="block text-gray-700 font-medium mb-2">Room Type</label>
                        <i class="fas fa-chalkboard"></i>
                        <select id="edit_room_type" name="room_type" required
                            class="px-4 py-2 border border-gray-300 rounded-md input-focus focus:outline-none transition-all">
                            <option value="lecture">Lecture</option>
                            <option value="laboratory">Laboratory</option>
                            <option value="avr">AVR/Multimedia Room</option>
                            <option value="seminar_room">Seminar Room</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Shared</label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" id="edit_shared" name="shared" value="1" class="form-checkbox h-5 w-5 text-gold-500">
                            <span class="ml-2 text-gray-700">Share with other departments</span>
                        </label>
                    </div>
                    <div class="input-icon">
                        <label for="edit_availability" class="block text-gray-700 font-medium mb-2">Availability</label>
                        <i class="fas fa-calendar-check"></i>
                        <select id="edit_availability" name="availability" required
                            class="px-4 py-2 border border-gray-300 rounded-md input-focus focus:outline-none transition-all">
                            <option value="available">Available</option>
                            <option value="unavailable">Unavailable</option>
                            <option value="under_maintenance">Under Maintenance</option>
                        </select>
                    </div>
                </div>
                <div class="mt-8 flex justify-end space-x-4">
                    <button type="button" onclick="closeModal('editClassroomModal')"
                        class="px-6 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-6 py-2 btn-gold rounded-md font-medium shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-gold-500 focus:ring-offset-2 transition-all">
                        Update Classroom
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Search Classroom Modal -->
<div id="searchClassroomModal" class="modal-overlay hidden fixed inset-0 z-50">
    <div class="modal-content">
        <div class="bg-gray-800 text-white px-6 py-4 rounded-t-lg flex justify-between items-center">
            <h5 class="text-xl font-semibold">Search Available Classrooms</h5>
            <button onclick="closeModal('searchClassroomModal')" class="text-white hover:text-gray-300 focus:outline-none text-xl" aria-label="Close modal">
                &times;
            </button>
        </div>
        <div class="p-6">
            <form method="POST">
                <input type="hidden" name="action" value="search">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="input-icon">
                        <label for="search_date" class="block text-gray-700 font-medium mb-2">Date</label>
                        <i class="fas fa-calendar"></i>
                        <input type="date" id="search_date" name="search_date" required
                            class="px-4 py-2 border border-gray-300 rounded-md input-focus focus:outline-none transition-all">
                    </div>
                    <div class="input-icon">
                        <label for="search_time" class="block text-gray-700 font-medium mb-2">Time</label>
                        <i class="fas fa-clock"></i>
                        <input type="time" id="search_time" name="search_time" required
                            class="px-4 py-2 border border-gray-300 rounded-md input-focus focus:outline-none transition-all">
                    </div>
                    <div class="input-icon">
                        <label for="search_department" class="block text-gray-700 font-medium mb-2">Department</label>
                        <i class="fas fa-university"></i>
                        <select id="search_department" name="search_department"
                            class="px-4 py-2 border border-gray-300 rounded-md input-focus focus:outline-none transition-all">
                            <option value="%">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= htmlspecialchars($dept['department_id']) ?>">
                                    <?= htmlspecialchars($dept['department_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="input-icon">
                        <label for="min_capacity" class="block text-gray-700 font-medium mb-2">Minimum Capacity</label>
                        <i class="fas fa-users"></i>
                        <input type="number" id="min_capacity" name="min_capacity" min="0" value="0"
                            class="px-4 py-2 border border-gray-300 rounded-md input-focus focus:outline-none transition-all">
                    </div>
                    <div class="input-icon">
                        <label for="room_type" class="block text-gray-700 font-medium mb-2">Room Type</label>
                        <i class="fas fa-chalkboard"></i>
                        <select id="room_type" name="room_type"
                            class="px-4 py-2 border border-gray-300 rounded-md input-focus focus:outline-none transition-all">
                            <option value="">Any</option>
                            <option value="lecture">Lecture</option>
                            <option value="laboratory">Laboratory</option>
                            <option value="avr">AVR/Multimedia Room</option>
                            <option value="seminar_room">Seminar Room</option>
                        </select>
                    </div>
                </div>
                <div class="mt-8 flex justify-end space-x-4">
                    <button type="button" onclick="closeModal('searchClassroomModal')"
                        class="px-6 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-6 py-2 btn-gold rounded-md font-medium shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-gold-500 focus:ring-offset-2 transition-all">
                        Search
                    </button>
                </div>
            </form>
            <?php if (!empty($availableClassrooms)): ?>
                <div class="mt-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Available Classrooms</h3>
                    <div class="overflow-x-auto">
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
                                    <tr class="hover:bg-gray-50 transition-colors">
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
                </div>
            <?php endif; ?>
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
        <style>
            :root {
                --gold: #D4AF37;
                --gray-dark: #1F2937;
                --gray-light: #F3F4F6;
            }

            /* Modal styles */
            .modal-overlay {
                background: rgba(0, 0, 0, 0.6) !important;
                backdrop-filter: blur(8px) !important;
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                z-index: 50 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.3s ease, visibility 0.3s ease;
            }

            .modal-overlay.active {
                opacity: 1;
                visibility: visible;
            }

            .modal-content {
                background: white;
                border-radius: 1rem;
                width: 60%;
                max-width: 75vw;
                max-height: 90vh;
                overflow-y: auto;
                transform: scale(0.9);
                transition: transform 0.3s ease;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
                margin: 1rem;
            }

            .modal-overlay.active .modal-content {
                transform: scale(1);
            }

            /* Input icon styling */
            .input-icon {
                position: relative;
            }

            .input-icon i {
                position: absolute;
                left: 1rem;
                top: 50%;
                transform: translateY(-50%);
                color: #6B7280;
            }

            .input-icon input,
            .input-icon select {
                padding-left: 2.5rem;
                width: 100%;
            }

            .input-focus:focus {
                border-color: var(--gold);
                box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
                outline: none;
            }

            .btn-gold {
                background-color: var(--gold);
                color: white;
            }

            .btn-gold:hover {
                background-color: #b8972e;
            }

            .fade-in {
                animation: fadeIn 0.3s ease-in;
            }

            @keyframes fadeIn {
                from {
                    opacity: 0;
                }

                to {
                    opacity: 1;
                }
            }

            /* Responsive adjustments */
            @media (max-width: 640px) {
                .modal-content {
                    max-width: 95vw;
                    margin: 0.5rem;
                }
            }
        </style>
    </head>

    <body class="bg-gray-100 font-sans antialiased">
        <div class="container mx-auto px-4 py-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-8">Manage Classrooms</h1>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 animate-fade-in">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- The container for the search bar and button -->
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4 max-w-4xl mx-auto">

                <!-- Search Form -->
                <form method="GET" class="w-full relative py-4">
                    <input
                        type="text"
                        name="search"
                        placeholder="Search classrooms..."
                        value="<?= htmlspecialchars($searchTerm) ?>"
                        class="w-full pl-12 pr-4 py-3 rounded-lg border-2 border-black bg-white shadow-sm
                       focus:outline-none focus:ring-2 focus:ring-gray-600 focus:ring-opacity-50
                       transition-all duration-300 text-gray-700 placeholder-gray-400">
                    <!-- Search Icon positioned inside the input field -->
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-black"></i>
                </form>

                <!-- Add Classroom Button -->
                <button
                    onclick="openModal('addClassroomModal')"
                    class="w-full sm:w-auto px-8 py-3 bg-gray-800 text-white rounded-lg font-medium
                   shadow-lg hover:bg-gray-900 transition-colors duration-300
                   focus:outline-none focus:ring-2 focus:ring-gray-600 focus:ring-offset-2
                   flex items-center justify-center sm:justify-start gap-2">
                    <i class="fas fa-plus"></i>
                    <span>Add Classroom</span>
                </button>

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
    <?php
    $content = ob_get_clean();
    require_once __DIR__ . '/layout.php';
    ?>

    </html>