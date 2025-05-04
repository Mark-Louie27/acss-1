<?php
ob_start();
?>

<div class="p-6 bg-gray-50 min-h-screen font-sans">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">My Class Schedule</h2>
    <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-yellow-600 text-white">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Course Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Course Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Section</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Room</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Day</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Type</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (isset($schedules) && !empty($schedules)): ?>
                        <?php foreach ($schedules as $schedule): ?>
                            <tr class="hover:bg-yellow-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($schedule['course_code'] ?? 'N/A'); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($schedule['course_name'] ?? 'N/A'); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full"><?php echo htmlspecialchars($schedule['section_name'] ?? 'N/A'); ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($schedule['room_name'] ?? 'TBD'); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($schedule['day_of_week'] ?? 'N/A'); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($schedule['start_time'] . ' - ' . $schedule['end_time'] ?? 'N/A'); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($schedule['schedule_type'] ?? 'N/A'); ?></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">No schedules found for this term.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-6 flex justify-end">
            <a href="/chair/schedule" class="text-sm bg-yellow-500 hover:bg-yellow-600 text-white py-2 px-4 rounded-lg transition duration-300 shadow-sm flex items-center">
                Full Schedule
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>