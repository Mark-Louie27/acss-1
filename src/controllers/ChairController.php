<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../../vendor/autoload.php'; // Make sure to install PhpSpreadsheet via Composer
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

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
                SELECT c.curriculum_id, c.curriculum_name, c.total_units, c.status, p.program_name 
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
            error_log("my schedule: Starting my Schedule method for user_id: $chairId");

            // Get department for the Chair
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

    private function getCurrentSemester()
    {
        // First, try to find the semester marked as current
        $stmt = $this->db->prepare("SELECT semester_id, CONCAT(semester_name, ' ', academic_year) AS semester_name 
                                   FROM semesters 
                                   WHERE is_current = 1");
        $stmt->execute();
        $semester = $stmt->fetch(PDO::FETCH_ASSOC);

        // If no semester is marked as current, fall back to date range
        if (!$semester) {
            $stmt = $this->db->prepare("SELECT semester_id, CONCAT(semester_name, ' ', academic_year) AS semester_name 
                                       FROM semesters 
                                       WHERE CURRENT_DATE BETWEEN start_date AND end_date");
            $stmt->execute();
            $semester = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return $semester;
    }

    private function exportTimetableToExcel($schedules, $filename, $roomName, $semesterName)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set title and headers
        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('A1', 'Republic of the Philippines');
        $sheet->mergeCells('A2:E2');
        $sheet->setCellValue('A2', 'PRESIDENT RAMON MAGSAYSAY STATE UNIVERSITY');
        $sheet->mergeCells('A3:E3');
        $sheet->setCellValue('A3', '(Formerly Ramon Magsaysay Technological University)');
        $sheet->mergeCells('A4:E4');
        $sheet->setCellValue('A4', 'COMPUTER LABORATORY SCHEDULE');
        $sheet->mergeCells('A5:E5');
        $sheet->setCellValue('A5', $semesterName);
        $sheet->mergeCells('A6:E6');
        $sheet->setCellValue('A6', strtoupper($roomName));

        // Faculty in-charge (placeholder)
        $sheet->setCellValue('A7', 'Faculty-in-charge:');
        $sheet->mergeCells('B7:E7');

        // Time slots and days
        $days = ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY'];
        $times = [
            '7:30 - 8:00',
            '8:00 - 9:00',
            '9:00 - 10:00',
            '10:00 - 11:00',
            '11:00 - 12:00',
            '12:00 - 1:00',
            '1:00 - 2:00',
            '2:00 - 3:00',
            '3:00 - 4:00',
            '4:00 - 5:00'
        ];

        $sheet->setCellValue('A9', 'TIME');
        for ($i = 0; $i < count($days); $i++) {
            $cell = chr(66 + $i) . '9'; // Convert column index to letter (B, C, D, etc.)
            $sheet->setCellValue($cell, $days[$i]);
        }

        $row = 10;
        foreach ($times as $time) {
            $sheet->setCellValue('A' . $row, $time);
            $row++;
        }

        // Populate schedule data
        $row = 10;
        foreach ($times as $time) {
            foreach ($days as $index => $day) {
                $cell = chr(66 + $index) . $row; // B, C, D, E columns
                foreach ($schedules as $schedule) {
                    if ($schedule['start_time'] === substr($time, 0, 5) && $schedule['day_of_week'] === $day) {
                        $sheet->setCellValue($cell, $schedule['course_code'] . ' - ' . $schedule['faculty_name']);
                    }
                }
                $sheet->getStyle($cell)->getAlignment()->setWrapText(true);
            }
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Style headers
        $sheet->getStyle('A1:E6')->getFont()->setBold(true);
        $sheet->getStyle('A9:E9')->getFont()->setBold(true);
        $sheet->getStyle('A1:E6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Write to file
        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    private function exportPlainExcel($courses, $faculty, $rooms, $sections, $filename)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $sheet->setCellValue('A1', 'Course Code');
        $sheet->setCellValue('B1', 'Course Name');
        $sheet->setCellValue('C1', 'Faculty Name');
        $sheet->setCellValue('D1', 'Room Name');
        $sheet->setCellValue('E1', 'Section Name');
        $sheet->setCellValue('F1', 'Day of Week');
        $sheet->setCellValue('G1', 'Start Time');
        $sheet->setCellValue('H1', 'End Time');

        // Populate with available resources
        $row = 2;
        foreach ($courses as $course) {
            $sheet->setCellValue('A' . $row, $course['course_code']);
            $sheet->setCellValue('B' . $row, $course['course_name']);
            $row++;
        }
        $row = 2;
        foreach ($faculty as $fac) {
            $sheet->setCellValue('C' . $row, $fac['name']);
            $row++;
        }
        $row = 2;
        foreach ($rooms as $room) {
            $sheet->setCellValue('D' . $row, $room['room_name']);
            $row++;
        }
        $row = 2;
        foreach ($sections as $section) {
            $sheet->setCellValue('E' . $row, $section['section_name']);
            $row++;
        }

        // Add days and times as dropdown options
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $times = ['07:30', '08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00'];
        $sheet->setCellValue('F2', implode(', ', $days));
        $sheet->setCellValue('G2', implode(', ', $times));
        $sheet->setCellValue('H2', implode(', ', $times));

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);

        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    private function validateCurriculumCourse($curriculumId, $courseId)
    {
        $stmt = $this->db->prepare("SELECT 1 FROM curriculum_courses 
                                  WHERE curriculum_id = :curriculum_id 
                                  AND course_id = :course_id");
        $stmt->execute([':curriculum_id' => $curriculumId, ':course_id' => $courseId]);
        return $stmt->fetchColumn();
    }

    private function validateCurriculumSection($curriculumId, $sectionId)
    {
        $currentSemester = $this->getCurrentSemester();
        $stmt = $this->db->prepare("
            SELECT 1 FROM sections 
            WHERE section_id = :section_id 
            AND curriculum_id = :curriculum_id
            AND semester = :semester
            AND is_active = 1
        ");
        $stmt->execute([
            ':section_id' => $sectionId,
            ':curriculum_id' => $curriculumId,
            ':semester' => $currentSemester['semester_name']
        ]);
        return $stmt->fetchColumn();
    }

    public function manageSchedule()
    {
        $chairId = $_SESSION['user_id'];
        $departmentId = $this->getChairDepartment($chairId);
        $currentSemester = $this->getCurrentSemester();
        $activeTab = $_GET['tab'] ?? 'manual';
        $error = null;
        $schedules = [];

        try {
            $selectedCurriculumId = $_POST['curriculum_id'] ?? ($_GET['curriculum_id'] ?? null);
            // Load common data
            $curricula = $this->db->prepare("
                SELECT curriculum_id, curriculum_name 
                FROM curricula 
                WHERE department_id = :dept_id 
                AND status = 'Active'
            ");
            $curricula->execute([':dept_id' => $departmentId]);
            $curricula = $curricula->fetchAll();

            // Get available classrooms
            $classrooms = $this->db->prepare("
                SELECT room_id, room_name 
                FROM classrooms 
                WHERE (department_id = :dept_id OR shared = 1) 
                AND availability = 'available'
            ");
            $classrooms->execute([':dept_id' => $departmentId]);
            $classrooms = $classrooms->fetchAll();

            // Get courses for the selected curriculum
            $courses = $this->db->prepare("
            SELECT 
                c.course_id,
                c.course_code,
                c.course_name,
                cc.curriculum_id,
                cc.year_level AS curriculum_year,
                cc.semester AS curriculum_semester
            FROM curriculum_courses cc
            JOIN courses c ON cc.course_id = c.course_id
            WHERE cc.curriculum_id = :curriculum_id
            ORDER BY 
                FIELD(cc.year_level, '1st Year', '2nd Year', '3rd Year', '4th Year'),
                FIELD(cc.semester, '1st', '2nd', 'Summer'),
                c.course_code
        ");
            $courses->execute([':curriculum_id' => $selectedCurriculumId]);
            $courses = $courses->fetchAll(PDO::FETCH_ASSOC);

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if ($activeTab === 'manual') {
                    $schedulesData = json_decode($_POST['schedules'], true);

                    foreach ($schedulesData as $schedule) {
                        $errors = [];
                        $curriculumId = $schedule['curriculum_id'] ?? 0;

                        // Validate curriculum course
                        if (!$this->validateCurriculumCourse($curriculumId, $schedule['course_id'])) {
                            $errors[] = "Course doesn't belong to selected curriculum";
                        }

                        // Validate curriculum section
                        if (!$this->validateCurriculumSection($curriculumId, $schedule['section_id'])) {
                            $errors[] = "Section doesn't belong to selected curriculum or is inactive";
                        }

                        // Validate room availability
                        $roomAvailable = $this->db->prepare("
                            SELECT 1 FROM classrooms 
                            WHERE room_id = :room_id 
                            AND availability = 'available'
                        ");
                        $roomAvailable->execute([':room_id' => $schedule['room_id']]);

                        if (!$roomAvailable->fetchColumn()) {
                            $errors[] = "Selected room is not available";
                        }

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
                                'curriculum_id' => $curriculumId
                            ]);

                            if ($response['code'] === 200) {
                                $schedules[] = $response['data'];
                            }
                        }
                    }
                } elseif ($activeTab === 'generate') {
                    $curriculumId = $_POST['curriculum_id'];
                    $yearLevels = $_POST['year_levels'] ?? [];

                    // Get curriculum courses
                    $courses = $this->db->prepare("
                        SELECT 
                            c.course_id,
                            c.course_code,
                            c.course_name,
                            cc.curriculum_id,
                            cc.year_level AS curriculum_year,
                            cc.semester AS curriculum_semester,
                            cc.subject_type
                        FROM curriculum_courses cc
                        JOIN courses c ON cc.course_id = c.course_id
                        JOIN curricula cr ON cc.curriculum_id = cr.curriculum_id
                        WHERE cr.curriculum_id = :curriculum_id
                        AND cr.status = 'Active'
                        ORDER BY
                            FIELD(cc.year_level, '1st Year', '2nd Year', '3rd Year', '4th Year'),
                            FIELD(cc.semester, '1st', '2nd', 'Summer'),
                            c.course_code
                    ");
                    $courses->execute([':curriculum_id' => $curriculumId]);
                    $courses = $courses->fetchAll(PDO::FETCH_ASSOC);

                    // Get all active sections for the current curriculum, semester, and department
                    $sections = $this->db->prepare("
                        SELECT 
                            section_id,
                            section_name,
                            year_level,
                            curriculum_id,
                            max_students
                        FROM sections
                        WHERE curriculum_id = :curriculum_id
                        AND department_id = :department_id
                        AND semester = :semester
                        AND is_active = 1
                        ORDER BY 
                            FIELD(year_level, '1st Year', '2nd Year', '3rd Year', '4th Year'),
                            section_name
                    ");
                    $sections->execute([
                        ':curriculum_id' => $curriculumId,
                        ':department_id' => $departmentId,
                        ':semester' => $currentSemester['semester_name']
                    ]);
                    $sections = $sections->fetchAll(PDO::FETCH_ASSOC);

                    // Generate schedule logic
                    foreach ($sections as $section) {
                        foreach ($courses as $course) {
                            // Check if course matches section year level and semester
                            if (
                                $course['curriculum_year'] !== $section['year_level'] ||
                                $course['curriculum_semester'] !== $currentSemester['semester_name']
                            ) {
                                continue;
                            }

                            // Find available room
                            $room = $this->db->prepare("
                                SELECT room_id, room_name 
                                FROM classrooms 
                                WHERE (department_id = :dept_id OR shared = 1)
                                AND availability = 'available'
                                AND capacity >= :students
                                ORDER BY RAND() LIMIT 1
                            ");
                            $room->execute([
                                ':dept_id' => $departmentId,
                                ':students' => $section['max_students']
                            ]);
                            $room = $room->fetch();

                            if (!$room) continue;

                            // Schedule creation logic
                            $scheduleData = [
                                'course_id' => $course['course_id'],
                                'faculty_id' => $this->getAvailableFaculty($departmentId),
                                'room_id' => $room['room_id'],
                                'section_id' => $section['section_id'],
                                'day_of_week' => $this->getRandomDay(),
                                'start_time' => $this->getRandomTimeSlot(),
                                'end_time' => $this->getEndTime($this->getRandomTimeSlot()),
                                'semester_id' => $currentSemester['semester_id'],
                                'curriculum_id' => $curriculumId
                            ];

                            $response = $this->callSchedulingService('POST', 'schedules', $scheduleData);
                            if ($response['code'] === 200) {
                                $schedules[] = $response['data'];
                            }
                        }
                    }
                }
            }

            // Load view data
            $courses = $this->db->prepare("
                SELECT c.* 
                FROM courses c
                JOIN curriculum_courses cc ON c.course_id = cc.course_id
                WHERE c.department_id = :dept_id
            ");
            $courses->execute([':dept_id' => $departmentId]);
            $courses = $courses->fetchAll();

            $faculty = $this->db->prepare("
                SELECT f.faculty_id, CONCAT(u.first_name, ' ', u.last_name) AS name 
                FROM faculty f
                JOIN users u ON f.user_id = u.user_id
                WHERE u.department_id = :dept_id
            ");
            $faculty->execute([':dept_id' => $departmentId]);
            $faculty = $faculty->fetchAll();

            $sections = $this->db->prepare("
                SELECT s.*, c.curriculum_name 
                FROM sections s
                JOIN curricula c ON s.curriculum_id = c.curriculum_id
                WHERE s.department_id = :department_id
                AND s.semester = :semester
                AND s.is_active = 1
            ");
            $sections->execute([
                ':department_id' => $departmentId,
                ':semester' => $currentSemester['semester_name']
            ]);
            $sections = $sections->fetchAll();

            $semesters = $this->db->query("SELECT * FROM semesters")->fetchAll();
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }

        require_once __DIR__ . '/../views/chair/schedule_management.php';
    }

    private function getAvailableFaculty($departmentId)
    {
        $stmt = $this->db->prepare("
            SELECT faculty_id 
            FROM faculty 
            WHERE department_id = :dept_id 
            ORDER BY RAND() LIMIT 1
        ");
        $stmt->execute([':dept_id' => $departmentId]);
        return $stmt->fetchColumn();
    }

    private function getRandomDay()
    {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        return $days[array_rand($days)];
    }

    private function getRandomTimeSlot()
    {
        $slots = ['07:30', '08:30', '09:30', '10:30', '13:00', '14:00'];
        return $slots[array_rand($slots)];
    }

    private function getEndTime($startTime)
    {
        $timeMap = [
            '07:30' => '08:30',
            '08:30' => '09:30',
            '09:30' => '10:30',
            '10:30' => '11:30',
            '13:00' => '14:00',
            '14:00' => '15:00'
        ];
        return $timeMap[$startTime] ?? '15:00';
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
            $error = null;

            $departmentInfo = null;

            // Get department and college info
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

            // Base query to fetch department classrooms
            $query = "SELECT c.*, d.department_name, cl.college_name 
                FROM classrooms c
                JOIN departments d ON c.department_id = d.department_id
                JOIN colleges cl ON d.college_id = cl.college_id
                WHERE c.department_id = :department_id";

            $params = [':department_id' => $departmentId];
            $conditions = [];

            // Check if searching via POST
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_classrooms'])) {
                $building = $_POST['building'] ?? '';
                $minCapacity = (int)($_POST['min_capacity'] ?? 0);
                $roomType = $_POST['room_type'] ?? '';
                $availability = $_POST['availability'] ?? 'available';

                // Add filters
                $conditions[] = "c.availability = :availability";
                $params[':availability'] = $availability;

                $conditions[] = "c.capacity >= :min_capacity";
                $params[':min_capacity'] = $minCapacity;
                

                if (!empty($building)) {
                    $conditions[] = "c.building LIKE :building";
                    $params[':building'] = "%$building%";
                }

                if (!empty($roomType)) {
                    $conditions[] = "c.room_type = :room_type";
                    $params[':room_type'] = $roomType;
                }

                // Restrict to shared or department classrooms if department exists
                if ($departmentId) {
                    $conditions[] = "(c.shared = 1 OR c.department_id = :department_id)";
                    $params[':department_id'] = $departmentId;
                }
            }

            // Add conditions to query
            if (!empty($conditions)) {
                $query .= " WHERE " . implode(" AND ", $conditions);
            }

            $query .= " ORDER BY c.room_name";

            // Execute the query
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $classrooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Handle missing department
            if (!$departmentId) {
                error_log("classroom: No department found for chairId: $chairId");
                $classrooms = []; // Clear classrooms if no department
                $error = "No department assigned to this chair.";
            }

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

            // Pagination settings
            $perPage = 15; // Number of courses per page
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $offset = ($page - 1) * $perPage;

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

            // Fetch total number of courses for pagination
            $totalStmt = $this->db->prepare("SELECT COUNT(*) FROM courses WHERE department_id = :department_id");
            $totalStmt->execute([':department_id' => $departmentId]);
            $totalCourses = $totalStmt->fetchColumn();
            $totalPages = ceil($totalCourses / $perPage);

            // Fetch courses with pagination
            $coursesStmt = $this->db->prepare("SELECT c.*, p.program_name 
                FROM courses c 
                LEFT JOIN programs p ON c.program_id = p.program_id 
                WHERE c.department_id = :department_id
                ORDER BY c.course_code
                LIMIT :offset, :perPage");
            $coursesStmt->bindValue(':department_id', $departmentId, PDO::PARAM_INT);
            $coursesStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $coursesStmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
            if ($coursesStmt->execute()) {
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
                    $checkStmt = $this->db->prepare("SELECT user_id FROM users WHERE user_id = :user_id AND department_id = :department_id");
                    $checkStmt->execute([':user_id' => $userId, ':department_id' => $departmentId]);

                    if ($checkStmt->fetchColumn()) {
                        $error = "This faculty member is already in your department.";
                    } else {
                        $updateStmt = $this->db->prepare("UPDATE users SET department_id = :department_id WHERE user_id = :user_id");
                        $updateStmt->execute([':department_id' => $departmentId, ':user_id' => $userId]);

                        $updateFacultyStmt = $this->db->prepare("UPDATE faculty SET department_id = :department_id WHERE user_id = :user_id");
                        $updateFacultyStmt->execute([':department_id' => $departmentId, ':user_id' => $userId]);

                        $success = "Faculty member added to your department successfully.";

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

        // Handle removing faculty from the chair's department
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_faculty'])) {
            try {
                $userId = intval($_POST['user_id']);
                if ($departmentId) {
                    $checkStmt = $this->db->prepare("SELECT user_id FROM users WHERE user_id = :user_id AND department_id = :department_id");
                    $checkStmt->execute([':user_id' => $userId, ':department_id' => $departmentId]);

                    if (!$checkStmt->fetchColumn()) {
                        $error = "This faculty member is not in your department.";
                    } else {
                        $updateStmt = $this->db->prepare("UPDATE users SET department_id = NULL WHERE user_id = :user_id");
                        $updateStmt->execute([':user_id' => $userId]);

                        $updateFacultyStmt = $this->db->prepare("UPDATE faculty SET department_id = NULL WHERE user_id = :user_id");
                        $updateFacultyStmt->execute([':user_id' => $userId]);

                        $success = "Faculty member removed from your department successfully.";

                        $facultyStmt->execute([':department_id' => $departmentId]);
                        $faculty = $facultyStmt->fetchAll(PDO::FETCH_ASSOC);
                    }
                } else {
                    $error = "Cannot remove faculty: No department assigned to this chair.";
                }
            } catch (PDOException $e) {
                $error = "Failed to remove faculty: " . htmlspecialchars($e->getMessage());
            }
        }

        require_once __DIR__ . '/../views/chair/faculty.php';
    }

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
                $uploadDir = __DIR__ . '/../public/uploads/profiles/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $data = [
                    'user_id' => $_SESSION['user_id'],
                    'email' => trim($_POST['email'] ?? ''),
                    'phone' => trim($_POST['phone'] ?? ''),
                    'first_name' => trim($_POST['first_name'] ?? ''),
                    'middle_name' => trim($_POST['middle_name'] ?? ''),
                    'last_name' => trim($_POST['last_name'] ?? ''),
                    'suffix' => trim($_POST['suffix'] ?? ''),
                    'college_id' => $_SESSION['college_id'] ?? null
                ];

                $errors = [];
                if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Valid email is required.";
                }
                if (empty($data['first_name'])) {
                    $errors[] = "First name is required.";
                }
                if (empty($data['last_name'])) {
                    $errors[] = "Last name is required.";
                }
                if (!empty($data['phone']) && !preg_match('/^\d{10,12}$/', $data['phone'])) {
                    $errors[] = "Phone number must be 10-12 digits.";
                }

                // Handle file upload
                if (!empty($_FILES['profile_picture']['name'])) {
                    $file = $_FILES['profile_picture'];
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    $maxSize = 2 * 1024 * 1024; // 2MB

                    if (!in_array($file['type'], $allowedTypes)) {
                        $errors[] = "Only JPG, PNG, and GIF files are allowed.";
                    } elseif ($file['size'] > $maxSize) {
                        $errors[] = "File size must be less than 2MB.";
                    } else {
                        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $filename = 'profile_' . $data['user_id'] . '_' . time() . '.' . $extension;
                        $targetPath = $uploadDir . $filename;

                        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                            $data['profile_picture'] = '/uploads/profiles/' . $filename;
                        } else {
                            $errors[] = "Failed to upload profile picture.";
                        }
                    }
                }

                if (empty($errors)) {
                    $setClause = [];
                    $params = [];

                    foreach (['email', 'phone', 'first_name', 'middle_name', 'last_name', 'suffix'] as $field) {
                        $setClause[] = "$field = :$field";
                        $params[":$field"] = $data[$field];
                    }

                    if (isset($data['profile_picture'])) {
                        $setClause[] = "profile_picture = :profile_picture";
                        $params[':profile_picture'] = $data['profile_picture'];
                    }

                    $query = "UPDATE users SET " . implode(', ', $setClause) . ", updated_at = NOW() WHERE user_id = :user_id";
                    $params[':user_id'] = $data['user_id'];

                    $stmt = $this->db->prepare($query);
                    $stmt->execute($params);

                    // Update session variables
                    $_SESSION['first_name'] = $data['first_name'];
                    $_SESSION['email'] = $data['email'];
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
            // Fetch user data
            $stmt = $this->db->prepare("
                SELECT u.*, d.department_name, c.college_name, r.role_name
                FROM users u
                LEFT JOIN departments d ON u.department_id = d.department_id
                LEFT JOIN colleges c ON u.college_id = c.college_id
                LEFT JOIN roles r ON u.role_id = r.role_id
                LEFT JOIN program_chairs pc ON u.user_id = pc.user_id
                WHERE u.user_id = :user_id AND (pc.is_current = 1 OR pc.is_current IS NULL)
            ");
            $stmt->execute([':user_id' => $_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                throw new Exception("User not found");
            }

            // Fetch faculty count for the department
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as facultyCount 
                FROM faculty f 
                JOIN department_instructors di ON f.user_id = di.user_id 
                WHERE di.department_id = :department_id AND di.is_current = 1
            ");
            $stmt->execute([':department_id' => $user['department_id']]);
            $facultyCount = $stmt->fetch(PDO::FETCH_ASSOC)['facultyCount'];

            // Fetch courses count for the department
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as coursesCount 
                FROM courses c 
                WHERE c.department_id = :department_id AND c.is_active = 1
            ");
            $stmt->execute([':department_id' => $user['department_id']]);
            $coursesCount = $stmt->fetch(PDO::FETCH_ASSOC)['coursesCount'];

            // Fetch pending applicants count from faculty_requests
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as pendingApplicantsCount 
                FROM faculty_requests fr 
                WHERE fr.department_id = :department_id AND fr.status = 'pending'
            ");
            $stmt->execute([':department_id' => $user['department_id']]);
            $pendingApplicantsCount = $stmt->fetch(PDO::FETCH_ASSOC)['pendingApplicantsCount'];

            // Fetch current semester
            $stmt = $this->db->prepare("SELECT semester_name FROM semesters WHERE is_current = 1");
            $stmt->execute();
            $currentSemester = $stmt->fetch(PDO::FETCH_ASSOC)['semester_name'] ?? '2nd';

            // Fetch last login from auth_logs
            $stmt = $this->db->prepare("
                SELECT created_at FROM auth_logs 
                WHERE user_id = :user_id AND action = 'login_success' 
                ORDER BY created_at DESC LIMIT 1
            ");
            $stmt->execute([':user_id' => $_SESSION['user_id']]);
            $lastLogin = $stmt->fetch(PDO::FETCH_ASSOC)['created_at'] ?? 'January 1, 1970, 1:00 am';

            require_once __DIR__ . '/../views/chair/profile.php';
        } catch (Exception $e) {
            error_log("profile: Error - " . $e->getMessage());
            $error = "Failed to load profile.";
            require_once __DIR__ . '/../views/chair/profile.php';
        }
    }
}
