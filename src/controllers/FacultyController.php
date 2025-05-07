<?php
// File: controllers/FacultyController.php
require_once __DIR__ . '/../config/Database.php';

class FacultyController
{
    private $db;

    public function __construct()
    {
        error_log("FacultyController instantiated");
        $this->db = (new Database())->connect();
        if ($this->db === null) {
            error_log("Failed to connect to the database in FacultyController");
            die("Database connection failed. Please try again later.");
        }
        $this->restrictToFaculty();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    private function restrictToFaculty()
    {
        error_log("restrictToFaculty: Checking session - user_id: " . ($_SESSION['user_id'] ?? 'none') . ", role_id: " . ($_SESSION['role_id'] ?? 'none'));
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 6) {
            error_log("restrictToFaculty: Redirecting to login due to unauthorized access");
            header('Location: /login?error=Unauthorized access');
            exit;
        }
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
                  SELECT s.schedule_id, c.course_code, r.room_name, s.day_of_week, s.start_time, s.end_time, s.schedule_type
                  FROM schedules s
                  JOIN courses c ON s.course_id = c.course_id
                  LEFT JOIN classrooms r ON s.room_id = r.room_id
                  WHERE s.faculty_id = :faculty_id
                  ORDER BY s.created_at DESC
                  LIMIT 5
              ");
            $recentSchedulesStmt->execute([':faculty_id' => $facultyId]);
            $recentSchedules = $recentSchedulesStmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("dashboard: Fetched " . count($recentSchedules) . " recent schedules for faculty_id $facultyId");

            $scheduleDistStmt = $this->db->prepare("
                  SELECT s.day_of_week, COUNT(*) as count 
                  FROM schedules s 
                  WHERE s.faculty_id = :faculty_id 
                  GROUP BY s.day_of_week
              ");
            $scheduleDistStmt->execute([':faculty_id' => $facultyId]);
            $scheduleDistData = $scheduleDistStmt->fetchAll(PDO::FETCH_ASSOC);

            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $scheduleDist = array_fill_keys($days, 0);
            foreach ($scheduleDistData as $row) {
                $scheduleDist[$row['day_of_week']] = (int)$row['count'];
            }
            $scheduleDistJson = json_encode(array_values($scheduleDist));

            // Pass variables to the view instead of a single $content string
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
        error_log("mySchedule: Starting mySchedule method");
        ob_start();
        try {
            $userId = $_SESSION['user_id'];
            $facultyId = $this->getFacultyId($userId);
            error_log("mySchedule: Faculty ID for user $userId is $facultyId");
            if (!$facultyId) {
                error_log("mySchedule: No faculty profile found for user_id: $userId");
                $error = "No faculty profile found for this user.";
                require_once __DIR__ . '/../views/faculty/my_schedule.php';
                $content = ob_get_clean();
                require_once __DIR__ . '/../views/faculty/layout.php';
                return;
            }

            // Get current semester
            $semesterStmt = $this->db->query("SELECT semester_id, semester_name, academic_year FROM semesters WHERE is_current = 1");
            $semester = $semesterStmt->fetch(PDO::FETCH_ASSOC);
            if (!$semester) {
                error_log("mySchedule: No current semester found");
                $error = "No current semester defined. Please contact the administrator to set the current semester.";
                require_once __DIR__ . '/../views/faculty/my_schedule.php';
                $content = ob_get_clean();
                require_once __DIR__ . '/../views/faculty/layout.php';
                return;
            }
            $semesterId = $semester['semester_id'];
            $semesterName = $semester['semester_name'] . ' Semester, AY ' . $semester['academic_year'];
            error_log("mySchedule: Current semester ID: $semesterId, Name: $semesterName");

            // Fetch department name
            $deptStmt = $this->db->prepare("SELECT d.department_name FROM departments d JOIN users u ON d.department_id = u.department_id WHERE u.user_id = :user_id");
            $deptStmt->execute([':user_id' => $userId]);
            $departmentName = $deptStmt->fetchColumn() ?: 'Unknown Department';

            // Fetch schedules with section name, handling potential NULL joins
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

            // If no schedules found for the current semester, try fetching all schedules as a fallback
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

            // Verify schedule data
            if (empty($schedules)) {
                error_log("mySchedule: No schedules found after fallback. Checking raw data...");
                $debugStmt = $this->db->prepare("SELECT * FROM schedules WHERE faculty_id = :faculty_id");
                $debugStmt->execute([':faculty_id' => $facultyId]);
                $debugSchedules = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("mySchedule: Debug - Found " . count($debugSchedules) . " raw schedules for faculty_id $facultyId");
            }

            // Calculate total weekly hours
            $totalHours = 0;
            foreach ($schedules as $schedule) {
                $totalHours += $schedule['duration_hours'];
            }
            error_log("mySchedule: Total hours calculated: $totalHours");

            // Pass data to the view
            require_once __DIR__ . '/../views/faculty/my_schedule.php';
            $content = ob_get_clean();
            require_once __DIR__ . '/../views/faculty/layout.php';
        } catch (Exception $e) {
            error_log("mySchedule: Error - " . $e->getMessage());
            $error = "Failed to load schedule due to an error: " . htmlspecialchars($e->getMessage());
            require_once __DIR__ . '/../views/faculty/my_schedule.php';
            $content = ob_get_clean();
            require_once __DIR__ . '/../views/faculty/layout.php';
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
    public function profile()
    {
        try {
            $userId = $_SESSION['user_id'];
            $facultyId = $this->getFacultyId($userId);

            if (!$facultyId) {
                error_log("profile: No faculty profile found for user_id: $userId");
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Faculty profile not found'];
                header('Location: /faculty/dashboard');
                exit;
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (isset($_POST['update_profile'])) {
                    $firstName = trim($_POST['first_name'] ?? '');
                    $lastName = trim($_POST['last_name'] ?? '');
                    $email = trim($_POST['email'] ?? '');
                    $phone = trim($_POST['phone'] ?? '');

                    if (!empty($firstName) && !empty($lastName) && !empty($email)) {
                        // Update user table
                        $stmt = $this->db->prepare("UPDATE users 
                        SET first_name = :first_name, 
                            last_name = :last_name, 
                            email = :email, 
                            phone = :phone 
                        WHERE user_id = :user_id");
                        $stmt->execute([
                            ':first_name' => $firstName,
                            ':last_name' => $lastName,
                            ':email' => $email,
                            ':phone' => $phone,
                            ':user_id' => $userId
                        ]);

                        // Update session data
                        $_SESSION['user_id']['first_name'] = $firstName;
                        $_SESSION['user_id']['last_name'] = $lastName;
                        $_SESSION['user_id']['email'] = $email;

                        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Profile updated successfully'];
                    } else {
                        $_SESSION['flash'] = ['type' => 'error', 'message' => 'All required fields must be filled'];
                    }
                } elseif (isset($_POST['add_specialization'])) {
                    $subjectName = trim($_POST['subject_name'] ?? '');
                    $expertiseLevel = $_POST['expertise_level'] ?? 'Intermediate';

                    if (!empty($subjectName)) {
                        $stmt = $this->db->prepare("INSERT INTO specializations 
                        (faculty_id, subject_name, expertise_level) 
                        VALUES (:faculty_id, :subject_name, :expertise_level)");
                        $stmt->execute([
                            ':faculty_id' => $facultyId,
                            ':subject_name' => $subjectName,
                            ':expertise_level' => $expertiseLevel
                        ]);
                        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Specialization added successfully'];
                    } else {
                        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Subject name is required'];
                    }
                } elseif (isset($_POST['delete_specialization'])) {
                    $specializationId = (int)($_POST['specialization_id'] ?? 0);
                    if ($specializationId > 0) {
                        $stmt = $this->db->prepare("DELETE FROM specializations 
                        WHERE specialization_id = :specialization_id 
                        AND faculty_id = :faculty_id");
                        $stmt->execute([
                            ':specialization_id' => $specializationId,
                            ':faculty_id' => $facultyId
                        ]);

                        if ($stmt->rowCount() > 0) {
                            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Specialization deleted successfully'];
                        } else {
                            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Specialization not found or already removed'];
                        }
                    }
                }

                header('Location: /faculty/profile');
                exit;
            }

            // Get faculty data
            $stmt = $this->db->prepare("
            SELECT u.*, f.*, d.department_name 
            FROM users u
            JOIN faculty f ON u.user_id = f.user_id
            JOIN departments d ON f.department_id = d.department_id
            WHERE f.faculty_id = :faculty_id
        ");
            $stmt->execute([':faculty_id' => $facultyId]);
            $faculty = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get specializations
            $stmt = $this->db->prepare("
            SELECT * FROM specializations 
            WHERE faculty_id = :faculty_id
            ORDER BY subject_name
        ");
            $stmt->execute([':faculty_id' => $facultyId]);
            $specializations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Load view
            require_once __DIR__ . '/../views/faculty/profile.php';
        } catch (PDOException $e) {
            error_log("Profile error (PDO): " . $e->getMessage());
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
            header('Location: /faculty/profile');
            exit;
        } catch (Exception $e) {
            error_log("Profile error: " . $e->getMessage());
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'An error occurred'];
            header('Location: /faculty/profile');
            exit;
        }
    }
}
