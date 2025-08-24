<?php
ob_start();
?>

<div class="p-8 min-h-screen font-sans">
    <!-- Header Section with Current Semester -->
    <div class="bg-gray-800 text-white rounded-xl p-6 mb-8 shadow-lg relative overflow-hidden">
        <div class="absolute top-0 left-0 w-2 h-full bg-gold-400"></div>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold">Dean's Dashboard</h1>
                <p class="text-navy-200 mt-2"><?php echo htmlspecialchars($college['college_name'] ?? 'Arts and Sciences'); ?></p>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-sm bg-navy-800 px-3 py-1 rounded-full flex items-center">
                    <svg class="w-4 h-4 mr-1 text-gold-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Current Semester: <?php echo htmlspecialchars($currentSemester); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Faculty Card -->
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-gold-400 hover:shadow-lg transition duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Total Faculty</h3>
                <div class="p-2 rounded-full bg-gold-50 text-gold-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($stats['total_faculty']); ?></p>
        </div>
        <!-- Classrooms Card -->
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-gold-400 hover:shadow-lg transition duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Total Classrooms</h3>
                <div class="p-2 rounded-full bg-gold-50 text-gold-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($stats['total_classrooms']); ?></p>
        </div>
        <!-- Departments Card -->
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-gold-400 hover:shadow-lg transition duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Total Departments</h3>
                <div class="p-2 rounded-full bg-gold-50 text-gold-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.747 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($stats['total_departments']); ?></p>
        </div>
        <!-- Pending Approvals Card -->
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-gold-400 hover:shadow-lg transition duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Pending Approvals</h3>
                <div class="p-2 rounded-full bg-gold-50 text-gold-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($stats['pending_approvals']); ?></p>
        </div>
    </div>

    <!-- Current Schedule Section -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-gray-900">My Current Schedule</h3>
            <span class="text-sm text-gray-500 flex items-center">
                <svg class="w-4 h-4 mr-1 text-gold-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <?php echo htmlspecialchars($currentSemester); ?>
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-800 text-white rounded-2xl">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Course Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Course Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Room</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Day</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Time</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($schedules)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">No schedule found for the current semester.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($schedules as $schedule): ?>
                            <tr class="hover:bg-gold-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($schedule['course_code']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($schedule['course_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($schedule['room_name'] ?? 'TBD'); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($schedule['day_of_week']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($schedule['start_time'] . ' - ' . $schedule['end_time']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Activities Section -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-gray-900 flex items-center">
                <svg class="w-5 h-5 text-gold-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Recent Activities
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-800 text-white rounded-2xl">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Department</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Action</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Time</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($activities)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">No recent activities found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($activities as $activity): ?>
                            <tr class="hover:bg-gold-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($activity['department_name'] ?? 'Unknown'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($activity['action_type'] ?? 'Unknown Action'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($activity['action_description'] ?? 'No description'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($activity['created_at']))); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    /* Custom Colors */
    .bg-navy-600 {
        background-color: #1e3a8a;
    }

    .bg-navy-700 {
        background-color: #172554;
    }

    .bg-navy-800 {
        background-color: #111827;
    }

    .text-navy-200 {
        color: #e5e7eb;
    }

    .bg-gold-400 {
        background-color: #f59e0b;
    }

    .bg-gold-50 {
        background-color: #fefce8;
    }

    .text-gold-400 {
        color: #f59e0b;
    }
</style>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>