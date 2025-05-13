<?php
ob_start();

// Ensure $curricula is set
if (!isset($curricula)) {
    $curricula = [];
}

// Ensure $courses is set
if (!isset($courses)) {
    $courses = [];
    $coursesStmt = $db->prepare("SELECT course_id, course_code, course_name, units FROM courses WHERE department_id = :department_id");
    $coursesStmt->execute([':department_id' => $departmentId]);
    $courses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Add this to your existing PHP code, near the other AJAX handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check_course_in_curriculum') {
    header('Content-Type: application/json');
    $curriculum_id = intval($_POST['curriculum_id'] ?? 0);
    $course_id = intval($_POST['course_id'] ?? 0);
    $response = ['exists' => false];

    if ($curriculum_id > 0 && $course_id > 0) {
        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM curriculum_courses 
                                WHERE curriculum_id = :curriculum_id AND course_id = :course_id");
            $stmt->execute([':curriculum_id' => $curriculum_id, ':course_id' => $course_id]);
            $count = $stmt->fetchColumn();
            $response['exists'] = $count > 0;
        } catch (PDOException $e) {
            $response['error'] = 'Error checking course: ' . htmlspecialchars($e->getMessage());
        }
    }

    echo json_encode($response);
    exit;
}

// Handle AJAX request for fetching curriculum courses
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_curriculum_courses') {
    header('Content-Type: application/json');
    $curriculum_id = intval($_POST['curriculum_id'] ?? 0);

    if ($curriculum_id < 1) {
        echo json_encode(['error' => 'Invalid curriculum ID']);
        exit;
    }

    try {
        $coursesStmt = $db->prepare("SELECT c.course_id, c.course_code, c.course_name, c.units, cc.year_level, cc.semester, cc.subject_type 
                                    FROM curriculum_courses cc 
                                    JOIN courses c ON cc.course_id = c.course_id 
                                    WHERE cc.curriculum_id = :curriculum_id");
        $coursesStmt->execute([':curriculum_id' => $curriculum_id]);
        $courses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($courses);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Error fetching courses: ' . htmlspecialchars($e->getMessage())]);
    }
    exit;
}

// Handle AJAX request for course code validation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check_course_code') {
    header('Content-Type: application/json');
    $course_code = trim($_POST['course_code'] ?? '');
    $response = ['exists' => false, 'message' => ''];

    if (!empty($course_code)) {
        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM courses WHERE course_code = :code AND department_id = :dept");
            $stmt->execute([':code' => $course_code, ':dept' => $departmentId]);
            $count = $stmt->fetchColumn();
            if ($count > 0) {
                $response['exists'] = true;
                $response['message'] = 'This course code already exists.';
            }
        } catch (PDOException $e) {
            $response['message'] = 'Error checking course code: ' . htmlspecialchars($e->getMessage());
        }
    }

    echo json_encode($response);
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'add_curriculum') {
                // Add new curriculum
                $curriculum_name = trim($_POST['curriculum_name'] ?? '');
                $curriculum_code = trim($_POST['curriculum_code'] ?? '');
                $effective_year = intval($_POST['effective_year'] ?? 0);
                $description = trim($_POST['description'] ?? '');
                $total_units = 0; // Will be updated when courses are added

                $errors = [];
                if (empty($curriculum_name)) $errors[] = "Curriculum name is required.";
                if (empty($curriculum_code)) $errors[] = "Curriculum code is required.";
                if ($effective_year < 2000 || $effective_year > 2100) $errors[] = "Invalid effective year.";

                if (empty($errors)) {
                    $stmt = $db->prepare("INSERT INTO curricula (curriculum_name, curriculum_code, description, total_units, department_id, effective_year, status) VALUES (:name, :code, :desc, :units, :dept, :year, 'Draft')");
                    $stmt->execute([
                        ':name' => $curriculum_name,
                        ':code' => $curriculum_code,
                        ':desc' => $description,
                        ':units' => $total_units,
                        ':dept' => $departmentId,
                        ':year' => $effective_year
                    ]);
                    $curriculum_id = $db->lastInsertId();

                    // Associate with the program (assuming one program per department for simplicity)
                    $programStmt = $db->prepare("SELECT program_id FROM programs WHERE department_id = :department_id LIMIT 1");
                    $programStmt->execute([':department_id' => $departmentId]);
                    $program_id = $programStmt->fetchColumn();
                    if ($program_id) {
                        $db->prepare("INSERT INTO curriculum_programs (curriculum_id, program_id, is_primary, required) VALUES (:curriculum_id, :program_id, 1, 1)")
                            ->execute([':curriculum_id' => $curriculum_id, ':program_id' => $program_id]);
                    }

                    $success = "Curriculum added successfully.";
                    // Refresh the curricula list
                    $curriculaStmt = $db->prepare("SELECT c.*, p.program_name 
                                                  FROM curricula c 
                                                  JOIN programs p ON c.department_id = p.department_id 
                                                  WHERE c.department_id = :department_id");
                    $curriculaStmt->execute([':department_id' => $departmentId]);
                    $curricula = $curriculaStmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $error = implode("<br>", $errors);
                }
            } elseif ($_POST['action'] === 'edit_curriculum') {
                // Edit curriculum
                $curriculum_id = intval($_POST['curriculum_id'] ?? 0);
                $curriculum_name = trim($_POST['curriculum_name'] ?? '');
                $curriculum_code = trim($_POST['curriculum_code'] ?? '');
                $effective_year = intval($_POST['effective_year'] ?? 0);
                $description = trim($_POST['description'] ?? '');
                $status = $_POST['status'] ?? 'Draft';

                $errors = [];
                if ($curriculum_id < 1) $errors[] = "Invalid curriculum.";
                if (empty($curriculum_name)) $errors[] = "Curriculum name is required.";
                if (empty($curriculum_code)) $errors[] = "Curriculum code is required.";
                if ($effective_year < 2000 || $effective_year > 2100) $errors[] = "Invalid effective year.";
                if (!in_array($status, ['Draft', 'Active', 'Archived'])) $errors[] = "Invalid status.";

                if (empty($errors)) {
                    $stmt = $db->prepare("UPDATE curricula SET curriculum_name = :name, curriculum_code = :code, description = :desc, effective_year = :year, status = :status WHERE curriculum_id = :id");
                    $stmt->execute([
                        ':name' => $curriculum_name,
                        ':code' => $curriculum_code,
                        ':desc' => $description,
                        ':year' => $effective_year,
                        ':status' => $status,
                        ':id' => $curriculum_id
                    ]);

                    $success = "Curriculum updated successfully.";
                    // Refresh the curricula list
                    $curriculaStmt = $db->prepare("SELECT c.*, p.program_name 
                                                  FROM curricula c 
                                                  JOIN programs p ON c.department_id = p.department_id 
                                                  WHERE c.department_id = :department_id");
                    $curriculaStmt->execute([':department_id' => $departmentId]);
                    $curricula = $curriculaStmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $error = implode("<br>", $errors);
                }
            } elseif ($_POST['action'] === 'add_course') {
                // Add course to curriculum
                $curriculum_id = intval($_POST['curriculum_id'] ?? 0);
                $course_id = intval($_POST['course_id'] ?? 0);
                $year_level = $_POST['year_level'] ?? '';
                $semester = $_POST['semester'] ?? '';
                $subject_type = $_POST['subject_type'] ?? 'Major';

                $errors = [];
                if ($curriculum_id < 1) $errors[] = "Invalid curriculum.";
                if ($course_id < 1) $errors[] = "Please select a course.";
                if (!in_array($year_level, ['1st Year', '2nd Year', '3rd Year', '4th Year'])) $errors[] = "Invalid year level.";
                if (!in_array($semester, ['1st', '2nd', 'Summer'])) $errors[] = "Invalid semester.";
                if (!in_array($subject_type, ['Major', 'Minor', 'General Education', 'Elective'])) $errors[] = "Invalid subject type.";

                if (empty($errors)) {
                    // Insert into curriculum_courses
                    $stmt = $db->prepare("INSERT INTO curriculum_courses (curriculum_id, course_id, year_level, semester, subject_type, is_core) VALUES (:curriculum_id, :course_id, :year_level, :semester, :subject_type, 1)");
                    $stmt->execute([
                        ':curriculum_id' => $curriculum_id,
                        ':course_id' => $course_id,
                        ':year_level' => $year_level,
                        ':semester' => $semester,
                        ':subject_type' => $subject_type
                    ]);

                    // Update total_units in curricula
                    $unitsStmt = $db->prepare("SELECT SUM(c.units) as total FROM curriculum_courses cc JOIN courses c ON cc.course_id = c.course_id WHERE cc.curriculum_id = :curriculum_id");
                    $unitsStmt->execute([':curriculum_id' => $curriculum_id]);
                    $total_units = $unitsStmt->fetchColumn();

                    $db->prepare("UPDATE curricula SET total_units = :total_units WHERE curriculum_id = :curriculum_id")
                        ->execute([':total_units' => $total_units, ':curriculum_id' => $curriculum_id]);

                    $success = "Course added to curriculum successfully.";
                    // Refresh the curricula list
                    $curriculaStmt = $db->prepare("SELECT c.*, p.program_name 
                                                  FROM curricula c 
                                                  JOIN programs p ON c.department_id = p.department_id 
                                                  WHERE c.department_id = :department_id");
                    $curriculaStmt->execute([':department_id' => $departmentId]);
                    $curricula = $curriculaStmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $error = implode("<br>", $errors);
                }
            } elseif ($_POST['action'] === 'create_course') {
                // Create new course
                $course_code = trim($_POST['course_code'] ?? '');
                $course_name = trim($_POST['course_name'] ?? '');
                $units = intval($_POST['units'] ?? 0);
                $description = trim($_POST['description'] ?? '');

                $errors = [];
                if (empty($course_code)) $errors[] = "Course code is required.";
                if (empty($course_name)) $errors[] = "Course name is required.";
                if ($units < 1 || $units > 10) $errors[] = "Units must be between 1 and 10.";
                // Check if course code already exists
                $stmt = $db->prepare("SELECT COUNT(*) FROM courses WHERE course_code = :code AND department_id = :dept");
                $stmt->execute([':code' => $course_code, ':dept' => $departmentId]);
                if ($stmt->fetchColumn() > 0) {
                    $errors[] = "Course code already exists.";
                }

                if (empty($errors)) {
                    $stmt = $db->prepare("INSERT INTO courses (course_code, course_name, units, department_id) VALUES (:code, :name, :units, :dept)");
                    $stmt->execute([
                        ':code' => $course_code,
                        ':name' => $course_name,
                        ':units' => $units,
                        ':dept' => $departmentId
                    ]);

                    $success = "Course created successfully.";
                    // Refresh the courses list
                    $coursesStmt = $db->prepare("SELECT course_id, course_code, course_name, units FROM courses WHERE department_id = :department_id");
                    $coursesStmt->execute([':department_id' => $departmentId]);
                    $courses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $error = implode("<br>", $errors);
                }
            } elseif ($_POST['action'] === 'toggle_curriculum') {
                // Toggle curriculum status
                $curriculum_id = intval($_POST['curriculum_id'] ?? 0);
                $new_status = $_POST['status'] === 'Active' ? 'Draft' : 'Active';

                $stmt = $db->prepare("UPDATE curricula SET status = :status WHERE curriculum_id = :curriculum_id");
                $stmt->execute([':status' => $new_status, ':curriculum_id' => $curriculum_id]);

                $success = "Curriculum status updated to $new_status.";
                // Refresh the curricula list
                $curriculaStmt = $db->prepare("SELECT c.*, p.program_name 
                                              FROM curricula c 
                                              JOIN programs p ON c.department_id = p.department_id 
                                              WHERE c.department_id = :department_id");
                $curriculaStmt->execute([':department_id' => $departmentId]);
                $curricula = $curriculaStmt->fetchAll(PDO::FETCH_ASSOC);
            } elseif ($_POST['action'] === 'remove_course') {
                // Remove course from curriculum
                $curriculum_id = intval($_POST['curriculum_id'] ?? 0);
                $course_id = intval($_POST['course_id'] ?? 0);

                $errors = [];
                if ($curriculum_id < 1) $errors[] = "Invalid curriculum.";
                if ($course_id < 1) $errors[] = "Invalid course.";

                if (empty($errors)) {
                    // Check if the course exists in the curriculum
                    $checkStmt = $db->prepare("SELECT COUNT(*) FROM curriculum_courses WHERE curriculum_id = :curriculum_id AND course_id = :course_id");
                    $checkStmt->execute([':curriculum_id' => $curriculum_id, ':course_id' => $course_id]);
                    if ($checkStmt->fetchColumn() == 0) {
                        $errors[] = "Course not found in this curriculum.";
                    } else {
                        // Delete the course from curriculum_courses
                        $deleteStmt = $db->prepare("DELETE FROM curriculum_courses WHERE curriculum_id = :curriculum_id AND course_id = :course_id");
                        $deleteStmt->execute([':curriculum_id' => $curriculum_id, ':course_id' => $course_id]);

                        // Update total_units in curricula
                        $unitsStmt = $db->prepare("SELECT SUM(c.units) as total FROM curriculum_courses cc JOIN courses c ON cc.course_id = c.course_id WHERE cc.curriculum_id = :curriculum_id");
                        $unitsStmt->execute([':curriculum_id' => $curriculum_id]);
                        $total_units = $unitsStmt->fetchColumn() ?: 0;

                        $db->prepare("UPDATE curricula SET total_units = :total_units WHERE curriculum_id = :curriculum_id")
                            ->execute([':total_units' => $total_units, ':curriculum_id' => $curriculum_id]);

                        // Return JSON for AJAX
                        if ($_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                            header('Content-Type: application/json');
                            echo json_encode(['success' => 'Course removed successfully.']);
                            exit;
                        }

                        $success = "Course removed successfully.";
                        // Refresh the curricula list
                        $curriculaStmt = $db->prepare("SELECT c.*, p.program_name 
                                                      FROM curricula c 
                                                      JOIN programs p ON c.department_id = p.department_id 
                                                      WHERE c.department_id = :department_id");
                        $curriculaStmt->execute([':department_id' => $departmentId]);
                        $curricula = $curriculaStmt->fetchAll(PDO::FETCH_ASSOC);
                    }
                }

                if (!empty($errors)) {
                    if ($_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                        header('Content-Type: application/json');
                        echo json_encode(['error' => implode(' ', $errors)]);
                        exit;
                    }
                    $error = implode("<br>", $errors);
                }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}
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
    }

    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

    body {
        font-family: 'Inter', sans-serif;
        background-color: var(--prmsu-gray-light);
        color: var(--prmsu-gray-dark);
        line-height: 1.6;
    }

    /* Add these styles to your existing CSS */
    #courseSearchInput {
        transition: all 0.3s ease;
        border: 1px solid var(--prmsu-gray);
    }

    #courseSearchInput:focus {
        border-color: var(--prmsu-gold);
        box-shadow: 0 0 0 3px rgba(239, 187, 15, 0.2);
    }

    #courseExistsNotification {
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
    }

    #courseSelect option[style*="display: none"] {
        display: none !important;
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

    /* Group Header */
    .group-header {
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--prmsu-gray-dark);
        margin-bottom: 0.5rem;
    }

    /* Toast Notification */
    .toast {
        opacity: 1;
        transition: opacity 0.3s ease-in-out;
    }

    /* Ensure table headers are uppercase and consistent */
    .table-header th {
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.05em;
        background-color: var(--prmsu-gray-dark);
        color: var(--prmsu-white);
    }

    .modal-overlay {
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        transition: opacity 0.3s ease, transform 0.3s ease;
        transform: scale(0.95);
    }

    .modal-content {
        transform: translateY(20px);
        transition: all 0.3s ease;
    }

    .modal-content.modal-open {
        transform: translateY(0);
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
            <select class="border border-prmsu-gray rounded-lg px-4 py-3 focus-gold bg-prmsu-white text-prmsu-gray-dark w-full sm:w-auto shadow-sm">
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="draft">Draft</option>
            </select>

            <select class="border border-prmsu-gray rounded-lg px-4 py-3 focus-gold bg-prmsu-white text-prmsu-gray-dark w-full sm:w-auto shadow-sm">
                <option value="">All Years</option>
                <option value="2025">2025</option>
                <option value="2024">2024</option>
                <option value="2023">2023</option>
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
                <tbody class="divide-y divide-prmsu-gray-light">
                    <?php foreach ($curricula as $curriculum): ?>
                        <?php
                        // Fetch the number of courses in this curriculum
                        $courseCountStmt = $db->prepare("SELECT COUNT(*) FROM curriculum_courses WHERE curriculum_id = :curriculum_id");
                        $courseCountStmt->execute([':curriculum_id' => $curriculum['curriculum_id']]);
                        $course_count = $courseCountStmt->fetchColumn();

                        // Fetch courses for the curriculum to display in the View Courses modal
                        $coursesStmt = $db->prepare("SELECT c.course_code, c.course_name, c.units, cc.year_level, cc.semester, cc.subject_type 
                                                    FROM curriculum_courses cc 
                                                    JOIN courses c ON cc.course_id = c.course_id 
                                                    WHERE cc.curriculum_id = :curriculum_id");
                        $coursesStmt->execute([':curriculum_id' => $curriculum['curriculum_id']]);
                        $curriculum_courses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <tr class="table-row">
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
                                    <button onclick='openEditCurriculumModal(<?= json_encode([
                                                                                    "id" => $curriculum['curriculum_id'],
                                                                                    "name" => htmlspecialchars($curriculum['curriculum_name']),
                                                                                    "code" => htmlspecialchars($curriculum['curriculum_code']),
                                                                                    "year" => $curriculum['effective_year'],
                                                                                    "description" => htmlspecialchars($curriculum['description']),
                                                                                    "status" => $curriculum['status']
                                                                                ]) ?>)'
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

<!-- Merged Add Curriculum and Course Modal -->
<div id="addCurriculumCourseModal" class="fixed inset-0 hidden">
    <div class="bg-white modal-overlay fixed inset-0 flex items-center justify-center p-4 bg-opacity-50 backdrop-blur-sm opacity-0 transition-opacity duration-300">
        <div class="modal-content bg-white rounded-xl shadow-2xl max-w-lg w-full transform translate-y-8 transition-transform duration-300 ease-out">
            <!-- Modal Header -->
            <div class="p-6 border-b border-gray-200 flex justify-between items-center bg-gradient-to-r from-amber-50 to-white rounded-t-xl">
                <h3 class="text-xl font-bold text-gray-800 flex items-center">
                    <svg class="w-6 h-6 mr-3 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    <span id="modalTitle">Add New</span>
                </h3>
                <button onclick="closeModal('addCurriculumCourseModal')" class="text-gray-500 hover:text-gray-700 transition-all transform hover:scale-110 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="p-6">
                <!-- Tabs -->
                <div class="flex border-b border-gray-200 mb-6">
                    <button id="curriculumTab" class="tab-button py-3 px-4 font-medium text-sm border-b-2 border-amber-500 text-amber-600 mr-4 focus:outline-none" onclick="showTab('curriculumForm', 'curriculumTab')">
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        Add Curriculum
                    </button>
                    <button id="courseTab" class="tab-button py-3 px-4 font-medium text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700 focus:outline-none" onclick="showTab('courseForm', 'courseTab')">
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                        Add Course
                    </button>
                </div>

                <!-- Curriculum Form -->
                <div id="curriculumForm" class="tab-content">
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

                <!-- Course Form -->
                <div id="courseForm" class="tab-content hidden">
                    <form method="POST" class="space-y-5" id="courseFormElement">
                        <input type="hidden" name="action" value="create_course">
                        <div class="form-group">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Course Code</label>
                            <input type="text" name="course_code" id="courseCodeInput" placeholder="e.g. CS101"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors" required>
                            <p id="courseCodeError" class="error-text"></p>
                        </div>
                        <div class="form-group">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Course Name</label>
                            <input type="text" name="course_name" placeholder="e.g. Introduction to Programming"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors" required>
                        </div>
                        <div class="form-group">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Units</label>
                            <input type="number" name="units" value="3" min="1" max="10"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors" required>
                        </div>
                        <div class="mt-6 pt-4 border-t border-gray-200 flex justify-end space-x-3">
                            <button type="button" onclick="closeModal('addCurriculumCourseModal')"
                                class="px-5 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                                Cancel
                            </button>
                            <button type="submit" id="createCourseButton"
                                class="px-5 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                                Create Course
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Curriculum Modal -->
<div id="editCurriculumModal" class="fixed inset-0 z-50 hidden">
    <div class="modal-overlay fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white modal-content bg-prmsu-white rounded-xl shadow-2xl max-w-md w-full">
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
<div id="manageCoursesModal" class="fixed inset-0 z-50 hidden">
    <div class="modal-overlay fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white modal-content bg-prmsu-white rounded-xl shadow-2xl max-w-2xl w-full">
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
                <!-- Add Search Bar Here -->
                <div class="relative mb-4">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-prmsu-gray" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text" id="courseSearchInput" placeholder="Search courses..."
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors">
                </div>

                <!-- Add Notification Area for Course Existence Check -->
                <div id="courseExistsNotification" class="hidden mb-4 p-3 rounded-lg bg-red-100 text-red-800 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span id="courseExistsMessage">This course already exists in the curriculum.</span>
                </div>

                <form method="POST" class="space-y-5" id="manageCoursesForm">
                    <input type="hidden" name="action" value="add_course">
                    <input type="hidden" name="curriculum_id" id="curriculumIdInput">
                    <div>
                        <label class="block text-sm font-medium text-prmsu-gray-dark mb-1">Select Course</label>
                        <select name="course_id" id="courseSelect" class="focus-gold" required>
                            <option value="">-- Select Course --</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= $course['course_id'] ?>"
                                    data-code="<?= htmlspecialchars($course['course_code']) ?>"
                                    data-name="<?= htmlspecialchars($course['course_name']) ?>">
                                    <?= htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-prmsu-gray-dark mb-1">Year Level</label>
                            <select name="year_level" class="focus-gold" required>
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-prmsu-gray-dark mb-1">Semester</label>
                            <select name="semester" class="focus-gold" required>
                                <option value="1st">1st Semester</option>
                                <option value="2nd">2nd Semester</option>
                                <option value="Summer">Summer</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-prmsu-gray-dark mb-1">Subject Type</label>
                            <select name="subject_type" class="focus-gold" required>
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
<div id="viewCoursesModal" class="fixed inset-0 z-50 hidden">
    <div class="modal-overlay fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white modal-content bg-prmsu-white rounded-xl shadow-2xl max-w-4xl w-full">
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
    // Open modal function
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        modal.classList.remove('hidden');

        const overlay = modal.querySelector('.modal-overlay');
        const content = modal.querySelector('.modal-content');

        void modal.offsetWidth;

        overlay.classList.add('opacity-100');
        content.classList.remove('translate-y-8');
        content.classList.add('translate-y-0');

        if (modalId === 'addCurriculumCourseModal') {
            showTab('curriculumForm', 'curriculumTab');
        }
    }

    // Close modal function
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        const overlay = modal.querySelector('.modal-overlay');
        const content = modal.querySelector('.modal-content');

        overlay.classList.remove('opacity-100');
        content.classList.remove('translate-y-0');
        content.classList.add('translate-y-8');

        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    // Tab functionality
    function showTab(tabContentId, tabButtonId) {
        console.log('Switching to tab:', tabContentId);

        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
            content.classList.add('hidden');
        });

        const selectedTab = document.getElementById(tabContentId);
        if (selectedTab) {
            selectedTab.classList.add('active');
            selectedTab.classList.remove('hidden');
        }

        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('active', 'border-amber-500', 'text-amber-600');
            button.classList.add('border-transparent', 'text-gray-500');
        });

        const selectedButton = document.getElementById(tabButtonId);
        if (selectedButton) {
            selectedButton.classList.add('active', 'border-amber-500', 'text-amber-600');
            selectedButton.classList.remove('border-transparent', 'text-gray-500');
        }

        const modalTitle = document.getElementById('modalTitle');
        if (modalTitle) {
            modalTitle.textContent = tabContentId === 'curriculumForm' ? 'Add New Curriculum' : 'Add New Course';
        }

        if (tabContentId === 'courseForm') {
            document.getElementById('courseCodeError').style.display = 'none';
            document.getElementById('courseCodeError').textContent = '';
            document.getElementById('createCourseButton').disabled = false;
        }
    }

    // Toast notification function
    function showToast(message, bgColor) {
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 px-4 py-2 rounded-lg shadow-lg text-white ${bgColor} transition-all duration-300`;
        toast.textContent = message;
        toast.style.zIndex = '9999';
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
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
        document.getElementById('curriculumIdInput').value = curriculumId;
        document.getElementById('manageCoursesTitle').textContent = `Manage Courses for ${curriculumName}`;

        // Reset form and notification
        document.getElementById('manageCoursesForm').reset();
        document.getElementById('courseExistsNotification').classList.add('hidden');
        document.getElementById('addCourseButton').disabled = false;

        openModal('manageCoursesModal');

        // Focus on search input when modal opens
        setTimeout(() => {
            document.getElementById('courseSearchInput').focus();
        }, 300);
    }

    // Add event listeners for search functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Course search functionality
        const courseSearchInput = document.getElementById('courseSearchInput');
        if (courseSearchInput) {
            courseSearchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const options = document.querySelectorAll('#courseSelect option');

                options.forEach(option => {
                    if (option.value === '') return; // Skip the first option

                    const text = option.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        option.style.display = '';
                    } else {
                        option.style.display = 'none';
                    }
                });
            });
        }

        // Course existence check when selecting a course
        const courseSelect = document.getElementById('courseSelect');
        if (courseSelect) {
            courseSelect.addEventListener('change', function() {
                const curriculumId = document.getElementById('curriculumIdInput').value;
                const courseId = this.value;

                if (!courseId || !curriculumId) {
                    document.getElementById('courseExistsNotification').classList.add('hidden');
                    document.getElementById('addCourseButton').disabled = false;
                    return;
                }

                // Check if course already exists in curriculum
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
                            const selectedOption = courseSelect.options[courseSelect.selectedIndex];
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

        // Handle form submission for adding courses
        const manageCoursesForm = document.getElementById('manageCoursesForm');
        if (manageCoursesForm) {
            manageCoursesForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const curriculumId = formData.get('curriculum_id');
                const courseId = formData.get('course_id');

                fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.success, 'bg-green-500');
                            closeModal('manageCoursesModal');

                            // Refresh the view courses modal if it's open
                            if (document.getElementById('viewCoursesModal') &&
                                !document.getElementById('viewCoursesModal').classList.contains('hidden')) {
                                const curriculumName = document.getElementById('viewCoursesTitle').textContent.replace('Courses for ', '');
                                fetchCoursesAndRefreshModal(curriculumId, curriculumName);
                            }
                        } else if (data.error) {
                            showToast(data.error, 'bg-red-500');
                        }
                    })
                    .catch(error => {
                        console.error('Error adding course:', error);
                        showToast('Failed to add course.', 'bg-red-500');
                    });
            });
        }
    });

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

            // Group courses by year_level and semester
            const groupedCourses = {};
            courses.forEach(course => {
                const key = `${course.year_level}-${course.semester}`;
                if (!groupedCourses[key]) {
                    groupedCourses[key] = [];
                }
                groupedCourses[key].push(course);
            });

            // Sort groups by year_level and semester
            const yearOrder = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
            const semesterOrder = ['1st', '2nd', 'Summer'];
            const sortedKeys = Object.keys(groupedCourses).sort((a, b) => {
                const [yearA, semesterA] = a.split('-');
                const [yearB, semesterB] = b.split('-');
                const yearDiff = yearOrder.indexOf(yearA) - yearOrder.indexOf(yearB);
                if (yearDiff !== 0) return yearDiff;
                return semesterOrder.indexOf(semesterA) - semesterOrder.indexOf(semesterB);
            });

            // Render each group
            sortedKeys.forEach(key => {
                const [yearLevel, semester] = key.split('-');
                const groupCourses = groupedCourses[key].sort((a, b) => a.course_code.localeCompare(b.course_code));

                // Create group header
                const header = document.createElement('div');
                header.className = 'mt-6 mb-4';
                header.innerHTML = `
                <h4 class="text-lg font-semibold text-prmsu-gray-dark">${yearLevel} - ${semester} Semester</h4>
                <hr class="border-prmsu-gray-light mt-2">
            `;
                container.appendChild(header);

                // Create table for the group
                const table = document.createElement('table');
                table.className = 'w-full table-auto border-collapse mb-6';
                table.innerHTML = `
                <thead>
                    <tr class="table-header">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-prmsu-white">Course Code</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-prmsu-white">Course Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-prmsu-white">Units</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-prmsu-white">Year Level</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-prmsu-white">Semester</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-prmsu-white">Subject Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-prmsu-white">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-prmsu-gray-light">
                    ${groupCourses.map(course => `
                        <tr class="table-row">
                            <td class="px-4 py-3 text-sm text-prmsu-gray-dark">${course.course_code || ''}</td>
                            <td class="px-4 py-3 text-sm text-prmsu-gray-dark">${course.course_name || ''}</td>
                            <td class="px-4 py-3 text-sm text-prmsu-gray">${course.units || ''}</td>
                            <td class="px-4 py-3 text-sm text-prmsu-gray">${course.year_level || ''}</td>
                            <td class="px-4 py-3 text-sm text-prmsu-gray">${course.semester || ''}</td>
                            <td class="px-4 py-3 text-sm text-prmsu-gray">${course.subject_type || ''}</td>
                            <td class="px-4 py-3 text-sm font-medium">
                                <button class="remove-course-btn text-red-600 hover:text-red-800 transition-all"
                                    data-course-id="${course.course_id}"
                                    data-curriculum-id="${curriculumId}"
                                    data-course-name="${course.course_name}"
                                    title="Remove Course">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
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
        const courseCodeInput = document.getElementById('courseCodeInput');
        const courseCodeError = document.getElementById('courseCodeError');
        const createCourseButton = document.getElementById('createCourseButton');
        let debounceTimeout;

        if (courseCodeInput) {
            courseCodeInput.addEventListener('input', function() {
                clearTimeout(debounceTimeout);
                debounceTimeout = setTimeout(() => {
                    const courseCode = courseCodeInput.value.trim();
                    if (courseCode === '') {
                        courseCodeError.textContent = '';
                        courseCodeError.style.display = 'none';
                        createCourseButton.disabled = false;
                        return;
                    }

                    fetch(window.location.href, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: `action=check_course_code&course_code=${encodeURIComponent(courseCode)}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.exists) {
                                courseCodeError.textContent = data.message;
                                courseCodeError.style.display = 'block';
                                createCourseButton.disabled = true;
                            } else {
                                courseCodeError.textContent = '';
                                courseCodeError.style.display = 'none';
                                createCourseButton.disabled = false;
                            }
                        })
                        .catch(error => {
                            courseCodeError.textContent = 'Error checking course code.';
                            courseCodeError.style.display = 'block';
                            createCourseButton.disabled = true;
                            console.error('Error:', error);
                        });
                }, 500);
            });
        }

        showTab('curriculumForm', 'curriculumTab');

        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    const modal = this.closest('.fixed');
                    if (modal) {
                        closeModal(modal.id);
                    }
                }
            });
        });

        // Handle remove course button clicks
        document.getElementById('viewCoursesModal').addEventListener('click', function(e) {
            if (e.target.closest('.remove-course-btn')) {
                const button = e.target.closest('.remove-course-btn');
                const courseId = button.dataset.courseId;
                const curriculumId = button.dataset.curriculumId;
                const courseName = button.dataset.courseName;

                if (confirm(`Are you sure you want to remove "${courseName}" from this curriculum?`)) {
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
                                showToast(data.success, 'bg-green-500');
                                // Fetch updated courses list
                                fetchCoursesAndRefreshModal(curriculumId, curriculumName);
                            } else {
                                showToast(data.error || 'Failed to remove course.', 'bg-red-500');
                            }
                        })
                        .catch(error => {
                            console.error('Remove Course Error:', error);
                            showToast('Failed to remove course.', 'bg-red-500');
                        });
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
                showToast('Failed to refresh courses.', 'bg-red-500');
            });
    }
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>