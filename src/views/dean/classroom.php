<?php
ob_start();

// Fetch departments
$collegeId = $controller->getDeanCollegeId($_SESSION['user_id']);
$query = "SELECT department_id, department_name FROM departments WHERE college_id = :college_id ORDER BY department_name";
$stmt = $controller->db->prepare($query); // Changed to $controller->db
$stmt->execute([':college_id' => $collegeId]);
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check for success/error messages from DeanController
$success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : null;
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : null;
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

<h2 class="text-3xl font-bold text-gray-600 mb-6 slide-in-left">Classroom Management</h2>

<!-- Add Classroom Form -->
<div class="bg-white p-6 rounded-lg shadow-md card mb-8">
    <h3 class="text-xl font-semibold text-gray-600 mb-4">Add New Classroom</h3>
    <form action="/dean/classroom" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label for="room_name" class="block text-sm font-medium text-gray-600">Room Name</label>
            <input type="text" id="room_name" name="room_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gold-400 focus:ring focus:ring-gold-400 focus:ring-opacity-50" placeholder="e.g., Lecture Room 101">
        </div>
        <div>
            <label for="building" class="block text-sm font-medium text-gray-600">Building</label>
            <input type="text" id="building" name="building" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gold-400 focus:ring focus:ring-gold-400 focus:ring-opacity-50" placeholder="e.g., Science Building">
        </div>
        <div>
            <label for="department_id" class="block text-sm font-medium text-gray-600">Department</label>
            <select id="department_id" name="department_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gold-400 focus:ring focus:ring-gold-400 focus:ring-opacity-50">
                <option value="">Select Department</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo $dept['department_id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="capacity" class="block text-sm font-medium text-gray-600">Capacity</label>
            <input type="number" id="capacity" name="capacity" required min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gold-400 focus:ring focus:ring-gold-400 focus:ring-opacity-50" placeholder="e.g., 50">
        </div>
        <div class="md:col-span-2">
            <button type="submit" name="add_classroom" class="bg-gold-400 text-white px-4 py-2 rounded hover:bg-gold-500 btn">Add Classroom</button>
        </div>
    </form>
</div>

<!-- Classrooms List -->
<div class="bg-white p-6 rounded-lg shadow-md card overflow-x-auto">
    <h3 class="text-xl font-semibold text-gray-600 mb-4">Classrooms</h3>
    <?php if (empty($classrooms)): ?>
        <p class="text-gray-600 text-lg">No classrooms found for your college.</p>
    <?php else: ?>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Building</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Capacity</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($classrooms as $classroom): ?>
                    <tr class="hover:bg-gray-50 transition-all duration-200 slide-in-right">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($classroom['room_name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($classroom['building']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($classroom['department_name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($classroom['capacity']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Room Reservations -->
<div class="bg-white p-6 rounded-lg shadow-md card mt-8 overflow-x-auto">
    <h3 class="text-xl font-semibold text-gray-600 mb-4">Pending Room Reservations</h3>
    <?php
    $query = "
    SELECT rr.reservation_id, rr.room_id, c.room_name, rr.start_time, rr.end_time, rr.description, u.first_name, u.last_name
    FROM room_reservations rr
    JOIN classrooms c ON rr.room_id = c.room_id
    JOIN departments d ON c.department_id = d.department_id
    JOIN users u ON rr.reserved_by = u.user_id
    WHERE d.college_id = :college_id AND rr.approval_status = 'Pending'
    ORDER BY rr.start_time";
    $stmt = $controller->db->prepare($query); // Changed to $controller->db
    $stmt->execute([':college_id' => $collegeId]);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <?php if (empty($reservations)): ?>
        <p class="text-gray-600 text-lg">No pending room reservations.</p>
    <?php else: ?>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requested By</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($reservations as $reservation): ?>
                    <tr class="hover:bg-gray-50 transition-all duration-200 slide-in-right">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($reservation['room_name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($reservation['first_name'] . ' ' . $reservation['last_name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($reservation['purpose']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            <?php echo htmlspecialchars(date('M d, Y', strtotime($reservation['start_time'])) . ' ' . date('h:i A', strtotime($reservation['start_time'])) . ' - ' . date('h:i A', strtotime($reservation['end_time']))); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <form action="/dean/classroom" method="POST" class="inline">
                                <input type="hidden" name="reservation_id" value="<?php echo $reservation['reservation_id']; ?>">
                                <input type="hidden" name="status" value="Approved">
                                <button type="submit" class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600 btn">Approve</button>
                            </form>
                            <form action="/dean/classroom" method="POST" class="inline ml-2">
                                <input type="hidden" name="reservation_id" value="<?php echo $reservation['reservation_id']; ?>">
                                <input type="hidden" name="status" value="Rejected">
                                <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 btn">Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>