<?php
ob_start();
?>

<style>
    :root {
        --gold: #D4AF37;
        --white: #FFFFFF;
        --gray-dark: #4B5563;
        --gray-light: #E5E7EB;
    }

    .fade-in {
        animation: fadeIn 0.5s ease-in;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    .btn-gold {
        background-color: var(--gold);
        color: var(--white);
    }

    .btn-gold:hover {
        background-color: #b8972e;
    }

    .badge-college {
        background-color: #4F46E5;
        color: white;
    }

    .badge-department {
        background-color: #059669;
        color: white;
    }

    .badge-semester {
        background-color: #DC2626;
        color: white;
    }
</style>

<div class="min-h-screen bg-gray-100 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header Section -->
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 bg-clip-text text-transparent bg-gradient-to-r from-yellow-600 to-yellow-400">
                        Schedule History - All Colleges
                    </h1>
                    <p class="mt-2 text-gray-600">View historical schedules across all colleges and departments</p>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 mb-6">
            <div class="p-6">
                <form method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <!-- College Filter -->
                    <div>
                        <label for="college_id" class="block text-sm font-medium text-gray-700 mb-2">College</label>
                        <select name="college_id" id="college_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                            <option value="">All Colleges</option>
                            <?php foreach ($colleges as $college): ?>
                                <option value="<?= $college['college_id'] ?>" <?= isset($_POST['college_id']) && $_POST['college_id'] == $college['college_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($college['college_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Department Filter -->
                    <div>
                        <label for="department_id" class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                        <select name="department_id" id="department_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['department_id'] ?>" data-college="<?= $dept['college_id'] ?>"
                                    <?= isset($_POST['department_id']) && $_POST['department_id'] == $dept['department_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept['department_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Semester Filter -->
                    <div>
                        <label for="semester_id" class="block text-sm font-medium text-gray-700 mb-2">Semester</label>
                        <select name="semester_id" id="semester_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                            <option value="">All Semesters</option>
                            <?php foreach ($allSemesters as $semester): ?>
                                <option value="<?= $semester['semester_id'] ?>" <?= isset($_POST['semester_id']) && $_POST['semester_id'] == $semester['semester_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($semester['semester_name'] . ' ' . $semester['academic_year']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Academic Year Filter -->
                    <div>
                        <label for="academic_year" class="block text-sm font-medium text-gray-700 mb-2">Academic Year</label>
                        <select name="academic_year" id="academic_year" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                            <option value="">All Years</option>
                            <?php
                            $years = array_unique(array_column($allSemesters, 'academic_year'));
                            rsort($years);
                            foreach ($years as $year): ?>
                                <option value="<?= $year ?>" <?= isset($_POST['academic_year']) && $_POST['academic_year'] == $year ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($year) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex items-end space-x-2">
                        <button type="submit" class="btn-gold px-6 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500 w-full">
                            Search Schedules
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 fade-in">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6 fade-in">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <!-- Results Section -->
        <?php if (!empty($historicalSchedules)): ?>
            <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-900">Schedule Results</h2>
                    <span class="text-sm font-medium text-gray-500 bg-gray-100 px-3 py-1 rounded-full">
                        <?= count($historicalSchedules) ?> Schedules Found
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Faculty</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Schedule</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">College</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Semester</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($historicalSchedules as $schedule): ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-150 fade-in">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($schedule['course_code']) ?></div>
                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($schedule['course_name']) ?></div>
                                        <div class="text-xs text-gray-400"><?= $schedule['units'] ?> units</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= htmlspecialchars($schedule['section_name']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= htmlspecialchars($schedule['faculty_name']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div class="font-medium"><?= htmlspecialchars($schedule['room_name']) ?></div>
                                        <div class="text-xs text-gray-500"><?= htmlspecialchars($schedule['building']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div class="font-medium"><?= htmlspecialchars($schedule['day_of_week']) ?></div>
                                        <div class="text-xs text-gray-500">
                                            <?= htmlspecialchars($schedule['start_time']) ?> - <?= htmlspecialchars($schedule['end_time']) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium badge-department">
                                            <?= htmlspecialchars($schedule['department_name']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium badge-college">
                                            <?= htmlspecialchars($schedule['college_name']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium badge-semester">
                                            <?= htmlspecialchars($schedule['semester_name'] . ' ' . $schedule['academic_year']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-8 text-center">
                <i class="fas fa-search text-gray-300 text-4xl mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No schedules found</h3>
                <p class="text-gray-500">Try adjusting your search criteria to find schedules.</p>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-8 text-center">
                <i class="fas fa-calendar-alt text-gray-300 text-4xl mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Search Schedule History</h3>
                <p class="text-gray-500">Use the filters above to search for historical schedules across all colleges and departments.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const collegeSelect = document.getElementById('college_id');
        const departmentSelect = document.getElementById('department_id');
        const allDepartments = <?= json_encode($departments) ?>;

        // Function to populate departments based on college
        function populateDepartments(collegeId) {
            // Clear current options except the first one
            departmentSelect.innerHTML = '<option value="">All Departments</option>';

            // Filter departments based on college
            const filteredDepartments = collegeId ?
                allDepartments.filter(dept => dept.college_id == collegeId) :
                allDepartments;

            // Add filtered departments to select
            filteredDepartments.forEach(dept => {
                const option = document.createElement('option');
                option.value = dept.department_id;
                option.textContent = dept.department_name;
                departmentSelect.appendChild(option);
            });

            // Preserve selected department if it exists in the filtered list
            const currentDeptId = '<?= isset($_POST['department_id']) ? $_POST['department_id'] : '' ?>';
            if (currentDeptId && filteredDepartments.some(dept => dept.department_id == currentDeptId)) {
                departmentSelect.value = currentDeptId;
            }
        }

        // Initialize departments on page load
        const initialCollegeId = collegeSelect.value;
        populateDepartments(initialCollegeId);

        // Handle college change
        collegeSelect.addEventListener('change', function() {
            populateDepartments(this.value);
        });
    });
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>