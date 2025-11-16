<?php
// Default values for safety
$teachingLoad = isset($teachingLoad) ? $teachingLoad : 0;
$pendingRequests = isset($pendingRequests) ? $pendingRequests : 0;
$recentSchedules = isset($recentSchedules) ? $recentSchedules : [];
$teachingHoursJson = isset($teachingHoursJson) ? $teachingHoursJson : json_encode([0, 0, 0, 0, 0, 0]);
$classCountJson = isset($classCountJson) ? $classCountJson : json_encode([0, 0, 0, 0, 0, 0]);
$totalWeeklyHours = isset($totalWeeklyHours) ? $totalWeeklyHours : 0;
$departmentName = isset($departmentName) ? $departmentName : 'Department';
$error = isset($error) ? $error : '';
$success = isset($success) ? $success : '';
$semesterInfo = isset($semesterInfo) ? $semesterInfo : '2nd Semester A.Y. 2024-2025';

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
            text-transform: uppercase;
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

        .table-row-hover {
            transition: all 0.2s ease;
        }

        .table-row-hover:hover {
            background: linear-gradient(90deg, rgba(251, 191, 36, 0.05) 0%, rgba(245, 158, 11, 0.05) 100%);
            transform: translateX(4px);
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
    <!-- Modern Header -->
    <div class="gradient-header text-white rounded-2xl mx-4 sm:mx-6 lg:mx-8 mt-6 mb-6 p-8 shadow-2xl relative">
        <div class="relative z-10">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                            <i class="fas fa-graduation-cap text-2xl"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl md:text-4xl font-bold tracking-tight">Faculty Dashboard</h1>
                            <p class="text-white/80 text-sm mt-1">PRMSU Scheduling System</p>
                        </div>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Welcome back, <?php echo htmlspecialchars($_SESSION['first_name']); ?>! 👋</h3>
                    <?php if (isset($departmentName) && !empty($departmentName)): ?>
                        <p class="text-white/90 flex items-center gap-2">
                            <i class="fas fa-building"></i>
                            <?php echo htmlspecialchars($departmentName); ?>
                        </p>
                    <?php endif; ?>
                </div>
                <div class="flex flex-wrap gap-3">
                    <span class="bg-gray-600 px-4 py-2 rounded-xl text-sm font-medium flex items-center gap-2 backdrop-blur-md">
                        <i class="fas fa-calendar-alt text-white"></i>
                        <?php echo htmlspecialchars($semesterInfo, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                    <span class="bg-yellow-500/90 px-4 py-2 rounded-xl text-sm font-semibold flex items-center gap-2 backdrop-blur-md">
                        <span class="status-dot bg-white"></span>
                        Active Term
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">

        <!-- Metrics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- Teaching Load -->
            <div class="glass-card rounded-2xl p-6 hover-scale cursor-pointer fade-in"
                onclick="window.location.href='/faculty/schedule'"
                style="animation-delay: 0.1s">
                <div class="flex items-start justify-between mb-4">
                    <div class="metric-icon">
                        <i class="fas fa-chalkboard-teacher text-white text-xl"></i>
                    </div>
                    <span class="text-xs font-semibold text-yellow-600 bg-yellow-100 px-3 py-1 rounded-full">ACTIVE</span>
                </div>
                <h3 class="text-gray-600 text-sm font-semibold uppercase tracking-wide mb-2">Teaching Load</h3>
                <p class="text-4xl font-bold text-gray-900 mb-1"><?php echo htmlspecialchars($teachingLoad); ?></p>
                <p class="text-sm text-gray-500 flex items-center gap-2">
                    <i class="fas fa-book-open"></i>
                    Total assigned schedules
                </p>
            </div>

            <!-- Weekly Hours -->
            <div class="glass-card rounded-2xl p-6 hover-scale fade-in" style="animation-delay: 0.2s">
                <div class="flex items-start justify-between mb-4">
                    <div class="metric-icon">
                        <i class="fas fa-clock text-white text-xl"></i>
                    </div>
                    <span class="text-xs font-semibold text-blue-600 bg-blue-100 px-3 py-1 rounded-full">WEEKLY</span>
                </div>
                <h3 class="text-gray-600 text-sm font-semibold uppercase tracking-wide mb-2">Teaching Hours</h3>
                <p class="text-4xl font-bold text-gray-900 mb-1"><?php echo number_format($totalWeeklyHours, 1); ?><span class="text-2xl text-gray-500">hrs</span></p>
                <p class="text-sm text-gray-500 flex items-center gap-2">
                    <i class="fas fa-hourglass-half"></i>
                    Total contact hours
                </p>
            </div>

            <!-- Status -->
            <div class="glass-card rounded-2xl p-6 hover-scale fade-in" style="animation-delay: 0.3s">
                <div class="flex items-start justify-between mb-4">
                    <div class="metric-icon">
                        <i class="fas fa-check-circle text-white text-xl"></i>
                    </div>
                    <span class="text-xs font-semibold text-emerald-600 bg-emerald-100 px-3 py-1 rounded-full badge-pulse">ONLINE</span>
                </div>
                <h3 class="text-gray-600 text-sm font-semibold uppercase tracking-wide mb-2">System Status</h3>
                <p class="text-4xl font-bold text-emerald-600 mb-1">Active</p>
                <p class="text-sm text-gray-500 flex items-center gap-2">
                    <i class="fas fa-server"></i>
                    All systems operational
                </p>
            </div>
        </div>

        <!-- Charts and Actions -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <!-- Teaching Hours Chart -->
            <div class="lg:col-span-2 glass-card rounded-2xl p-6 fade-in" style="animation-delay: 0.4s">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Weekly Teaching Hours</h3>
                        <p class="text-sm text-gray-500 flex items-center gap-2">
                            <i class="fas fa-chart-bar"></i>
                            Daily workload distribution
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span class="workload-badge workload-light">
                            <i class="fas fa-circle text-xs mr-1"></i> Light &lt;3h
                        </span>
                        <span class="workload-badge workload-moderate">
                            <i class="fas fa-circle text-xs mr-1"></i> Moderate 3-6h
                        </span>
                        <span class="workload-badge workload-heavy">
                            <i class="fas fa-circle text-xs mr-1"></i> Heavy &gt;6h
                        </span>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="teachingHoursChart" style="height: 280px;"></canvas>
                </div>
            </div>

            <!-- Quick Actions & Summary -->
            <div class="space-y-6">
                <!-- Quick Actions -->
                <div class="glass-card rounded-2xl p-6 fade-in" style="animation-delay: 0.5s">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <i class="fas fa-bolt text-yellow-600"></i>
                        Quick Actions
                    </h3>
                    <div class="space-y-3">
                        <a href="/faculty/schedule"
                            class="action-button w-full bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 text-white px-4 py-3 rounded-xl transition duration-200 flex items-center justify-center font-semibold shadow-lg shadow-yellow-500/30">
                            <i class="fas fa-calendar-alt mr-2"></i>
                            View Schedule
                        </a>

                        <a href="/faculty/profile"
                            class="action-button w-full bg-white hover:bg-gray-50 text-gray-700 border-2 border-gray-200 px-4 py-3 rounded-xl transition duration-200 flex items-center justify-center font-semibold">
                            <i class="fas fa-user-cog mr-2"></i>
                            Edit Profile
                        </a>

                        <a href="/faculty/reports"
                            class="action-button w-full bg-white hover:bg-gray-50 text-gray-700 border-2 border-gray-200 px-4 py-3 rounded-xl transition duration-200 flex items-center justify-center font-semibold">
                            <i class="fas fa-file-chart-line mr-2"></i>
                            View Reports
                        </a>
                    </div>
                </div>

                <!-- Weekly Summary -->
                <div class="glass-card rounded-2xl p-6 fade-in" style="animation-delay: 0.6s">
                    <h4 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <i class="fas fa-chart-pie text-yellow-600"></i>
                        Weekly Summary
                    </h4>
                    <div class="space-y-2">
                        <div class="summary-stat">
                            <span class="text-gray-600 font-medium">Total Hours</span>
                            <span class="font-bold text-gray-900"><?php echo number_format($totalWeeklyHours, 1); ?>h</span>
                        </div>
                        <div class="summary-stat">
                            <span class="text-gray-600 font-medium">Total Classes</span>
                            <span class="font-bold text-gray-900"><?php echo $teachingLoad; ?></span>
                        </div>
                        <div class="summary-stat">
                            <span class="text-gray-600 font-medium">Active Days</span>
                            <span class="font-bold text-gray-900">
                                <?php
                                $activeDays = 0;
                                $hoursArray = json_decode($teachingHoursJson, true);
                                foreach ($hoursArray as $hours) {
                                    if ($hours > 0) $activeDays++;
                                }
                                echo $activeDays;
                                ?>
                            </span>
                        </div>
                        <div class="summary-stat">
                            <span class="text-gray-600 font-medium">Avg per Day</span>
                            <span class="font-bold text-yellow-600">
                                <?php echo $activeDays > 0 ? number_format($totalWeeklyHours / $activeDays, 1) : '0'; ?>h
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Schedules -->
        <div class="glass-card rounded-2xl overflow-hidden fade-in" style="animation-delay: 0.7s">
            <div class="p-6 border-b border-gray-200">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 mb-1">Recent Schedules</h3>
                        <p class="text-sm text-gray-500">Your latest teaching assignments</p>
                    </div>
                    <a href="/faculty/schedule"
                        class="inline-flex items-center gap-2 text-yellow-600 hover:text-yellow-700 font-semibold text-sm transition">
                        View All Schedules
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <?php if (empty($recentSchedules)): ?>
                    <div class="px-6 py-16 text-center">
                        <div class="w-20 h-20 bg-gradient-to-br from-yellow-100 to-yellow-200 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-calendar-times text-yellow-600 text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">No Schedules Yet</h3>
                        <p class="text-gray-500">You don't have any teaching schedules assigned at the moment.</p>
                    </div>
                <?php else: ?>
                    <table class="min-w-full">
                        <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Course</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Section</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Room</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Schedule</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Type</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($recentSchedules as $schedule): ?>
                                <tr class="table-row-hover">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-book text-white text-sm"></i>
                                            </div>
                                            <div>
                                                <div class="text-sm font-bold text-gray-900">
                                                    <?php echo htmlspecialchars($schedule['course_code']); ?>
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    <?php echo htmlspecialchars($schedule['course_name']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2 text-sm font-medium text-gray-700">
                                            <i class="fas fa-users text-gray-400"></i>
                                            <?php echo htmlspecialchars($schedule['section_name']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2 text-sm font-medium text-gray-700">
                                            <i class="fas fa-door-open text-gray-400"></i>
                                            <?php echo htmlspecialchars($schedule['room_name'] ?? 'Online'); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-bold text-gray-900">
                                            <?php echo htmlspecialchars($schedule['day_of_week']); ?>
                                        </div>
                                        <div class="text-xs text-gray-500 flex items-center gap-1">
                                            <i class="fas fa-clock"></i>
                                            <?php echo htmlspecialchars(date('g:i A', strtotime($schedule['start_time'])) . ' - ' . date('g:i A', strtotime($schedule['end_time']))); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php
                                        $typeConfig = [
                                            'F2F' => ['bg-emerald-100 text-emerald-700 border-emerald-200', 'fas fa-users'],
                                            'Online' => ['bg-blue-100 text-blue-700 border-blue-200', 'fas fa-laptop'],
                                            'Hybrid' => ['bg-purple-100 text-purple-700 border-purple-200', 'fas fa-route'],
                                            'Asynchronous' => ['bg-amber-100 text-amber-700 border-amber-200', 'fas fa-clock']
                                        ];
                                        $config = $typeConfig[$schedule['schedule_type']] ?? ['bg-gray-100 text-gray-700 border-gray-200', 'fas fa-question'];
                                        ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold border <?php echo $config[0]; ?>">
                                            <i class="<?php echo $config[1]; ?> mr-1.5"></i>
                                            <?php echo htmlspecialchars($schedule['schedule_type']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('teachingHoursChart').getContext('2d');
            const teachingHours = <?php echo $teachingHoursJson; ?>;
            const classCount = <?php echo $classCountJson; ?>;
            const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

            const backgroundColors = teachingHours.map(hours => {
                if (hours === 0) return 'rgba(203, 213, 225, 0.8)';
                if (hours < 3) return 'rgba(16, 185, 129, 0.8)';
                if (hours < 6) return 'rgba(245, 158, 11, 0.8)';
                return 'rgba(239, 68, 68, 0.8)';
            });

            const borderColors = teachingHours.map(hours => {
                if (hours === 0) return 'rgba(148, 163, 184, 1)';
                if (hours < 3) return 'rgba(5, 150, 105, 1)';
                if (hours < 6) return 'rgba(217, 119, 6, 1)';
                return 'rgba(220, 38, 38, 1)';
            });

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: days,
                    datasets: [{
                        label: 'Teaching Hours',
                        data: teachingHours,
                        backgroundColor: backgroundColors,
                        borderColor: borderColors,
                        borderWidth: 2,
                        borderRadius: 12,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(31, 41, 55, 0.95)',
                            padding: 16,
                            borderColor: 'rgba(245, 158, 11, 0.5)',
                            borderWidth: 2,
                            cornerRadius: 12,
                            titleColor: '#fff',
                            bodyColor: '#e5e7eb',
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            callbacks: {
                                title: function(context) {
                                    return days[context[0].dataIndex];
                                },
                                label: function(context) {
                                    const hours = context.parsed.y;
                                    const classes = classCount[context.dataIndex];
                                    return [
                                        `📚 Teaching Hours: ${hours.toFixed(1)}h`,
                                        `👥 Classes: ${classes}`,
                                        `⏱️ Avg per class: ${classes > 0 ? (hours / classes).toFixed(1) : '0'}h`
                                    ];
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 2,
                                color: '#6b7280',
                                font: {
                                    size: 12,
                                    weight: '600'
                                },
                                callback: function(value) {
                                    return value + 'h';
                                }
                            },
                            grid: {
                                color: 'rgba(148, 163, 184, 0.1)',
                                drawBorder: false
                            },
                            border: {
                                display: false
                            }
                        },
                        x: {
                            ticks: {
                                color: '#4b5563',
                                font: {
                                    size: 12,
                                    weight: '600'
                                }
                            },
                            grid: {
                                display: false
                            },
                            border: {
                                display: false
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>

</html>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>