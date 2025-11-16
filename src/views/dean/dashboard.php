<?php
ob_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
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

        .chart-container {
            position: relative;
            padding: 24px;
            border-radius: 20px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(248, 250, 252, 0.9) 100%);
        }

        .workload-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .workload-light {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .workload-moderate {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }

        .workload-heavy {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
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

        .summary-stat {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(245, 158, 11, 0.1);
        }

        .summary-stat:last-child {
            border-bottom: none;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
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

            <!-- Left Column - Charts -->
            <div class="lg:col-span-2 space-y-8">

                <!-- Faculty Workload Chart -->
                <div class="glass-card rounded-2xl p-6 fade-in" style="animation-delay: 0.5s">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 mb-1">Faculty Workload Distribution</h3>
                            <p class="text-sm text-gray-500">Teaching hours per faculty member</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span class="workload-badge workload-light text-xs">
                                <i class="fas fa-circle mr-1"></i> &lt;12h
                            </span>
                            <span class="workload-badge workload-moderate text-xs">
                                <i class="fas fa-circle mr-1"></i> 12-24h
                            </span>
                            <span class="workload-badge workload-heavy text-xs">
                                <i class="fas fa-circle mr-1"></i> &gt;24h
                            </span>
                        </div>
                    </div>
                    <div class="chart-container">
                        <?php if (!empty($facultyWorkload)): ?>
                            <canvas id="facultyWorkloadChart" style="height: 320px;"></canvas>
                        <?php else: ?>
                            <div class="h-64 flex items-center justify-center text-gray-400">
                                <div class="text-center">
                                    <i class="fas fa-user-clock text-4xl mb-2"></i>
                                    <p>No faculty workload data available</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Classroom Utilization Chart -->
                <div class="glass-card rounded-2xl p-6 fade-in" style="animation-delay: 0.6s">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 mb-1">Classroom Utilization Rate</h3>
                            <p class="text-sm text-gray-500">Percentage of classroom usage</p>
                        </div>
                        <i class="fas fa-door-open text-yellow-600 text-xl"></i>
                    </div>
                    <div class="chart-container">
                        <?php if (!empty($classroomUtilization)): ?>
                            <canvas id="classroomUtilizationChart" style="height: 280px;"></canvas>
                        <?php else: ?>
                            <div class="h-64 flex items-center justify-center text-gray-400">
                                <div class="text-center">
                                    <i class="fas fa-door-open text-4xl mb-2"></i>
                                    <p>No classroom data available</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
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

                <!-- Quick Actions -->
                <div class="glass-card rounded-2xl p-6 fade-in" style="animation-delay: 0.6s">
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
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="glass-card rounded-3xl p-6 animate-slide-in" style="animation-delay: 1.1s">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-gray-900">Recent Activity</h3>
                        <i class="fas fa-bell text-orange-600 text-xl"></i>
                    </div>
                    <div class="space-y-3" id="activityFeed">
                        <?php if (!empty($activities)): ?>
                            <?php
                            // Helper function to get activity icon
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

                            // Helper function to get activity color
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
                            <?php foreach (array_slice($activities, 0, 8) as $index => $activity): ?>
                                <?php
                                $source = $activity['source'] ?? 'activity_log';
                                $icon = getActivityIcon($source);
                                $color = getActivityColor($source);
                                ?>
                                <div class="dept-card p-4 rounded-xl flex items-start gap-3 hover:shadow-md transition-all">
                                    <div class="w-10 h-10 bg-gradient-to-br <?php echo $color; ?> rounded-xl flex items-center justify-center flex-shrink-0 shadow-md">
                                        <i class="fas <?php echo $icon; ?> text-white text-sm"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-gray-900 font-semibold line-clamp-2">
                                            <?php echo htmlspecialchars($activity['description'] ?? 'Activity'); ?>
                                        </p>
                                        <div class="flex items-center gap-3 mt-1">
                                            <?php if (!empty($activity['department_name'])): ?>
                                                <span class="text-xs text-purple-600 font-medium">
                                                    <i class="fas fa-building text-xs mr-1"></i>
                                                    <?php echo htmlspecialchars($activity['department_name']); ?>
                                                </span>
                                            <?php endif; ?>
                                            <span class="text-xs text-gray-500 flex items-center gap-1">
                                                <i class="fas fa-clock text-xs"></i>
                                                <?php
                                                $timestamp = strtotime($activity['created_at'] ?? 'now');
                                                $now = time();
                                                $diff = $now - $timestamp;

                                                if ($diff < 3600) {
                                                    echo floor($diff / 60) . ' min ago';
                                                } elseif ($diff < 86400) {
                                                    echo floor($diff / 3600) . ' hrs ago';
                                                } else {
                                                    echo date('M d, h:i A', $timestamp);
                                                }
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <a href="/dean/activities" class="block text-center text-sm font-semibold text-purple-600 hover:text-purple-700 mt-4 transition-colors">
                                View All Activity →
                            </a>
                        <?php else: ?>
                            <div class="text-center py-8 text-gray-400">
                                <i class="fas fa-inbox text-4xl mb-2 opacity-50"></i>
                                <p class="text-sm font-semibold">No recent activity</p>
                                <p class="text-xs mt-1">Activities will appear here as they occur</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // Faculty Workload Chart
            const workloadCtx = document.getElementById('facultyWorkloadChart');
            if (workloadCtx) {
                <?php if (!empty($facultyWorkload)): ?>
                    const facultyNames = <?php echo json_encode(array_map(function ($f) {
                                                return strlen($f['faculty_name']) > 20 ? substr($f['faculty_name'], 0, 20) . '...' : $f['faculty_name'];
                                            }, $facultyWorkload)); ?>;
                    const teachingHours = <?php echo json_encode(array_map(function ($f) {
                                                return round($f['total_hours'] ?? 0, 1);
                                            }, $facultyWorkload)); ?>;

                    const workloadColors = teachingHours.map(hours => {
                        if (hours < 12) return '#10b981';
                        if (hours <= 24) return '#3b82f6';
                        return '#ef4444';
                    });

                    new Chart(workloadCtx, {
                        type: 'bar',
                        data: {
                            labels: facultyNames,
                            datasets: [{
                                label: 'Teaching Hours',
                                data: teachingHours,
                                backgroundColor: workloadColors,
                                borderRadius: 8,
                                barThickness: 24
                            }]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    backgroundColor: '#1f2937',
                                    padding: 12,
                                    titleColor: '#f3f4f6',
                                    bodyColor: '#d1d5db',
                                    borderColor: '#f59e0b',
                                    borderWidth: 1,
                                    cornerRadius: 8,
                                    callbacks: {
                                        label: function(ctx) {
                                            const hours = ctx.parsed.x;
                                            let status = hours < 12 ? 'Light Load' : hours <= 24 ? 'Optimal Load' : 'Heavy Load';
                                            return [`${hours}h per week`, status];
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    beginAtZero: true,
                                    grid: {
                                        color: '#f3f4f6',
                                        drawBorder: false
                                    },
                                    ticks: {
                                        callback: v => v + 'h',
                                        color: '#6b7280'
                                    }
                                },
                                y: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        color: '#374151',
                                        font: {
                                            size: 11
                                        }
                                    }
                                }
                            }
                        }
                    });
                <?php endif; ?>
            }

            // Classroom Utilization Chart
            const classroomCtx = document.getElementById('classroomUtilizationChart');
            if (classroomCtx) {
                <?php if (!empty($classroomUtilization)): ?>
                    const roomNames = <?php echo json_encode(array_map(function ($r) {
                                            return strlen($r['room_name']) > 15 ? substr($r['room_name'], 0, 15) . '...' : $r['room_name'];
                                        }, $classroomUtilization)); ?>;
                    const utilRates = <?php echo json_encode(array_column($classroomUtilization, 'utilization_rate')); ?>;

                    const utilColors = utilRates.map(rate => {
                        if (rate < 30) return '#10b981';
                        if (rate < 60) return '#f59e0b';
                        return '#ef4444';
                    });

                    new Chart(classroomCtx, {
                        type: 'bar',
                        data: {
                            labels: roomNames,
                            datasets: [{
                                label: 'Utilization %',
                                data: utilRates,
                                backgroundColor: utilColors,
                                borderRadius: 8,
                                barThickness: 22
                            }]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    backgroundColor: '#1f2937',
                                    padding: 12,
                                    borderColor: '#f59e0b',
                                    borderWidth: 1,
                                    cornerRadius: 8,
                                    callbacks: {
                                        label: ctx => `${ctx.parsed.x}% utilized`
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    beginAtZero: true,
                                    max: 100,
                                    grid: {
                                        color: '#f3f4f6',
                                        drawBorder: false
                                    },
                                    ticks: {
                                        callback: v => v + '%',
                                        color: '#6b7280'
                                    }
                                },
                                y: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        color: '#374151',
                                        font: {
                                            size: 11
                                        }
                                    }
                                }
                            }
                        }
                    });
                <?php endif; ?>
            }
        });
    </script>

</body>

</html>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>