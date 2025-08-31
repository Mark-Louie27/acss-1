<?php
require_once __DIR__ . '/../../controllers/ChairController.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();

// Initialize variables with defaults
$searchTerm = $_GET['search'] ?? '';
$error = $error ?? null;
$classrooms = $classrooms ?? []; // Ensure classrooms is defined
$departmentInfo = $departmentInfo ?? null;
$departments = $departments ?? []; // Ensure departments is defined

// Ensure $this->db is available
$controller = new ChairController();
$db = $controller->db;

// Fetch all classrooms (no filtering, handled client-side)
try {
    $stmt = $db->prepare("
        SELECT c.*, d.department_name, cl.college_name
        FROM classrooms c
        JOIN departments d ON c.department_id = d.department_id
        JOIN colleges cl ON d.college_id = cl.college_id
        ORDER BY c.room_name
    ");
    $stmt->execute();
    $classrooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    error_log("Failed to fetch classrooms: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $room_name = $_POST['room_name'] ?? '';
    $building = $_POST['building'] ?? '';
    $capacity = (int)($_POST['capacity'] ?? 0);
    $room_type = $_POST['room_type'] ?? 'lecture';
    $shared = isset($_POST['shared']) ? 1 : 0;
    $availability = $_POST['availability'] ?? 'available';
    $department_id = $departmentInfo['department_id'] ?? null;

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
        }

        header("Location: classroom");
        exit;
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Define modal content (unchanged except removing search modal)
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
            <!-- Search Bar and Buttons -->
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4 w-full">
                <!-- Search Bar -->
                <div class="w-full sm:w-1/2">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">
                            <i class="fas fa-search"></i>
                        </div>
                        <input type="text" id="searchInput" name="search" value="<?= htmlspecialchars($searchTerm) ?>"
                            placeholder="Search by room name, building, or department..."
                            class="pl-10 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-700 focus:border-gray-700 transition-all">
                    </div>
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
                        <table id="classroomsTable" class="w-full table-auto">
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
                                    <tr class="hover:bg-gray-50 transition-colors" data-search="<?= htmlspecialchars(strtolower($classroom['room_name'] . ' ' . $classroom['building'] . ' ' . ($classroom['department_name'] ?? ''))) ?>">
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
                let parent = sidebar.parentElement;
                while (parent && parent !== document.body) {
                    const parentStyle = window.getComputedStyle(parent);
                    console.log(`Sidebar parent <${parent.tagName.toLowerCase()}>: z-index=${parentStyle.zIndex}, transform=${parentStyle.transform}, opacity=${parentStyle.opacity}, position=${parentStyle.position}`);
                    parent = parent.parentElement;
                }
            } else {
                console.warn('No element with ID #sidebar found');
            }

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

        // Search functionality
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('searchInput');
            const tableRows = document.querySelectorAll('#classroomsTable tbody tr');

            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                tableRows.forEach(row => {
                    const searchData = row.getAttribute('data-search').toLowerCase();
                    if (searchData.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });

            // Close modals on click outside
            ['addClassroomModal', 'editClassroomModal'].forEach(modalId => {
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
                    ['addClassroomModal', 'editClassroomModal'].forEach(modalId => {
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