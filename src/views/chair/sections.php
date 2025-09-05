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
            --white: #FFFFFF;
            --gray-dark: #4B5563;
            --gray-light: #E5E7EB;
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

        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .modal {
            transition: opacity 0.3s ease;
        }

        .modal.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .modal-content {
            transition: transform 0.3s ease;
        }

        .input-focus {
            transition: all 0.2s ease;
        }

        .input-focus:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
        }

        .btn-gold {
            background-color: var(--gold);
            color: var(--white);
        }

        .btn-gold:hover {
            background-color: #b8972e;
        }

        .tooltip {
            display: none;
        }

        .group:hover .tooltip {
            display: block;
        }

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

        .collapsible-header {
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .collapsible-header:hover {
            background-color: #f9fafb;
        }

        .previous-section-select {
            border-color: var(--gray-light);
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            font-size: 1rem;
            width: 100%;
            transition: border-color 0.2s ease;
        }

        .previous-section-select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
        }

        /* New style for pre-filled fields */
        .pre-filled {
            border-color: var(--gold);
            background-color: #fffbeb;
            transition: all 0.3s ease;
        }

        .pre-filled:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.3);
        }

        /* Responsive Design */

        /* Default: Desktop View */
        .desktop-table {
            display: block;
        }

        .mobile-cards {
            display: none;
        }

        @media (max-width: 1024px) {
            .container {
                padding: 1rem;
            }
        }

        @media (max-width: 768px) {
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

            /* Mobile Table Responsiveness */
            .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .mobile-table {
                min-width: 800px;
            }
        }

        @media (max-width: 640px) {
            .desktop-table {
                display: none !important;
            }

            .mobile-cards {
                display: block !important;
            }

            .section-card {
                background: white;
                border-radius: 0.75rem;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                margin-bottom: 1rem;
                padding: 1rem;
                border-left: 4px solid var(--gold);
            }

            .section-card-header {
                display: flex;
                justify-content: between;
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
                gap: 0.5rem;
                font-size: 0.875rem;
                color: #6B7280;
            }

            .section-card-detail {
                display: flex;
                flex-direction: column;
            }

            .detail-label {
                font-weight: 500;
                color: var(--gray-dark);
                margin-bottom: 0.25rem;
            }

            .year-header-mobile {
                background: #F3F4F6;
                padding: 0.75rem 1rem;
                border-radius: 0.5rem;
                margin: 1rem 0 0.5rem 0;
                font-weight: 600;
                color: var(--gray-dark);
                cursor: pointer;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .modal-content {
                margin: 1rem;
                max-height: 90vh;
                overflow-y: auto;
            }
        }

        /* Action Buttons - Icon Style */
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
            background-color: #3B82F6;
            color: white;
        }

        .edit-btn:hover {
            background-color: #2563EB;
        }

        .remove-btn {
            background-color: #EF4444;
            color: white;
        }

        .remove-btn:hover {
            background-color: #DC2626;
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

        /* Responsive Header */
        @media (max-width: 640px) {
            .page-header h1 {
                font-size: 1.875rem;
            }

            .stats-badge {
                font-size: 0.75rem;
                padding: 0.25rem 0.75rem;
            }
        }
    </style>
</head>

<body class="bg-gray-light font-sans antialiased">
    <div id="toast-container" class="fixed top-5 right-5 z-50"></div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- Header -->
        <header class="mb-8 slide-in-left page-header">
            <div class="header-title">
                <h1 class="text-4xl font-bold text-gray-dark">Section Management</h1>
                <p class="text-gray-dark mt-2">Manage sections for your department</p>
            </div>
        </header>

        <!-- Sections Table -->
        <div class="bg-white rounded-xl shadow-lg fade-in">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center p-6 border-b border-gray-light bg-gradient-to-r from-white to-gray-50 rounded-t-xl header-actions">
                <h3 class="text-xl font-bold text-gray-dark mb-4 lg:mb-0">Your Department's Sections</h3>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center space-y-2 sm:space-y-0 sm:space-x-4 w-full lg:w-auto">
                    <select id="semesterFilter" class="input-focus px-4 py-2 border border-gray-light rounded-lg focus:outline-none focus:ring-2 focus:ring-gold w-full sm:w-auto" disabled>
                        <?php
                        $currentSemesterName = $currentSemester['semester_name'] ?? '';
                        $currentAcademicYear = $currentSemester['academic_year'] ?? '';
                        if ($currentSemesterName && $currentAcademicYear): ?>
                            <option value="<?php echo htmlspecialchars($currentSemesterName); ?>" selected>
                                <?php echo htmlspecialchars("{$currentSemesterName} {$currentAcademicYear}"); ?>
                            </option>
                        <?php else: ?>
                            <option value="" selected>Current Semester Not Set</option>
                        <?php endif; ?>
                    </select>
                    <span class="stats-badge text-sm font-medium text-gray-dark bg-gray-light px-3 py-2 rounded-full text-center sm:text-left">
                        <?php echo array_sum(array_map('count', $groupedSections)); ?> Sections
                    </span>
                    <button id="openAddModalBtn" class="btn-gold px-5 py-2 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 font-medium w-full sm:w-auto">
                        <i class="fas fa-plus mr-2"></i>Add Section
                    </button>
                </div>
            </div>

            <div class="p-6">
                <!-- Desktop Table View -->
                <div class="desktop-table table-container">
                    <?php if (empty($groupedSections['1st Year']) && empty($groupedSections['2nd Year']) && empty($groupedSections['3rd Year']) && empty($groupedSections['4th Year'])): ?>
                        <div class="text-center py-8 text-gray-dark">
                            <i class="fas fa-layer-group text-gray-dark text-2xl mb-2"></i>
                            <p class="font-medium">No sections found in your department</p>
                            <p class="text-sm mt-1">Add a new section to get started</p>
                        </div>
                    <?php else: ?>
                        <table class="mobile-table min-w-full divide-y divide-gray-light">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-dark uppercase tracking-wider">Section Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-dark uppercase tracking-wider">Program</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-dark uppercase tracking-wider">Year Level</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-dark uppercase tracking-wider">Semester</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-dark uppercase tracking-wider">Academic Year</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-dark uppercase tracking-wider">Max Students</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-dark uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-light">
                                <?php foreach (['1st Year', '2nd Year', '3rd Year', '4th Year'] as $yearLevel): ?>
                                    <?php if (!empty($groupedSections[$yearLevel])): ?>
                                        <tr class="collapsible-header bg-gray-100" data-year-level="<?php echo htmlspecialchars($yearLevel); ?>">
                                            <td colspan="7" class="px-6 py-3 text-sm font-semibold text-gray-dark">
                                                <div class="flex items-center">
                                                    <i class="fas fa-chevron-down mr-2 transition-transform duration-200"></i>
                                                    <?php echo htmlspecialchars($yearLevel); ?> (<span class="section-count"><?php echo count($groupedSections[$yearLevel]); ?></span> Sections)
                                                </div>
                                            </td>
                                        </tr>
                            <tbody class="collapsible-content">
                                <?php foreach ($groupedSections[$yearLevel] as $section): ?>
                                    <tr class="hover:bg-gray-50 transition-all duration-200 section-row" data-semester="<?php echo htmlspecialchars($section['semester']); ?>">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-dark"><?php echo htmlspecialchars($section['section_name']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-dark"><?php echo htmlspecialchars($section['program_name']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-dark"><?php echo htmlspecialchars($section['year_level']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-dark"><?php echo htmlspecialchars($section['semester']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-dark"><?php echo htmlspecialchars($section['academic_year']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-dark"><?php echo htmlspecialchars($section['max_students']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <button class="action-btn edit-btn"
                                                    data-id="<?php echo $section['section_id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($section['section_name']); ?>"
                                                    data-year="<?php echo htmlspecialchars($section['year_level']); ?>"
                                                    data-max="<?php echo htmlspecialchars($section['max_students']); ?>">
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
                    <?php endif; ?>
                </div>

                <!-- Mobile Card View -->
                <div class="mobile-cards">
                    <?php if (empty($groupedSections['1st Year']) && empty($groupedSections['2nd Year']) && empty($groupedSections['3rd Year']) && empty($groupedSections['4th Year'])): ?>
                        <div class="text-center py-8 text-gray-dark">
                            <i class="fas fa-layer-group text-gray-dark text-2xl mb-2"></i>
                            <p class="font-medium">No sections found in your department</p>
                            <p class="text-sm mt-1">Add a new section to get started</p>
                        </div>
                    <?php else: ?>
                        <?php foreach (['1st Year', '2nd Year', '3rd Year', '4th Year'] as $yearLevel): ?>
                            <?php if (!empty($groupedSections[$yearLevel])): ?>
                                <div class="year-header-mobile" data-year-level="<?php echo htmlspecialchars($yearLevel); ?>">
                                    <span><?php echo htmlspecialchars($yearLevel); ?> (<span class="section-count-mobile"><?php echo count($groupedSections[$yearLevel]); ?></span> Sections)</span>
                                    <i class="fas fa-chevron-down transition-transform duration-200"></i>
                                </div>
                                <div class="year-sections-mobile">
                                    <?php foreach ($groupedSections[$yearLevel] as $section): ?>
                                        <div class="section-card section-row-mobile" data-semester="<?php echo htmlspecialchars($section['semester']); ?>">
                                            <div class="section-card-header">
                                                <div class="section-card-title"><?php echo htmlspecialchars($section['section_name']); ?></div>
                                                <div class="section-card-actions">
                                                    <button class="action-btn edit-btn"
                                                        data-id="<?php echo $section['section_id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($section['section_name']); ?>"
                                                        data-year="<?php echo htmlspecialchars($section['year_level']); ?>"
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
                                                    <span class="detail-label">Semester</span>
                                                    <span><?php echo htmlspecialchars($section['semester']); ?></span>
                                                </div>
                                                <div class="section-card-detail">
                                                    <span class="detail-label">Academic Year</span>
                                                    <span><?php echo htmlspecialchars($section['academic_year']); ?></span>
                                                </div>
                                                <div class="section-card-detail">
                                                    <span class="detail-label">Max Students</span>
                                                    <span><?php echo htmlspecialchars($section['max_students']); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Add Section Modal -->
        <div id="add-modal" class="modal fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 transform modal-content scale-95">
                <div class="flex justify-between items-center p-6 border-b border-gray-light bg-gradient-to-r from-white to-gray-50 rounded-t-xl">
                    <h3 class="text-xl font-bold text-gray-dark">Add New Section</h3>
                    <button id="closeAddModalBtn"
                        class="text-gray-dark hover:text-gray-700 focus:outline-none bg-gray-light hover:bg-gray-200 rounded-full h-8 w-8 flex items-center justify-center transition-all duration-200"
                        aria-label="Close modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="addSectionForm" action="/chair/sections" method="POST">
                    <div class="p-6">
                        <div class="mb-4">
                            <label for="reuse_section" class="block text-sm font-medium text-gray-dark mb-1">Reuse Previous Section</label>
                            <select id="reuse_section" name="reuse_section_id" class="previous-section-select" onchange="populateFromPrevious(this)">
                                <option value="">Select a previous section</option>
                                <?php foreach ($previousSections as $prevSection): ?>
                                    <option value="<?php echo htmlspecialchars($prevSection['section_id']); ?>">
                                        <?php echo htmlspecialchars("{$prevSection['section_name']} - {$prevSection['year_level']} - {$prevSection['semester']} {$prevSection['academic_year']}"); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Select to pre-fill fields (editable after selection).</p>
                        </div>
                        <div class="mb-4">
                            <label for="section_name" class="block text-sm font-medium text-gray-dark mb-1">Section Name</label>
                            <input type="text" id="section_name" name="section_name"
                                class="input-focus w-full px-4 py-2 border border-gray-light rounded-lg focus:outline-none focus:ring-2 focus:ring-gold pre-filled"
                                required placeholder="e.g., BSIT-1A">
                        </div>
                        <div class="mb-4">
                            <label for="year_level" class="block text-sm font-medium text-gray-dark mb-1">Year Level</label>
                            <select id="year_level" name="year_level"
                                class="input-focus w-full px-4 py-2 border border-gray-light rounded-lg focus:outline-none focus:ring-2 focus:ring-gold pre-filled"
                                required>
                                <option value="" disabled selected>Select year level</option>
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="max_students" class="block text-sm font-medium text-gray-dark mb-1">Max Students</label>
                            <input type="number" id="max_students" name="max_students"
                                class="input-focus w-full px-4 py-2 border border-gray-light rounded-lg focus:outline-none focus:ring-2 focus:ring-gold pre-filled"
                                required min="1" max="100" value="40">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-dark mb-1">Semester & Academic Year</label>
                            <p class="text-gray-dark" id="current_semester_display">
                                <?php echo $currentSemester ? htmlspecialchars($currentSemester['semester_name'] . ' ' . $currentSemester['academic_year']) : 'Not set'; ?>
                            </p>
                            <input type="hidden" name="add_section" value="1">
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3 p-6 border-t border-gray-light">
                        <button type="button" id="cancelAddBtn"
                            class="bg-gray-light text-gray-dark px-5 py-3 rounded-lg hover:bg-gray-200 transition-all duration-200 font-medium w-full sm:w-auto">
                            Cancel
                        </button>
                        <button type="submit"
                            class="btn-gold px-5 py-3 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 font-medium w-full sm:w-auto">
                            Add Section
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Section Modal -->
        <div id="edit-modal" class="modal fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 transform modal-content scale-95">
                <div class="flex justify-between items-center p-6 border-b border-gray-light bg-gradient-to-r from-white to-gray-50 rounded-t-xl">
                    <h3 class="text-xl font-bold text-gray-dark">Edit Section</h3>
                    <button id="closeEditModalBtn"
                        class="text-gray-dark hover:text-gray-700 focus:outline-none bg-gray-light hover:bg-gray-200 rounded-full h-8 w-8 flex items-center justify-center transition-all duration-200"
                        aria-label="Close modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="editSectionForm" action="/chair/sections" method="POST">
                    <div class="p-6">
                        <div class="mb-4">
                            <label for="edit_section_name" class="block text-sm font-medium text-gray-dark mb-1">Section Name</label>
                            <input type="text" id="edit_section_name" name="section_name"
                                class="input-focus w-full px-4 py-2 border border-gray-light rounded-lg focus:outline-none focus:ring-2 focus:ring-gold"
                                required placeholder="e.g., BSIT-1A">
                        </div>
                        <div class="mb-4">
                            <label for="edit_year_level" class="block text-sm font-medium text-gray-dark mb-1">Year Level</label>
                            <select id="edit_year_level" name="year_level"
                                class="input-focus w-full px-4 py-2 border border-gray-light rounded-lg focus:outline-none focus:ring-2 focus:ring-gold"
                                required>
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="edit_max_students" class="block text-sm font-medium text-gray-dark mb-1">Max Students</label>
                            <input type="number" id="edit_max_students" name="max_students"
                                class="input-focus w-full px-4 py-2 border border-gray-light rounded-lg focus:outline-none focus:ring-2 focus:ring-gold"
                                required min="1" max="100">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-dark mb-1">Semester</label>
                            <p class="text-gray-dark" id="edit_semester"></p>
                            <input type="hidden" id="edit_section_id" name="section_id">
                            <input type="hidden" name="edit_section" value="1">
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3 p-6 border-t border-gray-light">
                        <button type="button" id="cancelEditBtn"
                            class="bg-gray-light text-gray-dark px-5 py-3 rounded-lg hover:bg-gray-200 transition-all duration-200 font-medium w-full sm:w-auto">
                            Cancel
                        </button>
                        <button type="submit"
                            class="btn-gold px-5 py-3 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 font-medium w-full sm:w-auto">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Remove Section Modal -->
        <div id="remove-modal" class="modal fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 transform modal-content scale-95">
                <div class="flex justify-between items-center p-6 border-b border-gray-light bg-gradient-to-r from-white to-gray-50 rounded-t-xl">
                    <h3 class="text-xl font-bold text-gray-dark">Remove Section</h3>
                    <button id="closeRemoveModalBtn"
                        class="text-gray-dark hover:text-gray-700 focus:outline-none bg-gray-light hover:bg-gray-200 rounded-full h-8 w-8 flex items-center justify-center transition-all duration-200"
                        aria-label="Close modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="removeSectionForm" action="/chair/sections" method="POST">
                    <div class="p-6">
                        <p class="text-gray-dark mb-6">Are you sure you want to remove <strong id="remove-modal-section-name"></strong> from your department?</p>
                        <input type="hidden" id="remove-modal-section-id" name="section_id">
                        <input type="hidden" name="remove_section" value="1">
                    </div>
                    <div class="flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3 p-6 border-t border-gray-light">
                        <button type="button" id="cancelRemoveBtn"
                            class="bg-gray-light text-gray-dark px-5 py-3 rounded-lg hover:bg-gray-200 transition-all duration-200 font-medium w-full sm:w-auto">
                            Cancel
                        </button>
                        <button type="submit"
                            class="bg-red-600 text-white px-5 py-3 rounded-lg hover:bg-red-700 transition-all duration-200 font-medium w-full sm:w-auto">
                            Confirm
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Semester Filter (Locked to Current Semester)
        const semesterFilter = document.getElementById('semesterFilter');
        const currentSemester = '<?php echo $currentSemester ? htmlspecialchars($currentSemester['semester_name']) : ""; ?>';
        const sectionRows = document.querySelectorAll('.section-row');
        const sectionRowsMobile = document.querySelectorAll('.section-row-mobile');
        const yearHeaders = document.querySelectorAll('.collapsible-header');
        const yearHeadersMobile = document.querySelectorAll('.year-header-mobile');
        const sectionCountSpan = document.querySelector('.stats-badge');

        function filterByCurrentSemester() {
            let visibleSections = 0;

            // Filter desktop table rows
            sectionRows.forEach(row => {
                const semester = row.dataset.semester;
                if (semester === currentSemester) {
                    row.style.display = '';
                    visibleSections++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Filter mobile card rows
            sectionRowsMobile.forEach(row => {
                const semester = row.dataset.semester;
                if (semester === currentSemester) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });

            // Update desktop year headers
            yearHeaders.forEach(header => {
                const yearLevel = header.dataset.yearLevel;
                const yearSections = Array.from(sectionRows).filter(row =>
                    row.closest('tbody').previousElementSibling.dataset.yearLevel === yearLevel
                );
                const visibleYearSections = yearSections.filter(row => row.style.display !== 'none').length;

                header.querySelector('.section-count').textContent = visibleYearSections;
                header.style.display = visibleYearSections > 0 ? '' : 'none';

                const content = header.nextElementSibling;
                if (visibleYearSections > 0 && content.classList.contains('hidden')) {
                    content.classList.remove('hidden');
                    header.querySelector('.fas').classList.remove('fa-chevron-down');
                    header.querySelector('.fas').classList.add('fa-chevron-up');
                }
            });

            // Update mobile year headers
            yearHeadersMobile.forEach(header => {
                const yearLevel = header.dataset.yearLevel;
                const yearSectionsMobile = header.nextElementSibling.querySelectorAll('.section-row-mobile');
                const visibleMobileSections = Array.from(yearSectionsMobile).filter(row => row.style.display !== 'none').length;

                header.querySelector('.section-count-mobile').textContent = visibleMobileSections;
                header.style.display = visibleMobileSections > 0 ? '' : 'none';
                header.nextElementSibling.style.display = visibleMobileSections > 0 ? '' : 'none';
            });

            if (sectionCountSpan) {
                sectionCountSpan.textContent = `${visibleSections} Sections`;
            }
        }

        // Add Section Modal and Previous Section Logic
        document.addEventListener('DOMContentLoaded', () => {
            filterByCurrentSemester();

            // Toast Notifications
            <?php if (isset($success)): ?>
                showToast('<?php echo htmlspecialchars($success); ?>', 'bg-green-500');
            <?php endif; ?>
            <?php if (isset($error)): ?>
                showToast('<?php echo htmlspecialchars($error); ?>', 'bg-red-500');
            <?php endif; ?>

            function showToast(message, bgColor) {
                const toast = document.createElement('div');
                toast.className = `toast ${bgColor} text-white px-4 py-2 rounded-lg shadow-lg`;
                toast.textContent = message;
                toast.setAttribute('role', 'alert');
                document.getElementById('toast-container').appendChild(toast);
                setTimeout(() => {
                    toast.style.opacity = '0';
                    setTimeout(() => toast.remove(), 300);
                }, 5000);
            }

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

            document.getElementById('openAddModalBtn').addEventListener('click', () => {
                openModal('add-modal');
                document.getElementById('reuse_section').value = '';
                populateFromPrevious(document.getElementById('reuse_section')); // Reset fields
            });

            document.getElementById('closeAddModalBtn').addEventListener('click', () => {
                closeModal('add-modal');
            });

            document.getElementById('cancelAddBtn').addEventListener('click', () => {
                closeModal('add-modal');
            });

            function populateFromPrevious(select) {
                const sectionNameInput = document.getElementById('section_name');
                const yearLevelSelect = document.getElementById('year_level');
                const maxStudentsInput = document.getElementById('max_students');
                const semesterDisplay = document.getElementById('current_semester_display');

                // Remove pre-filled class initially
                sectionNameInput.classList.remove('pre-filled');
                yearLevelSelect.classList.remove('pre-filled');
                maxStudentsInput.classList.remove('pre-filled');

                if (select.value) {
                    const section = <?php echo json_encode($previousSections); ?>.find(s => s.section_id == select.value);
                    if (section) {
                        sectionNameInput.value = section.section_name || '';
                        sectionNameInput.classList.add('pre-filled');

                        yearLevelSelect.value = section.year_level || '';
                        yearLevelSelect.classList.add('pre-filled');

                        maxStudentsInput.value = section.max_students || 40;
                        maxStudentsInput.classList.add('pre-filled');
                    }
                } else {
                    sectionNameInput.value = '';
                    yearLevelSelect.value = '';
                    maxStudentsInput.value = '40';
                }
                // Ensure semester display reflects current semester
                semesterDisplay.textContent = '<?php echo $currentSemester ? htmlspecialchars($currentSemester['semester_name'] . ' ' . $currentSemester['academic_year']) : "Not set"; ?>';
            }

            // Edit Section Modal - Handle both desktop and mobile buttons
            function attachEditHandlers() {
                document.querySelectorAll('.edit-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const sectionId = btn.dataset.id;
                        const sectionName = btn.dataset.name;
                        const yearLevel = btn.dataset.year;
                        const maxStudents = btn.dataset.max;

                        // Get semester info from the row
                        let semester;
                        const desktopRow = btn.closest('tr');
                        if (desktopRow) {
                            semester = desktopRow.querySelector('td:nth-child(4)').textContent + ' ' +
                                desktopRow.querySelector('td:nth-child(5)').textContent;
                        } else {
                            // Mobile card view
                            const mobileCard = btn.closest('.section-card');
                            const semesterDetail = mobileCard.querySelector('.section-card-detail:nth-child(3) span:last-child').textContent;
                            const yearDetail = mobileCard.querySelector('.section-card-detail:nth-child(4) span:last-child').textContent;
                            semester = semesterDetail + ' ' + yearDetail;
                        }

                        document.getElementById('edit_section_id').value = sectionId;
                        document.getElementById('edit_section_name').value = sectionName;
                        document.getElementById('edit_year_level').value = yearLevel;
                        document.getElementById('edit_max_students').value = maxStudents;
                        document.getElementById('edit_semester').textContent = semester;

                        openModal('edit-modal');
                    });
                });
            }

            attachEditHandlers();

            document.getElementById('closeEditModalBtn').addEventListener('click', () => {
                closeModal('edit-modal');
            });

            document.getElementById('cancelEditBtn').addEventListener('click', () => {
                closeModal('edit-modal');
            });

            // Remove Section Modal - Handle both desktop and mobile buttons
            function attachRemoveHandlers() {
                document.querySelectorAll('.remove-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const sectionId = btn.dataset.id;
                        const sectionName = btn.dataset.name;
                        document.getElementById('remove-modal-section-id').value = sectionId;
                        document.getElementById('remove-modal-section-name').textContent = sectionName;
                        openModal('remove-modal');
                    });
                });
            }

            attachRemoveHandlers();

            document.getElementById('closeRemoveModalBtn').addEventListener('click', () => {
                closeModal('remove-modal');
            });

            document.getElementById('cancelRemoveBtn').addEventListener('click', () => {
                closeModal('remove-modal');
            });

            // Close modals on backdrop click
            ['add-modal', 'edit-modal', 'remove-modal'].forEach(modalId => {
                const modal = document.getElementById(modalId);
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) closeModal(modalId);
                });
            });

            // Close modals on ESC key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    ['add-modal', 'edit-modal', 'remove-modal'].forEach(modalId => {
                        const modal = document.getElementById(modalId);
                        if (!modal.classList.contains('hidden')) closeModal(modalId);
                    });
                }
            });

            // Collapsible Year Level Sections - Desktop
            document.querySelectorAll('.collapsible-header').forEach(header => {
                header.addEventListener('click', () => {
                    const content = header.nextElementSibling;
                    const icon = header.querySelector('.fas');
                    content.classList.toggle('hidden');
                    icon.classList.toggle('fa-chevron-down');
                    icon.classList.toggle('fa-chevron-up');
                });
            });

            // Collapsible Year Level Sections - Mobile
            document.querySelectorAll('.year-header-mobile').forEach(header => {
                header.addEventListener('click', () => {
                    const content = header.nextElementSibling;
                    const icon = header.querySelector('.fas');

                    if (content.style.display === 'none' || !content.style.display) {
                        content.style.display = 'block';
                        icon.classList.remove('fa-chevron-down');
                        icon.classList.add('fa-chevron-up');
                    } else {
                        content.style.display = 'none';
                        icon.classList.remove('fa-chevron-up');
                        icon.classList.add('fa-chevron-down');
                    }
                });
            });

            // Handle window resize to ensure proper display
            window.addEventListener('resize', () => {
                filterByCurrentSemester();
            });
        });

        // Make populateFromPrevious globally accessible
        window.populateFromPrevious = function(select) {
            const sectionNameInput = document.getElementById('section_name');
            const yearLevelSelect = document.getElementById('year_level');
            const maxStudentsInput = document.getElementById('max_students');
            const semesterDisplay = document.getElementById('current_semester_display');

            // Remove pre-filled class initially
            sectionNameInput.classList.remove('pre-filled');
            yearLevelSelect.classList.remove('pre-filled');
            maxStudentsInput.classList.remove('pre-filled');

            if (select.value) {
                const section = <?php echo json_encode($previousSections); ?>.find(s => s.section_id == select.value);
                if (section) {
                    sectionNameInput.value = section.section_name || '';
                    sectionNameInput.classList.add('pre-filled');

                    yearLevelSelect.value = section.year_level || '';
                    yearLevelSelect.classList.add('pre-filled');

                    maxStudentsInput.value = section.max_students || 40;
                    maxStudentsInput.classList.add('pre-filled');
                }
            } else {
                sectionNameInput.value = '';
                yearLevelSelect.value = ''; 
                maxStudentsInput.value = '40';
            }
            // Ensure semester display reflects current semester
            semesterDisplay.textContent = '<?php echo $currentSemester ? htmlspecialchars($currentSemester['semester_name'] . ' ' . $currentSemester['academic_year']) : "Not set"; ?>';
        };
    </script>
</body>

</html>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>