<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../services/EmailService.php';
require_once __DIR__ . '/../services/SchedulingService.php';

// Start session only if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class ChairController
{
    public $db;
    private $authService;
    private $baseUrl;
    private $emailService;
    private $schedulingService;

    public function __construct()
    {
        error_log("ChairController instantiated");
        $this->db = (new Database())->connect();
        if ($this->db === null) {
            error_log("Failed to connect to the database in ChairController");
            die("Database connection failed. Please try again later.");
        }
        $this->restrictToChair();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->authService = new AuthService($this->db);

        $this->emailService = new EmailService();

        $this->schedulingService = new SchedulingService($this->db);
    }

    public function getDb()
    {
        return $this->db;
    }

    /**
     * Restrict access to Program Chair (role_id = 5)
     */
    private function restrictToChair()
    {
        error_log("restrictToChair: Checking session - user_id: " . ($_SESSION['user_id'] ?? 'none') . ", role_id: " . ($_SESSION['role_id'] ?? 'none'));
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 5) {
            error_log("restrictToChair: Redirecting to login due to unauthorized access");
            header('Location: /login?error=Unauthorized access');
            exit;
        }
    }

    /**
     * Make HTTP request to SchedulingService
     */
    private function callSchedulingService($method, $endpoint, $data = [])
    {
        $url = "http://localhost/api/scheduling.php?endpoint=" . urlencode($endpoint);
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        if ($method === 'POST' || $method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'GET') {
            if (!empty($data)) {
                $url .= '&' . http_build_query($data);
                curl_setopt($ch, CURLOPT_URL, $url);
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['code' => $httpCode, 'data' => json_decode($response, true)];
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

    /**
     * Display Chair dashboard
     */
    public function dashboard()
    {
        try {
            $chairId = $_SESSION['user_id'];
            error_log("dashboard: Starting dashboard method for user_id: $chairId");

            // Get department for the Chair
            $departmentId = $this->getChairDepartment($chairId);

            if (!$departmentId) {
                error_log("dashboard: No department found for chairId: $chairId");
                $error = "No department assigned to this chair. Please contact the administrator.";
                $viewPath = __DIR__ . '/../views/chair/dashboard.php';
                error_log("dashboard: Looking for view at: $viewPath");
                error_log("dashboard: File exists: " . (file_exists($viewPath) ? 'YES' : 'NO'));
                if (!file_exists($viewPath)) {
                    error_log("dashboard: View file not found at: $viewPath");
                    http_response_code(404);
                    echo "404 Not Found: Dashboard view missing";
                    exit;
                }
                require_once $viewPath;
                return;
            }

            error_log("dashboard: Department fetched - department_id: $departmentId");

            // Get department name
            $deptStmt = $this->db->prepare("SELECT department_name FROM departments WHERE department_id = :department_id");
            $deptStmt->execute([':department_id' => $departmentId]);
            $departmentName = $deptStmt->fetchColumn();

            // Get current semester
            $currentSemesterStmt = $this->db->query("SELECT semester_name, academic_year FROM semesters WHERE is_current = 1 LIMIT 1");
            $currentSemester = $currentSemesterStmt->fetch(PDO::FETCH_ASSOC);
            $semesterInfo = $currentSemester ? "{$currentSemester['semester_name']} Semester A.Y {$currentSemester['academic_year']}" : '2nd Semester 2024-2025';

            // Get counts for dashboard
            $schedulesCount = $this->db->query("SELECT COUNT(*) FROM schedules s 
                                                JOIN courses c ON s.course_id = c.course_id 
                                                WHERE c.department_id = " . (int)$departmentId)->fetchColumn();
            $facultyCount = $this->db->query("SELECT COUNT(*) FROM faculty f 
                                            JOIN users u ON f.user_id = u.user_id 
                                            WHERE u.department_id = " . (int)$departmentId)->fetchColumn();
            $coursesCount = $this->db->query("SELECT COUNT(*) FROM courses WHERE department_id = " . (int)$departmentId)->fetchColumn();

            error_log("dashboard: Counts - schedules: $schedulesCount, faculty: $facultyCount, courses: $coursesCount");

            // Get curricula with active status
            $curriculaStmt = $this->db->prepare("
                SELECT c.curriculum_id, c.curriculum_name, c.total_units, c.status, p.program_name 
                FROM curricula c 
                JOIN programs p ON c.department_id = p.department_id 
                WHERE c.department_id = :department_id
                ORDER BY c.curriculum_name
            ");
            $curriculaStmt->execute([':department_id' => $departmentId]);
            $curricula = $curriculaStmt->fetchAll(PDO::FETCH_ASSOC);

            error_log("dashboard: Fetched " . count($curricula) . " curricula");

            // Get recent schedules for current semester only
            $recentSchedulesStmt = $this->db->prepare("
                SELECT s.schedule_id, c.course_name, c.course_code, CONCAT(u.first_name, ' ', u.last_name) AS faculty_name, 
                    r.room_name, s.day_of_week, s.start_time, s.end_time, s.schedule_type, sec.section_name,
                    sem.semester_name, sem.academic_year
                FROM schedules s
                JOIN courses c ON s.course_id = c.course_id
                JOIN faculty f ON s.faculty_id = f.faculty_id
                JOIN users u ON f.user_id = u.user_id
                LEFT JOIN sections sec ON s.section_id = sec.section_id
                LEFT JOIN classrooms r ON s.room_id = r.room_id
                LEFT JOIN semesters sem ON s.semester_id = sem.semester_id
                WHERE c.department_id = :department_id 
                    AND sem.is_current = 1
                ORDER BY s.created_at DESC
                LIMIT 5
            ");
            $recentSchedulesStmt->execute([':department_id' => $departmentId]);
            $recentSchedules = $recentSchedulesStmt->fetchAll(PDO::FETCH_ASSOC);
            $schedules = $recentSchedules;

            error_log("dashboard: Fetched " . count($recentSchedules) . " recent schedules");

            // Get schedule distribution data for chart
            $scheduleDistStmt = $this->db->prepare("
                SELECT s.day_of_week, COUNT(*) as count 
                FROM schedules s 
                JOIN courses c ON s.course_id = c.course_id 
                WHERE c.department_id = :department_id 
                GROUP BY s.day_of_week
            ");
            $scheduleDistStmt->execute([':department_id' => $departmentId]);
            $scheduleDistData = $scheduleDistStmt->fetchAll(PDO::FETCH_ASSOC);

            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $scheduleDist = array_fill_keys($days, 0);
            foreach ($scheduleDistData as $row) {
                $scheduleDist[$row['day_of_week']] = (int)$row['count'];
            }
            $scheduleDistJson = json_encode(array_values($scheduleDist));

            error_log("dashboard: Schedule distribution - " . $scheduleDistJson);

            // Get faculty workload data for chart
            $workloadStmt = $this->db->prepare("
                SELECT CONCAT(u.first_name, ' ', u.last_name) AS faculty_name, COUNT(s.schedule_id) as course_count
                FROM faculty f
                JOIN users u ON f.user_id = u.user_id
                LEFT JOIN schedules s ON f.faculty_id = s.faculty_id
                JOIN courses c ON s.course_id = c.course_id
                WHERE u.department_id = :department_id
                GROUP BY f.faculty_id, u.first_name, u.last_name
                ORDER BY course_count DESC
                LIMIT 5
            ");
            $workloadStmt->execute([':department_id' => $departmentId]);
            $workloadData = $workloadStmt->fetchAll(PDO::FETCH_ASSOC);

            $workloadLabels = array_column($workloadData, 'faculty_name');
            $workloadCounts = array_column($workloadData, 'course_count');
            $workloadLabelsJson = json_encode($workloadLabels);
            $workloadCountsJson = json_encode($workloadCounts);

            error_log("dashboard: Faculty workload - labels: " . $workloadLabelsJson . ", counts: " . $workloadCountsJson);

            $viewPath = __DIR__ . '/../views/chair/dashboard.php';
            error_log("dashboard: Looking for view at: $viewPath");
            error_log("dashboard: File exists: " . (file_exists($viewPath) ? 'YES' : 'NO'));

            if (!file_exists($viewPath)) {
                error_log("dashboard: View file not found at: $viewPath");
                http_response_code(404);
                echo "404 Not Found: Dashboard view missing";
                exit;
            }

            require_once $viewPath;
        } catch (Exception $e) {
            error_log("dashboard: Full error: " . $e->getMessage());
            http_response_code(500);
            echo "Error loading dashboard: " . htmlspecialchars($e->getMessage());
            exit;
        }
    }

    public function mySchedule()
    {
        try {
            $chairId = $_SESSION['user_id'];
            error_log("mySchedule: Starting mySchedule method for user_id: $chairId");

            // Fetch faculty ID and name with join to users table
            $facultyStmt = $this->db->prepare("
            SELECT f.faculty_id, CONCAT(u.title, ' ', u.first_name, ' ', u.middle_name, ' ', u.last_name, ' ', u.suffix) AS faculty_name 
            FROM faculty f 
            JOIN users u ON f.user_id = u.user_id 
            WHERE u.user_id = :user_id
            ");
            $facultyStmt->execute([':user_id' => $chairId]);
            $faculty = $facultyStmt->fetch(PDO::FETCH_ASSOC);

            if (!$faculty) {
                $error = "No faculty profile found for this user.";
                require_once __DIR__ . '/../views/chair/my_schedule.php';
                return;
            }
            $facultyId = $faculty['faculty_id'];
            $facultyName = $faculty['faculty_name'];

            // fetch faculty position and employment stautus
            $positionStmt = $this->db->prepare("SELECT academic_rank FROM faculty WHERE faculty_id = :faculty_id");
            $positionStmt->execute([':faculty_id' => $facultyId]);
            $facultyPosition = $positionStmt->fetch(PDO::FETCH_ASSOC) ?: 'Not Specified';

            // Get department and college details
            $deptStmt = $this->db->prepare("
            SELECT d.department_name, c.college_name 
            FROM program_chairs pc 
            JOIN faculty f ON pc.faculty_id = f.faculty_id
            JOIN programs p ON pc.program_id = p.program_id 
            JOIN departments d ON p.department_id = d.department_id 
            JOIN colleges c ON d.college_id = c.college_id 
            WHERE f.user_id = :user_id AND pc.is_current = 1
            ");
            $deptStmt->execute([':user_id' => $chairId]);
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

            $schedulesStmt = $this->db->prepare("
            SELECT s.schedule_id, c.course_code, c.course_name, r.room_name, s.day_of_week, 
                   s.start_time, s.end_time, s.schedule_type, COALESCE(sec.section_name, 'N/A') AS section_name,
                   TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time) / 60 AS duration_hours
            FROM schedules s
            LEFT JOIN courses c ON s.course_id = c.course_id
            LEFT JOIN sections sec ON s.section_id = sec.section_id
            LEFT JOIN classrooms r ON s.room_id = r.room_id
            WHERE s.faculty_id = :faculty_id AND s.semester_id = :semester_id
            ORDER BY FIELD(s.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), s.start_time
            ");
            $schedulesStmt->execute([':faculty_id' => $facultyId, ':semester_id' => $semesterId]);
            $schedules = $schedulesStmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("mySchedule: Fetched " . count($schedules) . " schedules for faculty_id $facultyId in semester $semesterId");

            $showAllSchedules = false;
            if (empty($schedules)) {
                error_log("mySchedule: No schedules found for current semester, trying to fetch all schedules");
                $schedulesStmt = $this->db->prepare("
                SELECT s.schedule_id, c.course_code, c.course_name, r.room_name, s.day_of_week, 
                       s.start_time, s.end_time, s.schedule_type, COALESCE(sec.section_name, 'N/A') AS section_name,
                       TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time) / 60 AS duration_hours
                FROM schedules s
                LEFT JOIN courses c ON s.course_id = c.course_id
                LEFT JOIN sections sec ON s.section_id = sec.section_id
                LEFT JOIN classrooms r ON s.room_id = r.room_id
                WHERE s.faculty_id = :faculty_id
                ORDER BY FIELD(s.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), s.start_time
            ");
                $schedulesStmt->execute([':faculty_id' => $facultyId]);
                $schedules = $schedulesStmt->fetchAll(PDO::FETCH_ASSOC);
                $showAllSchedules = true;
                error_log("mySchedule: Fetched " . count($schedules) . " total schedules for faculty_id $facultyId");
            }

            if (empty($schedules)) {
                error_log("mySchedule: No schedules found after fallback. Checking raw data...");
                $debugStmt = $this->db->prepare("SELECT * FROM schedules WHERE faculty_id = :faculty_id");
                $debugStmt->execute([':faculty_id' => $facultyId]);
                $debugSchedules = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("mySchedule: Debug - Found " . count($debugSchedules) . " raw schedules for faculty_id $facultyId");
            }

            $totalHours = 0;
            foreach ($schedules as $schedule) {
                $totalHours += $schedule['duration_hours'];
            }
            error_log("mySchedule: Total hours calculated: $totalHours");

            require_once __DIR__ . '/../views/chair/my_schedule.php';
        } catch (Exception $e) {
            error_log("mySchedule: Full error: " . $e->getMessage());
            http_response_code(500);
            echo "Error loading schedule: " . htmlspecialchars($e->getMessage());
            exit;
        }
        require_once __DIR__ . '/../views/chair/my_schedule.php';
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

    private function validateCurriculumCourse($curriculumId, $courseId)
    {
        $stmt = $this->db->prepare("SELECT 1 FROM curriculum_courses 
                                  WHERE curriculum_id = :curriculum_id 
                                  AND course_id = :course_id");
        $stmt->execute([':curriculum_id' => $curriculumId, ':course_id' => $courseId]);
        return $stmt->fetchColumn();
    }

    private function getCurricula($departmentId)
    {
        $stmt = $this->db->prepare("SELECT curriculum_id, curriculum_name FROM curricula WHERE department_id = :dept_id AND status = 'Active'");
        $stmt->execute([':dept_id' => $departmentId]);
        return $stmt->fetchAll();
    }

    private function getClassrooms($departmentId)
    {
        $stmt = $this->db->prepare("SELECT room_id, room_name FROM classrooms WHERE (department_id = :dept_id OR shared = 1) AND availability = 'available'");
        $stmt->execute([':dept_id' => $departmentId]);
        return $stmt->fetchAll();
    }

    private function getFaculty($departmentId, $collegeId)
    {
        try {
            // Handle case where collegeId might be an array
            if (is_array($collegeId)) {
                error_log("Warning: collegeId is an array: " . json_encode($collegeId));
                $collegeId = $collegeId[0]; // Use first element if array
            }

            $stmt = $this->db->prepare("
            SELECT CONCAT(u.title, ' ',u.first_name, ' ', u.last_name) AS name, f.faculty_id, u.user_id, u.college_id, fd.department_id
            FROM faculty f
            JOIN users u ON f.user_id = u.user_id
            JOIN faculty_departments fd ON f.faculty_id = fd.faculty_id
            WHERE fd.department_id = :department_id
            AND u.college_id = :college_id
            AND u.is_active = 1
            ");
            $stmt->execute([':department_id' => $departmentId, ':college_id' => $collegeId]);
            $faculty = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($faculty === false || empty($faculty)) {
                error_log("No faculty found for department $departmentId, college $collegeId");
                return []; // Return empty array if no faculty
            }

            error_log("getFaculty for department $departmentId, college $collegeId: found " . count($faculty) . " faculty");
            return $faculty;
        } catch (PDOException $e) {
            error_log("getFaculty failed for department $departmentId, college $collegeId: " . $e->getMessage());
            return [];
        } catch (Exception $e) {
            error_log("Unexpected error in getFaculty for department $departmentId, college $collegeId: " . $e->getMessage());
            return [];
        }
    }

    private function getSections($departmentId, $semesterId)
    {
        error_log("getSections: Fetching for department $departmentId, semester_id $semesterId");
        try {
            // Use provided $semesterId; fallback to current semester only if not provided
            $effectiveSemesterId = $semesterId;
            if (!$effectiveSemesterId) {
                $currentSemester = $this->getCurrentSemester();
                $effectiveSemesterId = $currentSemester['semester_id'] ?? null;
                error_log("getSections: No semester_id provided, using current semester: " . $effectiveSemesterId);
                if (!$effectiveSemesterId) {
                    error_log("getSections: No valid semester_id found");
                    return [];
                }
            }

            $stmt = $this->db->prepare("
            SELECT s.section_id, s.section_name, s.year_level, s.semester_id, 
                   s.current_students, s.max_students, s.semester, s.academic_year
            FROM sections s
            WHERE s.department_id = :department_id 
            AND s.semester_id = :semester_id
            AND s.is_active = 1
            ORDER BY FIELD(s.year_level, '1st Year', '2nd Year', '3rd Year', '4th Year'), s.section_name
        ");
            $stmt->execute([
                ':department_id' => $departmentId,
                ':semester_id' => $effectiveSemesterId
            ]);
            $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("getSections: Found " . count($sections) . " sections: " . json_encode($sections));
            return $sections;
        } catch (PDOException $e) {
            error_log("getSections: PDO Error - " . $e->getMessage());
            return [];
        }
    }

    private function getCourses($departmentId)
    {
        $stmt = $this->db->prepare("
        SELECT course_id, course_code, course_name, units, lab_units, lecture_units, lab_hours, lecture_hours, lab_hours, COALESCE(subject_type, 'Professional Course') as subject_type 
        FROM courses 
        WHERE department_id = :department_id
        ");
        $stmt->execute([':department_id' => $departmentId]);
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Fetched courses at " . date('Y-m-d H:i:s') . ": " . print_r($courses, true));
        return $courses;
    }

    private function getCurriculumCourses($curriculumId)
    {
        if (!$curriculumId) {
            error_log("getCurriculumCourses: No curriculum_id provided");
            return [];
        }

        try {
            $currentSemester = $this->getCurrentSemester();
            $semesterName = $currentSemester['semester_name'] ?? '';

            $stmt = $this->db->prepare("    
            SELECT c.course_id, c.course_code, c.units, c.lecture_units, c.lab_units, 
                   c.lab_hours, c.lecture_hours, c.course_name, cc.subject_type, 
                   cc.year_level AS curriculum_year, cc.semester AS curriculum_semester 
            FROM curriculum_courses cc 
            JOIN courses c ON cc.course_id = c.course_id 
            JOIN curricula cr ON cc.curriculum_id = cr.curriculum_id 
            WHERE cc.curriculum_id = :curriculum_id 
            AND cc.semester = :semester 
            AND cr.status = 'Active' 
            ORDER BY FIELD(cc.year_level, '1st Year', '2nd Year', '3rd Year', '4th Year'), 
                     FIELD(cc.semester, '1st', '2nd', 'Mid Year'), c.course_code
        ");
            $stmt->execute([
                ':curriculum_id' => $curriculumId,
                ':semester' => $semesterName
            ]);
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $courses;
        } catch (PDOException $e) {
            error_log("getCurriculumCourses: PDO Error - " . $e->getMessage());
            return [];
        }
    }

    public function deleteAllSchedules()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['confirm']) || $_POST['confirm'] !== 'true') {
            echo json_encode(['success' => false, 'message' => 'Invalid request or confirmation missing.']);
            exit;
        }

        $chairId = $_SESSION['user_id'] ?? null;
        $departmentId = $this->getChairDepartment($chairId);

        if (!$departmentId) {
            echo json_encode(['success' => false, 'message' => 'Could not determine department.']);
            exit;
        }

        $transactionActive = false;
        try {
            // Debug: Check if PDO connection is active
            if (!$this->db || !$this->db->getAttribute(PDO::ATTR_CONNECTION_STATUS)) {
                throw new Exception('Database connection is not active.');
            }

            // Start transaction
            $this->db->beginTransaction();
            $transactionActive = true;

            // Delete all schedules created today for the current department
            $stmt = $this->db->prepare("DELETE FROM schedules WHERE department_id = :department_id AND DATE(created_at) = CURDATE()");
            $stmt->execute([':department_id' => $departmentId]);
            $deletedCount = $stmt->rowCount();

            // Commit transaction
            $this->db->commit();
            $transactionActive = false;

            // Reset auto-increment outside transaction
            $this->db->exec("ALTER TABLE schedules AUTO_INCREMENT = 1");

            echo json_encode([
                'success' => true,
                'message' => 'All schedules created today for department ' . $departmentId . ' deleted successfully.',
                'deleted_count' => $deletedCount
            ]);
        } catch (Exception $e) {
            // Roll back only if a transaction was started
            if ($transactionActive && $this->db->inTransaction()) {
                try {
                    $this->db->rollBack();
                } catch (PDOException $rollbackException) {
                    error_log('Failed to rollback transaction: ' . $rollbackException->getMessage());
                }
            }
            echo json_encode(['success' => false, 'message' => 'Error deleting schedules: ' . $e->getMessage()]);
        }
        exit;
    }

    private function getChairCollege($userId)
    {
        try {
            $stmt = $this->db->prepare("
            SELECT college_id, department_id
            FROM users
            WHERE user_id = :user_id AND is_active = 1
        ");
            $stmt->execute([':user_id' => $userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && !empty($result['college_id'])) {
                return [
                    'college_id' => $result['college_id'],
                    'college_name' => 'Unknown College' // Placeholder if no colleges table
                ];
            } else {
                error_log("No college_id found for user_id $userId");
                return null;
            }
        } catch (PDOException $e) {
            error_log("Database error in getChairCollege for user_id $userId: " . $e->getMessage());
            return null;
        } catch (Exception $e) {
            error_log("Unexpected error in getChairCollege for user_id $userId: " . $e->getMessage());
            return null;
        }
    }

    private function loadCommonData($departmentId, $currentSemester, $collegeId)
    {
        return [
            'curricula' => $this->getCurricula($departmentId),
            'classrooms' => $this->getClassrooms($departmentId),
            'faculty' => $this->getFaculty($departmentId, $collegeId),
            'sections' => $this->getSections($departmentId, $currentSemester['semester_id']),
            'curriculumCourses' => $this->getCurriculumCourses($currentSemester['curriculum_id']), // Default to curriculum_id 6 if needed
            'semester' => $currentSemester
        ];
    }

    public function manageSchedule()
    {
        $chairId = $_SESSION['user_id'] ?? null;
        $departmentId = $this->getChairDepartment($chairId);
        $currentSemester = $this->getCurrentSemester();
        $_SESSION['current_semester'] = $currentSemester;
        $activeTab = $_GET['tab'] ?? 'generate';
        $error = $success = null;
        $schedules = $this->loadSchedules($departmentId, $currentSemester);
        $collegeId = $this->getChairCollege($chairId)['college_id'] ?? null;

        if ($departmentId) {
            if (!isset($_SESSION['schedule_cache'][$departmentId])) {
                $_SESSION['schedule_cache'][$departmentId] = $this->loadCommonData($departmentId, $currentSemester, $collegeId);
                error_log("manageSchedule: Cache initialized for dept $departmentId: " . json_encode(array_keys($_SESSION['schedule_cache'][$departmentId])));
            }

            $cachedData = $_SESSION['schedule_cache'][$departmentId];
            $curricula = $cachedData['curricula'];
            $classrooms = $cachedData['classrooms'];
            $faculty = $cachedData['faculty'];
            $sections = $cachedData['sections'];
            $curriculumCourses = [];

            // Only fetch courses if curriculum_id is explicitly provided
            $selectedCurriculumId = $_POST['curriculum_id'] ?? $_GET['curriculum_id'] ?? null;
            if ($selectedCurriculumId) {
                $curriculumCourses = $this->getCurriculumCourses($selectedCurriculumId);
            }

            $jsData = [
                'departmentId' => $departmentId,
                'currentSemester' => $currentSemester,
                'sectionsData' => $this->getSections($departmentId, $currentSemester['semester_id']),
                'currentAcademicYear' => $currentSemester['academic_year'] ?? '',
                'faculty' => $this->getFaculty($departmentId, $collegeId),
                'classrooms' => $this->getClassrooms($departmentId),
                'curricula' => $curricula ?? [],
                'curriculumCourses' => $curriculumCourses,
                'schedules' => $schedules
            ];

            error_log("manageSchedule: jsData.sectionsData count: " . count($jsData['sectionsData']));
            error_log("manageSchedule: jsData.curriculumCourses count: " . count($jsData['curriculumCourses']));
        } else {
            $jsData = [
                'departmentId' => $departmentId,
                'currentSemester' => $currentSemester,
                'sectionsData' => [],
                'currentAcademicYear' => '',
                'faculty' => [],
                'classrooms' => [],
                'curricula' => [],
                'curriculumCourses' => [],
                'schedules' => []
            ];
            $error = "No department assigned to chair.";
        }

        require_once __DIR__ . '/../views/chair/schedule_management.php';
    }

    public function generateSchedulesAjax()
    {
        header('Content-Type: application/json');
        error_log("generateSchedulesAjax: Request received at " . date('Y-m-d H:i:s'));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['curriculum_id'])) {
            error_log("generateSchedulesAjax: Invalid request - Method: {$_SERVER['REQUEST_METHOD']}, curriculum_id: " . ($_POST['curriculum_id'] ?? 'Missing'));
            echo json_encode(['success' => false, 'message' => 'Invalid request method or missing parameters.']);
            exit;
        }

        $chairId = $_SESSION['user_id'] ?? null;
        $departmentId = $this->getChairDepartment($chairId);
        $currentSemester = $this->getCurrentSemester();
        $collegeData = $this->getChairCollege($chairId);

        error_log("generateSchedulesAjax: Chair ID: $chairId, Department ID: $departmentId, Semester: " . json_encode($currentSemester));

        if (!$chairId || !$currentSemester) {
            error_log("generateSchedulesAjax: Missing chairId or currentSemester");
            echo json_encode(['success' => false, 'message' => 'Could not determine user or current semester.']);
            exit;
        }

        if (!$departmentId) {
            error_log("generateSchedulesAjax: No department found for chair $chairId");
            echo json_encode(['success' => false, 'message' => 'Could not determine department for chair.']);
            exit;
        }

        if (!$collegeData || !$collegeData['college_id']) {
            error_log("generateSchedulesAjax: No college data for chair $chairId");
            echo json_encode(['success' => false, 'message' => 'Could not determine college for chair.']);
            exit;
        }
        $collegeId = $collegeData['college_id'];

        try {
            $cachedData = $_SESSION['schedule_cache'][$departmentId] ?? $this->loadCommonData($departmentId, $currentSemester, $collegeId);
            error_log("generateSchedulesAjax: Cached data loaded: " . json_encode(array_keys($cachedData)));

            $curriculumId = $_POST['curriculum_id'];
            error_log("generateSchedulesAjax: Processing curriculum ID: $curriculumId");

            $yearLevels = $_POST['year_levels'] ?? [];
            if (!is_array($yearLevels)) {
                $yearLevels = array_map('trim', explode(',', $yearLevels));
            }
            $yearLevels = array_filter($yearLevels);
            if (empty($yearLevels)) {
                $yearLevels = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
                error_log("generateSchedulesAjax: No year levels provided, using default: " . implode(', ', $yearLevels));
            } else {
                error_log("generateSchedulesAjax: Year levels provided: " . implode(', ', $yearLevels));
            }

            $classrooms = $cachedData['classrooms'];
            $faculty = $cachedData['faculty'];
            $sections = $this->getSections($departmentId, $currentSemester['semester_id']);
            error_log("generateSchedulesAjax: Sections count: " . count($sections));
            error_log("generateSchedulesAjax: Classrooms count: " . count($classrooms) . ", Faculty count: " . count($faculty));

            $schedules = $this->generateSchedules($curriculumId, $yearLevels, $collegeId, $currentSemester, $classrooms, $faculty, $departmentId);
            $this->removeDuplicateSchedules($departmentId, $currentSemester);

            $consolidatedSchedules = $this->getConsolidatedSchedules($departmentId, $currentSemester);
            error_log("generateSchedulesAjax: Generated " . count($consolidatedSchedules) . " schedules");

            $allCourseCodes = array_column($this->getCurriculumCourses($curriculumId), 'course_code');
            $assignedCourseCodes = array_unique(array_column($consolidatedSchedules, 'course_code'));
            $unassigned = !empty(array_diff($allCourseCodes, $assignedCourseCodes));

            echo json_encode([
                'success' => true,
                'schedules' => $consolidatedSchedules,
                'message' => "Schedules generated: " . count($consolidatedSchedules) . " unique courses",
                'unassigned' => $unassigned
            ]);
            exit;
        } catch (Exception $e) {
            error_log("generateSchedulesAjax: Exception - " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
            exit;
        }
    }

    private function generateSchedules($curriculumId, $yearLevels, $collegeId, $currentSemester, $classrooms, $faculty, $departmentId)
    {
        $schedules = [];
        error_log("generateSchedules: Started for curriculum $curriculumId, department $departmentId, semester " . $currentSemester['semester_name']);

        $this->db->beginTransaction();
        try {
            $courses = $this->getCurriculumCourses($curriculumId);
            error_log("generateSchedules: Fetched " . count($courses) . " courses for curriculum $curriculumId");

            $sections = $this->getSections($departmentId, $currentSemester['semester_name'], $currentSemester['academic_year']);
            error_log("generateSchedules: Fetched " . count($sections) . " sections");

            $matchingSections = array_filter($sections, fn($s) => isset($s['year_level']) && in_array($s['year_level'], $yearLevels));
            if (empty($matchingSections)) {
                $matchingSections = array_filter($sections, fn($s) => isset($s['year_level']) && in_array($s['year_level'], $yearLevels));
                error_log("generateSchedules: No exact curriculum match, using all sections for year levels: " . implode(', ', $yearLevels));
            }
            error_log("generateSchedules: Found " . count($matchingSections) . " matching sections");

            $relevantCourses = array_filter(
                $courses,
                fn($c) => $c['curriculum_semester'] === $currentSemester['semester_name'] && in_array($c['curriculum_year'], $yearLevels)
            );
            $relevantCourses = array_values($relevantCourses);
            error_log("generateSchedules: Found " . count($relevantCourses) . " relevant courses");

            if (empty($matchingSections) || empty($relevantCourses)) {
                error_log("generateSchedules: No sections or courses found for curriculum $curriculumId, semester {$currentSemester['semester_name']}");
                return $schedules;
            }

            $dayPatterns = [
                'MWF' => ['Monday', 'Wednesday', 'Friday'],
                'TTH' => ['Tuesday', 'Thursday'],
                'SAT' => ['Saturday']
            ];

            $flexibleTimeSlots = $this->generateFlexibleTimeSlots();
            error_log("generateSchedules: Available time slots: " . print_r($flexibleTimeSlots, true));
            $unassignedCourses = $relevantCourses;

            $facultySpecializations = $this->getFacultySpecializations($departmentId, $collegeId);
            error_log("generateSchedules: Faculty specializations count: " . count($facultySpecializations));

            $facultyAssignments = [];
            $roomAssignments = [];
            $sectionScheduleTracker = [];
            $onlineSlotTracker = [];
            $usedTimeSlots = [];
            $scheduledCourses = [];

            foreach ($matchingSections as $section) {
                $sectionScheduleTracker[$section['section_id']] = [];
            }

            $maxIterations = 10;
            $iteration = 0;
            $courseIndex = 0;

            while (!empty($unassignedCourses) && $iteration < $maxIterations) {
                $iteration++;
                error_log("generateSchedules: Iteration $iteration, remaining courses: " . count($unassignedCourses));
                $unassignedInThisIteration = [];

                $professionalCourses = array_filter($unassignedCourses, fn($c) => ($this->getCourseDetails($c['course_id'])['subject_type'] ?? 'General Education') === 'Professional Course');
                $generalCourses = array_filter($unassignedCourses, fn($c) => ($this->getCourseDetails($c['course_id'])['subject_type'] ?? 'General Education') !== 'Professional Course');
                $coursesToProcess = array_merge(array_values($professionalCourses), array_values($generalCourses));

                foreach ($coursesToProcess as $course) {
                    $courseDetails = $this->getCourseDetails($course['course_id']);
                    if (!$courseDetails) {
                        error_log("generateSchedules: Skipping course with invalid details for course_id {$course['course_id']}");
                        $unassignedInThisIteration[] = $course;
                        continue;
                    }

                    $lectureHours = $courseDetails['lecture_hours'] ?? 0;
                    $labHours = $courseDetails['lab_hours'] ?? 0;
                    $units = $courseDetails['units'] ?? 3;
                    $subjectType = $courseDetails['subject_type'] ?? 'General Education';
                    $hasLab = $labHours > 0;
                    $hasLecture = $lectureHours > 0;

                    error_log("generateSchedules: Processing {$course['course_code']} - Lecture: $lectureHours, Lab: $labHours, Units: $units, Type: $subjectType");

                    $sectionsForCourse = array_filter($matchingSections, fn($s) => $s['year_level'] === $course['curriculum_year']);
                    $assignedThisCourse = false;

                    $isNSTPCourse = $this->isNSTPCourse($course['course_code']);
                    $pattern = $isNSTPCourse ? 'SAT' : (($courseIndex % 2 === 0) ? 'MWF' : 'TTH');
                    $targetDays = $dayPatterns[$pattern];

                    if ($isNSTPCourse && !$this->areRoomsAvailableOnDays($departmentId, $sectionsForCourse, $targetDays, $flexibleTimeSlots, $roomAssignments, $schedules)) {
                        $alternativeDays = array_diff(array_merge($dayPatterns['MWF'], $dayPatterns['TTH']), $targetDays);
                        $targetDays = $alternativeDays ? [$alternativeDays[0]] : $targetDays;
                        error_log("generateSchedules: Switching NSTP {$course['course_code']} to alternative day: " . $targetDays[0]);
                    }

                    $durationData = $this->calculateCourseDuration($courseDetails);
                    error_log("generateSchedules: Duration data for {$courseDetails['course_code']}: " . print_r($durationData, true));
                    $lectureDuration = $hasLecture ? ($lectureHours > 0 ? $lectureHours / count($targetDays) : $units / count($targetDays)) : 0;
                    $labDuration = $hasLab ? ($labHours > 0 ? $labHours / count($targetDays) : $units / count($targetDays)) : 0;

                    $filteredTimeSlots = array_filter(
                        $flexibleTimeSlots,
                        fn($slot) => ($hasLecture && abs($slot[2] - $lectureDuration) <= 0.5) ||
                            ($hasLab && abs($slot[2] - $labDuration) <= 0.5) ||
                            (!$hasLecture && !$hasLab && abs($slot[2] - ($units / count($targetDays))) <= 0.5)
                    );
                    $filteredTimeSlots = array_values($filteredTimeSlots);
                    error_log("generateSchedules: Filtered time slots for {$course['course_code']}: " . print_r($filteredTimeSlots, true));

                    if (empty($filteredTimeSlots)) {
                        error_log("generateSchedules: No suitable time slots for {$course['course_code']} with durations Lecture: $lectureDuration, Lab: $labDuration, Units: $units");
                        $unassignedInThisIteration[] = $course;
                        continue;
                    }

                    usort($filteredTimeSlots, fn($a, $b) => $this->isTimeSlotUsed($a[0], $a[1], $targetDays, $usedTimeSlots) <=> $this->isTimeSlotUsed($b[0], $b[1], $targetDays, $usedTimeSlots));

                    foreach ($sectionsForCourse as $section) {
                        $key = $course['course_id'] . '-' . $section['section_id'];
                        if (isset($scheduledCourses[$key])) {
                            error_log("generateSchedules: Skipping already scheduled course-section pair: {$course['course_code']}-{$section['section_name']}");
                            continue;
                        }

                        $forceF2F = in_array($subjectType, ['Professional Course', 'Major Course']);

                        $timeSlot = reset($filteredTimeSlots);
                        $startTime = $timeSlot[0];
                        $endTime = $timeSlot[1];

                        $facultyId = $this->findBestFaculty(
                            $facultySpecializations,
                            $course['course_id'],
                            $targetDays,
                            $startTime,
                            $endTime,
                            $collegeId,
                            $departmentId,
                            $schedules,
                            $facultyAssignments,
                            $courseDetails['course_code'],
                            $section['section_id']
                        );
                        if (!$facultyId) {
                            error_log("generateSchedules: No available faculty for {$courseDetails['course_code']} (section {$section['section_name']})");
                            continue;
                        }

                        if ($hasLecture && $hasLab) {
                            $lectureResult = $this->scheduleCourseSectionsInDifferentTimeSlots(
                                $course,
                                [$section],
                                $targetDays,
                                $filteredTimeSlots,
                                $sectionScheduleTracker,
                                $facultySpecializations,
                                $facultyAssignments,
                                $currentSemester,
                                $departmentId,
                                $schedules,
                                $onlineSlotTracker,
                                $roomAssignments,
                                $usedTimeSlots,
                                $subjectType,
                                true,
                                false,
                                $forceF2F,
                                'Lecture',
                                $facultyId
                            );
                            $labResult = $this->scheduleCourseSectionsInDifferentTimeSlots(
                                $course,
                                [$section],
                                $targetDays,
                                $filteredTimeSlots,
                                $sectionScheduleTracker,
                                $facultySpecializations,
                                $facultyAssignments,
                                $currentSemester,
                                $departmentId,
                                $schedules,
                                $onlineSlotTracker,
                                $roomAssignments,
                                $usedTimeSlots,
                                $subjectType,
                                false,
                                true,
                                $forceF2F,
                                'Lab',
                                $facultyId
                            );
                            $assignedThisCourse = $lectureResult && $labResult;
                        } else {
                            $result = $this->scheduleCourseSectionsInDifferentTimeSlots(
                                $course,
                                [$section],
                                $targetDays,
                                $filteredTimeSlots,
                                $sectionScheduleTracker,
                                $facultySpecializations,
                                $facultyAssignments,
                                $currentSemester,
                                $departmentId,
                                $schedules,
                                $onlineSlotTracker,
                                $roomAssignments,
                                $usedTimeSlots,
                                $subjectType,
                                $hasLecture,
                                $hasLab,
                                $forceF2F,
                                null,
                                $facultyId
                            );
                            $assignedThisCourse = $result;
                        }

                        if ($assignedThisCourse) {
                            $scheduledCourses[$key] = true;
                            error_log("generateSchedules: ✅ All components for {$course['course_code']} scheduled for section {$section['section_name']} with faculty $facultyId");
                        }
                    }

                    $assignedThisCourse = count($sectionsForCourse) === count(array_filter($sectionsForCourse, fn($s) => isset($scheduledCourses[$course['course_id'] . '-' . $s['section_id']])));
                    if ($assignedThisCourse) {
                        error_log("generateSchedules: ✅ All sections for {$course['course_code']} scheduled");
                    } else {
                        $unassignedInThisIteration[] = $course;
                    }

                    $courseIndex++;
                }

                $unassignedCourses = $unassignedInThisIteration;

                if (count($unassignedCourses) === count($relevantCourses)) {
                    error_log("generateSchedules: No progress in iteration $iteration, breaking loop");
                    break;
                }
            }

            if (!empty($unassignedCourses)) {
                $unassignedDetails = array_map(fn($c) => "Course: {$c['course_code']} for year {$c['curriculum_year']}", $unassignedCourses);
                error_log("generateSchedules: Warning: Unscheduled courses: \n" . implode("\n", $unassignedDetails));
            } else {
                error_log("generateSchedules: Success: All courses scheduled.");
            }

            $this->db->commit();
            error_log("generateSchedules: Transaction committed, returning " . count($schedules) . " schedules");
            return $schedules;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("generateSchedules: Transaction rolled back due to error: " . $e->getMessage());
            return [];
        }
    }

    private function scheduleCourseSectionsInDifferentTimeSlots($course, $sectionsForCourse, $targetDays, $timeSlots, &$sectionScheduleTracker, $facultySpecializations, &$facultyAssignments, $currentSemester, $departmentId, &$schedules, &$onlineSlotTracker, &$roomAssignments, &$usedTimeSlots, $subjectType, $isLecture = false, $isLab = false, $forceF2F = false, $component = null, $facultyId = null)
    {
        $scheduledSections = [];
        $courseDetails = $this->getCourseDetails($course['course_id']);
        $durationData = $this->calculateCourseDuration($courseDetails);
        $durationHours = $durationData['duration_hours'] ?? 0;

        foreach ($sectionsForCourse as $section) {
            $sectionScheduledSuccessfully = false; // Initialize for each section

            foreach ($timeSlots as $timeSlot) {
                list($startTime, $endTime, $slotDuration) = $timeSlot;
                if (abs($slotDuration - $durationHours) > 0.5) {
                    error_log("Skipping time slot $startTime-$endTime for {$courseDetails['course_code']} (section {$section['section_name']}): duration mismatch");
                    continue;
                }

                if (!$this->isScheduleSlotAvailable($section['section_id'], $targetDays, $startTime, $endTime, $sectionScheduleTracker)) {
                    error_log("Section {$section['section_name']} busy at $startTime-$endTime on " . implode(', ', $targetDays));
                    continue;
                }

                $collegeId = $this->getChairCollege($_SESSION['user_id'])['college_id'] ?? null;
                $facultyId = $facultyId ?: $this->findBestFaculty(
                    $facultySpecializations,
                    $course['course_id'],
                    $targetDays,
                    $startTime,
                    $endTime,
                    $collegeId,
                    $departmentId,
                    $schedules,
                    $facultyAssignments,
                    $courseDetails['course_code'],
                    $section['section_id']
                );
                if (!$facultyId) {
                    error_log("No available faculty for {$courseDetails['course_code']} (section {$section['section_name']}) at $startTime-$endTime");
                    continue;
                }

                if (!$this->isFacultyAvailable($facultyId, $targetDays, $startTime, $endTime, $facultyAssignments)) {
                    error_log("Faculty $facultyId busy for {$courseDetails['course_code']} (section {$section['section_name']}) at $startTime-$endTime");
                    continue;
                }

                $forceF2F = $forceF2F || in_array($subjectType, ['Professional Course', 'Major Course']);
                $roomId = null;
                $roomName = 'Online';
                $scheduleType = 'Online';

                if ($forceF2F) {
                    $roomAssignmentDetails = $this->getRoomAssignments($departmentId, $section['max_students'], $targetDays, $startTime, $endTime, $schedules, $forceF2F);
                    $availableRoom = array_filter($roomAssignmentDetails, fn($room) => $room['room_id'] && !$this->hasRoomConflictForAnySection($schedules, $room['room_id'], $targetDays, $startTime, $endTime));
                    if (empty($availableRoom)) {
                        error_log("No F2F room available for {$courseDetails['course_code']} (section {$section['section_name']}) at $startTime-$endTime");
                        continue;
                    }
                    $roomDetail = reset($availableRoom);
                    $roomId = $roomDetail['room_id'];
                    $roomName = $roomDetail['room_name'];
                    $scheduleType = 'F2F';
                }

                $sectionScheduledSuccessfully = true; // Set to true initially
                foreach ($targetDays as $day) {
                    $scheduleData = [
                        'course_id' => $course['course_id'],
                        'section_id' => $section['section_id'],
                        'room_id' => $roomId,
                        'semester_id' => $currentSemester['semester_id'],
                        'faculty_id' => $facultyId,
                        'schedule_type' => $scheduleType,
                        'day_of_week' => $day,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'status' => 'Pending',
                        'is_public' => 1,
                        'course_code' => $courseDetails['course_code'],
                        'course_name' => $courseDetails['course_name'],
                        'faculty_name' => $this->getFaculty($facultyId, $collegeId),
                        'room_name' => $roomName,
                        'section_name' => $section['section_name'],
                        'year_level' => $section['year_level'],
                        'department_id' => $departmentId,
                        'days_pattern' => implode('', array_map(fn($d) => substr($d, 0, 1), $targetDays))
                    ];

                    $response = $this->saveScheduleToDB($scheduleData, $currentSemester);
                    if ($response['code'] !== 200) {
                        error_log("Failed to save schedule for {$courseDetails['course_code']} (section {$section['section_name']}) on $day: " . $response['message']);
                        $sectionScheduledSuccessfully = false;
                        break; // Exit day loop on failure
                    }
                    $schedules[] = array_merge($response['data'], $scheduleData);
                    $this->updateSectionScheduleTracker($sectionScheduleTracker, $section['section_id'], $day, $startTime, $endTime);
                    $facultyAssignments[] = ['faculty_id' => $facultyId, 'days' => [$day], 'start_time' => $startTime, 'end_time' => $endTime, 'course_code' => $courseDetails['course_code']];
                    if ($roomId) {
                        $this->updateRoomAssignments($roomAssignments, $roomId, $day, $startTime, $endTime);
                    } else {
                        $this->updateOnlineSlotTracker($onlineSlotTracker, $day, $startTime, $endTime, $section['max_students']);
                    }
                    $this->updateUsedTimeSlots($usedTimeSlots, $day, $startTime, $endTime);
                }

                if ($sectionScheduledSuccessfully) {
                    $scheduledSections[] = $section['section_id'];
                    error_log("✅ Scheduled {$courseDetails['course_code']} for section {$section['section_name']} at $startTime-$endTime");
                    break; // Move to next section once scheduled
                } else {
                    error_log("❌ Failed to schedule {$courseDetails['course_code']} for section {$section['section_name']}");
                }
            }

            if ($sectionScheduledSuccessfully) {
                $scheduledSections[] = $section['section_id'];
                error_log("✅ Scheduled {$courseDetails['course_code']} for section {$section['section_name']} at $startTime-$endTime");
                break;
            }
        }

        // Move the return outside the loop
        return count($scheduledSections) === count($sectionsForCourse);
    }

    private function isNSTPCourse($courseCode)
    {
        // Pattern matches course codes containing NSTP, CWTS, ROTC, or LTS, with optional suffixes
        // - ^: Start of string
        // - (?:NSTP|CWTS|ROTC|LTS): Non-capturing group for base NSTP terms
        // - \s*-?\s*: Optional space or hyphen
        // - (?:[0-9]+|I{1,3})?: Optional number (e.g., 1, 2) or Roman numeral (I, II, III)
        // - \s*: Optional trailing space
        // - i: Case-insensitive
        $pattern = '/^(?:NSTP|CWTS|ROTC|LTS)\s*-?\s*(?:[0-9]+|I{1,3})?$/i';
        $isNSTP = preg_match($pattern, trim($courseCode));
        error_log("isNSTPCourse: Checking course_code '$courseCode' - " . ($isNSTP ? 'Matched as NSTP' : 'Not NSTP'));
        return $isNSTP;
    }

    private function areRoomsAvailableOnDays($departmentId, $sections, $targetDays, $timeSlots, $roomAssignments, $schedules)
    {
        foreach ($timeSlots as $timeSlot) {
            list($startTime, $endTime, $slotDuration) = $timeSlot;
            foreach ($sections as $section) {
                $available = $this->getRoomAssignments($departmentId, $section['max_students'], $targetDays, $startTime, $endTime, $schedules);
                $conflicted = array_filter($available, fn($room) => $this->hasRoomConflictForAnySection($schedules, $room['room_id'], $targetDays, $startTime, $endTime));
                if (empty($conflicted) && !empty($available)) {
                    return true;
                }
            }
        }
        return false;
    }

    // New helper method to check room conflicts across all sections
    private function hasRoomConflictForAnySection($schedules, $roomId, $targetDays, $startTime, $endTime)
    {
        foreach ($schedules as $schedule) {
            if ($schedule['room_id'] == $roomId) {
                foreach ($targetDays as $day) {
                    if ($schedule['day_of_week'] == $day && $this->timeOverlap($schedule['start_time'], $schedule['end_time'], $startTime, $endTime)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    // Helper method to check if two time periods overlap
    private function timesOverlap($start1, $end1, $start2, $end2)
    {
        return ($start1 < $end2) && ($start2 < $end1);
    }

    // Helper method to check if a schedule slot is available for a section
    private function isScheduleSlotAvailable($sectionId, $days, $startTime, $endTime, $sectionScheduleTracker)
    {
        if (!isset($sectionScheduleTracker[$sectionId])) {
            return true;
        }

        foreach ($days as $day) {
            foreach ($sectionScheduleTracker[$sectionId] as $existingSchedule) {
                if ($existingSchedule['day'] === $day) {
                    if ($this->timesOverlap($startTime, $endTime, $existingSchedule['start_time'], $existingSchedule['end_time'])) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    // Helper method to check if a time slot is already used
    private function isTimeSlotUsed($startTime, $endTime, $days, $usedTimeSlots)
    {
        foreach ($days as $day) {
            $slotKey = $day . '_' . $startTime . '_' . $endTime;
            if (isset($usedTimeSlots[$slotKey])) {
                return true;
            }
        }
        return false;
    }

    // Helper method to check online slot availability
    private function isOnlineSlotAvailable($days, $startTime, $endTime, $onlineSlotTracker, $sectionSize)
    {
        $maxOnlineCapacity = 150; // Adjust based on your system's capacity

        foreach ($days as $day) {
            $slotKey = $day . '_' . $startTime . '_' . $endTime;
            $currentLoad = $onlineSlotTracker[$slotKey] ?? 0;
            if ($currentLoad + $sectionSize > $maxOnlineCapacity) {
                return false;
            }
        }
        return true;
    }

    // Helper method to update section schedule tracker
    private function updateSectionScheduleTracker(&$tracker, $sectionId, $day, $startTime, $endTime)
    {
        if (!isset($tracker[$sectionId])) {
            $tracker[$sectionId] = [];
        }
        $tracker[$sectionId][] = [
            'day' => $day,
            'start_time' => $startTime,
            'end_time' => $endTime
        ];
    }

    // Helper method to update room assignments
    private function updateRoomAssignments(&$assignments, $roomId, $day, $startTime, $endTime)
    {
        $roomKey = $roomId . '_' . $day . '_' . $startTime . '_' . $endTime;
        $assignments[$roomKey] = true;
    }

    // Helper method to update online slot tracker
    private function updateOnlineSlotTracker(&$tracker, $day, $startTime, $endTime, $sectionSize)
    {
        $slotKey = $day . '_' . $startTime . '_' . $endTime;
        $tracker[$slotKey] = ($tracker[$slotKey] ?? 0) + $sectionSize;
    }

    // Helper method to update used time slots globally
    private function updateUsedTimeSlots(&$usedTimeSlots, $day, $startTime, $endTime)
    {
        $slotKey = $day . '_' . $startTime . '_' . $endTime;
        $usedTimeSlots[$slotKey] = ($usedTimeSlots[$slotKey] ?? 0) + 1;
    }

    private function consolidateScheduleSlots($slots)
    {
        if (empty($slots)) return '';

        // Group by time and room
        $timeGroups = [];
        foreach ($slots as $slot) {
            $key = $slot['time'] . ' ' . $slot['room'];
            if (!isset($timeGroups[$key])) {
                $timeGroups[$key] = [];
            }
            $timeGroups[$key][] = substr($slot['day'], 0, 1); // First letter of day
        }

        $consolidatedSlots = [];
        foreach ($timeGroups as $timeRoom => $days) {
            // FIXED: Proper day sorting
            $dayOrder = ['M' => 1, 'T' => 2, 'W' => 3, 'H' => 4, 'F' => 5, 'S' => 6]; // H for Thursday
            usort($days, function ($a, $b) use ($dayOrder) {
                // Handle Tuesday/Thursday distinction
                if ($a === 'T' && $b === 'T') return 0;

                $aValue = $dayOrder[$a] ?? 7;
                $bValue = $dayOrder[$b] ?? 7;

                return $aValue - $bValue;
            });

            // Remove duplicates and create pattern
            $days = array_unique($days);
            $dayPattern = implode('', $days);
            $consolidatedSlots[] = $dayPattern . ' ' . $timeRoom;
        }

        return implode('; ', $consolidatedSlots);
    }

    private function getConsolidatedSchedules($departmentId, $currentSemester)
    {
        $stmt = $this->db->prepare("
            SELECT 
                s.course_id,
                c.course_code,
                c.course_name AS subject_description,
                c.units,
                c.lecture_hours AS lec,
                c.lab_hours AS lab,
                GROUP_CONCAT(
                    DISTINCT CONCAT(
                        COALESCE(s.day_of_week, 'Unknown'), '|',
                        s.start_time, '|',
                        s.end_time, '|',
                        COALESCE(r.room_name, 'Online'), '|',
                        COALESCE(s.schedule_type, 'F2F')
                    ) ORDER BY 
                        FIELD(s.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'),
                        s.start_time
                    SEPARATOR '||'
                ) AS schedule_details,
                CONCAT(u.first_name, ' ', u.last_name) AS instructor,
                GROUP_CONCAT(DISTINCT sec.section_name ORDER BY sec.section_name SEPARATOR ', ') AS sections
            FROM schedules s 
            JOIN courses c ON s.course_id = c.course_id 
            JOIN curriculum_courses cd ON c.course_id = cd.course_id
            JOIN sections sec ON s.section_id = sec.section_id 
            JOIN faculty f ON s.faculty_id = f.faculty_id 
            JOIN users u ON f.user_id = u.user_id 
            LEFT JOIN classrooms r ON s.room_id = r.room_id 
            WHERE s.semester_id = :semester_id 
            GROUP BY s.course_id, s.faculty_id
            ORDER BY c.course_code
        ");

        $stmt->execute([':semester_id' => $currentSemester['semester_id']]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $consolidatedSchedules = [];

        foreach ($results as $row) {
            $scheduleDetails = explode('||', $row['schedule_details'] ?? '');
            $lectureSchedule = '';
            $labSchedule = '';
            $lastValidDay = 'Monday'; // Default day for continuity

            $lectureSlots = [];
            $labSlots = [];

            foreach ($scheduleDetails as $detail) {
                if (empty($detail)) continue;

                $detailParts = array_map('trim', explode('|', $detail));
                if (count($detailParts) < 4) {
                    error_log("Skipping invalid schedule detail format: $detail (expected at least 4 parts)");
                    continue;
                }

                $day = isset($detailParts[0]) && $detailParts[0] !== 'Unknown' ? $detailParts[0] : $lastValidDay;
                $lastValidDay = $day; // Update last valid day
                $startTime = isset($detailParts[1]) && $this->isValidTimeFormat($detailParts[1]) ? $detailParts[1] : null;
                $endTime = isset($detailParts[2]) && $this->isValidTimeFormat($detailParts[2]) ? $detailParts[2] : null;
                $roomName = isset($detailParts[3]) ? $detailParts[3] : 'Online';
                $scheduleType = isset($detailParts[4]) ? $detailParts[4] : 'F2F';

                if (!$startTime || !$endTime) {
                    error_log("Missing or invalid time in detail $detail - Day: $day, Start: $startTime, End: $endTime");
                    continue;
                }

                $formattedTime = $this->formatTimeRange($startTime, $endTime);
                if ($formattedTime === false) {
                    error_log("Failed to format time range in detail $detail");
                    continue;
                }

                $scheduleEntry = [
                    'day' => $day,
                    'time' => $formattedTime,
                    'room' => $roomName,
                    'full' => "$day $formattedTime $roomName"
                ];

                if (strtolower($scheduleType) === 'lab') {
                    $labSlots[] = $scheduleEntry;
                } else {
                    $lectureSlots[] = $scheduleEntry;
                }
            }

            if (!empty($lectureSlots)) {
                $lectureSchedule = $this->consolidateScheduleSlots($lectureSlots);
            }

            if (!empty($labSlots)) {
                $labSchedule = $this->consolidateScheduleSlots($labSlots);
            }

            $consolidatedSchedules[] = [
                'course_code' => $row['course_code'] ?? 'Unknown',
                'instructor' => $row['instructor'],
                'days' => $lectureSchedule ? substr($lectureSchedule, 0, strpos($lectureSchedule, ' ')) : '',
                'time' => $lectureSchedule ? substr($lectureSchedule, strpos($lectureSchedule, ' ') + 1, -4) : '',
                'room' => $lectureSchedule ? substr($lectureSchedule, -4) : '',
                'sections' => $row['sections']
            ];
        }

        return $consolidatedSchedules;
    }

    private function isValidTimeFormat($time)
    {
        if (empty($time)) return false;
        return (bool) DateTime::createFromFormat('H:i:s', $time);
    }

    // Fixed formatTimeRange method with error handling
    private function formatTimeRange($startTime, $endTime)
    {
        // Validate input parameters
        if (empty($startTime) || empty($endTime)) {
            error_log("Empty time parameters - Start: '$startTime', End: '$endTime'");
            return false;
        }

        // Create DateTime objects from time strings
        $start = DateTime::createFromFormat('H:i:s', $startTime);
        $end = DateTime::createFromFormat('H:i:s', $endTime);

        // Check if DateTime creation was successful
        if ($start === false) {
            error_log("Failed to parse start time: $startTime");
            return false;
        }

        if ($end === false) {
            error_log("Failed to parse end time: $endTime");
            return false;
        }

        try {
            $startFormatted = $start->format('g:i');
            $endFormatted = $end->format('g:i');
            $period = $end->format('a');

            // If both times are in same period, show period only once
            if ($start->format('a') === $end->format('a')) {
                return "$startFormatted-$endFormatted $period";
            } else {
                return $start->format('g:i a') . '-' . $end->format('g:i a');
            }
        } catch (Exception $e) {
            error_log("Error formatting time range: " . $e->getMessage());
            return false;
        }
    }

    // NEW: Calculate course duration based on units and type
    private function calculateCourseDuration($courseDetails)
    {
        $units = $courseDetails['units'];
        $lectureHours = $courseDetails['lecture_hours'] ?: 1;

        // Different durations based on course requirements
        if ($units <= 1) {
            return ['duration_hours' => 1, 'end_time_offset' => 3600]; // 1 hour
        } elseif ($units <= 2) {
            return ['duration_hours' => 1.5, 'end_time_offset' => 5400]; // 1.5 hours
        } elseif ($units <= 3) {
            return ['duration_hours' => 2, 'end_time_offset' => 7200]; // 2 hours
        } else {
            return ['duration_hours' => 2.5, 'end_time_offset' => 9000]; // 2.5 hours
        }
    }

    private function generateFlexibleTimeSlots($baseDuration = 1)
    {
        $slots = [];
        $startTimes = [
            '07:30:00',
            '08:00:00',
            '08:30:00',
            '09:00:00',
            '10:30:00',
            '12:30:00',
            '13:00:00',
            '14:30:00',
            '16:00:00',
            '17:30:00' // Added more options
        ];

        foreach ($startTimes as $start) {
            $startTimestamp = strtotime($start);

            $durations = [1, 1.5, 2]; // Focus on 1, 1.5, 2 hours
            foreach ($durations as $duration) {
                $endTimestamp = $startTimestamp + ($duration * 3600);
                $endTime = date('H:i:s', $endTimestamp);

                if ($endTimestamp <= strtotime('19:00:00')) {
                    $slots[] = [$start, $endTime, $duration];
                }
            }
        }

        return $slots;
    }

    private function findBestFaculty($facultySpecializations, $courseId, $targetDays, $startTime, $endTime, $collegeId, $departmentId, $schedules, $facultyAssignments, $courseCode, $sectionId)
    {
        error_log("🎯 SMART SCHEDULING for $courseCode (ID: $courseId) - College: $collegeId, Department: $departmentId");
        error_log("📋 Faculty Specializations input: " . print_r($facultySpecializations, true));

        $availableFaculty = [];
        $courseDetails = $this->getCourseDetails($courseId);
        error_log("📚 Course Details for $courseId: " . print_r($courseDetails, true));
        $subjectType = $courseDetails['subject_type'] ?? 'General Education';

        // First pass: Prioritize specialized faculty
        foreach ($facultySpecializations as $faculty) {
            $facultyId = $faculty['faculty_id'];
            $collegeIdFaculty = $faculty['college_id'] ?? null; // Explicitly handle null
            $departmentIdFaculty = $faculty['department_id'] ?? null;
            $additionalDepartmentId = $faculty['additional_department_id'] ?? null;
            $isPrimary = $faculty['is_primary'] ?? 0;
            $specializations = isset($faculty['course_id']) ? [$faculty['course_id']] : ($faculty['specializations'] ?? []);

            error_log("🔍 Checking faculty $facultyId (college $collegeIdFaculty, dept $departmentIdFaculty, addl dept $additionalDepartmentId, primary: $isPrimary) for $courseCode (type: $subjectType)");
            error_log("📋 Faculty $facultyId specializations: " . print_r($specializations, true));

            // Allow null college_id faculty for general education, skip for professional unless matched
            if ($collegeIdFaculty === null) {
                if ($subjectType !== 'General Education') {
                    error_log("⚠️ Faculty $facultyId with null college_id skipped for $courseCode (Professional)");
                    continue;
                }
            } elseif ($collegeIdFaculty !== $collegeId) {
                error_log("🚫 Faculty $facultyId from other college ($collegeIdFaculty) skipped for $courseCode (required: $collegeId)");
                continue;
            }

            $isCurrentPrimaryDept = ($departmentIdFaculty == $departmentId && $isPrimary);
            $isAdditionalDept = ($additionalDepartmentId == $departmentId);

            if ($subjectType === 'Professional Course' && !$isCurrentPrimaryDept && !$isAdditionalDept) {
                error_log("🚫 Faculty $facultyId from other department skipped for $courseCode (Professional)");
                continue;
            }

            if (in_array($courseId, $specializations)) {
                if ($this->isFacultyAvailable($facultyId, $targetDays, $startTime, $endTime, $facultyAssignments)) {
                    $availableFaculty[$facultyId] = 'available';
                    error_log("✅ Faculty $facultyId is available for $courseCode (section $sectionId) at $startTime-$endTime");
                } else {
                    error_log("⚡ Faculty $facultyId is busy - attempting SMART REASSIGNMENT. Assignments: " . print_r($facultyAssignments, true));
                    if ($this->freeUpExpertFaculty($facultyId, $targetDays, $startTime, $endTime, $facultyAssignments, $facultySpecializations, $collegeId, $schedules, $courseId)) {
                        $availableFaculty[$facultyId] = 'reassigned';
                        error_log("🎯 Successfully freed up faculty $facultyId for $courseCode!");
                    } else {
                        error_log("❌ Failed to free up faculty $facultyId for $courseCode");
                    }
                }
            }
        }

        // Second pass: Fallback to any available faculty if no specialized ones are found
        if (empty($availableFaculty)) {
            $allFaculty = array_unique(array_column($facultySpecializations, 'faculty_id'));
            foreach ($allFaculty as $facultyId) {
                $faculty = array_filter($facultySpecializations, fn($f) => $f['faculty_id'] == $facultyId)[0] ?? ['college_id' => null];
                $collegeIdFaculty = $faculty['college_id'] ?? null;

                if ($this->isFacultyAvailable($facultyId, $targetDays, $startTime, $endTime, $facultyAssignments)) {
                    if ($collegeIdFaculty === null && $subjectType === 'General Education') {
                        $availableFaculty[$facultyId] = 'fallback';
                        error_log("🔧 Faculty $facultyId with null college_id assigned as fallback for $courseCode (General Education)");
                    } elseif ($collegeIdFaculty === $collegeId) {
                        $availableFaculty[$facultyId] = 'fallback';
                        error_log("🔧 Faculty $facultyId assigned as fallback for $courseCode due to no specialization");
                    }
                } else {
                    error_log("⏰ Faculty $facultyId unavailable for fallback assignment for $courseCode");
                }
            }
        }

        if (!empty($availableFaculty)) {
            $priorities = array_keys($availableFaculty, 'available', true);
            if (empty($priorities)) {
                $priorities = array_keys($availableFaculty, 'reassigned', true);
            }
            if (empty($priorities)) {
                $priorities = array_keys($availableFaculty, 'fallback', true);
            }
            if (!empty($priorities)) {
                $selectedFacultyId = $priorities[array_rand($priorities)];
                error_log("🏆 Assigned faculty $selectedFacultyId for $courseCode (section $sectionId)");
                return $selectedFacultyId;
            }
        }

        error_log("⚠️ No available faculty for $courseCode (ID: $courseId) - Available faculty check: " . print_r($availableFaculty, true));
        return null;
    }

    private function findBestNonExpertFaculty($facultySpecializations, $courseId, $targetDays, $startTime, $endTime, $departmentId, $schedules, $facultyAssignments)
    {
        $nonExpertFaculty = array_filter($facultySpecializations, fn($faculty) => !in_array($courseId, $this->getCourseDetails([$faculty], $courseId)));

        if (!empty($nonExpertFaculty)) {
            error_log("🔍 Found " . count($nonExpertFaculty) . " non-expert faculty for course $courseId");

            foreach ($nonExpertFaculty as $faculty) {
                $facultyId = $faculty['faculty_id'];
                $isCurrentDept = $faculty['department_id'] == $departmentId;
                $courseDetails = $this->getCourseDetails($courseId);
                $subjectType = $courseDetails['subject_type'] ?? 'General Education';

                // New Rule: Check department and course type compatibility
                if ($isCurrentDept && $subjectType !== 'Professional Course') {
                    error_log("🚫 Non-expert faculty $facultyId from current dept skipped for $courseDetails (not Professional)");
                    continue;
                }
                if (!$isCurrentDept && $subjectType === 'Professional Course') {
                    error_log("🚫 Non-expert faculty $facultyId from other dept skipped for $courseDetails (Professional)");
                    continue;
                }

                if ($this->isFacultyAvailable($facultyId, $targetDays, $startTime, $endTime, $facultyAssignments)) {
                    error_log("✅ Non-expert faculty $facultyId is available - ASSIGNED!");
                    return $facultyId;
                }
            }
        }

        error_log("❌ No available non-expert faculty for course $courseId");
        return null;
    }

    private function freeUpExpertFaculty($expertFacultyId, $targetDays, $startTime, $endTime, &$facultyAssignments, $facultySpecializations, $departmentId, $schedules, $expertCourseId)
    {
        error_log("🔄 Attempting to free up EXPERT faculty $expertFacultyId");

        // Find conflicting assignments
        $conflictingAssignments = $this->findConflictingAssignments($expertFacultyId, $targetDays, $startTime, $endTime, $facultyAssignments);

        if (empty($conflictingAssignments)) {
            return true; // Already free
        }

        error_log("📋 Found " . count($conflictingAssignments) . " conflicting assignments to reassign");

        foreach ($conflictingAssignments as $assignment) {
            if (!isset($assignment['course_id']) || !isset($assignment['day_of_week']) || !isset($assignment['schedule_id'])) {
                error_log("⚠️ Missing required keys in assignment: " . print_r($assignment, true));
                continue;
            }

            $conflictCourseId = $assignment['course_id'];

            // Skip if expert in both courses
            if ($this->isExpertInCourse($expertFacultyId, $conflictCourseId, $facultySpecializations)) {
                error_log("⚠️ Cannot reassign course $conflictCourseId - faculty $expertFacultyId is EXPERT in both");
                continue;
            }

            error_log("🔄 Trying to reassign course $conflictCourseId from expert faculty $expertFacultyId");

            // Find alternative faculty
            $alternativeFaculty = $this->findAlternativeFacultyForReassignment(
                $conflictCourseId,
                $assignment['day_of_week'],
                $assignment['start_time'],
                $assignment['end_time'],
                $facultySpecializations,
                $facultyAssignments,
                $departmentId,
                $expertFacultyId,
                $collegeId = null
            );

            if ($alternativeFaculty) {
                error_log("✅ Found alternative faculty $alternativeFaculty for course $conflictCourseId");
                if ($this->reassignCourse($assignment, $alternativeFaculty, $facultyAssignments, $schedules, $collegeId)) {
                    error_log("🎯 Successfully reassigned course $conflictCourseId to faculty $alternativeFaculty");
                } else {
                    error_log("❌ Failed to reassign course $conflictCourseId");
                    return false;
                }
            } else {
                // Try rescheduling
                if ($this->rescheduleConflictingCourse($assignment, $facultyAssignments, $schedules, $departmentId)) {
                    error_log("⏰ Successfully rescheduled conflicting course $conflictCourseId");
                } else {
                    error_log("❌ Could not find alternative for course $conflictCourseId");
                    return false;
                }
            }
        }

        return true;
    }

    // Check if faculty is expert in a specific course
    private function isExpertInCourse($facultyId, $courseId, $facultySpecializations)
    {
        foreach ($facultySpecializations as $spec) {
            if (
                $spec['faculty_id'] == $facultyId &&
                $spec['course_id'] == $courseId &&
                strtolower($spec['expertise_level']) === 'expert'
            ) {
                return true;
            }
        }
        return false;
    }

    private function findConflictingAssignments($facultyId, $targetDays, $startTime, $endTime, $facultyAssignments)
    {
        $conflicts = [];

        foreach ($facultyAssignments as $assignment) {
            if (isset($assignment['faculty_id']) && $assignment['faculty_id'] == $facultyId) {
                $assignmentDays = isset($assignment['day_of_week']) ? (array)$assignment['day_of_week'] : [];
                $dayOverlap = array_intersect($targetDays, $assignmentDays);

                if (
                    !empty($dayOverlap) &&
                    $this->hasTimeConflict($startTime, $endTime, $assignment['start_time'], $assignment['end_time'])
                ) {
                    $conflicts[] = array_merge($assignment, ['day_of_week' => $assignmentDays]); // Ensure day_of_week is set
                    error_log("⚡ Conflict found: Course {$assignment['course_id']} on " . implode(',', $assignmentDays) . " at {$assignment['start_time']}-{$assignment['end_time']}");
                }
            }
        }

        return $conflicts;
    }

    private function findAlternativeFacultyForReassignment($courseId, $targetDays, $startTime, $endTime, $facultySpecializations, $facultyAssignments, $departmentId, $collegeId, $excludeFacultyId)
    {
        $suitableFaculty = array_filter($facultySpecializations, function ($spec) use ($courseId, $excludeFacultyId) {
            return $spec['course_id'] == $courseId &&
                $spec['faculty_id'] != $excludeFacultyId &&
                in_array(strtolower($spec['expertise_level']), ['intermediate', 'advanced']);
        });

        usort($suitableFaculty, function ($a, $b) {
            $levels = ['beginner' => 1, 'intermediate' => 2, 'advanced' => 3, 'expert' => 4];
            return $levels[strtolower($b['expertise_level'])] - $levels[strtolower($a['expertise_level'])];
        });

        foreach ($suitableFaculty as $faculty) {
            if ($this->isFacultyAvailable($faculty['faculty_id'], $targetDays, $startTime, $endTime, $facultyAssignments)) {
                return $faculty['faculty_id'];
            }
        }

        $allFaculty = $this->getFaculty($departmentId, $collegeId);
        foreach ($allFaculty as $faculty) {
            if (
                $faculty['faculty_id'] != $excludeFacultyId &&
                $this->isFacultyAvailable($faculty['faculty_id'], $targetDays, $startTime, $endTime, $facultyAssignments)
            ) {
                return $faculty['faculty_id'];
            }
        }

        return null;
    }

    private function reassignCourse($assignment, $collegeId, $newFacultyId, &$facultyAssignments, &$schedules)
    {
        try {
            $stmt = $this->db->prepare("
            UPDATE schedules 
            SET faculty_id = :new_faculty_id
            WHERE schedule_id = :schedule_id
        ");

            $result = $stmt->execute([
                ':new_faculty_id' => $newFacultyId,
                ':schedule_id' => $assignment['schedule_id']
            ]);

            if ($result) {
                // Update in-memory arrays
                foreach ($facultyAssignments as &$fa) {
                    if ($fa['schedule_id'] == $assignment['schedule_id']) {
                        $fa['faculty_id'] = $newFacultyId;
                        $fa['faculty_name'] = $this->getFaculty($newFacultyId, $collegeId); // Fetch name if needed
                        break;
                    }
                }

                foreach ($schedules as &$schedule) {
                    if ($schedule['schedule_id'] == $assignment['schedule_id']) {
                        $schedule['faculty_id'] = $newFacultyId;
                        $schedule['faculty_name'] = $this->getFaculty($newFacultyId, $collegeId); // Fetch name if needed
                        break;
                    }
                }

                return true;
            }
        } catch (Exception $e) {
            error_log("❌ Error reassigning course: " . $e->getMessage());
        }

        return false;
    }

    // Helper method to check faculty availability
    private function isFacultyAvailable($facultyId, $targetDays, $startTime, $endTime, $facultyAssignments)
    {
        foreach ($facultyAssignments as $assignment) {
            if ($assignment['faculty_id'] == $facultyId) {
                foreach ($targetDays as $day) {
                    if (
                        in_array($day, (array)$assignment['days']) &&
                        $this->hasTimeConflict($startTime, $endTime, $assignment['start_time'], $assignment['end_time'])
                    ) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    // Helper method to reschedule a conflicting course to different time
    private function rescheduleConflictingCourse($assignment, &$facultyAssignments, &$schedules, $departmentId)
    {
        error_log("⏰ Attempting to reschedule course {$assignment['course_id']} to different time");

        // Get available time slots (you might need to adjust this based on your time slot generation)
        $alternativeTimeSlots = $this->getAlternativeTimeSlots($assignment['day_of_week']);

        foreach ($alternativeTimeSlots as $timeSlot) {
            $newStartTime = $timeSlot['start'];
            $newEndTime = $timeSlot['end'];

            // Check if faculty is available at new time
            if ($this->isFacultyAvailable($assignment['faculty_id'], $assignment['day_of_week'], $newStartTime, $newEndTime, $facultyAssignments)) {
                // Check if room is available at new time
                $roomAvailable = $this->isRoomAvailable($assignment['room_id'], $assignment['day_of_week'], $newStartTime, $newEndTime, $schedules);

                if ($roomAvailable) {
                    // Update the schedule
                    if ($this->updateScheduleTime($assignment['schedule_id'], $newStartTime, $newEndTime)) {
                        // Update in-memory arrays
                        foreach ($facultyAssignments as &$fa) {
                            if ($fa['schedule_id'] == $assignment['schedule_id']) {
                                $fa['start_time'] = $newStartTime;
                                $fa['end_time'] = $newEndTime;
                                break;
                            }
                        }

                        foreach ($schedules as &$schedule) {
                            if ($schedule['schedule_id'] == $assignment['schedule_id']) {
                                $schedule['start_time'] = $newStartTime;
                                $schedule['end_time'] = $newEndTime;
                                break;
                            }
                        }

                        error_log("✅ Successfully rescheduled course {$assignment['course_id']} to $newStartTime-$newEndTime");
                        return true;
                    }
                }
            }
        }

        error_log("❌ Could not find alternative time slot for course {$assignment['course_id']}");
        return false;
    }

    // Get alternative time slots for rescheduling
    private function getAlternativeTimeSlots($currentDays)
    {
        // Return common alternative time slots
        return [
            ['start' => '07:30:00', 'end' => '09:00:00'],
            ['start' => '09:00:00', 'end' => '10:30:00'],
            ['start' => '10:30:00', 'end' => '12:00:00'],
            ['start' => '13:00:00', 'end' => '14:30:00'],
            ['start' => '14:30:00', 'end' => '16:00:00'],
            ['start' => '16:00:00', 'end' => '17:30:00'],
            ['start' => '17:30:00', 'end' => '19:00:00']
        ];
    }

    // Check if room is available at specific time
    private function isRoomAvailable($roomId, $days, $startTime, $endTime, $schedules)
    {
        foreach ($schedules as $schedule) {
            if ($schedule['room_id'] == $roomId) {
                $scheduleDays = (array)$schedule['day_of_week'];
                $dayOverlap = array_intersect((array)$days, $scheduleDays);

                if (
                    !empty($dayOverlap) &&
                    $this->hasTimeConflict($startTime, $endTime, $schedule['start_time'], $schedule['end_time'])
                ) {
                    return false;
                }
            }
        }
        return true;
    }

    private function getAvailableRoom($departmentId, $maxStudents, $day, $startTime, $endTime, $schedules, $forceF2F = false)
    {
        // First query: Department-specific rooms
        $stmt = $this->db->prepare("
        SELECT r.room_id, r.room_name, r.capacity, r.room_type, r.department_id
        FROM classrooms r
        WHERE r.capacity >= :capacity AND r.department_id = :department_id
        AND NOT EXISTS (
            SELECT 1 FROM schedules s
            WHERE s.room_id = r.room_id
            AND s.day_of_week = :day
            AND NOT (:end_time <= s.start_time OR :start_time >= s.end_time)
            AND s.semester_id = :semester_id
        )
        ");
        $stmt->execute([
            ':department_id' => $departmentId,
            ':capacity' => $maxStudents,
            ':day' => $day,
            ':start_time' => $startTime,
            ':end_time' => $endTime,
            ':semester_id' => $_SESSION['current_semester']['semester_id']
        ]);

        $availableRooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($availableRooms as $room) {
            if (!$this->hasRoomConflict($schedules, $room['room_id'], $day, $startTime, $endTime)) {
                return $room;
            } else {
                error_log("Room {$room['room_name']} (ID: {$room['room_id']}) conflicted for day $day");
            }
        }

        // If no department-specific room or not forcing F2F, try all rooms
        if (!$forceF2F || empty($availableRooms)) {
            $stmt = $this->db->prepare("
            SELECT r.room_id, r.room_name, r.capacity, r.room_type, r.department_id
            FROM classrooms r
            WHERE r.capacity >= :capacity
            AND NOT EXISTS (
                SELECT 1 FROM schedules s
                WHERE s.room_id = r.room_id
                AND s.day_of_week = :day
                AND NOT (:end_time <= s.start_time OR :start_time >= s.end_time)
                AND s.semester_id = :semester_id
            )
        ");
            $stmt->execute([
                ':capacity' => $maxStudents,
                ':day' => $day,
                ':start_time' => $startTime,
                ':end_time' => $endTime,
                ':semester_id' => $_SESSION['current_semester']['semester_id']
            ]);
            $allRooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($allRooms as $room) {
                if (!$this->hasRoomConflict($schedules, $room['room_id'], $day, $startTime, $endTime)) {
                    return $room;
                } else {
                    error_log("Room {$room['room_name']} (ID: {$room['room_id']}) conflicted for day $day");
                }
            }
        }

        error_log("No available room found for day $day at $startTime-$endTime with capacity >= $maxStudents");
        return ['room_id' => null, 'room_name' => 'Online', 'capacity' => $maxStudents];
    }

    // Update schedule time in database
    private function updateScheduleTime($scheduleId, $newStartTime, $newEndTime)
    {
        try {
            $stmt = $this->db->prepare("
            UPDATE schedules 
            SET start_time = :start_time, end_time = :end_time 
            WHERE schedule_id = :schedule_id
        ");

            return $stmt->execute([
                ':start_time' => $newStartTime,
                ':end_time' => $newEndTime,
                ':schedule_id' => $scheduleId
            ]);
        } catch (Exception $e) {
            error_log("❌ Error updating schedule time: " . $e->getMessage());
            return false;
        }
    }

    // Enhanced hasTimeConflict method with logging
    private function hasTimeConflict($startTime1, $endTime1, $startTime2, $endTime2)
    {
        // Convert time strings to comparable format (24-hour format)
        $start1 = strtotime($startTime1);
        $end1 = strtotime($endTime1);
        $start2 = strtotime($startTime2);
        $end2 = strtotime($endTime2);

        // Check for overlap: two time periods overlap if one starts before the other ends
        $hasConflict = ($start1 < $end2) && ($start2 < $end1);

        return $hasConflict;
    }

    private function getRoomAssignments($departmentId, $maxStudents, $targetDays, $startTime, $endTime, $schedules, $forceF2F = false)
    {
        $assignments = [];

        foreach ($targetDays as $day) {
            $room = null;
            $attempts = 0;
            $maxAttempts = 3; // Retry for F2F or to find any available room

            while ($attempts < $maxAttempts && !$room) {
                $room = $this->getAvailableRoom($departmentId, $maxStudents, $day, $startTime, $endTime, $schedules, $forceF2F);
                if ($room && $room['room_id'] && !$this->hasRoomConflict($schedules, $room['room_id'], $day, $startTime, $endTime)) {
                    break;
                } elseif ($forceF2F) {
                    $maxStudents = max($maxStudents - 5, 1); // Reduce capacity for retry
                    $attempts++;
                    error_log("Retry $attempts for F2F room on $day at $startTime-$endTime, adjusted maxStudents to $maxStudents");
                } else {
                    $attempts++; // Try next attempt for GE/NSTP to find any room
                }
            }

            if ($room && $room['room_id']) {
                $assignments[$day] = [
                    'room_id' => $room['room_id'],
                    'room_name' => $room['room_name'],
                    'room_type' => $room['room_type']
                ];
                error_log("Assigned room {$room['room_name']} (ID: {$room['room_id']}) for day $day at $startTime-$endTime");
            } elseif ($forceF2F) {
                error_log("Critical: No room found for F2F course on $day at $startTime-$endTime despite retries");
                throw new Exception("No available room for Professional Course on $day at $startTime-$endTime");
            } else {
                $assignments[$day] = [
                    'room_id' => null,
                    'room_name' => 'Online'
                ];
                error_log("No available room found for day $day at $startTime-$endTime, falling back to online for GE/NSTP");
            }
        }

        return $assignments;
    }

    private function hasRoomConflict($schedules, $roomId, $day, $startTime, $endTime)
    {
        foreach ($schedules as $schedule) {
            if (
                $schedule['room_id'] == $roomId &&
                $schedule['day_of_week'] == $day &&
                $this->timeOverlap($schedule['start_time'], $schedule['end_time'], $startTime, $endTime)
            ) {
                error_log("Room conflict detected for room ID $roomId on $day at $startTime-$endTime with schedule: " . json_encode($schedule));
                return true;
            }
        }
        error_log("No conflict for room ID $roomId on $day at $startTime-$endTime");
        return false;
    }

    private function timeOverlap($start1, $end1, $start2, $end2)
    {
        $start1Time = strtotime($start1);
        $end1Time = strtotime($end1);
        $start2Time = strtotime($start2);
        $end2Time = strtotime($end2);
        return $start1Time < $end2Time && $start2Time < $end1Time;
    }

    private function processManualSchedules($schedulesData, $currentSemester, $departmentId)
    {
        $schedules = [];
        foreach ($schedulesData as $schedule) {
            $errors = $this->validateSchedule($schedule, $departmentId);
            if (empty($errors)) {
                $response = $this->callSchedulingService('POST', 'schedules', [
                    'course_id' => $schedule['course_id'],
                    'faculty_id' => $schedule['faculty_id'],
                    'room_id' => $schedule['room_id'],
                    'section_id' => $schedule['section_id'],
                    'day_of_week' => $schedule['day_of_week'],
                    'start_time' => $schedule['start_time'],
                    'end_time' => $schedule['end_time'],
                    'semester_id' => $currentSemester['semester_id'],
                    'curriculum_id' => $schedule['curriculum_id']
                ]);
                if ($response['code'] === 200) {
                    $schedules[] = $response['data'];
                }
            } else {
                $error = implode(", ", $errors);
                error_log("Validation errors in manual schedule: " . $error);
            }
        }
        return $schedules;
    }

    private function getFacultySpecializations($departmentId, $collegeId)
    {
        try {
            $stmt = $this->db->prepare("
            SELECT s.faculty_id, s.course_id, s.expertise_level, s.is_primary_specialization, u.department_id, u.college_id
            FROM specializations s
            JOIN faculty f ON s.faculty_id = f.faculty_id
            JOIN users u ON f.user_id = u.user_id
            JOIN faculty_departments fd ON s.faculty_id = fd.faculty_id
            JOIN courses c ON s.course_id = c.course_id
            WHERE fd.department_id = :department_id
            ORDER BY s.expertise_level DESC, s.is_primary_specialization DESC
        ");
            $stmt->execute([':department_id' => $departmentId]);
            $specializations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($specializations === false) {
                error_log("fetchAll returned false for getFacultySpecializations, department $departmentId");
                return [];
            }

            // Filter and log mismatches
            $filteredSpecializations = [];
            foreach ($specializations as $spec) {
                $facultyCollegeId = $spec['college_id'] ?? null;
                if ($facultyCollegeId && $facultyCollegeId != $collegeId) {
                    error_log("⚠️ Faculty {$spec['faculty_id']} (college $facultyCollegeId) specialization mismatch with required college $collegeId");
                } else {
                    $filteredSpecializations[] = $spec;
                }
            }

            error_log("getFacultySpecializations for department $departmentId, college $collegeId: found " . count($filteredSpecializations) . " matching specializations");
            return $filteredSpecializations;
        } catch (PDOException $e) {
            error_log("getFacultySpecializations failed for department $departmentId, college $collegeId: " . $e->getMessage());
            return [];
        } catch (Exception $e) {
            error_log("Unexpected error in getFacultySpecializations for department $departmentId, college $collegeId: " . $e->getMessage());
            return [];
        }
    }
    
    public function deleteSchedule()
    {
        if (isset($_POST['schedule_id'])) {
            $stmt = $this->db->prepare("DELETE FROM schedules WHERE schedule_id = :schedule_id AND semester_id = :semester_id");
            $stmt->execute([':schedule_id' => $_POST['schedule_id'], ':semester_id' => $_SESSION['current_semester']['semester_id']]);
            echo json_encode(['success' => $stmt->rowCount() > 0]);
        }
        exit;
    }

    public function updateSchedule()
    {
        if (isset($_POST['schedule_id']) && isset($_POST['data'])) {
            $data = json_decode($_POST['data'], true);
            $stmt = $this->db->prepare("UPDATE schedules SET course_id = :course_id, section_id = :section_id, room_id = :room_id, faculty_id = :faculty_id, day_of_week = :day_of_week, start_time = :start_time, end_time = :end_time WHERE schedule_id = :schedule_id AND semester_id = :semester_id");
            $stmt->execute([
                ':schedule_id' => $_POST['schedule_id'],
                ':course_id' => $data['course_id'],
                ':section_id' => $data['section_id'],
                ':room_id' => $data['room_id'] ?? null,
                ':faculty_id' => $data['faculty_id'],
                ':day_of_week' => $data['day_of_week'],
                ':start_time' => $data['start_time'],
                ':end_time' => $data['end_time'],
                ':semester_id' => $_SESSION['current_semester']['semester_id']
            ]);
            echo json_encode(['success' => $stmt->rowCount() > 0]);
        }
        exit;
    }

    private function saveScheduleToDB($scheduleData, $currentSemester)
    {
        try {
            $sql = "INSERT INTO schedules (course_id, section_id, room_id, semester_id, faculty_id, schedule_type, day_of_week, start_time, end_time,
                     status, is_public, department_id) 
                     VALUES (:course_id, :section_id, :room_id, :semester_id, :faculty_id, :schedule_type, :day_of_week, :start_time, :end_time, :status, :is_public, :department_id)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':course_id' => $scheduleData['course_id'],
                ':section_id' => $scheduleData['section_id'],
                ':room_id' => $scheduleData['room_id'],
                ':semester_id' => $scheduleData['semester_id'],
                ':faculty_id' => $scheduleData['faculty_id'],
                ':schedule_type' => $scheduleData['schedule_type'],
                ':day_of_week' => $scheduleData['day_of_week'],
                ':start_time' => $scheduleData['start_time'],
                ':end_time' => $scheduleData['end_time'],
                ':status' => $scheduleData['status'],
                ':is_public' => $scheduleData['is_public'],
                ':department_id' => $scheduleData['department_id']
            ]);

            return ['code' => 200, 'data' => ['schedule_id' => $this->db->lastInsertId()]];
        } catch (PDOException $e) {
            error_log("Database error in saveScheduleToDB: " . $e->getMessage());
            return ['code' => 500, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }

    private function loadSchedules($departmentId, $currentSemester)
    {
        $stmt = $this->db->prepare("SELECT s.*, c.course_code, c.course_name, sec.section_name, sec.year_level, CONCAT(u.first_name, ' ', u.last_name) AS faculty_name, r.room_name FROM schedules s JOIN courses c ON s.course_id = c.course_id JOIN sections sec ON s.section_id = sec.section_id JOIN faculty f ON s.faculty_id = f.faculty_id JOIN users u ON f.user_id = u.user_id LEFT JOIN classrooms r ON s.room_id = r.room_id WHERE s.semester_id = :semester_id");
        $stmt->execute([':semester_id' => $currentSemester['semester_id']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function removeDuplicateSchedules($departmentId, $currentSemester)
    {
        try {
            // Use a derived table to identify duplicates and delete them
            $sql = "
            DELETE s1 FROM schedules s1
            INNER JOIN (
                SELECT s2.schedule_id
                FROM schedules s2
                INNER JOIN (
                    SELECT course_id, section_id, day_of_week, start_time, end_time, semester_id,
                           MIN(schedule_id) as keep_id
                    FROM schedules
                    WHERE semester_id = :inner_semester_id
                    AND department_id = :inner_department_id
                    GROUP BY course_id, section_id, day_of_week, start_time, end_time, semester_id
                    HAVING COUNT(*) > 1
                ) dups ON s2.course_id = dups.course_id
                       AND s2.section_id = dups.section_id
                       AND s2.day_of_week = dups.day_of_week
                       AND s2.start_time = dups.start_time
                       AND s2.end_time = dups.end_time
                       AND s2.semester_id = dups.semester_id
                WHERE s2.schedule_id != dups.keep_id
            ) dups_to_delete ON s1.schedule_id = dups_to_delete.schedule_id
            WHERE s1.semester_id = :outer_semester_id
            AND s1.department_id = :outer_department_id
        ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':inner_semester_id' => $currentSemester['semester_id'],
                ':inner_department_id' => $departmentId,
                ':outer_semester_id' => $currentSemester['semester_id'],
                ':outer_department_id' => $departmentId
            ]);

            $rowsAffected = $stmt->rowCount();
            error_log("Removed $rowsAffected duplicate schedules for department $departmentId, semester " . $currentSemester['semester_id']);
        } catch (PDOException $e) {
            error_log("Error removing duplicate schedules: " . $e->getMessage());
            throw $e;
        }
    }

    private function validateSchedule($schedule, $departmentId)
    {
        $errors = [];
        if (!$this->validateCurriculumCourse($schedule['curriculum_id'], $schedule['course_id'])) {
            $errors[] = "Course doesn't belong to selected curriculum";
        }
        $stmt = $this->db->prepare("SELECT 1 FROM classrooms WHERE room_id = :room_id AND availability = 'available'");
        $stmt->execute([':room_id' => $schedule['room_id']]);
        if (!$stmt->fetchColumn()) {
            $errors[] = "Selected room is not available";
        }
        return $errors;
    }

    public function checkScheduleDeadlineStatus($userDepartmentId)
    {
        if (!$userDepartmentId) {
            error_log("checkScheduleDeadlineStatus: Invalid department ID");
            return ['locked' => false, 'message' => 'Department not set'];
        }

        // Check session cache first
        $cacheKey = "deadline_status_$userDepartmentId";
        if (isset($_SESSION[$cacheKey]) && time() < $_SESSION[$cacheKey . '_expiry']) {
            return $_SESSION[$cacheKey];
        }

        $stmt = $this->db->prepare("
            SELECT deadline 
            FROM schedule_deadlines 
            WHERE department_id = :department_id 
            AND is_active = 1 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([':department_id' => $userDepartmentId]);
        $deadline = $stmt->fetchColumn();

        $status = ['locked' => false, 'message' => null];
        if ($deadline) {
            $deadlineTime = new DateTime($deadline);
            $currentTime = new DateTime();

            if ($currentTime > $deadlineTime) {
                $status = [
                    'locked' => true,
                    'message' => 'Schedule creation deadline has passed (' . $deadlineTime->format('M j, Y g:i A') . ')'
                ];
            } else {
                $timeRemaining = $currentTime->diff($deadlineTime);
                $totalHours = ($timeRemaining->days * 24) + $timeRemaining->h;
                $status = [
                    'locked' => false,
                    'deadline' => $deadlineTime,
                    'time_remaining' => $timeRemaining,
                    'total_hours' => $totalHours
                ];
            }
        }

        // Cache for 5 minutes
        $_SESSION[$cacheKey] = $status;
        $_SESSION[$cacheKey . '_expiry'] = time() + 300;
        return $status;
    }

    public function viewScheduleHistory()
    {
        $chairId = $_SESSION['user_id'] ?? null;
        $departmentId = $this->getChairDepartment($chairId);
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

        require_once __DIR__ . '/../views/chair/schedule_history.php';
    }

    private function getCourseDetails($courseId)
    {
        try {
            $stmt = $this->db->prepare("SELECT c.course_id, c.course_code, c.course_name, c.lecture_hours, c.lab_hours, c.units, 
                                          COALESCE(c.subject_type, cc.subject_type) as subject_type 
                                    FROM courses c 
                                    LEFT JOIN curriculum_courses cc ON c.course_id = cc.course_id 
                                    WHERE c.course_id = :course_id LIMIT 1");
            $stmt->execute([':course_id' => $courseId]);
            $details = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($details) {
                $details['subject_type'] = $details['subject_type'] ?? 'General Education'; // Final fallback
                error_log("Fetched course details for course_id $courseId: " . print_r($details, true));
            } else {
                error_log("No course details found for course_id $courseId");
                $details = ['course_id' => $courseId, 'course_code' => 'Unknown', 'course_name' => 'Unknown', 'lecture_hours' => 1, 'lab_hours' => 0, 'units' => 0, 'subject_type' => 'General Education'];
            }
            return $details;
        } catch (PDOException $e) {
            error_log("Error fetching course details for course_id $courseId: " . $e->getMessage());
            return ['course_id' => $courseId, 'course_code' => 'Unknown', 'course_name' => 'Unknown', 'lecture_hours' => 1, 'lab_hours' => 0, 'units' => 0, 'subject_type' => 'General Education'];
        }
    }

    private function getHistoricalSchedules($departmentId, $semesterId = null, $academicYear = null)
    {
        try {
            $sql = "SELECT s.*, c.course_code, c.course_name, u.first_name, u.last_name, r.room_name, se.section_name, sem.semester_name, sem.academic_year
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

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array_map(function ($schedule) {
                return [
                    'schedule_id' => $schedule['schedule_id'],
                    'course_code' => $schedule['course_code'],
                    'course_name' => $schedule['course_name'],
                    'faculty_name' => trim($schedule['first_name'] . ' ' . $schedule['last_name']) ?: 'Unknown',
                    'room_name' => $schedule['room_name'] ?? 'Online',
                    'section_name' => $schedule['section_name'],
                    'day_of_week' => $schedule['day_of_week'],
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

    private function formatScheduleHistory($schedules)
    {
        $formatted = [];
        foreach ($schedules as $schedule) {
            $key = $schedule['semester_name'] . ' - ' . $schedule['academic_year'];
            if (!isset($formatted[$key])) {
                $formatted[$key] = [];
            }
            $formatted[$key][] = sprintf(
                "%s - %s (Section: %s, Faculty: %s, Room: %s, %s %s-%s)",
                $schedule['course_code'],
                $schedule['course_name'],
                $schedule['section_name'],
                $schedule['faculty_name'],
                $schedule['room_name'],
                $schedule['day_of_week'],
                $schedule['start_time'],
                $schedule['end_time']
            );
        }
        return $formatted;
    }

    /**
     * Manage classrooms
     */
    public function classroom()
    {
        error_log("classroom: Starting classroom method");
        try {
            $chairId = $_SESSION['user_id'];
            $departmentId = $this->getChairDepartment($chairId);
            $classrooms = [];
            $departments = []; // Initialize departments array
            $error = null;

            $departmentInfo = null;

            // Get department and college info for the chair's department
            if ($departmentId) {
                $stmt = $this->db->prepare("
                SELECT d.*, cl.college_name 
                FROM departments d
                JOIN colleges cl ON d.college_id = cl.college_id
                WHERE d.department_id = ?
            ");
                $stmt->execute([$departmentId]);
                $departmentInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            // Fetch all departments for the dropdown
            $deptStmt = $this->db->prepare("SELECT department_id, department_name FROM departments ORDER BY department_name");
            $deptStmt->execute();
            $departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch classrooms (own department + shared)
            $query = "
            SELECT c.*, d.department_name, cl.college_name 
            FROM classrooms c
            JOIN departments d ON c.department_id = d.department_id
            JOIN colleges cl ON d.college_id = cl.college_id
            WHERE c.department_id = :department_id OR c.shared = 1
        ";
            $params = [':department_id' => $departmentId];

            // Handle search via GET
            if (isset($_GET['search']) && !empty($_GET['search'])) {
                $searchTerm = '%' . $_GET['search'] . '%';
                $query .= " AND (c.room_name LIKE :search OR c.building LIKE :search)";
                $params[':search'] = $searchTerm;
            }

            $query .= " ORDER BY c.room_name";
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $classrooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Handle missing department
            if (!$departmentId) {
                error_log("classroom: No department found for chairId: $chairId");
                $classrooms = [];
                $error = "No department assigned to this chair.";
            }

            // Pass variables to the view
            $viewData = [
                'classrooms' => $classrooms,
                'departmentInfo' => $departmentInfo,
                'departments' => $departments,
                'error' => $error
            ];
            extract($viewData); // Extract variables into the current scope
            require_once __DIR__ . '/../views/chair/classroom.php';
        } catch (PDOException $e) {
            error_log("classroom: Error - " . $e->getMessage());
            $error = "Failed to load classrooms.";
            $departments = []; // Fallback in case of error
            $viewData = [
                'classrooms' => [],
                'departmentInfo' => null,
                'departments' => $departments,
                'error' => $error
            ];
            extract($viewData);
            require_once __DIR__ . '/../views/chair/classroom.php';
        }
    }

    public function sections()
    {
        error_log("sections: Starting sections method");
        try {
            $chairId = $_SESSION['user_id'];
            $departmentId = $this->getChairDepartment($chairId);
            $error = null;
            $success = null;

            if (!$departmentId) {
                error_log("sections: No department found for chairId: $chairId");
                $error = "No department assigned to this chair.";
                $currentSemesterSections = [];
                $groupedCurrentSections = [
                    '1st Year' => [],
                    '2nd Year' => [],
                    '3rd Year' => [],
                    '4th Year' => []
                ];
                $currentSemester = null;
                $previousSections = [];
                require_once __DIR__ . '/../views/chair/sections.php';
                return;
            }

            // Get current semester first
            $currentSemester = $this->getCurrentSemester();

            if (!$currentSemester) {
                error_log("sections: No current semester set");
                $error = "Current semester is not set. Please contact your administrator.";
                $currentSemesterSections = [];
                $groupedCurrentSections = [
                    '1st Year' => [],
                    '2nd Year' => [],
                    '3rd Year' => [],
                    '4th Year' => []
                ];
                $previousSections = [];
                require_once __DIR__ . '/../views/chair/sections.php';
                return;
            }

            // Auto-transition sections from previous semesters to inactive
            $this->autoTransitionSections($departmentId, $currentSemester);

            // Handle POST requests for add/remove/edit/reuse
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (isset($_POST['add_section'])) {
                    $this->addSection($departmentId);
                } elseif (isset($_POST['remove_section'])) {
                    $this->removeSection();
                } elseif (isset($_POST['edit_section'])) {
                    $this->editSection($departmentId);
                } elseif (isset($_POST['reuse_section'])) {
                    $this->reuseSection($departmentId);
                } elseif (isset($_POST['reuse_all_sections'])) {
                    $this->reuseAllSections($departmentId);
                }
                // Retrieve success/error messages after POST
                $success = $_SESSION['success'] ?? null;
                $error = $_SESSION['error'] ?? null;
                $info = $_SESSION['info'] ?? null;
                unset($_SESSION['success'], $_SESSION['error'], $_SESSION['info']);
            }

            // Fetch ONLY current semester sections
            $query = "
            SELECT s.*, p.program_name
            FROM sections s
            JOIN programs p ON s.department_id = p.department_id
            WHERE s.department_id = :department_id
            AND s.is_active = 1
            AND s.semester_id = :semester_id
            ORDER BY
            CASE s.year_level
            WHEN '1st Year' THEN 1
            WHEN '2nd Year' THEN 2
            WHEN '3rd Year' THEN 3
            WHEN '4th Year' THEN 4
            ELSE 5
            END,
            s.section_name
            ";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':department_id', $departmentId, PDO::PARAM_INT);
            $stmt->bindParam(':semester_id', $currentSemester['semester_id'], PDO::PARAM_INT);
            $stmt->execute();
            $currentSemesterSections = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Group current semester sections by year level
            $groupedCurrentSections = [
                '1st Year' => [],
                '2nd Year' => [],
                '3rd Year' => [],
                '4th Year' => []
            ];

            foreach ($currentSemesterSections as $section) {
                if (isset($groupedCurrentSections[$section['year_level']])) {
                    $groupedCurrentSections[$section['year_level']][] = $section;
                }
            }

            // Fetch previous semester sections
            $query = "
            SELECT s.*, p.program_name, sm.semester_name, sm.academic_year
            FROM sections s
            JOIN programs p ON s.department_id = p.department_id
            JOIN semesters sm ON s.semester_id = sm.semester_id
            WHERE s.department_id = :department_id
            AND s.semester_id != :current_semester_id
            ORDER BY sm.academic_year DESC,
            CASE sm.semester_name
            WHEN '1st' THEN 1
            WHEN '2nd' THEN 2
            WHEN 'Summer' THEN 3
            WHEN 'Mid Year' THEN 4
            ELSE 5
            END,
            CASE s.year_level
            WHEN '1st Year' THEN 1
            WHEN '2nd Year' THEN 2
            WHEN '3rd Year' THEN 3
            WHEN '4th Year' THEN 4
            ELSE 5
            END,
            s.section_name
            ";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':department_id', $departmentId, PDO::PARAM_INT);
            $stmt->bindParam(':current_semester_id', $currentSemester['semester_id'], PDO::PARAM_INT);
            $stmt->execute();
            $previousSections = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Group previous sections by semester and year level
            $groupedPreviousSections = [];
            foreach ($previousSections as $section) {
                $semesterKey = $section['semester_name'] . ' ' . $section['academic_year'];
                if (!isset($groupedPreviousSections[$semesterKey])) {
                    $groupedPreviousSections[$semesterKey] = [
                        '1st Year' => [],
                        '2nd Year' => [],
                        '3rd Year' => [],
                        '4th Year' => []
                    ];
                }
                if (isset($groupedPreviousSections[$semesterKey][$section['year_level']])) {
                    $groupedPreviousSections[$semesterKey][$section['year_level']][] = $section;
                }
            }

            error_log("sections: Found " . count($currentSemesterSections) . " current sections and " . count($previousSections) . " previous sections for department $departmentId");

            require_once __DIR__ . '/../views/chair/sections.php';
        } catch (PDOException $e) {
            error_log("sections: PDO Error - " . $e->getMessage());
            $error = "Failed to load sections.";
            $currentSemesterSections = [];
            $groupedCurrentSections = [
                '1st Year' => [],
                '2nd Year' => [],
                '3rd Year' => [],
                '4th Year' => []
            ];
            $previousSections = [];
            $groupedPreviousSections = [];
            require_once __DIR__ . '/../views/chair/sections.php';
        }
    }

    private function autoTransitionSections($departmentId, $currentSemester)
    {
        try {
            error_log("autoTransitionSections: Starting auto-transition for department $departmentId");

            // Deactivate sections not in the current semester
            $query = "
                                    UPDATE sections
                                    SET is_active = 0, updated_at = NOW()
                                    WHERE department_id = :department_id
                                    AND is_active = 1
                                    AND semester_id != :semester_id
                                    ";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':department_id' => $departmentId,
                ':semester_id' => $currentSemester['semester_id']
            ]);

            $transitionedCount = $stmt->rowCount();

            if ($transitionedCount > 0) {
                error_log("autoTransitionSections: Transitioned $transitionedCount sections to inactive for department $departmentId");
                if (!isset($_SESSION['auto_transition_notified'])) {
                    $_SESSION['info'] = "Sections from previous semesters have been automatically archived.";
                    $_SESSION['auto_transition_notified'] = true;
                }
            }
        } catch (PDOException $e) {
            error_log("autoTransitionSections: Error - " . $e->getMessage());
        }
    }

    private function addSection($departmentId)
    {
        error_log("addSection: Starting add section process for department $departmentId");
        try {
            if (!isset($_POST['add_section'])) {
                $_SESSION['error'] = "Invalid form submission.";
                error_log("addSection: Missing add_section field");
                header('Location: /chair/sections');
                exit;
            }

            $sectionName = trim($_POST['section_name'] ?? '');
            $yearLevel = trim($_POST['year_level'] ?? '');
            $maxStudents = (int)($_POST['max_students'] ?? 40);
            $currentStudents =(int)($_POST['current_students'] ?? 0);

            // Input validation
            if (!$sectionName || !$yearLevel || $maxStudents < 1 || $maxStudents > 100) {
                $_SESSION['error'] = "Invalid input data. Please provide section name, year level, and valid max students (1-100).";
                error_log("addSection: Validation failed - sectionName: '$sectionName', yearLevel: '$yearLevel', maxStudents: $maxStudents");
                header('Location: /chair/sections');
                exit;
            }

            // Validate year level
            $validYearLevels = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
            if (!in_array($yearLevel, $validYearLevels)) {
                $_SESSION['error'] = "Invalid year level selected.";
                error_log("addSection: Invalid year level: $yearLevel");
                header('Location: /chair/sections');
                exit;
            }

            // Fetch current semester
            $currentSemester = $this->getCurrentSemester();
            if (!$currentSemester) {
                $_SESSION['error'] = "Current semester not set. Please contact your administrator.";
                error_log("addSection: No current semester found");
                header('Location: /chair/sections');
                exit;
            }

            $semesterId = $currentSemester['semester_id'];
            $semesterName = $currentSemester['semester_name'];
            $academicYear = $currentSemester['academic_year'];

            // Validate semester_name
            $validSemesters = ['1st', '2nd', 'Summer', 'Mid Year'];
            if (!in_array($semesterName, $validSemesters)) {
                $_SESSION['error'] = "Invalid semester name in current semester.";
                error_log("addSection: Invalid semester name: $semesterName");
                header('Location: /chair/sections');
                exit;
            }

            error_log("addSection: Adding section '$sectionName' for department $departmentId, semester_id $semesterId, semester $semesterName, academic_year $academicYear");

            // Start transaction
            $this->db->beginTransaction();

            // Check for duplicate section
            $query = "
                    SELECT COUNT(*)
                    FROM sections
                    WHERE department_id = :department_id
                    AND section_name = :section_name
                    AND academic_year = :academic_year
                    AND is_active = 1
                    ";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':department_id' => $departmentId,
                ':section_name' => $sectionName,
                ':academic_year' => $academicYear
            ]);

            if ($stmt->fetchColumn() > 0) {
                $this->db->rollBack();
                $_SESSION['error'] = "A section with the name '$sectionName' already exists in this academic year.";
                error_log("addSection: Duplicate section name '$sectionName' for academic year $academicYear");
                header('Location: /chair/sections');
                exit;
            }

            // Insert new section
            $query = "
                    INSERT INTO sections (
                    department_id, section_name, year_level, max_students, current_students,
                    semester_id, semester, academic_year, is_active, created_at
                    ) VALUES (
                    :department_id, :section_name, :year_level, :max_students, :current_students,
                    :semester_id, :semester, :academic_year, 1, NOW()
                    )
                    ";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':department_id' => $departmentId,
                ':section_name' => $sectionName,
                ':year_level' => $yearLevel,
                ':max_students' => $maxStudents,
                ':current_students' => $currentStudents,
                ':semester_id' => $semesterId,
                ':semester' => $semesterName,
                ':academic_year' => $academicYear
            ]);

            // Commit transaction
            $this->db->commit();

            $_SESSION['success'] = "Section '$sectionName' added successfully.";
            error_log("addSection: Successfully added section '$sectionName' with semester_id $semesterId");
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("addSection: PDO Error - " . $e->getMessage());
            $_SESSION['error'] = "Failed to add section: " . $e->getMessage();
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("addSection: General Error - " . $e->getMessage());
            $_SESSION['error'] = "Failed to add section: " . $e->getMessage();
        }

        header('Location: /chair/sections');
        exit;
    }

    private function editSection($departmentId)
    {
        error_log("editSection: Starting edit section process for department $departmentId");
        try {
            if (!isset($_POST['edit_section'])) {
                $_SESSION['error'] = "Invalid form submission.";
                error_log("editSection: Missing edit_section field");
                header('Location: /chair/sections');
                exit;
            }

            $sectionId = (int)($_POST['section_id'] ?? 0);
            $sectionName = trim($_POST['section_name'] ?? '');
            $yearLevel = trim($_POST['year_level'] ?? '');
            $maxStudents = (int)($_POST['max_students'] ?? 40);
            $currentStudents = (int)($_POST['current_students'] ?? 0);

            // Validation
            $errors = [];
            if ($sectionId <= 0) {
                $errors[] = "Invalid section ID.";
            }
            if (empty($sectionName)) {
                $errors[] = "Section name is required.";
            }
            if (!in_array($yearLevel, ['1st Year', '2nd Year', '3rd Year', '4th Year'])) {
                $errors[] = "Invalid year level.";
            }
            if ($maxStudents < 1 || $maxStudents > 100) {
                $errors[] = "Max students must be between 1 and 100.";
            }

            if (!empty($errors)) {
                error_log("editSection: Validation errors - " . implode(", ", $errors));
                $_SESSION['error'] = implode(" ", $errors);
                header('Location: /chair/sections');
                exit;
            }

            // Check if section exists, is active, and belongs to the department
            $query = "
                            SELECT section_name, semester, academic_year, semester_id
                            FROM sections
                            WHERE section_id = :section_id
                            AND department_id = :department_id
                            AND is_active = 1
                            ";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':section_id' => $sectionId,
                ':department_id' => $departmentId
            ]);
            $section = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$section) {
                error_log("editSection: Section ID $sectionId not found or inactive for department_id: $departmentId");
                $_SESSION['error'] = "Section not found or not accessible.";
                header('Location: /chair/sections');
                exit;
            }

            // Check for duplicate section name (excluding current section)
            $query = "
                            SELECT COUNT(*)
                            FROM sections
                            WHERE department_id = :department_id
                            AND section_name = :section_name
                            AND academic_year = :academic_year
                            AND is_active = 1
                            AND section_id != :section_id
                            ";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':department_id' => $departmentId,
                ':section_name' => $sectionName,
                ':academic_year' => $section['academic_year'],
                ':section_id' => $sectionId
            ]);

            if ($stmt->fetchColumn() > 0) {
                $_SESSION['error'] = "A section with this name already exists for this academic year.";
                error_log("editSection: Duplicate section name '$sectionName' for academic year {$section['academic_year']}");
                header('Location: /chair/sections');
                exit;
            }

            // Update section
            $query = "
                            UPDATE sections
                            SET
                            section_name = :section_name,
                            year_level = :year_level,
                            max_students = :max_students,
                            current_students = :current_students,
                            updated_at = NOW()
                            WHERE section_id = :section_id
                            ";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':section_name' => $sectionName,
                ':year_level' => $yearLevel,
                ':current_students' => $currentStudents,
                ':max_students' => $maxStudents,
                ':section_id' => $sectionId
            ]);

            $_SESSION['success'] = "Section '$sectionName' updated successfully.";
            error_log("editSection: Section ID $sectionId updated to '$sectionName' for department_id: $departmentId");
        } catch (PDOException $e) {
            error_log("editSection: PDO Error - " . $e->getMessage());
            $_SESSION['error'] = "Failed to update section: " . $e->getMessage();
        } catch (Exception $e) {
            error_log("editSection: General Error - " . $e->getMessage());
            $_SESSION['error'] = "Failed to update section: " . $e->getMessage();
        }

        header('Location: /chair/sections');
        exit;
    }

    private function removeSection()
    {
        error_log("removeSection: Starting remove section process at " . date('Y-m-d H:i:s'));
        try {
            if (!isset($_POST['remove_section'])) {
                $_SESSION['error'] = "Invalid form submission.";
                error_log("removeSection: Missing remove_section field");
                header('Location: /chair/sections');
                exit;
            }

            $sectionId = (int)($_POST['section_id'] ?? 0);
            $chairId = $_SESSION['user_id'] ?? null;
            $departmentId = $this->getChairDepartment($chairId);

            if ($sectionId <= 0 || !$chairId) {
                error_log("removeSection: Invalid section ID: $sectionId or chair ID: $chairId");
                $_SESSION['error'] = "Invalid section or user.";
                header('Location: /chair/sections');
                exit;
            }

            // Validate section belongs to the chair's department
            $query = "
            SELECT section_name 
            FROM sections 
            WHERE section_id = :section_id 
            AND department_id = :department_id 
            AND is_active = 1
        ";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':section_id' => $sectionId,
                ':department_id' => $departmentId
            ]);
            $section = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$section) {
                error_log("removeSection: Section ID $sectionId not found or not accessible for department_id: $departmentId");
                $_SESSION['error'] = "Section not found or not accessible.";
                header('Location: /chair/sections');
                exit;
            }

            // Start transaction
            $this->db->beginTransaction();

            // Soft delete section
            $query = "
                                UPDATE sections
                                SET is_active = 0, updated_at = NOW()
                                WHERE section_id = :section_id
                                ";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':section_id' => $sectionId]);

            // Commit transaction
            $this->db->commit();

            $_SESSION['success'] = "Section '{$section['section_name']}' archived successfully.";
            error_log("removeSection: Section ID $sectionId ('{$section['section_name']}') archived");
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("removeSection: PDO Error - " . $e->getMessage());
            $_SESSION['error'] = "Failed to archive section: " . $e->getMessage();
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("removeSection: General Error - " . $e->getMessage());
            $_SESSION['error'] = "Failed to archive section: " . $e->getMessage();
        }

        header('Location: /chair/sections?refresh=1');
        exit;
    }

    private function reuseSection($departmentId)
    {
        error_log("reuseSection: Starting reuse section process for department $departmentId");
        try {
            if (!isset($_POST['reuse_section'])) {
                $_SESSION['error'] = "Invalid form submission.";
                error_log("reuseSection: Missing reuse_section field");
                header('Location: /chair/sections');
                exit;
            }

            $sectionId = (int)($_POST['section_id'] ?? 0);

            // Input validation
            if ($sectionId <= 0) {
                $_SESSION['error'] = "Invalid section selected.";
                error_log("reuseSection: Invalid section ID: $sectionId");
                header('Location: /chair/sections');
                exit;
            }

            // Fetch the section to reuse
            $query = "
            SELECT section_name, year_level, max_students, semester_id, semester, academic_year
            FROM sections
            WHERE section_id = :section_id AND department_id = :department_id
        ";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':section_id' => $sectionId,
                ':department_id' => $departmentId
            ]);
            $section = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$section) {
                $_SESSION['error'] = "Selected section not found.";
                error_log("reuseSection: Section ID $sectionId not found for department $departmentId");
                header('Location: /chair/sections');
                exit;
            }

            // Fetch current semester
            $currentSemester = $this->getCurrentSemester();
            if (!$currentSemester) {
                $_SESSION['error'] = "Current semester not set. Please contact your administrator.";
                error_log("reuseSection: No current semester found");
                header('Location: /chair/sections');
                exit;
            }

            $semesterId = $currentSemester['semester_id'];
            $semesterName = $currentSemester['semester_name'];
            $academicYear = $currentSemester['academic_year'];

            // Validate semester_name
            $validSemesters = ['1st', '2nd', 'Summer', 'Mid Year'];
            if (!in_array($semesterName, $validSemesters)) {
                $_SESSION['error'] = "Invalid semester name in current semester.";
                error_log("reuseSection: Invalid semester name: $semesterName");
                header('Location: /chair/sections');
                exit;
            }

            // Check for duplicate section in current semester
            $query = "
        SELECT COUNT(*)
        FROM sections
        WHERE department_id = :department_id
        AND section_name = :section_name
        AND academic_year = :academic_year
        AND is_active = 1
        ";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':department_id' => $departmentId,
                ':section_name' => $section['section_name'],
                ':academic_year' => $academicYear
            ]);

            if ($stmt->fetchColumn() > 0) {
                $_SESSION['error'] = "A section with the name '{$section['section_name']}' already exists in the current academic year.";
                error_log("reuseSection: Duplicate section name '{$section['section_name']}' for academic year $academicYear");
                header('Location: /chair/sections');
                exit;
            }

            // Start transaction
            $this->db->beginTransaction();

            // Insert new section for current semester
            $query = "
        INSERT INTO sections (
        department_id, section_name, year_level, max_students,
        semester_id, semester, academic_year, is_active, created_at
        ) VALUES (
        :department_id, :section_name, :year_level, :max_students,
        :semester_id, :semester, :academic_year, 1, NOW()
        )
        ";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':department_id' => $departmentId,
                ':section_name' => $section['section_name'],
                ':year_level' => $section['year_level'],
                ':max_students' => $section['max_students'],
                ':semester_id' => $semesterId,
                ':semester' => $semesterName,
                ':academic_year' => $academicYear
            ]);

            // Commit transaction
            $this->db->commit();

            $_SESSION['success'] = "Section '{$section['section_name']}' reused successfully for the current semester.";
            error_log("reuseSection: Successfully reused section ID $sectionId as '{$section['section_name']}' with semester_id $semesterId");
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("reuseSection: PDO Error - " . $e->getMessage());
            $_SESSION['error'] = "Failed to reuse section: " . $e->getMessage();
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("reuseSection: General Error - " . $e->getMessage());
            $_SESSION['error'] = "Failed to reuse section: " . $e->getMessage();
        }

        header('Location: /chair/sections');
        exit;
    }

    private function reuseAllSections($departmentId)
    {
        error_log("reuseAllSections: Starting reuse all sections process for department $departmentId");
        try {
            if (!isset($_POST['reuse_all_sections'])) {
                $_SESSION['error'] = "Invalid form submission.";
                error_log("reuseAllSections: Missing reuse_all_sections field");
                header('Location: /chair/sections');
                exit;
            }

            $semesterKey = $_POST['reuse_all_sections'];
            if (empty($semesterKey)) {
                $_SESSION['error'] = "No semester selected for reuse.";
                error_log("reuseAllSections: No semester selected");
                header('Location: /chair/sections');
                exit;
            }

            // Split semesterKey into semester_name and academic_year
            $parts = explode(' ', $semesterKey, 2);
            if (count($parts) !== 2) {
                $_SESSION['error'] = "Invalid semester format.";
                header('Location: /chair/sections');
                exit;
            }

            list($semesterName, $academicYear) = $parts;

            // Fetch semester_id for the selected semester
            $query = "
            SELECT semester_id
            FROM semesters
            WHERE semester_name = :semester_name AND academic_year = :academic_year
        ";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':semester_name' => $semesterName,
                ':academic_year' => $academicYear
            ]);
            $semester = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$semester) {
                $_SESSION['error'] = "Selected semester not found.";
                error_log("reuseAllSections: Semester $semesterKey not found");
                header('Location: /chair/sections');
                exit;
            }

            $semesterId = $semester['semester_id'];

            // Fetch all sections for the selected semester and department
            $query = "
            SELECT section_id, section_name, year_level, max_students
            FROM sections
            WHERE department_id = :department_id AND semester_id = :semester_id
        ";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':department_id' => $departmentId,
                ':semester_id' => $semesterId
            ]);
            $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($sections)) {
                $_SESSION['error'] = "No sections found for the selected semester.";
                error_log("reuseAllSections: No sections found for semester $semesterKey, department $departmentId");
                header('Location: /chair/sections');
                exit;
            }

            // Get current semester
            $currentSemester = $this->getCurrentSemester();
            if (!$currentSemester) {
                $_SESSION['error'] = "Current semester not set. Please contact your administrator.";
                error_log("reuseAllSections: No current semester found");
                header('Location: /chair/sections');
                exit;
            }

            $currentSemesterId = $currentSemester['semester_id'];
            $currentSemesterName = $currentSemester['semester_name'];
            $currentAcademicYear = $currentSemester['academic_year'];

            // Start transaction
            $this->db->beginTransaction();

            $reusedCount = 0;
            $duplicateCount = 0;

            foreach ($sections as $section) {
                // Check for duplicate section in current semester
                $query = "
                SELECT COUNT(*)
                FROM sections
                WHERE department_id = :department_id
                AND section_name = :section_name
                AND academic_year = :academic_year
                AND is_active = 1
            ";
                $stmt = $this->db->prepare($query);
                $stmt->execute([
                    ':department_id' => $departmentId,
                    ':section_name' => $section['section_name'],
                    ':academic_year' => $currentAcademicYear
                ]);

                if ($stmt->fetchColumn() > 0) {
                    $duplicateCount++;
                    continue; // Skip this section, it already exists
                }

                // Insert new section for current semester
                $query = "
                INSERT INTO sections (
                    department_id, section_name, year_level, max_students, current_students,
                    semester_id, semester, academic_year, is_active, created_at
                ) VALUES (
                    :department_id, :section_name, :year_level, :max_students, 0,
                    :semester_id, :semester, :academic_year, 1, NOW()
                )
            ";
                $stmt = $this->db->prepare($query);
                $stmt->execute([
                    ':department_id' => $departmentId,
                    ':section_name' => $section['section_name'],
                    ':year_level' => $section['year_level'],
                    ':max_students' => $section['max_students'],
                    ':semester_id' => $currentSemesterId,
                    ':semester' => $currentSemesterName,
                    ':academic_year' => $currentAcademicYear
                ]);

                $reusedCount++;
            }

            // Commit transaction
            $this->db->commit();

            $message = "Successfully reused $reusedCount sections from $semesterKey.";
            if ($duplicateCount > 0) {
                $message .= " $duplicateCount sections were skipped because they already exist.";
            }

            $_SESSION['success'] = $message;
            error_log("reuseAllSections: Successfully reused $reusedCount sections from $semesterKey for department $departmentId. $duplicateCount duplicates skipped.");
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("reuseAllSections: PDO Error - " . $e->getMessage());
            $_SESSION['error'] = "Failed to reuse sections: " . $e->getMessage();
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("reuseAllSections: General Error - " . $e->getMessage());
            $_SESSION['error'] = "Failed to reuse sections: " . $e->getMessage();
        }

        header('Location: /chair/sections');
        exit;
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
        error_log("curriculum: Starting curriculum method");
        try {
            $chairId = $_SESSION['user_id'] ?? 0;
            $departmentId = $this->getChairDepartment($chairId);
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
        error_log("courses: Starting courses method at " . date('Y-m-d H:i:s'));
        try {
            $chairId = $_SESSION['user_id'] ?? 0;
            $departmentId = $this->getChairDepartment($chairId);

            // Initialize variables
            $error = null;
            $success = null;
            $courses = [];
            $editCourse = null;
            $totalCourses = 0;
            $totalPages = 1;
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

            if (!$departmentId) {
                error_log("courses: No department found for chairId: $chairId");
                $error = "No department assigned to this chair.";
                require_once __DIR__ . '/../views/chair/courses.php';
                return;
            }

            // Pagination settings
            $perPage = 100;
            $offset = ($page - 1) * $perPage;

            // Handle form submissions for adding/editing courses
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
                        'lecture_hours' => intval($_POST['lecture_hours'] ?? 0), // Will be overridden
                        'lab_hours' => intval($_POST['lab_hours'] ?? 0),        // Will be overridden
                        'is_active' => isset($_POST['is_active']) ? 1 : 0
                    ];

                    // Automatically calculate hours based on units
                    $data['lecture_hours'] = $data['lecture_units'] * 1; // 1 lecture unit = 1 hour
                    $data['lab_hours'] = $data['lab_units'] * 2;        // 1 lab unit = 2 hours

                    $errors = [];
                    if (empty($data['course_code'])) $errors[] = "Course code is required.";
                    if (empty($data['course_name'])) $errors[] = "Course name is required.";
                    if ($data['units'] < 1) $errors[] = "Units must be at least 1.";
                    if (!in_array($data['subject_type'], ['Professional Course', 'General Education'])) {
                        $errors[] = "Invalid subject type.";
                    }

                    // Check if course code already exists
                    $codeCheckStmt = $this->db->prepare("SELECT course_id FROM courses WHERE course_code = :course_code AND course_id != :course_id");
                    $codeCheckStmt->execute(['course_code' => $data['course_code'], 'course_id' => $courseId]);
                    if ($codeCheckStmt->fetchColumn()) {
                        $errors[] = "Course code already exists.";
                    }

                    if (empty($errors)) {
                        if ($courseId > 0) {
                            $stmt = $this->db->prepare("
                            UPDATE courses SET 
                                course_code = :course_code, 
                                course_name = :course_name, 
                                department_id = :department_id, 
                                subject_type = :subject_type,
                                units = :units, 
                                lecture_units = :lecture_units,
                                lab_units = :lab_units,
                                lecture_hours = :lecture_hours, 
                                lab_hours = :lab_hours, 
                                is_active = :is_active 
                            WHERE course_id = :course_id
                        ");
                            $updateParams = [
                                'course_code' => $data['course_code'],
                                'course_name' => $data['course_name'],
                                'department_id' => $departmentId,
                                'subject_type' => $data['subject_type'],
                                'units' => $data['units'],
                                'lecture_units' => $data['lecture_units'],
                                'lab_units' => $data['lab_units'],
                                'lecture_hours' => $data['lecture_hours'],
                                'lab_hours' => $data['lab_hours'],
                                'is_active' => $data['is_active'],
                                'course_id' => $courseId
                            ];
                            error_log("Updating course: Query = UPDATE courses ..., Params = " . json_encode($updateParams));
                            $stmt->execute($updateParams);
                            $success = "Course updated successfully.";
                            $this->logActivity($chairId, $departmentId, 'Update Course', "Updated course ID $courseId", 'courses', $courseId);
                        } else {
                            $stmt = $this->db->prepare("
                            INSERT INTO courses 
                                (course_code, course_name, department_id, subject_type, units, 
                                lecture_units, lab_units, lecture_hours, lab_hours, is_active) 
                            VALUES 
                                (:course_code, :course_name, :department_id, :subject_type, :units, 
                                :lecture_units, :lab_units, :lecture_hours, :lab_hours, :is_active)
                        ");
                            $insertParams = [
                                'course_code' => $data['course_code'],
                                'course_name' => $data['course_name'],
                                'department_id' => $departmentId,
                                'subject_type' => $data['subject_type'],
                                'units' => $data['units'],
                                'lecture_units' => $data['lecture_units'],
                                'lab_units' => $data['lab_units'],
                                'lecture_hours' => $data['lecture_hours'],
                                'lab_hours' => $data['lab_hours'],
                                'is_active' => $data['is_active']
                            ];
                            error_log("Inserting course: Query = INSERT INTO courses ..., Params = " . json_encode($insertParams));
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

            // Handle status toggle (including deletion logic)
            if (isset($_GET['toggle_status']) && $_GET['toggle_status'] > 0) {
                try {
                    $courseId = intval($_GET['toggle_status']);
                    $currentStatusStmt = $this->db->prepare("SELECT is_active FROM courses WHERE course_id = :course_id AND department_id = :department_id");
                    $currentStatusStmt->execute(['course_id' => $courseId, 'department_id' => $departmentId]);
                    $currentStatus = $currentStatusStmt->fetchColumn();

                    $toggleStmt = $this->db->prepare("
                    UPDATE courses 
                    SET is_active = NOT is_active 
                    WHERE course_id = :course_id 
                    AND department_id = :department_id
                ");
                    $toggleParams = [
                        'course_id' => $courseId,
                        'department_id' => $departmentId
                    ];
                    error_log("Toggling status: Query = UPDATE courses ..., Params = " . json_encode($toggleParams));
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

            // Handle search
            $searchTerm = ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search']) && trim($_GET['search']) !== '') ? '%' . trim($_GET['search']) . '%' : null;

            // Fetch total number of courses for pagination
            $totalQuery = "SELECT COUNT(*) FROM courses c WHERE c.department_id = :department_id";
            $totalParams = [':department_id' => $departmentId];
            if ($searchTerm) {
                $totalQuery .= " AND (c.course_code LIKE :search OR c.course_name LIKE :search)";
                $totalParams[':search'] = $searchTerm;
            }
            $totalStmt = $this->db->prepare($totalQuery);
            error_log("Total courses query: Query = $totalQuery, Params = " . json_encode($totalParams));
            $totalStmt->execute($totalParams);
            $totalCourses = $totalStmt->fetchColumn();
            $totalPages = max(1, ceil($totalCourses / $perPage));

            // Fetch courses with pagination
            $coursesQuery = "SELECT c.*, d.department_name FROM courses c LEFT JOIN departments d ON c.department_id = d.department_id WHERE c.department_id = :department_id";
            $coursesParams = [':department_id' => $departmentId, ':offset' => $offset, ':perPage' => $perPage];
            if ($searchTerm) {
                $coursesQuery .= " AND (c.course_code LIKE :search OR c.course_name LIKE :search)";
                $coursesParams[':search'] = $searchTerm;
            }
            $coursesQuery .= " ORDER BY c.course_code LIMIT :offset, :perPage";
            $coursesStmt = $this->db->prepare($coursesQuery);
            error_log("Courses query: Query = $coursesQuery, Params = " . json_encode($coursesParams));
            $coursesStmt->execute($coursesParams);
            if ($coursesStmt->execute()) {
                $courses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                throw new PDOException("Courses query failed: " . implode(', ', $coursesStmt->errorInfo()));
            }

            // Fetch course data for editing
            if (isset($_GET['edit']) && $_GET['edit'] > 0) {
                try {
                    $courseId = intval($_GET['edit']);
                    $editStmt = $this->db->prepare("
                    SELECT c.* 
                    FROM courses c 
                    WHERE c.course_id = :course_id 
                    AND c.department_id = :department_id
                ");
                    $editParams = ['course_id' => $courseId, 'department_id' => $departmentId];
                    error_log("Edit course query: Query = SELECT c.* ..., Params = " . json_encode($editParams));
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
        } catch (PDOException $e) {
            error_log("courses: Error - " . $e->getMessage());
            $error = "Failed to load courses: " . $e->getMessage();
            $totalCourses = 0;
            $totalPages = 1;
        }
        require_once __DIR__ . '/../views/chair/courses.php';
    }

    // New helper method to log activity
    private function logActivity($userId, $departmentId, $actionType, $actionDescription, $entityType, $entityId, $metadataId = null)
    {
        try {
            $stmt = $this->db->prepare("
            INSERT INTO activity_logs 
            (user_id, department_id, action_type, action_description, entity_type, entity_id, metadata_id, created_at) 
            VALUES (:user_id, :department_id, :action_type, :action_description, :entity_type, :entity_id, :metadata_id, NOW())
        ");
            $params = [
                ':user_id' => $userId,
                ':department_id' => $departmentId,
                ':action_type' => $actionType,
                ':action_description' => $actionDescription,
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
            error_log("search: Processing for chair_id=$chairId");

            $departmentId = $this->getChairDepartment($chairId);
            if (!$departmentId) {
                error_log("search: No department found for chair_id=$chairId");
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['error' => 'No department assigned']);
                exit;
            }
            error_log("search: Department ID: $departmentId");

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
            error_log("search: College ID: $collegeId");

            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            error_log("search: Search parameter - name: '$name'");

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
                u.title,
                u.first_name,
                u.middle_name,
                u.last_name,
                u.suffix,
                r.role_name,
                f.academic_rank,
                f.employment_type,
                d.department_name,
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
                u.role_id IN (1, 2, 3, 4, 5, 6) -- Include Program Chairs (5) and Faculty (6)
                AND (u.first_name LIKE :name1 OR u.last_name LIKE :name2 OR CONCAT(u.first_name, ' ', u.last_name) LIKE :name3)
            ORDER BY u.last_name, u.first_name
            LIMIT 10";
            $params = [
                ':department_id' => $departmentId,
                ':name1' => "%$name%",
                ':name2' => "%$name%",
                ':name3' => "%$name%"
            ];

            error_log("search: Executing query: $query");
            error_log("search: Query parameters: " . json_encode($params));
            $stmt = $this->db->prepare($query);
            if (!$stmt) {
                $errorInfo = $this->db->errorInfo();
                error_log("search: Prepare Error: " . print_r($errorInfo, true));
                throw new Exception("Failed to prepare statement: " . $errorInfo[2]);
            }
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("search: Found " . count($results) . " results");

            // Query for includable users (role_id 5 or 6, not in current department, any college)
            $includableQuery = "
            SELECT 
                u.user_id,
                u.employee_id,
                u.title,
                u.first_name,
                u.middle_name,
                u.last_name,
                u.suffix,
                r.role_name,
                f.academic_rank,
                f.employment_type,
                d.department_name,
                c.college_name,
                GROUP_CONCAT(d.department_id SEPARATOR ', ') AS department_ids
            FROM 
                users u
                LEFT JOIN roles r ON u.role_id = r.role_id
                LEFT JOIN faculty f ON u.user_id = f.user_id
                LEFT JOIN faculty_departments fd ON f.faculty_id = fd.faculty_id
                LEFT JOIN departments d ON fd.department_id = d.department_id
                LEFT JOIN colleges c ON d.college_id = c.college_id OR u.college_id = c.college_id
            WHERE 
                u.role_id IN (1, 2, 3, 4, 5, 6) -- Include Program Chairs (5) and Faculty (6)
                AND u.user_id NOT IN (
                    SELECT u.user_id 
                    FROM users u
                    LEFT JOIN faculty f ON u.user_id = f.user_id
                    JOIN faculty_departments fd ON f.faculty_id = fd.faculty_id 
                    WHERE fd.department_id = :department_id
                )
                AND (u.first_name LIKE :name1 OR u.last_name LIKE :name2 OR CONCAT(u.first_name, ' ', u.last_name) LIKE :name3)
            GROUP BY 
                u.user_id,
                u.employee_id,
                u.first_name,
                u.last_name,
                r.role_name,
                f.academic_rank,
                f.employment_type,
                d.department_name,
                c.college_name
            ORDER BY u.last_name, u.first_name
            LIMIT 10";
            $includableParams = [
                ':department_id' => $departmentId,
                ':name1' => "%$name%",
                ':name2' => "%$name%",
                ':name3' => "%$name%"
            ];

            error_log("search: Executing includable query: $includableQuery");
            error_log("search: Includable query parameters: " . json_encode($includableParams));
            $includableStmt = $this->db->prepare($includableQuery);
            if (!$includableStmt) {
                $errorInfo = $this->db->errorInfo();
                error_log("search: Includable Prepare Error: " . print_r($errorInfo, true));
                throw new Exception("Failed to prepare includable statement: " . $errorInfo[2]);
            }
            $includableStmt->execute($includableParams);
            $includableResults = $includableStmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("search: Found " . count($includableResults) . " includable results");

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
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
        }

        $chairId = $_SESSION['user_id'] ?? null;
        $departmentId = $this->getChairDepartment($chairId);

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

        $fetchFaculty = function ($collegeId, $departmentId) {
            $baseUrl = $this->baseUrl;
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
                c.course_name AS specialization,
                COALESCE(u.profile_picture, '/uploads/profiles/') AS profile_picture,
                GROUP_CONCAT(d.department_name SEPARATOR ', ') AS department_names, 
                c2.college_name
            FROM 
                faculty f 
                JOIN users u ON f.user_id = u.user_id 
                JOIN faculty_departments fd ON f.faculty_id = fd.faculty_id
                JOIN departments d ON fd.department_id = d.department_id
                JOIN colleges c2 ON d.college_id = c2.college_id
                LEFT JOIN specializations s ON f.faculty_id = s.faculty_id 
                AND s.is_primary_specialization = 1
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
                if ($result['profile_picture'] && $result['profile_picture']) {
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

                        $checkStmt = $this->db->prepare("SELECT u.role_id, u.department_id, u.employee_id FROM users u WHERE u.user_id = :user_id");
                        $checkStmt->execute([':user_id' => $userId]);
                        $user = $checkStmt->fetch(PDO::FETCH_ASSOC);
                        error_log("faculty: User check for user_id=$userId: " . print_r($user, true));

                        if (!$user) {
                            throw new Exception("User does not exist.");
                        } elseif ($user['role_id'] != 6) {
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
                                ':max_hours' => 18.00,
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
                                ':max_hours' => 18.00
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
                            ':is_primary' => $hasPrimary ? 0 : 1
                        ]);
                        error_log("faculty: Inserted into faculty_departments for faculty_id=$facultyId, department_id=$departmentId, is_primary=" . ($hasPrimary ? 0 : 1));

                        if (!$hasPrimary) {
                            $updateStmt = $this->db->prepare("
                            UPDATE users 
                            SET department_id = :department_id, college_id = :college_id 
                            WHERE user_id = :user_id");
                            $updateStmt->execute([
                                ':department_id' => $departmentId,
                                ':college_id' => $collegeId,
                                ':user_id' => $userId
                            ]);
                            error_log("faculty: Updated users for user_id=$userId, department_id=$departmentId, college_id=$collegeId");
                        }

                        $this->db->commit();
                        $faculty = $fetchFaculty($collegeId, $departmentId); // Refresh faculty list
                        $response = [
                            'success' => true,
                            'message' => "Faculty member added successfully.",
                            'faculty' => $faculty
                        ];
                        $this->logActivity($chairId, $departmentId, 'Add Faculty', "Added faculty username=" . $_SESSION['username'] . " to department_id=$departmentId", 'faculty_departments', $this->db->lastInsertId());
                        error_log("faculty: Added user_id=$userId to department_id=$departmentId, faculty_id=$facultyId, is_primary=" . ($hasPrimary ? 0 : 1));
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
                            $updateStmt = $this->db->prepare("
                            UPDATE users 
                            SET department_id = NULL 
                            WHERE user_id = :user_id");
                            $updateStmt->execute([':user_id' => $userId]);

                            $newPrimaryStmt = $this->db->prepare("
                            SELECT department_id 
                            FROM faculty_departments 
                            WHERE faculty_id = :faculty_id 
                            LIMIT 1");
                            $newPrimaryStmt->execute([':faculty_id' => $facultyId]);
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

                                $collegeStmt = $this->db->prepare("
                                SELECT college_id 
                                FROM departments 
                                WHERE department_id = :department_id");
                                $collegeStmt->execute([':department_id' => $newPrimaryDept]);
                                $newCollegeId = $collegeStmt->fetchColumn();

                                if ($newCollegeId) {
                                    $updateUserStmt = $this->db->prepare("
                                    UPDATE users 
                                    SET department_id = :department_id, college_id = :college_id 
                                    WHERE user_id = :user_id");
                                    $updateUserStmt->execute([
                                        ':department_id' => $newPrimaryDept,
                                        ':college_id' => $newCollegeId,
                                        ':user_id' => $userId
                                    ]);
                                } else {
                                    error_log("faculty: No college_id found for new primary department $newPrimaryDept");
                                    throw new Exception("Failed to determine new college for faculty.");
                                }
                            } else {
                                error_log("faculty: No new primary department found for faculty_id=$facultyId");
                                $currentCollegeStmt = $this->db->prepare("SELECT college_id FROM users WHERE user_id = :user_id");
                                $currentCollegeStmt->execute([':user_id' => $userId]);
                                $currentCollegeId = $currentCollegeStmt->fetchColumn();
                                if ($currentCollegeId) {
                                    $updateUserStmt = $this->db->prepare("
                                    UPDATE users 
                                    SET department_id = NULL 
                                    WHERE user_id = :user_id");
                                    $updateUserStmt->execute([':user_id' => $userId]);
                                } else {
                                    throw new Exception("No current or new college assignment for faculty.");
                                }
                            }
                        }

                        $this->db->commit();
                        $faculty = $fetchFaculty($collegeId, $departmentId); // Refresh faculty list
                        $response = [
                            'success' => true,
                            'message' => "Faculty member removed successfully.",
                            'faculty' => $faculty
                        ];
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
                            COALESCE(u.profile_picture, '/uploads/profiles/') AS profile_picture,
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
    public function searchCourses()
    {
        try {
            if (!$this->authService->isLoggedIn()) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                exit;
            }

            $query = trim($_GET['query'] ?? '');
            if (strlen($query) < 2) {
                http_response_code(400);
                echo json_encode(['error' => 'Query must be at least 2 characters']);
                exit;
            }

            // Use positional parameters (?) instead
            $stmt = $this->db->prepare("
            SELECT c.course_id, c.course_code, c.course_name, d.department_name, co.college_name
            FROM courses c
            JOIN departments d ON c.department_id = d.department_id
            JOIN colleges co ON d.college_id = co.college_id
            WHERE UPPER(c.course_code) LIKE UPPER(?) OR UPPER(c.course_name) LIKE UPPER(?)
            LIMIT 10
        ");

            $searchTerm = "%" . strtoupper($query) . "%";
            error_log("searchCourses: Preparing query with positional parameters");
            error_log("searchCourses: Search term = $searchTerm");

            // Execute with array of parameters
            $stmt->execute([$searchTerm, $searchTerm]);
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            error_log("searchCourses: Query executed successfully, found " . count($courses) . " results");
            header('Content-Type: application/json');
            echo json_encode($courses);
        } catch (PDOException $e) {
            http_response_code(500);
            error_log("searchCourses: PDO Error - SQLSTATE[" . $e->getCode() . "]: " . $e->getMessage());
            error_log("searchCourses: Query: " . (isset($stmt) ? $stmt->queryString : 'Query not prepared'));
            error_log("searchCourses: Search term: " . (isset($searchTerm) ? $searchTerm : 'Not set'));
            echo json_encode(['error' => 'An error occurred while fetching courses: ' . $e->getMessage()]);
        } catch (Exception $e) {
            http_response_code(500);
            error_log("searchCourses: General Error - " . $e->getMessage());
            echo json_encode(['error' => 'An error occurred while fetching courses']);
        }
        exit;
    }

    public function profile()
    {
        try {
            if (!$this->authService->isLoggedIn()) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Please log in to view your profile'];
                header('Location: /login');
                exit;
            }

            $userId = $_SESSION['user_id'];
            $csrfToken = $this->authService->generateCsrfToken();

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (!$this->authService->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid CSRF token'];
                    header('Location: /chair/profile');
                    exit;
                }

                $data = [
                    'email' => trim($_POST['email'] ?? ''),
                    'phone' => trim($_POST['phone'] ?? ''),
                    'username' => trim($_POST['username'] ?? ''),
                    'first_name' => trim($_POST['first_name'] ?? ''),
                    'middle_name' => trim($_POST['middle_name'] ?? ''),
                    'last_name' => trim($_POST['last_name'] ?? ''),
                    'suffix' => trim($_POST['suffix'] ?? ''),
                    'title' => trim($_POST['title'] ?? ''),
                    'classification' => trim($_POST['classification'] ?? ''),
                    'academic_rank' => trim($_POST['academic_rank'] ?? ''),
                    'employment_type' => trim($_POST['employment_type'] ?? ''),
                    'bachelor_degree' => trim($_POST['bachelor_degree'] ?? ''),
                    'master_degree' => trim($_POST['master_degree'] ?? ''),
                    'doctorate_degree' => trim($_POST['doctorate_degree'] ?? ''),
                    'post_doctorate_degree' => trim($_POST['post_doctorate_degree'] ?? ''),
                    'advisory_class' => trim($_POST['advisory_class'] ?? ''),
                    'designation' => trim($_POST['designation'] ?? ''),
                    'course_id' => trim($_POST['course_id'] ?? ''),
                    'specialization_index' => trim($_POST['specialization_index'] ?? ''),
                    'action' => trim($_POST['action'] ?? ''),
                ];

                $errors = [];

                try {
                    $this->db->beginTransaction();

                    $profilePictureResult = $this->handleProfilePictureUpload($userId);
                    $profilePicturePath = null;

                    if ($profilePictureResult !== null) {
                        if (strpos($profilePictureResult, 'Error:') === 0) {
                            $errors[] = $profilePictureResult;
                        } else {
                            $profilePicturePath = $profilePictureResult;
                        }
                    }

                    // Handle user profile updates only if fields are provided or profile picture uploaded
                    if (
                        !empty($data['email']) || !empty($data['first_name']) || !empty($data['last_name']) ||
                        !empty($data['phone']) || !empty($data['username']) || !empty($data['suffix']) ||
                        !empty($data['title']) || $profilePicturePath
                    ) {
                        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                            $errors[] = 'Valid email is required.';
                        }
                        if (!empty($data['phone']) && !preg_match('/^\d{10,12}$/', $data['phone'])) {
                            $errors[] = 'Phone number must be 10-12 digits.';
                        }

                        if (empty($errors)) {
                            $setClause = [];
                            $params = [':user_id' => $userId];
                            $validFields = ['email', 'phone', 'username', 'first_name', 'middle_name', 'last_name', 'suffix', 'title'];

                            foreach ($validFields as $field) {
                                if (isset($data[$field]) && $data[$field] !== '') {
                                    $setClause[] = "`$field` = :$field";
                                    $params[":$field"] = $data[$field];
                                }
                            }

                            if ($profilePicturePath) {
                                $setClause[] = "`profile_picture` = :profile_picture";
                                $params[":profile_picture"] = $profilePicturePath;
                            }

                            if (!empty($setClause)) {
                                $userStmt = $this->db->prepare("UPDATE users SET " . implode(', ', $setClause) . ", updated_at = NOW() WHERE user_id = :user_id");

                                if (!$userStmt->execute($params)) {
                                    $errorInfo = $userStmt->errorInfo();
                                    error_log("profile: User update failed - " . print_r($errorInfo, true));
                                    throw new Exception("Failed to update user profile");
                                }
                                error_log("profile: User profile updated successfully");
                            }
                        }
                    }

                    // Get faculty ID
                    $facultyStmt = $this->db->prepare("SELECT faculty_id FROM faculty WHERE user_id = :user_id");
                    $facultyStmt->execute([':user_id' => $userId]);
                    $facultyId = $facultyStmt->fetchColumn();
                    error_log("profile: Retrieved faculty_id for user_id $userId: $facultyId");

                    if (!$facultyId) {
                        error_log("profile: No faculty record found for user_id $userId");
                        throw new Exception("Faculty record not found for this user");
                    }

                    // Handle faculty updates
                    if ($facultyId && empty($errors)) {
                        $facultyParams = [':faculty_id' => $facultyId];
                        $facultySetClause = [];
                        $facultyFields = [
                            'academic_rank',
                            'employment_type',
                            'classification',
                            'designation',
                            'advisory_class',
                            'bachelor_degree',
                            'master_degree',
                            'doctorate_degree',
                            'post_doctorate_degree'
                        ];
                        foreach ($facultyFields as $field) {
                            if (isset($data[$field]) && $data[$field] !== '') {
                                $facultySetClause[] = "$field = :$field";
                                $facultyParams[":$field"] = $data[$field];
                            }
                        }

                        if (!empty($facultySetClause)) {
                            $updateFacultyStmt = $this->db->prepare("UPDATE faculty SET " . implode(', ', $facultySetClause) . ", updated_at = NOW() WHERE faculty_id = :faculty_id");
                            error_log("profile: Faculty query - " . $updateFacultyStmt->queryString . ", Params: " . print_r($facultyParams, true));
                            if (!$updateFacultyStmt->execute($facultyParams)) {
                                $errorInfo = $updateFacultyStmt->errorInfo();
                                error_log("profile: Faculty update failed - " . print_r($errorInfo, true));
                                throw new Exception("Failed to update faculty information");
                            }
                        }

                        // Handle specialization actions
                        if (!empty($data['action'])) {
                            switch ($data['action']) {
                                case 'add_specialization':
                                    if (!empty($data['course_id'])) {
                                        // Check if specialization already exists
                                        $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM specializations WHERE faculty_id = :faculty_id AND course_id = :course_id");
                                        $checkStmt->execute([':faculty_id' => $facultyId, ':course_id' => $data['course_id']]);
                                        $exists = $checkStmt->fetchColumn();

                                        if ($exists > 0) {
                                            $errors[] = 'You already have this specialization.';
                                            break;
                                        }

                                        $insertSpecializationStmt = $this->db->prepare("
                                        INSERT INTO specializations (faculty_id, course_id, created_at)
                                        VALUES (:faculty_id, :course_id, NOW())
                                    ");
                                        $specializationParams = [
                                            ':faculty_id' => $facultyId,
                                            ':course_id' => $data['course_id'],
                                        ];
                                        error_log("profile: Add specialization query - " . $insertSpecializationStmt->queryString . ", Params: " . print_r($specializationParams, true));

                                        if (!$insertSpecializationStmt->execute($specializationParams)) {
                                            $errorInfo = $insertSpecializationStmt->errorInfo();
                                            error_log("profile: Add specialization failed - " . print_r($errorInfo, true));
                                            throw new Exception("Failed to add specialization");
                                        }
                                        error_log("profile: Successfully added specialization");
                                    } else {
                                        $errors[] = 'Course is required to add specialization.';
                                    }
                                    break;

                                case 'remove_specialization':
                                    if (!empty($data['course_id'])) {
                                        error_log("profile: Attempting to remove specialization with course_id: " . $data['course_id'] . ", faculty_id: $facultyId");

                                        $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM specializations WHERE faculty_id = :faculty_id AND course_id = :course_id");
                                        $checkStmt->execute([':faculty_id' => $facultyId, ':course_id' => $data['course_id']]);
                                        $recordExists = $checkStmt->fetchColumn();
                                        error_log("profile: Records found for deletion: $recordExists");

                                        if ($recordExists > 0) {
                                            $deleteStmt = $this->db->prepare("DELETE FROM specializations WHERE faculty_id = :faculty_id AND course_id = :course_id");
                                            $deleteParams = [
                                                ':faculty_id' => $facultyId,
                                                ':course_id' => $data['course_id'],
                                            ];
                                            error_log("profile: Remove specialization query - " . $deleteStmt->queryString . ", Params: " . print_r($deleteParams, true));

                                            if ($deleteStmt->execute($deleteParams)) {
                                                $affectedRows = $deleteStmt->rowCount();
                                                error_log("profile: Successfully deleted $affectedRows rows");
                                                if ($affectedRows === 0) {
                                                    error_log("profile: Warning - No rows were affected by delete operation");
                                                    $errors[] = 'No specialization was removed. It may have already been deleted.';
                                                }
                                            } else {
                                                $errorInfo = $deleteStmt->errorInfo();
                                                error_log("profile: Delete failed - " . print_r($errorInfo, true));
                                                throw new Exception("Failed to execute delete query: " . $errorInfo[2]);
                                            }
                                        } else {
                                            error_log("profile: No record found for deletion");
                                            $errors[] = 'Specialization not found for removal.';
                                        }
                                    } else {
                                        $errors[] = 'Course ID is required to remove specialization.';
                                    }
                                    break;

                                case 'edit_specialization':
                                    if (!empty($data['specialization_index'])) {
                                        error_log("profile: Edit specialization triggered for index: " . $data['specialization_index']);
                                        // No database update needed here, just trigger the modal
                                    }
                                    break;

                                default:
                                    error_log("profile: Unknown action: " . $data['action']);
                                    break;
                            }
                        }
                    }

                    // If there are validation errors, rollback and don't commit
                    if (!empty($errors)) {
                        $this->db->rollBack();
                        error_log("profile: Validation errors found, rolling back transaction: " . implode(', ', $errors));
                    } else {
                        $this->db->commit();
                        error_log("profile: Transaction committed successfully");

                        $_SESSION['username'] = $data['username'] ?: $_SESSION['username'];
                        $_SESSION['last_name'] = $data['last_name'] ?: $_SESSION['last_name'];
                        $_SESSION['middle_name'] = $data['middle_name'] ?: $_SESSION['middle_name'];
                        $_SESSION['suffix'] = $data['suffix'] ?: $_SESSION['suffix'];
                        $_SESSION['title'] = $data['title'] ?: $_SESSION['title'];
                        $_SESSION['first_name'] = $data['first_name'] ?: $_SESSION['first_name'];
                        $_SESSION['email'] = $data['email'] ?: $_SESSION['email'];

                        if ($profilePicturePath) {
                            $_SESSION['profile_picture'] = $profilePicturePath;
                            error_log("profile: Updated session profile_picture to: " . $profilePicturePath);
                        }

                        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Profile updated successfully'];
                    }
                } catch (PDOException $e) {
                    if ($this->db->inTransaction()) {
                        $this->db->rollBack();
                    }
                    error_log("profile: PDO Database error - " . $e->getMessage());
                    $errors[] = 'Database error occurred: ' . $e->getMessage();
                } catch (Exception $e) {
                    if ($this->db->inTransaction()) {
                        $this->db->rollBack();
                    }
                    error_log("profile: General error - " . $e->getMessage());
                    $errors[] = $e->getMessage();
                }

                if (!empty($errors)) {
                    $_SESSION['flash'] = ['type' => 'error', 'message' => implode('<br>', $errors)];
                }

                header('Location: /chair/profile');
                exit;
            }

            // GET request - Display profile
            $stmt = $this->db->prepare("
            SELECT u.*, d.department_name, c.college_name, r.role_name,
                   f.academic_rank, f.employment_type, f.classification, f.bachelor_degree, f.master_degree,
                   f.doctorate_degree, f.post_doctorate_degree, f.advisory_class, f.designation
            FROM users u
            LEFT JOIN departments d ON u.department_id = d.department_id
            LEFT JOIN colleges c ON u.college_id = c.college_id
            LEFT JOIN roles r ON u.role_id = r.role_id
            LEFT JOIN faculty f ON u.user_id = f.user_id
            WHERE u.user_id = :user_id
        ");
            $stmt->execute([':user_id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                throw new Exception('User not found.');
            }

            $specializationStmt = $this->db->prepare("
            SELECT s.expertise_level AS level, c.course_code, c.course_name, s.course_id
            FROM specializations s
            JOIN courses c ON s.course_id = c.course_id
            WHERE s.faculty_id = (SELECT faculty_id FROM faculty WHERE user_id = :user_id)
            ORDER BY c.course_code
        ");
            $specializationStmt->execute([':user_id' => $userId]);
            $specializations = $specializationStmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch user data and stats...
            $stmt = $this->db->prepare("
            SELECT u.*, d.department_name, c.college_name, r.role_name,
                   f.academic_rank, f.employment_type, f.classification, f.bachelor_degree, f.master_degree,
                   f.doctorate_degree, f.post_doctorate_degree, f.advisory_class, f.designation,
                   (SELECT COUNT(*) FROM faculty f2 JOIN users fu ON f2.user_id = fu.user_id WHERE fu.department_id = u.department_id) as facultyCount,
                   (SELECT COUNT(DISTINCT sch.course_id) FROM schedules sch WHERE sch.faculty_id = f.faculty_id) as coursesCount,
                   (SELECT COUNT(*) FROM specializations s2 WHERE s2.faculty_id = f.faculty_id) as specializationsCount,
                   (SELECT COUNT(*) FROM faculty_requests fr WHERE fr.department_id = u.department_id AND fr.status = 'pending') as pendingApplicantsCount,
                   (SELECT semester_name FROM semesters WHERE is_current = 1) as currentSemester,
                   (SELECT created_at FROM auth_logs WHERE user_id = u.user_id AND action = 'login_success' ORDER BY created_at DESC LIMIT 1) as lastLogin
            FROM users u
            LEFT JOIN departments d ON u.department_id = d.department_id
            LEFT JOIN colleges c ON u.college_id = c.college_id
            LEFT JOIN roles r ON u.role_id = r.role_id
            LEFT JOIN faculty f ON u.user_id = f.user_id
            WHERE u.user_id = :user_id
        ");
            $stmt->execute([':user_id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                throw new Exception('User not found.');
            }

            // Extract stats
            $facultyCount = $user['facultyCount'] ?? 0;
            $coursesCount = $user['coursesCount'] ?? 0;
            $specializationsCount = $user['specializationsCount'] ?? 0;
            $pendingApplicantsCount = $user['pendingApplicantsCount'] ?? 0;
            $currentSemester = $user['currentSemester'] ?? '2nd';
            $lastLogin = $user['lastLogin'] ?? 'N/A';

            require_once __DIR__ . '/../views/chair/profile.php';
        } catch (Exception $e) {
            if (isset($this->db) && $this->db->inTransaction()) {
                $this->db->rollBack();
            }

            error_log("profile: Error - " . $e->getMessage());
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to load or update profile. Please try again.'];

            $user = [
                'user_id' => $userId ?? 0,
                'username' => '',
                'first_name' => '',
                'last_name' => '',
                'middle_name' => '',
                'suffix' => '',
                'email' => '',
                'phone' => '',
                'title' => '',
                'profile_picture' => '',
                'employee_id' => '',
                'department_name' => '',
                'college_name' => '',
                'role_name' => 'Program Chair',
                'academic_rank' => '',
                'employment_type' => '',
                'classification' => '',
                'bachelor_degree' => '',
                'master_degree' => '',
                'doctorate_degree' => '',
                'post_doctorate_degree' => '',
                'advisory_class' => '',
                'designation' => '',
                'updated_at' => date('Y-m-d H:i:s')
            ];
            $specializations = [];
            require_once __DIR__ . '/../views/chair/profile.php';
        }
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
}
