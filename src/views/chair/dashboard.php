<?php
ob_start();
?>

<div class="p-4 sm:p-6 bg-gray-50 min-h-screen font-sans">
    <!-- Main Header Section with Gold Accent and Semester Selector -->
    <div class="bg-gray-800 text-white rounded-xl p-6 sm:p-8 mb-8 shadow-lg relative overflow-hidden">
        <div class="absolute top-0 left-0 w-2 h-full bg-yellow-600"></div>

        <!-- Main Header Row -->
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 mb-6">
            <div class="flex-1">
                <h1 class="text-2xl sm:text-3xl font-bold">PRMSU Scheduling System</h1>
                <p class="text-gray-300 text-sm sm:text-base mt-1">
                    <?php echo htmlspecialchars($departmentName ?? 'Unknown Department'); ?>
                </p>
            </div>

            <!-- Right side: Status badges -->
            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                <!-- Department Switcher (if multiple departments) -->
                <?php if (!empty($departments) && count($departments) > 1): ?>
                    <div class="relative group">
                        <button id="deptSwitcherBtn" class="bg-gray-700 hover:bg-gray-600 px-3 py-2 rounded-lg flex items-center transition-colors text-xs sm:text-sm">
                            <svg class="w-4 h-4 mr-1 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <span class="hidden sm:inline">Switch Dept</span>
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Dropdown -->
                        <div id="deptSwitcherDropdown" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-xl z-50 max-h-64 overflow-y-auto">
                            <?php foreach ($departments as $dept): ?>
                                <button
                                    class="dept-option w-full text-left px-4 py-2 hover:bg-gray-100 text-gray-800 text-sm flex items-center justify-between"
                                    data-department-id="<?php echo htmlspecialchars($dept['department_id']); ?>">
                                    <span><?php echo htmlspecialchars($dept['department_name']); ?></span>
                                    <?php if ($dept['department_id'] == $currentDepartmentId): ?>
                                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    <?php endif; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Semester Selector with Status Indicator -->
                <div class="relative">
                    <select
                        id="semesterSelector"
                        class="bg-gray-700 hover:bg-gray-600 text-white px-3 py-2 rounded-lg appearance-none cursor-pointer transition-colors text-xs sm:text-sm pr-8 focus:outline-none focus:ring-2 focus:ring-yellow-500"
                        style="min-width: 200px;">
                        <?php if (!empty($availableSemesters)): ?>
                            <?php foreach ($availableSemesters as $semester): ?>
                                <option
                                    value="<?php echo htmlspecialchars($semester['semester_id']); ?>"
                                    <?php echo ($semester['semester_id'] == $currentSemesterId) ? 'selected' : ''; ?>>
                                    <?php
                                    echo htmlspecialchars($semester['semester_name'] . ' - ' . $semester['academic_year']);
                                    if ($semester['is_current']) {
                                        echo ' ●'; // Current indicator
                                    }
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <svg class="absolute right-2 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>

                <!-- Active Term Status Badge -->
                <span class="bg-yellow-600 px-3 py-2 rounded-lg flex items-center text-xs sm:text-sm">
                    <?php if ($isHistoricalView ?? false): ?>
                        <svg class="w-4 h-4 mr-1 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="hidden sm:inline">Historical</span>
                    <?php else: ?>
                        <svg class="w-4 h-4 mr-1 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="hidden sm:inline">Active</span>
                    <?php endif; ?>
                </span>

                <!-- Quick Return to Current Button (only show when viewing historical) -->
                <?php if ($isHistoricalView ?? false): ?>
                    <button
                        id="returnToCurrentBtn"
                        class="bg-yellow-500 hover:bg-yellow-600 px-3 py-2 rounded-lg transition-colors text-xs sm:text-sm flex items-center"
                        title="Return to current semester">
                        <svg class="w-4 h-4 sm:mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="hidden sm:inline">Current</span>
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Historical View Alert (if applicable) -->
        <?php if ($isHistoricalView ?? false): ?>
            <div class="bg-blue-900/50 border border-blue-500/30 rounded-lg p-3 flex items-start">
                <svg class="w-5 h-5 text-blue-400 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-sm text-blue-200">
                    <span class="font-semibold text-blue-100">Viewing Historical Data:</span>
                    You are viewing data from <?php echo htmlspecialchars($semesterInfo ?? ''); ?>.
                    Changes cannot be made to historical data.
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Stats Cards - KEPT AS REQUESTED -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-yellow-500 hover:shadow-lg transition duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Faculty Members</h3>
                <div class="p-2 rounded-full bg-yellow-50 text-yellow-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
            <div class="flex justify-between items-end">
                <p class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($facultyCount ?? 24); ?></p>
                <a href="/chair/faculty" class="text-sm text-yellow-600 hover:text-yellow-700 flex items-center font-medium">
                    View all
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-yellow-500 hover:shadow-lg transition duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Total Curriculum</h3>
                <div class="p-2 rounded-full bg-yellow-50 text-yellow-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </div>
            <div class="flex justify-between items-end">
                <p class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars(count($curricula ?? [])); ?></p>
                <a href="/chair/curriculum" class="text-sm text-yellow-600 hover:text-yellow-700 flex items-center font-medium">
                    Manage
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        </div>

        <!-- Compact Schedule Status Card -->
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-yellow-500 hover:shadow-lg transition duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Schedule Status</h3>
                <div class="p-2 rounded-full bg-yellow-50 text-yellow-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>

            <!-- Total Count -->
            <div class="mb-3">
                <p class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($scheduleStatusCounts['total'] ?? 0); ?></p>
                <p class="text-sm text-gray-500">Total Schedules</p>
            </div>

            <!-- Status Progress Bars -->
            <div class="space-y-2">
                <!-- Approved -->
                <?php if ($scheduleStatusCounts['approved'] > 0): ?>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-green-600 font-medium">Approved</span>
                        <span class="text-gray-500"><?php echo htmlspecialchars($scheduleStatusCounts['approved']); ?></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-1.5">
                        <div class="bg-green-500 h-1.5 rounded-full" style="width: <?php echo ($scheduleStatusCounts['approved'] / max(1, $scheduleStatusCounts['total'])) * 100; ?>%"></div>
                    </div>
                <?php endif; ?>

                <!-- Dean Approved -->
                <?php if ($scheduleStatusCounts['dean_approved'] > 0): ?>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-blue-600 font-medium">Dean Approved</span>
                        <span class="text-gray-500"><?php echo htmlspecialchars($scheduleStatusCounts['dean_approved']); ?></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-1.5">
                        <div class="bg-blue-500 h-1.5 rounded-full" style="width: <?php echo ($scheduleStatusCounts['dean_approved'] / max(1, $scheduleStatusCounts['total'])) * 100; ?>%"></div>
                    </div>
                <?php endif; ?>

                <!-- Pending -->
                <?php if ($scheduleStatusCounts['pending'] > 0): ?>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-yellow-600 font-medium">Pending</span>
                        <span class="text-gray-500"><?php echo htmlspecialchars($scheduleStatusCounts['pending']); ?></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-1.5">
                        <div class="bg-yellow-500 h-1.5 rounded-full" style="width: <?php echo ($scheduleStatusCounts['pending'] / max(1, $scheduleStatusCounts['total'])) * 100; ?>%"></div>
                    </div>
                <?php endif; ?>

                <!-- Rejected -->
                <?php if ($scheduleStatusCounts['rejected'] > 0): ?>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-red-600 font-medium">Rejected</span>
                        <span class="text-gray-500"><?php echo htmlspecialchars($scheduleStatusCounts['rejected']); ?></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-1.5">
                        <div class="bg-red-500 h-1.5 rounded-full" style="width: <?php echo ($scheduleStatusCounts['rejected'] / max(1, $scheduleStatusCounts['total'])) * 100; ?>%"></div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mt-4 pt-3 border-t border-gray-200">
                <a href="/chair/schedule_management" class="text-sm text-yellow-600 hover:text-yellow-700 flex items-center font-medium justify-center">
                    View Details
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <!-- Replace entire charts section with this -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
        <!-- Conflict Alert Card -->
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-red-500">
            <div class="flex items-center justify-between mb-3">
                <div class="p-3 rounded-full bg-red-50">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <span class="text-3xl font-bold text-red-600"><?php echo $conflictCount ?? 0; ?></span>
            </div>
            <h4 class="text-sm font-semibold text-gray-700">Schedule Conflicts</h4>
            <p class="text-xs text-gray-500 mt-1">Requires immediate attention</p>
            <?php if (($conflictCount ?? 0) > 0): ?>
                <a href="/chair/schedule_management" class="mt-3 text-xs text-red-600 hover:text-red-700 font-medium flex items-center">
                    Resolve Now →
                </a>
            <?php endif; ?>
        </div>

        <!-- Pending Approvals -->
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between mb-3">
                <div class="p-3 rounded-full bg-yellow-50">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="text-3xl font-bold text-yellow-600"><?php echo $scheduleStatusCounts['pending'] ?? 0; ?></span>
            </div>
            <h4 class="text-sm font-semibold text-gray-700">Pending Schedules</h4>
            <p class="text-xs text-gray-500 mt-1">Awaiting approval</p>
        </div>

        <!-- Unassigned Courses -->
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between mb-3">
                <div class="p-3 rounded-full bg-blue-50">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <span class="text-3xl font-bold text-blue-600"><?php echo $unassignedCourses ?? 0; ?></span>
            </div>
            <h4 class="text-sm font-semibold text-gray-700">Unassigned Courses</h4>
            <p class="text-xs text-gray-500 mt-1">Need faculty assignment</p>
            <?php if (($unassignedCourses ?? 0) > 0): ?>
                <a href="/chair/schedule_management" class="mt-3 text-xs text-blue-600 hover:text-blue-700 font-medium flex items-center">
                    Assign Now →
                </a>
            <?php endif; ?>
        </div>

        <!-- Workload Balance -->
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between mb-3">
                <div class="p-3 rounded-full bg-green-50">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="text-3xl font-bold text-green-600"><?php echo $workloadBalance ?? 85; ?>%</span>
            </div>
            <h4 class="text-sm font-semibold text-gray-700">Workload Balance</h4>
            <p class="text-xs text-gray-500 mt-1">Faculty distribution score</p>
        </div>
    </div>

    <!-- Recent Schedule and Quick Actions Section -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
        <!-- Recent Schedules -->
        <div class="xl:col-span-2 bg-white rounded-xl shadow-md p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-bold text-gray-900">My Schedules</h3>
                <div class="text-sm text-gray-500 flex items-center">
                    <svg class="w-4 h-4 mr-1 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <?php echo htmlspecialchars($semesterInfo ?? '2nd Semester 2024-2025'); ?>
                </div>
            </div>

            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-yellow-600 text-white">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Course</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Section</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Faculty</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Room</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Schedule</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Type</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (isset($schedules) && !empty($schedules)): ?>
                            <?php foreach (array_slice($schedules, 0, 5) as $schedule): ?>
                                <tr class="hover:bg-yellow-50 transition-colors">
                                    <td class="px-4 py-4">
                                        <div class="flex flex-col">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($schedule['course_code'] ?? 'N/A'); ?></div>
                                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars(substr($schedule['course_name'] ?? 'N/A', 0, 30)) . (strlen($schedule['course_name'] ?? '') > 30 ? '...' : ''); ?></div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full"><?php echo htmlspecialchars($schedule['section_name'] ?? 'N/A'); ?></span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($schedule['faculty_name'] ?? 'TBD'); ?></div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($schedule['room_name'] ?? 'TBD'); ?></div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="text-sm font-bold text-blue-600 bg-blue-50 px-2 py-1 rounded inline-block"><?php echo htmlspecialchars($schedule['day_of_week'] ?? 'TBD'); ?></div>
                                        <div class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars(($schedule['start_time'] ?? '') . ' - ' . ($schedule['end_time'] ?? '')); ?></div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="px-2 py-1 text-xs font-medium <?php echo ($schedule['schedule_type'] ?? '') === 'Lecture' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'; ?> rounded-full">
                                            <?php echo htmlspecialchars($schedule['schedule_type'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="py-8 text-center text-gray-500">
                                    <div class="flex flex-col justify-center items-center">
                                        <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <p>No recent schedules found for the current semester.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-6 flex justify-end">
                <a href="/chair/my_schedule" class="text-sm bg-yellow-500 hover:bg-yellow-600 text-white py-2 px-4 rounded-lg transition duration-300 shadow-sm flex items-center">
                    View Full Schedule
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-6">Quick Actions</h3>
            <div class="space-y-4">
                <a href="/chair/faculty/" class="block w-full bg-yellow-500 hover:bg-yellow-600 text-white text-center py-3 px-4 rounded-lg transition duration-300 shadow-sm flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Add Faculty
                </a>
                <a href="/chair/schedule_management/" class="block w-full bg-gray-600 hover:bg-gray-700 text-white text-center py-3 px-4 rounded-lg transition duration-300 shadow-sm flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Create Schedule
                </a>
                <a href="/chair/curriculum/" class="block w-full border border-yellow-500 text-yellow-600 hover:bg-yellow-50 text-center py-3 px-4 rounded-lg transition duration-300 flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Add Curriculum
                </a>


            </div>
        </div>
    </div>

    <!-- Curriculum Overview -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-gray-900">Curriculum Overview</h3>
            <a href="/chair/curriculum" class="text-sm text-yellow-600 hover:text-yellow-700 flex items-center font-medium">
                Manage All
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>

        <?php if (isset($curricula) && !empty($curricula)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach (array_slice($curricula, 0, 6) as $curriculum): ?>
                    <div class="border border-gray-200 rounded-lg p-4 hover:border-yellow-500 hover:shadow-md transition-all duration-300">
                        <div class="flex justify-between items-start mb-3">
                            <h4 class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($curriculum['curriculum_name'] ?? 'N/A'); ?></h4>
                            <span class="px-2 py-1 text-xs font-medium <?php echo ($curriculum['status'] ?? '') === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?> rounded-full">
                                <?php echo htmlspecialchars(ucfirst($curriculum['status'] ?? 'inactive')); ?>
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mb-2"><?php echo htmlspecialchars($curriculum['program_name'] ?? 'N/A'); ?></p>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600"><?php echo htmlspecialchars($curriculum['total_units'] ?? 0); ?> Units</span>
                            <button class="text-xs text-yellow-600 hover:text-yellow-700 font-medium">View Details</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-8 text-gray-500">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p>No curricula found for this department.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Semester Selector
        const semesterSelector = document.getElementById('semesterSelector');
        const returnToCurrentBtn = document.getElementById('returnToCurrentBtn');

        if (semesterSelector) {
            semesterSelector.addEventListener('change', function() {
                const semesterId = this.value;

                // Disable and show loading
                this.disabled = true;
                this.classList.add('opacity-50', 'cursor-wait');

                // Add visual loading indicator
                const originalBg = this.classList.contains('bg-gray-700');
                this.classList.remove('bg-gray-700', 'bg-gray-600');
                this.classList.add('bg-yellow-600');

                fetch('/chair/switch_semester', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'semester_id=' + encodeURIComponent(semesterId)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(
                                'Switched to ' + data.semester_name + ' ' + data.academic_year,
                                'success'
                            );

                            // Reload page after brief delay
                            setTimeout(() => {
                                window.location.reload();
                            }, 800);
                        } else {
                            showNotification(data.error || 'Failed to switch semester', 'error');
                            this.disabled = false;
                            this.classList.remove('opacity-50', 'cursor-wait', 'bg-yellow-600');
                            if (originalBg) this.classList.add('bg-gray-700');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Network error. Please try again.', 'error');
                        this.disabled = false;
                        this.classList.remove('opacity-50', 'cursor-wait', 'bg-yellow-600');
                        if (originalBg) this.classList.add('bg-gray-700');
                    });
            });
        }

        if (returnToCurrentBtn) {
            returnToCurrentBtn.addEventListener('click', function() {
                this.disabled = true;
                this.classList.add('opacity-50');

                fetch('/chair/switch_semester', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'reset=true'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('Returned to current semester', 'success');
                            setTimeout(() => {
                                window.location.reload();
                            }, 500);
                        } else {
                            showNotification('Failed to return to current semester', 'error');
                            this.disabled = false;
                            this.classList.remove('opacity-50');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Network error. Please try again.', 'error');
                        this.disabled = false;
                        this.classList.remove('opacity-50');
                    });
            });
        }

        // Department Switcher Dropdown
        const deptSwitcherBtn = document.getElementById('deptSwitcherBtn');
        const deptSwitcherDropdown = document.getElementById('deptSwitcherDropdown');

        if (deptSwitcherBtn && deptSwitcherDropdown) {
            deptSwitcherBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                deptSwitcherDropdown.classList.toggle('hidden');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!deptSwitcherBtn.contains(e.target) && !deptSwitcherDropdown.contains(e.target)) {
                    deptSwitcherDropdown.classList.add('hidden');
                }
            });

            // Handle department selection
            document.querySelectorAll('.dept-option').forEach(option => {
                option.addEventListener('click', function() {
                    const departmentId = this.getAttribute('data-department-id');

                    fetch('/chair/switch_department', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'department_id=' + encodeURIComponent(departmentId)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showNotification('Department switched successfully', 'success');
                                setTimeout(() => {
                                    window.location.reload();
                                }, 500);
                            } else {
                                showNotification(data.error || 'Failed to switch department', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showNotification('Network error. Please try again.', 'error');
                        });

                    deptSwitcherDropdown.classList.add('hidden');
                });
            });
        }

        // Notification System
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            const bgColor = type === 'success' ? 'bg-green-500' :
                type === 'error' ? 'bg-red-500' :
                type === 'warning' ? 'bg-yellow-500' :
                'bg-blue-500';

            notification.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50 transform transition-all duration-300 flex items-center space-x-2 max-w-md`;

            const icon = type === 'success' ?
                '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>' :
                type === 'error' ?
                '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>' :
                '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';

            notification.innerHTML = `
            ${icon}
            <span>${message}</span>
        `;

            document.body.appendChild(notification);

            // Animate in
            setTimeout(() => {
                notification.classList.add('animate-slide-in-right');
            }, 10);

            // Remove after 4 seconds
            setTimeout(() => {
                notification.classList.add('opacity-0', 'translate-x-full');
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 4000);
        }

        // Schedule Distribution Chart
        const scheduleCtx = document.getElementById('scheduleChart').getContext('2d');
        const scheduleData = <?php echo $scheduleDistJson ?? '[0,0,0,0,0,0]'; ?>;
        const scheduleDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

        new Chart(scheduleCtx, {
            type: 'bar',
            data: {
                labels: scheduleDays,
                datasets: [{
                    label: 'Classes',
                    data: scheduleData,
                    backgroundColor: 'rgba(234, 179, 8, 0.8)',
                    borderColor: 'rgba(234, 179, 8, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            stepSize: 1
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                elements: {
                    bar: {
                        borderRadius: 4
                    }
                }
            }
        });

        // Faculty Workload Chart - FIXED for Chart.js 3.x
        const workloadCtx = document.getElementById('workloadChart').getContext('2d');
        const workloadLabels = <?php echo $workloadLabelsJson ?? '["No Data"]'; ?>;
        const workloadData = <?php echo $workloadCountsJson ?? '[0]'; ?>;

        console.log('Workload Chart Data:', {
            labels: workloadLabels,
            data: workloadData
        });

        new Chart(workloadCtx, {
            type: 'bar', // Changed from 'horizontalBar' to 'bar'
            data: {
                labels: workloadLabels.map(label => {
                    // Clean up extra spaces and truncate long names
                    const cleanLabel = label.trim();
                    return cleanLabel.length > 20 ? cleanLabel.substring(0, 17) + '...' : cleanLabel;
                }),
                datasets: [{
                    label: 'Courses Assigned',
                    data: workloadData,
                    backgroundColor: [
                        'rgba(234, 179, 8, 0.8)',
                        'rgba(202, 138, 4, 0.8)',
                        'rgba(180, 120, 3, 0.8)',
                        'rgba(160, 110, 2, 0.8)',
                        'rgba(140, 100, 1, 0.8)'
                    ],
                    borderColor: [
                        'rgba(234, 179, 8, 1)',
                        'rgba(202, 138, 4, 1)',
                        'rgba(180, 120, 3, 1)',
                        'rgba(160, 110, 2, 1)',
                        'rgba(140, 100, 1, 1)'
                    ],
                    borderWidth: 1,
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                indexAxis: 'y', // This makes the bar chart horizontal
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            title: function(tooltipItems) {
                                return workloadLabels[tooltipItems[0].dataIndex];
                            },
                            label: function(context) {
                                return context.parsed.x + ' course' + (context.parsed.x !== 1 ? 's' : '');
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            stepSize: 1,
                            callback: function(value) {
                                if (value % 1 === 0) {
                                    return value;
                                }
                            }
                        },
                        title: {
                            display: true,
                            text: 'Number of Courses'
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            maxRotation: 0,
                            font: {
                                size: 11
                            }
                        }
                    }
                },
                elements: {
                    bar: {
                        borderRadius: 6
                    }
                },
                layout: {
                    padding: {
                        left: 10,
                        right: 10,
                        top: 10,
                        bottom: 10
                    }
                }
            }
        });
    });
</script>

<style>
    /* Smooth animations */
    @keyframes slide-in-right {
        from {
            transform: translateX(100%);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .animate-slide-in-right {
        animation: slide-in-right 0.3s ease-out forwards;
    }

    /* Custom scrollbar for dropdown */
    #deptSwitcherDropdown {
        scrollbar-width: thin;
        scrollbar-color: #d4af37 #f1f1f1;
    }

    #deptSwitcherDropdown::-webkit-scrollbar {
        width: 6px;
    }

    #deptSwitcherDropdown::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }

    #deptSwitcherDropdown::-webkit-scrollbar-thumb {
        background: #d4af37;
        border-radius: 3px;
    }

    #deptSwitcherDropdown::-webkit-scrollbar-thumb:hover {
        background: #b8960b;
    }

    /* Improved select styling */
    #semesterSelector {
        background-image: none;
        /* Remove default arrow */
    }

    #semesterSelector:hover {
        background-color: #4a5568;
    }

    #semesterSelector:focus {
        outline: none;
        ring: 2px solid #eab308;
    }

    /* Responsive adjustments */
    @media (max-width: 640px) {
        #semesterSelector {
            min-width: 150px;
            font-size: 0.75rem;
            padding: 0.5rem 1.5rem 0.5rem 0.75rem;
        }

        #deptSwitcherDropdown {
            width: 100vw;
            left: 0;
            right: auto;
            border-radius: 0;
        }
    }

    /* Progress bar animations */
    .progress-bar {
        transition: width 0.8s ease-in-out;
    }

    /* Status color coding */
    .status-approved {
        color: #10B981;
    }

    .status-dean-approved {
        color: #3B82F6;
    }

    .status-pending {
        color: #F59E0B;
    }

    .status-rejected {
        color: #EF4444;
    }

    /* Hover effects for status items */
    .status-item:hover {
        transform: translateX(4px);
        transition: transform 0.2s ease-in-out;
    }

    .custom-scrollbar::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #d4af37;
        border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #b8960b;
    }

    /* Chart container responsive adjustments */
    @media (max-width: 768px) {
        .grid {
            grid-template-columns: 1fr;
        }

        .xl\:col-span-2 {
            grid-column: span 1;
        }

        canvas {
            max-height: 200px;
        }
    }

    /* Hover effects for cards */
    .hover\:shadow-lg:hover {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    /* Animation for loading states */
    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: .5;
        }
    }

    /* Table improvements */
    tbody tr:hover {
        transform: translateX(2px);
        transition: all 0.2s ease-in-out;
    }

    /* Button hover animations */
    button,
    a {
        transition: all 0.2s ease-in-out;
    }

    button:hover,
    a:hover {
        transform: translateY(-1px);
    }

    /* Chart responsive text */
    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
    }

    @media (max-width: 640px) {
        .chart-container {
            height: 200px;
        }
    }

    /* Loading skeleton for empty states */
    .skeleton {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
    }

    @keyframes loading {
        0% {
            background-position: 200% 0;
        }

        100% {
            background-position: -200% 0;
        }
    }

    /* Enhanced scrollbar for tables */
    .table-container {
        scrollbar-width: thin;
        scrollbar-color: #d4af37 #f1f1f1;
    }

    .table-container::-webkit-scrollbar {
        height: 6px;
    }

    .table-container::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }

    .table-container::-webkit-scrollbar-thumb {
        background: #d4af37;
        border-radius: 3px;
    }

    .table-container::-webkit-scrollbar-thumb:hover {
        background: #b8960b;
    }

    /* Status badge animations */
    .status-badge {
        position: relative;
        overflow: hidden;
    }

    .status-badge::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s;
    }

    .status-badge:hover::before {
        left: 100%;
    }

    /* Card entrance animations */
    .fade-in {
        animation: fadeIn 0.5s ease-in-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Interactive elements */
    .interactive-card {
        position: relative;
        overflow: hidden;
    }

    .interactive-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(234, 179, 8, 0.1), transparent);
        transition: left 0.6s ease-in-out;
    }

    .interactive-card:hover::before {
        left: 100%;
    }

    /* Enhanced focus states for accessibility */
    button:focus,
    a:focus {
        outline: 2px solid #d4af37;
        outline-offset: 2px;
    }

    /* Print styles */
    @media print {
        .no-print {
            display: none !important;
        }

        .print-full-width {
            width: 100% !important;
            grid-column: 1 / -1 !important;
        }

        .bg-gray-50 {
            background: white !important;
        }

        .shadow-md,
        .shadow-lg {
            box-shadow: none !important;
            border: 1px solid #e5e7eb !important;
        }
    }
</style>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>