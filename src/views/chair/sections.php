<?php
ob_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Section Management | ACSS</title>
    <link rel="stylesheet" href="/css/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        :root {
            --gold: #D4AF37;
            --gold-hover: #b8972e;
            --white: #FFFFFF;
            --gray-dark: #4B5563;
            --gray-medium: #6B7280;
            --gray-light: #E5E7EB;
            --gray-50: #F9FAFB;
            --gray-100: #F3F4F6;
            --blue-500: #3B82F6;
            --blue-600: #2563EB;
            --red-500: #EF4444;
            --red-600: #DC2626;
            --green-500: #10B981;
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .slide-in-left {
            animation: slideInLeft 0.5s ease-in;
        }

        @keyframes slideInLeft {
            from {
                transform: translateX(-20px);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 1.25rem;
            right: 1.25rem;
            z-index: 1000;
        }

        .toast {
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
            color: white;
            font-weight: 500;
        }

        .toast-success {
            background-color: var(--green-500);
        }

        .toast-error {
            background-color: var(--red-500);
        }

        .toast-info {
            background-color: var(--blue-500);
        }

        /* Modal Styles */
        .modal {
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
        }

        .modal-content {
            transition: transform 0.2s ease-in-out;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        /* Button Styles */
        .btn-gold {
            background-color: var(--gold);
            color: var(--white);
            transition: all 0.2s ease;
        }

        .btn-gold:hover:not(:disabled) {
            background-color: var(--gold-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3);
        }

        .btn-gold:disabled {
            background-color: var(--gray-light);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-outline {
            border: 1px solid var(--gray-light);
            color: var(--gray-dark);
            background-color: white;
            transition: all 0.2s ease;
        }

        .btn-outline:hover {
            background-color: var(--gray-50);
            border-color: var(--gray-medium);
        }

        /* Form Styles */
        .input-focus {
            border: 1px solid var(--gray-light);
            transition: all 0.2s ease;
        }

        .input-focus:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
            outline: none;
        }

        /* Badge Styles */
        .current-semester-badge {
            background: linear-gradient(135deg, var(--blue-500) 0%, var(--blue-600) 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            font-size: 0.875rem;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
        }

        .stats-badge {
            background-color: var(--gray-100);
            color: var(--gray-dark);
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Search and Filter */
        .search-input {
            width: 100%;
            padding: 0.5rem 1rem;
            border: 1px solid var(--gray-light);
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .search-input:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
            outline: none;
        }

        .filter-select {
            padding: 0.5rem 1rem;
            border: 1px solid var(--gray-light);
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .filter-select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
            outline: none;
        }

        /* Loading Animation */
        .loading::after {
            content: '';
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid var(--gold);
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-left: 8px;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Action Buttons */
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
            position: relative;
        }

        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .edit-btn {
            background-color: var(--blue-500);
            color: white;
        }

        .edit-btn:hover {
            background-color: var(--blue-600);
        }

        .remove-btn {
            background-color: var(--red-500);
            color: white;
        }

        .remove-btn:hover {
            background-color: var(--red-600);
        }

        .action-tooltip {
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s;
            z-index: 10;
            margin-bottom: 0.25rem;
        }

        .action-btn:hover .action-tooltip {
            opacity: 1;
        }

        /* Empty State */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 400px;
            padding: 2rem;
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
            border-radius: 1rem;
            border: 2px dashed var(--gray-light);
            text-align: center;
            animation: fadeIn 0.5s ease-in;
        }

        .empty-state-icon {
            width: 4rem;
            height: 4rem;
            margin: 0 auto 1.5rem;
            background: var(--gold);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .empty-state-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--gray-dark);
            margin-bottom: 0.5rem;
        }

        .empty-state-description {
            color: var(--gray-medium);
            margin-bottom: 2rem;
            font-size: 1.1rem;
            max-width: 500px;
            line-height: 1.5;
        }

        .empty-state-action {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--gold);
            color: white;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }

        .empty-state-action:hover {
            background: var(--gold-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3);
        }

        /* Mobile Cards */
        @media (max-width: 768px) {
            .desktop-table {
                display: none !important;
            }

            .mobile-cards {
                display: block !important;
            }

            .container {
                padding: 0.5rem;
            }

            .header-actions {
                flex-direction: column;
                gap: 1rem;
            }

            .header-actions>* {
                width: 100%;
            }

            .header-title {
                text-align: center;
                margin-bottom: 1rem;
            }

            .page-header h1 {
                font-size: 1.875rem;
            }

            .stats-badge {
                font-size: 0.75rem;
                padding: 0.25rem 0.75rem;
            }
        }

        /* Section Cards for Mobile */
        .section-card {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
            padding: 1rem;
            border-left: 4px solid var(--gold);
            transition: all 0.2s ease;
        }

        .section-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .section-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
        }

        .section-card-title {
            font-weight: 600;
            color: var(--gray-dark);
            font-size: 1.1rem;
            flex: 1;
        }

        .section-card-actions {
            display: flex;
            gap: 0.5rem;
            margin-left: 1rem;
        }

        .section-card-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            font-size: 0.875rem;
            color: var(--gray-medium);
        }

        .section-card-detail {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-weight: 500;
            color: var(--gray-dark);
            margin-bottom: 0.25rem;
            font-size: 0.8rem;
        }

        /* Year Headers */
        .year-header-mobile {
            background: var(--gray-100);
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin: 1rem 0 0.5rem 0;
            font-weight: 600;
            color: var(--gray-dark);
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background-color 0.2s ease;
        }

        .year-header-mobile:hover {
            background: var(--gray-200);
        }

        .collapsible-header {
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .collapsible-header:hover {
            background-color: var(--gray-50);
        }

        /* Table Improvements */
        .table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .mobile-table {
            min-width: 800px;
        }

        .table-row:hover {
            background-color: var(--gray-50);
        }

        /* No Results */
        .no-results {
            text-align: center;
            color: var(--gray-medium);
            padding: 3rem 2rem;
            font-size: 1rem;
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
            border-radius: 0.5rem;
            border: 2px dashed var(--gray-light);
        }

        /* Modal Responsiveness */
        @media (max-width: 640px) {
            .modal-content {
                margin: 1rem;
                max-height: 90vh;
                overflow-y: auto;
            }
        }
    </style>
</head>

<body class="bg-gray-50 font-sans antialiased">
    <div id="toast-container" class="toast-container"></div>

    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <header class="mb-8 slide-in-left">
            <div class="header-title">
                <h1 class="text-4xl font-bold text-gray-800">Section Management</h1>
                <p class="text-gray-600 mt-2">Manage sections for your department</p>
            </div>
        </header>

        <div class="bg-white rounded-xl shadow-lg fade-in">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center p-6 border-b border-gray-200 bg-gradient-to-r from-white to-gray-50 rounded-t-xl header-actions">
                <h3 class="text-xl font-bold text-gray-800 mb-4 lg:mb-0">Your Department's Sections</h3>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center space-y-2 sm:space-y-0 sm:space-x-4 w-full lg:w-auto">
                    <div class="current-semester-badge text-center">
                        <?php if ($currentSemester): ?>
                            <?php echo htmlspecialchars("{$currentSemester['semester_name']} {$currentSemester['academic_year']}"); ?>
                        <?php else: ?>
                            Current Semester Not Set
                        <?php endif; ?>
                    </div>
                    <span class="stats-badge text-center sm:text-left" id="sections-count">
                        <?php echo count($currentSemesterSections); ?> Sections
                    </span>
                    <button id="openAddModalBtn" class="btn-gold px-5 py-2 rounded-lg shadow-md font-medium w-full sm:w-auto flex items-center justify-center">
                        <i class="fas fa-plus mr-2"></i>Add Section
                    </button>
                    <button id="openReuseModalBtn" class="btn-gold px-5 py-2 rounded-lg shadow-md font-medium w-full sm:w-auto flex items-center justify-center">
                        <i class="fas fa-recycle mr-2"></i>Reuse Section
                    </button>
                </div>
            </div>

            <div class="p-6">
                <?php if (empty($currentSemesterSections)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <div class="empty-state-title">No Sections Yet</div>
                        <div class="empty-state-description">
                            Create your first section or reuse a previous one to start organizing students by year level and program.
                        </div>
                        <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-4">
                            <button class="empty-state-action" id="emptyStateAddBtn">
                                <i class="fas fa-plus"></i>
                                Create Your First Section
                            </button>
                            <button class="empty-state-action" id="emptyStateReuseBtn">
                                <i class="fas fa-recycle"></i>
                                Reuse Previous Section
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Desktop Table View -->
                    <div class="desktop-table table-container">
                        <table class="mobile-table min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-800 uppercase tracking-wider">Section Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-800 uppercase tracking-wider">Program</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-800 uppercase tracking-wider">Year Level</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-800 uppercase tracking-wider">Current Students</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-800 uppercase tracking-wider">Max Students</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-800 uppercase tracking-wider">Semester</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-800 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach (['1st Year', '2nd Year', '3rd Year', '4th Year'] as $yearLevel): ?>
                                    <?php if (!empty($groupedCurrentSections[$yearLevel])): ?>
                                        <tr class="collapsible-header bg-gray-100" data-year-level="<?php echo htmlspecialchars($yearLevel); ?>">
                                            <td colspan="7" class="px-6 py-3 text-sm font-semibold text-gray-800">
                                                <div class="flex items-center">
                                                    <i class="fas fa-chevron-down mr-2 transition-transform duration-200"></i>
                                                    <?php echo htmlspecialchars($yearLevel); ?> (<span class="section-count"><?php echo count($groupedCurrentSections[$yearLevel]); ?></span> Sections)
                                                </div>
                                            </td>
                                        </tr>
                            <tbody class="collapsible-content">
                                <?php foreach ($groupedCurrentSections[$yearLevel] as $section): ?>
                                    <tr class="table-row hover:bg-gray-50 transition-all duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800"><?php echo htmlspecialchars($section['section_name']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800"><?php echo htmlspecialchars($section['program_name']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800"><?php echo htmlspecialchars($section['year_level']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800"><?php echo htmlspecialchars($section['current_students']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800"><?php echo htmlspecialchars($section['max_students']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800"><?php echo htmlspecialchars($section['semester'] . ' ' . $section['academic_year']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <button class="action-btn edit-btn"
                                                    data-id="<?php echo $section['section_id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($section['section_name']); ?>"
                                                    data-year="<?php echo htmlspecialchars($section['year_level']); ?>"
                                                    data-max="<?php echo htmlspecialchars($section['max_students']); ?>"
                                                    data-current="<?php echo htmlspecialchars($section['current_students']); ?>">
                                                    <i class="fas fa-edit"></i>
                                                    <span class="action-tooltip">Edit Section</span>
                                                </button>
                                                <button class="action-btn remove-btn"
                                                    data-id="<?php echo $section['section_id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($section['section_name']); ?>">
                                                    <i class="fas fa-trash"></i>
                                                    <span class="action-tooltip">Delete Section</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    </tbody>
                        </table>
                    </div>

                    <!-- Mobile Cards View -->
                    <div class="mobile-cards hidden">
                        <?php foreach (['1st Year', '2nd Year', '3rd Year', '4th Year'] as $yearLevel): ?>
                            <?php if (!empty($groupedCurrentSections[$yearLevel])): ?>
                                <div class="year-header-mobile" data-year-level="<?php echo htmlspecialchars($yearLevel); ?>">
                                    <span><?php echo htmlspecialchars($yearLevel); ?> (<span class="section-count-mobile"><?php echo count($groupedCurrentSections[$yearLevel]); ?></span> Sections)</span>
                                    <i class="fas fa-chevron-down transition-transform duration-200"></i>
                                </div>
                                <div class="year-sections-mobile">
                                    <?php foreach ($groupedCurrentSections[$yearLevel] as $section): ?>
                                        <div class="section-card">
                                            <div class="section-card-header">
                                                <div class="section-card-title"><?php echo htmlspecialchars($section['section_name']); ?></div>
                                                <div class="section-card-actions">
                                                    <button class="action-btn edit-btn"
                                                        data-id="<?php echo $section['section_id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($section['section_name']); ?>"
                                                        data-year="<?php echo htmlspecialchars($section['year_level']); ?>"
                                                        data-current="<?php echo htmlspecialchars($section['current_students']); ?>"
                                                        data-max="<?php echo htmlspecialchars($section['max_students']); ?>">
                                                        <i class="fas fa-edit"></i>
                                                        <span class="action-tooltip">Edit</span>
                                                    </button>
                                                    <button class="action-btn remove-btn"
                                                        data-id="<?php echo $section['section_id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($section['section_name']); ?>">
                                                        <i class="fas fa-trash"></i>
                                                        <span class="action-tooltip">Delete</span>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="section-card-details">
                                                <div class="section-card-detail">
                                                    <span class="detail-label">Program</span>
                                                    <span><?php echo htmlspecialchars($section['program_name']); ?></span>
                                                </div>
                                                <div class="section-card-detail">
                                                    <span class="detail-label">Year Level</span>
                                                    <span><?php echo htmlspecialchars($section['year_level']); ?></span>
                                                </div>
                                                <div class="section-card-detail">
                                                    <span class="detail-label">Current Students</span>
                                                    <span><?php echo htmlspecialchars($section['current_students']); ?></span>
                                                </div>
                                                <div class="section-card-detail">
                                                    <span class="detail-label">Max Students</span>
                                                    <span><?php echo htmlspecialchars($section['max_students']); ?></span>
                                                </div>
                                                <div class="section-card-detail">
                                                    <span class="detail-label">Semester</span>
                                                    <span><?php echo htmlspecialchars($section['semester'] . ' ' . $section['academic_year']); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Add Section Modal -->
        <div id="add-modal" class="modal fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 transform modal-content scale-95">
                <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-gradient-to-r from-white to-gray-50 rounded-t-xl">
                    <h3 class="text-xl font-bold text-gray-800">Add New Section</h3>
                    <button id="closeAddModalBtn"
                        class="text-gray-500 hover:text-gray-700 focus:outline-none bg-gray-100 hover:bg-gray-200 rounded-full h-8 w-8 flex items-center justify-center transition-all duration-200"
                        aria-label="Close modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="addSectionForm" action="/chair/sections" method="POST">
                    <div class="p-6 space-y-4">
                        <div>
                            <label for="section_name" class="block text-sm font-medium text-gray-700 mb-1">Section Name</label>
                            <input type="text" id="section_name" name="section_name"
                                class="input-focus w-full px-4 py-2 border border-gray-300 rounded-lg"
                                required placeholder="e.g., BSIT-1A">
                        </div>
                        <div>
                            <label for="year_level" class="block text-sm font-medium text-gray-700 mb-1">Year Level</label>
                            <select id="year_level" name="year_level"
                                class="input-focus w-full px-4 py-2 border border-gray-300 rounded-lg"
                                required>
                                <option value="" disabled selected>Select year level</option>
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                            </select>
                        </div>
                        <div>
                            <label for="current_students" class="block text-sm font-medium text-gray-700 mb-1">Current Students</label>
                            <input type="number" id="current_students" name="current_students"
                                class="input-focus w-full px-4 py-2 border border-gray-300 rounded-lg"
                                required min="1" max="100" value="40">
                        </div>
                        <div>
                            <label for="max_students" class="block text-sm font-medium text-gray-700 mb-1">Max Students</label>
                            <input type="number" id="max_students" name="max_students"
                                class="input-focus w-full px-4 py-2 border border-gray-300 rounded-lg"
                                required min="1" max="100" value="40">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Semester & Academic Year</label>
                            <div class="current-semester-badge">
                                <?php echo $currentSemester ? htmlspecialchars($currentSemester['semester_name'] . ' ' . $currentSemester['academic_year']) : 'Not set'; ?>
                            </div>
                            <input type="hidden" name="add_section" value="1">
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3 p-6 border-t border-gray-200">
                        <button type="button" id="cancelAddBtn"
                            class="btn-outline px-5 py-2 rounded-lg font-medium w-full sm:w-auto">
                            Cancel
                        </button>
                        <button type="submit"
                            class="btn-gold px-5 py-2 rounded-lg shadow-md font-medium w-full sm:w-auto">
                            Add Section
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Section Modal -->
        <div id="edit-modal" class="modal fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 transform modal-content scale-95">
                <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-gradient-to-r from-white to-gray-50 rounded-t-xl">
                    <h3 class="text-xl font-bold text-gray-800">Edit Section</h3>
                    <button id="closeEditModalBtn"
                        class="text-gray-500 hover:text-gray-700 focus:outline-none bg-gray-100 hover:bg-gray-200 rounded-full h-8 w-8 flex items-center justify-center transition-all duration-200"
                        aria-label="Close modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="editSectionForm" action="/chair/sections" method="POST">
                    <div class="p-6 space-y-4">
                        <div>
                            <label for="edit_section_name" class="block text-sm font-medium text-gray-700 mb-1">Section Name</label>
                            <input type="text" id="edit_section_name" name="section_name"
                                class="input-focus w-full px-4 py-2 border border-gray-300 rounded-lg"
                                required placeholder="e.g., BSIT-1A">
                        </div>
                        <div>
                            <label for="edit_year_level" class="block text-sm font-medium text-gray-700 mb-1">Year Level</label>
                            <select id="edit_year_level" name="year_level"
                                class="input-focus w-full px-4 py-2 border border-gray-300 rounded-lg"
                                required>
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                            </select>
                        </div>
                        <div>
                            <label for="edit_current_students" class="block text-sm font-medium text-gray-700 mb-1">Current Students</label>
                            <input type="number" id="edit_current_students" name="current_students"
                                class="input-focus w-full px-4 py-2 border border-gray-300 rounded-lg"
                                required min="1" max="100">
                        </div>
                        <div>
                            <label for="edit_max_students" class="block text-sm font-medium text-gray-700 mb-1">Max Students</label>
                            <input type="number" id="edit_max_students" name="max_students"
                                class="input-focus w-full px-4 py-2 border border-gray-300 rounded-lg"
                                required min="1" max="100">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Semester</label>
                            <div class="current-semester-badge" id="edit_semester">
                                <?php echo $currentSemester ? htmlspecialchars($currentSemester['semester_name'] . ' ' . $currentSemester['academic_year']) : 'Not set'; ?>
                            </div>
                            <input type="hidden" id="edit_section_id" name="section_id">
                            <input type="hidden" name="edit_section" value="1">
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3 p-6 border-t border-gray-200">
                        <button type="button" id="cancelEditBtn"
                            class="btn-outline px-5 py-2 rounded-lg font-medium w-full sm:w-auto">
                            Cancel
                        </button>
                        <button type="submit"
                            class="btn-gold px-5 py-2 rounded-lg shadow-md font-medium w-full sm:w-auto">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Remove Section Modal -->
        <div id="remove-modal" class="modal fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 transform modal-content scale-95">
                <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-gradient-to-r from-white to-gray-50 rounded-t-xl">
                    <h3 class="text-xl font-bold text-gray-800">Remove Section</h3>
                    <button id="closeRemoveModalBtn"
                        class="text-gray-500 hover:text-gray-700 focus:outline-none bg-gray-100 hover:bg-gray-200 rounded-full h-8 w-8 flex items-center justify-center transition-all duration-200"
                        aria-label="Close modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="removeSectionForm" action="/chair/sections" method="POST">
                    <div class="p-6">
                        <p class="text-gray-700 mb-6">Are you sure you want to remove <strong id="remove-modal-section-name"></strong> from your department?</p>
                        <input type="hidden" id="remove-modal-section-id" name="section_id">
                        <input type="hidden" name="remove_section" value="1">
                    </div>
                    <div class="flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3 p-6 border-t border-gray-200">
                        <button type="button" id="cancelRemoveBtn"
                            class="btn-outline px-5 py-2 rounded-lg font-medium w-full sm:w-auto">
                            Cancel
                        </button>
                        <button type="submit"
                            class="bg-red-600 text-white px-5 py-2 rounded-lg hover:bg-red-700 transition-all duration-200 font-medium w-full sm:w-auto">
                            Confirm
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Reuse Section Modal -->
        <div id="reuse-modal" class="modal fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4 transform modal-content scale-95">
                <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-gradient-to-r from-white to-gray-50 rounded-t-xl">
                    <h3 class="text-xl font-bold text-gray-800">Reuse Previous Section</h3>
                    <button id="closeReuseModalBtn"
                        class="text-gray-500 hover:text-gray-700 focus:outline-none bg-gray-100 hover:bg-gray-200 rounded-full h-8 w-8 flex items-center justify-center transition-all duration-200"
                        aria-label="Close modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-6">
                    <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="section-search" class="block text-sm font-medium text-gray-700 mb-1">Search Sections</label>
                            <input type="text" id="section-search" class="search-input" placeholder="Search by section name...">
                        </div>
                        <div>
                            <label for="semester-filter" class="block text-sm font-medium text-gray-700 mb-1">Filter by Semester</label>
                            <select id="semester-filter" class="filter-select">
                                <option value="">All Semesters</option>
                                <?php foreach (array_keys($groupedPreviousSections) as $semesterKey): ?>
                                    <option value="<?php echo htmlspecialchars($semesterKey); ?>"><?php echo htmlspecialchars($semesterKey); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="year-filter" class="block text-sm font-medium text-gray-700 mb-1">Filter by Year Level</label>
                            <select id="year-filter" class="filter-select">
                                <option value="">All Year Levels</option>
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                            </select>
                        </div>
                    </div>
                    <div class="max-h-96 overflow-y-auto">
                        <?php if (empty($groupedPreviousSections)): ?>
                            <div class="no-results">
                                <div class="empty-state-icon mx-auto mb-4">
                                    <i class="fas fa-layer-group"></i>
                                </div>
                                <div class="empty-state-title mb-2">No Previous Sections</div>
                                <div class="empty-state-description mb-4">
                                    There are no previous sections available to reuse. Create a new section instead.
                                </div>
                                <button class="empty-state-action" id="createNewSectionBtn">
                                    <i class="fas fa-plus"></i>
                                    Create New Section
                                </button>
                            </div>
                        <?php else: ?>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 sticky top-0">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-800 uppercase tracking-wider">Section Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-800 uppercase tracking-wider">Program</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-800 uppercase tracking-wider">Year Level</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-800 uppercase tracking-wider">Current Students</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-800 uppercase tracking-wider">Max Students</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-800 uppercase tracking-wider">Semester</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-800 uppercase tracking-wider">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="previous-sections-table" class="bg-white divide-y divide-gray-200">
                                    <!-- Content will be populated dynamically -->
                                </tbody>
                            </table>
                            <div id="no-results-message" class="no-results hidden">
                                <i class="fas fa-search mb-2 text-2xl text-gray-400"></i>
                                <p>No sections found matching your criteria.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3 p-6 border-t border-gray-200">
                    <button type="button" id="cancelReuseBtn"
                        class="btn-outline px-5 py-2 rounded-lg font-medium w-full sm:w-auto">
                        Cancel
                    </button>
                    <button type="button" id="reuseAllBtn"
                        class="btn-gold px-5 py-2 rounded-lg shadow-md font-medium w-full sm:w-auto flex items-center justify-center">
                        <i class="fas fa-recycle mr-2"></i>Reuse All Sections
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize mobile view detection
            function checkMobileView() {
                if (window.innerWidth <= 768) {
                    document.querySelector('.desktop-table').classList.add('hidden');
                    document.querySelector('.mobile-cards').classList.remove('hidden');
                } else {
                    document.querySelector('.desktop-table').classList.remove('hidden');
                    document.querySelector('.mobile-cards').classList.add('hidden');
                }
            }

            // Check on load and resize
            checkMobileView();
            window.addEventListener('resize', checkMobileView);

            // Toast Notification System
            <?php if (isset($_SESSION['success'])): ?>
                showToast('<?php echo htmlspecialchars($_SESSION['success']); ?>', 'toast-success');
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                showToast('<?php echo htmlspecialchars($_SESSION['error']); ?>', 'toast-error');
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['info'])): ?>
                showToast('<?php echo htmlspecialchars($_SESSION['info']); ?>', 'toast-info');
                <?php unset($_SESSION['info']); ?>
            <?php endif; ?>

            function showToast(message, type) {
                const toast = document.createElement('div');
                toast.className = `toast ${type}`;
                toast.textContent = message;
                toast.setAttribute('role', 'alert');

                const container = document.getElementById('toast-container');
                container.appendChild(toast);

                // Auto remove after 5 seconds
                setTimeout(() => {
                    toast.style.opacity = '0';
                    setTimeout(() => toast.remove(), 300);
                }, 5000);
            }

            // Modal Management
            function openModal(modalId) {
                const modal = document.getElementById(modalId);
                const modalContent = modal.querySelector('.modal-content');
                modal.classList.remove('hidden');
                modalContent.classList.remove('scale-95');
                modalContent.classList.add('scale-100');
                document.body.style.overflow = 'hidden';
            }

            function closeModal(modalId) {
                const modal = document.getElementById(modalId);
                const modalContent = modal.querySelector('.modal-content');
                modalContent.classList.remove('scale-100');
                modalContent.classList.add('scale-95');
                setTimeout(() => {
                    modal.classList.add('hidden');
                    document.body.style.overflow = 'auto';
                }, 200);
            }

            // Add Section Modal
            document.getElementById('openAddModalBtn').addEventListener('click', () => openModal('add-modal'));
            document.getElementById('emptyStateAddBtn')?.addEventListener('click', () => openModal('add-modal'));
            document.getElementById('closeAddModalBtn').addEventListener('click', () => closeModal('add-modal'));
            document.getElementById('cancelAddBtn').addEventListener('click', () => closeModal('add-modal'));

            // Edit Section Modal
            function attachEditHandlers() {
                document.querySelectorAll('.edit-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const sectionId = this.dataset.id;
                        const sectionName = this.dataset.name;
                        const yearLevel = this.dataset.year;
                        const maxStudents = this.dataset.max;
                        const currentStudents = this.dataset.current;

                        document.getElementById('edit_section_id').value = sectionId;
                        document.getElementById('edit_section_name').value = sectionName;
                        document.getElementById('edit_year_level').value = yearLevel;
                        document.getElementById('edit_current_students').value = currentStudents;
                        document.getElementById('edit_max_students').value = maxStudents;

                        openModal('edit-modal');
                    });
                });
            }

            // Remove Section Modal
            function attachRemoveHandlers() {
                document.querySelectorAll('.remove-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const sectionId = this.dataset.id;
                        const sectionName = this.dataset.name;

                        document.getElementById('remove-modal-section-id').value = sectionId;
                        document.getElementById('remove-modal-section-name').textContent = sectionName;
                        openModal('remove-modal');
                    });
                });
            }

            // Attach handlers
            attachEditHandlers();
            attachRemoveHandlers();

            // Modal close handlers
            document.getElementById('closeEditModalBtn').addEventListener('click', () => closeModal('edit-modal'));
            document.getElementById('cancelEditBtn').addEventListener('click', () => closeModal('edit-modal'));
            document.getElementById('closeRemoveModalBtn').addEventListener('click', () => closeModal('remove-modal'));
            document.getElementById('cancelRemoveBtn').addEventListener('click', () => closeModal('remove-modal'));

            // Close modals on outside click
            document.querySelectorAll('.modal').forEach(modal => {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeModal(this.id);
                    }
                });
            });

            // Close modals on ESC key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    document.querySelectorAll('.modal').forEach(modal => {
                        if (!modal.classList.contains('hidden')) {
                            closeModal(modal.id);
                        }
                    });
                }
            });

            // Collapsible sections
            document.querySelectorAll('.collapsible-header, .year-header-mobile').forEach(header => {
                header.addEventListener('click', function() {
                    const content = this.nextElementSibling;
                    const icon = this.querySelector('i');

                    if (content.classList.contains('hidden')) {
                        content.classList.remove('hidden');
                        icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
                    } else {
                        content.classList.add('hidden');
                        icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
                    }
                });
            });

            // Reuse Section Modal
            document.getElementById('openReuseModalBtn').addEventListener('click', () => openModal('reuse-modal'));
            document.getElementById('emptyStateReuseBtn')?.addEventListener('click', () => openModal('reuse-modal'));
            document.getElementById('closeReuseModalBtn').addEventListener('click', () => closeModal('reuse-modal'));
            document.getElementById('cancelReuseBtn').addEventListener('click', () => closeModal('reuse-modal'));

            // Create new section from reuse modal
            document.getElementById('createNewSectionBtn')?.addEventListener('click', function() {
                closeModal('reuse-modal');
                openModal('add-modal');
            });

            // Reuse all sections
            document.getElementById('reuseAllBtn').addEventListener('click', function() {
                const semesterFilter = document.getElementById('semester-filter').value;
                if (!semesterFilter) {
                    showToast('Please select a semester to reuse all sections.', 'toast-error');
                    return;
                }

                if (confirm(`Are you sure you want to reuse all sections from ${semesterFilter}? This action cannot be undone.`)) {
                    this.disabled = true;
                    this.classList.add('loading');
                    this.innerHTML = 'Reusing Sections...';

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '/chair/sections';

                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'reuse_all_sections';
                    input.value = semesterFilter;

                    form.appendChild(input);
                    document.body.appendChild(form);
                    form.submit();
                }
            });

            // Search and filter functionality for reuse modal
            const sectionSearch = document.getElementById('section-search');
            const semesterFilter = document.getElementById('semester-filter');
            const yearFilter = document.getElementById('year-filter');

            function filterSections() {
                const searchTerm = sectionSearch.value.toLowerCase();
                const selectedSemester = semesterFilter.value;
                const selectedYear = yearFilter.value;

                // Implementation would depend on your data structure
                // This is a placeholder for the filtering logic
                console.log('Filtering sections:', {
                    searchTerm,
                    selectedSemester,
                    selectedYear
                });
            }

            let filterTimeout;

            function debouncedFilter() {
                clearTimeout(filterTimeout);
                filterTimeout = setTimeout(filterSections, 300);
            }

            sectionSearch.addEventListener('input', debouncedFilter);
            semesterFilter.addEventListener('change', debouncedFilter);
            yearFilter.addEventListener('change', debouncedFilter);
        });
    </script>
</body>

</html>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>