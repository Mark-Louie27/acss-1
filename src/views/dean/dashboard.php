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

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        .workload-bar {
            height: 8px;
            background: #e5e7eb;
            border-radius: 999px;
            overflow: hidden;
            position: relative;
        }

        .workload-fill {
            height: 100%;
            border-radius: 999px;
            transition: width 0.5s ease;
        }

        .stat-item {
            padding: 12px;
            border-radius: 12px;
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 1px solid #fbbf24;
        }

        .progress-ring {
            transform: rotate(-90deg);
        }

        .progress-ring-circle {
            transition: stroke-dashoffset 0.5s ease;
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
    </style>
</head>

<body class="min-h-screen">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        <!-- Modern Header -->
        <div class="gradient-header text-white rounded-2xl p-8 mb-6 shadow-xl relative">
            <div class="absolute top-0 right-0 w-64 h-64 bg-yellow-500/10 rounded-full blur-3xl"></div>
            <div class="relative z-10">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                                <i class="fas fa-university text-2xl"></i>
                            </div>
                            <div>
                                <h1 class="text-3xl md:text-4xl font-bold tracking-tight">College Dean Dashboard</h1>
                                <p class="text-white/80 text-sm mt-1">PRMSU Scheduling System</p>
                            </div>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Welcome back! 👋</h3>
                        <p class="text-white/90 flex items-center gap-2">
                            <i class="fas fa-building"></i>
                            <?php echo htmlspecialchars($college['college_name'] ?? 'College'); ?>
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <span class="bg-gray-600 px-4 py-2 rounded-xl text-sm font-medium flex items-center gap-2 backdrop-blur-md">
                            <i class="fas fa-calendar-alt text-white"></i>
                            <?php echo htmlspecialchars($currentSemester); ?>
                        </span>
                        <span class="bg-yellow-500/90 px-4 py-2 rounded-xl text-sm font-semibold flex items-center gap-2 backdrop-blur-md">
                            <span class="status-dot bg-white"></span>
                            Active Term
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metrics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Faculty Card -->
            <div class="glass-card rounded-2xl p-6 hover-scale fade-in" style="animation-delay: 0.1s">
                <div class="flex items-start justify-between mb-4">
                    <div class="metric-icon">
                        <i class="fas fa-chalkboard-teacher text-white text-xl"></i>
                    </div>
                    <span class="text-xs font-semibold text-yellow-600 bg-yellow-100 px-3 py-1 rounded-full">FACULTY</span>
                </div>
                <h3 class="text-gray-600 text-sm font-semibold uppercase tracking-wide mb-2">Total Faculty</h3>
                <p class="text-4xl font-bold text-gray-900 mb-1"><?php echo $stats['total_faculty']; ?></p>
                <p class="text-sm text-gray-500 flex items-center gap-2">
                    <i class="fas fa-users"></i>
                    Active members
                </p>
            </div>

            <!-- Classrooms Card -->
            <div class="glass-card rounded-2xl p-6 hover-scale fade-in" style="animation-delay: 0.2s">
                <div class="flex items-start justify-between mb-4">
                    <div class="metric-icon">
                        <i class="fas fa-door-open text-white text-xl"></i>
                    </div>
                    <span class="text-xs font-semibold text-blue-600 bg-blue-100 px-3 py-1 rounded-full">ROOMS</span>
                </div>
                <h3 class="text-gray-600 text-sm font-semibold uppercase tracking-wide mb-2">Classrooms</h3>
                <p class="text-4xl font-bold text-gray-900 mb-1"><?php echo $stats['total_classrooms']; ?></p>
                <p class="text-sm text-gray-500 flex items-center gap-2">
                    <i class="fas fa-map-marked-alt"></i>
                    Available spaces
                </p>
            </div>

            <!-- Departments Card -->
            <div class="glass-card rounded-2xl p-6 hover-scale fade-in" style="animation-delay: 0.3s">
                <div class="flex items-start justify-between mb-4">
                    <div class="metric-icon">
                        <i class="fas fa-building text-white text-xl"></i>
                    </div>
                    <span class="text-xs font-semibold text-purple-600 bg-purple-100 px-3 py-1 rounded-full">DEPTS</span>
                </div>
                <h3 class="text-gray-600 text-sm font-semibold uppercase tracking-wide mb-2">Departments</h3>
                <p class="text-4xl font-bold text-gray-900 mb-1"><?php echo $stats['total_departments']; ?></p>
                <p class="text-sm text-gray-500 flex items-center gap-2">
                    <i class="fas fa-sitemap"></i>
                    Under college
                </p>
            </div>

            <!-- Pending Card -->
            <div class="glass-card rounded-2xl p-6 hover-scale cursor-pointer fade-in" onclick="window.location.href='/dean/manage_schedules'" style="animation-delay: 0.4s">
                <div class="flex items-start justify-between mb-4">
                    <div class="metric-icon" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                        <i class="fas fa-clock text-white text-xl"></i>
                    </div>
                    <span class="text-xs font-semibold text-red-600 bg-red-100 px-3 py-1 rounded-full badge-pulse">URGENT</span>
                </div>
                <h3 class="text-gray-600 text-sm font-semibold uppercase tracking-wide mb-2">Pending Approvals</h3>
                <p class="text-4xl font-bold text-gray-900 mb-1"><?php echo $stats['pending_approvals']; ?></p>
                <p class="text-sm text-gray-500 flex items-center gap-2">
                    <i class="fas fa-exclamation-circle"></i>
                    Needs review
                </p>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Left Column - Stats Cards -->
            <div class="lg:col-span-2 space-y-8">

                <!-- Faculty Workload Overview Card -->
                <div class="glass-card rounded-2xl p-6 fade-in" style="animation-delay: 0.5s">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 mb-1">Faculty Workload Overview</h3>
                            <p class="text-sm text-gray-500">Teaching load distribution across faculty</p>
                        </div>
                        <div class="flex gap-2">
                            <span class="px-3 py-1 bg-green-100 text-green-700 rounded-lg text-xs font-semibold">
                                <i class="fas fa-circle text-[6px] mr-1"></i> Light
                            </span>
                            <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-lg text-xs font-semibold">
                                <i class="fas fa-circle text-[6px] mr-1"></i> Optimal
                            </span>
                            <span class="px-3 py-1 bg-red-100 text-red-700 rounded-lg text-xs font-semibold">
                                <i class="fas fa-circle text-[6px] mr-1"></i> Heavy
                            </span>
                        </div>
                    </div>

                    <?php if (!empty($facultyWorkload)): ?>
                        <div class="space-y-4">
                            <?php
                            $workloadStats = [
                                'light' => 0,
                                'optimal' => 0,
                                'heavy' => 0,
                                'total_hours' => 0
                            ];

                            foreach ($facultyWorkload as $faculty) {
                                $hours = $faculty['total_hours'] ?? 0;
                                $workloadStats['total_hours'] += $hours;
                                if ($hours < 12) $workloadStats['light']++;
                                elseif ($hours <= 24) $workloadStats['optimal']++;
                                else $workloadStats['heavy']++;
                            }
                            $avgHours = count($facultyWorkload) > 0 ? round($workloadStats['total_hours'] / count($facultyWorkload), 1) : 0;
                            ?>

                            <!-- Summary Stats -->
                            <div class="grid grid-cols-3 gap-4 mb-6">
                                <div class="stat-item text-center">
                                    <div class="text-3xl font-bold text-gray-900"><?php echo $workloadStats['light']; ?></div>
                                    <div class="text-xs text-gray-600 mt-1">Light Load (&lt;12h)</div>
                                </div>
                                <div class="stat-item text-center" style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-color: #3b82f6;">
                                    <div class="text-3xl font-bold text-gray-900"><?php echo $workloadStats['optimal']; ?></div>
                                    <div class="text-xs text-gray-600 mt-1">Optimal (12-24h)</div>
                                </div>
                                <div class="stat-item text-center" style="background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); border-color: #ef4444;">
                                    <div class="text-3xl font-bold text-gray-900"><?php echo $workloadStats['heavy']; ?></div>
                                    <div class="text-xs text-gray-600 mt-1">Heavy Load (&gt;24h)</div>
                                </div>
                            </div>

                            <!-- Faculty List -->
                            <div class="space-y-3">
                                <?php foreach (array_slice($facultyWorkload, 0, 8) as $faculty): ?>
                                    <?php
                                    $hours = round($faculty['total_hours'] ?? 0, 1);
                                    $maxHours = 40;
                                    $percentage = min(($hours / $maxHours) * 100, 100);

                                    if ($hours < 12) {
                                        $color = 'bg-green-500';
                                        $bgColor = 'bg-green-50';
                                        $borderColor = 'border-green-200';
                                        $textColor = 'text-green-700';
                                    } elseif ($hours <= 24) {
                                        $color = 'bg-blue-500';
                                        $bgColor = 'bg-blue-50';
                                        $borderColor = 'border-blue-200';
                                        $textColor = 'text-blue-700';
                                    } else {
                                        $color = 'bg-red-500';
                                        $bgColor = 'bg-red-50';
                                        $borderColor = 'border-red-200';
                                        $textColor = 'text-red-700';
                                    }
                                    ?>
                                    <div class="<?php echo $bgColor; ?> <?php echo $borderColor; ?> border rounded-xl p-4 hover:shadow-md transition-all">
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 bg-gradient-to-br from-gray-700 to-gray-800 rounded-lg flex items-center justify-center shadow-md">
                                                    <i class="fas fa-user-tie text-white text-sm"></i>
                                                </div>
                                                <div>
                                                    <p class="font-bold text-gray-900"><?php echo htmlspecialchars($faculty['faculty_name']); ?></p>
                                                    <p class="text-xs text-gray-700">
                                                        <i class="fas fa-building text-[8px] mr-1"></i>
                                                        <?php echo htmlspecialchars($faculty['department_name']); ?>
                                                      
                                                    </p>
                                                    <p class="text-xs text-gray-700">                                       
                                                        <?php echo htmlspecialchars($faculty['department_code']); ?>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-2xl font-bold <?php echo $textColor; ?>"><?php echo $hours; ?>h</div>
                                                <div class="text-xs text-gray-500"><?php echo $faculty['class_count']; ?> classes</div>
                                            </div>
                                        </div>
                                        <div class="workload-bar">
                                            <div class="workload-fill <?php echo $color; ?>" style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if (count($facultyWorkload) > 8): ?>
                                <a href="/dean/faculty" class="block text-center text-sm font-semibold text-yellow-600 hover:text-yellow-700 mt-4">
                                    View All Faculty (<?php echo count($facultyWorkload); ?>) →
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12 text-gray-400">
                            <i class="fas fa-user-clock text-5xl mb-3 opacity-50"></i>
                            <p class="font-semibold">No faculty workload data</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Classroom Utilization Card -->
                <div class="glass-card rounded-2xl p-6 fade-in" style="animation-delay: 0.6s">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 mb-1">Classroom Utilization</h3>
                            <p class="text-sm text-gray-500">Usage rate across all classrooms</p>
                        </div>
                        <i class="fas fa-door-open text-yellow-600 text-2xl"></i>
                    </div>

                    <?php if (!empty($classroomUtilization)): ?>
                        <?php
                        $avgUtilization = array_sum(array_column($classroomUtilization, 'utilization_rate')) / count($classroomUtilization);
                        $highUsage = count(array_filter($classroomUtilization, fn($r) => $r['utilization_rate'] >= 60));
                        $lowUsage = count(array_filter($classroomUtilization, fn($r) => $r['utilization_rate'] < 30));
                        ?>

                        <!-- Summary Grid -->
                        <div class="grid grid-cols-3 gap-4 mb-6">
                            <div class="text-center p-4 bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl border border-yellow-200">
                                <div class="text-3xl font-bold text-yellow-700"><?php echo round($avgUtilization, 1); ?>%</div>
                                <div class="text-xs text-gray-600 mt-1">Avg Utilization</div>
                            </div>
                            <div class="text-center p-4 bg-gradient-to-br from-red-50 to-red-100 rounded-xl border border-red-200">
                                <div class="text-3xl font-bold text-red-700"><?php echo $highUsage; ?></div>
                                <div class="text-xs text-gray-600 mt-1">High Usage (&gt;60%)</div>
                            </div>
                            <div class="text-center p-4 bg-gradient-to-br from-green-50 to-green-100 rounded-xl border border-green-200">
                                <div class="text-3xl font-bold text-green-700"><?php echo $lowUsage; ?></div>
                                <div class="text-xs text-gray-600 mt-1">Low Usage (&lt;30%)</div>
                            </div>
                        </div>

                        <!-- Room List -->
                        <div class="space-y-3">
                            <?php foreach (array_slice($classroomUtilization, 0, 6) as $room): ?>
                                <?php
                                $rate = $room['utilization_rate'];
                                if ($rate < 30) {
                                    $color = 'bg-green-500';
                                    $bgColor = 'bg-green-50';
                                    $textColor = 'text-green-700';
                                    $label = 'Available';
                                } elseif ($rate < 60) {
                                    $color = 'bg-yellow-500';
                                    $bgColor = 'bg-yellow-50';
                                    $textColor = 'text-yellow-700';
                                    $label = 'Moderate';
                                } else {
                                    $color = 'bg-red-500';
                                    $bgColor = 'bg-red-50';
                                    $textColor = 'text-red-700';
                                    $label = 'High Usage';
                                }
                                ?>
                                <div class="<?php echo $bgColor; ?> rounded-xl p-4 border border-gray-200">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-gradient-to-br from-gray-700 to-gray-800 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-door-open text-white text-sm"></i>
                                            </div>
                                            <div>
                                                <p class="font-bold text-gray-900"><?php echo htmlspecialchars($room['room_name']); ?></p>
                                                <p class="text-xs text-gray-600">
                                                    <?php echo htmlspecialchars($room['room_type']); ?> • Cap: <?php echo $room['capacity']; ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-2xl font-bold <?php echo $textColor; ?>"><?php echo round($rate, 1); ?>%</div>
                                            <div class="text-xs <?php echo $textColor; ?> font-semibold"><?php echo $label; ?></div>
                                        </div>
                                    </div>
                                    <div class="workload-bar">
                                        <div class="workload-fill <?php echo $color; ?>" style="width: <?php echo $rate; ?>%"></div>
                                    </div>
                                    <div class="text-xs text-gray-600 mt-2">
                                        <?php echo $room['scheduled_classes']; ?> classes • <?php echo $room['time_slots_used']; ?> time slots
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12 text-gray-400">
                            <i class="fas fa-door-open text-5xl mb-3 opacity-50"></i>
                            <p class="font-semibold">No classroom data</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Department Overview -->
                <div class="glass-card rounded-2xl p-6 fade-in" style="animation-delay: 0.7s">
                    <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <i class="fas fa-building text-yellow-600"></i>
                        Department Overview
                    </h3>
                    <div class="space-y-3">
                        <?php if (!empty($departmentOverview)): ?>
                            <?php foreach ($departmentOverview as $dept): ?>
                                <div class="flex items-center justify-between p-4 bg-gradient-to-r from-yellow-50 to-amber-50 rounded-xl border border-yellow-100 hover:shadow-md transition-all">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl flex items-center justify-center shadow-lg">
                                            <i class="fas fa-building text-white"></i>
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-900"><?php echo htmlspecialchars($dept['department_name']); ?></p>
                                            <p class="font-bold text-gray-700"><?php echo htmlspecialchars($dept['department_code']); ?></p>
                                            <p class="text-sm text-gray-600">
                                                <i class="fas fa-users text-xs mr-1"></i><?php echo $dept['faculty_count']; ?> faculty •
                                                <i class="fas fa-calendar text-xs mr-1"></i><?php echo $dept['active_schedules']; ?> schedules
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-2xl font-bold text-gray-900"><?php echo $dept['course_count']; ?></p>
                                        <p class="text-xs text-gray-500">courses</p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-12 text-gray-400">
                                <i class="fas fa-inbox text-4xl mb-2"></i>
                                <p>No departments found</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar -->
            <div class="space-y-8">

                <!-- Approval Status -->
                <?php if (!empty($scheduleApprovalStatus)): ?>
                    <div class="glass-card rounded-2xl p-6 fade-in" style="animation-delay: 0.5s">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-bold text-gray-900">Approval Status</h3>
                            <i class="fas fa-tasks text-yellow-600"></i>
                        </div>
                        <div class="space-y-4">
                            <?php foreach ($scheduleApprovalStatus as $status): ?>
                                <?php
                                $statusConfig = [
                                    'pending' => ['bg-yellow-500', 'text-white', 'fas fa-clock', 'Pending Review'],
                                    'approved' => ['bg-green-500', 'text-white', 'fas fa-check-circle', 'Approved'],
                                    'rejected' => ['bg-red-500', 'text-white', 'fas fa-times-circle', 'Rejected']
                                ];
                                $config = $statusConfig[$status['status']] ?? ['bg-gray-500', 'text-white', 'fas fa-question', ucfirst($status['status'])];
                                ?>
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-200 hover-scale">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 <?php echo $config[0]; ?> rounded-xl flex items-center justify-center shadow-lg">
                                            <i class="<?php echo $config[2]; ?> <?php echo $config[1]; ?>"></i>
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-900"><?php echo $config[3]; ?></p>
                                            <p class="text-xs text-gray-500">Last 30 days</p>
                                        </div>
                                    </div>
                                    <span class="text-2xl font-bold text-gray-900"><?php echo $status['count']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Department Performance Card -->
                <?php if (!empty($departmentPerformance)): ?>
                    <div class="glass-card rounded-2xl p-6 fade-in" style="animation-delay: 0.6s">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-bold text-gray-900">Department Performance</h3>
                            <i class="fas fa-chart-line text-yellow-600"></i>
                        </div>
                        <div class="space-y-4">
                            <?php foreach ($departmentPerformance as $dept): ?>
                                <?php
                                $loadPerFaculty = $dept['avg_load_per_faculty'] ?? 0;
                                $efficiency = $loadPerFaculty >= 3 ? 'High' : ($loadPerFaculty >= 2 ? 'Good' : 'Low');
                                $efficiencyColor = $loadPerFaculty >= 3 ? 'text-green-600' : ($loadPerFaculty >= 2 ? 'text-blue-600' : 'text-yellow-600');
                                ?>
                                <div class="p-4 bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl border border-gray-200">
                                    <div class="flex items-center justify-between mb-3">
                                        <p class="font-bold text-gray-900 text-sm"><?php echo htmlspecialchars($dept['department_name']); ?></p>
                                        <p class="font-bold text-gray-900 text-sm"><?php echo htmlspecialchars($dept['department_code']); ?></p>
                                        <span class="px-2 py-1 <?php echo $efficiencyColor; ?> bg-white rounded-lg text-xs font-semibold border">
                                            <?php echo $efficiency; ?>
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2 text-center">
                                        <div>
                                            <div class="text-xl font-bold text-gray-900"><?php echo $dept['faculty_count']; ?></div>
                                            <div class="text-[10px] text-gray-500">Faculty</div>
                                        </div>
                                        <div>
                                            <div class="text-xl font-bold text-gray-900"><?php echo $dept['schedule_count']; ?></div>
                                            <div class="text-[10px] text-gray-500">Schedules</div>
                                        </div>
                                        <div>
                                            <div class="text-xl font-bold text-gray-900"><?php echo number_format($loadPerFaculty, 1); ?></div>
                                            <div class="text-[10px] text-gray-500">Avg Load</div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Quick Actions -->
                <div class="glass-card rounded-2xl p-6 fade-in" style="animation-delay: 0.7s">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <i class="fas fa-bolt text-yellow-600"></i>
                        Quick Actions
                    </h3>
                    <div class="space-y-3">
                        <a href="/dean/manage_schedules" class="action-button w-full bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 text-white px-4 py-3 rounded-xl transition duration-200 flex items-center justify-center font-semibold shadow-lg shadow-yellow-500/30">
                            <i class="fas fa-check-circle mr-2"></i>
                            Review Approvals
                        </a>

                        <a href="/dean/schedule" class="action-button w-full bg-white hover:bg-gray-50 text-gray-700 border-2 border-gray-200 px-4 py-3 rounded-xl transition duration-200 flex items-center justify-center font-semibold">
                            <i class="fas fa-calendar-alt mr-2"></i>
                            View Schedule
                        </a>

                        <a href="/dean/faculty" class="action-button w-full bg-white hover:bg-gray-50 text-gray-700 border-2 border-gray-200 px-4 py-3 rounded-xl transition duration-200 flex items-center justify-center font-semibold">
                            <i class="fas fa-users mr-2"></i>
                            Manage Faculty
                        </a>

                        <a href="/dean/classrooms" class="action-button w-full bg-white hover:bg-gray-50 text-gray-700 border-2 border-gray-200 px-4 py-3 rounded-xl transition duration-200 flex items-center justify-center font-semibold">
                            <i class="fas fa-door-open mr-2"></i>
                            Manage Rooms
                        </a>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="glass-card rounded-2xl p-6 fade-in" style="animation-delay: 0.8s">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900">Recent Activity</h3>
                        <i class="fas fa-bell text-yellow-600"></i>
                    </div>
                    <div class="space-y-3" id="activityFeed">
                        <?php if (!empty($activities)): ?>
                            <?php
                            function getActivityIcon($source)
                            {
                                $icons = [
                                    'schedule_update' => 'fa-calendar-edit',
                                    'schedule_request' => 'fa-clipboard-check',
                                    'faculty_assignment' => 'fa-user-plus',
                                    'course_addition' => 'fa-book-plus',
                                    'activity_log' => 'fa-history'
                                ];
                                return $icons[$source] ?? 'fa-bolt';
                            }

                            function getActivityColor($source)
                            {
                                $colors = [
                                    'schedule_update' => 'from-blue-500 to-blue-600',
                                    'schedule_request' => 'from-green-500 to-green-600',
                                    'faculty_assignment' => 'from-purple-500 to-purple-600',
                                    'course_addition' => 'from-yellow-500 to-yellow-600',
                                    'activity_log' => 'from-gray-500 to-gray-600'
                                ];
                                return $colors[$source] ?? 'from-orange-500 to-orange-600';
                            }
                            ?>
                            <?php foreach (array_slice($activities, 0, 6) as $activity): ?>
                                <?php
                                $source = $activity['source'] ?? 'activity_log';
                                $icon = getActivityIcon($source);
                                $color = getActivityColor($source);
                                ?>
                                <div class="p-3 bg-gray-50 rounded-xl flex items-start gap-3 hover:shadow-md transition-all border border-gray-100">
                                    <div class="w-10 h-10 bg-gradient-to-br <?php echo $color; ?> rounded-lg flex items-center justify-center flex-shrink-0 shadow-md">
                                        <i class="fas <?php echo $icon; ?> text-white text-sm"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs text-gray-900 font-semibold line-clamp-2">
                                            <?php echo htmlspecialchars($activity['description'] ?? 'Activity'); ?>
                                        </p>
                                        <div class="flex items-center gap-2 mt-1">
                                            <?php if (!empty($activity['department_name'])): ?>
                                                <span class="text-[10px] text-purple-600 font-medium">
                                                    <i class="fas fa-building text-[8px] mr-1"></i>
                                                    <?php echo htmlspecialchars(substr($activity['department_name'], 0, 15)); ?>
                                                </span>
                                            <?php endif; ?>
                                            <span class="text-[10px] text-gray-500">
                                                <i class="fas fa-clock text-[8px]"></i>
                                                <?php
                                                $timestamp = strtotime($activity['created_at'] ?? 'now');
                                                $now = time();
                                                $diff = $now - $timestamp;

                                                if ($diff < 3600) {
                                                    echo floor($diff / 60) . 'm';
                                                } elseif ($diff < 86400) {
                                                    echo floor($diff / 3600) . 'h';
                                                } else {
                                                    echo floor($diff / 86400) . 'd';
                                                }
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <a href="/dean/activities" class="block text-center text-sm font-semibold text-yellow-600 hover:text-yellow-700 mt-4 transition-colors">
                                View All Activity →
                            </a>
                        <?php else: ?>
                            <div class="text-center py-8 text-gray-400">
                                <i class="fas fa-inbox text-4xl mb-2 opacity-50"></i>
                                <p class="text-sm font-semibold">No recent activity</p>
                                <p class="text-xs mt-1">Activities will appear here</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Schedule Changes -->
                <?php if (!empty($recentScheduleChanges)): ?>
                    <div class="glass-card rounded-2xl p-6 fade-in" style="animation-delay: 0.9s">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-gray-900">Schedule Changes</h3>
                            <i class="fas fa-history text-yellow-600"></i>
                        </div>
                        <div class="space-y-3">
                            <?php foreach ($recentScheduleChanges as $change): ?>
                                <div class="p-3 bg-blue-50 rounded-xl border border-blue-100 hover:shadow-md transition-all">
                                    <div class="flex items-start gap-3">
                                        <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <i class="fas fa-calendar-day text-white text-xs"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-bold text-gray-900 text-sm">
                                                <?php echo htmlspecialchars($change['course_code']); ?>
                                            </p>
                                            <p class="text-xs text-gray-600 line-clamp-1">
                                                <?php echo htmlspecialchars($change['faculty_name']); ?>
                                            </p>
                                            <div class="flex items-center gap-2 mt-1 text-[10px] text-gray-500">
                                                <span><i class="fas fa-door-open mr-1"></i><?php echo htmlspecialchars($change['room_name'] ?? 'TBA'); ?></span>
                                                <span><i class="fas fa-clock mr-1"></i><?php echo date('g:i A', strtotime($change['start_time'])); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Faculty Distribution -->
                <?php if (!empty($facultyDistribution)): ?>
                    <div class="glass-card rounded-2xl p-6 fade-in" style="animation-delay: 1.0s">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-gray-900">Faculty Distribution</h3>
                            <i class="fas fa-user-tie text-yellow-600"></i>
                        </div>
                        <div class="space-y-3">
                            <?php
                            $totalFaculty = array_sum(array_column($facultyDistribution, 'count'));
                            foreach ($facultyDistribution as $type):
                                $percentage = $totalFaculty > 0 ? round(($type['count'] / $totalFaculty) * 100, 1) : 0;
                                $colorClass = match (strtolower($type['employment_type'])) {
                                    'full-time', 'full time', 'permanent' => 'from-green-500 to-green-600',
                                    'part-time', 'part time' => 'from-blue-500 to-blue-600',
                                    'contractual', 'contract' => 'from-yellow-500 to-yellow-600',
                                    default => 'from-gray-500 to-gray-600'
                                };
                            ?>
                                <div class="p-4 bg-gray-50 rounded-xl border border-gray-200">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-gradient-to-br <?php echo $colorClass; ?> rounded-lg flex items-center justify-center shadow-md">
                                                <i class="fas fa-user text-white text-sm"></i>
                                            </div>
                                            <div>
                                                <p class="font-bold text-gray-900"><?php echo htmlspecialchars($type['employment_type']); ?></p>
                                                <p class="text-xs text-gray-500"><?php echo $percentage; ?>% of total</p>
                                            </div>
                                        </div>
                                        <span class="text-2xl font-bold text-gray-900"><?php echo $type['count']; ?></span>
                                    </div>
                                    <div class="workload-bar">
                                        <div class="workload-fill bg-gradient-to-r <?php echo $colorClass; ?>" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Add smooth animations on scroll
        document.addEventListener('DOMContentLoaded', function() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, {
                threshold: 0.1
            });

            document.querySelectorAll('.fade-in').forEach((el) => {
                observer.observe(el);
            });

            // Animate workload bars on load
            setTimeout(() => {
                document.querySelectorAll('.workload-fill').forEach(bar => {
                    const width = bar.style.width;
                    bar.style.width = '0';
                    setTimeout(() => {
                        bar.style.width = width;
                    }, 100);
                });
            }, 300);
        });
    </script>

</body>

</html>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>