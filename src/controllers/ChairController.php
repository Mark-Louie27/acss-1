<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../services/EmailService.php';
require_once __DIR__ . '/../services/SchedulingService.php';
require_once __DIR__ . '/../services/ScheduleService.php';
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/BaseProfileController.php';
require_once __DIR__ . '/../repositories/SectionRepository.php';
require_once __DIR__ . '/../repositories/ClassroomRepository.php';
require_once __DIR__ . '/../repositories/ScheduleRepository.php';
require_once __DIR__ . '/../repositories/CurriculumRepository.php';

use Src\Repositories\SectionRepository;
use Src\Repositories\ClassroomRepository;
use Src\Repositories\ScheduleRepository;
use Src\Repositories\CurriculumRepository;

class ChairController extends BaseProfileController
{
    // ── Profile routing config (new) ─────────────────────────────────────────
    protected string $redirectPath      = '/chair/profile';
    protected string $viewPath          = __DIR__ . '/../views/chair/profile.php';
    protected string $fallbackRoleName  = 'Program Chair';
    protected bool   $withExpertiseLevel = false;

    public $db;
    protected $authService;
    private $baseUrl;
    private $emailService;
    private $schedulingService;
    private $userDepartments = []; // Store chair's departments
    private $currentDepartmentId = 0; // Track the current department

    // ──────────────────────────────────────────────────────────────
    // VALID CONSTANTS  (reuse in all private methods)
    // ──────────────────────────────────────────────────────────────
    private const VALID_YEAR_LEVELS = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
    private const VALID_SEMESTERS   = ['1st', '2nd', 'Summer', 'Mid Year'];


    public function __construct()
    {
        parent::__construct(); // BaseController sets $this->db, $this->userRoles
        error_log("ChairController instantiated");

        $this->db = (new Database())->connect(); // same as original
        if ($this->db === null) {
            error_log("Failed to connect to the database in ChairController");
            die("Database connection failed. Please try again later.");
        }
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->authService       = new AuthService($this->db);
        $this->emailService      = new EmailService();
        $this->schedulingService = new SchedulingService($this->db);

        // ── ONE new line — wire up profile repos/services ─────────────────────
        $this->initProfileDependencies();
        // ─────────────────────────────────────────────────────────────────────

        $userId = $_SESSION['user_id'] ?? 0;
        if ($userId) {
            $this->loadUserDepartments($userId);
            $this->setCurrentDepartment();
        }
    }

    private function loadUserDepartments($userId)
    {
        $query = "
        SELECT cd.department_id, d.department_name, cd.is_primary
        FROM chair_departments cd
        JOIN departments d ON cd.department_id = d.department_id
        WHERE cd.user_id = :user_id
        ";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        $this->userDepartments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("loadUserDepartments for user_id=$userId returned: " . json_encode($this->userDepartments));
        if (empty($this->userDepartments)) {
            error_log("No departments found for user_id=$userId");
        } else {
            error_log("Loaded " . count($this->userDepartments) . " departments for user_id=$userId");
        }
    }

    // Set the current department, defaulting to the primary one if set
    private function setCurrentDepartment()
    {
        if (empty($this->userDepartments)) {
            $this->currentDepartmentId = 0;
            return;
        }

        // Check session for previously selected department
        $sessionDeptId = $_SESSION['current_department_id'] ?? 0;
        if ($sessionDeptId && in_array($sessionDeptId, array_column($this->userDepartments, 'department_id'))) {
            $this->currentDepartmentId = $sessionDeptId;
        } else {
            // Default to primary department if exists, otherwise first department
            $primaryDept = array_filter($this->userDepartments, fn($dept) => $dept['is_primary']);
            $this->currentDepartmentId = !empty($primaryDept) ? reset($primaryDept)['department_id'] : $this->userDepartments[0]['department_id'];
            $_SESSION['current_department_id'] = $this->currentDepartmentId;
        }
        error_log("Current department set to {$this->currentDepartmentId} for user_id=" . ($_SESSION['user_id'] ?? 'unknown'));
    }

    public function switchSemester()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Handle reset to current semester
            if (isset($_POST['reset']) && $_POST['reset'] === 'true') {
                $currentStmt = $this->db->prepare("
                SELECT semester_id, semester_name, academic_year, is_current 
                FROM semesters 
                WHERE is_current = 1
            ");
                $currentStmt->execute();
                $currentSemester = $currentStmt->fetch(PDO::FETCH_ASSOC);

                if ($currentSemester) {
                    $_SESSION['selected_semester_id'] = $currentSemester['semester_id'];
                    $_SESSION['selected_semester'] = $currentSemester;

                    // Clear historical view flag
                    unset($_SESSION['is_historical_view']);

                    echo json_encode([
                        'success' => true,
                        'message' => 'Returned to current semester',
                        'semester_id' => $currentSemester['semester_id'],
                        'semester_name' => $currentSemester['semester_name'],
                        'academic_year' => $currentSemester['academic_year'],
                        'is_current' => true
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Current semester not found'
                    ]);
                }
                exit;
            }

            // Handle semester switch
            if (isset($_POST['semester_id'])) {
                $newSemesterId = intval($_POST['semester_id']);

                $stmt = $this->db->prepare("
                SELECT semester_id, semester_name, academic_year, is_current 
                FROM semesters 
                WHERE semester_id = ?
            ");
                $stmt->execute([$newSemesterId]);
                $semester = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($semester) {
                    // Store selected semester in session
                    $_SESSION['selected_semester_id'] = $newSemesterId;
                    $_SESSION['selected_semester'] = $semester;

                    // Set historical view flag if not current
                    $_SESSION['is_historical_view'] = !$semester['is_current'];

                    error_log("Switched to semester_id=$newSemesterId (historical: " . ($_SESSION['is_historical_view'] ? 'yes' : 'no') . ")");

                    echo json_encode([
                        'success' => true,
                        'message' => 'Semester switched successfully',
                        'semester_id' => $newSemesterId,
                        'semester_name' => $semester['semester_name'],
                        'academic_year' => $semester['academic_year'],
                        'is_current' => $semester['is_current']
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Semester not found'
                    ]);
                }
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid request'
                ]);
            }
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid request method'
            ]);
        }

        exit;
    }

    private function isHistoricalView()
    {
        return $_SESSION['is_historical_view'] ?? false;
    }

    private function getActiveSemester()
    {
        // Check if user has selected a specific semester
        if (isset($_SESSION['selected_semester_id'])) {
            $stmt = $this->db->prepare("
            SELECT semester_id, semester_name, academic_year, is_current 
            FROM semesters 
            WHERE semester_id = ?
        ");
            $stmt->execute([$_SESSION['selected_semester_id']]);
            $semester = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($semester) {
                // Set historical flag correctly
                $_SESSION['is_historical_view'] = !($semester['is_current'] == 1);
                return $semester;
            }
        }

        // Fall back to current semester - never historical
        $_SESSION['is_historical_view'] = false;
        return $this->getCurrentSemester();
    }

    // Add this helper method to get all available semesters
    private function getAvailableSemesters()
    {
        $stmt = $this->db->prepare("
        SELECT semester_id, semester_name, academic_year, is_current 
        FROM semesters 
        ORDER BY academic_year DESC, 
        FIELD(semester_name, '2nd', '1st', 'Summer', 'Mid Year')
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Method to switch department
    public function switchDepartment()
    {
        // Set JSON header immediately
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['department_id'])) {
            $newDeptId = intval($_POST['department_id']);

            // Check if user has access to this department
            if (in_array($newDeptId, array_column($this->userDepartments, 'department_id'))) {
                $_SESSION['current_department_id'] = $newDeptId;
                $this->currentDepartmentId = $newDeptId;

                error_log("Switched to department_id=$newDeptId for user_id=" . ($_SESSION['user_id'] ?? 'unknown'));

                // Return JSON success response
                echo json_encode([
                    'success' => true,
                    'message' => 'Department switched successfully',
                    'department_id' => $newDeptId
                ]);
            } else {
                // Return JSON error response
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'error' => 'You do not have access to this department'
                ]);
            }
        } else {
            // Return JSON error for invalid request
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid request'
            ]);
        }

        exit; // Important: stop execution after JSON output
    }

    public function dashboard()
    {
        $this->requireAnyRole('chair', 'dean');
        try {
            $chairId = $_SESSION['user_id'];
            error_log("dashboard: Starting dashboard method for user_id: $chairId");

            // Get department for the Chair - use currentDepartmentId if Program Chair
            $departmentId = $this->currentDepartmentId ?: $this->getChairDepartment($chairId);

            if (!$departmentId) {
                error_log("dashboard: No department found for chairId: $chairId");
                $error = "No department assigned to this chair. Please contact the administrator.";
                $viewPath = __DIR__ . '/../views/chair/dashboard.php';
                if (!file_exists($viewPath)) {
                    error_log("dashboard: View file not found at: $viewPath");
                    http_response_code(404);
                    echo "404 Not Found: Dashboard view missing";
                    exit;
                }
                require_once $viewPath;
                return;
            }

            // Get active semester (current or user-selected)
            $currentSemester = $this->getActiveSemester();
            $currentSemesterId = $currentSemester['semester_id'] ?? null;

            // Get all available semesters for the dropdown
            $availableSemesters = $this->getAvailableSemesters();

            // ✅ FIX: Determine if viewing historical data - check BOTH is_current flag AND session flag
            $isHistoricalView = isset($_SESSION['is_historical_view']) && $_SESSION['is_historical_view'] === true;

            // ✅ ADD: If semester is marked as current in DB, force isHistoricalView to false
            if (isset($currentSemester['is_current']) && $currentSemester['is_current'] == 1) {
                $isHistoricalView = false;
                unset($_SESSION['is_historical_view']); // Clear the session flag
            }

            error_log("Dashboard: Semester {$currentSemester['semester_name']} {$currentSemester['academic_year']} - is_current in DB: " . ($currentSemester['is_current'] ?? 'NULL') . ", isHistoricalView: " . ($isHistoricalView ? 'TRUE' : 'FALSE'));

            // Get department name
            $deptStmt = $this->db->prepare("SELECT department_name FROM departments WHERE department_id = ?");
            $deptStmt->execute([$departmentId]);
            $departmentName = $deptStmt->fetchColumn();

            // Format semester info with indicator if historical
            $semesterInfo = $currentSemester ? "{$currentSemester['semester_name']} Semester A.Y {$currentSemester['academic_year']}" : '2nd Semester 2024-2025';
            if ($isHistoricalView) {
                $semesterInfo .= " (Historical View)";
            }

            // Replace your existing scheduleStatusStmt query with this improved version:
            $scheduleStatusStmt = $this->db->prepare("
        SELECT 
            s.status,
            COUNT(*) as count
        FROM schedules s 
        JOIN courses c ON s.course_id = c.course_id 
        WHERE c.department_id = :department_id
        " . ($currentSemesterId ? "AND s.semester_id = :semester_id" : "") . "
        GROUP BY s.status
        ");

            $params = [':department_id' => $departmentId];
            if ($currentSemesterId) {
                $params[':semester_id'] = $currentSemesterId;
            }
            $scheduleStatusStmt->execute($params);
            $scheduleStatusData = $scheduleStatusStmt->fetchAll(PDO::FETCH_ASSOC);

            // Debug: Log what we got
            error_log("Schedule Status Raw Data: " . json_encode($scheduleStatusData));

            // Initialize status counts with all possible statuses
            $scheduleStatusCounts = [
                'total' => 0,
                'approved' => 0,
                'dean_approved' => 0,
                'pending' => 0,
                'rejected' => 0,
                'draft' => 0
            ];

            // Process status data - be careful with case sensitivity
            foreach ($scheduleStatusData as $statusRow) {
                $status = strtolower(trim($statusRow['status'])); // Normalize to lowercase
                $count = (int)$statusRow['count'];

                $scheduleStatusCounts['total'] += $count;

                // Match status - handle different possible values
                if (in_array($status, ['approved', 'approve'])) {
                    $scheduleStatusCounts['approved'] = $count;
                } elseif (in_array($status, ['dean_approved', 'dean approved', 'deanapproved'])) {
                    $scheduleStatusCounts['dean_approved'] = $count;
                } elseif (in_array($status, ['pending', 'pending approval'])) {
                    $scheduleStatusCounts['pending'] = $count;
                } elseif (in_array($status, ['rejected', 'reject', 'declined'])) {
                    $scheduleStatusCounts['rejected'] = $count;
                } elseif (in_array($status, ['draft'])) {
                    $scheduleStatusCounts['draft'] = $count;
                }
            }

            // Fixed faculty count to include all faculty in the department, not just primary
            $facultyCountStmt = $this->db->prepare("
            SELECT COUNT(DISTINCT f.faculty_id) as faculty_count
            FROM faculty f
            JOIN users u ON f.user_id = u.user_id
            JOIN faculty_departments fd ON f.faculty_id = fd.faculty_id
            WHERE fd.department_id = :department_id
        ");
            $facultyCountStmt->execute([':department_id' => $departmentId]);
            $facultyCount = $facultyCountStmt->fetchColumn();

            $coursesCountStmt = $this->db->prepare("
            SELECT COUNT(*) FROM courses WHERE department_id = ?
        ");
            $coursesCountStmt->execute([$departmentId]);
            $coursesCount = $coursesCountStmt->fetchColumn();

            // Get curricula with active status
            $curriculaStmt = $this->db->prepare("
            SELECT c.curriculum_id, c.curriculum_name, c.total_units, c.status, p.program_name 
            FROM curricula c 
            JOIN programs p ON c.department_id = p.department_id 
            WHERE c.department_id = ?
            ORDER BY c.curriculum_name
            ");
            $curriculaStmt->execute([$departmentId]);
            $curricula = $curriculaStmt->fetchAll(PDO::FETCH_ASSOC);

            error_log("dashboard: Fetched " . count($curricula) . " curricula");

            // Get my schedules (filtered by chair's faculty_id)
            $chairFacultyIdStmt = $this->db->prepare("
            SELECT faculty_id FROM faculty WHERE user_id = ?
            ");
            $chairFacultyIdStmt->execute([$chairId]);
            $chairFacultyId = $chairFacultyIdStmt->fetchColumn();

            $mySchedulesStmt = $this->db->prepare("
            SELECT 
                s.schedule_id, 
                c.course_name, 
                c.course_code, 
                CONCAT(COALESCE(u.title, ''), ' ', u.first_name, ' ', u.last_name) AS faculty_name, 
                r.room_name, 
                GROUP_CONCAT(DISTINCT s.day_of_week ORDER BY 
                    CASE s.day_of_week 
                        WHEN 'Monday' THEN 1
                        WHEN 'Tuesday' THEN 2
                        WHEN 'Wednesday' THEN 3
                        WHEN 'Thursday' THEN 4
                        WHEN 'Friday' THEN 5
                        WHEN 'Saturday' THEN 6
                        WHEN 'Sunday' THEN 7
                    END
                    SEPARATOR ', '
                ) as day_of_week,
                s.start_time, 
                s.end_time, 
                s.schedule_type, 
                sec.section_name,
                sem.semester_name, 
                sem.academic_year
            FROM schedules s
            JOIN courses c ON s.course_id = c.course_id
            JOIN faculty f ON s.faculty_id = f.faculty_id
            JOIN users u ON f.user_id = u.user_id
            LEFT JOIN sections sec ON s.section_id = sec.section_id
            LEFT JOIN classrooms r ON s.room_id = r.room_id
            LEFT JOIN semesters sem ON s.semester_id = sem.semester_id
            WHERE s.faculty_id = ?
            " . ($currentSemesterId ? "AND s.semester_id = ?" : "") . "
            GROUP BY c.course_id, s.faculty_id, s.start_time, s.end_time, s.schedule_type, sec.section_name, r.room_name
            ORDER BY s.created_at DESC
            LIMIT 5
            ");

            $params = [$chairFacultyId];
            if ($currentSemesterId) {
                $params[] = $currentSemesterId;
            }
            $mySchedulesStmt->execute($params);
            $mySchedules = $mySchedulesStmt->fetchAll(PDO::FETCH_ASSOC);

            // Process the day format to show MWF, TTH format
            foreach ($mySchedules as &$schedule) {
                $schedule['day_of_week'] = $this->schedulingService->formatScheduleDays($schedule['day_of_week']);
            }

            $schedules = $mySchedules;

            error_log("dashboard: Fetched " . count($mySchedules) . " my schedules");

            // Get schedule distribution data for chart - FIXED
            $scheduleDistStmt = $this->db->prepare("
            SELECT s.day_of_week, COUNT(*) as count 
                FROM schedules s 
                JOIN courses c ON s.course_id = c.course_id 
                WHERE c.department_id = ?
                " . ($currentSemesterId ? "AND s.semester_id = ?" : "") . "
                GROUP BY s.day_of_week
                ORDER BY 
                    CASE s.day_of_week 
                        WHEN 'Monday' THEN 1
                        WHEN 'Tuesday' THEN 2
                        WHEN 'Wednesday' THEN 3
                        WHEN 'Thursday' THEN 4
                        WHEN 'Friday' THEN 5
                        WHEN 'Saturday' THEN 6
                        WHEN 'Sunday' THEN 7
                    END
            ");

            $params = [$departmentId];
            if ($currentSemesterId) {
                $params[] = $currentSemesterId;
            }
            $scheduleDistStmt->execute($params);
            $scheduleDistData = $scheduleDistStmt->fetchAll(PDO::FETCH_ASSOC);

            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            $scheduleDist = array_fill_keys($days, 0);
            foreach ($scheduleDistData as $row) {
                if (isset($scheduleDist[$row['day_of_week']])) {
                    $scheduleDist[$row['day_of_week']] = (int)$row['count'];
                }
            }
            $scheduleDistJson = json_encode(array_values($scheduleDist));

            error_log("dashboard: Schedule distribution - " . $scheduleDistJson);

                // FIXED: Get faculty workload data for chart
                $workloadStmt = $this->db->prepare("
                SELECT 
                    CONCAT(COALESCE(u.title, ''), ' ', u.first_name, ' ', u.last_name) AS faculty_name, 
                    COUNT(DISTINCT s.schedule_id) as course_count
                FROM faculty f
                JOIN users u ON f.user_id = u.user_id
                LEFT JOIN schedules s ON f.faculty_id = s.faculty_id AND s.semester_id = ?
                WHERE u.department_id = ?
                GROUP BY f.faculty_id, u.title, u.first_name, u.last_name
                HAVING course_count > 0
                ORDER BY course_count DESC
                LIMIT 5
            ");

            $workloadParams = [$currentSemesterId, $departmentId];
            $workloadStmt->execute($workloadParams);
            $workloadData = $workloadStmt->fetchAll(PDO::FETCH_ASSOC);

            // If no data, provide sample data for testing
            if (empty($workloadData)) {
                error_log("dashboard: No faculty workload data found");
                $workloadData = [
                    ['faculty_name' => 'No assignments yet', 'course_count' => 0]
                ];
            }

            $workloadLabels = array_column($workloadData, 'faculty_name');
            $workloadCounts = array_column($workloadData, 'course_count');
            $workloadLabelsJson = json_encode($workloadLabels);
            $workloadCountsJson = json_encode($workloadCounts);

            error_log("dashboard: Faculty workload - labels: " . $workloadLabelsJson . ", counts: " . $workloadCountsJson);

            // Get conflict count - FIXED to detect REAL conflicts only
            $conflictStmt = $this->db->prepare(
                "
            SELECT COUNT(DISTINCT s1.schedule_id) as conflict_count
            FROM schedules s1
            JOIN schedules s2 ON (
                s1.schedule_id != s2.schedule_id
                AND s1.semester_id = s2.semester_id
                AND s1.day_of_week = s2.day_of_week
                AND s1.start_time < s2.end_time 
                AND s1.end_time > s2.start_time
                AND (
                    -- Faculty conflict: same faculty teaching at overlapping times
                    (s1.faculty_id = s2.faculty_id)
                    OR 
                    -- Room conflict: same room used at overlapping times (exclude online)
                    (s1.room_id = s2.room_id AND s1.room_id IS NOT NULL)
                    OR
                    -- Section conflict: same section at overlapping times for DIFFERENT courses
                    (s1.section_id = s2.section_id AND s1.course_id != s2.course_id)
                )
            )
            JOIN courses c ON s1.course_id = c.course_id
            WHERE c.department_id = ?
            " .
            ($currentSemesterId ? "AND s1.semester_id = ?" : "")
            );

            $params = [$departmentId];
            if ($currentSemesterId) {
                $params[] = $currentSemesterId;
            }
            $conflictStmt->execute($params);
            $conflictCount = $conflictStmt->fetchColumn() ?: 0;

            error_log("Dashboard: Detected $conflictCount actual conflicts");

            // Get unassigned courses count - FIXED to use active curriculum and current semester
            $unassignedCoursesStmt = $this->db->prepare("
                SELECT COUNT(DISTINCT c.course_id) as unassigned_count
                FROM courses c
                JOIN curriculum_courses cc ON c.course_id = cc.course_id
                JOIN curricula cur ON cc.curriculum_id = cur.curriculum_id
                LEFT JOIN schedules s ON c.course_id = s.course_id 
                    " . ($currentSemesterId ? "AND s.semester_id = :semester_id" : "") . "
                WHERE c.department_id = :department_id
                AND cur.status = 'active'
                AND s.schedule_id IS NULL
            ");

            $params = [':department_id' => $departmentId];
            if ($currentSemesterId) {
                $params[':semester_id'] = $currentSemesterId;
            }
            $unassignedCoursesStmt->execute($params);
            $unassignedCourses = $unassignedCoursesStmt->fetchColumn();

            // Calculate workload balance (faculty distribution score)
            $workloadBalanceStmt = $this->db->prepare("
                SELECT 
                    COUNT(DISTINCT s.faculty_id) as faculty_with_load,
                    COUNT(DISTINCT f.faculty_id) as total_faculty
                FROM faculty f
                JOIN users u ON f.user_id = u.user_id
                JOIN faculty_departments fd ON f.faculty_id = fd.faculty_id
                LEFT JOIN schedules s ON f.faculty_id = s.faculty_id 
                    " . ($currentSemesterId ? "AND s.semester_id = ?" : "") . "
                WHERE fd.department_id = ?
            ");

            $params = [];
            if ($currentSemesterId) {
                $params[] = $currentSemesterId;
            }
            $params[] = $departmentId;
            $workloadBalanceStmt->execute($params);
            $workloadBalanceData = $workloadBalanceStmt->fetch(PDO::FETCH_ASSOC);

            // Calculate balance percentage
            $workloadBalance = 0;
            if ($workloadBalanceData['total_faculty'] > 0) {
                $workloadBalance = round(($workloadBalanceData['faculty_with_load'] / $workloadBalanceData['total_faculty']) * 100);
            }

            // Add these to your existing $data array
            $data = [
                'departmentName' => $departmentName,
                'semesterInfo' => $semesterInfo,
                'facultyCount' => $facultyCount,
                'coursesCount' => $coursesCount,
                'curricula' => $curricula,
                'schedules' => $schedules,
                'scheduleStatusCounts' => $scheduleStatusCounts,
                'scheduleDistJson' => $scheduleDistJson,
                'workloadLabelsJson' => $workloadLabelsJson,
                'workloadCountsJson' => $workloadCountsJson,
                'departments' => $this->userDepartments,
                'currentDepartmentId' => $this->currentDepartmentId,
                'availableSemesters' => $availableSemesters,
                'currentSemesterId' => $currentSemesterId,
                'isHistoricalView' => $isHistoricalView,
                // ADD THESE NEW LINES:
                'conflictCount' => $conflictCount ?? 0,
                'unassignedCourses' => $unassignedCourses ?? 0,
                'workloadBalance' => $workloadBalance ?? 0
            ];

            $viewPath = __DIR__ . '/../views/chair/dashboard.php';
            error_log("dashboard: Looking for view at: $viewPath");
            error_log("dashboard: File exists: " . (file_exists($viewPath) ? 'YES' : 'NO'));

            if (!file_exists($viewPath)) {
                error_log("dashboard: View file not found at: $viewPath");
                http_response_code(404);
                echo "404 Not Found: Dashboard view missing";
                exit;
            }

            // Extract variables into the view scope
            extract($data);
            require_once $viewPath;
        } catch (Exception $e) {
            error_log("dashboard: Full error: " . $e->getMessage());
            error_log("dashboard: Stack trace: " . $e->getTraceAsString());
            http_response_code(500);
            echo "Error loading dashboard: " . htmlspecialchars($e->getMessage());
            exit;
        }
    }

    public function getChairDepartment($chairId)
    {
        $stmt = $this->db->prepare("SELECT p.department_id 
                               FROM program_chairs pc 
                               JOIN faculty f ON pc.faculty_id = f.faculty_id
                               JOIN programs p ON pc.program_id = p.program_id 
                               WHERE f.user_id = :user_id AND pc.is_current = 1");
        $stmt->execute([':user_id' => $chairId]);
        return $stmt->fetchColumn();
    }

    public function mySchedule()
    {
        $this->requireAnyRole('chair', 'dean');
        try {
            $chairId = $_SESSION['user_id'];
            // Use active semester (current or selected)
            $semester = $this->getActiveSemester();
            $semesterId = $semester['semester_id'];
            $semesterName = $semester['semester_name'] . ' Semester, A.Y ' . $semester['academic_year'];

            // Add historical indicator
            $isHistoricalView = !$semester['is_current'];
            if ($isHistoricalView) {
                $semesterName .= ' (Historical View)';
            }

            // Handle download requests
            if (isset($_GET['action']) && $_GET['action'] === 'download') {
                $this->handleDownload($chairId, $semesterId); // Pass semesterId
                return;
            }

            // Fetch faculty ID and complete faculty info with join to users table
            $facultyStmt = $this->db->prepare("
            SELECT f.*, 
                   CONCAT(COALESCE(u.title, ''), ' ', u.first_name, ' ', 
                          COALESCE(u.middle_name, ''), ' ', u.last_name, ' ', 
                          COALESCE(u.suffix, '')) AS faculty_name,
                   u.first_name, u.middle_name, u.last_name, u.title, u.suffix
            FROM faculty f 
            JOIN users u ON f.user_id = u.user_id 
            WHERE u.user_id = ?
        ");
            $facultyStmt->execute([$chairId]);
            $faculty = $facultyStmt->fetch(PDO::FETCH_ASSOC);

            if (!$faculty) {
                $error = "No faculty profile found for this user.";
                require_once __DIR__ . '/../views/chair/my_schedule.php';
                return;
            }

            $facultyId = $faculty['faculty_id'];
            $facultyName = trim($faculty['faculty_name']);
            $facultyPosition = $faculty['academic_rank'] ?? 'Not Specified';
            $employmentType = $faculty['employment_type'] ?? 'Regular';

            // Get department and college details
            $deptStmt = $this->db->prepare("
            SELECT d.department_name, c.college_name 
            FROM program_chairs pc 
            JOIN faculty f ON pc.faculty_id = f.faculty_id
            JOIN programs p ON pc.program_id = p.program_id 
            JOIN departments d ON p.department_id = d.department_id 
            JOIN colleges c ON d.college_id = c.college_id 
            WHERE f.user_id = ? AND pc.is_current = 1
        ");
            $deptStmt->execute([$chairId]);
            $department = $deptStmt->fetch(PDO::FETCH_ASSOC);
            $departmentName = $department['department_name'] ?? 'Not Assigned';
            $collegeName = $department['college_name'] ?? 'Not Assigned';

            $semesterStmt = $this->db->query("SELECT semester_id, semester_name, academic_year FROM semesters WHERE is_current = 1");
            $semester = $semesterStmt->fetch(PDO::FETCH_ASSOC);

            if (!$semester) {
                error_log("mySchedule: No current semester found");
                $error = "No current semester defined. Please contact the administrator to set the current semester.";
                require_once __DIR__ . '/../views/chair/my_schedule.php';
                return;
            }

            $semesterId = $semester['semester_id'];
            $semesterName = $semester['semester_name'] . ' Semester, A.Y ' . $semester['academic_year'];
            error_log("mySchedule: Current semester ID: $semesterId, Name: $semesterName");

            // ✅ FIXED: Get schedules WITHOUT premature aggregation
            $schedulesStmt = $this->db->prepare("
            SELECT 
                s.schedule_id, 
                c.course_code, 
                c.course_name, 
                c.units,
                COALESCE(r.room_name, 'Online') as room_name, 
                s.day_of_week, 
                s.start_time, 
                s.end_time, 
                COALESCE(s.component_type, 'lecture') as component_type,
                s.schedule_type,
                COALESCE(sec.section_name, 'N/A') AS section_name, 
                COALESCE(sec.current_students, 0) as current_students,
                sec.year_level,
                COALESCE(TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time) / 60, 0) AS duration_hours
            FROM schedules s
            LEFT JOIN courses c ON s.course_id = c.course_id
            LEFT JOIN sections sec ON s.section_id = sec.section_id
            LEFT JOIN classrooms r ON s.room_id = r.room_id
            WHERE s.faculty_id = ? 
                AND s.semester_id = ?
                AND s.status != 'Rejected'
            ORDER BY c.course_code, 
                     COALESCE(s.component_type, 'lecture'),
                     FIELD(s.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
                     s.start_time
        ");
            $schedulesStmt->execute([$facultyId, $semesterId]);
            $rawSchedules = $schedulesStmt->fetchAll(PDO::FETCH_ASSOC);

            // ✅ FIXED: Group schedules properly, keeping component type distinction
            $groupedSchedules = [];

            foreach ($rawSchedules as $schedule) {
                // Create unique key that includes component type
                $key = $schedule['course_code'] . '|' .
                    $schedule['component_type'] . '|' .
                    $schedule['start_time'] . '|' .
                    $schedule['end_time'] . '|' .
                    $schedule['section_name'];

                if (!isset($groupedSchedules[$key])) {
                    $groupedSchedules[$key] = $schedule;
                    $groupedSchedules[$key]['days'] = [];
                    $groupedSchedules[$key]['total_duration'] = 0;
                }

                $groupedSchedules[$key]['days'][] = $schedule['day_of_week'];
                $groupedSchedules[$key]['total_duration'] += floatval($schedule['duration_hours']);
            }

            // ✅ FIXED: Create final schedules array with proper calculations
            $schedules = [];
            foreach ($groupedSchedules as $schedule) {
                $componentType = strtolower($schedule['component_type']);

                // Calculate hours based on component type
                $schedule['lecture_hours'] = 0;
                $schedule['lab_hours'] = 0;

                if ($componentType === 'laboratory') {
                    $schedule['lab_hours'] = $schedule['total_duration'];
                    $schedule['schedule_type'] = 'Laboratory';
                } else {
                    $schedule['lecture_hours'] = $schedule['total_duration'];
                    $schedule['schedule_type'] = 'Lecture';
                }

                // Format days
                $schedule['day_of_week'] = $this->schedulingService->formatScheduleDays(implode(', ', $schedule['days']));

                // Keep duration_hours for display
                $schedule['duration_hours'] = $schedule['total_duration'];

                // Student count
                $schedule['student_count'] = $schedule['current_students'];

                unset($schedule['days']);
                unset($schedule['total_duration']);

                $schedules[] = $schedule;
            }

            error_log("mySchedule: Fetched " . count($schedules) . " grouped schedules for faculty_id $facultyId in semester $semesterId");

            $showAllSchedules = false;
            if (empty($schedules)) {
                error_log("mySchedule: No schedules found for current semester, trying to fetch all schedules");

                // Repeat for all semesters
                $schedulesStmt = $this->db->prepare("
                SELECT 
                    s.schedule_id, 
                    c.course_code, 
                    c.course_name, 
                    c.units,
                    COALESCE(r.room_name, 'Online') as room_name, 
                    s.day_of_week, 
                    s.start_time, 
                    s.end_time, 
                    COALESCE(s.component_type, 'lecture') as component_type,
                    s.schedule_type,
                    COALESCE(sec.section_name, 'N/A') AS section_name, 
                    COALESCE(sec.current_students, 0) as current_students,
                    sec.year_level,
                    COALESCE(TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time) / 60, 0) AS duration_hours
                FROM schedules s
                LEFT JOIN courses c ON s.course_id = c.course_id
                LEFT JOIN sections sec ON s.section_id = sec.section_id
                LEFT JOIN classrooms r ON s.room_id = r.room_id
                WHERE s.faculty_id = ?
                    AND s.status != 'Rejected'
                ORDER BY c.course_code, 
                         COALESCE(s.component_type, 'lecture'),
                         s.start_time
            ");
                $schedulesStmt->execute([$facultyId]);
                $rawSchedules = $schedulesStmt->fetchAll(PDO::FETCH_ASSOC);

                // Same grouping logic
                $groupedSchedules = [];
                foreach ($rawSchedules as $schedule) {
                    $key = $schedule['course_code'] . '|' .
                        $schedule['component_type'] . '|' .
                        $schedule['start_time'] . '|' .
                        $schedule['end_time'] . '|' .
                        $schedule['section_name'];

                    if (!isset($groupedSchedules[$key])) {
                        $groupedSchedules[$key] = $schedule;
                        $groupedSchedules[$key]['days'] = [];
                        $groupedSchedules[$key]['total_duration'] = 0;
                    }

                    $groupedSchedules[$key]['days'][] = $schedule['day_of_week'];
                    $groupedSchedules[$key]['total_duration'] += floatval($schedule['duration_hours']);
                }

                $schedules = [];
                foreach ($groupedSchedules as $schedule) {
                    $componentType = strtolower($schedule['component_type']);

                    $schedule['lecture_hours'] = 0;
                    $schedule['lab_hours'] = 0;

                    if ($componentType === 'laboratory') {
                        $schedule['lab_hours'] = $schedule['total_duration'];
                        $schedule['schedule_type'] = 'Laboratory';
                    } else {
                        $schedule['lecture_hours'] = $schedule['total_duration'];
                        $schedule['schedule_type'] = 'Lecture';
                    }

                    $schedule['day_of_week'] = $this->schedulingService->formatScheduleDays(implode(', ', $schedule['days']));
                    $schedule['duration_hours'] = $schedule['total_duration'];
                    $schedule['student_count'] = $schedule['current_students'];

                    unset($schedule['days']);
                    unset($schedule['total_duration']);

                    $schedules[] = $schedule;
                }

                $showAllSchedules = true;
                error_log("mySchedule: Fetched " . count($schedules) . " total grouped schedules for faculty_id $facultyId");
            }

            // ✅ FIXED: Calculate totals with proper component type handling
            $totalHours = 0;
            $totalLectureHours = 0;
            $totalLabHours = 0;
            $preparations = [];

            foreach ($schedules as $schedule) {
                $totalHours += floatval($schedule['duration_hours']);
                $totalLectureHours += floatval($schedule['lecture_hours']);
                $totalLabHours += floatval($schedule['lab_hours']);

                // Track unique courses for preparations
                $prepKey = $schedule['course_code'] . '-' . $schedule['component_type'];
                $preparations[$prepKey] = true;
            }

            $totalLabHoursX075 = $totalLabHours * 0.75;
            $noOfPreparations = count($preparations);
            $actualTeachingLoad = $totalLectureHours + $totalLabHoursX075;
            $equivalTeachingLoad = floatval($faculty['equiv_teaching_load'] ?? 0);
            $totalWorkingLoad = $actualTeachingLoad + $equivalTeachingLoad;
            $excessHours = max(0, $totalWorkingLoad - 24);

            error_log("mySchedule: Calculations - Total hours: $totalHours, Lecture: $totalLectureHours, Lab: $totalLabHours, Preparations: $noOfPreparations");

            // Pass all data to view
            $facultyData = [
                'faculty_id' => $facultyId,
                'faculty_name' => $facultyName,
                'academic_rank' => $facultyPosition,
                'employment_type' => $employmentType,
                'bachelor_degree' => $faculty['bachelor_degree'] ?? 'Not specified',
                'master_degree' => $faculty['master_degree'] ?? 'Not specified',
                'doctorate_degree' => $faculty['doctorate_degree'] ?? 'Not specified',
                'post_doctorate_degree' => $faculty['post_doctorate_degree'] ?? 'Not applicable',
                'designation' => $faculty['designation'] ?? 'Not specified',
                'classification' => $faculty['classification'] ?? 'Not specified',
                'advisory_class' => $faculty['advisory_class'] ?? 'Not assigned',
                'total_lecture_hours' => round($totalLectureHours, 2),
                'total_laboratory_hours' => round($totalLabHours, 2),
                'total_laboratory_hours_x075' => round($totalLabHoursX075, 2),
                'no_of_preparation' => $noOfPreparations,
                'actual_teaching_load' => round($actualTeachingLoad, 2),
                'equiv_teaching_load' => $equivalTeachingLoad,
                'total_working_load' => round($totalWorkingLoad, 2),
                'excess_hours' => round($excessHours, 2)
            ];

            require_once __DIR__ . '/../views/chair/my_schedule.php';
        } catch (Exception $e) {
            error_log("mySchedule: Full error: " . $e->getMessage());
            http_response_code(500);
            echo "Error loading schedule: " . htmlspecialchars($e->getMessage());
            exit;
        }
    }

    private function handleDownload($chairId)
    {
        $format = $_GET['format'] ?? 'pdf';

        // Fetch all necessary data for download
        $facultyStmt = $this->db->prepare("
        SELECT f.*, 
               CONCAT(COALESCE(u.title, ''), ' ', u.first_name, ' ', 
                      COALESCE(u.middle_name, ''), ' ', u.last_name, ' ', 
                      COALESCE(u.suffix, '')) AS faculty_name,
               u.first_name, u.middle_name, u.last_name, u.title, u.suffix
        FROM faculty f 
        JOIN users u ON f.user_id = u.user_id 
        WHERE u.user_id = ?
        ");
        $facultyStmt->execute([$chairId]);
        $faculty = $facultyStmt->fetch(PDO::FETCH_ASSOC);

        if (!$faculty) {
            http_response_code(404);
            echo "No faculty profile found.";
            exit;
        }

        $facultyId = $faculty['faculty_id'];
        $facultyName = trim($faculty['faculty_name']);

        // Get department and college details
        $deptStmt = $this->db->prepare("
        SELECT d.department_name, c.college_name 
        FROM program_chairs pc 
        JOIN faculty f ON pc.faculty_id = f.faculty_id
        JOIN programs p ON pc.program_id = p.program_id 
        JOIN departments d ON p.department_id = d.department_id 
        JOIN colleges c ON d.college_id = c.college_id 
        WHERE f.user_id = ? AND pc.is_current = 1
        ");
        $deptStmt->execute([$chairId]);
        $department = $deptStmt->fetch(PDO::FETCH_ASSOC);
        $departmentName = $department['department_name'] ?? 'Not Assigned';
        $collegeName = $department['college_name'] ?? 'Not Assigned';

        // Get current semester
        $semesterStmt = $this->db->query("SELECT semester_id, semester_name, academic_year FROM semesters WHERE is_current = 1");
        $semester = $semesterStmt->fetch(PDO::FETCH_ASSOC);

        if (!$semester) {
            http_response_code(404);
            echo "No current semester defined.";
            exit;
        }

        $semesterId = $semester['semester_id'];
        $semesterName = $semester['semester_name'] . ' Semester, A.Y ' . $semester['academic_year'];

        // Fetch schedules (same logic as your existing method)
        $schedulesStmt = $this->db->prepare("
        SELECT s.schedule_id, c.course_code, c.course_name, c.units,
               r.room_name, s.day_of_week, s.start_time, s.end_time, s.schedule_type, 
               COALESCE(sec.section_name, 'N/A') AS section_name, sec.current_students,
               TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time) / 60 AS duration_hours,
               sec.year_level,
               CASE 
                   WHEN s.schedule_type = 'Laboratory' THEN TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time) / 60
                   ELSE 0 
               END AS lab_hours,
               CASE 
                   WHEN s.schedule_type = 'Lecture' THEN TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time) / 60
                   ELSE 0 
               END AS lecture_hours,
               COUNT(sec.current_students) as student_count
        FROM schedules s
        LEFT JOIN courses c ON s.course_id = c.course_id
        LEFT JOIN sections sec ON s.section_id = sec.section_id
        LEFT JOIN classrooms r ON s.room_id = r.room_id
        WHERE s.faculty_id = ? AND s.semester_id = ?
        GROUP BY s.schedule_id, c.course_code, c.course_name, r.room_name, 
                 s.start_time, s.end_time, s.schedule_type, sec.section_name
        ORDER BY c.course_code, s.start_time
        ");
        $schedulesStmt->execute([$facultyId, $semesterId]);
        $rawSchedules = $schedulesStmt->fetchAll(PDO::FETCH_ASSOC);

        // Group schedules (same logic as your existing method)
        $groupedSchedules = [];
        foreach ($rawSchedules as $schedule) {
            $key = $schedule['course_code'] . '|' . $schedule['start_time'] . '|' . $schedule['end_time'] . '|' . $schedule['schedule_type'] . '|' . $schedule['section_name'] . '|' . $schedule['student_count'];

            if (!isset($groupedSchedules[$key])) {
                $groupedSchedules[$key] = $schedule;
                $groupedSchedules[$key]['days'] = [];
            }

            $groupedSchedules[$key]['days'][] = $schedule['day_of_week'];
        }

        $schedules = [];
        foreach ($groupedSchedules as $schedule) {
            $schedule['day_of_week'] = $this->schedulingService->formatScheduleDays(implode(', ', $schedule['days']));
            unset($schedule['days']);
            $schedules[] = $schedule;
        }

        // Calculate totals
        $totalHours = 0;
        $totalLectureHours = 0;
        $totalLabHours = 0;
        $preparations = [];

        foreach ($schedules as $schedule) {
            $totalHours += $schedule['duration_hours'];
            $totalLectureHours += $schedule['lecture_hours'];
            $totalLabHours += $schedule['lab_hours'];
            $preparations[$schedule['course_code']] = true;
        }

        $totalLabHoursX075 = $totalLabHours * 0.75;
        $noOfPreparations = count($preparations);
        $actualTeachingLoad = $totalLectureHours + $totalLabHoursX075;
        $equivalTeachingLoad = $faculty['equiv_teaching_load'] ?? 0;
        $totalWorkingLoad = $actualTeachingLoad + $equivalTeachingLoad;
        $excessHours = max(0, $totalWorkingLoad - 24);

        // Faculty data
        $facultyData = [
            'faculty_id' => $facultyId,
            'faculty_name' => $facultyName,
            'academic_rank' => $faculty['academic_rank'] ?? 'Not specified',
            'employment_type' => $faculty['employment_type'] ?? 'Regular',
            'bachelor_degree' => $faculty['bachelor_degree'] ?? 'Not specified',
            'master_degree' => $faculty['master_degree'] ?? 'Not specified',
            'doctorate_degree' => $faculty['doctorate_degree'] ?? 'Not specified',
            'post_doctorate_degree' => $faculty['post_doctorate_degree'] ?? 'Not applicable',
            'designation' => $faculty['designation'] ?? 'Not specified',
            'classification' => $faculty['classification'] ?? 'Not specified',
            'advisory_class' => $faculty['advisory_class'] ?? 'Not assigned',
            'total_lecture_hours' => $totalLectureHours,
            'total_laboratory_hours' => $totalLabHours,
            'total_laboratory_hours_x075' => $totalLabHoursX075,
            'no_of_preparation' => $noOfPreparations,
            'actual_teaching_load' => $actualTeachingLoad,
            'equiv_teaching_load' => $equivalTeachingLoad,
            'total_working_load' => $totalWorkingLoad,
            'excess_hours' => $excessHours
        ];

        // Generate download based on format
        // if ($format === 'pdf') {
        //     $this->schedulingService->generateOfficialPDF($schedules, $semesterName, $collegeName, $facultyData, $facultyName);
        // } elseif ($format === 'excel') {
        //     $this->schedulingService->generateOfficialExcel($schedules, $semesterName, $collegeName, $facultyData, $facultyName);
        // }
    }

    private function getCurrentSemester()
    {
        try {
            error_log("getCurrentSemester: Querying for current semester");
            // First, try to find the semester marked as current
            $stmt = $this->db->prepare("
                SELECT semester_id, semester_name, academic_year 
                FROM semesters 
                WHERE is_current = 1
            ");
            $stmt->execute();
            $semester = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($semester) {
                error_log("getCurrentSemester: Found current semester - semester_id: {$semester['semester_id']}, semester_name: {$semester['semester_name']}, academic_year: {$semester['academic_year']}");
                return $semester;
            }

            error_log("getCurrentSemester: No semester with is_current = 1, checking date range");
            // Fall back to date range
            $stmt = $this->db->prepare("
                SELECT semester_id, semester_name, academic_year 
                FROM semesters 
                WHERE CURRENT_DATE BETWEEN start_date AND end_date
            ");
            $stmt->execute();
            $semester = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($semester) {
                error_log("getCurrentSemester: Found semester by date range - semester_id: {$semester['semester_id']}, semester_name: {$semester['semester_name']}, academic_year: {$semester['academic_year']}");
            } else {
                error_log("getCurrentSemester: No semester found for current date");
            }

            return $semester ?: null;
        } catch (PDOException $e) {
            error_log("getCurrentSemester: Error - " . $e->getMessage());
            return null;
        }
    }

    private function getCurricula(int $departmentId): array
    {
        return $this->curriculumRepo()->getActiveCurricula($departmentId);
    }

    private function getClassrooms(int $departmentId): array
    {
        return $this->curriculumRepo()->getAvailableClassrooms($departmentId);
    }

    private function getFaculty(int $departmentId, int $collegeId): array
    {
        return $this->curriculumRepo()->getFacultyByDepartment($departmentId, $collegeId);
    }

    private function getSections(int $departmentId, int $semesterId): array
    {
        return $this->curriculumRepo()->getSections($departmentId, $semesterId);
    }

    private function getCourses(int $departmentId): array
    {
        return $this->curriculumRepo()->getCoursesByDepartment($departmentId);
    }

    private function getCurriculumCourses(int $curriculumId, string $semesterName = ''): array
    {
        return $this->curriculumRepo()->getCurriculumCourses($curriculumId, $semesterName);
    }

    private function getCourseDetails(int $courseId): array|false
    {
        return $this->curriculumRepo()->getCourseById($courseId);
    }

    private function getScheduleById(int $scheduleId): array|false
    {
        return $this->scheduleRepo()->getById($scheduleId);
    }

    private function loadSchedules(int $departmentId, array $currentSemester): array
    {
        if (!$departmentId || !$currentSemester) return [];
        return $this->scheduleRepo()->getByDepartmentAndSemester($departmentId, (int)$currentSemester['semester_id']);
    }

    private function loadCommonData(int $departmentId, array $currentSemester, int $collegeId): array
    {
        return [
            'curricula'        => $this->getCurricula($departmentId),
            'classrooms'       => $this->getClassrooms($departmentId),
            'faculty'          => $this->getFaculty($departmentId, $collegeId),
            'sections'         => $this->getSections($departmentId, (int)$currentSemester['semester_id']),
            'curriculumCourses' => [],
            'semester'         => $currentSemester,
        ];
    }

    private function verifyScheduleOwnership(int $scheduleId, int $departmentId): bool
    {
        return $this->scheduleRepo()->verifyOwnership($scheduleId, $departmentId);
    }

    private function getChairCollege(int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT college_id FROM users WHERE user_id = :uid AND is_active = 1");
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row && $row['college_id'] ? ['college_id' => $row['college_id']] : null;
    }

    // ──────────────────────────────────────────────────────────────
    // checkScheduleConflicts — delegates to ScheduleService
    // ──────────────────────────────────────────────────────────────

    private function checkScheduleConflicts(
        int $sectionId,
        int $facultyId,
        ?int $roomId,
        string $dayOfWeek,
        string $startTime,
        string $endTime,
        int $semesterId,
        ?int $excludeScheduleId = null
    ): array {
        return $this->scheduleService()->checkScheduleConflicts(
            $sectionId,
            $facultyId,
            $roomId,
            $dayOfWeek,
            $startTime,
            $endTime,
            $semesterId,
            $excludeScheduleId
        );
    }

    // ──────────────────────────────────────────────────────────────
    // checkScheduleDeadlineStatus
    // ──────────────────────────────────────────────────────────────

    public function checkScheduleDeadlineStatus(int $userDepartmentId): array
    {
        if (!$userDepartmentId) return ['locked' => false, 'message' => 'Department not set'];
        return $this->scheduleService()->checkDeadlineStatus($userDepartmentId);
    }

    // ──────────────────────────────────────────────────────────────
    // manageSchedule (page render)
    // ──────────────────────────────────────────────────────────────

    public function manageSchedule(): void
    {
        $this->requireAnyRole('chair', 'dean');

        $chairId      = $_SESSION['user_id'] ?? null;
        $departmentId = $this->currentDepartmentId ?: $this->getChairDepartment($chairId);

        $currentSemester = $this->getActiveSemester();
        $_SESSION['current_semester'] = $currentSemester;

        $isHistoricalView = !$currentSemester['is_current'];
        $error = $success = null;

        $schedules  = $this->loadSchedules($departmentId, $currentSemester);
        $collegeId  = $this->getChairCollege($chairId)['college_id'] ?? null;

        $cachedData = $this->loadCommonData($departmentId, $currentSemester, $collegeId);
        $_SESSION['schedule_cache'][$departmentId] = $cachedData;

        $curricula   = $cachedData['curricula'];
        $classrooms  = $cachedData['classrooms'];
        $faculty     = $this->getFaculty($departmentId, $collegeId);
        $sections    = $this->getSections($departmentId, (int)$currentSemester['semester_id']);

        $curriculumCourses = [];
        if (!empty($curricula)) {
            $curriculumCourses = $this->getCurriculumCourses(
                (int)$curricula[0]['curriculum_id'],
                $currentSemester['semester_name']
            );
        }

        $jsData = [
            'departmentId'       => $departmentId,
            'collegeId'          => $collegeId,
            'currentSemester'    => $currentSemester,
            'isHistoricalView'   => $isHistoricalView,
            'sectionsData'       => $sections,
            'currentAcademicYear' => $currentSemester['academic_year'] ?? '',
            'faculty'            => $faculty,
            'classrooms'         => $classrooms,
            'curricula'          => $curricula,
            'curriculumCourses'  => $curriculumCourses,
            'schedules'          => $schedules,
        ];

        define('IN_MANAGE_SCHEDULE', true);
        require_once __DIR__ . '/../views/chair/schedule_management.php';
    }

    public function generateSchedulesAjax(): void
    {
        header('Content-Type: application/json');
        set_time_limit(300);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        $chairId         = $_SESSION['user_id'] ?? null;
        $departmentId    = $this->currentDepartmentId ?: $this->getChairDepartment($chairId);
        $currentSemester = $this->getCurrentSemester();
        $collegeData     = $this->getChairCollege($chairId);
        $collegeId       = $collegeData['college_id'] ?? null;

        if (!$chairId || !$currentSemester || !$departmentId || !$collegeId) {
            echo json_encode(['success' => false, 'message' => 'Missing required context']);
            exit;
        }

        $action = $_POST['action'] ?? '';

        switch ($action) {

            // ── Curriculum courses ───────────────────────────────
            case 'get_curriculum_courses':
                $curriculumId = (int)($_POST['curriculum_id'] ?? 0);
                $semName      = $currentSemester['semester_name'] ?? '';
                if (!$curriculumId) {
                    echo json_encode(['success' => false, 'message' => 'Missing curriculum ID']);
                    break;
                }
                $courses = $this->getCurriculumCourses($curriculumId, $semName);
                echo json_encode(['success' => true, 'courses' => $courses]);
                break;

            // ── Add schedule ─────────────────────────────────────
            case 'add_schedule':
                $this->handleAddSchedule($_POST, $departmentId, $currentSemester, $collegeId);
                break;

            // ── Update schedule ──────────────────────────────────
            case 'update_schedule':
                $this->handleUpdateSchedule($_POST, $departmentId, $currentSemester, $collegeId);
                break;

            // ── Auto-generate ────────────────────────────────────
            case 'generate_schedule':
                $curriculumId = (int)($_POST['curriculum_id'] ?? 0);
                $yearLevels   = $_POST['year_levels'] ?? [];
                if (!is_array($yearLevels)) {
                    $yearLevels = array_filter(array_map('trim', explode(',', $yearLevels)));
                }
                if (empty($yearLevels)) {
                    $yearLevels = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
                }
                if (!$curriculumId) {
                    echo json_encode(['success' => false, 'message' => 'Missing curriculum ID']);
                    break;
                }

                $cached     = $_SESSION['schedule_cache'][$departmentId] ?? $this->loadCommonData($departmentId, $currentSemester, $collegeId);
                $semName    = strtolower($currentSemester['semester_name'] ?? '');
                $semType    = in_array($semName, ['midyear', 'summer', 'mid-year', 'mid year', '3rd']) ? $semName : 'regular';

                $start      = microtime(true);
                $service    = $this->scheduleService();

                $service->generateSchedules(
                    $curriculumId,
                    $yearLevels,
                    $collegeId,
                    $currentSemester,
                    $cached['classrooms'],
                    $cached['faculty'],
                    $departmentId,
                    $semType
                );

                $elapsed    = round(microtime(true) - $start, 2);

                $this->scheduleRepo()->removeDuplicates($departmentId, (int)$currentSemester['semester_id']);
                $consolidated = $this->getConsolidatedSchedules($departmentId, $currentSemester);

                $allCodes      = array_column($this->getCurriculumCourses($curriculumId, $currentSemester['semester_name']), 'course_code');
                $assignedCodes = array_unique(array_column($consolidated, 'course_code'));
                $unassigned    = array_values(array_filter(
                    $this->getCurriculumCourses($curriculumId, $currentSemester['semester_name']),
                    fn($c) => !in_array($c['course_code'], $assignedCodes)
                ));

                $total       = count($allCodes);
                $successRate = $total > 0 ? number_format((count($assignedCodes) / $total) * 100, 2) . '%' : '0%';

                echo json_encode([
                    'success'          => true,
                    'schedules'        => $consolidated,
                    'unassignedCourses' => $unassigned,
                    'totalCourses'     => $total,
                    'totalSections'    => count(array_unique(array_column($consolidated, 'section_id'))),
                    'successRate'      => $successRate,
                    'executionTime'    => $elapsed,
                    'message'          => "Generated " . count($consolidated) . " schedules in {$elapsed}s",
                ]);
                break;

            // ── Delete all ───────────────────────────────────────
            case 'delete_schedules':
                $confirm = $_POST['confirm'] ?? null;
                if ($confirm !== 'true' && $confirm !== true && $confirm !== 1 && $confirm !== '1') {
                    echo json_encode(['success' => false, 'message' => 'Confirmation required']);
                    break;
                }
                $this->deleteAllSchedules();
                break;

            // ── Delete single ────────────────────────────────────
            case 'delete_schedule':
                $this->deleteSingleSchedule((int)($_POST['schedule_id'] ?? 0), $departmentId);
                break;

            // ── Drag update ──────────────────────────────────────
            case 'update_schedule_drag':
                $this->updateScheduleDrag();
                return;

                // ── Drag conflict check ──────────────────────────────
            case 'check_drag_conflicts':
                $this->checkDragConflicts();
                return;

            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        exit;
    }

    private function handleAddSchedule(array $data, int $departmentId, array $currentSemester, int $collegeId): void
    {
        try {
            $required = ['course_code', 'course_name', 'section_name', 'faculty_name', 'day_of_week', 'start_time', 'end_time'];
            foreach ($required as $f) {
                if (empty($data[$f])) {
                    echo json_encode(['success' => false, 'message' => "Missing: $f"]);
                    return;
                }
            }

            $curricula = $this->getCurricula($departmentId);
            if (empty($curricula)) {
                echo json_encode(['success' => false, 'message' => 'No active curriculum']);
                return;
            }

            $courseId = null;
            foreach ($this->getCurriculumCourses((int)$curricula[0]['curriculum_id'], $currentSemester['semester_name']) as $c) {
                if ($c['course_code'] === $data['course_code']) {
                    $courseId = $c['course_id'];
                    break;
                }
            }
            if (!$courseId) {
                echo json_encode(['success' => false, 'message' => 'Course not found in curriculum: ' . $data['course_code']]);
                return;
            }

            $facultyId = null;
            foreach ($this->getFaculty($departmentId, $collegeId) as $f) {
                if (str_contains($f['name'], $data['faculty_name'])) {
                    $facultyId = $f['faculty_id'];
                    break;
                }
            }
            if (!$facultyId) {
                echo json_encode(['success' => false, 'message' => 'Faculty not found: ' . $data['faculty_name']]);
                return;
            }

            $sectionId = null;
            foreach ($this->getSections($departmentId, (int)$currentSemester['semester_id']) as $s) {
                if ($s['section_name'] === $data['section_name']) {
                    $sectionId = $s['section_id'];
                    break;
                }
            }
            if (!$sectionId) {
                echo json_encode(['success' => false, 'message' => 'Section not found: ' . $data['section_name']]);
                return;
            }

            $roomId = null;
            if (!empty($data['room_name']) && $data['room_name'] !== 'Online') {
                foreach ($this->getClassrooms($departmentId) as $r) {
                    if ($r['room_name'] === $data['room_name']) {
                        $roomId = $r['room_id'];
                        break;
                    }
                }
            }

            $service      = $this->scheduleService();
            $days         = $service->expandDayPattern($data['day_of_week']) ?: [$data['day_of_week']];
            $saved        = [];
            $allConflicts = [];

            foreach ($days as $day) {
                $conflicts = $service->checkScheduleConflicts(
                    $sectionId,
                    $facultyId,
                    $roomId,
                    $day,
                    $data['start_time'],
                    $data['end_time'],
                    (int)$currentSemester['semester_id'],
                    null
                );
                if (!empty($conflicts)) {
                    $allConflicts = array_merge($allConflicts, $conflicts);
                    continue;
                }

                $id = $this->scheduleRepo()->insert([
                    'course_id'     => $courseId,
                    'section_id'    => $sectionId,
                    'faculty_id'    => $facultyId,
                    'room_id'       => $roomId,
                    'day_of_week'   => $day,
                    'start_time'    => $data['start_time'],
                    'end_time'      => $data['end_time'],
                    'semester_id'   => $currentSemester['semester_id'],
                    'department_id' => $departmentId,
                    'schedule_type' => $data['schedule_type'] ?? 'f2f',
                    'status'        => 'Pending',
                    'is_public'     => 0,
                ]);
                $saved[] = $this->getScheduleById($id);
            }

            if (empty($saved)) {
                echo json_encode(['success' => false, 'message' => 'Conflicts on all days', 'conflicts' => array_unique($allConflicts)]);
                return;
            }
            echo json_encode([
                'success'        => true,
                'message'        => 'Schedule(s) added for ' . count($saved) . ' day(s)',
                'schedules'      => $saved,
                'partial_success' => count($saved) < count($days),
                'failed_days'    => count($days) - count($saved),
            ]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    // ──────────────────────────────────────────────────────────────
    // handleUpdateSchedule
    // ──────────────────────────────────────────────────────────────

    private function handleUpdateSchedule(array $data, int $departmentId, array $currentSemester, int $collegeId): void
    {
        try {
            $scheduleId = (int)($data['schedule_id'] ?? 0);
            if ($scheduleId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid schedule ID']);
                return;
            }
            if (!$this->verifyScheduleOwnership($scheduleId, $departmentId)) {
                echo json_encode(['success' => false, 'message' => 'Schedule not found or access denied']);
                return;
            }

            // Resolve IDs
            $courseId = null;
            foreach ($this->getCurriculumCourses((int)($this->getCurricula($departmentId)[0]['curriculum_id'] ?? 0), $currentSemester['semester_name']) as $c) {
                if ($c['course_code'] === $data['course_code']) {
                    $courseId = $c['course_id'];
                    break;
                }
            }
            if (!$courseId) {
                foreach ($this->getCourses($departmentId) as $c) {
                    if ($c['course_code'] === $data['course_code']) {
                        $courseId = $c['course_id'];
                        break;
                    }
                }
            }
            if (!$courseId) {
                echo json_encode(['success' => false, 'message' => 'Course not found: ' . $data['course_code']]);
                return;
            }

            $facultyId = null;
            foreach ($this->getFaculty($departmentId, $collegeId) as $f) {
                if (str_contains($f['name'], $data['faculty_name'])) {
                    $facultyId = $f['faculty_id'];
                    break;
                }
            }
            if (!$facultyId) {
                echo json_encode(['success' => false, 'message' => 'Faculty not found: ' . $data['faculty_name']]);
                return;
            }

            $sectionId = null;
            foreach ($this->getSections($departmentId, (int)$currentSemester['semester_id']) as $s) {
                if ($s['section_name'] === $data['section_name']) {
                    $sectionId = $s['section_id'];
                    break;
                }
            }
            if (!$sectionId) {
                echo json_encode(['success' => false, 'message' => 'Section not found: ' . $data['section_name']]);
                return;
            }

            $roomId = null;
            if (!empty($data['room_name']) && $data['room_name'] !== 'Online') {
                foreach ($this->getClassrooms($departmentId) as $r) {
                    if ($r['room_name'] === $data['room_name']) {
                        $roomId = $r['room_id'];
                        break;
                    }
                }
            }

            $conflicts = $this->checkScheduleConflicts(
                $sectionId,
                $facultyId,
                $roomId,
                $data['day_of_week'],
                $data['start_time'],
                $data['end_time'],
                (int)$currentSemester['semester_id'],
                $scheduleId
            );
            if (!empty($conflicts)) {
                echo json_encode(['success' => false, 'message' => 'Conflicts detected', 'conflicts' => $conflicts]);
                return;
            }

            $this->scheduleRepo()->update($scheduleId, [
                'course_id'     => $courseId,
                'section_id'    => $sectionId,
                'faculty_id'    => $facultyId,
                'room_id'       => $roomId,
                'day_of_week'   => $data['day_of_week'],
                'start_time'    => $data['start_time'],
                'end_time'      => $data['end_time'],
                'schedule_type' => $data['schedule_type'] ?? 'f2f',
            ]);

            echo json_encode(['success' => true, 'message' => 'Schedule updated', 'schedule' => $this->getScheduleById($scheduleId)]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    // ──────────────────────────────────────────────────────────────
    // deleteAllSchedules
    // ──────────────────────────────────────────────────────────────

    public function deleteAllSchedules(): void
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['confirm'] ?? '') !== 'true') {
            echo json_encode(['success' => false, 'message' => 'Invalid request or confirmation missing']);
            exit;
        }

        $chairId      = $_SESSION['user_id'] ?? null;
        $departmentId = $this->currentDepartmentId ?: $this->getChairDepartment($chairId);
        if (!$departmentId) {
            echo json_encode(['success' => false, 'message' => 'Could not determine department']);
            exit;
        }

        try {
            $this->db->beginTransaction();
            $deleted = $this->scheduleRepo()->deleteByDepartment($departmentId);
            $this->db->commit();
            $this->scheduleRepo()->resetAutoIncrement();
            echo json_encode(['success' => true, 'message' => "Deleted $deleted schedules", 'deleted_count' => $deleted]);
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // ──────────────────────────────────────────────────────────────
    // deleteSingleSchedule
    // ──────────────────────────────────────────────────────────────

    private function deleteSingleSchedule(int $scheduleId, int $departmentId): void
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['confirm'] ?? '') !== 'true') {
            echo json_encode(['success' => false, 'message' => 'Confirmation required']);
            exit;
        }
        if ($scheduleId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid schedule ID']);
            exit;
        }

        try {
            if (!$this->verifyScheduleOwnership($scheduleId, $departmentId)) {
                echo json_encode(['success' => false, 'message' => 'Schedule not found or access denied']);
                exit;
            }
            $ok = $this->scheduleRepo()->deleteById($scheduleId);
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Deleted' : 'Delete failed', 'deleted_count' => (int)$ok]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // ──────────────────────────────────────────────────────────────
    // updateScheduleDrag
    // ──────────────────────────────────────────────────────────────

    public function updateScheduleDrag(): void
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid method']);
            exit;
        }

        $scheduleId = (int)($_POST['schedule_id'] ?? 0);
        $day        = $_POST['day_of_week'] ?? '';
        $start      = $_POST['start_time']  ?? '';
        $end        = $_POST['end_time']    ?? '';

        if (!$scheduleId || !$day || !$start || !$end) {
            echo json_encode(['success' => false, 'message' => 'Missing fields']);
            exit;
        }

        try {
            $row = $this->scheduleRepo()->getById($scheduleId);
            if (!$row) {
                echo json_encode(['success' => false, 'message' => 'Schedule not found']);
                exit;
            }

            $semesterId = (int)$this->getCurrentSemester()['semester_id'];
            $conflicts  = $this->checkScheduleConflicts(
                (int)$row['section_id'],
                (int)$row['faculty_id'],
                $row['room_id'] ? (int)$row['room_id'] : null,
                $day,
                $start,
                $end,
                $semesterId,
                $scheduleId
            );
            if (!empty($conflicts)) {
                echo json_encode(['success' => false, 'message' => 'Conflicts', 'conflicts' => $conflicts]);
                exit;
            }

            $ok = $this->scheduleRepo()->updateDrag($scheduleId, $day, $start, $end);
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Updated' : 'Update failed']);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // ──────────────────────────────────────────────────────────────
    // checkDragConflicts
    // ──────────────────────────────────────────────────────────────

    public function checkDragConflicts(): void
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid method']);
            exit;
        }

        $scheduleId = (int)($_POST['schedule_id'] ?? 0);
        $sectionId  = (int)($_POST['section_id']  ?? 0);
        $facultyId  = (int)($_POST['faculty_id']  ?? 0);
        $roomId     = $_POST['room_id']     ? (int)$_POST['room_id'] : null;
        $day        = $_POST['day_of_week'] ?? '';
        $start      = $_POST['start_time']  ?? '';
        $end        = $_POST['end_time']    ?? '';
        $semesterId = (int)($_POST['semester_id'] ?? 0);

        if (!$scheduleId || !$sectionId || !$facultyId || !$day || !$start || !$end || !$semesterId) {
            echo json_encode(['success' => false, 'message' => 'Missing fields']);
            exit;
        }

        $service   = $this->scheduleService();
        $raw       = $service->checkScheduleConflicts($sectionId, $facultyId, $roomId, $day, $start, $end, $semesterId, $scheduleId);
        $formatted = array_map(fn($m) => [
            'type'     => $service->getConflictType($m),
            'message'  => $m,
            'severity' => $service->getConflictSeverity($m),
        ], $raw);

        echo json_encode(['success' => true, 'conflicts' => $formatted]);
        exit;
    }

    // ──────────────────────────────────────────────────────────────
    // departmentTeachingLoad
    // ──────────────────────────────────────────────────────────────

    public function departmentTeachingLoad(): void
    {
        $this->requireAnyRole('chair', 'dean');

        try {
            $userId          = $_SESSION['user_id'];
            $semester        = $this->getActiveSemester();
            $semesterId      = (int)$semester['semester_id'];
            $semesterName    = $semester['semester_name'] . ' Semester, A.Y ' . $semester['academic_year'];
            $isHistoricalView = !$semester['is_current'];
            if ($isHistoricalView) $semesterName .= ' (Historical View)';

            // Resolve department
            $deptStmt = $this->db->prepare("
            SELECT d.department_id, d.department_name, c.college_name, c.college_id
            FROM program_chairs pc
            JOIN faculty f    ON pc.faculty_id = f.faculty_id
            JOIN programs p   ON pc.program_id = p.program_id
            JOIN departments d ON p.department_id = d.department_id
            JOIN colleges c   ON d.college_id = c.college_id
            WHERE f.user_id = ? AND pc.is_current = 1
        ");
            $deptStmt->execute([$userId]);
            $department = $deptStmt->fetch(PDO::FETCH_ASSOC);

            if (!$department) {
                $error = "No department assigned to this program chair.";
                require_once __DIR__ . '/../views/chair/faculty-teaching-load.php';
                return;
            }

            $departmentId = (int)$department['department_id'];

            // Summary rows from repository
            $rawRows = $this->curriculumRepo()->getFacultyTeachingLoadSummary($departmentId, $semesterId);

            $facultyTeachingLoads = [];
            $departmentTotals     = array_fill_keys(
                ['total_faculty', 'total_lecture_hours', 'total_lab_hours', 'total_teaching_load', 'total_working_load', 'total_excess_hours'],
                0
            );

            foreach ($rawRows as $row) {
                $lec   = (float)($row['lecture_hours'] ?? 0);
                $lab   = (float)($row['lab_hours']     ?? 0);
                $lab75 = $lab * 0.75;
                $atl   = $lec + $lab75;
                $etl   = (float)($row['equiv_teaching_load'] ?? 0);
                $twl   = $atl + $etl;
                $exc   = max(0, $twl - 24);

                $facultyTeachingLoads[] = [
                    'faculty_id'           => $row['faculty_id'],
                    'faculty_name'         => trim($row['faculty_name']),
                    'department_name'      => $row['department_name'],
                    'department_id'        => $row['department_id'],
                    'academic_rank'        => $row['academic_rank']        ?? 'Not Specified',
                    'employment_type'      => $row['employment_type']      ?? 'Regular',
                    'bachelor_degree'      => $row['bachelor_degree']      ?? 'Not specified',
                    'master_degree'        => $row['master_degree']        ?? 'Not specified',
                    'doctorate_degree'     => $row['doctorate_degree']     ?? 'Not specified',
                    'post_doctorate_degree' => $row['post_doctorate_degree'] ?? 'Not applicable',
                    'designation'          => $row['designation']          ?? 'Not specified',
                    'classification'       => $row['classification']       ?? 'Not specified',
                    'advisory_class'       => $row['advisory_class']       ?? 'Not assigned',
                    'total_schedules'      => (int)($row['total_schedules'] ?? 0),
                    'total_courses'        => (int)($row['total_courses']   ?? 0),
                    'total_hours'          => (float)($row['total_hours']   ?? 0),
                    'lecture_hours'        => $lec,
                    'lab_hours'            => $lab,
                    'lab_hours_x075'       => round($lab75, 2),
                    'total_preparations'   => (int)($row['lecture_preparations'] ?? 0) + (int)($row['lab_preparations'] ?? 0),
                    'lecture_preparations' => (int)($row['lecture_preparations'] ?? 0),
                    'lab_preparations'     => (int)($row['lab_preparations']     ?? 0),
                    'actual_teaching_load' => round($atl, 2),
                    'equiv_teaching_load'  => $etl,
                    'total_working_load'   => round($twl, 2),
                    'excess_hours'         => round($exc, 2),
                    'load_status'          => $this->schedulingService->getLoadStatus($twl, $exc),
                ];

                $departmentTotals['total_faculty']++;
                $departmentTotals['total_lecture_hours'] += $lec;
                $departmentTotals['total_lab_hours']     += $lab;
                $departmentTotals['total_teaching_load'] += $atl;
                $departmentTotals['total_working_load']  += $twl;
                $departmentTotals['total_excess_hours']  += $exc;
            }
            foreach ($departmentTotals as $k => $v) {
                if ($k !== 'total_faculty') $departmentTotals[$k] = round($v, 2);
            }

            // Detailed per-faculty schedules
            $facultyIds       = array_column($facultyTeachingLoads, 'faculty_id');
            $rawSchedules     = $this->scheduleRepo()->getForFacultyTeachingLoad($facultyIds, $semesterId);
            $detailedSchedules = [];

            // Group by faculty → course+component → days
            $grouped = [];
            foreach ($rawSchedules as $row) {
                $fid = $row['faculty_id'];
                $gk  = $row['course_code'] . '|' . $row['component_type'] . '|' . $row['start_time'] . '|' . $row['end_time'] . '|' . $row['section_name'];
                if (!isset($grouped[$fid][$gk])) {
                    $grouped[$fid][$gk] = $row;
                    $grouped[$fid][$gk]['days'] = [];
                }
                $grouped[$fid][$gk]['days'][] = $row['day_of_week'];
            }

            foreach ($grouped as $fid => $groups) {
                $detailedSchedules[$fid] = [];
                foreach ($groups as $row) {
                    $formattedDays = $this->schedulingService->formatScheduleDays(implode(', ', $row['days']));
                    $ck  = $row['course_code'] . '-' . $row['component_type'];
                    $dur = (float)($row['duration_hours'] ?? 0);
                    $wkly = $dur * count($row['days']);

                    if (!isset($detailedSchedules[$fid][$ck])) {
                        $detailedSchedules[$fid][$ck] = [
                            'course_code'    => $row['course_code'],
                            'course_name'    => $row['course_name'],
                            'units'          => $row['units'] ?? 0,
                            'component_type' => $row['component_type'],
                            'sections'       => [],
                            'total_hours'    => 0,
                        ];
                    }
                    $detailedSchedules[$fid][$ck]['total_hours'] += $wkly;
                    $detailedSchedules[$fid][$ck]['sections'][]   = [
                        'section_name'    => $row['section_name'],
                        'day_of_week'     => $formattedDays,
                        'start_time'      => $row['start_time'],
                        'end_time'        => $row['end_time'],
                        'room_name'       => $row['room_name'],
                        'duration_hours'  => round($dur, 2),
                        'weekly_hours'    => round($wkly, 2),
                        'current_students' => (int)($row['current_students'] ?? 0),
                        'year_level'      => $row['year_level'] ?? 'N/A',
                    ];
                }
            }

            require_once __DIR__ . '/../views/chair/faculty-teaching-load.php';
        } catch (\Exception $e) {
            error_log("departmentTeachingLoad error: " . $e->getMessage());
            http_response_code(500);
            echo "Error loading teaching loads: " . htmlspecialchars($e->getMessage());
            exit;
        }
    }

    // ──────────────────────────────────────────────────────────────
    // getConsolidatedSchedules (kept private — only used internally)
    // ──────────────────────────────────────────────────────────────

    private function getConsolidatedSchedules(int $departmentId, array $currentSemester): array
    {
        $rows   = $this->scheduleRepo()->getConsolidated((int)$currentSemester['semester_id']);
        $result = [];

        foreach ($rows as $row) {
            $details  = explode('||', $row['schedule_details'] ?? '');
            $lecSlots = [];
            $labSlots = [];

            foreach ($details as $d) {
                if (empty($d)) continue;
                $parts = explode('|', $d);
                if (count($parts) < 4) continue;
                [$day, $start, $end, $room, $type] = array_pad($parts, 5, 'F2F');
                if (!$start || !$end) continue;

                $fmt = $this->formatTimeRange($start, $end);
                if (!$fmt) continue;

                $entry = ['day' => $day, 'time' => $fmt, 'room' => $room, 'full' => "$day $fmt $room"];
                strtolower($type) === 'lab' ? $labSlots[] = $entry : $lecSlots[] = $entry;
            }

            $result[] = [
                'course_code' => $row['course_code'],
                'instructor'  => $row['instructor'],
                'sections'    => $row['sections'],
                'section_id'  => $row['section_id'],
                'lecture'     => $this->consolidateSlots($lecSlots),
                'lab'         => $this->consolidateSlots($labSlots),
            ];
        }
        return $result;
    }

    private function consolidateSlots(array $slots): string
    {
        if (empty($slots)) return '';
        $groups = [];
        foreach ($slots as $s) {
            $k = $s['time'] . ' ' . $s['room'];
            $groups[$k][] = substr($s['day'], 0, 1);
        }
        $out = [];
        foreach ($groups as $tr => $days) {
            $days = array_unique($days);
            sort($days);
            $out[] = implode('', $days) . ' ' . $tr;
        }
        return implode('; ', $out);
    }

    private function formatTimeRange(string $start, string $end): string|false
    {
        $s = \DateTime::createFromFormat('H:i:s', $start);
        $e = \DateTime::createFromFormat('H:i:s', $end);
        if (!$s || !$e) return false;
        if ($s->format('a') === $e->format('a')) {
            return $s->format('g:i') . '-' . $e->format('g:i') . ' ' . $e->format('a');
        }
        return $s->format('g:i a') . '-' . $e->format('g:i a');
    }

    // ──────────────────────────────────────────────────────────────
    // FACTORIES
    // ──────────────────────────────────────────────────────────────

    private function scheduleRepo(): ScheduleRepository
    {
        return new ScheduleRepository($this->db);
    }

    private function curriculumRepo(): CurriculumRepository
    {
        return new CurriculumRepository($this->db);
    }

    private function scheduleService(): ScheduleService
    {
        return new ScheduleService($this->db);
    }

    

    /**
     * API endpoint to get faculty schedule details for program chairs
     */
    public function getFacultyScheduleForChair($facultyId)
    {
        $this->requireAnyRole('chair', 'dean');

        header('Content-Type: application/json');

        try {
            $userId = $_SESSION['user_id'];

            // Verify the faculty belongs to the chair's department
            $deptStmt = $this->db->prepare("
            SELECT d.department_id 
            FROM program_chairs pc 
            JOIN faculty f ON pc.faculty_id = f.faculty_id
            JOIN programs p ON pc.program_id = p.program_id 
            JOIN departments d ON p.department_id = d.department_id 
            WHERE f.user_id = ? AND pc.is_current = 1
        ");
            $deptStmt->execute([$userId]);
            $chairDept = $deptStmt->fetch(PDO::FETCH_ASSOC);

            if (!$chairDept) {
                echo json_encode(['success' => false, 'message' => 'No department assigned']);
                exit;
            }

            // Get faculty info and verify they're in the same department
            $facultyStmt = $this->db->prepare("
            SELECT f.faculty_id, 
                   CONCAT(COALESCE(u.title, ''), ' ', u.first_name, ' ', u.last_name) AS faculty_name,
                   d.department_name,
                   f.employment_type,
                   f.academic_rank
            FROM faculty f
            JOIN users u ON f.user_id = u.user_id
            JOIN faculty_departments fd ON f.faculty_id = fd.faculty_id
            JOIN departments d ON fd.department_id = d.department_id
            WHERE f.faculty_id = ? AND d.department_id = ?
        ");
            $facultyStmt->execute([$facultyId, $chairDept['department_id']]);
            $faculty = $facultyStmt->fetch(PDO::FETCH_ASSOC);

            if (!$faculty) {
                echo json_encode(['success' => false, 'message' => 'Faculty not found in your department']);
                exit;
            }

            // Get current semester
            $semesterStmt = $this->db->query("SELECT semester_id FROM semesters WHERE is_current = 1");
            $semester = $semesterStmt->fetch(PDO::FETCH_ASSOC);

            if (!$semester) {
                echo json_encode(['success' => false, 'message' => 'No current semester']);
                exit;
            }

            // ✅ Get faculty schedules with day grouping
            $schedulesStmt = $this->db->prepare("
            SELECT 
                c.course_code,
                c.course_name,
                COALESCE(sec.section_name, 'N/A') AS section_name,
                COALESCE(r.room_name, 'Online') as room_name,
                s.day_of_week,
                s.start_time,
                s.end_time,
                COALESCE(s.component_type, 'lecture') as component_type,
                s.status,
                COALESCE(TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time) / 60, 0) AS duration_hours
            FROM schedules s
            LEFT JOIN courses c ON s.course_id = c.course_id
            LEFT JOIN sections sec ON s.section_id = sec.section_id
            LEFT JOIN classrooms r ON s.room_id = r.room_id
            WHERE s.faculty_id = ? 
                AND s.semester_id = ?
                AND s.status != 'Rejected'
            ORDER BY 
                c.course_code,
                s.component_type,
                CASE s.day_of_week 
                    WHEN 'Monday' THEN 1
                    WHEN 'Tuesday' THEN 2
                    WHEN 'Wednesday' THEN 3
                    WHEN 'Thursday' THEN 4
                    WHEN 'Friday' THEN 5
                    WHEN 'Saturday' THEN 6
                    WHEN 'Sunday' THEN 7
                END,
                s.start_time
        ");
            $schedulesStmt->execute([$facultyId, $semester['semester_id']]);
            $rawSchedules = $schedulesStmt->fetchAll(PDO::FETCH_ASSOC);

            // ✅ Group schedules by course, component, time, and room
            $groupedSchedules = [];
            foreach ($rawSchedules as $schedule) {
                $groupKey = $schedule['course_code'] . '|' .
                    $schedule['component_type'] . '|' .
                    $schedule['start_time'] . '|' .
                    $schedule['end_time'] . '|' .
                    $schedule['room_name'] . '|' .
                    $schedule['section_name'];

                if (!isset($groupedSchedules[$groupKey])) {
                    $groupedSchedules[$groupKey] = $schedule;
                    $groupedSchedules[$groupKey]['days'] = [];
                }

                $groupedSchedules[$groupKey]['days'][] = $schedule['day_of_week'];
            }

            // ✅ Format days for each grouped schedule
            $schedules = [];
            foreach ($groupedSchedules as $schedule) {
                $schedule['day_of_week'] = $this->schedulingService->formatScheduleDays(
                    implode(', ', $schedule['days'])
                );
                unset($schedule['days']);
                $schedules[] = $schedule;
            }

            echo json_encode([
                'success' => true,
                'faculty' => $faculty,
                'schedules' => $schedules
            ]);
            exit;
        } catch (Exception $e) {
            error_log("getFacultyScheduleForChair error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error loading schedule']);
            exit;
        }
    }

    public function viewScheduleHistory()
    {
        $this->requireAnyRole('chair', 'dean');
        $chairId = $_SESSION['user_id'] ?? null;
        $departmentId = $this->currentDepartmentId ?: $this->getChairDepartment($chairId);
        $error = $success = null;
        $historicalSchedules = [];
        $allSemesters = [];

        if ($departmentId) {
            $allSemesters = $this->getPastSemesters($departmentId);
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $semesterId = $_POST['semester_id'] ?? null;
                $academicYear = $_POST['academic_year'] ?? null;
                if ($semesterId || $academicYear) {
                    $historicalSchedules = $this->getHistoricalSchedules($departmentId, $semesterId, $academicYear);
                    $success = "Schedules retrieved: " . count($historicalSchedules) . " schedules";
                } else {
                    $error = "Please select a semester or academic year.";
                }
            }
        } else {
            $error = "No department assigned to chair.";
        }

        $data = [
            'error' => $error,
            'success' => $success,
            'historicalSchedules' => $historicalSchedules,
            'allSemesters' => $allSemesters,
            'title' => 'Schedule History'
        ];

        require_once __DIR__ . '/../views/chair/schedule_history.php';
    }

    private function getHistoricalSchedules($departmentId, $semesterId = null, $academicYear = null)
    {
        try {
            $sql = "SELECT s.*, c.course_code, c.course_name, c.units, u.first_name, u.last_name, r.room_name, se.section_name, sem.semester_name, sem.academic_year,
                GROUP_CONCAT(DISTINCT s.day_of_week SEPARATOR ', ') as all_days,
                MIN(s.start_time) as start_time, MAX(s.end_time) as end_time
                FROM schedules s
                LEFT JOIN courses c ON s.course_id = c.course_id
                LEFT JOIN faculty f ON s.faculty_id = f.faculty_id
                LEFT JOIN users u ON f.user_id = u.user_id
                LEFT JOIN classrooms r ON s.room_id = r.room_id
                LEFT JOIN sections se ON s.section_id = se.section_id
                LEFT JOIN semesters sem ON s.semester_id = sem.semester_id
                WHERE s.department_id = :departmentId";
            $params = [':departmentId' => $departmentId];

            if ($semesterId) {
                $sql .= " AND s.semester_id = :semesterId";
                $params[':semesterId'] = $semesterId;
            }
            if ($academicYear) {
                $sql .= " AND sem.academic_year = :academicYear";
                $params[':academicYear'] = $academicYear;
            }

            $sql .= " GROUP BY s.course_id, s.faculty_id, s.section_id, s.room_id, s.semester_id, c.course_code, c.course_name, c.units, u.first_name, u.last_name, r.room_name, se.section_name, sem.semester_name, sem.academic_year";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array_map(function ($schedule) {
                return [
                    'schedule_id' => $schedule['schedule_id'],
                    'course_code' => $schedule['course_code'],
                    'course_name' => $schedule['course_name'],
                    'units' => $schedule['units'] ?? 3,
                    'faculty_name' => trim($schedule['first_name'] . ' ' . $schedule['last_name']) ?: 'Unknown',
                    'room_name' => $schedule['room_name'] ?? 'Online',
                    'section_name' => $schedule['section_name'],
                    'day_of_week' => $this->schedulingService->formatScheduleDays($schedule['all_days']), // Combine and format days
                    'start_time' => $schedule['start_time'],
                    'end_time' => $schedule['end_time'],
                    'schedule_type' => $schedule['schedule_type'],
                    'semester_name' => $schedule['semester_name'],
                    'academic_year' => $schedule['academic_year']
                ];
            }, $schedules);
        } catch (PDOException $e) {
            error_log("Error fetching historical schedules: " . $e->getMessage());
            return [];
        }
    }

    private function getPastSemesters($departmentId)
    {
        try {
            $currentSemester = $_SESSION['current_semester'] ?? $this->getCurrentSemester();
            $currentSemesterId = $currentSemester['semester_id'] ?? null;
            $currentAcademicYear = $currentSemester['academic_year'] ?? null;

            // Log all semesters to check total data
            $allSemestersStmt = $this->db->query("SELECT semester_id, semester_name, academic_year FROM semesters");
            $allSemesters = $allSemestersStmt->fetchAll(PDO::FETCH_ASSOC);

            // Log schedules to check department linkage
            $schedulesStmt = $this->db->prepare("SELECT semester_id FROM schedules WHERE department_id = :departmentId");
            $schedulesStmt->execute([':departmentId' => $departmentId]);
            $scheduleSemesters = $schedulesStmt->fetchAll(PDO::FETCH_ASSOC);
            // Fetch all semesters
            $sql = "SELECT semester_id, semester_name, academic_year FROM semesters";
            $stmt = $this->db->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $results; // Return all semesters, including current
        } catch (PDOException $e) {
            error_log("Error fetching semesters: " . $e->getMessage());
            return [];
        }
    }


    /**
     * Manage classrooms
     */
    public function setClassroomsAvailableForSemester($departmentId, $semesterId)
    {
        try {
            if (!$departmentId || !$semesterId) {
                throw new Exception("Invalid department ID or semester ID.");
            }

            $affected = $this->classroomRepo()->setAllAvailableForDepartment((int)$departmentId);
            error_log("setClassroomsAvailableForSemester: Set $affected classrooms to available for semester_id=$semesterId, department_id=$departmentId");

            $this->logActivity(null, $departmentId, 'Set Classrooms Available', "Set all classrooms to available for semester_id=$semesterId", 'classrooms', null);

            return [
                'success' => true,
                'message' => "All classrooms set to available for semester ID $semesterId.",
            ];
        } catch (PDOException | Exception $e) {
            error_log("setClassroomsAvailableForSemester: Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Failed to set classrooms available: " . htmlspecialchars($e->getMessage()),
            ];
        }
    }

    // ──────────────────────────────────────────────────────────────
    // checkClassroomAvailability()
    // ──────────────────────────────────────────────────────────────
    public function checkClassroomAvailability($departmentId)
    {
        try {
            if (!$departmentId) {
                throw new Exception("Invalid department ID.");
            }

            $currentSemester = $this->getCurrentSemester();
            if (!$currentSemester || !isset($currentSemester['semester_id'])) {
                throw new Exception("No current semester found.");
            }
            $currentSemesterId = (int)$currentSemester['semester_id'];

            $repo       = $this->classroomRepo();
            $classrooms = $repo->getLabsAndSharedRooms((int)$departmentId, $currentSemesterId);

            $days        = ['M', 'T', 'W', 'R', 'F', 'S'];
            $updatedCount = 0;

            foreach ($classrooms as $classroom) {
                $roomId              = $classroom['room_id'];
                $currentAvailability = $classroom['current_availability'];

                // Never touch rooms under maintenance
                if ($currentAvailability === 'under_maintenance') {
                    error_log("checkClassroomAvailability: Skipping room_id=$roomId (under_maintenance)");
                    continue;
                }

                $scheduleCount = (int)$classroom['schedule_count'];
                $timeSlots     = $scheduleCount > 0 && $classroom['time_slots']
                    ? explode(',', $classroom['time_slots'])
                    : [];

                if ($scheduleCount === 0) {
                    $newAvailability = 'available';
                } else {
                    $availabilityByDay = array_fill_keys($days, [
                        'morning'   => true,
                        'afternoon' => true,
                        'evening'   => true,
                    ]);

                    foreach ($timeSlots as $slot) {
                        preg_match('/([MTWRFS]+) (\d{1,2}:\d{2})-(\d{1,2}:\d{2})/', $slot, $m);
                        if (count($m) < 4) continue;

                        $slotDays     = str_split($m[1]);
                        $startMinutes = (int)date('H', strtotime($m[2])) * 60 + (int)date('i', strtotime($m[2]));
                        $endMinutes   = (int)date('H', strtotime($m[3])) * 60 + (int)date('i', strtotime($m[3]));

                        foreach ($slotDays as $day) {
                            if (!in_array($day, $days)) continue;

                            // Morning  : 07:00–12:00 (420–720)
                            // Afternoon: 12:00–18:00 (720–1080)
                            // Evening  : 18:00–21:00 (1080–1260)
                            if ($startMinutes < 720 && $endMinutes <= 720) {
                                $availabilityByDay[$day]['morning'] = false;
                            } elseif ($startMinutes >= 720 && $endMinutes <= 1080) {
                                $availabilityByDay[$day]['afternoon'] = false;
                            } elseif ($startMinutes >= 1080 && $endMinutes <= 1260) {
                                $availabilityByDay[$day]['evening'] = false;
                            } elseif ($startMinutes < 720 && $endMinutes > 720 && $endMinutes <= 1080) {
                                $availabilityByDay[$day]['morning']   = false;
                                $availabilityByDay[$day]['afternoon'] = false;
                            } elseif ($startMinutes >= 720 && $startMinutes < 1080 && $endMinutes > 1080) {
                                $availabilityByDay[$day]['afternoon'] = false;
                                $availabilityByDay[$day]['evening']   = false;
                            } elseif ($startMinutes < 720 && $endMinutes > 1080) {
                                $availabilityByDay[$day]['morning']   = false;
                                $availabilityByDay[$day]['afternoon'] = false;
                                $availabilityByDay[$day]['evening']   = false;
                            }
                        }
                    }

                    $isAvailable = array_reduce($availabilityByDay, function ($carry, $slots) {
                        return $carry || $slots['morning'] || $slots['afternoon'] || $slots['evening'];
                    }, false);

                    $newAvailability = $isAvailable ? 'available' : 'unavailable';
                }

                if ($newAvailability !== $currentAvailability) {
                    $repo->updateAvailability((int)$roomId, $newAvailability);
                    $updatedCount++;
                    error_log("checkClassroomAvailability: room_id=$roomId → $newAvailability");
                }
            }

            error_log("checkClassroomAvailability: Updated $updatedCount classrooms for semester_id=$currentSemesterId, department_id=$departmentId");
            $this->logActivity(null, $departmentId, 'Check Classroom Availability', "Updated $updatedCount classrooms for semester_id=$currentSemesterId", 'classrooms', null);

            return [
                'success' => true,
                'message' => "Classroom availability updated for semester ID $currentSemesterId.",
            ];
        } catch (PDOException | Exception $e) {
            error_log("checkClassroomAvailability: Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Failed to check classroom availability: " . htmlspecialchars($e->getMessage()),
            ];
        }
    }

    // ──────────────────────────────────────────────────────────────
    // classroom()
    // ──────────────────────────────────────────────────────────────
    public function classroom()
    {
        $this->requireAnyRole('chair', 'dean');
        error_log("classroom: Starting");

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
        }

        try {
            $chairId      = $_SESSION['user_id'] ?? null;
            $departmentId = $this->currentDepartmentId ?: $this->getChairDepartment($chairId);
            $repo         = $this->classroomRepo();
            $classrooms   = [];
            $departments  = [];
            $searchResults = [];
            $error = $success = null;
            $departmentInfo = null;

            // ── Department + college info ────────────────────────────
            if ($departmentId) {
                $stmt = $this->db->prepare("
                SELECT d.*, cl.college_id, cl.college_name
                FROM departments d
                JOIN colleges cl ON d.college_id = cl.college_id
                WHERE d.department_id = ?
            ");
                $stmt->execute([$departmentId]);
                $departmentInfo = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$departmentInfo || !$departmentInfo['college_id']) {
                    $error = "No college assigned to this department.";
                    error_log("classroom: No college for department_id=$departmentId");
                }
            } else {
                $error = "No department assigned to this chair.";
                error_log("classroom: No department for chairId=$chairId");
            }

            // ── All departments (for UI selects) ────────────────────
            $deptStmt = $this->db->prepare("SELECT department_id, department_name, college_id FROM departments ORDER BY department_name");
            $deptStmt->execute();
            $departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

            // ── Load classrooms helper ───────────────────────────────
            $loadClassrooms = function () use ($departmentId, $repo): array {
                $sem = $this->getActiveSemester();
                return $repo->getByDepartment((int)$departmentId, (int)$sem['semester_id']);
            };

            if ($departmentId) {
                $classrooms = $loadClassrooms();
            }

            // ── POST actions ─────────────────────────────────────────
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
                switch ($_POST['action']) {

                    // ── Get classroom schedule (modal) ───────────────
                    case 'get_classroom_schedule':
                        try {
                            $roomId = (int)($_POST['room_id'] ?? 0);
                            if (!$roomId) throw new Exception("Invalid room ID.");

                            $sem       = $this->getCurrentSemester();
                            $rows      = $repo->getScheduleByRoom($roomId, (int)$sem['semester_id']);
                            $grouped   = ClassroomRepository::groupScheduleByDay($rows);
                            $info      = $repo->getInfo($roomId);

                            $response = [
                                'success'        => true,
                                'schedule'       => $grouped,
                                'classroom_info' => $info,
                            ];
                        } catch (PDOException | Exception $e) {
                            $response = ['success' => false, 'message' => htmlspecialchars($e->getMessage())];
                            error_log("classroom get_classroom_schedule: " . $e->getMessage());
                        }
                        ob_clean();
                        echo json_encode($response);
                        exit;

                        // ── Add classroom ────────────────────────────────
                    case 'add':
                        try {
                            if (!$departmentId) throw new Exception("Invalid department.");

                            $roomName    = trim($_POST['room_name']    ?? '');
                            $building    = trim($_POST['building']     ?? '');
                            $capacity    = (int)($_POST['capacity']    ?? 0);
                            $roomType    = $_POST['room_type']         ?? 'lecture';
                            $shared      = isset($_POST['shared']) ? 1 : 0;
                            $availability = $_POST['availability']    ?? 'available';

                            if (!$roomName || !$building || $capacity < 1) {
                                throw new Exception("Room name, building, and valid capacity are required.");
                            }

                            $roomId = $repo->create((int)$departmentId, $roomName, $building, $capacity, $roomType, $shared, $availability);
                            error_log("classroom add: Created room_id=$roomId for department_id=$departmentId");

                            $this->logActivity($chairId, $departmentId, 'Add Classroom', "Added classroom $roomName", 'classrooms', $roomId);
                            $classrooms = $loadClassrooms();
                            $response   = ['success' => true, 'message' => "Classroom added successfully.", 'classrooms' => $classrooms];
                        } catch (PDOException | Exception $e) {
                            $response = ['success' => false, 'message' => "Failed to add classroom: " . htmlspecialchars($e->getMessage())];
                            error_log("classroom add: " . $e->getMessage());
                        }
                        if ($isAjax) {
                            ob_clean();
                            echo json_encode($response);
                            exit;
                        }
                        $success = $response['message'];
                        break;

                    // ── Edit classroom ───────────────────────────────
                    case 'edit':
                        try {
                            $roomId      = (int)($_POST['room_id']     ?? 0);
                            $roomName    = trim($_POST['room_name']    ?? '');
                            $building    = trim($_POST['building']     ?? '');
                            $capacity    = (int)($_POST['capacity']    ?? 0);
                            $roomType    = $_POST['room_type']         ?? 'lecture';
                            $shared      = isset($_POST['shared']) ? 1 : 0;
                            $availability = $_POST['availability']    ?? 'available';

                            if (!$roomId || !$roomName || !$building || $capacity < 1) {
                                throw new Exception("Room name, building, capacity, and valid room ID are required.");
                            }

                            $owner = $repo->getOwnerDepartment($roomId);
                            if ($owner !== (int)$departmentId) {
                                throw new Exception("You can only edit classrooms owned by your department.");
                            }

                            $repo->update($roomId, (int)$departmentId, $roomName, $building, $capacity, $roomType, $shared, $availability);
                            error_log("classroom edit: Updated room_id=$roomId for department_id=$departmentId");

                            $this->logActivity($chairId, $departmentId, 'Edit Classroom', "Edited classroom $roomName", 'classrooms', $roomId);
                            $classrooms = $loadClassrooms();
                            $response   = ['success' => true, 'message' => "Classroom updated successfully.", 'classrooms' => $classrooms];
                        } catch (PDOException | Exception $e) {
                            $response = ['success' => false, 'message' => "Failed to update classroom: " . htmlspecialchars($e->getMessage())];
                            error_log("classroom edit: " . $e->getMessage());
                        }
                        if ($isAjax) {
                            ob_clean();
                            echo json_encode($response);
                            exit;
                        }
                        $success = $response['message'];
                        break;

                    // ── Include shared room ──────────────────────────
                    case 'include_room':
                        try {
                            $roomId = (int)($_POST['room_id'] ?? 0);
                            if (!$roomId || !$departmentId) throw new Exception("Invalid room ID or department ID.");

                            $owner = $repo->getOwnerDepartment($roomId);
                            if ($owner === false) throw new Exception("Room not found.");
                            if ($owner === (int)$departmentId) throw new Exception("Cannot include a room owned by your department.");

                            // Verify the room is actually shared
                            $sharedCheck = $this->db->prepare("SELECT shared FROM classrooms WHERE room_id = :id");
                            $sharedCheck->execute([':id' => $roomId]);
                            if (!(int)$sharedCheck->fetchColumn()) throw new Exception("This room is not shared with other departments.");

                            if ($repo->getInclusionId($roomId, (int)$departmentId)) {
                                throw new Exception("This room is already included in your department.");
                            }

                            $cdId = $repo->includeRoom($roomId, (int)$departmentId);
                            error_log("classroom include_room: room_id=$roomId for department_id=$departmentId");

                            $this->logActivity($chairId, $departmentId, 'Include Room', "Included room_id=$roomId", 'classroom_departments', $cdId);
                            $classrooms = $loadClassrooms();
                            $response   = ['success' => true, 'message' => "Room included successfully.", 'classrooms' => $classrooms];
                        } catch (PDOException | Exception $e) {
                            $response = ['success' => false, 'message' => "Failed to include room: " . htmlspecialchars($e->getMessage())];
                            error_log("classroom include_room: " . $e->getMessage());
                        }
                        ob_clean();
                        echo json_encode($response);
                        exit;

                        // ── Remove shared room ───────────────────────────
                    case 'remove_room':
                        try {
                            $roomId = (int)($_POST['room_id'] ?? 0);
                            if (!$roomId || !$departmentId) throw new Exception("Invalid room ID or department ID.");

                            $cdId = $repo->getInclusionId($roomId, (int)$departmentId);
                            if (!$cdId) throw new Exception("This room is not included in your department.");

                            $repo->excludeRoom($cdId);
                            error_log("classroom remove_room: room_id=$roomId from department_id=$departmentId");

                            $this->logActivity($chairId, $departmentId, 'Remove Room', "Removed room_id=$roomId", 'classroom_departments', $cdId);
                            $classrooms = $loadClassrooms();
                            $response   = ['success' => true, 'message' => "Room removed successfully.", 'classrooms' => $classrooms];
                        } catch (PDOException | Exception $e) {
                            $response = ['success' => false, 'message' => "Failed to remove room: " . htmlspecialchars($e->getMessage())];
                            error_log("classroom remove_room: " . $e->getMessage());
                        }
                        ob_clean();
                        echo json_encode($response);
                        exit;

                        // ── Search shared rooms ──────────────────────────
                    case 'search_shared_rooms':
                        try {
                            $search  = $_POST['search'] ?? '';
                            $results = $repo->searchSharedRooms((int)$departmentId, $search);
                            error_log("classroom search_shared_rooms: Found " . count($results) . " results");
                            $response = ['success' => true, 'searchResults' => $results];
                        } catch (PDOException | Exception $e) {
                            $response = ['success' => false, 'message' => "Search failed: " . htmlspecialchars($e->getMessage())];
                            error_log("classroom search_shared_rooms: " . $e->getMessage());
                        }
                        ob_clean();
                        echo json_encode($response);
                        exit;
                }
            }

            // ── AJAX non-POST fallback ───────────────────────────────
            if ($isAjax) {
                ob_clean();
                echo json_encode([
                    'success'        => $success,
                    'error'          => $error,
                    'classrooms'     => $classrooms,
                    'departmentInfo' => $departmentInfo,
                    'departments'    => $departments,
                    'searchResults'  => $searchResults,
                ]);
                exit;
            }

            // ── Render view ──────────────────────────────────────────
            extract([
                'classrooms'     => $classrooms,
                'departmentInfo' => $departmentInfo,
                'departments'    => $departments,
                'error'          => $error,
                'success'        => $success,
            ]);
            require_once __DIR__ . '/../views/chair/classroom.php';
        } catch (Exception $e) {
            error_log("classroom: General Error: " . $e->getMessage());
            if ($isAjax) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => "An error occurred: " . htmlspecialchars($e->getMessage())]);
                exit;
            }
            $error = "An error occurred: " . htmlspecialchars($e->getMessage());
            require_once __DIR__ . '/../views/chair/classroom.php';
        }
    }

    // ──────────────────────────────────────────────────────────────
    // PRIVATE: classroomRepo() — lazy factory
    // ──────────────────────────────────────────────────────────────
    private function classroomRepo(): ClassroomRepository
    {
        return new ClassroomRepository($this->db);
    }

    public function sections()
    {
        $this->requireAnyRole('chair', 'dean');
        error_log("sections: Starting sections method");

        try {
            $chairId      = $_SESSION['user_id'];
            $departmentId = $this->currentDepartmentId ?: $this->getChairDepartment($chairId);
            $error = $success = $info = null;

            // ── No department ────────────────────────────────────────
            if (!$departmentId) {
                error_log("sections: No department found for chairId: $chairId");
                $error                   = "No department assigned to this chair.";
                $currentSemesterSections = [];
                $groupedCurrentSections  = SectionRepository::groupByYearLevel([]);
                $currentSemester         = null;
                $previousSections        = [];
                $groupedPreviousSections = [];
                require_once __DIR__ . '/../views/chair/sections.php';
                return;
            }

            // ── Resolve active semester ──────────────────────────────
            $currentSemester = $this->getActiveSemester();
            if (!$currentSemester) {
                error_log("sections: No valid semester in session, falling back to current");
                unset($_SESSION['selected_semester_id'], $_SESSION['selected_semester'], $_SESSION['is_historical_view']);
                $currentSemester = $this->getCurrentSemester();
            }

            if (!$currentSemester) {
                error_log("sections: No current semester configured");
                $error                   = "Current semester is not set. Please contact your administrator.";
                $currentSemesterSections = [];
                $groupedCurrentSections  = SectionRepository::groupByYearLevel([]);
                $previousSections        = [];
                $groupedPreviousSections = [];
                require_once __DIR__ . '/../views/chair/sections.php';
                return;
            }

            $isHistoricalView = !$currentSemester['is_current'];

            // ── Handle POST ──────────────────────────────────────────
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (isset($_POST['add_section']))         $this->addSection($departmentId);
                elseif (isset($_POST['remove_section']))  $this->removeSection();
                elseif (isset($_POST['edit_section']))    $this->editSection($departmentId);
                elseif (isset($_POST['reuse_section']))   $this->reuseSection($departmentId);
                elseif (isset($_POST['reuse_all_sections'])) $this->reuseAllSections($departmentId);

                $success = $_SESSION['success'] ?? null;
                $error   = $_SESSION['error']   ?? null;
                $info    = $_SESSION['info']    ?? null;
                unset($_SESSION['success'], $_SESSION['error'], $_SESSION['info']);
            }

            // ── Fetch data via repository ────────────────────────────
            $repo = $this->sectionRepo();

            $currentSemesterSections = $repo->getSectionsBySemester($departmentId, $currentSemester['semester_id']);
            $groupedCurrentSections  = SectionRepository::groupByYearLevel($currentSemesterSections);

            error_log("sections: Found " . count($currentSemesterSections) . " sections for semester_id={$currentSemester['semester_id']}");

            $previousSections        = $repo->getPreviousSections($departmentId, $currentSemester['semester_id']);
            $groupedPreviousSections = SectionRepository::groupBySemesterAndYear($previousSections);

            require_once __DIR__ . '/../views/chair/sections.php';
        } catch (PDOException $e) {
            error_log("sections: PDO Error - " . $e->getMessage());
            $error                   = "Failed to load sections.";
            $currentSemesterSections = [];
            $groupedCurrentSections  = SectionRepository::groupByYearLevel([]);
            $previousSections        = [];
            $groupedPreviousSections = [];
            require_once __DIR__ . '/../views/chair/sections.php';
        }
    }

    // ──────────────────────────────────────────────────────────────
    // PRIVATE: addSection()
    // ──────────────────────────────────────────────────────────────
    private function addSection(int $departmentId): void
    {
        error_log("addSection: department=$departmentId");
        try {
            $sectionName     = trim($_POST['section_name']     ?? '');
            $yearLevel       = trim($_POST['year_level']       ?? '');
            $maxStudents     = (int)($_POST['max_students']    ?? 40);
            $currentStudents = (int)($_POST['current_students'] ?? 0);

            // ── Validate inputs ──────────────────────────────────────
            if (!$sectionName || !$yearLevel || $maxStudents < 1 || $maxStudents > 100) {
                $_SESSION['error'] = "Please provide a section name, year level, and valid max students (1–100).";
                header('Location: /chair/sections');
                exit;
            }
            if (!in_array($yearLevel, self::VALID_YEAR_LEVELS)) {
                $_SESSION['error'] = "Invalid year level selected.";
                header('Location: /chair/sections');
                exit;
            }

            // ── Resolve current semester ─────────────────────────────
            $sem = $this->getCurrentSemester();
            if (!$sem || !in_array($sem['semester_name'], self::VALID_SEMESTERS)) {
                $_SESSION['error'] = "Current semester is not configured. Please contact your administrator.";
                header('Location: /chair/sections');
                exit;
            }

            // ── Duplicate check: same name in SAME semester only ─────
            $repo = $this->sectionRepo();
            if ($repo->existsInSemester($departmentId, $sectionName, $sem['semester_id'])) {
                $_SESSION['error'] = "A section named '$sectionName' already exists in the current semester ({$sem['semester_name']} {$sem['academic_year']}).";
                header('Location: /chair/sections');
                exit;
            }

            // ── Insert ───────────────────────────────────────────────
            $this->db->beginTransaction();
            $newId = $repo->create(
                $departmentId,
                $sectionName,
                $yearLevel,
                $maxStudents,
                $currentStudents,
                $sem['semester_id'],
                $sem['semester_name'],
                $sem['academic_year']
            );
            $this->db->commit();

            $_SESSION['success'] = "Section '$sectionName' added successfully.";
            error_log("addSection: Created section_id=$newId");
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("addSection: PDO Error - " . $e->getMessage());
            $_SESSION['error'] = "Failed to add section: " . $e->getMessage();
        }
        header('Location: /chair/sections');
        exit;
    }

    // ──────────────────────────────────────────────────────────────
    // PRIVATE: editSection()
    // ──────────────────────────────────────────────────────────────
    private function editSection(int $departmentId): void
    {
        error_log("editSection: department=$departmentId");
        try {
            $sectionId       = (int)($_POST['section_id']       ?? 0);
            $sectionName     = trim($_POST['section_name']      ?? '');
            $yearLevel       = trim($_POST['year_level']        ?? '');
            $maxStudents     = (int)($_POST['max_students']     ?? 40);
            $currentStudents = (int)($_POST['current_students'] ?? 0);

            // ── Validate ─────────────────────────────────────────────
            $errors = [];
            if ($sectionId <= 0)                                    $errors[] = "Invalid section ID.";
            if (empty($sectionName))                                $errors[] = "Section name is required.";
            if (!in_array($yearLevel, self::VALID_YEAR_LEVELS))     $errors[] = "Invalid year level.";
            if ($maxStudents < 1 || $maxStudents > 100)             $errors[] = "Max students must be 1–100.";

            if ($errors) {
                $_SESSION['error'] = implode(' ', $errors);
                header('Location: /chair/sections');
                exit;
            }

            $repo    = $this->sectionRepo();
            $section = $repo->findActiveById($sectionId, $departmentId);
            if (!$section) {
                $_SESSION['error'] = "Section not found or not accessible.";
                header('Location: /chair/sections');
                exit;
            }

            // ── Duplicate check: same semester scope, excluding self ─
            if ($repo->existsInSemester($departmentId, $sectionName, (int)$section['semester_id'], $sectionId)) {
                $_SESSION['error'] = "A section named '$sectionName' already exists in that semester.";
                header('Location: /chair/sections');
                exit;
            }

            $repo->update($sectionId, $sectionName, $yearLevel, $maxStudents, $currentStudents);
            $_SESSION['success'] = "Section '$sectionName' updated successfully.";
            error_log("editSection: Updated section_id=$sectionId");
        } catch (PDOException $e) {
            error_log("editSection: PDO Error - " . $e->getMessage());
            $_SESSION['error'] = "Failed to update section: " . $e->getMessage();
        }
        header('Location: /chair/sections');
        exit;
    }

    // ──────────────────────────────────────────────────────────────
    // PRIVATE: removeSection()
    // ──────────────────────────────────────────────────────────────
    private function removeSection(): void
    {
        error_log("removeSection: Starting at " . date('Y-m-d H:i:s'));
        try {
            $sectionId    = (int)($_POST['section_id'] ?? 0);
            $chairId      = $_SESSION['user_id'] ?? null;
            $departmentId = $this->getChairDepartment($chairId);

            if ($sectionId <= 0 || !$chairId) {
                $_SESSION['error'] = "Invalid section or user.";
                header('Location: /chair/sections');
                exit;
            }

            $repo    = $this->sectionRepo();
            $section = $repo->findActiveById($sectionId, $departmentId);
            if (!$section) {
                $_SESSION['error'] = "Section not found or not accessible.";
                header('Location: /chair/sections');
                exit;
            }

            $this->db->beginTransaction();
            $repo->softDelete($sectionId);
            $this->db->commit();

            $_SESSION['success'] = "Section '{$section['section_name']}' archived successfully.";
            error_log("removeSection: Archived section_id=$sectionId");
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("removeSection: PDO Error - " . $e->getMessage());
            $_SESSION['error'] = "Failed to archive section: " . $e->getMessage();
        }
        header('Location: /chair/sections?refresh=1');
        exit;
    }

    // ──────────────────────────────────────────────────────────────
    // PRIVATE: reuseSection()
    // ──────────────────────────────────────────────────────────────
    private function reuseSection(int $departmentId): void
    {
        error_log("reuseSection: department=$departmentId");
        try {
            $sectionId = (int)($_POST['section_id'] ?? 0);
            if ($sectionId <= 0) {
                $_SESSION['error'] = "Invalid section selected.";
                header('Location: /chair/sections');
                exit;
            }

            $repo    = $this->sectionRepo();
            $section = $repo->findById($sectionId, $departmentId);
            if (!$section) {
                $_SESSION['error'] = "Selected section not found.";
                header('Location: /chair/sections');
                exit;
            }

            $sem = $this->getCurrentSemester();
            if (!$sem || !in_array($sem['semester_name'], self::VALID_SEMESTERS)) {
                $_SESSION['error'] = "Current semester is not configured. Please contact your administrator.";
                header('Location: /chair/sections');
                exit;
            }

            // ── Duplicate check: same name in TARGET semester ────────
            if ($repo->existsInSemester($departmentId, $section['section_name'], $sem['semester_id'])) {
                $_SESSION['error'] = "Section '{$section['section_name']}' already exists in the current semester ({$sem['semester_name']} {$sem['academic_year']}).";
                header('Location: /chair/sections');
                exit;
            }

            $this->db->beginTransaction();
            $repo->create(
                $departmentId,
                $section['section_name'],
                $section['year_level'],
                (int)$section['max_students'],
                0,
                $sem['semester_id'],
                $sem['semester_name'],
                $sem['academic_year']
            );
            $this->db->commit();

            $_SESSION['success'] = "Section '{$section['section_name']}' reused for {$sem['semester_name']} {$sem['academic_year']}.";
            error_log("reuseSection: Reused section_id=$sectionId into semester_id={$sem['semester_id']}");
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("reuseSection: PDO Error - " . $e->getMessage());
            $_SESSION['error'] = "Failed to reuse section: " . $e->getMessage();
        }
        header('Location: /chair/sections');
        exit;
    }

    // ──────────────────────────────────────────────────────────────
    // PRIVATE: reuseAllSections()
    // ──────────────────────────────────────────────────────────────
    private function reuseAllSections(int $departmentId): void
    {
        error_log("reuseAllSections: department=$departmentId");
        try {
            $semesterKey = $_POST['reuse_all_sections'] ?? '';
            if (empty($semesterKey)) {
                $_SESSION['error'] = "No semester selected for reuse.";
                header('Location: /chair/sections');
                exit;
            }

            // semesterKey format: "1st 2024-2025"
            $parts = explode(' ', $semesterKey, 2);
            if (count($parts) !== 2) {
                $_SESSION['error'] = "Invalid semester format.";
                header('Location: /chair/sections');
                exit;
            }
            [$semesterName, $academicYear] = $parts;

            // Resolve the source semester_id
            $stmt = $this->db->prepare("
            SELECT semester_id FROM semesters
            WHERE semester_name = :semester_name AND academic_year = :academic_year
        ");
            $stmt->execute([':semester_name' => $semesterName, ':academic_year' => $academicYear]);
            $sourceSem = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$sourceSem) {
                $_SESSION['error'] = "Selected semester not found.";
                header('Location: /chair/sections');
                exit;
            }

            $repo     = $this->sectionRepo();
            $sections = $repo->getSectionsBySemesterId($departmentId, (int)$sourceSem['semester_id']);
            if (empty($sections)) {
                $_SESSION['error'] = "No sections found for the selected semester.";
                header('Location: /chair/sections');
                exit;
            }

            $sem = $this->getCurrentSemester();
            if (!$sem || !in_array($sem['semester_name'], self::VALID_SEMESTERS)) {
                $_SESSION['error'] = "Current semester is not configured. Please contact your administrator.";
                header('Location: /chair/sections');
                exit;
            }

            $this->db->beginTransaction();
            $reused = $skipped = 0;

            foreach ($sections as $section) {
                // ── Duplicate check per section against TARGET semester ──
                if ($repo->existsInSemester($departmentId, $section['section_name'], $sem['semester_id'])) {
                    $skipped++;
                    continue;
                }
                $repo->create(
                    $departmentId,
                    $section['section_name'],
                    $section['year_level'],
                    (int)$section['max_students'],
                    0,
                    $sem['semester_id'],
                    $sem['semester_name'],
                    $sem['academic_year']
                );
                $reused++;
            }

            $this->db->commit();

            $msg = "Successfully reused $reused section(s) from $semesterKey.";
            if ($skipped > 0) {
                $msg .= " $skipped section(s) skipped — already exist in the current semester.";
            }
            $_SESSION['success'] = $msg;
            error_log("reuseAllSections: reused=$reused, skipped=$skipped for department=$departmentId");
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("reuseAllSections: PDO Error - " . $e->getMessage());
            $_SESSION['error'] = "Failed to reuse sections: " . $e->getMessage();
        }
        header('Location: /chair/sections');
        exit;
    }

    // ──────────────────────────────────────────────────────────────
    // PRIVATE: autoTransitionSections()
    // ──────────────────────────────────────────────────────────────
    private function autoTransitionSections(int $departmentId, array $currentSemester): void
    {
        try {
            $count = $this->sectionRepo()->deactivateOutOfSemester($departmentId, (int)$currentSemester['semester_id']);
            if ($count > 0 && !isset($_SESSION['auto_transition_notified'])) {
                $_SESSION['info']                    = "Sections from previous semesters have been automatically archived.";
                $_SESSION['auto_transition_notified'] = true;
            }
            error_log("autoTransitionSections: deactivated $count sections for department=$departmentId");
        } catch (PDOException $e) {
            error_log("autoTransitionSections: Error - " . $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────────────────────
    // PRIVATE: sectionRepo() — lazy factory
    // ──────────────────────────────────────────────────────────────
    private function sectionRepo(): SectionRepository
    {
        return new SectionRepository($this->db);
    }

    private function fetchCurricula($departmentId)
    {
        $stmt = $this->db->prepare("
            SELECT c.*, p.program_name,
                   (SELECT COUNT(*) FROM curriculum_courses cc WHERE cc.curriculum_id = c.curriculum_id) as course_count,
                   (SELECT SUM(c2.units) FROM curriculum_courses cc JOIN courses c2 ON cc.course_id = c2.course_id WHERE cc.curriculum_id = c.curriculum_id) as total_units
            FROM curricula c 
            JOIN programs p ON c.department_id = p.department_id 
            WHERE c.department_id = :department_id
        ");
        $stmt->execute([':department_id' => $departmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function validateInput($data, $field, $rules = [])
    {
        $value = trim($data[$field] ?? '');
        $errors = [];

        if (in_array('required', $rules) && empty($value)) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required.";
        }
        if (in_array('string', $rules) && !is_string($value)) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . " must be a string.";
        }
        if (in_array('numeric', $rules) && !is_numeric($value)) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . " must be numeric.";
        }
        if (isset($rules['min']) && $value < $rules['min']) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . " must be at least {$rules['min']}.";
        }
        if (isset($rules['max']) && $value > $rules['max']) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . " must not exceed {$rules['max']}.";
        }
        if (isset($rules['in']) && !in_array($value, $rules['in'])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is invalid.";
        }

        return [$value, $errors];
    }

    private function handlePDOException(PDOException $e, $context = 'Database error')
    {
        $error = "$context: " . htmlspecialchars($e->getMessage());
        error_log($error);
        if ($_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['error' => $error]);
            exit;
        }
        return $error;
    }

    /**
     * Manage curriculum
     */
    public function curriculum()
    {
        $this->requireAnyRole('chair', 'dean');
        try {
            $chairId = $_SESSION['user_id'] ?? 0;
            // Get department for the Chair - use currentDepartmentId if Program Chair
            $departmentId = $this->currentDepartmentId ?: $this->getChairDepartment($chairId);
            if (!$departmentId) {
                error_log("curriculum: No department found for chairId: $chairId");
                $error = "No department assigned to this chair.";
                $curricula = [];
                $courses = [];
                $db = $this->db;
                require_once __DIR__ . '/../views/chair/curriculum.php';
                return;
            }

            // Initialize data
            $curricula = $this->fetchCurricula($departmentId);
            $courses = $this->getCourses($departmentId);
            $success = null;
            $error = null;

            // Handle POST and AJAX requests
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
                switch ($_POST['action']) {
                    case 'check_course_in_curriculum':
                        header('Content-Type: application/json');
                        $curriculum_id = intval($_POST['curriculum_id'] ?? 0);
                        $course_id = intval($_POST['course_id'] ?? 0);
                        $response = ['exists' => false];

                        if ($curriculum_id > 0 && $course_id > 0) {
                            $stmt = $this->db->prepare("SELECT COUNT(*) FROM curriculum_courses WHERE curriculum_id = :curriculum_id AND course_id = :course_id");
                            $stmt->execute([':curriculum_id' => $curriculum_id, ':course_id' => $course_id]);
                            $response['exists'] = $stmt->fetchColumn() > 0;
                        }

                        echo json_encode($response);
                        exit;

                    case 'get_curriculum_courses':
                        header('Content-Type: application/json');
                        $curriculum_id = intval($_POST['curriculum_id'] ?? 0);
                        if ($curriculum_id < 1) {
                            echo json_encode(['error' => 'Invalid curriculum ID']);
                            exit;
                        }
                        try {
                            $stmt = $this->db->prepare("
                            SELECT 
                                c.course_id, 
                                c.course_code, 
                                c.course_name, 
                                c.units,
                                c.lecture_units,
                                c.lab_units,
                                c.lab_hours,
                                c.lecture_hours, 
                                cc.year_level, 
                                cc.semester, 
                                cc.subject_type,
                                cc.is_core,
                                cc.prerequisites,
                                cc.co_requisites
                            FROM curriculum_courses cc
                            JOIN courses c ON cc.course_id = c.course_id
                            WHERE cc.curriculum_id = :curriculum_id
                            ORDER BY cc.year_level, cc.semester, c.course_code
                        ");
                            $stmt->execute([':curriculum_id' => $curriculum_id]);
                            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            echo json_encode($courses);
                        } catch (Exception $e) {

                            echo json_encode(['error' => 'Failed to fetch courses: ' . $e->getMessage()]);
                        }
                        exit;

                        break;

                    case 'get_curricula':
                        header('Content-Type: application/json');
                        $curriculum_id = isset($_POST['curriculum_id']) ? intval($_POST['curriculum_id']) : null;

                        try {
                            $query = "SELECT curriculum_id, curriculum_name, total_units, updated_at, status, effective_year 
                            FROM curricula WHERE department_id = :department_id";
                            $params = [':department_id' => $departmentId];
                            if ($curriculum_id) {
                                $query .= " AND curriculum_id = :curriculum_id";
                                $params[':curriculum_id'] = $curriculum_id;
                            }
                            $stmt = $this->db->prepare($query);
                            $stmt->execute($params);
                            $curricula = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            if (empty($curricula)) {
                                echo json_encode(['error' => 'No curricula found']);
                                exit;
                            }

                            // Fetch course counts for each curriculum
                            foreach ($curricula as &$curriculum) {
                                $courseCountStmt = $this->db->prepare("SELECT COUNT(*) FROM curriculum_courses WHERE curriculum_id = :curriculum_id");
                                $courseCountStmt->execute([':curriculum_id' => $curriculum['curriculum_id']]);
                                $curriculum['course_count'] = $courseCountStmt->fetchColumn();
                            }
                            unset($curriculum); // Unset reference

                            echo json_encode($curricula);
                            exit;
                        } catch (PDOException $e) {
                            error_log("get_curricula: Error - " . $e->getMessage());
                            echo json_encode(['error' => 'Database error occurred']);
                            exit;
                        }
                        break;

                    case 'add_curriculum':
                        $errors = [];
                        [$curriculum_name, $nameErrors] = $this->validateInput($_POST, 'curriculum_name', ['required', 'string']);
                        [$curriculum_code, $codeErrors] = $this->validateInput($_POST, 'curriculum_code', ['required', 'string']);
                        [$effective_year, $yearErrors] = $this->validateInput($_POST, 'effective_year', ['required', 'numeric', 'min' => 2000, 'max' => 2100]);
                        [$description, $descErrors] = $this->validateInput($_POST, 'description', ['string']);
                        $errors = array_merge($errors, $nameErrors, $codeErrors, $yearErrors, $descErrors);

                        if (empty($errors)) {
                            $stmt = $this->db->prepare("
                            INSERT INTO curricula (curriculum_name, curriculum_code, description, total_units, department_id, effective_year, status) 
                            VALUES (:name, :code, :desc, 0, :dept, :year, 'Draft')
                        ");
                            $stmt->execute([
                                ':name' => $curriculum_name,
                                ':code' => $curriculum_code,
                                ':desc' => $description,
                                ':dept' => $departmentId,
                                ':year' => $effective_year
                            ]);
                            $curriculum_id = $this->db->lastInsertId();

                            $programStmt = $this->db->prepare("SELECT program_id FROM programs WHERE department_id = :department_id LIMIT 1");
                            $programStmt->execute([':department_id' => $departmentId]);
                            $program_id = $programStmt->fetchColumn();
                            if ($program_id) {
                                $this->db->prepare("
                                INSERT INTO curriculum_programs (curriculum_id, program_id, is_primary, required) 
                                VALUES (:curriculum_id, :program_id, 1, 1)
                            ")->execute([':curriculum_id' => $curriculum_id, ':program_id' => $program_id]);
                            }

                            $success = "Curriculum added successfully.";
                            $this->logActivity($chairId, $departmentId, 'Add Curriculum', "Added curriculum ID $curriculum_id", 'curricula', $curriculum_id);
                            $curricula = $this->fetchCurricula($departmentId);
                        } else {
                            $error = implode("<br>", $errors);
                        }
                        break;

                    case 'get_curriculum_data':
                        header('Content-Type: application/json');
                        $curriculum_id = intval($_POST['curriculum_id'] ?? 0);

                        if ($curriculum_id < 1) {
                            echo json_encode(['error' => 'Invalid curriculum ID']);
                            exit;
                        }

                        try {
                            $stmt = $this->db->prepare("
                                SELECT curriculum_id, curriculum_name, curriculum_code, description, 
                                    effective_year, status, total_units, created_at, updated_at
                                FROM curricula 
                                WHERE curriculum_id = :curriculum_id AND department_id = :department_id
                            ");
                            $stmt->execute([
                                ':curriculum_id' => $curriculum_id,
                                ':department_id' => $departmentId
                            ]);
                            $curriculum = $stmt->fetch(PDO::FETCH_ASSOC);

                            if (!$curriculum) {
                                echo json_encode(['error' => 'Curriculum not found']);
                                exit;
                            }

                            echo json_encode($curriculum);
                            exit;
                        } catch (PDOException $e) {
                            error_log("get_curriculum_data: Error - " . $e->getMessage());
                            echo json_encode(['error' => 'Database error occurred']);
                            exit;
                        }
                        break;

                    case 'edit_curriculum':
                        $curriculum_id = intval($_POST['curriculum_id'] ?? 0);
                        $errors = [];

                        [$curriculum_name, $nameErrors] = $this->validateInput($_POST, 'curriculum_name', ['required', 'string']);
                        [$curriculum_code, $codeErrors] = $this->validateInput($_POST, 'curriculum_code', ['required', 'string']);
                        [$effective_year, $yearErrors] = $this->validateInput($_POST, 'effective_year', ['required', 'numeric', 'min' => 2000, 'max' => 2100]);
                        [$description, $descErrors] = $this->validateInput($_POST, 'description', ['string']);
                        [$status, $statusErrors] = $this->validateInput($_POST, 'status', ['required', 'in' => ['Draft', 'Active', 'Archived']]);

                        $errors = array_merge($errors, $nameErrors, $codeErrors, $yearErrors, $descErrors, $statusErrors);

                        if ($curriculum_id < 1) {
                            $errors[] = "Invalid curriculum ID.";
                        }

                        if (empty($errors)) {
                            $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM curricula WHERE curriculum_id = :curriculum_id AND department_id = :department_id");
                            $checkStmt->execute([':curriculum_id' => $curriculum_id, ':department_id' => $departmentId]);
                            if ($checkStmt->fetchColumn() == 0) {
                                $errors[] = "Curriculum not found or access denied.";
                            }
                        }

                        if (empty($errors)) {
                            try {
                                $stmt = $this->db->prepare("
                                UPDATE curricula 
                                SET curriculum_name = :name, curriculum_code = :code, description = :desc, 
                                    effective_year = :year, status = :status, updated_at = CURRENT_TIMESTAMP
                                WHERE curriculum_id = :id AND department_id = :department_id
                            ");
                                $result = $stmt->execute([
                                    ':name' => $curriculum_name,
                                    ':code' => $curriculum_code,
                                    ':desc' => $description,
                                    ':year' => $effective_year,
                                    ':status' => $status,
                                    ':id' => $curriculum_id,
                                    ':department_id' => $departmentId
                                ]);

                                if ($result && $stmt->rowCount() > 0) {
                                    $this->logActivity($chairId, $departmentId, 'Update Curriculum', "Updated curriculum ID $curriculum_id", 'curricula', $curriculum_id);
                                    if ($_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                                        header('Content-Type: application/json');
                                        echo json_encode(['success' => 'Curriculum updated successfully.']);
                                        exit;
                                    }
                                    $success = "Curriculum updated successfully.";
                                    $curricula = $this->fetchCurricula($departmentId);
                                } else {
                                    $errors[] = "No changes were made or curriculum not found.";
                                }
                            } catch (PDOException $e) {
                                error_log("edit_curriculum: Database error - " . $e->getMessage());
                                $errors[] = "Database error occurred while updating curriculum.";
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
                        break;

                    case 'add_course':
                        header('Content-Type: application/json');
                        $curriculum_id = intval($_POST['curriculum_id'] ?? 0);
                        $course_id = intval($_POST['course_id'] ?? 0);
                        $year_level = $_POST['year_level'] ?? '';
                        $semester = $_POST['semester'] ?? '';
                        $subject_type = $_POST['subject_type'] ?? '';

                        if ($curriculum_id < 1 || $course_id < 1 || empty($year_level) || empty($semester) || empty($subject_type)) {
                            echo json_encode(['error' => 'Invalid input']);
                            exit;
                        }

                        try {
                            $this->db->beginTransaction();

                            // Check for duplicate
                            $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM curriculum_courses WHERE curriculum_id = :curriculum_id AND course_id = :course_id");
                            $checkStmt->execute([':curriculum_id' => $curriculum_id, ':course_id' => $course_id]);
                            if ($checkStmt->fetchColumn() > 0) {
                                echo json_encode(['error' => 'Course already exists in this curriculum']);
                                exit;
                            }

                            // Insert the course
                            $insertStmt = $this->db->prepare("INSERT INTO curriculum_courses (curriculum_id, course_id, year_level, semester, subject_type, is_core) VALUES (:curriculum_id, :course_id, :year_level, :semester, :subject_type, 1)");
                            $insertStmt->execute([
                                ':curriculum_id' => $curriculum_id,
                                ':course_id' => $course_id,
                                ':year_level' => $year_level,
                                ':semester' => $semester,
                                ':subject_type' => $subject_type
                            ]);

                            // Update total_units in curricula
                            $courseUnitsStmt = $this->db->prepare("SELECT units FROM courses WHERE course_id = :course_id");
                            $courseUnitsStmt->execute([':course_id' => $course_id]);
                            $units = $courseUnitsStmt->fetchColumn();
                            if ($units !== false) {
                                $updateStmt = $this->db->prepare("UPDATE curricula SET total_units = total_units + :units, updated_at = NOW() WHERE curriculum_id = :curriculum_id");
                                $updateStmt->execute([':curriculum_id' => $curriculum_id, ':units' => $units]);
                            }

                            $this->db->commit();

                            // Fetch updated total_units
                            $curriculumStmt = $this->db->prepare("SELECT total_units FROM curricula WHERE curriculum_id = :curriculum_id");
                            $curriculumStmt->execute([':curriculum_id' => $curriculum_id]);
                            $new_total_units = $curriculumStmt->fetchColumn();

                            echo json_encode(['success' => true, 'new_total_units' => $new_total_units]);
                            exit;
                        } catch (PDOException $e) {
                            $this->db->rollBack();
                            error_log("add_course: Error - " . $e->getMessage());
                            echo json_encode(['error' => 'Database error occurred']);
                            exit;
                        }
                        break;

                    case 'remove_course':
                        header('Content-Type: application/json');
                        $curriculum_id = intval($_POST['curriculum_id'] ?? 0);
                        $course_id = intval($_POST['course_id'] ?? 0);

                        if ($curriculum_id < 1 || $course_id < 1) {
                            echo json_encode(['error' => 'Invalid curriculum or course ID']);
                            exit;
                        }

                        try {
                            $this->db->beginTransaction();

                            // Check if the course exists in the curriculum
                            $courseStmt = $this->db->prepare("
                                SELECT c.units 
                                FROM curriculum_courses cc 
                                JOIN courses c ON cc.course_id = c.course_id 
                                WHERE cc.curriculum_id = :curriculum_id AND cc.course_id = :course_id
                            ");
                            $courseStmt->execute([':curriculum_id' => $curriculum_id, ':course_id' => $course_id]);
                            $course = $courseStmt->fetch(PDO::FETCH_ASSOC);

                            if (!$course) {
                                $this->db->rollBack();
                                echo json_encode(['error' => 'Course not found in curriculum']);
                                exit;
                            }

                            $unitsToRemove = $course['units'];

                            // Remove the course
                            $deleteStmt = $this->db->prepare("DELETE FROM curriculum_courses WHERE curriculum_id = :curriculum_id AND course_id = :course_id");
                            $deleteStmt->execute([':curriculum_id' => $curriculum_id, ':course_id' => $course_id]);

                            // Update total_units in curricula
                            $updateStmt = $this->db->prepare("
                                UPDATE curricula 
                                SET total_units = GREATEST(0, total_units - :units_to_remove), 
                                    updated_at = NOW() 
                                WHERE curriculum_id = :curriculum_id
                            ");
                            $updateStmt->execute([':curriculum_id' => $curriculum_id, ':units_to_remove' => $unitsToRemove]);

                            $this->db->commit();

                            // Fetch the updated total_units
                            $curriculumStmt = $this->db->prepare("SELECT total_units FROM curricula WHERE curriculum_id = :curriculum_id");
                            $curriculumStmt->execute([':curriculum_id' => $curriculum_id]);
                            $new_total_units = $curriculumStmt->fetchColumn();

                            echo json_encode(['success' => true, 'new_total_units' => $new_total_units]);
                            exit;
                        } catch (PDOException $e) {
                            $this->db->rollBack();
                            error_log("remove_course: Error - " . $e->getMessage());
                            echo json_encode(['error' => 'Database error occurred']);
                            exit;
                        }
                        break;

                    case 'toggle_curriculum':
                        $curriculum_id = intval($_POST['curriculum_id'] ?? 0);
                        $current_status = $_POST['status'] ?? '';
                        $new_status = $current_status === 'Active' ? 'Draft' : 'Active';

                        if ($curriculum_id < 1) {
                            $error = "Invalid curriculum.";
                            break;
                        }

                        $stmt = $this->db->prepare("UPDATE curricula SET status = :status WHERE curriculum_id = :curriculum_id");
                        $stmt->execute([':status' => $new_status, ':curriculum_id' => $curriculum_id]);

                        $this->logActivity($chairId, $departmentId, 'Toggle Curriculum Status', "Toggled curriculum ID $curriculum_id to $new_status", 'curricula', $curriculum_id);
                        $success = "Curriculum status updated to $new_status.";
                        $curricula = $this->fetchCurricula($departmentId);
                        break;

                    default:
                        if ($_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                            header('Content-Type: application/json');
                            echo json_encode(['error' => 'Invalid action']);
                            exit;
                        }
                        $error = "Invalid action.";
                }
            }

            // Pass variables to the view
            $db = $this->db;
            require_once __DIR__ . '/../views/chair/curriculum.php';
        } catch (PDOException $e) {
            error_log("curriculum: Error - " . $e->getMessage());
            $error = $this->handlePDOException($e, "Failed to load curriculum data");
            $curricula = [];
            $courses = [];
            $db = $this->db;
            require_once __DIR__ . '/../views/chair/curriculum.php';
        }
    }

    /**
     * Manage courses
     */
    public function courses()
    {
        $this->requireAnyRole('chair', 'dean');
        try {
            $chairId = $_SESSION['user_id'] ?? 0;
            // Get department for the Chair - use currentDepartmentId if Program Chair
            $departmentId = $this->currentDepartmentId ?: $this->getChairDepartment($chairId);

            $error = null;
            $success = null;
            $courses = [];
            $editCourse = null;
            $totalCourses = 0;
            $totalPages = 1;
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $searchTerm = isset($_GET['search']) && trim($_GET['search']) !== '' ? trim($_GET['search']) : '';

            if (!$departmentId) {
                error_log("courses: No department found for chairId: $chairId");
                $error = "No department assigned to this chair.";
                require_once __DIR__ . '/../views/chair/courses.php';
                return;
            }

            $perPage = 100;
            $offset = ($page - 1) * $perPage;

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    $courseId = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
                    $data = [
                        'course_code' => trim($_POST['course_code'] ?? ''),
                        'course_name' => trim($_POST['course_name'] ?? ''),
                        'department_id' => $departmentId,
                        'subject_type' => $_POST['subject_type'] ?? 'Professional Course',
                        'units' => intval($_POST['units'] ?? 0),
                        'lecture_units' => intval($_POST['lecture_units'] ?? 0),
                        'lab_units' => intval($_POST['lab_units'] ?? 0),
                        'lecture_hours' => intval($_POST['lecture_hours'] ?? 0),
                        'lab_hours' => intval($_POST['lab_hours'] ?? 0),
                        'is_active' => isset($_POST['is_active']) ? 1 : 0
                    ];

                    $data['lecture_hours'] = $data['lecture_units'] * 1;
                    $data['lab_hours'] = $data['lab_units'] * 2;

                    $errors = [];
                    if (empty($data['course_code'])) $errors[] = "Course code is required.";
                    if (empty($data['course_name'])) $errors[] = "Course name is required.";
                    if ($data['units'] < 1) $errors[] = "Units must be at least 1.";
                    if (!in_array($data['subject_type'], ['Professional Course', 'General Education'])) {
                        $errors[] = "Invalid subject type.";
                    }

                    $codeCheckStmt = $this->db->prepare("SELECT course_id FROM courses WHERE course_code = ? AND course_id != ?");
                    $codeCheckStmt->execute([$data['course_code'], $courseId]);
                    if ($codeCheckStmt->fetchColumn()) {
                        $errors[] = "Course code already exists.";
                    }

                    if (empty($errors)) {
                        if ($courseId > 0) {
                            $stmt = $this->db->prepare("
                            UPDATE courses SET 
                                course_code = ?, 
                                course_name = ?, 
                                department_id = ?, 
                                subject_type = ?,
                                units = ?, 
                                lecture_units = ?,
                                lab_units = ?,
                                lecture_hours = ?, 
                                lab_hours = ?, 
                                is_active = ? 
                            WHERE course_id = ?
                        ");
                            $updateParams = [
                                $data['course_code'],
                                $data['course_name'],
                                $data['department_id'],
                                $data['subject_type'],
                                $data['units'],
                                $data['lecture_units'],
                                $data['lab_units'],
                                $data['lecture_hours'],
                                $data['lab_hours'],
                                $data['is_active'],
                                $courseId
                            ];
                            $stmt->execute($updateParams);
                            $success = "Course updated successfully.";
                            $this->logActivity($chairId, $departmentId, 'Update Course', "Updated course ID $courseId", 'courses', $courseId);
                        } else {
                            $stmt = $this->db->prepare("
                            INSERT INTO courses 
                                (course_code, course_name, department_id, subject_type, units, 
                                lecture_units, lab_units, lecture_hours, lab_hours, is_active) 
                            VALUES 
                                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                            $insertParams = [
                                $data['course_code'],
                                $data['course_name'],
                                $data['department_id'],
                                $data['subject_type'],
                                $data['units'],
                                $data['lecture_units'],
                                $data['lab_units'],
                                $data['lecture_hours'],
                                $data['lab_hours'],
                                $data['is_active']
                            ];
                            $stmt->execute($insertParams);
                            $courseId = $this->db->lastInsertId();
                            $success = "Course added successfully.";
                            $this->logActivity($chairId, $departmentId, 'Add Course', "Added course ID $courseId", 'courses', $courseId);
                        }
                    } else {
                        $error = implode("<br>", $errors);
                    }
                } catch (PDOException $e) {
                    error_log("courses: Error saving course - " . $e->getMessage());
                    $error = "Failed to save course: " . $e->getMessage();
                }
            }

            if (isset($_GET['toggle_status']) && $_GET['toggle_status'] > 0) {
                try {
                    $courseId = intval($_GET['toggle_status']);
                    $currentStatusStmt = $this->db->prepare("SELECT is_active FROM courses WHERE course_id = ? AND department_id = ?");
                    $currentStatusStmt->execute([$courseId, $departmentId]);
                    $currentStatus = $currentStatusStmt->fetchColumn();

                    $toggleStmt = $this->db->prepare("
                    UPDATE courses 
                    SET is_active = NOT is_active 
                    WHERE course_id = ? 
                    AND department_id = ?
                ");
                    $toggleParams = [$courseId, $departmentId];

                    $toggleStmt->execute($toggleParams);
                    if ($toggleStmt->rowCount() > 0) {
                        $newStatus = !$currentStatus;
                        $actionType = $newStatus ? 'Activate Course' : 'Delete Course';
                        $actionDesc = "Toggled course ID $courseId to " . ($newStatus ? 'active' : 'inactive');
                        $this->logActivity($chairId, $departmentId, $actionType, $actionDesc, 'courses', $courseId);
                        $success = "Course status updated successfully.";
                    } else {
                        $error = "Course not found or you don't have permission to update it.";
                    }
                } catch (PDOException $e) {
                    error_log("courses: Error toggling status - " . $e->getMessage());
                    $error = "Failed to update course status: " . $e->getMessage();
                }
            }

            if (isset($_GET['edit']) && $_GET['edit'] > 0) {
                try {
                    $courseId = intval($_GET['edit']);
                    $editStmt = $this->db->prepare("
                    SELECT c.* 
                    FROM courses c 
                    WHERE c.course_id = ? 
                    AND c.department_id = ?
                ");
                    $editParams = [$courseId, $departmentId];

                    $editStmt->execute($editParams);
                    $editCourse = $editStmt->fetch(PDO::FETCH_ASSOC);
                    if (!$editCourse) {
                        $error = "Course not found or you don't have permission to edit it.";
                    }
                } catch (PDOException $e) {
                    error_log("courses: Error loading course for editing - " . $e->getMessage());
                    $error = "Failed to load course for editing: " . $e->getMessage();
                }
            }

            // Fetch total number of courses
            try {
                $totalQuery = "SELECT COUNT(*) as total FROM courses WHERE department_id = ?";
                $totalParams = [$departmentId];

                if ($searchTerm) {
                    $totalQuery = "SELECT COUNT(*) as total FROM courses WHERE department_id = ? AND (course_code LIKE ? OR course_name LIKE ?)";
                    $totalParams = [$departmentId, "%$searchTerm%", "%$searchTerm%"];
                }

                $totalStmt = $this->db->prepare($totalQuery);

                $totalStmt->execute($totalParams);
                $result = $totalStmt->fetch(PDO::FETCH_ASSOC);
                $totalCourses = $result['total'] ?? 0;
                $totalPages = max(1, ceil($totalCourses / $perPage));
            } catch (PDOException $e) {
                error_log("Error counting courses: " . $e->getMessage());
                $error = "Failed to count courses: " . $e->getMessage();
                $totalCourses = 0;
                $totalPages = 1;
            }

            // Fetch courses with pagination
            try {
                $coursesQuery = "SELECT c.*, d.department_name FROM courses c LEFT JOIN departments d ON c.department_id = d.department_id WHERE c.department_id = ?";
                $coursesParams = [$departmentId];

                if ($searchTerm) {
                    $coursesQuery .= " AND (c.course_code LIKE ? OR c.course_name LIKE ?)";
                    $coursesParams[] = "%$searchTerm%";
                    $coursesParams[] = "%$searchTerm%";
                }

                $coursesQuery .= " ORDER BY c.course_code LIMIT ? OFFSET ?";
                $coursesParams[] = (int)$perPage;
                $coursesParams[] = (int)$offset;

                error_log("Courses query: Query = $coursesQuery, Params = " . json_encode($coursesParams));

                $coursesStmt = $this->db->prepare($coursesQuery);
                $coursesStmt->execute($coursesParams);
                $courses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);

                error_log("Courses fetched: " . count($courses) . " rows returned");
            } catch (PDOException $e) {
                error_log("Error fetching courses: " . $e->getMessage());
                $error = "Failed to load courses: " . $e->getMessage();
                $courses = [];
            }
        } catch (PDOException $e) {
            error_log("courses: Unexpected error - " . $e->getMessage());
            $error = "An unexpected error occurred: " . $e->getMessage();
            $totalCourses = 0;
            $totalPages = 1;
        }

        require_once __DIR__ . '/../views/chair/courses.php';
    }

    // Updated helper method to log activity with user names
    private function logActivity($userId, $departmentId, $actionType, $actionDescription, $entityType, $entityId, $metadataId = null)
    {
        try {
            // First, get the user's complete name
            $userName = $this->schedulingService->getUserCompleteName($userId);

            // Replace user_id with user name in the action description
            $formattedDescription = $this->schedulingService->formatActionDescription($actionDescription, $userId, $userName);

            $stmt = $this->db->prepare("
            INSERT INTO activity_logs 
            (user_id, department_id, action_type, action_description, entity_type, entity_id, metadata_id, created_at) 
            VALUES (:user_id, :department_id, :action_type, :action_description, :entity_type, :entity_id, :metadata_id, NOW())
        ");
            $params = [
                ':user_id' => $userId,
                ':department_id' => $departmentId,
                ':action_type' => $actionType,
                ':action_description' => $formattedDescription,
                ':entity_type' => $entityType,
                ':entity_id' => $entityId,
                ':metadata_id' => $metadataId
            ];
            error_log("Logging activity: Query = INSERT INTO activity_log ..., Params = " . json_encode($params));
            $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("logActivity: Failed to log activity - " . $e->getMessage());
        }
    }

    public function checkCourseCode()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_code'])) {
            $courseCode = trim($_POST['course_code']);
            $chairId = $_SESSION['user_id'] ?? 0;
            $departmentId = $this->getChairDepartment($chairId);

            if ($departmentId) {
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM courses WHERE course_code = :course_code AND department_id = :department_id");
                $stmt->execute(['course_code' => $courseCode, 'department_id' => $departmentId]);
                $exists = $stmt->fetchColumn() > 0;
                echo json_encode(['exists' => $exists]);
            }
            exit;
        }
    }

    /**
     * Manage faculty
     */

    public function search()
    {
        $this->requireAnyRole('chair', 'dean');
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if (!$isAjax || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log("search: Invalid request - Expected POST AJAX, got " . $_SERVER['REQUEST_METHOD']);
            header('Content-Type: application/json');
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
            exit;
        }

        try {
            $chairId = $_SESSION['user_id'] ?? null;
            if (!$chairId) {
                error_log("search: No user_id in session");
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['error' => 'No user session']);
                exit;
            }

            $departmentId = $this->getChairDepartment($chairId);
            if (!$departmentId) {
                error_log("search: No department found for chair_id=$chairId");
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['error' => 'No department assigned']);
                exit;
            }

            $collegeStmt = $this->db->prepare("SELECT college_id FROM departments WHERE department_id = :department_id");
            $collegeStmt->execute([':department_id' => $departmentId]);
            $collegeId = $collegeStmt->fetchColumn();
            if (!$collegeId) {
                error_log("search: No college found for department_id=$departmentId");
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['error' => 'No college assigned']);
                exit;
            }


            $name = isset($_POST['name']) ? trim($_POST['name']) : '';

            if (empty($name)) {
                error_log("search: No name provided");
                header('Content-Type: application/json');
                echo json_encode(['results' => [], 'includable' => []]);
                exit;
            }

            // Log the search activity
            $this->logActivity($chairId, $departmentId, 'Search Faculty', "Searched for name: $name", 'users', null);

            // Query for users already in the chair's department (role_id 5 or 6)
            $query = "
            SELECT 
                u.user_id,
                u.employee_id,
                COALESCE(u.title, '') as title, 
                u.first_name,
                u.middle_name,
                u.last_name,
                COALESCE(u.suffix, '') as suffix,
                r.role_name,
                COALESCE(f.academic_rank, 'N/A') as academic_rank,
                COALESCE(f.employment_type, 'N/A') as employment_type,
                COALESCE(d.department_name, 'N/A') as department_name,
                c.college_name,
                pc.program_id,
                p.program_name,
                deans.college_id AS dean_college_id
            FROM 
                users u
                LEFT JOIN roles r ON u.role_id = r.role_id
                LEFT JOIN faculty f ON u.user_id = f.user_id
                JOIN faculty_departments fd ON f.faculty_id = fd.faculty_id AND fd.department_id = :department_id
                JOIN departments d ON fd.department_id = d.department_id
                JOIN colleges c ON d.college_id = c.college_id
                LEFT JOIN program_chairs pc ON u.user_id = pc.user_id AND pc.is_current = 1
                LEFT JOIN programs p ON pc.program_id = p.program_id
                LEFT JOIN deans ON u.user_id = deans.user_id AND deans.is_current = 1
            WHERE 
                u.role_id IN ( 2, 3, 4, 5, 6) -- Include Program Chairs (5) and Faculty (6)
                AND (u.first_name LIKE :name1 OR u.last_name LIKE :name2 OR CONCAT(u.first_name, ' ', u.last_name) LIKE :name3)
            ORDER BY u.last_name, u.first_name
            LIMIT 10";
            $params = [
                ':department_id' => $departmentId,
                ':name1' => "%$name%",
                ':name2' => "%$name%",
                ':name3' => "%$name%"
            ];


            $stmt = $this->db->prepare($query);
            if (!$stmt) {
                $errorInfo = $this->db->errorInfo();
                error_log("search: Prepare Error: " . print_r($errorInfo, true));
                throw new Exception("Failed to prepare statement: " . $errorInfo[2]);
            }
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("search: Found " . count($results) . " results");

            $includableQuery = "
            SELECT 
                u.user_id,
                u.employee_id,
                COALESCE(u.title, '') as title,
                u.first_name,
                COALESCE(u.middle_name, '') as middle_name,
                u.last_name,
                COALESCE(u.suffix, '') as suffix,
                r.role_name,
                COALESCE(f.academic_rank, 'N/A') as academic_rank,
                COALESCE(f.employment_type, 'N/A') as employment_type,
                COALESCE(d.department_name, 'N/A') as department_name,
                COALESCE(c.college_name, 'N/A') as college_name,
                COALESCE(cs.course_name, 'N/A') as specialization,
                GROUP_CONCAT(COALESCE(d.department_id, 'N/A') SEPARATOR ', ') AS department_ids
            FROM 
                users u
                LEFT JOIN roles r ON u.role_id = r.role_id
                LEFT JOIN faculty f ON u.user_id = f.user_id
                LEFT JOIN faculty_departments fd ON f.faculty_id = fd.faculty_id
                LEFT JOIN departments d ON fd.department_id = d.department_id
                LEFT JOIN colleges c ON (d.college_id = c.college_id OR u.college_id = c.college_id)
                LEFT JOIN specializations sp ON f.faculty_id = sp.faculty_id AND sp.is_primary_specialization = 1
                LEFT JOIN courses cs ON sp.course_id = cs.course_id
            WHERE 
                u.role_id IN (1, 2, 3, 4, 5, 6)
                AND u.user_id NOT IN (
                    SELECT u2.user_id 
                    FROM users u2
                    LEFT JOIN faculty f2 ON u2.user_id = f2.user_id
                    LEFT JOIN faculty_departments fd2 ON f2.faculty_id = fd2.faculty_id 
                    WHERE fd2.department_id = :department_id
                )
                AND (u.first_name LIKE :name1 OR u.last_name LIKE :name2 OR CONCAT(u.first_name, ' ', u.last_name) LIKE :name3)
            GROUP BY 
                u.user_id
            ORDER BY u.last_name, u.first_name
            LIMIT 10";
            $includableParams = [
                ':department_id' => $departmentId,
                ':name1' => "%$name%",
                ':name2' => "%$name%",
                ':name3' => "%$name%"
            ];


            $includableStmt = $this->db->prepare($includableQuery);
            if (!$includableStmt) {
                $errorInfo = $this->db->errorInfo();

                throw new Exception("Failed to prepare includable statement: " . $errorInfo[2]);
            }
            $includableStmt->execute($includableParams);
            $includableResults = $includableStmt->fetchAll(PDO::FETCH_ASSOC);

            header('Content-Type: application/json');
            echo json_encode(['results' => $results, 'includable' => $includableResults]);
            exit;
        } catch (Exception $e) {
            error_log("search: Error - " . $e->getMessage());
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['error' => 'Failed to search faculty: ' . $e->getMessage()]);
            exit;
        }
    }

    public function faculty()
    {
        $this->requireAnyRole('chair', 'dean');
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
        }

        $chairId = $_SESSION['user_id'] ?? null;
        // Get department for the Chair - use currentDepartmentId if Program Chair
        $departmentId = $this->currentDepartmentId ?: $this->getChairDepartment($chairId);

        $error = null;
        $success = null;
        $faculty = [];
        $colleges = [];
        $departments = [];
        $searchResults = [];

        $collegeId = null;
        if ($departmentId) {
            try {
                $collegeStmt = $this->db->prepare("SELECT college_id FROM departments WHERE department_id = :department_id");
                $collegeStmt->execute([':department_id' => $departmentId]);
                $collegeId = $collegeStmt->fetchColumn();

                if (!$collegeId) {
                    $error = "No college assigned to this department.";
                    error_log("faculty: No college found for department_id=$departmentId");
                }
            } catch (PDOException $e) {
                $error = "Failed to load college data: " . htmlspecialchars($e->getMessage());
                error_log("faculty: College Fetch Error: " . $e->getMessage());
            }
        } else {
            $error = "No department assigned to this chair.";
            error_log("faculty: No department assigned for chair_id=$chairId");
        }

        // In faculty() function, update the $fetchFaculty query:
        $fetchFaculty = function ($collegeId, $departmentId) {
            $baseUrl = $this->baseUrl;
            $query = "
            SELECT 
                u.user_id, 
                u.employee_id,
                COALESCE(u.title, '') as title, 
                u.first_name,
                COALESCE(u.middle_name, '') as middle_name, 
                u.last_name,
                COALESCE(u.suffix, '') as suffix, 
                COALESCE(f.academic_rank, 'N/A') as academic_rank, 
                COALESCE(f.employment_type, 'N/A') as employment_type, 
                COALESCE(c.course_name, 'N/A') AS specialization,
                COALESCE(u.profile_picture, '') AS profile_picture,
                COALESCE(GROUP_CONCAT(DISTINCT d.department_name SEPARATOR ', '), 'N/A') AS department_names, 
                COALESCE(c2.college_name, 'N/A') as college_name
            FROM 
                faculty f 
                JOIN users u ON f.user_id = u.user_id 
                JOIN faculty_departments fd ON f.faculty_id = fd.faculty_id
                JOIN departments d ON fd.department_id = d.department_id
                LEFT JOIN colleges c2 ON d.college_id = c2.college_id
                LEFT JOIN specializations s ON f.faculty_id = s.faculty_id AND s.is_primary_specialization = 1
                LEFT JOIN courses c ON s.course_id = c.course_id
            WHERE 
                fd.department_id = :department_id
            GROUP BY 
                u.user_id, f.faculty_id
            ORDER BY 
                u.last_name, u.first_name";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':department_id' => $departmentId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($results as &$result) {
                if ($result['profile_picture']) {
                    $result['profile_picture'] = $baseUrl . $result['profile_picture'];
                }
            }
            return $results;
        };

        if ($departmentId && $collegeId) {
            try {
                $faculty = $fetchFaculty($collegeId, $departmentId);
                error_log("faculty: Fetched " . count($faculty) . " faculty members");
            } catch (PDOException $e) {
                $error = "Failed to load faculty: " . htmlspecialchars($e->getMessage());
                error_log("faculty: Faculty Fetch Error: " . $e->getMessage());
            }
        }

        try {
            $collegesStmt = $this->db->query("SELECT college_id, college_name FROM colleges ORDER BY college_name");
            $colleges = $collegesStmt->fetchAll(PDO::FETCH_ASSOC);
            $departmentsStmt = $this->db->query("SELECT department_id, department_name, college_id FROM departments ORDER BY department_name");
            $departments = $departmentsStmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("faculty: Fetched " . count($colleges) . " colleges, " . count($departments) . " departments");
        } catch (PDOException $e) {
            $error = "Failed to load colleges and departments: " . htmlspecialchars($e->getMessage());
            error_log("faculty: Colleges/Departments Fetch Error: " . $e->getMessage());
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_faculty':
                    try {
                        $userId = intval($_POST['user_id']);
                        if (!$departmentId || !$collegeId) {
                            throw new Exception("Cannot add faculty: Invalid or missing department or college ID.");
                        }

                        $this->db->beginTransaction();

                        $checkStmt = $this->db->prepare("SELECT u.role_id, u.department_id, u.college_id, u.employee_id FROM users u WHERE u.user_id = :user_id");
                        $checkStmt->execute([':user_id' => $userId]);
                        $user = $checkStmt->fetch(PDO::FETCH_ASSOC);
                        error_log("faculty: User check for user_id=$userId: " . print_r($user, true));

                        if (!$user) {
                            throw new Exception("User does not exist.");
                        } elseif ($user['role_id'] != 3 && $user['role_id'] != 4 && $user['role_id'] != 5 && $user['role_id'] != 6) {
                            throw new Exception("Only faculty members can be added to the department.");
                        }

                        $facultyCheckStmt = $this->db->prepare("SELECT faculty_id FROM faculty WHERE user_id = :user_id");
                        $facultyCheckStmt->execute([':user_id' => $userId]);
                        $facultyId = $facultyCheckStmt->fetchColumn();
                        error_log("faculty: Faculty check for user_id=$userId, faculty_id=$facultyId");

                        if ($facultyId) {
                            $updateFacultyStmt = $this->db->prepare("
                            UPDATE faculty 
                            SET 
                                academic_rank = :academic_rank,
                                employment_type = :employment_type,
                                max_hours = :max_hours,
                                updated_at = CURRENT_TIMESTAMP
                            WHERE user_id = :user_id");
                            $updateFacultyStmt->execute([
                                ':academic_rank' => 'Instructor',
                                ':employment_type' => 'Part-time',
                                ':max_hours' => 18.00 ?? '',
                                ':user_id' => $userId
                            ]);
                            error_log("faculty: Updated faculty_id=$facultyId for user_id=$userId");
                        } else {
                            $insertFacultyStmt = $this->db->prepare("
                            INSERT INTO faculty (
                                user_id, 
                                employee_id, 
                                academic_rank, 
                                employment_type, 
                                max_hours
                            ) VALUES (
                                :user_id, 
                                :employee_id, 
                                :academic_rank, 
                                :employment_type, 
                                :max_hours
                            )");
                            $insertFacultyStmt->execute([
                                ':user_id' => $userId,
                                ':employee_id' => $user['employee_id'],
                                ':academic_rank' => 'Instructor',
                                ':employment_type' => 'Part-time',
                                ':max_hours' => 18.00 ?? ''
                            ]);
                            $facultyId = $this->db->lastInsertId();
                            error_log("faculty: Inserted new faculty_id=$facultyId for user_id=$userId");
                        }

                        $deptCountStmt = $this->db->prepare("SELECT COUNT(*) FROM faculty_departments WHERE faculty_id = :faculty_id");
                        $deptCountStmt->execute([':faculty_id' => $facultyId]);
                        $deptCount = $deptCountStmt->fetchColumn();
                        error_log("faculty: Department count for faculty_id=$facultyId: $deptCount");

                        if ($deptCount >= 5) {
                            throw new Exception("Faculty member is already assigned to the maximum of 5 departments.");
                        }

                        $deptCheckStmt = $this->db->prepare("
                        SELECT faculty_department_id 
                        FROM faculty_departments 
                        WHERE faculty_id = :faculty_id AND department_id = :department_id");
                        $deptCheckStmt->execute([
                            ':faculty_id' => $facultyId,
                            ':department_id' => $departmentId
                        ]);
                        $existingDept = $deptCheckStmt->fetchColumn();
                        error_log("faculty: Existing dept check for faculty_id=$facultyId, department_id=$departmentId: " . ($existingDept ? 'Exists' : 'Not found'));

                        if ($existingDept) {
                            throw new Exception("This faculty member is already in your department.");
                        }

                        $primaryCheckStmt = $this->db->prepare("
                        SELECT COUNT(*) 
                        FROM faculty_departments 
                        WHERE faculty_id = :faculty_id AND is_primary = 1");
                        $primaryCheckStmt->execute([':faculty_id' => $facultyId]);
                        $hasPrimary = $primaryCheckStmt->fetchColumn() > 0;
                        error_log("faculty: Has primary dept for faculty_id=$facultyId: " . ($hasPrimary ? 'Yes' : 'No'));


                        $isPrimary = !$hasPrimary;

                        $insertDeptStmt = $this->db->prepare("
                        INSERT INTO faculty_departments (
                            faculty_id, 
                            department_id, 
                            is_primary
                        ) VALUES (
                            :faculty_id, 
                            :department_id, 
                            :is_primary
                        )");
                        $insertDeptStmt->execute([
                            ':faculty_id' => $facultyId,
                            ':department_id' => $departmentId,
                            ':is_primary' => $isPrimary
                        ]);

                        // ✅ NO UPDATES to users.department_id or users.college_id
                        error_log("faculty: Added faculty_department assignment - faculty_id=$facultyId, department_id=$departmentId, is_primary=$isPrimary");
                        error_log("faculty: User's original department_id: {$user['department_id']}, college_id: {$user['college_id']} preserved");

                        $this->db->commit();
                        $faculty = $fetchFaculty($collegeId, $departmentId); // Refresh faculty list
                        $response = [
                            'success' => true,
                            'message' => "Faculty member added successfully.",
                            'faculty' => $faculty
                        ];
                        $this->logActivity($chairId, $departmentId, 'Add Faculty', "Added faculty username=" . $_SESSION['username'] . " to department_id=$departmentId", 'faculty_departments', $this->db->lastInsertId());
                        error_log("faculty: Added user_id=$userId to department_id=$departmentId, faculty_id=$facultyId, is_primary=$isPrimary");
                    } catch (PDOException | Exception $e) {
                        $this->db->rollBack();
                        $response = [
                            'success' => false,
                            'message' => "Failed to add faculty: " . htmlspecialchars($e->getMessage())
                        ];
                        error_log("faculty: Add Faculty Error: " . $e->getMessage() . " at " . ($e instanceof PDOException ? $e->getFile() . ":" . $e->getLine() : ''));
                    }
                    ob_clean();
                    echo json_encode($response);
                    exit;
                    break;

                case 'remove_faculty':
                    try {
                        $userId = intval($_POST['user_id']);
                        if (!$departmentId) {
                            throw new Exception("Cannot remove faculty: No department assigned to this chair.");
                        }

                        $this->db->beginTransaction();

                        $facultyCheckStmt = $this->db->prepare("SELECT faculty_id FROM faculty WHERE user_id = :user_id");
                        $facultyCheckStmt->execute([':user_id' => $userId]);
                        $facultyId = $facultyCheckStmt->fetchColumn();

                        if (!$facultyId) {
                            throw new Exception("Faculty member not found.");
                        }

                        $deptCheckStmt = $this->db->prepare("
                        SELECT faculty_department_id, is_primary 
                        FROM faculty_departments 
                        WHERE faculty_id = :faculty_id AND department_id = :department_id");
                        $deptCheckStmt->execute([
                            ':faculty_id' => $facultyId,
                            ':department_id' => $departmentId
                        ]);
                        $deptInfo = $deptCheckStmt->fetch(PDO::FETCH_ASSOC);

                        if (!$deptInfo) {
                            throw new Exception("This faculty member is not in your department.");
                        }

                        $deleteDeptStmt = $this->db->prepare("
                        DELETE FROM faculty_departments 
                        WHERE faculty_id = :faculty_id AND department_id = :department_id");
                        $deleteDeptStmt->execute([
                            ':faculty_id' => $facultyId,
                            ':department_id' => $departmentId
                        ]);

                        if ($deptInfo['is_primary']) {
                            // ✅ Only handle faculty_departments, don't touch users table
                            $newPrimaryStmt = $this->db->prepare("
                            SELECT department_id 
                            FROM faculty_departments 
                            WHERE faculty_id = :faculty_id 
                            AND faculty_department_id != :current_dept_id
                            LIMIT 1");
                            $newPrimaryStmt->execute([
                                ':faculty_id' => $facultyId,
                                ':current_dept_id' => $deptInfo['faculty_department_id']
                            ]);
                            $newPrimaryDept = $newPrimaryStmt->fetchColumn();

                            if ($newPrimaryDept) {
                                $updateDeptStmt = $this->db->prepare("
                                    UPDATE faculty_departments 
                                    SET is_primary = 1 
                                    WHERE faculty_id = :faculty_id AND department_id = :department_id");
                                $updateDeptStmt->execute([
                                    ':faculty_id' => $facultyId,
                                    ':department_id' => $newPrimaryDept
                                ]);
                                error_log("faculty: Set new primary department: $newPrimaryDept for faculty_id=$facultyId");
                            }
                            // ✅ NO updates to users.department_id or users.college_id
                        }
                        $faculty = $fetchFaculty($collegeId, $departmentId); // Refresh faculty list
                        $response = [
                            'success' => true,
                            'message' => "Faculty member removed successfully.",
                            'faculty' => $faculty
                        ];

                        $this->db->commit();
                        $this->logActivity($chairId, $departmentId, 'Remove Faculty', "Removed faculty user_id=$userId from department_id=$departmentId", 'faculty_departments', $deptInfo['faculty_department_id']);
                        error_log("faculty: Removed user_id=$userId from department_id=$departmentId");
                    } catch (PDOException | Exception $e) {
                        $this->db->rollBack();
                        $response = [
                            'success' => false,
                            'message' => "Failed to remove faculty: " . htmlspecialchars($e->getMessage())
                        ];
                        error_log("faculty: Remove Faculty Error: " . $e->getMessage() . " at " . ($e instanceof PDOException ? $e->getFile() . ":" . $e->getLine() : ''));
                    }
                    ob_clean();
                    echo json_encode($response);
                    exit;
                    break;

                case 'get_faculty_details':
                    try {
                        $userId = intval($_POST['user_id']);
                        $query = "
                        SELECT 
                            u.user_id, 
                            u.employee_id,
                            u.title, 
                            u.first_name,
                            u.middle_name, 
                            u.last_name,
                            u.suffix, 
                            f.academic_rank, 
                            f.employment_type, 
                            GROUP_CONCAT(CONCAT(c.course_name, IF(s.expertise_level IS NOT NULL, CONCAT(' (', s.expertise_level, ')'), '')) SEPARATOR ', ') AS specialization,
                            COALESCE(u.profile_picture) AS profile_picture,
                            GROUP_CONCAT(d.department_name SEPARATOR ', ') AS department_names, 
                            c2.college_name,
                            u.email
                        FROM 
                            faculty f 
                            JOIN users u ON f.user_id = u.user_id 
                            LEFT JOIN faculty_departments fd ON f.faculty_id = fd.faculty_id
                            LEFT JOIN departments d ON fd.department_id = d.department_id
                            LEFT JOIN colleges c2 ON u.college_id = c2.college_id
                            LEFT JOIN specializations s ON f.faculty_id = s.faculty_id 
                            LEFT JOIN courses c ON s.course_id = c.course_id
                        WHERE 
                            u.user_id = :user_id
                        GROUP BY 
                            u.user_id, f.faculty_id";
                        $stmt = $this->db->prepare($query);
                        $stmt->execute([':user_id' => $userId]);
                        $facultyDetails = $stmt->fetch(PDO::FETCH_ASSOC);

                        if (!$facultyDetails) {
                            error_log("get_faculty_details: User ID $userId not found");
                            ob_clean();
                            echo json_encode(['error' => 'Faculty member not found']);
                            exit;
                        }

                        if ($facultyDetails['profile_picture'] && $facultyDetails['profile_picture']) {
                            $facultyDetails['profile_picture'] = $this->baseUrl . $facultyDetails['profile_picture'];
                        }

                        $this->logActivity($chairId, $departmentId, 'View Faculty Details', "Viewed details for user_id=$userId", 'users', $userId);
                        error_log("get_faculty_details: Fetched details for user_id=$userId");
                        ob_clean();
                        echo json_encode(['success' => true, 'data' => $facultyDetails]);
                        exit;
                    } catch (PDOException $e) {
                        error_log("get_faculty_details: Error - " . $e->getMessage());
                        ob_clean();
                        echo json_encode(['error' => 'Failed to fetch faculty details: ' . htmlspecialchars($e->getMessage())]);
                        exit;
                    }
                    break;

                default:
            }
        }

        if ($isAjax && ($_POST['action'] ?? '') !== 'get_faculty_details') {
            ob_clean();
            echo json_encode([
                'success' => $success,
                'error' => $error,
                'faculty' => $faculty,
                'colleges' => $colleges,
                'departments' => $departments,
                'searchResults' => $searchResults
            ]);
            exit;
        }

        if (!$isAjax) {
            require_once __DIR__ . '/../views/chair/faculty.php';
        }
    }

    /**
     * View/edit profile
     */
    public function profile(): void
    {
        $this->requireAnyRole('chair', 'dean');
        parent::profile();
    }

    private function handleProfilePictureUpload($userId)
    {
        if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] == UPLOAD_ERR_NO_FILE) {
            error_log("profile: No file uploaded for user_id: $userId");
            return null;
        }

        $file = $_FILES['profile_picture'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        if (!in_array($file['type'], $allowedTypes)) {
            error_log("profile: Invalid file type for user_id: $userId - " . $file['type']);
            return "Error: Only JPEG, PNG, and GIF files are allowed.";
        }

        if ($file['size'] > $maxSize) {
            error_log("profile: File size exceeds limit for user_id: $userId - " . $file['size']);
            return "Error: File size exceeds 2MB limit.";
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = "profile_{$userId}_" . time() . ".{$ext}";
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/profile_pictures/'; // Public-accessible path
        $uploadPath = $uploadDir . $filename;

        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                error_log("profile: Failed to create upload directory: $uploadDir");
                return "Error: Failed to create upload directory.";
            }
        }

        // Remove existing profile picture
        $stmt = $this->db->prepare("SELECT profile_picture FROM users WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        $currentPicture = $stmt->fetchColumn();
        if ($currentPicture && file_exists($_SERVER['DOCUMENT_ROOT'] . $currentPicture)) {
            if (!unlink($_SERVER['DOCUMENT_ROOT'] . $currentPicture)) {
                error_log("profile: Failed to delete existing profile picture: $currentPicture");
            }
        }

        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            error_log("profile: Successfully uploaded file to $uploadPath for user_id: $userId");
            return "/uploads/profile_pictures/{$filename}";
        } else {
            error_log("profile: Failed to move uploaded file for user_id: $userId to $uploadPath - Check permissions or disk space");
            return "Error: Failed to upload file.";
        }
    }

    public function settings()
    {
        $this->requireAnyRole('chair', 'dean');
        if (!$this->authService->isLoggedIn()) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Please log in to access settings'];
            header('Location: /login');
            exit;
        }

        $userId = $_SESSION['user_id'];
        $chairId = $this->getChairDepartment($userId);
        $departmentId = $_SESSION['department_id'];
        $csrfToken = $this->authService->generateCsrfToken();
        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->authService->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid CSRF token'];
                header('Location: /chair/settings');
                exit;
            }

            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $newEmail = $_POST['new_email'] ?? '';

            // Handle password update
            if (!empty($newPassword) || !empty($confirmPassword)) {
                if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                    $error = 'All password fields are required.';
                } elseif ($newPassword !== $confirmPassword) {
                    $error = 'New password and confirmation do not match.';
                } elseif (strlen($newPassword) < 8) {
                    $error = 'New password must be at least 8 characters long.';
                } else {
                    $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE user_id = :user_id");
                    $stmt->execute([':user_id' => $userId]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$row || !password_verify($currentPassword, $row['password_hash'])) {
                        $error = 'Current password is incorrect.';
                    } else {
                        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
                        $updateStmt = $this->db->prepare("UPDATE users SET password_hash = :password_hash, updated_at = NOW() WHERE user_id = :user_id");
                        if ($updateStmt->execute([':password_hash' => $newHash, ':user_id' => $userId])) {
                            $success = 'Password updated successfully.';
                            $this->logActivity($chairId, $departmentId, 'Change Password', 'Changed account password', 'users', $userId);
                        } else {
                            $error = 'Failed to update password. Please try again.';
                        }
                    }
                }
            }

            // Handle email update
            if (!empty($newEmail)) {
                if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Invalid email format.';
                } else {
                    $checkStmt = $this->db->prepare("SELECT user_id FROM users WHERE email = :email AND user_id != :user_id");
                    $checkStmt->execute([':email' => $newEmail, ':user_id' => $userId]);
                    if ($checkStmt->fetch()) {
                        $error = 'Email is already in use.';
                    } else {
                        $updateEmailStmt = $this->db->prepare("UPDATE users SET email = :email, updated_at = NOW() WHERE user_id = :user_id");
                        if ($updateEmailStmt->execute([':email' => $newEmail, ':user_id' => $userId])) {
                            $success = $success ? "$success Email updated successfully." : 'Email updated successfully.';
                            $this->logActivity($chairId, $departmentId, 'Change Email', 'Changed account email', 'users', $userId);
                        } else {
                            $error = 'Failed to update email. Please try again.';
                        }
                    }
                }
            }

            if (!empty($error)) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => $error];
            } elseif (!empty($success)) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => $success];
            }
            header('Location: /chair/settings');
            exit;
        }

        require_once __DIR__ . '/../views/chair/settings.php';
    }
}
