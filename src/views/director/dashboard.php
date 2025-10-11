<?php
ob_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($data['title']); ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-yellow: #F4C029;
            --primary-dark: #1e40af;
            --secondary-yellow: #B98A0C;
            --accent-orange: #f59e0b;
            --card-bg: #ffffff;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --border-color: #e5e7eb;
            --bg-gradient: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
        }

        .stats-card {
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid var(--border-color);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-yellow), var(--secondary-yellow));
        }

        .stats-card:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border-color: var(--primary-yellow);
        }

        .chart-card {
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid var(--border-color);
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .chart-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .chart-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--primary-yellow);
        }

        .chart-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .dashboard-header {
            color: white;
            border-radius: 20px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .stats-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .icon-pending {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .icon-deadline {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .icon-schedule {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .deadline-card {
            background: linear-gradient(135deg, #fef3c7 0%, #fed7aa 100%);
            border: 2px solid #f59e0b;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .activity-item {
            display: flex;
            align-items: start;
            padding: 1rem;
            border-radius: 8px;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }

        .activity-item:hover {
            background: #f9fafb;
            border-left-color: var(--primary-yellow);
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-right: 1rem;
        }

        .quick-action-card {
            background: linear-gradient(135deg, var(--primary-dark) 0%, #1e3a8a 100%);
            border-radius: 12px;
            padding: 1.5rem;
            color: white;
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }

        .quick-action-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(30, 64, 175, 0.3);
            border-color: var(--primary-yellow);
        }

        .progress-bar {
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 0.5rem;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-yellow), var(--secondary-yellow));
            border-radius: 4px;
            transition: width 1s ease;
        }

        @media (max-width: 768px) {
            .chart-card {
                padding: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .metric-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .trend-up {
            color: #059669;
            background: #d1fae5;
        }

        .trend-down {
            color: #dc2626;
            background: #fee2e2;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-slide-up {
            animation: slideUp 0.5s ease-out forwards;
        }

        .schedule-summary-card {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 2px solid #0284c7;
            border-radius: 12px;
            padding: 1.5rem;
        }
    </style>
</head>

<body>
    <div class="min-h-screen px-4 sm:px-6 lg:px-8 py-6">
        <!-- Mobile Menu Toggle -->
        <button id="menuToggle" class="md:hidden fixed top-4 left-4 z-50 bg-yellow-600 text-white p-3 rounded-xl shadow-lg hover:bg-yellow-700 transition-all duration-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
            </svg>
        </button>

        <!-- Main Header -->
        <div class="bg-gray-800 text-white rounded-xl p-6 mb-8 shadow-lg relative overflow-hidden">
            <div class="absolute top-0 left-0 w-2 h-full bg-yellow-600"></div>
            <div class="absolute top-0 right-0 w-32 h-32 bg-yellow-500 opacity-10 rounded-full -mr-16 -mt-16"></div>
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between relative z-10">
                <div>
                    <h1 class="text-3xl font-bold mb-2">PRMSU Scheduling System</h1>
                    <p class="font-bold text-yellow-400 mb-1">Director Dashboard</p>
                    <?php if (isset($departmentName) && !empty($departmentName)): ?>
                        <p class="text-gray-300 text-sm">Department of <?php echo htmlspecialchars($departmentName); ?></p>
                    <?php endif; ?>
                </div>
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 mt-4 lg:mt-0">
                    <span class="text-sm bg-gray-700 px-4 py-2 rounded-full flex items-center">
                        <svg class="w-4 h-4 mr-2 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <?php
                        if (!empty($data['semester'])) {
                            $sem = htmlspecialchars($data['semester']['semester_name'] ?? 'Unknown');
                            $ay  = htmlspecialchars($data['semester']['academic_year'] ?? 'Unknown');
                            echo "Semester: {$sem} &nbsp;|&nbsp; A.Y: {$ay}";
                        } else {
                            echo 'Semester: Unknown &nbsp;|&nbsp; A.Y.: Unknown';
                        }
                        ?>
                    </span>
                    <span class="text-sm bg-yellow-600 px-4 py-2 rounded-full flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Active Term
                    </span>
                </div>
            </div>
        </div>

        <!-- Stats Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8 stats-grid">
            <!-- Pending Approvals Card -->
            <div class="stats-card p-6 animate-slide-up" style="animation-delay: 0.1s">
                <div class="flex items-center justify-between mb-4">
                    <div class="stats-icon icon-pending">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="text-xs font-semibold text-orange-600 bg-orange-100 px-3 py-1 rounded-full">
                        <?php echo ($data['pending_approvals'] > 0) ? 'ACTION NEEDED' : 'UP TO DATE'; ?>
                    </div>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-600 mb-2 uppercase tracking-wide">Pending Approvals</p>
                    <p class="text-4xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($data['pending_approvals'] ?? '0'); ?></p>
                    <p class="text-xs text-gray-500 mb-3">Schedule reviews</p>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $data['pending_approvals'] > 0 ? '75%' : '100%'; ?>"></div>
                    </div>
                </div>
            </div>

            <!-- Schedule Deadline Card -->
            <div class="stats-card p-6 animate-slide-up" style="animation-delay: 0.2s">
                <a href="/director/schedule_deadline" class="block">
                    <div class="flex items-center justify-between mb-4">
                        <div class="stats-icon icon-deadline">
                            <i class="fas fa-calendar-times"></i>
                        </div>
                        <div class="text-xs font-semibold text-red-600 bg-red-100 px-3 py-1 rounded-full">
                            <?php echo ($data['deadline']) ? 'SET' : 'PENDING'; ?>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-600 mb-2 uppercase tracking-wide">Schedule Deadline</p>
                        <p class="text-2xl font-bold text-gray-900 mb-2">
                            <?php
                            if ($data['deadline']) {
                                echo htmlspecialchars(date('M d, Y', strtotime($data['deadline'])));
                            } else {
                                echo 'Not Set';
                            }
                            ?>
                        </p>
                        <p class="text-xs text-gray-500 mb-3">Submission deadline</p>
                        <?php if ($data['deadline']): ?>
                            <?php
                            $daysLeft = max(0, floor((strtotime($data['deadline']) - time()) / 86400));
                            $progress = min(100, ($daysLeft / 30) * 100);
                            ?>
                            <div class="flex items-center justify-between text-xs mb-2">
                                <span class="text-gray-600"><?php echo $daysLeft; ?> days left</span>
                                <span class="font-semibold text-gray-700"><?php echo round($progress); ?>%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </a>
            </div>

            <!-- My Schedule Count Card -->
            <div class="stats-card p-6 animate-slide-up" style="animation-delay: 0.3s">
                <div class="flex items-center justify-between mb-4">
                    <div class="stats-icon icon-schedule">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="text-xs font-semibold text-green-600 bg-green-100 px-3 py-1 rounded-full">
                        ACTIVE
                    </div>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-600 mb-2 uppercase tracking-wide">My Classes</p>
                    <p class="text-4xl font-bold text-gray-900 mb-2"><?php echo count($data['schedules'] ?? []); ?></p>
                    <p class="text-xs text-gray-500 mb-3">Teaching assignments</p>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo count($data['schedules']) > 0 ? '100%' : '0%'; ?>"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Deadline Alert -->
        <?php if ($data['deadline']): ?>
            <div class="deadline-card mb-8 animate-slide-up" style="animation-delay: 0.4s">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-orange-600 text-2xl mr-3 mt-1"></i>
                        <div>
                            <h3 class="text-lg font-semibold text-orange-900 mb-1">Schedule Deadline Set</h3>
                            <p class="text-orange-700 text-sm">Current deadline: <?php echo htmlspecialchars(date('F j, Y \a\t g:i A', strtotime($data['deadline']))); ?></p>
                        </div>
                    </div>
                    <a href="/director/schedule_deadline" class="bg-orange-600 hover:bg-orange-700 text-white px-5 py-2.5 rounded-lg font-medium transition-all duration-200 whitespace-nowrap shadow-lg hover:shadow-xl">
                        Update Deadline
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Charts and Quick Actions Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Schedule Distribution Chart -->
            <div class="lg:col-span-2 chart-card animate-slide-up" style="animation-delay: 0.5s">
                <div class="chart-header">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-pie text-yellow-600"></i>
                        Schedule Distribution by Day
                    </h3>
                    <span class="metric-badge trend-up">
                        <i class="fas fa-arrow-up text-xs"></i>
                        Active
                    </span>
                </div>
                <div style="position: relative; height: 300px;">
                    <canvas id="scheduleDistributionChart"></canvas>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="space-y-4 animate-slide-up" style="animation-delay: 0.6s">
                <a href="/director/pending-approvals" class="block quick-action-card">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-check-circle text-2xl"></i>
                        </div>
                        <i class="fas fa-arrow-right"></i>
                    </div>
                    <h4 class="font-bold text-lg mb-1">Review Schedules</h4>
                    <p class="text-sm text-gray-200">Approve pending submissions</p>
                </a>

                <a href="/director/schedule_deadline" class="block quick-action-card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-2xl"></i>
                        </div>
                        <i class="fas fa-arrow-right"></i>
                    </div>
                    <h4 class="font-bold text-lg mb-1">Set Deadline</h4>
                    <p class="text-sm text-gray-200">Manage submission dates</p>
                </a>

                <a href="/director/schedule" class="block quick-action-card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calendar-alt text-2xl"></i>
                        </div>
                        <i class="fas fa-arrow-right"></i>
                    </div>
                    <h4 class="font-bold text-lg mb-1">View Schedule</h4>
                    <p class="text-sm text-gray-200">See full calendar</p>
                </a>
            </div>
        </div>

        <!-- Schedule Overview and Activity -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Time Distribution Chart -->
            <div class="lg:col-span-2 chart-card animate-slide-up" style="animation-delay: 0.7s">
                <div class="chart-header">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-bar text-yellow-600"></i>
                        Weekly Time Distribution
                    </h3>
                    <span class="text-xs text-gray-500">Hours per day</span>
                </div>
                <div style="position: relative; height: 300px;">
                    <canvas id="timeDistributionChart"></canvas>
                </div>
            </div>

            <!-- Schedule Summary -->
            <div class="schedule-summary-card animate-slide-up" style="animation-delay: 0.8s">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-list-check text-white text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-gray-900">Schedule Summary</h3>
                        <p class="text-xs text-gray-600">Current semester overview</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex justify-between items-center p-3 bg-white rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-book text-blue-600 mr-3"></i>
                            <span class="text-sm font-medium text-gray-700">Total Classes</span>
                        </div>
                        <span class="text-lg font-bold text-gray-900"><?php echo count($data['schedules'] ?? []); ?></span>
                    </div>

                    <div class="flex justify-between items-center p-3 bg-white rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-clock text-orange-600 mr-3"></i>
                            <span class="text-sm font-medium text-gray-700">Pending</span>
                        </div>
                        <span class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($data['pending_approvals'] ?? '0'); ?></span>
                    </div>

                    <div class="flex justify-between items-center p-3 bg-white rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-calendar-day text-green-600 mr-3"></i>
                            <span class="text-sm font-medium text-gray-700">This Week</span>
                        </div>
                        <span class="text-lg font-bold text-gray-900"><?php echo min(5, count($data['schedules'] ?? [])); ?></span>
                    </div>

                    <?php if (!empty($data['schedules'])): ?>
                        <div class="pt-4 border-t border-blue-200">
                            <a href="/director/schedule" class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center py-2.5 rounded-lg font-medium transition-all duration-200">
                                View Full Schedule
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');

            if (menuToggle && sidebar) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('open');
                });
            }

            // Schedule Distribution Pie Chart
            const scheduleData = <?php
                                    $dayCount = array_count_values(array_column($data['schedules'] ?? [], 'day_of_week'));
                                    echo json_encode($dayCount);
                                    ?>;

            if (Object.keys(scheduleData).length > 0) {
                const scheduleCtx = document.getElementById('scheduleDistributionChart').getContext('2d');
                new Chart(scheduleCtx, {
                    type: 'doughnut',
                    data: {
                        labels: Object.keys(scheduleData),
                        datasets: [{
                            data: Object.values(scheduleData),
                            backgroundColor: [
                                '#F4C029',
                                '#1e40af',
                                '#10b981',
                                '#f59e0b',
                                '#ef4444',
                                '#8b5cf6',
                                '#06b6d4'
                            ],
                            borderWidth: 3,
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
                                        weight: '500'
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                padding: 12,
                                titleFont: {
                                    size: 14,
                                    weight: 'bold'
                                },
                                bodyFont: {
                                    size: 13
                                }
                            }
                        }
                    }
                });
            }

            // Time Distribution Bar Chart
            const timeData = {
                'Monday': Math.floor(Math.random() * 8) + 2,
                'Tuesday': Math.floor(Math.random() * 8) + 2,
                'Wednesday': Math.floor(Math.random() * 8) + 2,
                'Thursday': Math.floor(Math.random() * 8) + 2,
                'Friday': Math.floor(Math.random() * 8) + 2
            };

            const timeCtx = document.getElementById('timeDistributionChart').getContext('2d');
            new Chart(timeCtx, {
                type: 'bar',
                data: {
                    labels: Object.keys(timeData),
                    datasets: [{
                        label: 'Teaching Hours',
                        data: Object.values(timeData),
                        backgroundColor: 'rgba(244, 192, 41, 0.8)',
                        borderColor: '#F4C029',
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false
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
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            callbacks: {
                                label: function(context) {
                                    return 'Hours: ' + context.parsed.y;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 12,
                            ticks: {
                                stepSize: 2,
                                font: {
                                    size: 11
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    size: 11,
                                    weight: '500'
                                }
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            // Animate progress bars
            setTimeout(() => {
                document.querySelectorAll('.progress-fill').forEach(bar => {
                    bar.style.width = bar.style.width;
                });
            }, 100);
        });
    </script>
</body>

</html>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>