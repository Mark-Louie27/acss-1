<?php
ob_start();
?>

<div class="bg-white">
    <h2 class="text-2xl font-semibold text-gray-900 mb-4"><?= htmlspecialchars($dept['college_name']) ?></h2>
    <div class="mb-4 flex flex-col md:flex-row gap-4">
        <select id="department-filter" class="p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400">
            <option value="">All Departments</option>
            <?php foreach ($departments as $dept): ?>
                <option value="<?= $dept['department_id'] ?>"><?= htmlspecialchars($dept['department_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" id="search-schedule" placeholder="Search by course code or section..." class="p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400">
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200 rounded-lg">
            <thead class="bg-gray-100">
                <tr>
                    <th class="py-2 px-4 border-b text-left text-gray-600">Department</th>
                    <th class="py-2 px-4 border-b text-left text-gray-600">Course Code</th>
                    <th class="py-2 px-4 border-b text-left text-gray-600">Section</th>
                    <th class="py-2 px-4 border-b text-left text-gray-600">Day</th>
                    <th class="py-2 px-4 border-b text-left text-gray-600">Time</th>
                    <th class="py-2 px-4 border-b text-left text-gray-600">Room</th>
                    <th class="py-2 px-4 border-b text-left text-gray-600">Schedule Type</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($departments as $dept): ?>
                    <?php if (!empty($schedules[$dept['department_id']])): ?>
                        <?php foreach ($schedules[$dept['department_id']] as $schedule): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="py-2 px-4 border-b"><?= htmlspecialchars($dept['department_name']) ?></td>
                                <td class="py-2 px-4 border-b"><?= htmlspecialchars($schedule['course_code']) ?></td>
                                <td class="py-2 px-4 border-b"><?= htmlspecialchars($schedule['section_name']) ?></td>
                                <td class="py-2 px-4 border-b"><?= htmlspecialchars($schedule['day_of_week']) ?></td>
                                <td class="py-2 px-4 border-b"><?= htmlspecialchars($schedule['start_time']) . ' - ' . htmlspecialchars($schedule['end_time']) ?></td>
                                <td class="py-2 px-4 border-b"><?= htmlspecialchars($schedule['room_name']) ?></td>
                                <td class="py-2 px-4 border-b"><?= htmlspecialchars($schedule['schedule_type']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="py-4 text-center text-gray-500">No schedules available for <?= htmlspecialchars($dept['department_name']) ?>.</td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>



<script>
    document.addEventListener('DOMContentLoaded', () => {
        const departmentFilter = document.getElementById('department-filter');
        const searchInput = document.getElementById('search-schedule');
        const tableRows = document.querySelectorAll('tbody tr');

        function filterTable() {
            const deptId = departmentFilter.value;
            const searchTerm = searchInput.value.toLowerCase();

            tableRows.forEach(row => {
                const dept = row.cells[0].textContent.toLowerCase();
                const courseCode = row.cells[1].textContent.toLowerCase();
                const section = row.cells[2].textContent.toLowerCase();

                const deptMatch = !deptId || dept.includes(deptId.toLowerCase());
                const searchMatch = !searchTerm || courseCode.includes(searchTerm) || section.includes(searchTerm);

                row.style.display = deptMatch && searchMatch ? '' : 'none';
            });
        }

        departmentFilter.addEventListener('change', filterTable);
        searchInput.addEventListener('input', filterTable);
    });
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>