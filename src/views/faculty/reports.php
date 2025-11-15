<?php
ob_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports | Faculty Portal</title>
    <style>
        :root {
            --gold: #D4AF37;
            --orange: #E69F54;
            --white: #FFFFFF;
            --gray-dark: #4B5563;
            --gray-light: #E5E7EB;
            --blue: #3B82F6;
            --green: #10B981;
            --purple: #8B5CF6;
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--gold), var(--orange));
            color: white;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3);
        }

        .stat-card {
            border-left: 4px solid;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateX(4px);
        }

        .stat-card.blue {
            border-left-color: var(--blue);
            background: linear-gradient(135deg, #EFF6FF, #FFFFFF);
        }

        .stat-card.green {
            border-left-color: var(--green);
            background: linear-gradient(135deg, #ECFDF5, #FFFFFF);
        }

        .stat-card.purple {
            border-left-color: var(--purple);
            background: linear-gradient(135deg, #F5F3FF, #FFFFFF);
        }

        .stat-card.orange {
            border-left-color: var(--orange);
            background: linear-gradient(135deg, #FFF7ED, #FFFFFF);
        }

        .report-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .filter-section {
            background: #F8FAFC;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .download-options {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-download {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn-download.pdf {
            background: #DC2626;
            color: white;
        }

        .btn-download.excel {
            background: #059669;
            color: white;
        }

        .btn-download.print {
            background: var(--gray-dark);
            color: white;
        }

        .btn-download:hover {
            transform: translateY(-1px);
            opacity: 0.9;
        }

        .report-section {
            margin-bottom: 2rem;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .day-schedule {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            border: 1px solid #E5E7EB;
        }

        .day-header {
            font-weight: 600;
            color: var(--gray-dark);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--gold);
        }

        .time-slot {
            background: #F8FAFC;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            border-left: 3px solid var(--blue);
        }

        .time-slot.lab {
            border-left-color: var(--green);
        }

        .time-range {
            font-weight: 600;
            color: var(--gray-dark);
            font-size: 0.875rem;
        }

        .course-info {
            margin-top: 0.5rem;
        }

        .course-code {
            font-weight: 600;
            color: var(--gray-dark);
        }

        .room-info {
            color: #6B7280;
            font-size: 0.875rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #6B7280;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--gold);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 768px) {
            .schedule-grid {
                grid-template-columns: 1fr;
            }

            .download-options {
                justify-content: center;
            }

            .stat-card {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>

<body class="bg-gray-50 font-sans antialiased">
    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-5 right-5 z-50"></div>

    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- Page Header -->
        <header class="mb-8 fade-in">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Reports & Analytics</h1>
                    <p class="text-gray-600 mt-2">View and download your teaching reports and schedules</p>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="text-sm text-gray-500 bg-white px-3 py-1 rounded-full border">
                        <i class="fas fa-calendar-alt mr-2"></i>
                        <?php echo htmlspecialchars($currentSemester ?? 'Current Semester', ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </div>
            </div>
        </header>

        <!-- Quick Stats -->
        <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 fade-in">
            <div class="stat-card blue card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Teaching Hours</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($totalHours ?? 0, 1); ?></p>
                        <p class="text-xs text-gray-500 mt-1">This semester</p>
                    </div>
                    <div class="report-icon bg-blue-100 text-blue-600">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card green card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Courses Assigned</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $courseCount ?? 0; ?></p>
                        <p class="text-xs text-gray-500 mt-1">Active preparations</p>
                    </div>
                    <div class="report-icon bg-green-100 text-green-600">
                        <i class="fas fa-book"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card purple card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Specializations</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $specializationsCount ?? 0; ?></p>
                        <p class="text-xs text-gray-500 mt-1">Subject expertise</p>
                    </div>
                    <div class="report-icon bg-purple-100 text-purple-600">
                        <i class="fas fa-star"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card orange card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Students</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $totalStudents ?? 0; ?></p>
                        <p class="text-xs text-gray-500 mt-1">Across all classes</p>
                    </div>
                    <div class="report-icon bg-orange-100 text-orange-600">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </section>

        <!-- Report Navigation -->
        <section class="mb-8 fade-in">
            <div class="card p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Available Reports</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Teaching Load Report -->
                    <div class="border border-gray-200 rounded-lg p-4 hover:border-gold transition-colors">
                        <div class="flex items-center space-x-3 mb-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-chart-bar text-blue-600"></i>
                            </div>
                            <h3 class="font-semibold text-gray-800">Teaching Load</h3>
                        </div>
                        <p class="text-sm text-gray-600 mb-4">Detailed breakdown of your teaching assignments and hours</p>
                        <a href="/faculty/reports/teaching-load" class="btn-primary px-4 py-2 rounded-lg text-sm font-medium inline-flex items-center space-x-2">
                            <i class="fas fa-eye"></i>
                            <span>View Report</span>
                        </a>
                    </div>

                    <!-- Schedule Report -->
                    <div class="border border-gray-200 rounded-lg p-4 hover:border-gold transition-colors">
                        <div class="flex items-center space-x-3 mb-3">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-calendar-alt text-green-600"></i>
                            </div>
                            <h3 class="font-semibold text-gray-800">Weekly Schedule</h3>
                        </div>
                        <p class="text-sm text-gray-600 mb-4">Your complete teaching schedule organized by day and time</p>
                        <a href="/faculty/schedule" class="btn-primary px-4 py-2 rounded-lg text-sm font-medium inline-flex items-center space-x-2">
                            <i class="fas fa-eye"></i>
                            <span>View Report</span>
                        </a>
                    </div>

                    <!-- Specializations Report -->
                    <div class="border border-gray-200 rounded-lg p-4 hover:border-gold transition-colors">
                        <div class="flex items-center space-x-3 mb-3">
                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-graduation-cap text-purple-600"></i>
                            </div>
                            <h3 class="font-semibold text-gray-800">Specializations</h3>
                        </div>
                        <p class="text-sm text-gray-600 mb-4">Your subject expertise and specialization areas</p>
                        <a href="/faculty/reports/specializations" class="btn-primary px-4 py-2 rounded-lg text-sm font-medium inline-flex items-center space-x-2">
                            <i class="fas fa-eye"></i>
                            <span>View Report</span>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Quick Actions -->
        <section class="mb-8 fade-in">
            <div class="card p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Quick Downloads</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <a href="/faculty/reports/download?type=teaching_load&format=pdf"
                        class="flex items-center space-x-3 p-4 border border-gray-200 rounded-lg hover:border-red-300 hover:bg-red-50 transition-colors group">
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center group-hover:bg-red-200 transition-colors">
                            <i class="fas fa-file-pdf text-red-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800">Teaching Load PDF</h3>
                            <p class="text-sm text-gray-600">Official format</p>
                        </div>
                    </a>

                    <a href="/faculty/reports/download?type=schedule&format=excel"
                        class="flex items-center space-x-3 p-4 border border-gray-200 rounded-lg hover:border-green-300 hover:bg-green-50 transition-colors group">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center group-hover:bg-green-200 transition-colors">
                            <i class="fas fa-file-excel text-green-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800">Schedule Excel</h3>
                            <p class="text-sm text-gray-600">Spreadsheet format</p>
                        </div>
                    </a>

                    <a href="/faculty/reports/download?type=specializations&format=pdf"
                        class="flex items-center space-x-3 p-4 border border-gray-200 rounded-lg hover:border-purple-300 hover:bg-purple-50 transition-colors group">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center group-hover:bg-purple-200 transition-colors">
                            <i class="fas fa-file-pdf text-purple-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800">Specializations PDF</h3>
                            <p class="text-sm text-gray-600">Expertise summary</p>
                        </div>
                    </a>

                    <a href="#" onclick="window.print()"
                        class="flex items-center space-x-3 p-4 border border-gray-200 rounded-lg hover:border-gray-300 hover:bg-gray-50 transition-colors group">
                        <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center group-hover:bg-gray-200 transition-colors">
                            <i class="fas fa-print text-gray-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800">Print All</h3>
                            <p class="text-sm text-gray-600">Current view</p>
                        </div>
                    </a>
                </div>
            </div>
        </section>

        <!-- Recent Activity / Summary -->
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-8 fade-in">
            <!-- Upcoming Schedule -->
            <div class="card p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-800">Today's Schedule</h2>
                    <span class="text-sm text-gray-500 bg-gray-100 px-2 py-1 rounded">
                        <?php echo date('l, F j, Y'); ?>
                    </span>
                </div>

                <div id="todaySchedule">
                    <?php if (!empty($todaySchedule)): ?>
                        <?php foreach ($todaySchedule as $class): ?>
                            <div class="time-slot <?php echo $class['schedule_type'] === 'Laboratory' ? 'lab' : ''; ?>">
                                <div class="flex justify-between items-start">
                                    <div class="time-range">
                                        <?php echo date('g:i A', strtotime($class['start_time'])); ?> -
                                        <?php echo date('g:i A', strtotime($class['end_time'])); ?>
                                    </div>
                                    <span class="text-xs font-medium px-2 py-1 rounded-full 
                                        <?php echo $class['schedule_type'] === 'Laboratory' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'; ?>">
                                        <?php echo htmlspecialchars($class['schedule_type'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </div>
                                <div class="course-info">
                                    <div class="course-code">
                                        <?php echo htmlspecialchars($class['course_code'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        <?php echo htmlspecialchars($class['course_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <div class="room-info">
                                        <i class="fas fa-map-marker-alt mr-1"></i>
                                        <?php echo htmlspecialchars($class['room_name'] ?? 'TBA', ENT_QUOTES, 'UTF-8'); ?>
                                        <?php if (!empty($class['section_name'])): ?>
                                            • Section <?php echo htmlspecialchars($class['section_name'], ENT_QUOTES, 'UTF-8'); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times text-gray-300"></i>
                            <p class="text-gray-500">No classes scheduled for today</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Report Statistics -->
            <div class="card p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Report Statistics</h2>
                <div class="space-y-4">
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-chart-line text-blue-600 text-sm"></i>
                            </div>
                            <span class="text-gray-700">Teaching Load Distribution</span>
                        </div>
                        <a href="/faculty/reports/teaching-load" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            View Details
                        </a>
                    </div>

                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-clock text-green-600 text-sm"></i>
                            </div>
                            <span class="text-gray-700">Weekly Time Allocation</span>
                        </div>
                        <a href="/faculty/schedule" class="text-green-600 hover:text-green-800 text-sm font-medium">
                            View Details
                        </a>
                    </div>

                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-star text-purple-600 text-sm"></i>
                            </div>
                            <span class="text-gray-700">Specialization Areas</span>
                        </div>
                        <a href="/faculty/reports/specializations" class="text-purple-600 hover:text-purple-800 text-sm font-medium">
                            View Details
                        </a>
                    </div>

                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-download text-orange-600 text-sm"></i>
                            </div>
                            <span class="text-gray-700">Download Center</span>
                        </div>
                        <a href="#downloads" class="text-orange-600 hover:text-orange-800 text-sm font-medium">
                            Access Files
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toast notification function
            function showToast(message, type = 'info') {
                const toast = document.createElement('div');
                const bgColor = {
                    'success': 'bg-green-500',
                    'error': 'bg-red-500',
                    'warning': 'bg-yellow-500',
                    'info': 'bg-blue-500'
                } [type] || 'bg-gray-500';

                toast.className = `toast ${bgColor} text-white px-4 py-3 rounded-lg shadow-lg flex items-center space-x-2 mb-2 transform transition-transform duration-300 translate-x-full`;
                toast.innerHTML = `
                    <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i>
                    <span>${message}</span>
                `;

                document.getElementById('toast-container').appendChild(toast);

                setTimeout(() => {
                    toast.classList.remove('translate-x-full');
                }, 100);

                setTimeout(() => {
                    toast.classList.add('translate-x-full');
                    setTimeout(() => toast.remove(), 300);
                }, 5000);
            }

            // Handle download clicks
            document.querySelectorAll('a[href*="/faculty/reports/download"]').forEach(link => {
                link.addEventListener('click', function(e) {
                    const originalText = this.innerHTML;
                    this.innerHTML = '<span class="loading-spinner mr-2"></span> Generating...';
                    this.classList.add('opacity-75');

                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.classList.remove('opacity-75');
                        showToast('Report download started successfully', 'success');
                    }, 2000);
                });
            });

            // Print functionality
            document.querySelector('a[onclick="window.print()"]').addEventListener('click', function(e) {
                e.preventDefault();
                showToast('Preparing print layout...', 'info');
                setTimeout(() => window.print(), 1000);
            });

            // Load today's schedule if not already loaded
            if (document.getElementById('todaySchedule').children.length === 0) {
                fetchTodaySchedule();
            }

            function fetchTodaySchedule() {
                const today = new Date().toLocaleDateString('en-US', {
                    weekday: 'long'
                });
                // You can implement AJAX call here to fetch today's schedule
                console.log('Fetching schedule for:', today);
            }

            // Auto-hide toasts on print
            window.addEventListener('beforeprint', function() {
                document.getElementById('toast-container').style.display = 'none';
            });

            window.addEventListener('afterprint', function() {
                document.getElementById('toast-container').style.display = 'block';
            });

            // Initialize tooltips if needed
            const tooltipElements = document.querySelectorAll('[data-tooltip]');
            tooltipElements.forEach(el => {
                el.addEventListener('mouseenter', function() {
                    // Add tooltip implementation if needed
                });
            });
        });

        // Print styles
        const style = document.createElement('style');
        style.textContent = `
            @media print {
                .no-print { display: none !important; }
                .card { break-inside: avoid; }
                body { background: white !important; }
                .bg-gray-50 { background: white !important; }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>

</html>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>