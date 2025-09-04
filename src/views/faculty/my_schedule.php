<?php
ob_start();
$schedules = isset($schedules) ? $schedules : [];
$semesterName = isset($semesterName) ? $semesterName : 'Unknown Semester';
$departmentName = isset($departmentName) ? $departmentName : 'Unknown Department';
$totalHours = isset($totalHours) ? $totalHours : 0;
$error = isset($error) ? $error : '';
$showAllSchedules = isset($showAllSchedules) ? $showAllSchedules : false;
?>

<div class="flex flex-col p-6 bg-gray-100 min-h-screen">
    <!-- Header Section -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">My Teaching Schedule</h1>
        <div class="mt-2 flex flex-col sm:flex-row sm:items-center sm:space-x-4">
            <p class="text-sm text-gray-600">Semester: <span class="font-medium"><?php echo htmlspecialchars($semesterName); ?></span></p>
            <p class="text-sm text-gray-600">Department: <span class="font-medium"><?php echo htmlspecialchars($departmentName); ?></span></p>
            <p class="text-sm text-gray-600">Total Weekly Hours: <span class="font-medium"><?php echo number_format($totalHours, 2); ?> hrs</span></p>
        </div>
    </div>

    <!-- Error Message -->
    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <!-- Schedule Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Assigned Classes</h3>
            <?php if ($showAllSchedules && !empty($schedules)): ?>
                <p class="text-sm text-gray-500">No schedules found for the current semester. Showing all schedules instead.</p>
            <?php endif; ?>
        </div>
        <?php if (empty($schedules)): ?>
            <div class="px-6 py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No schedules</h3>
                <p class="mt-1 text-sm text-gray-500">You have no assigned schedules for the current semester.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course Code</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Day & Time</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hours</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($schedules as $schedule): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($schedule['course_code']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($schedule['course_name']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($schedule['section_name']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($schedule['room_name'] ?? 'Online'); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($schedule['day_of_week']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($schedule['start_time'] . ' - ' . $schedule['end_time']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $typeClasses = [
                                        'F2F' => 'bg-green-100 text-green-800',
                                        'Online' => 'bg-blue-100 text-blue-800',
                                        'Hybrid' => 'bg-purple-100 text-purple-800',
                                        'Asynchronous' => 'bg-yellow-100 text-yellow-800'
                                    ];
                                    $typeClass = $typeClasses[$schedule['schedule_type']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $typeClass; ?>">
                                        <?php echo htmlspecialchars($schedule['schedule_type']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo number_format($schedule['duration_hours'], 2); ?> hrs</div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>