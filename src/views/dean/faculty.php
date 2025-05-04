<?php
ob_start();
$collegeId = $controller->getDeanCollegeId($_SESSION['user_id']);
$stmt = $controller->db->prepare($query); // Changed to $controller->db


$query = "
        SELECT fr.request_id, fr.first_name, fr.last_name, fr.email, fr.academic_rank, fr.employment_type, d.department_name
        FROM faculty_requests fr
        JOIN departments d ON fr.department_id = d.department_id
        WHERE d.college_id = :college_id AND fr.status = 'pending'
        ORDER BY fr.created_at";
$stmt = $controller->db->prepare($query);
$stmt->execute([':college_id' => $collegeId]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

<h2 class="text-3xl font-bold text-gray-600 mb-6 slide-in-left">Faculty Management</h2>

<!-- Faculty List -->
<div class="bg-white p-6 rounded-lg shadow-md card overflow-x-auto">
    <h3 class="text-xl font-semibold text-gray-600 mb-4">Faculty</h3>
    <?php if (empty($faculty)): ?>
        <p class="text-gray-600 text-lg">No faculty found in your college.</p>
    <?php else: ?>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Academic Rank</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employment Type</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($faculty as $f): ?>
                    <tr class="hover:bg-gray-50 transition-all duration-200 slide-in-right">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            <?php echo htmlspecialchars($f['first_name'] . ' ' . ($f['middle_name'] ? $f['middle_name'][0] . '. ' : '') . $f['last_name'] . ($f['suffix'] ? ' ' . $f['suffix'] : '')); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($f['department_name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($f['academic_rank']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($f['employment_type']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Pending Faculty Requests -->
<div class="bg-white p-6 rounded-lg shadow-md card mt-8 overflow-x-auto">
    <h3 class="text-xl font-semibold text-gray-600 mb-4">Pending Faculty Requests</h3>
    <?php if (empty($requests)): ?>
        <p class="text-gray-600 text-lg">No pending faculty requests.</p>
    <?php else: ?>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Academic Rank</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($requests as $request): ?>
                    <tr class="hover:bg-gray-50 transition-all duration-200 slide-in-right">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($request['email']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($request['department_name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($request['academic_rank']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <form action="/dean/faculty" method="POST" class="inline">
                                <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                <input type="hidden" name="status" value="approved">
                                <button type="submit" class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600 btn">Approve</button>
                            </form>
                            <form action="/dean/faculty" method="POST" class="inline ml-2">
                                <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                <input type="hidden" name="status" value="rejected">
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