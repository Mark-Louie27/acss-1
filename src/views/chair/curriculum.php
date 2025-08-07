<?php
ob_start();

?>

<style>
    :root {
        --prmsu-gray-dark: #333333;
        --prmsu-gray: #666666;
        --prmsu-gray-light: #f5f5f5;
        --prmsu-gold: #EFBB0F;
        --prmsu-gold-light: #F9F3E5;
        --prmsu-white: #ffffff;
        --solid-green: #D1E7DD;
        --solid-red: #F8D7DA;
        --solid-black: #000000;
        --warning-yellow: #FFF3CD;
        --warning-yellow-text: #856404;
        --warning-orange: #FFE4CC;
        --warning-orange-text: #B45309;
    }

    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

    body {
        font-family: 'Inter', sans-serif;
        background-color: var(--prmsu-gray-light);
        color: var(--prmsu-gray-dark);
        line-height: 1.6;
    }

    #searchInput,
    #statusFilter,
    #yearFilter {
        transition: all 0.3s ease;
        border: 1px solid var(--prmsu-gray);
    }

    #searchInput:focus,
    #statusFilter:focus,
    #yearFilter:focus {
        border-color: var(--prmsu-gold);
        box-shadow: 0 0 0 3px rgba(239, 187, 15, 0.2);
    }

    .font-heading {
        font-weight: 600;
    }

    .transition-all {
        transition: all 0.3s ease-in-out;
    }

    .focus-gold:focus {
        outline: none;
        border-color: var(--prmsu-gold);
    }

    .btn-gold {
        background-color: var(--prmsu-gold);
        color: var(--prmsu-gray-dark);
        font-weight: 500;
        border: none;
        border-radius: 8px;
        padding: 10px 16px;
        transition: all 0.3s ease;
    }

    .btn-gold:hover {
        background-color: #E5B00E;
        transform: translateY(-2px);
        box-shadow: 0 6px 12px #0000001A;
    }

    .btn-gold:active {
        transform: translateY(0);
        box-shadow: 0 2px 4px #0000001A;
    }

    .btn-gold:disabled {
        background-color: var(--prmsu-gray);
        color: var(--prmsu-gray-light);
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    .btn-outline {
        background-color: var(--prmsu-white);
        border: 1px solid var(--prmsu-gray);
        color: var(--prmsu-gray);
        font-weight: 500;
        border-radius: 8px;
        padding: 10px 16px;
        transition: all 0.3s ease;
    }

    .btn-outline:hover {
        background-color: var(--prmsu-gray-light);
        border-color: var(--prmsu-gray-dark);
        color: var(--prmsu-gray-dark);
    }

    .card {
        background-color: var(--prmsu-white);
        border-radius: 12px;
        box-shadow: 0 4px 12px #0000000D;
        transition: box-shadow 0.3s ease;
    }

    .card:hover {
        box-shadow: 0 6px 16px #0000001A;
    }

    .table-header {
        background-color: var(--prmsu-gray-dark);
        color: var(--prmsu-white);
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .table-row {
        transition: background-color 0.3s ease;
    }

    .table-row:hover {
        background-color: var(--prmsu-gray-light);
    }

    .group-header {
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--prmsu-gray-dark);
        margin-bottom: 0.5rem;
    }

    .toast {
        opacity: 1;
        transition: opacity 0.3s ease-in-out;
    }

    .table-header th {
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.05em;
        background-color: var(--prmsu-gray-dark);
        color: var(--prmsu-white);
    }

    .modal.hidden {
        opacity: 0;
        pointer-events: none;
    }

    /* Modal Styles */
    .modal-overlay {
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(8px);
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 50;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }

    .modal-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    .modal-content {
        background: white;
        border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        width: 100%;
        max-width: 90vw;
        max-height: 90vh;
        overflow-y: auto;
        transform: scale(0.9) translateY(20px);
        transition: all 0.3s ease;
        position: relative;
    }

    .modal-overlay.active .modal-content {
        transform: scale(1) translateY(0);
    }

    /* Responsive Modal Sizes */
    .modal-sm {
        max-width: 500px;
    }

    .modal-md {
        max-width: 700px;
    }

    .modal-lg {
        max-width: 900px;
    }

    .modal-xl {
        max-width: 1200px;
    }

    .modal-full {
        max-width: 95vw;
    }

    input,
    select,
    textarea {
        border: 1px solid var(--prmsu-gray);
        border-radius: 8px;
        padding: 10px 12px;
        width: 100%;
        font-size: 14px;
        transition: all 0.3s ease;
    }

    input:focus,
    select:focus,
    textarea:focus {
        border-color: var(--prmsu-gold);
        outline: none;
    }

    textarea {
        resize: vertical;
    }

    ::-webkit-scrollbar {
        width: 6px;
    }

    ::-webkit-scrollbar-track {
        background: var(--prmsu-gray-light);
    }

    ::-webkit-scrollbar-thumb {
        background: var(--prmsu-gray);
        border-radius: 3px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: var(--prmsu-gray-dark);
    }

    .tab-button {
        padding: 10px 20px;
        border-bottom: 2px solid transparent;
        transition: all 0.3s ease;
    }

    .tab-button.active {
        border-bottom: 2px solid var(--prmsu-gold);
        color: var(--prmsu-gray-dark);
        font-weight: 500;
    }

    .tab-button:hover {
        color: var(--prmsu-gray-dark);
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    .error-text {
        color: #B91C1C;
        font-size: 0.875rem;
        margin-top: 0.25rem;
        display: none;
    }

    /* Warning and notification styles */
    .warning-notification {
        background-color: var(--warning-yellow);
        color: var(--warning-yellow-text);
        border: 1px solid #F6E05E;
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 16px;
        display: flex;
        align-items: flex-start;
        gap: 8px;
        animation: slideInDown 0.3s ease-out;
    }

    .duplicate-warning {
        background-color: var(--warning-orange);
        color: var(--warning-orange-text);
        border: 1px solid #F97316;
    }

    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .loading-spinner {
        border: 3px solid var(--prmsu-gray-light);
        border-top: 3px solid var(--prmsu-gold);
        border-radius: 50%;
        width: 20px;
        height: 20px;
        animation: spin 1s linear infinite;
        margin-right: 8px;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }
</style>

<!-- Display success/error messages -->
<?php if (isset($success)): ?>
    <div class="lg:max-w-4xl mx-auto mb-6 p-4 bg-[var(--solid-green)] text-green-800 rounded-lg flex items-center shadow-sm border-l-4 border-green-500 transition-all">
        <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
        </svg>
        <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>
<?php if (isset($error)): ?>
    <div class="lg:max-w-4xl mx-auto mb-6 p-4 bg-[var(--solid-red)] text-red-800 rounded-lg flex items-center shadow-sm border-l-4 border-red-500 transition-all">
        <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
        </svg>
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<!-- Main Content -->
<div class="flex flex-col p-6 min-h-screen">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8">
        <div>
            <h2 class="text-2xl sm:text-3xl font-heading text-prmsu-gray-dark">Curriculum Management</h2>
            <p class="text-prmsu-gray text-sm mt-1">Organize and manage academic curricula with ease</p>
        </div>
        <div class="flex space-x-3 mt-4 sm:mt-0">
            <button onclick="openModal('addCurriculumCourseModal')" class="btn-gold flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                <span>Add New</span>
            </button>
        </div>
    </div>

    <!-- Search and Filter Bar -->
    <div class="mb-6 flex flex-col sm:flex-row items-center space-y-3 sm:space-y-0 sm:space-x-4">
        <div class="relative flex-1 w-full">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-prmsu-gray" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <input type="text" placeholder="Search curricula..." id="searchInput"
                class="w-full pl-10 pr-4 py-3 border border-prmsu-gray rounded-lg focus-gold bg-prmsu-white shadow-sm">
        </div>

        <div class="flex space-x-3 w-full sm:w-auto">
            <select id="statusFilter" class="border border-prmsu-gray rounded-lg px-4 py-3 focus-gold bg-prmsu-white text-prmsu-gray-dark w-full sm:w-auto shadow-sm">
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="draft">Draft</option>
            </select>
        </div>
    </div>

    <!-- Curriculum Table -->
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full table-auto border-collapse">
                <thead>
                    <tr class="table-header">
                        <th class="px-4 sm:px-6 py-4 text-left">Curriculum Name</th>
                        <th class="px-4 sm:px-6 py-4 text-left">Courses</th>
                        <th class="px-4 sm:px-6 py-4 text-left">Total Units</th>
                        <th class="px-4 sm:px-6 py-4 text-left">Last Updated</th>
                        <th class="px-4 sm:px-6 py-4 text-left">Status</th>
                        <th class="px-4 sm:px-6 py-4 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody id="curriculaTableBody" class="divide-y divide-prmsu-gray-light">
                    <?php foreach ($curricula as $curriculum): ?>
                        <?php
                        $courseCountStmt = $db->prepare("SELECT COUNT(*) FROM curriculum_courses WHERE curriculum_id = :curriculum_id");
                        $courseCountStmt->execute([':curriculum_id' => $curriculum['curriculum_id']]);
                        $course_count = $courseCountStmt->fetchColumn();

                        $coursesStmt = $db->prepare("SELECT c.course_code, c.course_name, c.units, cc.year_level, cc.semester, cc.subject_type 
                                                    FROM curriculum_courses cc 
                                                    JOIN courses c ON cc.course_id = c.course_id 
                                                    WHERE cc.curriculum_id = :curriculum_id");
                        $coursesStmt->execute([':curriculum_id' => $curriculum['curriculum_id']]);
                        $curriculum_courses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <tr class="table-row" data-name="<?= htmlspecialchars($curriculum['curriculum_name']) ?>" data-year="<?= $curriculum['effective_year'] ?>" data-status="<?= strtolower($curriculum['status']) ?>">
                            <td class="px-4 sm:px-6 py-4 text-sm font-medium text-prmsu-gray-dark"><?= htmlspecialchars($curriculum['curriculum_name']) ?></td>
                            <td class="px-4 sm:px-6 py-4 text-sm text-prmsu-gray"><?= htmlspecialchars($course_count) ?> Courses</td>
                            <td class="px-4 sm:px-6 py-4 text-sm text-prmsu-gray"><?= htmlspecialchars($curriculum['total_units']) ?> Total Units</td>
                            <td class="px-4 sm:px-6 py-4 text-sm text-prmsu-gray"><?= htmlspecialchars($curriculum['updated_at']) ?></td>
                            <td class="px-4 sm:px-6 py-4 text-sm">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium <?= $curriculum['status'] === 'Active' ? 'bg-[var(--solid-green)] text-green-700' : 'bg-prmsu-gray-light text-prmsu-gray' ?>">
                                    <span class="w-2 h-2 mr-2 rounded-full <?= $curriculum['status'] === 'Active' ? 'bg-green-500' : 'bg-prmsu-gray' ?>"></span>
                                    <?= htmlspecialchars($curriculum['status']) ?>
                                </span>
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-sm font-medium">
                                <div class="flex space-x-3">
                                    <button onclick='openViewCoursesModal(<?= json_encode($curriculum_courses) ?>, "<?= htmlspecialchars($curriculum['curriculum_name']) ?>", <?= $curriculum['curriculum_id'] ?>)'
                                        class="text-blue-600 hover:text-blue-800 transition-all"
                                        title="View Courses">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <button onclick="openManageCoursesModal(<?= $curriculum['curriculum_id'] ?>, '<?= htmlspecialchars($curriculum['curriculum_name']) ?>')"
                                        class="text-green-600 hover:text-green-800 transition-all"
                                        title="Manage Courses">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                        </svg>
                                    </button>
                                    <button onclick="openEditCurriculumModal(<?= json_encode([
                                                                                    'id' => $curriculum['curriculum_id'],
                                                                                    'name' => htmlspecialchars($curriculum['curriculum_name']),
                                                                                    'code' => htmlspecialchars($curriculum['curriculum_code']),
                                                                                    'year' => $curriculum['effective_year'],
                                                                                    'description' => htmlspecialchars($curriculum['description']),
                                                                                    'status' => $curriculum['status'],
                                                                                ]) ?>)"
                                        class="text-blue-600 hover:text-blue-800 transition-all"
                                        title="Edit Curriculum">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="toggle_curriculum">
                                        <input type="hidden" name="curriculum_id" value="<?= $curriculum['curriculum_id'] ?>">
                                        <input type="hidden" name="status" value="<?= $curriculum['status'] ?>">
                                        <button type="submit"
                                            class="text-prmsu-gray hover:text-prmsu-gray-dark transition-all"
                                            title="<?= $curriculum['status'] === 'Active' ? 'Deactivate Curriculum' : 'Activate Curriculum' ?>">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $curriculum['status'] === 'Active' ? 'M10 9v6m4-6v6m-7-3h10' : 'M9 12h6m-3-3v6' ?>" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($curricula)): ?>
                        <tr>
                            <td colspan="6" class="px-4 sm:px-6 py-12 text-center text-prmsu-gray">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-16 h-16 text-prmsu-gray-light mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <p class="text-lg font-medium mb-2">No curricula found</p>
                                    <p class="text-sm">Start by adding a new curriculum</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Curriculum Modal -->
<div id="addCurriculumCourseModal" class="fixed inset-0 hidden">
    <div class="modal-overlay fixed inset-0 flex items-center justify-center p-4 bg-opacity-50 backdrop-blur-sm opacity-0 transition-opacity duration-300">
        <div class="modal-content bg-white rounded-xl shadow-2xl max-w-lg w-full transform translate-y-8 transition-transform duration-300 ease-out">
            <div class="p-6 border-b border-gray-200 flex justify-between items-center bg-gradient-to-r from-amber-50 to-white rounded-t-xl">
                <h3 class="text-xl font-bold text-gray-800 flex items-center">
                    <svg class="w-6 h-6 mr-3 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    <span id="modalTitle">Add New Curriculum</span>
                </h3>
                <button onclick="closeModal('addCurriculumCourseModal')" class="text-gray-500 hover:text-gray-700 transition-all transform hover:scale-110 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="p-6">
                <form method="POST" class="space-y-5">
                    <input type="hidden" name="action" value="add_curriculum">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Curriculum Name</label>
                        <input type="text" name="curriculum_name" placeholder="e.g. Bachelor of Science in Computer Science"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Curriculum Code</label>
                            <input type="text" name="curriculum_code" placeholder="e.g. BSCS-2025"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors" required>
                        </div>
                        <div class="form-group">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Effective Year</label>
                            <input type="number" name="effective_year" value="2025" min="2000" max="2100"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" rows="3" placeholder="Brief description of the curriculum..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors resize-none"></textarea>
                    </div>
                    <div class="mt-6 pt-4 border-t border-gray-200 flex justify-end space-x-3">
                        <button type="button" onclick="closeModal('addCurriculumCourseModal')"
                            class="px-5 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-5 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                            Create Curriculum
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Curriculum Modal -->
<div id="editCurriculumModal" class="fixed inset-0 hidden">
    <div class="modal-overlay fixed inset-0 flex items-center justify-center p-4">
        <div class="modal-content bg-white rounded-xl shadow-2xl max-w-md w-full">
            <div class="p-6 border-b border-prmsu-gray-light flex justify-between items-center">
                <h3 class="text-xl font-heading text-prmsu-gray-dark flex items-center">
                    <svg class="w-6 h-6 mr-2 text-prmsu-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit Curriculum
                </h3>
                <button onclick="closeModal('editCurriculumModal')" class="text-prmsu-gray hover:text-prmsu-gray-dark transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form method="POST" class="p-6">
                <input type="hidden" name="action" value="edit_curriculum">
                <input type="hidden" name="curriculum_id" id="editCurriculumId">
                <div class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-prmsu-gray-dark mb-1">Curriculum Name</label>
                        <input type="text" name="curriculum_name" id="editCurriculumName"
                            class="focus-gold" placeholder="e.g. Bachelor of Science in Computer Science" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-prmsu-gray-dark mb-1">Curriculum Code</label>
                            <input type="text" name="curriculum_code" id="editCurriculumCode"
                                class="focus-gold" placeholder="e.g. BSCS-2025" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-prmsu-gray-dark mb-1">Effective Year</label>
                            <input type="number" name="effective_year" id="editEffectiveYear"
                                class="focus-gold" min="2000" max="2100" required>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-prmsu-gray-dark mb-1">Description</label>
                        <textarea name="description" id="editDescription" rows="3"
                            class="focus-gold" placeholder="Provide a brief description of this curriculum..."></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-prmsu-gray-dark mb-1">Status</label>
                        <select name="status" id="editStatus" class="focus-gold">
                            <option value="Draft">Draft</option>
                            <option value="Active">Active</option>
                            <option value="Archived">Archived</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6 pt-5 border-t border-prmsu-gray-light flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('editCurriculumModal')"
                        class="btn-outline">Cancel</button>
                    <button type="submit" class="btn-gold">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Manage Courses Modal -->
<div id="manageCoursesModal" class="fixed inset-0 hidden">
    <div class="modal-overlay fixed inset-0 flex items-center justify-center p-4">
        <div class="modal-content bg-white rounded-xl shadow-2xl max-w-2xl w-full">
            <div class="p-6 border-b border-prmsu-gray-light flex justify-between items-center">
                <h3 class="text-xl font-heading text-prmsu-gray-dark flex items-center" id="manageCoursesTitle">
                    <svg class="w-6 h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    Manage Courses
                </h3>
                <button onclick="closeModal('manageCoursesModal')" class="text-prmsu-gray hover:text-prmsu-gray-dark transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-6">
                <div class="relative mb-4">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-prmsu-gray" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text" id="courseSearchInput" placeholder="Search courses..."
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors">
                </div>

                <div id="courseExistsNotification" class="hidden mb-4 p-3 rounded-lg bg-red-100 text-red-800 flex items-center">
                    <div id="courseCheckingLoader" class="hidden loading-spinner mr-2"></div>
                    <span id="courseExistsMessage">This course already exists in this curriculum.</span>
                </div>

                <form method="POST" class="space-y-5" id="manageCoursesForm">
                    <input type="hidden" name="action" value="add_course">
                    <input type="hidden" name="curriculum_id" id="curriculumIdInput">
                    <div>
                        <label class="block text-sm font-medium text-prmsu-gray-dark mb-1">Select Course</label>
                        <select name="course_id" id="courseSelect" class="focus-gold" required>
                            <option value="">-- Select Course --</option>
                            <?php
                            // Ensure $courses is set
                            if (!isset($courses)) {
                                $courses = [];
                                $coursesStmt = $db->prepare("SELECT course_id, course_code, course_name, units, subject_type FROM courses WHERE department_id = :department_id");
                                $coursesStmt->execute([':department_id' => $departmentId]);
                                $courses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);
                                error_log("Fetched courses at " . date('Y-m-d H:i:s') . ": " . print_r($courses, true));
                            }
                            // Generate options with detailed debugging
                            foreach ($courses as $index => $course) {
                                $subjectType = $course['subject_type'] ?? 'Unknown';
                                error_log("Course #$index - ID: {$course['course_id']}, Subject Type: $subjectType");
                                echo '<option value="' . htmlspecialchars($course['course_id']) . '" ' .
                                    'data-code="' . htmlspecialchars($course['course_code'] ?? '') . '" ' .
                                    'data-name="' . htmlspecialchars($course['course_name'] ?? '') . '" ' .
                                    'data-subject-type="' . htmlspecialchars($subjectType) . '">' .
                                    htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']) .
                                    '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-prmsu-gray-dark mb-1">Year Level</label>
                            <select name="year_level" class="focus-gold" required>
                                <option value="">--- Please Select Year Level ---</option>
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-prmsu-gray-dark mb-1">Semester</label>
                            <select name="semester" class="focus-gold" required>
                                <option value="">--- Please Select Semester ---</option>
                                <option value="1st">1st Semester</option>
                                <option value="2nd">2nd Semester</option>
                                <option value="Mid Year">Mid Year</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-prmsu-gray-dark mb-1">Subject Type</label>
                            <select name="subject_type" id="subjectTypeSelect" class="focus-gold" required disabled>
                                <option value="">-- Auto Set --</option>
                                <option value="Professional Course">Professional Course</option>
                                <option value="Major">Major</option>
                                <option value="Minor">Minor</option>
                                <option value="General Education">General Education</option>
                                <option value="Elective">Elective</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-6 pt-5 border-t border-prmsu-gray-light flex justify-end space-x-3">
                        <button type="button" onclick="closeModal('manageCoursesModal')"
                            class="btn-outline">Cancel</button>
                        <button type="submit" class="btn-gold" id="addCourseButton">Add Course</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- View Courses Modal -->
<div id="viewCoursesModal" class="fixed inset-0 hidden">
    <div class="modal-overlay fixed inset-0 flex items-center justify-center p-4">
        <div class="modal-content bg-white rounded-xl shadow-2xl max-w-4xl w-full">
            <div class="p-6 border-b border-prmsu-gray-light flex justify-between items-center">
                <h3 class="text-xl font-heading text-prmsu-gray-dark flex items-center" id="viewCoursesTitle">
                    <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    View Courses
                </h3>
                <button onclick="closeModal('viewCoursesModal')" class="text-prmsu-gray hover:text-prmsu-gray-dark transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-6 max-h-[60vh] overflow-y-auto">
                <div id="coursesContainer">
                    <!-- Courses will be populated dynamically via JavaScript -->
                </div>
                <div id="noCoursesMessage" class="hidden text-center text-prmsu-gray py-8">
                    <svg class="w-16 h-16 text-prmsu-gray-light mb-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p class="text-lg font-medium mb-2">No courses found</p>
                    <p class="text-sm">Add courses to this curriculum using the "Manage Courses" option.</p>
                </div>
            </div>
            <div class="p-6 border-t border-prmsu-gray-light flex justify-end">
                <button onclick="closeModal('viewCoursesModal')" class="btn-outline">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Global variables
    let duplicateCheckTimeout = null;

    // Open modal function
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        const modalContent = modal.querySelector('.modal-content');
        modal.classList.remove('hidden');
        modalContent.classList.remove('scale-95');
        modalContent.classList.add('scale-100');
        document.body.style.overflow = 'hidden';

        modal.classList.remove('hidden');

        const overlay = modal.querySelector('.modal-overlay');

        void modal.offsetWidth;

        overlay.classList.add('active');
        modalContent.classList.remove('translate-y-8');
        modalContent.classList.add('translate-y-0');
    }

    // Close modal function
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        const overlay = modal.querySelector('.modal-overlay');
        const content = modal.querySelector('.modal-content');

        overlay.classList.remove('active');
        content.classList.remove('translate-y-0');
        content.classList.add('translate-y-8');

        setTimeout(() => {
            modal.classList.add('hidden');
            // Reset form when closing manage courses modal
            if (modalId === 'manageCoursesModal') {
                resetManageCoursesForm();
            }
        }, 300);
    }

    // Reset manage courses form
    function resetManageCoursesForm() {
        const form = document.getElementById('manageCoursesForm');
        if (form) {
            form.reset();
        }
        hideAllNotifications();
        enableAddButton();
    }

    // Hide all notifications
    function hideAllNotifications() {
        document.getElementById('courseExistsNotification').classList.add('hidden');
        document.getElementById('courseCheckingLoader').classList.add('hidden');
    }

    // Enable/disable add button
    function enableAddButton(enabled = true) {
        const button = document.getElementById('addCourseButton');
        const buttonText = document.getElementById('addCourseButtonText');
        const buttonSpinner = document.getElementById('addCourseButtonSpinner');

        if (button) {
            button.disabled = !enabled;
            if (enabled) {
                buttonText.classList.remove('hidden');
                buttonSpinner.classList.add('hidden');
            } else {
                buttonText.classList.add('hidden');
                buttonSpinner.classList.remove('hidden');
            }
        }
    }

    // Enhanced toast notification function
    function showToast(message, type = 'success') {
        const bgColors = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            warning: 'bg-yellow-500',
            info: 'bg-blue-500'
        };

        const icons = {
            success: `<svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>`,
            error: `<svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>`,
            warning: `<svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>`,
            info: `<svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>`
        };

        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 px-4 py-3 rounded-lg shadow-lg text-white ${bgColors[type]} transition-all duration-300 z-50 flex items-center max-w-sm`;
        toast.innerHTML = `${icons[type]}${message}`;
        toast.style.transform = 'translateX(100%)';

        document.body.appendChild(toast);

        // Slide in
        setTimeout(() => {
            toast.style.transform = 'translateX(0)';
        }, 100);

        // Slide out and remove
        setTimeout(() => {
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    // Edit curriculum modal
    function openEditCurriculumModal(curriculum) {
        document.getElementById('editCurriculumId').value = curriculum.id;
        document.getElementById('editCurriculumName').value = curriculum.name;
        document.getElementById('editCurriculumCode').value = curriculum.code;
        document.getElementById('editEffectiveYear').value = curriculum.year;
        document.getElementById('editDescription').value = curriculum.description;
        document.getElementById('editStatus').value = curriculum.status;
        openModal('editCurriculumModal');
    }

    // Manage courses modal
    function openManageCoursesModal(curriculumId, curriculumName) {
        const curriculumIdInput = document.getElementById('curriculumIdInput');
        const manageCoursesTitle = document.getElementById('manageCoursesTitle');
        if (!curriculumIdInput || !manageCoursesTitle) return;

        curriculumIdInput.value = curriculumId;
        manageCoursesTitle.textContent = `Manage Courses for ${curriculumName}`;
        resetManageCoursesForm();
        openModal('manageCoursesModal');

        setTimeout(() => {
            const courseSearchInput = document.getElementById('courseSearchInput');
            if (courseSearchInput) courseSearchInput.focus();
        }, 300);
    }

    // Reset manage courses form
    function resetManageCoursesForm() {
        const form = document.getElementById('manageCoursesForm');
        if (form) form.reset();
        hideAllNotifications();
        enableAddButton();
    }

    // Hide all notifications
    function hideAllNotifications() {
        const courseExistsNotification = document.getElementById('courseExistsNotification');
        if (courseExistsNotification) courseExistsNotification.classList.add('hidden');
        const courseCheckingLoader = document.getElementById('courseCheckingLoader');
        if (courseCheckingLoader) courseCheckingLoader.classList.add('hidden');
    }

    // Enable/disable add button
    function enableAddButton(enabled = true) {
        const button = document.getElementById('addCourseButton');
        if (!button) return;
        button.disabled = !enabled;
    }

    // Enhanced course duplicate checking
    function checkCourseDuplicate(curriculumId, courseId) {
        if (!curriculumId || !courseId) {
            hideAllNotifications();
            enableAddButton();
            return;
        }

        // Show loading indicator
        hideAllNotifications();
        document.getElementById('courseCheckingLoader').classList.remove('hidden');
        enableAddButton(false);

        // Clear previous timeout
        if (duplicateCheckTimeout) {
            clearTimeout(duplicateCheckTimeout);
        }

        // Debounce the API call
        duplicateCheckTimeout = setTimeout(() => {
            fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `action=check_course_in_curriculum&curriculum_id=${encodeURIComponent(curriculumId)}&course_id=${encodeURIComponent(courseId)}`
                })
                .then(response => response.json())
                .then(data => {
                    hideAllNotifications();

                    if (data.exists) {
                        const courseSelect = document.getElementById('courseSelect');
                        const selectedOption = courseSelect.options[courseSelect.selectedIndex];
                        const courseCode = selectedOption.dataset.code;
                        const courseName = selectedOption.dataset.name;

                        document.getElementById('courseExistsMessage').innerHTML =
                            `<strong>${courseCode}</strong> - ${courseName} is already part of this curriculum. Please select a different course.`;
                        document.getElementById('courseExistsNotification').classList.remove('hidden');
                        enableAddButton(false);
                    } else {
                        enableAddButton(true);
                    }
                })
                .catch(error => {
                    console.error('Error checking course:', error);
                    hideAllNotifications();
                    enableAddButton(true);
                    showToast('Error checking for duplicates. Please try again.', 'error');
                });
        }, 500); // 500ms debounce
    }

    // View courses modal with grouping and remove functionality
    function openViewCoursesModal(courses, curriculumName, curriculumId) {
        const container = document.getElementById('coursesContainer');
        const noCoursesMessage = document.getElementById('noCoursesMessage');
        container.innerHTML = '';

        if (!courses || courses.length === 0) {
            noCoursesMessage.classList.remove('hidden');
            container.classList.add('hidden');
        } else {
            noCoursesMessage.classList.add('hidden');
            container.classList.remove('hidden');

            const groupedCourses = {};
            courses.forEach(course => {
                const key = `${course.year_level}-${course.semester}`;
                if (!groupedCourses[key]) {
                    groupedCourses[key] = [];
                }
                groupedCourses[key].push(course);
            });

            const yearOrder = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
            const semesterOrder = ['1st', '2nd', 'Mid Year'];
            const sortedKeys = Object.keys(groupedCourses).sort((a, b) => {
                const [yearA, semesterA] = a.split('-');
                const [yearB, semesterB] = b.split('-');
                const yearDiff = yearOrder.indexOf(yearA) - yearOrder.indexOf(yearB);
                if (yearDiff !== 0) return yearDiff;
                return semesterOrder.indexOf(semesterA) - semesterOrder.indexOf(semesterB);
            });

            sortedKeys.forEach(key => {
                const [yearLevel, semester] = key.split('-');
                const groupCourses = groupedCourses[key].sort((a, b) => a.course_code.localeCompare(b.course_code));

                const header = document.createElement('div');
                header.className = 'mt-6 mb-4';
                header.innerHTML = `
                    <h4 class="text-lg font-semibold text-prmsu-gray-dark flex items-center">
                        <svg class="w-5 h-5 mr-2 text-prmsu-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        ${yearLevel} - ${semester} Semester
                        <span class="ml-2 text-sm text-prmsu-gray bg-prmsu-gray-light px-2 py-1 rounded-full">${groupCourses.length} courses</span>
                    </h4>
                    <hr class="border-prmsu-gray-light mt-2">
                `;
                container.appendChild(header);

                const table = document.createElement('table');
                table.className = 'w-full table-auto border-collapse mb-6';
                table.innerHTML = `
                    <thead>
                        <tr class="table-header">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-prmsu-white">Course Code</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-prmsu-white">Course Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-prmsu-white">Units</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-prmsu-white">Subject Type</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-prmsu-white">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-prmsu-gray-light">
                        ${groupCourses.map(course => `
                            <tr class="table-row hover:bg-prmsu-gray-light transition-colors">
                                <td class="px-4 py-3 text-sm font-medium text-prmsu-gray-dark">${course.course_code || ''}</td>
                                <td class="px-4 py-3 text-sm text-prmsu-gray-dark">${course.course_name || ''}</td>
                                <td class="px-4 py-3 text-sm text-prmsu-gray">${course.units || ''}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        ${course.subject_type || ''}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm font-medium">
                                    <button class="remove-course-btn text-red-600 hover:text-red-800 hover:bg-red-50 transition-all p-2 rounded-lg"
                                        data-course-id="${course.course_id}"
                                        data-curriculum-id="${curriculumId}"
                                        data-course-name="${course.course_name}"
                                        data-course-code="${course.course_code}"
                                        title="Remove Course">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                `;
                container.appendChild(table);
            });
        }

        document.getElementById('viewCoursesTitle').textContent = `Courses for ${curriculumName}`;
        openModal('viewCoursesModal');
    }

    // Event listeners
    document.addEventListener('DOMContentLoaded', function() {
        // Course search functionality in Manage Courses modal
        const courseSearchInput = document.getElementById('courseSearchInput');
        if (courseSearchInput) {
            courseSearchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const options = document.querySelectorAll('#courseSelect option');

                options.forEach(option => {
                    if (option.value === '') return;

                    const text = option.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        option.style.display = '';
                    } else {
                        option.style.display = 'none';
                    }
                });
            });
        }

        // Course selection and subject type auto-set
        const courseSelect = document.getElementById('courseSelect');
        const subjectTypeSelect = document.getElementById('subjectTypeSelect');
        if (courseSelect && subjectTypeSelect) {
            courseSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const subjectType = selectedOption.dataset.subjectType;
                console.log('Selected Option:', selectedOption); // Log the entire option
                console.log('Selected subjectType:', subjectType); // Log the subjectType value

                if (subjectType) {
                    subjectTypeSelect.value = subjectType === 'Unknown' ? '' : subjectType;
                    console.log('Setting subjectTypeSelect to:', subjectTypeSelect.value);
                } else {
                    subjectTypeSelect.value = '';
                    console.log('No subjectType, setting to empty');
                }

                const curriculumId = document.getElementById('curriculumIdInput').value;
                const courseId = this.value;
                checkCourseDuplicate(curriculumId, courseId);

                if (!courseId || !curriculumId) {
                    document.getElementById('courseExistsNotification').classList.add('hidden');
                    document.getElementById('addCourseButton').disabled = false;
                    return;
                }

                fetch(window.location.href, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: `action=check_course_in_curriculum&curriculum_id=${encodeURIComponent(curriculumId)}&course_id=${encodeURIComponent(courseId)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.exists) {
                            const courseCode = selectedOption.dataset.code;
                            const courseName = selectedOption.dataset.name;

                            document.getElementById('courseExistsMessage').textContent =
                                `"${courseCode} - ${courseName}" already exists in this curriculum.`;
                            document.getElementById('courseExistsNotification').classList.remove('hidden');
                            document.getElementById('addCourseButton').disabled = true;
                        } else {
                            document.getElementById('courseExistsNotification').classList.add('hidden');
                            document.getElementById('addCourseButton').disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error checking course:', error);
                        document.getElementById('courseExistsNotification').classList.add('hidden');
                        document.getElementById('addCourseButton').disabled = false;
                    });
            });
        }

        // Handle form submission for adding courses with enhanced error handling
        const manageCoursesForm = document.getElementById('manageCoursesForm');
        if (manageCoursesForm) {
            manageCoursesForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const curriculumId = formData.get('curriculum_id');
                const courseId = formData.get('course_id');
                const courseSelect = document.getElementById('courseSelect');
                const selectedOption = courseSelect.options[courseSelect.selectedIndex];
                const courseName = selectedOption ? selectedOption.dataset.name : 'Unknown Course';

                // Show loading state
                enableAddButton(false);
                hideAllNotifications();

                fetch(window.location.href, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        enableAddButton(true);

                        if (data.success) {
                            showToast(`${courseName} added successfully!`, 'success');
                            closeModal('manageCoursesModal');

                            // Refresh view courses modal if it's open
                            if (!document.getElementById('viewCoursesModal').classList.contains('hidden')) {
                                const curriculumName = document.getElementById('viewCoursesTitle').textContent.replace('Courses for ', '');
                                fetchCoursesAndRefreshModal(curriculumId, curriculumName);
                            }

                            // Optionally refresh the page to update the curriculum table
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else if (data.error) {
                            showToast(data.error, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error adding course:', error);
                        enableAddButton(true);
                        showToast('Failed to add course. Please try again.', 'error');
                    });
            });
        }

        // Handle remove course button clicks with confirmation
        document.getElementById('viewCoursesModal').addEventListener('click', function(e) {
            if (e.target.closest('.remove-course-btn')) {
                const button = e.target.closest('.remove-course-btn');
                const courseId = button.dataset.courseId;
                const curriculumId = button.dataset.curriculumId;
                const courseName = button.dataset.courseName;
                const courseCode = button.dataset.courseCode;

                // Enhanced confirmation dialog
                if (confirm(`⚠️ Remove Course Confirmation\n\nAre you sure you want to remove "${courseCode} - ${courseName}" from this curriculum?\n\nThis action cannot be undone.`)) {
                    // Show loading state on button
                    button.innerHTML = `<div class="loading-spinner"></div>`;
                    button.disabled = true;

                    fetch(window.location.href, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: `action=remove_course&curriculum_id=${encodeURIComponent(curriculumId)}&course_id=${encodeURIComponent(courseId)}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showToast(`${courseCode} removed successfully!`, 'success');
                                const curriculumName = document.getElementById('viewCoursesTitle').textContent.replace('Courses for ', '');
                                fetchCoursesAndRefreshModal(curriculumId, curriculumName);
                            } else {
                                showToast(data.error || 'Failed to remove course.', 'error');
                                // Reset button
                                button.innerHTML = `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>`;
                                button.disabled = false;
                            }
                        })
                        .catch(error => {
                            console.error('Remove Course Error:', error);
                            showToast('Failed to remove course. Please try again.', 'error');
                            // Reset button
                            button.innerHTML = `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>`;
                            button.disabled = false;
                        });
                }
            }
        });

        // Search and filter functionality
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const tableBody = document.getElementById('curriculaTableBody');

        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase();
            const statusValue = statusFilter.value.toLowerCase();

            Array.from(tableBody.getElementsByTagName('tr')).forEach(row => {
                if (row.dataset.name) {
                    const name = row.dataset.name.toLowerCase();
                    const status = row.dataset.status;

                    const matchesSearch = name.includes(searchTerm);
                    const matchesStatus = !statusValue || status === statusValue;

                    row.style.display = matchesSearch && matchesStatus ? '' : 'none';
                }
            });
        }

        [searchInput, statusFilter].forEach(element => {
            if (element) {
                element.addEventListener('input', () => {
                    clearTimeout(window.filterTimeout);
                    window.filterTimeout = setTimeout(filterTable, 300);
                });
            }
        });

        // Initial filter application
        filterTable();

        // Close modals when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-overlay') && e.target.classList.contains('active')) {
                const modal = e.target.closest('[id$="Modal"]');
                if (modal) {
                    closeModal(modal.id);
                }
            }
        });

        // Close modals with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const activeModal = document.querySelector('.modal-overlay.active');
                if (activeModal) {
                    const modal = activeModal.closest('[id$="Modal"]');
                    if (modal) {
                        closeModal(modal.id);
                    }
                }
            }
        });
    });

    // Fetch updated courses and refresh modal
    function fetchCoursesAndRefreshModal(curriculumId, curriculumName) {
        fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `action=get_curriculum_courses&curriculum_id=${encodeURIComponent(curriculumId)}`
            })
            .then(response => response.json())
            .then(courses => {
                openViewCoursesModal(courses, curriculumName, curriculumId);
            })
            .catch(error => {
                console.error('Fetch Courses Error:', error);
                showToast('Failed to refresh courses.', 'error');
            });
    }
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>