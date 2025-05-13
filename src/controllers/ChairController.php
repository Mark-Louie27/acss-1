<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../../vendor/autoload.php'; // Make sure to install PhpSpreadsheet via Composer
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ChairController
{
    public $db;

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
    public function getChairDepartment($chairId)
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
            $error = null;
            $success = null;

            if (!$departmentId) {
                error_log("sections: No department found for chairId: $chairId");
                $error = "No department assigned to this chair.";
                $sections = [];
                $currentSemester = null;
                require_once __DIR__ . '/../views/chair/sections.php';
                return;
            }

            // Handle POST requests for add/remove/edit
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (isset($_POST['add_section'])) {
                    $this->addSection($departmentId);
                } elseif (isset($_POST['remove_section'])) {
                    $this->removeSection();
                } elseif (isset($_POST['edit_section'])) {
                    $this->editSection($departmentId);
                }
                // Retrieve success/error messages after POST
                $success = $_SESSION['success'] ?? null;
                $error = $_SESSION['error'] ?? null;
                unset($_SESSION['success'], $_SESSION['error']);
            }

            // Fetch sections, grouped by year level
            $query = "
                 SELECT s.*, p.program_name 
                 FROM sections s 
                 JOIN programs p ON s.department_id = p.department_id 
                 WHERE s.department_id = :department_id AND s.is_active = 1
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
            $stmt->execute();
            $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Group sections by year level
            $groupedSections = [
                '1st Year' => [],
                '2nd Year' => [],
                '3rd Year' => [],
                '4th Year' => []
            ];
            foreach ($sections as $section) {
                $groupedSections[$section['year_level']][] = $section;
            }

            // Get current semester
            $currentSemester = $this->getCurrentSemester();

            require_once __DIR__ . '/../views/chair/sections.php';
        } catch (PDOException $e) {
            error_log("sections: PDO Error - " . $e->getMessage());
            $error = "Failed to load sections.";
            $sections = [];
            $groupedSections = [
                '1st Year' => [],
                '2nd Year' => [],
                '3rd Year' => [],
                '4th Year' => []
            ];
            $currentSemester = null;
            require_once __DIR__ . '/../views/chair/sections.php';
        }
    }

    /**
     * Add a new section
     * @param int $departmentId
     */
    private function addSection($departmentId)
    {
        try {
            $section_name = trim($_POST['section_name'] ?? '');
            $year_level = trim($_POST['year_level'] ?? '');
            $max_students = (int)($_POST['max_students'] ?? 40);
            $currentSemester = $this->getCurrentSemester();

            // Validation
            $errors = [];
            if (empty($section_name)) {
                $errors[] = "Section name is required.";
            }
            if (!in_array($year_level, ['1st Year', '2nd Year', '3rd Year', '4th Year'])) {
                $errors[] = "Invalid year level.";
            }
            if ($max_students < 1 || $max_students > 100) {
                $errors[] = "Max students must be between 1 and 100.";
            }
            if (!$currentSemester || !isset($currentSemester['semester_name'], $currentSemester['academic_year'])) {
                $errors[] = "No current semester is set. Please contact the administrator.";
                error_log("addSection: No valid current semester found");
            }

            // Check if section name already exists
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
                ':section_name' => $section_name,
                ':academic_year' => $currentSemester['academic_year']
            ]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "A section with this name already exists for this academic year.";
            }

            if (!empty($errors)) {
                error_log("addSection: Validation errors - " . implode(", ", $errors));
                $_SESSION['error'] = implode("<br>", $errors);
                return;
            }

            // Insert section
            $query = "
                 INSERT INTO sections (
                     department_id, section_name, year_level, 
                     semester, academic_year, max_students, 
                     current_students, is_active, created_at, updated_at
                 )
                 VALUES (
                     :department_id, :section_name, :year_level, 
                     :semester, :academic_year, :max_students, 
                     0, 1, NOW(), NOW()
                 )
             ";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':department_id' => $departmentId,
                ':section_name' => $section_name,
                ':year_level' => $year_level,
                ':semester' => $currentSemester['semester_name'],
                ':academic_year' => $currentSemester['academic_year'],
                ':max_students' => $max_students
            ]);

            $_SESSION['success'] = "Section '$section_name' added successfully.";
            error_log("addSection: Section '$section_name' added for department_id: $departmentId");
        } catch (PDOException $e) {
            error_log("addSection: Error - " . $e->getMessage());
            $_SESSION['error'] = "Failed to add section.";
        }
    }

    /**
     * Edit an existing section
     * @param int $departmentId
     */
    private function editSection($departmentId)
    {
        try {
            $section_id = (int)($_POST['section_id'] ?? 0);
            $section_name = trim($_POST['section_name'] ?? '');
            $year_level = trim($_POST['year_level'] ?? '');
            $max_students = (int)($_POST['max_students'] ?? 40);

            // Validation
            $errors = [];
            if ($section_id <= 0) {
                $errors[] = "Invalid section ID.";
            }
            if (empty($section_name)) {
                $errors[] = "Section name is required.";
            }
            if (!in_array($year_level, ['1st Year', '2nd Year', '3rd Year', '4th Year'])) {
                $errors[] = "Invalid year level.";
            }
            if ($max_students < 1 || $max_students > 100) {
                $errors[] = "Max students must be between 1 and 100.";
            }

            // Check if section exists and is active
            $query = "
                 SELECT section_name, academic_year 
                 FROM sections 
                 WHERE section_id = :section_id 
                 AND department_id = :department_id 
                 AND is_active = 1
             ";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':section_id' => $section_id,
                ':department_id' => $departmentId
            ]);
            $section = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$section) {
                $errors[] = "Section not found or not active.";
                error_log("editSection: Section ID $section_id not found or inactive for department_id: $departmentId");
            }

            // Check for duplicate section name (excluding current section)
            if (empty($errors)) {
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
                    ':section_name' => $section_name,
                    ':academic_year' => $section['academic_year'],
                    ':section_id' => $section_id
                ]);
                if ($stmt->fetchColumn() > 0) {
                    $errors[] = "A section with this name already exists for this academic year.";
                }
            }

            if (!empty($errors)) {
                error_log("editSection: Validation errors - " . implode(", ", $errors));
                $_SESSION['error'] = implode("<br>", $errors);
                return;
            }

            // Update section
            $query = "
                 UPDATE sections 
                 SET 
                     section_name = :section_name, 
                     year_level = :year_level, 
                     max_students = :max_students, 
                     updated_at = NOW()
                 WHERE section_id = :section_id
             ";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':section_name' => $section_name,
                ':year_level' => $year_level,
                ':max_students' => $max_students,
                ':section_id' => $section_id
            ]);

            $_SESSION['success'] = "Section '$section_name' updated successfully.";
            error_log("editSection: Section ID $section_id updated to '$section_name' for department_id: $departmentId");
        } catch (PDOException $e) {
            error_log("editSection: Error - " . $e->getMessage());
            $_SESSION['error'] = "Failed to update section.";
        }
    }

    /**
     * Remove a section (soft delete)
     */
    private function removeSection()
    {
        try {
            $section_id = (int)($_POST['section_id'] ?? 0);

            // Validate section exists and is active
            $query = "SELECT section_name FROM sections WHERE section_id = :section_id AND is_active = 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':section_id', $section_id, PDO::PARAM_INT);
            $stmt->execute();
            $section = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$section) {
                error_log("removeSection: Section ID $section_id not found or already inactive");
                $_SESSION['error'] = "Section not found.";
                return;
            }

            // Soft delete section
            $query = "UPDATE sections SET is_active = 0, updated_at = NOW() WHERE section_id = :section_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':section_id', $section_id, PDO::PARAM_INT);
            $stmt->execute();

            $_SESSION['success'] = "Section '{$section['section_name']}' removed successfully.";
            error_log("removeSection: Section ID $section_id ('{$section['section_name']}') set to inactive");
        } catch (PDOException $e) {
            error_log("removeSection: Error - " . $e->getMessage());
            $_SESSION['error'] = "Failed to remove section.";
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
            $chairId = $_SESSION['user_id'] ?? 0;
            $departmentId = $this->getChairDepartment($chairId);

            // Initialize variables
            $error = null;
            $success = null;
            $courses = [];
            $programs = [];
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
            $perPage = 15;
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
                    $codeCheckStmt->execute(['course_code' => $data['course_code'], 'course_id' => $courseId]);
                    if ($codeCheckStmt->fetchColumn()) {
                        $errors[] = "Course code already exists.";
                    }

                    // Validate program_id belongs to the chair's department
                    if ($data['program_id']) {
                        $progCheckStmt = $this->db->prepare("SELECT program_id FROM programs WHERE program_id = :program_id AND department_id = :department_id");
                        $progCheckStmt->execute(['program_id' => $data['program_id'], 'department_id' => $departmentId]);
                        if (!$progCheckStmt->fetchColumn()) {
                            $errors[] = "Invalid program selected.";
                        }
                    }

                    if (empty($errors)) {
                        if ($courseId > 0) {
                            // Update existing course
                            $stmt = $this->db->prepare("
                            UPDATE courses SET 
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
                            WHERE course_id = :course_id
                        ");
                            $updateParams = [
                                'course_code' => $data['course_code'],
                                'course_name' => $data['course_name'],
                                'department_id' => $data['department_id'],
                                'program_id' => $data['program_id'],
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
                        } else {
                            // Add new course
                            $stmt = $this->db->prepare("
                            INSERT INTO courses 
                                (course_code, course_name, department_id, program_id, units, 
                                lecture_units, lab_units, lecture_hours, lab_hours, is_active) 
                            VALUES 
                                (:course_code, :course_name, :department_id, :program_id, :units, 
                                :lecture_units, :lab_units, :lecture_hours, :lab_hours, :is_active)
                        ");
                            $insertParams = [
                                'course_code' => $data['course_code'],
                                'course_name' => $data['course_name'],
                                'department_id' => $data['department_id'],
                                'program_id' => $data['program_id'],
                                'units' => $data['units'],
                                'lecture_units' => $data['lecture_units'],
                                'lab_units' => $data['lab_units'],
                                'lecture_hours' => $data['lecture_hours'],
                                'lab_hours' => $data['lab_hours'],
                                'is_active' => $data['is_active']
                            ];
                            error_log("Inserting course: Query = INSERT INTO courses ..., Params = " . json_encode($insertParams));
                            $stmt->execute($insertParams);
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
                    $toggleStmt = $this->db->prepare("
                    UPDATE courses 
                    SET is_active = NOT is_active 
                    WHERE course_id = :course_id 
                    AND (program_id IS NULL OR department_id = :department_id)
                ");
                    $toggleParams = [
                        'course_id' => $courseId,
                        'department_id' => $departmentId
                    ];
                    error_log("Toggling status: Query = UPDATE courses ..., Params = " . json_encode($toggleParams));
                    $toggleStmt->execute($toggleParams);
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

            // Handle search (only for GET requests, not during POST)
            $searchTerm = ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search']) && trim($_GET['search']) !== '') ? '%' . trim($_GET['search']) . '%' : null;

            // Fetch total number of courses for pagination
            $totalQuery = "
            SELECT COUNT(*) 
            FROM courses c 
            LEFT JOIN programs p ON c.program_id = p.program_id
            WHERE (c.program_id IS NULL OR c.department_id = :department_id)
        ";
            $totalParams = ['department_id' => $departmentId];
            if ($searchTerm) {
                $totalQuery .= " AND (c.course_code LIKE :search OR c.course_name LIKE :search OR p.program_name LIKE :search)";
                $totalParams['search'] = $searchTerm;
            }
            $totalStmt = $this->db->prepare($totalQuery);
            error_log("Total courses query: Query = $totalQuery, Params = " . json_encode($totalParams));
            $totalStmt->execute($totalParams);
            $totalCourses = $totalStmt->fetchColumn();
            $totalPages = max(1, ceil($totalCourses / $perPage));

            // Fetch courses with pagination
            $coursesQuery = "
            SELECT c.*, p.program_name, d.department_name 
            FROM courses c 
            LEFT JOIN programs p ON c.program_id = p.program_id 
            LEFT JOIN departments d ON c.department_id = d.department_id
            WHERE (c.program_id IS NULL OR c.department_id = :department_id)
        ";
            $coursesParams = ['department_id' => $departmentId];
            if ($searchTerm) {
                $coursesQuery .= " AND (c.course_code LIKE :search OR c.course_name LIKE :search OR p.program_name LIKE :search)";
                $coursesParams['search'] = $searchTerm;
            }
            $coursesQuery .= " ORDER BY c.course_code LIMIT :offset, :perPage";
            $coursesStmt = $this->db->prepare($coursesQuery);
            $coursesStmt->bindValue('department_id', $departmentId, PDO::PARAM_INT);
            $coursesStmt->bindValue('offset', $offset, PDO::PARAM_INT);
            $coursesStmt->bindValue('perPage', $perPage, PDO::PARAM_INT);
            if ($searchTerm) {
                $coursesStmt->bindValue('search', $searchTerm, PDO::PARAM_STR);
            }
            error_log("Courses query: Query = $coursesQuery, Params = " . json_encode($coursesParams));
            if ($coursesStmt->execute()) {
                $courses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                throw new PDOException("Courses query failed: " . implode(', ', $coursesStmt->errorInfo()));
            }

            // Fetch programs for the chair's department
            $programsStmt = $this->db->prepare("SELECT program_id, program_name 
            FROM programs WHERE department_id = :department_id");
            $programsStmt->execute(['department_id' => $departmentId]);
            $programs = $programsStmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch course data for editing
            if (isset($_GET['edit']) && $_GET['edit'] > 0) {
                try {
                    $courseId = intval($_GET['edit']);
                    $editStmt = $this->db->prepare("
                    SELECT c.* 
                    FROM courses c 
                    WHERE c.course_id = :course_id 
                    AND (c.program_id IS NULL OR c.department_id = :department_id)
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

    /**
     * Manage faculty
     */

    public function search()
    {
        // Ensure the request is a POST and AJAX
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if (!$isAjax || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log("search: Invalid request - Expected POST AJAX, got " . $_SERVER['REQUEST_METHOD']);
            header('Content-Type: application/json');
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
            exit;
        }

        try {
            // Get the chair's department and college
            $chairId = $_SESSION['user_id'] ?? null;
            if (!$chairId) {
                error_log("search: No user_id in session");
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['error' => 'No user session']);
                exit;
            }
            error_log("search: Processing for chairId: $chairId");

            $departmentId = $this->getChairDepartment($chairId);
            if (!$departmentId) {
                error_log("search: No department found for chairId: $chairId");
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
                error_log("search: No college found for departmentId: $departmentId");
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['error' => 'No college assigned']);
                exit;
            }
            error_log("search: College ID: $collegeId");

            // Get the search query for name
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            error_log("search: Search parameter - name: '$name'");

            // Validate input
            if (empty($name)) {
                error_log("search: No name provided");
                header('Content-Type: application/json');
                echo json_encode(['results' => [], 'includable' => []]);
                exit;
            }

            // Query for search results (faculty in the chair's department)
            $query = "
                 SELECT 
                     u.user_id,
                     u.employee_id,
                     u.first_name,
                     u.last_name,
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
                     LEFT JOIN departments d ON u.department_id = d.department_id
                     LEFT JOIN colleges c ON u.college_id = c.college_id
                     LEFT JOIN program_chairs pc ON u.user_id = pc.user_id AND pc.is_current = 1
                     LEFT JOIN programs p ON pc.program_id = p.program_id
                     LEFT JOIN deans ON u.user_id = deans.user_id AND deans.is_current = 1
                 WHERE 
                     u.role_id IN (4, 5, 6)
                     AND u.college_id = :college_id
                     AND u.department_id = :department_id
                     AND (u.first_name LIKE :name1 OR u.last_name LIKE :name2 OR CONCAT(u.first_name, ' ', u.last_name) LIKE :name3)
                 ORDER BY u.last_name, u.first_name LIMIT 10
             ";
            $params = [
                ':college_id' => $collegeId,
                ':department_id' => $departmentId,
                ':name1' => "%$name%",
                ':name2' => "%$name%",
                ':name3' => "%$name%"
            ];

            error_log("search: Executing query: $query");
            error_log("search: Query parameters: " . json_encode($params));
            error_log("search: Expected placeholders: 4 (:college_id, :department_id, :name1, :name2, :name3)");

            $stmt = $this->db->prepare($query);
            if (!$stmt) {
                $errorInfo = $this->db->errorInfo();
                error_log("search: Prepare Error: " . print_r($errorInfo, true));
                throw new Exception("Failed to prepare statement: " . $errorInfo[2]);
            }

            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("search: Found " . count($results) . " results");

            // Query for includable faculty (role_id = 6, not in chair's department, in same college or no department)
            $includableQuery = "
                 SELECT 
                     u.user_id,
                     u.employee_id,
                     u.first_name,
                     u.last_name,
                     r.role_name,
                     f.academic_rank,
                     f.employment_type,
                     d.department_name,
                     c.college_name
                 FROM 
                     users u
                     LEFT JOIN roles r ON u.role_id = r.role_id
                     LEFT JOIN faculty f ON u.user_id = f.user_id
                     LEFT JOIN departments d ON u.department_id = d.department_id
                     LEFT JOIN colleges c ON u.college_id = c.college_id
                 WHERE 
                     u.role_id = 6
                     AND (u.college_id = :college_id OR u.college_id IS NULL)
                     AND (u.department_id != :department_id OR u.department_id IS NULL)
                     AND (u.first_name LIKE :name1 OR u.last_name LIKE :name2 OR CONCAT(u.first_name, ' ', u.last_name) LIKE :name3)
                 ORDER BY u.last_name, u.first_name LIMIT 10
             ";
            $includableParams = [
                ':college_id' => $collegeId,
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
        // Check if this is an AJAX request
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

        // Get the chair's college_id
        $collegeId = null;
        if ($departmentId) {
            try {
                $collegeStmt = $this->db->prepare("SELECT college_id FROM departments WHERE department_id = :department_id");
                $collegeStmt->execute([':department_id' => $departmentId]);
                $collegeId = $collegeStmt->fetchColumn();

                if (!$collegeId) {
                    $error = "No college assigned to this department.";
                    error_log("faculty: No college found for department_id: $departmentId");
                }
            } catch (PDOException $e) {
                $error = "Failed to load college data: " . htmlspecialchars($e->getMessage());
                error_log("faculty: College Fetch Error: " . $e->getMessage());
            }
        } else {
            $error = "No department assigned to this chair.";
            error_log("faculty: No department assigned for chairId: $chairId");
        }

        // Fetch current faculty members in the chair's department
        if ($departmentId && $collegeId) {
            try {
                $query = "
                     SELECT 
                         u.user_id, 
                         u.employee_id, 
                         u.first_name, 
                         u.last_name, 
                         f.academic_rank, 
                         f.employment_type, 
                         d.department_name, 
                         c.college_name
                     FROM 
                         faculty f 
                         JOIN users u ON f.user_id = u.user_id 
                         JOIN departments d ON u.department_id = d.department_id
                         JOIN colleges c ON u.college_id = c.college_id
                     WHERE 
                         u.college_id = :college_id 
                         AND u.department_id = :department_id
                     ORDER BY 
                         u.last_name, u.first_name
                 ";
                $params = [
                    ':college_id' => $collegeId,
                    ':department_id' => $departmentId
                ];

                $stmt = $this->db->prepare($query);
                $stmt->execute($params);
                $faculty = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $error = "Failed to load faculty: " . htmlspecialchars($e->getMessage());
                error_log("faculty: Faculty Fetch Error: " . $e->getMessage());
            }
        }

        // Fetch colleges and departments for filters
        try {
            $collegesStmt = $this->db->query("SELECT college_id, college_name FROM colleges ORDER BY college_name");
            $colleges = $collegesStmt->fetchAll(PDO::FETCH_ASSOC);

            $departmentsStmt = $this->db->query("SELECT department_id, department_name, college_id FROM departments ORDER BY department_name");
            $departments = $departmentsStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error = "Failed to load colleges and departments: " . htmlspecialchars($e->getMessage());
            error_log("faculty: Colleges/Departments Fetch Error: " . $e->getMessage());
        }

        // Handle adding faculty
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_faculty'])) {
            try {
                $userId = intval($_POST['user_id']);
                if ($departmentId) {
                    $checkStmt = $this->db->prepare("SELECT user_id FROM users WHERE user_id = :user_id AND department_id = :department_id");
                    $checkStmt->execute([':user_id' => $userId, ':department_id' => $departmentId]);

                    if ($checkStmt->fetchColumn()) {
                        $error = "This faculty member is already in your department.";
                    } else {
                        $roleStmt = $this->db->prepare("SELECT role_id FROM users WHERE user_id = :user_id");
                        $roleStmt->execute([':user_id' => $userId]);
                        $roleId = $roleStmt->fetchColumn();

                        if ($roleId == 6) {
                            $updateStmt = $this->db->prepare("UPDATE users SET department_id = :department_id WHERE user_id = :user_id");
                            $updateStmt->execute([':department_id' => $departmentId, ':user_id' => $userId]);

                            $facultyCheckStmt = $this->db->prepare("SELECT faculty_id FROM faculty WHERE user_id = :user_id");
                            $facultyCheckStmt->execute([':user_id' => $userId]);
                            if ($facultyCheckStmt->fetchColumn()) {
                                $updateFacultyStmt = $this->db->prepare("UPDATE faculty SET department_id = :department_id WHERE user_id = :user_id");
                                $updateFacultyStmt->execute([':department_id' => $departmentId, ':user_id' => $userId]);
                            } else {
                                $insertFacultyStmt = $this->db->prepare("
                                     INSERT INTO faculty (user_id, employee_id, academic_rank, employment_type, department_id)
                                     SELECT user_id, employee_id, 'Instructor', 'Part-time', :department_id
                                     FROM users WHERE user_id = :user_id
                                 ");
                                $insertFacultyStmt->execute([':department_id' => $departmentId, ':user_id' => $userId]);
                            }

                            $success = "Faculty member added to your department successfully.";

                            // Refresh faculty list
                            $query = "
                                 SELECT 
                                     u.user_id, 
                                     u.employee_id, 
                                     u.first_name, 
                                     u.last_name, 
                                     f.academic_rank, 
                                     f.employment_type, 
                                     d.department_name, 
                                     c.college_name
                                 FROM 
                                     faculty f 
                                     JOIN users u ON f.user_id = u.user_id 
                                     JOIN departments d ON u.department_id = d.department_id
                                     JOIN colleges c ON u.college_id = c.college_id
                                 WHERE 
                                     u.college_id = :college_id 
                                     AND u.department_id = :department_id
                                 ORDER BY 
                                     u.last_name, u.first_name
                             ";
                            $params = [
                                ':college_id' => $collegeId,
                                ':department_id' => $departmentId
                            ];
                            $stmt = $this->db->prepare($query);
                            $stmt->execute($params);
                            $faculty = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        } else {
                            $error = "Only faculty members can be added to the department.";
                        }
                    }
                } else {
                    $error = "Cannot add faculty: No department assigned to this chair.";
                }
            } catch (PDOException $e) {
                $error = "Failed to add faculty: " . htmlspecialchars($e->getMessage());
                error_log("faculty: Add Faculty Error: " . $e->getMessage());
            }
        }

        // Handle removing faculty
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

                        // Refresh faculty list
                        $query = "
                             SELECT 
                                 u.user_id, 
                                 u.employee_id, 
                                 u.first_name, 
                                 u.last_name, 
                                 f.academic_rank, 
                                 f.employment_type, 
                                 d.department_name, 
                                 c.college_name
                             FROM 
                                 faculty f 
                                 JOIN users u ON f.user_id = u.user_id 
                                 JOIN departments d ON u.department_id = d.department_id
                                 JOIN colleges c ON u.college_id = c.college_id
                             WHERE 
                                 u.college_id = :college_id 
                                 AND u.department_id = :department_id
                             ORDER BY 
                                 u.last_name, u.first_name
                         ";
                        $params = [
                            ':college_id' => $collegeId,
                            ':department_id' => $departmentId
                        ];
                        $stmt = $this->db->prepare($query);
                        $stmt->execute($params);
                        $faculty = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    }
                } else {
                    $error = "Cannot remove faculty: No department assigned to this chair.";
                }
            } catch (PDOException $e) {
                $error = "Failed to remove faculty: " . htmlspecialchars($e->getMessage());
                error_log("faculty: Remove Faculty Error: " . $e->getMessage());
            }
        }

        // Render view for non-AJAX requests
        if (!$isAjax) {
            require_once __DIR__ . '/../views/chair/faculty.php';
        }
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
