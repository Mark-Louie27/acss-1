<?php
ob_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teaching Load Report | Faculty Portal</title>
    <style>
        :root {
            --gold: #D4AF37;
            --orange: #E69F54;
            --blue: #3B82F6;
            --green: #10B981;
            --red: #EF4444;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
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

        .stat-card.orange {
            border-left-color: var(--orange);
            background: linear-gradient(135deg, #FFF7ED, #FFFFFF);
        }

        .stat-card.red {
            border-left-color: var(--red);
            background: linear-gradient(135deg, #FEF2F2, #FFFFFF);
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

        .table-responsive {
            overflow-x: auto;
        }

        .course-badge {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .badge-lecture {
            background: #DBEAFE;
            color: #1E40AF;
        }

        .badge-lab {
            background: #D1FAE5;
            color: #065F46;
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

        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.875rem;
            }
        }
    </style>
</head>

<body class="bg-gray-50 font-sans antialiased">
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- Page Header -->
        <header class="mb-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Teaching Load Report</h1>
                    <p class="text-gray-600 mt-2">Detailed breakdown of your teaching assignments and hours</p>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="text-sm text-gray-500 bg-white px-3 py-1 rounded-full border">
                        <i class="fas fa-calendar-alt mr-2"></i>
                        <?php echo htmlspecialchars($semesterInfo ?? 'Current Semester', ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                    <div class="download-options flex space-x-2">
                        <a href="/faculty/reports/download?type=teaching_load&format=pdf"
                            class="btn-download pdf px-3 py-2 rounded-lg text-sm font-medium">
                            <i class="fas fa-file-pdf mr-1"></i>PDF
                        </a>
                        <a href="/faculty/reports/download?type=teaching_load&format=excel"
                            class="btn-download excel px-3 py-2 rounded-lg text-sm font-medium">
                            <i class="fas fa-file-excel mr-1"></i>Excel
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Faculty Information -->
        <section class="card p-6 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Faculty Information</h3>
                    <p class="text-gray-700"><strong>Name:</strong> <?php echo htmlspecialchars($faculty['faculty_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
                    <p class="text-gray-700"><strong>Department:</strong> <?php echo htmlspecialchars($faculty['department_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
                    <p class="text-gray-700"><strong>Academic Rank:</strong> <?php echo htmlspecialchars($faculty['academic_rank'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Teaching Summary</h3>
                    <p class="text-gray-700"><strong>Total Courses:</strong> <?php echo $courseCount ?? 0; ?></p>
                    <p class="text-gray-700"><strong>Total Hours:</strong> <?php echo number_format($totalHours ?? 0, 1); ?> hrs</p>
                    <p class="text-gray-700"><strong>Equivalent Load:</strong> <?php echo number_format($equivalentLoad ?? 0, 1); ?> hrs</p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Load Analysis</h3>
                    <p class="text-gray-700"><strong>Lecture Hours:</strong> <?php echo number_format($lectureHours ?? 0, 1); ?> hrs</p>
                    <p class="text-gray-700"><strong>Lab Hours:</strong> <?php echo number_format($labHours ?? 0, 1); ?> hrs</p>
                    <p class="text-gray-700"><strong>Total Working Load:</strong> <?php echo number_format($totalWorkingLoad ?? 0, 1); ?> hrs</p>
                </div>
            </div>
        </section>

        <!-- Quick Stats -->
        <section class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="stat-card blue card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Hours</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($totalHours ?? 0, 1); ?></p>
                    </div>
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-clock text-blue-600"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card green card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Courses</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $courseCount ?? 0; ?></p>
                    </div>
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-book text-green-600"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card orange card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Lecture Hours</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($lectureHours ?? 0, 1); ?></p>
                    </div>
                    <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chalkboard text-orange-600"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card red card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Lab Hours</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($labHours ?? 0, 1); ?></p>
                    </div>
                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-flask text-red-600"></i>
                    </div>
                </div>
            </div>
        </section>

        <!-- Teaching Load Details -->
        <section class="card p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold text-gray-800">Teaching Assignments</h2>

                <!-- Semester Filter -->
                <form method="GET" class="flex items-center space-x-2">
                    <select name="semester_id" onchange="this.form.submit()"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Semesters</option>
                        <?php foreach ($semesters as $semester): ?>
                            <option value="<?php echo $semester['semester_id']; ?>"
                                <?php echo ($_GET['semester_id'] ?? '') == $semester['semester_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($semester['semester_display'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <?php if (!empty($teachingData)): ?>
                <div class="table-responsive">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Schedule Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Day & Time</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Students</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hours</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Computed Hours</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($teachingData as $index => $row): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($row['course_code'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($row['course_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="course-badge badge-<?php echo strtolower($row['schedule_type']); ?>">
                                            <?php echo htmlspecialchars($row['schedule_type'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['day_of_week'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="text-sm text-gray-500">
                                            <?php echo date('g:i A', strtotime($row['start_time'])); ?> -
                                            <?php echo date('g:i A', strtotime($row['end_time'])); ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($row['room_name'] ?? 'TBA', ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($row['section_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $row['current_students'] ?? '0'; ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo number_format($row['duration_hours'], 1); ?> hrs
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo number_format($row['computed_hours'], 1); ?> hrs
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="6" class="px-4 py-3 text-right text-sm font-medium text-gray-900">Totals:</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900"><?php echo number_format($totalHours, 1); ?> hrs</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900"><?php echo number_format($totalComputedHours, 1); ?> hrs</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list text-gray-300"></i>
                    <p class="text-gray-500 text-lg mb-4">No teaching assignments found</p>
                    <p class="text-gray-400 text-sm">You don't have any teaching assignments for the selected semester.</p>
                </div>
            <?php endif; ?>
        </section>

        <!-- Load Analysis -->
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
            <div class="card p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Load Distribution</h3>
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">Lecture Hours</span>
                            <span class="font-medium"><?php echo number_format($lectureHours, 1); ?> hrs</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full"
                                style="width: <?php echo $totalHours > 0 ? ($lectureHours / $totalHours * 100) : 0; ?>%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">Laboratory Hours</span>
                            <span class="font-medium"><?php echo number_format($labHours, 1); ?> hrs</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full"
                                style="width: <?php echo $totalHours > 0 ? ($labHours / $totalHours * 100) : 0; ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Working Load Summary</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Actual Teaching Load:</span>
                        <span class="font-medium"><?php echo number_format($totalComputedHours, 1); ?> hrs</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Equivalent Load:</span>
                        <span class="font-medium"><?php echo number_format($equivalentLoad, 1); ?> hrs</span>
                    </div>
                    <div class="flex justify-between border-t pt-2">
                        <span class="text-gray-800 font-semibold">Total Working Load:</span>
                        <span class="text-gray-800 font-semibold"><?php echo number_format($totalWorkingLoad, 1); ?> hrs</span>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add loading states to download buttons
            document.querySelectorAll('.btn-download').forEach(button => {
                button.addEventListener('click', function(e) {
                    const originalHTML = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Generating...';
                    this.classList.add('opacity-75');

                    setTimeout(() => {
                        this.innerHTML = originalHTML;
                        this.classList.remove('opacity-75');
                    }, 3000);
                });
            });
        });
    </script>
</body>

</html>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layout.php';
?>