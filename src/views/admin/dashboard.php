<?php
ob_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        /* Toast Notification Styles */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .toast {
            min-width: 300px;
            max-width: 500px;
            padding: 16px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideInRight 0.3s ease-out;
            backdrop-filter: blur(10px);
        }

        .toast-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.95) 0%, rgba(5, 150, 105, 0.95) 100%);
            color: white;
        }

        .toast-error {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.95) 0%, rgba(220, 38, 38, 0.95) 100%);
            color: white;
        }

        .toast-icon {
            width: 24px;
            height: 24px;
            flex-shrink: 0;
        }

        .toast-close {
            margin-left: auto;
            cursor: pointer;
            opacity: 0.8;
            transition: opacity 0.2s;
        }

        .toast-close:hover {
            opacity: 1;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }

            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }

        .toast-removing {
            animation: slideOutRight 0.3s ease-out forwards;
        }
    </style>
</head>

<body class="min-h-screen">

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

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
                                <h1 class="text-3xl md:text-4xl font-bold tracking-tight">Admin Dashboard</h1>
                                <p class="text-white/80 text-sm mt-1">Automated Classroom Scheduling System</p>
                            </div>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Welcome back, <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>! 👋</h3>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <span class="bg-gray-600 px-4 py-2 rounded-xl text-sm font-medium flex items-center gap-2 backdrop-blur-md">
                            <i class="fas fa-calendar-alt text-white"></i>
                            <?php echo htmlspecialchars($semesterInfo ?? '2nd Semester 2024-2025'); ?>
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
            <!-- Total Users -->
            <div class="glass-card rounded-2xl p-6 hover-scale cursor-pointer fade-in" onclick="window.location.href='/admin/users'" style="animation-delay: 0.1s">
                <div class="flex items-start justify-between mb-4">
                    <div class="metric-icon">
                        <i class="fas fa-users text-white text-xl"></i>
                    </div>
                    <span class="text-xs font-semibold text-yellow-600 bg-yellow-100 px-3 py-1 rounded-full">USERS</span>
                </div>
                <h3 class="text-gray-600 text-sm font-semibold uppercase tracking-wide mb-2">Total Users</h3>
                <p class="text-4xl font-bold text-gray-900 mb-1"><?php echo htmlspecialchars($userCount ?? '0'); ?></p>
                <p class="text-sm text-gray-500 flex items-center gap-2">
                    <i class="fas fa-user-check"></i>
                    Active accounts
                </p>
            </div>

            <!-- Total Colleges -->
            <div class="glass-card rounded-2xl p-6 hover-scale cursor-pointer fade-in" onclick="window.location.href='/admin/colleges'" style="animation-delay: 0.2s">
                <div class="flex items-start justify-between mb-4">
                    <div class="metric-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); box-shadow: 0 8px 16px rgba(59, 130, 246, 0.3);">
                        <i class="fas fa-building text-white text-xl"></i>
                    </div>
                    <span class="text-xs font-semibold text-blue-600 bg-blue-100 px-3 py-1 rounded-full">COLLEGES</span>
                </div>
                <h3 class="text-gray-600 text-sm font-semibold uppercase tracking-wide mb-2">Total Colleges</h3>
                <p class="text-4xl font-bold text-gray-900 mb-1"><?php echo htmlspecialchars($collegeCount ?? '0'); ?></p>
                <p class="text-sm text-gray-500 flex items-center gap-2">
                    <i class="fas fa-graduation-cap"></i>
                    Academic units
                </p>
            </div>

            <!-- Total Departments -->
            <div class="glass-card rounded-2xl p-6 hover-scale cursor-pointer fade-in" onclick="window.location.href='/admin/colleges'" style="animation-delay: 0.3s">
                <div class="flex items-start justify-between mb-4">
                    <div class="metric-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); box-shadow: 0 8px 16px rgba(139, 92, 246, 0.3);">
                        <i class="fas fa-sitemap text-white text-xl"></i>
                    </div>
                    <span class="text-xs font-semibold text-purple-600 bg-purple-100 px-3 py-1 rounded-full">DEPTS</span>
                </div>
                <h3 class="text-gray-600 text-sm font-semibold uppercase tracking-wide mb-2">Departments</h3>
                <p class="text-4xl font-bold text-gray-900 mb-1"><?php echo htmlspecialchars($departmentCount ?? '0'); ?></p>
                <p class="text-sm text-gray-500 flex items-center gap-2">
                    <i class="fas fa-layer-group"></i>
                    Organizational units
                </p>
            </div>

            <!-- Total Schedules -->
            <div class="glass-card rounded-2xl p-6 hover-scale cursor-pointer fade-in" onclick="window.location.href='/admin/schedules'" style="animation-delay: 0.4s">
                <div class="flex items-start justify-between mb-4">
                    <div class="metric-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 8px 16px rgba(16, 185, 129, 0.3);">
                        <i class="fas fa-calendar-check text-white text-xl"></i>
                    </div>
                    <span class="text-xs font-semibold text-green-600 bg-green-100 px-3 py-1 rounded-full">SCHEDULES</span>
                </div>
                <h3 class="text-gray-600 text-sm font-semibold uppercase tracking-wide mb-2">Total Schedules</h3>
                <p class="text-4xl font-bold text-gray-900 mb-1"><?php echo htmlspecialchars($scheduleCount ?? '0'); ?></p>
                <p class="text-sm text-gray-500 flex items-center gap-2">
                    <i class="fas fa-clock"></i>
                    Active schedules
                </p>
            </div>
        </div>

        <!-- Semester Configuration (Moved Here) -->
        <div class="glass-card rounded-2xl p-6 mb-8 fade-in" style="animation-delay: 0.5s">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-cog text-white"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Semester Configuration</h3>
                    <p class="text-sm text-gray-500">Set the current active academic semester</p>
                </div>
            </div>

            <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="semester_name" class="block text-sm font-semibold text-gray-700 mb-2">Semester</label>
                    <select id="semester_name" name="semester_name" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition bg-white">
                        <option value="1st" <?php echo $currentSemester && $currentSemester['semester_name'] === '1st' ? 'selected' : ''; ?>>1st Semester</option>
                        <option value="2nd" <?php echo $currentSemester && $currentSemester['semester_name'] === '2nd' ? 'selected' : ''; ?>>2nd Semester</option>
                        <option value="Mid Year" <?php echo $currentSemester && $currentSemester['semester_name'] === 'Mid Year' ? 'selected' : ''; ?>>Mid Year</option>
                    </select>
                </div>

                <div>
                    <label for="academic_year" class="block text-sm font-semibold text-gray-700 mb-2">Academic Year</label>
                    <input type="text" id="academic_year" name="academic_year"
                        value="<?php echo htmlspecialchars($currentSemester['academic_year'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition"
                        placeholder="e.g., 2024-2025">
                </div>

                <div class="flex items-end">
                    <button type="submit" name="set_semester"
                        class="w-full bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 text-white px-4 py-3 rounded-xl font-semibold transition shadow-lg shadow-yellow-500/30">
                        <i class="fas fa-save mr-2"></i>Update Semester
                    </button>
                </div>
            </form>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Charts Section -->
            <div class="lg:col-span-2 space-y-8">

                <!-- User Distribution Chart -->
                <div class="glass-card rounded-2xl p-6 fade-in" style="animation-delay: 0.6s">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 mb-1">User Distribution</h3>
                            <p class="text-sm text-gray-500">Distribution by role</p>
                        </div>
                        <i class="fas fa-chart-pie text-yellow-600 text-xl"></i>
                    </div>
                    <div class="chart-container">
                        <canvas id="userDistributionChart" style="height: 280px;"></canvas>
                    </div>
                </div>

                <!-- Schedule Status Chart -->
                <div class="glass-card rounded-2xl p-6 fade-in" style="animation-delay: 0.7s">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 mb-1">Schedule Status</h3>
                            <p class="text-sm text-gray-500">Current semester overview</p>
                        </div>
                        <i class="fas fa-chart-bar text-yellow-600 text-xl"></i>
                    </div>
                    <div class="chart-container">
                        <canvas id="scheduleStatusChart" style="height: 280px;"></canvas>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar -->
            <div class="space-y-8">

                <!-- System Status -->
                <div class="glass-card rounded-2xl p-6 fade-in" style="animation-delay: 0.6s">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-lg">
                            <i class="fas fa-server text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">System Status</h3>
                            <p class="text-xs text-gray-500">All systems operational</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-3 bg-green-50 rounded-xl">
                            <span class="text-sm font-medium text-gray-700">Database</span>
                            <span class="px-3 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded-full flex items-center gap-1">
                                <span class="status-dot bg-green-500"></span>
                                Online
                            </span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-green-50 rounded-xl">
                            <span class="text-sm font-medium text-gray-700">API Services</span>
                            <span class="px-3 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded-full flex items-center gap-1">
                                <span class="status-dot bg-green-500"></span>
                                Operational
                            </span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-green-50 rounded-xl">
                            <span class="text-sm font-medium text-gray-700">Scheduling</span>
                            <span class="px-3 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded-full flex items-center gap-1">
                                <span class="status-dot bg-green-500"></span>
                                Running
                            </span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-green-50 rounded-xl">
                            <span class="text-sm font-medium text-gray-700">Authentication</span>
                            <span class="px-3 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded-full flex items-center gap-1">
                                <span class="status-dot bg-green-500"></span>
                                Secure
                            </span>
                        </div>
                    </div>

                    <div class="mt-6 p-4 bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl border border-green-200">
                        <p class="text-sm text-green-800 flex items-center gap-2">
                            <i class="fas fa-check-circle"></i>
                            Last updated: <?php echo date('M j, Y g:i A'); ?>
                        </p>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="glass-card rounded-2xl p-6 fade-in" style="animation-delay: 0.7s">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-bold text-gray-900">Recent Activity</h3>
                        <i class="fas fa-bell text-yellow-600"></i>
                    </div>
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        <?php
                        try {
                            $stmt = $controller->db->prepare("
                            SELECT al.log_id, al.action_type, al.action_description, al.created_at,
                                   u.first_name, u.last_name
                            FROM activity_logs al
                            JOIN users u ON al.user_id = u.user_id
                            ORDER BY al.created_at DESC
                            LIMIT 6
                        ");
                            $stmt->execute();
                            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            if (empty($logs)): ?>
                                <div class="text-center py-12 text-gray-400">
                                    <i class="fas fa-inbox text-4xl mb-2"></i>
                                    <p class="text-sm">No recent activity</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-xl border border-gray-100">
                                        <div class="w-8 h-8 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <i class="fas fa-bolt text-white text-xs"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-bold text-gray-900">
                                                <?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?>
                                            </p>
                                            <p class="text-xs text-gray-600 mt-1 truncate">
                                                <?php echo htmlspecialchars($log['action_description']); ?>
                                            </p>
                                            <p class="text-xs text-gray-400 mt-1">
                                                <i class="far fa-clock mr-1"></i>
                                                <?php echo date('M d, h:i A', strtotime($log['created_at'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif;
                        } catch (PDOException $e) {
                            error_log("Activity logs error: " . $e->getMessage());
                            ?>
                            <div class="text-center py-8 text-red-400">
                                <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                                <p class="text-sm">Error loading activity</p>
                            </div>
                        <?php } ?>
                    </div>

                    <div class="mt-6">
                        <a href="/admin/act_logs" class="w-full bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 text-white px-4 py-3 rounded-xl font-semibold transition shadow-lg shadow-yellow-500/30 flex items-center justify-center gap-2">
                            View All Activity
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // Toast Notification Function
            function showToast(message, type = 'success') {
                const container = document.getElementById('toastContainer');
                const toast = document.createElement('div');
                toast.className = `toast toast-${type}`;

                const icon = type === 'success' ?
                    '<i class="fas fa-check-circle toast-icon"></i>' :
                    '<i class="fas fa-exclamation-circle toast-icon"></i>';

                toast.innerHTML = `
            ${icon}
            <span style="flex: 1;">${message}</span>
            <i class="fas fa-times toast-close"></i>
        `;

                container.appendChild(toast);

                // Close button
                toast.querySelector('.toast-close').addEventListener('click', () => {
                    removeToast(toast);
                });

                // Auto remove after 4 seconds
                setTimeout(() => removeToast(toast), 4000);
            }

            function removeToast(toast) {
                toast.classList.add('toast-removing');
                setTimeout(() => toast.remove(), 300);
            }

            // Show PHP session messages as toasts
            <?php if (isset($_SESSION['success'])): ?>
                showToast(<?php echo json_encode($_SESSION['success']); ?>, 'success');
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                showToast(<?php echo json_encode($_SESSION['error']); ?>, 'error');
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            // User Distribution Chart
            const userCtx = document.getElementById('userDistributionChart');
            if (userCtx) {
                const roleData = <?php echo json_encode($roleDistribution); ?>;
                new Chart(userCtx, {
                    type: 'doughnut',
                    data: {
                        labels: roleData.map(item => item.role_name),
                        datasets: [{
                            data: roleData.map(item => parseInt(item.count)),
                            backgroundColor: [
                                'rgba(245, 158, 11, 0.8)',
                                'rgba(59, 130, 246, 0.8)',
                                'rgba(139, 92, 246, 0.8)',
                                'rgba(16, 185, 129, 0.8)',
                                'rgba(239, 68, 68, 0.8)',
                                'rgba(6, 182, 212, 0.8)'
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
                                cornerRadius: 8,
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.raw || 0;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = Math.round((value / total) * 100);
                                        return `${label}: ${value} (${percentage}%)`;
                                    }
                                }
                            }
                        },
                        cutout: '65%'
                    }
                });
            }

            // Schedule Status Chart
            const scheduleCtx = document.getElementById('scheduleStatusChart');
            if (scheduleCtx) {
                const scheduleData = <?php echo json_encode($scheduleDistribution ?? []); ?>;
                const labels = scheduleData.length > 0 ?
                    scheduleData.map(item => item.status) : ['Pending', 'Approved', 'Completed', 'Cancelled'];
                const counts = scheduleData.length > 0 ?
                    scheduleData.map(item => parseInt(item.count)) : [5, 12, 8, 2];

                new Chart(scheduleCtx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Schedules',
                            data: counts,
                            backgroundColor: [
                                'rgba(245, 158, 11, 0.8)',
                                'rgba(16, 185, 129, 0.8)',
                                'rgba(59, 130, 246, 0.8)',
                                'rgba(239, 68, 68, 0.8)'
                            ],
                            borderColor: [
                                'rgba(217, 119, 6, 1)',
                                'rgba(5, 150, 105, 1)',
                                'rgba(37, 99, 235, 1)',
                                'rgba(220, 38, 38, 1)'
                            ],
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