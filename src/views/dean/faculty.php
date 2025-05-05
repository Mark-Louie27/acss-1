<?php
ob_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 font-sans">
    <div class="container mx-auto p-6">
        <!-- Header with Semester -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Faculty Management</h1>
            <div class="text-gray-600">
                Current Semester:
                <?php echo $currentSemester ? htmlspecialchars($currentSemester['semester_name'] . ' ' . $currentSemester['academic_year']) : 'Not Set'; ?>
            </div>
        </div>

        <!-- Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 text-green-700 p-4 rounded mb-6">
                <?php echo htmlspecialchars($_SESSION['success']);
                unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded mb-6">
                <?php echo htmlspecialchars($_SESSION['error']);
                unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Department Filter -->
        <div class="mb-6">
            <label for="departmentFilter" class="block text-gray-700 font-semibold mb-2">Filter by Department:</label>
            <select id="departmentFilter" class="w-full max-w-xs p-2 border rounded bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-yellow-500">
                <option value="all">All Departments</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo $dept['department_id']; ?>">
                        <?php echo htmlspecialchars($dept['department_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Program Chairs Section -->
        <h2 class="text-2xl font-bold mb-4 text-gray-800">Program Chairs</h2>
        <div class="bg-white shadow-md rounded-lg overflow-hidden mb-8">
            <table class="min-w-full" id="programChairsTable">
                <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="py-3 px-4 text-left">Name</th>
                        <th class="py-3 px-4 text-left">Program</th>
                        <th class="py-3 px-4 text-left">Department</th>
                        <th class="py-3 px-4 text-left">Status</th>
                        <th class="py-3 px-4 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($programChairs)): ?>
                        <tr class="no-results">
                            <td colspan="5" class="py-4 px-4 text-center text-gray-500">No program chairs found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($programChairs as $chair): ?>
                            <tr class="border-b hover:bg-gray-50" data-department="<?php echo $chair['department_id']; ?>">
                                <td class="py-3 px-4"><?php echo htmlspecialchars($chair['last_name'] . ', ' . $chair['first_name']); ?></td>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($chair['program_name']); ?></td>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($chair['department_name']); ?></td>
                                <td class="py-3 px-4">
                                    <?php echo $chair['is_active'] ? '<span class="text-green-600">Active</span>' : '<span class="text-red-600">Inactive</span>'; ?>
                                </td>
                                <td class="py-3 px-4">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="user_id" value="<?php echo $chair['user_id']; ?>">
                                        <input type="hidden" name="action" value="<?php echo $chair['is_active'] ? 'deactivate' : 'activate'; ?>">
                                        <button type="submit" class="px-3 py-1 rounded text-white <?php echo $chair['is_active'] ? 'bg-red-500 hover:bg-red-600' : 'bg-green-500 hover:bg-green-600'; ?>">
                                            <?php echo $chair['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Faculty Section -->
        <h2 class="text-2xl font-bold mb-4 text-gray-800">Faculty</h2>
        <div class="bg-white shadow-md rounded-lg overflow-hidden mb-8">
            <table class="min-w-full" id="facultyTable">
                <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="py-3 px-4 text-left">Name</th>
                        <th class="py-3 px-4 text-left">Academic Rank</th>
                        <th class="py-3 px-4 text-left">Employment Type</th>
                        <th class="py-3 px-4 text-left">Department</th>
                        <th class="py-3 px-4 text-left">Status</th>
                        <th class="py-3 px-4 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($faculty)): ?>
                        <tr class="no-results">
                            <td colspan="6" class="py-4 px-4 text-center text-gray-500">No faculty found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($faculty as $member): ?>
                            <tr class="border-b hover:bg-gray-50" data-department="<?php echo $member['department_id']; ?>">
                                <td class="py-3 px-4"><?php echo htmlspecialchars($member['last_name'] . ', ' . $member['first_name']); ?></td>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($member['academic_rank']); ?></td>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($member['employment_type']); ?></td>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($member['department_name']); ?></td>
                                <td class="py-3 px-4">
                                    <?php echo $member['is_active'] ? '<span class="text-green-600">Active</span>' : '<span class="text-red-600">Inactive</span>'; ?>
                                </td>
                                <td class="py-3 px-4">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="user_id" value="<?php echo $member['user_id']; ?>">
                                        <input type="hidden" name="action" value="<?php echo $member['is_active'] ? 'deactivate' : 'activate'; ?>">
                                        <button type="submit" class="px-3 py-1 rounded text-white <?php echo $member['is_active'] ? 'bg-red-500 hover:bg-red-600' : 'bg-green-500 hover:bg-green-600'; ?>">
                                            <?php echo $member['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pending Requests Section -->
        <h2 class="text-2xl font-bold mb-4 text-gray-800">Pending Faculty Requests</h2>
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <table class="min-w-full">
                <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="py-3 px-4 text-left">Name</th>
                        <th class="py-3 px-4 text-left">Academic Rank</th>
                        <th class="py-3 px-4 text-left">Employment Type</th>
                        <th class="py-3 px-4 text-left">Department</th>
                        <th class="py-3 px-4 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="5" class="py-4 px-4 text-center text-gray-500">No pending requests.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($requests as $request): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="py-3 px-4"><?php echo htmlspecialchars($request['last_name'] . ', ' . $request['first_name']); ?></td>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($request['academic_rank']); ?></td>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($request['employment_type']); ?></td>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($request['department_name']); ?></td>
                                <td class="py-3 px-4 flex space-x-2">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                        <input type="hidden" name="action" value="accept">
                                        <button type="submit" class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600">Accept</button>
                                    </form>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.getElementById('departmentFilter').addEventListener('change', function() {
            const selectedDept = this.value;
            filterTable('programChairsTable', selectedDept);
            filterTable('facultyTable', selectedDept);
        });

        function filterTable(tableId, departmentId) {
            const table = document.getElementById(tableId);
            const rows = table.querySelectorAll('tbody tr:not(.no-results)');
            const noResultsRow = table.querySelector('tbody tr.no-results');
            let visibleRows = 0;

            rows.forEach(row => {
                const rowDept = row.getAttribute('data-department');
                if (departmentId === 'all' || rowDept === departmentId) {
                    row.style.display = '';
                    visibleRows++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (noResultsRow) {
                noResultsRow.style.display = visibleRows === 0 ? '' : 'none';
            }
        }
    </script>
</body>

</html>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>