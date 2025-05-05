<?php
ob_start();

// Fetch departments for the modal and filter
$collegeId = $controller->getDeanCollegeId($_SESSION['user_id']);
$query = "SELECT department_id, department_name FROM departments WHERE college_id = :college_id ORDER BY department_name";
$stmt = $controller->db->prepare($query);
$stmt->execute([':college_id' => $collegeId]);
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check for success/error messages from DeanController
$success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : null;
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : null;

// Fetch classrooms for the Dean's college only
$query = "
    SELECT c.*, d.department_name, col.college_name
    FROM classrooms c
    JOIN departments d ON c.department_id = d.department_id
    JOIN colleges col ON d.college_id = col.college_id
    WHERE d.college_id = :college_id
    ORDER BY d.department_name, c.building, c.room_name";
$stmt = $controller->db->prepare($query);
$stmt->execute([':college_id' => $collegeId]);
$classrooms = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
if (!is_array($classrooms)) {
    error_log("classroom.php: \$classrooms is not an array. College ID: $collegeId");
    $classrooms = [];
}

// Fetch reservations and ensure $reservations is an array
$query = "
    SELECT rr.reservation_id, rr.room_id, c.room_name, rr.start_time, rr.end_time, rr.description, u.first_name, u.last_name
    FROM room_reservations rr
    JOIN classrooms c ON rr.room_id = c.room_id
    JOIN departments d ON c.department_id = d.department_id
    JOIN users u ON rr.reserved_by = u.user_id
    WHERE d.college_id = :college_id AND rr.approval_status = 'Pending'
    ORDER BY rr.start_time";
$stmt = $controller->db->prepare($query);
$stmt->execute([':college_id' => $collegeId]);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
if (!is_array($reservations)) {
    error_log("classroom.php: \$reservations is not an array. College ID: $collegeId");
    $reservations = [];
}
?>

<?php if ($success): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toast = document.createElement('div');
            toast.className = 'toast bg-green-500 text-white px-4 py-2 rounded-lg';
            toast.textContent = '<?php echo $success; ?>';
            document.getElementById('toast-container').appendChild(toast);
            setTimeout(() => toast.remove(), 5000);
        });
    </script>
<?php endif; ?>
<?php if ($error): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toast = document.createElement('div');
            toast.className = 'toast bg-red-500 text-white px-4 py-2 rounded-lg';
            toast.textContent = '<?php echo $error; ?>';
            document.getElementById('toast-container').appendChild(toast);
            setTimeout(() => toast.remove(), 5000);
        });
    </script>
<?php endif; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<style>
    /* Modal animations */
    #addClassroomModal,
    #editClassroomModal {
        transition: opacity 0.3s ease;
    }

    #addClassroomModal.hidden,
    #editClassroomModal.hidden {
        opacity: 0;
        pointer-events: none;
    }

    .modal-content {
        transition: transform 0.3s ease;
    }

    /* Table enhancements */
    table {
        border-collapse: separate;
        border-spacing: 0;
    }

    th,
    td {
        border-right: 1px solid #E5E7EB;
    }

    th:last-child,
    td:last-child {
        border-right: none;
    }

    /* Tooltip styling */
    .group:hover .group-hover\:block {
        display: block;
    }

    /* Input and select focus and error states */
    input:focus,
    select:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(202, 138, 4, 0.2);
    }

    input.border-red-500,
    select.border-red-500 {
        border-color: #EF4444;
    }

    /* Search bar and filter styling */
    #searchClassrooms {
        max-width: 400px;
    }

    #clearSearch:hover {
        cursor: pointer;
    }

    #departmentFilter {
        min-width: 150px;
    }
</style>

<body class="bg-gray-100 font-sans antialiased">

    <div class="container mx-auto p-6 w-full max-w-7xl">

        <h2 class="text-3xl font-bold text-gray-600 mb-6 slide-in-left">Classroom Management</h2>

        <!-- Add Classroom Button -->
        <div class="mb-6 flex justify-end">
            <button id="openModalBtn" class="bg-yellow-600 text-white px-6 py-3 rounded-lg shadow-md hover:bg-yellow-700 btn flex items-center transition-all duration-300">
                <i class="fas fa-plus mr-2"></i> Add Classroom
            </button>
        </div>

        <!-- Add Classroom Modal -->
        <div id="addClassroomModal" class="fixed inset-0 bg-black bg-opacity-60 flex items-start md:items-center justify-center z-10 hidden overflow-y-auto backdrop-blur-sm py-8">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 transform transition-all duration-300 scale-95 modal-content my-8 -mt-48">
                <!-- Modal Header -->
                <div class="sticky top-0 z-10 bg-white flex justify-between items-center p-6 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white rounded-t-xl">
                    <h3 class="text-xl font-bold text-gray-700">Add New Classroom</h3>
                    <button id="closeModalBtn" class="text-gray-500 hover:text-gray-700 focus:outline-none bg-gray-100 hover:bg-gray-200 rounded-full h-8 w-8 flex items-center justify-center transition-all duration-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Form Content - Reorganized in 2 columns -->
                <form action="/dean/classroom" method="POST" class="p-6 overflow-y-auto max-h-[calc(100vh-12rem)]" id="addClassroomForm">
                    <!-- Row 1 - Two Columns -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Column 1 - Room Name -->
                        <div>
                            <label for="room_name" class="block text-sm font-medium text-gray-700 mb-1">Room Name</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-door-open text-gray-400"></i>
                                </div>
                                <input type="text" id="room_name" name="room_name" required class="pl-10 pr-4 py-3 block w-full rounded-lg border-gray-300 bg-gray-50 shadow-sm focus:border-yellow-600 focus:ring-2 focus:ring-yellow-600 focus:ring-opacity-50 transition-all duration-200 text-base" placeholder="e.g., Lecture Room 101">
                            </div>
                            <p class="text-red-500 text-xs mt-1 hidden error-message">Room name is required.</p>
                        </div>

                        <!-- Column 2 - Building -->
                        <div>
                            <label for="building" class="block text-sm font-medium text-gray-700 mb-1">Building</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-building text-gray-400"></i>
                                </div>
                                <input type="text" id="building" name="building" required class="pl-10 pr-4 py-3 block w-full rounded-lg border-gray-300 bg-gray-50 shadow-sm focus:border-yellow-600 focus:ring-2 focus:ring-yellow-600 focus:ring-opacity-50 transition-all duration-200 text-base" placeholder="e.g., Science Building">
                            </div>
                            <p class="text-red-500 text-xs mt-1 hidden error-message">Building is required.</p>
                        </div>
                    </div>

                    <!-- Row 2 - Two Columns -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Column 1 - Department -->
                        <div>
                            <label for="department_id" class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-university text-gray-400"></i>
                                </div>
                                <select id="department_id" name="department_id" required class="pl-10 pr-10 py-3 block w-full rounded-lg border-gray-300 bg-gray-50 shadow-sm focus:border-yellow-600 focus:ring-2 focus:ring-yellow-600 focus:ring-opacity-50 transition-all duration-200 text-base appearance-none">
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['department_id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <i class="fas fa-chevron-down text-gray-400"></i>
                                </div>
                            </div>
                            <p class="text-red-500 text-xs mt-1 hidden error-message">Department is required.</p>
                        </div>

                        <!-- Column 2 - Capacity -->
                        <div>
                            <label for="capacity" class="block text-sm font-medium text-gray-700 mb-1">Capacity</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-users text-gray-400"></i>
                                </div>
                                <input type="number" id="capacity" name="capacity" required min="1" class="pl-10 pr-4 py-3 block w-full rounded-lg border-gray-300 bg-gray-50 shadow-sm focus:border-yellow-600 focus:ring-2 focus:ring-yellow-600 focus:ring-opacity-50 transition-all duration-200 text-base" placeholder="e.g., 50">
                            </div>
                            <p class="text-red-500 text-xs mt-1 hidden error-message">Capacity must be at least 1.</p>
                        </div>
                    </div>

                    <!-- Row 3 - Two Columns -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Column 1 - Room Type -->
                        <div>
                            <label for="room_type" class="block text-sm font-medium text-gray-700 mb-1">Room Type</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-chalkboard text-gray-400"></i>
                                </div>
                                <select id="room_type" name="room_type" required class="pl-10 pr-10 py-3 block w-full rounded-lg border-gray-300 bg-gray-50 shadow-sm focus:border-yellow-600 focus:ring-2 focus:ring-yellow-600 focus:ring-opacity-50 transition-all duration-200 text-base appearance-none">
                                    <option value="classroom">Classroom</option>
                                    <option value="laboratory">Laboratory</option>
                                    <option value="auditorium">Auditorium</option>
                                    <option value="seminar">Seminar</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <i class="fas fa-chevron-down text-gray-400"></i>
                                </div>
                            </div>
                            <p class="text-red-500 text-xs mt-1 hidden error-message">Room type is required.</p>
                        </div>

                        <!-- Column 2 - Availability -->
                        <div>
                            <label for="availability" class="block text-sm font-medium text-gray-700 mb-1">Availability</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-traffic-light text-gray-400"></i>
                                </div>
                                <select id="availability" name="availability" required class="pl-10 pr-10 py-3 block w-full rounded-lg border-gray-300 bg-gray-50 shadow-sm focus:border-yellow-600 focus:ring-2 focus:ring-yellow-600 focus:ring-opacity-50 transition-all duration-200 text-base appearance-none">
                                    <option value="available">Available</option>
                                    <option value="unavailable">Unavailable</option>
                                    <option value="under_maintenance">Under Maintenance</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <i class="fas fa-chevron-down text-gray-400"></i>
                                </div>
                            </div>
                            <p class="text-red-500 text-xs mt-1 hidden error-message">Availability is required.</p>
                        </div>
                    </div>

                    <!-- Row 4 - Single Column -->
                    <div class="mb-6">
                        <div class="flex items-center bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <input type="checkbox" id="shared" name="shared" class="h-5 w-5 text-yellow-600 focus:ring-yellow-600 border-gray-300 rounded">
                            <label for="shared" class="ml-2 text-sm text-gray-700">Allow sharing with other departments</label>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-3 pt-2 border-t border-gray-100">
                        <button type="button" id="cancelModalBtn" class="bg-gray-200 text-gray-700 px-5 py-3 rounded-lg hover:bg-gray-300 transition-all duration-200 font-medium">
                            Cancel
                        </button>
                        <button type="submit" name="add_classroom" class="bg-yellow-600 text-white px-5 py-3 rounded-lg hover:bg-yellow-700 shadow-md hover:shadow-lg btn transition-all duration-200 font-medium">
                            Add Classroom
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Classroom Modal -->
        <div id="editClassroomModal" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-10 hidden backdrop-blur-sm">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 transform transition-all duration-300 scale-95 modal-content my-8 -mt-48">
                <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white rounded-t-xl">
                    <h3 class="text-xl font-bold text-gray-700">Edit Classroom</h3>
                    <button id="closeEditModalBtn" class="text-gray-500 hover:text-gray-700 focus:outline-none bg-gray-100 hover:bg-gray-200 rounded-full h-8 w-8 flex items-center justify-center transition-all duration-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form action="/dean/classroom" method="POST" class="p-6 overflow-y-auto max-h-[calc(100vh-12rem)]" id="editClassroomForm">
                    <input type="hidden" id="edit_room_id" name="room_id">

                    <!-- First row - Room Name and Building side by side -->
                    <div class="flex flex-col md:flex-row gap-4 mb-5">
                        <div class="flex-1">
                            <label for="edit_room_name" class="block text-sm font-medium text-gray-700 mb-1">Room Name</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-door-open text-gray-400"></i>
                                </div>
                                <input type="text" id="edit_room_name" name="room_name" required class="pl-10 pr-4 py-3 block w-full rounded-lg border-gray-300 bg-gray-50 shadow-sm focus:border-yellow-600 focus:ring-2 focus:ring-yellow-600 focus:ring-opacity-50 transition-all duration-200 text-base" placeholder="e.g., Lecture Room 101">
                            </div>
                            <p class="text-red-500 text-xs mt-1 hidden error-message">Room name is required.</p>
                        </div>
                        <div class="flex-1">
                            <label for="edit_building" class="block text-sm font-medium text-gray-700 mb-1">Building</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-building text-gray-400"></i>
                                </div>
                                <input type="text" id="edit_building" name="building" required class="pl-10 pr-4 py-3 block w-full rounded-lg border-gray-300 bg-gray-50 shadow-sm focus:border-yellow-600 focus:ring-2 focus:ring-yellow-600 focus:ring-opacity-50 transition-all duration-200 text-base" placeholder="e.g., Science Building">
                            </div>
                            <p class="text-red-500 text-xs mt-1 hidden error-message">Building is required.</p>
                        </div>
                    </div>

                    <!-- Second row - Department (full width) -->
                    <div class="mb-5">
                        <label for="edit_department_id" class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-university text-gray-400"></i>
                            </div>
                            <select id="edit_department_id" name="department_id" required class="pl-10 pr-10 py-3 block w-full rounded-lg border-gray-300 bg-gray-50 shadow-sm focus:border-yellow-600 focus:ring-2 focus:ring-yellow-600 focus:ring-opacity-50 transition-all duration-200 text-base appearance-none">
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['department_id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <i class="fas fa-chevron-down text-gray-400"></i>
                            </div>
                        </div>
                        <p class="text-red-500 text-xs mt-1 hidden error-message">Department is required.</p>
                    </div>

                    <!-- Third row - Capacity and Room Type side by side -->
                    <div class="flex flex-col md:flex-row gap-4 mb-5">
                        <div class="flex-1">
                            <label for="edit_capacity" class="block text-sm font-medium text-gray-700 mb-1">Capacity</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-users text-gray-400"></i>
                                </div>
                                <input type="number" id="edit_capacity" name="capacity" required min="1" class="pl-10 pr-4 py-3 block w-full rounded-lg border-gray-300 bg-gray-50 shadow-sm focus:border-yellow-600 focus:ring-2 focus:ring-yellow-600 focus:ring-opacity-50 transition-all duration-200 text-base" placeholder="e.g., 50">
                            </div>
                            <p class="text-red-500 text-xs mt-1 hidden error-message">Capacity must be at least 1.</p>
                        </div>
                        <div class="flex-1">
                            <label for="edit_room_type" class="block text-sm font-medium text-gray-700 mb-1">Room Type</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-chalkboard text-gray-400"></i>
                                </div>
                                <select id="edit_room_type" name="room_type" required class="pl-10 pr-10 py-3 block w-full rounded-lg border-gray-300 bg-gray-50 shadow-sm focus:border-yellow-600 focus:ring-2 focus:ring-yellow-600 focus:ring-opacity-50 transition-all duration-200 text-base appearance-none">
                                    <option value="classroom">Classroom</option>
                                    <option value="laboratory">Laboratory</option>
                                    <option value="auditorium">Auditorium</option>
                                    <option value="seminar">Seminar</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <i class="fas fa-chevron-down text-gray-400"></i>
                                </div>
                            </div>
                            <p class="text-red-500 text-xs mt-1 hidden error-message">Room type is required.</p>
                        </div>
                    </div>

                    <!-- Fourth row - Availability and Shared in a grid -->
                    <div class="flex flex-col md:flex-row gap-4 mb-5">
                        <div class="flex-1">
                            <label for="edit_availability" class="block text-sm font-medium text-gray-700 mb-1">Availability</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-traffic-light text-gray-400"></i>
                                </div>
                                <select id="edit_availability" name="availability" required class="pl-10 pr-10 py-3 block w-full rounded-lg border-gray-300 bg-gray-50 shadow-sm focus:border-yellow-600 focus:ring-2 focus:ring-yellow-600 focus:ring-opacity-50 transition-all duration-200 text-base appearance-none">
                                    <option value="available">Available</option>
                                    <option value="unavailable">Unavailable</option>
                                    <option value="under_maintenance">Under Maintenance</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <i class="fas fa-chevron-down text-gray-400"></i>
                                </div>
                            </div>
                            <p class="text-red-500 text-xs mt-1 hidden error-message">Availability is required.</p>
                        </div>
                        <div class="flex-1 flex items-center">
                            <div class="mt-4">
                                <label for="edit_shared" class="text-sm font-medium text-gray-700 mb-1 block">Shared</label>
                                <div class="flex items-center mt-1">
                                    <input type="checkbox" id="edit_shared" name="shared" class="h-5 w-5 text-yellow-600 focus:ring-yellow-600 border-gray-300 rounded">
                                    <span class="ml-2 text-sm text-gray-600">Allow sharing with other departments</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="flex justify-end space-x-3 pt-4 mt-2">
                        <button type="button" id="cancelEditModalBtn" class="bg-gray-200 text-gray-700 px-5 py-3 rounded-lg hover:bg-gray-300 transition-all duration-200 font-medium">Cancel</button>
                        <button type="submit" name="update_classroom" class="bg-yellow-600 text-white px-5 py-3 rounded-lg hover:bg-yellow-700 shadow-md hover:shadow-lg btn transition-all duration-200 font-medium">Update Classroom</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Classrooms List -->
        <div class="bg-white p-6 rounded-lg shadow-md card overflow-hidden slide-in-right">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-700">Classrooms</h3>
                <span class="text-sm font-medium text-gray-500 bg-gray-100 px-3 py-1 rounded-full" id="classroomCount"><?php echo count($classrooms); ?> Classrooms</span>
            </div>
            <!-- Search and Filter Section -->
            <div class="flex flex-col sm:flex-row justify-end items-center space-y-3 sm:space-y-0 sm:space-x-4 mb-6 slide-in-right">
                <div class="relative w-full sm:w-48">
                    <select id="departmentFilter" class="appearance-none block w-full rounded-lg border-gray-300 bg-gray-50 shadow-sm focus:border-yellow-600 focus:ring-2 focus:ring-yellow-600 focus:ring-opacity-50 transition-all duration-200 pl-10 pr-10 py-2.5 text-sm">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['department_id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-filter text-gray-400"></i>
                    </div>
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <i class="fas fa-chevron-down text-gray-400"></i>
                    </div>
                </div>
                <div class="relative flex-1 max-w-md">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                    <input type="text" id="searchClassrooms" class="block w-full pl-10 pr-10 py-2.5 rounded-lg border-gray-300 bg-gray-50 shadow-sm focus:border-yellow-600 focus:ring-2 focus:ring-yellow-600 focus:ring-opacity-50 transition-all duration-200 text-sm" placeholder="Search by room, building, department, or capacity...">
                    <button id="clearSearch" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 hidden">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div id="noResults" class="text-gray-600 text-lg hidden py-8 text-center">
                <i class="fas fa-search text-gray-400 text-2xl mb-2"></i>
                <p>No classrooms found.</p>
            </div>
            <?php if (empty($classrooms)): ?>
                <div class="text-gray-600 text-lg py-10 text-center">
                    <i class="fas fa-school text-gray-400 text-3xl mb-3"></i>
                    <p>No classrooms found in your college.</p>
                </div>
            <?php else: ?>
                <div class="rounded-lg border border-gray-200 overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200" id="classroomsTable">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Room Name</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Building</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Department</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Capacity</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Room Type</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Shared</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Availability</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($classrooms as $index => $classroom): ?>
                                <tr class="transition-all duration-200 hover:bg-gray-50 <?php echo $index % 2 ? 'bg-gray-50' : ''; ?>"
                                    data-room-name="<?php echo htmlspecialchars(strtolower($classroom['room_name'])); ?>"
                                    data-building="<?php echo htmlspecialchars(strtolower($classroom['building'])); ?>"
                                    data-department="<?php echo htmlspecialchars(strtolower($classroom['department_name'])); ?>"
                                    data-capacity="<?php echo htmlspecialchars($classroom['capacity']); ?>"
                                    data-department-id="<?php echo htmlspecialchars($classroom['department_id']); ?>">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-700"><?php echo htmlspecialchars($classroom['room_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($classroom['building']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <?php echo htmlspecialchars($classroom['department_name']); ?>
                                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            <i class="fas fa-graduation-cap mr-1 text-yellow-600"></i>Your College
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <span class="inline-flex items-center">
                                            <i class="fas fa-users text-gray-400 mr-1.5"></i>
                                            <?php echo htmlspecialchars($classroom['capacity']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars(ucfirst($classroom['room_type'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <?php echo $classroom['shared'] ? '<i class="fas fa-check text-green-500"></i> Yes' : '<i class="fas fa-times text-red-500"></i> No'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <form action="/dean/classroom" method="POST" class="inline">
                                            <input type="hidden" name="room_id" value="<?php echo $classroom['room_id']; ?>">
                                            <input type="hidden" name="current_availability" value="<?php echo $classroom['availability']; ?>">
                                            <button type="submit" name="toggle_availability" class="px-3 py-1 rounded text-white text-xs font-medium <?php echo $classroom['availability'] === 'available' ? 'bg-green-500 hover:bg-green-600' : ($classroom['availability'] === 'unavailable' ? 'bg-red-500 hover:bg-red-600' : 'bg-orange-500 hover:bg-orange-600'); ?> transition-all duration-200 relative group" title="Change to <?php echo $classroom['availability'] === 'available' ? 'Unavailable' : ($classroom['availability'] === 'unavailable' ? 'Under Maintenance' : 'Available'); ?>">
                                                <?php echo htmlspecialchars(ucfirst($classroom['availability'])); ?>
                                                <span class="absolute hidden group-hover:block bg-gray-800 text-white text-xs rounded py-1 px-2 -top-8 left-1/2 transform -translate-x-1/2">Change to <?php echo $classroom['availability'] === 'available' ? 'Unavailable' : ($classroom['availability'] === 'unavailable' ? 'Under Maintenance' : 'Available'); ?></span>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <button class="editClassroomBtn bg-yellow-600 text-white px-3 py-1 rounded hover:bg-yellow-700 btn transition-all duration-200 relative group"
                                            data-room-id="<?php echo $classroom['room_id']; ?>"
                                            data-room-name="<?php echo htmlspecialchars($classroom['room_name']); ?>"
                                            data-building="<?php echo htmlspecialchars($classroom['building']); ?>"
                                            data-department-id="<?php echo $classroom['department_id']; ?>"
                                            data-capacity="<?php echo htmlspecialchars($classroom['capacity']); ?>"
                                            data-room-type="<?php echo htmlspecialchars($classroom['room_type']); ?>"
                                            data-shared="<?php echo $classroom['shared']; ?>"
                                            data-availability="<?php echo htmlspecialchars($classroom['availability']); ?>"
                                            title="Edit Classroom">
                                            Edit
                                            <span class="absolute hidden group-hover:block bg-gray-800 text-white text-xs rounded py-1 px-2 -top-8 left-1/2 transform -translate-x-1/2">Edit Classroom</span>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Room Reservations -->
        <div class="bg-white p-6 rounded-lg shadow-md card mt-8 overflow-x-auto slide-in-right">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-700">Pending Room Reservations</h3>
                <span class="text-sm font-medium text-gray-500 bg-gray-100 px-3 py-1 rounded-full"><?php echo count($reservations); ?> Pending</span>
            </div>
            <?php if (empty($reservations)): ?>
                <div class="text-gray-600 text-lg py-10 text-center">
                    <i class="fas fa-calendar-times text-gray-400 text-3xl mb-3"></i>
                    <p>No pending room reservations.</p>
                </div>
            <?php else: ?>
                <div class="rounded-lg border border-gray-200 overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Room</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Requested By</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Purpose</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date & Time</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($reservations as $index => $reservation): ?>
                                <tr class="transition-all duration-200 hover:bg-gray-50 <?php echo $index % 2 ? 'bg-gray-50' : ''; ?>">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($reservation['room_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($reservation['first_name'] . ' ' . $reservation['last_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($reservation['description']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <?php echo htmlspecialchars(date('M d, Y', strtotime($reservation['start_time'])) . ' ' . date('h:i A', strtotime($reservation['start_time'])) . ' - ' . date('h:i A', strtotime($reservation['end_time']))); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <form action="/dean/classroom" method="POST" class="inline">
                                            <input type="hidden" name="reservation_id" value="<?php echo $reservation['reservation_id']; ?>">
                                            <input type="hidden" name="status" value="Approved">
                                            <button type="submit" class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600 btn relative group" title="Approve Reservation">
                                                Approve
                                                <span class="absolute hidden group-hover:block bg-gray-800 text-white text-xs rounded py-1 px-2 -top-8 left-1/2 transform -translate-x-1/2">Approve Reservation</span>
                                            </button>
                                        </form>
                                        <form action="/dean/classroom" method="POST" class="inline ml-2">
                                            <input type="hidden" name="reservation_id" value="<?php echo $reservation['reservation_id']; ?>">
                                            <input type="hidden" name="status" value="Rejected">
                                            <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 btn relative group" title="Reject Reservation">
                                                Reject
                                                <span class="absolute hidden group-hover:block bg-gray-800 text-white text-xs rounded py-1 px-2 -top-8 left-1/2 transform -translate-x-1/2">Reject Reservation</span>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>                          
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                // Add Classroom Modal
                const addModal = document.getElementById('addClassroomModal');
                const openModalBtn = document.getElementById('openModalBtn');
                const closeModalBtn = document.getElementById('closeModalBtn');
                const cancelModalBtn = document.getElementById('cancelModalBtn');
                const addModalContent = addModal.querySelector('.modal-content');

                // Edit Classroom Modal
                const editModal = document.getElementById('editClassroomModal');
                const closeEditModalBtn = document.getElementById('closeEditModalBtn');
                const cancelEditModalBtn = document.getElementById('cancelEditModalBtn');
                const editModalContent = editModal.querySelector('.modal-content');
                const editClassroomButtons = document.querySelectorAll('.editClassroomBtn');

                // Open Add Modal
                openModalBtn.addEventListener('click', () => {
                    addModal.classList.remove('hidden');
                    addModalContent.classList.remove('scale-95');
                    addModalContent.classList.add('scale-100');
                    document.body.style.overflow = 'hidden';
                });

                // Close Add Modal
                const closeAddModal = () => {
                    addModalContent.classList.remove('scale-100');
                    addModalContent.classList.add('scale-95');
                    setTimeout(() => {
                        addModal.classList.add('hidden');
                        document.body.style.overflow = 'auto';
                        document.getElementById('addClassroomForm').reset();
                        document.querySelectorAll('#addClassroomForm .error-message').forEach(msg => msg.classList.add('hidden'));
                        document.querySelectorAll('#addClassroomForm input, #addClassroomForm select').forEach(input => input.classList.remove('border-red-500'));
                    }, 200);
                };

                closeModalBtn.addEventListener('click', closeAddModal);
                cancelModalBtn.addEventListener('click', closeAddModal);

                // Open Edit Modal
                editClassroomButtons.forEach(button => {
                    button.addEventListener('click', () => {
                        const roomId = button.dataset.roomId;
                        const roomName = button.dataset.roomName;
                        const building = button.dataset.building;
                        const departmentId = button.dataset.departmentId;
                        const capacity = button.dataset.capacity;
                        const roomType = button.dataset.roomType;
                        const shared = button.dataset.shared === '1';
                        const availability = button.dataset.availability;

                        document.getElementById('edit_room_id').value = roomId;
                        document.getElementById('edit_room_name').value = roomName;
                        document.getElementById('edit_building').value = building;
                        document.getElementById('edit_department_id').value = departmentId;
                        document.getElementById('edit_capacity').value = capacity;
                        document.getElementById('edit_room_type').value = roomType;
                        document.getElementById('edit_shared').checked = shared;
                        document.getElementById('edit_availability').value = availability;

                        editModal.classList.remove('hidden');
                        editModalContent.classList.remove('scale-95');
                        editModalContent.classList.add('scale-100');
                        document.body.style.overflow = 'hidden';
                    });
                });

                // Close Edit Modal
                const closeEditModal = () => {
                    editModalContent.classList.remove('scale-100');
                    editModalContent.classList.add('scale-95');
                    setTimeout(() => {
                        editModal.classList.add('hidden');
                        document.body.style.overflow = 'auto';
                        document.getElementById('editClassroomForm').reset();
                        document.querySelectorAll('#editClassroomForm .error-message').forEach(msg => msg.classList.add('hidden'));
                        document.querySelectorAll('#editClassroomForm input, #editClassroomForm select').forEach(input => input.classList.remove('border-red-500'));
                    }, 200);
                };

                closeEditModalBtn.addEventListener('click', closeEditModal);
                cancelEditModalBtn.addEventListener('click', closeEditModal);

                // Close modals on backdrop click
                addModal.addEventListener('click', (e) => {
                    if (e.target === addModal) closeAddModal();
                });
                editModal.addEventListener('click', (e) => {
                    if (e.target === editModal) closeEditModal();
                });

                // Close modals on ESC key
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        if (!addModal.classList.contains('hidden')) closeAddModal();
                        if (!editModal.classList.contains('hidden')) closeEditModal();
                    }
                });

                // Form validation for both modals
                ['addClassroomForm', 'editClassroomForm'].forEach(formId => {
                    const form = document.getElementById(formId);
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

                        const capacityInput = form.querySelector('[name="capacity"]');
                        const capacityError = capacityInput.nextElementSibling;
                        if (capacityInput.value < 1) {
                            capacityInput.classList.add('border-red-500');
                            capacityError.classList.remove('hidden');
                            isValid = false;
                        } else {
                            capacityInput.classList.remove('border-red-500');
                            capacityError.classList.add('hidden');
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

                    form.querySelector('[name="capacity"]').addEventListener('input', function() {
                        const errorMessage = this.nextElementSibling;
                        if (this.value >= 1) {
                            this.classList.remove('border-red-500');
                            errorMessage.classList.add('hidden');
                        }
                    });
                });

                // Search and filter functionality
                const searchInput = document.getElementById('searchClassrooms');
                const clearSearchBtn = document.getElementById('clearSearch');
                const departmentFilter = document.getElementById('departmentFilter');
                const classroomsTable = document.getElementById('classroomsTable');
                const noResults = document.getElementById('noResults');
                const classroomCount = document.getElementById('classroomCount');
                const rows = classroomsTable ? classroomsTable.querySelectorAll('tbody tr') : [];

                const updateTable = () => {
                    const query = searchInput.value.trim().toLowerCase();
                    const selectedDepartment = departmentFilter.value;
                    let visibleRows = 0;

                    rows.forEach(row => {
                        const roomName = row.dataset.roomName;
                        const building = row.dataset.building;
                        const department = row.dataset.department;
                        const capacity = row.dataset.capacity;
                        const departmentId = row.dataset.departmentId;

                        const matchesSearch = query === '' ||
                            roomName.includes(query) ||
                            building.includes(query) ||
                            department.includes(query) ||
                            capacity.includes(query);

                        const matchesDepartment = selectedDepartment === '' || departmentId === selectedDepartment;

                        const matches = matchesSearch && matchesDepartment;

                        row.style.display = matches ? '' : 'none';
                        if (matches) visibleRows++;
                    });

                    clearSearchBtn.classList.toggle('hidden', query === '');
                    noResults.classList.toggle('hidden', visibleRows > 0);
                    classroomCount.textContent = `${visibleRows} Classrooms`;
                };

                searchInput.addEventListener('input', updateTable);
                departmentFilter.addEventListener('change', updateTable);

                clearSearchBtn.addEventListener('click', () => {
                    searchInput.value = '';
                    departmentFilter.value = '';
                    rows.forEach(row => row.style.display = '');
                    clearSearchBtn.classList.add('hidden');
                    noResults.classList.add('hidden');
                    classroomCount.textContent = `${rows.length} Classrooms`;
                });

                // Trigger initial table update
                updateTable();
            });
        </script>

</body>

</html>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>