<?php
require_once __DIR__ . '/../config/Database.php';

class ChairController
{
    private $db;

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

    /**
     * Get department_id for the chair
     */
    private function getChairDepartment($chairId)
    {
        $stmt = $this->db->prepare("SELECT p.department_id 
                                    FROM program_chairs pc 
                                    JOIN programs p ON pc.program_id = p.program_id 
                                    WHERE pc.user_id = :user_id AND pc.is_current = 1");
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
                SELECT c.curriculum_id, c.curriculum_name, c.status, p.program_name 
                FROM curricula c 
                JOIN programs p ON c.department_id = p.department_id 
                WHERE c.department_id = :department_id
                ORDER BY c.curriculum_name
            ");
            $curriculaStmt->execute([':department_id' => $departmentId]);
            $curricula = $curriculaStmt->fetchAll(PDO::FETCH_ASSOC);

            error_log("dashboard: Fetched " . count($curricula) . " curricula");

            // Get recent schedules
            $recentSchedulesStmt = $this->db->prepare("
                SELECT s.schedule_id, c.course_code, CONCAT(u.first_name, ' ', u.last_name) AS faculty_name, 
                       r.room_name, s.day_of_week, s.start_time, s.end_time, s.schedule_type
                FROM schedules s
                JOIN courses c ON s.course_id = c.course_id
                JOIN faculty f ON s.faculty_id = f.faculty_id
                JOIN users u ON f.user_id = u.user_id
                LEFT JOIN classrooms r ON s.room_id = r.room_id
                WHERE c.department_id = :department_id
                ORDER BY s.created_at DESC
                LIMIT 5
            ");
            $recentSchedulesStmt->execute([':department_id' => $departmentId]);
            $recentSchedules = $recentSchedulesStmt->fetchAll(PDO::FETCH_ASSOC);

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

    /**
     * Display Chair's teaching schedule
     */
    public function mySchedule()
    {
        error_log("mySchedule: Starting mySchedule method");
        try {
            $chairId = $_SESSION['user_id'];
            $departmentId = $this->getChairDepartment($chairId);
            if (!$departmentId) {
                error_log("mySchedule: No department found for chairId: $chairId");
                $error = "No department assigned to this chair.";
                require_once __DIR__ . '/../views/chair/my_schedule.php';
                return;
            }

            $facultyStmt = $this->db->prepare("SELECT faculty_id FROM faculty WHERE user_id = :user_id AND department_id = :department_id");
            $facultyStmt->execute([':user_id' => $chairId, ':department_id' => $departmentId]);
            $facultyId = $facultyStmt->fetchColumn();

            if (!$facultyId) {
                error_log("mySchedule: No faculty profile found for user_id: $chairId in department_id: $departmentId");
                $error = "No faculty profile found for this user.";
                require_once __DIR__ . '/../views/chair/my_schedule.php';
                return;
            }

            // Get current semester
            $semesterStmt = $this->db->query("SELECT semester_id FROM semesters WHERE is_current = 1");
            $semesterId = $semesterStmt->fetchColumn();

            error_log("mySchedule: Fetching schedule for faculty_id: $facultyId, semester_id: $semesterId");

            // Call SchedulingService to get faculty schedule
            $response = $this->callSchedulingService('GET', 'faculty-schedule', [
                'facultyId' => $facultyId,
                'semesterId' => $semesterId
            ]);

            if ($response['code'] !== 200) {
                error_log("mySchedule: Failed to fetch schedule - " . ($response['data']['error'] ?? 'Unknown error'));
                throw new Exception("Failed to fetch schedule: " . ($response['data']['error'] ?? 'Unknown error'));
            }

            $schedules = $response['data'];
            require_once __DIR__ . '/../views/chair/my_schedule.php';
        } catch (Exception $e) {
            error_log("mySchedule: Error - " . $e->getMessage());
            $error = "Failed to load schedule.";
            require_once __DIR__ . '/../views/chair/my_schedule.php';
        }
    }

    /**
     * Create a new schedule manually
     */
    public function createSchedule()
    {
        error_log("createSchedule: Starting createSchedule method");
        $chairId = $_SESSION['user_id'];
        $departmentId = $this->getChairDepartment($chairId);

        if (!$departmentId) {
            error_log("createSchedule: No department found for chairId: $chairId");
            $error = "No department assigned to this chair.";
            require_once __DIR__ . '/../views/chair/create_schedule.php';
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $data = [
                    'course_id' => intval($_POST['course_id'] ?? 0),
                    'faculty_id' => intval($_POST['faculty_id'] ?? 0),
                    'room_id' => intval($_POST['room_id'] ?? 0),
                    'section_id' => intval($_POST['section_id'] ?? 0),
                    'schedule_type' => $_POST['schedule_type'] ?? 'F2F',
                    'day_of_week' => $_POST['day_of_week'] ?? '',
                    'start_time' => $_POST['start_time'] ?? '',
                    'end_time' => $_POST['end_time'] ?? '',
                    'semester_id' => intval($_POST['semester_id'] ?? 0)
                ];

                error_log("createSchedule: POST data - " . json_encode($data));

                $errors = [];
                // Validate course belongs to department
                $courseStmt = $this->db->prepare("SELECT course_id FROM courses WHERE course_id = :course_id AND department_id = :department_id");
                $courseStmt->execute([':course_id' => $data['course_id'], ':department_id' => $departmentId]);
                if (!$courseStmt->fetchColumn()) {
                    $errors[] = "Invalid course selected or not in your department.";
                }
                // Validate faculty belongs to department
                $facultyStmt = $this->db->prepare("SELECT f.faculty_id FROM faculty f JOIN users u ON f.user_id = u.user_id 
                                                  WHERE f.faculty_id = :faculty_id AND u.department_id = :department_id");
                $facultyStmt->execute([':faculty_id' => $data['faculty_id'], ':department_id' => $departmentId]);
                if (!$facultyStmt->fetchColumn()) {
                    $errors[] = "Invalid faculty selected or not in your department.";
                }
                // Validate section belongs to department
                $sectionStmt = $this->db->prepare("SELECT section_id FROM sections WHERE section_id = :section_id AND department_id = :department_id");
                $sectionStmt->execute([':section_id' => $data['section_id'], ':department_id' => $departmentId]);
                if (!$courseStmt->fetchColumn()) {
                    $errors[] = "Invalid section selected or not in your department.";
                }
                if (!in_array($data['day_of_week'], ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'])) {
                    $errors[] = "Invalid day of week.";
                }
                if (!preg_match('/^\d{2}:\d{2}$/', $data['start_time']) || !preg_match('/^\d{2}:\d{2}$/', $data['end_time'])) {
                    $errors[] = "Invalid time format.";
                }
                if ($data['semester_id'] < 1) {
                    $errors[] = "Invalid semester selected.";
                }
                if (!in_array($data['schedule_type'], ['F2F', 'Online', 'Hybrid', 'Asynchronous'])) {
                    $errors[] = "Invalid schedule type.";
                }
                if ($data['schedule_type'] !== 'Asynchronous' && $data['room_id'] < 1) {
                    $errors[] = "Room is required for non-asynchronous schedules.";
                }

                if (empty($errors)) {
                    error_log("createSchedule: Validated data, calling SchedulingService");
                    // Call SchedulingService to create schedule
                    $response = $this->callSchedulingService('POST', 'create-schedule', $data);

                    if ($response['code'] !== 200 || !isset($response['data']['success'])) {
                        error_log("createSchedule: Failed to create schedule - " . ($response['data']['error'] ?? 'Unknown error'));
                        throw new Exception($response['data']['error'] ?? "Failed to create schedule.");
                    }

                    error_log("createSchedule: Schedule created successfully");
                    header('Location: /chair/schedule?success=Schedule created successfully');
                    exit;
                } else {
                    error_log("createSchedule: Validation errors - " . implode(", ", $errors));
                    $error = implode("<br>", $errors);
                }
            } catch (Exception $e) {
                error_log("createSchedule: Error - " . $e->getMessage());
                $error = $e->getMessage();
            }
        }

        try {
            error_log("createSchedule: Fetching form data for chairId: $chairId, departmentId: $departmentId");

            $courses = $this->db->query("SELECT course_id, course_code, course_name FROM courses WHERE department_id = " . (int)$departmentId)->fetchAll(PDO::FETCH_ASSOC);
            $faculty = $this->db->query("SELECT f.faculty_id, CONCAT(u.first_name, ' ', u.last_name) AS name 
                                         FROM faculty f JOIN users u ON f.user_id = u.user_id 
                                         WHERE u.department_id = " . (int)$departmentId)->fetchAll(PDO::FETCH_ASSOC);
            $classrooms = $this->db->query("SELECT room_id, room_name FROM classrooms")->fetchAll(PDO::FETCH_ASSOC);
            $sections = $this->db->query("SELECT section_id, section_name FROM sections WHERE department_id = " . (int)$departmentId)->fetchAll(PDO::FETCH_ASSOC);
            $semesters = $this->db->query("SELECT semester_id, CONCAT(semester_name, ' ', academic_year) AS semester_name FROM semesters")->fetchAll(PDO::FETCH_ASSOC);

            error_log("createSchedule: Form data fetched, loading view");
            require_once __DIR__ . '/../views/chair/create_schedule.php';
        } catch (PDOException $e) {
            error_log("createSchedule: Error loading form data - " . $e->getMessage());
            $error = "Failed to load form data.";
            require_once __DIR__ . '/../views/chair/create_schedule.php';
        }
    }

    /**
     * Generate schedules automatically
     */
    public function generateSchedule()
    {
        error_log("generateSchedule: Starting generateSchedule method");
        $chairId = $_SESSION['user_id'];
        $departmentId = $this->getChairDepartment($chairId);

        if (!$departmentId) {
            error_log("generateSchedule: No department found for chairId: $chairId");
            $error = "No department assigned to this chair.";
            require_once __DIR__ . '/../views/chair/generate_schedule.php';
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $semesterId = intval($_POST['semester_id'] ?? 0);
                if ($semesterId < 1) {
                    throw new Exception("Invalid semester selected.");
                }

                error_log("generateSchedule: Generating schedule for department_id: $departmentId, semester_id: $semesterId");

                // Fetch data for scheduling
                $courses = $this->db->query("SELECT course_id, course_code, course_name FROM courses WHERE department_id = " . (int)$departmentId)->fetchAll(PDO::FETCH_ASSOC);
                $faculty = $this->db->query("SELECT f.faculty_id, CONCAT(u.first_name, ' ', u.last_name) AS name 
                                             FROM faculty f JOIN users u ON f.user_id = u.user_id 
                                             WHERE u.department_id = " . (int)$departmentId)->fetchAll(PDO::FETCH_ASSOC);
                $classrooms = $this->db->query("SELECT room_id, room_name FROM classrooms")->fetchAll(PDO::FETCH_ASSOC);
                $sections = $this->db->query("SELECT section_id, section_name FROM sections WHERE department_id = " . (int)$departmentId)->fetchAll(PDO::FETCH_ASSOC);

                // Define time slots (Monday to Saturday, 8:00 AM to 5:00 PM, 2-hour blocks)
                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                $timeSlots = ['08:00', '10:00', '13:00', '15:00'];

                $schedules = [];
                foreach ($sections as $section) {
                    foreach ($courses as $course) {
                        // Randomly assign faculty, room, day, and time
                        $facultyId = $faculty[array_rand($faculty)]['faculty_id'];
                        $roomId = $classrooms[array_rand($classrooms)]['room_id'];
                        $day = $days[array_rand($days)];
                        $startTime = $timeSlots[array_rand($timeSlots)];
                        $endTime = date('H:i', strtotime($startTime . ' +2 hours'));

                        // Check for conflicts
                        $conflictStmt = $this->db->prepare("
                            SELECT COUNT(*) FROM schedules 
                            WHERE semester_id = :semester_id 
                            AND (faculty_id = :faculty_id OR room_id = :room_id)
                            AND day_of_week = :day_of_week
                            AND (
                                (start_time <= :start_time AND end_time > :start_time) OR
                                (start_time < :end_time AND end_time >= :end_time) OR
                                (start_time >= :start_time AND end_time <= :end_time)
                            )
                        ");
                        $conflictStmt->execute([
                            ':semester_id' => $semesterId,
                            ':faculty_id' => $facultyId,
                            ':room_id' => $roomId,
                            ':day_of_week' => $day,
                            ':start_time' => $startTime,
                            ':end_time' => $endTime
                        ]);
                        if ($conflictStmt->fetchColumn() > 0) {
                            error_log("generateSchedule: Conflict detected for course_id: {$course['course_id']}, section_id: {$section['section_id']}");
                            continue; // Skip if conflict exists
                        }

                        // Add schedule
                        $data = [
                            'course_id' => $course['course_id'],
                            'faculty_id' => $facultyId,
                            'room_id' => $roomId,
                            'section_id' => $section['section_id'],
                            'schedule_type' => 'F2F',
                            'day_of_week' => $day,
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                            'semester_id' => $semesterId
                        ];
                        $response = $this->callSchedulingService('POST', 'create-schedule', $data);
                        if ($response['code'] === 200 && isset($response['data']['success'])) {
                            $schedules[] = $data;
                        } else {
                            error_log("generateSchedule: Failed to create schedule for course_id: {$course['course_id']} - " . ($response['data']['error'] ?? 'Unknown error'));
                        }
                    }
                }

                error_log("generateSchedule: Generated " . count($schedules) . " schedules");
                header('Location: /chair/schedule?success=Automatically generated ' . count($schedules) . ' schedules');
                exit;
            } catch (Exception $e) {
                error_log("generateSchedule: Error - " . $e->getMessage());
                $error = $e->getMessage();
            }
        }

        try {
            $semesters = $this->db->query("SELECT semester_id, CONCAT(semester_name, ' ', academic_year) AS semester_name FROM semesters")->fetchAll(PDO::FETCH_ASSOC);
            error_log("generateSchedule: Form data fetched, loading view");
            require_once __DIR__ . '/../views/chair/generate_schedule.php';
        } catch (PDOException $e) {
            error_log("generateSchedule: Error loading form data - " . $e->getMessage());
            $error = "Failed to load form data.";
            require_once __DIR__ . '/../views/chair/generate_schedule.php';
        }
    }

    /**
     * Manage classrooms
     */
    public function classroom()
    {
        error_log("classroom: Starting classroom method");
        try {
            $classrooms = $this->db->query("SELECT * FROM classrooms ORDER BY room_name")->fetchAll(PDO::FETCH_ASSOC);
            require_once __DIR__ . '/../views/chair/classroom.php';
        } catch (PDOException $e) {
            error_log("classroom: Error - " . $e->getMessage());
            $error = "Failed to load classrooms.";
            require_once __DIR__ . '/../views/chair/classroom.php';
        }
    }

    /**
     * Manage sections
     */
    public function sections()
    {
        error_log("sections: Starting sections method");
        try {
            $chairId = $_SESSION['user_id'];
            $departmentId = $this->getChairDepartment($chairId);
            if (!$departmentId) {
                error_log("sections: No department found for chairId: $chairId");
                $error = "No department assigned to this chair.";
                require_once __DIR__ . '/../views/chair/sections.php';
                return;
            }

            $sections = $this->db->query("SELECT s.*, p.program_name 
                                         FROM sections s 
                                         JOIN programs p ON s.department_id = p.department_id 
                                         WHERE s.department_id = " . (int)$departmentId)->fetchAll(PDO::FETCH_ASSOC);

            require_once __DIR__ . '/../views/chair/sections.php';
        } catch (PDOException $e) {
            error_log("sections: Error - " . $e->getMessage());
            $error = "Failed to load sections.";
            require_once __DIR__ . '/../views/chair/sections.php';
        }
    }

    /**
     * Manage curriculum
     */
    public function curriculum()
    {
        error_log("curriculum: Starting curriculum method");
        try {
            $chairId = $_SESSION['user_id'];
            $departmentId = $this->getChairDepartment($chairId);
            if (!$departmentId) {
                error_log("curriculum: No department found for chairId: $chairId");
                $error = "No department assigned to this chair.";
                require_once __DIR__ . '/../views/chair/curriculum.php';
                return;
            }

            // Fetch curricula for the department
            $curriculaStmt = $this->db->prepare("SELECT c.*, p.program_name 
                                            FROM curricula c 
                                            JOIN programs p ON c.department_id = p.department_id 
                                            WHERE c.department_id = :department_id");
            $curriculaStmt->execute([':department_id' => $departmentId]);
            $curricula = $curriculaStmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch courses for the department (to be used in the "Manage Courses" modal)
            $coursesStmt = $this->db->prepare("SELECT course_id, course_code, course_name FROM courses WHERE department_id = :department_id");
            $coursesStmt->execute([':department_id' => $departmentId]);
            $courses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);

            // Pass the database connection and department ID to the view
            $db = $this->db;

            require_once __DIR__ . '/../views/chair/curriculum.php';
        } catch (PDOException $e) {
            error_log("curriculum: Error - " . $e->getMessage());
            $error = "Failed to load curriculums.";
            require_once __DIR__ . '/../views/chair/curriculum.php';
        }
    }

    /**
     * Manage courses
     */
    public function courses()
    {
        error_log("courses: Starting courses method");
        try {
            $chairId = $_SESSION['user_id'];
            $departmentId = $this->getChairDepartment($chairId);

            // Initialize variables
            $error = null;
            $success = null;
            $courses = [];
            $programs = [];
            $editCourse = null;

            if (!$departmentId) {
                error_log("courses: No department found for chairId: $chairId");
                $error = "No department assigned to this chair.";
                require_once __DIR__ . '/../views/chair/courses.php';
                return;
            }

            $error = null;
            $success = null;
            

            // Handle form submissions for adding/editing courses
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    $courseId = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
                    $data = [
                        'course_code' => trim($_POST['course_code'] ?? ''),
                        'course_name' => trim($_POST['course_name'] ?? ''),
                        'department_id' => $departmentId,
                        'program_id' => !empty($_POST['program_id']) ? intval($_POST['program_id']) : null,
                        'units' => intval($_POST['units'] ?? 0),
                        'lecture_units' => intval($_POST['lecture_units'] ?? 0),
                        'lab_units' => intval($_POST['lab_units'] ?? 0),
                        'lecture_hours' => intval($_POST['lecture_hours'] ?? 0),
                        'lab_hours' => intval($_POST['lab_hours'] ?? 0),
                        'is_active' => isset($_POST['is_active']) ? 1 : 0
                    ];

                    $errors = [];
                    if (empty($data['course_code'])) $errors[] = "Course code is required.";
                    if (empty($data['course_name'])) $errors[] = "Course name is required.";
                    if ($data['units'] < 1) $errors[] = "Units must be at least 1.";

                    // Check if course code already exists
                    $codeCheckStmt = $this->db->prepare("SELECT course_id FROM courses WHERE course_code = :course_code AND course_id != :course_id");
                    $codeCheckStmt->execute([':course_code' => $data['course_code'], ':course_id' => $courseId]);
                    if ($codeCheckStmt->fetchColumn()) {
                        $errors[] = "Course code already exists.";
                    }

                    if (empty($errors)) {
                        if ($courseId > 0) {
                            // Update existing course
                            $stmt = $this->db->prepare("UPDATE courses SET 
                            course_code = :course_code, 
                            course_name = :course_name, 
                            department_id = :department_id, 
                            program_id = :program_id, 
                            units = :units, 
                            lecture_units = :lecture_units,
                            lab_units = :lab_units,
                            lecture_hours = :lecture_hours, 
                            lab_hours = :lab_hours, 
                            is_active = :is_active 
                            WHERE course_id = :course_id");
                            $data['course_id'] = $courseId;
                            $stmt->execute($data);
                            $success = "Course updated successfully.";
                        } else {
                            // Add new course
                            $stmt = $this->db->prepare("INSERT INTO courses 
                            (course_code, course_name, department_id, program_id, units, 
                            lecture_units, lab_units, lecture_hours, lab_hours, is_active) 
                            VALUES 
                            (:course_code, :course_name, :department_id, :program_id, :units, 
                            :lecture_units, :lab_units, :lecture_hours, :lab_hours, :is_active)");
                            $stmt->execute($data);
                            $success = "Course added successfully.";
                        }
                    } else {
                        $error = implode("<br>", $errors);
                    }
                } catch (PDOException $e) {
                    error_log("courses: Error saving course - " . $e->getMessage());
                    $error = "Failed to save course: " . $e->getMessage();
                }
            }

            // Handle status toggle
            if (isset($_GET['toggle_status']) && $_GET['toggle_status'] > 0) {
                try {
                    $courseId = intval($_GET['toggle_status']);
                    $toggleStmt = $this->db->prepare("UPDATE courses SET is_active = NOT is_active WHERE course_id = :course_id AND department_id = :department_id");
                    $toggleStmt->execute([':course_id' => $courseId, ':department_id' => $departmentId]);
                    if ($toggleStmt->rowCount() > 0) {
                        $success = "Course status updated successfully.";
                    } else {
                        $error = "Course not found or you don't have permission to update it.";
                    }
                } catch (PDOException $e) {
                    error_log("courses: Error toggling status - " . $e->getMessage());
                    $error = "Failed to update course status: " . $e->getMessage();
                }
            }

            // Fetch courses
            $coursesStmt = $this->db->prepare("SELECT c.*, p.program_name 
            FROM courses c 
            LEFT JOIN programs p ON c.program_id = p.program_id 
            WHERE c.department_id = :department_id
            ORDER BY c.course_code");
            if ($coursesStmt->execute([':department_id' => $departmentId])) {
                $courses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                throw new PDOException("Courses query failed: " . implode(', ', $coursesStmt->errorInfo()));
            }

            // Fetch programs
            $programsStmt = $this->db->prepare("SELECT program_id, program_name 
            FROM programs WHERE department_id = :department_id");
            if ($programsStmt->execute([':department_id' => $departmentId])) {
                $programs = $programsStmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                throw new PDOException("Programs query failed: " . implode(', ', $programsStmt->errorInfo()));
            }

            // Fetch course data for editing
            $editCourse = null;
            if (isset($_GET['edit']) && $_GET['edit'] > 0) {
                try {
                    $courseId = intval($_GET['edit']);
                    $editStmt = $this->db->prepare("SELECT * FROM courses WHERE course_id = :course_id AND department_id = :department_id");
                    $editStmt->execute([':course_id' => $courseId, ':department_id' => $departmentId]);
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
            $error = "Failed to load courses.";
        }
        require_once __DIR__ . '/../views/chair/courses.php';
    }
    /**
     * Manage faculty
     */
    /**
 * Manage faculty
 */
public function faculty()
{
    $chairId = $_SESSION['user_id'];
    $departmentId = $this->getChairDepartment($chairId);

    $error = null;
    $success = null;
    $faculty = [];
    $colleges = [];
    $departments = [];
    $searchResults = [];

    // Handle AJAX search request for name suggestions
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
        try {
            $name = trim($_POST['name']);
            $collegeId = isset($_POST['college_id']) ? intval($_POST['college_id']) : 0;
            $departmentIdSearch = isset($_POST['department_id']) ? intval($_POST['department_id']) : 0;

            $query = "
                SELECT u.user_id, u.employee_id, u.first_name, u.last_name
                FROM faculty f 
                JOIN users u ON f.user_id = u.user_id 
                WHERE (u.first_name LIKE :name OR u.last_name LIKE :name)
            ";
            $params = [':name' => "%$name%"];

            if ($collegeId > 0) {
                $query .= " AND u.college_id = :college_id";
                $params[':college_id'] = $collegeId;
            }
            if ($departmentIdSearch > 0) {
                $query .= " AND u.department_id = :department_id";
                $params[':department_id'] = $departmentIdSearch;
            }
            if ($departmentId) {
                $query .= " AND u.department_id != :chair_department_id";
                $params[':chair_department_id'] = $departmentId;
            }

            $query .= " ORDER BY u.last_name, u.first_name LIMIT 10";

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode($results);
            exit;
        } catch (PDOException $e) {
            header('Content-Type: application/json');
            echo json_encode([]);
            exit;
        }
    }

    // Fetch current faculty members in the chair's department
    if (!$departmentId) {
        $error = "No department assigned to this chair.";
    } else {
        try {
            $facultyStmt = $this->db->prepare("
                SELECT u.user_id, u.employee_id, u.first_name, u.last_name, f.academic_rank, f.employment_type, d.department_name, c.college_name
                FROM faculty f 
                JOIN users u ON f.user_id = u.user_id 
                JOIN departments d ON u.department_id = d.department_id
                JOIN colleges c ON u.college_id = c.college_id
                WHERE u.department_id = :department_id
                ORDER BY u.last_name, u.first_name
            ");
            $facultyStmt->execute([':department_id' => $departmentId]);
            $faculty = $facultyStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error = "Failed to load faculty: " . htmlspecialchars($e->getMessage());
        }
    }

    // Fetch all colleges and departments for search filters
    try {
        $collegesStmt = $this->db->query("SELECT college_id, college_name FROM colleges ORDER BY college_name");
        $colleges = $collegesStmt->fetchAll(PDO::FETCH_ASSOC);

        $departmentsStmt = $this->db->query("SELECT department_id, department_name, college_id FROM departments ORDER BY department_name");
        $departments = $departmentsStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Failed to load colleges and departments: " . htmlspecialchars($e->getMessage());
    }

    // Handle search functionality
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
        try {
            $collegeId = isset($_POST['college_id']) ? intval($_POST['college_id']) : 0;
            $departmentIdSearch = isset($_POST['department_id']) ? intval($_POST['department_id']) : 0;
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';

            $query = "
                SELECT u.user_id, u.employee_id, u.first_name, u.last_name, f.academic_rank, f.employment_type, d.department_name, c.college_name
                FROM faculty f 
                JOIN users u ON f.user_id = u.user_id 
                JOIN departments d ON u.department_id = d.department_id
                JOIN colleges c ON u.college_id = c.college_id
                WHERE 1=1
            ";
            $params = [];

            if ($collegeId > 0) {
                $query .= " AND u.college_id = :college_id";
                $params[':college_id'] = $collegeId;
            }
            if ($departmentIdSearch > 0) {
                $query .= " AND u.department_id = :department_id";
                $params[':department_id'] = $departmentIdSearch;
            }
            if (!empty($name)) {
                $query .= " AND (u.first_name LIKE :name OR u.last_name LIKE :name)";
                $params[':name'] = "%$name%";
            }

            // Exclude faculty already in the chair's department
            if ($departmentId) {
                $query .= " AND u.department_id != :chair_department_id";
                $params[':chair_department_id'] = $departmentId;
            }

            $query .= " ORDER BY u.last_name, u.first_name";

            $searchStmt = $this->db->prepare($query);
            $searchStmt->execute($params);
            $searchResults = $searchStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error = "Failed to search faculty: " . htmlspecialchars($e->getMessage());
        }
    }

    // Handle adding faculty to the chair's department
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_faculty'])) {
        try {
            $userId = intval($_POST['user_id']);
            if ($departmentId) {
                // First check if the user is already in the department
                $checkStmt = $this->db->prepare("SELECT user_id FROM users WHERE user_id = :user_id AND department_id = :department_id");
                $checkStmt->execute([':user_id' => $userId, ':department_id' => $departmentId]);
                
                if ($checkStmt->fetchColumn()) {
                    $error = "This faculty member is already in your department.";
                } else {
                    // Update the user's department in the users table
                    $updateStmt = $this->db->prepare("UPDATE users SET department_id = :department_id WHERE user_id = :user_id");
                    $updateStmt->execute([':department_id' => $departmentId, ':user_id' => $userId]);

                    // Update the faculty's department in the faculty table
                    $updateFacultyStmt = $this->db->prepare("UPDATE faculty SET department_id = :department_id WHERE user_id = :user_id");
                    $updateFacultyStmt->execute([':department_id' => $departmentId, ':user_id' => $userId]);

                    $success = "Faculty member added to your department successfully.";

                    // Refresh the faculty list
                    $facultyStmt->execute([':department_id' => $departmentId]);
                    $faculty = $facultyStmt->fetchAll(PDO::FETCH_ASSOC);
                }
            } else {
                $error = "Cannot add faculty: No department assigned to this chair.";
            }
        } catch (PDOException $e) {
            $error = "Failed to add faculty: " . htmlspecialchars($e->getMessage());
        }
    }

    // Pass data to the view
    require_once __DIR__ . '/../views/chair/faculty.php';
}

/**
 * AJAX endpoint for faculty search suggestions
 */
public function search()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
        try {
            $name = trim($_POST['name']);
            $collegeId = isset($_POST['college_id']) ? intval($_POST['college_id']) : 0;
            $departmentId = isset($_POST['department_id']) ? intval($_POST['department_id']) : 0;
            $chairDepartmentId = $this->getChairDepartment($_SESSION['user_id']);

            $query = "
                SELECT u.user_id, u.employee_id, u.first_name, u.last_name
                FROM faculty f 
                JOIN users u ON f.user_id = u.user_id 
                WHERE (u.first_name LIKE :name OR u.last_name LIKE :name)
            ";
            $params = [':name' => "%$name%"];

            if ($collegeId > 0) {
                $query .= " AND u.college_id = :college_id";
                $params[':college_id'] = $collegeId;
            }
            if ($departmentId > 0) {
                $query .= " AND u.department_id = :department_id";
                $params[':department_id'] = $departmentId;
            }
            if ($chairDepartmentId) {
                $query .= " AND u.department_id != :chair_department_id";
                $params[':chair_department_id'] = $chairDepartmentId;
            }

            $query .= " ORDER BY u.last_name, u.first_name LIMIT 10";

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode($results);
            exit;
        } catch (PDOException $e) {
            header('Content-Type: application/json');
            echo json_encode([]);
            exit;
        }
    }

        // Pass data to the view
        require_once __DIR__ . '/../views/chair/faculty.php';
}

    /**
     * View/edit profile
     */
    public function profile()
    {
        error_log("profile: Starting profile method");
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $data = [
                    'user_id' => $_SESSION['user_id'],
                    'email' => trim($_POST['email'] ?? ''),
                    'phone' => trim($_POST['phone'] ?? ''),
                    'first_name' => trim($_POST['first_name'] ?? ''),
                    'middle_name' => trim($_POST['middle_name'] ?? ''),
                    'last_name' => trim($_POST['last_name'] ?? ''),
                    'suffix' => trim($_POST['suffix'] ?? '')
                ];

                error_log("profile: Updating profile with data - " . json_encode($data));

                $errors = [];
                if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
                if (empty($data['first_name'])) $errors[] = "First name is required.";
                if (empty($data['last_name'])) $errors[] = "Last name is required.";

                if (empty($errors)) {
                    $stmt = $this->db->prepare("UPDATE users SET email = :email, phone = :phone, first_name = :first_name, 
                                                middle_name = :middle_name, last_name = :last_name, suffix = :suffix 
                                                WHERE user_id = :user_id");
                    $stmt->execute($data);
                    $success = "Profile updated successfully.";
                } else {
                    error_log("profile: Validation errors - " . implode(", ", $errors));
                    $error = implode("<br>", $errors);
                }
            } catch (PDOException $e) {
                error_log("profile: Error updating profile - " . $e->getMessage());
                $error = "Failed to update profile.";
            }
        }

        try {
            $stmt = $this->db->prepare("SELECT u.*, d.department_name 
                                        FROM users u 
                                        JOIN program_chairs pc ON u.user_id = pc.user_id 
                                        JOIN programs p ON pc.program_id = p.program_id 
                                        JOIN departments d ON p.department_id = d.department_id 
                                        WHERE u.user_id = :user_id AND pc.is_current = 1");
            $stmt->execute([':user_id' => $_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            error_log("profile: Fetched user data, loading view");
            require_once __DIR__ . '/../views/chair/profile.php';
        } catch (PDOException $e) {
            error_log("profile: Error - " . $e->getMessage());
            $error = "Failed to load profile.";
            require_once __DIR__ . '/../views/chair/profile.php';
        }
    }
}
