<?php
// File: controllers/FacultyController.php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../services/SchedulingService.php';
require_once __DIR__ . '/../services/PdfService.php';

class FacultyController
{
    private $db;
    private $authService;
    private $schedulingService;
    private $pdfService;

    public function __construct()
    {
        error_log("FacultyController instantiated");
        $this->db = (new Database())->connect();
        if ($this->db === null) {
            error_log("Failed to connect to the database in FacultyController");
            die("Database connection failed. Please try again later.");
        }
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->authService = new AuthService($this->db);
        $this->schedulingService = new SchedulingService($this->db);
        $this->pdfService = new PdfService($this->db);
    }

    private function getFacultyId($userId)
    {
        $stmt = $this->db->prepare("SELECT faculty_id FROM faculty WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchColumn();
    }

    public function dashboard()
    {
        try {
            $userId = $_SESSION['user_id'];
            error_log("dashboard: Starting dashboard method for user_id: $userId");

            $facultyId = $this->getFacultyId($userId);
            if (!$facultyId) {
                error_log("dashboard: No faculty profile found for user_id: $userId");
                $error = "No faculty profile found for this user.";
                require_once __DIR__ . '/../views/faculty/dashboard.php';
                return;
            }

            // Get current semester
            $currentSemesterStmt = $this->db->query("SELECT semester_name, academic_year FROM semesters WHERE is_current = 1 LIMIT 1");
            $currentSemester = $currentSemesterStmt->fetch(PDO::FETCH_ASSOC);
            $semesterInfo = $currentSemester ? "{$currentSemester['semester_name']} Semester A.Y {$currentSemester['academic_year']}" : '2nd Semester 2024-2025';

            $deptStmt = $this->db->prepare("SELECT d.department_name FROM departments d JOIN users u ON d.department_id = u.department_id WHERE u.user_id = :user_id");
            $deptStmt->execute([':user_id' => $userId]);
            $departmentName = $deptStmt->fetchColumn();

            $teachingLoadStmt = $this->db->prepare("SELECT COUNT(*) FROM schedules s WHERE s.faculty_id = :faculty_id");
            $teachingLoadStmt->execute([':faculty_id' => $facultyId]);
            $teachingLoad = $teachingLoadStmt->fetchColumn();

            $pendingRequestsStmt = $this->db->prepare("SELECT COUNT(*) FROM schedule_requests sr WHERE sr.faculty_id = :faculty_id AND sr.status = 'pending'");
            $pendingRequestsStmt->execute([':faculty_id' => $facultyId]);
            $pendingRequests = $pendingRequestsStmt->fetchColumn();

            $recentSchedulesStmt = $this->db->prepare("
            SELECT s.schedule_id, c.course_code, c.course_name, r.room_name, s.day_of_week, s.start_time, s.end_time, s.schedule_type, sec.section_name
            FROM schedules s
            JOIN courses c ON s.course_id = c.course_id
            LEFT JOIN sections sec ON s.section_id = sec.section_id
            LEFT JOIN classrooms r ON s.room_id = r.room_id
            WHERE s.faculty_id = :faculty_id
            ORDER BY s.created_at DESC
            LIMIT 5
        ");
            $recentSchedulesStmt->execute([':faculty_id' => $facultyId]);
            $recentSchedules = $recentSchedulesStmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("dashboard: Fetched " . count($recentSchedules) . " recent schedules for faculty_id $facultyId");

            // NEW: Teaching Hours Distribution (Option 1)
            $teachingHoursStmt = $this->db->prepare("
            SELECT 
                s.day_of_week,
                SUM(TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time) / 60.0) as total_hours,
                COUNT(*) as class_count
            FROM schedules s 
            WHERE s.faculty_id = :faculty_id 
            GROUP BY s.day_of_week
        ");
            $teachingHoursStmt->execute([':faculty_id' => $facultyId]);
            $teachingHoursData = $teachingHoursStmt->fetchAll(PDO::FETCH_ASSOC);

            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $teachingHours = array_fill_keys($days, 0);
            $classCount = array_fill_keys($days, 0);

            foreach ($teachingHoursData as $row) {
                $teachingHours[$row['day_of_week']] = round((float)$row['total_hours'], 1);
                $classCount[$row['day_of_week']] = (int)$row['class_count'];
            }

            $teachingHoursJson = json_encode(array_values($teachingHours));
            $classCountJson = json_encode(array_values($classCount));

            // Calculate total weekly hours
            $totalWeeklyHours = array_sum($teachingHours);

            // Pass variables to the view
            require_once __DIR__ . '/../views/faculty/dashboard.php';
        } catch (Exception $e) {
            error_log("dashboard: Full error: " . $e->getMessage());
            http_response_code(500);
            echo "Error loading dashboard: " . htmlspecialchars($e->getMessage());
            exit;
        }
    }

    /**
     * Display Faculty's teaching schedule
     */
    public function mySchedule()
    {
        try {
            $userId = $_SESSION['user_id'];
            error_log("mySchedule: Starting mySchedule method for user_id: $userId");

            // Handle download requests
            if (isset($_GET['action']) && $_GET['action'] === 'download') {
                $this->handleDownload($userId);
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
            $facultyStmt->execute([$userId]);
            $faculty = $facultyStmt->fetch(PDO::FETCH_ASSOC);

            if (!$faculty) {
                $error = "No faculty profile found for this user.";
                require_once __DIR__ . '/../views/faculty/my_schedule.php';
                return;
            }

            $facultyId = $faculty['faculty_id'];
            $facultyName = trim($faculty['faculty_name']);
            $facultyPosition = $faculty['academic_rank'] ?? 'Not Specified';
            $employmentType = $faculty['employment_type'] ?? 'Regular';

            // Get department and college details from department_instructors table
            $deptStmt = $this->db->prepare("
                SELECT d.department_name, c.college_name 
                FROM department_instructors dn 
                JOIN departments d ON dn.department_id = d.department_id 
                JOIN colleges c ON d.college_id = c.college_id 
                WHERE dn.user_id = ? AND dn.is_current = 1
            ");
            $deptStmt->execute([$userId]);
            $department = $deptStmt->fetch(PDO::FETCH_ASSOC);
            $departmentName = $department['department_name'] ?? 'Not Assigned';
            $collegeName = $department['college_name'] ?? 'Not Assigned';

            $semesterStmt = $this->db->query("SELECT semester_id, semester_name, academic_year FROM semesters WHERE is_current = 1");
            $semester = $semesterStmt->fetch(PDO::FETCH_ASSOC);

            if (!$semester) {
                error_log("mySchedule: No current semester found");
                $error = "No current semester defined. Please contact the administrator to set the current semester.";
                require_once __DIR__ . '/../views/faculty/my_schedule.php';
                return;
            }

            $semesterId = $semester['semester_id'];
            $semesterName = $semester['semester_name'] . ' Semester, A.Y ' . $semester['academic_year'];
            error_log("mySchedule: Current semester ID: $semesterId, Name: $semesterName");

            // Get schedules with grouped days and better data structure
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

            // Group schedules by course, time, and room to combine days
            $groupedSchedules = [];
            foreach ($rawSchedules as $schedule) {
                $key = $schedule['course_code'] . '|' . $schedule['start_time'] . '|' . $schedule['end_time'] . '|' . $schedule['schedule_type'] . '|' . $schedule['section_name'];

                if (!isset($groupedSchedules[$key])) {
                    $groupedSchedules[$key] = $schedule;
                    $groupedSchedules[$key]['days'] = [];
                }

                $groupedSchedules[$key]['days'][] = $schedule['day_of_week'];
            }

            // Format days and create final schedule array
            $schedules = [];
            foreach ($groupedSchedules as $schedule) {
                $schedule['day_of_week'] = $this->schedulingService->formatScheduleDays(implode(', ', $schedule['days']));
                unset($schedule['days']);
                $schedules[] = $schedule;
            }

            error_log("mySchedule: Fetched " . count($schedules) . " grouped schedules for faculty_id $facultyId in semester $semesterId");

            $showAllSchedules = false;
            if (empty($schedules)) {
                error_log("mySchedule: No schedules found for current semester, trying to fetch all schedules");
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
                    WHERE s.faculty_id = ?
                    GROUP BY s.schedule_id, c.course_code, c.course_name, r.room_name, 
                             s.start_time, s.end_time, s.schedule_type, sec.section_name
                    ORDER BY c.course_code, s.start_time
                ");
                $schedulesStmt->execute([$facultyId]);
                $rawSchedules = $schedulesStmt->fetchAll(PDO::FETCH_ASSOC);

                // Same grouping logic
                $groupedSchedules = [];
                foreach ($rawSchedules as $schedule) {
                    $key = $schedule['course_code'] . '|' . $schedule['start_time'] . '|' . $schedule['end_time'] . '|' . $schedule['schedule_type'] . '|' . $schedule['section_name'];

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

                $showAllSchedules = true;
                error_log("mySchedule: Fetched " . count($schedules) . " total grouped schedules for faculty_id $facultyId");
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
                'total_lecture_hours' => $totalLectureHours,
                'total_laboratory_hours' => $totalLabHours,
                'total_laboratory_hours_x075' => $totalLabHoursX075,
                'no_of_preparation' => $noOfPreparations,
                'actual_teaching_load' => $actualTeachingLoad,
                'equiv_teaching_load' => $equivalTeachingLoad,
                'total_working_load' => $totalWorkingLoad,
                'excess_hours' => $excessHours
            ];

            require_once __DIR__ . '/../views/faculty/my_schedule.php';
        } catch (Exception $e) {
            error_log("mySchedule: Full error: " . $e->getMessage());
            http_response_code(500);
            echo "Error loading schedule: " . htmlspecialchars($e->getMessage());
            exit;
        }
    }

    private function handleDownload($facultyId)
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
        $facultyStmt->execute([$facultyId]);
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
        FROM faculty f
        JOIN users u ON f.user_id = u.user_id 
        JOIN departments d ON u.department_id = d.department_id 
        JOIN colleges c ON d.college_id = c.college_id 
        WHERE f.user_id = ? 
        ");
        $deptStmt->execute([$facultyId]);
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
        if ($format === 'pdf') {
            $this->schedulingService->generateOfficialPDFFaculty($schedules, $semesterName, $collegeName, $facultyData, $facultyName);
        } elseif ($format === 'excel') {
            $this->schedulingService->generateOfficialExcelFaculty($schedules, $semesterName, $collegeName, $facultyData, $facultyName);
        }
    }
    /**
     * Submit a schedule request
     */
    public function submitScheduleRequest()
    {
        error_log("submitScheduleRequest: Starting submitScheduleRequest method");
        $userId = $_SESSION['user_id'];
        $facultyId = $this->getFacultyId($userId);

        if (!$facultyId) {
            error_log("submitScheduleRequest: No faculty profile found for user_id: $userId");
            $error = "No faculty profile found for this user.";
            require_once __DIR__ . '/../views/faculty/schedule_request.php';
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $data = [
                    'faculty_id' => $facultyId,
                    'course_id' => intval($_POST['course_id'] ?? 0),
                    'preferred_day' => $_POST['preferred_day'] ?? '',
                    'preferred_start_time' => $_POST['preferred_start_time'] ?? '',
                    'preferred_end_time' => $_POST['preferred_end_time'] ?? '',
                    'room_preference' => $_POST['room_preference'] ?? '',
                    'schedule_type' => $_POST['schedule_type'] ?? 'F2F',
                    'semester_id' => intval($_POST['semester_id'] ?? 0),
                    'reason' => trim($_POST['reason'] ?? '')
                ];

                error_log("submitScheduleRequest: POST data - " . json_encode($data));

                $errors = [];
                if ($data['course_id'] < 1) {
                    $errors[] = "Course is required.";
                }
                if (!in_array($data['preferred_day'], ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'])) {
                    $errors[] = "Invalid preferred day.";
                }
                if (!preg_match('/^\d{2}:\d{2}$/', $data['preferred_start_time']) || !preg_match('/^\d{2}:\d{2}$/', $data['preferred_end_time'])) {
                    $errors[] = "Invalid time format.";
                }
                if ($data['semester_id'] < 1) {
                    $errors[] = "Semester is required.";
                }
                if (empty($data['reason'])) {
                    $errors[] = "Reason for request is required.";
                }
                if (!in_array($data['schedule_type'], ['F2F', 'Online', 'Hybrid', 'Asynchronous'])) {
                    $errors[] = "Invalid schedule type.";
                }

                if (empty($errors)) {
                    $stmt = $this->db->prepare("
                          INSERT INTO schedule_requests (faculty_id, course_id, preferred_day, preferred_start_time, preferred_end_time, room_preference, schedule_type, semester_id, reason, status, created_at)
                          VALUES (:faculty_id, :course_id, :preferred_day, :preferred_start_time, :preferred_end_time, :room_preference, :schedule_type, :semester_id, :reason, 'pending', NOW())
                      ");
                    $stmt->execute($data);

                    error_log("submitScheduleRequest: Schedule request submitted successfully");
                    header('Location: /faculty/schedule/requests?success=Schedule request submitted successfully');
                    exit;
                } else {
                    error_log("submitScheduleRequest: Validation errors - " . implode(", ", $errors));
                    $error = implode("<br>", $errors);
                }
            } catch (Exception $e) {
                error_log("submitScheduleRequest: Error - " . $e->getMessage());
                $error = $e->getMessage();
            }
        }

        try {
            $coursesStmt = $this->db->prepare("SELECT c.course_id, c.course_code, c.course_name 
                                                 FROM courses c 
                                                 JOIN users u ON c.department_id = u.department_id 
                                                 WHERE u.user_id = :user_id");
            $coursesStmt->execute([':user_id' => $userId]);
            $courses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);

            $semesters = $this->db->query("SELECT semester_id, CONCAT(semester_name, ' ', academic_year) AS semester_name FROM semesters")->fetchAll(PDO::FETCH_ASSOC);

            require_once __DIR__ . '/../views/faculty/schedule_request.php';
        } catch (PDOException $e) {
            error_log("submitScheduleRequest: Error loading form data - " . $e->getMessage());
            $error = "Failed to load form data.";
            require_once __DIR__ . '/../views/faculty/schedule_request.php';
        }
    }

    /**
     * View schedule requests
     */
    public function getScheduleRequests()
    {
        error_log("getScheduleRequests: Starting getScheduleRequests method");
        try {
            $userId = $_SESSION['user_id'];
            $facultyId = $this->getFacultyId($userId);
            if (!$facultyId) {
                error_log("getScheduleRequests: No faculty profile found for user_id: $userId");
                $error = "No faculty profile found for this user.";
                require_once __DIR__ . '/../views/faculty/schedule_requests.php';
                return;
            }

            $requestsStmt = $this->db->prepare("
                  SELECT sr.request_id, c.course_code, sr.preferred_day, sr.preferred_start_time, sr.preferred_end_time, 
                         sr.room_preference, sr.schedule_type, sr.status, sr.reason, s.semester_name
                  FROM schedule_requests sr
                  JOIN courses c ON sr.course_id = c.course_id
                  JOIN semesters s ON sr.semester_id = s.semester_id
                  WHERE sr.faculty_id = :faculty_id
                  ORDER BY sr.created_at DESC
              ");
            $requestsStmt->execute([':faculty_id' => $facultyId]);
            $requests = $requestsStmt->fetchAll(PDO::FETCH_ASSOC);

            require_once __DIR__ . '/../views/faculty/schedule_requests.php';
        } catch (Exception $e) {
            error_log("getScheduleRequests: Error - " . $e->getMessage());
            $error = "Failed to load schedule requests.";
            require_once __DIR__ . '/../views/faculty/schedule_requests.php';
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
                    header('Location: /faculty/profile');
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

                header('Location: /faculty/profile');
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

            require_once __DIR__ . '/../views/faculty/profile.php';
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
                'role_name' => 'Faculty',
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
            require_once __DIR__ . '/../views/faculty/profile.php';
        }
    }

    private function handleProfilePictureUpload($userId)
    {
        if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] == UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $file = $_FILES['profile_picture'];
        $allowedTypes = ['image/jpeg', 'image/png'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        if (!in_array($file['type'], $allowedTypes)) {
            error_log("profile: Invalid file type for user_id: $userId - " . $file['type']);
            return "Error: Only JPEG and PNG files are allowed.";
        }

        if ($file['size'] > $maxSize) {
            error_log("profile: File size exceeds limit for user_id: $userId - " . $file['size']);
            return "Error: File size exceeds 2MB limit.";
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = "user_{$userId}_" . time() . ".{$ext}";
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/profile_pictures/';
        $uploadPath = $uploadDir . $filename;

        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                error_log("profile: Failed to create upload facultyy: $uploadDir");
                return "Error: Failed to create upload facultyy.";
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
            return "/uploads/profile_pictures/{$filename}";
        } else {
            error_log("profile: Failed to move uploaded file for user_id: $userId to $uploadPath");
            return "Error: Failed to upload file.";
        }
    }

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

    public function settings()
    {
        if (!$this->authService->isLoggedIn()) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Please log in to access settings'];
            header('Location: /login');
            exit;
        }

        $userId = $_SESSION['user_id'];
        $facultyId = $this->getFacultyId($userId);
        $departmentId = $_SESSION['department_id'];
        $csrfToken = $this->authService->generateCsrfToken();
        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->authService->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid CSRF token'];
                header('Location: /faculty/settings');
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
                            $this->logActivity($facultyId, $departmentId, 'Change Password', 'Changed account password', 'users', $userId);
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
                            $this->logActivity($facultyId, $departmentId, 'Change Email', 'Changed account email', 'users', $userId);
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
            header('Location: /faculty/settings');
            exit;
        }

        require_once __DIR__ . '/../views/faculty/settings.php';
    }

    public function teachingLoadReport()
    {
        try {
            $userId = $_SESSION['user_id'];
            $facultyId = $this->getFacultyId($userId);

            if (!$facultyId) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'No faculty profile found'];
                header('Location: /faculty/dashboard');
                exit;
            }

            // Get semester filter - FIXED: Use proper parameter handling
            $semesterId = $_GET['semester_id'] ?? null;

            // Build the query with proper parameter handling
            $semesterFilter = "";
            $params = [$facultyId];

            if ($semesterId) {
                $semesterFilter = "AND s.semester_id = ?";
                $params[] = $semesterId;
            } else {
                // Get current semester if no filter
                $semesterStmt = $this->db->query("SELECT semester_id FROM semesters WHERE is_current = 1");
                $currentSemester = $semesterStmt->fetch(PDO::FETCH_ASSOC);
                if ($currentSemester) {
                    $semesterFilter = "AND s.semester_id = ?";
                    $params[] = $currentSemester['semester_id'];
                    $semesterId = $currentSemester['semester_id'];
                }
            }

            // Fetch faculty basic info
            $facultyStmt = $this->db->prepare("
            SELECT f.*, 
                   CONCAT(COALESCE(u.title, ''), ' ', u.first_name, ' ', 
                          COALESCE(u.middle_name, ''), ' ', u.last_name, ' ', 
                          COALESCE(u.suffix, '')) AS faculty_name,
                   d.department_name,
                   c.college_name
            FROM faculty f 
            JOIN users u ON f.user_id = u.user_id 
            LEFT JOIN departments d ON u.department_id = d.department_id
            LEFT JOIN colleges c ON d.college_id = c.college_id
            WHERE f.faculty_id = ?
        ");
            $facultyStmt->execute([$facultyId]);
            $faculty = $facultyStmt->fetch(PDO::FETCH_ASSOC);

            if (!$faculty) {
                throw new Exception("Faculty profile not found");
            }

            // Fetch teaching load data - FIXED: Use proper parameter binding
            $teachingLoadStmt = $this->db->prepare("
            SELECT 
                c.course_code,
                c.course_name,
                c.units,
                s.schedule_type,
                s.day_of_week,
                s.start_time,
                s.end_time,
                TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time) / 60 AS duration_hours,
                r.room_name,
                sec.section_name,
                sec.current_students,
                sem.semester_name,
                sem.academic_year,
                CASE 
                    WHEN s.schedule_type = 'Laboratory' THEN TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time) / 60 * 0.75
                    ELSE TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time) / 60
                END AS computed_hours
            FROM schedules s
            JOIN courses c ON s.course_id = c.course_id
            LEFT JOIN sections sec ON s.section_id = sec.section_id
            LEFT JOIN classrooms r ON s.room_id = r.room_id
            JOIN semesters sem ON s.semester_id = sem.semester_id
            WHERE s.faculty_id = ? $semesterFilter
            ORDER BY c.course_code, s.day_of_week, s.start_time
        ");

            error_log("teachingLoadReport: Executing query with params: " . print_r($params, true));
            $teachingLoadStmt->execute($params);
            $teachingData = $teachingLoadStmt->fetchAll(PDO::FETCH_ASSOC);

            // Calculate totals
            $totalHours = 0;
            $totalComputedHours = 0;
            $lectureHours = 0;
            $labHours = 0;
            $courseCount = 0;
            $preparations = [];

            foreach ($teachingData as $row) {
                $totalHours += $row['duration_hours'];
                $totalComputedHours += $row['computed_hours'];

                if ($row['schedule_type'] === 'Lecture') {
                    $lectureHours += $row['duration_hours'];
                } else {
                    $labHours += $row['duration_hours'];
                }

                $preparations[$row['course_code']] = true;
            }

            $courseCount = count($preparations);
            $equivalentLoad = $faculty['equiv_teaching_load'] ?? 0;
            $totalWorkingLoad = $totalComputedHours + $equivalentLoad;

            // Get available semesters for filter
            $semestersStmt = $this->db->query("
            SELECT semester_id, CONCAT(semester_name, ' ', academic_year) as semester_display 
            FROM semesters 
            ORDER BY academic_year DESC, semester_name
        ");
            $semesters = $semestersStmt->fetchAll(PDO::FETCH_ASSOC);

            // Get semester info for display
            $semesterInfo = "Current Semester";
            if ($semesterId) {
                $semesterDisplayStmt = $this->db->prepare("SELECT CONCAT(semester_name, ' ', academic_year) as semester_display FROM semesters WHERE semester_id = ?");
                $semesterDisplayStmt->execute([$semesterId]);
                $semesterDisplay = $semesterDisplayStmt->fetch(PDO::FETCH_ASSOC);
                if ($semesterDisplay) {
                    $semesterInfo = $semesterDisplay['semester_display'];
                }
            }

            require_once __DIR__ . '/../views/faculty/reports/teaching_load.php';
        } catch (Exception $e) {
            error_log("teachingLoadReport: Error - " . $e->getMessage());
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to generate teaching load report: ' . $e->getMessage()];
            header('Location: /faculty/dashboard');
            exit;
        }
    }

    public function specializationsReport()  // Changed from specializationReport()
    {
        try {
            $userId = $_SESSION['user_id'];
            $facultyId = $this->getFacultyId($userId);

            if (!$facultyId) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'No faculty profile found'];
                header('Location: /faculty/dashboard');
                exit;
            }

            // Fetch faculty info
            $facultyStmt = $this->db->prepare("
            SELECT 
                CONCAT(COALESCE(u.title, ''), ' ', u.first_name, ' ', 
                       COALESCE(u.middle_name, ''), ' ', u.last_name, ' ', 
                       COALESCE(u.suffix, '')) AS faculty_name,
                d.department_name,
                c.college_name,
                f.academic_rank,
                f.employment_type
            FROM faculty f 
            JOIN users u ON f.user_id = u.user_id 
            LEFT JOIN departments d ON u.department_id = d.department_id
            LEFT JOIN colleges c ON d.college_id = c.college_id
            WHERE f.faculty_id = ?
        ");
            $facultyStmt->execute([$facultyId]);
            $faculty = $facultyStmt->fetch(PDO::FETCH_ASSOC);

            if (!$faculty) {
                throw new Exception("Faculty profile not found");
            }

            // Fetch specializations
            $specializationStmt = $this->db->prepare("
            SELECT 
                s.specialization_id,
                c.course_code,
                c.course_name,
                c.units,
                d.department_name,
                s.expertise_level,
                s.created_at,
                s.updated_at
            FROM specializations s
            JOIN courses c ON s.course_id = c.course_id
            JOIN departments d ON c.department_id = d.department_id
            WHERE s.faculty_id = ?
            ORDER BY c.course_code
        ");
            $specializationStmt->execute([$facultyId]);
            $specializations = $specializationStmt->fetchAll(PDO::FETCH_ASSOC);

            // Count by expertise level
            $expertiseCount = [
                'beginner' => 0,
                'intermediate' => 0,
                'expert' => 0
            ];

            foreach ($specializations as $spec) {
                $level = strtolower($spec['expertise_level']);
                if (isset($expertiseCount[$level])) {
                    $expertiseCount[$level]++;
                }
            }

            require_once __DIR__ . '/../views/faculty/reports/specializations.php';
        } catch (Exception $e) {
            error_log("specializationsReport: Error - " . $e->getMessage());
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to generate specialization report'];
            header('Location: /faculty/dashboard');
            exit;
        }
    }

    /**
     * Generate schedule report
     */
    public function scheduleReport()
    {
        try {
            $userId = $_SESSION['user_id'];
            $facultyId = $this->getFacultyId($userId);

            if (!$facultyId) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'No faculty profile found'];
                header('Location: /faculty/dashboard');
                exit;
            }

            // Get semester filter
            $semesterId = $_GET['semester_id'] ?? null;

            // Get current semester if no filter
            if (!$semesterId) {
                $semesterStmt = $this->db->query("SELECT semester_id FROM semesters WHERE is_current = 1");
                $currentSemester = $semesterStmt->fetch(PDO::FETCH_ASSOC);
                $semesterId = $currentSemester['semester_id'] ?? null;
            }

            // Fetch faculty info
            $facultyStmt = $this->db->prepare("
            SELECT 
                CONCAT(COALESCE(u.title, ''), ' ', u.first_name, ' ', 
                       COALESCE(u.middle_name, ''), ' ', u.last_name, ' ', 
                       COALESCE(u.suffix, '')) AS faculty_name,
                d.department_name,
                c.college_name,
                f.academic_rank
            FROM faculty f 
            JOIN users u ON f.user_id = u.user_id 
            LEFT JOIN departments d ON u.department_id = d.department_id
            LEFT JOIN colleges c ON d.college_id = c.college_id
            WHERE f.faculty_id = ?
        ");
            $facultyStmt->execute([$facultyId]);
            $faculty = $facultyStmt->fetch(PDO::FETCH_ASSOC);

            // Fetch schedule data grouped by day and time
            $scheduleStmt = $this->db->prepare("
            SELECT 
                s.day_of_week,
                s.start_time,
                s.end_time,
                c.course_code,
                c.course_name,
                s.schedule_type,
                r.room_name,
                sec.section_name,
                sec.year_level,
                sec.current_students,
                sem.semester_name,
                sem.academic_year
            FROM schedules s
            JOIN courses c ON s.course_id = c.course_id
            LEFT JOIN sections sec ON s.section_id = sec.section_id
            LEFT JOIN classrooms r ON s.room_id = r.room_id
            JOIN semesters sem ON s.semester_id = sem.semester_id
            WHERE s.faculty_id = ? AND s.semester_id = ?
            ORDER BY 
                FIELD(s.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'),
                s.start_time
        ");

            $scheduleStmt->execute([$facultyId, $semesterId]);
            $scheduleData = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC);

            // Group by day for better display
            $scheduleByDay = [];
            foreach ($scheduleData as $class) {
                $day = $class['day_of_week'];
                if (!isset($scheduleByDay[$day])) {
                    $scheduleByDay[$day] = [];
                }
                $scheduleByDay[$day][] = $class;
            }

            // Get available semesters for filter
            $semestersStmt = $this->db->query("
            SELECT semester_id, CONCAT(semester_name, ' ', academic_year) as semester_display 
            FROM semesters 
            ORDER BY academic_year DESC, semester_name
        ");
            $semesters = $semestersStmt->fetchAll(PDO::FETCH_ASSOC);

            require_once __DIR__ . '/../views/faculty/reports/schedule.php';
        } catch (Exception $e) {
            error_log("scheduleReport: Error - " . $e->getMessage());
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to generate schedule report'];
            header('Location: /faculty/dashboard');
            exit;
        }
    }

    /**
     * Generate specialization report
     */
    public function specializationReport()
    {
        try {
            $userId = $_SESSION['user_id'];
            $facultyId = $this->getFacultyId($userId);

            if (!$facultyId) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'No faculty profile found'];
                header('Location: /faculty/dashboard');
                exit;
            }

            // Fetch faculty info
            $facultyStmt = $this->db->prepare("
            SELECT 
                CONCAT(COALESCE(u.title, ''), ' ', u.first_name, ' ', 
                       COALESCE(u.middle_name, ''), ' ', u.last_name, ' ', 
                       COALESCE(u.suffix, '')) AS faculty_name,
                d.department_name,
                c.college_name,
                f.academic_rank,
                f.employment_type
            FROM faculty f 
            JOIN users u ON f.user_id = u.user_id 
            LEFT JOIN departments d ON u.department_id = d.department_id
            LEFT JOIN colleges c ON d.college_id = c.college_id
            WHERE f.faculty_id = ?
        ");
            $facultyStmt->execute([$facultyId]);
            $faculty = $facultyStmt->fetch(PDO::FETCH_ASSOC);

            // Fetch specializations
            $specializationStmt = $this->db->prepare("
            SELECT 
                s.specialization_id,
                c.course_code,
                c.course_name,
                c.units,
                d.department_name,
                s.expertise_level,
                s.created_at,
                s.updated_at
            FROM specializations s
            JOIN courses c ON s.course_id = c.course_id
            JOIN departments d ON c.department_id = d.department_id
            WHERE s.faculty_id = ?
            ORDER BY c.course_code
        ");
            $specializationStmt->execute([$facultyId]);
            $specializations = $specializationStmt->fetchAll(PDO::FETCH_ASSOC);

            // Count by expertise level
            $expertiseCount = [
                'beginner' => 0,
                'intermediate' => 0,
                'expert' => 0
            ];

            foreach ($specializations as $spec) {
                $level = strtolower($spec['expertise_level']);
                if (isset($expertiseCount[$level])) {
                    $expertiseCount[$level]++;
                }
            }

            require_once __DIR__ . '/../views/faculty/reports/specializations.php';
        } catch (Exception $e) {
            error_log("specializationReport: Error - " . $e->getMessage());
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to generate specialization report'];
            header('Location: /faculty/dashboard');
            exit;
        }
    }

    /**
     * Download report in various formats
     */
    public function downloadReport()
    {
        try {
            $userId = $_SESSION['user_id'];
            $facultyId = $this->getFacultyId($userId);

            if (!$facultyId) {
                http_response_code(404);
                echo "No faculty profile found";
                exit;
            }

            $reportType = $_GET['type'] ?? '';
            $format = $_GET['format'] ?? 'pdf';
            $semesterId = $_GET['semester_id'] ?? null;

            switch ($reportType) {
                case 'teaching_load':
                    $this->downloadTeachingLoadReport($facultyId, $format, $semesterId);
                    break;
                case 'schedule':
                    $this->downloadScheduleReport($facultyId, $format, $semesterId);
                    break;
                case 'specializations':
                    $this->downloadSpecializationReport($facultyId, $format);
                    break;
                default:
                    http_response_code(400);
                    echo "Invalid report type";
                    exit;
            }
        } catch (Exception $e) {
            error_log("downloadReport: Error - " . $e->getMessage());
            http_response_code(500);
            echo "Failed to generate download";
            exit;
        }
    }


    // Then replace the download methods with these:

    /**
     * Download teaching load report
     */
    private function downloadTeachingLoadReport($facultyId, $format, $semesterId = null)
    {
        try {
            // Fetch faculty data
            $facultyStmt = $this->db->prepare("
            SELECT f.*, 
                   CONCAT(COALESCE(u.title, ''), ' ', u.first_name, ' ', 
                          COALESCE(u.middle_name, ''), ' ', u.last_name, ' ', 
                          COALESCE(u.suffix, '')) AS faculty_name,
                   d.department_name,
                   c.college_name
            FROM faculty f 
            JOIN users u ON f.user_id = u.user_id 
            LEFT JOIN departments d ON u.department_id = d.department_id
            LEFT JOIN colleges c ON d.college_id = c.college_id
            WHERE f.faculty_id = ?
        ");
            $facultyStmt->execute([$facultyId]);
            $faculty = $facultyStmt->fetch(PDO::FETCH_ASSOC);

            if (!$faculty) {
                throw new Exception("Faculty not found");
            }

            // Get semester info
            if (!$semesterId) {
                $semesterStmt = $this->db->query("SELECT semester_id, semester_name, academic_year FROM semesters WHERE is_current = 1");
                $semester = $semesterStmt->fetch(PDO::FETCH_ASSOC);
                $semesterId = $semester['semester_id'] ?? null;
            } else {
                $semesterStmt = $this->db->prepare("SELECT semester_id, semester_name, academic_year FROM semesters WHERE semester_id = ?");
                $semesterStmt->execute([$semesterId]);
                $semester = $semesterStmt->fetch(PDO::FETCH_ASSOC);
            }

            $semesterName = $semester ? "{$semester['semester_name']} Semester, A.Y {$semester['academic_year']}" : 'Current Semester';

            // Fetch teaching load data
            $teachingStmt = $this->db->prepare("
            SELECT 
                c.course_code,
                c.course_name,
                c.units,
                s.schedule_type,
                s.day_of_week,
                s.start_time,
                s.end_time,
                TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time) / 60 AS duration_hours,
                r.room_name,
                sec.section_name,
                sec.current_students,
                CASE 
                    WHEN s.schedule_type = 'Laboratory' THEN TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time) / 60 * 0.75
                    ELSE TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time) / 60
                END AS computed_hours
            FROM schedules s
            JOIN courses c ON s.course_id = c.course_id
            LEFT JOIN sections sec ON s.section_id = sec.section_id
            LEFT JOIN classrooms r ON s.room_id = r.room_id
            WHERE s.faculty_id = ? AND s.semester_id = ?
            ORDER BY c.course_code, s.day_of_week, s.start_time
        ");
            $teachingStmt->execute([$facultyId, $semesterId]);
            $teachingData = $teachingStmt->fetchAll(PDO::FETCH_ASSOC);

            // Calculate totals
            $totalHours = 0;
            $totalComputedHours = 0;
            $lectureHours = 0;
            $labHours = 0;
            $preparations = [];

            foreach ($teachingData as $row) {
                $totalHours += $row['duration_hours'];
                $totalComputedHours += $row['computed_hours'];

                if ($row['schedule_type'] === 'Lecture') {
                    $lectureHours += $row['duration_hours'];
                } else {
                    $labHours += $row['duration_hours'];
                }

                $preparations[$row['course_code']] = true;
            }

            $courseCount = count($preparations);
            $equivalentLoad = $faculty['equiv_teaching_load'] ?? 0;
            $totalWorkingLoad = $totalComputedHours + $equivalentLoad;

            // Generate PDF
            $pdfService = new PdfService();

            if ($format === 'pdf') {
                $pdfContent = $this->generateTeachingLoadPdf($faculty, $teachingData, $semesterName, [
                    'total_hours' => $totalHours,
                    'total_computed_hours' => $totalComputedHours,
                    'lecture_hours' => $lectureHours,
                    'lab_hours' => $labHours,
                    'course_count' => $courseCount,
                    'equivalent_load' => $equivalentLoad,
                    'total_working_load' => $totalWorkingLoad
                ]);

                $filename = "teaching_load_{$faculty['faculty_name']}_" . date('Y-m-d') . ".pdf";
                $pdfService->sendAsDownload($pdfContent, $filename);
            } elseif ($format === 'excel') {
                // For Excel, you can create a simple CSV or implement Excel generation
                $this->generateTeachingLoadExcel($faculty, $teachingData, $semesterName, [
                    'total_hours' => $totalHours,
                    'total_computed_hours' => $totalComputedHours,
                    'lecture_hours' => $lectureHours,
                    'lab_hours' => $labHours,
                    'course_count' => $courseCount,
                    'equivalent_load' => $equivalentLoad,
                    'total_working_load' => $totalWorkingLoad
                ]);
            }
        } catch (Exception $e) {
            error_log("downloadTeachingLoadReport: Error - " . $e->getMessage());
            http_response_code(500);
            echo "Failed to generate teaching load report: " . $e->getMessage();
            exit;
        }
    }

    /**
     * Download schedule report
     */
    private function downloadScheduleReport($facultyId, $format, $semesterId = null)
    {
        try {
            // Fetch faculty data
            $facultyStmt = $this->db->prepare("
            SELECT 
                CONCAT(COALESCE(u.title, ''), ' ', u.first_name, ' ', 
                       COALESCE(u.middle_name, ''), ' ', u.last_name, ' ', 
                       COALESCE(u.suffix, '')) AS faculty_name,
                d.department_name,
                c.college_name,
                f.academic_rank
            FROM faculty f 
            JOIN users u ON f.user_id = u.user_id 
            LEFT JOIN departments d ON u.department_id = d.department_id
            LEFT JOIN colleges c ON d.college_id = c.college_id
            WHERE f.faculty_id = ?
        ");
            $facultyStmt->execute([$facultyId]);
            $faculty = $facultyStmt->fetch(PDO::FETCH_ASSOC);

            // Get semester info
            if (!$semesterId) {
                $semesterStmt = $this->db->query("SELECT semester_id, semester_name, academic_year FROM semesters WHERE is_current = 1");
                $semester = $semesterStmt->fetch(PDO::FETCH_ASSOC);
                $semesterId = $semester['semester_id'] ?? null;
            } else {
                $semesterStmt = $this->db->prepare("SELECT semester_id, semester_name, academic_year FROM semesters WHERE semester_id = ?");
                $semesterStmt->execute([$semesterId]);
                $semester = $semesterStmt->fetch(PDO::FETCH_ASSOC);
            }

            $semesterName = $semester ? "{$semester['semester_name']} Semester, A.Y {$semester['academic_year']}" : 'Current Semester';

            // Fetch schedule data
            $scheduleStmt = $this->db->prepare("
            SELECT 
                s.day_of_week,
                s.start_time,
                s.end_time,
                c.course_code,
                c.course_name,
                s.schedule_type,
                r.room_name,
                sec.section_name,
                sec.year_level,
                sec.current_students
            FROM schedules s
            JOIN courses c ON s.course_id = c.course_id
            LEFT JOIN sections sec ON s.section_id = sec.section_id
            LEFT JOIN classrooms r ON s.room_id = r.room_id
            WHERE s.faculty_id = ? AND s.semester_id = ?
            ORDER BY 
                FIELD(s.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'),
                s.start_time
        ");
            $scheduleStmt->execute([$facultyId, $semesterId]);
            $scheduleData = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC);

            // Generate PDF
            $pdfService = new PdfService();

            if ($format === 'pdf') {
                $pdfContent = $this->generateSchedulePdf($faculty, $scheduleData, $semesterName);
                $filename = "schedule_{$faculty['faculty_name']}_" . date('Y-m-d') . ".pdf";
                $pdfService->sendAsDownload($pdfContent, $filename);
            } elseif ($format === 'excel') {
                // Use enhanced version for better Excel compatibility
                $this->generateEnhancedScheduleExcel($faculty, $scheduleData, $semesterName, 'csv');
            }
        } catch (Exception $e) {
            error_log("downloadScheduleReport: Error - " . $e->getMessage());
            http_response_code(500);
            echo "Failed to generate schedule report: " . $e->getMessage();
            exit;
        }
    }

    /**
     * Download specialization report
     */
    private function downloadSpecializationReport($facultyId, $format)
    {
        try {
            // Fetch faculty data
            $facultyStmt = $this->db->prepare("
            SELECT 
                CONCAT(COALESCE(u.title, ''), ' ', u.first_name, ' ', 
                       COALESCE(u.middle_name, ''), ' ', u.last_name, ' ', 
                       COALESCE(u.suffix, '')) AS faculty_name,
                d.department_name,
                c.college_name,
                f.academic_rank,
                f.employment_type
            FROM faculty f 
            JOIN users u ON f.user_id = u.user_id 
            LEFT JOIN departments d ON u.department_id = d.department_id
            LEFT JOIN colleges c ON d.college_id = c.college_id
            WHERE f.faculty_id = ?
        ");
            $facultyStmt->execute([$facultyId]);
            $faculty = $facultyStmt->fetch(PDO::FETCH_ASSOC);

            // Fetch specializations
            $specializationStmt = $this->db->prepare("
            SELECT 
                s.specialization_id,
                c.course_code,
                c.course_name,
                c.units,
                d.department_name,
                s.expertise_level,
                s.created_at,
                s.updated_at
            FROM specializations s
            JOIN courses c ON s.course_id = c.course_id
            JOIN departments d ON c.department_id = d.department_id
            WHERE s.faculty_id = ?
            ORDER BY c.course_code
        ");
            $specializationStmt->execute([$facultyId]);
            $specializations = $specializationStmt->fetchAll(PDO::FETCH_ASSOC);

            // Generate PDF
            $pdfService = new PdfService();

            if ($format === 'pdf') {
                $pdfContent = $this->generateSpecializationPdf($faculty, $specializations);
                $filename = "specializations_{$faculty['faculty_name']}_" . date('Y-m-d') . ".pdf";
                $pdfService->sendAsDownload($pdfContent, $filename);
            } elseif ($format === 'excel') {
                $this->generateSpecializationExcel($faculty, $specializations);
            }
        } catch (Exception $e) {
            error_log("downloadSpecializationReport: Error - " . $e->getMessage());
            http_response_code(500);
            echo "Failed to generate specialization report: " . $e->getMessage();
            exit;
        }
    }

    /**
     * Generate teaching load PDF using PdfService
     */
    private function generateTeachingLoadPdf($faculty, $teachingData, $semesterName, $totals)
    {
        $pdfService = new PdfService();

        $html = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; margin: 20px; font-size: 12px; line-height: 1.4; }
            .header { text-align: center; border-bottom: 3px solid #2c3e50; padding-bottom: 15px; margin-bottom: 20px; }
            .university-name { font-size: 20px; font-weight: bold; color: #2c3e50; margin-bottom: 5px; }
            .report-title { font-size: 16px; color: #34495e; margin-bottom: 10px; }
            .faculty-info { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
            .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 20px; }
            .summary-item { text-align: center; padding: 10px; background: white; border-radius: 3px; border: 1px solid #ddd; }
            .summary-number { font-size: 16px; font-weight: bold; color: #2c3e50; }
            .table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            .table th { background: #34495e; color: white; padding: 8px; text-align: left; }
            .table td { padding: 8px; border-bottom: 1px solid #ddd; }
            .table tr:nth-child(even) { background: #f8f9fa; }
            .footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid #ddd; text-align: center; font-size: 10px; color: #7f8c8d; }
        </style>
    </head>
    <body>
        <div class='header'>
            <div class='university-name'>ACADEMIC SCHEDULING SYSTEM</div>
            <div class='report-title'>TEACHING LOAD REPORT</div>
            <div>Semester: {$semesterName}</div>
            <div>Generated on: " . date('F j, Y') . "</div>
        </div>

        <div class='faculty-info'>
            <strong>Faculty:</strong> {$faculty['faculty_name']}<br>
            <strong>Department:</strong> {$faculty['department_name']}<br>
            <strong>Academic Rank:</strong> {$faculty['academic_rank']}
        </div>

        <div class='summary-grid'>
            <div class='summary-item'>
                <div class='summary-number'>{$totals['total_hours']}</div>
                <div>Total Hours</div>
            </div>
            <div class='summary-item'>
                <div class='summary-number'>{$totals['course_count']}</div>
                <div>Courses</div>
            </div>
            <div class='summary-item'>
                <div class='summary-number'>{$totals['lecture_hours']}</div>
                <div>Lecture Hours</div>
            </div>
            <div class='summary-item'>
                <div class='summary-number'>{$totals['lab_hours']}</div>
                <div>Lab Hours</div>
            </div>
        </div>

        <table class='table'>
            <thead>
                <tr>
                    <th>Course Code</th>
                    <th>Course Name</th>
                    <th>Schedule Type</th>
                    <th>Day</th>
                    <th>Time</th>
                    <th>Room</th>
                    <th>Section</th>
                    <th>Hours</th>
                </tr>
            </thead>
            <tbody>";

        foreach ($teachingData as $row) {
            $html .= "
                <tr>
                    <td>{$row['course_code']}</td>
                    <td>{$row['course_name']}</td>
                    <td>{$row['schedule_type']}</td>
                    <td>{$row['day_of_week']}</td>
                    <td>" . date('g:i A', strtotime($row['start_time'])) . " - " . date('g:i A', strtotime($row['end_time'])) . "</td>
                    <td>{$row['room_name']}</td>
                    <td>{$row['section_name']}</td>
                    <td>{$row['duration_hours']}</td>
                </tr>";
        }

        $html .= "
            </tbody>
        </table>

        <div class='footer'>
            <p>Confidential Teaching Load Report - Generated by Academic Scheduling System</p>
        </div>
    </body>
    </html>";

        return $pdfService->generateFromHtml($html);
    }

    /**
     * Generate schedule PDF
     */
    private function generateSchedulePdf($faculty, $scheduleData, $semesterName)
    {
        $pdfService = new PdfService();

        // Group by day
        $scheduleByDay = [];
        foreach ($scheduleData as $class) {
            $day = $class['day_of_week'];
            if (!isset($scheduleByDay[$day])) {
                $scheduleByDay[$day] = [];
            }
            $scheduleByDay[$day][] = $class;
        }

        $html = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; margin: 20px; font-size: 11px; line-height: 1.4; }
            .header { text-align: center; border-bottom: 3px solid #2c3e50; padding-bottom: 15px; margin-bottom: 20px; }
            .university-name { font-size: 20px; font-weight: bold; color: #2c3e50; }
            .faculty-info { margin-bottom: 20px; text-align: center; }
            .day-section { margin-bottom: 25px; page-break-inside: avoid; }
            .day-header { background: #34495e; color: white; padding: 8px 12px; font-weight: bold; margin-bottom: 10px; }
            .class-item { background: #f8f9fa; padding: 10px; margin-bottom: 8px; border-left: 4px solid #3498db; }
            .class-time { font-weight: bold; color: #2c3e50; }
            .class-details { margin-top: 5px; }
            .footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid #ddd; text-align: center; font-size: 10px; color: #7f8c8d; }
        </style>
    </head>
    <body>
        <div class='header'>
            <div class='university-name'>ACADEMIC SCHEDULING SYSTEM</div>
            <div>WEEKLY SCHEDULE REPORT</div>
            <div>Semester: {$semesterName}</div>
        </div>

        <div class='faculty-info'>
            <strong>Faculty:</strong> {$faculty['faculty_name']} | 
            <strong>Department:</strong> {$faculty['department_name']} | 
            <strong>Generated on:</strong> " . date('F j, Y') . "
        </div>";

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        foreach ($days as $day) {
            if (isset($scheduleByDay[$day])) {
                $html .= "
            <div class='day-section'>
                <div class='day-header'>{$day}</div>";

                foreach ($scheduleByDay[$day] as $class) {
                    $html .= "
                <div class='class-item'>
                    <div class='class-time'>
                        " . date('g:i A', strtotime($class['start_time'])) . " - " . date('g:i A', strtotime($class['end_time'])) . "
                        <span style='float: right; background: " . ($class['schedule_type'] === 'Laboratory' ? '#10B981' : '#3B82F6') . "; color: white; padding: 2px 6px; border-radius: 3px; font-size: 9px;'>
                            {$class['schedule_type']}
                        </span>
                    </div>
                    <div class='class-details'>
                        <strong>{$class['course_code']} - {$class['course_name']}</strong><br>
                        Room: {$class['room_name']} | Section: {$class['section_name']} | Students: {$class['current_students']}
                    </div>
                </div>";
                }

                $html .= "
            </div>";
            }
        }

        $html .= "
        <div class='footer'>
            <p>Weekly Schedule Report - Academic Scheduling System</p>
        </div>
    </body>
    </html>";

        return $pdfService->generateFromHtml($html);
    }

    /**
     * Generate specialization PDF
     */
    private function generateSpecializationPdf($faculty, $specializations)
    {
        $pdfService = new PdfService();

        $html = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; margin: 20px; font-size: 12px; line-height: 1.4; }
            .header { text-align: center; border-bottom: 3px solid #2c3e50; padding-bottom: 15px; margin-bottom: 20px; }
            .university-name { font-size: 20px; font-weight: bold; color: #2c3e50; }
            .faculty-info { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
            .table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            .table th { background: #34495e; color: white; padding: 8px; text-align: left; }
            .table td { padding: 8px; border-bottom: 1px solid #ddd; }
            .expertise-badge { padding: 2px 6px; border-radius: 3px; font-size: 10px; font-weight: bold; }
            .expertise-beginner { background: #dbeafe; color: #1e40af; }
            .expertise-intermediate { background: #fef3c7; color: #d97706; }
            .expertise-expert { background: #dcfce7; color: #16a34a; }
            .footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid #ddd; text-align: center; font-size: 10px; color: #7f8c8d; }
        </style>
    </head>
    <body>
        <div class='header'>
            <div class='university-name'>ACADEMIC SCHEDULING SYSTEM</div>
            <div>SUBJECT SPECIALIZATION REPORT</div>
            <div>Generated on: " . date('F j, Y') . "</div>
        </div>

        <div class='faculty-info'>
            <strong>Faculty:</strong> {$faculty['faculty_name']}<br>
            <strong>Department:</strong> {$faculty['department_name']}<br>
            <strong>Academic Rank:</strong> {$faculty['academic_rank']}<br>
            <strong>Employment Type:</strong> {$faculty['employment_type']}
        </div>

        <table class='table'>
            <thead>
                <tr>
                    <th>Course Code</th>
                    <th>Course Name</th>
                    <th>Units</th>
                    <th>Department</th>
                    <th>Expertise Level</th>
                    <th>Date Added</th>
                </tr>
            </thead>
            <tbody>";

        foreach ($specializations as $spec) {
            $expertiseClass = 'expertise-' . strtolower($spec['expertise_level']);
            $html .= "
                <tr>
                    <td>{$spec['course_code']}</td>
                    <td>{$spec['course_name']}</td>
                    <td>{$spec['units']}</td>
                    <td>{$spec['department_name']}</td>
                    <td><span class='expertise-badge {$expertiseClass}'>{$spec['expertise_level']}</span></td>
                    <td>" . date('M j, Y', strtotime($spec['created_at'])) . "</td>
                </tr>";
        }

        $html .= "
            </tbody>
        </table>

        <div class='summary' style='margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;'>
            <strong>Summary:</strong> Total " . count($specializations) . " specializations recorded
        </div>

        <div class='footer'>
            <p>Specialization Report - Academic Scheduling System</p>
        </div>
    </body>
    </html>";

        return $pdfService->generateFromHtml($html);
    }

    /**
     * Simple Excel/CSV generation methods (basic implementation)
     */
    private function generateTeachingLoadExcel($faculty, $teachingData, $semesterName, $totals)
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="teaching_load_' . $faculty['faculty_name'] . '_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        // Headers
        fputcsv($output, ['Teaching Load Report - ' . $semesterName]);
        fputcsv($output, ['Faculty: ' . $faculty['faculty_name']]);
        fputcsv($output, ['Department: ' . $faculty['department_name']]);
        fputcsv($output, ['Generated: ' . date('F j, Y')]);
        fputcsv($output, []); // Empty row

        // Column headers
        fputcsv($output, ['Course Code', 'Course Name', 'Schedule Type', 'Day', 'Start Time', 'End Time', 'Room', 'Section', 'Hours']);

        // Data
        foreach ($teachingData as $row) {
            fputcsv($output, [
                $row['course_code'],
                $row['course_name'],
                $row['schedule_type'],
                $row['day_of_week'],
                $row['start_time'],
                $row['end_time'],
                $row['room_name'],
                $row['section_name'],
                $row['duration_hours']
            ]);
        }

        fputcsv($output, []); // Empty row
        fputcsv($output, ['Total Hours:', $totals['total_hours']]);
        fputcsv($output, ['Total Courses:', $totals['course_count']]);

        fclose($output);
        exit;
    }


    /**
     * Generate Schedule Excel/CSV
     */
    private function generateScheduleExcel($faculty, $scheduleData, $semesterName)
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="schedule_' . preg_replace('/[^a-zA-Z0-9]/', '_', $faculty['faculty_name']) . '_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        // Headers and metadata
        fputcsv($output, ['WEEKLY SCHEDULE REPORT - ' . strtoupper($semesterName)]);
        fputcsv($output, ['Faculty: ' . $faculty['faculty_name']]);
        fputcsv($output, ['Department: ' . $faculty['department_name']]);
        fputcsv($output, ['Academic Rank: ' . $faculty['academic_rank']]);
        fputcsv($output, ['Generated: ' . date('F j, Y g:i A')]);
        fputcsv($output, []); // Empty row

        // Group by day for better organization
        $scheduleByDay = [];
        foreach ($scheduleData as $class) {
            $day = $class['day_of_week'];
            if (!isset($scheduleByDay[$day])) {
                $scheduleByDay[$day] = [];
            }
            $scheduleByDay[$day][] = $class;
        }

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        foreach ($days as $day) {
            if (isset($scheduleByDay[$day]) && !empty($scheduleByDay[$day])) {
                fputcsv($output, [strtoupper($day)]);
                fputcsv($output, ['Time', 'Course Code', 'Course Name', 'Schedule Type', 'Room', 'Section', 'Year Level', 'Students', 'Duration']);

                foreach ($scheduleByDay[$day] as $class) {
                    $startTime = date('g:i A', strtotime($class['start_time']));
                    $endTime = date('g:i A', strtotime($class['end_time']));
                    $duration = number_format($class['duration_hours'], 1) . ' hrs';

                    fputcsv($output, [
                        $startTime . ' - ' . $endTime,
                        $class['course_code'],
                        $class['course_name'],
                        $class['schedule_type'],
                        $class['room_name'],
                        $class['section_name'],
                        $class['year_level'] ?? 'N/A',
                        $class['current_students'] ?? '0',
                        $duration
                    ]);
                }
                fputcsv($output, []); // Empty row between days
            }
        }

        // Summary section
        fputcsv($output, ['SCHEDULE SUMMARY']);
        fputcsv($output, ['Total Classes:', count($scheduleData)]);

        $totalHours = array_sum(array_column($scheduleData, 'duration_hours'));
        fputcsv($output, ['Total Hours:', number_format($totalHours, 1)]);

        $lectureHours = array_sum(array_map(function ($class) {
            return $class['schedule_type'] === 'Lecture' ? $class['duration_hours'] : 0;
        }, $scheduleData));
        fputcsv($output, ['Lecture Hours:', number_format($lectureHours, 1)]);

        $labHours = array_sum(array_map(function ($class) {
            return $class['schedule_type'] === 'Laboratory' ? $class['duration_hours'] : 0;
        }, $scheduleData));
        fputcsv($output, ['Laboratory Hours:', number_format($labHours, 1)]);

        // Unique courses
        $uniqueCourses = array_unique(array_column($scheduleData, 'course_code'));
        fputcsv($output, ['Unique Courses:', count($uniqueCourses)]);

        fclose($output);
        exit;
    }

    /**
     * Generate Specialization Excel/CSV
     */
    private function generateSpecializationExcel($faculty, $specializations)
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="specializations_' . preg_replace('/[^a-zA-Z0-9]/', '_', $faculty['faculty_name']) . '_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        // Headers and metadata
        fputcsv($output, ['SUBJECT SPECIALIZATION REPORT']);
        fputcsv($output, ['Faculty: ' . $faculty['faculty_name']]);
        fputcsv($output, ['Department: ' . $faculty['department_name']]);
        fputcsv($output, ['Academic Rank: ' . $faculty['academic_rank']]);
        fputcsv($output, ['Employment Type: ' . $faculty['employment_type']]);
        fputcsv($output, ['Generated: ' . date('F j, Y g:i A')]);
        fputcsv($output, []); // Empty row

        // Column headers
        fputcsv($output, ['Course Code', 'Course Name', 'Units', 'Department', 'Expertise Level', 'Date Added', 'Last Updated']);

        // Data rows
        foreach ($specializations as $spec) {
            fputcsv($output, [
                $spec['course_code'],
                $spec['course_name'],
                $spec['units'],
                $spec['department_name'],
                $spec['expertise_level'],
                date('M j, Y', strtotime($spec['created_at'])),
                $spec['updated_at'] ? date('M j, Y', strtotime($spec['updated_at'])) : 'Never'
            ]);
        }

        fputcsv($output, []); // Empty row

        // Summary and statistics
        fputcsv($output, ['SPECIALIZATION SUMMARY']);
        fputcsv($output, ['Total Specializations:', count($specializations)]);

        // Count by expertise level
        $expertiseCounts = [];
        foreach ($specializations as $spec) {
            $level = $spec['expertise_level'];
            if (!isset($expertiseCounts[$level])) {
                $expertiseCounts[$level] = 0;
            }
            $expertiseCounts[$level]++;
        }

        foreach ($expertiseCounts as $level => $count) {
            fputcsv($output, [$level . ' Level Specializations:', $count]);
        }

        // Department distribution
        $departmentCounts = [];
        foreach ($specializations as $spec) {
            $dept = $spec['department_name'];
            if (!isset($departmentCounts[$dept])) {
                $departmentCounts[$dept] = 0;
            }
            $departmentCounts[$dept]++;
        }

        fputcsv($output, []); // Empty row
        fputcsv($output, ['DEPARTMENT DISTRIBUTION']);
        foreach ($departmentCounts as $dept => $count) {
            fputcsv($output, [$dept . ':', $count]);
        }

        // Units summary
        $totalUnits = array_sum(array_column($specializations, 'units'));
        fputcsv($output, []); // Empty row
        fputcsv($output, ['Total Units Across All Specializations:', $totalUnits]);

        $averageUnits = count($specializations) > 0 ? $totalUnits / count($specializations) : 0;
        fputcsv($output, ['Average Units Per Course:', number_format($averageUnits, 1)]);

        fclose($output);
        exit;
    }

    /**
     * Enhanced Excel generation with multiple format options
     */
    private function generateEnhancedScheduleExcel($faculty, $scheduleData, $semesterName, $format = 'csv')
    {
        if ($format === 'csv') {
            $this->generateScheduleExcel($faculty, $scheduleData, $semesterName);
            return;
        }

        // For actual Excel format (if you want to implement PHPExcel or PhpSpreadsheet later)
        // This is a placeholder for future enhancement
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="schedule_' . preg_replace('/[^a-zA-Z0-9]/', '_', $faculty['faculty_name']) . '_' . date('Y-m-d') . '.xls"');

        // Simple HTML table that Excel can open
        echo "<table border='1'>";
        echo "<tr><th colspan='9'>WEEKLY SCHEDULE REPORT - " . strtoupper($semesterName) . "</th></tr>";
        echo "<tr><td colspan='9'><strong>Faculty:</strong> " . $faculty['faculty_name'] . " | <strong>Department:</strong> " . $faculty['department_name'] . "</td></tr>";
        echo "<tr><td colspan='9'><strong>Generated:</strong> " . date('F j, Y g:i A') . "</td></tr>";
        echo "<tr><td colspan='9'></td></tr>";

        $scheduleByDay = [];
        foreach ($scheduleData as $class) {
            $day = $class['day_of_week'];
            if (!isset($scheduleByDay[$day])) {
                $scheduleByDay[$day] = [];
            }
            $scheduleByDay[$day][] = $class;
        }

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        foreach ($days as $day) {
            if (isset($scheduleByDay[$day]) && !empty($scheduleByDay[$day])) {
                echo "<tr><td colspan='9' style='background:#34495e;color:white;font-weight:bold;'>" . strtoupper($day) . "</td></tr>";
                echo "<tr style='background:#f8f9fa;font-weight:bold;'>";
                echo "<td>Time</td><td>Course Code</td><td>Course Name</td><td>Type</td><td>Room</td><td>Section</td><td>Year Level</td><td>Students</td><td>Duration</td>";
                echo "</tr>";

                foreach ($scheduleByDay[$day] as $class) {
                    $startTime = date('g:i A', strtotime($class['start_time']));
                    $endTime = date('g:i A', strtotime($class['end_time']));
                    $duration = number_format($class['duration_hours'], 1) . ' hrs';

                    echo "<tr>";
                    echo "<td>" . $startTime . ' - ' . $endTime . "</td>";
                    echo "<td>" . $class['course_code'] . "</td>";
                    echo "<td>" . $class['course_name'] . "</td>";
                    echo "<td>" . $class['schedule_type'] . "</td>";
                    echo "<td>" . $class['room_name'] . "</td>";
                    echo "<td>" . $class['section_name'] . "</td>";
                    echo "<td>" . ($class['year_level'] ?? 'N/A') . "</td>";
                    echo "<td>" . ($class['current_students'] ?? '0') . "</td>";
                    echo "<td>" . $duration . "</td>";
                    echo "</tr>";
                }
                echo "<tr><td colspan='9'></td></tr>";
            }
        }

        echo "</table>";
        exit;
    }

    /**
     * Enhanced Specialization Excel with multiple format options
     */
    private function generateEnhancedSpecializationExcel($faculty, $specializations, $format = 'csv')
    {
        if ($format === 'csv') {
            $this->generateSpecializationExcel($faculty, $specializations);
            return;
        }

        // For actual Excel format
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="specializations_' . preg_replace('/[^a-zA-Z0-9]/', '_', $faculty['faculty_name']) . '_' . date('Y-m-d') . '.xls"');

        echo "<table border='1'>";
        echo "<tr><th colspan='7'>SUBJECT SPECIALIZATION REPORT</th></tr>";
        echo "<tr><td colspan='7'><strong>Faculty:</strong> " . $faculty['faculty_name'] . " | <strong>Department:</strong> " . $faculty['department_name'] . "</td></tr>";
        echo "<tr><td colspan='7'><strong>Academic Rank:</strong> " . $faculty['academic_rank'] . " | <strong>Employment Type:</strong> " . $faculty['employment_type'] . "</td></tr>";
        echo "<tr><td colspan='7'><strong>Generated:</strong> " . date('F j, Y g:i A') . "</td></tr>";
        echo "<tr><td colspan='7'></td></tr>";

        // Headers
        echo "<tr style='background:#34495e;color:white;font-weight:bold;'>";
        echo "<td>Course Code</td><td>Course Name</td><td>Units</td><td>Department</td><td>Expertise Level</td><td>Date Added</td><td>Last Updated</td>";
        echo "</tr>";

        // Data
        foreach ($specializations as $spec) {
            $expertiseStyle = '';
            switch (strtolower($spec['expertise_level'])) {
                case 'beginner':
                    $expertiseStyle = 'background:#dbeafe;';
                    break;
                case 'intermediate':
                    $expertiseStyle = 'background:#fef3c7;';
                    break;
                case 'expert':
                    $expertiseStyle = 'background:#dcfce7;';
                    break;
            }

            echo "<tr>";
            echo "<td>" . $spec['course_code'] . "</td>";
            echo "<td>" . $spec['course_name'] . "</td>";
            echo "<td>" . $spec['units'] . "</td>";
            echo "<td>" . $spec['department_name'] . "</td>";
            echo "<td style='" . $expertiseStyle . "'>" . $spec['expertise_level'] . "</td>";
            echo "<td>" . date('M j, Y', strtotime($spec['created_at'])) . "</td>";
            echo "<td>" . ($spec['updated_at'] ? date('M j, Y', strtotime($spec['updated_at'])) : 'Never') . "</td>";
            echo "</tr>";
        }

        echo "</table>";
        exit;
    }

    /**
     * Get report data for AJAX requests
     */
    public function getReportData()
    {
        try {
            if (!$this->authService->isLoggedIn()) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                exit;
            }

            $userId = $_SESSION['user_id'];
            $facultyId = $this->getFacultyId($userId);
            $reportType = $_GET['report_type'] ?? '';

            if (!$facultyId) {
                http_response_code(404);
                echo json_encode(['error' => 'Faculty profile not found']);
                exit;
            }

            switch ($reportType) {
                case 'teaching_stats':
                    echo json_encode($this->getTeachingStats($facultyId));
                    break;
                case 'schedule_summary':
                    echo json_encode($this->getScheduleSummary($facultyId));
                    break;
                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid report type']);
            }
        } catch (Exception $e) {
            error_log("getReportData: Error - " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch report data']);
        }
        exit;
    }

    /**
     * Get teaching statistics for charts
     */
    private function getTeachingStats($facultyId)
    {
        $statsStmt = $this->db->prepare("
        SELECT 
            s.schedule_type,
            COUNT(*) as class_count,
            SUM(TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time) / 60) as total_hours,
            AVG(sec.current_students) as avg_students
        FROM schedules s
        LEFT JOIN sections sec ON s.section_id = sec.section_id
        WHERE s.faculty_id = ? AND s.semester_id = (SELECT semester_id FROM semesters WHERE is_current = 1)
        GROUP BY s.schedule_type
    ");
        $statsStmt->execute([$facultyId]);
        return $statsStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get schedule summary for dashboard
     */
    private function getScheduleSummary($facultyId)
    {
        $summaryStmt = $this->db->prepare("
            SELECT 
                s.day_of_week,
                COUNT(*) as class_count,
                SUM(TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time) / 60) as total_hours
            FROM schedules s
            WHERE s.faculty_id = ? AND s.semester_id = (SELECT semester_id FROM semesters WHERE is_current = 1)
            GROUP BY s.day_of_week
            ORDER BY FIELD(s.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')
        ");
        $summaryStmt->execute([$facultyId]);
        return $summaryStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Display reports dashboard
     */
    public function reports()
    {
        try {
            $userId = $_SESSION['user_id'];
            $facultyId = $this->getFacultyId($userId);

            if (!$facultyId) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'No faculty profile found'];
                header('Location: /faculty/dashboard');
                exit;
            }

            // Get current semester
            $semesterStmt = $this->db->query("SELECT semester_name, academic_year FROM semesters WHERE is_current = 1");
            $currentSemester = $semesterStmt->fetch(PDO::FETCH_ASSOC);
            $currentSemester = $currentSemester ? "{$currentSemester['semester_name']} {$currentSemester['academic_year']}" : 'Current Semester';

            // Get today's schedule
            $today = date('l');
            $todayScheduleStmt = $this->db->prepare("
            SELECT 
                c.course_code,
                c.course_name,
                s.start_time,
                s.end_time,
                s.schedule_type,
                r.room_name,
                sec.section_name
            FROM schedules s
            JOIN courses c ON s.course_id = c.course_id
            LEFT JOIN sections sec ON s.section_id = sec.section_id
            LEFT JOIN classrooms r ON s.room_id = r.room_id
            WHERE s.faculty_id = ? AND s.day_of_week = ?
            ORDER BY s.start_time
        ");
            $todayScheduleStmt->execute([$facultyId, $today]);
            $todaySchedule = $todayScheduleStmt->fetchAll(PDO::FETCH_ASSOC);

            // Get teaching statistics
            $statsStmt = $this->db->prepare("
            SELECT 
                COUNT(DISTINCT s.course_id) as course_count,
                COUNT(DISTINCT s.schedule_id) as class_count,
                SUM(TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time) / 60) as total_hours,
                (SELECT COUNT(*) FROM specializations WHERE faculty_id = ?) as specializations_count,
                (SELECT SUM(sec.current_students) 
                 FROM schedules s2 
                 LEFT JOIN sections sec ON s2.section_id = sec.section_id 
                 WHERE s2.faculty_id = ?) as total_students
            FROM schedules s
            WHERE s.faculty_id = ? AND s.semester_id = (SELECT semester_id FROM semesters WHERE is_current = 1)
        ");
            $statsStmt->execute([$facultyId, $facultyId, $facultyId]);
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

            // Pass data to view
            $totalHours = $stats['total_hours'] ?? 0;
            $courseCount = $stats['course_count'] ?? 0;
            $specializationsCount = $stats['specializations_count'] ?? 0;
            $totalStudents = $stats['total_students'] ?? 0;

            require_once __DIR__ . '/../views/faculty/reports.php';
        } catch (Exception $e) {
            error_log("reports: Error - " . $e->getMessage());
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to load reports dashboard'];
            header('Location: /faculty/dashboard');
            exit;
        }
    }
}
