<?php
ob_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Specializations Report | Faculty Portal</title>
    <style>
        :root {
            --gold: #D4AF37;
            --orange: #E69F54;
            --blue: #3B82F6;
            --green: #10B981;
            --purple: #8B5CF6;
            --yellow: #F59E0B;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .specialization-card {
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .specialization-card:hover {
            border-color: var(--orange);
            box-shadow: 0 4px 12px rgba(230, 159, 84, 0.15);
        }

        .expertise-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .level-beginner {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .level-intermediate {
            background-color: #fef3c7;
            color: #d97706;
        }

        .level-expert {
            background-color: #dcfce7;
            color: #16a34a;
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

        .stat-card.yellow {
            border-left-color: var(--yellow);
            background: linear-gradient(135deg, #FEFCE8, #FFFFFF);
        }

        .stat-card.purple {
            border-left-color: var(--purple);
            background: linear-gradient(135deg, #FAF5FF, #FFFFFF);
        }

        @media (max-width: 768px) {
            .grid-cols-2 {
                grid-template-columns: 1fr;
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
                    <h1 class="text-3xl font-bold text-gray-900">Subject Specializations</h1>
                    <p class="text-gray-600 mt-2">Your subject expertise and specialization areas</p>
                </div>
                <div class="download-options flex space-x-2">
                    <a href="/faculty/reports/download?type=specializations&format=pdf"
                        class="btn-download pdf px-3 py-2 rounded-lg text-sm font-medium">
                        <i class="fas fa-file-pdf mr-1"></i>PDF
                    </a>
                    <a href="/faculty/reports/download?type=specializations&format=excel"
                        class="btn-download excel px-3 py-2 rounded-lg text-sm font-medium">
                        <i class="fas fa-file-excel mr-1"></i>Excel
                    </a>
                </div>
            </div>
        </header>

        <!-- Faculty Information -->
        <section class="card p-6 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Faculty Information</h3>
                    <p class="text-gray-700"><strong>Name:</strong> <?php echo htmlspecialchars($faculty['faculty_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
                    <p class="text-gray-700"><strong>Department:</strong> <?php echo htmlspecialchars($faculty['department_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
                    <p class="text-gray-700"><strong>Academic Rank:</strong> <?php echo htmlspecialchars($faculty['academic_rank'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Specialization Summary</h3>
                    <p class="text-gray-700"><strong>Total Specializations:</strong> <?php echo count($specializations); ?></p>
                    <p class="text-gray-700"><strong>Employment Type:</strong> <?php echo htmlspecialchars($faculty['employment_type'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
                    <p class="text-gray-700"><strong>Last Updated:</strong> <?php echo date('F j, Y'); ?></p>
                </div>
            </div>
        </section>

        <!-- Expertise Statistics -->
        <section class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="stat-card blue card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Specializations</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo count($specializations); ?></p>
                    </div>
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-star text-blue-600"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card green card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Expert Level</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $expertiseCount['expert'] ?? 0; ?></p>
                    </div>
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-trophy text-green-600"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card yellow card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Intermediate</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $expertiseCount['intermediate'] ?? 0; ?></p>
                    </div>
                    <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-line text-yellow-600"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card purple card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Beginner</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $expertiseCount['beginner'] ?? 0; ?></p>
                    </div>
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-seedling text-purple-600"></i>
                    </div>
                </div>
            </div>
        </section>

        <!-- Specializations List -->
        <section class="card p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold text-gray-800">Your Specializations</h2>
                <a href="/faculty/profile" class="btn-primary px-4 py-2 rounded-lg text-sm font-medium">
                    <i class="fas fa-plus mr-2"></i>Add Specialization
                </a>
            </div>

            <?php if (!empty($specializations)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($specializations as $spec): ?>
                        <div class="specialization-card">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <span class="expertise-badge level-<?php echo strtolower($spec['expertise_level']); ?>">
                                        <?php echo htmlspecialchars($spec['expertise_level'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <?php echo date('M Y', strtotime($spec['created_at'])); ?>
                                </div>
                            </div>

                            <h3 class="font-semibold text-gray-800 mb-2 text-lg">
                                <?php echo htmlspecialchars($spec['course_code'], ENT_QUOTES, 'UTF-8'); ?>
                            </h3>

                            <p class="text-gray-600 mb-3">
                                <?php echo htmlspecialchars($spec['course_name'], ENT_QUOTES, 'UTF-8'); ?>
                            </p>

                            <div class="space-y-2 text-sm text-gray-500">
                                <div class="flex justify-between">
                                    <span>Units:</span>
                                    <span class="font-medium"><?php echo $spec['units']; ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Department:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($spec['department_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Added:</span>
                                    <span><?php echo date('M j, Y', strtotime($spec['created_at'])); ?></span>
                                </div>
                                <?php if ($spec['updated_at'] && $spec['updated_at'] != $spec['created_at']): ?>
                                    <div class="flex justify-between">
                                        <span>Updated:</span>
                                        <span><?php echo date('M j, Y', strtotime($spec['updated_at'])); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-graduation-cap text-gray-300"></i>
                    <p class="text-gray-500 text-lg mb-4">No specializations added yet</p>
                    <p class="text-gray-400 text-sm mb-6">You haven't added any subject specializations to your profile.</p>
                    <a href="/faculty/profile" class="btn-primary px-6 py-3 rounded-lg font-medium inline-flex items-center space-x-2">
                        <i class="fas fa-plus"></i>
                        <span>Add Your First Specialization</span>
                    </a>
                </div>
            <?php endif; ?>
        </section>

        <!-- Expertise Distribution -->
        <?php if (!empty($specializations)): ?>
            <section class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
                <div class="card p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Expertise Distribution</h3>
                    <div class="space-y-4">
                        <?php
                        $total = count($specializations);
                        $levels = [
                            'expert' => $expertiseCount['expert'] ?? 0,
                            'intermediate' => $expertiseCount['intermediate'] ?? 0,
                            'beginner' => $expertiseCount['beginner'] ?? 0
                        ];

                        foreach ($levels as $level => $count):
                            if ($total > 0):
                                $percentage = ($count / $total) * 100;
                        ?>
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="text-gray-600 capitalize"><?php echo $level; ?></span>
                                        <span class="font-medium"><?php echo $count; ?> (<?php echo number_format($percentage, 1); ?>%)</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="h-2 rounded-full level-<?php echo $level; ?>"
                                            style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                </div>
                        <?php
                            endif;
                        endforeach;
                        ?>
                    </div>
                </div>

                <div class="card p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Department Distribution</h3>
                    <div class="space-y-3">
                        <?php
                        $departmentCounts = [];
                        foreach ($specializations as $spec) {
                            $dept = $spec['department_name'];
                            if (!isset($departmentCounts[$dept])) {
                                $departmentCounts[$dept] = 0;
                            }
                            $departmentCounts[$dept]++;
                        }

                        foreach ($departmentCounts as $dept => $count):
                        ?>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600"><?php echo htmlspecialchars($dept, ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="font-medium"><?php echo $count; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>
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