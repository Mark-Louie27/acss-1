<?php
ob_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($data['title']); ?></title>
    <link rel="stylesheet" href="/css/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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

        .deadline-alert {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-left: 4px solid #f59e0b;
            border-radius: 12px;
        }
    </style>
</head>

<body class="min-h-screen">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        <!-- Modern Header -->
        <div class="gradient-header text-white rounded-2xl p-8 mb-6 shadow-xl relative fade-in">
            <div class="absolute top-0 right-0 w-64 h-64 bg-yellow-500/10 rounded-full blur-3xl"></div>
            <div class="relative z-10">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                                <i class="fas fa-user-shield text-2xl"></i>
                            </div>
                            <div>
                                <h1 class="text-3xl md:text-4xl font-bold tracking-tight">Director Dashboard</h1>
                                <p class="text-white/80 text-sm mt-1">PRMSU Scheduling System</p>
                            </div>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Welcome back! 👋</h3>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <span class="bg-gray-600 px-4 py-2 rounded-xl text-sm font-medium flex items-center gap-2 backdrop-blur-md">
                            <i class="fas fa-calendar-alt text-white"></i>
                            <?php
                            if (!empty($data['semester'])) {
                                $sem = htmlspecialchars($data['semester']['semester_name'] ?? 'Unknown');
                                $ay  = htmlspecialchars($data['semester']['academic_year'] ?? 'Unknown');
                                echo "{$sem} | A.Y: {$ay}";
                            } else {
                                echo 'Semester: Not Set';
                            }
                            ?>
                        </span>
                        <span class="bg-yellow-500/90 px-4 py-2 rounded-xl text-sm font-semibold flex items-center gap-2 backdrop-blur-md">
                            <span class="status-dot bg-white"></span>
                            Active Term
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Deadline Alert (if set) -->
        <?php if ($data['deadline']): ?>
            <div class="deadline-alert p-6 mb-6 fade-in" style="animation-delay: 0.1s">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-yellow-500 rounded-xl flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-yellow-900 text-lg mb-1">Schedule Deadline Active</h3>
                            <p class="text-yellow-800 font-medium">
                                <i class="far fa-clock mr-1"></i>
                                Deadline: <?php echo htmlspecialchars(date('F j, Y \a\t g:i A', strtotime($data['deadline']))); ?>
                            </p>
                        </div>
                    </div>
                    <a href="/director/schedule_deadline" class="action-button bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-xl font-semibold transition shadow-lg shadow-yellow-600/30">
                        Update Deadline
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Metrics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- Pending Approvals -->
            <div class="glass-card rounded-2xl p-6 hover-scale cursor-pointer fade-in" onclick="window.location.href='/director/pending-approvals'" style="animation-delay: 0.1s">
                <div class="flex items-start justify-between mb-4">
                    <div class="metric-icon">
                        <i class="fas fa-clock text-white text-xl"></i>
                    </div>
                    <span class="text-xs font-semibold <?php echo ($data['pending_approvals'] > 0) ? 'text-orange-600 bg-orange-100 badge-pulse' : 'text-green-600 bg-green-100'; ?> px-3 py-1 rounded-full">
                        <?php echo ($data['pending_approvals'] > 0) ? 'ACTION NEEDED' : 'UP TO DATE'; ?>
                    </span>
                </div>
                <h3 class="text-gray-600 text-sm font-semibold uppercase tracking-wide mb-2">Pending Approvals</h3>
                <p class="text-4xl font-bold text-gray-900 mb-1"><?php echo htmlspecialchars($data['pending_approvals'] ?? '0'); ?></p>
                <p class="text-sm text-gray-500 flex items-center gap-2">
                    <i class="fas fa-clipboard-check"></i>
                    Schedule reviews awaiting
                </p>
            </div>

            <!-- Schedule Deadline -->
            <div class="glass-card rounded-2xl p-6 hover-scale cursor-pointer fade-in" onclick="window.location.href='/director/schedule_deadline'" style="animation-delay: 0.2s">
                <div class="flex items-start justify-between mb-4">
                    <div class="metric-icon" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); box-shadow: 0 8px 16px rgba(239, 68, 68, 0.3);">
                        <i class="fas fa-calendar-times text-white text-xl"></i>
                    </div>
                    <span class="text-xs font-semibold <?php echo ($data['deadline']) ? 'text-green-600 bg-green-100' : 'text-red-600 bg-red-100'; ?> px-3 py-1 rounded-full">
                        <?php echo ($data['deadline']) ? 'SET' : 'NOT SET'; ?>
                    </span>
                </div>
                <h3 class="text-gray-600 text-sm font-semibold uppercase tracking-wide mb-2">Schedule Deadline</h3>
                <p class="text-2xl font-bold text-gray-900 mb-1">
                    <?php echo $data['deadline'] ? htmlspecialchars(date('M d, Y', strtotime($data['deadline']))) : 'Not Set'; ?>
                </p>
                <p class="text-sm text-gray-500 flex items-center gap-2">
                    <i class="fas fa-bell"></i>
                    Submission deadline
                </p>
            </div>

            <!-- Total Schedules -->
            <div class="glass-card rounded-2xl p-6 hover-scale fade-in" style="animation-delay: 0.3s">
                <div class="flex items-start justify-between mb-4">
                    <div class="metric-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 8px 16px rgba(16, 185, 129, 0.3);">
                        <i class="fas fa-calendar-check text-white text-xl"></i>
                    </div>
                    <span class="text-xs font-semibold text-blue-600 bg-blue-100 px-3 py-1 rounded-full">ALL COLLEGES</span>
                </div>
                <h3 class="text-gray-600 text-sm font-semibold uppercase tracking-wide mb-2">Total Schedules</h3>
                <p class="text-4xl font-bold text-gray-900 mb-1"><?php echo htmlspecialchars($data['schedule_stats']['total_schedules'] ?? '0'); ?></p>
                <p class="text-sm text-gray-500 flex items-center gap-2">
                    <i class="fas fa-building"></i>
                    Across all departments
                </p>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Charts Section -->
            <div class="lg:col-span-2 space-y-8">

                <!-- Schedule Distribution Chart -->
                <div class="glass-card rounded-2xl p-6 fade-in" style="animation-delay: 0.4s">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 mb-1">Schedule Distribution</h3>
                            <p class="text-sm text-gray-500">Distribution by day of week</p>
                        </div>
                        <i class="fas fa-chart-pie text-yellow-600 text-xl"></i>
                    </div>
                    <div class="chart-container">
                        <canvas id="scheduleDistributionChart" style="height: 280px;"></canvas>
                    </div>
                </div>

                <!-- Weekly Distribution Chart -->
                <div class="glass-card rounded-2xl p-6 fade-in" style="animation-delay: 0.5s">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 mb-1">Weekly Distribution</h3>
                            <p class="text-sm text-gray-500">Schedules per day</p>
                        </div>
                        <i class="fas fa-chart-bar text-yellow-600 text-xl"></i>
                    </div>
                    <div class="chart-container">
                        <canvas id="timeDistributionChart" style="height: 280px;"></canvas>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar -->
            <div class="space-y-8">

                <!-- Quick Actions -->
                <div class="glass-card rounded-2xl p-6 fade-in" style="animation-delay: 0.4s">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <i class="fas fa-bolt text-yellow-600"></i>
                        Quick Actions
                    </h3>
                    <div class="space-y-3">
                        <a href="/director/pending-approvals" class="action-button w-full bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 text-white px-4 py-3 rounded-xl transition duration-200 flex items-center justify-center font-semibold shadow-lg shadow-yellow-500/30">
                            <i class="fas fa-check-circle mr-2"></i>
                            Review Schedules
                        </a>

                        <a href="/director/schedule_deadline" class="action-button w-full bg-white hover:bg-gray-50 text-gray-700 border-2 border-gray-200 px-4 py-3 rounded-xl transition duration-200 flex items-center justify-center font-semibold">
                            <i class="fas fa-clock mr-2"></i>
                            Set Deadline
                        </a>

                        <a href="/director/schedule" class="action-button w-full bg-white hover:bg-gray-50 text-gray-700 border-2 border-gray-200 px-4 py-3 rounded-xl transition duration-200 flex items-center justify-center font-semibold">
                            <i class="fas fa-calendar-alt mr-2"></i>
                            View Schedule
                        </a>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="glass-card rounded-2xl p-6 fade-in" style="animation-delay: 0.5s">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900">Recent Activity</h3>
                        <i class="fas fa-bell text-yellow-600"></i>
                    </div>
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        <?php if (!empty($data['recent_activity'])): ?>
                            <?php foreach ($data['recent_activity'] as $activity): ?>
                                <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-xl border border-gray-100">
                                    <div class="w-8 h-8 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <i class="fas fa-user text-white text-xs"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold text-gray-900">
                                            <?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?>
                                        </p>
                                        <p class="text-xs text-gray-600 mt-1">
                                            <?php echo htmlspecialchars($activity['action_description']); ?>
                                        </p>
                                        <p class="text-xs text-gray-400 mt-1 flex items-center gap-1">
                                            <i class="far fa-clock"></i>
                                            <?php echo date('M d, h:i A', strtotime($activity['created_at'])); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-12 text-gray-400">
                                <i class="fas fa-inbox text-4xl mb-2"></i>
                                <p class="text-sm">No recent activity</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dayDistribution = <?php echo json_encode($data['day_distribution'] ?? []); ?>;
            const timeDistribution = <?php echo json_encode($data['time_distribution'] ?? []); ?>;

            // Schedule Distribution Chart (Doughnut)
            const scheduleCtx = document.getElementById('scheduleDistributionChart');
            if (scheduleCtx && dayDistribution && dayDistribution.length > 0) {
                new Chart(scheduleCtx, {
                    type: 'doughnut',
                    data: {
                        labels: dayDistribution.map(item => item.day_of_week),
                        datasets: [{
                            data: dayDistribution.map(item => parseInt(item.count)),
                            backgroundColor: [
                                'rgba(245, 158, 11, 0.8)',
                                'rgba(59, 130, 246, 0.8)',
                                'rgba(16, 185, 129, 0.8)',
                                'rgba(239, 68, 68, 0.8)',
                                'rgba(139, 92, 246, 0.8)',
                                'rgba(6, 182, 212, 0.8)',
                                'rgba(251, 146, 60, 0.8)'
                            ],
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 15,
                                    font: {
                                        size: 12,
                                        weight: '600'
                                    },
                                    color: '#374151'
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(31, 41, 55, 0.95)',
                                padding: 12,
                                borderColor: 'rgba(245, 158, 11, 0.5)',
                                borderWidth: 2,
                                cornerRadius: 8
                            }
                        }
                    }
                });
            }

            // Time Distribution Chart (Bar)
            const daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            const timeCtx = document.getElementById('timeDistributionChart');

            if (timeCtx) {
                new Chart(timeCtx, {
                    type: 'bar',
                    data: {
                        labels: daysOfWeek,
                        datasets: [{
                            label: 'Schedules',
                            data: daysOfWeek.map(day => {
                                const dayData = timeDistribution.find(item => item.day_of_week === day);
                                return dayData ? parseInt(dayData.count) : 0;
                            }),
                            backgroundColor: 'rgba(245, 158, 11, 0.8)',
                            borderColor: 'rgba(217, 119, 6, 1)',
                            borderWidth: 2,
                            borderRadius: 8
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
                                padding: 12,
                                borderColor: 'rgba(245, 158, 11, 0.5)',
                                borderWidth: 2,
                                cornerRadius: 8
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1,
                                    font: {
                                        size: 11,
                                        weight: '600'
                                    },
                                    color: '#6b7280'
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
                                    font: {
                                        size: 11,
                                        weight: '600'
                                    },
                                    color: '#374151'
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
            }
        });
    </script>

</body>

</html>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>