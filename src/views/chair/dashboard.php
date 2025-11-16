<?php
ob_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        * {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
        }

        .hover-scale {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .hover-scale:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 12px 48px rgba(0, 0, 0, 0.12);
        }

        .gradient-header {
            background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
            position: relative;
            overflow: hidden;
        }

        .gradient-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .metric-icon {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 16px rgba(245, 158, 11, 0.3);
        }

        .badge-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: .7;
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeInUp 0.6s ease-out forwards;
        }

        .action-button {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .action-button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .action-button:hover::before {
            width: 300px;
            height: 300px;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        .table-row-hover {
            transition: all 0.2s ease;
        }

        .table-row-hover:hover {
            background: linear-gradient(90deg, rgba(251, 191, 36, 0.05) 0%, rgba(245, 158, 11, 0.05) 100%);
            transform: translateX(4px);
        }
    </style>
</head>

<body class="min-h-screen">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        <!-- Modern Header -->
        <div class="gradient-header text-white rounded-2xl p-8 mb-6 shadow-xl relative fade-in">
            <div class="absolute top-0 right-0 w-64 h-64 bg-yellow-500/10 rounded-full blur-3xl"></div>
            <div class="relative z-10">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                                <i class="fas fa-user-tie text-2xl"></i>
                            </div>
                            <div>
                                <h1 class="text-3xl md:text-4xl font-bold tracking-tight">Program Chair Dashboard</h1>
                                <p class="text-white/80 text-sm mt-1">Automated Classroom Scheduling System</p>
                            </div>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Welcome back! 👋</h3>
                        <p class="text-white/90 flex items-center gap-2">
                            <i class="fas fa-building"></i>
                            <?php echo htmlspecialchars($departmentName ?? 'Department'); ?>
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Semester Selector -->
                        <div class="relative">
                            <select id="semesterSelector" class="bg-gray-600 px-4 py-2 rounded-xl text-sm font-medium appearance-none cursor-pointer pr-10 focus:outline-none focus:ring-2 focus:ring-yellow-500">
                                <?php if (!empty($availableSemesters)): ?>
                                    <?php foreach ($availableSemesters as $semester): ?>
                                        <option value="<?php echo htmlspecialchars($semester['semester_id']); ?>"
                                            <?php echo ($semester['semester_id'] == $currentSemesterId) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($semester['semester_name'] . ' - ' . $semester['academic_year']); ?>
                                            <?php if ($semester['is_current']) echo ' ●'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <i class="fas fa-calendar-alt absolute right-3 top-1/2 transform -translate-y-1/2 text-white pointer-events-none"></i>
                        </div>

                        <!-- Status Badge -->
                        <span class="bg-yellow-500/90 px-4 py-2 rounded-xl text-sm font-semibold flex items-center gap-2 backdrop-blur-md">
                            <span class="status-dot bg-white"></span>
                            <?php echo ($isHistoricalView ?? false) ? 'Historical' : 'Active'; ?>
                        </span>

                        <!-- Return to Current Button -->
                        <?php if ($isHistoricalView ?? false): ?>
                            <button id="returnToCurrentBtn" class="bg-yellow-500 hover:bg-yellow-600 px-4 py-2 rounded-xl text-sm font-semibold flex items-center gap-2 transition">
                                <i class="fas fa-clock"></i>
                                <span class="hidden sm:inline">Current</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Historical Alert -->
                <?php if ($isHistoricalView ?? false): ?>
                    <div class="mt-4 bg-blue-500/20 border border-blue-400/30 rounded-xl p-4 flex items-start gap-3">
                        <i class="fas fa-info-circle text-blue-300 mt-0.5"></i>
                        <div class="text-sm text-blue-100">
                            <span class="font-semibold">Viewing Historical Data:</span>
                            You are viewing data from <?php echo htmlspecialchars($semesterInfo ?? ''); ?>. Changes cannot be made to historical data.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Metrics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- Faculty Card -->
            <div class="glass-card rounded-2xl p-6 hover-scale fade-in" style="animation-delay: 0.1s">
                <div class="flex items-start justify-between mb-4">
                    <div class="metric-icon">
                        <i class="fas fa-chalkboard-teacher text-white text-xl"></i>
                    </div>
                    <span class="text-xs font-semibold text-yellow-600 bg-yellow-100 px-3 py-1 rounded-full">FACULTY</span>
                </div>
                <h3 class="text-gray-600 text-sm font-semibold uppercase tracking-wide mb-2">Faculty Members</h3>
                <p class="text-4xl font-bold text-gray-900 mb-1"><?php echo htmlspecialchars($facultyCount ?? 0); ?></p>
                <a href="/chair/faculty" class="text-sm text-yellow-600 hover:text-yellow-700 flex items-center gap-1 font-medium mt-2">
                    View all <i class="fas fa-arrow-right text-xs"></i>
                </a>
            </div>

            <!-- Curriculum Card -->
            <div class="glass-card rounded-2xl p-6 hover-scale fade-in" style="animation-delay: 0.2s">
                <div class="flex items-start justify-between mb-4">
                    <div class="metric-icon">
                        <i class="fas fa-book text-white text-xl"></i>
                    </div>
                    <span class="text-xs font-semibold text-blue-600 bg-blue-100 px-3 py-1 rounded-full">PROGRAMS</span>
                </div>
                <h3 class="text-gray-600 text-sm font-semibold uppercase tracking-wide mb-2">Total Curriculum</h3>
                <p class="text-4xl font-bold text-gray-900 mb-1"><?php echo count($curricula ?? []); ?></p>
                <a href="/chair/curriculum" class="text-sm text-yellow-600 hover:text-yellow-700 flex items-center gap-1 font-medium mt-2">
                    Manage <i class="fas fa-arrow-right text-xs"></i>
                </a>
            </div>

            <!-- Schedule Status Card -->
            <div class="glass-card rounded-2xl p-6 hover-scale fade-in" style="animation-delay: 0.3s">
                <div class="flex items-start justify-between mb-4">
                    <div class="metric-icon">
                        <i class="fas fa-calendar-check text-white text-xl"></i>
                    </div>
                    <span class="text-xs font-semibold text-purple-600 bg-purple-100 px-3 py-1 rounded-full">SCHEDULES</span>
                </div>
                <h3 class="text-gray-600 text-sm font-semibold uppercase tracking-wide mb-2">Total Schedules</h3>
                <p class="text-4xl font-bold text-gray-900 mb-1"><?php echo $scheduleStatusCounts['total'] ?? 0; ?></p>

                <!-- Mini Status Breakdown -->
                <div class="mt-3 space-y-1">
                    <?php if (($scheduleStatusCounts['approved'] ?? 0) > 0): ?>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-green-600 font-medium">✓ Approved</span>
                            <span class="font-bold text-gray-700"><?php echo $scheduleStatusCounts['approved']; ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (($scheduleStatusCounts['pending'] ?? 0) > 0): ?>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-yellow-600 font-medium">⏳ Pending</span>
                            <span class="font-bold text-gray-700"><?php echo $scheduleStatusCounts['pending']; ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Alert Cards Row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Conflict Alert -->
            <div class="glass-card rounded-2xl p-6 border-l-4 border-red-500 hover-scale fade-in" style="animation-delay: 0.4s">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                    </div>
                    <span class="text-3xl font-bold text-red-600"><?php echo $conflictCount ?? 0; ?></span>
                </div>
                <h4 class="text-sm font-semibold text-gray-700 mb-1">Schedule Conflicts</h4>
                <p class="text-xs text-gray-500">Requires attention</p>
                <?php if (($conflictCount ?? 0) > 0): ?>
                    <a href="/chair/schedule_management" class="mt-3 text-xs text-red-600 hover:text-red-700 font-medium flex items-center gap-1">
                        Resolve Now <i class="fas fa-arrow-right"></i>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Pending -->
            <div class="glass-card rounded-2xl p-6 border-l-4 border-yellow-500 hover-scale fade-in" style="animation-delay: 0.5s">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-clock text-yellow-600 text-xl"></i>
                    </div>
                    <span class="text-3xl font-bold text-yellow-600"><?php echo $scheduleStatusCounts['pending'] ?? 0; ?></span>
                </div>
                <h4 class="text-sm font-semibold text-gray-700 mb-1">Pending Schedules</h4>
                <p class="text-xs text-gray-500">Awaiting approval</p>
            </div>

            <!-- Unassigned -->
            <div class="glass-card rounded-2xl p-6 border-l-4 border-blue-500 hover-scale fade-in" style="animation-delay: 0.6s">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-clipboard-list text-blue-600 text-xl"></i>
                    </div>
                    <span class="text-3xl font-bold text-blue-600"><?php echo $unassignedCourses ?? 0; ?></span>
                </div>
                <h4 class="text-sm font-semibold text-gray-700 mb-1">Unassigned Courses</h4>
                <p class="text-xs text-gray-500">Need faculty assignment</p>
                <?php if (($unassignedCourses ?? 0) > 0): ?>
                    <a href="/chair/schedule_management" class="mt-3 text-xs text-blue-600 hover:text-blue-700 font-medium flex items-center gap-1">
                        Assign Now <i class="fas fa-arrow-right"></i>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Workload -->
            <div class="glass-card rounded-2xl p-6 border-l-4 border-green-500 hover-scale fade-in" style="animation-delay: 0.7s">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-balance-scale text-green-600 text-xl"></i>
                    </div>
                    <span class="text-3xl font-bold text-green-600"><?php echo $workloadBalance ?? 85; ?>%</span>
                </div>
                <h4 class="text-sm font-semibold text-gray-700 mb-1">Workload Balance</h4>
                <p class="text-xs text-gray-500">Faculty distribution</p>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">

            <!-- Recent Schedules -->
            <div class="xl:col-span-2 glass-card rounded-2xl p-6 fade-in" style="animation-delay: 0.8s">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">My Schedules</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            <i class="fas fa-calendar text-yellow-500 mr-1"></i>
                            <?php echo htmlspecialchars($semesterInfo ?? '2nd Semester 2024-2025'); ?>
                        </p>
                    </div>
                    <a href="/chair/my_schedule" class="text-sm text-yellow-600 hover:text-yellow-700 font-semibold flex items-center gap-1">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gradient-to-r from-yellow-500 to-yellow-600 text-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Course</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Section</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Faculty</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Room</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Schedule</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Type</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (isset($schedules) && !empty($schedules)): ?>
                                <?php foreach (array_slice($schedules, 0, 5) as $schedule): ?>
                                    <tr class="table-row-hover">
                                        <td class="px-4 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-xl flex items-center justify-center shadow-sm">
                                                    <i class="fas fa-book text-white text-sm"></i>
                                                </div>
                                                <div>
                                                    <div class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($schedule['course_code'] ?? 'N/A'); ?></div>
                                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars(substr($schedule['course_name'] ?? 'N/A', 0, 30)) . (strlen($schedule['course_name'] ?? '') > 30 ? '...' : ''); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4">
                                            <span class="px-3 py-1 text-xs font-semibold bg-yellow-100 text-yellow-800 rounded-full"><?php echo htmlspecialchars($schedule['section_name'] ?? 'N/A'); ?></span>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($schedule['faculty_name'] ?? 'TBD'); ?></div>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="text-sm text-gray-600 flex items-center gap-1">
                                                <i class="fas fa-door-open text-gray-400 text-xs"></i>
                                                <?php echo htmlspecialchars($schedule['room_name'] ?? 'TBD'); ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($schedule['day_of_week'] ?? 'TBD'); ?></div>
                                            <div class="text-xs text-gray-500 flex items-center gap-1 mt-1">
                                                <i class="far fa-clock text-xs"></i>
                                                <?php echo htmlspecialchars(($schedule['start_time'] ?? '') . ' - ' . ($schedule['end_time'] ?? '')); ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4">
                                            <?php
                                            $typeConfig = [
                                                'Lecture' => 'bg-blue-100 text-blue-700',
                                                'Laboratory' => 'bg-green-100 text-green-700',
                                                'Online' => 'bg-purple-100 text-purple-700'
                                            ];
                                            $typeClass = $typeConfig[$schedule['schedule_type'] ?? ''] ?? 'bg-gray-100 text-gray-700';
                                            ?>
                                            <span class="px-3 py-1 text-xs font-semibold <?php echo $typeClass; ?> rounded-full">
                                                <?php echo htmlspecialchars($schedule['schedule_type'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="py-12 text-center text-gray-400">
                                        <i class="fas fa-calendar-times text-4xl mb-3"></i>
                                        <p>No schedules found for the current semester.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right Sidebar -->
            <div class="space-y-6">

                <!-- Quick Actions -->
                <div class="glass-card rounded-2xl p-6 fade-in" style="animation-delay: 0.8s">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <i class="fas fa-bolt text-yellow-600"></i>
                        Quick Actions
                    </h3>
                    <div class="space-y-3">
                        <a href="/chair/faculty/" class="action-button w-full bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 text-white px-4 py-3 rounded-xl transition duration-200 flex items-center justify-center font-semibold shadow-lg shadow-yellow-500/30">
                            <i class="fas fa-user-plus mr-2"></i>
                            Add Faculty
                        </a>

                        <a href="/chair/schedule_management/" class="action-button w-full bg-white hover:bg-gray-50 text-gray-700 border-2 border-gray-200 px-4 py-3 rounded-xl transition duration-200 flex items-center justify-center font-semibold">
                            <i class="fas fa-calendar-plus mr-2"></i>
                            Create Schedule
                        </a>

                        <a href="/chair/curriculum/" class="action-button w-full bg-white hover:bg-gray-50 text-gray-700 border-2 border-gray-200 px-4 py-3 rounded-xl transition duration-200 flex items-center justify-center font-semibold">
                            <i class="fas fa-book-medical mr-2"></i>
                            Add Curriculum
                        </a>
                    </div>
                </div>

                <!-- Curriculum Overview -->
                <div class="glass-card rounded-2xl p-6 fade-in" style="animation-delay: 0.9s">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900">Curriculum</h3>
                        <a href="/chair/curriculum" class="text-sm text-yellow-600 hover:text-yellow-700 font-semibold flex items-center gap-1">
                            View All <i class="fas fa-arrow-right text-xs"></i>
                        </a>
                    </div>
                    <div class="space-y-3">
                        <?php if (isset($curricula) && !empty($curricula)): ?>
                            <?php foreach (array_slice($curricula, 0, 4) as $curriculum): ?>
                                <div class="p-4 bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl border border-gray-200 hover:shadow-md transition-all">
                                    <div class="flex items-start justify-between mb-2">
                                        <h4 class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($curriculum['curriculum_name'] ?? 'N/A'); ?></h4>
                                        <span class="px-2 py-1 text-xs font-semibold <?php echo ($curriculum['status'] ?? '') === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'; ?> rounded-full">
                                            <?php echo htmlspecialchars(ucfirst($curriculum['status'] ?? 'inactive')); ?>
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 mb-2"><?php echo htmlspecialchars($curriculum['program_name'] ?? 'N/A'); ?></p>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-semibold text-gray-700 flex items-center gap-1">
                                            <i class="fas fa-graduation-cap text-yellow-600 text-xs"></i>
                                            <?php echo htmlspecialchars($curriculum['total_units'] ?? 0); ?> Units
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-8 text-gray-400">
                                <i class="fas fa-inbox text-2xl mb-2"></i>
                                <p class="text-sm">No curricula found</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // Semester Selector
            const semesterSelector = document.getElementById('semesterSelector');
            const returnToCurrentBtn = document.getElementById('returnToCurrentBtn');

            if (semesterSelector) {
                semesterSelector.addEventListener('change', function() {
                    const semesterId = this.value;
                    this.disabled = true;
                    this.classList.add('opacity-50', 'cursor-wait');

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
                                showNotification('Switched to ' + data.semester_name + ' ' + data.academic_year, 'success');
                                setTimeout(() => window.location.reload(), 800);
                            } else {
                                showNotification(data.error || 'Failed to switch semester', 'error');
                                this.disabled = false;
                                this.classList.remove('opacity-50', 'cursor-wait');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showNotification('Network error. Please try again.', 'error');
                            this.disabled = false;
                            this.classList.remove('opacity-50', 'cursor-wait');
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
                                setTimeout(() => window.location.reload(), 500);
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

            // Department Switcher
            const deptSwitcherBtn = document.getElementById('deptSwitcherBtn');
            const deptSwitcherDropdown = document.getElementById('deptSwitcherDropdown');

            if (deptSwitcherBtn && deptSwitcherDropdown) {
                deptSwitcherBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    deptSwitcherDropdown.classList.toggle('hidden');
                });

                document.addEventListener('click', function(e) {
                    if (!deptSwitcherBtn.contains(e.target) && !deptSwitcherDropdown.contains(e.target)) {
                        deptSwitcherDropdown.classList.add('hidden');
                    }
                });

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
                                    setTimeout(() => window.location.reload(), 500);
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
                    type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500';

                notification.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-3 rounded-xl shadow-lg z-50 transform transition-all duration-300 flex items-center space-x-2 max-w-md`;

                const icon = type === 'success' ?
                    '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>' :
                    type === 'error' ?
                    '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>' :
                    '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';

                notification.innerHTML = `${icon}<span>${message}</span>`;
                document.body.appendChild(notification);

                setTimeout(() => {
                    notification.classList.add('animate-slide-in-right');
                }, 10);

                setTimeout(() => {
                    notification.classList.add('opacity-0', 'translate-x-full');
                    setTimeout(() => document.body.removeChild(notification), 300);
                }, 4000);
            }
        });
    </script>

    <style>
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

        #deptSwitcherDropdown {
            scrollbar-width: thin;
            scrollbar-color: #f59e0b #f1f1f1;
        }

        #deptSwitcherDropdown::-webkit-scrollbar {
            width: 6px;
        }

        #deptSwitcherDropdown::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        #deptSwitcherDropdown::-webkit-scrollbar-thumb {
            background: #f59e0b;
            border-radius: 3px;
        }

        #deptSwitcherDropdown::-webkit-scrollbar-thumb:hover {
            background: #d97706;
        }

        #semesterSelector {
            background-image: none;
        }

        #semesterSelector:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        #semesterSelector:focus {
            outline: none;
            ring: 2px solid #f59e0b;
        }

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

        tbody tr:hover {
            transform: translateX(2px);
            transition: all 0.2s ease-in-out;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .bg-gradient-to-r,
            .bg-gradient-to-br {
                background: white !important;
            }

            .shadow-md,
            .shadow-lg,
            .shadow-xl {
                box-shadow: none !important;
                border: 1px solid #e5e7eb !important;
            }
        }
    </style>

</body>

</html>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>