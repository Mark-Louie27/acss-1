<?php
ob_start();
?>

<div class="flex flex-col p-6 bg-gray-100 min-h-screen">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</h1>
                <p class="mt-2 text-lg text-gray-600">Managing: <span class="font-semibold text-gold-600"><?php echo htmlspecialchars($departmentName ?? 'Department'); ?></span></p>
            </div>
            <div class="text-sm text-gray-500">
                <?php echo date('l, F j, Y'); ?>
            </div>
        </div>
    </div>

    <!-- Error Message -->
    <?php if (isset($error)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
            <p class="font-bold">Error</p>
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php else: ?>
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- Schedules Card -->
            <div class="bg-white rounded-lg shadow-md p-6 flex items-center hover:shadow-lg transition-shadow duration-300 cursor-pointer" onclick="window.location.href='/chair/schedule'">
                <div class="p-3 rounded-full bg-gold-100 text-gold-600 mr-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-medium text-gray-600">Schedules</h3>
                    <p class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($schedulesCount ?? 0); ?></p>
                    <p class="text-sm text-gray-500">Active schedules</p>
                </div>
            </div>
            <!-- Faculty Card -->
            <div class="bg-white rounded-lg shadow-md p-6 flex items-center hover:shadow-lg transition-shadow duration-300 cursor-pointer" onclick="window.location.href='/chair/faculty'">
                <div class="p-3 rounded-full bg-gold-100 text-gold-600 mr-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-medium text-gray-600">Faculty</h3>
                    <p class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($facultyCount ?? 0); ?></p>
                    <p class="text-sm text-gray-500">Teaching staff</p>
                </div>
            </div>
            <!-- Courses Card -->
            <div class="bg-white rounded-lg shadow-md p-6 flex items-center hover:shadow-lg transition-shadow duration-300 cursor-pointer" onclick="window.location.href='/chair/courses'">
                <div class="p-3 rounded-full bg-gold-100 text-gold-600 mr-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.747 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-medium text-gray-600">Courses</h3>
                    <p class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($coursesCount ?? 0); ?></p>
                    <p class="text-sm text-gray-500">Available courses</p>
                </div>
            </div>
        </div>

        <!-- Charts and Curricula -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Schedule Distribution Chart -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Schedule Distribution</h3>
                <div class="h-64">
                    <canvas id="scheduleChart"></canvas>
                </div>
            </div>
            <!-- Faculty Workload Chart -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Faculty Workload</h3>
                <div class="h-64">
                    <canvas id="workloadChart"></canvas>
                </div>
            </div>
            <!-- Curricula Table -->
            <div class="bg-white rounded-lg shadow-md p-6 lg:col-span-2">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Curricula Status</h3>
                <?php if (empty($curricula)): ?>
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No curricula</h3>
                        <p class="mt-1 text-sm text-gray-500">No curricula available for this department.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Curriculum</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Program</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($curricula as $curriculum): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($curriculum['curriculum_name']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($curriculum['program_name']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $curriculum['status'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo $curriculum['status'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mb-8">
            <h3 class="text-xl font-semibold text-gray-900 mb-4">Quick Actions</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <a href="/chair/schedule/create" class="bg-gold-600 text-white px-4 py-3 rounded-md hover:bg-gold-700 transition duration-300 flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Create Schedule
                </a>
                <a href="/chair/faculty" class="bg-white border border-gray-300 text-gray-700 px-4 py-3 rounded-md hover:bg-gray-50 transition duration-300 flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Manage Faculty
                </a>
                <a href="/chair/courses" class="bg-white border border-gray-300 text-gray-700 px-4 py-3 rounded-md hover:bg-gray-50 transition duration-300 flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.747 0-3.332.477-4.5 1.253" />
                    </svg>
                    Manage Courses
                </a>
                <a href="/chair/schedule" class="bg-white border border-gray-300 text-gray-700 px-4 py-3 rounded-md hover:bg-gray-50 transition duration-300 flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    View Schedules
                </a>
            </div>
        </div>

        <!-- Recent Schedules -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Recent Schedules</h3>
                <p class="text-sm text-gray-500 mt-1">Recently created schedules in your department</p>
            </div>
            <div class="overflow-x-auto">
                <?php if (empty($recentSchedules)): ?>
                    <div class="px-6 py-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No schedules</h3>
                        <p class="mt-1 text-sm text-gray-500">Create your first schedule to get started.</p>
                        <div class="mt-6">
                            <a href="/chair/schedule/create" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-gold-600 hover:bg-gold-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gold-500">
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                New Schedule
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Faculty</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Day & Time</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th scope="col" class="relative px-6 py-3">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($recentSchedules as $schedule): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($schedule['course_code']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($schedule['faculty_name']); ?></div>
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
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="/chair/schedule/edit/<?php echo $schedule['schedule_id']; ?>" class="text-gold-600 hover:text-gold-900 mr-3">Edit</a>
                                        <a href="/chair/schedule/delete/<?php echo $schedule['schedule_id']; ?>" class="text-red-600 hover:text-red-900">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Chart.js Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Schedule Distribution Chart
    const scheduleCtx = document.getElementById('scheduleChart').getContext('2d');
    const scheduleChart = new Chart(scheduleCtx, {
        type: 'doughnut',
        data: {
            labels: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
            datasets: [{
                data: <?php echo $scheduleDistJson; ?>,
                backgroundColor: [
                    '#D4AF37', // Gold
                    '#FFD700', // Light Gold
                    '#4B5563', // Gray
                    '#6B7280', // Light Gray
                    '#9CA3AF', // Lighter Gray
                    '#D1D5DB' // Lightest Gray
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        font: {
                            size: 14,
                            family: 'Inter, sans-serif'
                        },
                        color: '#4B5563'
                    }
                }
            }
        }
    });

    // Faculty Workload Chart
    const workloadCtx = document.getElementById('workloadChart').getContext('2d');
    const workloadChart = new Chart(workloadCtx, {
        type: 'bar',
        data: {
            labels: <?php echo $workloadLabelsJson; ?>,
            datasets: [{
                label: 'Courses Assigned',
                data: <?php echo $workloadCountsJson; ?>,
                backgroundColor: '#D4AF37',
                borderColor: '#FFD700',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        color: '#4B5563',
                        font: {
                            family: 'Inter, sans-serif'
                        }
                    },
                    grid: {
                        color: '#D1D5DB'
                    }
                },
                x: {
                    ticks: {
                        color: '#4B5563',
                        font: {
                            family: 'Inter, sans-serif'
                        }
                    },
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>